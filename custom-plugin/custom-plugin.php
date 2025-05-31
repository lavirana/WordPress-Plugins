<?php

/**
 * Plugin Name: Member Management System
 * Description: A WordPress plugin to manage site members with custom profiles, roles, and admin dashboard controls.
 * Version: 1.0.0
 * Author: Ashish Rana
 * Author URI: https://thetechinfo.net/
 * Plugin URI: https://thetechinfo.net/member-management-system
 * Requires at least: 6.3.2
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: member-management-system
 * Domain Path: /languages
 */


 define("MMS_PLUGIN_PATH", plugin_dir_path(__FILE__));
 define("MMS_PLUGIN_URL", plugin_dir_url(__FILE__));
 //calling action hook to add menu
 add_action("admin_menu", "cpp_add_admin_menu");


 //Add Menu
 function cpp_add_admin_menu(){
    add_menu_page("Member System | Member Management System", "Member System", "manage_options","member-system", "cp_handle_app_menu", "dashicons-businessman", 5);

    add_submenu_page("member-system", "Add Member", "Add Member", "manage_options", "member-system" ,"cp_handle_app_menu");

    add_submenu_page("member-system", "List Member", "List Member", "manage_options", "list-member", "ems_list_member");
 }


 //Menu Handle callback
 function cp_handle_app_menu(){
      include_once(MMS_PLUGIN_PATH."pages/add-member.php");
 }

 //Sub Menu Handle callback
 function ems_list_member(){
   include_once(MMS_PLUGIN_PATH."pages/list-member.php");
     }

 register_activation_hook(__FILE__, "mms_create_table");    

 function mms_create_table(){
   global $wpdb;
   $table_prefix = $wpdb->prefix;
   $sql = "CREATE TABLE {$table_prefix}mms_form_data (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `phoneNo` varchar(50) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

include_once ABSPATH. "wp-admin/includes/upgrade.php";

dbDelta($sql);
 }


 //Plugin deactivation
   register_deactivation_hook(__FILE__, "mms_drope_table"); 
   function mms_drope_table(){
      global $wpdb;
      $table_prefix = $wpdb->prefix;
      $sql = "DROP TABLE IF EXISTS {$table_prefix}mms_form_data"; // {$table_prefix}mms_form_data
      $wpdb->query($sql);
   }

   add_action("admin_enqueue_scripts","mms_add_plugin_assets");

   function mms_add_plugin_assets(){

      //style (css)
      wp_enqueue_style("mms-bootstrap-css", MMS_PLUGIN_URL."css/bootstrap.min.css", array(),"1.0.0","all");
      wp_enqueue_style("mms-datatables-css", MMS_PLUGIN_URL."css/dataTables.min.css", array(),"1.0.0","all");
      wp_enqueue_style("mms-custom-css", MMS_PLUGIN_URL."css/custom.css", array(),"1.0.0","all");

      //js (javascript plugin script)
      wp_enqueue_script("mms-bootstrap-js",MMS_PLUGIN_URL."js/bootstrap.min.js", array("jquery"),"1.0.0","all");
      wp_enqueue_script("mms-datatables-js",MMS_PLUGIN_URL."js/dataTables.min.js", array("jquery"),"1.0.0");
      wp_enqueue_script("mms-validate-js",MMS_PLUGIN_URL."js/jquery.validate.min.js", array("jquery"),"1.0.0");
      wp_enqueue_script("mms-custom-js",MMS_PLUGIN_URL."js/custom.js", array("jquery"),"1.0.0");
   }
