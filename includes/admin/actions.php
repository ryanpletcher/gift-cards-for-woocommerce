<?php
/**
 * Admin Actions
 *
 * @since       2.6.0
 */

if ( ! function_exists( 'kodiak_giftcard_regenerate' ) ) {

    function kodiak_giftcard_regenerate( $data ) {
        if ( ! wp_verify_nonce( $data['_wpnonce'], 'kodiak-giftcard-regenerate' ) ) return;
            $post = get_post( $data[ 'post_id'] );

            if ( $post->post_type == 'rp_shop_giftcard' ) {
                $gift = new KODIAK_Giftcard();
                $card_number = $gift->generateNumber( );
                $post->post_title = $card_number;
                $post->post_name = $card_number;
            }

            wp_update_post( $post );
            wp_redirect( remove_query_arg( 'kodiak_action' ) ); exit;
      }
      add_action( 'kodiak_giftcard_regenerate', 'kodiak_giftcard_regenerate' );
}


if ( ! function_exists( 'kodiak_giftcard_resend' ) ) {
    function kodiak_giftcard_resend( $data ) {
        if ( ! wp_verify_nonce( $data['_wpnonce'], 'kodiak-giftcard-resend' ) ) return;
            $post = get_post( $data[ 'post_id'] );

            if ( $post->post_type == 'rp_shop_giftcard' ) {
                $gift = new KODIAK_Giftcard();
                $gift_data = $gift->card_info( $data[ 'post_id']  );
                $gift->sendTheCard( $gift_data );
            }

            wp_redirect( remove_query_arg( 'kodiak_action' ) ); exit;
      }
      add_action( 'kodiak_giftcard_resend', 'kodiak_giftcard_resend' );
}


    function kodiak_giftcard_send( $data ) {
        $post = get_post( $data );

        if ( $post->post_type == 'rp_shop_giftcard' ) {
            $gift = new KODIAK_Giftcard();
            $gift_data = $gift->card_info( $data  );
            if ( get_option('wpr_woocommerce_admin_send_automatically') == true ) {
                $gift->sendTheCard( $gift_data );
            }
        }
    }
    add_action( 'woocommerce_rpgc_after_save', 'kodiak_giftcard_send', 10, 1 );
