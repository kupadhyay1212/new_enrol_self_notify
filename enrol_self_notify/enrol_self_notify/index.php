<?php
/**
 * Self Enrollment Notification
 *
 * @package    local_enrol_self_notify
 * @author     Pratik K Lalan
 * @copyright  2024 Pratik K Lalan <lalan.pratik755@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

// Include Moodle configuration file.
require_once __DIR__ . '/../../config.php';

// Check if the user is logged in.
require_login();

// Check if the user is a site administrator.
if (!is_siteadmin()) {
    // Redirect to a different page or display an error message.
    // You can customize this behavior based on your requirements.
    redirect(new moodle_url('/'));
}

// Set up page parameters.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/enrol_self_notify/index.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_enrol_self_notify'));
$PAGE->navbar->add(get_string('pluginname', 'local_enrol_self_notify'));

// Add your plugin-specific functionality here.
// For example, you may want to display a list of recent self-enrollments or provide options for configuring the plugin.

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_enrol_self_notify'));
// Add your plugin content here.
echo $OUTPUT->footer();
