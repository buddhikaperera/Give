<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Give_Seq_Donation_Number {
	/**
	 * Donation number meta key name
	 *
	 * @since 2.1.0
	 * @var string
	 */
	private $meta_key = '_give_donation_number';

	/**
	 * Instance.
	 *
	 * @since  2.1.0
	 * @access private
	 * @var
	 */
	static private $instance;

	/**
	 * Singleton pattern.
	 *
	 * @since  2.1.0
	 * @access private
	 */
	private function __construct() {
	}


	/**
	 * Get instance.
	 *
	 * @since  2.1.0
	 * @access static
	 * @return Give_Seq_Donation_Number
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			self::$instance = new static();

			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin, bailing if any required conditions are not met,
	 * including minimum WooCommerce version
	 *
	 * @since 2.1.0
	 */
	public function init() {
		add_action( 'wp_insert_post', array( $this, '__save_donation_title' ), 10, 3 );
	}

	/**
	 * Set serialize donation number as donation title.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int     $donation_id
	 * @param WP_Post $donation_post_data
	 * @param bool    $existing_donation_updated
	 *
	 * @return void
	 */
	public function __save_donation_title( $donation_id, $donation_post_data, $existing_donation_updated ) {
		// Bailout
		if ( $existing_donation_updated ) {
			return;
		}

		$serial_code = $this->get_next_serial_code();

		try {
			/* @var WP_Error $wp_error */
			$wp_error = wp_update_post(
				array(
					'ID'         => $donation_id,
					'post_title' => $serial_code
				)
			);

			if ( is_wp_error( $wp_error ) ) {
				throw new Exception( $wp_error->get_error_message() );
			}
		} catch ( Exception $e ) {
			error_log( "Give caught exception: {$e->getMessage()}" );
		}

		$this->set_donation_number( $donation_id );
	}


	/**
	 * Get next donation number
	 *
	 * @since  2.1.0
	 * @access public
	 * @return string
	 */
	public function get_next() {
		$max_donation_number = $this->get_max_donation_number();

		return ++ $max_donation_number;
	}

	/**
	 * get donation number serial code
	 *
	 * @since  2.1.0
	 * @access public
	 * @return string
	 */
	public function get_next_serial_code() {
		$max_donation_number = $this->get_max_donation_number();
		$donation_number     = ++ $max_donation_number;

		/**
		 * Filter the donation serial code
		 *
		 * @since 2.1.0
		 */
		return apply_filters( 'give_get_donation_serial_code', "PW-{$donation_number}" );
	}


	/**
	 * Get max donation number.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_max_donation_number() {
		global $wpdb;

		$max_donatiion_number = $wpdb->get_var(
			$wpdb->prepare( "
			SELECT MAX(meta_value) FROM $wpdb->paymentmeta WHERE meta_key=%s
			", $this->meta_key )
		);

		return empty( $max_donatiion_number ) ? 0 : $max_donatiion_number;
	}

	/**
	 * Get donation number serial code
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int|Give_Payment $donation
	 * @param boolean          $with_hash
	 *
	 * @return string
	 */
	public function get_serial_code( $donation, $with_hash = true ) {
		$serial_code = '';
		$donation    = $donation instanceof Give_Payment ? $donation : new Give_Payment( $donation );

		// Bailout.
		if ( empty( $donation->ID ) ) {
			return $serial_code;
		}

		$donation_number = give_get_meta( $donation->ID, $this->meta_key, true );

		if ( ! empty( $donation_number ) ) {
			$serial_code = get_the_title( $donation->ID );
			$serial_code = $with_hash ? "#{$serial_code}" : $serial_code;
		}

		/**
		 * Filter the donation serial code
		 *
		 * @since 2.1.0
		 */
		return apply_filters( 'give_get_donation_serial_code', $serial_code, $donation, $with_hash );
	}

	/**
	 * Set donation number
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int $donation_id
	 *
	 * @return boolean|int
	 */
	public function set_donation_number( $donation_id ) {
		return give_update_meta( $donation_id, $this->meta_key, $this->get_next() );
	}


	/**
	 * Search for a donation with donation number or serial code
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param string $donation_number_or_serial_code
	 *
	 * @return int
	 */
	public function find_donation_by_donation_number( $donation_number_or_serial_code ) {
		return 0;
	}


	/**
	 * Get a donation id on basis of donation number or serial code.
	 *
	 * @since  2.11.0
	 * @access public
	 *
	 * @param string $donation_number_or_serial_code
	 *
	 * @return int
	 */
	public function get_donation_number( $donation_number_or_serial_code ) {
		return 0;
	}
}

// @todo: add post_title support in Give_Payment
// @todo: resolve caching issue: donation listing is not updating when updating donation