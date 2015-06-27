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


// Load configuration and required libraries
require_once(__ROOT__ . "/config.php");
require_once(__ROOT__ . "/default.php");

// Some sanity checks
$process_user = posix_getpwuid(posix_geteuid());
if ($exec_user != $process_user['name']) {
    echo translate("ScriptMustBeRunnedWithUser") .
        " '" . $exec_user . "'" . translate("commaExit") . "\n";
    exit(1);
}
$process_group = posix_getgrgid(posix_getegid());
if ($exec_group != $process_group['name']) {
    echo translate("ScriptMustBeRunnedWithGroup") .
        " '" . $exec_group . "'" . translate("commaExit") . "\n";
    exit(1);
}

// A function to log message in a file
function write_log($message) {

    global $log_file;

    // Get formatted date and time
    $date = date("Y-m-d H:i:s", time());

    // Append to the log file
    if($fd = @fopen($log_file, "a")) {
        $result = fputs($fd, $date . " " . "phpplatesender" . " " .
            basename($_SERVER['SCRIPT_FILENAME']) . " " . $message . "\n");
        fclose($fd);
        if($result <= 0) {
            echo "Unable to write to '" . $log_file . "', exit!\n";
            exit(1);
        }
    } else {
        echo "Unable to open '" . $log_file . "', exit!\n";
        exit(1);
    }
}

?>
