<?php
/**
 * Admin Page for Woomorrintegration Plugin
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add submenu page under Settings
 */
function woomorrintegration_add_admin_page() {
	add_options_page(
		__( 'Woomorrintegration Settings', 'woomorrintegration' ),
		__( 'Woomorrintegration', 'woomorrintegration' ),
		'manage_options',
		'woomorrintegration_settings',
		'woomorrintegration_render_admin_page'
	);
}
add_action( 'admin_menu', 'woomorrintegration_add_admin_page' );

/**
 * Callback function to render admin page
 */
function woomorrintegration_render_admin_page() {
	$nonce = isset( $_POST['woomorrintegration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woomorrintegration_nonce'] ) ) : '';

	if ( isset( $_POST['submit'] ) && wp_verify_nonce( $nonce, 'woomorrintegration_settings_nonce' ) ) {
		// Save the API secret key.
		if ( isset( $_POST['api_secret_key'] ) ) {
			$api_secret_key = sanitize_text_field( wp_unslash( $_POST['api_secret_key'] ) );
			update_option( 'woomorrintegration_api_secret_key', sanitize_text_field( $api_secret_key ) );
			echo '<div class="updated"><p>' . esc_html__( 'API secret key saved successfully!', 'woomorrintegration' ) . '</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="">
			<?php wp_nonce_field( 'woomorrintegration_settings_nonce', 'woomorrintegration_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'API Secret Key', 'woomorrintegration' ); ?></th>
					<td>
						<input type="text" name="api_secret_key" value="<?php echo esc_attr( get_option( 'woomorrintegration_api_secret_key' ) ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Changes', 'woomorrintegration' ), 'primary', 'submit', true ); ?>
		</form>
	</div>
	<?php
}
