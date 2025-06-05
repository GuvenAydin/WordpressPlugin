<?php
/*
Plugin Name: Apple Calendar Appointments
Description: Display Apple Calendar appointments on your WordPress site via a public iCal URL.
Version: 1.1.0
Requires at least: 6.0
Tested up to: 6.5
Author: OpenAI
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Enqueue front-end styles
function aca_enqueue_styles() {
    wp_enqueue_style(
        'aca-calendar',
        plugin_dir_url(__FILE__) . 'apple-calendar-appointments.css',
        [],
        '1.1.0'
    );
}
add_action('wp_enqueue_scripts', 'aca_enqueue_styles');

// Enqueue JavaScript dependencies
function aca_enqueue_scripts() {
    wp_enqueue_script(
        'fullcalendar',
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js',
        [],
        '6.1.8',
        true
    );
    wp_enqueue_script(
        'aca-calendar',
        plugin_dir_url(__FILE__) . 'apple-calendar-appointments.js',
        ['fullcalendar'],
        '1.1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'aca_enqueue_scripts');

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
    $lines  = preg_split('/\r?\n/', $ical);

    // join folded lines that begin with a space
    $unfolded = [];
    foreach ($lines as $line) {
        if ($line === '') continue;
        if (isset($unfolded[count($unfolded)-1]) && preg_match('/^[ \t]/', $line)) {
            $unfolded[count($unfolded)-1] .= ltrim($line);
        } else {
            $unfolded[] = $line;
        }
    }

    $event = null;
    $lastKey = '';
    foreach ($unfolded as $line) {
        $line = trim($line);
        if ($line === 'BEGIN:VEVENT') {
            $event = [];
        } elseif ($line === 'END:VEVENT') {
            if ($event) $events[] = $event;
            $event = null;
        } elseif ($event !== null) {
            if (strpos($line, ':') !== false) {
                list($prop, $value) = explode(':', $line, 2);
                $key = strtoupper($prop);
                // strip parameters after semicolon
                if (strpos($key, ';') !== false) {
                    list($key) = explode(';', $key, 2);
                }
                $event[$key] = $value;
                $lastKey = $key;
            } elseif ($lastKey) {
                $event[$lastKey] .= $line;
            }
        }
    }
    return $events;
}

function aca_format_ical_date($v) {
    $ts = strtotime($v);
    return $ts ? gmdate('c', $ts) : $v;
}

function aca_render_events() {
    $url    = get_option('aca_ical_url');
    $events = aca_fetch_ical_events($url);
    if (empty($events)) return '<p>No events found.</p>';

    $formatted = [];
    foreach ($events as $e) {
        if (empty($e['DTSTART']) || empty($e['SUMMARY'])) continue;
        $formatted[] = [
            'title' => $e['SUMMARY'],
            'start' => aca_format_ical_date($e['DTSTART']),
            'end'   => !empty($e['DTEND']) ? aca_format_ical_date($e['DTEND']) : null,
        ];
    }

    wp_enqueue_script('aca-calendar');
    wp_localize_script('aca-calendar', 'acaEvents', $formatted);

    ob_start();
    echo '<div id="aca-calendar-controls">';
    echo '<button type="button" data-view="day">Day</button>';
    echo '<button type="button" data-view="week">Week</button>';
    echo '<button type="button" data-view="month">Month</button>';
    echo '</div>';
    echo '<div id="aca-calendar"></div>';
    return ob_get_clean();
}
add_shortcode('apple_calendar_appointments', 'aca_render_events');

