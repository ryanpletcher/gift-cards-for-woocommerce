<?php
/**
 * Email tags are wrapped in { }
 * A few examples:
 * {download_list}
 * {name}
 * {sitename}
 * To replace tags in content, use: kodiak_do_email_tags( $content, giftcard_id );
 * To add tags, use: kodiak_add_email_tag( $tag, $description, $func ). Be sure to wrap kodiak_add_email_tag()
 * in a function hooked to the 'kodiak_add_email_tags' action
 * @package     RPG
 * @subpackage  Emails
 * @copyright   Copyright (c) 2015, WP-Ronin
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 * @author      Barry Kooij
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class KODIAK_Giftcard_Email_Template_Tags {
	/**
	 * Container for storing all tags
	 * @since 1.9
	 */
	private $tags;

	/**
	 * Payment ID
	 * @since 1.9
	 */
	private $giftcard_id;

	/**
	 * Add an email tag
	 * @since 1.9
	 * @param string   $tag  Email tag to be replace in email
	 * @param callable $func Hook to run when email tag is found
	 */
	public function add( $tag, $description, $func ) {
		if ( is_callable( $func ) ) {
			$this->tags[$tag] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func
			);
		}
	}

	/**
	 * Remove an email tag
	 * @since 1.9
	 * @param string $tag Email tag to remove hook from
	 */
	public function remove( $tag ) {
		unset( $this->tags[$tag] );
	}

	/**
	 * Check if $tag is a registered email tag
	 * @since 1.9
	 * @param string $tag Email tag that will be searched
	 * @return bool
	 */
	public function email_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}

	/**
	 * Returns a list of all email tags
	 * @since 1.9
	 * @return array
	 */
	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Search content for email tags and filter email tags through their hooks
	 * @param string $content Content to search for email tags
	 * @param int $giftcard_id The payment id
	 * @since 1.9
	 * @return string Content with email tags filtered out.
	 */
	public function do_tags( $content, $giftcard_id ) {

		// Check if there is atleast one tag added
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->giftcard_id = $giftcard_id;

		$new_content = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'do_tag' ), $content );

		$this->giftcard_id = null;

		return $new_content;
	}

	/**
	 * Do a specific tag, this function should not be used. Please use kodiak_do_email_tags instead.
   * @since 1.9
   * @param $m message
   * @return mixed
	 */
	public function do_tag( $m ) {

		// Get tag
		$tag = $m[1];

		// Return tag if tag not set
		if ( ! $this->email_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[$tag]['func'], $this->giftcard_id, $tag );
	}

}

/**
 * Add an email tag
 * @since 1.9
 * @param string   $tag  Email tag to be replace in email
 * @param callable $func Hook to run when email tag is found
 */
function kodiak_add_email_tag( $tag, $description, $func ) {
	KODIAK_GIFTCARDS()->email_tags->add( $tag, $description, $func );
}

/**
 * Remove an email tag
 * @since 1.9
 * @param string $tag Email tag to remove hook from
 */
function kodiak_remove_email_tag( $tag ) {
	KODIAK_GIFTCARDS()->email_tags->remove( $tag );
}

/**
 * Check if $tag is a registered email tag
 * @since 1.9
 * @param string $tag Email tag that will be searched
 * @return bool
 */
function kodiak_email_tag_exists( $tag ) {
	return KODIAK_GIFTCARDS()->email_tags->email_tag_exists( $tag );
}

/**
 * Get all email tags
 * @since 1.9
 * @return array
 */
function kodiak_get_email_tags() {
	return KODIAK_GIFTCARDS()->email_tags->get_tags();
}

/**
 * Get a formatted HTML list of all available email tags
 * @since 1.9
 * @return string
 */
function kodiak_get_emails_tags_list() {
	// The list
	$list = '';

	// Get all tags
	$email_tags = kodiak_get_email_tags();

	// Check
	if ( count( $email_tags ) > 0 ) {
		foreach ( $email_tags as $email_tag ) {
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>'; // Add email tag to list
		}
	}

	// Return the list
	return $list;
}

/**
 * Search content for email tags and filter email tags through their hooks
 * @param string $content Content to search for email tags
 * @param int $giftcard_id The payment id
 * @since 1.9
 * @return string Content with email tags filtered out.
 */
function kodiak_do_email_tags( $content, $giftcard_id ) {

	// Replace all tags
	$content = KODIAK_GIFTCARDS()->email_tags->do_tags( $content, $giftcard_id );

	// Return content
	return $content;
}

/**
 * Load email tags
 * @since 1.9
 */
function kodiak_load_email_tags() {
	do_action( 'kodiak_add_email_tags' );
}
add_action( 'init', 'kodiak_load_email_tags', -999 );

/**
 * Add default RPG email template tags
 * @since 1.9
 */
function kodiak_setup_email_tags() {

	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'giftcard_number',
			'description' => __( 'The Gift Card Number that was generated', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_gift_card_number'
		),
		array(
			'tag'         => 'giftcard_message',
			'description' => __( 'The message that was entered by the customer that purchased the card.', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_gift_card_message'
		),
		array(
			'tag'         => 'giftcard_balance',
			'description' => __( 'The funds that are on the card.', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_gift_card_balance'
		),
		array(
			'tag'         => 'giftcard_expiration_date',
			'description' => __( 'The date that the gift card expires if it has an experation date.', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_gift_card_expiration'
		),
		array(
			'tag'         => 'name',
			'description' => __( "The buyer's name", 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_name'
		),
		array(
			'tag'         => 'recipient_name',
			'description' => __( "The recipient's name", 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_recipient_name'
		),
		array(
			'tag'         => 'user_email',
			'description' => __( "The buyer's email address", 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_user_email'
		),
// TODO: Coming on next release/
//		array(
//			'tag'         => 'date',
//			'description' => __( 'The date of the purchase', 'wpkodiak_giftcards' ),
//			'function'    => 'kodiak_email_tag_date'
//		),
		array(
			'tag'         => 'giftcard_id',
			'description' => __( 'The unique ID number for this purchase', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_giftcard_id'
		),
		array(
			'tag'         => 'receipt_id',
			'description' => __( 'The unique ID number for this purchase receipt', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_receipt_id'
		),
		array(
			'tag'         => 'sitename',
			'description' => __( 'Your site name', 'wpkodiak_giftcards' ),
			'function'    => 'kodiak_email_tag_sitename'
		),

	);

	// Apply kodiak_email_tags filter
	$email_tags = apply_filters( 'kodiak_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		kodiak_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
	}

}
add_action( 'kodiak_add_email_tags', 'kodiak_setup_email_tags' );

/**
 * Email template tag: giftcard_number
 * The gift card number that was generated
 * @param int $giftcard_id
 * @return string fullname
 */
function kodiak_email_tag_gift_card_number( $giftcard_id ) {
	$card_info =  get_post( $giftcard_id );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info->post_title;
}

/**
 * Email template tag: giftcard_number
 * The gift card number that was generated
 * @param int $giftcard_id
 * @return string fullname
 */
function kodiak_email_tag_gift_card_number_plain( $giftcard_id ) {
	return $giftcard_id;
}

/**
 * Email template tag: giftcard_message
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_gift_card_message( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info["note"];
}

/**
 * Email template tag: giftcard_balanace
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_gift_card_balance( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return wc_price( $card_info["balance"] );
}

/**
 * Email template tag: giftcard_amount
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_gift_card_amount( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info["amount"];
}

/**
 * Email template tag: giftcard_expiration
 * The buyer's full name, first and last
 * @param int $giftcard_id
 * @return string fullname
 */
function kodiak_email_tag_gift_card_expiration( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info ) ) { return ''; }
	if( empty( $card_info["expiry_date"] ) ) { return ''; }

	$entered_date = date( $card_info["expiry_date"] );
	$entered_date_timestamp = strtotime($entered_date);
	$myDate = date( get_option('date_format'), $entered_date_timestamp );

	return $myDate;
}

/**
 * Email template tag: name
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_name( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info['from'];
}

/**
 * Email template tag: recipient_name
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_recipient_name( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info["to"];
}

/**
 * Email template tag: user_email
 * The buyer's email address
 * @param int $giftcard_id
 * @return string user_email
 */
function kodiak_email_tag_user_email( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info["fromEmail"];
}

/**
 * Email template tag: user_email
 * The buyer's email address
 * @param int $giftcard_id
 * @return string user_email
 */
function kodiak_email_tag_recipient_email( $giftcard_id ) {
	$card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

	if( empty( $card_info) ) {
		return '';
	}

	return $card_info["toEmail"];
}

/**
 * Email template tag: date
 * Date of purchase
 * @param int $giftcard_id
 * @return string date
 */
function kodiak_email_tag_date( $giftcard_id ) {
	$giftcard = new KODIAK_Giftcard( $giftcard_id );

	return date_i18n( get_option( 'date_format' ), strtotime( $giftcard->date ) );
}

/**
 * Email template tag: giftcard_id
 * The buyer's first name
 * @param int $giftcard_id
 * @return string name
 */
function kodiak_email_tag_giftcard_id( $giftcard_id ) {
  $card_info =  get_post_meta( $giftcard_id, '_wpr_giftcard', true );

  if( empty( $card_info) ) {
    return '';
  }

  return $card_info["id"];
}

/**
 * Email template tag: sitename
 * Your site name
 * @param int $giftcard_id
 * @return string sitename
 */
function kodiak_email_tag_sitename( $giftcard_id ) {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}
