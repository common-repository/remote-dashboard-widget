<?php

namespace wpw_dashboard_widget;

defined('ABSPATH') || exit;

/**
 * Class Widget
 * @package wpw_dashboard_widget
 */
class Widget
{
	const API_URL = 'https://wpdashboardwidget.com/api/v1/';
	const API_URL_DEV = 'https://wpdashboardwidget.test/api/v1/';
	const KEY_CONTENT = 'body';
	const KEY_TITLE = 'title';
	const KEY_TEMPLATE = 'template';
	const KEY_LINK_COLOR = 'link-color';
	const KEY_TITLE_COLOR = 'title-color';
	const TOKEN_PREFIX_DEV = 'test_';
	const TRANSIENT_NAME = 'wp_dashboard_widget';
	const TRANSIENT_DEFAULT_EXPIRATION_IN_SECONDS = 86400;

	private $remote_settings = [];
	private $token = '';

	/**
	 * Widget constructor.
	 */
	public function __construct() {
		$screen = get_current_screen();
		if ( 'dashboard' === $screen->id ) {
			$remote_widget_settings = [];
			$token = cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_TOKEN );
			if ( $token ) {
				$this->token = $token;
			}

			if ( $this->isUseTransients() ) {
				$remote_widget_settings = get_transient( self::TRANSIENT_NAME );
			}

			if ( empty( $remote_widget_settings ) ) {
				$remote_widget_settings = json_decode( $this->get_json(), true );

				// JSON cannot be decoded in test environment.
				if ( is_null( $remote_widget_settings ) && $this->isTestToken() ) {
					$remote_widget_settings = [
						self::KEY_TITLE   => Dashboard_Widget::WIDGET_TITLE,
						self::KEY_CONTENT => wp_sprintf(
							__( 'JSON error. Is <strong>debugbar</strong> active %s?', Dashboard_Widget::TEXT_DOMAIN ),
							wp_sprintf(
								'<a href="%s" target="_blank">%s</a>',
								esc_url( str_replace( 'api/v1/', '', self::API_URL_DEV ) ),
								esc_html( __( 'in the admin environment', Dashboard_Widget::TEXT_DOMAIN ) )
							)
						)
					];
				}

				// JSON cannot be decoded.
				if ( is_null( $remote_widget_settings ) ) {
					$remote_widget_settings = [
						self::KEY_TITLE   => Dashboard_Widget::WIDGET_TITLE,
						self::KEY_CONTENT => wp_sprintf(
							__( 'Could not obtain valid JSON', Dashboard_Widget::TEXT_DOMAIN ),
							esc_url( str_replace( 'api/v1/', '', self::API_URL_DEV ) )
						)
					];
				}

				// Set Transient
				if ( $this->isUseTransients() && ! empty( $this->token ) && $remote_widget_settings ) {
					$expiration = cmb2_get_option( OptionsPage::KEY_OPTION,
						OptionsPage::KEY_FIELD_TRANSIENT_EXPIRATION );
					if ( ! $expiration ) {
						$expiration = self::TRANSIENT_DEFAULT_EXPIRATION_IN_SECONDS;
					}

					set_transient( self::TRANSIENT_NAME, $remote_widget_settings, $expiration );
				}
			}

			if ( $remote_widget_settings ) {
				$this->remote_settings = $remote_widget_settings;
			}
		}
	}

	/**
	 * @return void
	 */
	public function add(): void {
		if ( $this->remote_settings ) {
			$this->add_widget_to_dashboard( $this->remote_settings[ self::KEY_TITLE ], $this->remote_settings[ self::KEY_CONTENT ] );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function get( string $key ): string {
		if ( array_key_exists( $key, $this->remote_settings ) ) {
			return $this->remote_settings[ $key ];
		}

		return '';
	}

	/**
	 * @param  string  $title
	 * @param  string  $content
	 *
	 * @return void
	 */
	public function add_widget_to_dashboard( string $title, string $content ): void {
		$config = \HTMLPurifier_Config::createDefault();
		$purifier = new \HTMLPurifier( $config );
		$content = $purifier->purify( $content, [
			'HTML.AllowedComments' => [
				Dashboard_Widget::COMPANY_URL,
				Dashboard_Widget::COMPANY_URL_TEST,
			],
			'URI.AllowedSchemes' => [
				'https' => true,
				'http' => true,
				'mailto' => true,
				'tel' => true,
				'data' => true,
			]
		] );

		wp_add_dashboard_widget(
			Dashboard_Widget::WIDGET_NAME,
			__(
				sprintf( '<span class="dashboard-widget-title">%s</span>', esc_htmL( $title ) ),
				Dashboard_Widget::TEXT_DOMAIN
			),
			function () use ( $content ) {
				_e( $content, Dashboard_Widget::TEXT_DOMAIN );
			},
			null,
			null,
			( cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_CONTEXT ) ?: 'normal' ),
			( cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_PRIORITY ) ?: 'high' )
		);
	}

	/**
	 * @return string
	 */
	private function get_json(): string {
		if ( empty( $this->token ) ) {
			return $this->get_placeholder( __( 'no token set', Dashboard_Widget::TEXT_DOMAIN ) );
		}

		$pieces = explode( '//', get_site_url() );

		$args = array();
		if ( $this->isTestToken() ) {
			$args[ 'sslverify' ] = false;
		}

		$token = str_replace( self::TOKEN_PREFIX_DEV, '', $this->token );

		$response = wp_remote_get(
			esc_url_raw( $this->get_api_url() . 'get/' . $token . '/' . urlencode( $pieces[1] ) ),
			$args
		);

		if ( ! is_array( $response ) ) {
			return $this->get_placeholder( __( 'token result is empty', Dashboard_Widget::TEXT_DOMAIN ) );
		}

		if ( in_array( $response['body'], [ 'invalid token', 'something went wrong' ] ) ) {
			return $this->get_placeholder( $response['body'] );
		}

		if ( $response['response']['message'] === 'widget not found' ) {
			return $this->get_placeholder( $response['body'] );
		}

		return $response['body'];
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	private function get_placeholder( string $message ): string {
		return json_encode( [
			self::KEY_TITLE   => Dashboard_Widget::WIDGET_TITLE,
			self::KEY_CONTENT => sprintf(
				'<p>%s</p><p>%s</p>',
				esc_html( ucfirst( $message ) ),
				sprintf(
					__( 'You can configure your widget %s', Dashboard_Widget::TEXT_DOMAIN ),
					sprintf(
						'<a class="dashboard-widget-link" href="%s">%s</a>',
						esc_url ( admin_url( 'options-general.php?page=' . urlencode( OptionsPage::KEY_OPTION ) ) ),
						__( 'here', Dashboard_Widget::TEXT_DOMAIN) )
				)
			)
		] );
	}

	/**
	 * @return string
	 */
	private function get_api_url(): string {
		$api_url = self::API_URL;

		if ( $this->isTestToken() ) {
			$api_url = self::API_URL_DEV;
		}

		return $api_url;
	}

	/**
	 * @return bool
	 */
	private function isTestToken(): bool
	{
		return substr( $this->token, 0, 5 ) === self::TOKEN_PREFIX_DEV;
	}

	/**
	 * @return bool
	 */
	private function isUseTransients(): bool {
		$is_transients_disabled = cmb2_get_option( OptionsPage::KEY_OPTION, OptionsPage::KEY_FIELD_DISABLE_TRANSIENTS );

		return ( false === $is_transients_disabled );
	}
}