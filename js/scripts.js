(function($) {
	$(document).ready(function() {
		selectTour();
		selectVenue();
		selectMonth();
		calendarDisplayToggle();
		eventListDetailsToggle();
        initializePopOver();
		
		var domain = document.domain;
		var saosPluginDir = saosEventCalendar.plugin_dir + "/saos-event-calendar/";
		var saosPathToAjax = saosEventCalendar.site_url + "/wp-admin/admin-ajax.php";

        function initializePopOver() {
            $('.popoverData').popover({
                html : true
            });
        }
		
		function eventListDetailsToggle() {
			$(".event-date").hover(function() {
				$(this).next(".event-date-details").stop(true, false).fadeToggle('fast');
			});
		}
		
		function calendarDisplayToggle() {
			$("body").on('click', '#toggle_list', function(e) {
				e.preventDefault();
				$("#calendar_grid, .calendar-control, .category-legend").fadeOut(function() {
					$("#calendar_list, .list-control").fadeIn();
				});            
			});
			$("body").on('click', '#toggle_grid', function(e) {
				e.preventDefault();
				$("#calendar_list, .list-control").fadeOut(function() {
					$("#calendar_grid, .calendar-control, .category-legend").fadeIn();
				});            
			});
		}
		
		function selectVenue() {
			// Updates the events displayed on the calendar according to Venue selected
			$(".venue-select").change(function() {
				var venue = $(".venue-select").val();
				if(venue != "all-venues") {
					$("p.popoverData").each(function() {
						if($(this).data("venue") == venue) {
							$(this).fadeIn("fast");
						}
						if($(this).data("venue") != venue) {
							$(this).fadeOut("fast");
						}
					});
				}
				if(venue == "all-venues") {
					$("p.popoverData").each(function() {
						$(this).fadeIn("fast");
					});
				}
			});
		}
		
		function selectTour() {
			/* 
			 * Here's what I need to to do:
			 * 1: Get rid of data-purplepass and data-livestream in functions.php in favor of 1 field: data-eventmeta. It's  messing up our show/hide because both can be true.
			 * 2: Change all .tour-select option values to the taxonomy slug, not full name
			 * 3: 
			 */
			
			// Updates the events displayed on the calendar according to Tour selected
			$(".tour-select").change(function() {
				var tour = $(".tour-select").val();
				console.log(tour + " was selected");
				switch(tour) {
					case 'all-categories' :
						console.log("All categories");
						$("p.popoverData").each(function() {
							$(this).fadeIn("fast");
						});
						break;
					case 'purple-pass' :
						$("p.popoverData").each(function() {
							if($(this).data("purplepass") == tour) {
								$(this).fadeIn("fast");
							}
							if($(this).data("purplepass") != tour) {
								$(this).fadeOut("fast");
							}
						});
						break;
					case 'live-stream' :
						$("p.popoverData").each(function() {
							if($(this).data("livestream") == tour) {
								$(this).fadeIn("fast");
							}
							if($(this).data("livestream") != tour) {
								$(this).fadeOut("fast");
							}
						});
						break;
					default :
						$("p.popoverData").each(function() {
							if($(this).data("tour") == tour) {
								$(this).fadeIn("fast");
							}
							if($(this).data("tour") != tour) {
								$(this).fadeOut("fast");
							}
						});
						
				}
			});
		}
		
		// Maintain show/hide venue status on month change
		function filterVenues() {
			var venue = $(".venue-select").val();
			if(venue != "all-venues") {
				// 			if($(".venue-select").val() == venue ) {
				$("p.popoverData").each(function() {
					if($(this).data("venue") == venue) {
						$(this).show();
					}
					if($(this).data("venue") != venue) {
						$(this).hide();
					}
				});
				// 			}
			}
		}
		
		// Maintain tour show/hide status on month change
		function filterTours() {
			var tour = $(".tour-select").val();
			if(tour != "all-categories") {
				// 			if($(".tour-select").val() == tour ) {
				$("p.popoverData").each(function() {
					if($(this).data("tour") == tour) {
						$(this).show();
					}
					if($(this).data("tour") != tour) {
						$(this).hide();
					}
				});
				// 			}
			}
		}
		
		function selectMonth() {
			$("#month_select").change(function() {
				var selectedMonth = $("#month_select").val();
				$(".cal-list-item").each(function() {
					if($(this).data("month") == selectedMonth) {
						$(this).fadeIn("fast");
					}
					else if($(this).data("month") != selectedMonth) {
						$(this).fadeOut("fast");
					}
				});
			});
		}
		
		var getYear = $("#nextMonth").data("nextyear");
		
		// Get the next month for the calendar
		$('body').on('click', '#nextMonth', function() {
			var nextMonth = parseInt($(this).data("month")) +1;
			var useCals = $('#calendar_grid').data('usecals');
			var useCats = $('#calendar_grid').data('usecats');
			console.log("Use cals: " + useCals);
			console.log("Use cats: " + useCats);
			$.ajax( {
				type: 'POST',
				url: saosPathToAjax,
				data: {
					action: 'draw_calendar',
					getMonth: nextMonth,
					getYear: getYear,
					useCals: useCals,
					useCats: useCats
				},
				beforeSend: function() {
					$(".curMonth").text("").append('<img src="' + saosPluginDir + 'images/ajax-loader.gif" height="23" width="auto" />');
				},
				success: function(data, textStatus, XMLHttpRequest) {
					$("#calendar").html('').append(data);
					// Update the list view date select field with our new month/getYear
					$("#month_select option").each(function() { // loop through the options
						if($(this).attr("selected") == "selected") { // find the currently selected one
							$(this).removeAttr("selected"); // remove the selected attr
						}
						if($(this).attr("value") == nextMonth) { // find the month that should be selected
							$(this).attr("selected","selected"); // select it
						}
					});
					$('.popoverData').popover();
					if(useCals != "") {
						console.log("Not undefined");
						console.log("Yo: " + useCals);
						filterVenues();
						filterTours();
					}
					eventListDetailsToggle();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert(errorThrown);
				},
				complete: function() {
					console.log("Ajax Complete");
				}
			} );
		} );
		
		// Get the previous month for the calendar
		$('body').on('click', '#prevMonth', function() {
			var prevMonth = parseInt($(this).data("month")) -1;
			var useCals = $('#calendar_grid').data('usecals');
			var useCats = $('#calendar_grid').data('usecats');
			$.ajax({
				type: 'POST',
				url: saosPathToAjax,
				data: {
					action: 'draw_calendar',
					getMonth: prevMonth,
					getYear: getYear,
					useCals: useCals,
					useCats: useCats
				},
				beforeSend: function() {
					$(".curMonth").text("").append('<img src="' + saosPluginDir + 'images/ajax-loader.gif" height="23" width="auto" />');
				},
				success: function(data, textStatus, XMLHttpRequest) {
					$("#calendar").html('').append(data);
					// Update the list view date select field with our new month/getYear
					$("#month_select option").each(function() { // loop through the options
						if($(this).attr("selected") == "selected") { // find the currently selected one
							$(this).removeAttr("selected"); // remove the selected attr
						}
						if($(this).attr("value") == prevMonth) { // find the month that should be selected
							$(this).attr("selected","selected"); // select it
						}
					});
					$('.popoverData').popover();
					if(useCals != "") {
						console.log("Not undefined");
						console.log("Yo: " + useCals);
						filterVenues();
						filterTours();
					}
					eventListDetailsToggle();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert(errorThrown);
				},
				complete: function() {
					console.log("Ajax Complete");
				}
			});
		});
		
		// Get the month selected in the dropdown for list view
		$("body").on('change', '#month_select', function() {
			var getMonth = $("#month_select").val();
			var useCals = $('#calendar_grid').data('usecals');
			$.ajax({
				type: 'POST',
				url: saosPathToAjax,
				data: {
					action: 'draw_calendar',
					getMonth: getMonth,
					getYear: getYear,
					useCals: useCals
				},
				beforeSend: function() {
					$("#calendar_list").append("<div class='gif-load'><img src='" + saosPluginDir + "images/gif-load.gif' height='auto' width='auto' /></div>");
				},
				success: function(data, textStatus, XMLHttpRequest) {
					console.log("Success!");
					$("#calendar").html('').append(data);
					$("#calendar_grid").hide();
					$("#calendar_list").show();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert(errorThrown);
				},
				complete: function() {
					console.log("Ajax Complete");
					eventListDetailsToggle();
				}
			});
		});
		
	});
	
	
})(jQuery);
