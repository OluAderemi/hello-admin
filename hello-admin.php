<?php
/*
Plugin Name: Hello Admin Pro
Description: Saves messages with timestamps, reminder dates, and sends email alerts when reminders are due.
Version: 1.4
Author: Pelumi Aderemi
*/

add_action('admin_menu', 'hello_admin_pro_menu');
add_action('hello_admin_check_reminders', 'hello_admin_check_due_reminders');

// Schedule the reminder checker to run every 10 minutes
add_action('init', function () {
    if (!wp_next_scheduled('hello_admin_check_reminders')) {
        wp_schedule_event(time(), 'ten_minutes', 'hello_admin_check_reminders');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('hello_admin_check_reminders');
});

// Add custom interval to cron
add_filter('cron_schedules', function($schedules) {
    $schedules['ten_minutes'] = [
        'interval' => 600, // 10 minutes
        'display' => __('Every 10 Minutes')
    ];
    return $schedules;
});

function hello_admin_pro_menu() {
    add_menu_page(
        'Hello Admin Pro',
        'Hello Admin Pro',
        'manage_options',
        'hello-admin-pro',
        'hello_admin_pro_page_content',
        'dashicons-smiley',
        20
    );
}

function hello_admin_pro_page_content() {
    // Handle delete request
    if (isset($_GET['hap_delete']) && is_numeric($_GET['hap_delete'])) {
        $messages = get_option('hap_saved_messages', []);
        $delete_index = (int) $_GET['hap_delete'];
        if (isset($messages[$delete_index])) {
            unset($messages[$delete_index]);
            $messages = array_values($messages); // Re-index
            update_option('hap_saved_messages', $messages);
            echo '<div class="updated"><p><strong>Message deleted.</strong></p></div>';
        }
    }

    if (isset($_POST['hap_message']) && isset($_POST['hap_reminder_date']) && isset($_POST['hap_reminder_time'])) {
        check_admin_referer('hap_save_message');
        $message = sanitize_text_field($_POST['hap_message']);
        $date = sanitize_text_field($_POST['hap_reminder_date']);
        $time = sanitize_text_field($_POST['hap_reminder_time']);
        $reminder = "$date $time";

        $timestamp = current_time('mysql');

        // Retrieve existing messages
        $messages = get_option('hap_saved_messages', []);
        
        // Add the new message to the array
        $messages[] = [
            'message' => $message,
            'timestamp' => $timestamp,
            'reminder' => $reminder,
            'sent' => false,
        ];

        // Sort the messages by timestamp (newest first)
        usort($messages, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Save the updated messages array
        update_option('hap_saved_messages', $messages);

        echo '<div class="updated"><p><strong>Message saved!</strong></p></div>';
    }

    // Retrieve the saved messages to display
    $messages = get_option('hap_saved_messages', []);

    // Sort messages again (newest first)
    usort($messages, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    echo '<div class="wrap">';
    echo '<h1>Hello Admin Pro</h1>';
    echo '<form method="post">';
    wp_nonce_field('hap_save_message');
    echo '<label for="hap_message">Enter a message:</label><br>';
    echo '<input type="text" id="hap_message" name="hap_message" value="" style="width:300px;"><br><br>';

    echo '<label for="hap_reminder_date">Set reminder date:</label><br>';
    echo '<input type="text" id="hap_reminder_date" name="hap_reminder_date" style="width:200px;" placeholder="Select a date"><br><br>';

    echo '<label for="hap_reminder_time">Set reminder time:</label><br>';
    echo '<input type="time" id="hap_reminder_time" name="hap_reminder_time" style="width:200px;"><br><br>';

    echo '<input type="submit" class="button button-primary" value="Save Message">';
    echo '</form>';

    echo '<h2>Saved Messages:</h2>';
    if ($messages) {
        echo '<ul>';
        foreach ($messages as $index => $entry) {
            if (is_array($entry)) {
                $msg = esc_html($entry['message']);
                $time = esc_html($entry['timestamp']);
                $reminder = esc_html($entry['reminder']);
                $sent = isset($entry['sent']) && $entry['sent'];

                echo "<li><strong>Message:</strong> $msg<br>";
                echo "<strong>Saved at:</strong> $time<br>";
                if ($reminder) {
                    echo "<strong>Reminder:</strong> $reminder<br>";
                    if ($sent) {
                        echo "<span style='color:gray;'>Reminder sent.</span>";
                    } else {
                        $now = current_time('mysql');
                        echo $now >= $reminder
                            ? "<span style='color:red;'>Reminder due!</span>"
                            : "<span style='color:green;'>Reminder scheduled.</span>";
                    }
                }
                $delete_url = admin_url('admin.php?page=hello-admin-pro&hap_delete=' . $index);
                echo "<br><a href='" . esc_url($delete_url) . "' onclick='return confirm(\"Are you sure you want to delete this message?\")'>Delete</a>";
                echo "</li><br>";
            }
        }
        echo '</ul>';
    } else {
        echo '<p>No messages saved yet.</p>';
    }
    echo '</div>';
}

function hello_admin_check_due_reminders() {
    $messages = get_option('hap_saved_messages', []);
    $updated = false;
    $now = current_time('mysql');
    $admin_email = get_option('admin_email');

    foreach ($messages as $index => $entry) {
        if (is_array($entry) && !empty($entry['reminder']) && !$entry['sent']) {
            if ($now >= $entry['reminder']) {
                $subject = 'Reminder: ' . $entry['message'];
                $body = "This is a reminder you set in Hello Admin Pro:\n\n"
                      . "Message: {$entry['message']}\n"
                      . "Saved at: {$entry['timestamp']}\n"
                      . "Reminder time: {$entry['reminder']}\n";

                wp_mail($admin_email, $subject, $body);
                $entry['sent'] = true;
                $messages[$index] = $entry;
                $updated = true;
            }
        }
    }

    if ($updated) {
        update_option('hap_saved_messages', $messages);
    }
}

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
});

add_action('admin_footer', function() {
    echo '
    <script>
    jQuery(document).ready(function($){
        $("#hap_reminder_date, #hap_reminder_time").on("keydown paste", function(e) {
            e.preventDefault();
        });

        $("#hap_reminder_date").datepicker({
            dateFormat: "dd MM yy",
            minDate: 0,
            changeMonth: true,
            changeYear: true
        });
    });
    </script>
    ';
});
