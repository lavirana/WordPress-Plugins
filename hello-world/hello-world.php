<?php
/*
Plugin Name: Hello world
Plugin URI: https://example.com/hello-world/
Description: This is our first plugin which creates some information widget to admin dashboard.
Author: Ashish Rana
Version: 1.0.0
Author URI: https://thetechinfo.net/
 */

 // Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Now we set that function up to execute when the admin_notices action is called.
add_action( 'admin_notices', 'hw_show_warning_message' );

function hw_show_success_message(){
    echo '<div class="notice notice-success is-dismissible"><p>Hello, I am a success message</p></div>';
}

function hw_show_error_message(){
    echo '<div class="notice notice-error is-dismissible"><p>Hello, I am an error message</p></div>';
}
function hw_show_information_message(){
    echo '<div class="notice notice-info is-dismissible"><p>Hello, I am an Informational message</p></div>';
}
function hw_show_warning_message(){
    echo '<div class="notice notice-warning is-dismissible"><p>Hello, I am an Warning message</p></div>';
}


//Admin Dashboard Widget

add_action('wp_dashboard_setup', "hw_hello_world_dashboard_widget");

function hw_hello_world_dashboard_widget(){
    wp_add_dashboard_widget("hw_hello_world", "Hw - Hello World Widget", "hw_custom_admin_widget");
}

function hw_custom_admin_widget(){
    echo "This is the Hello world custom admin widget";
}