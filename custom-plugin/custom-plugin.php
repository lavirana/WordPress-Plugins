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
