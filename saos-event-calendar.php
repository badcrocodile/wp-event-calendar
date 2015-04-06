<?php

/*
Plugin Name: GC Music Center Event Calendar
Plugin URI: http://studioaceofspade.com
Description: Event calendar for Goshen College Music Center
Author: Studio Ace of Spade
Version: 1.0
Author URI: http://studioaceofspade.com
*/

if(!is_admin()) { 
}

function enq_scripts() {
	wp_register_style('saos-event-calendar-styles', plugins_url( 'css/styles.css', __FILE__ ));
	wp_enqueue_style('saos-event-calendar-styles');
    wp_enqueue_script('saos-event-calendar-scripts', plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'));
    $params = array(
        'site_url' => get_option('siteurl'),
        'plugin_dir' => plugins_url()
    );
    wp_localize_script('saos-event-calendar-scripts', 'saosEventCalendar', $params);
}
add_action('wp_enqueue_scripts', 'enq_scripts');

include("event_calendar_header.php");
include("event_shortcodes.php");
include("draw_calendar.php");

// Add Event CPT
add_action('init', 'cptui_register_my_cpt_event');
function cptui_register_my_cpt_event() {
    register_post_type('event', array(
        'label' => 'Events',
        'description' => '',
        'menu_icon' => 'dashicons-calendar',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'event', 'with_front' => true),
        'query_var' => true,
        'supports' => array('title','editor','excerpt','trackbacks','custom-fields','comments','revisions','thumbnail','author','page-attributes','post-formats'),
        'taxonomies' => array('post_tag'),
        'labels' => array (
            'name' => 'Events',
            'singular_name' => 'Event',
            'menu_name' => 'Events',
            'add_new' => 'Add Event',
            'add_new_item' => 'Add New Event',
            'edit' => 'Edit',
            'edit_item' => 'Edit Event',
            'new_item' => 'New Event',
            'view' => 'View Event',
            'view_item' => 'View Event',
            'search_items' => 'Search Events',
            'not_found' => 'No Events Found',
            'not_found_in_trash' => 'No Events Found in Trash',
            'parent' => 'Parent Event',
        )
    ) ); 
}
// Add Event taxonomies
add_action('init', 'cptui_register_my_taxes_tours');
function cptui_register_my_taxes_tours() {
    register_taxonomy( 'tours',array (
        0 => 'event',
    ),
    array( 
        'hierarchical' => true,
        'label' => 'Tours',
        'show_ui' => true,
        'query_var' => true,
        'show_admin_column' => true,
    ) ); 
}
add_action('init', 'cptui_register_my_taxes_venues');
function cptui_register_my_taxes_venues() {
    register_taxonomy( 'venues', array (
        0 => 'event',
    ),
    array( 
        'hierarchical' => true,
        'label' => 'Venues',
        'show_ui' => true,
        'query_var' => true,
        'show_admin_column' => true,
    ) ); 
}

?>
