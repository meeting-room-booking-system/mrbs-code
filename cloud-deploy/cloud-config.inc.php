<?php
namespace MRBS;
use PDO;

// Database settings
// We'll use environment variables for sensitive data
$dbsys = "mysqli";  // Changed to match local config

// For Cloud SQL with Unix socket, we need to specify the full socket path
$socket_dir = getenv('DB_HOST');
$instance_connection_name = getenv('INSTANCE_CONNECTION_NAME');

if ($instance_connection_name) {
    // We're in Cloud Run, use Unix socket
    $db_host = $socket_dir;  // Use the full socket path
} else {
    // Local development
    $db_host = getenv('DB_HOST');
}

$db_database = getenv('DB_NAME');
$db_login = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

// When using Unix socket with Cloud SQL, we don't need a port
$db_port = null;

// Use SSL for database connection in production
$db_ssl = false;  // We're using Unix socket, no need for SSL

// Database connection options
$db_options = array(
    'mysql' => array()  // Initialize the mysql key to avoid undefined key error
);

// Set table prefix
$db_tbl_prefix = "mrbs_";

// Timezone and locale settings
$timezone = "America/New_York";
$weekstarts = 0;

// Site settings
$mrbs_admin = "admin";
$mrbs_admin_email = getenv('ADMIN_EMAIL');

// Authentication settings
$auth["type"] = "db";
$auth["session"] = "php";
$auth["admin"][] = "admin";

// Security settings (cloud-specific)
$secure_headers = true;
$strict_transport_security = true;

// Email settings
$mail_settings['smtp_host'] = getenv('SMTP_HOST') ?: '';
$mail_settings['smtp_port'] = getenv('SMTP_PORT') ?: 587;
$mail_settings['smtp_username'] = getenv('SMTP_USER') ?: '';
$mail_settings['smtp_password'] = getenv('SMTP_PASSWORD') ?: '';

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

// Debug settings (disable in production)
if (getenv('ENVIRONMENT') === 'production') {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    $debug = false;
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    $debug = true;
}
