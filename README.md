# WordPress Plugin: Apple Calendar Appointments

This plugin displays your Apple Calendar appointments on your WordPress site using a public iCal URL. It has been tested with WordPress 6.5 and version 1.4.0 of the plugin. The plugin files live inside the `apple-calendar-appointments` folder and include styles and scripts for an interactive calendar.

## Installation
1. Upload the entire `apple-calendar-appointments` folder to your WordPress `wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Apple Calendar** and enter the public iCal URL of your Apple Calendar.
   The calendar must be publicly shared for WordPress to fetch the events.

## Usage

In the settings page you can define your daily working hours, a lunch break, specific days off and weekly days off such as weekends. These settings are used to highlight unavailable time periods on the calendar so visitors see when you are closed.

### Services and reservations
You can list your available services (one per line as `Name|Price|Minutes`) in the settings page. Visitors must enter their name and phone number and select one or more services when reserving a slot. The end time is calculated from the total service minutes. Reserved times appear in **gray** until you add the same appointment to your iCloud calendar, after which the reservation turns **green** to show it is confirmed.
### Working hours and days off
In the settings page you can optionally define your daily working hours, a lunch break, and any specific days off. These settings are used to highlight unavailable time periods on the calendar so visitors see when you are closed.


