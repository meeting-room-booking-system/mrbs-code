<?php
namespace MRBS;

// Database settings
$dbsys = "mysqli";
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_database = getenv('DB_NAME') ?: 'mrbs';
$db_login = getenv('DB_USER') ?: 'mrbs_user';
$db_password = getenv('DB_PASSWORD') ?: 'mrbs-db-user-2025';
$db_port = getenv('DB_PORT') ?: 3306;

// Set table prefix
$db_tbl_prefix = "mrbs_";

// Mail settings
$mrbs_admin_email = getenv('ADMIN_EMAIL') ?: 'derek.crager@gmail.com';
$mail_settings['smtp_host'] = getenv('SMTP_HOST') ?: '';
$mail_settings['smtp_port'] = getenv('SMTP_PORT') ?: 587;
$mail_settings['smtp_username'] = getenv('SMTP_USER') ?: '';
$mail_settings['smtp_password'] = getenv('SMTP_PASSWORD') ?: '';

// General settings
$timezone = "America/New_York";
$weekstarts = 0;

// Authentication settings
$auth["type"] = "db";
$auth["session"] = "php";
$auth["admin"][] = "admin";
$auth["user"]["admin"] = "secret";  // Local development only

// Security settings (relaxed for local development)
$secure_headers = false;
$strict_transport_security = false;
$db_ssl = false;

// Area settings
$area_list_format = "select";

// Entry settings
$resolution = 1800;
$default_duration = 3600;
$default_duration_all_day = TRUE;

// Display settings
$default_view = "month";
$view_week_number = TRUE;
$times_along_top = FALSE;
$highlight_past_time = TRUE;
$show_plus_link = TRUE;

// Calendar settings
$monthly_view_entries_details = TRUE;

// Custom Fields for Room Features
$custom_fields['room']['features'] = array(
  'label'         => "Room Features",
  'type'          => 'custom',
  'custom_html'   => function($data) {
    $features = array(
      'projector'   => "Projector",
      'whiteboard'  => "Whiteboard",
      'tv'         => "TV/Display",
      'computer'   => "Computer Workstation",
      'phone'      => "Conference Phone",
      'webcam'     => "Video Conference",
      'accessible' => "Wheelchair Accessible"
    );
    
    $html = '<div class="room-features">';
    foreach ($features as $key => $label) {
      $checked = !empty($data['features'][$key]) ? ' checked' : '';
      $html .= '<div class="checkbox">';
      $html .= '<label>';
      $html .= '<input type="checkbox" name="features[' . $key . ']"' . $checked . '>';
      $html .= $label;
      $html .= '</label>';
      $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
  }
);

// Vocabulary overrides and types
$vocab_override['en']['type.'] = "Room Type";
$booking_types = array(
    'I' => "Internal",
    'E' => "External"
);

// Make custom fields visible on forms
$edit_entry_field_order = array('name', 'description', 'start_time', 'end_time', 'room_id', 'type', 'confirmation_status');

// Room fields order
$room_field_order = array('name', 'description', 'capacity', 'features');

// Debug settings (enabled for local development)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
$debug = true;
