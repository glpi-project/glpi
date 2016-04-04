<?php
/*********************************************************************************/
/**
 *
 * iCalcreator, a PHP rfc2445/rfc5545 solution.
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
 * selectComponent help class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-10
 */
class iCaldateTime extends dateTime {
/** @var string default date[-time] format */
  public $dateFormat = 'Y-m-d H:i:s e';
/** @var string default object instance date[-time] 'key' */
  public $key        = null;
/** @var array date[-time] origin */
  public $SCbools    = array();
/**
 * return time (His) array
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-07
 * @return array
 */
  public function getTime() {
    return explode( ':', $this->format( 'H:i:s' ));
  }
/**
 * return the timezone name
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-07
 * @return string
 */
  public function getTimezoneName() {
    $tz = $this->getTimezone();
    return $tz->getName();
  }
/**
 * return formatted date
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-07
 * @param string $format
 * @uses iCaldateTime::$dateFormat
 * @return string
 */
  public function format( $format=null ) {
    if( empty( $format ) && isset( $this->dateFormat ))
      $format = $this->dateFormat;
    return parent::format( $format );
  }
/**
 * return iCaldateTime object instance based on date array and timezone(s)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-28
 * @param array  $date
 * @param array  $params
 * @param array  $tz
 * @param string $dtstartTz
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCaldateTime::getTimezoneName()
 * @uses iCaldateTime::$dateFormat
 * @uses iCaldateTime::$key
 * @return object instance
 */
  public static function factory( array $date, $params=null, $tz=null, $dtstartTz=null ) {
    if(     isset( $params['TZID'] ) && ! empty( $params['TZID'] ))
      $tz           = ( 'Z' == $params['TZID'] ) ? 'UTC' : $params['TZID'];
    elseif( isset( $tz['tz'] )       && ! empty( $tz['tz'] ))
      $tz           = ( 'Z' == $tz['tz'] )       ? 'UTC' : $tz['tz'];
    else
      $tz           = ini_get( 'date_default_timezone_set' );
    $strdate        = sprintf( iCalUtilityFunctions::$fmt['Ymd'], (int) $date['year'], (int) $date['month'], (int) $date['day'] );
    if( isset( $date['hour'] ))
      $strdate     .= 'T'.sprintf( iCalUtilityFunctions::$fmt['His'], (int) $date['hour'], (int) $date['min'], (int) $date['sec'] );
    try {
      $timezone     = new DateTimeZone( $tz );
      $d            = new iCaldateTime( $strdate, $timezone );
    }
    catch( Exception $e ) {
      $d            = new iCaldateTime( $strdate );
    }
    if( ! empty( $dtstartTz )) {
      if( 'Z' == $dtstartTz )
        $dtstartTz  = 'UTC';
      if( $dtstartTz != $d->getTimezoneName()) { // set the same timezone as dtstart
        try {
          $timezone = new DateTimeZone( $dtstartTz );
          $d->setTimezone( $timezone );
        }
        catch( Exception $e ) {}
      }
    }
    unset( $timezone, $strdate );
    if( isset( $params['VALUE'] ) && ( 'DATE' == $params['VALUE'] )) {
      $d->dateFormat = 'Y-m-d';
      $d->key       = $d->format( 'Ymd' );
    }
    else
      $d->key       = $d->format( 'YmdHis' );
    return $d;
  }
}
