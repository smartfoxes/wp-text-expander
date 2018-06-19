<?php

class WPTextExpander_Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
    // custom WP_List_Table object
	public $terms_obj;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'Add_plugin_page' ) );
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
    }
    
	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


    /**
     * Add options page to WP Dashboard
     */
    public function add_plugin_page()
    {
        $hook = add_menu_page(
            __('Text Expander',WPTextExpander_Mytext::$text_domain), 
            __('Text Expander',WPTextExpander_Mytext::$text_domain), 
            'manage_options', 
            'wp-text-expander-settings', 
            array( $this, 'settings_page' ),
            'dashicons-book'
        );
        
		add_action( "load-$hook", [ $this, 'screen_option' ] );
    }
    
    /**
    *   Add setting link to plugin page
    */
    public static function action_link( $actions, $plugin_file ) {
    	static $plugin;
    	
    	$plugin_dir = explode( '/', $plugin_file )[0];

    	if (!isset($plugin))
    		$plugin = explode( '/', plugin_basename( __FILE__ ) )[0];
  
    	if ($plugin == $plugin_dir) {
			$settings = array('settings' => '<a href="admin.php?page=wp-text-expander-settings">' . __('Settings',  WPTextExpander_Mytext::$text_domain ) . '</a>');	
			$actions = array_merge($settings, $actions);
        }
		
		return $actions;
    }

    /**
     * Options page callback
     */
    public function settings_page()
    {

		$this->terms_obj->prepare_items();
        ?>
        <div class="wrap">
			<h1 class="wp-heading-inline"><?php echo __("WP Text Expander",  WPTextExpander_Mytext::$text_domain);?></h1>
			<a href="?action=new&page=<?php echo esc_attr($_REQUEST["page"]);?>" class="page-title-action">Add New Term</a>

        
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
				    <?php if($this->terms_obj->has_items()): ?>
					<div id="post-body-content">
					    <form id="wp-text-expander-search-form" method="get">
                			<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
                			<?php 
                				$this->terms_obj->search_box( __( 'Find',  WPTextExpander_Mytext::$text_domain ), 'WPTextExpander_mytext_search_id' );
                			?>					
                		</form>
                		
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->terms_obj->display(); ?>
							</form>
						</div>
					</div> 
					
					<div class="big-margin">
                    	<p class="help">Are you enjoying this plugin? <a href="https://www.smartfoxes.ca/coffee">Buy developer a coffee</a> to fuel new features and other plugins development.</p>
                    	
                    	<p class="help" id="wp-text-expander-welcome-panel-link"><a target="_blank" href="#" onClick="jQuery('#wp-text-expander-welcome-panel').removeClass('hidden');jQuery('#wp-text-expander-welcome-panel-link').addClass('hidden');return false;">Show Plugin Intro Page</a></p>
                    </div>

				    <?php endif; ?>
				    
				    
				    <div id="wp-text-expander-welcome-panel" class="welcome-panel <?php if($this->terms_obj->has_items()) { echo "hidden"; } ?>">		
			            <div class="welcome-panel-content">
                        	<h1>Welcome to WP Text Expander!</h1>
                        	
                        	<p style="margin-top:1em;" class="about-description">This plugin makes it easy to control snippets of text across your WordPress website from a central spot in WP Admin's Dashboard.</p>
                        	
                            <p style="margin-top:1em;" class="about-description">The usage is extremely simple - just create text snippets using short and easy to remember slug, e.g. 'next_course_date' or 'home_service_price', add short or long text snippet to this term (plain text or HTML) and use shortcode in your pages and posts, for example [mytext next_course_date] or [mytext home_service_price].</p>
                            
                            <p style="margin-top:1em;" class="about-description">Shortcodes will be automatically replaced with the actual text you entered on this page, giving you easy way to update those texts everywhere on the website from the central spot.</p>
                                        				    
        				    <p><a href="?action=new&page=<?php echo esc_attr($_REQUEST["page"]);?>" class="button button-primary button-hero">Add Your First Term</a></p>   
        				    
        				    <h3>P.S. Don't be this guy :)</h3>
        				    <p><img style="max-width:100%;" src="https://worduoso.com/media/WordPress-Sins-Hardcoding-Content-1024x705.jpg"></p>
                    	</div>                    	
            		</div>
						    
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
    }
    
    /**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => __('Terms per page',WPTextExpander_Mytext::$text_domain),
			'default' => 50,
			'option'  => __('terms_per_page',WPTextExpander_Mytext::$text_domain)
		];

		add_screen_option( $option, $args );

		$this->terms_obj = new WPTextExpander_ListTable();
	}
   
}