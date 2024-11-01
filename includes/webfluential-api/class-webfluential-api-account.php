<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_once( 'abstract-webfluential-api.php' );

class Webfluential_API_Account extends Webfluential_API {

	private $args = array();

	public function __construct( ) {}

	public function webfluential_create_account( $args ) {
		Webfluential_WC()->log_msg( 'Create Account Arguments : ' . print_r( $args, true ) );

		$callBackUrl = WEBFLUENTIAL_PLUGIN_DIR_URL . '/includes/webfluential-api/webfluential-api-auth-callback.php';

		if( empty( $args['redirect_url'] ) ) {
			throw new Exception( __('No redirect url was passed', 'webfluential-wc') );
		}

		try{
		    $wf = new Webfluential_API_SDK([
		        'apiKey' => WEBFLUENTIAL_API_KEY,
		        'apiSecret' => WEBFLUENTIAL_API_SECRET,
		        'apiCallback' => $args['redirect_url'],
		    ]);
		    
		    $company = $args['company'] ? $args['company'] : '';
		    $name = $args['name'] ? $args['name'] : '';
		    $email = $args['email'] ? $args['email'] : '';

		    $logUrl = $wf->getLoginUrl( ['woocommerce.search'], $company, $email, $name );
		    
		}catch (Exception $e){
			throw $e;
		}
		
		Webfluential_WC()->log_msg( 'Log URL : ' . $logUrl );

		return $logUrl;
	}

	public function webfluential_get_token( $args ) {
		Webfluential_WC()->log_msg( 'Token Arguments : ' . print_r( $args, true ) );

		//If code was sent back, then it was successful
		if (! empty($args['code'])) {
		    //Now get the access token
		    try{
		        $wf = new Webfluential_API_SDK([
		            'apiKey' => WEBFLUENTIAL_API_KEY,
		            'apiSecret' => WEBFLUENTIAL_API_SECRET,
		            'apiCallback' => $args['redirect_url'],
		        ]);
		        $accessToken = $wf->getOAuthToken($args['code']);
		        
		    } catch (Exception $e){
		        throw $e;
		    }
		    
		} else{
		    //If code was not sent back there will errors : Ex: User declined
		    throw new Exception( __('No code was returned', 'webfluential-wc') );
		    
		}
		
		Webfluential_WC()->log_msg( 'Token Response : ' . print_r( $accessToken, true ) );

		if( ( $accessToken->meta->code == 200 ) && isset( $accessToken->data->access_token ) ) {
			return $accessToken->data->access_token;
		} else {
			throw new Exception( __('No token was returned', 'webfluential-wc') );
		}
	}
}
