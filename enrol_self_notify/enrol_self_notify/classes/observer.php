<?php

/**
 * Enrol Self Notify
 *
 * @package    enrol_self_notify
 * @author     Pratik K Lalan
 * @copyright  2024 Pratik K Lalan <lalan.pratik755@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

namespace local_enrol_self_notify;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        // Perform actions when a user is enrolled in a course.
        $user = $event->get_record_snapshot('user', $event->userid);
        $course = $event->get_record_snapshot('course', $event->courseid);
    
        // Check if the enrollment was triggered by self-enrollment or admin enrollment.
        $self_enrolment = $event->other['enrol'] === 'self';
    
        // Example: Send notification to teachers.
        self::notify_teachers($user, $course, $self_enrolment);

        // Notify students if it was an admin or teacher enrollment.
        if (!$self_enrolment) {
            self::notify_student($event);
        }
    }
    
    private static function notify_teachers($user, $course, $self_enrolment = false) {
        global $DB;
    
        // If this is a self-enrollment by a student, send the email notification to the course assigned teacher.
        if ($self_enrolment) {
             // Get the context of the course.
            $context = \context_course::instance($course->id);

            // Get all teachers of the course.
            $teachers = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.lastname, u.firstname');

            // Iterate through each teacher and send notification email.
            foreach ($teachers as $teacher) {
                // Check if the teacher is assigned to the course.
                if (is_enrolled($context, $teacher->id)) {
                    $html = '';
                    $html .= '<h4>Dear ' . $teacher->firstname . ',</h4>';
                    $html .= '<p>A new user, ' . $user->firstname . ', has been assigned to the course, ' . $course->fullname . '.</p>';
                    $html .= '<p>Regards,<br>Leapfrog technologies<br>Thank you.</p>';

                    //email_to_user($teacher, $user, 'To-teacher-New Student Enrolled', "A new student, {$user->firstname} {$user->lastname}, has self-enrolled in your course: {$course->fullname}.");
                    email_to_user($teacher, $user, 'New Student Enrolled', $html, '', '', '', true);

                }
            }
            // Exit the method after sending the notifications.
            return;

        }

        // Below code could be managed to send the notification on manual enrollement from admin to teacher.

        // // If it's not a self-enrollment, and it's an admin enrollment, send notifications as before.
        // // Check if there is an assigned teacher for the course.
        // $teacher = $DB->get_record_sql("SELECT u.*
        //                                 FROM {user} u
        //                                 INNER JOIN {role_assignments} ra ON ra.userid = u.id
        //                                 INNER JOIN {context} ctx ON ctx.id = ra.contextid
        //                                 WHERE ctx.instanceid = :courseid
        //                                 AND ra.roleid = :roleid",
        //                                 ['courseid' => $course->id, 'roleid' => $DB->get_field('role', 'id', ['shortname' => 'editingteacher'])]);
    
        // // If there is an assigned teacher, send notification email to that teacher.
        // if ($teacher) {
        //     email_to_user($teacher, $user, 'from-admin-to-teacher-New Student Enrolled', "A new student, {$user->firstname} {$user->lastname}, has been assigned to your course: {$course->fullname}.");
        // } else {
        //     // If no teacher is assigned, notify the admin instead.
        //     $admins = get_admins();
        //     foreach ($admins as $admin) {
        //         // Send notification email to admin.
        //         email_to_user($admin, $user, 'Toa admin - New Student Enrolled', "A new student, {$user->firstname} {$user->lastname}, has been assigned to the course: {$course->fullname}.");
        //     }
        // }
    }    
    
    // Below function to send notification on manual enrolment
    private static function notify_student(\core\event\user_enrolment_created $event) {
        global $DB;
    
        // Get the user being enrolled.
        $user = $event->get_record_snapshot('user', $event->relateduserid);
    
        // Get the course information.
        $course = $event->get_record_snapshot('course', $event->courseid);
    
        // Get the context of the course.
        $context = \context_course::instance($course->id);
    
        // Query the database to find the admin or teacher who enrolled the user.
        $admin_or_teacher = $DB->get_record_sql("
                                                SELECT u.*
                                                FROM {user} u
                                                JOIN {role_assignments} ra ON ra.userid = u.id
                                                JOIN {context} ctx ON ctx.id = ra.contextid
                                                WHERE ctx.instanceid = :courseid
                                                AND ra.userid = :userid
                                                ORDER BY ra.timemodified DESC
                                                LIMIT 1
                                                ", ['courseid' => $course->id, 'userid' => $event->userid]);
    
        if ($admin_or_teacher) {
            // Construct the HTML content for the email.
            $html = '';
            $html .= '<h4>Dear ' . $user->firstname . ',</h4>';
            $html .= '<p>You have been enrolled in the course, ' . $course->fullname . ', by ' . $admin_or_teacher->firstname . ' ' . $admin_or_teacher->lastname . '.</p>';
            $html .= '<p>Regards,<br>Leapfrog Technologies<br>Thank you.</p>';
    
            // Send the email notification to the student from the admin's account.
            $admin_or_teacher_user = \core_user::get_user($admin_or_teacher->id);
            $result = email_to_user($user, $admin_or_teacher_user, 'Course Enrollment Notification', strip_tags($html), $html, '', '', true, $admin_or_teacher_user->firstname);
    
            // Log email sending status.
            if ($result) {
                error_log("Email successfully sent to {$user->email} for course {$course->fullname} by {$admin_or_teacher_user->email}");
            } else {
                error_log("Failed to send email to {$user->email} for course {$course->fullname} by {$admin_or_teacher_user->email}");
            }
        } else {
            error_log("No admin or teacher found for course {$course->fullname}");
        }
    }

}

