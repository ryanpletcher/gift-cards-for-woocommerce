<?php
/**
 * Setup Post Type
 *
 * @package     Gift Cards For WooCommerce
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

require_once(ABSPATH . 'wp-admin/includes/screen.php');

function kodiak_giftcard_create_post_type() {
    $show_in_menu = current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : false;

    register_post_type( 'rp_shop_giftcard',
        array(
            'labels' => array(
                'name'                  => __( 'Gift Cards', 'wpkodiak_giftcards' ),
                'singular_name'         => __( 'Gift Card', 'wpkodiak_giftcards' ),
                'menu_name'             => _x( 'Gift Cards', 'Admin menu name', 'wpkodiak_giftcards' ),
                'add_new'               => __( 'Add Gift Card', 'wpkodiak_giftcards' ),
                'add_new_item'          => __( 'Add New Gift Card', 'wpkodiak_giftcards' ),
                'edit'                  => __( 'Edit', 'wpkodiak_giftcards' ),
                'edit_item'             => __( 'Edit Gift Card', 'wpkodiak_giftcards' ),
                'new_item'              => __( 'New Gift Card', 'wpkodiak_giftcards' ),
                'view'                  => __( 'View Gift Cards', 'wpkodiak_giftcards' ),
                'view_item'             => __( 'View Gift Card', 'wpkodiak_giftcards' ),
                'search_items'          => __( 'Search Gift Cards', 'wpkodiak_giftcards' ),
                'not_found'             => __( 'No Gift Cards found', 'wpkodiak_giftcards' ),
                'not_found_in_trash'    => __( 'No Gift Cards found in trash', 'wpkodiak_giftcards' ),
                'parent'                => __( 'Parent Gift Card', 'wpkodiak_giftcards' )
                ),

            'public'                => true,
            'has_archive'           => true,
            'publicly_queryable'    => false,
            'exclude_from_search'   => false,
            'show_in_menu'          => $show_in_menu,
            'hierarchical'          => false,
            'supports'              => array( 'title', 'comments' )
        )
    );

    register_post_status( 'zerobalance', array(
        'label'                     => __( 'Zero Balance', 'wpkodiak_giftcards' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Zero Balance <span class="count">(%s)</span>', 'Zero Balance <span class="count">(%s)</span>', 'wpkodiak_giftcards' )
    ) );

}
add_action( 'init', 'kodiak_giftcard_create_post_type' );


/**
 * Define our custom columns shown in admin.
 * @param  string $column
 *
 */

function rpgc_add_columns( $columns ) {
    $new_columns = ( is_array( $columns ) ) ? $columns : array();
    unset( $new_columns['title'] );
    unset( $new_columns['date'] );
    unset( $new_columns['comments'] );

    //all of your columns will be added before the actions column on the Giftcard page

    $new_columns["title"]       = __( 'Giftcard Number', 'wpkodiak_giftcards' );
    $new_columns["amount"]      = __( 'Amount', 'wpkodiak_giftcards' );
    $new_columns["buyer"]       = __( 'Buyer', 'wpkodiak_giftcards' );
    $new_columns["recipient"]   = __( 'Recipient', 'wpkodiak_giftcards' );
    $new_columns["expiry_date"] = __( 'Expiry date', 'wpkodiak_giftcards' );
    $new_columns["sentEmail"]   = __( 'Sent?', 'wpkodiak_giftcards' );
    $new_columns["order_actions"]     = __( 'Actions', 'wpkodiak_giftcards' );

    //$new_columns['comments']    = $columns['comments'];
    //$new_columns['date']        = __( 'Creation Date', 'wpkodiak_giftcards' );

    return  apply_filters( 'kodiak_giftcard_columns', $new_columns);
}
add_filter( 'manage_edit-rp_shop_giftcard_columns', 'rpgc_add_columns' );



/**
 * Define our custom columns contents shown in admin.
 * @param  string $column
 *
 */
function rpgc_custom_columns( $column ) {
    global $post;

    $giftcardInfo = get_post_meta( $post->ID, '_wpr_giftcard', true );


    switch ( $column ) {

        case "buyer" :
            echo '<div><strong>' . esc_html( isset( $giftcardInfo[ 'from' ] ) ? $giftcardInfo[ 'from' ] : '' ) . '</strong><br />';
            echo '<span style="font-size: 0.9em">' . esc_html( isset( $giftcardInfo[ 'fromEmail' ] ) ? $giftcardInfo[ 'fromEmail' ] : '' ) . '</div>';
            break;

        case "recipient" :
            echo '<div><strong>' . esc_html( isset( $giftcardInfo[ 'to' ] ) ? $giftcardInfo[ 'to' ] : '' ) . '</strong><br />';
            echo '<span style="font-size: 0.9em">' . esc_html( isset( $giftcardInfo[ 'toEmail' ] ) ? $giftcardInfo[ 'toEmail' ] : '' ) . '</span></div>';
        break;

        case "amount" :
            $amount = isset( $giftcardInfo[ 'amount' ] ) ? $giftcardInfo[ 'amount' ] : 0;
            $balance = isset( $giftcardInfo[ 'balance' ] ) ? $giftcardInfo[ 'balance' ] : 0;

            $originalValue = '';
            if ( $amount != $balance ) {
                $originalValue = ' / <small>' . wc_price( $amount ) . '</small>';
            }

            echo '<div class="kodiak_giftcard_balance">' . wc_price( $balance ) . $originalValue . '</div>';
        break;

        case "sentEmail" :
            $sent = isset( $giftcardInfo[ 'sendTheEmail' ] ) ? $giftcardInfo[ 'sendTheEmail' ] : '';
            if ( $sent == 1 ) {
                echo "Yes";
            } else {
                echo "No";
            }
        break;

        case "expiry_date" :
            $expiry_date = isset( $giftcardInfo[ 'expiry_date' ] ) ? $giftcardInfo[ 'expiry_date' ] : '';

            if ( $expiry_date )
                echo esc_html( date_i18n( 'F j, Y', strtotime( $expiry_date ) ) );
            else
                echo '&ndash;';
        break;

        case 'order_actions' :
            $actions = array();

//            $actions['cancel'] = array(
//                'url'       => wp_nonce_url( add_query_arg( 'cancel', $post->ID ), 'cancel' ),
//                'name'      => __( 'Cancel Gift Card', 'wpkodiak_giftcards' ),
//                'action'    => "cancel",
//            );
//
//            $actions['decrease'] = array(
//                'url'       => wp_nonce_url( add_query_arg( 'decrease', $post->ID ), 'decrease' ),
//                'name'      => __( 'Decrease Value', 'wpkodiak_giftcards' ),
//                'action'    => "decrease",
//            );


            $actions['resend'] = array(
                'url'       => wp_nonce_url( add_query_arg( array( 'kodiak_action' => 'giftcard_resend', 'post_id' => $post->ID ) ), 'kodiak-giftcard-resend' ),
                'name'      => __( 'Resend Gift Card', 'wpkodiak_giftcards' ),
                'action'    => "resend",
            );

            $actions['regen'] = array(

                'url'       => wp_nonce_url( add_query_arg( array( 'kodiak_action' => 'giftcard_regenerate', 'post_id' => $post->ID ) ), 'kodiak-giftcard-regenerate' ),
                'name'      => __( 'Regrenerate Gift Card Number', 'wpkodiak_giftcards' ),
                'action'    => "regen",
            );

            $actions = apply_filters( 'kodiak_giftcard_user_actions', $actions, $post );

            echo '<p>';
            foreach ( $actions as $action ) {
                printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
            }
            echo '</p>';
        break;
    }
}
add_action( 'manage_rp_shop_giftcard_posts_custom_column', 'rpgc_custom_columns', 2 );



function wpfstop_change_default_title( $title ){
    if ( is_admin() ) {
        $screen = get_current_screen();

        if ( 'rp_shop_giftcard' == $screen->post_type ){
            $title = __( 'Enter Gift Card Number Here', 'wpkodiak_giftcards' );
        }
    }

    return $title;
}
add_filter( 'enter_title_here', 'wpfstop_change_default_title' );

function cf_search_join( $join ) {
    global $wpdb;
    if ( is_admin() ) {
        $screen = get_current_screen();

        if ( 'rp_shop_giftcard' == $screen->post_type ){
            if ( is_search() ) {
                $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
            }
        }
    }

    return $join;
}
add_filter('posts_join', 'cf_search_join' );

/**
 * Modify the search query with posts_where
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
 */
function cf_search_where( $where ) {
    global $pagenow, $wpdb;
    if ( is_admin() ) {
        $screen = get_current_screen();

        if ( 'rp_shop_giftcard' == $screen->post_type ){
            if ( is_search() ) {
                $where = preg_replace(
                    "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                    "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
            }
        }
    }

    return $where;
}

add_filter( 'posts_where', 'cf_search_where' );

/**
 * Prevent duplicates
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
 */
function cf_search_distinct( $where ) {
    global $wpdb;
    if ( is_admin() ) {
        $screen = get_current_screen();
        if ( 'rp_shop_giftcard' == $screen->post_type ){
            if ( is_search() ) {
                return "DISTINCT";
            }
        }
    }

    return $where;
}
add_filter( 'posts_distinct', 'cf_search_distinct' );
