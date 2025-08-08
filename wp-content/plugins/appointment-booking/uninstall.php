<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'abp_appointments';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

delete_option('abp_settings');