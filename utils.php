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


// A function to sort the plate list by confidence level descending order
function sort_array ($data, $field) {
    $field = (array) $field;
    uasort($data, function($a, $b) use($field) {
        $retval = 0;
        foreach ($field as $fieldname) {
            if ($retval == 0) {
                $retval = strnatcmp($b[$fieldname], $a[$fieldname]);
            }
        }
        return $retval;
    });
    return $data;
}

// A function to translate strings
// If no translation is found, the index is return as a fallback method
function translate ($name) {

    global $SLANG;

    if ( array_key_exists( $name, $SLANG ) )
        return $SLANG[$name];
    else
        return $name;
}

// A function to load the language file
function loadLanguage () {

    global $language;

    $fallback_lang_file = __ROOT__ . "/lang/en_gb.php";
    $user_lang_file = __ROOT__ . "/lang/" . $language . ".php";

    if (file_exists($user_lang_file)) {
        return $user_lang_file;
    } elseif (file_exists($fallback_lang_file)) {
        return $fallback_lang_file;
    } else {
        return false;
    }
}

// Load the language file
if ($lang_file = loadLanguage ()) {
    require_once($lang_file);
} else {
    write_log("Can not load language file, exit!");
    exit(1);
}

?>
