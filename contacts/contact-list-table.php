<?php

class ContactListTable extends WP_List_Table {
	protected function column_id( $item ) {
		return sprintf(
			'<label><input type="checkbox" name="contact[]" value="%1$s" />%1$s</label>',
			$item['id']
		);
	}

	protected function column_first_name($item) {
		$page = wp_unslash($_REQUEST['page']);

		$edit_query_args = array(
			'page'   => $page,
			'action' => 'edit',
			'contact'  => $item['id'],
		);

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( wp_nonce_url( add_query_arg( $edit_query_args, 'admin.php' ), 'edit_' . $item['id'] ) ),
			'Modifier'
		);

		$delete_query_args = array(
			'page'   => $page,
			'action' => 'delete',
			'contact'  => $item['id'],
		);

		$actions['delete'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( wp_nonce_url( add_query_arg( $delete_query_args, 'admin.php' ), 'delete_' . $item['id'] ) ),
			'Supprimer'
		);

		return sprintf('%1$s %2$s', $item['first_name'], $this->row_actions( $actions ));
	}

	protected function get_bulk_actions() {
		$actions = array(
			'delete' => 'Supprimer',
			'edit' => 'Modifier',
		);

		return $actions;
	}

	protected function process_bulk_action() {
		if ('delete' === $this->current_action() ) {
			
		}

		if ('edit' === $this->current_action() ) {
			
		}
	}

	/// ------- ///

	function get_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'contacts';
		return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
	}

	function get_columns() {
		return array(
			'id' => 'ID',
			'first_name' => 'Prénom',
			'last_name' => 'Nom de famille',
			'phone' => 'Numéro de téléphone',
			'comment' => 'Commentaire',
			'event' => 'Evènement',
		);
	}

	function get_sortable_columns() {
		return array(
			'id' => array('id', false),
			'event' => array('event', false),
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