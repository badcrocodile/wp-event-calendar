<?php

function calendar_header($use_cals=false, $use_cats=false) {

	if($use_cats) {
		return; // no header for category display
	} else {
	
		$taxonomies = array(
			'tours'
		);
		$args = array(
			'orderby' => 'name',
			'child_of' => 27
		);
		$terms = get_terms($taxonomies, $args);
// 		echo "<pre>";
// 		print_r($terms);
// 		echo "</pre>";
	
		$cheader = '<div class="calendar-nav form-inline cf">';
			$cheader .= '<div class="select-wrap form-group calendar-control">';
				$cheader .=	'<h3>Venue</h3>';
				/**
				* We only want the child venues of the current calendar type (main, csa, mt)
                * Remove $get_venue_term_ID and $venue_term_ID and 'parent' if we don't need to support multiple calendars
				*/
				$get_venue_term_ID = get_term_by('slug',$use_cals,'venues'); // Shortcode parameter is taxonomy-slug based so find its ID by slug with get_term_by 
				$venue_term_ID = $get_venue_term_ID->term_id; // the tax ID
				$args = array(
					'hide_empty' => true,
					'parent'     => $venue_term_ID,
				);
				$venues = get_terms('venues', $args);
				$cheader .= '<select name="venue-select" class="venue-select form-control">';
					$cheader .= '<option value="all-venues">All Venues</option>';
					foreach($venues as $venue) :
						$cheader .= "<option value='{$venue->slug}'>{$venue->name}</option>";
					endforeach;
				$cheader .= '</select>';
			$cheader .= '</div>';
			
			$cheader .= '<div class="select-wrap form-group calendar-control">';
				$cheader .= '<h3>Category</h3>';
				/**
				* We only want the child tours of the current calendar type (main, csa, mt)
                * Remove $get_venue_term_ID and $venue_term_ID and 'parent' if we don't need to support multiple calendars
				*/
				$get_tour_term_ID = get_term_by('slug',$use_cals,'tours'); // Shortcode parameter is taxonomy-slug based so find its ID by slug with get_term_by 
				$tour_term_ID = $get_tour_term_ID->term_id; // the tax ID
				$args = array(
					'hide_empty' => true,
					'parent'     => $tour_term_ID,
				);
				$terms = get_terms('tours', $args); // get child categories of XXX
// 				var_dump($terms);
				$cheader .= '<select name="tour-select" class="tour-select form-control">';
					$cheader .= '<option value="all-categories">All Categories</option>';
					foreach($terms as $term) :
						$cheader .= "<option value='{$term->slug}'>{$term->name}</option>";
					endforeach;
				$cheader .= '</select>';
			$cheader .= '</div>';
			
			$cheader .= '<div class="search-wrap form-group calendar-control">';
				$cheader .= '<h3>Search for an Event</h3>';
// 				$cheader .= '<input name="search" class="event-search form-control" type="text" placeholder="Search Events..." />';
				$onsubmit = "location.href=this.action+'search/'+encodeURIComponent(this.s.value).replace(/%20/g, '+'); return false;";
				$action = home_url( '/' );
				$cheader .= "
					<form role='search' method='get' id='searchform' action='$action'>
						<input type='text' value='' name='s' id='s' class='event-search form-control' type='text' placeholder='Search Events...' />
						<input type='hidden' name='post_type' value='events' />
					</form>";
			$cheader .= '</div>';
			
			$month = isset($_POST['getMonth']) ? sanitize_text_field($_POST['getMonth']) : date("m"); // current month unless set by POST
			$year = isset($_POST['getYear']) ? sanitize_text_field($_POST['getYear']) : date("Y"); // current year unless set by POST
			$months_in_year = array(1=>"January", 2=>"February", 3=>"March", 4=>"April", 5=>"May", 6=>"June", 7=>"July", 8=>"August", 9=>"September", 10=>"October", 11=>"November", 12=>"December");

			$cheader .= '<div class="select-wrap form-group list-control" style="display:none;">';
				$cheader .= '<h3>Month</h3>';
				$cheader .= '<select name="month-select" id="month_select" class="month-select form-control">';
					foreach($months_in_year as $k=>$m) {
						if($month == $k) {
							$cheader .= "<option selected='selected' value='{$k}'>$m</option>";
						} else {
							$cheader .= "<option value='{$k}'>$m</option>";
						}
					}
				$cheader .= '</select>';
			$cheader .= '</div>';
			
			$cheader .= '<div class="select-wrap form-group list-control" style="display:none;">';
				$cheader .= '<h3>Year</h3>';
				$cheader .= "<select name='year-select' id='year_select' class='year-select form-control'>";
					$y = ($year - 2);
					while($y <= ($year + 2)) {
						if($y == $year) {
							$cheader .= "<option selected='selected' value='$y'>$y</option>";
						} else { // Use the current year as selected
							$cheader .= "<option value='$y'>$y</option>";
						}
						$y++;
					}
				$cheader .= '</select>';
			$cheader .= '</div>';
			
			$cheader .= '<div class="search-wrap form-group list-control" style="display:none;">';
				$cheader .= '<h3>Search for an Event</h3>';
				$cheader .= '<input name="search" class="event-search form-control" type="text" placeholder="Search Events..." />';
			$cheader .= '</div>';

			$cheader .= '<div class="display-toggle right">';
				$cheader .= '<a id="toggle_grid" href="#" title="Calendar View"><span class="toggle grid-view"><i class="fa fa-th fa-3x"></i></span></a>';
				$cheader .= '<a id="toggle_list" href="#" title="List View"><span class="toggle list-view"><i class="fa fa-th-list fa-3x"></i></span></a>';
			$cheader .= '</div>';
			
		$cheader .= '</div>';
		
		$cheader .= '<div class="category-legend cf">';
			foreach($terms as $term) {
				$tour_color = get_field('color_of_event_category', $term);
				$cheader .= "
					<div class='legend-item'>
						<span class='legend-color' style='background-color:$tour_color'></span>
						<span class='legend-cat'>{$term->name}</span>
					</div>
				";
			}
		$cheader .= '</div>';

		return $cheader;
		
	}
	
}
