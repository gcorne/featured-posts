<?php
/*
Plugin Name: Featured Posts
Plugin Author: Gregory Cornelius
Description: Demonstration of how a dialog can be used to make curation of content easy for users
*/

/*

Copyright (C) 2011 Gregory Cornelius

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

/**
 * Enqueue scripts on the post editor screens.
 *
 * @param string $hook_suffix
 */
function gc_featured_admin_scripts( $hook_suffix ) {

	if ( ! in_array($hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
		return;
	}

	wp_enqueue_script('gc-post-selector', plugins_url('/js/post-selector.js', __FILE__), array('jquery-ui-dialog'), '1.0', true);

	wp_enqueue_style('gc-post-selector', plugins_url('/css/post-selector.css', __FILE__));
	wp_enqueue_style('jquery-ui-dialog');
	wp_enqueue_style( 'gc-ui', plugins_url( '/jquery-ui/jquery-ui-1.10.2.custom.css', __FILE__ ) );

}

add_action( 'admin_enqueue_scripts', 'gc_featured_admin_scripts', 10, 1 );

/**
 * Markup for the search dialog
 **/
function gc_post_selector_admin_footer() {
    include( 'interface/post-selector.php' );
}

add_action( 'admin_footer', 'gc_post_selector_admin_footer' );

function gc_feature_add_meta_box($post_type, $post) {
	add_meta_box( 'gcfeatured', __("Featured"), 'gc_feature_meta_box', $post_type, 'normal', 'high' );
}

add_action( 'add_meta_boxes', 'gc_feature_add_meta_box', 10, 2 );


/**
 * Render admin meta boxd.
 * @param object $post
 * @param array $box (unused)
 */
function gc_feature_meta_box( $post, $box ) {
	$feature = get_post_meta( $post->ID, '_gc_feature', true );

	$post_id = empty( $feature['post_id'] ) ? '' : $feature['post_id'];
	$title = empty( $feature['title'] ) ? '' : $feature['title'];
	$image = get_the_post_thumbnail( $post_id, 'thumbnail' );
	include( 'interface/meta-box.php' );
}

/**
 * Filter handler that appends the feature, if there is one, to the end
 * of the content.
 *
 * @param string $content
 * @return string
 */
function gc_feature_content_filter($content) {

	$feature = get_post_meta(get_the_ID(), '_gc_feature', true);
	if(empty($feature['post_id'])) return $content;

	$post = get_post($feature['post_id']);

	$html = sprintf('<div style="background-color: #ccc; min-height: 40px; padding: 20px;">%s<h3><a href="%s">%s<a/></h3></div>', get_the_post_thumbnail($post->ID, 'thumbnail'), get_permalink($post), $feature['title'] );

	$content .= $html;

	return $content;
}

add_filter('the_content', 'gc_feature_content_filter', 10, 1);

/**
 * Save feature when the post is saved.
 *
 * @param int $post_id
 * @param object $post
 */
function gc_feature_save_post_handler($post_id, $post) {

	if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	if ( ! isset( $_POST['gc_feature'] )
			|| ! is_array($_POST['gc_feature'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce($_POST['gc_feature_nonce'], 'gc_feature' ) ) {
		return;
	}

	$feature_id = (int) $_POST['gc_feature']['post_id'];
	$custom_title = trim( $_POST['gc_feature']['title'] );
	// post_exists() check
	update_post_meta($post_id, '_gc_feature', array('post_id' => $feature_id, 'title' => $custom_title));

	// delete check

}

add_action('save_post', 'gc_feature_save_post_handler', 10, 2);


/**
 * Add custom ajax action that provides a mechanism for finding a post.
 *
 */
function gc_ajax_get_posts() {
	global $post;

	if( ! wp_verify_nonce( $_POST['nonce'], 'gc_ajax_post_search' ) ) 
		return;

	// needs to support post_types, taxonomies, and ????
	if( ! current_user_can( 'edit_posts' ) ) {
		die(-1);
	}
	$search = '';
	if ( isset( $_POST['s'] ) ) {
		$search = trim( strip_tags( $_POST['s'] ) );

		if( strpos( $search, 'http://' ) === 0 ) {
			$post_id = url_to_postid( $search );
			$search = '';
		}

		if( is_numeric( $search ) ) {
			$post_id = (int) $search;
			$search = '';
		}
	}
	$post_types = explode( '+', $_POST['post_types'] );

	if ( isset( $_POST['page'] ) ) {
		if ( is_numeric( $_POST['page'] ) ) {
			$page = (int) $_POST['page'];
		} else {
			die(-1);
		}
	} else {
		$page = 1;
	}

	if ( is_array( $post_types ) ) {
		$active_post_types = get_post_types();
		$post_types = array_intersect( $post_types, $active_post_types );
	}
	if ( ! is_array( $post_types ) || count( $post_types ) === 0 ) {
		$post_types = array('post');
	}

	$args = array(
		'post_type' => $post_types,
		'suppress_filters' => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => true,
		'post_status' => 'any',
		'order' => 'DESC',
		'orderby' => 'post_date',
		'posts_per_page' => 20,
		'paged' => $page
	);

	if ( ! empty( $search ) ) {
		$args['s'] = $search;
	}

	if( isset( $post_id ) && is_numeric ($post_id ) ) {
		$args['p'] = $post_id;
	}

	$query = new WP_Query( $args );

	if( ! $query->have_posts() ) 
		die(-1);

	$results = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$result = (object) array(
			'ID' => $post->ID,
			'post_type' => $post->post_type,
			'title' => trim( htmlspecialchars( $post->post_title ) ),
			'date' => mysql2date( __('Y/m/d'), $post->post_date ),
			'status' => $post->post_status
		);

		$image = gc_get_featured_image( $post->ID );
		if ( $image ) {
			$result->image = $image;
		}
		array_push( $results, $result );
	}
	header('Content-type: application/json');
	echo json_encode( $results );
	die();
}

add_action('wp_ajax_gc_get_posts', 'gc_ajax_get_posts');

function gc_get_featured_image( $post_id ) {
	$image = new StdClass;
	$featured_image_id = get_post_thumbnail_id( $post_id );
	if ( ! $featured_image_id ) {
		return;
	}
	$image->post_id = $featured_image_id;
	$sizes = get_intermediate_image_sizes();
	foreach( $sizes as $size ) {
		$intermediate_img = wp_get_attachment_image_src( $featured_image_id, $size, false );
		$image->$size = new StdClass;
		$image->$size->src = $intermediate_img[0];
		$image->$size->width = $intermediate_img[1];
		$image->$size->height = $intermediate_img[2];
	}
	return $image;
}
