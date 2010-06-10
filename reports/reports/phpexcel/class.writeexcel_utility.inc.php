<?php
/*
 * Copyleft 2002 Johann Hanne
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307 USA
 */

/*
 * This is the Spreadsheet::WriteExcel Perl package ported to PHP
 * Spreadsheet::WriteExcel was written by John McNamara, jmcnamara@cpan.org
 */


###############################################################################
#
# xl_rowcol_to_cell($row, $col, $row_absolute, $col_absolute)
#
function xl_rowcol_to_cell($row, $col, $row_abs=false, $col_abs=false) {

    $row_abs = $row_abs ? '$' : '';
    $col_abs = $col_abs ? '$' : '';

    $int  = floor($col / 26);
    $frac = $col % 26;

    $chr1 = ''; # Most significant character in AA1

    if ($int > 0) {
        $chr1 = chr(ord('A') + $int - 1);
    }

    $chr2 = chr(ord('A') + $frac);

    # Zero index to 1-index
    $row++;

    return $col_abs . $chr1 . $chr2 . $row_abs. $row;
}

###############################################################################
#
# xl_cell_to_rowcol($string)
#
# Returns: ($row, $col, $row_absolute, $col_absolute)
#
# The $row_absolute and $col_absolute parameters aren't documented because they
# mainly used internally and aren't very useful to the user.
#
function xl_cell_to_rowcol($cell) {

    preg_match('/(\$?)([A-I]?[A-Z])(\$?)(\d+)/', $cell, $reg);

    $col_abs = ($reg[1] == "") ? 0 : 1;
    $col     = $reg[2];
    $row_abs = ($reg[3] == "") ? 0 : 1;
    $row     = $reg[4];

    # Convert base26 column string to number
    # All your Base are belong to us.
    $chars  = preg_split('//', $col, -1, PREG_SPLIT_NO_EMPTY);
    $expn    = 0;
    $col    = 0;

    while (sizeof($chars)>0) {
        $char = array_pop($chars); # LS char first
        $col += (ord($char) - ord('A') + 1) * pow(26, $expn);
        $expn++;
    }

    # Convert 1-index to zero-index
    $row--;
    $col--;

    return array($row, $col, $row_abs, $col_abs);
}

###############################################################################
#
# xl_inc_row($string)
#
function xl_inc_row($cell) {

    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);

    return xl_rowcol_to_cell(++$row, $col, $row_abs, $col_abs);
}

###############################################################################
#
# xl_dec_row($string)
#
# Decrements the row number of an Excel cell reference in A1 notation.
# For example C4 to C3
#
# Returns: a cell reference string.
#
function xl_dec_row($cell) {

    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);

    return xl_rowcol_to_cell(--$row, $col, $row_abs, $col_abs);
}

###############################################################################
#
# xl_inc_col($string)
#
# Increments the column number of an Excel cell reference in A1 notation.
# For example C3 to D3
#
# Returns: a cell reference string.
#
function xl_inc_col($cell) {

    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);

    return xl_rowcol_to_cell($row, ++$col, $row_abs, $col_abs);
}

###############################################################################
#
# xl_dec_col($string)
#
function xl_dec_col($cell) {

    list($row, $col, $row_abs, $col_abs) = xl_cell_to_rowcol($cell);

    return xl_rowcol_to_cell($row, --$col, $row_abs, $col_abs);
}

###############################################################################
#
# xl_date_list($years, $months, $days, $hours, $minutes, $seconds)
#
function xl_date_list($years, $months=1, $days=1,
                      $hours=0, $minutes=0, $seconds=0) {

    $date = array($years, $months, $days, $hours, $minutes, $seconds);
    $epoch = array(1899, 12, 31, 0, 0, 0);

//todo
    list($days, $hours, $minutes, $seconds) = Delta_DHMS($epoch, $date);

    $date = $days + ($hours*3600 + $minutes*60 + $seconds)/(24*60*60);

    # Add a day for Excel's missing leap day in 1900
    if ($date > 59) {
        $date++;
    }

    return $date;
}

###############################################################################
#
# xl_parse_time($string)
#
function xl_parse_time($time) {

    if (preg_match('/(\d{1,2}):(\d\d):?((?:\d\d)(?:\.\d+)?)?(?:\s+)?(am|pm)?/i', $time, $reg)) {

        $hours       = $reg[1];
        $minutes     = $reg[2];
        $seconds     = $reg[3] || 0;
        $meridian    = strtolower($reg[4]) || '';

        # Normalise midnight and midday
        if ($hours == 12 && $meridian != '') {
            $hours = 0;
        }

        # Add 12 hours to the pm times. Note: 12.00 pm has been set to 0.00.
        if ($meridian == 'pm') {
            $hours += 12;
        }

        # Calculate the time as a fraction of 24 hours in seconds
        return ($hours*3600 + $minutes*60 + $seconds)/(24*60*60);

    } else {
        return false; # Not a valid time string
    }
}

###############################################################################
#
# xl_parse_date($string)
#
function xl_parse_date($date) {

//todo
    $date = ParseDate($date);

    if ($date===false) {
        return false;
    }

    # Unpack the return value from ParseDate()
    list  ($years, $months, $days, $hours, $dummy, $minutes, $dummy, $seconds) =
    unpack("A4     A2       A2     A2      C      A2        C      A2", $date);

    # Convert to Excel date
    return xl_date_list($years, $months, $days, $hours, $minutes, $seconds);
}

###############################################################################
#
# xl_parse_date_init("variable=value", ...)
#
function xl_parse_date_init() {

//todo    Date_Init(@_); # How lazy is that.
}

###############################################################################
#
# xl_decode_date_EU($string)
#
function xl_decode_date_EU($date) {

    $time = 0;

    # Remove and decode the time portion of the string
    if (preg_match('/(\d{1,2}:\d\d:?(\d\d(\.\d+)?)?(\s+)?(am|pm)?)/i', $date, $reg)) {
        $date=preg_replace('/'.$reg[1].'/', '', $date);
        $time = xl_parse_time($reg[1]);
        if ($time===false) {
            return false;
        }
    }

    # Return if the string is now blank, i.e. it contained a time only.
    if (preg_match('/^\s*$/', $date)) {
        return $time;
    }

    # Decode the date portion of the string
//todo
    $dates = Decode_Date_EU($date);
    if($dates===false) {
        return false;
    }

    return xl_date_list($dates) + $time;
}

###############################################################################
#
# xl_decode_date_US($string)
#
function xl_decode_date_US($date) {

    $time = 0;

    # Remove and decode the time portion of the string
    if (preg_match('/(\d{1,2}:\d\d:?(\d\d(\.\d+)?)?(\s+)?(am|pm)?)/i', $date, $reg)) {
        $date=preg_replace('/'.$reg[1].'/', "", $date);
        $time = xl_parse_time($reg[1]);
        if ($time===false) {
            return false;
        }
    }

    # Return if the string is now blank, i.e. it contained a time only.
    if (preg_match('/^\s*$/', $date)) {
        return $time;
    }

    # Decode the date portion of the string
//todo
    $dates = Decode_Date_US($date);
    if($dates===false) {
        return false;
    }

    return xl_date_list($dates) + $time;
}

###############################################################################
#
# xl_decode_date_US($string)
#
function xl_date_1904($date=0) {

    if ($date < 1462) {
        # before 1904
        $date = 0;
    } else {
        $date -= 1462;
    }

    return $date;
}

?>
