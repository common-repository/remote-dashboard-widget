<?php

namespace wpw_dashboard_widget;

defined('ABSPATH' ) || exit;

/**
 * Class SettingsPage
 * @package wpw_dashboard_widget
 */
class OptionsPage
{
	const KEY_METABOX = 'wp_dashboard_widget_options_metabox';
	const KEY_OPTION = 'wp_dashboard_widget_page_options';
	const KEY_FIELD_TOKEN = 'wp_dashboard_widget_token';
	const KEY_FIELD_DISABLE_TRANSIENTS = 'wp_dashboard_widget_disable_transients';
	const KEY_FIELD_TRANSIENT_EXPIRATION = 'wp_dashboard_widget_transient_expiration';
	const KEY_FIELD_CONTEXT = 'wp_dashboard_widget_context';
	const KEY_FIELD_PRIORITY = 'wp_dashboard_widget_priority';
	const KEY_FIELD_ENABLED_ROLES = 'wp_dashboard_widget_enabled_roles';
	const KEY_FIELD_DISABLED_DASHBOARD_WIDGETS = 'wp_dashboard_widget_disabled_widgets';


	/**
	 * SettingsPage constructor.
	 */
	public function __construct() {
		add_action( 'cmb2_before_form', [ $this, 'add_form_instruction' ] );
		add_action( 'cmb2_save_options-page_fields_wp_dashboard_widget_options_metabox', [ $this, 'delete_transient' ], 10, 3 );

		$cmb = new_cmb2_box( array(
			'id'           => self::KEY_METABOX,
			'title'        => $this->get_page_title(),
			'object_types' => [ 'options-page' ],
			'option_key'   => self::KEY_OPTION,
			'parent_slug'  => 'options-general.php',
		) );

		$cmb->add_field( array(
			'name' => __( 'Token', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => wp_sprintf(
				__( 'The WP Dashboard Widget token. Get your token at %s', Dashboard_Widget::TEXT_DOMAIN ),
				wp_sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer nofollow">%s</a>',
					esc_url( trailingslashit( Dashboard_Widget::COMPANY_URL ) ),
					esc_html( trailingslashit( Dashboard_Widget::COMPANY_URL ) )
				) . '.<br>' .
				wp_sprintf(
					__( 'If you\'d prefer not to register right away, use the following demo token: %s and visit your %s', Dashboard_Widget::TEXT_DOMAIN ),
					sprintf( '<code>%s</code>', esc_html( Dashboard_Widget::DEMO_TOKEN ) ),
					sprintf( '<a href="%s">%s</a>.', esc_url( get_dashboard_url() ), __( 'your dashboard', Dashboard_Widget::TEXT_DOMAIN ) )
				)
			),
			'id'   => self::KEY_FIELD_TOKEN,
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Context', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => wp_sprintf(
				__( 'The widget %s', Dashboard_Widget::TEXT_DOMAIN ),
				wp_sprintf(
					'<a href="https://developer.wordpress.org/reference/functions/wp_add_dashboard_widget/" target="_blank">%s</a>',
					__( 'position', Dashboard_Widget::TEXT_DOMAIN )
				)
			),
			'id' => self::KEY_FIELD_CONTEXT,
			'type' => 'select',
			'options' => [ 'normal' => 'normal', 'side' => 'side', 'column3' => 'column3', 'column4' => 'column4' ],
			'default' => 'normal',
		) );

		$cmb->add_field( array(
			'name' => __( 'Priority', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => wp_sprintf(
				__( 'The widget %s', Dashboard_Widget::TEXT_DOMAIN ),
				wp_sprintf(
					'<a href="https://developer.wordpress.org/reference/functions/wp_add_dashboard_widget/" target="_blank">%s</a>',
					__( 'priority', Dashboard_Widget::TEXT_DOMAIN )
				)
			),
			'id' => self::KEY_FIELD_PRIORITY,
			'type' => 'select',
			'options' => [ 'high' => 'high', 'core' => 'core', 'default' => 'default', 'low' => 'low' ],
			'default' => 'default',
		) );

		$cmb->add_field( array(
			'name' => __( 'Disable transients', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => __( 'Disable transient caching of the plugin. Only use this option for testing purposes.', Dashboard_Widget::TEXT_DOMAIN ),
			'id' => self::KEY_FIELD_DISABLE_TRANSIENTS,
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
				'name' => __(  'Transient Expiry', Dashboard_Widget::TEXT_DOMAIN ),
				'desc' => wp_sprintf(
					__( 'The maximum duration (in seconds) for which the cached widget %s will be valid. The existing transient will be deleted when saving the settings on this page.', Dashboard_Widget::TEXT_DOMAIN ),
					wp_sprintf(
						'<a href="https://developer.wordpress.org/apis/transients/" target="_blank">%s</a>',
						__( 'transient', Dashboard_Widget::TEXT_DOMAIN )
					)
				),
				'default' => Widget::TRANSIENT_DEFAULT_EXPIRATION_IN_SECONDS,
				'id' => self::KEY_FIELD_TRANSIENT_EXPIRATION,
				'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Roles able to see the dashboard widget', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => __( 'Select the roles you\'d like to be able to see the dashboard widget. Except for Administrators, unselected roles will not be able to see the dashboard widget.', Dashboard_Widget::TEXT_DOMAIN ),
			'id' => self::KEY_FIELD_ENABLED_ROLES,
			'type' => 'multicheck',
			'options_cb' => [ $this, 'get_roles' ],
		) );

		$cmb->add_field( array(
			'name' => __( 'Disable dashboard widgets', Dashboard_Widget::TEXT_DOMAIN ),
			'desc' => wp_sprintf(
				__( 'Select the dashboard widgets you\'d like to disable. No widgets listed here? Then, first visit %s and return to this page.', Dashboard_Widget::TEXT_DOMAIN ),
				wp_sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_dashboard_url() ),
					__( 'your dashboard', Dashboard_Widget::TEXT_DOMAIN )
				)
			),
			'id' => self::KEY_FIELD_DISABLED_DASHBOARD_WIDGETS,
			'type' => 'multicheck',
			'options_cb' => [ $this, 'get_options' ],
		) );
	}


	/**
	 * @return array
	 */
	public function get_options(): array {
		$field_options = array();
		$registered_dashboard_widgets = get_option( Dashboard_Widget::OPTION_REGISTERED_DASHBOARD_WIDGETS );
		if ( $registered_dashboard_widgets ) {
			foreach ( $registered_dashboard_widgets as $widget_id => $registered_dashboard_widget ) {
				if ( is_array( $registered_dashboard_widget ) ) {
					$field_options[ $widget_id ] = $registered_dashboard_widget['title'];
				}
			}
		}

		return $field_options;
	}


	/**
	 * @return array
	 */
	public function get_roles(): array {
		$roles = array();
		$editable_roles = get_editable_roles();
		foreach( $editable_roles as $key => $role ) {
			if ( 'administrator' !== $key ) {
				$roles[ $key ] = $role['name'];
			}
		}

		return $roles;
	}


	/**
	 * @return void
	 */
	public function add_form_instruction( $cmb_id ): void {
		if ( self::KEY_METABOX === $cmb_id ) {
			echo '<p>';
			echo sprintf(
				__( 'This page allows you to configure your dashboard widget token. Configure your widget content and obtain your token at %s', Dashboard_Widget::TEXT_DOMAIN ),
				sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer nofollow">%s</a>',
					esc_url( trailingslashit( Dashboard_Widget::COMPANY_URL ) ),
					esc_html( trailingslashit( Dashboard_Widget::COMPANY_URL ) )
				)
			);
			echo '</p>';
		}
	}


	/**
	 * @return void
	 */
	public function delete_transient(): void {
		delete_transient( Widget::TRANSIENT_NAME );
	}


	/**
	 * @return string
	 */
	private function get_page_title(): string {
		return
			sprintf(
				__( '%s Settings', Dashboard_Widget::TEXT_DOMAIN ),
				esc_html( Dashboard_Widget::WIDGET_TITLE )
			);
	}
}