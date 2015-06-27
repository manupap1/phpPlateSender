<?php

/* *****************************************************************************
 * Copyright (C) 2015 Emmanuel Papin <manupap01@gmail.com>
 *
 * Authors: Emmanuel Papin <manupap01@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston MA 02110-1301, USA.
 * ****************************************************************************/


// Root directory
define('__ROOT__', dirname(__FILE__));

// Load configuration and required libraries
require_once(__ROOT__ . "/config.php");
require_once(__ROOT__ . "/default.php");
require_once(__ROOT__ . "/utils.php");
require_once(__ROOT__ . "/logger.php");
require_once(__PHPMAILER_ROOT__ . "/class.phpmailer.php");

// Some sanity checks
if (empty($argv[1]) || empty($argv[2])) {
    write_log(translate("MissingArgument") . translate("commaExit"));
    exit(1);
}
if (!is_bool($enable_notifications)) {
    write_log("'$enable_notifications' " . translate("isNotAValidValueFor") .
        " \$enable_notifications" . translate("commaExit"));
}
if (!is_int($max_plates) || ($max_plates < 1)) {
    write_log("'$max_plates' " . translate("isNotAValidValueFor") .
        " \$max_plates" . translate("commaExit"));
    exit(1);
}
if (!is_bool($use_sendmail)) {
    write_log("'$use_sendmail' " . translate("isNotAValidValueFor") .
        " \$use_sendmail" . translate("commaExit"));
    exit(1);
}
if (!$use_sendmail) {
    if (empty($smtp_hosts)) {
        write_log("\$smtp_hosts " . translate("canNotBeAnEmptyString") .
            translate("commaExit"));
        exit(1);
    }
    if (!is_bool($smtp_auth)) {
        write_log("'$smtp_auth' " . translate("isNotAValidValueFor") .
            " \$smtp_auth" . translate("commaExit"));
        exit(1);
    }
    if (empty($smtp_username)) {
        write_log("\$smtp_username " . translate("canNotBeAnEmptyString") .
            translate("commaExit"));
        exit(1);
    }
    if (empty($smtp_password)) {
        write_log("\$smtp_password " . translate("canNotBeAnEmptyString") .
            translate("commaExit"));
        exit(1);
    }
    if (empty($smtp_sec_proto)) {
        write_log("\$smtp_sec_proto " . translate("canNotBeAnEmptyString") .
            translate("commaExit"));
        exit(1);
    }
    if (!is_int($smtp_port) || ($smtp_port < 1) || ($smtp_port > 65535)) {
        write_log("'$smtp_port' " . translate("isNotAValidValueFor") .
            " \$smtp_port" . translate("commaExit"));
        exit(1);
    }
}
if (empty($sender_email)) {
    write_log("\$sender_email " . translate("canNotBeAnEmptyString") .
        translate("commaExit"));
    exit(1);
}
if (empty($sender_name)) {
    write_log("\$sender_name " . translate("canNotBeAnEmptyString") .
        translate("commaExit"));
    exit(1);
}
if (empty($recipient_email)) {
    write_log("\$recipient_email " . translate("canNotBeAnEmptyString") .
        translate("commaExit"));
    exit(1);
}
if (!is_int($max_det_duration) || ($max_det_duration < 0)) {
    write_log("'$max_det_duration' " . translate("isNotAValidValueFor") .
        " \$max_det_duration" . translate("commaExit"));
    exit(1);
}
if (!is_int($no_det_duration) || ($no_det_duration < 0)) {
    write_log("'$no_det_duration' " . translate("isNotAValidValueFor") .
        " \$no_det_duration" . translate("commaExit"));
    exit(1);
}
if (!is_dir($directory)) {
    write_log("'$directory' " . translate("isNotADirectory") .
        translate("commaExit"));
    exit(1);
}
if (empty($memcached_host)) {
    write_log("\$memcached_host " . translate("canNotBeAnEmptyString") .
        translate("commaExit"));
    exit(1);
}
if (!is_int($memcached_port) || ($memcached_port < 1)
    || ($memcached_port > 65535)) {
    write_log("'$memcached_port' " . translate("isNotAValidValueFor") .
        "\$memcached_port" . translate("commaExit"));
    exit(1);
}

// Share variables between 'threads' with memcached
$memcache = new Memcached;
$memcache->addServer($memcached_host, $memcached_port);
if ($memcache->getVersion()["$memcached_host:$memcached_port"]
    == "255.255.255") {
    write_log(translate("MemCachedDown") . translate("commaExit"));
    exit(1);
}

// Set memcached keys
$serial_plate_list = $argv[1] . "_plate_list";
$serial_last_det_time = $argv[1] . "_last_det_time";
$serial_stop = $argv[1] . "_stop";

// Create a PHPMailer instance
$mail = new PHPMailer;
$mail->CharSet = "UTF-8";
if ($use_sendmail) {
    $mail->isSendmail();
} else {
    $mail->isSMTP();
    $mail->Host = $smtp_hosts;
    $mail->SMTPAuth = $smtp_auth;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = $smtp_sec_proto;
    $mail->Port = $smtp_port;
}
$mail->setFrom($sender_email, $sender_name);
if (empty($recipient_name)) {
    $mail->addAddress($recipient_email);
} else {
    $mail->addAddress($recipient_email, $recipient_name);
}
$mail->Subject = translate("MailSubjectPlateDetected");

// Set some default values
$stop = false;
$det_running = false;
$exit_code = 1;

// Sender 'thread'
while(true) {

    // Intercept stop signal and do a final loop to flush results
    if (($stop == false) && $memcache->get($serial_stop)) {
        write_log(translate("StopSignalReceived"));
        $stop = true;
        continue;
    }

    if (($stop == false) && isset($first_det_time) &&
        ((time() - $first_det_time) > $max_det_duration)) {

        // Force notification if we exceed the maximum detection duration
        write_log(translate("MaxDetDurationExceeded"));

    } else {

        // Get the last detection time
        $last_det_time = $memcache->get($serial_last_det_time);
        if ($memcache->getResultCode() == Memcached::RES_NOTFOUND) {
            if ($stop == true) {
                // Stop signal received, nothing to flush, exit loop now
                write_log(translate("TerminatingSenderThread"));
                break;
            } else {
                // Proceed next loop while no plate is detected
                // Do not monopolize the CPU more than necessary
                sleep(1);
                continue;
            }
        } else {
            // Set the time of the first detection since last notification
            if (!$det_running) {
                $det_running = true;
                $first_det_time = $last_det_time;
            }
        }

        if ($stop == false) {
            // Skip the loop if we do not exceed the non detection delay
            if ((time() - $last_det_time) < $no_det_duration) {
                // Do not monopolize the CPU more than necessary
                sleep(1);
                continue;
            } else {
                write_log(translate("NoDetDurationExceeded"));
            }
        }
    }

    // Reinitialize variables for next detection
    $det_running = false;
    unset($first_det_time);

    // Get the plate list
    $plate_list = $memcache->get($serial_plate_list);

    if ($memcache->getResultCode() != Memcached::RES_SUCCESS) {
        // Send a kill command to the main 'thread' in case of cached memory
        // error
        write_log(translate("UnexpectedErrorwithMem") .
            translate("commaExit"));
        posix_kill($argv[2], SIGINT);
        // Exit loop now. The main 'thread' can not do it because of cached
        // memory error
        break;
    }

    // Sort the plate list by confidence level descending order
    $plate_list = sort_array($plate_list, 'conf');

    // Get the plate with the highest confidence
    reset($plate_list);
    $first_plate = current($plate_list);

    // Set email body with plate numbers
    $mail->Body = translate("VehiclesEnteredYourProperty") . "\n\n" .
        translate("DetectedPlate") . " " . $first_plate['num'] . "\n\n" .
        translate("CandidatesConfidence") . "\n";
    $nb_plates = 0;
    foreach ($plate_list as $plate) {
        $nb_plates++;
        if ($nb_plates > $max_plates) {
            break;
        }
        $mail->Body .= "- " . $plate['num'] . " (" . $plate['conf'] . ")\n";
    }
    $mail->Body .= "\n" . translate("PictureIsAttached") . "\n";

    // Add picture with the highest confidence to the email
    $mail->clearAttachments();
    $mail->addAttachment($directory . "/" . $first_plate['uuid'] . ".jpg");

    // Send the email
    write_log(translate("SendEmailWithPlates"));
    if (!$mail->send()) {
        write_log(translate("MessageNotSent") . " (" . $mail->ErrorInfo . ")");
    }

    // We have flushed the result, reset variables in cached memory
    $memcache->delete($serial_last_det_time);
    $plate_list = array();
    $memcache->replace($serial_plate_list, $plate_list, 0);

    // The last loop is finished, we can exit now
    if ($stop == true) {
        write_log(translate("TerminatingSenderThread"));
        $exit_code = 0;
        break;
    }
}

exit($exit_code);

?>
