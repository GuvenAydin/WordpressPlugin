# WordPress Plugin: Apple Calendar Appointments

This plugin displays your Apple Calendar appointments on your WordPress site using a public iCal URL. It has been tested with WordPress 6.5 and version 1.7.1 of the plugin. The plugin files live inside the `apple-calendar-appointments` folder and include styles and scripts for an interactive calendar.
Separate JavaScript files are included for the public calendar and the admin services table.

## Installation
1. Upload the entire `apple-calendar-appointments` folder to your WordPress `wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Apple Calendar** and enter the public iCal URL of your Apple Calendar.
   The calendar must be publicly shared for WordPress to fetch the events.

## Usage

In the settings page you can define your daily working hours, a lunch break, specific days off and weekly days off such as weekends. These settings are used to highlight unavailable time periods on the calendar so visitors see when you are closed.

### Services and reservations
Services are managed from the settings page in a small table where you can add, edit or remove each entry. Each service has a name, price and duration in minutes. Visitors must enter their name and phone number and select one or more services when reserving a slot. The end time is calculated from the total service minutes. Reserved times appear in **gray** until you add the same appointment to your iCloud calendar, after which the reservation turns **green** to show it is confirmed.
The calendar hides the **All Day** row for a cleaner layout, and reservations are disabled while viewing the **Month** calendar.

Navigation buttons let visitors move to the previous or next day, week or month depending on the current view.

Lunch breaks and days off appear with a pink crosshatch background so visitors clearly see when you are closed. A **Today** button lets you jump back to the current date at any time.

Past dates cannot be selected for reservations, and lunch breaks are omitted on days that are completely marked as days off.

### Working hours and days off
In the settings page you can optionally define your daily working hours, a lunch break, and any specific days off. These settings are used to highlight unavailable time periods on the calendar so visitors see when you are closed.


