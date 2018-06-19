<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPTextExpander_ListTable extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Term', WPTextExpander_Mytext::$text_domain  ), //singular name of the listed records
			'plural'   => __( 'Terms', WPTextExpander_Mytext::$text_domain ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve terms data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 * @param string $s
	 *
	 * @return mixed
	 */
	public static function get_terms( $per_page = 5, $page_number = 1, $s=null) {

		global $wpdb;

		$sql = "SELECT * FROM ".WPTextExpander_Mytext::table()." ";
		if(isset($s) and strlen($s)) {
		    $sql .= " WHERE shortcode like '".esc_sql($s)."%' ";
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a term record.
	 *
	 * @param int $id term ID
	 */
	public static function delete_term( $id ) {
		global $wpdb;

		$wpdb->delete(
			"".WPTextExpander_Mytext::table()."",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @param string $s
	 *
	 * @return null|string
	 */
	public static function record_count($s) {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM ".WPTextExpander_Mytext::table()."";
    	if(isset($s) and strlen($s)) {
		    $sql .= " WHERE shortcode like '".esc_sql($s)."%' ";
		}

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no term data is available */
	public function no_items() {
		_e( 'No terms avaliable.', 'sp' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'shortcode':
			case 'text':
				return $item[ $column_name ];
		    case 'hint':
		        return '[mytext '.$item['shortcode'].']';
			default:
				return '&nbsp;';
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for shortcode column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_shortcode( $item ) {

		$edit_nonce = wp_create_nonce( 'WPTextExpander_Mytext_edit_nonce' );
        $delete_nonce = wp_create_nonce( 'WPTextExpander_Mytext_delete_nonce' );

		$title = '<strong>' . $item['shortcode'] . '</strong>';

		$actions = [
			'edit' => sprintf( '<a href="?page=%s&action=%s&term=%s&_wpnonce=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ), $edit_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&term=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'shortcode'    => __( 'Term', WPTextExpander_Mytext::$text_domain ),
			'text' => __( 'Replacement', WPTextExpander_Mytext::$text_domain ),
			'hint' => __( 'Shortcode', WPTextExpander_Mytext::$text_domain ),
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'shortcode' => array( 'shortcode', true ),
			'text' => array( 'text', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => __('Delete',WPTextExpander_Mytext::$text_domain)
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'terms_per_page', 50 );
		$current_page = $this->get_pagenum();
		$s = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
	    
		$total_items  = self::record_count($s);

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_terms( $per_page, $current_page, $s );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
        	$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'WPTextExpander_Mytext_delete_nonce' ) ) {
				die( 'Session check error, please try again' );
			}
			else {
				self::delete_term( absint( $_GET['term'] ) );
                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}
		} 
		
		if ( 'edit' === $this->current_action()) {
        	$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'WPTextExpander_Mytext_edit_nonce' ) ) {
				die( 'Session check error, please try again' );
			}
			else {
				$continue = self::edit_term( absint( $_GET['term'] ) );
                if(!$continue) {
    				exit;
    			}
			}
		}
		
		if ( 'save' === $this->current_action()) {
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'WPTextExpander_Mytext_save_nonce' ) ) {
				die( 'Session check error, please try again' );
			}
			else {
				$continue = self::edit_term( absint( $_GET['term'] ) );
                if(!$continue) {
    				exit;
    			}
			}
		}
		
		if ( 'new' === $this->current_action() ) {
			$continue = self::edit_term( absint(  ) );
            if(!$continue) {
				exit;
			}
		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_term( $id );

			}
		    wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}
	
	/*
	* Saves term into database, returns error if failed
	*
	* @param int $id
	* @param array $term
	*
	* @return string | false 
	*/
	public function save_term($id = 0, $term) {
        global $wpdb;
        
        if(!isset($term["shortcode"]) || strlen($term["shortcode"]) === 0) {
            return __( "Shortcode is required", WPTextExpander_Mytext::$text_domain  );
        }
        
        if(!preg_match("/^[\w\-]+$/", $term["shortcode"])) {
            return __( "Shortcode may only contain letters, numbers, underscore (_) and dash (-) in it", WPTextExpander_Mytext::$text_domain  );
        }
        
        // check for uniqueness
        $sql = "SELECT * FROM ".WPTextExpander_Mytext::table()." WHERE shortcode = '".esc_sql( $term['shortcode'] )."' and id <> ".intval($id);        
		$result = $wpdb->get_row( $sql, 'ARRAY_A' );
		if($result) {
		    return 
		        __( "Shortcode", WPTextExpander_Mytext::$text_domain). 		        
		        " ".esc_attr($term["shortcode"])." ".
		        __("already exists", WPTextExpander_Mytext::$text_domain);
        }
        
        // all good, save
	    if($id) {
	        $r = $wpdb->update( 
            	WPTextExpander_Mytext::table(), 
            	array( 
            		'shortcode' => $term['shortcode'],
            		'text' => $term['text'] 
            	), 
            	array( 'id' => $id ), 
            	array( 
            		'%s',
            		'%s'
            	), 
            	array( '%d' ) 
            );  
        
	    } else {
    	    $r = $wpdb->insert( 
            	WPTextExpander_Mytext::table(), 
            	array( 
            		'shortcode' => $term['shortcode'],
            		'text' => $term['text'] 
            	), array( 
            		'%s',
            		'%s'
            	)
            );  
        
    	}
    	return $r===false ? __( "Could not save term",WPTextExpander_Mytext::$text_domain) : false; 
    }
    
    /*
    * Term edit form
    *
    * @param int $id
    *
    */
	public function edit_term($id = 0) {
	    
	    if($id) {
	        $term = WPTextExpander_Mytext::loadSingleTerm($id);
	    } else {
    	    $term = [
    	        "id" => '',
    	        'shortcode' => '',
    	        'text' => '',
    	    ];
    	}
    	if(isset($_POST['submit'])) {
			$term['text'] = wp_unslash($_POST["term_text"]);
			$term['shortcode'] = sanitize_text_field(wp_unslash(trim($_POST["term_shortcode"])));

        	$err = self::save_term( absint( $_GET['term']), $term );
			
			if($err) {
			    ?>
			    <div class="notice notice-error"><p><?php echo $err; ?></p></div>
			    <?php
			} else {
			    return true;
			}
	    }
	    
        ?>
	    <div class="wrap">
            <h2><?php echo __($id ? "Edit Term" : "Add Term",WPTextExpander_Mytext::$text_domain); ?></h2>
            <form method="post" action="<?php echo sprintf('?page=%s&action=%s&term=%s', esc_attr( $_REQUEST['page'] ), 'save', absint( $id ));?>">
            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'WPTextExpander_Mytext_save_nonce' ); ?>">
           
            
            <table class="form-table">
                <tr valign="top">
                <th scope="row"><?php echo __("Term", WPTextExpander_Mytext::$text_domain);?></th>
                <td><input placeholder="<?php echo __("Term reference key, e.g. my_name",WPTextExpander_Mytext::$text_domain);?>" class="regular-text" maxlength="70" type="text" name="term_shortcode" value="<?php echo esc_attr( $term["shortcode"] ); ?>" /></td>
                </tr>
                 
                <tr valign="top">
                <th scope="row"><?php echo __("Replacement",WPTextExpander_Mytext::$text_domain);?></th>
                <td><textarea name="term_text" class="regular-text" placeholder="<?php echo __("Replacement text, e.g. John Doe. HTML is ok",WPTextExpander_Mytext::$text_domain);?>"><?php echo esc_textarea( $term["text"]  ); ?></textarea></td>
                </tr>
                
            </table>
            
            <?php
                submit_button();
            ?>
            </form>
        </div>
        <?php
	}

}

