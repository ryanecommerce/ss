<?php

/*
Plugin Name: RDP Wiki Embed
Plugin URI: http://www.rdptechsolutions.com/
Description: Enables the inclusion of Wikimedia content into your own blog page or post through the use of shortcodes.
Version: 1.2.13
Author: Robert D Payne
Author URI: http://www.rdptechsolutions.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Turn off all error reporting
//error_reporting(E_ALL^ E_WARNING);
global $wpdb;
global $oRDP_WIKI_EMBED_PLUGIN;
$dir = plugin_dir_path( __FILE__ );
define('RDP_WIKI_EMBED_PLUGIN_BASEDIR', $dir);
define('RDP_WIKI_EMBED_PLUGIN_BASEURL',plugins_url( null, __FILE__ ) );
define('RDP_WIKI_EMBED_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RDP_WIKI_EMBED_TABLE', $wpdb->prefix . 'rdp_wiki_embed');

define('RDP_WIKI_EMBED_INT_MAX', 9223372036854775808);
include_once 'bl/rdpWEUtilities.php';


class RDP_WIKI_EMBED_PLUGIN {
    public static $plugin_slug = 'rdp-wiki-embed'; 
    public static $options_name = 'rdp_wiki_embed_options';    
    public static $version = '1.2.11';    
    private $_options = array(); 
    /* @var $_instance RDP_WIKI_EMBED */
    private $_instance = null;
    private $_resource = '';
    
    function __construct() {
        $options = get_option( RDP_WIKI_EMBED_PLUGIN::$options_name );
        if(is_array($options)){
            $this->_options = $options;        
        }else{
            $this->_options = self::default_settings();
        }
        $this->_resource = RDP_WIKI_EMBED_UTILITIES::globalRequest('rdp_we_resource');  
        $REQUEST_URI = '';
        
        if(empty($this->_resource)):
            $REQUEST_URI = $_SERVER['REQUEST_URI'];
            if($REQUEST_URI){
                $pieces = explode('?',$REQUEST_URI);
                if(count($pieces) > 1){
                    parse_str($pieces[1], $output);
                    if(key_exists('rdp_we_resource', $output)){
                        $this->_resource = $output['rdp_we_resource'];
                    }
                }
            }
        endif;

        add_filter('rdp_ppe_allow_shortcode', array(&$this,'maybeBlockPPE'), 10, 3);
        $this->load_dependencies();
    }//__construct
    
    function maybeBlockPPE($allow, $atts, $content) {
        return empty($this->_resource);
    }//maybeBlockPPE
    
    static function default_settings() {
        return array(
            'wiki_update'     => "1440", /* minutes */
            'wiki_links'      => "default",
            'wiki_links_open_new' => 1,
            'global_content_replace' => 1,
            'global_content_replace_template' => 'same',
            'source_show'      => 1,
            'pre_source'  => "source: ",
            'toc_show' => 0,
            'edit_show'     => 0,
            'infobox_show'  => 1,
            'unreferenced_show' => 0,
            'whitelist' => 'en.wikipedia.org',
            'admin_nav_show' => 0,
            'footer_show' => 0
        );    
    }//default_settings    
    
    static function link_settings(){
        return array('default','overwrite');
    }  
    
    static function cache_settings() {
        return array(
            'No Caching' => '0',
            'Daily' => '1440',
            'Weekly' => '10080',
            'Fortnightly' => '20160',
            'Monthly' => '43800'
        );
    }
    
    private function load_dependencies() {
        if (is_admin()){
            include_once 'pl/rdpWEAdminMenu.php' ;
            include_once 'pl/rdpWEShortcodePopup.php' ;
        } 
        include_once 'bl/simple_html_dom.php';
        include_once 'bl/rdpWEContent.php';
        include_once 'pl/rdpWE.php' ;     
    }//load_dependencies      
    
    private function define_front_hooks(){
        if(defined( 'DOING_AJAX' ))return;
        if(is_admin())return;
        $props = get_object_vars($this); 
        $this->_instance =  new RDP_WIKI_EMBED(self::$version, $props);
        
        if($this->_resource){
            add_filter( 'the_content', array( &$this->_instance, 'contentFilter' ),1 );
        }else{
            add_shortcode('rdp-wiki-embed', array(&$this->_instance, 'shortcode'));
        }        
        add_action( 'wp_enqueue_scripts', array(&$this->_instance, 'enqueueStyles') );
        add_action( 'wp_enqueue_scripts', array(&$this->_instance, 'enqueueScripts') );
        
        add_filter( 'template_include', array( &$this->_instance, 'handleTemplateSelection' ), 99 );       
    }//define_front_hooks 
    
    public function instance() {
        return $this->_instance;
    }
    
    private function define_admin_hooks() {
        if(!is_admin())return;
        if(defined( 'DOING_AJAX' ))return;
        $oRDP_WIKI_EMBED_ADMIN_MENU = new RDP_WIKI_EMBED_ADMIN_MENU(self::$version,$this->_options);
        $oRDP_WIKI_EMBED_ADMIN_MENU->enqueueStylesScripts();
        add_action( 'admin_footer', 'RDP_WIKI_EMBED_SHORTCODE_POPUP::renderPopupForm' );        
        add_action('admin_menu', 'RDP_WIKI_EMBED_ADMIN_MENU::add_menu_item');
        add_action('admin_init', 'RDP_WIKI_EMBED_ADMIN_MENU::admin_page_init'); 
        add_action( 'media_buttons', 'RDP_WIKI_EMBED_SHORTCODE_POPUP::addMediaButton',1 );        
    }//define_admin_hooks    
    
    private function define_ajax_hooks(){
        if(!defined( 'DOING_AJAX' ))return;
        
    }//define_ajax_hooks    
    
    public function run() {
        $this->define_front_hooks();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();  
    }//run      
    
    public static function install(){
        global $wpdb;
        $wpdb->suppress_errors();
        $wpdb->show_errors(false); 

        $table_name = RDP_WIKI_EMBED_TABLE;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
          $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
          $charset_collate .= " COLLATE {$wpdb->collate}";
        } 
        
        $sql = "CREATE TABLE $table_name (
                wiki_key varchar(32) NOT NULL,
                wiki_content longtext NOT NULL,
                date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (wiki_key)
                ) $charset_collate;";          
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $result = dbDelta( $sql ); 
    }//install
    
    
    public static function cron_exec() {
        global $wpdb;
        $wpdb->suppress_errors();
        $wpdb->show_errors(false); 
        $options = get_option( RDP_WIKI_EMBED_PLUGIN::$options_name );
        if(empty($options))$options = self::default_settings();
        $table_name = RDP_WIKI_EMBED_TABLE;
        $sSQL = sprintf('DELETE FROM %s Where date_created < NOW() - INTERVAL %d MINUTE;', $table_name, $options['wiki_update']);
        $wpdb->query($sSQL);
        
    }//cron_exec
    
    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'rdp_we_cron_hook' );
        wp_unschedule_event( $timestamp, 'rdp_we_cron_hook' );
    }//deactivate
}//RDP_WIKI_EMBED_PLUGIN

register_activation_hook( __FILE__, array( 'RDP_WIKI_EMBED_PLUGIN', 'install' ) );

function rdp_wiki_embed_run(){
    add_action( 'rdp_we_cron_hook', array( 'RDP_WIKI_EMBED_PLUGIN','cron_exec') );
    if ( ! wp_next_scheduled( 'rdp_we_cron_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'rdp_we_cron_hook' );
    }    
    //  prevent running code unnecessarily
    if(RDP_WIKI_EMBED_UTILITIES::abortExecution())return;
    global $oRDP_WIKI_EMBED_PLUGIN;    
    $oRDP_WIKI_EMBED_PLUGIN = new RDP_WIKI_EMBED_PLUGIN();
    $oRDP_WIKI_EMBED_PLUGIN->run();
}
add_action('wp_loaded','rdp_wiki_embed_run');

register_deactivation_hook( __FILE__, array( 'RDP_WIKI_EMBED_PLUGIN', 'deactivate' ) );