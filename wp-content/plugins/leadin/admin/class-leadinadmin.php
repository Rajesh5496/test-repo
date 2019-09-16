<?php

if ( ! defined( 'LEADIN_PLUGIN_VERSION' ) ) {
	wp_die( '', '', 403 );
}

// =============================================
// Define Constants
// =============================================
if ( ! defined( 'LEADIN_ADMIN_PATH' ) ) {
	define( 'LEADIN_ADMIN_PATH', untrailingslashit( __FILE__ ) );
}

// =============================================
// Include Needed Files
// =============================================
require_once ABSPATH . 'wp-admin/includes/plugin.php';
/**
 * general function which can be used to get the treatment for an experiment
 * arg1 => the name of the experiment e.g leadin_banner_experiment. this will be used as the name of the option in the wordpress DB
 * arg2 => number of possible variations
 * returns => 
 * returns a number between 1-number of possible variations for that experiment
 */
function leadin_get_experiment_treatment( $experiment_name, $experiment_ungated = false ,$number_of_variations=2 ) {
	$treatment = (int) get_option( $experiment_name );
	if ( empty( $treatment ) ) {
		$treatment = rand( 1 , $number_of_variations );
		add_option( $experiment_name, $treatment);
	}
	return $treatment;
}

function leadin_render_disconnected_banner( ) {
	$experiment_treatment_value = leadin_get_experiment_treatment( 'leadin_banner_experiment' , LEADIN_NEW_BANNER_GATE);
	if ( LEADIN_NEW_BANNER_GATE  && $experiment_treatment_value === 2 ) {
		?>
			<div id = "disconnectedBanner" class="notice notice-warning is-dismissible banner-wrapper">
				<div class="logo-wrapper">
					<img src="<?php echo esc_attr( LEADIN_PATH . '/images/hubspot-wordmark.svg' ); ?>" class="logo-style"  />
				</div>
				<div class="title-wrapper">
					<p class="heading-text"><?php echo(__('Grow your list. Manage your contacts. Send beautiful email','leadin')) ?></p>
				</div>

				<div id= "hubspot-dashboard-banner-login"  class="content-wrapper">
					<p class="banner-description"><?php echo (__('Power up your site for free with the ultimate all-in-one marketing plugin for Wordpress','leadin'))?>
						<br class="mobile-spacing"/>
							<a  id="loginLink" href ="#" class="signup-link"><?php echo (__( 'Log in', 'leadin' )) ?></a>
							or
							<a href="./admin.php?page=leadin&bannerClick=true">
								<button class="signup-button">
									<p class="button-text"><?php echo (__( 'Sign up now', 'leadin' )) ?></p>
								</button>
							</a>
						</p>
				</div>

				<div  id="hubspot-dashboard-banner-connect" class="content-wrapper">
					<p class="banner-description"><?php echo (__('Power up your site for free with the ultimate all-in-one marketing plugin for Wordpress','leadin'))?>
						<br class="mobile-spacing"/>
						<a href="./admin.php?page=leadin&bannerClick=true">
							<button class="signup-button">
								<p class="button-text"><?php echo (__( 'Connect my account', 'leadin' )) ?></p>
							</button>
						</a>
					</p>
				</div>
			</div>

		<?php
	} else {

		?>
			<div id="hubspot-dashboard-banner" class="notice notice-warning is-dismissible">
				<p>
					<img src="<?php echo esc_attr( LEADIN_PATH . '/images/sprocket.svg' ); ?>" height="16" style="margin-bottom: -3px" />
					&nbsp;
					<?php echo sprintf(
						esc_html(__( 'The HubSpot plugin isn’t connected right now. To use HubSpot tools on your WordPress site, %1$sconnect the plugin now%2$s.', 'leadin' ))
						,'<a href="admin.php?page=leadin&bannerClick=true">',
						'</a>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			</div>
		<?php
	}
}

/**
 * Find what notice (if any) needs to be rendered
 */
function leadin_action_required_notice() {
	$current_screen = get_current_screen();
	$messageType = "";
	if ( 'leadin' !== $current_screen->parent_base ) {
		if ( ! get_option( 'leadin_portalId' ) && leadin_is_admin() ) {
			$messageType = "DISCONNECTED_MESSAGE";
		}
	}
	if ( $messageType === "DISCONNECTED_MESSAGE" ) {
		leadin_render_disconnected_banner();
	}
}

/**
 * LeadinAdmin Class
 */
class LeadinAdmin {
	/**
	 * Class constructor
	 */
	public function __construct() {
		// =============================================
		// Hooks & Filters
		// =============================================
		$plugin_version = get_option( 'leadin_pluginVersion' );

		// If the plugin version matches the latest version escape the update function.
		if ( LEADIN_PLUGIN_VERSION !== $plugin_version ) {
			self::leadin_update_check();
		}

		add_action( 'admin_init', array( &$this,'leadin_maybe_redirect' ) );
		add_action( 'admin_menu', array( &$this, 'leadin_add_menu_items' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'add_leadin_admin_scripts' ) );
		add_filter( 'plugin_action_links_leadin/leadin.php', array( $this, 'leadin_plugin_settings_link' ) );
		add_action( 'admin_notices', array( &$this, 'leadin_add_background_iframe' ) );
		add_action( 'admin_notices', 'leadin_action_required_notice' );

		$affiliate = $this->get_affiliate_code();
		if ( $affiliate ) {
			add_option( 'hubspot_affiliate_code', $affiliate );
		}
		$this->hydrate_acquisition_attribution();
	}

	public function leadin_maybe_redirect() {
		if ( get_transient('leadin_redirect_after_activation' ) ) {
				delete_transient('leadin_redirect_after_activation');
				wp_safe_redirect( admin_url( 'admin.php?page=leadin' ) );
				exit;
		}
	}

	/**
	 * Return affiliate code from either file or option
	 */
	private function get_affiliate_code() {
		$affiliate = get_option( 'hubspot_affiliate_code' );
		if ( ! $affiliate ) {
			$affiliate = trim( preg_replace( '/\s\s+/', ' ', leadin_file_get_contents( 'hs_affiliate.txt' ) ) );
		}
		if ( $affiliate ) {
			return $affiliate;
		}
		return false;
	}

	/**
	 * Get hubspot_acquisition_attribution option
	 */
	private function get_acquisition_attribution_option() {
		return get_option( 'hubspot_acquisition_attribution' );
	}

	/**
	 * Return attribution string from wither file or option
	 */
	private function hydrate_acquisition_attribution() {
		if ( $this->get_acquisition_attribution_option() ) {
			return;
		}

		if ( file_exists( LEADIN_PLUGIN_DIR . '/hs_attribution.txt' ) ) {
			$acquisition_attribution = trim( leadin_file_get_contents( 'hs_attribution.txt' ) );
			add_option( 'hubspot_acquisition_attribution', $acquisition_attribution );
		}
	}

	/**
	 * Store current version in option
	 */
	private function leadin_update_check() {
		update_option( 'leadin_pluginVersion', LEADIN_PLUGIN_VERSION );
	}

	// =============================================
	// Menus
	// =============================================
	/**
	 * Adds Leadin menu to /wp-admin sidebar
	 */
	public function leadin_add_menu_items() {
		global $submenu;
		global $wp_version;

		$notification_icon = '';
		if ( ! get_option( 'leadin_portalId' ) ) {
			$notification_icon = ' <span class="update-plugins count-1"><span class="plugin-count">!</span></span>';
		}

		$portal_id = get_option( 'leadin_portalId' );
		if ( ! empty( $portal_id ) ) {
			add_menu_page( __( 'HubSpot', 'leadin' ), __( 'HubSpot', 'leadin' ) . $notification_icon, 'read', 'leadin', array( $this, 'leadin_build_app' ), 'dashicons-sprocket', '25.100713' );
			add_submenu_page( 'leadin', __( 'Forms', 'leadin' ), __( 'Forms', 'leadin' ), 'read', 'leadin_forms', array( $this, 'leadin_build_app' ) );
			add_submenu_page( 'leadin', __( 'Settings', 'leadin' ), __( 'Settings', 'leadin' ), 'read', 'leadin_settings', array( $this, 'leadin_build_app' ) );
			remove_submenu_page( 'leadin', 'leadin' );
		} else {
			add_menu_page( __( 'HubSpot', 'leadin' ), __( 'HubSpot', 'leadin' ) . $notification_icon, 'manage_options', 'leadin', array( $this, 'leadin_build_app' ), 'dashicons-sprocket', '25.100713' );
		}
	}

	// =============================================
	// Settings Page
	// =============================================
	/**
	 * Adds setting link for Leadin to plugins management page
	 *
	 * @param   array $links Return the links for the settings page.
	 * @return  array
	 */
	public function leadin_plugin_settings_link( $links ) {
		$portal_id = get_option( 'leadin_portalId' );
		if ( ! empty( $portal_id ) ) {
			$page = 'leadin_settings';
		} else {
			$page = 'leadin';
		}
		$url           = get_admin_url( get_current_blog_id(), "admin.php?page=$page" );
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'leadin' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Creates leadin app
	 */
	public function leadin_build_app() {
		global $wp_version;

		wp_enqueue_style( 'leadin-bridge-css' );

		$error_message = '';

		if ( version_compare( phpversion(), LEADIN_REQUIRED_PHP_VERSION, '<' ) ) {
			$error_message = sprintf(
				__( 'HubSpot All-In-One Marketing %1$s requires PHP %2$s or higher. Please upgrade WordPress first.', 'leadin' ),
				LEADIN_PLUGIN_VERSION,
				LEADIN_REQUIRED_PHP_VERSION
			);
		} elseif ( version_compare( $wp_version, LEADIN_REQUIRED_WP_VERSION, '<' ) ) {
			$error_message = sprintf(
				__( 'HubSpot All-In-One Marketing %1$s requires PHP %2$s or higher. Please upgrade WordPress first.', 'leadin' ),
				LEADIN_PLUGIN_VERSION,
				LEADIN_REQUIRED_WP_VERSION
			);
		}

		if ( $error_message ) {
			?>
				<div class='notice notice-warning'>
					<p>
						<?php echo esc_html( $error_message ); ?>
					</p>
				</div>
			<?php
		} else {
			$iframe_url = leadin_get_iframe_src();
			?>
				<iframe id="leadin-iframe" src="<?php echo esc_attr( $iframe_url ); ?>"></iframe>
			<?php
		}
	}

	/**
	 * Render background iframe
	 */
	public function leadin_add_background_iframe() {
		$screen = get_current_screen();
		if ( 'dashboard' === $screen->id || 'post' === $screen->id || 'page' === $screen->id ) {
			$background_iframe_url = leadin_get_background_iframe_src();
			?>
				<iframe class="leadin-background-iframe" style="display: none" id="leadin-iframe" src="<?php echo esc_attr( $background_iframe_url ); ?>"></iframe>
			<?php
		}
	}

	// =============================================
	// Admin Styles & Scripts
	// =============================================
	/**
	 * Adds admin javascript
	 */
	public function add_leadin_admin_scripts() {
		wp_register_style( 'leadin-bridge-css', LEADIN_PATH . '/style/leadin-bridge.css?', array(), LEADIN_PLUGIN_VERSION );
		wp_register_script( 'leadin-js', LEADIN_JS_BASE_PATH . '/leadin.js', array(), LEADIN_PLUGIN_VERSION, true );
		wp_localize_script( 'leadin-js', 'leadinConfig', leadin_get_leadin_config() );
		wp_localize_script( 'leadin-js', 'leadinI18n', leadin_get_leadin_i18n() );
		wp_enqueue_script( 'leadin-js' );
	}
}
