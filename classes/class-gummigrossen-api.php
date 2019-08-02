<?php
defined( 'ABSPATH' ) or exit;

/**
 * Class to connect with Gummigrossen's API
 */
class Gummigrossen_API
{
    const TRANSIENT_TOKEN      = 'gummigrossen-api-token';
    const TRANSIENT_TOKEN_TYPE = 'gummigrossen-api-token-type';
    const API_URL              = 'http://api.gummigrossen.se/';
    const API_USER             = 'Enter your API user here';
    const API_PASS             = 'Enter your API password here';
    const SESSION_KEY_SUCCESS  = 'gummigrossen-success';
    const SESSION_KEY_ERROR    = 'gummigrossen-errors';

    public $token;
    public $tokenType;
    public $articles;
    public $authorizationHeader;
    public $error;

    public function __construct() {
        if ( false === ( $this->token = get_transient( self::TRANSIENT_TOKEN ) ) ) {
            $this->authenticate();
        } else {
            $this->tokenType = get_transient( self::TRANSIENT_TOKEN_TYPE );
        }

        $this->authorizationHeader = $this->tokenType . ' ' . $this->token;
    }

    public function authenticate() {
        $path = self::API_URL . 'Token';

        $data = array(
            'grant_type' => password,
            'username' => self::API_USER,
            'password' => self::API_PASS,
        );

        try {
            $response = \Httpful\Request::post( $path )
                ->sendsType( \Httpful\Mime::FORM )
                ->body( $data )
                ->send();
        } catch ( Exception $e ) {
            $this->error = $e->getMessage();

            return false;
        }

        if ( 200 === $response->code ) {
            $this->token = $response->body->access_token;
            $this->tokenType = $response->body->token_type;

            set_transient( self::TRANSIENT_TOKEN, $this->token, $response->body->expires_in );
            set_transient( self::TRANSIENT_TOKEN_TYPE, $this->tokenType, $response->body->expires_in );
        } else {
            $this->error = "Couldn't authenticate";

            return false;
        }

        return true;
    }

    public function loadArticles() {
        $path = self::API_URL . 'api/Articles';
        $params = array(
            'IncludeAlloyRims' => 'True',
            'IncludeSteelRims' => 'True',
            'IncludeAccessories' => 'True',
        );

        $fullPath = sprintf( "%s?%s", $path, http_build_query( $params ) );

        try {
            $response = \Httpful\Request::get( $fullPath )
                ->addHeader( 'Authorization', $this->authorizationHeader )
                ->addHeader( 'X-Requested-With', 'XMLHttpRequest' )
                ->sendsType( \Httpful\Mime::FORM )
                ->send();

        } catch ( Exception $e ) {
            $this->error = $e->getMessage();

            return false;
        }

        if ( $response->code === 200 ) {
            $this->articles = $response->body;

            return true;
        } else {
            $this->error = $response->body->Message;

            return false;
        }

        return true;
    }

    public function generateXML() {
        if ( ! is_array( $this->articles ) ) {
            $this->articles = [ $this->articles ];
        }

        $xmlData = '<document>';
        foreach ( $this->articles as $article ) {
            $stock_status = $article->QuantityAvailable > 0 ? 'instock' : 'outofstock';

            $xmlData .= '<product>';
            $xmlData .= '<title>' . $this->escapeChars( $article->ArticleText ) . '</title>';
            $xmlData .= '<price>' . $this->escapeChars( $article->Price ) . '</price>';
            $xmlData .= '<sku>' . $this->escapeChars( $article->EAN ) . '</sku>';
            $xmlData .= '<stock>' . $this->escapeChars( $article->QuantityAvailable ) . '</stock>';
            $xmlData .= '<stock_status>' . $stock_status . '</stock_status>';
            $xmlData .= '<description>' . $this->escapeChars( $article->Extra ) . '</description>';
            $xmlData .= '<category>' . $this->escapeChars( $article->MainGroupName ) . '</category>';
            $xmlData .= '<brand>' . $this->escapeChars( $article->BrandName ) . '</brand>';
            $xmlData .= '<ArticleID>' . $this->escapeChars( $article->ArticleId ) . '</ArticleID>';
            $xmlData .= '<ImageId>' . $this->escapeChars( $article->ImageId ) . '</ImageId>';
            $xmlData .= '</product>';
        }
        $xmlData .= '</document>';

        try {
            $xml = new SimpleXMLElement( $xmlData );
        } catch ( Exception $e ) {
            echo $e->getMessage();
            die;
        }

        return $xml->asXML();
    }

    protected function escapeChars( $string ) {
        return htmlspecialchars( $string, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
    }

    public function importImages() {
        $imported = 0;
        $offset = 0;
        $posts_per_page = 30;

        do {
            $products = get_posts( array(
                'posts_per_page' => $posts_per_page,
                'offset' => $offset,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_type' => 'product',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => WC_Gummigrossen::META_KEY_IMG,
                        'compare' => 'EXISTS'
                    ),
                ),
            ) );

            if ( is_array( $products ) && count( $products ) > 0 ) {

                $this->error = '';
                $upload_dir = wp_upload_dir();

                foreach ( $products as $product ) {
                    $articleImageId = get_post_meta( $product->ID, WC_Gummigrossen::META_KEY_IMG, true );

                    if ( $articleImageId ) {
                        $imageContent = $this->getArticleImage( $articleImageId );

                        if ( $imageContent ) {
                            $name = sprintf( "ArticleImage-%s.jpg", $product->ID );
                            $filename = sprintf( "%s%s", trailingslashit( $upload_dir['path'] ), $name );

                            if ( file_put_contents( $filename, $imageContent ) ) {

                                $attachment = array(
                                    'guid' => $filename,
                                    'post_mime_type' => 'image/jpeg',
                                    'post_title' => $name,
                                    'post_content' => '',
                                    'post_status' => 'inherit',
                                );

                                if ( $attach_id = wp_insert_attachment( $attachment, $filename, $product->ID ) ) {
                                    if ( set_post_thumbnail( $product->ID, $attach_id ) ) {
                                        $imported++;
                                    } else {
                                        $this->error .= 'Image ' . $filename . ' not set as feature for product #' . $product->ID . "\n";
                                    }
                                } else {
                                    $this->error .= 'Image ' . $filename . ' downloaded but not attached to the product #' . $product->ID . "\n";
                                }
                            } else {

                                $this->error .= 'Image ' . $filename . " not saved\n";

                            }
                        }
                    }
                } // End foreach().
            } else {
                break;
            } // End if().

            $offset += $posts_per_page;

        } while ( count( $products ) > 0 );

        $_SESSION[ self::SESSION_KEY_SUCCESS ] = $imported . ' images imported.';
        $_SESSION[ self::SESSION_KEY_ERROR ] = $this->error;
    }

    public function getArticleImage( $id ) {
        $path = self::API_URL . 'api/ArticleImages/' . $id;

        try {
            $response = \Httpful\Request::get( $path )
                ->addHeader( 'Authorization', $this->authorizationHeader )
                ->addHeader( 'X-Requested-With', 'XMLHttpRequest' )
                ->sendsType( \Httpful\Mime::FORM )
                ->send();

        } catch ( Exception $e ) {
            $this->error = $e->getMessage();

            return null;
        }

        if ( $response->code === 200 ) {
            return $response->body;
        } else {
            $this->error = $response->body->Message;

            return null;
        }
    }
}
