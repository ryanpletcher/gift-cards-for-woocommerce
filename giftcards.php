<?php
/**
 * Plugin Name: WooCommerce - Gift Cards
 * Plugin URI: http://wp-ronin.com
 * Description: WooCommerce - Gift Cards allows you to offer gift cards to your customer and allow them to place orders using them.
 * Author: WP Ronin
 * Author URI: http://wp-ronin.com
 * Version: 2.6.4
 * License: GPL2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.5
 *
 * Text Domain:     kodiak-giftcards
 *
 * @package         Gift-Cards-for-Woocommerce
 * @author          Ryan Pletcher
 * @copyright       Copyright (c) 2015
 *
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'KODIAK_GIFTCARDS' ) ) {

    /**
     * Main KODIAK_GIFTCARDS class
     *
     * @since       1.0.0
     */
    class KODIAK_GIFTCARDS {

        /**
         * @var         KODIAK_GIFTCARDS $instance The one true KODIAK_GIFTCARDS
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true KODIAK_GIFTCARDS
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new KODIAK_GIFTCARDS();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->giftcards      = new KODIAK_Giftcard();
                self::$instance->emails         = new KODIAK_Giftcard_Emails();
                self::$instance->email_tags  = new KODIAK_Giftcard_Email_Template_Tags();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {

            define( 'WPKODIAK_VERSION',     '2.6.4' ); // Plugin version
            define( 'WPKODIAK_DIR',             plugin_dir_path( __FILE__ ) ); // Plugin Folder Path
            define( 'WPKODIAK_URL',             plugins_url( 'gift-cards-for-woocommerce', 'giftcards.php' ) ); // Plugin Folder URL
            define( 'WPKODIAK_FILE',            plugin_basename( __FILE__ )  ); // Plugin Root File

            if ( ! defined( 'WPR_STORE_URL' ) ) {
                define( 'WPR_STORE_URL', 'https://wp-ronin.com' ); // Premium Plugin Store
            }
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once WPKODIAK_DIR . 'includes/scripts.php';
            require_once WPKODIAK_DIR . 'includes/functions.php';
            require_once WPKODIAK_DIR . 'includes/post-type.php';

            require_once WPKODIAK_DIR . 'includes/admin/metabox.php';
            require_once WPKODIAK_DIR . 'includes/admin/actions.php';

            if( ! class_exists( 'KODIAK_Giftcard' ) ) {
                require_once WPKODIAK_DIR . 'includes/class.giftcard.php';
            }

            if( ! class_exists( 'KODIAK_Giftcard_Email' ) ) {
                require_once WPKODIAK_DIR . 'includes/emails/class.giftcard_email.php';
                require_once WPKODIAK_DIR . 'includes/emails/class.giftcard_email_tags.php';
                require_once WPKODIAK_DIR . 'includes/emails/actions.php';
                require_once WPKODIAK_DIR . 'includes/emails/template.php';
            }

            require_once WPKODIAK_DIR . 'includes/giftcard-product.php';
            require_once WPKODIAK_DIR . 'includes/giftcard-checkout.php';
            require_once WPKODIAK_DIR . 'includes/giftcard-paypal.php';
            require_once WPKODIAK_DIR . 'includes/giftcard-meta.php';
            require_once WPKODIAK_DIR . 'includes/shortcodes.php';
            // require_once WPKODIAK_DIR . 'includes/widgets.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         *
         */
        private function hooks() {
            // Register settings
            $wpr_woo_giftcard_settings = get_option( 'wpr_wg_options' );

            add_filter( 'woocommerce_get_settings_pages', array( $this, 'rpgc_add_settings_page'), 10, 1);
            add_filter( 'woocommerce_calculated_total', array( 'KODIAK_Giftcard', 'wpr_discount_total'), 10, 2 );
            add_filter( 'plugin_action_links_' . WPKODIAK_FILE, array( __CLASS__, 'plugin_action_links' ) );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            //add_filter( 'load_textdomain_mofile', array( $this, 'load_old_textdomain' ), 10, 2 );

            // Set filter for plugin's languages directory.
            $lang_dir  = dirname( plugin_basename( WPKODIAK_DIR ) ) . '/languages/';
            $lang_dir  = apply_filters( 'giftcards_for_woocommerce_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter.
            $locale        = apply_filters( 'plugin_locale',  get_locale(), 'kodiak-giftcards' );
            $mofile        = sprintf( '%1$s-%2$s.mo', 'kodiak-giftcards', $locale );

            // Look for wp-content/languages/kodiak/giftcards-{lang}_{country}.mo
            $mofile_global = WP_LANG_DIR . '/kodiak/kodiak-giftcards-' . $locale . '.mo';

            // Look for wp-content/languages/wpr/kodiak-giftcards-{lang}_{country}.mo
            $mofile_global1 = WP_LANG_DIR . '/wpr/kodiak-giftcards-' . $locale . '.mo';

            // Look for wp-content/languages/wpr/wpr-{lang}_{country}.mo
            $mofile_global2 = WP_LANG_DIR . '/wpr/wpr-' . $locale . '.mo';

            // Look in wp-content/languages/plugins/kodiak-giftcards
            $mofile_global3 = WP_LANG_DIR . '/plugins/kodiak-giftcards/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
               load_textdomain( 'kodiak-giftcards', $mofile_global );
            } elseif ( file_exists( $mofile_global1 ) ) {
                load_textdomain( 'kodiak-giftcards', $mofile_global1 );
            } elseif ( file_exists( $mofile_global2 ) ) {
                load_textdomain( 'kodiak-giftcards', $mofile_global2 );
            } elseif ( file_exists( $mofile_global3 ) ) {
                load_textdomain( 'kodiak-giftcards', $mofile_global3 );
            } else {
                // Load the default language files.
                load_plugin_textdomain( 'kodiak-giftcards', false, $lang_dir );
            }
        }

        public function rpgc_add_settings_page( $settings ) {

            require_once WPKODIAK_DIR . 'includes/admin/class.settings.php';

            $settings[] = new RPGC_Settings();

            return apply_filters( 'rpgc_setting_classes', $settings );
        }

        /**
         * Show action links on the plugin screen.
         *
         * @param   mixed $links Plugin Action links
         * @since       2.2.2
         * @return  array
         */
        public static function plugin_action_links( $links ) {
            $action_links = array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=giftcard' ) . '" title="' . esc_attr( __( 'View Gift Card Settings', 'kodiak-giftcards', 'gift-cards-for-woocommerce' ) ) . '">' . __( 'Settings', 'kodiak-giftcards', 'gift-cards-for-woocommerce' ) . '</a>',
            );

            return array_merge( $action_links, $links );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true KODIAK_GIFTCARDS
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \KODIAK_GIFTCARDS The one true KODIAK_GIFTCARDS
 *
 */
function KODIAK_GIFTCARDS_load() {
    if( ! class_exists( 'WooCommerce' ) ) {
        if( ! class_exists( 'KODIAK_Giftcard_Activation' ) ) {
            require_once 'includes/class.activation.php';
        }

        $activation = new KODIAK_Giftcard_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
        var_dump( $activation );

        return KODIAK_GIFTCARDS::instance();
    } else {
        return KODIAK_GIFTCARDS::instance();
    }

}
add_action( 'plugins_loaded', 'KODIAK_GIFTCARDS_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function wpr_giftcard_activation() {
    /* Activation functions here */
}
//register_activation_hook( __FILE__, 'wpr_giftcard_activation' );

function KODIAK_GIFTCARDS() {
    return Kodiak_Giftcards::instance();
}


KODIAK_GIFTCARDS();
