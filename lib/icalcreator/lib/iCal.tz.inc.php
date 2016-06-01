<?php
/*********************************************************************************/
/*          Additional functions to use with vtimezone components                */
/*********************************************************************************/
/**
 * For use with
 * iCalcreator (kigkonsult.se/iCalcreator/index.php)
 * copyright (c) 2011 Yitzchok Lavi
 * icalcreator@onebigsystem.com
 * @version   2.22
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * Additional functions to use with vtimezone components
 *
 * Before calling the functions, set time zone 'GMT' ('date_default_timezone_set')!
 *
 * @author Yitzchok Lavi <icalcreator@onebigsystem.com>
 *         adjusted for iCalcreator Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @version 1.0.2 - 2011-02-24
 *
 */
/**
 * Returns array with the offset information from UTC for a (UTC) datetime/timestamp in the
 * timezone, according to the VTIMEZONE information in the input array.
 *
 * @param array  $timezonesarray  output from function getTimezonesAsDateArrays (below)
 * @param string $tzid            time zone identifier
 * @param mixed  $timestamp       timestamp or a UTC datetime (in array format)
 * @return array                  time zone data with keys for 'offsetHis', 'offsetSec' and 'tzname'
 *
 */
function getTzOffsetForDate($timezonesarray, $tzid, $timestamp) {
    if( is_array( $timestamp )) {
//$disp = sprintf( '%04d%02d%02d %02d%02d%02d', $timestamp['year'], $timestamp['month'], $timestamp['day'], $timestamp['hour'], $timestamp['min'], $timestamp['sec'] ); // test ###
      $timestamp = gmmktime(
            $timestamp['hour'],
            $timestamp['min'],
            $timestamp['sec'],
            $timestamp['month'],
            $timestamp['day'],
            $timestamp['year']
            ) ;
    }
    $tzoffset = array();
    // something to return if all goes wrong (such as if $tzid doesn't find us an array of dates)
    $tzoffset['offsetHis'] = '+0000';
    $tzoffset['offsetSec'] = 0;
    $tzoffset['tzname']    = '?';
    if( !isset( $timezonesarray[$tzid] ))
      return $tzoffset;
    $tzdatearray = $timezonesarray[$tzid];
    if ( is_array($tzdatearray) ) {
        sort($tzdatearray); // just in case
        if ( $timestamp < $tzdatearray[0]['timestamp'] ) {
            // our date is before the first change
            $tzoffset['offsetHis'] = $tzdatearray[0]['tzbefore']['offsetHis'] ;
            $tzoffset['offsetSec'] = $tzdatearray[0]['tzbefore']['offsetSec'] ;
            $tzoffset['tzname']    = $tzdatearray[0]['tzbefore']['offsetHis'] ; // we don't know the tzname in this case
        } elseif ( $timestamp >= $tzdatearray[count($tzdatearray)-1]['timestamp'] ) {
            // our date is after the last change (we do this so our scan can stop at the last record but one)
            $tzoffset['offsetHis'] = $tzdatearray[count($tzdatearray)-1]['tzafter']['offsetHis'] ;
            $tzoffset['offsetSec'] = $tzdatearray[count($tzdatearray)-1]['tzafter']['offsetSec'] ;
            $tzoffset['tzname']    = $tzdatearray[count($tzdatearray)-1]['tzafter']['tzname'] ;
        } else {
            // our date somewhere in between
            // loop through the list of dates and stop at the one where the timestamp is before our date and the next one is after it
            // we don't include the last date in our loop as there isn't one after it to check
            for ( $i = 0 ; $i <= count($tzdatearray)-2 ; $i++ ) {
                if(( $timestamp >= $tzdatearray[$i]['timestamp'] ) && ( $timestamp < $tzdatearray[$i+1]['timestamp'] )) {
                    $tzoffset['offsetHis'] = $tzdatearray[$i]['tzafter']['offsetHis'] ;
                    $tzoffset['offsetSec'] = $tzdatearray[$i]['tzafter']['offsetSec'] ;
                    $tzoffset['tzname']    = $tzdatearray[$i]['tzafter']['tzname'] ;
                    break;
                }
            }
        }
    }
    return $tzoffset;
}
/**
 * Returns an array containing all the timezone data in the vcalendar object
 *
 * @param object $vcalendar  iCalcreator calendar instance
 * @return array             time zone transition timestamp, array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
 *                           based on the timezone data in the vcalendar object
 *
 */
function getTimezonesAsDateArrays($vcalendar) {
    $timezonedata = array();
    while( $vtz = $vcalendar->getComponent( 'vtimezone' )) {
        $tzid       = $vtz->getProperty('tzid');
        $alltzdates = array();
        while ( $vtzc = $vtz->getComponent( 'standard' )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        while ( $vtzc = $vtz->getComponent( 'daylight' )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        sort($alltzdates);
        $timezonedata[$tzid] = $alltzdates;
    }
    return $timezonedata;
}
/**
 * Returns an array containing time zone data from vtimezone standard/daylight instances
 *
 * @param object $vtzc   an iCalcreator calendar standard/daylight instance
 * @return array         time zone data; array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
 *
 */
function expandTimezoneDates($vtzc) {
    $tzdates = array();
    // prepare time zone "description" to attach to each change
    $tzbefore = array();
    $tzbefore['offsetHis']  = $vtzc->getProperty('tzoffsetfrom') ;
    $tzbefore['offsetSec'] = iCalUtilityFunctions::_tz2offset($tzbefore['offsetHis']);
    if(( '-' != substr( (string) $tzbefore['offsetSec'], 0, 1 )) && ( '+' != substr( (string) $tzbefore['offsetSec'], 0, 1 )))
      $tzbefore['offsetSec'] = '+'.$tzbefore['offsetSec'];
    $tzafter = array();
    $tzafter['offsetHis']   = $vtzc->getProperty('tzoffsetto') ;
    $tzafter['offsetSec']  = iCalUtilityFunctions::_tz2offset($tzafter['offsetHis']);
    if(( '-' != substr( (string) $tzafter['offsetSec'], 0, 1 )) && ( '+' != substr( (string) $tzafter['offsetSec'], 0, 1 )))
      $tzafter['offsetSec'] = '+'.$tzafter['offsetSec'];
    if( FALSE === ( $tzafter['tzname'] = $vtzc->getProperty('tzname')))
      $tzafter['tzname'] = $tzafter['offsetHis'];
    // find out where to start from
    $dtstart = $vtzc->getProperty('dtstart');
    $dtstarttimestamp = mktime(
            $dtstart['hour'],
            $dtstart['min'],
            $dtstart['sec'],
            $dtstart['month'],
            $dtstart['day'],
            $dtstart['year']
            ) ;
    if( !isset( $dtstart['unparsedtext'] )) // ??
      $dtstart['unparsedtext'] = sprintf( '%04d%02d%02dT%02d%02d%02d', $dtstart['year'], $dtstart['month'], $dtstart['day'], $dtstart['hour'], $dtstart['min'], $dtstart['sec'] );
    if ( $dtstarttimestamp == 0 ) {
        // it seems that the dtstart string may not have parsed correctly
        // let's set a timestamp starting from 1902, using the time part of the original string
        // so that the time will change at the right time of day
        // at worst we'll get midnight again
        $origdtstartsplit = explode('T',$dtstart['unparsedtext']) ;
        $dtstarttimestamp = strtotime("19020101",0);
        $dtstarttimestamp = strtotime($origdtstartsplit[1],$dtstarttimestamp);
    }
    // the date (in dtstart and opt RDATE/RRULE) is ALWAYS LOCAL (not utc!!), adjust from 'utc' to 'local' timestamp
    $diff  = -1 * $tzbefore['offsetSec'];
    $dtstarttimestamp += $diff;
                // add this (start) change to the array of changes
    $tzdates[] = array(
        'timestamp' => $dtstarttimestamp,
        'tzbefore'  => $tzbefore,
        'tzafter'   => $tzafter
        );
    $datearray = getdate($dtstarttimestamp);
    // save original array to use time parts, because strtotime (used below) apparently loses the time
    $changetime = $datearray ;
    // generate dates according to an RRULE line
    $rrule = $vtzc->getProperty('rrule') ;
    if ( is_array($rrule) ) {
        if ( $rrule['FREQ'] == 'YEARLY' ) {
            // calculate transition dates starting from DTSTART
            $offsetchangetimestamp = $dtstarttimestamp;
            // calculate transition dates until 10 years in the future
            $stoptimestamp = strtotime("+10 year",time());
            // if UNTIL is set, calculate until then (however far ahead)
            if ( isset( $rrule['UNTIL'] ) && ( $rrule['UNTIL'] != '' )) {
                $stoptimestamp = mktime(
                    $rrule['UNTIL']['hour'],
                    $rrule['UNTIL']['min'],
                    $rrule['UNTIL']['sec'],
                    $rrule['UNTIL']['month'],
                    $rrule['UNTIL']['day'],
                    $rrule['UNTIL']['year']
                    ) ;
            }
            $count = 0 ;
            $stopcount = isset( $rrule['COUNT'] ) ? $rrule['COUNT'] : 0 ;
            $daynames = array(
                        'SU' => 'Sunday',
                        'MO' => 'Monday',
                        'TU' => 'Tuesday',
                        'WE' => 'Wednesday',
                        'TH' => 'Thursday',
                        'FR' => 'Friday',
                        'SA' => 'Saturday'
                        );
            // repeat so long as we're between DTSTART and UNTIL, or we haven't prepared COUNT dates
            while ( $offsetchangetimestamp < $stoptimestamp && ( $stopcount == 0 || $count < $stopcount ) ) {
                // break up the timestamp into its parts
                $datearray = getdate($offsetchangetimestamp);
                if ( isset( $rrule['BYMONTH'] ) && ( $rrule['BYMONTH'] != 0 )) {
                    // set the month
                    $datearray['mon'] = $rrule['BYMONTH'] ;
                }
                if ( isset( $rrule['BYMONTHDAY'] ) && ( $rrule['BYMONTHDAY'] != 0 )) {
                    // set specific day of month
                    $datearray['mday']  = $rrule['BYMONTHDAY'];
                } elseif ( is_array($rrule['BYDAY']) ) {
                    // find the Xth WKDAY in the month
                    // the starting point for this process is the first of the month set above
                    $datearray['mday'] = 1 ;
                    // turn $datearray as it is now back into a timestamp
                    $offsetchangetimestamp = mktime(
                        $datearray['hours'],
                        $datearray['minutes'],
                        $datearray['seconds'],
                        $datearray['mon'],
                        $datearray['mday'],
                        $datearray['year']
                            );
                    if ($rrule['BYDAY'][0] > 0) {
                        // to find Xth WKDAY in month, we find last WKDAY in month before
                        // we do that by finding first WKDAY in this month and going back one week
                        // then we add X weeks (below)
                        $offsetchangetimestamp = strtotime($daynames[$rrule['BYDAY']['DAY']],$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime("-1 week",$offsetchangetimestamp);
                    } else {
                        // to find Xth WKDAY before the end of the month, we find the first WKDAY in the following month
                        // we do that by going forward one month and going to WKDAY there
                        // then we subtract X weeks (below)
                        $offsetchangetimestamp = strtotime("+1 month",$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime($daynames[$rrule['BYDAY']['DAY']],$offsetchangetimestamp);
                    }
                    // now move forward or back the appropriate number of weeks, into the month we want
                    $offsetchangetimestamp = strtotime($rrule['BYDAY'][0] . " week",$offsetchangetimestamp);
                    $datearray = getdate($offsetchangetimestamp);
                }
                // convert the date parts back into a timestamp, setting the time parts according to the
                // original time data which we stored
                $offsetchangetimestamp = mktime(
                    $changetime['hours'],
                    $changetime['minutes'],
                    $changetime['seconds'] + $diff,
                    $datearray['mon'],
                    $datearray['mday'],
                    $datearray['year']
                        );
                // add this change to the array of changes
                $tzdates[] = array(
                    'timestamp' => $offsetchangetimestamp,
                    'tzbefore'  => $tzbefore,
                    'tzafter'   => $tzafter
                    );
                // update counters (timestamp and count)
                $offsetchangetimestamp = strtotime("+" . (( isset( $rrule['INTERVAL'] ) && ( $rrule['INTERVAL'] != 0 )) ? $rrule['INTERVAL'] : 1 ) . " year",$offsetchangetimestamp);
                $count += 1 ;
            }
        }
    }
    // generate dates according to RDATE lines
    while ($rdates = $vtzc->getProperty('rdate')) {
        if ( is_array($rdates) ) {

            foreach ( $rdates as $rdate ) {
                // convert the explicit change date to a timestamp
                $offsetchangetimestamp = mktime(
                        $rdate['hour'],
                        $rdate['min'],
                        $rdate['sec'] + $diff,
                        $rdate['month'],
                        $rdate['day'],
                        $rdate['year']
                        ) ;
                // add this change to the array of changes
                $tzdates[] = array(
                    'timestamp' => $offsetchangetimestamp,
                    'tzbefore'  => $tzbefore,
                    'tzafter'   => $tzafter
                    );
            }
        }
    }
    return $tzdates;
}
