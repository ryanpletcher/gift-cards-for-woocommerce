<?php
/**
 * Gift Card handler
 *
 * @package     Woo Gift Cards\GiftCardHandler
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Gift Card Handler Class
 *
 * @since       1.0.0
 */
class KODIAK_Giftcard {
    public $giftcard;
    public $card_info;

    /**
     * Setup the activation class
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function __construct(  ) {

    }

    public function card_info( $giftcard_id ) {
        $giftcard = get_post_meta( $giftcard_id, '_wpr_giftcard', true );
        return $giftcard;
    }

    public function create( $giftInformation ) {
        global $wpdb;

        $giftCard['sendTheEmail'] = 0;

        // Set Most of the Gift card Values
        foreach ($giftInformation as $key => $value) {
            $giftCard[ $key ]    = wc_clean( $value );
        }

        if ( ! isset( $giftInformation['balance'] ) ) {
            $giftCard['balance']    = $giftCard['amount'];
        }

        if ( ! isset( $giftInformation['expiry_date'] ) ) {
            $giftCard['expiry_date'] = '';
        }

        update_post_meta( $_POST['ID'], '_wpr_giftcard', $giftCard );

        return $giftCard;
    }

    public function regenerateNumber( $giftInformation ) {
        global $wpdb;
        if ( ( $_POST['post_title'] == '' ) || isset( $giftInformation['kodiak_regen_number'] ) ){
            if ( ( $giftInformation['kodiak_regen_number'] == 'yes' ) ) {
                $newNumber = apply_filters( 'kodiak_regen_number', $this->generateNumber());

                $wpdb->update( $wpdb->posts, array( 'post_title' => $newNumber ), array( 'ID' => $_POST['ID'] ) );
                $wpdb->update( $wpdb->posts, array( 'post_name' => $newNumber ), array( 'ID' => $_POST['ID'] ) );
            }
        }
        return $newNumber;
    }

    public function sendTheCard( $giftInformation ) {
        $from_name = kodiak_do_email_tags( $giftInformation["from"], $giftInformation["post_ID"] );
        $from_name = apply_filters( 'kodiak_giftcard_email_from_name', $from_name, 0, array() );

        $from_email = $giftInformation["fromEmail"];
        $from_email = apply_filters( 'kodiak_giftcard_from_email', $from_email, 0, array() );

        $subject = kodiak_do_email_tags( kodiak_get_option( 'kodiak_giftcard_email_subject', __( 'Gift Card Sent', 'kodiak-giftcards' ) ), $giftInformation["post_ID"] );
        $subject = apply_filters( 'kodiak_giftcard_email_subject', wp_strip_all_tags( $subject ), 0 );

        $heading = kodiak_get_option( 'kodiak_giftcard_email_heading', __( 'Gift Card Sent', 'kodiak-giftcards' ) );
        $heading = apply_filters( 'kodiak_giftcard_email_heading', $heading, 0, array() );

        $message = kodiak_do_email_tags( kodiak_get_email_body_content( 0, array() ), $giftInformation["post_ID"] );

        $emails = KODIAK_GIFTCARDS()->emails;
        $emails->__set( 'from_name' , $from_name );
        $emails->__set( 'from_email', $from_email );
        $emails->__set( 'heading'   , $heading );

        $headers = apply_filters( 'kodiak_get_email_headers', $emails->get_headers(), 0, array() );
        $emails->__set( 'headers', $headers );

        $emails->send( $giftInformation["toEmail"], $subject, $message );

        $giftInformation['sendTheEmail'] = 1;
        wpr_set_giftcard_info( $giftInformation["post_ID"], $giftInformation );
    }

    public function resendTheCard( $giftInformation ) {
        if( isset( $giftInformation['kodiak_resend_email'] ) ) {
            $this->sendTheCard( $giftInformation );
        }
    }



    // Function to create the gift card
    public function send( $giftInformation ) {
        $giftCard['sendTheEmail'] = 1;
    }



    // Function to generate the gift card number for the card
    public function generateNumber( ){
        $randomNumber = substr( number_format( time() * rand(), 0, '', '' ), 0, 15 );

        return apply_filters('rpgc_generate_number', $randomNumber);
    }

    // Function to check if a product is a gift card
    public static function wpr_is_giftcard( $giftcard_id ) {
        $giftcard = get_post_meta( $giftcard_id, '_giftcard', true );

        if ( $giftcard != 'yes' ) {
            return false;
        }

        return true;
    }


    public static function wpr_get_giftcard_by_code( $value = '' ) {
        global $wpdb;

        // Check for Giftcard
        $giftcard_found = $wpdb->get_var( $wpdb->prepare( "
            SELECT $wpdb->posts.ID
            FROM $wpdb->posts
            WHERE $wpdb->posts.post_type = 'rp_shop_giftcard'
            AND $wpdb->posts.post_status = 'publish'
            AND $wpdb->posts.post_title = '%s'
        ", $value ) );

        return $giftcard_found;
    }

    public function wpr_get_payment_amount( ){
        $giftcards = WC()->session->giftcard_post;
        $cart = WC()->session->cart;

        if ( isset( $giftcards ) ) {
            $balance = 0;

            foreach ($giftcards as $key => $card_id) {
                $balance += wpr_get_giftcard_balance( $card_id );
            }

            $charge_shipping    = get_option('woocommerce_enable_giftcard_charge_shipping');
            $charge_tax         = get_option('woocommerce_enable_giftcard_charge_tax');
            $charge_fee         = get_option('woocommerce_enable_giftcard_charge_fee');
            $charge_gifts       = get_option('woocommerce_enable_giftcard_charge_giftcard');

            $exclude_product    = array();
            $exclude_product    = array_filter( array_map( 'absint', explode( ',', get_option( 'wpr_giftcard_exclude_product_ids' ) ) ) );

            $giftcardPayment = 0;

            foreach( $cart as $key => $product ) {
                if ( isset( $product['product_id'] ) ) {
                    if( ! in_array( $product['product_id'], $exclude_product ) ) {

                        if ( ! KODIAK_Giftcard::wpr_is_giftcard( $product['product_id'] ) ) {
                            if( $charge_tax == 'yes' ){
                                $giftcardPayment += $product['line_total'];
                                $giftcardPayment += $product['line_tax'];
                            } else {
                                $giftcardPayment += $product['line_total'];
                            }
                        } else {
                            if ( $charge_gifts == "yes" ) {
                                $giftcardPayment += $product['line_total'];
                            }
                        }
                    }
                }
            }

            if( $charge_shipping == 'yes' ) {
                $giftcardPayment += WC()->cart->shipping_total;
            }

            if( $charge_tax == "yes" ) {
                if( $charge_shipping == 'yes' ) {
                    $giftcardPayment += WC()->cart->shipping_tax_total;
                }
            }

            if( $charge_fee == "yes" ) {
                $giftcardPayment += WC()->cart->fee_total;
            }

            if( $charge_gifts == "yes" ) {
                $giftcardPayment += WC()->cart->fee_total;
            }

            if ( $giftcardPayment <= $balance ) {
                $display = $giftcardPayment;
            } else {
                $display = $balance;
            }
            return $display;
        }
    }

    public function wpr_decrease_balance( $giftCard_id ) {

        $payment = $this->wpr_get_payment_amount();

        if ( $payment > wpr_get_giftcard_balance( $giftCard_id ) ) {
            $newBalance = 0;
        } else {
            $newBalance = wpr_get_giftcard_balance( $giftCard_id ) - $payment;
        }

        wpr_set_giftcard_balance( $giftCard_id, $newBalance );

        // Check if the gift card ballance is 0 and if it is change the post status to zerobalance
        if( wpr_get_giftcard_balance( $giftCard_id ) == 0 ) {
            wpr_update_giftcard_status( $giftCard_id, 'zerobalance' );
        }

        return $payment;
    }

    public function wpr_increase_balance( $giftCard_id, $amount ) {
        $newBalance = wpr_get_giftcard_balance( $giftCard_id ) + $amount;

        wpr_set_giftcard_balance( $giftCard_id, $newBalance );
    }


    public static function wpr_discount_total( $gift ) {
        $giftcard = new KODIAK_Giftcard(  );

        $discount = $giftcard->wpr_get_payment_amount();

        $gift -= round( $discount, 2 );
        return $gift;
    }
}
