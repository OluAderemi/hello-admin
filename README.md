# Hello Admin

**Version:** 1.0  
**Author:** Pelumi Aderemi  

## Description

Hello Admin Pro is a WordPress admin plugin that allows you to:

- Save custom messages with timestamps.
- Set reminder dates and times for each message.
- Receive email notifications when reminders are due.

Perfect for admin-only notes, quick to-dos, or time-sensitive alerts — all managed right from your WordPress dashboard.

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress Plugins menu.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Hello Admin Pro** in your admin sidebar.

---

## Email Configuration (REQUIRED)

This plugin uses WordPress's `wp_mail()` function to send reminder emails.

To ensure emails are sent reliably:

1. Install the [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) plugin.
2. Activate and configure it with your preferred email provider (e.g. Gmail, SMTP, SendGrid).
3. Test that emails are working correctly in **WP Mail SMTP > Settings**.

---

## Reminder Cron Job

The plugin checks for due reminders every **10 minutes** using WordPress's cron system.

---

## Uninstall

When deactivated, the scheduled reminder job is cleared automatically.  
To remove all saved messages, delete the plugin via the **Plugins** menu.

---

## Notes

- Only administrators can access and manage messages.
- Messages are saved in WordPress options (`hap_saved_messages`).
- Each reminder is marked as sent after an email is triggered to avoid duplicates.

---

## Feedback & Contributions

Feel free to fork, submit issues, or contribute improvements via GitHub!

---

## Roadmap Ideas

- Email reminders to multiple users.
- Categorized or tagged messages.
- Optional recurring reminders.
- Export messages to CSV.

---

