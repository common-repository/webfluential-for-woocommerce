<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Webfluential_WC_Integration' ) ) :

class Webfluential_WC_Integration extends WC_Integration {
	
	
	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;
		$this->id                 = 'webfluential_wc';
		$this->method_title       = __( 'Create an account on Webfluential', 'webfluential-wc' );
		$this->method_description = __( 'Webfluential is a web platform for brands to discover influencers, collaborate on campaigns and report on results.', 'webfluential-wc' );

		$this->redirect_uri  = WC()->api_request_url( 'webfluential_create_account' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_webfluential_create_account' , array( $this, 'oauth_redirect' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		if ( isset( $_POST['webfluential_create_account_redirect'] ) && $_POST['webfluential_create_account_redirect'] && empty( $_POST['save'] ) ) {
			add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_wf_account_redirect' ) );
		}
	}

	/**
	 * Process Webfluential Account Creation redirect
	 *
	 */
	public function process_wf_account_redirect() {

		$args['company'] = $this->get_option( 'wf_account_company' );
		$args['name'] = $this->get_option( 'wf_account_name' );
		$args['email'] = $this->get_option( 'wf_account_email' );
		$args['redirect_url'] = $this->redirect_uri;

		try {
			$wf_api_search = new Webfluential_API_Account();
			$oauth_url = $wf_api_search->webfluential_create_account( $args );
		} catch (Exception $e) {
			throw $e;			
		}

		wp_redirect( $oauth_url );
		exit;
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array();

		$log_path = Webfluential_WC()->get_log_url();
		$access_token  = get_option( 'webfluential_access_token' );

		if ( empty($access_token) ) {
			
			$company_name = get_bloginfo('name');

			$user_id = get_current_user_id();
			$user_info = get_userdata( $user_id );

			$nicename = $user_info->display_name;
	      	$email = $user_info->user_email;

			$this->form_fields = array(
				'wf_account_company' => array(
					'title'             => __('Company Name', 'webfluential-wc'),
					'type'              => 'text',
					'default'           => $company_name,
					'description'       => __( 'Create an account using this company name.', 'webfluential-wc' ),
					'desc_tip'          => false,
				),
				'wf_account_name' => array(
					'title'             => __('Name', 'webfluential-wc'),
					'type'              => 'text',
					'default'           => $nicename,
					'description'       => __( 'Create an account using your name.', 'webfluential-wc' ),
					'desc_tip'          => false,
				),
				'wf_account_email' => array(
					'title'             => __('Email', 'webfluential-wc'),
					'type'              => 'text',
					'default'           => $email,
					'description'       => __( 'Create an account using your email.', 'webfluential-wc' ),
					'desc_tip'          => false,
				),
			);
		}

		$this->form_fields += array(
			
			'wf_create_account_button' => array(
				'title'             => __('Link Account', 'webfluential-wc'),
				'type'              => 'button',
			),
			'wf_debug' => array(
				'title'             => __( 'Debug Log', 'webfluential-wc' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'webfluential-wc' ),
				'default'           => 'yes',
				'description'       => sprintf( __( 'A log file containing the communication to the Webfluential server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.', 'webfluential-wc' ), '<a href="' . $log_path . '" target = "_blank">', '</a>' )
			),
		);
	}

	/**
	 * Generate the Webfluential "Create Account" button.
	 *
	 * @param  mixed $key
	 * @param  array $data
	 *
	 * @return string
	 */
	public function generate_button_html( $key, $data ) {
		$options       = $this->plugin_id . $this->id . '_';
		$wf_account_company     = isset( $_POST[ $options . 'wf_account_company' ] ) ? sanitize_text_field( $_POST[ $options . 'wf_account_company' ] ) : $this->get_option( 'wf_account_company' );
		$wf_account_name = isset( $_POST[ $options . 'wf_account_name' ] ) ? sanitize_text_field( $_POST[ $options . 'wf_account_name' ] ) : $this->get_option( 'wf_account_name' );
		$wf_account_email   = isset( $_POST[ $options . 'wf_account_email' ] ) ? sanitize_text_field( $_POST[ $options . 'wf_account_email' ] ) : $this->get_option( 'wf_account_email' );
		$access_token  = get_option( 'webfluential_access_token' );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $data['title'] ); ?>
			</th>
			<td class="forminp">
				<input type="hidden" name="webfluential_create_account_redirect" id="webfluential_create_account_redirect">
				<?php if ( ! $access_token ) : ?>
					<p><?php _e('Click here to log in or create a Webfluential account and link it to your WooCommerce account.', 'webfluential-wc'); ?></p>
					<p class="submit"><a class="button button-primary" onclick="jQuery('#webfluential_create_account_redirect').val('1'); jQuery('#mainform').submit();"><?php echo wp_kses_post( $data['title'] ); ?></a></p>
				<?php elseif ( $access_token ) : ?>
					<p><?php _e( 'Successfully authenticated - ', 'webfluential-wc' ); ?><a href="<?php echo admin_url('admin.php?page=webfluential_search_wc'); ?>"><?php _e( 'click here to search influencers', 'webfluential-wc' ); ?></a></p>
					<p class="submit"><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'logout' => 'true' ), $this->redirect_uri ) ); ?>"><?php _e( 'Disconnect', 'webfluential-wc' ); ?></a></p>
				<?php else : ?>
					<p><?php _e( 'Unable to authenticate.', 'webfluential-wc' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}


	/**
	 * Process the oauth redirect.
	 *
	 * @return void
	 */
	public function oauth_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied!', 'webfluential-wc' ) );
		}

		$redirect_args = array(
			'page'    => 'wc-settings',
			'tab'     => 'integration',
			'section' => $this->id,
		);

		// OAuth.
		if ( isset( $_GET['code'] ) ) {
			$args['code'] = sanitize_text_field( $_GET['code'] );
			$args['redirect_url'] = $this->redirect_uri;
			try {
				$wf_api_search = new Webfluential_API_Account();
				$access_token = $wf_api_search->webfluential_get_token( $args );
			} catch (Exception $e) {
				$redirect_args['webfluential_oauth_status'] = 'fail';
				wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
				exit;
			}
			
			// $access_token = $this->get_access_token( $code );
			// Save Webfluential token
			if ( '' != $access_token ) {
				update_option( 'webfluential_access_token', $access_token );

				$redirect_args['webfluential_oauth_status'] = 'success';

				wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
				exit;
			} else {
				$redirect_args['webfluential_oauth_status'] = 'fail';
				wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
				exit;
			}
		} /* else {
			$redirect_args['webfluential_oauth_status'] = 'fail';
			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}*/

		// Logout.
		if ( isset( $_GET['logout'] ) ) {
			// $logout = $this->oauth_logout();
			$logout = delete_option( 'webfluential_access_token' );
			$redirect_args['webfluential_account_logout'] = ( $logout ) ? 'success' : 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		wp_die( __( 'Invalid request!', 'webfluential-wc' ) );
	}

	/**
	 * Display admin screen notices.
	 *
	 * @return string
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['webfluential_oauth_status'] ) ) {
			if ( 'success' == $_GET['webfluential_oauth_status'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Webfluential', 'webfluential-wc' ) . '</strong> ' . __( 'Account created successfully! ', 'webfluential-wc' ) . '<a href="' . admin_url('admin.php?page=webfluential_search_wc') . '">Click here to search influencers</a></p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Webfluential', 'webfluential-wc' ) . '</strong> ' . __( 'Failed to create an account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'webfluential-wc' ) . '</p></div>';
			}
		}

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['webfluential_account_logout'] ) ) {
			if ( 'success' == $_GET['webfluential_account_logout'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Webfluential', 'webfluential-wc' ) . '</strong> ' . __( 'Account disconnected successfully!', 'webfluential-wc' ) . '</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Webfluential', 'webfluential-wc' ) . '</strong> ' . __( 'Failed to delete your account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'webfluential-wc' ) . '</p></div>';
			}
		}
	}
}

endif;
