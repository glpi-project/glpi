<?php
/*********************************************************************************/
/**
 *
 * A PHP implementation of rfc2445/rfc5545.
 *
 * @copyright Copyright (c) 2007-2015 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      http://kigkonsult.se/iCalcreator/index.php
 * @license   http://kigkonsult.se/downloads/dl.php?f=LGPL
 * @package   iCalcreator
 * @version   2.22
 */
/**
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
/*********************************************************************************/
/**
 * moving all utility (static) functions to a utility class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-04-03
 */
class iCalUtilityFunctions {
/** @var string  tmp line delimiter, used in convEolChar (parse) */
  private static $baseDelim = null;
/** @var array protocol prefix, used in _splitContent() */
  private static $parValPrefix = array ( 'MStz'   => array( 'utc-', 'utc+', 'gmt-', 'gmt+' )
                                       , 'Proto3' => array( 'fax:', 'cid:', 'sms:', 'tel:', 'urn:' )
                                       , 'Proto4' => array( 'crid:', 'news:', 'pres:' )
                                       , 'Proto6' => array( 'mailto:' ));
/** @var string  output format for geo latitude and longitude (before rtrim) */
  public static $geoLatFmt  = '%09.6f';
  public static $geoLongFmt = '%8.6f';
/** @var array  date/datetime formats */
  public static $fmt        = array( 'Ymd'       => '%04d%02d%02d',
                                     'His'       => '%02d%02d%02d',
                                     'dayOfDays' => 'day %d of %d',
                                     'durDHis'   => '%a days, %h hours, %i min, %s sec',
                                     'Ymd2'      => 'Y-m-d',
                                     'YmdHis2'   => 'Y-m-d H:i:s',
                                     'YmdHis2e'  => 'Y-m-d H:i:s e',
                                     'YmdHis3'   => 'Y-m-d-H-i-s',
                                     'YmdHise'   => '%04d-%02d-%02d %02d:%02d:%02d %s',
                                     'YmdTHisO'  => 'Y-m-d\TH:i:s O',
                                     'dateKey'   => '%04d%02d%02d%02d%02d%02d000',
                                   );
/** @var array  component property UID value */
  public static $vComps     = array( 'vevent', 'vtodo', 'vjournal', 'vfreebusy' );
  public static $mComps     = array( 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone' );
  public static $miscComps  = array( 'valarm', 'vtimezone', 'standard', 'daylight' );
  public static $tzComps    = array( 'vtimezone', 'standard', 'daylight' );
  public static $allComps   = array( 'vtimezone', 'standard', 'daylight', 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm' );
/** @var array  component property collections */
  public static $mProps1    = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES' );
  public static $mProps2    = array( 'ATTACH',   'ATTENDEE', 'CATEGORIES', 'COMMENT',   'CONTACT', 'DESCRIPTION',    'EXDATE', 'EXRULE',
                                     'FREEBUSY', 'RDATE',    'RELATED-TO', 'RESOURCES', 'RRULE',   'REQUEST-STATUS', 'TZNAME', 'X-PROP'  );
  public static $dateProps  = array( 'DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED', 'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID' );
  public static $otherProps = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL' );
/** @var object Store the single instance of iCalUtilityFunctions */
  private static $m_pInstance;
/**
 * Private constructor to limit object instantiation to within the class
 *
 * @access private
 */
  private function __construct() {
    $m_pInstance = FALSE;
  }
/** @var array component property UID value */
/**
 * Getter method for creating/returning the single instance of this class
 *
 * @uses iCalUtilityFunctions::$m_pInstance
 */
  public static function getInstance() {
    if (!self::$m_pInstance)
      self::$m_pInstance = new iCalUtilityFunctions();
    return self::$m_pInstance;
  }
/**
 * ensures internal date-time/date format (keyed array) for an input date-time/date array (keyed or unkeyed)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.24 - 2013-06-26
 * @param array $datetime
 * @param int $parno optional, default FALSE
 * @return array
 */
  public static function _chkDateArr( $datetime, $parno=FALSE ) {
    $output = array();
    if(( !$parno || ( 6 <= $parno )) && isset( $datetime[3] ) && !isset( $datetime[4] )) { // Y-m-d with tz
      $temp        = $datetime[3];
      $datetime[3] = $datetime[4] = $datetime[5] = 0;
      $datetime[6] = $temp;
    }
    foreach( $datetime as $dateKey => $datePart ) {
      switch ( $dateKey ) {
        case '0': case 'year':   $output['year']  = $datePart; break;
        case '1': case 'month':  $output['month'] = $datePart; break;
        case '2': case 'day':    $output['day']   = $datePart; break;
      }
      if( 3 != $parno ) {
        switch ( $dateKey ) {
          case '0':
          case '1':
          case '2': break;
          case '3': case 'hour': $output['hour']  = $datePart; break;
          case '4': case 'min' : $output['min']   = $datePart; break;
          case '5': case 'sec' : $output['sec']   = $datePart; break;
          case '6': case 'tz'  : $output['tz']    = $datePart; break;
        }
      }
    }
    if( 3 != $parno ) {
      if( !isset( $output['hour'] ))         $output['hour'] = 0;
      if( !isset( $output['min']  ))         $output['min']  = 0;
      if( !isset( $output['sec']  ))         $output['sec']  = 0;
      if( isset( $output['tz'] ) &&
        (( '+0000' == $output['tz'] ) || ( '-0000' == $output['tz'] ) || ( '+000000' == $output['tz'] ) || ( '-000000' == $output['tz'] )))
                                             $output['tz']   = 'Z';
    }
    return $output;
  }
/**
 * check date(-time) and params arrays for an opt. timezone and if it is a DATE-TIME or DATE (updates $parno and params)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.30 - 2012-01-16
 * @param array $theDate    date to check
 * @param int   $parno      no of date parts (i.e. year, month.. .)
 * @param array $params     property parameters
 * @uses iCalUtilityFunctions::_strdate2date()
 * @uses iCalUtilityFunctions::_isOffset()
 * @return void
 */
  public static function _chkdatecfg( $theDate, & $parno, & $params ) {
    if( isset( $params['TZID'] ))
      $parno = 6;
    elseif( isset( $params['VALUE'] ) && ( 'DATE' == $params['VALUE'] ))
      $parno = 3;
    else {
      if( isset( $params['VALUE'] ) && ( 'PERIOD' == $params['VALUE'] ))
        $parno = 7;
      if( is_array( $theDate )) {
        if( isset( $theDate['timestamp'] ))
          $tzid = ( isset( $theDate['tz'] )) ? $theDate['tz'] : null;
        else
          $tzid = ( isset( $theDate['tz'] )) ? $theDate['tz'] : ( 7 == count( $theDate )) ? end( $theDate ) : null;
        if( !empty( $tzid )) {
          $parno = 7;
          if( !iCalUtilityFunctions::_isOffset( $tzid ))
            $params['TZID'] = $tzid; // save only timezone
        }
        elseif( !$parno && ( 3 == count( $theDate )) &&
          ( isset( $params['VALUE'] ) && ( 'DATE' == $params['VALUE'] )))
          $parno = 3;
        else
          $parno = 6;
      }
      else { // string
        $date = trim( $theDate );
        if( 'Z' == substr( $date, -1 ))
          $parno = 7; // UTC DATE-TIME
        elseif((( 8 == strlen( $date ) && ctype_digit( $date )) || ( 11 >= strlen( $date ))) &&
          ( !isset( $params['VALUE'] ) || !in_array( $params['VALUE'], array( 'DATE-TIME', 'PERIOD' ))))
          $parno = 3; // DATE
        $date = iCalUtilityFunctions::_strdate2date( $date, $parno );
        unset( $date['unparsedtext'] );
        if( !empty( $date['tz'] )) {
          $parno = 7;
          if( !iCalUtilityFunctions::_isOffset( $date['tz'] ))
            $params['TZID'] = $date['tz']; // save only timezone
        }
        elseif( empty( $parno ))
          $parno = 6;
      }
      if( isset( $params['TZID'] ))
        $parno = 6;
    }
  }
/**
 * vcalendar sort callback function
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-17
 * @param array $a
 * @param array $b
 * @uses calendarComponent::$objName
 * @return int
 */
  public static function _cmpfcn( $a, $b ) {
    if(        empty( $a ))                       return -1;
    if(        empty( $b ))                       return  1;
    if( 'vtimezone' == $a->objName ) {
      if( 'vtimezone' != $b->objName )            return -1;
      elseif( $a->srtk[0] <= $b->srtk[0] )        return -1;
      else                                        return  1;
    }
    elseif( 'vtimezone' == $b->objName )          return  1;
    $sortkeys = array( 'year', 'month', 'day', 'hour', 'min', 'sec' );
    for( $k = 0; $k < 4 ; $k++ ) {
      if(        empty( $a->srtk[$k] ))           return -1;
      elseif(    empty( $b->srtk[$k] ))           return  1;
      if( is_array( $a->srtk[$k] )) {
        if( is_array( $b->srtk[$k] )) {
          foreach( $sortkeys as $key ) {
            if    ( !isset( $a->srtk[$k][$key] )) return -1;
            elseif( !isset( $b->srtk[$k][$key] )) return  1;
            if    (  empty( $a->srtk[$k][$key] )) return -1;
            elseif(  empty( $b->srtk[$k][$key] )) return  1;
            if    (         $a->srtk[$k][$key] == $b->srtk[$k][$key])
                                                  continue;
            if    ((  (int) $a->srtk[$k][$key] ) < ((int) $b->srtk[$k][$key] ))
                                                  return -1;
            elseif((  (int) $a->srtk[$k][$key] ) > ((int) $b->srtk[$k][$key] ))
                                                  return  1;
          }
        }
        else                                      return -1;
      }
      elseif( is_array( $b->srtk[$k] ))           return  1;
      elseif( $a->srtk[$k] < $b->srtk[$k] )       return -1;
      elseif( $a->srtk[$k] > $b->srtk[$k] )       return  1;
    }
    return 0;
  }
/**
 * byte oriented line folding fix
 *
 * remove any line-endings that may include spaces or tabs
 * and convert all line endings (iCal default '\r\n'),
 * takes care of '\r\n', '\r' and '\n' and mixed '\r\n'+'\r', '\r\n'+'\n'
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.16 - 2014-04-04
 * @param string $text
 * @param string $nl
 * @uses iCalUtilityFunctions::$baseDelim
 * @return string
 */
  public static function convEolChar( & $text, $nl ) {
            /* fix dummy line separator */
    if( empty( iCalUtilityFunctions::$baseDelim )) {
      iCalUtilityFunctions::$baseDelim = substr( microtime(), 2, 4 );
      $base   = 'aAbB!cCdD"eEfF#gGhHiIjJ%kKlL&mMnN/oOpP(rRsS)tTuU=vVxX?uUvV*wWzZ-1234_5678|90';
      $len    = strlen( $base ) - 1;
      for( $p = 0; $p < 6; $p++ )
        iCalUtilityFunctions::$baseDelim .= $base{mt_rand( 0, $len )};
    }
            /* fix eol chars */
    $text   = str_replace( array( "\r\n", "\n\r", "\n", "\r" ), iCalUtilityFunctions::$baseDelim, $text );
            /* fix empty lines */
    $text   = str_replace( iCalUtilityFunctions::$baseDelim.iCalUtilityFunctions::$baseDelim, iCalUtilityFunctions::$baseDelim.str_pad( '', 75 ).iCalUtilityFunctions::$baseDelim, $text );
            /* fix line folding */
    $text   = str_replace( iCalUtilityFunctions::$baseDelim, $nl, $text );
    $text   = str_replace( array( $nl.' ', $nl."\t" ), '', $text );
            /* split in component/property lines */
    $text   = explode( $nl, $text );
    return $text;
  }
/**
 * create a calendar timezone and standard/daylight components
 *
 * Result when 'Europe/Stockholm' and no from/to arguments is used as timezone:
 *
 * BEGIN:VTIMEZONE
 * TZID:Europe/Stockholm
 * BEGIN:STANDARD
 * DTSTART:20101031T020000
 * TZOFFSETFROM:+0200
 * TZOFFSETTO:+0100
 * TZNAME:CET
 * END:STANDARD
 * BEGIN:DAYLIGHT
 * DTSTART:20100328T030000
 * TZOFFSETFROM:+0100
 * TZOFFSETTO:+0200
 * TZNAME:CEST
 * END:DAYLIGHT
 * END:VTIMEZONE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-04-03
 * Generates components for all transitions in a date range, based on contribution by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * Additional changes jpirkey
 * @param object $calendar  iCalcreator calendar instance
 * @param string $timezone  PHP5 (DateTimeZone) valid timezone
 * @param array  $xProp     *[x-propName => x-propValue], optional
 * @param int    $from      unix timestamp
 * @param int    $to        unix timestamp
 * @uses vcalendar::getProperty()
 * @uses iCalUtilityFunctions::$fmt
 * @uses vcalendar::newComponent()
 * @uses calendarComponent::setproperty()
 * @uses iCalUtilityFunctions::offsetSec2His()
 * @return bool
 */
  public static function createTimezone( & $calendar, $timezone, $xProp=array(), $from=null, $to=null ) {
    if( empty( $timezone ))
      return FALSE;
    if( !empty( $from ) && !is_int( $from ))
      return FALSE;
    if( !empty( $to )   && !is_int( $to ))
      return FALSE;
    try {
      $dtz               = new DateTimeZone( $timezone );
      $transitions       = $dtz->getTransitions();
      $utcTz             = new DateTimeZone( 'UTC' );
    }
    catch( Exception $e ) { return FALSE; }
    if( empty( $from ) || empty( $to )) {
      $dates             = array_keys( $calendar->getProperty( 'dtstart' ));
      if( empty( $dates ))
        $dates           = array( date( 'Ymd' ));
    }
    if( ! empty( $from )) {
      $dateFrom          = new DateTime( "@$from" );             // set lowest date (UTC)
      $dateFrom->modify( '-7 month' );                           // set $dateFrom to seven month before the lowest date
    }
    else {
      $from              = reset( $dates );                      // set lowest date to the lowest dtstart date
      $dateFrom          = new DateTime( $from.'T000000', $dtz );
      $dateFrom->modify( '-7 month' );                           // set $dateFrom to seven month before the lowest date
      $dateFrom->setTimezone( $utcTz );                          // convert local date to UTC
    }
    $dateFromYmd         = $dateFrom->format( iCalUtilityFunctions::$fmt['Ymd2'] );
    if( ! empty( $to ))
      $dateTo            = new DateTime( "@$to" );               // set end date (UTC)
    else {
      $to                = end( $dates );                        // set highest date to the highest dtstart date
      $dateTo            = new DateTime( $to.'T235959', $dtz );
      $dateTo->modify( '+18 month' );                            // set $dateTo to 18 month after the highest date
      $dateTo->setTimezone( $utcTz );                            // convert local date to UTC
    }
    $dateToYmd           = $dateTo->format( iCalUtilityFunctions::$fmt['Ymd2'] );
    unset( $dtz );
    $transTemp           = array();
    $prevOffsetfrom      = 0;
    $stdIx  = $dlghtIx   = null;
    $prevTrans           = FALSE;
    foreach( $transitions as $tix => $trans ) {                  // all transitions in date-time order!!
      $date              = new DateTime( "@{$trans['ts']}" );    // set transition date (UTC)
      $transDateYmd      = $date->format( iCalUtilityFunctions::$fmt['Ymd2'] );
      if ( $transDateYmd < $dateFromYmd ) {
        $prevOffsetfrom  = $trans['offset'];                     // previous trans offset will be 'next' trans offsetFrom
        $prevTrans       = $trans;                               // save it in case we don't find any that match
        $prevTrans['offsetfrom'] = ( 0 < $tix ) ? $transitions[$tix-1]['offset'] : 0;
        continue;
      }
      if( $transDateYmd > $dateToYmd )
        break;                                                   // loop always (?) breaks here
      if( !empty( $prevOffsetfrom ) || ( 0 == $prevOffsetfrom )) {
        $trans['offsetfrom'] = $prevOffsetfrom;                  // i.e. set previous offsetto as offsetFrom
        $date->modify( $trans['offsetfrom'].'seconds' );         // convert utc date to local date
        $d               = $date->format( iCalUtilityFunctions::$fmt['YmdHis3'] );
        $d               = explode( '-', $d );                   // set date to array to ease up dtstart and (opt) rdate setting
        $trans['time']   = array( 'year' => (int) $d[0], 'month' => (int) $d[1], 'day' => (int) $d[2], 'hour' => (int) $d[3], 'min' => (int) $d[4], 'sec' => (int) $d[5] );
      }
      $prevOffsetfrom    = $trans['offset'];
      if( TRUE !== $trans['isdst'] ) {                           // standard timezone
        if( !empty( $stdIx ) && isset( $transTemp[$stdIx]['offsetfrom'] )  && // check for any repeating rdate's (in order)
           ( $transTemp[$stdIx]['abbr']       ==   $trans['abbr'] )        &&
           ( $transTemp[$stdIx]['offsetfrom'] ==   $trans['offsetfrom'] )  &&
           ( $transTemp[$stdIx]['offset']     ==   $trans['offset'] )) {
          $transTemp[$stdIx]['rdate'][]        =   $trans['time'];
          continue;
        }
        $stdIx           = $tix;
      } // end standard timezone
      else {                                                     // daylight timezone
        if( !empty( $dlghtIx ) && isset( $transTemp[$dlghtIx]['offsetfrom'] ) && // check for any repeating rdate's (in order)
           ( $transTemp[$dlghtIx]['abbr']       ==   $trans['abbr'] )         &&
           ( $transTemp[$dlghtIx]['offsetfrom'] ==   $trans['offsetfrom'] )   &&
           ( $transTemp[$dlghtIx]['offset']     ==   $trans['offset'] )) {
          $transTemp[$dlghtIx]['rdate'][]        =   $trans['time'];
          continue;
        }
        $dlghtIx         = $tix;
      } // end daylight timezone
      $transTemp[$tix]   = $trans;
    } // end foreach( $transitions as $tix => $trans )
    $tz                  = $calendar->newComponent( 'vtimezone' );
    $tz->setproperty( 'tzid', $timezone );
    if( !empty( $xProp )) {
      foreach( $xProp as $xPropName => $xPropValue )
        if( 'x-' == strtolower( substr( $xPropName, 0, 2 )))
          $tz->setproperty( $xPropName, $xPropValue );
    }
    if( empty( $transTemp )) {      // if no match found
      if( $prevTrans ) {            // then we use the last transition (before startdate) for the tz info
        $date            = new DateTime( "@{$prevTrans['ts']}" );// set transition date (UTC)
        $date->modify( $prevTrans['offsetfrom'].'seconds' );     // convert utc date to local date
        $d               = $date->format( iCalUtilityFunctions::$fmt['YmdHis3'] );
        $d               = explode( '-', $d );                   // set date to array to ease up dtstart setting
        $prevTrans['time'] = array( 'year' => (int) $d[0], 'month' => (int) $d[1], 'day' => (int) $d[2], 'hour' => (int) $d[3], 'min' => (int) $d[4], 'sec' => (int) $d[5] );
        $transTemp[0] = $prevTrans;
      }
      else {                        // or we use the timezone identifier to BUILD the standard tz info (?)
        $date            = new DateTime( 'now', new DateTimeZone( $timezone ));
        $transTemp[0]    = array( 'time'       => $date->format( iCalUtilityFunctions::$fmt['YmdTHisO'] ),
                                  'offset'     => $date->format( 'Z' ),
                                  'offsetfrom' => $date->format( 'Z' ),
                                  'isdst'      => FALSE );
      }
    }
    unset( $transitions, $date, $prevTrans );
    foreach( $transTemp as $tix => $trans ) { // create standard/daylight subcomponents
      $type              = ( TRUE !== $trans['isdst'] ) ? 'standard' : 'daylight';
      $scomp             = $tz->newComponent( $type );
      $scomp->setProperty( 'dtstart',         $trans['time'] );
//      $scomp->setProperty( 'x-utc-timestamp', $tix.' : '.$trans['ts'] );   // test ###
      if( !empty( $trans['abbr'] ))
        $scomp->setProperty( 'tzname',        $trans['abbr'] );
      if( isset( $trans['offsetfrom'] ))
        $scomp->setProperty( 'tzoffsetfrom',  iCalUtilityFunctions::offsetSec2His( $trans['offsetfrom'] ));
      $scomp->setProperty( 'tzoffsetto',      iCalUtilityFunctions::offsetSec2His( $trans['offset'] ));
      if( isset( $trans['rdate'] ))
        $scomp->setProperty( 'RDATE',         $trans['rdate'] );
    }
    return TRUE;
  }
/**
 * creates formatted output for calendar component property data value type date/date-time
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array   $datetime
 * @param int     $parno     optional, default 6
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_tz2offset()
 * @return string
 */
  public static function _date2strdate( $datetime, $parno=6 ) {
    if( !isset( $datetime['year'] )  &&
        !isset( $datetime['month'] ) &&
        !isset( $datetime['day'] )   &&
        !isset( $datetime['hour'] )  &&
        !isset( $datetime['min'] )   &&
        !isset( $datetime['sec'] ))
      return;
    $output     = null;
    foreach( $datetime as $dkey => & $dvalue )
      if( 'tz' != $dkey ) $dvalue = (int) $dvalue;
    $output     = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $datetime['year'], $datetime['month'], $datetime['day'] );
    if( 3 == $parno )
      return $output;
    if( !isset( $datetime['hour'] )) $datetime['hour'] = 0;
    if( !isset( $datetime['min'] ))  $datetime['min']  = 0;
    if( !isset( $datetime['sec'] ))  $datetime['sec']  = 0;
    $output    .= 'T'.sprintf( iCalUtilityFunctions::$fmt['His'], $datetime['hour'], $datetime['min'], $datetime['sec'] );
    if( isset( $datetime['tz'] ) && ( '' < trim( $datetime['tz'] ))) {
      $datetime['tz'] = trim( $datetime['tz'] );
      if( 'Z'  == $datetime['tz'] )
        $parno  = 7;
      elseif( iCalUtilityFunctions::_isOffset( $datetime['tz'] )) {
        $parno  = 7;
        $offset = iCalUtilityFunctions::_tz2offset( $datetime['tz'] );
        try {
          $d    = new DateTime( $output, new DateTimeZone( 'UTC' ));
          if( 0 != $offset ) // adjust för offset
            $d->modify( "$offset seconds" );
          $output = $d->format( 'Ymd\THis' );
        }
        catch( Exception $e ) {
          $output = date( 'Ymd\THis', mktime( $datetime['hour'], $datetime['min'], ($datetime['sec'] - $offset), $datetime['month'], $datetime['day'], $datetime['year'] ));
        }
      }
      if( 7 == $parno )
        $output .= 'Z';
    }
    return $output;
  }
/**
 * ensures internal duration format for input in array format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.4 - 2014-03-14
 * @param array $duration
 * @return array
 */
  public static function _duration2arr( $duration ) {
    $seconds        = 0;
    foreach( $duration as $durKey => $durValue ) {
      if( empty( $durValue )) continue;
      switch ( $durKey ) {
        case '0': case 'week':
          $seconds += (((int) $durValue ) * 60 * 60 * 24 * 7 );
          break;
        case '1': case 'day':
          $seconds += (((int) $durValue ) * 60 * 60 * 24 );
          break;
        case '2': case 'hour':
          $seconds += (((int) $durValue ) * 60 * 60 );
          break;
        case '3': case 'min':
          $seconds += (((int) $durValue ) * 60 );
          break;
        case '4': case 'sec':
          $seconds +=   (int) $durValue;
          break;
      }
    }
    $output         = array();
    $output['week'] = (int) floor( $seconds / ( 60 * 60 * 24 * 7 ));
    if(( 0 < $output['week'] ) && ( 0 == ( $seconds % ( 60 * 60 * 24 * 7 ))))
      return $output;
    unset( $output['week'] );
    $output['day']  = (int) floor( $seconds / ( 60 * 60 * 24 ));
    $seconds        =            ( $seconds % ( 60 * 60 * 24 ));
    $output['hour'] = (int) floor( $seconds / ( 60 * 60 ));
    $seconds        =            ( $seconds % ( 60 * 60 ));
    $output['min']  = (int) floor( $seconds /   60 );
    $output['sec']  =            ( $seconds %   60 );
    if( empty( $output['day'] ))
      unset( $output['day'] );
    if(( 0 == $output['hour'] ) && ( 0 == $output['min'] ) && ( 0 == $output['sec'] ))
      unset( $output['hour'], $output['min'], $output['sec'] );
    return $output;
  }
/**
 * convert startdate+duration to a array format datetime
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param array   $startdate
 * @param array   $duration
 * @uses iCalUtilityFunctions::$fmt
 * @return array, date format
 */
  public static function _duration2date( $startdate, $duration ) {
    $dateOnly          = ( isset( $startdate['hour'] ) || isset( $startdate['min'] ) || isset( $startdate['sec'] )) ? FALSE : TRUE;
    $startdate['hour'] = ( isset( $startdate['hour'] )) ? $startdate['hour'] : 0;
    $startdate['min']  = ( isset( $startdate['min'] ))  ? $startdate['min']  : 0;
    $startdate['sec']  = ( isset( $startdate['sec'] ))  ? $startdate['sec']  : 0;
    $dtend = 0;
    if(    isset( $duration['week'] )) $dtend += ( $duration['week'] * 7 * 24 * 60 * 60 );
    if(    isset( $duration['day'] ))  $dtend += ( $duration['day'] * 24 * 60 * 60 );
    if(    isset( $duration['hour'] )) $dtend += ( $duration['hour'] * 60 *60 );
    if(    isset( $duration['min'] ))  $dtend += ( $duration['min'] * 60 );
    if(    isset( $duration['sec'] ))  $dtend +=   $duration['sec'];
    $date     = date( iCalUtilityFunctions::$fmt['YmdHis3'],
                      mktime((int) $startdate['hour'], (int) $startdate['min'], (int) ( $startdate['sec'] + $dtend ), (int) $startdate['month'], (int) $startdate['day'], (int) $startdate['year'] ));
    $d        = explode( '-', $date );
    $dtend2   = array( 'year' => $d[0], 'month' => $d[1], 'day' => $d[2], 'hour' => $d[3], 'min' => $d[4], 'sec' => $d[5] );
    if( isset( $startdate['tz'] ))
      $dtend2['tz']   = $startdate['tz'];
    if( $dateOnly && (( 0 == $dtend2['hour'] ) && ( 0 == $dtend2['min'] ) && ( 0 == $dtend2['sec'] )))
      unset( $dtend2['hour'], $dtend2['min'], $dtend2['sec'] );
    return $dtend2;
  }
/**
 * ensures internal duration format for an input string (iCal) formatted duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-25
 * @param string $duration
 * @uses iCalUtilityFunctions::_duration2arr()
 * @return array
 */
  public static function _durationStr2arr( $duration ) {
    $duration = (string) trim( $duration );
    while( 'P' != strtoupper( substr( $duration, 0, 1 ))) {
      if( 0 < strlen( $duration ))
        $duration = substr( $duration, 1 );
      else
        return false; // no leading P !?!?
    }
    $duration = substr( $duration, 1 ); // skip P
    $duration = str_replace ( 't', 'T', $duration );
    $duration = str_replace ( 'T', '', $duration );
    $output = array();
    $val    = null;
    for( $ix=0; $ix < strlen( $duration ); $ix++ ) {
      switch( strtoupper( substr( $duration, $ix, 1 ))) {
       case 'W':
         $output['week'] = $val;
         $val            = null;
         break;
       case 'D':
         $output['day']  = $val;
         $val            = null;
         break;
       case 'H':
         $output['hour'] = $val;
         $val            = null;
         break;
       case 'M':
         $output['min']  = $val;
         $val            = null;
         break;
       case 'S':
         $output['sec']  = $val;
         $val            = null;
         break;
       default:
         if( !ctype_digit( substr( $duration, $ix, 1 )))
           return false; // unknown duration control character  !?!?
         else
           $val .= substr( $duration, $ix, 1 );
      }
    }
    return iCalUtilityFunctions::_duration2arr( $output );
  }
/**
 * creates formatted output for calendar component property data value type duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.8 - 2012-10-30
 * @param array $duration, array( week, day, hour, min, sec )
 * @return string
 */
  public static function _duration2str( array $duration ) {
    if( isset( $duration['week'] ) ||
        isset( $duration['day'] )  ||
        isset( $duration['hour'] ) ||
        isset( $duration['min'] )  ||
        isset( $duration['sec'] ))
       $ok = TRUE;
    else
      return;
    if( isset( $duration['week'] ) && ( 0 < $duration['week'] ))
      return 'P'.$duration['week'].'W';
    $output = 'P';
    if( isset($duration['day'] ) && ( 0 < $duration['day'] ))
      $output .= $duration['day'].'D';
    if(( isset( $duration['hour']) && ( 0 < $duration['hour'] )) ||
       ( isset( $duration['min'])  && ( 0 < $duration['min'] ))  ||
       ( isset( $duration['sec'])  && ( 0 < $duration['sec'] ))) {
      $output .= 'T';
      $output .= ( isset( $duration['hour']) && ( 0 < $duration['hour'] )) ? $duration['hour'].'H' : '0H';
      $output .= ( isset( $duration['min'])  && ( 0 < $duration['min'] ))  ? $duration['min']. 'M' : '0M';
      $output .= ( isset( $duration['sec'])  && ( 0 < $duration['sec'] ))  ? $duration['sec']. 'S' : '0S';
    }
    if( 'P' == $output )
      $output = 'PT0H0M0S';
    return $output;
  }
/**
 * removes expkey+expvalue from array and returns hitval (if found) else returns elseval
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-11-08
 * @param array  $array    iCal property parameters
 * @param string $expkey   expected key
 * @param string $expval   expected value
 * @param int    $hitVal   return value if found
 * @param int    $elseVal  return value if not found
 * @param int    $preSet   return value if already preset
 * @return int
 */
  public static function _existRem( & $array, $expkey, $expval=FALSE, $hitVal=null, $elseVal=null, $preSet=null ) {
    if( $preSet )
      return $preSet;
    if( !is_array( $array ) || ( 0 == count( $array )))
      return $elseVal;
    foreach( $array as $key => $value ) {
      if( strtoupper( $expkey ) == strtoupper( $key )) {
        if( !$expval || ( strtoupper( $expval ) == strtoupper( $array[$key] ))) {
          unset( $array[$key] );
          return $hitVal;
        }
      }
    }
    return $elseVal;
  }
/**
 * check if dates are in scope
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-25
 * @param object $start       datetime
 * @param object $scopeStart  datetime
 * @param object $end         datetime
 * @param object $scopeEnd    datetime
 * @param string $format
 * @return bool
 */
  public static function _inScope( $start, $scopeStart, $end, $scopeEnd, $format ) {
    return (( $start->format( $format ) >= $scopeStart->format( $format )) &&
            ( $end->format( $format )   <= $scopeEnd->format( $format )));
}
/**
 * mgnt geo part output
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.10 - 2013-09-02
 * @param float $ll
 * @param string $format
 * @return string
 */
  public static function _geo2str2( $ll, $format ) {
    if( 0.0 < $ll )
      $sign   = '+';
    else
      $sign   = ( 0.0 > $ll ) ? '-' : '';
    return rtrim( rtrim( $sign.sprintf( $format, abs( $ll )), '0' ), '.' );
  }
/**
 * checks if input contains a (array formatted) date/time
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.24 - 2013-07-02
 * @param array $input
 * @uses iCalUtilityFunctions::_strdate2date()
 * @return bool
 */
  public static function _isArrayDate( $input ) {
    if( !is_array( $input ) || isset( $input['week'] ) || isset( $input['timestamp'] ) || ( 3 > count( $input )))
      return FALSE;
    if( 7 == count( $input ))
      return TRUE;
    if( isset( $input['year'] ) && isset( $input['month'] ) && isset( $input['day'] ))
      return checkdate( (int) $input['month'], (int) $input['day'], (int) $input['year'] );
    if( isset( $input['day'] ) || isset( $input['hour'] ) || isset( $input['min'] ) || isset( $input['sec'] ))
      return FALSE;
    if(( 0 == $input[0] ) || ( 0 == $input[1] ) || ( 0 == $input[2] ))
      return FALSE;
    if(( 1970 > $input[0] ) || ( 12 < $input[1] ) || ( 31 < $input[2] ))
      return FALSE;
    if(( isset( $input[0] ) && isset( $input[1] ) && isset( $input[2] )) &&
         checkdate((int) $input[1], (int) $input[2], (int) $input[0] ))
      return TRUE;
    $input = iCalUtilityFunctions::_strdate2date( $input[1].'/'.$input[2].'/'.$input[0], 3 ); //  m - d - Y
    if( isset( $input['year'] ) && isset( $input['month'] ) && isset( $input['day'] ))
      return checkdate( (int) $input['month'], (int) $input['day'], (int) $input['year'] );
    return FALSE;
  }
/**
 * checks if input array contains a timestamp date
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-18
 * @param array $input
 * @return bool
 */
  public static function _isArrayTimestampDate( $input ) {
    return ( is_array( $input ) && isset( $input['timestamp'] )) ? TRUE : FALSE ;
  }
/**
 * controls if input string contains (trailing) UTC/iCal offset
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-21
 * @param string $input
 * @return bool
 */
  public static function _isOffset( $input ) {
    $input         = trim( (string) $input );
    if( 'Z' == substr( $input, -1 ))
      return TRUE;
    elseif((   5 <= strlen( $input )) &&
       ( in_array( substr( $input, -5, 1 ), array( '+', '-' ))) &&
       (   '0000' <= substr( $input, -4 )) && (   '9999' >= substr( $input, -4 )))
      return TRUE;
    elseif((    7 <= strlen( $input )) &&
       ( in_array( substr( $input, -7, 1 ), array( '+', '-' ))) &&
       ( '000000' <= substr( $input, -6 )) && ( '999999' >= substr( $input, -6 )))
      return TRUE;
    return FALSE;
  }
/**
 * (very simple) conversion of a MS timezone to a PHP5 valid (Date-)timezone
 * matching (MS) UCT offset and time zone descriptors
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-16
 * @param string $timezone     to convert
 * @uses iCalUtilityFunctions::_tz2offset()
 * @return bool
 */
  public static function ms2phpTZ( & $timezone ) {
    if( empty( $timezone ))
      return FALSE;
    $search = str_replace( '"', '', $timezone );
    $search = str_replace( array('GMT', 'gmt', 'utc' ), 'UTC', $search );
    if( '(UTC' != substr( $search, 0, 4 ))
      return FALSE;
    if( FALSE === ( $pos = strpos( $search, ')' )))
      return FALSE;
    $pos    = strpos( $search, ')' );
    $searchOffset = substr( $search, 4, ( $pos - 4 ));
    $searchOffset = iCalUtilityFunctions::_tz2offset( str_replace( ':', '', $searchOffset ));
    while( ' ' ==substr( $search, ( $pos + 1 )))
      $pos += 1;
    $searchText   = trim( str_replace( array( '(', ')', '&', ',', '  ' ), ' ', substr( $search, ( $pos + 1 )) ));
    $searchWords  = explode( ' ', $searchText );
    $timezone_abbreviations = DateTimeZone::listAbbreviations();
    $hits = array();
    foreach( $timezone_abbreviations as $name => $transitions ) {
      foreach( $transitions as $cnt => $transition ) {
        if( empty( $transition['offset'] )      ||
            empty( $transition['timezone_id'] ) ||
          ( $transition['offset'] != $searchOffset ))
        continue;
        $cWords = explode( '/', $transition['timezone_id'] );
        $cPrio   = $hitCnt = $rank = 0;
        foreach( $cWords as $cWord ) {
          if( empty( $cWord ))
            continue;
          $cPrio += 1;
          $sPrio  = 0;
          foreach( $searchWords as $sWord ) {
            if( empty( $sWord ) || ( 'time' == strtolower( $sWord )))
              continue;
            $sPrio += 1;
            if( strtolower( $cWord ) == strtolower( $sWord )) {
              $hitCnt += 1;
              $rank   += ( $cPrio + $sPrio );
            }
            else
              $rank += 10;
          }
        }
        if( 0 < $hitCnt ) {
          $hits[$rank][] = $transition['timezone_id'];
        }
      }
    }
    unset( $timezone_abbreviations );
    if( empty( $hits ))
      return FALSE;
    ksort( $hits );
    foreach( $hits as $rank => $tzs ) {
      if( !empty( $tzs )) {
        $timezone = reset( $tzs );
        return TRUE;
      }
    }
    return FALSE;
  }
/**
 * transforms offset in seconds to [-/+]hhmm[ss]
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2011-05-02
 * @param string $seconds
 * @return string
 */
  public static function offsetSec2His( $seconds ) {
    if( '-' == substr( $seconds, 0, 1 )) {
      $prefix  = '-';
      $seconds = substr( $seconds, 1 );
    }
    elseif( '+' == substr( $seconds, 0, 1 )) {
      $prefix  = '+';
      $seconds = substr( $seconds, 1 );
    }
    else
      $prefix  = '+';
    $output  = '';
    $hour    = (int) floor( $seconds / 3600 );
    if( 10 > $hour )
      $hour  = '0'.$hour;
    $seconds = $seconds % 3600;
    $min     = (int) floor( $seconds / 60 );
    if( 10 > $min )
      $min   = '0'.$min;
    $output  = $hour.$min;
    $seconds = $seconds % 60;
    if( 0 < $seconds) {
      if( 9 < $seconds)
        $output .= $seconds;
      else
        $output .= '0'.$seconds;
    }
    return $prefix.$output;
  }
/**
 * updates an array with dates based on a recur pattern
 *
 * if missing, UNTIL is set 1 year from startdate (emergency break)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array $result    array to update, array([Y-m-d] => bool)
 * @param array $recur     pattern for recurrency (only value part, params ignored)
 * @param mixed $wdate     component start date, string / array / (datetime) obj
 * @param mixed $fcnStart  start date, string / array / (datetime) obj
 * @param mixed $fcnEnd    end date, string / array / (datetime) obj
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_stepdate()
 * @uses iCalUtilityFunctions::_recurIntervalIx()
 * @uses iCalUtilityFunctions::_recurBYcntcheck()
 * @return void
 * @todo BYHOUR, BYMINUTE, BYSECOND, WEEKLY at year end/start OR not at all
 */
  public static function _recur2date( & $result, $recur, $wdate, $fcnStart, $fcnEnd=FALSE ) {
    if( is_string( $wdate ))
      iCalUtilityFunctions::_strDate2arr( $wdate );
    elseif( is_a( $wdate, 'DateTime' )) {
      $wdate = $wdate->format( iCalUtilityFunctions::$fmt['YmdHis2'] );
      iCalUtilityFunctions::_strDate2arr( $wdate );
    }
    foreach( $wdate as $k => $v ) if( ctype_digit( $v )) $wdate[$k] = (int) $v;
    $wdateYMD     = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $wdate['year'], $wdate['month'], $wdate['day'] );
    $wdateHis     = sprintf( iCalUtilityFunctions::$fmt['His'], $wdate['hour'], $wdate['min'],   $wdate['sec'] );
    $untilHis     = $wdateHis;
    if( is_string( $fcnStart ))
      iCalUtilityFunctions::_strDate2arr( $fcnStart );
    elseif( is_a( $fcnStart, 'DateTime' )) {
      $fcnStart = $fcnStart->format( iCalUtilityFunctions::$fmt['YmdHis2'] );
      iCalUtilityFunctions::_strDate2arr( $fcnStart );
    }
    foreach( $fcnStart as $k => $v ) if( ctype_digit( $v )) $fcnStart[$k] = (int) $v;
    $fcnStartYMD = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $fcnStart['year'], $fcnStart['month'], $fcnStart['day'] );
    if( is_string( $fcnEnd ))
      iCalUtilityFunctions::_strDate2arr( $fcnEnd );
    elseif( is_a( $fcnEnd, 'DateTime' )) {
      $fcnEnd = $fcnEnd->format( iCalUtilityFunctions::$fmt['YmdHis2'] );
      iCalUtilityFunctions::_strDate2arr( $fcnEnd );
    }
    if( !$fcnEnd ) {
      $fcnEnd = $fcnStart;
      $fcnEnd['year'] += 1;
    }
    foreach( $fcnEnd as $k => $v ) if( ctype_digit( $v )) $fcnEnd[$k] = (int) $v;
    $fcnEndYMD = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $fcnEnd['year'], $fcnEnd['month'], $fcnEnd['day'] );
// echo "<b>recur _in_ comp</b> start ".implode('-',$wdate)." period start ".implode('-',$fcnStart)." period end ".implode('-',$fcnEnd)."<br>\n";
// echo 'recur='.str_replace( array( PHP_EOL, ' ' ), '', var_export( $recur, TRUE ))."<br> \n"; // test ###
    if( !isset( $recur['COUNT'] ) && !isset( $recur['UNTIL'] ))
      $recur['UNTIL'] = $fcnEnd; // create break
    if( isset( $recur['UNTIL'] )) {
      foreach( $recur['UNTIL'] as $k => $v ) if( ctype_digit( $v )) $recur['UNTIL'][$k] = (int) $v;
      unset( $recur['UNTIL']['tz'] );
      if( $fcnEnd > $recur['UNTIL'] ) {
        $fcnEnd = $recur['UNTIL']; // emergency break
        $fcnEndYMD = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $fcnEnd['year'], $fcnEnd['month'], $fcnEnd['day'] );
      }
      if( isset( $recur['UNTIL']['hour'] ))
        $untilHis  = sprintf( iCalUtilityFunctions::$fmt['His'], $recur['UNTIL']['hour'], $recur['UNTIL']['min'], $recur['UNTIL']['sec'] );
      else
        $untilHis  = sprintf( iCalUtilityFunctions::$fmt['His'], 23, 59, 59 );
// echo 'recurUNTIL='.str_replace( array( PHP_EOL, ' ' ), '', var_export( $recur['UNTIL'], TRUE )).", untilHis={$untilHis}<br> \n"; // test ###
    }
// echo 'fcnEnd:'.$fcnEndYMD.$untilHis."<br>\n";//test
    if( $wdateYMD > $fcnEndYMD ) {
// echo 'recur out of date, '.implode('-',$wdate).', end='.implode('-',$fcnEnd)."<br>\n";//test
      return array(); // nothing to do.. .
    }
    if( !isset( $recur['FREQ'] )) // "MUST be specified.. ."
      $recur['FREQ'] = 'DAILY'; // ??
    $wkst         = ( isset( $recur['WKST'] ) && ( 'SU' == $recur['WKST'] )) ? 24*60*60 : 0; // ??
    if( !isset( $recur['INTERVAL'] ))
      $recur['INTERVAL'] = 1;
    $countcnt     = ( !isset( $recur['BYSETPOS'] )) ? 1 : 0; // DTSTART counts as the first occurrence
            /* find out how to step up dates and set index for interval count */
    $step = array();
    if( 'YEARLY' == $recur['FREQ'] )
      $step['year']  = 1;
    elseif( 'MONTHLY' == $recur['FREQ'] )
      $step['month'] = 1;
    elseif( 'WEEKLY' == $recur['FREQ'] )
      $step['day']   = 7;
    else
      $step['day']   = 1;
    if( isset( $step['year'] ) && isset( $recur['BYMONTH'] ))
      $step = array( 'month' => 1 );
    if( empty( $step ) && isset( $recur['BYWEEKNO'] )) // ??
      $step = array( 'day' => 7 );
    if( isset( $recur['BYYEARDAY'] ) || isset( $recur['BYMONTHDAY'] ) || isset( $recur['BYDAY'] ))
      $step = array( 'day' => 1 );
    $intervalarr = array();
    if( 1 < $recur['INTERVAL'] ) {
      $intervalix = iCalUtilityFunctions::_recurIntervalIx( $recur['FREQ'], $wdate, $wkst );
      $intervalarr = array( $intervalix => 0 );
    }
    if( isset( $recur['BYSETPOS'] )) { // save start date + weekno
      $bysetposymd1 = $bysetposymd2 = $bysetposw1 = $bysetposw2 = array();
// echo "bysetposXold_start=$bysetposYold $bysetposMold $bysetposDold<br>\n"; // test ###
      if( is_array( $recur['BYSETPOS'] )) {
        foreach( $recur['BYSETPOS'] as $bix => $bval )
          $recur['BYSETPOS'][$bix] = (int) $bval;
      }
      else
        $recur['BYSETPOS'] = array( (int) $recur['BYSETPOS'] );
      if( 'YEARLY' == $recur['FREQ'] ) {
        $wdate['month'] = $wdate['day'] = 1; // start from beginning of year
        $wdateYMD = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $wdate['year'], $wdate['month'], $wdate['day'] );
        iCalUtilityFunctions::_stepdate( $fcnEnd, $fcnEndYMD, array( 'year' => 1 )); // make sure to count whole last year
      }
      elseif( 'MONTHLY' == $recur['FREQ'] ) {
        $wdate['day']   = 1; // start from beginning of month
        $wdateYMD = sprintf( iCalUtilityFunctions::$fmt['Ymd'], $wdate['year'], $wdate['month'], $wdate['day'] );
        iCalUtilityFunctions::_stepdate( $fcnEnd, $fcnEndYMD, array( 'month' => 1 )); // make sure to count whole last month
      }
      else
        iCalUtilityFunctions::_stepdate( $fcnEnd, $fcnEndYMD, $step); // make sure to count whole last period
// echo "BYSETPOS endDat =".implode('-',$fcnEnd).' step='.var_export($step,TRUE)."<br>\n";//test###
      $bysetposWold = (int) date( 'W', mktime( 0, 0, $wkst, $wdate['month'], $wdate['day'], $wdate['year'] ));
      $bysetposYold = $wdate['year'];
      $bysetposMold = $wdate['month'];
      $bysetposDold = $wdate['day'];
    }
    else
      iCalUtilityFunctions::_stepdate( $wdate, $wdateYMD, $step);
    $year_old      = null;
    static $daynames = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );
             /* MAIN LOOP */
// echo "recur start:$wdateYMD, end:$fcnEndYMD<br>\n";//test
    while( TRUE ) {
// echo "recur while:$wdateYMD, end:$fcnEndYMD<br>\n";//test
      if( $wdateYMD.$wdateHis > $fcnEndYMD.$untilHis )
        break;
      if( isset( $recur['COUNT'] ) && ( $countcnt >= $recur['COUNT'] ))
        break;
      if( $year_old != $wdate['year'] ) {
        $year_old   = $wdate['year'];
        $daycnts    = array();
        $yeardays   = $weekno = 0;
        $yeardaycnt = array();
        foreach( $daynames as $dn )
          $yeardaycnt[$dn] = 0;
        for( $m = 1; $m <= 12; $m++ ) { // count up and update up-counters
          $daycnts[$m] = array();
          $weekdaycnt = array();
          foreach( $daynames as $dn )
            $weekdaycnt[$dn] = 0;
          $mcnt     = date( 't', mktime( 0, 0, 0, $m, 1, $wdate['year'] ));
          for( $d   = 1; $d <= $mcnt; $d++ ) {
            $daycnts[$m][$d] = array();
            if( isset( $recur['BYYEARDAY'] )) {
              $yeardays++;
              $daycnts[$m][$d]['yearcnt_up'] = $yeardays;
            }
            if( isset( $recur['BYDAY'] )) {
              $day    = date( 'w', mktime( 0, 0, 0, $m, $d, $wdate['year'] ));
              $day    = $daynames[$day];
              $daycnts[$m][$d]['DAY'] = $day;
              $weekdaycnt[$day]++;
              $daycnts[$m][$d]['monthdayno_up'] = $weekdaycnt[$day];
              $yeardaycnt[$day]++;
              $daycnts[$m][$d]['yeardayno_up'] = $yeardaycnt[$day];
            }
            if(  isset( $recur['BYWEEKNO'] ) || ( $recur['FREQ'] == 'WEEKLY' ))
              $daycnts[$m][$d]['weekno_up'] =(int)date('W',mktime(0,0,$wkst,$m,$d,$wdate['year']));
          } // end for( $d   = 1; $d <= $mcnt; $d++ )
        } // end for( $m = 1; $m <= 12; $m++ )
        $daycnt = 0;
        $yeardaycnt = array();
        if(  isset( $recur['BYWEEKNO'] ) || ( $recur['FREQ'] == 'WEEKLY' )) {
          $weekno = null;
          for( $d=31; $d > 25; $d-- ) { // get last weekno for year
            if( !$weekno )
              $weekno = $daycnts[12][$d]['weekno_up'];
            elseif( $weekno < $daycnts[12][$d]['weekno_up'] ) {
              $weekno = $daycnts[12][$d]['weekno_up'];
              break;
            }
          }
        }
        for( $m = 12; $m > 0; $m-- ) { // count down and update down-counters
          $weekdaycnt = array();
          foreach( $daynames as $dn )
            $yeardaycnt[$dn] = $weekdaycnt[$dn] = 0;
          $monthcnt = 0;
          $mcnt     = date( 't', mktime( 0, 0, 0, $m, 1, $wdate['year'] ));
          for( $d   = $mcnt; $d > 0; $d-- ) {
            if( isset( $recur['BYYEARDAY'] )) {
              $daycnt -= 1;
              $daycnts[$m][$d]['yearcnt_down'] = $daycnt;
            }
            if( isset( $recur['BYMONTHDAY'] )) {
              $monthcnt -= 1;
              $daycnts[$m][$d]['monthcnt_down'] = $monthcnt;
            }
            if( isset( $recur['BYDAY'] )) {
              $day  = $daycnts[$m][$d]['DAY'];
              $weekdaycnt[$day] -= 1;
              $daycnts[$m][$d]['monthdayno_down'] = $weekdaycnt[$day];
              $yeardaycnt[$day] -= 1;
              $daycnts[$m][$d]['yeardayno_down'] = $yeardaycnt[$day];
            }
            if(  isset( $recur['BYWEEKNO'] ) || ( $recur['FREQ'] == 'WEEKLY' ))
              $daycnts[$m][$d]['weekno_down'] = ($daycnts[$m][$d]['weekno_up'] - $weekno - 1);
          }
        } // end for( $m = 12; $m > 0; $m-- )
      } // end if( $year_old != $wdate['year'] )
            /* check interval */
      if( 1 < $recur['INTERVAL'] ) {
            /* create interval index */
        $intervalix = iCalUtilityFunctions::_recurIntervalIx( $recur['FREQ'], $wdate, $wkst );
            /* check interval */
        $currentKey = array_keys( $intervalarr );
        $currentKey = end( $currentKey ); // get last index
        if( $currentKey != $intervalix )
          $intervalarr = array( $intervalix => ( $intervalarr[$currentKey] + 1 ));
        if(( $recur['INTERVAL'] != $intervalarr[$intervalix] ) &&
           ( 0 != $intervalarr[$intervalix] )) {
            /* step up date */
// echo "skip: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n";//test
          iCalUtilityFunctions::_stepdate( $wdate, $wdateYMD, $step);
          continue;
        }
        else // continue within the selected interval
          $intervalarr[$intervalix] = 0;
// echo "cont: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n";//test
      } // endif( 1 < $recur['INTERVAL'] )
      $updateOK = TRUE;
      if( $updateOK && isset( $recur['BYMONTH'] ))
        $updateOK = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYMONTH']
                                           , $wdate['month']
                                           ,($wdate['month'] - 13));
      if( $updateOK && isset( $recur['BYWEEKNO'] ))
        $updateOK = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYWEEKNO']
                                           , $daycnts[$wdate['month']][$wdate['day']]['weekno_up']
                                           , $daycnts[$wdate['month']][$wdate['day']]['weekno_down'] );
      if( $updateOK && isset( $recur['BYYEARDAY'] ))
        $updateOK = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYYEARDAY']
                                           , $daycnts[$wdate['month']][$wdate['day']]['yearcnt_up']
                                           , $daycnts[$wdate['month']][$wdate['day']]['yearcnt_down'] );
      if( $updateOK && isset( $recur['BYMONTHDAY'] ))
        $updateOK = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYMONTHDAY']
                                           , $wdate['day']
                                           , $daycnts[$wdate['month']][$wdate['day']]['monthcnt_down'] );
// echo "efter BYMONTHDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'TRUE' : 'FALSE'; echo "<br>\n";//test###
      if( $updateOK && isset( $recur['BYDAY'] )) {
        $updateOK = FALSE;
        $m = $wdate['month'];
        $d = $wdate['day'];
        if( isset( $recur['BYDAY']['DAY'] )) { // single day, opt with year/month day order no
          $daynoexists = $daynosw = $daynamesw =  FALSE;
          if( $recur['BYDAY']['DAY'] == $daycnts[$m][$d]['DAY'] )
            $daynamesw = TRUE;
          if( isset( $recur['BYDAY'][0] )) {
            $daynoexists = TRUE;
            if(( isset( $recur['FREQ'] ) && ( $recur['FREQ'] == 'MONTHLY' )) || isset( $recur['BYMONTH'] ))
              $daynosw = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYDAY'][0]
                                                , $daycnts[$m][$d]['monthdayno_up']
                                                , $daycnts[$m][$d]['monthdayno_down'] );
            elseif( isset( $recur['FREQ'] ) && ( $recur['FREQ'] == 'YEARLY' ))
              $daynosw = iCalUtilityFunctions::_recurBYcntcheck( $recur['BYDAY'][0]
                                                , $daycnts[$m][$d]['yeardayno_up']
                                                , $daycnts[$m][$d]['yeardayno_down'] );
          }
          if((  $daynoexists &&  $daynosw && $daynamesw ) ||
             ( !$daynoexists && !$daynosw && $daynamesw )) {
            $updateOK = TRUE;
// echo "m=$m d=$d day=".$daycnts[$m][$d]['DAY']." yeardayno_up=".$daycnts[$m][$d]['yeardayno_up']." daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw updateOK:$updateOK<br>\n"; // test ###
          }
// echo "m=$m d=$d day=".$daycnts[$m][$d]['DAY']." yeardayno_up=".$daycnts[$m][$d]['yeardayno_up']." daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw updateOK:$updateOK<br>\n"; // test ###
        }
        else {
          foreach( $recur['BYDAY'] as $bydayvalue ) {
            $daynoexists = $daynosw = $daynamesw = FALSE;
            if( isset( $bydayvalue['DAY'] ) &&
                     ( $bydayvalue['DAY'] == $daycnts[$m][$d]['DAY'] ))
              $daynamesw = TRUE;
            if( isset( $bydayvalue[0] )) {
              $daynoexists = TRUE;
              if(( isset( $recur['FREQ'] ) && ( $recur['FREQ'] == 'MONTHLY' )) ||
                   isset( $recur['BYMONTH'] ))
                $daynosw = iCalUtilityFunctions::_recurBYcntcheck( $bydayvalue['0']
                                                  , $daycnts[$m][$d]['monthdayno_up']
                                                  , $daycnts[$m][$d]['monthdayno_down'] );
              elseif( isset( $recur['FREQ'] ) && ( $recur['FREQ'] == 'YEARLY' ))
                $daynosw = iCalUtilityFunctions::_recurBYcntcheck( $bydayvalue['0']
                                                  , $daycnts[$m][$d]['yeardayno_up']
                                                  , $daycnts[$m][$d]['yeardayno_down'] );
            }
// echo "daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw<br>\n"; // test ###
            if((  $daynoexists &&  $daynosw && $daynamesw ) ||
               ( !$daynoexists && !$daynosw && $daynamesw )) {
              $updateOK = TRUE;
              break;
            }
          }
        }
      }
// echo "efter BYDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'TRUE' : 'FALSE'; echo "<br>\n"; // test ###
            /* check BYSETPOS */
      if( $updateOK ) {
        if( isset( $recur['BYSETPOS'] ) &&
          ( in_array( $recur['FREQ'], array( 'YEARLY', 'MONTHLY', 'WEEKLY', 'DAILY' )))) {
          if( isset( $recur['WEEKLY'] )) {
            if( $bysetposWold == $daycnts[$wdate['month']][$wdate['day']]['weekno_up'] )
              $bysetposw1[] = $wdateYMD;
            else
              $bysetposw2[] = $wdateYMD;
          }
          else {
            if(( isset( $recur['FREQ'] ) && ( 'YEARLY'      == $recur['FREQ'] )  &&
                                            ( $bysetposYold == $wdate['year'] ))   ||
               ( isset( $recur['FREQ'] ) && ( 'MONTHLY'     == $recur['FREQ'] )  &&
                                           (( $bysetposYold == $wdate['year'] )  &&
                                            ( $bysetposMold == $wdate['month'] ))) ||
               ( isset( $recur['FREQ'] ) && ( 'DAILY'       == $recur['FREQ'] )  &&
                                           (( $bysetposYold == $wdate['year'] )  &&
                                            ( $bysetposMold == $wdate['month'])  &&
                                            ( $bysetposDold == $wdate['day'] )))) {
// echo "bysetposymd1[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
              $bysetposymd1[] = $wdateYMD;
            }
            else {
// echo "bysetposymd2[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
              $bysetposymd2[] = $wdateYMD;
            }
          }
        }
        else {
          if( checkdate($wdate['month'], $wdate['day'], $wdate['year'] )) {
            /* update result array if BYSETPOS is not set */
            $countcnt++;
            if( $fcnStartYMD <= $wdateYMD ) { // only output within period
              $result[$wdateYMD] = TRUE;
// echo "recur $wdateYMD<br>\n";//test
            }
          }
// else echo "recur, no date $wdateYMD<br>\n";//test
          $updateOK = FALSE;
        }
      }
            /* step up date */
      iCalUtilityFunctions::_stepdate( $wdate, $wdateYMD, $step);
            /* check if BYSETPOS is set for updating result array */
      if( $updateOK && isset( $recur['BYSETPOS'] )) {
        $bysetpos       = FALSE;
        if( isset( $recur['FREQ'] ) && ( 'YEARLY'  == $recur['FREQ'] ) &&
          ( $bysetposYold != $wdate['year'] )) {
          $bysetpos     = TRUE;
          $bysetposYold = $wdate['year'];
        }
        elseif( isset( $recur['FREQ'] ) && ( 'MONTHLY' == $recur['FREQ'] &&
         (( $bysetposYold != $wdate['year'] ) || ( $bysetposMold != $wdate['month'] )))) {
          $bysetpos     = TRUE;
          $bysetposYold = $wdate['year'];
          $bysetposMold = $wdate['month'];
        }
        elseif( isset( $recur['FREQ'] ) && ( 'WEEKLY'  == $recur['FREQ'] )) {
          $weekno = (int) date( 'W', mktime( 0, 0, $wkst, $wdate['month'], $wdate['day'], $wdate['year']));
          if( $bysetposWold != $weekno ) {
            $bysetposWold = $weekno;
            $bysetpos     = TRUE;
          }
        }
        elseif( isset( $recur['FREQ'] ) && ( 'DAILY'   == $recur['FREQ'] ) &&
         (( $bysetposYold != $wdate['year'] )  ||
          ( $bysetposMold != $wdate['month'] ) ||
          ( $bysetposDold != $wdate['day'] ))) {
          $bysetpos     = TRUE;
          $bysetposYold = $wdate['year'];
          $bysetposMold = $wdate['month'];
          $bysetposDold = $wdate['day'];
        }
        if( $bysetpos ) {
          if( isset( $recur['BYWEEKNO'] )) {
            $bysetposarr1 = & $bysetposw1;
            $bysetposarr2 = & $bysetposw2;
          }
          else {
            $bysetposarr1 = & $bysetposymd1;
            $bysetposarr2 = & $bysetposymd2;
          }

          foreach( $recur['BYSETPOS'] as $ix ) {
            if( 0 > $ix ) // both positive and negative BYSETPOS allowed
              $ix = ( count( $bysetposarr1 ) + $ix + 1);
            $ix--;
            if( isset( $bysetposarr1[$ix] )) {
              if( $fcnStartYMD <= $bysetposarr1[$ix] ) { // only output within period
//                $testweekno = (int) date( 'W', mktime( 0, 0, $wkst, (int) substr( $bysetposarr1[$ix], 4, 2 ), (int) substr( $bysetposarr1[$ix], 6, 2 ), (int) substr( $bysetposarr1[$ix], 0, 3 ))); // test ###
// echo " testYMD (weekno)=$bysetposarr1[$ix] ($testweekno)";   // test ###
                $result[$bysetposarr1[$ix]] = TRUE;
              }
              $countcnt++;
            }
            if( isset( $recur['COUNT'] ) && ( $countcnt >= $recur['COUNT'] ))
              break;
          }
// echo "<br>\n"; // test ###
          $bysetposarr1 = $bysetposarr2;
          $bysetposarr2 = array();
        } // end if( $bysetpos )
      } // end if( $updateOK && isset( $recur['BYSETPOS'] ))
    } // end while( TRUE )
// echo 'output='.str_replace( array( PHP_EOL, ' ' ), '', var_export( $result, TRUE ))."<br> \n"; // test ###
  }
/**
 * _recur2date help function, checking BYDAY (etc) hits
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param array $BYvalue
 * @param int   $upValue
 * @param int   $downValue
 * @return bool
 */
  public static function _recurBYcntcheck( $BYvalue, $upValue, $downValue ) {
    if( is_array( $BYvalue ) &&
      ( in_array( $upValue, $BYvalue ) || in_array( $downValue, $BYvalue )))
      return TRUE;
    elseif(( $BYvalue == $upValue ) || ( $BYvalue == $downValue ))
      return TRUE;
    else
      return FALSE;
  }
/**
 * _recur2date help function, (re-)calculate internal index
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param string $freq
 * @param array  $date
 * @param int    $wkst
 * @return bool
 */
  public static function _recurIntervalIx( $freq, $date, $wkst ) {
            /* create interval index */
    switch( $freq ) {
      case 'YEARLY':
        $intervalix = $date['year'];
        break;
      case 'MONTHLY':
        $intervalix = $date['year'].'-'.$date['month'];
        break;
      case 'WEEKLY':
        $intervalix = (int) date( 'W', mktime( 0, 0, $wkst, (int) $date['month'], (int) $date['day'], (int) $date['year'] ));
       break;
      case 'DAILY':
           default:
        $intervalix = $date['year'].'-'.$date['month'].'-'.$date['day'];
        break;
    }
    return $intervalix;
  }
/**
 * sort recur dates
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param array  $bydaya
 * @param array  $bydayb
 * @return int
 */
  public static function _recurBydaySort( $bydaya, $bydayb ) {
    static $days = array( 'SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6 );
    return ( $days[substr( $bydaya, -2 )] < $days[substr( $bydayb, -2 )] ) ? -1 : 1;
  }
/**
 * convert input format for exrule and rrule to internal format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array $rexrule
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_strdate2date()
 * @return array
 */
  public static function _setRexrule( $rexrule ) {
    $input          = array();
    if( empty( $rexrule ))
      return $input;
    $rexrule        = array_change_key_case( $rexrule, CASE_UPPER );
    foreach( $rexrule as $rexrulelabel => $rexrulevalue ) {
      if( 'UNTIL'  != $rexrulelabel )
        $input[$rexrulelabel]   = $rexrulevalue;
      else {
        iCalUtilityFunctions::_strDate2arr( $rexrulevalue );
        if( iCalUtilityFunctions::_isArrayTimestampDate( $rexrulevalue )) // timestamp, always date-time UTC
          $input[$rexrulelabel] = iCalUtilityFunctions::_timestamp2date( $rexrulevalue, 7, 'UTC' );
        elseif( iCalUtilityFunctions::_isArrayDate( $rexrulevalue )) { // date or UTC date-time
          $parno = ( isset( $rexrulevalue['hour'] ) || isset( $rexrulevalue[4] )) ? 7 : 3;
          $d = iCalUtilityFunctions::_chkDateArr( $rexrulevalue, $parno );
          if(( 3 < $parno ) && isset( $d['tz'] ) && ( 'Z' != $d['tz'] ) && iCalUtilityFunctions::_isOffset( $d['tz'] )) {
            $strdate              = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
            $input[$rexrulelabel] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
            unset( $input[$rexrulelabel]['unparsedtext'] );
          }
          else
           $input[$rexrulelabel] = $d;
        }
        elseif( 8 <= strlen( trim( $rexrulevalue ))) { // ex. textual date-time 2006-08-03 10:12:18 => UTC
          $input[$rexrulelabel] = iCalUtilityFunctions::_strdate2date( $rexrulevalue );
          unset( $input['$rexrulelabel']['unparsedtext'] );
        }
        if(( 3 < count( $input[$rexrulelabel] )) && !isset( $input[$rexrulelabel]['tz'] ))
          $input[$rexrulelabel]['tz'] = 'Z';
      }
    }
            /* set recurrence rule specification in rfc2445 order */
    $input2 = array();
    if( isset( $input['FREQ'] ))
      $input2['FREQ']       = $input['FREQ'];
    if( isset( $input['UNTIL'] ))
      $input2['UNTIL']      = $input['UNTIL'];
    elseif( isset( $input['COUNT'] ))
      $input2['COUNT']      = $input['COUNT'];
    if( isset( $input['INTERVAL'] ))
      $input2['INTERVAL']   = $input['INTERVAL'];
    if( isset( $input['BYSECOND'] ))
      $input2['BYSECOND']   = $input['BYSECOND'];
    if( isset( $input['BYMINUTE'] ))
      $input2['BYMINUTE']   = $input['BYMINUTE'];
    if( isset( $input['BYHOUR'] ))
      $input2['BYHOUR']     = $input['BYHOUR'];
    if( isset( $input['BYDAY'] )) {
      if( !is_array( $input['BYDAY'] )) // ensure upper case.. .
        $input2['BYDAY']    = strtoupper( $input['BYDAY'] );
      else {
        foreach( $input['BYDAY'] as $BYDAYx => $BYDAYv ) {
          if( 'DAY'        == strtoupper( $BYDAYx ))
             $input2['BYDAY']['DAY'] = strtoupper( $BYDAYv );
          elseif( !is_array( $BYDAYv )) {
             $input2['BYDAY'][$BYDAYx]  = $BYDAYv;
          }
          else {
            foreach( $BYDAYv as $BYDAYx2 => $BYDAYv2 ) {
              if( 'DAY'    == strtoupper( $BYDAYx2 ))
                 $input2['BYDAY'][$BYDAYx]['DAY'] = strtoupper( $BYDAYv2 );
              else
                 $input2['BYDAY'][$BYDAYx][$BYDAYx2] = $BYDAYv2;
            }
          }
        }
      }
    }
    if( isset( $input['BYMONTHDAY'] ))
      $input2['BYMONTHDAY'] = $input['BYMONTHDAY'];
    if( isset( $input['BYYEARDAY'] ))
      $input2['BYYEARDAY']  = $input['BYYEARDAY'];
    if( isset( $input['BYWEEKNO'] ))
      $input2['BYWEEKNO']   = $input['BYWEEKNO'];
    if( isset( $input['BYMONTH'] ))
      $input2['BYMONTH']    = $input['BYMONTH'];
    if( isset( $input['BYSETPOS'] ))
      $input2['BYSETPOS']   = $input['BYSETPOS'];
    if( isset( $input['WKST'] ))
      $input2['WKST']       = $input['WKST'];
    return $input2;
  }
/**
 * convert format for input date to internal date with parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param mixed  $year
 * @param mixed  $month   optional
 * @param int    $day     optional
 * @param int    $hour    optional
 * @param int    $min     optional
 * @param int    $sec     optional
 * @param string $tz      optional
 * @param array  $params  optional
 * @param string $caller  optional
 * @param string $objName optional
 * @param string $tzid    optional
 * @uses iCalUtilityFunctions::$tzComps
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isArrayDate()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_existRem()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_strdate2date()
 * @return array
 */
  public static function _setDate( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE, $caller=null, $objName=null, $tzid=FALSE ) {
    $input = $parno = null;
    $localtime = (( 'dtstart' == $caller ) && in_array( $objName, iCalUtilityFunctions::$tzComps )) ? TRUE : FALSE;
    iCalUtilityFunctions::_strDate2arr( $year );
    if( iCalUtilityFunctions::_isArrayDate( $year )) {
      $input['value']  = iCalUtilityFunctions::_chkDateArr( $year, FALSE ); //$parno );
      if( 100 > $input['value']['year'] )
        $input['value']['year'] += 2000;
      if( $localtime )
        unset( $month['VALUE'], $month['TZID'] );
      elseif( !isset( $month['TZID'] ) && isset( $tzid ))
        $month['TZID'] = $tzid;
      if( isset( $input['value']['tz'] ) && iCalUtilityFunctions::_isOffset( $input['value']['tz'] ))
        unset( $month['TZID'] );
      elseif( !isset( $input['value']['tz'] ) &&  isset( $month['TZID'] ) && iCalUtilityFunctions::_isOffset( $month['TZID'] )) {
        $input['value']['tz'] = $month['TZID'];
        unset( $month['TZID'] );
      }
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      $hitval          = ( isset( $input['value']['tz'] )) ? 7 : 6;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval );
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3, count( $input['value'] ), $parno );
      if( 6 > $parno )
        unset( $input['value']['tz'], $input['params']['TZID'], $tzid );
      if(( 6 <= $parno ) && isset( $input['value']['tz'] ) && ( 'Z' != $input['value']['tz'] ) && iCalUtilityFunctions::_isOffset( $input['value']['tz'] )) {
        $d             = $input['value'];
        $strdate       = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, $parno );
        unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
      }
      if( isset( $input['value']['tz'] ) && !iCalUtilityFunctions::_isOffset( $input['value']['tz'] )) {
        $input['params']['TZID'] = $input['value']['tz'];
        unset( $input['value']['tz'] );
      }
    } // end if( iCalUtilityFunctions::_isArrayDate( $year ))
    elseif( iCalUtilityFunctions::_isArrayTimestampDate( $year )) {
      if( $localtime ) unset ( $month['VALUE'], $month['TZID'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3 );
      $hitval          = 7;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval, $parno );
      if( isset( $year['tz'] ) && !empty( $year['tz'] )) {
        if( !iCalUtilityFunctions::_isOffset( $year['tz'] )) {
          $input['params']['TZID'] = $year['tz'];
          unset( $year['tz'], $tzid );
        }
        else {
          if( isset( $input['params']['TZID'] ) && !empty( $input['params']['TZID'] )) {
            if( !iCalUtilityFunctions::_isOffset( $input['params']['TZID'] ))
              unset( $tzid );
            else
              unset( $input['params']['TZID']);
          }
          elseif( isset( $tzid ) && !iCalUtilityFunctions::_isOffset( $tzid ))
            $input['params']['TZID'] = $tzid;
        }
      }
      elseif( isset( $input['params']['TZID'] ) && !empty( $input['params']['TZID'] )) {
        if( iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
          $year['tz'] = $input['params']['TZID'];
          unset( $input['params']['TZID']);
          if( isset( $tzid ) && !empty( $tzid ) && !iCalUtilityFunctions::_isOffset( $tzid ))
            $input['params']['TZID'] = $tzid;
        }
      }
      elseif( isset( $tzid ) && !empty( $tzid )) {
        if( iCalUtilityFunctions::_isOffset( $tzid )) {
          $year['tz'] = $tzid;
          unset( $input['params']['TZID']);
        }
        else
          $input['params']['TZID'] = $tzid;
      }
      $input['value']  = iCalUtilityFunctions::_timestamp2date( $year, $parno );
    } // end elseif( iCalUtilityFunctions::_isArrayTimestampDate( $year ))
    elseif( 8 <= strlen( trim((string) $year ))) { // ex. 2006-08-03 10:12:18 [[[+/-]1234[56]] / timezone]
      if( $localtime )
        unset( $month['VALUE'], $month['TZID'] );
      elseif( !isset( $month['TZID'] ) && !empty( $tzid ))
        $month['TZID'] = $tzid;
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', 7, $parno );
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3, $parno, $parno );
      $input['value']  = iCalUtilityFunctions::_strdate2date( $year, $parno );
      if( 3 == $parno )
        unset( $input['value']['tz'], $input['params']['TZID'] );
      unset( $input['value']['unparsedtext'] );
      if( isset( $input['value']['tz'] )) {
        if( iCalUtilityFunctions::_isOffset( $input['value']['tz'] )) {
          $d           = $input['value'];
          $strdate     = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
          $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
          unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
        }
        else {
          $input['params']['TZID'] = $input['value']['tz'];
          unset( $input['value']['tz'] );
        }
      }
      elseif( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
        $d             = $input['value'];
        $strdate       = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $input['params']['TZID'] );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
        unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
      }
    } // end elseif( 8 <= strlen( trim((string) $year )))
    else {
      if( is_array( $params ))
        $input['params'] = iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' ));
      elseif( is_array( $tz )) {
        $input['params'] = iCalUtilityFunctions::_setParams( $tz,     array( 'VALUE' => 'DATE-TIME' ));
        $tz = FALSE;
      }
      elseif( is_array( $hour )) {
        $input['params'] = iCalUtilityFunctions::_setParams( $hour,   array( 'VALUE' => 'DATE-TIME' ));
        $hour = $min = $sec = $tz = FALSE;
      }
      if( $localtime )
        unset ( $input['params']['VALUE'], $input['params']['TZID'] );
      elseif( !isset( $tz ) && !isset( $input['params']['TZID'] ) && !empty( $tzid ))
        $input['params']['TZID'] = $tzid;
      elseif( isset( $tz ) && iCalUtilityFunctions::_isOffset( $tz ))
        unset( $input['params']['TZID'] );
      elseif( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
        $tz            = $input['params']['TZID'];
        unset( $input['params']['TZID'] );
      }
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3 );
      $hitval          = ( iCalUtilityFunctions::_isOffset( $tz )) ? 7 : 6;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval, $parno, $parno );
      $input['value']  = array( 'year'  => $year, 'month' => $month, 'day'   => $day );
      if( 3 != $parno ) {
        $input['value']['hour'] = ( $hour ) ? $hour : '0';
        $input['value']['min']  = ( $min )  ? $min  : '0';
        $input['value']['sec']  = ( $sec )  ? $sec  : '0';
        if( !empty( $tz ))
          $input['value']['tz'] = $tz;
        $strdate       = iCalUtilityFunctions::_date2strdate( $input['value'], $parno );
        if( !empty( $tz ) && !iCalUtilityFunctions::_isOffset( $tz ))
          $strdate    .= ( 'Z' == $tz ) ? $tz : ' '.$tz;
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, $parno );
        unset( $input['value']['unparsedtext'] );
        if( isset( $input['value']['tz'] )) {
          if( iCalUtilityFunctions::_isOffset( $input['value']['tz'] )) {
            $d           = $input['value'];
            $strdate     = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
            $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
            unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
          }
          else {
            $input['params']['TZID'] = $input['value']['tz'];
            unset( $input['value']['tz'] );
          }
        }
        elseif( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
          $d             = $input['value'];
          $strdate       = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $input['params']['TZID'] );
          $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
          unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
        }
      }
    } // end else (i.e. using all arguments)
    if(( 3 == $parno ) || ( isset( $input['params']['VALUE'] ) && ( 'DATE' == $input['params']['VALUE'] ))) {
      $input['params']['VALUE'] = 'DATE';
      unset( $input['value']['hour'], $input['value']['min'], $input['value']['sec'], $input['value']['tz'], $input['params']['TZID'] );
    }
    elseif( isset( $input['params']['TZID'] )) {
      if(( 'UTC' == strtoupper( $input['params']['TZID'] )) || ( 'GMT' == strtoupper( $input['params']['TZID'] ))) {
        $input['value']['tz'] = 'Z';
        unset( $input['params']['TZID'] );
      }
      else
        unset( $input['value']['tz'] );
    }
    elseif( isset( $input['value']['tz'] )) {
      if(( 'UTC' == strtoupper( $input['value']['tz'] )) || ( 'GMT' == strtoupper( $input['value']['tz'] )))
        $input['value']['tz'] = 'Z';
      if( 'Z' != $input['value']['tz'] ) {
        $input['params']['TZID'] = $input['value']['tz'];
        unset( $input['value']['tz'] );
      }
      else
        unset( $input['params']['TZID'] );
    }
    if( $localtime )
      unset( $input['value']['tz'], $input['params']['TZID'] );
    return $input;
  }
/**
 * convert format for input date (UTC) to internal date with parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param mixed $year
 * @param mixed $month  optional
 * @param int   $day    optional
 * @param int   $hour   optional
 * @param int   $min    optional
 * @param int   $sec    optional
 * @param array $params optional
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isArrayDate()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_strdate2date()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses iCalUtilityFunctions::_existRem()
 * @return array
 */
  public static function _setDate2( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    $input = null;
    iCalUtilityFunctions::_strDate2arr( $year );
    if( iCalUtilityFunctions::_isArrayDate( $year )) {
      $input['value']  = iCalUtilityFunctions::_chkDateArr( $year, 7 );
      if( isset( $input['value']['year'] ) && ( 100 > $input['value']['year'] ))
        $input['value']['year'] += 2000;
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      unset( $input['params']['VALUE']  );
      if( isset( $input['value']['tz'] ) && iCalUtilityFunctions::_isOffset( $input['value']['tz'] ))
        $tzid = $input['value']['tz'];
      elseif( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] ))
        $tzid = $input['params']['TZID'];
      else
        $tzid = '';
      unset( $input['params']['VALUE'], $input['params']['TZID']  );
      if( !empty( $tzid ) && ( 'Z' != $tzid ) && iCalUtilityFunctions::_isOffset( $tzid )) {
        $d             = $input['value'];
        $strdate       = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $tzid );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
        unset( $input['value']['unparsedtext'] );
      }
    }
    elseif( iCalUtilityFunctions::_isArrayTimestampDate( $year )) {
      if( isset( $year['tz'] ) && ! iCalUtilityFunctions::_isOffset( $year['tz'] ))
        $year['tz']    = 'UTC';
      elseif( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] ))
        $year['tz']    = $input['params']['TZID'];
      else
        $year['tz']    = 'UTC';
      $input['value']  = iCalUtilityFunctions::_timestamp2date( $year, 7 );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      unset( $input['params']['VALUE'], $input['params']['TZID']  );
    }
    elseif( 8 <= strlen( trim((string) $year ))) { // ex. 2006-08-03 10:12:18
      $input['value']  = iCalUtilityFunctions::_strdate2date( $year, 7 );
      unset( $input['value']['unparsedtext'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      if(( !isset( $input['value']['tz'] ) || empty( $input['value']['tz'] )) && isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
        $d             = $input['value'];
        $strdate       = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $input['params']['TZID'] );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
        unset( $input['value']['unparsedtext'] );
      }
      unset( $input['params']['VALUE'], $input['params']['TZID']  );
    }
    else {
      $input['value']  = array( 'year'  => $year
                              , 'month' => $month
                              , 'day'   => $day
                              , 'hour'  => $hour
                              , 'min'   => $min
                              , 'sec'   => $sec );
      if(  isset( $tz )) $input['value']['tz'] = $tz;
      if(( isset( $tz ) && iCalUtilityFunctions::_isOffset( $tz )) ||
         ( isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] ))) {
          if( !isset( $tz ) && isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] ))
            $input['value']['tz'] = $input['params']['TZID'];
          unset( $input['params']['TZID'] );
        $strdate        = iCalUtilityFunctions::_date2strdate( $input['value'], 7 );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
        unset( $input['value']['unparsedtext'] );
      }
      $input['params'] = iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' ));
      unset( $input['params']['VALUE']  );
    }
    $parno = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', 7 ); // remove default
    if( !isset( $input['value']['hour'] )) $input['value']['hour'] = 0;
    if( !isset( $input['value']['min'] ))  $input['value']['min']  = 0;
    if( !isset( $input['value']['sec'] ))  $input['value']['sec']  = 0;
    $input['value']['tz'] = 'Z';
    return $input;
  }
/**
 * check index and set (an indexed) content in multiple value array
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param array $valArr
 * @param mixed $value
 * @param array $params
 * @param array $defaults
 * @param int $index
 * @uses iCalUtilityFunctions::_setParams()
 * @return void
 */
  public static function _setMval( & $valArr, $value, $params=FALSE, $defaults=FALSE, $index=FALSE ) {
    if( !is_array( $valArr )) $valArr = array();
    if( $index )
      $index = $index - 1;
    elseif( 0 < count( $valArr )) {
      $keys  = array_keys( $valArr );
      $index = end( $keys ) + 1;
    }
    else
      $index = 0;
    $valArr[$index] = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params, $defaults ));
    ksort( $valArr );
  }
/**
 * set input (formatted) parameters- component property attributes
 *
 * default parameters can be set, if missing
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.10 - 2013-09-04
 * @param array $params
 * @param array $defaults
 * @return array
 */
  public static function _setParams( $params, $defaults=FALSE ) {
    if( !is_array( $params))
      $params = array();
    $input    = array();
    $params   = array_change_key_case( $params, CASE_UPPER );
    foreach( $params as $paramKey => $paramValue ) {
      if( is_array( $paramValue )) {
        foreach( $paramValue as $pkey => $pValue ) {
          if(( '"' == substr( $pValue, 0, 1 )) && ( '"' == substr( $pValue, -1 )))
            $paramValue[$pkey] = substr( $pValue, 1, ( strlen( $pValue ) - 2 ));
        }
      }
      elseif(( '"' == substr( $paramValue, 0, 1 )) && ( '"' == substr( $paramValue, -1 )))
        $paramValue = substr( $paramValue, 1, ( strlen( $paramValue ) - 2 ));
      if( 'VALUE' == $paramKey )
        $input['VALUE']   = strtoupper( $paramValue );
      else
        $input[$paramKey] = $paramValue;
    }
    if( is_array( $defaults )) {
      foreach( $defaults as $paramKey => $paramValue ) {
        if( !isset( $input[$paramKey] ))
          $input[$paramKey] = $paramValue;
      }
    }
    return (0 < count( $input )) ? $input : null;
  }
/**
 * set sort arguments/parameters in component
 *
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param object $c       valendar component
 * @param string $sortArg
 * @uses calendarComponent::$srtk
 * @uses calendarComponent::$objName
 * @uses calendarComponent::$getProperty()
 * @uses iCalUtilityFunctions::$mProps1
 * @uses calendarComponent::_getProperties()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @return void
 */
  public static function _setSortArgs( $c, $sortArg=FALSE ) {
    $c->srtk = array( '0', '0', '0', '0' );
    if( 'vtimezone' == $c->objName ) {
      if( FALSE === ( $c->srtk[0] = $c->getProperty( 'tzid' )))
        $c->srtk[0] = 0;
      return;
    }
    elseif( $sortArg ) {
      if( in_array( $sortArg, iCalUtilityFunctions::$mProps1 )) {
        $propValues = array();
        $c->_getProperties( $sortArg, $propValues );
        if( !empty( $propValues )) {
          $sk         = array_keys( $propValues );
          $c->srtk[0] = $sk[0];
          if( 'RELATED-TO'  == $sortArg )
            $c->srtk[0] .= $c->getProperty( 'uid' );
        }
        elseif( 'RELATED-TO'  == $sortArg )
          $c->srtk[0] = $c->getProperty( 'uid' );
      }
      elseif( FALSE !== ( $d = $c->getProperty( $sortArg ))) {
        $c->srtk[0] = $d;
        if( 'UID' == $sortArg ) {
          if( FALSE !== ( $d = $c->getProperty( 'recurrence-id' ))) {
            $c->srtk[1] = iCalUtilityFunctions::_date2strdate( $d );
            if( FALSE === ( $c->srtk[2] = $c->getProperty( 'sequence' )))
              $c->srtk[2] = PHP_INT_MAX;
          }
          else
            $c->srtk[1] = $c->srtk[2] = PHP_INT_MAX;
        }
      }
      return;
    } // end elseif( $sortArg )
    if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTSTART' ))) {
      $c->srtk[0] = iCalUtilityFunctions::_strdate2date( $d[1] );
      unset( $c->srtk[0]['unparsedtext'] );
    }
    elseif( FALSE === ( $c->srtk[0] = $c->getProperty( 'dtstart' )))
      $c->srtk[0] = 0;                                                // sortkey 0 : dtstart
    if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTEND' ))) {
      $c->srtk[1] = iCalUtilityFunctions::_strdate2date( $d[1] );     // sortkey 1 : dtend/due(/duration)
      unset( $c->srtk[1]['unparsedtext'] );
    }
    elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'dtend' ))) {
      if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DUE' ))) {
        $c->srtk[1] = iCalUtilityFunctions::_strdate2date( $d[1] );
        unset( $c->srtk[1]['unparsedtext'] );
      }
      elseif( FALSE === ( $c->srtk[1] = $c->getProperty( 'due' )))
        if( FALSE === ( $c->srtk[1] = $c->getProperty( 'duration', FALSE, FALSE, TRUE )))
          $c->srtk[1] = 0;
    }
    if( FALSE === ( $c->srtk[2] = $c->getProperty( 'created' )))      // sortkey 2 : created/dtstamp
      if( FALSE === ( $c->srtk[2] = $c->getProperty( 'dtstamp' )))
        $c->srtk[2] = 0;
    if( FALSE === ( $c->srtk[3] = $c->getProperty( 'uid' )))          // sortkey 3 : uid
      $c->srtk[3] = 0;
  }
/**
 * break lines at pos 75
 *
 * Lines of text SHOULD NOT be longer than 75 octets, excluding the line
 * break. Long content lines SHOULD be split into a multiple line
 * representations using a line "folding" technique. That is, a long
 * line can be split between any two characters by inserting a CRLF
 * immediately followed by a single linear white space character (i.e.,
 * SPACE, US-ASCII decimal 32 or HTAB, US-ASCII decimal 9). Any sequence
 * of CRLF followed immediately by a single linear white space character
 * is ignored (i.e., removed) when processing the content type.
 *
 * Edited 2007-08-26 by Anders Litzell, anders@litzell.se to fix bug where
 * the reserved expression "\n" in the arg $string could be broken up by the
 * folding of lines, causing ambiguity in the return string.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @param string $string
 * @param string $nl
 * @return string
 */
  public static function _size75( $string, $nl ) {
    $tmp             = $string;
    $string          = '';
    $cCnt = $x       = 0;
    while( TRUE ) {
      if( !isset( $tmp[$x] )) {
        $string     .= $nl;                           // loop breakes here
        break;
      }
      elseif(( 74   <= $cCnt ) && ( '\\'  == $tmp[$x] ) && ( 'n' == $tmp[$x+1] )) {
        $string     .= $nl.' \n';                     // don't break lines inside '\n'
        $x          += 2;
        if( !isset( $tmp[$x] )) {
          $string   .= $nl;
          break;
        }
        $cCnt        = 3;
      }
      elseif( 75    <= $cCnt ) {
        $string     .= $nl.' ';
        $cCnt        = 1;
      }
      $byte          = ord( $tmp[$x] );
      $string       .= $tmp[$x];
      switch( TRUE ) { // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        case(( $byte >= 0x20 ) && ( $byte <= 0x7F )): // characters U-00000000 - U-0000007F (same as ASCII)
          $cCnt     += 1;
          break;                                      // add a one byte character
        case(( $byte & 0xE0) == 0xC0 ):               // characters U-00000080 - U-000007FF, mask 110XXXXX
          if( isset( $tmp[$x+1] )) {
            $cCnt   += 1;
            $string  .= $tmp[$x+1];
            $x       += 1;                            // add a two bytes character
          }
          break;
        case(( $byte & 0xF0 ) == 0xE0 ):              // characters U-00000800 - U-0000FFFF, mask 1110XXXX
          if( isset( $tmp[$x+2] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1].$tmp[$x+2];
            $x      += 2;                             // add a three bytes character
          }
          break;
        case(( $byte & 0xF8 ) == 0xF0 ):              // characters U-00010000 - U-001FFFFF, mask 11110XXX
          if( isset( $tmp[$x+3] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1].$tmp[$x+2].$tmp[$x+3];
            $x      += 3;                             // add a four bytes character
          }
          break;
        case(( $byte & 0xFC ) == 0xF8 ):              // characters U-00200000 - U-03FFFFFF, mask 111110XX
          if( isset( $tmp[$x+4] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1].$tmp[$x+2].$tmp[$x+3].$tmp[$x+4];
            $x      += 4;                             // add a five bytes character
          }
          break;
        case(( $byte & 0xFE ) == 0xFC ):              // characters U-04000000 - U-7FFFFFFF, mask 1111110X
          if( isset( $tmp[$x+5] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1].$tmp[$x+2].$tmp[$x+3].$tmp[$x+4].$tmp[$x+5];
            $x      += 5;                             // add a six bytes character
          }
        default:                                      // add any other byte without counting up $cCnt
          break;
      } // end switch( TRUE )
      $x         += 1;                                // next 'byte' to test
    } // end while( TRUE ) {
    return $string;
  }
/**
 * sort callback function for exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param array $a
 * @param array $b
 * @uses iCalUtilityFunctions::$fmt
 * @return int
 */
  public static function _sortExdate1( $a, $b ) {
    $as  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $a['year'], (int) $a['month'], (int) $a['day'] );
    $as .= ( isset( $a['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $a['hour'], (int) $a['min'], (int) $a['sec'] ) : '';
    $bs  = sprintf( iCalUtilityFunctions::$fmt['His'], (int) $b['year'], (int) $b['month'], (int) $b['day'] );
    $bs .= ( isset( $b['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $b['hour'], (int) $b['min'], (int) $b['sec'] ) : '';
    return strcmp( $as, $bs );
  }
/**
 * sort callback function for exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param array $a
 * @param array $b
 * @uses iCalUtilityFunctions::$fmt
 * @return int
 */
  public static function _sortExdate2( $a, $b ) {
    $val = reset( $a['value'] );
    $as  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
    $as .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    $val = reset( $b['value'] );
    $bs  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
    $bs .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    return strcmp( $as, $bs );
  }
/**
 * sort callback function for rdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param array $a
 * @param array $b
 * @uses iCalUtilityFunctions::$fmt
 * @return int
 */
  public static function _sortRdate1( $a, $b ) {
    $val = isset( $a['year'] ) ? $a : $a[0];
    $as  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
    $as .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    $val = isset( $b['year'] ) ? $b : $b[0];
    $bs  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
    $bs .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    return strcmp( $as, $bs );
  }
/**
 * sort callback function for rdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param array $a
 * @param array $b
 * @uses iCalUtilityFunctions::$fmt
 * @return int
 */
  public static function _sortRdate2( $a, $b ) {
    $val   = isset( $a['value'][0]['year'] ) ? $a['value'][0] : $a['value'][0][0];
    if( empty( $val ))
      $as  = '';
    else {
      $as  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
      $as .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    }
    $val   = isset( $b['value'][0]['year'] ) ? $b['value'][0] : $b['value'][0][0];
    if( empty( $val ))
      $bs  = '';
    else {
      $bs  = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $val['year'], (int) $val['month'], (int) $val['day'] );
      $bs .= ( isset( $val['hour'] )) ? sprintf( iCalUtilityFunctions::$fmt['His'], (int) $val['hour'], (int) $val['min'], (int) $val['sec'] ) : '';
    }
    return strcmp( $as, $bs );
  }
/**
 * separate property attributes from property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.6 - 2013-08-29
 * @param string $line      property content
 * @param array  $propAttr  property parameters
 * @uses iCalUtilityFunctions::$parValPrefix
 * @return void
 */
  public static function _splitContent( & $line, & $propAttr=null ) {
    $attr         = array();
    $attrix       = -1;
    $clen         = strlen( $line );
    $WithinQuotes = FALSE;
    $cix          = 0;
    while( FALSE !== substr( $line, $cix, 1 )) {
      if(  ! $WithinQuotes  &&   (  ':' == $line[$cix] )                         &&
                                 ( substr( $line,$cix,     3 )  != '://' )       &&
         ( ! in_array( strtolower( substr( $line,$cix - 6, 4 )), iCalUtilityFunctions::$parValPrefix['MStz'] ))   &&
         ( ! in_array( strtolower( substr( $line,$cix - 3, 4 )), iCalUtilityFunctions::$parValPrefix['Proto3'] )) &&
         ( ! in_array( strtolower( substr( $line,$cix - 4, 5 )), iCalUtilityFunctions::$parValPrefix['Proto4'] )) &&
         ( ! in_array( strtolower( substr( $line,$cix - 6, 7 )), iCalUtilityFunctions::$parValPrefix['Proto6'] ))) {
        $attrEnd = TRUE;
        if(( $cix < ( $clen - 4 )) &&
             ctype_digit( substr( $line, $cix+1, 4 ))) { // an URI with a (4pos) portnr??
          for( $c2ix = $cix; 3 < $c2ix; $c2ix-- ) {
            if( '://' == substr( $line, $c2ix - 2, 3 )) {
              $attrEnd = FALSE;
              break; // an URI with a portnr!!
            }
          }
        }
        if( $attrEnd) {
          $line = substr( $line, ( $cix + 1 ));
          break;
        }
        $cix++;
      }
      if( '"' == $line[$cix] )
        $WithinQuotes = ! $WithinQuotes;
      if( ';' == $line[$cix] )
        $attr[++$attrix] = null;
      else
        $attr[$attrix] .= $line[$cix];
      $cix++;
    }
            /* make attributes in array format */
    $propAttr = array();
    foreach( $attr as $attribute ) {
      $attrsplit = explode( '=', $attribute, 2 );
      if( 1 < count( $attrsplit ))
        $propAttr[$attrsplit[0]] = $attrsplit[1];
    }
  }
/**
 * step date, return updated date, array and timpstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array  $date     date to step
 * @param string $dateYMD  date YMD
 * @param array  $step     default array( 'day' => 1 )
 * @uses iCalUtilityFunctions::$fmt
 * @return void
 */
  public static function _stepdate( & $date, & $dateYMD, $step=array( 'day' => 1 )) {
    if( !isset( $date['hour'] )) $date['hour'] = 0;
    if( !isset( $date['min'] ))  $date['min']  = 0;
    if( !isset( $date['sec'] ))  $date['sec']  = 0;
    if( isset( $step['day'] ))
      $mcnt        = date( 't', mktime( (int) $date['hour'], (int) $date['min'], (int) $date['sec'], (int) $date['month'], (int) $date['day'], (int) $date['year'] ));
    foreach( $step as $stepix => $stepvalue )
      $date[$stepix]   += $stepvalue;
    if( isset( $step['month'] )) {
      if( 12 < $date['month'] ) {
        $date['year']  += 1;
        $date['month'] -= 12;
      }
    }
    elseif( isset( $step['day'] )) {
      if( $mcnt < $date['day'] ) {
        $date['day']   -= $mcnt;
        $date['month'] += 1;
        if( 12 < $date['month'] ) {
          $date['year']  += 1;
          $date['month'] -= 12;
        }
      }
    }
    $dateYMD       = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $date['year'], (int) $date['month'], (int) $date['day'] );
    unset( $mcnt );
  }
/**
 * convert a date from specific string to array format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.8 - 2012-01-27
 * @param mixed $input
 * @return bool, TRUE on success
 */
  public static function _strDate2arr( & $input ) {
    if( is_array( $input ))
      return FALSE;
    if( 5 > strlen( (string) $input ))
      return FALSE;
    $work = $input;
    if( 2 == substr_count( $work, '-' ))
      $work = str_replace( '-', '', $work );
    if( 2 == substr_count( $work, '/' ))
      $work = str_replace( '/', '', $work );
    if( !ctype_digit( substr( $work, 0, 8 )))
      return FALSE;
    $temp = array( 'year'  => (int) substr( $work,  0, 4 )
                 , 'month' => (int) substr( $work,  4, 2 )
                 , 'day'   => (int) substr( $work,  6, 2 ));
    if( !checkdate( $temp['month'], $temp['day'], $temp['year'] ))
      return FALSE;
    if( 8 == strlen( $work )) {
      $input = $temp;
      return TRUE;
    }
    if(( ' ' == substr( $work, 8, 1 )) || ( 'T' == substr( $work, 8, 1 )) || ( 't' == substr( $work, 8, 1 )))
      $work =  substr( $work, 9 );
    elseif( ctype_digit( substr( $work, 8, 1 )))
      $work = substr( $work, 8 );
    else
     return FALSE;
    if( 2 == substr_count( $work, ':' ))
      $work = str_replace( ':', '', $work );
    if( !ctype_digit( substr( $work, 0, 4 )))
      return FALSE;
    $temp['hour']  = substr( $work, 0, 2 );
    $temp['min']   = substr( $work, 2, 2 );
    if((( 0 > $temp['hour'] ) || ( $temp['hour'] > 23 )) ||
       (( 0 > $temp['min'] )  || ( $temp['min']  > 59 )))
      return FALSE;
    if( ctype_digit( substr( $work, 4, 2 ))) {
      $temp['sec'] = substr( $work, 4, 2 );
      if((  0 > $temp['sec'] ) || ( $temp['sec']  > 59 ))
        return FALSE;
      $len = 6;
    }
    else {
      $temp['sec'] = 0;
      $len = 4;
    }
    if( $len < strlen( $work))
      $temp['tz'] = trim( substr( $work, 6 ));
    $input = $temp;
    return TRUE;
  }
/**
 * ensures internal date-time/date format for input date-time/date in string fromat
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-15
 * Modified to also return original string value by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * @param array $datetime
 * @param int   $parno optional, default FALSE
 * @param moxed $wtz optional, default null
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_tz2offset()
 * @uses iCalUtilityFunctions::$fmt
 * @return array
 */
  public static function _strdate2date( $datetime, $parno=FALSE, $wtz=null ) {
    $unparseddatetime = $datetime;
    $datetime   = (string) trim( $datetime );
    $tz         = null;
    $offset     = 0;
    $tzSts      = FALSE;
    $len        = strlen( $datetime );
    if( 'Z' == substr( $datetime, -1 )) {
      $tz       = 'Z';
      $datetime = trim( substr( $datetime, 0, ( $len - 1 )));
      $tzSts    = TRUE;
    }
    if( iCalUtilityFunctions::_isOffset( substr( $datetime, -5, 5 ))) { // [+/-]NNNN offset
      $tz       = substr( $datetime, -5, 5 );
      $datetime = trim( substr( $datetime, 0, ($len - 5)));
    }
    elseif( iCalUtilityFunctions::_isOffset( substr( $datetime, -7, 7 ))) { // [+/-]NNNNNN offset
      $tz       = substr( $datetime, -7, 7 );
      $datetime = trim( substr( $datetime, 0, ($len - 7)));
    }
    elseif( empty( $wtz ) && ctype_digit( substr( $datetime, 0, 4 )) && ctype_digit( substr( $datetime, -2, 2 )) && iCalUtilityFunctions::_strDate2arr( $datetime )) {
      $output = $datetime;
      if( !empty( $tz ))
        $output['tz'] = 'Z';
      $output['unparsedtext'] = $unparseddatetime;
      return $output;
    }
    else {
      $cx  = $tx = 0;    //  find any trailing timezone or offset
      $len = strlen( $datetime );
      for( $cx = -1; $cx > ( 9 - $len ); $cx-- ) {
        $char = substr( $datetime, $cx, 1 );
        if(( ' ' == $char ) || ctype_digit( $char ))
          break; // if exists, tz ends here.. . ?
        else
           $tx--; // tz length counter
      }
      if( 0 > $tx ) { // if any
        $tz     = substr( $datetime, $tx );
        $datetime = trim( substr( $datetime, 0, $len + $tx ));
      }
      if(( ctype_digit( substr( $datetime, 0, 8 )) && ( 'T' ==  substr( $datetime, 8, 1 )) && ctype_digit( substr( $datetime, -6, 6 ))) ||
         ( ctype_digit( substr( $datetime, 0, 14 ))))
        $tzSts  = TRUE;
    }
    if( empty( $tz ) && !empty( $wtz ))
      $tz       = $wtz;
    if( 3 == $parno )
      $tz       = null;
    if( !empty( $tz )) { // tz set
      if(( 'Z' != $tz ) && ( iCalUtilityFunctions::_isOffset( $tz ))) {
        $offset = (string) iCalUtilityFunctions::_tz2offset( $tz ) * -1;
        $tz     = 'UTC';
        $tzSts  = TRUE;
      }
      elseif( !empty( $wtz ))
        $tzSts  = TRUE;
      $tz       = trim( $tz );
      if(( 'Z' == $tz ) || ( 'GMT' == strtoupper( $tz )))
        $tz     = 'UTC';
      if( 0 < substr_count( $datetime, '-' ))
        $datetime = str_replace( '-', '/', $datetime );
      try {
        $d        = new DateTime( $datetime, new DateTimeZone( $tz ));
        if( 0  != $offset )  // adjust for offset
          $d->modify( $offset.' seconds' );
        $datestring = $d->format( iCalUtilityFunctions::$fmt['YmdHis3'] );
        unset( $d );
      }
      catch( Exception $e ) {
        $datestring = date( iCalUtilityFunctions::$fmt['YmdHis3'], strtotime( $datetime ));
      }
    } // end if( !empty( $tz ))
    else
      $datestring = date( iCalUtilityFunctions::$fmt['YmdHis3'], strtotime( $datetime ));
    if( 'UTC' == $tz )
      $tz         = 'Z';
    $d            = explode( '-', $datestring );
    $output       = array( 'year' => $d[0], 'month' => $d[1], 'day' => $d[2] );
    if( !$parno || ( 3 != $parno )) { // parno is set to 6 or 7
      $output['hour'] = $d[3];
      $output['min']  = $d[4];
      $output['sec']  = $d[5];
      if(( $tzSts || ( 7 == $parno )) && !empty( $tz ))
        $output['tz'] = $tz;
    }
    // return original string in the array in case strtotime failed to make sense of it
    $output['unparsedtext'] = $unparseddatetime;
    return $output;
  }
/********************************************************************************/
/**
 * special characters management output
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @param string $string
 * @param string $format
 * @param string $nl
 * @return string
 */
  public static function _strrep( $string, $format, $nl ) {
    switch( $format ) {
      case 'xcal':
        $string = str_replace( '\n',  $nl, $string);
        $string = htmlspecialchars( strip_tags( stripslashes( urldecode ( $string ))));
        break;
      default:
        $pos = 0;
        $specChars = array( 'n', 'N', 'r', ',', ';' );
        while( isset( $string[$pos] )) {
          if( FALSE === ( $pos = strpos( $string, "\\", $pos )))
            break;
          if( !in_array( substr( $string, $pos, 1 ), $specChars )) {
            $string = substr( $string, 0, $pos )."\\".substr( $string, ( $pos + 1 ));
            $pos += 1;
          }
          $pos += 1;
        }
        if( FALSE !== strpos( $string, '"' ))
          $string = str_replace('"',   "'",       $string);
        if( FALSE !== strpos( $string, ',' ))
          $string = str_replace(',',   '\,',      $string);
        if( FALSE !== strpos( $string, ';' ))
          $string = str_replace(';',   '\;',      $string);
        if( FALSE !== strpos( $string, "\r\n" ))
          $string = str_replace( "\r\n", '\n',    $string);
        elseif( FALSE !== strpos( $string, "\r" ))
          $string = str_replace( "\r", '\n',      $string);
        elseif( FALSE !== strpos( $string, "\n" ))
          $string = str_replace( "\n", '\n',      $string);
        if( FALSE !== strpos( $string, '\N' ))
          $string = str_replace( '\N', '\n',      $string);
//        if( FALSE !== strpos( $string, $nl ))
          $string = str_replace( $nl, '\n', $string);
        break;
    }
    return $string;
  }
/**
 * special characters management input (from iCal file)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @param string $string
 * @return string
 */
  public static function _strunrep( $string ) {
    $string = str_replace( '\\\\', '\\',     $string);
    $string = str_replace( '\,',   ',',      $string);
    $string = str_replace( '\;',   ';',      $string);
//    $string = str_replace( '\n',  $nl, $string); // ??
    return $string;
  }
/**
 * convert timestamp to date array, default UTC or adjusted for offset/timezone
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param mixed   $timestamp
 * @param int     $parno
 * @param string  $wtz
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_tz2offset()
 * @uses iCalUtilityFunctions::$fmt
 * @return array
 */
  public static function _timestamp2date( $timestamp, $parno=6, $wtz=null ) {
    if( is_array( $timestamp )) {
      $tz        = ( isset( $timestamp['tz'] )) ? $timestamp['tz'] : $wtz;
      $timestamp = $timestamp['timestamp'];
    }
    $tz          = ( isset( $tz )) ? $tz : $wtz;
    $offset      = 0;
    if( empty( $tz ) || ( 'Z' == $tz ) || ( 'GMT' == strtoupper( $tz )))
      $tz        = 'UTC';
    elseif( iCalUtilityFunctions::_isOffset( $tz )) {
      $offset    = iCalUtilityFunctions::_tz2offset( $tz );
    }
    try {
      $d         = new DateTime( "@$timestamp" );  // set UTC date
      if(  0 != $offset )                          // adjust for offset
        $d->modify( $offset.' seconds' );
      elseif( 'UTC' != $tz )
        $d->setTimezone( new DateTimeZone( $tz )); // convert to local date
      $date      = $d->format( iCalUtilityFunctions::$fmt['YmdHis3'] );
      unset( $d );
    }
    catch( Exception $e ) {
      $date      = date( iCalUtilityFunctions::$fmt['YmdHis3'], $timestamp );
    }
    $date        = explode( '-', $date );
    $output      = array( 'year' => $date[0], 'month' => $date[1], 'day' => $date[2] );
    if( 3 != $parno ) {
      $output['hour'] = $date[3];
      $output['min']  = $date[4];
      $output['sec']  = $date[5];
      if(( 'UTC' == $tz ) || ( 0 == $offset ))
        $output['tz'] = 'Z';
    }
    return $output;
  }
/**
 * transforms a dateTime from a timezone to another using PHP DateTime and DateTimeZone class (PHP >= PHP 5.2.0)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.1 - 2012-10-17
 * @param mixed  $date    date to alter
 * @param string $tzFrom  PHP valid 'from' timezone
 * @param string $tzTo    PHP valid 'to' timezone, default 'UTC'
 * @param string $format  date output format, default 'Ymd\THis'
 * @uses iCalUtilityFunctions::_isArrayDate()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @return bool
 */
  public static function transformDateTime( & $date, $tzFrom, $tzTo='UTC', $format = 'Ymd\THis' ) {
    if( is_array( $date ) && isset( $date['timestamp'] )) {
      try {
        $d = new DateTime( "@{$date['timestamp']}" ); // set UTC date
        $d->setTimezone(new DateTimeZone( $tzFrom )); // convert to 'from' date
      }
      catch( Exception $e ) { return FALSE; }
    }
    else {
      if( iCalUtilityFunctions::_isArrayDate( $date )) {
        if( isset( $date['tz'] ))
          unset( $date['tz'] );
        $date  = iCalUtilityFunctions::_date2strdate( iCalUtilityFunctions::_chkDateArr( $date ));
      }
      if( 'Z' == substr( $date, -1 ))
        $date = substr( $date, 0, ( strlen( $date ) - 2 ));
      try { $d = new DateTime( $date, new DateTimeZone( $tzFrom )); }
      catch( Exception $e ) { return FALSE; }
    }
    try { $d->setTimezone( new DateTimeZone( $tzTo )); }
    catch( Exception $e ) { return FALSE; }
    $date = $d->format( $format );
    return TRUE;
  }
/**
 * convert offset, [+/-]HHmm[ss], to seconds, used when correcting UTC to localtime or v.v.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.4 - 2012-01-11
 * @param string $tz
 * @return integer
 */
  public static function _tz2offset( $tz ) {
    $tz           = trim( (string) $tz );
    $offset       = 0;
    if(((     5  != strlen( $tz ))       && ( 7  != strlen( $tz ))) ||
       ((    '+' != substr( $tz, 0, 1 )) && ( '-' != substr( $tz, 0, 1 ))) ||
       (( '0000' >= substr( $tz, 1, 4 )) && ( '9999' < substr( $tz, 1, 4 ))) ||
           (( 7  == strlen( $tz ))       && ( '00' > substr( $tz, 5, 2 )) && ( '99' < substr( $tz, 5, 2 ))))
      return $offset;
    $hours2sec    = (int) substr( $tz, 1, 2 ) * 3600;
    $min2sec      = (int) substr( $tz, 3, 2 ) *   60;
    $sec          = ( 7  == strlen( $tz )) ? (int) substr( $tz, -2 ) : '00';
    $offset       = $hours2sec + $min2sec + $sec;
    $offset       = ('-' == substr( $tz, 0, 1 )) ? $offset * -1 : $offset;
    return $offset;
  }
}
