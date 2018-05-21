<?php
/**
 * Gift Card Metabox Functions
 *
 * @package     Gift-Cards-for-Woocommerce
 * @copyright   Copyright (c) 2014, Ryan Pletcher
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin()  ) {
    add_action( 'load-post.php', 'call_WPR_Gift_Card_Meta' );
    add_action( 'load-post-new.php', 'call_WPR_Gift_Card_Meta' );
}

/**
 * The Class.
 */
class WPR_Gift_Card_Meta {
	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'kodiak_giftcards_metabox' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
		global $post, $wpdb;

		// Check if our nonce is set.
		if ( ! isset( $_POST['woocommerce_giftcard_nonce'] ) )
			return $post_id;

		$nonce = $_POST['woocommerce_giftcard_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// Check the user's permissions.
		if ( 'rp_shop_giftcard' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		$newGift = new KODIAK_Giftcard();
		$card = $newGift->create( $_POST );

		$giftcardInfo = $_POST;

		if ( isset( $_POST["kodiak_regen_number"] ) ) {
			if ( $_POST["kodiak_regen_number"] == 'yes' ) {
				$newNumber = $newGift->regenerateNumber( $_POST );
				$_POST["post_title"] = $newNumber;
				$_POST["post_name"] = $newNumber;
			}
		}

		if ( isset( $_POST["save"] ) ) {
			if ( $_POST["save"] == 'Update' ) {
				if ( isset( $_POST["kodiak_resend_email"]  ) ) {
					if ( $_POST["kodiak_resend_email"] == 'yes' ) {
						$newGift->resendTheCard( $_POST );
					}
				}
			}
		}

		do_action( 'woocommerce_rpgc_options' );
		do_action( 'woocommerce_rpgc_after_save', $post_id );
	}


	/**
	 * Sets up the new meta box for the creation of a gift card.
	 * Removes the other three Meta Boxes that are not needed.
	 *
	 */
	public function kodiak_giftcards_metabox() {
		global $post;

		add_meta_box(
			'rpgc-woocommerce-data',
			__( 'Gift Card Data', 'kodiak_giftcards' ),
			array( $this, 'rpgc_meta_box'),
			'rp_shop_giftcard',
			'normal',
			'high'
		);

		$data = get_post_meta( $post->ID );

		if ( isset( $data['rpgc_id'] ) )
			if ( $data['rpgc_id'][0] <> '' )
				add_meta_box(
					'rpgc-order-data',
					__( 'Gift Card Information', 'kodiak_giftcards' ),
					array( $this, 'rpgc_info_meta_box'),
					'shop_order',
					'side',
					'default'
				);

		//if ( ! isset( $_GET['action'] ) )
		//	remove_post_type_support( 'rp_shop_giftcard', 'title' );

		if ( isset ( $_GET['action'] ) ) {
			add_meta_box(
				'rpgc-more-options',
				__( 'Additional Card Options', 'kodiak_giftcards' ),
				array( $this, 'rpgc_options_meta_box'),
				'rp_shop_giftcard',
				'side',
				'low'
			);

			add_meta_box(
				'rpgc-usage-data',
				__( 'Card Usage Data', 'kodiak_giftcards' ),
				array( $this, 'wpr_giftcard_usage_data'),
				'rp_shop_giftcard',
				'side',
				'low'
			);

		}

		remove_meta_box( 'woothemes-settings', 'rp_shop_giftcard' , 'normal' );
		remove_meta_box( 'commentstatusdiv', 'rp_shop_giftcard' , 'normal' );
		remove_meta_box( 'commentsdiv', 'rp_shop_giftcard' , 'normal' );
		remove_meta_box( 'slugdiv', 'rp_shop_giftcard' , 'normal' );
	}

	/**
	 * Creates the Giftcard Meta Box in the admin control panel when in the Giftcard Post Type.  Allows you to create a giftcard manually.
	 * @param  [type] $post
	 * @return [type]
	 */
	public function rpgc_meta_box( $post ) {
		global $woocommerce;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_giftcard_nonce' );

		$giftValue = get_post_meta( $post->ID, '_wpr_giftcard', true );

		?>
		<style type="text/css"  media="screen">
			<?php if ( get_the_title( $post->id ) == '' ) { ?>
			<?php } ?>
			#edit-slug-box,
			#minor-publishing-actions {
				display:none
			}

			.form-field input[type="text"],
			.form-field input[type="email"],
			.form-field input[type="number"],
			.form-field textarea {
				width:95% !important;
			}

			input[type="checkbox"],
			input[type="radio"] {
				float: left;
				width:16px;
			}

			.kodiak-giftcard-table-heading {
				background: #f1f1f1;
				padding:0.6rem;
				font-weight:bold;
			}

			.giftcard-field-description{
				width: 100%;
				float: left;
				padding-bottom: 0.5rem;
			}

			.kodiak-giftcard-table-field-heading {
				width: 15%;
				text-align: right;
				vertical-align:
				top; padding: 5px 5px 0;
				font-weight: bold;

			}

		</style>

		<div id="giftcard_options" class="panel woocommerce_options_panel">
		<?php

		do_action( 'rpgc_woocommerce_options_before_sender' );


		// Description
		woocommerce_wp_textarea_input(
			array(
				'id' 			=> 'description',
				'label'			=> __( 'Gift Card description', 'kodiak_giftcards' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'Enter an optional description for this gift card. Only for your reference.', 'kodiak_giftcards' ),
				'value'			=> isset( $giftValue['description'] ) ? $giftValue['description'] : ''
			)
		);

		do_action( 'rpgc_woocommerce_options_after_description' );

		?>
		<table style="width: 100%">
			<tbody>
				<tr>
					<td colspan="2" class="kodiak-giftcard-table-heading"><?php _e( 'To', 'kodiak_giftcards' ); ?></td>
				</tr>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Name', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="text" name="to" placeholder="" value="<?php echo isset( $giftValue['to'] ) ? $giftValue['to'] : '' ?>">
						<div class="giftcard-field-description"><?php _e( 'Who is getting this gift card.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Email', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="email" name="toEmail" placeholder="" value="<?php echo isset( $giftValue['toEmail'] ) ? $giftValue['toEmail'] : '' ?>">
						<div class="giftcard-field-description"><?php _e( 'What email should we send this gift card to.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<tr>
					<td colspan="2" class="kodiak-giftcard-table-heading"><?php _e( 'From', 'kodiak_giftcards' ); ?></td>
				</tr>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Name', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="text" name="from" placeholder="" value="<?php echo isset( $giftValue['from'] ) ? $giftValue['from'] : '' ?>">
						<div class="giftcard-field-description"><?php _e( 'Who is sending this gift card.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Email', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="email" name="fromEmail" placeholder="" value="<?php echo isset( $giftValue['fromEmail'] ) ? $giftValue['fromEmail'] : '' ?>">
						<div class="giftcard-field-description"><?php _e( 'What email account is sending this gift card.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<?php do_action( 'rpgc_woocommerce_options_before_personalize' ); ?>

				<tr>
					<td colspan="2" class="kodiak-giftcard-table-heading"><?php _e( 'Personalize it', 'kodiak_giftcards' ); ?></td>
				</tr>
				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Amount', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="number" name="amount" placeholder="" value="<?php echo isset( $giftValue['amount'] ) ? $giftValue['amount'] : '' ?>" step='any' min='0'>
						<div class="giftcard-field-description"><?php _e( 'Original Value of the Gift Card.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<?php
				if ( isset( $_GET['action']  ) ) {
					if ( $_GET['action'] == 'edit' ) { ?>
						<tr>
							<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Balance', 'kodiak_giftcards' ); ?></td>
							<td class="form-field">
								<input type="number" name="balance" placeholder="" value="<?php echo isset( $giftValue['balance'] ) ? $giftValue['balance'] : '' ?>" step='any' min='0'>
								<div class="giftcard-field-description"><?php _e( 'Remaining Balance of the Gift Car.', 'kodiak_giftcards' ) ?></div>
							</td>
						</tr>
					<?php }
				}
				?>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Note', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<textarea name="note" placeholder="" rows='3'><?php echo isset( $giftValue['note'] ) ? $giftValue['note'] : '' ?></textarea>
						<div class="giftcard-field-description"><?php _e( 'Enter a message to your customer.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

				<tr>
					<td class="kodiak-giftcard-table-field-heading"><?php _e( 'Expiry Date', 'kodiak_giftcards' ); ?></td>
					<td class="form-field">
						<input type="text" name="expiry_date" placeholder="" value="<?php echo isset( $giftValue['expiry_date'] ) ? $giftValue['expiry_date'] : '' ?>" >
						<div class="giftcard-field-description"><?php _e( 'The date this Gift Card will expire, <code>YYYY-MM-DD</code>.', 'kodiak_giftcards' ) ?></div>
					</td>
				</tr>

			</tbody>
		</table>
		<?php

		do_action( 'rpgc_woocommerce_options_after_personalize', $giftValue );

		echo '</div>';
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var $body = $('body');

				$body.on('click', '.showTitle', function() {

					$('#post-body-content').slideToggle();
				});

				$( '.date-picker' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1

				});

				$('#_wpr_cp').change(function( $ ) {
				    var c = this.checked ? '1.00': '';

					$('#_regular_price').val( c );
				});

			});
		</script>

		<?php
	}



	/**
	 * Creates the Giftcard Regenerate Meta Box in the admin control panel when in the Giftcard Post Type.  Allows you to click a button regenerate the number.
	 * @param  [type] $post
	 * @return [type]
	 */
	public function rpgc_options_meta_box( $post ) {
		global $woocommerce;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );


		echo '<div id="giftcard_regenerate" class="panel woocommerce_options_panel">';
		echo '    <div class="options_group">';

		if( $post->post_status <> 'zerobalance' ) {
			// Regenerate the Card Number
			woocommerce_wp_checkbox( array( 'id' => 'kodiak_resend_email', 'label' => __( 'Send Gift Card Email', 'kodiak_giftcards' ) ) );

			// Regenerate the Card Number
			woocommerce_wp_checkbox( array( 'id' => 'kodiak_regen_number', 'label' => __( 'Regenerate Card Number', 'kodiak_giftcards' ) ) );


			do_action( 'rpgc_add_more_options' );

		} else {
			_e( 'No additional options available. Zero balance', 'kodiak_giftcards' );
		}

		echo '    </div>';
		echo '</div>';

	}



	public function rpgc_info_meta_box( $post ) {
		global $wpdb;

		$data = get_post_meta( $post->ID );

		$orderCardNumbers 	= wpr_get_order_card_numbers( $post->ID );
		$orderCardBalance 	= wpr_get_order_card_balance( $post->ID );
		$orderCardPayment 	= wpr_get_order_card_payment( $post->ID );
		$isAlreadyRefunded	= wpr_get_order_refund_status( $post->ID );

		foreach ($orderCardNumbers as $key => $orderCardNumber ) {
			echo '<div id="giftcard_regenerate" class="panel woocommerce_options_panel">';
			echo '    <div class="options_group">';
				echo '<ul>';
					if ( isset( $orderCardNumber ) )
						echo '<li>' . __( 'Gift Card #:', 'kodiak_giftcards' ) . ' ' . esc_attr( $orderCardNumber ) . '</li>';

					if ( isset( $orderCardPayment ) )
						echo '<li>' . __( 'Payment:', 'kodiak_giftcards' ) . ' ' . wc_price( $orderCardPayment[ $key ] ) . '</li>';

					if ( isset( $orderCardBalance ) )
						echo '<li>' . __( 'Balance remaining:', 'kodiak_giftcards' ) . ' ' . wc_price( $orderCardBalance[ $key ] ) . '</li>';

				echo '</ul>';

				$giftcard_found = wpr_get_giftcard_by_code( $orderCardNumber );

				if ( $giftcard_found ) {
					echo '<div>';
						$link = 'post.php?post=' . $giftcard_found . '&action=edit';
						echo '<a href="' . admin_url( $link ) . '">' . __('Access Gift Card', 'kodiak_giftcards') . '</a>';

						if( ! empty( $isAlreadyRefunded[ $key] ) )
							echo  '<br /><span style="color: #dd0000;">' . __( 'Gift card refunded ', 'kodiak_giftcards' ) . ' ' . wc_price( $orderCardPayment[ $key ] ) . '</span>';
					echo '</div>';

				}

			echo '    </div>';
			echo '</div>';
		}
	}



	// Meta box with gift card used on the order
	public function wpr_giftcard_usage_data( $post ) { ?>
		<div id="giftcard_usage" class="panel woocommerce_options_panel">
		<?php
		$giftcardDecreaseIDs = get_post_meta( $post->ID, 'wpr_existingOrders_id', true );
		$giftcardReloads = get_post_meta( $post->ID, '_wpr_card_reloads', true );

		$activity = 0;

		if( ! empty($giftcardDecreaseIDs) ) {
			$activity = 1;
		?>
			<div class="options_group">

				<?php
				foreach ($giftcardDecreaseIDs as $giftID ) {
					$giftcardIDS = wpr_get_order_card_ids( $giftID );
					$giftcardPayments = wpr_get_order_card_payment( $giftID );
					$giftcardBalances = wpr_get_order_card_balance( $giftID );
					//$giftcarBalance -= $giftcardPayment;
					$orederLink = admin_url( 'post.php?post=' . $giftID . '&action=edit' );


					foreach ($giftcardPayments as $key => $giftcardPayment) {
						if ( $giftcardIDS[ $key ] == $post->ID ) { ?>

							<div class="box-inside">
								<p>
									<strong><?php _e( 'Order Number:', 'kodiak_giftcards' ); ?></strong>&nbsp;
									<span><a href="<?php echo $orederLink; ?>"><?php echo esc_attr( $giftID ); ?></a></span>
									<br />
									<strong><?php _e( 'Amount Used:', 'kodiak_giftcards' ); ?></strong>&nbsp;
									<span><?php echo wc_price( $giftcardPayment ); ?></span>
									<br />
									<strong><?php _e( 'Card Balance After Order:', 'kodiak_giftcards' ); ?></strong>&nbsp;
									<span><?php echo wc_price( $giftcardBalances[ $key ] ); ?></span>
								</p>
							</div>

				<?php
						}
					}
				} ?>

			</div>
		<?php
		}

		if ( ! empty($giftcardReloads) ) {
			$activity = 1;
			?>
			<div class="options_group">
				<?php foreach ($giftcardReloads as $giftIncrease ) {
					$orederLink = admin_url( 'post.php?post=' . $giftIncrease["Order"] . '&action=edit' );
					?>

					<div class="box-inside">
						<p>
							<strong><?php _e( 'Order Number:', 'kodiak_giftcards' ); ?></strong>&nbsp;
							<span><a href="<?php echo $orederLink; ?>"><?php echo esc_attr( $giftIncrease["Order"] ); ?></a></span>
							<br />
							<strong><?php _e( 'Card Balance Increased:', 'kodiak_giftcards' ); ?></strong>&nbsp;
							<span><?php echo wc_price( $giftIncrease["Amount"] ); ?></span>
						</p>
					</div>
				<?php } ?>
			</div>
		<?php
		}

		if ($activity == 0 ) {
			?>
				<div class="options_group" style="text-align: center;">
				<strong><?php _e( 'Gift card has not been used.', 'kodiak_giftcards' ); ?></strong>

				</div>

			<?php
		}
		?>
		</div>
		<?php
	}
}

/**
 * Calls the class on the post edit screen.
 */
function call_WPR_Gift_Card_Meta() {
    	new WPR_Gift_Card_Meta();
}
