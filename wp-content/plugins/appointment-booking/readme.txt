=== Appointment Booking Plugin ===
Contributors: you
Tags: appointments, booking, schedule
Requires at least: 5.6
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight appointment booking plugin with services, time slots, and email notifications.

== Description ==

- Custom post type `abp_service` for defining bookable services (duration and capacity per slot)
- Shortcode `[abp_booking_form]` to display a booking form
- AJAX-powered slot availability and booking submission
- Admin pages to view appointments and configure settings

== Installation ==

1. Upload the `appointment-booking` folder to your site's `wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Create one or more Services under 'Services'.
4. Add the shortcode `[abp_booking_form]` to any page.

== Settings ==

- Appointments → Settings:
  - Recipient Email
  - Business Start/End Time (HH:MM)
  - Slot Interval (minutes)
  - Success Message

== Shortcodes ==

- `[abp_booking_form]` – renders the booking form
  - Attributes:
    - `service_id` (optional): preselect a specific service by ID

== Uninstalling ==

Uninstalling the plugin will drop the `wp_abp_appointments` table and remove the `abp_settings` option.

== Changelog ==

= 1.0.0 =
* Initial release