<?php
/**
* Plugin Name: Insert Link Class Plugin
* Plugin URI: http://www.n7studios.co.uk/2010/03/07/wordpress-insert-link-class-plugin/
* Version: 1
* Author: <a href="http://www.n7studios.co.uk/">Tim Carr</a>
* Description: Allows custom class names to be added to the Insert / edit link functionality in the Wordpress Page and Post Editor.
*/

/**
* Insert Link Class Plugin Class
* 
* @package Wordpress
* @subpackage Insert Link Class Plugin
* @author Tim Carr
* @version 1
* @copyright n7 Studios
*/
class InsertLinkClassPlugin {
    /**
    * Constructor.  Initiates plugin hooks and filters.
    */
    function InsertLinkClassPlugin() {
        if (is_admin()) { // Only if we're in the admin section
            define(PLUGIN_NAME, 'insert-link-class-plugin'); // Plugin programmatic name        
            define(TABLE_NAME, 'insert_link_classes'); // Table name, without Wordpress table prefix
            define(DOCUMENT_ROOT, substr(str_replace("\\", "/", dirname(__FILE__)), 0, strpos(str_replace("\\", "/", dirname(__FILE__)), "/wp-content")));
            define(PLUGIN_ROOT, substr(str_replace("\\", "/", dirname(__FILE__)), 0, strpos(str_replace("\\", "/", dirname(__FILE__)), "/".PLUGIN_NAME))."/".PLUGIN_NAME);
            
            register_activation_hook(__FILE__, array(&$this, 'Install')); // Activation routine
            register_deactivation_hook(__FILE__, array(&$this, 'Uninstall')); // Deactivation routine
            
            add_action('admin_menu', array(&$this, 'AddAdminPanels')); // Add admin panels to Wordpress Admin
            add_filter('tiny_mce_before_init', array(&$this, 'AddCustomTinyMCEOptions')); // Custom options for TinyMCE editor
            
            wp_enqueue_script('jquery'); // jQuery
        }
    }
    
    /**
    * Installation routine
    */
    function Install() {
        global $wpdb;
        
        $wpdb->query("  CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.TABLE_NAME." (
                            classID int(10) NOT NULL AUTO_INCREMENT,
                            name varchar(200) NOT NULL,
                            css varchar(200) NOT NULL,
                            PRIMARY KEY (`classID`)
                        ) 
                        ENGINE=MyISAM
                        DEFAULT CHARSET=utf8
                        AUTO_INCREMENT=1");
    }
    
    /**
    * Uninstallation routine
    */
    function Uninstall() {
        global $wpdb;
        
        $wpdb->query("  DROP TABLE IF EXISTS ".$wpdb->prefix.TABLE_NAME);
    }
    
    /**
    * Creates menu and submenu entries in Wordpress Admin.
    */
    function AddAdminPanels() {
        add_menu_page('Link Classes', 'Link Classes', 9, PLUGIN_NAME, array(&$this, 'AdminPanel'));
        add_submenu_page(PLUGIN_NAME, 'Settings', 'Settings', 9, PLUGIN_NAME, array(&$this, 'AdminPanel'));        
    }
    
    /**
    * Outputs the plugin Admin Panel in Wordpress Admin
    */
    function AdminPanel() {
        switch ($_GET['cmd']) {
            case 'add':
            case 'edit':
                // Save form
                if (isset($_POST['classID'])) {
                    // Save & display list of current records
                    $this->SaveRecord($_GET['pKey'], $_POST);
                    $this->data = $this->GetAllRecords();
                    $this->successMessage = 'Record Saved';
                    include_once(PLUGIN_ROOT.'/list.php');    
                } else {
                    // Display form
                    $this->data = $this->GetRecord($_GET['pKey']);
                    include_once(PLUGIN_ROOT.'/form.php');
                }
                break;
            case 'save':
                // Delete & display list of all current records
                if ($_POST['doAction']) {
                    foreach ($_POST['classID'] as $classID=>$delete) {
                        if ($delete) $this->DeleteRecord($classID);
                    }
                }
                $this->data = $this->GetAllRecords();
                $this->successMessage = 'Record(s) Deleted';
                include_once(PLUGIN_ROOT.'/list.php');
                break;
            default:
                // Display list of current records
                $this->data = $this->GetAllRecords();
                include_once(PLUGIN_ROOT.'/list.php');
                break;    
        }        
    }
    
    /**
    * Custom options for TinyMCE editor
    * 
    * @param array $initArray Default TinyMCE options
    * @return array Amended TinyMCE options
    */
    function AddCustomTinyMCEOptions($initArray) {
        global $wpdb;
        
        // Default Wordpress classes
        $cssClasses = array('aligncenter' => 'aligncenter',
                            'alignleft' => 'alignleft',
                            'alignright' => 'alighright',
                            'wp-caption' => 'wp-caption',
                            'wp-caption-dd' => 'wp-caption-dd',
                            'wpGallery' => 'wpGallery',
                            'wp-oembed' => 'wp-oembed');
        
        // Custom classes
        $customClasses = $this->GetAllRecords();
        
        // Build array
        foreach($cssClasses as $css=>$name) {
            $initArray['theme_advanced_styles'] .= $name.'='.$css.';';
        }
        foreach($customClasses as $key=>$customClass) {
            $initArray['theme_advanced_styles'] .= $customClass->name.'='.$customClass->css.';';
        } 
        $initArray['theme_advanced_styles'] = rtrim($initArray['theme_advanced_styles'], ';'); // Remove final semicolon from list
        
        return $initArray;
    }
    
    /**
    * Adds or updates a record
    */
    function SaveRecord($pKey = '', $data) {
        global $wpdb;
        
        if ($pKey == '') {
            // Add new record
            $wpdb->query("  INSERT INTO ".$wpdb->prefix.TABLE_NAME." (name, css)
                            VALUES ('".htmlentities($data['name'])."', '".htmlentities($data['css'])."')");
        } else {
            // Edit existing record
            $wpdb->query("  UPDATE ".$wpdb->prefix.TABLE_NAME." SET
                            name = '".htmlentities($data['name'])."', 
                            css = '".htmlentities($data['css'])."'
                            WHERE classID = ".mysql_real_escape_string($pKey)."
                            LIMIT 1");
        }
        
        return true;
    }
    
    /**
    * Deletes the specified record by primary key
    * 
    * @param int $pKey Primary Key
    * @return bool Success
    */
    function DeleteRecord($pKey) {
        global $wpdb;
        
        $wpdb->query("  DELETE FROM ".$wpdb->prefix.TABLE_NAME."
                        WHERE classID = ".mysql_real_escape_string($pKey)."
                        LIMIT 1");
        
        return true;
    }
    
    /**
    * Gets specific record from the table by primary key
    * 
    * @return array Record
    */
    function GetRecord($pKey) {
        global $wpdb;
        
        $results = $wpdb->get_results(" SELECT *
                                        FROM ".$wpdb->prefix.TABLE_NAME."
                                        WHERE classID = ".mysql_real_escape_string($pKey)."
                                        LIMIT 1");
        return $results[0];
    }
    
    /**
    * Gets all records from the table
    * 
    * @return array Records
    */
    function GetAllRecords() {
        global $wpdb;
        
        return $wpdb->get_results(" SELECT *
                                    FROM ".$wpdb->prefix.TABLE_NAME."
                                    ORDER BY name ASC");
    }
}

$ilcp = new InsertLinkClassPlugin(); // Initialise class
?>