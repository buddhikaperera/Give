<?php
/**
 * Admin Pages
 *
 * @package     Give
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the admin submenu pages under the Give menu and assigns their
 * links to global variables
 *
 * @since 1.0
 *
 * @global $give_settings_page
 * @global $give_payments_page
 * @global $give_reports_page
 * @global $give_add_ons_page
 * @global $give_donors_page
 *
 * @return void
 */
function give_add_options_links() {
	global $give_settings_page, $give_payments_page, $give_reports_page, $give_add_ons_page, $give_donors_page, $give_tools_page;

	//Payments
	$give_payment       = get_post_type_object( 'give_payment' );
	$give_payments_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		$give_payment->labels->name,
		$give_payment->labels->menu_name,
		'edit_give_payments',
		'give-payment-history',
		'give_payment_history_page'
	);

	//Donors
	$give_donors_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		esc_html__( 'Donors', 'give' ),
		esc_html__( 'Donors', 'give' ),
		'view_give_reports',
		'give-donors',
		'give_donors_page'
	);

	//Reports`
	$give_reports_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		esc_html__( 'Donation Reports', 'give' ),
		esc_html__( 'Reports', 'give' ),
		'view_give_reports',
		'give-reports',
		array(
			Give()->give_settings,
			'output',
		)
	);

	//Settings
	$give_settings_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		esc_html__( 'Give Settings', 'give' ),
		esc_html__( 'Settings', 'give' ),
		'manage_give_settings',
		'give-settings',
		array(
			Give()->give_settings,
			'output',
		)
	);

	//Tools.
	$give_tools_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		esc_html__( 'Give Tools', 'give' ),
		esc_html__( 'Tools', 'give' ),
		'manage_give_settings',
		'give-tools',
		array(
			Give()->give_settings,
			'output',
		)
	);

	//Add-ons
	$give_add_ons_page = add_submenu_page(
		'edit.php?post_type=give_forms',
		esc_html__( 'Give Add-ons', 'give' ),
		esc_html__( 'Add-ons', 'give' ),
		'install_plugins',
		'give-addons',
		'give_add_ons_page'
	);
}

add_action( 'admin_menu', 'give_add_options_links', 10 );

/**
 *  Determines whether the current admin page is a Give admin page.
 *
 *  Only works after the `wp_loaded` hook, & most effective
 *  starting on `admin_menu` hook.
 *
 * @since 1.0
 * @since 2.1 Simplified function.
 *
 * @param string $passed_page Optional. Main page's slug
 * @param string $passed_view Optional. Page view ( ex: `edit` or `delete` )
 *
 * @return bool True if Give admin page.
 */
function give_is_admin_page( $passed_page = '', $passed_view = '' ) {
	global $pagenow, $typenow;

	$get_query_args = isset( $_GET ) ? array_map( 'give_clean', $_GET ) : array();
	$is_admin       = false;
	$query_vars     = wp_parse_args( $get_query_args, array_fill_keys( array( 'post_type', 'action', 'taxonomy', 'page', 'view', 'tab' ), false ) );

	// Page expected to pass.
	$expected_pages = array( 'give_forms', 'categories', 'tags', 'payments', 'reports', 'settings', 'addons', 'donors' );

	// If post type or type is give_forms.
	if ( ( 'give_forms' === $typenow || 'give_forms' === $query_vars['post_type'] ) || in_array( $passed_page, $expected_pages, true ) ) {
		if ( 'edit.php' === $pagenow && 'give_forms' !== $passed_page ) {

			switch ( $passed_page ) {
				// Give reports page.
				case 'reports':
					$has_view = array_intersect( array( $passed_view, $query_vars['view'] ), array( 'earnings', 'gateways', 'export', 'logs', 'donors', 'customers' ) );

					// Check if passed view page is available or not.
					if ( ! empty( $has_view ) ) {

						// Check for customers or donor page.
						if ( $passed_view !== $query_vars['view'] && 'donors' === $passed_view && 'customers' === $query_vars['view'] ) {
							$is_admin = true;
						} else if ( $passed_view === $query_vars['view'] && 'give-reports' === $query_vars['page'] ) {
							// If we are on earning report page.
							if ( 'earnings' === $passed_view && in_array( $query_vars['view'], array( 'earnings', '-1', false ), true ) ) {
								$is_admin = true;
							}
						}
					} else if ( 'give-reports' === $query_vars['page'] ) {
						$is_admin = true;
					}
					break;
				// Give setting page.
				case 'settings':
					$has_view  = array_intersect( array( $passed_view, $query_vars['tab'] ), array( 'general', 'gateways', 'emails', 'display', 'licenses', 'api', 'advanced', 'system_info' ) );

					if ( 'give-settings' === $query_vars['page'] ) {
						$is_admin = (bool) (
							( $passed_view === $query_vars['tab'] && ! empty( $has_view ) && 'general' !== $query_vars['tab'] )
							|| ( 'general' === $query_vars['tab'] || false === $query_vars['tab'] ) // If general tab or has no tab.
						);
					}
					break;
				// Give Donors page.
				case 'donors':
					$has_view = array_intersect( array( $passed_view, $query_vars['view'] ), array( 'list-table', 'overview', 'notes' ) );
					if ( 'give-donors' === $query_vars['page'] && ! empty( $has_view ) ) {
						if ( 'list-table' === $passed_view && false === $query_vars['view'] || $passed_view === $query_vars['view'] ) {
							$is_admin = true;
						}
					}
					break;
				// Give Add-on page.
				case 'addons':
					$is_admin = (bool) ( 'give-addons' === $query_vars['page'] );
					break;
				// Give Donations page.
				case 'payments' :
					if ( 'give-payment-history' == $query_vars['page'] ) {
						if ( ( 'list-table' === $passed_view || empty( $passed_view ) )
						     && false === $query_vars['view']
						     || (
							     'edit' === $passed_view
							     && 'view-payment-details' === $query_vars['view']
						     )
						) {
							$is_admin = true;
						}
					}
					break;
			}
		} else if ( in_array( $pagenow, array( 'post-new.php', 'edit-tags.php', 'post.php' ), true ) || ( 'give_forms' === $passed_page && 'edit.php' === $pagenow ) ) {

			switch ( $passed_page ) {
				// Category or tags page.
				case 'categories':
				case 'tags':
					if (
						in_array( $query_vars['taxonomy'], array( 'give_forms_category', 'give_forms_tag' ), true )
						&& 'edit-tags.php' === $pagenow
					) {
						switch ( $passed_view ) {
							case 'list-table':
							case 'new':
								$is_admin = (bool) ( 'edit' !== $query_vars['action'] );
								break;
							case 'edit':
								$is_admin = (bool) ( 'edit' === $query_vars['action'] );
								break;
							default:
								$is_admin = (bool) (
									empty( $passed_view )
									&& 'edit-tags.php' === $pagenow
								);
						}
					}
					break;
				// Give Donation form page.
				case 'give_forms':
					switch ( $passed_view ) {
						case 'new':
						case 'list-table':
							$is_admin = (bool) ( 'post-new.php' === $pagenow );
							break;
						case 'edit':
							$is_admin = (bool) ( 'post.php' === $pagenow );
							break;
						default:
							$is_admin = (bool) (
								'give_forms' === $query_vars['post_type']
								|| (
									'post-new.php' === $pagenow
									&& 'give_forms' === $query_vars['post_type']
								)
							);
					}
					break;
			}
		}
	} else {
		global $give_payments_page, $give_settings_page, $give_reports_page, $give_system_info_page, $give_add_ons_page, $give_settings_export, $give_donors_page, $give_tools_page;
		$admin_pages = apply_filters( 'give_admin_pages', array(
			$give_payments_page,
			$give_settings_page,
			$give_reports_page,
			$give_system_info_page,
			$give_add_ons_page,
			$give_settings_export,
			$give_donors_page,
			$give_tools_page,
			'widgets.php',
		) );

		$is_admin = (bool) ( 'give_forms' == $typenow || in_array( $pagenow, array_merge( $admin_pages, array( 'index.php', 'post-new.php', 'post.php' ) ), true ) );
	}

	return (bool) apply_filters( 'give_is_admin_page', $is_admin, $query_vars['page'], $query_vars['view'], $passed_page, $passed_view );
}

/**
 * Add setting tab to give-settings page
 *
 * @since  1.8
 * @param  array $settings
 * @return array
 */
function give_settings_page_pages( $settings ) {
	include( 'abstract-admin-settings-page.php' );
	include( 'settings/class-settings-cmb2-backward-compatibility.php' );

	$settings = array(
		// General settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-general.php' ),

		// Payment Gateways Settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-gateways.php' ),

		// Display settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-display.php' ),

		// Emails settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-email.php' ),

		// Addons settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-addon.php' ),

		// License settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-license.php' ),

		// Advanced settings.
		include( GIVE_PLUGIN_DIR . 'includes/admin/settings/class-settings-advanced.php' )
	);

	// Output.
	return $settings;
}
add_filter( 'give-settings_get_settings_pages', 'give_settings_page_pages', 0, 1 );


/**
 * Add setting tab to give-settings page
 *
 * @since  1.8
 * @param  array $settings
 * @return array
 */
function give_reports_page_pages( $settings ) {
	include( 'abstract-admin-settings-page.php' );

	$settings = array(
		// Earnings.
		include( 'reports/class-earnings-report.php' ),

		// Forms.
		include( 'reports/class-forms-report.php' ),

		// Gateways.
		include( 'reports/class-gateways-report.php' ),

	);

	// Output.
	return $settings;
}
add_filter( 'give-reports_get_settings_pages', 'give_reports_page_pages', 0, 1 );

/**
 * Add setting tab to give-settings page
 *
 * @since  1.8
 * @param  array $settings
 * @return array
 */
function give_tools_page_pages( $settings ) {
	include( 'abstract-admin-settings-page.php' );

	$settings = array(
		// System Info.
		include( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-system-info.php' ),

		// Logs.
		include( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-logs.php' ),

		// API.
		include( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-api.php' ),

		// Data.
		include( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-data.php' ),

		// Export.
		include( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-export.php' ),

		// Import
		include_once( GIVE_PLUGIN_DIR . 'includes/admin/tools/class-settings-import.php' ),
	);

	// Output.
	return $settings;
}
add_filter( 'give-tools_get_settings_pages', 'give_tools_page_pages', 0, 1 );

/**
 * Set default tools page tab.
 *
 * @since  1.8
 * @param  string $default_tab Default tab name.
 * @return string
 */
function give_set_default_tab_form_tools_page( $default_tab ) {
	return 'system-info';
}
add_filter( 'give_default_setting_tab_give-tools', 'give_set_default_tab_form_tools_page', 10, 1 );


/**
 * Set default reports page tab.
 *
 * @since  1.8
 * @param  string $default_tab Default tab name.
 * @return string
 */
function give_set_default_tab_form_reports_page( $default_tab ) {
	return 'earnings';
}
add_filter( 'give_default_setting_tab_give-reports', 'give_set_default_tab_form_reports_page', 10, 1 );


/**
 * Add a page display state for special Give pages in the page list table.
 *
 * @since 1.8.18
 *
 * @param array $post_states An array of post display states.
 * @param WP_Post $post The current post object.
 *
 * @return array
 */
function give_add_display_page_states( $post_states, $post ) {

	switch( $post->ID ) {
		case give_get_option( 'success_page' ):
			$post_states['give_successfully_page'] = __( 'Donation Success Page', 'give' );
			break;

		case give_get_option( 'failure_page' ):
			$post_states['give_failure_page'] = __( 'Donation Failed Page', 'give' );
			break;

		case give_get_option( 'history_page' ):
			$post_states['give_history_page'] = __( 'Donation History Page', 'give' );
			break;
	}

	return $post_states;
}

// Add a post display state for special Give pages.
add_filter( 'display_post_states', 'give_add_display_page_states', 10, 2 );