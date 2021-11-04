<?php

class ReservationListTable extends WP_List_Table {
	function column_post_id($item) {
		global $wpdb;
		$post_id = $item['post_id'];
		$table_name = $wpdb->prefix . 'posts';
		$event = $wpdb->get_row("SELECT * FROM $table_name WHERE ID=$post_id AND post_type='events'");

		$date = get_post_meta($event->ID, 'event_date', true);
		$query_args = array('action' => 'edit','post'  => $event->ID);
		return '<a href="'. esc_url(wp_nonce_url(add_query_arg( $query_args, 'post.php' ))) .'">'. $event->post_title . ' le ' . $date . '</a>';
	}

	function get_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'reservations';
		return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
	}

	function get_columns() {
		return array(
			'id' => 'ID',
			'first_name' => 'Prénom',
			'last_name' => 'Nom de famille',
			'phone' => 'Numéro de téléphone',
			'post_id' => 'Evènement',
		);
	}

	function get_sortable_columns() {
		return array(
			'id' => array('id', false),
		);
	}

	function column_default($item, $column_name) {
		return $item[$column_name];
	}
	
	function usort_reorder($a, $b) {
		$orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
		$order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
		$result = strcmp($a[$orderby], $b[$orderby]);
		return ($order === 'asc') ? $result : -$result;
	}

	function prepare_items() {
		$data = $this->get_data();
		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, array(), $sortable);
		usort($data, array($this,'usort_reorder'));
		$this->process_bulk_action();
		$this->items = $data;
	}
}