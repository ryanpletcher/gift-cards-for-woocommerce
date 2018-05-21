<?php
/**
 * Gift Card Admin Functions
 *
 * @package     Gift-Cards-for-Woocommerce
 * @copyright   Copyright (c) 2014, Ryan Pletcher
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Settings_Accounts
 */
class RPGC_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'giftcard';
		$this->label = __( 'Gift Cards',  'kodiak-giftcards'  );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

		add_action( 'woocommerce_admin_field_wpr_upgrader', array( $this, 'wpr_upgrader' ) );
		add_action( 'woocommerce_admin_field_addon_settings', array( $this, 'addon_setting' ) );
		add_action( 'woocommerce_admin_field_excludeProduct', array( $this, 'excludeProducts' ) );
		add_action( 'woocommerce_admin_field_wpr_licences', array( $this, 'wpr_licences' ) );

		add_action( 'woocommerce_admin_field_kodiakemail', array( $this, 'kodiak_giftcard_email' ) );
		add_action( 'woocommerce_admin_field_kodiakemail_options', array( $this, 'kodiak_giftcard_email_options' ) );



	}


	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = apply_filters( 'woocommerce_add_section_giftcard',
			array(
				'' 				=> __( 'General Options', 'wpkodiak_giftcards' ),
				'purchasing' 	=> __( 'Purchasing', 'wpkodiak_giftcards' ),
				'email'	 		=> __( 'Email', 'wpkodiak_giftcards' ),
				'language'		=> __('Language', 'wpkodiak_giftcards' )
			)
		);
		//'product'		=> __( 'Product', 'wpkodiak_giftcards' ),
		//'redeeming' 	=> __( 'Redeeming', 'wpkodiak_giftcards' ),
		//'management' 	=> __( 'Management', 'wpkodiak_giftcards' ),

		$premium = array( 'licences' => __( 'Licences', 'kodiak-giftcards' ) );

		$wpr_gift_version = get_option( 'wpr_gift_version' );

		if ( ! $wpr_gift_version ) {
			// 2.0.0 is the first version to use this option so we must add it
			$wpr_gift_version = WPKODIAK_VERSION;
		}

		$sections = array_merge($sections, $premium);

		$wpr_gift_version = preg_replace( '/[^0-9.].*/', '', $wpr_gift_version );

		if ( version_compare( $wpr_gift_version, '2.0.0', '<' ) ) {
			$upgrade = array( 'upgrades' => __( 'Gift Card Upgrades', 'kodiak-giftcards' ) );

			$sections = array_merge($sections, $upgrade);
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

 		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

	}


	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$options = '';
		if( $current_section == '' ) {

			$options = apply_filters( 'kodiak_giftcard_settings' . $current_section, array(

				array( 'title' 		=> __( 'Processing Options',  'kodiak-giftcards'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),

				array(
					'title'         => __( 'Display on Cart?',  'kodiak-giftcards'  ),
					'desc'          => __( 'Display the giftcard form on the cart page.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_cartpage',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Display on Checkout?',  'kodiak-giftcards'  ),
					'desc'          => __( 'Display the giftcard form on the checkout page.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_checkoutpage',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Disable Notes Field',  'kodiak-giftcards'  ),
					'desc'          => __( 'Disable notes field when purchaseing a gift card.',  'kodiak-giftcards'  ),
					'id'            => 'wpr_woocommerce_disable_notes',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Automatically Send Gift Card',  'kodiak-giftcards'  ),
					'desc'          => __( 'Send newly creating gift cards from the wordpress admin, when its created.',  'kodiak-giftcards'  ),
					'id'            => 'wpr_woocommerce_admin_send_automatically',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),

				array( 'type' => 'sectionend', 'id' => 'account_registration_options'),

				array( 'title' 		=> __( 'Gift Card Uses',  'kodiak-giftcards'  ), 'type' => 'title', 'id' => 'giftcard_products_title' ),

				array(
					'title'         => __( 'Shipping',  'kodiak-giftcards'  ),
					'desc'          => __( 'Allow customers to pay for shipping with their gift card.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_charge_shipping',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Tax',  'kodiak-giftcards'  ),
					'desc'          => __( 'Allow customers to pay for tax with their gift card.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_charge_tax',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Fee',  'kodiak-giftcards'  ),
					'desc'          => __( 'Allow customers to pay for fees with their gift card.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_charge_fee',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Other Gift Cards',  'kodiak-giftcards'  ),
					'desc'          => __( 'Allow customers to pay for gift cards with their existing gift card.',  'kodiak-giftcards'  ),
					'id'            => 'woocommerce_enable_giftcard_charge_giftcard',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array( 'type' => 'excludeProduct' ),

				array( 'type' => 'sectionend', 'id' => 'account_registration_options'),

			));

		} else if( $current_section == 'language' ) {
			$options = apply_filters( 'kodiak_giftcard_settings_' . $current_section,
			      array(
				      array( 'title' 		=> __( 'Language',  'kodiak-giftcards'  ), 'type' => 'title', 'id' => 'giftcard_language_title' ),

					array(
						'name'     => __( 'To', 'kodiak-giftcards' ),
						'desc'     => __( 'This is the value that will display before a gift card number.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_to',
						'std'      => 'To', // WooCommerce < 2.0
						'default'  => 'To', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'To Email', 'kodiak-giftcards' ),
						'desc'     => __( 'This is the value that will display before a gift card number.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_toEmail',
						'std'      => 'Send To', // WooCommerce < 2.0
						'default'  => 'Send To', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Note Option', 'kodiak-giftcards' ),
						'desc'     => __( 'This will change the placeholder field for the gift card note.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_note',
						'std'      => 'Enter your note here.', // WooCommerce < 2.0
						'default'  => 'Enter your note here.', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Address', 'kodiak-giftcards' ),
						'desc'     => __( 'This will change the placeholder field for the address field.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_address',
						'std'      => 'Address', // WooCommerce < 2.0
						'default'  => 'Address', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Reload Gift Card', 'kodiak-giftcards' ),
						'desc'     => __( 'This will change the placeholder field for the reloading option.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_reload_card',
						'std'      => 'Cart Number', // WooCommerce < 2.0
						'default'  => 'Cart Number', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Gift Card Button Text', 'kodiak-giftcards' ),
						'desc'     => __( 'This is the text that will be displayed on the button to customize the information.', 'kodiak-giftcards' ),
						'id'       => 'woocommerce_giftcard_button',
						'std'      => 'Customize', // WooCommerce < 2.0
						'default'  => 'Customize', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),
					array( 'type' 		=> 'sectionend', 'id' => 'kodiak_giftcard_email_template_end' ),
				)
			);
		} else if( $current_section == 'purchasing' ) {
			$options = apply_filters( 'kodiak_giftcard_settings_' . $current_section,
			      array(
				      array( 'title' 		=> __( 'Processing Options',  'kodiak-giftcards'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),
					array(
						'title'         => __( 'Require Recipient Information?',  'kodiak-giftcards'  ),
						'desc'          => __( 'Requires that your customers enter a name and email when purchasing a Gift Card.',  'kodiak-giftcards'  ),
						'id'            => 'woocommerce_enable_giftcard_info_requirements',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => true
					),
					array(
						'title'         => __( 'Customize Add to Cart?',  'kodiak-giftcards'  ),
						'desc'          => __( 'Change Add to cart label and disable add to cart from product list.',  'kodiak-giftcards'  ),
						'id'            => 'woocommerce_enable_addtocart',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false
					),
					array(
						'title'         => __( 'Physical Card?',  'kodiak-giftcards'  ),
						'desc'          => __( 'Select this if you would like to offer physical gift cards.',  'kodiak-giftcards'  ),
						'id'            => 'woocommerce_enable_physical',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false
					),
					array(
						'title'         => __( 'One Time Use Cards?',  'kodiak-giftcards'  ),
						'desc'          => __( 'Select this if you want cards to be disabled after first use.',  'kodiak-giftcards'  ),
						'id'            => 'woocommerce_enable_one_time_use',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false
					),
					array(
						'title'         => __( 'Allow Multiples',  'kodiak-giftcards'  ),
						'desc'          => __( 'Select this if you would like to allow customers to purchase multiples of one card.',  'kodiak-giftcards'  ),
						'id'            => 'woocommerce_enable_multiples',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false
					),
					array(
						'title'         => __( 'Disable Coupons',  'kodiak-giftcards'  ),
						'desc'          => __( 'Disable coupons when purchaseing a gift card.',  'kodiak-giftcards'  ),
						'id'            => 'wpr_woocommerce_disable_coupons',
						'default'       => 'no',
						'type'          => 'checkbox',
						'autoload'      => false
					),
					array( 'type' 		=> 'sectionend', 'id' => 'kodiak_giftcard_email_template_end' ),
				)
			);


		} else if( $current_section == 'email' ) {
			// TODO
			//
			// Need to make sure that the test email works and see if I can get the colors to change on the email.
			$options = apply_filters( 'kodiak_giftcard_settings_' . $current_section,
				array(
					array( 'title' 		=> __( 'Gift Card Email',  'wpkodiak_giftcards'  ), 'type' => 'title', 'id' => 'kodiak_giftcard_email_title' ),
					// TODO NEED TO BE FIXED SO IT SHOWS UP
					array( 'type' 		=> 'kodiakemail_options' ),

					array(
						'title'         => __( 'Email Template',  'wpkodiak_giftcards'  ),
						'desc'        	=> __( 'Choose a template. Click "Save Changes" then "Preview Purchase Receipt" to see the new template.', 'wpkodiak_giftcards' ),
						'id'          	=> 'kodiak_giftcard_email_template_select',
						'std'			=> '', // WooCommerce < 2.0
						'default'		=> '', // WooCommerce >= 2.0
						'type'			=> 'select',
						'class'			=> 'chosen_select',
						'options'		=> kodiak_get_email_templates(),
						'desc_tip'    	=> true,
					),

					array(
						'title'         => __( 'Email Logo',  'wpkodiak_giftcards'  ),
						'desc'        	=> __( 'URL to an image you want to show in the email header. Upload images using the media uploader (Admin > Media).', 'wpkodiak_giftcards' ),
						'id'          	=> 'kodiak_giftcard_email_header_image',
						'type'        	=> 'text',
						'css'         	=> 'min-width:300px;',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'default'     	=> '',
						'autoload'    	=> false,
						'desc_tip'    	=> true,
					),

					array(
						'title'         => __( 'Email Heading',  'wpkodiak_giftcards'  ),
						'desc'          => __( 'The heading on gift card emails.',  'wpkodiak_giftcards'  ),
						'id'            => 'kodiak_giftcard_email_heading',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'default'     	=> '',
						'css'         	=> 'min-width:300px;',
						'type'          => 'text',
						'autoload'      => true,
						'desc_tip'		=> true,
					),

					array(
						'title'         => __( 'Email From Name',  'wpkodiak_giftcards'  ),
						'desc'          => __( 'The name purchase receipts are said to come from.',  'wpkodiak_giftcards'  ),
						'id'            => 'kodiak_giftcard_email_from_name',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'default'     	=> '',
						'css'         	=> 'min-width:300px;',
						'type'          => 'text',
						'autoload'      => true,
						'desc_tip' 		=> true,
					),

					array(
						'title'         => __( 'Email From Address',  'wpkodiak_giftcards'  ),
						'desc'          => __( 'Email to send purchase receipts from. This will act as the "from" and "reply-to" address.',  'wpkodiak_giftcards'  ),
						'id'            => 'kodiak_giftcard_email_from_address',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'default'     	=> '',
						'css'         	=> 'min-width:300px;',
						'type'          => 'text',
						'autoload'      => true,
						'desc_tip' 		=> true,
					),

					array(
						'title'         => __( 'Email Subject',  'wpkodiak_giftcards'  ),
						'desc'          => __( 'Enter the subject line for the gift card email.',  'wpkodiak_giftcards'  ),
						'id'            => 'kodiak_giftcard_email_subject',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'default'     	=> '',
						'css'         	=> 'min-width:300px;',
						'type'          => 'text',
						'autoload'      => true,
						'desc_tip' 		=> true,
					),

					array(
						'title'       	=> __( 'Footer text', 'wpkodiak_giftcards' ),
						'desc'        	=> __( 'The text to appear in the footer of WooCommerce emails.', 'wpkodiak_giftcards' ),
						'id'          	=> 'kodiak_giftcard_email_footer_text',
						'css'         	=> 'width:300px; height: 75px;',
						'placeholder' 	=> __( 'N/A', 'wpkodiak_giftcards' ),
						'type'        	=> 'textarea',
						'default'     	=> get_bloginfo( 'name', 'display' ),
						'autoload'    	=> false,
						'desc_tip'    	=> true,
					),

					array( 'type' => 'sectionend', 'id' => 'kodiak_giftcard_emailend'),

					array( 'title' 		=> __( 'Gift Card Email Tempalate',  'wpkodiak_giftcards'  ), 'type' => 'title', 'id' => 'kodiak_giftcard_email_title' ),
					array( 'type' 		=> 'kodiakemail' ),

					array( 'type' 		=> 'sectionend', 'id' => 'kodiak_giftcard_email_template_end' ),
				)
			);

		} else if( $current_section == 'upgrades') {

			$options = apply_filters( 'kodiak_giftcard_settings_' . $current_section,
				array( 'type' 	=> 'sectionend', 'id' => 'giftcard_upgrades' ),

				array( 'type' => 'wpr_upgrader' )

			);
		} else if( $current_section == 'licences') {

			$options = apply_filters( 'kodiak_giftcard_settings_' . $current_section,
                         array(
					array( 'title'		=> __( 'Gift Card License',  'wpkodiak_giftcards'  ),
					          'type' 	=> 'title',
					          'id' 		=> 'kodiak_giftcard_license_title' ),

			     		array( 'type' 	=> 'wpr_licences' ),

					array( 'type' 	=> 'sectionend',
					          'id' 		=> 'kodiak_giftcard_licenses' )
				)

			); // End pages settings
		}

		return apply_filters ('get_giftcard_settings', $options, $current_section );
	}

	public function wpr_licences() {
		do_action( 'wpr_add_license_field' );
	}

	public function wpr_register_settings() {
		// creates our settings in the options table

		register_setting('wpr-options', 'wpr_options' );
		register_setting('wpr-options', 'wpr_license_key', array( $this, 'wpr_sanitize_license' ) );
	}



	public function wpr_upgrader() {
		?>
		<h3><?php _e('Upgrade Gift Cards', 'kodiak-giftcards' ); ?></h3>
		<p><?php _e( 'With the resent update on Woocommerce - Giftcards you will need to upgrade your database. Please backup your database before upgrading. You can do this is in the tools area to the left in the Wordpress Admin.', 'kodiak-giftcards' ); ?></p>
		<div style="margin: 0 0 100px 50px;">
			<?php
			if( isset( $updatedCards ) ) {
				echo '<h3>' . __( 'Gift Cards Updated', 'kodiak-giftcards' ) . '</h3>';
				echo '<table>';
				foreach( $updatedCards as $card ) {
					echo '<tr>';
					echo '<td>' . $card . '</td>';
					echo '<td>' . __( 'Updated', 'kodiak-giftcards' ) . '</td>';
					echo '</tr>';
				}
				echo '</table>';
			} else {
				submit_button( __( 'Upgrade Now', 'kodiak-giftcards' ) );
			}
			?>
		</div>
		<?php

	}


	/**
	 * Output the frontend styles settings.
	 */
	public function addon_setting() {

		if( $this->activatedPlugins() ) {
			register_setting( 'wpr-options', 'wpr_options' );
			?>
			<h3><?php _e('Activate Extensions', 'kodiak-giftcards' ); ?></h3>
			<table>
			<?php do_action( 'wpr_add_license_field' ); ?>
			</table>
			<br class="clear" />

		<?php } ?>

		<h3><?php _e(' Premium features available', 'kodiak-giftcards' ); ?></h3>
		<p>
		<?php _e( 'You can now add additional functionallity to the gift card plugin using some of my premium plugins offered through', 'kodiak-giftcards' ); ?> <a href="wp-ronin.com">wp-ronin.com</a>.
		</p>
		<br class="clear" />
		<div class='wc_addons_wrap' style="margin-top:10px;">
		<ul class="products" style="overflow:hidden;">
		<?php

			$i = 0;
			$addons = array();

			if( ! class_exists( 'WPRWG_GiftCards_Pro' ) ) {
				$addons[$i]["title"] = __('Woocommerce Giftcards Pro', 'kodiak-giftcards' );
				$addons[$i]["image"] = "";
				$addons[$i]["excerpt"] = __( 'Get all the added features of the Pro gift card addon in this one package.', 'kodiak-giftcards' );
				$addons[$i]["link"] = "https://wp-ronin.com/downloads/";
				$i++;
			}

			if( ! class_exists( 'WPRWG_Custom_Price' ) ) {
				$addons[$i]["title"] = __('Custom Price', 'kodiak-giftcards' );
				$addons[$i]["image"] = "";
				$addons[$i]["excerpt"] = __( 'Dont want to have to create multiple products to offer Gift Cards on your site.  Use this plugin to create a single product that allows your customers to put in the price.  Select 10 – 10000000 it wont matter.', 'kodiak-giftcards' );
				$addons[$i]["link"] = "https://wp-ronin.com/downloads/woocommerce-gift-cards-custom-price/";
				$i++;
			}

			if( ! class_exists( 'WPRWG_Custom_Number' ) ) {
				$addons[$i]["title"] = __( 'Customize Card Number', 'kodiak-giftcards' );
				$addons[$i]["image"] = "";
				$addons[$i]["excerpt"] = __( 'Want to be able to customize the gift card number when it is created, this plugin will do it.', 'kodiak-giftcards' );
				$addons[$i]["link"] = "https://wp-ronin.com/downloads/woocommerce-gift-cards-customize-gift-card/";
				$i++;
			}

			if( ! class_exists( 'WPRWG_Auto_Send' ) ) {
				$addons[$i]["title"] = __( 'Auto Send Card', 'kodiak-giftcards' );
				$addons[$i]["image"] = "";
				$addons[$i]["excerpt"] = __( 'Save time creating gift cards by using this plugin.  Enable it and customers will have their gift card sent out directly upon purchase or payment.', 'kodiak-giftcards' );
				$addons[$i]["link"] = "https://wp-ronin.com/downloads/auto-send-email-woocommerce-gift-cards/";
				$i++;
			}

			if( ! class_exists( 'WPRWG_CSV_Importer' ) ) {
				$addons[$i]["title"] = __( 'CSV Importer', 'kodiak-giftcards' );
				$addons[$i]["image"] = "";
				$addons[$i]["excerpt"] = __( 'Import large number of gift cards with this extention. Use our supplied .', 'kodiak-giftcards' );
				$addons[$i]["link"] = "https://wp-ronin.com/downloads/csvimporter/";
				$i++;
			}

			foreach ( $addons as $addon ) {
				echo '<li class="product" style="float:left; margin:0 1em 1em 0 !important; padding:0; vertical-align:top; width:300px;">';
				echo '<a href="' . $addon['link'] . '">';
				if ( ! empty( $addon['image'] ) ) {
					echo '<img src="' . $addon['image'] . '"/>';
				} else {
					echo '<h3>' . $addon['title'] . '</h3>';
				}
				echo '<p>' . $addon['excerpt'] . '</p>';
				echo '</a>';
				echo '</li>';
			}
		?>
		</ul>
		</div>
		<?php
	}

	public function activatedPlugins() {
		if( defined( 'WPR_GC_PRO_TEXT' ) || defined( 'RPWCGC_AUTO_CORE_TEXT_DOMAIN' ) || defined( 'WPR_CP_CORE_TEXT_DOMAIN' ) || defined( 'RPWCGC_CN_CORE_TEXT_DOMAIN' ) )
			return true;

		if( defined( 'WPR_GC_ACTIVE_PLUGIN' ) )
			return true;

		return false;

	}


	public function excludeProducts() {
		if( isset( $_POST['wpr_giftcard_exclude_product_ids'] ) )
			update_option( 'wpr_giftcard_exclude_product_ids', $_POST['wpr_giftcard_exclude_product_ids'] );

		?>
			<tr valign="top" class="">
				<th class="titledesc" scope="row">
					<?php _e( 'Exclude products', 'kodiak-giftcards' ); ?>
					<img class="help_tip" data-tip='<?php _e( 'Products which gift cards can not be used on', 'kodiak-giftcards' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
					<td class="forminp forminp-checkbox">
					<fieldset>
						<input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="wpr_giftcard_exclude_product_ids" data-placeholder="<?php _e( 'Search for a product&hellip;', 'kodiak-giftcards' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
							$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wpr_giftcard_exclude_product_ids' ) ) ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
							}

							echo esc_attr( json_encode( $json_ids ) );
						?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
					</fieldset>
				</td>
			</tr>
		<?php

	}

	public function run_update() {

		if ( ! $wpr_gift_version ) {
			// 1.3 is the first version to use this option so we must add it
			$wpr_gift_version = WPKODIAK_VERSION;
			add_option( 'wpr_gift_version', $wpr_gift_version );
		}

		if ( version_compare( WPKODIAK_VERSION, $wpr_gift_version, '>' ) ) {
			$this->wpr_gift_v200_upgrades();
		}

		do_action( 'wpr_add_updates' );

		update_option( 'wpr_gift_version', WPKODIAK_VERSION );

		wp_redirect( admin_url() . 'edit.php?post_type=rp_shop_giftcard' );
		exit;

	}

	public function wpr_gift_v200_upgrades() {

		$loop = new WP_Query( array( 'post_type' => 'rp_shop_giftcard', 'posts_per_page' => -1 ) );
		$updatedCards = array();

		while ( $loop->have_posts() ) : $loop->the_post();

			$id = get_the_id();

			$giftcard_data = get_post_meta( $id );

			if( ! isset( $giftcard_data['_wpr_giftcard'] ) ) {

				$newGift['sendTheEmail'] = isset( $giftcard_data["rpgc_email_sent"][0] ) 	? $giftcard_data["rpgc_email_sent"][0] 	: '';
				$newGift['description']  = isset( $giftcard_data["rpgc_description"][0] ) 	? $giftcard_data["rpgc_description"][0] : '';
				$newGift['to']           = isset( $giftcard_data["rpgc_to"][0] ) 			? $giftcard_data["rpgc_to"][0] 			: '';
				$newGift['toEmail']      = isset( $giftcard_data["rpgc_email_to"][0] ) 		? $giftcard_data["rpgc_email_to"][0] 	: '';
				$newGift['from']         = isset( $giftcard_data["rpgc_from"][0] ) 			? $giftcard_data["rpgc_from"][0] 		: '';
				$newGift['fromEmail']    = isset( $giftcard_data["rpgc_email_from"][0] ) 	? $giftcard_data["rpgc_email_from"][0] 	: '';
				$newGift['amount']       = isset( $giftcard_data["rpgc_amount"][0] ) 		? $giftcard_data["rpgc_amount"][0] 		: '';
				$newGift['balance']      = isset( $giftcard_data["rpgc_balance"][0] ) 		? $giftcard_data["rpgc_balance"][0] 	: '';
				$newGift['note']         = isset( $giftcard_data["rpgc_note"][0] ) 			? $giftcard_data["rpgc_note"][0] 		: '';
				$newGift['expiry_date']  = isset( $giftcard_data["rpgc_expiry_date"][0] ) 	? $giftcard_data["rpgc_expiry_date"][0] : '';

				update_post_meta( $id, '_wpr_giftcard', $newGift );

				delete_post_meta($id, 'rpgc_to' );
				delete_post_meta($id, 'rpgc_email_to' );
				delete_post_meta($id, 'rpgc_from' );
				delete_post_meta($id, 'rpgc_email_from' );
				delete_post_meta($id, 'rpgc_amount' );
				delete_post_meta($id, 'rpgc_balance' );
				delete_post_meta($id, 'rpgc_note' );
				delete_post_meta($id, 'rpgc_expiry_date' );
				delete_post_meta($id, 'rpgc_description' );
				delete_post_meta($id, 'rpgc_email_sent' );

				$updatedCards[] = $id;
			}

		endwhile;

		wp_reset_query();
	}

	public function kodiak_giftcard_email() {

		$emailTemplate = __( "Dear", "wpkodiak_giftcards" ) . " {recipient_name},\n\n" . __( "{fullname} has sent you a gift card.", "wpkodiak_giftcards" ) . "\n\n{giftcard_message}\n\n" . __( "Card Number:", "wpkodiak_giftcards" ) . " {giftcard_number}\n\n{sitename}";
		if( isset( $_POST['kodiak_giftcard_email_template'] ) ) {
			update_option( 'kodiak_giftcard_email_template', $_POST['kodiak_giftcard_email_template'] );
			$emailTemplate = $_POST['kodiak_giftcard_email_template'];
		} else {
			$emailTemplate = kodiak_get_option( 'kodiak_giftcard_email_template' );
		}
		?>
		<tr>
			<th scope="row">Email Content</th>
			<td>
			<?php wp_editor( stripslashes( $emailTemplate ), 'kodiak_giftcard_email_template' ); ?>
			<?php
				echo __('Enter the text that is sent as purchase receipt email to users after completion of a successful purchase. HTML is accepted.','wpkodiak_giftcards' );
				echo '<br/><br/>' . __('Available template tags:', 'wpkodiak_giftcards' ) . '<br />' . kodiak_get_emails_tags_list();
			?>
			</td>
		</tr>
		<?php

	}

	public function kodiak_giftcard_email_options() {

		if( $this->activatedPlugins() ) {
			register_setting( 'wpr-options', 'wpr_options' );
			?>

			<br class="clear" />

		<?php } ?>

		<tr>
			<th scope="row"></th>
			<td>
				<a href="<?php echo esc_url( add_query_arg( array( 'kodiak_action' => 'preview_email' ), home_url() ) ); ?>" class="button-secondary" target="_blank">Preview Gift Card Email</a>
				<a href="<?php echo wp_nonce_url( add_query_arg( array( 'kodiak_action' => 'send_test_email' ) ), 'kodiak-giftcard-test-email' ); ?>" class="button-secondary">Send Test Email</a>
			</td>
		</tr>

		<?php
	}

}
//return new RPGC_Settings();
