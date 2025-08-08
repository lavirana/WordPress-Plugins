<?php
/**
 * Plugin Name: Appointment Booking Plugin
 * Description: Lightweight appointment booking with services, time slots, and email notifications.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: appointment-booking
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Constants
if (!defined('ABP_VERSION')) {
    define('ABP_VERSION', '1.0.0');
}
if (!defined('ABP_PLUGIN_FILE')) {
    define('ABP_PLUGIN_FILE', __FILE__);
}
if (!defined('ABP_PLUGIN_DIR')) {
    define('ABP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('ABP_PLUGIN_URL')) {
    define('ABP_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Options key
const ABP_SETTINGS_OPTION_KEY = 'abp_settings';

// Activation: create database table and default options
register_activation_hook(__FILE__, 'abp_activate_plugin');
function abp_activate_plugin(): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_name = $wpdb->prefix . 'abp_appointments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id BIGINT UNSIGNED NULL,
        customer_name VARCHAR(190) NOT NULL,
        customer_email VARCHAR(190) NOT NULL,
        customer_phone VARCHAR(50) NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        appointment_datetime DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY unique_slot (service_id, appointment_datetime),
        PRIMARY KEY  (id),
        KEY service_idx (service_id),
        KEY status_idx (status),
        KEY date_idx (appointment_date)
    ) {$charset_collate};";

    dbDelta($sql);

    $default_settings = array(
        'recipient_email' => get_option('admin_email'),
        'business_start' => '09:00',
        'business_end' => '17:00',
        'slot_interval_minutes' => 30,
        'success_message' => __('Thank you! Your appointment has been booked. Check your email for confirmation.', 'appointment-booking'),
    );

    $existing = get_option(ABP_SETTINGS_OPTION_KEY);
    if (!is_array($existing)) {
        add_option(ABP_SETTINGS_OPTION_KEY, $default_settings);
    } else {
        update_option(ABP_SETTINGS_OPTION_KEY, wp_parse_args($existing, $default_settings));
    }
}

// Deactivation: nothing for now
register_deactivation_hook(__FILE__, 'abp_deactivate_plugin');
function abp_deactivate_plugin(): void {}

// Register Service custom post type
add_action('init', 'abp_register_service_cpt');
function abp_register_service_cpt(): void {
    $labels = array(
        'name' => _x('Services', 'Post Type General Name', 'appointment-booking'),
        'singular_name' => _x('Service', 'Post Type Singular Name', 'appointment-booking'),
        'menu_name' => __('Services', 'appointment-booking'),
        'name_admin_bar' => __('Service', 'appointment-booking'),
        'add_new' => __('Add New', 'appointment-booking'),
        'add_new_item' => __('Add New Service', 'appointment-booking'),
        'new_item' => __('New Service', 'appointment-booking'),
        'edit_item' => __('Edit Service', 'appointment-booking'),
        'view_item' => __('View Service', 'appointment-booking'),
        'all_items' => __('All Services', 'appointment-booking'),
        'search_items' => __('Search Services', 'appointment-booking'),
        'not_found' => __('No services found.', 'appointment-booking'),
    );

    $args = array(
        'label' => __('Service', 'appointment-booking'),
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-hammer',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => false,
        'query_var' => false,
        'show_in_rest' => false,
    );

    register_post_type('abp_service', $args);
}

// Service meta: duration and capacity
add_action('add_meta_boxes', 'abp_register_service_metaboxes');
function abp_register_service_metaboxes(): void {
    add_meta_box(
        'abp_service_settings',
        __('Service Settings', 'appointment-booking'),
        'abp_render_service_metabox',
        'abp_service',
        'side',
        'default'
    );
}

function abp_render_service_metabox(WP_Post $post): void {
    wp_nonce_field('abp_service_meta_nonce', 'abp_service_meta_nonce_field');

    $duration_minutes = (int) get_post_meta($post->ID, '_abp_duration_minutes', true);
    if ($duration_minutes <= 0) {
        $duration_minutes = 30;
    }
    $capacity = (int) get_post_meta($post->ID, '_abp_capacity', true);
    if ($capacity <= 0) {
        $capacity = 1;
    }
    ?>
    <p>
        <label for="abp_duration_minutes"><strong><?php echo esc_html(__('Duration (minutes)', 'appointment-booking')); ?></strong></label>
        <input type="number" min="5" step="5" id="abp_duration_minutes" name="abp_duration_minutes" value="<?php echo esc_attr($duration_minutes); ?>" style="width:100%" />
    </p>
    <p>
        <label for="abp_capacity"><strong><?php echo esc_html(__('Capacity (bookings per slot)', 'appointment-booking')); ?></strong></label>
        <input type="number" min="1" step="1" id="abp_capacity" name="abp_capacity" value="<?php echo esc_attr($capacity); ?>" style="width:100%" />
    </p>
    <?php
}

add_action('save_post_abp_service', 'abp_save_service_meta', 10, 2);
function abp_save_service_meta(int $post_id, WP_Post $post): void {
    if (!isset($_POST['abp_service_meta_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abp_service_meta_nonce_field'])), 'abp_service_meta_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $duration_minutes = isset($_POST['abp_duration_minutes']) ? (int) $_POST['abp_duration_minutes'] : 30;
    $capacity = isset($_POST['abp_capacity']) ? (int) $_POST['abp_capacity'] : 1;

    if ($duration_minutes < 5) {
        $duration_minutes = 5;
    }
    if ($capacity < 1) {
        $capacity = 1;
    }

    update_post_meta($post_id, '_abp_duration_minutes', $duration_minutes);
    update_post_meta($post_id, '_abp_capacity', $capacity);
}

// Settings page
add_action('admin_menu', 'abp_register_admin_menu');
function abp_register_admin_menu(): void {
    add_menu_page(
        __('Appointments', 'appointment-booking'),
        __('Appointments', 'appointment-booking'),
        'manage_options',
        'abp_appointments',
        'abp_render_admin_appointments_page',
        'dashicons-calendar-alt',
        25
    );

    add_submenu_page(
        'abp_appointments',
        __('Settings', 'appointment-booking'),
        __('Settings', 'appointment-booking'),
        'manage_options',
        'abp_settings',
        'abp_render_settings_page'
    );
}

function abp_render_admin_appointments_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_appointments';

    $per_page = 20;
    $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $offset = ($page - 1) * $per_page;

    $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    $where = '';
    $params = array();
    if ($status_filter !== '') {
        $where = 'WHERE status = %s';
        $params[] = $status_filter;
    }

    $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} " . $where, $params));
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} " . $where . " ORDER BY appointment_datetime DESC LIMIT %d OFFSET %d", array_merge($params, array($per_page, $offset))), ARRAY_A);

    $num_pages = (int) ceil($total / $per_page);

    echo '<div class="wrap"><h1>' . esc_html(__('Appointments', 'appointment-booking')) . '</h1>';

    echo '<form method="get" style="margin:16px 0">';
    echo '<input type="hidden" name="page" value="abp_appointments" />';
    echo '<select name="status">';
    echo '<option value="">' . esc_html(__('All statuses', 'appointment-booking')) . '</option>';
    foreach (array('pending', 'confirmed', 'cancelled') as $status) {
        printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($status), selected($status_filter, $status, false));
    }
    echo '</select> ';
    submit_button(__('Filter'), 'secondary', '', false);
    echo '</form>';

    echo '<table class="widefat fixed striped"><thead><tr>';
    $cols = array(__('ID', 'appointment-booking'), __('Service', 'appointment-booking'), __('Name', 'appointment-booking'), __('Email', 'appointment-booking'), __('Phone', 'appointment-booking'), __('Date', 'appointment-booking'), __('Time', 'appointment-booking'), __('Status', 'appointment-booking'));
    foreach ($cols as $col) {
        echo '<th>' . esc_html($col) . '</th>';
    }
    echo '</tr></thead><tbody>';

    if (empty($items)) {
        echo '<tr><td colspan="8">' . esc_html(__('No appointments found.', 'appointment-booking')) . '</td></tr>';
    } else {
        foreach ($items as $row) {
            $service_title = $row['service_id'] ? get_the_title((int) $row['service_id']) : __('(No service)', 'appointment-booking');
            echo '<tr>';
            echo '<td>' . esc_html((string) $row['id']) . '</td>';
            echo '<td>' . esc_html($service_title) . '</td>';
            echo '<td>' . esc_html($row['customer_name']) . '</td>';
            echo '<td>' . esc_html($row['customer_email']) . '</td>';
            echo '<td>' . esc_html($row['customer_phone']) . '</td>';
            echo '<td>' . esc_html($row['appointment_date']) . '</td>';
            echo '<td>' . esc_html(substr($row['appointment_time'], 0, 5)) . '</td>';
            echo '<td>' . esc_html($row['status']) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    // Pagination
    if ($num_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ($i = 1; $i <= $num_pages; $i++) {
            $url = add_query_arg(array('page' => 'abp_appointments', 'paged' => $i, 'status' => $status_filter));
            $class = $i === $page ? ' class="page-numbers current"' : ' class="page-numbers"';
            echo '<a' . $class . ' href="' . esc_url($url) . '">' . esc_html((string) $i) . '</a> ';
        }
        echo '</div></div>';
    }

    echo '</div>';
}

function abp_render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = get_option(ABP_SETTINGS_OPTION_KEY, array());

    if (isset($_POST['abp_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abp_settings_nonce'])), 'abp_save_settings')) {
        $settings['recipient_email'] = isset($_POST['recipient_email']) ? sanitize_email(wp_unslash($_POST['recipient_email'])) : get_option('admin_email');
        $settings['business_start'] = isset($_POST['business_start']) ? sanitize_text_field(wp_unslash($_POST['business_start'])) : '09:00';
        $settings['business_end'] = isset($_POST['business_end']) ? sanitize_text_field(wp_unslash($_POST['business_end'])) : '17:00';
        $settings['slot_interval_minutes'] = isset($_POST['slot_interval_minutes']) ? max(5, (int) $_POST['slot_interval_minutes']) : 30;
        $settings['success_message'] = isset($_POST['success_message']) ? wp_kses_post(wp_unslash($_POST['success_message'])) : __('Thank you! Your appointment has been booked. Check your email for confirmation.', 'appointment-booking');
        update_option(ABP_SETTINGS_OPTION_KEY, $settings);
        echo '<div class="updated"><p>' . esc_html(__('Settings saved.', 'appointment-booking')) . '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(__('Appointment Booking Settings', 'appointment-booking')); ?></h1>
        <form method="post">
            <?php wp_nonce_field('abp_save_settings', 'abp_settings_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="recipient_email"><?php echo esc_html(__('Recipient Email', 'appointment-booking')); ?></label></th>
                    <td><input type="email" id="recipient_email" name="recipient_email" value="<?php echo esc_attr($settings['recipient_email'] ?? get_option('admin_email')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="business_start"><?php echo esc_html(__('Business Start Time (HH:MM)', 'appointment-booking')); ?></label></th>
                    <td><input type="time" id="business_start" name="business_start" value="<?php echo esc_attr($settings['business_start'] ?? '09:00'); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="business_end"><?php echo esc_html(__('Business End Time (HH:MM)', 'appointment-booking')); ?></label></th>
                    <td><input type="time" id="business_end" name="business_end" value="<?php echo esc_attr($settings['business_end'] ?? '17:00'); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slot_interval_minutes"><?php echo esc_html(__('Slot Interval (minutes)', 'appointment-booking')); ?></label></th>
                    <td><input type="number" min="5" step="5" id="slot_interval_minutes" name="slot_interval_minutes" value="<?php echo esc_attr($settings['slot_interval_minutes'] ?? 30); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="success_message"><?php echo esc_html(__('Success Message', 'appointment-booking')); ?></label></th>
                    <td><textarea id="success_message" name="success_message" rows="3" class="large-text"><?php echo esc_textarea($settings['success_message'] ?? ''); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(__('Save Changes', 'appointment-booking')); ?>
        </form>
    </div>
    <?php
}

// Frontend assets
add_action('wp_enqueue_scripts', 'abp_enqueue_frontend_assets');
function abp_enqueue_frontend_assets(): void {
    wp_register_style('abp-frontend', ABP_PLUGIN_URL . 'assets/css/abp-frontend.css', array(), ABP_VERSION);
    wp_register_script('abp-frontend', ABP_PLUGIN_URL . 'assets/js/abp-frontend.js', array('jquery'), ABP_VERSION, true);

    $ajax_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('abp_ajax_nonce'),
        'i18n' => array(
            'select_time' => __('Select a time', 'appointment-booking'),
            'no_slots' => __('No available time slots on this date.', 'appointment-booking'),
            'booking_success' => (get_option(ABP_SETTINGS_OPTION_KEY, array())['success_message'] ?? __('Thank you! Your appointment has been booked. Check your email for confirmation.', 'appointment-booking')),
            'booking_error' => __('Sorry, this slot is no longer available. Please choose another.', 'appointment-booking'),
        ),
    );

    wp_localize_script('abp-frontend', 'ABP', $ajax_data);
}

// Shortcode: [abp_booking_form]
add_shortcode('abp_booking_form', 'abp_render_booking_form');
function abp_render_booking_form($atts = array()): string {
    wp_enqueue_style('abp-frontend');
    wp_enqueue_script('abp-frontend');

    $atts = shortcode_atts(array(
        'service_id' => 0,
    ), $atts, 'abp_booking_form');

    $selected_service_id = (int) $atts['service_id'];

    $services = get_posts(array(
        'post_type' => 'abp_service',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    ob_start();
    ?>
    <form class="abp-booking-form" data-nonce="<?php echo esc_attr(wp_create_nonce('abp_booking_nonce')); ?>">
        <div class="abp-field">
            <label><?php echo esc_html(__('Service', 'appointment-booking')); ?></label>
            <select name="service_id" required>
                <option value=""><?php echo esc_html(__('Select a service', 'appointment-booking')); ?></option>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr((string) $service->ID); ?>" <?php selected($selected_service_id, (int) $service->ID); ?>><?php echo esc_html(get_the_title($service)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Date', 'appointment-booking')); ?></label>
            <input type="date" name="appointment_date" required min="<?php echo esc_attr(gmdate('Y-m-d')); ?>" />
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Time', 'appointment-booking')); ?></label>
            <select name="appointment_time" required>
                <option value=""><?php echo esc_html(__('Select a date first', 'appointment-booking')); ?></option>
            </select>
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Your Name', 'appointment-booking')); ?></label>
            <input type="text" name="customer_name" required />
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Email', 'appointment-booking')); ?></label>
            <input type="email" name="customer_email" required />
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Phone', 'appointment-booking')); ?></label>
            <input type="tel" name="customer_phone" />
        </div>
        <div class="abp-field">
            <label><?php echo esc_html(__('Notes', 'appointment-booking')); ?></label>
            <textarea name="notes" rows="3"></textarea>
        </div>
        <div class="abp-actions">
            <button type="submit" class="abp-button"><?php echo esc_html(__('Book Appointment', 'appointment-booking')); ?></button>
        </div>
        <div class="abp-message" hidden></div>
    </form>
    <?php
    return ob_get_clean();
}

// AJAX: Fetch slots
add_action('wp_ajax_abp_get_slots', 'abp_ajax_get_slots');
add_action('wp_ajax_nopriv_abp_get_slots', 'abp_ajax_get_slots');
function abp_ajax_get_slots(): void {
    check_ajax_referer('abp_ajax_nonce', 'nonce');

    $service_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

    if ($service_id <= 0 || !abp_is_valid_date($date)) {
        wp_send_json_error(array('message' => __('Invalid input', 'appointment-booking')));
    }

    $slots = abp_generate_slots_for_date($service_id, $date);
    wp_send_json_success(array('slots' => $slots));
}

// AJAX: Submit booking
add_action('wp_ajax_abp_submit_booking', 'abp_ajax_submit_booking');
add_action('wp_ajax_nopriv_abp_submit_booking', 'abp_ajax_submit_booking');
function abp_ajax_submit_booking(): void {
    check_ajax_referer('abp_ajax_nonce', 'nonce');

    $service_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
    $time = isset($_POST['time']) ? sanitize_text_field(wp_unslash($_POST['time'])) : '';
    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $customer_email = isset($_POST['customer_email']) ? sanitize_email(wp_unslash($_POST['customer_email'])) : '';
    $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field(wp_unslash($_POST['customer_phone'])) : '';
    $notes = isset($_POST['notes']) ? wp_kses_post(wp_unslash($_POST['notes'])) : '';

    if ($service_id <= 0 || !abp_is_valid_date($date) || !abp_is_valid_time($time) || empty($customer_name) || !is_email($customer_email)) {
        wp_send_json_error(array('message' => __('Invalid submission data', 'appointment-booking')));
    }

    $result = abp_create_appointment($service_id, $date, $time, $customer_name, $customer_email, $customer_phone, $notes);
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array('message' => (get_option(ABP_SETTINGS_OPTION_KEY, array())['success_message'] ?? __('Thank you! Your appointment has been booked.', 'appointment-booking'))));
}

// Helpers
function abp_is_valid_date(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function abp_is_valid_time(string $time): bool {
    $d = DateTime::createFromFormat('H:i', $time);
    return $d && $d->format('H:i') === $time;
}

function abp_generate_slots_for_date(int $service_id, string $date): array {
    $settings = get_option(ABP_SETTINGS_OPTION_KEY, array());
    $business_start = $settings['business_start'] ?? '09:00';
    $business_end = $settings['business_end'] ?? '17:00';
    $slot_interval = (int) ($settings['slot_interval_minutes'] ?? 30);

    $capacity = (int) get_post_meta($service_id, '_abp_capacity', true);
    if ($capacity <= 0) {
        $capacity = 1;
    }

    $slots = array();

    try {
        $start_dt = new DateTime($date . ' ' . $business_start);
        $end_dt = new DateTime($date . ' ' . $business_end);
    } catch (Exception $e) {
        return $slots;
    }

    if ($end_dt <= $start_dt) {
        return $slots;
    }

    // Build all slots between start and end, step interval
    $cursor = clone $start_dt;

    while ($cursor < $end_dt) {
        $slot_time = $cursor->format('H:i');
        $available = abp_is_slot_available($service_id, $date, $slot_time, $capacity);
        $slots[] = array(
            'time' => $slot_time,
            'available' => $available,
        );
        $cursor->modify('+' . $slot_interval . ' minutes');
    }

    return $slots;
}

function abp_is_slot_available(int $service_id, string $date, string $time, int $capacity): bool {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_appointments';
    $datetime_str = $date . ' ' . $time . ':00';

    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE service_id = %d AND appointment_datetime = %s AND status IN ('pending','confirmed')",
        $service_id,
        $datetime_str
    ));

    return $count < $capacity;
}

function abp_create_appointment(int $service_id, string $date, string $time, string $customer_name, string $customer_email, string $customer_phone, string $notes) {
    if (!abp_is_slot_available($service_id, $date, $time, (int) get_post_meta($service_id, '_abp_capacity', true) ?: 1)) {
        return new WP_Error('slot_unavailable', __('Sorry, this slot is no longer available.', 'appointment-booking'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_appointments';

    $appointment_datetime = $date . ' ' . $time . ':00';

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'service_id' => $service_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'appointment_date' => $date,
            'appointment_time' => $time . ':00',
            'appointment_datetime' => $appointment_datetime,
            'status' => 'pending',
            'notes' => $notes,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        return new WP_Error('db_insert_failed', __('Could not save appointment. Please try again.', 'appointment-booking'));
    }

    $appointment_id = (int) $wpdb->insert_id;

    abp_send_notification_emails($appointment_id);

    return $appointment_id;
}

function abp_send_notification_emails(int $appointment_id): void {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_appointments';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $appointment_id), ARRAY_A);
    if (!$row) {
        return;
    }

    $service_title = $row['service_id'] ? get_the_title((int) $row['service_id']) : __('Service', 'appointment-booking');
    $admin_email = (get_option(ABP_SETTINGS_OPTION_KEY, array())['recipient_email'] ?? '') ?: get_option('admin_email');

    $when_str = $row['appointment_date'] . ' ' . substr($row['appointment_time'], 0, 5);

    // Admin email
    $subject_admin = sprintf(__('New appointment: %s on %s', 'appointment-booking'), $service_title, $when_str);
    $message_admin = sprintf(
        "Service: %s\nDate: %s\nTime: %s\nName: %s\nEmail: %s\nPhone: %s\nNotes: %s\n",
        $service_title,
        $row['appointment_date'],
        substr($row['appointment_time'], 0, 5),
        $row['customer_name'],
        $row['customer_email'],
        $row['customer_phone'],
        wp_strip_all_tags($row['notes'])
    );
    wp_mail($admin_email, $subject_admin, $message_admin);

    // Customer email
    $subject_user = sprintf(__('Your appointment: %s on %s', 'appointment-booking'), $service_title, $when_str);
    $message_user = sprintf(
        __('Hello %1$s,\n\nYour appointment request has been received. Details:\n\nService: %2$s\nDate: %3$s\nTime: %4$s\n\nWe will contact you if anything changes.\n\nThank you!', 'appointment-booking'),
        $row['customer_name'],
        $service_title,
        $row['appointment_date'],
        substr($row['appointment_time'], 0, 5)
    );
    wp_mail($row['customer_email'], $subject_user, $message_user);
}

// Admin: load minimal styles for table
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_abp_appointments') {
        wp_enqueue_style('abp-frontend');
    }
});

// End of file