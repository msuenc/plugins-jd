<?php
/*
Plugin Name: Contacts
Description: This is a plugin.
Author: Joe Doe
Version: 1.0.0
*/

// First step: Create the database

function contact_database() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'contacts';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		first_name varchar(55) NOT NULL,
		last_name varchar(55) NOT NULL,
		phone VARCHAR(20) NOT NULL,
		comment text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	add_option('contact_db_version', '1.0');
}

register_activation_hook(__FILE__, 'contact_database');

// Second step: Create default data

function contact_default_data() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'contacts';
	
	$wpdb->insert( 
		$table_name,
		array( 
			'first_name' => 'Joe',
			'last_name' => 'Doe',
			'phone' => '988888',
			'comment' => 'lol',
		) 
	);
}

register_activation_hook(__FILE__, 'contact_default_data');

// Third step: Add plugin to admin

function add_plugin_to_admin() {
	function contact_content() {
		echo "<h1>Contacts</h1>";
		echo "<div style='margin-right:20px'>";

		if(class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
			require_once(plugin_dir_path( __FILE__ ) . 'contact-list-table.php');
			$contactListTable = new ContactListTable();
			$contactListTable->prepare_items();
			$contactListTable->display();
		} else {
			echo "WP_List_Table n'est pas disponible.";
		}
		
		echo "</div>";
	}

	add_menu_page('Contacts', 'Contacts', 'manage_options', 'contact-plugin', 'contact_content');
}

add_action('admin_menu', 'add_plugin_to_admin');

// Fourth step: Create the form

function show_contact_form() {
	ob_start();

	if (isset($_POST['contact'])) {
		$first_name = sanitize_text_field($_POST["first_name"]);
		$last_name = sanitize_text_field($_POST["last_name"]);
		$phone = sanitize_text_field($_POST["phone"]);
		$comment = esc_textarea($_POST["comment"]);
		// $event = esc_textarea($_POST["event"]);

		if ($first_name != '' && $last_name != '' && $phone  != '' && $comment  != '') {
			global $wpdb;

			$table_name = $wpdb->prefix . 'contacts';
	
			$wpdb->insert( 
				$table_name,
				array( 
					'first_name' => $first_name,
					'last_name' => $last_name,
					'phone' => $phone,
					'comment' => $comment,
					// 'event' => $event,
				) 
			);

			echo "<h4>Merci! Nous vous re-contacterons d??s que possible.</h4>";
		}
	}

	echo "<form method='POST'>";
	// echo "<input type='hidden' name='event' value='" . get_the_title() . "'>";
	echo "<input type='text' name='first_name' placeholder='Pr??nom' style='width:100%' required>";
	echo "<input type='text' name='last_name' placeholder='Nom de famille' style='width:100%' required>";
	echo "<input type='tel' name='phone' placeholder='Num??ro de t??l??phone' style='width:100%' required>";
	echo "<textarea name='comment' placeholder='Ajouter un commentaire' style='width:100%' required></textarea>";
	echo "<input type='submit' name='contact' value='Envoyez'>";
	echo "</form>";

	return ob_get_clean();
}

add_shortcode('show_contact_form', 'show_contact_form');

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