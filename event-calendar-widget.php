<?php
/**
 * Register all of the default WordPress widgets on startup.
 *
 * Calls 'widgets_init' action after all of the WordPress widgets have been
 * registered.
 *
 * @since 2.2.0
 */
function event_calendar_widget_init() {
	register_widget('WP_Event_Widget_Calendar');
	
}	
add_action('widgets_init', 'event_calendar_widget_init');

/**
 * Calendar widget class
 *
 * @since 2.8.0
 */	
class WP_Event_Widget_Calendar extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_event_calendar widget_calendar', 'description' => __( 'A calendar of your site&#8217;s events') );
		parent::__construct('event-calendar', __('Event Calendar', 'bp-checkins'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div id="calendar_wrap">';
		get_event_calendar();
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = strip_tags($instance['title']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
<?php
	}
}

/**
 * Display calendar with days that have posts as links.
 *
 * The calendar is cached, which will be retrieved, if it exists. If there are
 * no posts for the month, then it will not be displayed.
 *
 * @since 1.0.0
 * @uses calendar_week_mod()
 *
 * @param bool $initial Optional, default is true. Use initial calendar names.
 * @param bool $echo Optional, default is true. Set to false for return.
 * @return string|null String when retrieving, null when displaying.
 */
function get_event_calendar($initial = true, $echo = true) {
	global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

	$cache = array();
	$key = md5( $m . $monthnum . $year );
	if ( $cache = wp_cache_get( 'get_event_calendar', 'event-calendar' ) ) {
		if ( is_array($cache) && isset( $cache[ $key ] ) ) {
			if ( $echo ) {
				echo apply_filters( 'get_event_calendar',  $cache[$key] );
				return;
			} else {
				return apply_filters( 'get_event_calendar',  $cache[$key] );
			}
		}
	}

	if ( !is_array($cache) )
		$cache = array();

	// Quick check. If we have no posts at all, abort!
	if ( !$posts ) {
		$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
		if ( !$gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_event_calendar', $cache, 'event-calendar' );
			return;
		}
	}

	if ( isset($_GET['w']) )
		$w = ''.intval($_GET['w']);

	// week_begins = 0 stands for Sunday
	$week_begins = intval(get_option('start_of_week'));

	// Let's figure out when we are
	if ( !empty($monthnum) && !empty($year) ) {
		$thismonth = ''.zeroise(intval($monthnum), 2);
		$thisyear = ''.intval($year);
	} elseif ( !empty($w) ) {
		// We need to get the month from MySQL
		$thisyear = ''.intval(substr($m, 0, 4));
		$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
	} elseif ( !empty($m) ) {
		$thisyear = ''.intval(substr($m, 0, 4));
		if ( strlen($m) < 6 )
				$thismonth = '01';
		else
				$thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
	} else {
		$thisyear = gmdate('Y', current_time('timestamp'));
		$thismonth = gmdate('m', current_time('timestamp'));
	}

	$unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
	$last_day = date('t', $unixmonth);
	
	$firstofmonth = $thisyear.'-'.$thismonth.'-01';
	$lastofmonth = $thisyear.'-'.$thismonth.'-'.$last_day.' 23:59:59';

	// Get the next and previous month and year with at least one post
	/*
	$previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date < '$thisyear-$thismonth-01'
		AND post_type = 'post' AND post_status = 'publish'
			ORDER BY post_date DESC
			LIMIT 1");
	$next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
		AND post_type = 'post' AND post_status = 'publish'
			ORDER BY post_date ASC
			LIMIT 1");
	*/
	$previous = $wpdb->get_row("SELECT MONTH(CAST(meta2.meta_value AS DATE)) AS month, YEAR(CAST(meta2.meta_value AS DATE)) AS year
		FROM $wpdb->posts post
		INNER JOIN $wpdb->postmeta meta1 ON (post.ID = meta1.post_id AND meta1.meta_key = 'bpci_places_is_live' AND meta1.meta_value = 'live')
		INNER JOIN $wpdb->postmeta meta2 ON (post.ID = meta2.post_id AND meta2.meta_key = 'bpci_places_live_start')
		WHERE CAST(meta2.meta_value AS DATE) < '$thisyear-$thismonth-01'
		AND post.post_type = 'places' AND post.post_status = 'publish'
			ORDER BY meta2.meta_value DESC
			LIMIT 1");
	$next = $wpdb->get_row("SELECT MONTH(CAST(meta3.meta_value AS DATE)) AS month, YEAR(CAST(meta3.meta_value AS DATE)) AS year
		FROM $wpdb->posts post
		INNER JOIN $wpdb->postmeta meta1 ON (post.ID = meta1.post_id AND meta1.meta_key = 'bpci_places_is_live' AND meta1.meta_value = 'live')
		INNER JOIN $wpdb->postmeta meta3 ON (post.ID = meta3.post_id AND meta3.meta_key = 'bpci_places_live_end')
		WHERE CAST(meta3.meta_value AS DATE) > '$thisyear-$thismonth-{$last_day} 23:59:59'
		AND post.post_type = 'places' AND post.post_status = 'publish'
			ORDER BY meta3.meta_value ASC
			LIMIT 1");

	/* translators: Calendar caption: 1: month name, 2: 4-digit year */
	$calendar_caption = _x('%1$s %2$s', 'calendar caption');
	$calendar_output = '<table id="wp-calendar">
	<caption>' . sprintf($calendar_caption, $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
	}

	foreach ( $myweek as $wd ) {
		$day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
		$wd = esc_attr($wd);
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';

	if ( $previous ) {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . get_month_link($previous->year, $previous->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year)))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>';
	} else {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
	}

	$calendar_output .= "\n\t\t".'<td class="pad">&nbsp;</td>';

	if ( $next ) {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . get_month_link($next->year, $next->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month), date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) ) . '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($next->month)) . ' &raquo;</a></td>';
	} else {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
	}

	$calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';

	// Get days with posts
	/*
	$dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
		FROM $wpdb->posts WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
		AND post_type = 'post' AND post_status = 'publish'
		AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'", ARRAY_N);
	*/
	$dayswithposts = $wpdb->get_results("SELECT post.ID AS id, post.post_title AS title, meta2.meta_value AS start, meta3.meta_value AS end 
		FROM $wpdb->posts post 
		INNER JOIN $wpdb->postmeta meta1 ON (post.ID = meta1.post_id AND meta1.meta_key = 'bpci_places_is_live' AND meta1.meta_value = 'live')
		INNER JOIN $wpdb->postmeta meta2 ON (post.ID = meta2.post_id AND meta2.meta_key = 'bpci_places_live_start')
		INNER JOIN $wpdb->postmeta meta3 ON (post.ID = meta3.post_id AND meta3.meta_key = 'bpci_places_live_end')
		WHERE post.post_type = 'places' AND post.post_status = 'publish' AND CAST(meta2.meta_value AS DATE) <= '{$lastofmonth}' AND CAST(meta3.meta_value AS DATE) >= '{$firstofmonth}'
		ORDER BY meta2.meta_value DESC", ARRAY_A);
	if ( $dayswithposts ) {
		$daywithpost = array();
		$calendardays = array();
		foreach ( (array) $dayswithposts as $daywith ) {
			$start_date = strtotime($daywith['start']);
			$end_date = strtotime($daywith['end']);
			//echo $start_date.' '.$end_date;
			//echo $firstofmonth.' - '.$lastofmonth;
			//print_r($daywith);
			while ( $start_date + 86400 < $end_date ) {
				if( $start_date>=strtotime($firstofmonth) &&  $start_date<=strtotime($lastofmonth) ) {
					//if(!in_array(date('Y-m-d H:i:s', $start_date),$daywithpost))
						$thisday = date('Y-m-d H:i:s', $start_date);
						//$calendays[$thisday] = array( 'id' => $daywith['id'], 'title' => $daywith['title']);
						//$calendardays[] = array( 'ID' => $daywith['id'], 'post_title' => $daywith['title'], 'dom' => $thisday );
						$calendardays[] = array( 'ID' => $daywith['id'], 'post_title' => $daywith['title'], 'dom' => date('d', $start_date) );
						$daywithpost[] = date('d', $start_date);
				}
				$start_date += 86400; // full day
			}
			$daywithpost = array_unique($daywithpost);
		}
	} else {
		$daywithpost = array();
	}

	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
		$ak_title_separator = "\n";
	else
		$ak_title_separator = ', ';

	$ak_titles_for_day = array();
	/*
	$ak_post_titles = $wpdb->get_results("SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
		."FROM $wpdb->posts "
		."WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00' "
		."AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' "
		."AND post_type = 'places' AND post_status = 'publish'"
	);
	echo '<pre>caldays';
	print_r($daywithpost);
	print_r($calendardays);
	echo '</pre>';
	*/
	if ( !empty($calendardays) )
		foreach ( $calendardays as $calendarday ) {
			$ak_post_titles[] = (object)$calendarday;
		}	
	if ( $ak_post_titles ) {

		foreach ( (array) $ak_post_titles as $ak_post_title ) {

				/** This filter is documented in wp-includes/post-template.php */
				$post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );

				if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
					$ak_titles_for_day['day_'.$ak_post_title->dom] = '';
				if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
					$ak_titles_for_day["$ak_post_title->dom"] = $post_title;
				else
					$ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
		}
	}
	/*
	echo '<pre>';
	//print_r($ak_post_titles);
	print_r($ak_titles_for_day);
	echo '</pre>';
	*/
	// See how much we should pad in the beginning
	$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
	if ( 0 != $pad )
		$calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';

	$daysinmonth = intval(date('t', $unixmonth));
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset($newrow) && $newrow )
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		$newrow = false;

		if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) )
			$calendar_output .= '<td id="today">';
		else
			$calendar_output .= '<td>';

		if ( in_array($day, $daywithpost) ) // any posts today?
				$calendar_output .= '<a href="' . events_day_link( $thisyear, $thismonth, $day ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
		else
			$calendar_output .= $day;
		$calendar_output .= '</td>';

		if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
			$newrow = true;
	}

	$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
	if ( $pad != 0 && $pad != 7 )
		$calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';

	$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_event_calendar', $cache, 'event-calendar' );

	if ( $echo )
		echo apply_filters( 'get_event_calendar',  $calendar_output );
	else
		return apply_filters( 'get_event_calendar',  $calendar_output );

}
function events_day_link($year, $month, $day, $post_type = 'events') {
	global $wp_rewrite;	
	$daylink = $wp_rewrite->get_day_permastruct();
	$post_type_obj = get_post_type_object( $post_type );
	$post_type_slug = $post_type_obj->rewrite['slug'];
	//echo '<pre>p'.$post_type.$post_type_slug.'</pre>';
	if ( !empty($daylink) ) {
		$daylink = str_replace('%year%', $year, $daylink);
		$daylink = str_replace('%monthnum%', zeroise(intval($month), 2), $daylink);
		$daylink = str_replace('%day%', zeroise(intval($day), 2), $daylink);
		return apply_filters('day_link', home_url( $post_type_slug.user_trailingslashit($daylink, 'day') ), $year, $month, $day);
	} else {
		return apply_filters('day_link', home_url( '?post_type='.$post_type.'&m=' . $year . zeroise($month, 2) . zeroise($day, 2) ), $year, $month, $day);
	}
}
//add_filter( 'day_link', 'places_day_link', 10, 3 );
?>