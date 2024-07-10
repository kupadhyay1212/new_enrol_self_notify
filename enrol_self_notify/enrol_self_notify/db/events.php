<?php
$observers = array(
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'local_enrol_self_notify\observer::user_enrolment_created',
    ),

);
