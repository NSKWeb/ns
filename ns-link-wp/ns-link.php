<?php
/**
 * Plugin Name:       NS Link — Control Layer
 * Plugin URI:        https://github.com/NSKWeb/ns
 * Description:         WP Safelink ka FREE alternative. Ek simple WordPress page/post par ek "control layer" inject karta hai: timer ring, "Scroll Down to Continue" hint, Continue / Open-Your-Link button, ad-slot containers, edition indicator. Ads aur article content aapke apne WordPress / cPanel se control hote hain.
 * Version:            0.1.0
 * Requires at least:   5.8
 * Requires PHP:       7.4
 * Author:              NSKWeb
 * Author URI:          https://github.com/NSKWeb
 * License:             GPL-2.0-or-later
 * Text Domain:         ns-link
 *
 * @package NS_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NSLINK_VERSION', '0.1.0' );

/**
 * NS_Link_Control — main plugin class.
 Scope: sirf control layer.
 */
final class NS_Link_Control {

	/** @var NS_Link_Control|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this,'register_assets' ) );
		add_action( 'wp_footer', array( $this,'inject_control_layer' ) );
		add_action( 'admin_menu', array( $this,'register_settings_page' ) );
		add_action( 'admin_init', array( $this,'register_settings' ) );
	}

	/* ---------------------------------------------------------------------
	 * ASSETS
	 * ------------------------------------------------------------------- */

	public function register_assets() {
		wp_register_style( 'ns-link', plugins_url( 'assets/css/ns-link.css', __FILE__ ), array(), NSLINK_VERSION );
		wp_register_script( 'ns-link', plugins_url( 'assets/js/ns-link.js', __FILE__ ), array(), NSLINK_VERSION, true );
	}

	/* ---------------------------------------------------------------------
	 * RENDER DECISION
	 * ------------------------------------------------------------------- */

	private function should_render() {
		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';
		$next = isset( $_GET['next'] ) ? esc_url_raw( wp_unslash( $_GET['next'] ) ) : '';
		$done = isset( $_GET['done'] ) ? sanitize_text_field( wp_unslash( $_GET['done'] ) ) : '';
		return ( '' !== $step || '' !== $next || '' !== $done );
	}

	/* ---------------------------------------------------------------------
	 * INJECT CONTROL LAYER
	 * ------------------------------------------------------------------- */

	public function inject_control_layer() {
		if ( ! $this->should_render() ) {
			return;
		}

		$s = $this->get_settings();

		$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 0;
		$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : 4;
		$wait   = isset( $_GET['wait'] ) ? absint( $_GET['wait'] ) : absint( $s['default_wait'] );
		$next   = isset( $_GET['next'] ) ? esc_url_raw( wp_unslash( $_GET['next'] ) ) : '';
		$done   = isset( $_GET['done'] ) ? 1 : 0;
		$dest   = isset( $_GET['dest'] ) ? esc_url_raw( wp_unslash( $_GET['dest'] ) ) : '';

		if ( $wait < 3 ) { $wait = 3; }
		if ( $wait > 120 ) { $wait = 120; }
		if ( $step < 1 ) { $step = 1; }

		$data = array(
			'step'         => $step,
			'total'        => $total,
			'wait'         => $wait,
			'final'        => (bool)$done,
			'finishText'  => $done ? $s['final_line_text'] : $s['continue_line_text'],
			'finishHint'   => $done ? $s['final_hint'] : $s['continue_hint'],
			'buttonLabel'  => $done ? $s['final_button_label'] : $s['continue_button_label'],
			'buttonTarget'  => $done ? $dest : $next,
			'autoScroll'  => (bool)$s['auto_scroll'] && $done,
			'showAds'    => (bool)$s['show_ad_slots'],
			'adTop'      => (bool)$s['ad_top'],
			'adMid'      => (bool)$s['ad_mid'],
			'adFoot'     => (bool)$s['ad_foot'],
			'stickyBar'  => (bool)$s['sticky_bar'],
			'progressRule' => (bool)$s['progress_rule'],
			'skip'      => isset( $_GET['skip'] ) ? 1 : 0,
			'themeMode'  => $s['theme_mode'],
		);

		wp_enqueue_style( 'ns-link' );
		wp_enqueue_script( 'ns-link' );
		wp_localize_script( 'ns-link', 'NSLINK', $data );

		$this->render_skeleton( $data );
	}

	/* ---------------------------------------------------------------------
	 * SKELETON OUTPUT
	 * ------------------------------------------------------------------- */

	private function render_skeleton( $data ) {
		$classes = array( 'nslink-layer','nslink-mode-' . $data['themeMode'] );
		if ( $data['final'] ) { $classes[] = 'nslink-final'; }
		if ( $data['autoScroll'] ) { $classes[] = 'nslink-autoscroll'; }
		if ( $data['skip'] ) { $classes[] = 'nslink-skip'; }

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="nslink-layer" data-wait="' . esc_attr( (string)$data['wait'] ) . '" data-final="' . esc_attr( $data['final'] ? '1' : '0' ) . '">';

		// Masthead.
_
		echo '<div class="nslink-mast">';
		echo '<span class="nslink-brand">NS LINK <em>NEWS</em></span>';
		echo '<span class="nslink-edition">' . esc_html( sprintf( 'EDITION %1$d / %2$d', $data['step'], $data['total'] ) ) . '</span>';
		echo '</div>';

		// Header ads (2 slots).
_
		if ( $data['showAds'] && $data['adTop'] ) {
			echo '<div class="nslink-adrow">';
			echo '<div class="nslink-ad nslink-ad-top nslink-ad-1"></div>';
			echo '<div class="nslink-ad nslink-ad-top nslink-ad-2"></div>';
			echo '</div>';
		}

		echo '<div class="nslink-body">';

		if ( $data['showAds'] && $data['adMid'] ) {
			echo '<div class="nslink-ad nslink-ad-mid nslink-ad-mid-up"></div>';
		}

		echo '<div class="nslink-timer" role="timer" aria-live="polite">';
		echo '<svg class="nslink-ring" width="120" height="120" viewBox="0 0 120 120" aria-hidden="true">';
		echo '<circle class="nslink-ring-bg" cx="60" cy="60" r="54"></circle>';
		echo '<circle class="nslink-ring-fg" cx="60" cy="60" r="54"></circle>';
		echo '</svg>';
		echo '<div class="nslink-num">' . esc_html( (string)$data['wait'] ) . '</div>';
		echo '<div class="nslink-unit">' . esc_html__( 'seconds','ns-link' ) . '</div>';
		echo '</div>';

		echo '<div class="nslink-finish" aria-hidden="true">';
		echo '<span class="nslink-finish-arrow"></span>';
		echo '<span class="nslink-finish-text"></span>';
		echo '</div>';

		if ( $data['showAds'] && $data['adMid'] ) {
			echo '<div class="nslink-ad nslink-ad-mid nslink-ad-mid-down"></div>';
		}

		echo '</div>'; // .nslink-body

		if ( $data['progressRule'] && ! $data['final'] ) {
			echo '<div class="nslink-progress"><span></span></div>';
		}

		echo '</div>'; // .nslink-layer

		if ( $data['stickyBar'] ) {
			echo '<div class="nslink-sticky" id="nslink-sticky">';
			if ( $data['showAds'] && $data['adFoot'] ) {
				echo '<div class="nslink-ad nslink-ad-foot"></div>';
			}
			echo '<button type="button" class="nslink-btn" disabled>' . esc_html( $data['buttonLabel'] ) . '</button>';
			echo '</div>';
		}
	}

	/* ---------------------------------------------------------------------
	 * SETTINGS
	 * ------------------------------------------------------------------- */

	private function defaults() {
		return array(
			'default_wait'         => 8,
			'continue_line_text'   => __( 'Scroll Down to Continue','ns-link' ),
			'final_line_text'      => __( 'Continue to Link','ns-link' ),
			'continue_button_label' => __( 'Continue →','ns-link' ),
			'final_button_label'    => __( '🎁 Open Your Link →','ns-link' ),
			'continue_hint'        => __( 'the next edition awaits','ns-link' ),
			'final_hint'           => __( 'your destination awaits','ns-link' ),
			'theme_mode'          => 'custom',
			'auto_scroll'         => 1,
			'show_ad_slots'      => 1,
			'ad_top'             => 1,
			'ad_mid'             => 1,
			'ad_foot'            => 1,
			'sticky_bar'         => 1,
			'progress_rule'      => 1,
		);
	}

	private function get_settings() {
		$saved = get_option( 'nslink_settings', array() );
		return wp_parse_args( (array)$saved, $this->defaults() );
	}

	public function sanitize_settings( $input ) {
		$clean = $this->defaults();
		if ( ! is_array( $input ) ) { return $clean; }

		$clean['default_wait'] = isset( $input['default_wait'] ) ? absint( $input['default_wait'] ) : $clean['default_wait'];
		if ( $clean['default_wait'] < 3 ) { $clean['default_wait'] = 3; }
		if ( $clean['default_wait'] > 120 ) { $clean['default_wait'] = 120; }

		foreach ( array( 'continue_line_text','final_line_text','continue_button_label','final_button_label','continue_hint','final_hint' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $clean[ $key ];
		}

		$clean['theme_mode']   = ( isset( $input['theme_mode'] ) && 'auto' === $input['theme_mode'] ) ? 'auto' : 'custom';
		$clean['auto_scroll']    = empty( $input['auto_scroll'] ) ? 0 : 1;
		$clean['show_ad_slots']  = empty( $input['show_ad_slots'] ) ? 0 : 1;
		$clean['ad_top']        = empty( $input['ad_top'] ) ? 0 :  1;
		$clean['ad_mid']        = empty( $input['ad_mid'] ) ? 0 : 1;
		$clean['ad_foot']       = empty( $input['ad_foot'] ) ? 0 : 1;
		$clean['sticky_bar']     = empty( $input['sticky_bar'] ) ? 0 : 1;
		$clean['progress_rule']   = empty( $input['progress_rule'] ) ? 0 :  1;

		return $clean;
	}

	public function register_settings() {
		register_setting( 'nslink_settings','nslink_settings', array(
			'type'               => 'array',
			'sanitize_callback' => array( $this,'sanitize_settings' ),
			'default'            => $this->defaults(),
		) );
	}

	public function register_settings_page() {
		add_options_page( __( 'NS Link','ns-link' ), __( 'NS Link','ns-link' ), 'manage_options','ns-link', array( $this,'render_settings_page' ) );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$s = $this->get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'NS Link — Control Layer','ns-link' ); ?></h1>
			<p class="description"><?php esc_html_e( 'WP Safelink ka FREE alternative: timer ring, scroll-hint, continue/open-link button, ad-slot containers. Ads aur article content aapke WordPress/cPanel se control hote hain. Shortener URL params: ?step&total&wait&next?done.', 'ns-link' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'nslink_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default timer (seconds)','ns-link' ); ?></th>
						<td>
							<input type="number" min="3" max="120" name="nslink_settings[default_wait]" value="<?php echo esc_attr( (string)$s['default_wait'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Har page ka time shortener URL se bhi set ho sakta hai: ?wait=14.','ns-link' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Line — pages 1–3','ns-link' ); ?></th>
						<td><input type="text" class="regular-text" name="nslink_settings[continue_line_text]" value="<?php echo esc_attr( $s['continue_line_text'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Line — final page','ns-link' ); ?></th>
						<td><input type="text" class="regular-text" name="nslink_settings[final_line_text]" value="<?php echo esc_attr( $s['final_line_text'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button — continue','ns-link' ); ?></th>
						<td><input type="text" class="regular-text" name="nslink_settings[continue_button_label]" value="<?php echo esc_attr( $s['continue_button_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button — final','ns-link' ); ?></th>
						<td><input type="text" class="regular-text" name="nslink_settings[final_button_label]" value="<?php echo esc_attr( $s['final_button_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Theme mode','ns-link' ); ?></th>
						<td>
							<select name="nslink_settings[theme_mode]">
								<option value="custom" <?php selected( $s['theme_mode'],'custom' ); ?>><?php esc_html_e( 'Custom (Literary Neo-Brutalism)','ns-link' ); ?></option>
								<option value="auto" <?php selected( $s['theme_mode'],'auto' ); ?>><?php esc_html_e( 'Auto (WP theme colors)','ns-link' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-scroll (final)','ns-link' ); ?></th>
						<td><label><input type="checkbox" name="nslink_settings[auto_scroll]" value="1" <?php checked( $s['auto_scroll'],1 ); ?> /> <?php esc_html_e( 'Final page par timer khtm hote hi footer/button tak khud scroll.','ns-link' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ad-slot containers','ns-link' ); ?></th>
						<td>
							<label><input type="checkbox" name="nslink_settings[show_ad_slots]" value="1" <?php checked( $s['show_ad_slots'],1 ); ?> /> <?php esc_html_e( 'Empty ad-slot divs dikhao (codes aap daaloge)','ns-link' ); ?></label><br>
							<label style="margin-left:16px"><input type="checkbox" name="nslink_settings[ad_top]" value="1" <?php checked( $s['ad_top'],1 ); ?> /> <?php esc_html_e( 'Header me 2 slots','ns-link' ); ?></label><br>
							<label style="margin-left:16px"><input type="checkbox" name="nslink_settings[ad_mid]" value="1" <?php checked( $s['ad_mid'],1 ); ?> /> <?php esc_html_e( 'Timer ke upar aur neeche','ns-link' ); ?></label><br>
							<label style="margin-left:16px"><input type="checkbox" name="nslink_settings[ad_foot]" value="1" <?php checked( $s['ad_foot'],1 ); ?> /> <?php esc_html_e( 'Footer slot','ns-link' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sticky continue bar','ns-link' ); ?></th>
						<td><label><input type="checkbox" name="nslink_settings[sticky_bar]" value="1" <?php checked( $s['sticky_bar'],1 ); ?> /> <?php esc_html_e( 'Mobile par button hamesha neeche chipka rahe.','ns-link' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Progress rule','ns-link' ); ?></th>
						<td><label><input type="checkbox" name="nslink_settings[progress_rule]" value="1" <?php checked( $s['progress_rule'],1 ); ?> /> <?php esc_html_e( 'Timer ke saath bharne wali patli rule.','ns-link' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

NS_Link_Control::instance();