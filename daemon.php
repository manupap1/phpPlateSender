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
require_once(__ROOT__ . "/logger.php");
require_once(__ROOT__ . "/utils.php");

// Some sanity checks
if (!isset($argv[1])) {
    echo translate("MissingArgument") . translate("commaExit") . "\n";
    write_log(translate("MissingArgument") . translate("commaExit"));
    exit(1);
}
if (!is_executable($php_bin_path)) {
    echo "'$php_bin_path' " . translate("isNotExecutable") . "\n";
    write_log("'$php_bin_path' " . translate("isNotExecutable") .
        translate("commaExit"));
    exit(1);
}

// Get the PID registered if exists
$running_instance = false;
$pid = @file_get_contents($pid_file);
if ($pid !== false) {
    // A PID file exists, check now if the registered PID belongs to a running
    // instance of this script
    $cmd_line = exec('ps -o cmd ' . $pid);
    if (strpos($cmd_line, "phpplatesender/worker.php") !== false) {
        $running_instance = true;
    }
}

// Exit with error by default
$exit_code = 1;

switch($argv[1]) {
    case "stop":
        if ($running_instance) {
            // Send kill command to stop the running instance
            posix_kill($pid, SIGINT);
        } else {
            echo translate("NoRunningInstance") . translate("commaExit") . "\n";
            write_log(translate("NoRunningInstance") . translate("commaExit"));
            exit(1);
        }
        // Timeout when stopping the running instance
        $timeout = 60;
        $timecount = 0;
        // Wait until entries are deleted in the cached memory
        do {
            $cmd_line = exec('ps -o cmd ' . $pid);
            $timecount++;
            if ($timecount > $timeout) {
                echo translate("TimeoutWhenStopping") . "\n";
                write_log(translate("TimeoutWhenStopping"));
                exit(1);
            }
            sleep(1);
        } while (strpos($cmd_line, "phpplatesender/worker.php") !== false);
        $running_instance = false;
        $exit_code = 0;
        break;
    case "reload":
        // FIXME: Not implemented
        $exit_code = 0;
        break;
    case "start":
        if ($running_instance) {
            // Raise an error and exit if a running instance exists
            echo translate("InstanceAlreadyExists") . translate("commaExit") .
                "\n";
            write_log(translate("InstanceAlreadyExists") .
                translate("commaExit"));
            exit(1);
        }
        // Run the worker 'thread' in the background
        exec($php_bin_path . " -f " . __ROOT__ . "/worker.php" .
            " 2>> " . $error_log_file . " > /dev/null &");
        $exit_code = 0;
        break;
    case "status":
        if ($running_instance) {
            echo translate("InstanceIsRunning") . "\n";
            write_log(translate("InstanceIsRunning"));
            $exit_code = 0;
        } else {
            echo translate("NoRunningInstance") . "\n";
            write_log(translate("NoRunningInstance"));
        }
        break;
    default:
        echo translate("UnknownArgument") . "\n";
        write_log(translate("UnknownArgument"));
        break;
}

exit($exit_code);

?>
