<?php
/*
Plugin Name: Featured Posts
Plugin Author: Gregory Cornelius
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



function bu_featured_admin_scripts($hook_suffix) {

    if(!in_array($hook_suffix, array('post.php', 'post-new.php'))) return;

	wp_enqueue_script('bu-post-selector', plugins_url('/js/post-selector.js', __FILE__), array('jquery-ui-dialog'), '1.0', true);

	wp_enqueue_style('bu-post-selector', plugins_url('/css/post-selector.css', __FILE__));
	wp_enqueue_style('jquery-ui-dialog');
	wp_enqueue_style('bu-jquery-custom-ui', plugins_url('/css/smoothness/jquery-ui-1.8.14.custom.css', __FILE__));
	add_action('admin_footer', 'bu_post_selector_admin_footer');

}

add_action('admin_enqueue_scripts', 'bu_featured_admin_scripts', 10, 1);

/**
 * Markup for the search dialog
 **/
function bu_post_selector_admin_footer() {
    include('interface/post-selector.php');
}

function bu_feature_add_meta_box($post_type, $post) {
	add_meta_box('bufeatured', "Featured", 'bu_feature_meta_box', $post_type, 'normal', 'high');

}

add_action('add_meta_boxes', 'bu_feature_add_meta_box', 10, 2);

function bu_feature_meta_box($post, $box) {
	$feature = get_post_meta($post->ID, '_bu_feature', true);

	$post_id = empty($feature['post_id']) ? '' : $feature['post_id'];
	$title = empty($feature['title']) ? '' : $feature['title'];

	include('interface/meta-box.php');
}

function bu_feature_content_filter($content) {

	$feature = get_post_meta(get_the_ID(), '_bu_feature', true);
	if(empty($feature['post_id'])) return $content;

	$post = get_post($feature['post_id']);

	$html = sprintf('<div style="background-color: #ccc;"><h3><a href="%s">%s<a/></h3></div>', get_permalink($post), esc_html($feature['title']));

	$content .= $html;

	return $content;
}

add_filter('the_content', 'bu_feature_content_filter', 10, 1);


function bu_feature_save_post_handler($post_id, $post) {

	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX)) {
		return;
	}

	if (!is_array($_POST['bu_feature'])) {
		return;
	}

	if (!wp_verify_nonce($_POST['bu_feature_nonce'], 'bu_feature')) {
		return;
	}

	$feature_id = (int) trim(strip_tags($_POST['bu_feature']['post_id']));
	$custom_title = trim(strip_tags($_POST['bu_feature']['title']));
	// post_exists() check
	update_post_meta($post_id, '_bu_feature', array('post_id' => $feature_id, 'title' => $custom_title));

	// delete check

}

add_action('save_post', 'bu_feature_save_post_handler', 10, 2);


/**
 *
 * @todo feature image
 *
 *
 */
function bu_ajax_get_posts() {
	global $post;

	if(!wp_verify_nonce($_POST['nonce'], 'bu_ajax_post_search')) return;
	// needs to support post_types, taxonomies, and ????
	if(!current_user_can('edit_posts')) die(-1);

	$search = trim(strip_tags($_POST['s']));

	if(strpos($search, 'http://') === 0) {
		$post_id = url_to_postid($search);
		$search = '';
	}

	if(is_numeric($search)) {
		$post_id = (int) $search;
		$search = '';
	}

	$active_post_types = get_post_types();
	$post_types = explode('+', $_POST['post_types']);

	if(isset($_POST['page'])) {
		if(is_numeric($_POST['page'])) {
			$page = (int) $_POST['page'];
		} else {
			die(-1);
		}
	} else {
		$page = 1;
	}

	if(is_array($post_types)) $post_types = array_intersect($post_types, $active_post_types);
	if(!is_array($post_types) || count($post_types) === 0) $post_types = array('post');

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

	if(!empty($search)) $args['s'] = $search;

	if(is_numeric($post_id)) {
		$args['p'] = $post_id;
	}

	$query = new WP_Query($args);

	if(!$query->have_posts()) die(-1);

	$results = array();

	while ($query->have_posts()) {
		$query->the_post();
		$result = array(
			'ID' => $post->ID,
			'post_type' => $post->post_type,
			'title' => trim(esc_html(strip_tags(get_the_title($post)))),
			'date' => mysql2date(__('Y/m/d'), $post->post_date),
			'status' => $post->post_status
		);

		if (function_exists('bu_get_thumbnail')) {
			$image = array();
			$thumb = bu_get_thumbnail($post->ID, null, null, false, 'thumbnail');
			if($thumb) {
				$image['post_id'] = $thumb['post_id'];
				$sizes = get_intermediate_image_sizes();
				foreach ($sizes as $size) {
					$img = bu_get_thumbnail($post->ID, null, null, false, $size);
					if ($img) {
						$image[$size] = array(
						    'url' => $img['url'],
						    'width' => $img['width'],
						    'height' => $img['height']
						);
					}
				}
			}
			if (!empty($image)) {
				$result['image'] = $image;
			}
		}
		array_push($results, $result);
	}
	header('Content-type: application/json');
	echo json_encode($results);
	die();
}

add_action('wp_ajax_bu_get_posts', 'bu_ajax_get_posts');
