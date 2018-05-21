<?php
/**
 * Helper Functions
 *
 * @package     WPR\PluginName\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 1.8.4
 * @global $kodiak_options Array of all the RPG Options
 * @return mixed
 */
function kodiak_get_option( $key = '', $default = false ) {
	$value = get_option( $key );

	if ( $value == "" ) {
		$value = $default;
	}

	$value = apply_filters( 'kodiak_options', $value );
	return apply_filters( 'kodiak_options_' . $key, $value );
}

/**
 * Hooks RPG actions, when present in the $_GET superglobal. Every edd_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
*/
function kodiak_get_actions() {
	$key = ! empty( $_GET['kodiak_action'] ) ? sanitize_key( $_GET['kodiak_action'] ) : false;

	if ( ! empty( $key ) ) {
		do_action( "kodiak_{$key}" , $_GET );
	}
}
add_action( 'init', 'kodiak_get_actions' );


function wpr_upgrade_notice() {
	$wpr_gift_version = get_option( 'wpr_gift_version' );

	if ( ! $wpr_gift_version ) {
		// 2.0.0 is the first version to use this option so we must add it
		$wpr_gift_version = WPKODIAK_VERSION;
	}

	$wpr_gift_version = preg_replace( '/[^0-9.].*/', '', $wpr_gift_version );

	if ( version_compare( $wpr_gift_version, '2.0.0', '<' ) ) {
    	printf(
			'<div class="error"><p>' . esc_html__( 'Woocommerce - Gift Cards has been updated please backup your database and run the database updater %shere%s. Gift cards will not work until updated.', 'kodiak-giftcards' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=giftcard&section=upgrades' ) ) . '">',
			'</a>'
		);

	}
}
add_action( 'admin_notices', 'wpr_upgrade_notice' );

//function createNumber($value) {
//
//	if ( get_post_type() == "rp_shop_giftcard" ) {
//		$newGift = new KODIAK_Giftcard();
//		$cardnumber = $newGift->generateNumber();
//
//
//	    if ( empty($value) ) {
//	        return $cardnumber;
//	    }
//	 }
//
//	 return $value;
//}
//add_filter('pre_post_title', 'createNumber', 10, 3);

//function sendGiftCard( $giftCardNumber ) {
//    $giftCard = get_post_meta( $giftCardNumber, '_wpr_giftcard', true );
//		kodiak_email( $giftCardNumber );
//   	if ( ( ( $giftCard['sendTheEmail'] == 1 ) && ( $giftCard['balance'] <> 0 ) ) ) {
//        $email = new KODIAK_Giftcard_Email();
//        $post = get_post( $giftCardNumber );
//
//        $email->send ( $post );
//
//    }
//}
//add_action( 'woocommerce_rpgc_after_save', 'sendGiftCard', 10, 2);

function generateGiftCardNumber( $post ) {
	if ( isset( $_REQUEST["action"] ) ) return;

	if ( $post->post_type == 'rp_shop_giftcard' ) {
		$gift = new KODIAK_Giftcard();
		$card_number = $gift->generateNumber( );
		$post->post_title = $card_number;
		$post->post_name = $card_number;
	}
}
add_action( 'edit_form_top', 'generateGiftCardNumber', 10, 1);

function  make_gift_card_purchasable( $purchasable, $product ) {
	$is_giftcard = get_post_meta( $product->id, '_giftcard', true );
	$in_stock = get_post_meta( $product->id, '_stock_status', true ) ;

	if ( ( $is_giftcard == 'yes') && ( $in_stock == "instock" ) ) {
		$purchasable = true;
	}

	return $purchasable;
}
//add_filter ( 'woocommerce_is_purchasable', 'make_gift_card_purchasable', 10, 2);

function wpr_disable_coupons( $enabled ) {
	global $woocommerce;

	$has_giftcard = "no";
	if ( $woocommerce->cart->get_cart_contents_count() > 0 ) {
		foreach ( $woocommerce->cart->get_cart() as $key => $product) {
			$is_giftcard = get_post_meta( $product["product_id"], '_giftcard', true );

			if ( $is_giftcard == "yes" ) {
				$has_giftcard = "yes";
			}
		}

		if ( ( get_option( 'wpr_woocommerce_disable_coupons') == "yes" ) && ( $has_giftcard == "yes" ) ) {
			$enabled = false;
		}
	}

	return $enabled;
}
//add_filter( 'woocommerce_coupons_enabled', 'wpr_disable_coupons', 10, 1 );

function wpr_remove_hyphens( $cardNumber ){

	$card_number = str_replace("-", "", $cardNumber);

	return $card_number;
}

