<?php
/**
 * Gift Card Checkout Functions
 *
 * @package     Gift-Cards-for-Woocommerce
 * @copyright   Copyright (c) 2014, Ryan Pletcher
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Adds the Gift Card form to the checkout page so that customers can enter the gift card information
function rpgc_cart_form() {

	if( get_option( 'woocommerce_enable_giftcard_cartpage' ) == "yes" ) {
		do_action( 'wpr_before_cart_form' );

		?>

		<div class="giftcard" style="float: left;">
			<label type="text" for="giftcard_code" style="display: none;"><?php _e( 'Giftcard', 'kodiak-giftcards' ); ?>:</label><input type="text" name="giftcard_code" class="input-text" id="giftcard_code" value="" placeholder="<?php _e( 'Gift Card', 'kodiak-giftcards' ); ?>" /><input type="submit" class="button" name="apply_giftcard" value="<?php _e( 'Apply Gift card', 'kodiak-giftcards' ); ?>" />
		</div>

		<?php
		do_action( 'wpr_after_cart_form' );
	}

}
add_action( 'woocommerce_cart_actions', 'rpgc_cart_form' );


if ( ! function_exists( 'rpgc_checkout_form' ) ) {

	/**
	 * Output the Giftcard form for the checkout.
	 * @access public
	 * @subpackage Checkout
	 * @return void
	 */
	function rpgc_checkout_form() {

		if( get_option( 'woocommerce_enable_giftcard_checkoutpage' ) == 'yes' ){

			do_action( 'wpr_before_checkout_form' );

			$info_message = apply_filters( 'woocommerce_checkout_giftcard_message', __( 'Have a giftcard?', 'kodiak-giftcards' ) . ' <a href="#" class="showgiftcard">' . __( 'Click here to enter your code', 'kodiak-giftcards' ) . '</a>' );
			wc_print_notice( $info_message, 'notice' );
			?>

			<form class="checkout_giftcard" method="post" style="display:none">
				<p class="form-row form-row-first"><input type="text" name="giftcard_code" class="input-text" placeholder="<?php _e( 'Gift card', 'kodiak-giftcards' ); ?>" id="giftcard_code" value="" /></p>
				<p class="form-row form-row-last"><input type="submit" class="button" name="apply_giftcard" value="<?php _e( 'Apply Gift card', 'kodiak-giftcards' ); ?>" /></p>
				<div class="clear"></div>
			</form>

			<?php do_action( 'wpr_after_checkout_form' ); ?>

		<?php
		}
	}
	add_action( 'woocommerce_before_checkout_form', 'rpgc_checkout_form', 10 );
}


//  Display the current gift card information on the cart
//  *Plan on adding ability to edit the infomration in the future
function wpr_display_giftcard_in_cart() {
	$cart = WC()->session->cart;
	$gift = 0;
	$card = array();

	foreach( $cart as $key => $product ) {

		if( KODIAK_Giftcard::wpr_is_giftcard($product['product_id'] ) )
				$card[] = $product;
	}

	if( ! empty( $card ) ) {
		echo '<h6>' . __( 'Gift Cards In Cart', 'kodiak-giftcards' ) . '</h6>';
		echo '<table width="100%" class="shop_table cart">';
		echo '<thead>';
		echo '<tr><td>' . __( 'Name', 'kodiak-giftcards' ) . '</td><td>' . __( 'Email', 'kodiak-giftcards' ) . '/' . __( 'Address', 'kodiak-giftcards' ) . '</td><td>' . __( 'Price', 'kodiak-giftcards' ) . '</td><td>' . __( 'Note', 'kodiak-giftcards' ) . '</td></tr>';
		echo '</thead>';
		foreach( $card as $key => $information ) {
			if( KODIAK_Giftcard::wpr_is_giftcard($information['product_id'] ) ){
				$gift += 1;

				$rpw_to_check 		= ( get_option( 'woocommerce_giftcard_to' ) <> NULL ? get_option( 'woocommerce_giftcard_to' ) : __('To', 'kodiak-giftcards' ) );
				$rpw_toEmail_check 	= ( get_option( 'woocommerce_giftcard_toEmail' ) <> NULL ? get_option( 'woocommerce_giftcard_toEmail' ) : __('To Email', 'kodiak-giftcards' )  );
				$rpw_address_check 	= ( get_option( 'woocommerce_giftcard_address' ) <> NULL ? get_option( 'woocommerce_giftcard_address' ) : __('Address', 'kodiak-giftcards' )  );
				$rpw_note_check		= ( get_option( 'woocommerce_giftcard_note' ) <> NULL ? get_option( 'woocommerce_giftcard_note' ) : __('Note', 'kodiak-giftcards' )  );
				$rpw_reload_card	= ( get_option( 'woocommerce_giftcard_reload_card' ) <> NULL ? get_option( 'woocommerce_giftcard_reload' ) : __('Card Number', 'kodiak-giftcards' )  );

				for ( $i = 0; $i < $information["quantity"]; $i++ ) {
					echo '<tr style="font-size: 0.8em">';

						echo '<td>';
						echo $information["variation"][$rpw_to_check];
						if ( isset( $information["variation"][$rpw_reload_card] ) ) {
							echo __( 'Reload card:', 'kodiak-giftcards') . ' ' . $information["variation"][$rpw_reload_card];
						}
						echo '</td>';

						echo '<td>';
						echo $information["variation"][$rpw_toEmail_check];
						echo $information["variation"][$rpw_address_check];
						echo '</td>';

						echo '<td>' . wc_price( $information["line_total"] / $information["quantity"] ) . '</td>';

						echo '<td>';
						if ( isset( $information["variation"][$rpw_toEmail_check] ) ) {
							echo $information["variation"][$rpw_note_check];
						}
						echo '</td>';
					echo '</tr>';
				}
			}
		}
		echo '</table>';
	}
}
add_action( 'woocommerce_after_cart_table', 'wpr_display_giftcard_in_cart' );


function woocommerce_apply_giftcard($giftcard_code) {
	global $wpdb;

	if ( !  empty( $_POST['giftcard_code'] ) ) {
		$giftcard_number = sanitize_text_field( $_POST['giftcard_code'] );
		$giftcard_id = KODIAK_Giftcard::wpr_get_giftcard_by_code( $giftcard_number );

		if ( $giftcard_id ) {

			if ( ! WC()->session->giftcard_post ) {
				WC()->session->giftcard_post = array();
			}

			if ( ! in_array($giftcard_id, WC()->session->giftcard_post) ) {
				$current_date = date("Y-m-d");
				$cardExperation = wpr_get_giftcard_expiration( $giftcard_id );

				if ( ( strtotime($current_date) <= strtotime($cardExperation) ) || ( strtotime($cardExperation) == '' ) ) {
					if( wpr_get_giftcard_balance( $giftcard_id ) > 0 ) {

						if ( WC()->session->giftcard_post == NULL ) {
							WC()->session->giftcard_post = array( $giftcard_id );

						} else {
							$newCard = array( $giftcard_id );
							$currentCards = WC()->session->giftcard_post;

							WC()->session->giftcard_post = array_merge($newCard, $currentCards);
						}

						if ( get_option( "woocommerce_disable_coupons" ) == "yes" ) {
							WC()->cart->remove_coupons();
						}

						WC()->cart->calculate_totals();

						wc_add_notice(  __( 'Gift card applied successfully.', 'kodiak-giftcards' ), 'success' );

					} else {
						wc_add_notice( __( 'Gift Card does not have a balance!', 'kodiak-giftcards' ), 'error' );
					}
				} else {
					wc_add_notice( __( 'Gift Card has expired!', 'kodiak-giftcards' ), 'error' ); // Giftcard Entered has expired
				}
			} else {
				wc_add_notice( __( 'Gift Card already in the cart!', 'kodiak-giftcards' ), 'error' );  //  You already have a gift card in the cart
			}
		} else {
			wc_add_notice( __( 'Gift Card does not exist!', 'kodiak-giftcards' ), 'error' ); // Giftcard Entered does not exist
		}

		wc_print_notices();

		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			die();
		}
	}
}
add_action( 'wp_ajax_woocommerce_apply_giftcard', 'woocommerce_apply_giftcard' );



function woocommerce_apply_giftcard_ajax($giftcard_code) {

	woocommerce_apply_giftcard( $giftcard_code );

	WC()->cart->calculate_totals();

}
add_action( 'wp_ajax_nopriv_woocommerce_apply_giftcard', 'woocommerce_apply_giftcard_ajax' );


function apply_cart_giftcard( ) {
	if ( isset( $_POST['giftcard_code'] ) )
		woocommerce_apply_giftcard( $_POST['giftcard_code'] );

	WC()->cart->calculate_totals();

}
add_action ( 'woocommerce_before_cart', 'apply_cart_giftcard' );
add_action ( 'wpr_before_checkout_form', 'apply_cart_giftcard' );



/**
 * Function to add the giftcard data to the cart display on both the card page and the checkout page WC()->session->giftcard_balance
 *
 */
function rpgc_order_giftcard( ) {
	global $woocommerce;

	if ( isset( $_GET['remove_giftcards'] ) ) {
		$newGiftCards = array();
		$usedGiftCards = WC()->session->giftcard_post;

		foreach ($usedGiftCards as $key => $giftcard) {
			if ( wpr_get_giftcard_number( $giftcard ) != $_GET['remove_giftcards'] ) {
				$newGiftCards[] = $giftcard;
			}
		}

		WC()->session->giftcard_post = $newGiftCards;
		WC()->cart->calculate_totals();
	}

	if ( isset( WC()->session->giftcard_post ) ) {
		if ( WC()->session->giftcard_post ){

			$giftCards = WC()->session->giftcard_post;



			$giftcard = new KODIAK_Giftcard();
			$price = $giftcard->wpr_get_payment_amount();

			if ( is_cart() ) {
				$gotoPage = WC()->cart->get_cart_url();
			} else {
				$gotoPage = WC()->cart->get_checkout_url();
			}

			?>
			<tr class="giftcard">
				<th><?php _e( 'Gift Card Payment', 'kodiak-giftcards' ); ?> </th>
				<td style="font-size:0.85em;">
					<?php echo wc_price( $price ); ?>
					<?php foreach ( $giftCards as $key => $giftCard) {
						$cardNumber = wpr_get_giftcard_number( $giftCard );
						$cardValue  = wpr_get_giftcard_balance( $giftCard );

						?>

						<br /> <a href="<?php echo add_query_arg( 'remove_giftcards', $cardNumber, $gotoPage ) ?>"><small>[<?php _e( 'Remove', 'kodiak-giftcards' ); ?> <?php echo wc_price( $cardValue); ?> <?php _e( 'Gift Card', 'kodiak-giftcards' ); ?>]</small></a>
					<?php } ?>
				</td>
			</tr>
			<?php

		}
	}
}
add_action( 'woocommerce_review_order_before_order_total', 'rpgc_order_giftcard' );
add_action( 'woocommerce_cart_totals_before_order_total', 'rpgc_order_giftcard' );




/**
 * Updates the Gift Card and the order information when the order is processed
 *
 */
function rpgc_update_card( $order_id ) {
	global $woocommerce;

	$giftCards = WC()->session->giftcard_post;
	$giftcard = new KODIAK_Giftcard();
	$payment = $giftcard->wpr_get_payment_amount();

	if ( isset( $giftCards ) ) {
		foreach ($giftCards as $key => $giftCard_id ) {

			if ( $giftCard_id != '' ) {
				//Decrease Balance of card
				$balance = wpr_get_giftcard_balance( $giftCard_id );

				$giftcardPayment = $giftcard->wpr_decrease_balance( $giftCard_id );

				$giftCard_IDs = get_post_meta ( $giftCard_id, 'wpr_existingOrders_id', true );
				if ( is_array ( $giftCard_IDs ) ) {
					$giftCard_IDs[] = $order_id;
				} else {
					$giftCard_IDs = array( $order_id );
				}


				$giftCardIDs = get_post_meta( $order_id, 'rpgc_id', true );
				if ( is_array ( $giftCardIDs ) ) {
					$giftCardIDs[] = $order_id;
				} else {
					$giftCardIDs = array( $order_id );
				}

				$giftCardPayments = get_post_meta( $order_id, 'rpgc_payment', true );
				$giftCardBalances = get_post_meta( $order_id, 'rpgc_balance', true );


				if ( $payment > $balance ){
					if ( is_array ( $giftCardPayments ) ) {
						$giftCardPayments[] = $giftcardPayment;
					} else {
						$giftCardPayments = array( $giftcardPayment );
					}
					$payment -= $balance;
					$newBalance = 0;
				} else {
					if ( is_array ( $giftCardPayments ) ) {
						$giftCardPayments[] = $giftcardPayment;
					} else {
						$giftCardPayments = array( $giftcardPayment );
					}
					$newBalance = $balance - $giftcardPayment;
				}

				if ( is_array ( $giftCardBalances ) ) {
					$giftCardBalances[] = $newBalance;
				} else {
					$giftCardBalances = array( $newBalance );
				}

				$giftCardInfo = get_post_meta( $giftCard_id, '_wpr_giftcard', true );
				$giftCardInfo['balance'] = $newBalance;

				update_post_meta( $giftCard_id, '_wpr_giftcard', $giftCardInfo ); // Update balance of Giftcard
				update_post_meta( $giftCard_id, 'rpgc_balance', $giftCardBalances );
				update_post_meta( $giftCard_id, 'wpr_existingOrders_id', $giftCard_IDs ); // Saves order id to gifctard post

				update_post_meta( $order_id, 'rpgc_id', $giftCardIDs );
				update_post_meta( $order_id, 'rpgc_payment', $giftCardPayments );
				update_post_meta( $order_id, 'rpgc_balance', $giftCardBalances );

				WC()->session->idForEmail = $order_id;
			}
		}

		unset( WC()->session->giftcard_payment, WC()->session->giftcard_post );
	}

	if ( isset ( WC()->session->giftcard_data ) ) {
		update_post_meta( $order_id, 'rpgc_data', WC()->session->giftcard_data );

		unset( WC()->session->giftcard_data );
	}

}
add_action( 'woocommerce_checkout_order_processed', 'rpgc_update_card' );


/**
 * Displays the giftcard data on the order thank you page
 *
 */
function rpgc_display_giftcard( $order ) {

	$theIDNums =  get_post_meta( $order->get_id(), 'rpgc_id', true );
	$theBalance = get_post_meta( $order->get_id(), 'rpgc_balance', true );

	if ( $theIDNums ) {
		?>


		<h4 style="margin-bottom: 10px;"><?php _e( 'Gift Card Balance After Order:', 'kodiak-giftcards' ); ?></h4>
		<ul style="list-style:none; margin-left: 10px;">
		<?php
		foreach ($theIDNums as $key => $theIDNum) {
			if( isset( $theIDNum ) ) {
				if ( $theIDNum <> '' ) {
				?>
					<li><?php _e( 'Gift Card', 'kodiak-giftcards' ); ?> <?php echo wpr_get_giftcard_number( $theIDNum );  ?>: <?php echo wc_price( $theBalance[$key] ); ?> <?php _e( 'remaining', 'kodiak-giftcards' ); ?> <?php do_action('wpr_after_remaining_balance', $theIDNum, $theBalance[$key] ); ?></li>

					<?php
				}
			}
		}
		?>
		</ul>
		<?php
		$theGiftCardData = get_post_meta( $order->get_id(), 'rpgc_data', true );
		if( isset( $theGiftCardData ) ) {
			if ( $theGiftCardData <> '' ) {
		?>
				<h4><?php _e( 'Gift Card Information:', 'kodiak-giftcards' ); ?></h4>
				<?php
				$i = 1;

				foreach ( $theGiftCardData as $giftcard ) {

					if ( $i % 2 ) echo '<div style="margin-bottom: 10px;">';
					echo '<div style="float: left; width: 45%; margin-right: 2%;>';
					echo '<h6><strong> ' . __('Giftcard',  'kodiak-giftcards' ) . ' ' . $i . '</strong></h6>';
					echo '<ul style="font-size: 0.85em; list-style: none outside none;">';
					if ( $giftcard[rpgc_product_num] ) 	echo '<li>' . __('Card', 'kodiak-giftcards') . ': ' . get_the_title( $giftcard[rpgc_product_num] ) . '</li>';
					if ( $giftcard[rpgc_to] ) 			echo '<li>' . __('To',  'kodiak-giftcards' ) . ': ' . $giftcard[rpgc_to] . '</li>';
					if ( $giftcard[rpgc_to_email] ) 	echo '<li>' . __('Send To',  'kodiak-giftcards' ) . ': ' . $giftcard[rpgc_to_email] . '</li>';
					if ( $giftcard[rpgc_balance] ) 		echo '<li>' . __('Balance',  'kodiak-giftcards' ) . ': ' . wc_price( $giftcard[rpgc_balance] ) . '</li>';
					if ( $giftcard[rpgc_note] ) 		echo '<li>' . __('Note',  'kodiak-giftcards' ) . ': ' . $giftcard[rpgc_note] . '</li>';
					if ( $giftcard[rpgc_quantity] ) 	echo '<li>' . __('Quantity',  'kodiak-giftcards' ) . ': ' . $giftcard[rpgc_quantity] . '</li>';
					echo '</ul>';
					echo '</div>';
					if ( !( $i % 2 ) ) echo '</div>';
					$i++;
				}
				echo '<div class="clear"></div>';
			}
		}
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'rpgc_display_giftcard' );
add_action( 'woocommerce_email_after_order_table', 'rpgc_display_giftcard' );


// NEED TO FIGURE THIS PART OUT
function rpgc_add_order_giftcard( $total_rows, $order ) {
	$return = array();

	$order_id = $order->get_id();

	$giftCardPayment = get_post_meta( $order_id, 'rpgc_payment', true);

	if ( $giftCardPayment <> 0 ) {

		$giftValue = get_post_meta( $order->get_id(), 'rpgc_payment', true);
		$discount = get_post_meta( $order->get_id(), '_cart_discount', true);

		if( $discount == $giftValue ) {
			unset( $total_rows['discount'] );
		} elseif ( $discount > $giftValue ) {
			$total_rows['discount']['value'] = $discount - $giftValue;
		}

		foreach ($giftCardPayment as $key => $payment ) {
			$newRow['rpgc_data' . $key ] = array(
				'label' => __( 'Gift Card Payment:', 'kodiak-giftcards' ),
				'value'	=> wc_price( -1 * $payment )
			);
		}

		if( get_option( 'woocommerce_enable_giftcard_process' ) == 'no' ){
			array_splice($total_rows, 1, 0, $newRow);
		} else {
			array_splice($total_rows, 2, 0, $newRow);
		}
	}

	return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'rpgc_add_order_giftcard', 10, 2);


function wpr_giftcard_in_order( $order_id ) {

	$giftCardPayment = get_post_meta( $order_id, 'rpgc_payment', true);

	if ( $giftCardPayment ) {
		foreach ($giftCardPayment as $key => $payment ) {?>

		<tr>
			<td class="label"><?php _e( 'Gift Card Payment', 'kodiak-giftcards' ); ?> <span class="tips" data-tip="<?php _e( 'This is the amount used by gift cards.', 'kodiak-giftcards' ); ?>">[?]</span>:</td>
			<td width="1%"></td>
			<td class="total"><?php echo wc_price($payment); ?></td>

		</tr>

	<?php
		}
	}

}
add_action( 'woocommerce_admin_order_totals_after_tax', 'wpr_giftcard_in_order', 10, 1 );
