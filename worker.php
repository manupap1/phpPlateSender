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
require_once(__PHEANSTALK_ROOT__ . "/pheanstalk_init.php");

// Some sanity checks
if (!is_int($min_conf_level) || ($min_conf_level < 0)
    || ($min_conf_level > 100)) {
    write_log("'$min_conf_level' " . translate("isNotAValidValueFor") .
        " \$min_conf_level" . translate("commaExit"));
    exit(1);
}
if (!is_int($ret_delay) || ($ret_delay < 0)) {
    write_log("'$ret_delay' " . translate("isNotAValidValueFor") .
        " \$ret_delay" . translate("commaExit"));
    exit(1);
}
if (empty($beanstalkd_host)) {
    write_log("\$beanstalkd_host " . translate("canNotBeAnEmptyString") .
        translate("commaExit"));
    exit(1);
}
if (!is_executable($php_bin_path)) {
    write_log("'$php_bin_path' " . translate("isNotExecutable") .
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

// Get openalpr-daemon (alprd) configuration
$alprd_config = parse_ini_file($alprd_config_file);
var_dump($alprd_config);

// Share variables between 'threads' with memcached
$memcache = new Memcached;
$memcache->addServer($memcached_host, $memcached_port);
if ($memcache->getVersion()["$memcached_host:$memcached_port"]
    == "255.255.255") {
    write_log(translate("MemCachedDown") . translate("commaExit"));
    exit(1);
}

// Get the PID registered if exists
$pid = @file_get_contents($pid_file);
if ($pid !== false) {
    // A PID file exists, check now if the registered PID belongs to a running
    // instance of this script
    $cmd_line = exec('ps -o cmd ' . $pid);
    if (strpos($cmd_line, $_SERVER['PHP_SELF']) !== false) {
        // Raise an error and exit if a running instance exists
        write_log(translate("InstanceAlreadyExists") . translate("commaExit"));
        exit(1);
    } else {
        // Remove the PID file if no running instance is found
        write_log(translate("RemoveStalledPIDFile"));
        unlink($pid_file);
    }
}

// Try to get the unique identifier of a running instance of phpplatesender
$old_serial = $memcache->get("phpplatesender_serial");

// Remove any entries in cached memory that belong to the old instance
if ($memcache->getResultCode() != Memcached::RES_NOTFOUND) {
    write_log(translate("RemoveStalledEntriesInMem"));
    $memcache->delete($old_serial . "_stop");
    $memcache->delete($old_serial . "_plate_list");
    $memcache->delete($old_serial . "_last_det_time");
    $memcache->delete("phpplatesender_serial");
}

// Generate a unique identifier for this script instance and write it to the
// cached memory
$serial = microtime(true) . mt_rand(0, time()) . mt_rand(0, time());
$memcache->set("phpplatesender_serial", $serial, 0);

// Set memcached keys
$serial_plate_list = $serial . "_plate_list";
$serial_last_det_time = $serial . "_last_det_time";
$serial_stop = $serial . "_stop";

// Intercept signals
declare(ticks = 1);
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");
pcntl_signal(SIGHUP, "signal_handler");

// Create a Pheanstalk instance
$pheanstalk = new Pheanstalk_Pheanstalk($beanstalkd_host);

// Global declaration of plate list
$plate_list = array();

// Initialize some variables in cached memory
$memcache->set($serial_plate_list, $plate_list, 0);
$memcache->set($serial_stop, false, 0);

// A function to set $serial_stop to true if a stop signal is received
// This variable is used to stop the sender 'thread'
function signal_handler($signal) {
    global $enable_notifications;
    global $memcache;
    global $serial;
    global $serial_plate_list;
    global $serial_last_det_time;
    global $serial_stop;
    global $pid_file;
    switch($signal) {
        case SIGTERM:
            // The 'kill' command has been used to interrupt the process
        case SIGINT:
            // The user has stopped the script with CTRL+C
            // Or daemon.php script has been called with 'stop' argument
            // to interrupt the process
        case SIGHUP:
            // The controlling terminal is closed, we should stop the process
            write_log(translate("TerminatingProgram"));
            $sender_terminated = true;
            if ($enable_notifications) {
                // Trigger sender 'thread' ending
                $memcache->set($serial_stop, true, 0);
                // Timeout when stopping the running instance
                $timeout = 60;
                $timecount = 0;
                // Wait until sender 'thread' is stopped
                do {
                    unset($out);
                    $out = array();
                    exec("ps aux | grep '" .
                        __ROOT__ . "/sender.php " . $serial .
                        "' | grep -v grep | awk '{ print $2 }' | head -1", $out);
                    $timecount++;
                    if ($timecount > $timeout) {
                        write_log(translate("TimeoutWhenStopping"));
                        $sender_terminated = false;
                        break;
                    }
                    sleep(1);
                } while (isset($out[0]));
            }
            // Delete entries in cached memory if we properly stopped the sender
            // thread
            if ($sender_terminated) {
                $memcache->delete($serial_stop);
                $memcache->delete($serial_plate_list);
                $memcache->delete($serial_last_det_time);
                $memcache->delete("phpplatesender_serial");
            }
            // Remove the PID file and exit
            unlink($pid_file);
            exit(0);
            break;
        default:
            write_log(translate("UnknownSignalReceived") . " '" . $signal .
                "'");
            break;
    }
}

// A function to add plates to the plate list
function addPlate($candidate, $uuid) {
    global $plate_list;

    foreach ($plate_list as $index => $plate) {
        // If plate number already exists, add confidence
        if ($candidate->plate == $plate['num']) {
            $plate_list[$index]['conf'] += $candidate->confidence;
            write_log(translate("RaiseConfOfPlate") . " " . $candidate->plate .
                 " " . translate("to") . " " . $plate_list[$index]['conf']);
            return false;
        }
    }
    // Add a new plate otherwise
    write_log(translate("AddPlate") . " " . $candidate->plate .
         " " . translate("withConfidence") . " " . $candidate->confidence);
    $plate = array();
    $plate['num'] = $candidate->plate;
    $plate['conf'] = $candidate->confidence;
    $plate['uuid'] = $uuid;
    $plate_list[] = $plate;
    return true;
}

write_log(translate("StartingProgram"));

// Write the PID file
$pid = getmypid();
if($fd = @fopen($pid_file, "w")) {
    $result = fputs($fd, $pid);
    fclose($fd);
    if($result <= 0) {
        echo "Unable to write to '" . $pid_file . "', exit!\n";
        exit(1);
    }
} else {
    echo "Unable to open '" . $pid_file . "', exit!\n";
    exit(1);
}

// Run the sender 'thread' in the background if notifications are enabled
if ($enable_notifications) {
    exec($php_bin_path . " -f " . __ROOT__ . "/sender.php " . $serial .
        " " . $pid . " 2>> " . $error_log_file . "  > /dev/null &");
} else {
    write_log(translate("NotificationsDisabled"));
}

// Exit with error by default
$exit_code = 1;

// Initialize time
$old_det_time = time();

// Main 'thread'
while (true) {

    // Wait for next message in alprd queue
    $job = $pheanstalk->watch('alprd')->ignore('default')->reserve();

    // Decode the message
    $job_data = json_decode($job->getData(), false);

    $is_update = false;

    if ($enable_notifications) {
        // Update plate list from cached memory
        // The sender 'thread' may have modify it
        $plate_list = $memcache->get($serial_plate_list);
    } else {
        // The plate list will never be reseted when notifications are disabled
        // So reset the plate list after a delay to prevent infinite growing
        if ((time() - $old_det_time) > $ret_delay) {
            write_log(translate("DelayExceededResetPlateList"));
            $plate_list = array();
            $old_det_time = time();
        }
    }

    // Exit loop in case of any error with cached memory
    if ($memcache->getResultCode() != Memcached::RES_SUCCESS) {
        write_log(translate("UnexpectedErrorwithMem") . translate("commaExit"));
        break;
    }

    // Add plates to the list
    foreach ($job_data->results as $result) {
        foreach ($result->candidates as $candidate) {
            // Skip plates below the minimum confidence level
            if ($candidate->confidence < $min_conf_level) {
                continue;
            }
            addPlate($candidate, $job_data->uuid);
            $is_update = true;
            // Set or update the last detection time in the memory cache
            $memcache->set($serial_last_det_time, time(), 0);
        }
    }

    // Update the plate list in the memory cache
    if ($is_update) {
        $memcache->replace($serial_plate_list, $plate_list, 0);
    }

    // Remove message from the queue
    $pheanstalk->delete($job);
}

exit($exit_code);

?>
