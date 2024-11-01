<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


abstract class Webfluential_API {

	/**
	 * The request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * The query string
	 *
	 * @var string
	 */
	private $query = array();

	/**
	 * The request response
	 * @var array
	 */
	protected $response = null;

	/**
	 * The request response
	 * @var array
	 */
	protected $response_body = null;


	/**
	 * @var Integrater
	 */
	protected $id = '';

	/**
	 * @var string
	 */
	protected $token_bearer = '';

	/**
	 * @var array
	 */
	protected $remote_header = array();

	/**
	 * @var string
	 */
	protected $query_string = '';

	/**
	 * @var array
	 */
	protected $body_request = array();

	/**
	 * constructor.
	 *
	 * @param string $api_key, $api_secret
	 */
	public function __construct( ) {

	}

	/**
	 * Method to set id
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the id
	 *
	 * @return $id
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Method to set endpoint
	 *
	 * @param $endpoint
	 */
	protected function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Get the endpoint
	 *
	 * @return String
	 */
	protected function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * @return string
	 */
	protected function get_query() {
		return $this->query;
	}


	/**
	 * @param string $query
	 */
	protected function set_query( $query ) {
		$this->query = $query;
	}

	/**
	 * Get response
	 *
	 * @return array
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Clear the response
	 *
	 * @return bool
	 */
	private function clear_response() {
		$this->response = null;

		return true;
	}

	public function is_response_success() {
		if ($this->response_body) {
			if ($this->response_body->meta->code == 200) {
				return true;
			} else {
				return false;
			}
		}
	}

	protected function validate_args( $args ) {
		$this->set_arguments( $args );
		$this->set_query_string();
		$this->set_message();
	}
	
	protected function get_request() {
		// $this->set_header( $this->get_access_token( $client_id, $client_secret ) );

		$wp_request_url = WEBFLUENTIAL_DOMAIN . $this->get_endpoint() . '?' . $this->get_query_string();
		$wp_request_headers = $this->get_header();
		
		// $wp_request_body = $this->get_message();

		Webfluential_WC()->log_msg( 'GET URL: ' . $wp_request_url );
		Webfluential_WC()->log_msg( 'GET Header: ' . print_r( $wp_request_headers, true ) );
		// Webfluential_WC()->log_msg( 'GET Body: ' . $wp_request_body );

		$this->response = wp_remote_get(
		    $wp_request_url,
		    array( 'headers' => $wp_request_headers,
		    		'timeout' => 10 )
		);


		$response_code = wp_remote_retrieve_response_code( $this->response );
		$this->response_body = json_decode( wp_remote_retrieve_body( $this->response ) );
		// $this->response_body = strip_tags( wp_remote_retrieve_body( $this->response ) );
		// $this->response_body = json_decode($this->response_body);
		// $this->response_body = wp_remote_retrieve_body( $this->response );
		// $session_id = wp_remote_retrieve_header( $this->response, 'x-correlationid' );
		if ($this->response_body) {
		} else {
			switch (json_last_error()) {
		        case JSON_ERROR_NONE:
		        break;
		        case JSON_ERROR_DEPTH:
		        break;
		        case JSON_ERROR_STATE_MISMATCH:
		        break;
		        case JSON_ERROR_CTRL_CHAR:
		        break;
		        case JSON_ERROR_SYNTAX:
		        break;
		        case JSON_ERROR_UTF8:
		        break;
		        default:
		        break;
		    }
		}
		

		// Webfluential_WC()->log_msg( 'GET Response Header Session ID: ' . $session_id );
		Webfluential_WC()->log_msg( 'GET Response Code: ' . $response_code );
		Webfluential_WC()->log_msg( 'GET Response Body: ' . print_r( $this->response_body, true ) );

		switch ( $response_code ) {
			case '200':
				break;
			case '201':
				break;
			case '400':
				$error_message = str_replace('/', ' / ', $this->response_body->message);
				throw new Exception( __('400 - ', 'webfluential-wc') . $error_message );
				break;
			case '401':
				throw new Exception( __('401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'webfluential-wc') );
				break;
			case '429':
				throw new Exception( __('429 - Too many requests in given amount of time', 'webfluential-wc') );
				break;
			case '503':
				throw new Exception( __('503 - Service Unavailable', 'webfluential-wc') );
				break;
			default:
				$error_message = str_replace('/', ' / ', $this->response_body->message);
				throw new Exception( $response_code .' - ' . $error_message );
				break;
		}

		
		// return $this->response_body;
	}

	protected function post_request() {
		// $this->set_header( $this->get_access_token( $client_id, $client_secret ) );

		$wp_request_url = self::WEBFLUENTIAL_DOMAIN . $this->get_endpoint() . '?' . $this->get_query_string();
		$wp_request_headers = $this->get_header();
		
		$wp_request_body = $this->get_message();

		Webfluential_WC()->log_msg( 'POST URL: ' . $wp_request_url );
		Webfluential_WC()->log_msg( 'POST Header: ' . print_r( $wp_request_headers, true ) );
		Webfluential_WC()->log_msg( 'POST Body: ' . $wp_request_body );

		$this->response = wp_remote_post(
		    $wp_request_url,
		    array( 'headers' => $wp_request_headers,
		    		'body' => $wp_request_body )
		);

		$response_code = wp_remote_retrieve_response_code( $this->response );
		$this->response_body = json_decode( wp_remote_retrieve_body( $this->response ) );
		$session_id = wp_remote_retrieve_header( $this->response, 'x-correlationid' );

		Webfluential_WC()->log_msg( 'POST Response Header Session ID: ' . $session_id );
		Webfluential_WC()->log_msg( 'POST Response Code: ' . $response_code );
		Webfluential_WC()->log_msg( 'POST Response Body: ' . print_r( $this->response_body, true ) );

		switch ( $response_code ) {
			case '201':
				break;
			case '400':
				$error_message = str_replace('/', ' / ', $this->response_body->message);
				throw new Exception( __('400 - ', 'webfluential-wc') . $error_message );
				break;
			case '401':
				throw new Exception( __('401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'webfluential-wc') );
				break;
			case '429':
				throw new Exception( __('429 - Too many requests in given amount of time', 'webfluential-wc') );
				break;
			case '503':
				throw new Exception( __('503 - Service Unavailable', 'webfluential-wc') );
				break;
			default:
				$error_message = str_replace('/', ' / ', $this->response_body->message);
				throw new Exception( $response_code .' - ' . $error_message );
				break;
		}

		
		return $this->response_body;
	}

	protected function set_header( $token_bearer ) {
	}

	protected function get_header( ) {
		return $this->remote_header;
	}

	protected function get_query_string( ) {
		return $this->query_string;
	}

	protected function get_message( ) {
		return $this->body_request;
	}
}
