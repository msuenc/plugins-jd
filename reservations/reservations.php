<?php
/*
Plugin Name: Reservations
Description: This is a plugin.
Author: Joe Doe
Version: 1.0.1
*/

// Create the database

function reservation_database() {
	global $wpdb;

	$posts = $wpdb->prefix . 'posts';
	$reservations = $wpdb->prefix . 'reservations';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $reservations (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		first_name varchar(55) NOT NULL,
		last_name varchar(55) NOT NULL,
		phone VARCHAR(20) NOT NULL,
		post_id mediumint(9) NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	add_option('reservation_db_version', '1.0');
}

register_activation_hook(__FILE__, 'reservation_database');

// Add plugin to admin

function add_plugin_to_admin() {
	function reservation_content() {
		echo "<h1>Events</h1>";
		echo "<div style='margin-right:20px'>";

		if(class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
			require_once(plugin_dir_path( __FILE__ ) . 'reservation-list-table.php');
			$reservationListTable = new ReservationListTable();
			$reservationListTable->prepare_items();
			$reservationListTable->display();
		} else {
			echo "WP_List_Table n'est pas disponible.";
		}
		
		echo "</div>";
	}

	add_menu_page('Reservations', 'Reservations', 'manage_options', 'reservation-plugin', 'reservation_content');
}

add_action('admin_menu', 'add_plugin_to_admin');

// Create the form

function show_reservation_form() {
	ob_start();

	if (isset($_POST['reservation'])) {
		$first_name = sanitize_text_field($_POST["first_name"]);
		$last_name = sanitize_text_field($_POST["last_name"]);
		$phone = sanitize_text_field($_POST["phone"]);
		$event_id = $_POST["event_id"];

		if ($first_name != '' && $last_name != '' && $phone  != '') {
			global $wpdb;

			$table_name = $wpdb->prefix . '`reservations`';
	
			$wpdb->insert( 
				$table_name,
				array( 
					'first_name' => $first_name,
					'last_name' => $last_name,
					'phone' => $phone,
					'event_id' => $event_id,
				) 
			);

			echo "<h4>Merci! Nous vous re-reservationerons dès que possible.</h4>";
		}
	}

	echo "<form method='POST'>";
	// Start event
	echo "<input type='hidden' name='event_id' value='" . get_the_ID() . "'>";
	// End event
	echo "<input type='text' name='first_name' placeholder='Prénom' style='width:100%' required>";
	echo "<input type='text' name='last_name' placeholder='Nom de famille' style='width:100%' required>";
	echo "<input type='tel' name='phone' placeholder='Numéro de téléphone' style='width:100%' required>";
	echo "<input type='submit' name='reservation' value='Envoyez'>";
	echo "</form>";

	return ob_get_clean();
}

add_shortcode('show_reservation_form', 'show_reservation_form');

// Add post type 'events'

function events_init() {
	$args = array(
		'labels' => array(
			'name' => __('Events'),
			'singular_name' => __('Event'),
		),
		'public' => true,
		'has_archive' => true,
		'show_in_rest' => true,
		'rewrite' => array("slug" => "events"), 
		'supports' => array('thumbnail', 'editor', 'title')
	);

	register_post_type('events', $args);
}

add_action('init', 'events_init');

// Add meta box date to event

function add_event_date_meta_box() {
	function event_date($post) {
		$date = get_post_meta($post->ID, 'event_date', true);
	
		if (empty($date)) $date = the_date();
	
		echo '<input type="date" name="event_date" value="' . $date  . '" />';
	}

	add_meta_box('event_date_meta_boxes', 'Date', 'event_date', 'events', 'side', 'default');
}

add_action('add_meta_boxes', 'add_event_date_meta_box');

// Update meta on event post save

function events_post_save_meta($post_id) {
    if(isset($_POST['event_date']) && $_POST['event_date'] !== "") {
		update_post_meta($post_id, 'event_date', $_POST['event_date']);
	}
}

add_action('save_post', 'events_post_save_meta');

// Add event post type to home and main query

function add_event_post_type($query) {
	if (is_home() && $query->is_main_query()) {
		$query->set('post_type', array('post', 'events'));
		return $query;
	}
}

add_action('pre_get_posts', 'add_event_post_type');

// Short code to display event date meta data

function show_event_date() {
	ob_start();
	$date = get_post_meta(get_the_ID(), 'event_date', true);
	echo "<date>$date</date>";
	return ob_get_clean();
}

add_shortcode('show_event_date', 'show_event_date');