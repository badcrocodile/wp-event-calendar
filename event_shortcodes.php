<?php
/** EVENT SHORTCODES **/

/* 
 * The calendar shortcode
 * Usage to show program calendar: [event-calendar calendar="csa"]
 * Options are 'main', 'csa', and 'mt'. Defaults to 'main'.
 * Usage to show category calendar: [event-calendar category="category-slug"]
 * Options are any existing Event category
 */

add_shortcode('event-calendar', 'saos_show_event_calendar');
function saos_show_event_calendar($atts) {
	ob_start();
	extract(shortcode_atts(array(
		'calendar' => false,
		'category' => false
	), $atts));
	$options = array(
		'calendar' => $calendar,
		'category' => $category
	);
	$cheader = calendar_header($calendar, $category);
	echo $cheader;
	echo '<div id="calendar" class="calendar-grid">';
	$calendar = draw_calendar($calendar, $category);
	echo $calendar;
	echo '</div>';
	
	$x = ob_get_clean();
	
	return $x;
}

/*
 * Event list shortcode
 * Usage: [list-events type="event" tours="2014-15-performing-arts-series"]
 * Options are any established event tour taxonomy slug
 * Default is to list all events
 */
 
add_shortcode('list-events', 'saos_list_events_shortcode');
function saos_list_events_shortcode($atts) {
    ob_start();
    // define attributes and their defaults
    extract(shortcode_atts(array(
        'type' => 'event',
        'order' => 'date',
        'orderby' => 'title',
        'posts' => -1,
        'tours' => '',
        'venues' => '',
    ), $atts ) );
    // define query parameters based on attributes
    $options = array(
        'post_type' => $type,
        'posts_per_page' => $posts,
        'tours' => $tours,
        'venues' => $venues,
        'meta_key' => 'date',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'posts_per_page' => -1
    );
    $query = new WP_Query($options);
    // run the loop based on the query
    if ($query->have_posts()) : ?>
		<div class="event-listings">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php get_template_part('loop/loop', 'events'); ?>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
    <?php else : ?>
		<div class="event-listings">
			<h2>No events scheduled</h2>
		</div>
    <?php endif;
    
	$myvariable = ob_get_clean();
	
	return $myvariable;
}
?>