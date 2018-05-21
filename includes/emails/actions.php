<?php
/**
 * Email Actions
 *
 * @package     RPG
 * @subpackage  Emails
 * @copyright   Copyright (c) 2015, WP-Ronin
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger the sending of a Test Email
 *
 * @since 1.5
 * @param array $data Parameters sent from Settings page
 * @return void
 */
function kodiak_send_test_email( $data ) {
	if ( ! wp_verify_nonce( $data['_wpnonce'], 'kodiak-giftcard-test-email' ) ) {
		return;
	}

	// Send a test email
	kodiak_email_test();

	// Remove the test email query arg
	wp_redirect( remove_query_arg( 'kodiak_action' ) ); exit;
}
add_action( 'kodiak_send_test_email', 'kodiak_send_test_email' );

/**
 * Email the download link(s) and payment confirmation to the admin accounts for testing.
 *
 * @since 1.5
 * @return void
 */
function kodiak_email_test() {
	$from_name   = kodiak_do_email_tags( kodiak_get_option( 'kodiak_giftcard_email_from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ), 0);
	$from_name   = apply_filters( 'kodiak_giftcard_email_from_name', $from_name, 0, array() );

	$from_email  = kodiak_get_option( 'kodiak_giftcard_email_from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kodiak_giftcard_email_from_email', $from_email, 0, array() );

	$subject     = kodiak_get_option( 'kodiak_giftcard_email_subject', __( 'Gift Card Recieved', 'wpkodiak_giftcards' ) );
	$subject     = apply_filters( 'kodiak_giftcard_email_subject', wp_strip_all_tags( $subject ), 0 );
	$subject     = kodiak_do_email_tags( $subject, 0 );

	$heading     = kodiak_get_option( 'kodiak_giftcard_email_heading', __( 'Gift Card Recieved', 'wpkodiak_giftcards' ) );
	$heading     = apply_filters( 'kodiak_giftcard_heading', kodiak_do_email_tags( $heading, 0 ), 0, array() );

	$attachments = apply_filters( 'kodiak_giftcard_attachments', array(), 0, array() );

	$message     = kodiak_do_email_tags( kodiak_get_email_body_content( 0, array() ), 0 );

	$emails = KODIAK_GIFTCARDS()->emails;
	$emails->__set( 'from_name' , $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading'   , $heading );

	$headers = apply_filters( 'kodiak_giftcard_headers', $emails->get_headers(), 0, array() );
	$emails->__set( 'headers', $headers );

	$emails->send( kodiak_get_admin_notice_emails(), $subject, $message, $attachments );
}

function kodiak_get_meta ( $giftcard_id, $meta_key = '_wpr_giftcard', $single = true ) {
    $giftcard = new KODIAK_Giftcard( );
    $meta = get_post_meta( $giftcard_id, $meta_key, $single );
    return $meta;
}

/**
 * Email the download link(s) and payment confirmation to the buyer in a
 * customizable Purchase Receipt
 *
 * @since 1.0
 * @since 2.8 - Add parameters for RPG_Payment and RPG_Customer object.
 *
 * @param int          $giftcard_id   Payment ID
 * @param bool         $admin_notice Whether to send the admin email notification or not (default: true)
 * @param RPG_Payment  $giftcard      Payment object for giftcard ID.
 * @param RPG_Customer $customer     Customer object for associated giftcard.
 * @return void
 */
function kodiak_email( $giftcard_id, $admin_notice = true, $to_email = '', $giftcard = null, $customer = null ) {
	if ( is_null( $giftcard ) ) {
		//$giftcard = new KODIAK_Giftcard( $giftcard_id );
		$giftcard = wpr_get_giftcard( $giftcard_id );
	}

	$giftcard_data = kodiak_get_meta( $giftcard_id, '_wpr_giftcard', true );

	$from_name = kodiak_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name = apply_filters( 'edd_purchase_from_name', $from_name, $giftcard_id, $giftcard_data );
	$from_name = kodiak_do_email_tags( $from_name, $giftcard_id );

	$from_email = kodiak_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email = apply_filters( 'edd_purchase_from_address', $from_email, $giftcard_id, $giftcard_data );

	if ( empty( $to_email ) ) {
		$to_email = $giftcard->email;
	}

	$subject = kodiak_get_option( 'purchase_subject', __( 'Purchase Receipt', 'kodiak-giftcards' ) );
	$subject = apply_filters( 'edd_purchase_subject', wp_strip_all_tags( $subject ), $giftcard_id );
	$subject = wp_specialchars_decode( kodiak_do_email_tags( $subject, $giftcard_id ) );

	$heading = kodiak_get_option( 'purchase_heading', __( 'Purchase Receipt', 'kodiak-giftcards' ) );
	$heading = apply_filters( 'edd_purchase_heading', $heading, $giftcard_id, $giftcard_data );
	$heading = kodiak_do_email_tags( $heading, 0 );
	error_log( $heading, true );
	$attachments = apply_filters( 'edd_receipt_attachments', array(), $giftcard_id, $giftcard_data );
	$message = kodiak_do_email_tags( kodiak_get_email_body_content( $giftcard_id, $giftcard_data ), $giftcard_id );

	$emails = KODIAK_GIFTCARDS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'edd_receipt_headers', $emails->get_headers(), $giftcard_id, $giftcard_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );
}


/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the RPG Settings)
 *
 * @since 1.0
 * @return mixed
 */
function kodiak_get_admin_notice_emails() {
	$emails = kodiak_get_option( 'admin_notice_emails', false );
	$emails = strlen( trim( $emails ) ) > 0 ? $emails : get_bloginfo( 'admin_email' );
	$emails = array_map( 'trim', explode( "\n", $emails ) );

	return apply_filters( 'kodiak_admin_notice_emails', $emails );
}
