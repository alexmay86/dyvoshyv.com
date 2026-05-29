<?php
/**
 * GTM dataLayer events for Elementor Pro forms (successful submit only).
 *
 * Map widget CSS ID (Advanced → CSS ID on Form block) to event name.
 * ID renders on widget wrapper (#consultation-form), not on <form>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'blankslate_gtm_elementor_form_events_by_css_id' ) ) {

/**
 * @return array<string, string> CSS ID => GTM event name.
 */
function blankslate_gtm_elementor_form_events_by_css_id() {
	return apply_filters(
		'blankslate_gtm_elementor_form_events_by_css_id',
		array(
			'consultation-form' => 'send_form_personal_advice',
			'contact-form'      => 'send_form_send_message',
		)
	);
}

} // blankslate_gtm_elementor_form_events_by_css_id

if ( ! function_exists( 'blankslate_gtm_elementor_form_css_id_for_record' ) ) {

/**
 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
 * @return string CSS ID or empty.
 */
function blankslate_gtm_elementor_form_css_id_for_record( $record ) {
	$css_id = trim( (string) $record->get_form_settings( '_element_id' ) );
	if ( '' !== $css_id ) {
		return $css_id;
	}

	$form_id = $record->get_form_settings( 'id' );
	if ( ! $form_id || ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\ElementorPro\Modules\Forms\Module' ) ) {
		return '';
	}

	$plugin = \Elementor\Plugin::$instance;
	if ( ! $plugin || ! isset( $plugin->documents ) ) {
		return '';
	}

	$post_ids = array_unique(
		array_filter(
			array_map(
				'absint',
				array(
					$record->get_form_settings( 'form_post_id' ),
					$record->get_form_settings( 'edit_post_id' ),
					isset( $_POST['post_id'] ) ? wp_unslash( $_POST['post_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
				)
			)
		)
	);

	foreach ( $post_ids as $post_id ) {
		$document = $plugin->documents->get( $post_id );
		if ( ! $document ) {
			continue;
		}

		$element = \ElementorPro\Modules\Forms\Module::find_element_recursive( $document->get_elements_data(), $form_id );
		if ( ! empty( $element['settings']['_element_id'] ) ) {
			return trim( (string) $element['settings']['_element_id'] );
		}
	}

	return '';
}

} // blankslate_gtm_elementor_form_css_id_for_record

if ( ! function_exists( 'blankslate_gtm_elementor_form_event_for_record' ) ) {

/**
 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
 * @return string GTM event name or empty.
 */
function blankslate_gtm_elementor_form_event_for_record( $record ) {
	$css_id = blankslate_gtm_elementor_form_css_id_for_record( $record );
	$map    = blankslate_gtm_elementor_form_events_by_css_id();

	if ( '' !== $css_id && isset( $map[ $css_id ] ) ) {
		return $map[ $css_id ];
	}

	return '';
}

} // blankslate_gtm_elementor_form_event_for_record

if ( ! function_exists( 'blankslate_gtm_elementor_form_on_submit' ) ) {

/**
 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record  Form record.
 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler Ajax handler.
 */
function blankslate_gtm_elementor_form_on_submit( $record, $handler ) {
	if ( ! $handler->is_success ) {
		return;
	}

	$event = blankslate_gtm_elementor_form_event_for_record( $record );
	if ( '' === $event ) {
		return;
	}

	$handler->add_response_data( 'blankslate_gtm_event', $event );
}

} // blankslate_gtm_elementor_form_on_submit

/**
 * Register hook after Elementor Pro is loaded.
 */
function blankslate_gtm_elementor_form_register_hook() {
	if ( ! class_exists( '\ElementorPro\Modules\Forms\Classes\Form_Record' ) ) {
		return;
	}
	if ( has_action( 'elementor_pro/forms/new_record', 'blankslate_gtm_elementor_form_on_submit' ) ) {
		return;
	}
	add_action( 'elementor_pro/forms/new_record', 'blankslate_gtm_elementor_form_on_submit', 99, 2 );
}
add_action( 'elementor_pro/init', 'blankslate_gtm_elementor_form_register_hook', 20 );
add_action( 'plugins_loaded', 'blankslate_gtm_elementor_form_register_hook', 25 );
