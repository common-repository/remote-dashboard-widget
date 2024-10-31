<?php

namespace wpw_dashboard_widget;

defined('ABSPATH') || exit;

/*
    Plugin Name: Remote Dashboard Widget
    Plugin URI: https://wpdashboardwidget.com/
    Description: Marketing widget for (remotely) displaying website maintainer or -support contact information on the WordPress dashboard.
    Version: 0.0.30
	Requires at least: 5.4
	Requires PHP: 8.1
    Author: WP Wolf 🐺
    Author URI: https://wp-wolf.com/
    License: GPL v2
    License URI: https://www.gnu.org/licenses/gpl-2.0.html
    Text Domain: dashboard-widget
	Domain Path: /languages
*/

require_once 'vendor/autoload.php';
require_once 'vendor/cmb2/cmb2/init.php';
require_once 'includes/OptionsPage.php';
require_once 'includes/Widget.php';

/**
 * Class Dashboard_Widget
 *
 * @package wpw_dashboard_widget
 */
if ( ! class_exists( 'wpw_dashboard_widget\Dashboard_Widget' ) )
{
	class Dashboard_Widget {

		const VERSION = '0.0.30';
		const WIDGET_TITLE = 'WP Dashboard Widget';
		const WIDGET_NAME = 'dashboard-widget';
		const TEXT_DOMAIN = 'wpw-dashboard-widget';
		const COMPANY_URL = 'https://wpdashboardwidget.com';
		const COMPANY_URL_TEST = 'https://wpdashboardwidget.test';
		const DEMO_TOKEN = 'T4pk12pkjPLXI3M9IgMEQZfYnsKpiDro';
		const HANDLE_CSS = 'wp-dashboard-widget-css';
		const OPTION_REGISTERED_DASHBOARD_WIDGETS = 'wp-dashboard-widget-registered-widgets';

		private ?Widget $widget = null;


		/**
		 * Dashboard_Widget constructor.
		 */
		public function __construct() {
			// TODO: network support
			define( 'WP_DASHBOARD_WIDGET_PATH', plugin_dir_path( __FILE__ ) );
			define( 'WP_DASHBOARD_WIDGET_FILE_PATH', WP_DASHBOARD_WIDGET_PATH . self::WIDGET_NAME . '.php' );

			add_action( 'admin_init', [ $this, 'initialize' ] );
			add_action( 'cmb2_admin_init', [ $this, 'add_options_page' ] );
		}


		/**
		 * @return void
		 */
		public function initialize(): void {
			// TODO: https://developer.wordpress.org/reference/hooks/wp_network_dashboard_setup/ | https://wisdmlabs.com/blog/build-multisite-compatible-wordpress-plugin/
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
			add_action( 'wp_dashboard_setup', [ $this, 'store_registered_dashboard_widgets' ], 70 );
			add_action( 'wp_dashboard_setup', [ $this, 'remove_disabled_dashboard_widgets' ], 80 );
			add_action( 'wp_dashboard_setup', [ $this, 'add_widget' ] );
			add_filter( 'plugin_action_links_remote-dashboard-widget/dashboard-widget.php', [ $this, 'add_settings_link' ] );
		}


		/**
		 * @return void
		 */
		public function enqueue_styles(): void {
			wp_enqueue_style(
				self::HANDLE_CSS,
				plugins_url( 'admin/css/style.css', __FILE__ ),
				[],
				self::VERSION
			);

			if ( ! $this->widget ) {
				$this->widget = new Widget();
			}

			$linkColor = $this->widget->get( Widget::KEY_LINK_COLOR );
			$titleColor = $this->widget->get( Widget::KEY_TITLE_COLOR );

			$style = wp_sprintf(
				'#dashboard-widget a, #dashboard-widget a:hover, .dashboard-widget-link, .dashboard-widget-link:hover { color: %s }
				.dashboard-widget-title { color: %s }',
				$linkColor,
				$titleColor
			);

			wp_add_inline_style( self::HANDLE_CSS, $style );
		}


		/**
		 * @return void
		 */
		public function add_options_page(): void {
			new OptionsPage();
		}


		/**
		 * @param  array  $links
		 *
		 * @return array
		 */
		public function add_settings_link( array $links ): array {
			$url = add_query_arg(
				'page',
				'wp_dashboard_widget_page_options',
				get_admin_url() . 'options-general.php'
			);
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'Settings' ) );

			return $links;
		}


		/**
		 * @return void
		 */
		public function remove_disabled_dashboard_widgets(): void {
			$widgets = cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_DISABLED_DASHBOARD_WIDGETS );
			$stored_dashboard_widgets = get_option( self::OPTION_REGISTERED_DASHBOARD_WIDGETS );
			if ( $widgets ) {
				foreach ( $widgets as $widget ) {
					remove_meta_box( $widget, 'dashboard', $stored_dashboard_widgets[ $widget ]['context'] );
				}
			}
		}


		/**
		 * @return void
		 */
		public function add_widget(): void {
			$enabled_roles = cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_ENABLED_ROLES );
			if ( ! $enabled_roles ) {
				$enabled_roles = array();
			}

			$enabled_roles[] = 'administrator';

			$user = wp_get_current_user();
			if ( ! empty( array_intersect( $user->roles, $enabled_roles ) ) ) {
				if ( ! $this->widget ) {
					$this->widget = new Widget();
				}

				$this->widget->add();
			}
		}


		/**
		 * @return void
		 */
		public function store_registered_dashboard_widgets(): void {
			global $wp_meta_boxes;

			$stored_dashboard_widgets = get_option( self::OPTION_REGISTERED_DASHBOARD_WIDGETS );
			if ( ! $stored_dashboard_widgets ) {
				$stored_dashboard_widgets = array();
			}

			$registered_dashboard_widgets = array();
			foreach ( $wp_meta_boxes["dashboard"] as $position => $core ) {
				if ( is_array( $core ) && array_key_exists( 'core', $core ) && is_array( $core['core'] ) ) {
					foreach ( $core["core"] as $widget_id => $widget_info ) {
						if ( self::WIDGET_NAME !== $widget_id ) {
							$registered_dashboard_widgets[ $widget_id ] = [
								'title'   => $widget_info['title'],
								'active'  => (
								array_key_exists( $widget_id, $stored_dashboard_widgets ) ?
									$stored_dashboard_widgets[ $widget_id ]['active'] :
									true
								),
								'context' => $position,
							];
						}
					}
				}
			}

			update_option( self::OPTION_REGISTERED_DASHBOARD_WIDGETS, $registered_dashboard_widgets );
		}


		/**
		 * @return void
		 */
		public static function deactivate(): void {
			delete_transient( Widget::TRANSIENT_NAME );
		}


		/**
		 * @return void
		 */
		public static function uninstall(): void {
			delete_transient( Widget::TRANSIENT_NAME );
			delete_option( Dashboard_Widget::OPTION_REGISTERED_DASHBOARD_WIDGETS );
			delete_option( OptionsPage::KEY_OPTION );
			delete_option( 'external_updates-' . Dashboard_Widget::WIDGET_NAME );
		}
	}

	register_deactivation_hook(
		__FILE__,
		'wpw_dashboard_widget\Dashboard_Widget::deactivate'
	);

	register_uninstall_hook(
		__FILE__,
		'wpw_dashboard_widget\Dashboard_Widget::uninstall'
	);

	new Dashboard_Widget();
}

if ( ! function_exists( 'dd' ) ) {
	function dd( $var ) {
		var_dump( $var );
		exit(1);
	}
}