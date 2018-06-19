<?php

class WPTextExpander_Mytext {
    
    protected static $_table;
    protected static $_db_version = "1.0.0";
    public static $text_domain = "wptextexpander_mytext";
    
    public static $instance;
    public static $replacements = null;
    
    /**
    *   Singleton implementation - return same instance once it's initialized
    */
    public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
    * Returns table name where all terms are stored
    */    
    public static function table() {
        global $wpdb;
        
        if(self::$_table) {
            return self::$_table;
        }
        self::$_table = $wpdb->prefix . "wptextexpander_mytext";
        return self::$_table;
    }
    
    /**
    * Plugin installation - create / update db table
    */
    public function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE ".self::table()." (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          shortcode varchar(70) not null,
          text text NOT NULL,
          PRIMARY KEY  (id),
          UNIQUE (shortcode)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        add_option( 'wptextexpander_mytext_db_version', self::$_db_version );
    }
    
    /**
    *   DB version check - call for update if necessary
    */
    public function update_db_check() {
        if (get_site_option( 'wptextexpander_mytext_db_version' ) != self::$_db_version ) {
            $this->install();
            update_option( 'wptextexpander_mytext_db_version', self::$_db_version );
        }
    }
    
    /**
    *   Plugin init call - add filters and shortcode
    */
    public static function init() {
        $self = WPTextExpander_Mytext::get_instance();
        $self->update_db_check();

        add_shortcode( 'mytext' , array('WPTextExpander_Mytext', 'mytext'));
        add_filter('widget_text', array('WPTextExpander_Mytext', 'do_shortcode'));
        add_filter('widget_title', array('WPTextExpander_Mytext', 'do_shortcode'));
        add_filter('wp_nav_menu_items', array('WPTextExpander_Mytext', 'do_shortcode'));         
    }
    
    /**
    *   Actual shortcode execution - load terms on first call, lookup replacement 
    */
    public static function mytext($atts, $content="") {
        global $wpdb;
        
        $key = isset($atts[0]) ? $atts[0] : null;
        
        if($key) {
            $key = strtolower($key);
            
            if(!isset(WPTextExpander_Mytext::$replacements)) {
                WPTextExpander_Mytext::$replacements = array();
                
                $sql = "SELECT * FROM ".WPTextExpander_Mytext::table()."";
		        $results = $wpdb->get_results( $sql, 'ARRAY_A' );
		        foreach($results as $term) {
		            WPTextExpander_Mytext::$replacements[strtolower($term["shortcode"])] = $term["text"];
                
		        }	
            }
            
            if(isset(WPTextExpander_Mytext::$replacements[$key])) {
                $content = WPTextExpander_Mytext::$replacements[$key];
            }
        } 
        
        return do_shortcode($content);
    }
    
    /**
    *   Do Shortcode wrap
    */
    public static function do_shortcode($content="") {
        return do_shortcode($content);
    }
     
    /**
    *   Load term by ID
    */      
    public static function loadSingleTerm($id) {
        global $wpdb;
        
        $sql = "SELECT * FROM ".WPTextExpander_Mytext::table()." WHERE id = ".intval($id);
		$result = $wpdb->get_row( $sql, 'ARRAY_A' );
		
		return $result;   
    }   
}