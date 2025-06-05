<?php
/*
Plugin Name: Apple Calendar Appointments
Description: Display Apple Calendar appointments on your WordPress site via a public iCal URL.
Version: 1.0.1
Requires at least: 6.0
Tested up to: 6.5
Author: OpenAI
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register settings
function aca_register_settings() {
    register_setting('aca_settings_group', 'aca_ical_url');
}
add_action('admin_init', 'aca_register_settings');

// Add settings page
function aca_add_settings_page() {
    add_options_page('Apple Calendar', 'Apple Calendar', 'manage_options', 'aca-settings', 'aca_render_settings_page');
}
add_action('admin_menu', 'aca_add_settings_page');

function aca_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Apple Calendar Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('aca_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr valign="top">
                    <th scope="row"><label for="aca_ical_url">Public iCal URL</label></th>
                    <td><input type="text" id="aca_ical_url" name="aca_ical_url" value="<?php echo esc_attr(get_option('aca_ical_url')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Shortcode to display appointments
function aca_fetch_ical_events($url) {
    if (empty($url)) return [];

    $response = wp_remote_get($url);
    if (is_wp_error($response)) return [];

    $body = wp_remote_retrieve_body($response);
    return aca_parse_ical($body);
}

function aca_parse_ical($ical) {
    $events = [];
    $lines = preg_split('/\r?\n/', $ical);
    $event = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === 'BEGIN:VEVENT') {
            $event = [];
        } elseif ($line === 'END:VEVENT') {
            if ($event) $events[] = $event;
            $event = null;
        } elseif ($event !== null) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = strtoupper($key);
                $event[$key] = $value;
            }
        }
    }
    return $events;
}

function aca_render_events() {
    $url = get_option('aca_ical_url');
    $events = aca_fetch_ical_events($url);
    if (empty($events)) return '<p>No events found.</p>';

    ob_start();
    echo '<table class="widefat fixed" style="max-width:600px">';
    echo '<thead><tr><th>Date</th><th>Summary</th></tr></thead><tbody>';
    foreach ($events as $e) {
        if (empty($e['DTSTART']) || empty($e['SUMMARY'])) continue;
        $date = date('Y-m-d H:i', strtotime($e['DTSTART']));
        echo '<tr><td>' . esc_html($date) . '</td><td>' . esc_html($e['SUMMARY']) . '</td></tr>';
    }
    echo '</tbody></table>';
    return ob_get_clean();
}
add_shortcode('apple_calendar_appointments', 'aca_render_events');

