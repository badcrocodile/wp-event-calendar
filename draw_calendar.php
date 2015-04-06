<?php 
/* Draw the calendar */
function draw_calendar($use_cals=false, $use_cats=false) { // $use_cals being the calendar on which this event should be displayed. Passed to function via shortcode for initial page load, via AJAX POST on subsequent laods.
	function my_posts_where_date( $where ) { // custom filter to replace '=' with 'LIKE'
		$where = str_replace("meta_key = 'dates_%_date'", "meta_key LIKE 'dates_%_date'", $where);
		return $where;
	}
	function my_posts_where_time( $where ) { // custom filter to replace '=' with 'LIKE'
		$where = str_replace("meta_key = 'dates_%_time'", "meta_key LIKE 'dates_%_time'", $where);
		return $where;
	}
	add_filter('posts_where', 'my_posts_where_date');
	add_filter('posts_where', 'my_posts_where_time');

	if(isset($_POST['useCals'])) { // We're pulling data from our AJAX
		$use_cals = $_POST['useCals'];
	}
	if(isset($_POST['useCats'])) { // We're pulling data from our AJAX
		$use_cats = $_POST['useCats'];
	}

	/**
	 * Only 1 parameter should be passed to the shortcode - the name of the calendar to display.
	 * Options are main, csa, and mt.
	 */
// 	$explode_cals = explode(",", $use_cals); // Build an array from shortcode parameters
// 	echo "Explode cals: "; print_r($explode_cals);
// 	$pad_cals = array_pad($explode_cals, 3, "qqq"); // Pad the array to ensure we have exactly 3 values. Any empty values will become "qqq" which won't match anything (null was matching everything)
// 	echo "<br />Pad cals: "; print_r($pad_cals);
// 	list($l_a, $l_b, $l_c) = $pad_cals; // Break our array into variables that will match against the "OR" query comparison.
// 	echo "<br />1: $l_a, 2: $l_b, 3: $l_c";
	
	if($use_cals) { // we are displaying a program calendar
		$args = array(
			'post_type'  => 'event',
			'meta_key'   => 'dates',
			'meta_query' => array(
				array(
					'key' => 'dates_%_date',
				),
				array(
					'key' => 'dates_%_time',
				),
				array(
					'key' => 'calendar_display',
					'value' => '"' . $use_cals . '"',
					'compare' => 'LIKE'
				),
			),
			'posts_per_page' => -1,
		);
	} else { // we are displaying a category calendar
		switch($use_cats) { // Purple Pass and Live Stream are edge cases that apply accross event type so we need to query DB a bit differently for those calendars
			case "purple-pass" :
				$args = array(
					'post_type'  => 'event',
					'meta_key'   => 'dates',
					'meta_query' => array(
						array(
							'key' => 'dates_%_date',
						),
						array(
							'key' => 'dates_%_time',
						),
						array(
							'key' => 'purple_pass',
							'value' => true,
							'compare' => '='
						),
					),
					'posts_per_page' => -1,
				);
				break;
			case "live-stream" :
				$args = array(
					'post_type'  => 'event',
					'meta_key'   => 'dates',
					'meta_query' => array(
						array(
							'key' => 'dates_%_date',
						),
						array(
							'key' => 'dates_%_time',
						),
					),
					'tax_query'   => array(
						'taxonomy' => 'tours',
						'field'     => 'slug',
						'terms'    => $use_cats
					),
					'posts_per_page' => -1,
				);
				break;
			default :
				$args = array(
					'post_type'  => 'event',
					'meta_key'   => 'dates',
					'meta_query' => array(
						array(
							'key' => 'dates_%_date',
						),
						array(
							'key' => 'dates_%_time',
						),
					),
					'tax_query'   => array(
						array(
							'taxonomy' => 'tours',
							'field'     => 'slug',
							'terms'    => $use_cats
						),
					),
				'posts_per_page' => -1,
				);
		}
	}
	add_filter('posts_orderby','customorderby');
	$events_query = new WP_Query($args);
	remove_filter('posts_orderby','customorderby');
	
	
// 	echo "<pre>";
// 	print_r($events_query);
// 	echo "</pre>";
	
	if( $events_query->have_posts() ):
		$timestamp_arrays = array(); // array key is timestamp
		while ( $events_query->have_posts() ) : $events_query->the_post();
			if(have_rows('dates')) : // each event should have at least 1 date but lets check anyway
				while(have_rows('dates')) : the_row();
					$e_date = strtotime(get_sub_field('date'));
					$e_time = strtotime(get_sub_field('time',false)); // false = do not format results from DB
					$date_to_timestamp = strtotime(get_sub_field('date') . " " . get_sub_field('time', false)); // convert the individual date and time fields to a singular unix timestamp
					$timestamp_to_date = date("F j, Y, g:i a", $date_to_timestamp); // convert the timestamp back to a date and time format
					$timestamp_arrays[$e_date][$date_to_timestamp][] = get_the_ID(); // array key = timestamp, value = event ID
				endwhile;
			endif;
		endwhile; wp_reset_postdata();
	endif;
	
	// Sort the array by key (unix timestamp)
	ksort($timestamp_arrays); // sort top-level keys by date
	$event_query_ids = array(); // grab the post id's
	foreach($timestamp_arrays as $key => $value) { // foreach (array)dates as (array)times => event
		ksort($value); // sort (secondary) keys by timestamp
		foreach($value as $k => $v) { // foreach array(times) as key => event ID
			foreach($v as $event_id) { // foreach key as Event ID
				$event_query_ids[] = $event_id;
			}
		}
	}

	wp_reset_query();  // Restore global post data stomped by the_post().
    
    if(isset($_POST['getMonth']) && isset($_POST['getYear'])) {
        $year = (int)$_POST['getYear'];
        $month = (int)$_POST['getMonth'];
        $day = date('d');
    } else {
        list( $year, $month, $day ) = explode( '-', date( 'Y-m-d' ) );
    }
    /* days and weeks vars now ... */
    $running_day = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );
    $days_in_month = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
    $display_month_name = date( 'F', mktime( 0, 0, 0, $month, 10 ) );
    $display_date = strtotime( "$month/$day/$year" );
    $current_date = strtotime( date( 'Y-m-d' ) );
    $days_in_this_week = 1;
    $day_counter = 0;
    $dates_array = array();

    /* draw table */
    $calendar = '<table id="calendar_grid" cellpadding="0" cellspacing="0" class="calendar" data-usecats="' . $use_cats . '" data-usecals="' . $use_cals . '">';

    $calendar .= '
        <tr class="header">
            <td colspan="7" class="current-month" align="center" style="position: relative">
				<div id="prevMonth" class="month-nav" data-month= "' . $month . '" data-nextmonth = "'. ($month - 1) . '" data-nextyear = "2015" ><i class="fa fa-chevron-left"></i></div> 
				<div id="nextMonth" class="month-nav" data-month= "' . $month . '" data-nextmonth = "'. ($month + 1) . '" data-nextyear = "2015" ><i class="fa fa-chevron-right"></i></div>
				<div class="curMonth">' . $display_month_name . '</div>
            </td>
        </tr>';

    /* table headings */
    $headings = array('Sun', 'Mon', 'Tues', 'Wed', 'Thurs', 'Fri', 'Sat');
    $calendar .= '<tr class="calendar-row"><td class="calendar-day-head">' . implode('</td><td class="calendar-day-head">', $headings) . '</td></tr>';

    /* row for week one */
    $calendar .= '<tr class="calendar-row">';

    /* print "blank" days until the first of the current week */
    for ($x = 0; $x < $running_day; $x++) :
        $calendar .= '<td class="calendar-day-np"> </td>';
        $days_in_this_week++;
    endfor;

    /* keep going with days.... */
    for ($list_day = 1; $list_day <= $days_in_month; $list_day++) :
		/* Need to know what date the loop is on so we can compare that to todays date */
		$loop_date = strtotime("$month/$list_day/$year");
		$calendar .= ($loop_date == $current_date ? "<td class='calendar-day current-day'>" : "<td class='calendar-day'>");
        /* add in the day number */
        $calendar .= '<div class="day-number">' . $list_day . '</div>';
//         $calendar .= "<p>Calendar: $use_cals</p>";
//         $calendar .= "<p>Categroy: $use_cats</p>";

        /** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
		foreach($timestamp_arrays as $timestamp_key => $timestamp_value) {
			ksort($timestamp_value);
			// if we have an event on this day
			if($loop_date == $timestamp_key) {
				foreach($timestamp_value as $t_key => $t_val) {
					foreach($t_val as $k => $v) {
						/* get information attached to the 'tours' taxonomy (tour color) */
						$tours = get_the_terms($v, 'tours');
						if(!empty($tours)) {
							$tour = array_pop($tours);
							$tour_color = get_field('color_of_event_category', $tour);
						}
						/* get the venues attached to the post */
						$venues = get_field('venue', $v);
						if( $venues ) {
// 							foreach( $venues as $venue ) {
								$venue_name = $venues->name;
								$venue_slug = $venues->slug;
// 							}
						}
						/* get the tours attached to the post */
						$tours = get_field('tour', $v);
						if( $tours ) {
							$tour_name = $tours->name;
							$tour_slug = $tours->slug;
						}
						// If the event is on a Saturday the popover should pop left
						$saturday_event = date('D', $timestamp_key) == "Sat" ? "left" : "right";
						// Get any associated Purple Pass or Live Stream data
							$event_meta = "";
							$data_purplepass = "";
							$data_livestream = "";
						if(get_field('purple_pass', $v) && get_field('live_stream', $v)) {
							$event_meta = '<br />
								<span class="event-meta purple-pass" style="margin-top:5px; background-color: ' . $tour_color . '"><i class="fa fa-star"></i>Purple Pass</span>
								<span class="event-meta live-stream" style="margin-top:5px; background-color: ' . $tour_color . '"><i class="fa fa-video-camera"></i>Live Stream</span>';
							$data_purplepass = "Purple Pass";
							$data_livestream = "Live Stream";
						} if(get_field('purple_pass', $v) && !get_field('live_stream', $v)) {
							$event_meta = '<br /><span class="event-meta purple-pass" style="margin-top:5px; background-color: ' . $tour_color . '"><i class="fa fa-heart"></i>Purple Pass</span>';
							$data_purplepass = "Purple Pass";
						} if(get_field('live_stream', $v) && !get_field('purple_pass', $v)) {
							$event_meta = '<br /><span class="event-meta live-stream" style="margin-top:5px; background-color: ' . $tour_color . '"><i class="fa fa-video-camera"></i>Live Stream</span>';
							$data_livestream = "Live Stream";
						}
						// Build the popover
						$popover_content = "Where: $venue_name<br>When: " . date('D, M dS', $timestamp_key) . " @ " . date("g:i a", $t_key);
						if(get_field('price', $v))
						$popover_content .= "<br>Price: " . get_field('price', $v);
						$popover_content .= $event_meta;
						$popover_data = "
							<p 
								class='popoverData' 
								data-original-title='" . get_field('title', $v) . "' 
								data-content='" . $popover_content . "' 
								data-placement='{$saturday_event}' 
								data-venue='$venue_slug'
								data-tour='$tour_slug'
								data-purplepass='$data_purplepass'
								data-livestream='$data_livestream'
								data-trigger='hover' 
								style='color:$tour_color'
								data-template='
									<div class=\"popover\" style=\"border-color:$tour_color\">
										<div class=\"arrow\" style=\"border-left-color:$tour_color;border-right-color:$tour_color\"></div>
										<div class=\"popover-inner\">
											<h3 class=\"popover-title\"></h3>
											<div class=\"popover-content cf\">
												<p></p>
											</div>
										</div>
									</div>' 
								rel='popover' 
							>
								<a style='color:$tour_color' href='" . get_permalink($v) . "'>" . get_the_title($v) . "</a>
							</p>";
						$calendar .= $popover_data;
					}
				}
			}
		}
			
// 		foreach($timestamp_arrays as $timestamp_key => $timestamp_value) {
// // 			echo "UNIX Date = $timestamp_key <br />";
// 			ksort($timestamp_value);
// 			echo "Shows On " . date("F j, Y", $timestamp_key) . ":<br />";
// 			foreach($timestamp_value as $t_key => $t_val) {
// 				echo " -- At " . date("g:i a", $t_key) . ":<br /> ";
// 				foreach($t_val as $k => $v) { 
// 					echo "----" . get_the_title($v) . "<br />";
// 				}
// 			}
// 		}
		
        $calendar .= '</td>';
        if ($running_day == 6) :
            $calendar .= '</tr>';
            if (($day_counter + 1) != $days_in_month) :
                $calendar .= '<tr class="calendar-row">';
            endif;
            $running_day = -1;
            $days_in_this_week = 0;
        endif;
        $days_in_this_week++;
        $running_day++;
        $day_counter++;

    endfor;

    /* finish the rest of the days in the week */
    if ($days_in_this_week < 8) :
        for ($x = 1; $x <= (8 - $days_in_this_week); $x++) :
            $calendar .= '<td class="calendar-day-np"> </td>';
        endfor;
    endif;

    $calendar .= '</tr>';
    
    /* final row */
    $calendar .= '
        <tr class="header">
            <td colspan="7" class="current-month" align="center" style="position: relative">
				<div id="prevMonth" class="month-nav" data-month= "' . $month . '" data-nextmonth = "'. ($month - 1) . '" data-nextyear = "2015" ><i class="fa fa-chevron-left"></i></div> 
				<div id="nextMonth" class="month-nav" data-month= "' . $month . '" data-nextmonth = "'. ($month + 1) . '" data-nextyear = "2015" ><i class="fa fa-chevron-right"></i></div>
				<div class="curMonth">' . $display_month_name . '</div>
            </td>
        </tr>';

    /* end the table */
    $calendar .= '</table>';


    /** Create the list view for the calendar **/
    
	$calendar .= "<div id='calendar_list' class='calendar-list-wrap' style='display:none'>";
	$have_events = false;
	$calendar .= "<h1>Events in $display_month_name, $year</h1>";
	
	$running_day = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );
    $days_in_month = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
    $display_month_name = date( 'F', mktime( 0, 0, 0, $month, 10 ) );
    $display_date = strtotime( "$month/$day/$year" );
    $current_date = strtotime( date( 'Y-m-d' ) );
    $days_in_this_week = 1;
    $day_counter = 0;
    $dates_array = array();
    
    /* print "blank" days until the first of the current week */
    for ($x = 0; $x < $running_day; $x++) :
        $days_in_this_week++;
    endfor;
    
    /* keep going with days.... */
    for ($list_day = 1; $list_day <= $days_in_month; $list_day++) :
		/* Need to know what date the loop is on so we can compare that to todays date */
		$loop_date = strtotime("$month/$list_day/$year");

        /** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
        foreach($timestamp_arrays as $timestamp_key => $timestamp_value) {
			ksort($timestamp_value);
			// if we have an event on this day
			if($loop_date == $timestamp_key) {
				foreach($timestamp_value as $t_key => $t_val) {
					foreach($t_val as $k => $v) {
						$calendar .= "<div class='cal-list-item' data-month='$display_month_name' data-year='$year'>";
					
						$cal_id = get_the_ID($v);
						$cal_link = get_permalink($v);
						$cal_title = get_the_title($v);
						$cal_date = date('D, M dS', $timestamp_key) . " @ " . date("g:i a", $t_key);
						$cal_price = get_field('price', $v);
						$cal_thumb = wp_get_attachment_image(get_field('image', $v), array(250,250));
						$cal_desc = get_field('description', $v);
						$event_extras = "";
						if(get_field('purple_pass', $v) || get_field('live_stream', $v)) :
							$event_extras .= "<div class='event-extras'>";
						endif;
						if(get_field('purple_pass', $v)) :
							$event_extras .= "<h4 class='event-extra purple-pass'><a class='cta cta-main'>Purple Pass</a></h4>";
						endif;
						if(get_field('live_stream', $v)) :
							$event_extras .= "<h4 class='event-extra live-stream'><a class='cta cta-main'>Live Stream</a></h4>";
						endif;
						if(get_field('purple_pass', $v) || get_field('live_stream', $v)) :
							$event_extras .= "</div>";
						endif;
						
						if(get_field('image', $v)) : // card with a thumbnail
							$calendar .= <<<EOT
							<div id="post-$cal_id" class="event">
								<div class="feature-image-left card">
									<h2><a href="$cal_link" title="$cal_title">$cal_title</a></h2>
									$event_extras
									<h4 class="event-meta">$cal_date &mdash; $venue_name</h4>
									<div class="event-date-details">
										<p>Where: $venue_name</p>
										<p>Price: $cal_price</p>
									</div>
									<div class="excerpt-thumbnail small">
										$cal_thumb
									</div>
									<div class="excerpt-content">
										$cal_desc
									</div>
									<footer class="excerpt-footer cf">
										<a class="cta cta-secondary" href="$cal_link">Event Details</a>
									</footer>
								</div>
							</div>
EOT;
						else : // no thumbnail
							$calendar .= <<<EOD
							<div id="post-$cal_id" class="event">
								<div class="card">
									<h2><a href="$cal_link" title="$cal_title">$cal_title</a></h2>
									$event_extras
									<h4 class="event-meta">$cal_date &mdash; $venue_name</h4>
									<div class="event-date-details">
										<p>Where: $venue_name</p>
										<p>Price: $cal_price</p>
									</div>
									<div class="excerpt-content">
										$cal_desc
									</div>
									<footer class="excerpt-footer cf">
										<a class="cta cta-secondary" href="$cal_link">Event Details</a>
									</footer>
								</div>
							</div>
EOD;
						endif;
						
						$calendar .= "</div>";
					}
				}
			}
		}
        
        if ($running_day == 6) :
            $calendar .= '</tr>';
            if (($day_counter + 1) != $days_in_month) :
                $calendar .= '<tr class="calendar-row">';
            endif;
            $running_day = -1;
            $days_in_this_week = 0;
        endif;
        $days_in_this_week++;
        $running_day++;
        $day_counter++;
        
	endfor;

    $calendar .= "</div>"; // end calendar-list-wrap


    // if we are initializing the calendar on page load, return our value
    if(!isset($_POST['getMonth']) && !isset($_POST['getYear'])) {
        return $calendar;
    } else {
        // we're returning the calendar after our ajax call, so it's gotta die()
        die($calendar);
    }
}
// creating Ajax call for WordPress
add_action( 'wp_ajax_nopriv_draw_calendar', 'draw_calendar' );
add_action( 'wp_ajax_draw_calendar', 'draw_calendar' );
?>
