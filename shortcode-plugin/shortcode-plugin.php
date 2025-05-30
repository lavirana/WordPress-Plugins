<?php
/*
Plugin Name: ShortCode Plugin
Plugin URI: https://example.com/hello-world/
Description: This is our second plugin which gives idea about shortcode.
Author: Ashish Rana
Version: 1.0.0
Author URI: https://thetechinfo.net/
 */

 // Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

//basic short code//
add_shortcode("message", "sp_show_static_message");
function sp_show_static_message(){
    return '<p style="color:red; font-size:36px;font-weight:bold">Hello I am simple shortcode message<p>';
}


//shortcode with parameters//
add_shortcode('student', 'sp_handle_student_data');
function sp_handle_student_data($attributes){
$attributes = shortcode_atts(array(
    "name" => "Default Student",
    "email" => "Default Email"
   ), $attributes, "student");

   return "<h3>Student Data : Name - {$attributes['name']}, Email - {$attributes['email']}</h3>";
}


//shortcode with DB Operations
add_shortcode("list-post","sp_handle_list_posts");
     function sp_handle_list_posts(){
            global $wpdb;
            $table_prefix = $wpdb->prefix;
            $table_name = $table_prefix . "posts";
            //Get posts whose post type = post and status = publish
            $posts = $wpdb->get_results(
                "SELECT post_title from ".$table_name." WHERE post_type = 'post' AND post_status = 'publish'"
            );
        if(count($posts) > 0 ){
            $outputHtml = '<ul>';
            foreach($posts as $post){
                $outputHtml .= '<li>'.$post->post_title.'</li>';
            }
            $outputHtml .= '<ul>';
            return $outputHtml;
        }
        return 'No Post Found';
    }

    add_shortcode("list-post","sp_handle_list_posts_wp_query_class");
    function sp_handle_list_posts_wp_query_class($attributes){
                $attributes = shortcode_atts(array(
                    'number' => 5
                ), $attributes, "list-post");

                $query = new WP_Query(array(
                "post_per_page" => $attributes['number'],
                "post_status" => "publish"
                ));
                if($query->have_posts()){
                    $outputHtml = '<ul>';
                    while($query->have_posts()){
                        $query->the_post();
                        $outputHtml .= '<li>'.get_the_title().'</li>';
                    }
                    $outputHtml .= '<ul>';
                    return $outputHtml;
                }
                return 'No Post Found';
    }