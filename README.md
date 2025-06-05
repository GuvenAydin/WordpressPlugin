# WordPress Plugin: Apple Calendar Appointments

This plugin displays your Apple Calendar appointments on your WordPress site using a public iCal URL. It has been tested with WordPress 6.5 and version 1.2.0 of the plugin. The plugin files live inside the `apple-calendar-appointments` folder and include styles and scripts for an interactive calendar.

## Installation
1. Upload the entire `apple-calendar-appointments` folder to your WordPress `wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Apple Calendar** and enter the public iCal URL of your Apple Calendar.
   The calendar must be publicly shared for WordPress to fetch the events.

## Usage
Insert the shortcode `[apple_calendar_appointments]` into any post or page. The calendar includes **Day**, **Week** and **Month** views so you can browse your appointments interactively.

### Working hours and days off
In the settings page you can optionally define your daily working hours, a lunch break, and any specific days off. These settings are used to highlight unavailable time periods on the calendar so visitors see when you are closed.



