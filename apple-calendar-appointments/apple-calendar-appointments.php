<?php
/*
Plugin Name: Apple Calendar Appointments
Description: Display Apple Calendar appointments on your WordPress site via a public iCal URL.
Version: 1.9.13
Requires at least: 6.0
Tested up to: 6.5
Author: OpenAI
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Create reservations table on activation
function aca_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'aca_reservations';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        phone varchar(100) NOT NULL,
        start datetime NOT NULL,
        end datetime NOT NULL,
        services text NOT NULL,
        PRIMARY KEY (id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'aca_activate');

// Enqueue front-end styles
function aca_enqueue_styles() {
    wp_enqueue_style(
        'aca-calendar',
        plugin_dir_url(__FILE__) . 'apple-calendar-appointments.css',
        [],
        '1.9.13'
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
        '1.9.13',
        true
    );
}
add_action('wp_enqueue_scripts', 'aca_enqueue_scripts');

// Enqueue scripts on the settings page
function aca_admin_enqueue_scripts($hook) {
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if($page === 'aca-settings') {
        wp_enqueue_style(
            'aca-calendar',
            plugin_dir_url(__FILE__) . 'apple-calendar-appointments.css',
            [],
            '1.9.13'
        );
        wp_enqueue_script(
            'aca-calendar-admin',
            plugin_dir_url(__FILE__) . 'apple-calendar-admin.js',
            [],
            '1.9.13',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'aca_admin_enqueue_scripts');

// Register settings
function aca_sanitize_int_array($value) {
    return array_map('intval', (array) $value);
}

function aca_sanitize_checkbox($value) {
    return $value === '1' ? '1' : '0';
}

function aca_register_settings() {
    register_setting('aca_settings_group', 'aca_ical_url', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('aca_settings_group', 'aca_work_start', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aca_settings_group', 'aca_work_end', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aca_settings_group', 'aca_lunch_start', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aca_settings_group', 'aca_lunch_end', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aca_settings_group', 'aca_days_off', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('aca_settings_group', 'aca_days_off_week', ['sanitize_callback' => 'aca_sanitize_int_array']);
    register_setting('aca_settings_group', 'aca_services', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('aca_settings_group', 'aca_enable_reservations', ['sanitize_callback' => 'aca_sanitize_checkbox']);
}
add_action('admin_init', 'aca_register_settings');

// Add settings page
function aca_add_settings_page() {
    add_options_page('Apple Calendar', 'Apple Calendar', 'manage_options', 'aca-settings', 'aca_render_settings_page');
}
add_action('admin_menu', 'aca_add_settings_page');

function aca_render_settings_page() {
    global $wpdb;
    $table = esc_sql($wpdb->prefix . 'aca_reservations');
    $reservations = $wpdb->get_results("SELECT name, phone, start, end, services FROM {$table} ORDER BY start DESC", ARRAY_A);
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
                <tr valign="top">
                    <th scope="row"><label for="aca_work_start">Workday Start</label></th>
                    <td><input type="time" id="aca_work_start" name="aca_work_start" value="<?php echo esc_attr(get_option('aca_work_start', '09:00')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="aca_work_end">Workday End</label></th>
                    <td><input type="time" id="aca_work_end" name="aca_work_end" value="<?php echo esc_attr(get_option('aca_work_end', '20:00')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="aca_lunch_start">Lunch Start</label></th>
                    <td><input type="time" id="aca_lunch_start" name="aca_lunch_start" value="<?php echo esc_attr(get_option('aca_lunch_start')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="aca_lunch_end">Lunch End</label></th>
                    <td><input type="time" id="aca_lunch_end" name="aca_lunch_end" value="<?php echo esc_attr(get_option('aca_lunch_end')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Days Off</th>
                    <td>
                        <input type="hidden" id="aca_days_off" name="aca_days_off" value="<?php echo esc_attr(get_option('aca_days_off')); ?>" />
                        <table id="aca-dayoff-table" class="widefat">
                            <thead>
                                <tr>
                                    <th>Date (YYYY-MM-DD)</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div id="aca-dayoff-form">
                            <input type="hidden" id="aca-dayoff-index" value="" />
                            <input type="date" id="aca-dayoff-date" />
                            <input type="text" id="aca-dayoff-name" placeholder="Name" />
                            <button type="button" id="aca-dayoff-add" class="button">Add Day</button>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Weekly Days Off</th>
                    <td>
                        <?php $dow = (array) get_option('aca_days_off_week', []); $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; foreach($days as $i=>$n){ ?>
                        <label><input type="checkbox" name="aca_days_off_week[]" value="<?php echo $i; ?>" <?php checked(in_array($i,$dow)); ?> /> <?php echo esc_html($n); ?></label><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Services</th>
                    <td>
                        <input type="hidden" id="aca_services" name="aca_services" value="<?php echo esc_attr(get_option('aca_services')); ?>" />
                        <table id="aca-services-table" class="widefat">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Minutes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div id="aca-service-form">
                            <input type="hidden" id="aca-service-index" value="" />
                            <input type="text" id="aca-service-name" placeholder="Name" />
                            <input type="text" id="aca-service-price" placeholder="Price" />
                            <input type="number" id="aca-service-minutes" placeholder="Minutes" min="0" />
                            <button type="button" id="aca-service-add" class="button">Add Service</button>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="aca_enable_reservations">Enable Reservations</label></th>
                    <td><input type="checkbox" id="aca_enable_reservations" name="aca_enable_reservations" value="1" <?php checked(get_option('aca_enable_reservations', '1'), '1'); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Reservations</h2>
        <table id="aca-reservations-table" class="widefat">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Services</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservations)) { foreach ($reservations as $r) { $srv = maybe_unserialize($r['services']); ?>
                <tr>
                    <td><?php echo esc_html($r['name']); ?></td>
                    <td><?php echo esc_html($r['phone']); ?></td>
                    <td><?php echo esc_html($r['start']); ?></td>
                    <td><?php echo esc_html($r['end']); ?></td>
                    <td><?php echo esc_html(implode(', ', (array)$srv)); ?></td>
                </tr>
                <?php } } else { ?>
                <tr><td colspan="5">No reservations yet.</td></tr>
                <?php } ?>
            </tbody>
        </table>
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

function aca_get_services() {
    $services_raw = get_option('aca_services');
    $services = [];
    if ($services_raw) {
        foreach (preg_split('/\r?\n/', $services_raw) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if ($parts[0] === '') continue;
            $services[$parts[0]] = [
                'name'     => $parts[0],
                'price'    => $parts[1] ?? '',
                'duration' => isset($parts[2]) ? (int) $parts[2] : 0,
            ];
        }
    }
    return $services;
}

function aca_get_days_off() {
    $raw = get_option('aca_days_off');
    $out = [];
    if ($raw) {
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if ($parts[0] === '') continue;
            $out[] = [
                'date' => $parts[0],
                'name' => $parts[1] ?? '',
            ];
        }
    }
    return $out;
}

function aca_render_events() {
    $url    = get_option('aca_ical_url');
    $events = aca_fetch_ical_events($url);
    if (empty($events)) return '<p>No events found.</p>';

    $work_start  = get_option('aca_work_start', '09:00');
    $work_end    = get_option('aca_work_end', '20:00');
    $lunch_start = get_option('aca_lunch_start');
    $lunch_end   = get_option('aca_lunch_end');
    $days_off_data  = aca_get_days_off();
    $days_off_week  = (array) get_option('aca_days_off_week', []);
    global $wpdb;
    $table = esc_sql($wpdb->prefix . 'aca_reservations');
    $reservations = $wpdb->get_results("SELECT start, end, services FROM {$table}", ARRAY_A);

    $services = array_values(aca_get_services());

    $formatted = [];
    $existing  = [];
    foreach ($events as $e) {
        if (empty($e['DTSTART'])) continue;
        $start = aca_format_ical_date($e['DTSTART']);
        $end   = !empty($e['DTEND']) ? aca_format_ical_date($e['DTEND']) : null;
        $formatted[] = [
            'title' => '',
            'start' => $start,
            'end'   => $end,
            'color' => 'gray',
        ];
        $existing[$start . '|' . $end] = true;
    }

    $closed = [];
    if ($lunch_start && $lunch_end) {
        $week_off  = array_map('intval', $days_off_week);
        $days      = array_values(array_diff(range(0, 6), $week_off));
        if (!empty($days)) {
            $dates_off = array_flip(array_map('trim', array_column($days_off_data, 'date')));
            $startDate = new DateTime('now', new DateTimeZone('UTC'));
            $startDate->modify('-1 year');
            $endDate = new DateTime('now', new DateTimeZone('UTC'));
            $endDate->modify('+2 years');
            for ($d = clone $startDate; $d <= $endDate; $d->modify('+1 day')) {
                $date = $d->format('Y-m-d');
                if (in_array($d->format('w'), $week_off, true)) continue;
                if (isset($dates_off[$date])) continue;
                $closed[] = [
                    'title'     => 'Lunch Break',
                    'display'   => 'background',
                    'start'     => $date . 'T' . $lunch_start,
                    'end'       => $date . 'T' . $lunch_end,
                    'className' => 'aca-closed',
                    'color'     => '#ffeaea',
                ];
            }
        }
    }

    if (!empty($days_off_week)) {
        $closed[] = [
            'title'      => 'Day Off',
            'display'    => 'background',
            'daysOfWeek' => array_map('intval', $days_off_week),
            'className'  => 'aca-closed',
            'color'      => '#ffeaea',
        ];
    }

    if (!empty($days_off_data)) {
        foreach ($days_off_data as $off) {
            $d = $off['date'];
            $name = $off['name'] !== '' ? $off['name'] : 'Day Off';
            $closed[] = [
                'title'     => $name,
                'start'     => $d . 'T00:00:00',
                'end'       => $d . 'T23:59:59',
                'allDay'    => true,
                'display'   => 'background',
                'className' => 'aca-closed',
                'color'     => '#ffeaea',
                'overlap'   => false,
            ];
        }
    }

    if (!empty($reservations)) {
        foreach ($reservations as $res) {
            if (empty($res['start']) || empty($res['end'])) continue;
            $key   = $res['start'] . '|' . $res['end'];
            $color = isset($existing[$key]) ? 'green' : 'gray';
            $formatted[] = [
                'title' => 'Reserved',
                'start' => $res['start'],
                'end'   => $res['end'],
                'color' => $color,
            ];
        }
    }

    wp_enqueue_script('aca-calendar');
    wp_localize_script('aca-calendar', 'acaEvents', $formatted);
    wp_localize_script('aca-calendar', 'acaOptions', [
        'workStart'    => $work_start,
        'workEnd'      => $work_end,
        'closedEvents' => $closed,
        'services'     => $services,
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'reservationsEnabled' => get_option('aca_enable_reservations', '1') ? true : false,
    ]);

    ob_start();
    echo '<div id="aca-calendar-header">';
    echo '<div id="aca-calendar-title"></div>';
    echo '<div id="aca-calendar-controls">';
    echo '<button type="button" data-view="day">Day</button>';
    echo '<button type="button" data-view="week">Week</button>';
    echo '<button type="button" data-view="month">Month</button>';
    echo '<button type="button" data-nav="prev">&lt;</button>';
    echo '<button type="button" data-nav="today">Today</button>';
    echo '<button type="button" data-nav="next">&gt;</button>';
    echo '</div>';
    echo '</div>';
    echo '<div id="aca-calendar"></div>';
    return ob_get_clean();
}
add_shortcode('apple_calendar_appointments', 'aca_render_events');

// Handle reservation submission via AJAX
function aca_save_reservation_callback() {
    $start    = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
    $services = isset($_POST['services']) ? array_map('sanitize_text_field', (array) $_POST['services']) : [];
    $name     = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone    = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

    if (!$start || empty($services) || !$name || !$phone) {
        wp_send_json_error();
    }

    $defs = aca_get_services();
    $minutes = 0;
    foreach ($services as $srv) {
        if (isset($defs[$srv])) {
            $minutes += (int) $defs[$srv]['duration'];
        }
    }
    if ($minutes <= 0) {
        wp_send_json_error();
    }

    $end_ts = strtotime($start) + $minutes * 60;
    $end    = gmdate('c', $end_ts);

    global $wpdb;
    $table = esc_sql($wpdb->prefix . 'aca_reservations');
    $wpdb->insert(
        $table,
        [
            'name'     => $name,
            'phone'    => $phone,
            'start'    => gmdate('Y-m-d H:i:s', strtotime($start)),
            'end'      => gmdate('Y-m-d H:i:s', $end_ts),
            'services' => maybe_serialize($services),
        ],
        ['%s','%s','%s','%s','%s']
    );

    $to = get_option('admin_email');
    $subject = 'New Reservation Request';
    $body  = "Name: $name\n";
    $body .= "Phone: $phone\n";
    $body .= "Start: $start\n";
    $body .= "End: $end\n";
    $body .= "Services: " . implode(', ', $services);
    wp_mail($to, $subject, $body);

    wp_send_json_success(['end' => $end]);
}
add_action('wp_ajax_aca_save_reservation', 'aca_save_reservation_callback');
add_action('wp_ajax_nopriv_aca_save_reservation', 'aca_save_reservation_callback');

