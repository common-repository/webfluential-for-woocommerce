<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_once( 'abstract-webfluential-api.php' );

class Webfluential_API_Search extends Webfluential_API {

	const ABOUT_CHARS = 430;

	private $args = array();

	public function __construct( ) {}

	public function webfluential_search_call( $args, $access_token = '' ) {
		
		Webfluential_WC()->log_msg( 'Search Arguments : ' . print_r( $args, true ) );

		try{
		   $wf = new Webfluential_API_SDK();

		   $wf->searchParams['channel_1'] = $args['webfluential_channel_facebook'] ? true : false;
		   $wf->searchParams['channel_2'] = $args['webfluential_channel_twitter'] ? true : false;
		   $wf->searchParams['channel_3'] = $args['webfluential_channel_instagram'] ? true : false;
		   $wf->searchParams['channel_50'] = $args['webfluential_channel_blogs'] ? true : false;
		   $wf->searchParams['channel_51'] = $args['webfluential_channel_youtube'] ? true : false;
		   $wf->searchParams['countries'] = $args['webfluential_country'];
		   $wf->searchParams['markets'] = $args['webfluential_market'];
		   $wf->searchParams['age_groups'] = $args['webfluential_age'];

		   if ( empty( $access_token ) ) {
				$wf->setClientToken( WEBFLUENTIAL_API_CLIENT_TOKEN );
 				$this->response_body = $wf->publicSearch( $args['webfluential_paging'], $args['webfluential_sort'] );
		   } else {
				$wf->setAccessToken( $access_token );
 				$this->response_body = $wf->search( $args['webfluential_paging'], $args['webfluential_sort'] );
		   }

		   return $this->get_search_results();

		} catch ( Exception $e ){
		   throw $e;
		}
	}

	public function get_search_results() {
		Webfluential_WC()->log_msg( 'Search Results : ' . print_r( $this->response_body, true ) );

		if ( $this->is_response_success() && isset( $this->response_body->data ) ) {
			
			foreach ( $this->response_body->data as $key => $result ) {
				$search_results[ $key ]['img'] = $result->img;
				$search_results[ $key ]['name'] = $result->name;
				$search_results[ $key ]['channels'] = $result->profile->channel_ids;
				$search_results[ $key ]['response'] = $result->response_times;
				$search_results[ $key ]['reach'] = $result->profile->reach;
				$search_results[ $key ]['about'] = $this->get_about( $result->profile->about ); 
				$search_results[ $key ]['rating'] = $result->influencer_rating;
				$search_results[ $key ]['slug'] = $result->profile->slug;		
				// $search_results[ $key ]['brands'] = $result->profile->brands;
				$search_results[ $key ]['brands'] = json_decode( json_encode( $result->profile->brands ), true );
			}
			return $search_results;	

		} elseif ( $this->is_limit_reached() && isset( $this->response_body->meta->errors->LimitReached ) ) {
			throw new Exception( $this->response_body->meta->errors->LimitReached );
		} else {
			throw new Exception( __('There were no matching results based on your criteria.', 'webfluential-wc' ) );
			
		}
	}

	protected function get_about( $about ) {
		if( strlen( $about ) > self::ABOUT_CHARS ) {
			return substr( $about, 0, self::ABOUT_CHARS ) . '...';
		} else {
			return $about;
		}
	}

	public function get_pagination() {
		if ( $this->is_response_success() && isset( $this->response_body->paging ) ) {
			return $this->response_body->paging;
		} else {
			return array();
		}
	}

	public function is_limit_reached() {
		if ($this->response_body) {
			if ($this->response_body->meta->code == 403) {
				return true;
			} else {
				return false;
			}
		}
	}
}
