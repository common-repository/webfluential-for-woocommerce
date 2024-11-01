<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Webfluential_WC_Search' ) ) :

class Webfluential_WC_Search {
	
	const WEBFLUENTIAL_SCREEN_ID = 'webfluential_search_wc';

	protected $target_markets = array();
	
	protected $target_ages = array();
	/**
	 * Init the class
	 *
	 * @since 1.0
	 * @return \WC_Checkout_Add_Ons_Admin
	 */
	public function __construct() {
		// define('webfluential_search_wc', '');
		
		$this->target_markets = array(
								'25' => __('Arts', 'webfluential-wc'),
								'50' => __('Beauty', 'webfluential-wc'),
								'26' => __('Business', 'webfluential-wc'),
								'27' => __('Celebrities', 'webfluential-wc'),
								'29' => __('Creative', 'webfluential-wc'),
								'31' => __('Education', 'webfluential-wc'),
								'32' => __('Entertainment', 'webfluential-wc'),
								'33' => __('Fashion', 'webfluential-wc'),
								'34' => __('Finance', 'webfluential-wc'),
								'51' => __('Fitness', 'webfluential-wc'),
								'35' => __('Food', 'webfluential-wc'),
								'47' => __('Gaming', 'webfluential-wc'),
								'36' => __('Law', 'webfluential-wc'),
								'48' => __('Lifestyle', 'webfluential-wc'),
								'49' => __('Motoring', 'webfluential-wc'),
								'37' => __('Music', 'webfluential-wc'),
								'38' => __('Parenting', 'webfluential-wc'),
								'39' => __('Personal', 'webfluential-wc'),
								'30' => __('Pets', 'webfluential-wc'),
								'40' => __('Photography', 'webfluential-wc'),
								'41' => __('Political', 'webfluential-wc'),
								'42' => __('Science', 'webfluential-wc'),
								'43' => __('Sex', 'webfluential-wc'),
								'44' => __('Shopping', 'webfluential-wc'),
								'45' => __('Sports', 'webfluential-wc'),
								'28' => __('Technology', 'webfluential-wc'),
								'46' => __('Travel', 'webfluential-wc'),
								);

		$this->target_ages = array(
								'11' => __('under 18', 'webfluential-wc'),
								'5'  => __('18 to 24', 'webfluential-wc'),
								'12' => __('25 to 30', 'webfluential-wc'),
								'13' => __('30 to 34', 'webfluential-wc'),
								'14' => __('35 to 45', 'webfluential-wc'),
								'16' => __('46 to 54', 'webfluential-wc'),
								'15' => __('55+', 'webfluential-wc'),
								);

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// load WC styles / scripts on editor screen
		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );

		// add 'checkout add-ons' link under WooCommerce menu
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );

		add_action( 'wp_ajax_webfluential_search', array( $this, 'get_webfluential_search_results' ) );
	}

	/**
	 * Load admin styles and scripts
	 *
	 * @since 1.0
	 * @param string $hook_suffix the current URL filename, ie edit.php, post.php, etc
	 */
	public function load_styles_scripts( $hook_suffix ) {
		global $post_type, $wp_scripts;
		$is_coa_page = $this->page_id === $hook_suffix;

		// load admin css only on view orders / edit order screens
		if ( $is_coa_page ) {

			// admin CSS
			wp_enqueue_style( 'webfluential-wc-css', WEBFLUENTIAL_PLUGIN_DIR_URL . '/assets/css/webfluential-admin.css', array( 'woocommerce_admin_styles' ), WEBFLUENTIAL_VERSION );

			$wf_wc_data = array(
								'ajax_url'	=>	admin_url( 'admin-ajax.php' )
								);
			// admin JS
			wp_enqueue_script( 'webfluential-wc-js', WEBFLUENTIAL_PLUGIN_DIR_URL . '/assets/js/webfluential-admin-search.js', array( 'jquery', 'woocommerce_admin' ), WEBFLUENTIAL_VERSION );
			wp_localize_script( 'webfluential-wc-js', 'wf_wc_data', $wf_wc_data );

			wp_enqueue_script( 'font-awesome', '//use.fontawesome.com/releases/v5.0.8/js/all.js' );

		}
	}


	/**
	 * Add  screen ID to the list of pages for WC to load its JS on
	 *
	 * @since 1.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {

		// The textdomain usage is intentional here, we need to match the menu title.
		$prefix = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		$screen_ids[] = $prefix . '_page_' . self::WEBFLUENTIAL_SCREEN_ID;
		
		return $screen_ids;
	}
		
	/**
	 * Add 'Order add-ons' sub-menu link under 'WooCommerce' top level menu
	 *
	 * @since 1.0
	 */
	public function add_menu_link() {

		$this->page_id = add_submenu_page(
			'woocommerce',
			__( 'Webfluential', 'webfluential-wc' ),
			__( 'Webfluential', 'webfluential-wc' ),
			'manage_woocommerce',
			self::WEBFLUENTIAL_SCREEN_ID,
			array( $this, 'render_editor_screen' )
		);
	}

	/**
	 * Render the checkout add-ons editor
	 *
	 * @since 1.0
	 */
	public function render_editor_screen() {

		?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="" enctype="multipart/form-data" class="">
				<div id="icon-woocommerce" class="icon32"><br /></div>
				<h2><?php esc_html_e( 'Webfluential Search', 'webfluential-wc' ); ?></h2> <?php

				// show add-on editor
				$this->render_editor();

				?>
			</form>
		</div><?php
	}

	/**
	 * Render the checkout add-ons editor table
	 *
	 * @since 1.0
	 */
	
	private function render_editor() {
		
		$target_countries = WC()->countries->get_countries();

		?>
			<div id="webfuential-wc-search-form">
				<h2><?php esc_html_e( 'Search criteria for influencers', 'webfluential-wc' ); ?></h2>
				<p><?php esc_html_e('By identifying influencers who resonate with your target audience, you can collaborate with them to produce content on their media channels to drive awareness, authenticity and will encourage sales', 'webfluential-wc'); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="webfluential_country"><?php esc_html_e( 'Target location(s)', 'webfluential-wc' ); ?></label>
						</th>
						<td class="forminp forminp-text">
							<select
								multiple="multiple"
								name="webfluential_country[]"
								id="webfluential_country"
								style=""
								value=""
								class="wc-enhanced-select"
							/>
							<?php 

								$selected_country = ! empty( $_POST['webfluential_country'] ) ? wc_clean( $_POST['webfluential_country'] ) : array();

								foreach ($target_countries as $option_key => $option_value) { ?>
									<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( $option_key, $selected_country ), true ); ?>><?php echo esc_attr( $option_value ); ?></option>

							<?php }	?>
						</td>
					</tr>
					<?php
						$selected_channel_facebook = ! empty( $_POST['webfluential_channel_facebook'] ) ? wc_clean( $_POST['webfluential_channel_facebook'] ) : '';
						$selected_channel_twitter = ! empty( $_POST['webfluential_channel_twitter'] ) ? wc_clean( $_POST['webfluential_channel_twitter'] ) : '';
						$selected_channel_instagram = ! empty( $_POST['webfluential_channel_instagram'] ) ? wc_clean( $_POST['webfluential_channel_instagram'] ) : '';
						$selected_channel_blogs = ! empty( $_POST['webfluential_channel_blogs'] ) ? wc_clean( $_POST['webfluential_channel_blogs'] ) : '';
						$selected_channel_youtube = ! empty( $_POST['webfluential_channel_youtube'] ) ? wc_clean( $_POST['webfluential_channel_youtube'] ) : '';
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="webfluential_channel"><?php esc_html_e( 'Channel Options', 'webfluential-wc' ); ?></label>
						</th>
						<td class="forminp forminp-text">
							<input type="checkbox" id="webfluential_channel_facebook" name="webfluential_channel_facebook" value="1" checked <?php checked( $selected_channel_facebook, '1' ); ?>><?php esc_html_e( 'Facebook', 'webfluential-wc' ); ?></br>
							<input type="checkbox" id="webfluential_channel_twitter" name="webfluential_channel_twitter" value="2" checked <?php checked( $selected_channel_twitter, '2' ); ?>><?php esc_html_e( 'Twitter', 'webfluential-wc' ); ?></br>
							<input type="checkbox" id="webfluential_channel_instagram" name="webfluential_channel_instagram" value="3" checked <?php checked( $selected_channel_instagram, '3' ); ?>><?php esc_html_e( 'Instagram', 'webfluential-wc' ); ?></br>
							<input type="checkbox" id="webfluential_channel_blogs" name="webfluential_channel_blogs" value="50" checked <?php checked( $selected_channel_blogs, '50' ); ?>><?php esc_html_e( 'Blogs', 'webfluential-wc' ); ?></br>
							<input type="checkbox" id="webfluential_channel_youtube" name="webfluential_channel_youtube" value="51" checked <?php checked( $selected_channel_youtube, '51' ); ?>><?php esc_html_e( 'Youtube', 'webfluential-wc' ); ?>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="webfluential_market"><?php esc_html_e( 'Target Market(s)', 'webfluential-wc' ); ?></label>
						</th>
						<td class="forminp forminp-text">
							<select
								multiple
								name="webfluential_market[]"
								id="webfluential_market"
								style=""
								value=""
								class="wc-enhanced-select"
							/>
							<?php 
								$selected_market = ! empty( $_POST['webfluential_market'] ) ? wc_clean( $_POST['webfluential_market'] ) : array();

								foreach ($this->target_markets as $option_key => $option_value) { ?>
									<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( $option_key, $selected_market ), true ); ?>><?php echo esc_attr( $option_value ); ?></option>

							<?php }	?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="webfluential_age"><?php esc_html_e( 'Target age group(s)', 'webfluential-wc' ); ?></label>
						</th>
						<td class="forminp forminp-text">
							<select
								multiple
								name="webfluential_age[]"
								id="webfluential_age"
								style=""
								value=""
								class="wc-enhanced-select"
							/>
							<?php 
								$selected_age = ! empty( $_POST['webfluential_age'] ) ? wc_clean( $_POST['webfluential_age'] ) : array();

								foreach ($this->target_ages as $option_key => $option_value) { ?>
									<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( $option_key, $selected_age ), true ); ?>><?php echo esc_attr( $option_value ); ?></option>

							<?php }	?>
						</td>
					</tr>
					<tfoot>
						<tr>
							<th>
								<button id="webfluential-search-btn" type="button" class="button-primary">
									<?php esc_html_e( 'Search influencers', 'webfluential-wc' ); ?>
								</button>
							</th>
						</tr>
					</tfoot>					
				</table>
				<p><?php _e('Search results are provided by Webfluential.com and influencers can be easily contacted through their media kits.</br><br/>Visit <a href="https://webfluential.com/" target="_blank">Webfluential.com</a> for further features and products.', 'webfluential-wc'); ?></p>
			</div>
		<?php

		wp_nonce_field( 'wf-wc-search', 'webfluential_nonce' );
	}

	protected function get_search_criteria() {
		// loop through inputs
		$search_field_ids = array( 'webfluential_country', 'webfluential_channel_facebook', 'webfluential_channel_twitter', 'webfluential_channel_instagram', 'webfluential_channel_blogs', 'webfluential_channel_youtube', 'webfluential_market' ,'webfluential_age', 'webfluential_paging', 'webfluential_sort');
		
		foreach ($search_field_ids as $key => $value) {
			// Save value if it exists
			if ( isset( $_POST[ $value ] ) ) {
				$args[ $value ]	 = wc_clean( $_POST[ $value ] );
			}
		}		

		return $args;
	}

	public function get_webfluential_search_results() {
		check_ajax_referer( 'wf-wc-search', 'webfluential_nonce' );

		$search_criteria = $this->get_search_criteria();

		try {

			$access_token  = get_option( 'webfluential_access_token' );
			$wf_api_search = new Webfluential_API_Search();
			$wf_api_search->webfluential_search_call( $search_criteria, $access_token );

			if( $wf_api_search->is_response_success() ) {
				ob_start();
				$this->display_results( $wf_api_search->get_search_results(), $wf_api_search->get_pagination() );
				$results = ob_get_clean();
				// ob_get_clean();
				// echo "shadi";
			}

			
			wp_send_json( array( 
				'results' => $results
				) );

		} catch (Exception $e) {
			$wf_error = $e->getMessage();
			
			if( $wf_api_search->is_limit_reached() ) {
				$wf_error .= sprintf( __( ' - <a href="%s">clicking here</a>', 'webfluential-wc' ), admin_url('admin.php?page=wc-settings&tab=integration&section=webfluential_wc') );
			}

			wp_send_json( array( 'error' => $wf_error ) );
		}
	}

	protected function display_results( $search_results, $pagination ) {
		?>
			<?php $this->display_pagination( $pagination ); ?>

			<!-- Search Results -->
			<h2><?php esc_html_e( 'Search Results', 'webfluential-wc' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th class="">
							<?php esc_html_e( 'Profile Image', 'webfluential-wc' ); ?>
						</th>
						<th class="wf-col-profile">
							<?php esc_html_e( 'Profile', 'webfluential-wc' ); ?>
						</th>
						<th class="wf-col-about">
							<?php esc_html_e( 'About', 'webfluential-wc' ); ?>
						</th>
						<th class="">
							<?php esc_html_e( 'Action', 'webfluential-wc' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $search_results as $key => $result ) {
					?>
						<tr class="">
							<td class="">
								<img class="wf-res-img" src="<?php echo $result['img']; ?>">
							</td>
							<td class="wf-row-profile">
								<span class="wf-txt-name"><?php echo $result['name']; ?></span><br/>
								<?php echo $this->display_channels( $result['channels'] ); ?><br/>
								&plusmn;<?php echo round($result['reach'], -2); ?><br/>
								<?php echo $this->display_stars( $result['rating'] ); ?><br/>
								<span class="wf-txt-response"><?php echo 'Likely to respond within <strong>'. $result['response'] . '</strong>'; ?></span>
							</td>
							<td class="">
								<?php echo $result['about']; ?>
								<?php echo $this->display_brands( $result['brands'] ); ?>
							</td>
							</td>
							<td class="">
								<a href="<?php echo WEBFLUENTIAL_DOMAIN . '/' . $result['slug']; ?>" target="_blank" class="button-primary"><?php _e('View Profile', 'webfluential-wc'); ?> <i class="far fa-comment-alt"></i></a>
							</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>
				

		<?php

			$this->display_pagination( $pagination );
	}

	protected function display_channels( $channels ) {
		$channel_icons = '';
		foreach ($channels as $key => $channel) {
			switch ($channel) {
				case '1':
					$channel_icons .= ' <i class="fab fa-facebook-f"></i>';
					break;
				case '2':
					$channel_icons .= ' <i class="fab fa-twitter"></i>';
					break;
				case '3':
					$channel_icons .= ' <i class="fab fa-instagram"></i>';
					break;
				case '50':
					$channel_icons .= ' <i class="fab fa-youtube"></i>';
					break;
				case '51':
					$channel_icons .= ' <i class="fab fa-wordpress"></i>';
					break;
			}
		}

		return $channel_icons;
	}

	protected function display_brands( $brands ) {
		$brands_images = '';

		if ( ! empty( $brands ) ) {

			$brands_images .= '<div class="wf-brands"><strong>' . __( 'This influencer has worked with:', 'webfluential-wc' ) . '</strong>';

			foreach ($brands as $key => $brand) {
				$brands_images .= '<a href="' . $brand['link'] . '" target="_blank"><img class="wf-brand-img" src="'. $brand['brand_twitter_img_alt'] . '"></a> ';

				if( $key == 5 ) {
					break;
				}
			}

			$brands_images .= '</div>';
		}

		return $brands_images;
	}

	protected function display_stars( $stars ) {
		?>
		<div class="wf-star-rating">
			<span style="width:<?php echo round(($stars/5)*100, -1); ?>%">
				<strong><?php echo (round($stars * 2) / 2); ?></strong> out of 5
			</span>
		</div>
		<?php
	}
	protected function display_pagination( $pagination ) {
		?>
			<div class="tablenav bottom">
				
				<!-- Sorting buttons -->
				<div class="alignleft actions bulkactions">
					<div class="wf-pagination">
						<span class="wf-sort-txt"><?php _e( 'Sort by:', 'webfluential-wc' ); ?></span>
						<button id="webfluential-previous-page-btn" type="button" class="button" onclick="webfluential_search( <?php echo (int)$pagination->currentPage; ?>, 'response' );return false;">
							<?php esc_html_e( 'Response ', 'webfluential-wc' ); ?><i class="far fa-clock"></i>
						</button>
						<button id="webfluential-previous-page-btn" type="button" class="button"  onclick="webfluential_search( <?php echo (int)$pagination->currentPage; ?>, 'rating' );return false;">
							<?php esc_html_e( 'Rating ', 'webfluential-wc' ); ?><i class="fas fa-star"></i>
						</button>
						<button id="webfluential-previous-page-btn" type="button" class="button"  onclick="webfluential_search( <?php echo (int)$pagination->currentPage; ?>, 'reach_desc' );return false;">
							<?php esc_html_e( 'Reach ', 'webfluential-wc' ); ?><i class="fas fa-sort-amount-down"></i>
						</button>
					</div>
				</div>

				<!-- Pagination -->
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo $pagination->totalRecords . __(' Results', 'webfluential-wc'); ?></span>
					<span class="pagination-links">
						
						<?php if( $pagination->previous ) : ?>
							
							<a class="prev-page" href="#" onclick="webfluential_search( <?php echo (int)$pagination->currentPage - 1; ?>, '<?php echo $pagination->sort; ?>' );return false;">
								<span class="screen-reader-text">Previous page</span>
								<span aria-hidden="true">‹</span>
							</a>
						
						<?php else : ?>

							<span class="tablenav-pages-navspan" aria-hidden="true">‹</span>

						<?php endif; ?>

						<span class="screen-reader-text">Current Page</span>
						<span id="table-paging" class="paging-input" data-current-page="<?php echo $pagination->currentPage; ?>">
							<span class="tablenav-paging-text"><?php echo $pagination->currentPage . __(' of ', 'webfluential-wc'); ?>
								<span class="total-pages"><?php echo $pagination->totalPages; ?></span>
							</span>
						</span>
						
						<?php if( $pagination->next ) : ?>

							<a id="webfluential-next-link" class="next-page" href="#" onclick="webfluential_search( <?php echo (int)$pagination->currentPage + 1; ?>, '<?php echo $pagination->sort; ?>' );return false;" >
								<span class="screen-reader-text">Next page</span>
								<span aria-hidden="true">›</span>
							</a>

						<?php else : ?>

							<span class="tablenav-pages-navspan" aria-hidden="true">›</span>

						<?php endif; ?>

					</span>
				</div>
			</div>
		<?php
	}
}

endif;
