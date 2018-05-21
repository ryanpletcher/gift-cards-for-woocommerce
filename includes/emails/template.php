<?php
/**
 * Template
 *
 * @package     Outlaw Gift Cards
 * @copyright   Copyright (c) 2015, WP Outlaw
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/


/**
 * Gets all the email templates that have been registerd. The list is extendable
 * and more templates can be added.
 *
 * As of 2.0, this is simply a wrapper to KODIAK_Email_Templates->get_templates()
 *
 * @since 1.0.8.2
 * @return array $templates All the registered email templates
 */
function kodiak_get_email_templates() {
	$templates = new KODIAK_Giftcard_Emails;
	return $templates->get_templates();
}

/**
 * Displays the email preview
 *
 * @since 2.1
 * @return void
 */
function kodiak_display_email_template_preview() {

	if( empty( $_GET['kodiak_action'] ) ) {
		return;
	}

	if( 'preview_email' !== $_GET['kodiak_action'] ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	KODIAK_GIFTCARDS()->emails->heading = kodiak_email_preview_template_tags( kodiak_get_option( 'kodiak_giftcard_email_heading', __( 'Gift Card Email', 'wpkodiak_giftcards' ) ) );
	echo KODIAK_GIFTCARDS()->emails->build_email( kodiak_email_preview_template_tags( kodiak_get_email_body_content( 0, array() ) ) );

	exit;
}
add_action( 'template_redirect', 'kodiak_display_email_template_preview' );


/**
 * Email Template Body
 *
 * @since 1.0.8.2
 * @param int $payment_id Payment ID
 * @param array $payment_data Payment Data
 * @return string $email_body Body of the email
 */
function kodiak_get_email_body_content( $payment_id = 0, $payment_data = array() ) {
	$default_email_body = __( "Dear", "wpkodiak_giftcards" ) . " {recipient_name},\n\n";
	$default_email_body .= "{name}" . __( " has sent you a gift card.", "wpkodiak_giftcards" ) . "\n\n";
	$default_email_body .= "{giftcard_message}\n\n";
	$default_email_body .= "Card Number: {giftcard_number}\n\n";

	$email = kodiak_get_option( 'kodiak_giftcard_email_template', false );
	$email = $email ? stripslashes( $email ) : $default_email_body;


	$email_body = apply_filters( 'kodiak_email_template_wpautop', true ) ? wpautop( $email ) : $email;

	$email_body = apply_filters( 'kodiak_giftcard_email_' . KODIAK_GIFTCARDS()->emails->get_template(), $email_body, $payment_id, $payment_data );

	return apply_filters( 'kodiak_giftcard_email', $email_body, $payment_id, $payment_data );
}




function kodiak_email_preview_template_tags( $message ) {
	$file_urls = esc_html( trailingslashit( get_site_url() ) . 'test.zip?test=key&key=123' );

	$price = wc_price(10.50);

	//$gateway = kodiak_get_gateway_admin_label( kodiak_get_default_gateway() );

	$receipt_id = strtolower( md5( uniqid() ) );

	$notes = __( 'These are some sample notes added to a product.', 'wpkodiak_giftcards' );

	$tax = wc_price(1.00);

	$sub_total = wc_price(9.50);

	$payment_id = rand(1, 100);

	$user = wp_get_current_user();

	$message = str_replace( '{giftcard_number}', "765445353456765", $message );
	$message = str_replace( '{giftcard_message}', __( 'Sample Gift Card Message', 'wpkodiak_giftcards' ), $message );
	$message = str_replace( '{giftcard_balance}', $price, $message );
	$message = str_replace( '{giftcard_expiration_date}', __( 'Sample Gift Card Message', 'wpkodiak_giftcards' ), $message );
	$message = str_replace( '{file_urls}', $file_urls, $message );
	$message = str_replace( '{recipient_name}', $user->display_name, $message );
	$message = str_replace( '{recipient_fullname}', $user->display_name, $message );
	$message = str_replace( '{name}', $user->display_name, $message );
	$message = str_replace( '{fullname}', $user->display_name, $message );
 	$message = str_replace( '{username}', $user->user_login, $message );
	$message = str_replace( '{date}', date( get_option( 'date_format' ), current_time( 'timestamp' ) ), $message );
	$message = str_replace( '{subtotal}', $sub_total, $message );
	$message = str_replace( '{tax}', $tax, $message );
	$message = str_replace( '{price}', $price, $message );
	$message = str_replace( '{receipt_id}', $receipt_id, $message );
	//$message = str_replace( '{payment_method}', $gateway, $message );
	$message = str_replace( '{sitename}', get_bloginfo( 'name' ), $message );
	$message = str_replace( '{product_notes}', $notes, $message );
	$message = str_replace( '{payment_id}', $payment_id, $message );
	//$message = str_replace( '{receipt_link}', kodiak_email_tag_receipt_link( $payment_id ), $message );

	$message = apply_filters( 'kodiak_giftcard_email_preview_template_tags', $message );

	return apply_filters( 'kodiak_giftcard_email_template_wpautop', true ) ? wpautop( $message ) : $message;
}


/**
 * Retrieves a template part
 *
 * @since v1.2
 *
 * Taken from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 * @param bool   $load
 *
 * @return string
 *
 * @uses kodiak_locate_template()
 * @uses load_template()
 * @uses get_template_part()
 */
function kodiak_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	$load_template = apply_filters( 'kodiak_giftcard_allow_template_part_' . $slug . '_' . $name, true );
	if ( false === $load_template ) {
		return '';
	}

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'kodiak_giftcard_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return kodiak_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 * @since 1.2
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *   Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function kodiak_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach( kodiak_get_theme_template_paths() as $template_path ) {

			if( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}

		if( $located ) {
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Returns a list of paths to check for template locations
 *
 * @since 1.8.5
 * @return mixed|void
 */
function kodiak_get_theme_template_paths() {

	$template_dir = kodiak_get_theme_template_dir_name();

	$file_paths = array(
		1 => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10 => trailingslashit( get_template_directory() ) . $template_dir,
		100 => kodiak_get_templates_dir()
	);

	$file_paths = apply_filters( 'kodiak_template_paths', $file_paths );

	// sort the file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

/**
 * Returns the template directory name.
 *
 * Themes can filter this by using the edd_templates_dir filter.
 *
 * @since 1.6.2
 * @return string
*/
function kodiak_get_theme_template_dir_name() {
	return trailingslashit( apply_filters( 'kodiak_giftcard_templates_dir', 'kodiak_templates' ) );
}

/**
 * Returns the path to the Giftcard templates directory
 *
 * @since 1.2
 * @return string
 */
function kodiak_get_templates_dir() {
	return WPKODIAK_DIR . 'templates';
}

/**
 * Returns the URL to the Giftcard templates directory
 *
 * @since 1.3.2.1
 * @return string
 */
function kodiak_get_templates_url() {
	return WPKODIAK_URL . 'templates';
}
