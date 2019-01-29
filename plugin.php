<?php
/**
 * Plugin Name: HM Post GTM
 * Description: Enables adding a tag manager instance per post.
 * Author: Human Made Limited
 * Author URL: https://humanmade.com
 */

namespace HM\PostGTM;

use WP_Post;

if ( ! function_exists( 'HM_GTM\tag' ) ) {
	trigger_error( 'HM Post GTM requires the HM GTM plugin to be installed and activated.' );
	return;
}

function add_meta_boxes( $post_type ) {
	if ( ! in_array( $post_type, get_post_types( [ 'public' => true ] ), true ) ) {
		return;
	}

	add_meta_box(
		'hm-post-gtm',
		__( 'Google Tag Manager', 'hm-post-gtm' ),
		__NAMESPACE__ . '\meta_box',
		$post_type,
		'side'
	);
}

add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_meta_boxes' );

function meta_box( WP_POST $post ) {
	$container_id = get_post_meta( $post->ID, 'hm_post_gtm', true );
	printf( '<input name="hm_post_gtm" type="text" class="widefat" value="%s" placeholder="%s" />',
		esc_attr( $container_id ),
		'GTM-XXXXXX'
	);
}

function save_post( $post_id, WP_Post $post ) {
	if ( defined( 'REST_REQUEST' ) ) {
		return;
	}

	$container_id = filter_input( INPUT_POST, 'hm_post_gtm', FILTER_SANITIZE_STRING );

	update_post_meta( $post_id, 'hm_post_gtm', $container_id );
}

add_action( 'save_post', __NAMESPACE__ . '\save_post', 10, 2 );

function tag() {
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();
	$container_id = get_post_meta( $post_id, 'hm_post_gtm', true );

	if ( empty( $container_id ) ) {
		return;
	}

	$set_container_id = function () use ( $container_id ) {
		return $container_id;
	};

	$set_datalayer_name = function () use ( $container_id ) {
		$key = strtoupper( $container_id );
		$key = sanitize_key( $key );
		$key = str_replace( [ '-', '.' ], '_', $key );
		return 'dataLayer' . $key;
	};

	add_filter( 'hm_gtm_id', $set_container_id );
	add_filter( 'hm_gtm_network_id', '__return_false' );
	add_filter( 'hm_gtm_data_layer_var', $set_datalayer_name );

	// Output the tag after adding our filters.
	\HM_GTM\tag();

	remove_filter( 'hm_gtm_id', $set_container_id );
	remove_filter( 'hm_gtm_network_id', '__return_false' );
	remove_filter( 'hm_gtm_data_layer_var', $set_datalayer_name );
}

// Output the tag again.
add_action( 'wp_head', __NAMESPACE__ . '\tag', 1 );
