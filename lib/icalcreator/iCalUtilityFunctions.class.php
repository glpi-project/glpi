<?php
/**
 * iCalcreator class v2.8
 * copyright (c) 2007-2011 Kjell-Inge Gustafsson kigkonsult
 * www.kigkonsult.se/iCalcreator/index.php
 * ical@kigkonsult.se
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
 * moving all utility (static) functions to a utility class
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.22 - 2010-09-25
 *
 */
class iCalUtilityFunctions {
  // Store the single instance of iCalUtilityFunctions
  private static $m_pInstance;

  // Private constructor to limit object instantiation to within the class
  private function __construct() {
    $m_pInstance = FALSE;
  }

  // Getter method for creating/returning the single instance of this class
  public static function getInstance() {
    if (!self::$m_pInstance)
      self::$m_pInstance = new iCalUtilityFunctions();

    return self::$m_pInstance;
  }
/**
 * check a date(-time) for an opt. timezone and if it is a DATE-TIME or DATE
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-25
 * @param array $date, date to check
 * @param int $parno, no of date parts (i.e. year, month.. .)
 * @return array $params, property parameters
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
        $date = iCalUtilityFunctions::_date_time_string( $date, $parno );
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
 * convert date/datetime to timestamp
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-30
 * @param array  $datetime  datetime/(date)
 * @param string $tz        timezone
 * @return timestamp
 */
  public static function _date2timestamp( $datetime, $tz=null ) {
    $output = null;
    if( !isset( $datetime['hour'] )) $datetime['hour'] = '0';
    if( !isset( $datetime['min'] ))  $datetime['min']  = '0';
    if( !isset( $datetime['sec'] ))  $datetime['sec']  = '0';
    foreach( $datetime as $dkey => $dvalue ) {
      if( 'tz' != $dkey )
        $datetime[$dkey] = (integer) $dvalue;
    }
    if( $tz )
      $datetime['tz'] = $tz;
    $offset = ( isset( $datetime['tz'] ) && ( '' < trim ( $datetime['tz'] ))) ? iCalUtilityFunctions::_tz2offset( $datetime['tz'] ) : 0;
    $output = mktime( $datetime['hour'], $datetime['min'], ($datetime['sec'] + $offset), $datetime['month'], $datetime['day'], $datetime['year'] );
    return $output;
  }
/**
 * ensures internal date-time/date format for input date-time/date in array format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-15
 * @param array $datetime
 * @param int $parno optional, default FALSE
 * @return array
 */
  public static function _date_time_array( $datetime, $parno=FALSE ) {
    $output = array();
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
      if( !isset( $output['hour'] ))
        $output['hour'] = 0;
      if( !isset( $output['min']  ))
        $output['min'] = 0;
      if( !isset( $output['sec']  ))
        $output['sec'] = 0;
    }
    return $output;
  }
/**
 * ensures internal date-time/date format for input date-time/date in string fromat
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.35 - 2010-12-03
 * @param array $datetime
 * @param int $parno optional, default FALSE
 * @return array
 */
  public static function _date_time_string( $datetime, $parno=FALSE ) {
    $datetime = (string) trim( $datetime );
    $tz  = null;
    $len = strlen( $datetime ) - 1;
    if( 'Z' == substr( $datetime, -1 )) {
      $tz = 'Z';
      $datetime = trim( substr( $datetime, 0, $len ));
    }
    elseif( ( ctype_digit( substr( $datetime, -2, 2 ))) && // time or date
                  ( '-' == substr( $datetime, -3, 1 )) ||
                  ( ':' == substr( $datetime, -3, 1 )) ||
                  ( '.' == substr( $datetime, -3, 1 ))) {
      $continue = TRUE;
    }
    elseif( ( ctype_digit( substr( $datetime, -4, 4 ))) && // 4 pos offset
            ( ' +' == substr( $datetime, -6, 2 )) ||
            ( ' -' == substr( $datetime, -6, 2 ))) {
      $tz = substr( $datetime, -5, 5 );
      $datetime = substr( $datetime, 0, ($len - 5));
    }
    elseif( ( ctype_digit( substr( $datetime, -6, 6 ))) && // 6 pos offset
            ( ' +' == substr( $datetime, -8, 2 )) ||
            ( ' -' == substr( $datetime, -8, 2 ))) {
      $tz = substr( $datetime, -7, 7 );
      $datetime = substr( $datetime, 0, ($len - 7));
    }
    elseif( ( 6 < $len ) && ( ctype_digit( substr( $datetime, -6, 6 )))) {
      $continue = TRUE;
    }
    elseif( 'T' ==  substr( $datetime, -7, 1 )) {
      $continue = TRUE;
    }
    else {
      $cx  = $tx = 0;    //  19970415T133000 US-Eastern
      for( $cx = -1; $cx > ( 9 - $len ); $cx-- ) {
        $char = substr( $datetime, $cx, 1 );
        if(( ' ' == $char) || ctype_digit( $char))
          break; // if exists, tz ends here.. . ?
        else
           $tx--; // tz length counter
      }
      if( 0 > $tx ) {
        $tz = substr( $datetime, $tx );
        $datetime = trim( substr( $datetime, 0, $len + $tx + 1 ));
      }
    }
    if( 0 < substr_count( $datetime, '-' )) {
      $datetime = str_replace( '-', '/', $datetime );
    }
    elseif( ctype_digit( substr( $datetime, 0, 8 )) &&
           ( 'T' ==      substr( $datetime, 8, 1 )) &&
            ctype_digit( substr( $datetime, 9, 6 ))) {
      $datetime = substr( $datetime,  4, 2 )
             .'/'.substr( $datetime,  6, 2 )
             .'/'.substr( $datetime,  0, 4 )
             .' '.substr( $datetime,  9, 2 )
             .':'.substr( $datetime, 11, 2 )
             .':'.substr( $datetime, 13);
    }
    $datestring = date( 'Y-m-d H:i:s', strtotime( $datetime ));
    $tz                = trim( $tz );
    $output            = array();
    $output['year']    = substr( $datestring, 0, 4 );
    $output['month']   = substr( $datestring, 5, 2 );
    $output['day']     = substr( $datestring, 8, 2 );
    if(( 6 == $parno ) || ( 7 == $parno ) || ( !$parno && ( 'Z' == $tz ))) {
      $output['hour']  = substr( $datestring, 11, 2 );
      $output['min']   = substr( $datestring, 14, 2 );
      $output['sec']   = substr( $datestring, 17, 2 );
      if( !empty( $tz ))
        $output['tz']  = $tz;
    }
    elseif( 3 != $parno ) {
      if(( '00' < substr( $datestring, 11, 2 )) ||
         ( '00' < substr( $datestring, 14, 2 )) ||
         ( '00' < substr( $datestring, 17, 2 ))) {
        $output['hour']  = substr( $datestring, 11, 2 );
        $output['min']   = substr( $datestring, 14, 2 );
        $output['sec']   = substr( $datestring, 17, 2 );
      }
      if( !empty( $tz ))
        $output['tz']  = $tz;
    }
    return $output;
  }
/**
 * convert local startdate/enddate (Ymd[His]) to duration array
 *
 * uses this component dates if missing input dates
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.11 - 2010-10-21
 * @param array $startdate
 * @param array $duration
 * @return array duration
 */
  function _date2duration( $startdate, $enddate ) {
    $startWdate  = mktime( 0, 0, 0, $startdate['month'], $startdate['day'], $startdate['year'] );
    $endWdate    = mktime( 0, 0, 0, $enddate['month'],   $enddate['day'],   $enddate['year'] );
    $wduration   = $endWdate - $startWdate;
    $dur         = array();
    $dur['week'] = (int) floor( $wduration / ( 7 * 24 * 60 * 60 ));
    $wduration   =              $wduration % ( 7 * 24 * 60 * 60 );
    $dur['day']  = (int) floor( $wduration / ( 24 * 60 * 60 ));
    $wduration   =              $wduration % ( 24 * 60 * 60 );
    $dur['hour'] = (int) floor( $wduration / ( 60 * 60 ));
    $wduration   =              $wduration % ( 60 * 60 );
    $dur['min']  = (int) floor( $wduration / ( 60 ));
    $dur['sec']  = (int)        $wduration % ( 60 );
    return $dur;
  }
/**
 * ensures internal duration format for input in array format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.1.1 - 2007-06-24
 * @param array $duration
 * @return array
 */
  public static function _duration_array( $duration ) {
    $output = array();
    if(    is_array( $duration )        &&
       ( 1 == count( $duration ))       &&
              isset( $duration['sec'] ) &&
              ( 60 < $duration['sec'] )) {
      $durseconds  = $duration['sec'];
      $output['week'] = floor( $durseconds / ( 60 * 60 * 24 * 7 ));
      $durseconds  =           $durseconds % ( 60 * 60 * 24 * 7 );
      $output['day']  = floor( $durseconds / ( 60 * 60 * 24 ));
      $durseconds  =           $durseconds % ( 60 * 60 * 24 );
      $output['hour'] = floor( $durseconds / ( 60 * 60 ));
      $durseconds  =           $durseconds % ( 60 * 60 );
      $output['min']  = floor( $durseconds / ( 60 ));
      $output['sec']  =      ( $durseconds % ( 60 ));
    }
    else {
      foreach( $duration as $durKey => $durValue ) {
        if( empty( $durValue )) continue;
        switch ( $durKey ) {
          case '0': case 'week': $output['week']  = $durValue; break;
          case '1': case 'day':  $output['day']   = $durValue; break;
          case '2': case 'hour': $output['hour']  = $durValue; break;
          case '3': case 'min':  $output['min']   = $durValue; break;
          case '4': case 'sec':  $output['sec']   = $durValue; break;
        }
      }
    }
    if( isset( $output['week'] ) && ( 0 < $output['week'] )) {
      unset( $output['day'], $output['hour'], $output['min'], $output['sec'] );
      return $output;
    }
    unset( $output['week'] );
    if( empty( $output['day'] ))
      unset( $output['day'] );
    if ( isset( $output['hour'] ) || isset( $output['min'] ) || isset( $output['sec'] )) {
      if( !isset( $output['hour'] )) $output['hour'] = 0;
      if( !isset( $output['min']  )) $output['min']  = 0;
      if( !isset( $output['sec']  )) $output['sec']  = 0;
      if(( 0 == $output['hour'] ) && ( 0 == $output['min'] ) && ( 0 == $output['sec'] ))
        unset( $output['hour'], $output['min'], $output['sec'] );
    }
    return $output;
  }
/**
 * ensures internal duration format for input in string format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.0.5 - 2007-03-14
 * @param string $duration
 * @return array
 */
  public static function _duration_string( $duration ) {
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
           return false; // unknown duration controll character  !?!?
         else
           $val .= substr( $duration, $ix, 1 );
      }
    }
    return iCalUtilityFunctions::_duration_array( $output );
  }
/**
 * convert duration to date in array format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.23 - 2010-10-23
 * @param array $startdate
 * @param array $duration
 * @return array, date format
 */
  function _duration2date( $startdate=null, $duration=null ) {
    if( empty( $startdate )) return FALSE;
    if( empty( $duration ))  return FALSE;
    $dateOnly          = ( isset( $startdate['hour'] ) || isset( $startdate['min'] ) || isset( $startdate['sec'] )) ? FALSE : TRUE;
    $startdate['hour'] = ( isset( $startdate['hour'] )) ? $startdate['hour'] : 0;
    $startdate['min']  = ( isset( $startdate['min'] ))  ? $startdate['min']  : 0;
    $startdate['sec']  = ( isset( $startdate['sec'] ))  ? $startdate['sec']  : 0;
    $dtend = 0;
    if(    isset( $duration['week'] ))
      $dtend += ( $duration['week'] * 7 * 24 * 60 * 60 );
    if(    isset( $duration['day'] ))
      $dtend += ( $duration['day'] * 24 * 60 * 60 );
    if(    isset( $duration['hour'] ))
      $dtend += ( $duration['hour'] * 60 *60 );
    if(    isset( $duration['min'] ))
      $dtend += ( $duration['min'] * 60 );
    if(    isset( $duration['sec'] ))
      $dtend +=   $duration['sec'];
    if(( 24 * 60 * 60 ) < $dtend )
      $dtend -= ( 24 * 60 * 60 ); // exclude start day
    $dtend += mktime( $startdate['hour'], $startdate['min'], $startdate['sec'], $startdate['month'], $startdate['day'], $startdate['year'] );
    $dtend2 = array();
    $dtend2['year']   = date('Y', $dtend );
    $dtend2['month']  = date('m', $dtend );
    $dtend2['day']    = date('d', $dtend );
    $dtend2['hour']   = date('H', $dtend );
    $dtend2['min']    = date('i', $dtend );
    $dtend2['sec']    = date('s', $dtend );
    if( isset( $startdate['tz'] ))
      $dtend2['tz']   = $startdate['tz'];
    if( $dateOnly && (( 0 == $dtend2['hour'] ) && ( 0 == $dtend2['min'] ) && ( 0 == $dtend2['sec'] )))
      unset( $dtend2['hour'], $dtend2['min'], $dtend2['sec'] );
    return $dtend2;
  }
/**
 * if not preSet, if exist, remove key with expected value from array and return hit value else return elseValue
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-11-08
 * @param array $array
 * @param string $expkey, expected key
 * @param string $expval, expected value
 * @param int $hitVal optional, return value if found
 * @param int $elseVal optional, return value if not found
 * @param int $preSet optional, return value if already preset
 * @return int
 */
  public static function _existRem( &$array, $expkey, $expval=FALSE, $hitVal=null, $elseVal=null, $preSet=null ) {
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
 * creates formatted output for calendar component property data value type date/date-time
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-30
 * @param array   $datetime
 * @param int     $parno, optional, default 6
 * @return string
 */
  public static function _format_date_time( $datetime, $parno=6 ) {
    if( !isset( $datetime['year'] )  &&
        !isset( $datetime['month'] ) &&
        !isset( $datetime['day'] )   &&
        !isset( $datetime['hour'] )  &&
        !isset( $datetime['min'] )   &&
        !isset( $datetime['sec'] ))
      return ;
    $output = null;
    // if( !isset( $datetime['day'] )) { $o=''; foreach($datetime as $k=>$v) {if(is_array($v)) $v=implode('-',$v);$o.=" $k=>$v";} echo " day SAKNAS : $o <br />\n"; }
    foreach( $datetime as $dkey => & $dvalue )
      if( 'tz' != $dkey ) $dvalue = (integer) $dvalue;
    $output = date('Ymd', mktime( 0, 0, 0, $datetime['month'], $datetime['day'], $datetime['year']));
    if( isset( $datetime['hour'] )  ||
        isset( $datetime['min'] )   ||
        isset( $datetime['sec'] )   ||
        isset( $datetime['tz'] )) {
      if( isset( $datetime['tz'] )  &&
         !isset( $datetime['hour'] ))
        $datetime['hour'] = 0;
      if( isset( $datetime['hour'] )  &&
         !isset( $datetime['min'] ))
        $datetime['min'] = 0;
      if( isset( $datetime['hour'] )  &&
          isset( $datetime['min'] )   &&
         !isset( $datetime['sec'] ))
        $datetime['sec'] = 0;
      $date = mktime( $datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year']);
      $output .= date('\THis', $date );
      if( isset( $datetime['tz'] ) && ( '' < trim ( $datetime['tz'] ))) {
        $datetime['tz'] = trim( $datetime['tz'] );
        if( 'Z' == $datetime['tz'] )
          $output .= 'Z';
        $offset = iCalUtilityFunctions::_tz2offset( $datetime['tz'] );
        if( 0 != $offset ) {
          $date = mktime( $datetime['hour'], $datetime['min'], ($datetime['sec'] + $offset), $datetime['month'], $datetime['day'], $datetime['year']);
          $output    = date( 'Ymd\THis\Z', $date );
        }
      }
      elseif( 7 == $parno )
        $output .= 'Z';
    }
    return $output;
  }
/**
 * creates formatted output for calendar component property data value type duration
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.10 - 2010-11-28
 * @param array $duration ( week, day, hour, min, sec )
 * @return string
 */
  public static function _format_duration( $duration ) {
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
       ( isset( $duration['sec'])  && ( 0 < $duration['sec'] )))
      $output .= 'T';
    $output .= ( isset( $duration['hour']) && ( 0 < $duration['hour'] )) ? $duration['hour'].'H' : '';
    $output .= ( isset( $duration['min'])  && ( 0 < $duration['min'] ))  ? $duration['min']. 'M' : '';
    $output .= ( isset( $duration['sec'])  && ( 0 < $duration['sec'] ))  ? $duration['sec']. 'S' : '';
    return $output;
  }
/**
 * checks if input array contains a date
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-25
 * @param array $input
 * @return bool
 */
  public static function _isArrayDate( $input ) {
    if( isset( $input['week'] ) || ( !in_array( count( $input ), array( 3, 6, 7 ))))
      return FALSE;
    if( 7 == count( $input ))
      return TRUE;
    if( isset( $input['year'] ) && isset( $input['month'] ) && isset( $input['day'] ))
      return checkdate( (int) $input['month'], (int) $input['day'], (int) $input['year'] );
    if( isset( $input['day'] ) || isset( $input['hour'] ) || isset( $input['min'] ) || isset( $input['sec'] ))
      return FALSE;
    if( in_array( 0, $input ))
      return FALSE;
    if(( 1970 > $input[0] ) || ( 12 < $input[1] ) || ( 31 < $input[2] ))
      return FALSE;
    if(( isset( $input[0] ) && isset( $input[1] ) && isset( $input[2] )) &&
         checkdate( (int) $input[1], (int) $input[2], (int) $input[0] ))
      return TRUE;
    $input = iCalUtilityFunctions::_date_time_string( $input[1].'/'.$input[2].'/'.$input[0], 3 ); //  m - d - Y
    if( isset( $input['year'] ) && isset( $input['month'] ) && isset( $input['day'] ))
      return checkdate( (int) $input['month'], (int) $input['day'], (int) $input['year'] );
    return FALSE;
  }
/**
 * checks if input array contains a timestamp date
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-18
 * @param array $input
 * @return bool
 */
  public static function _isArrayTimestampDate( $input ) {
    return ( is_array( $input ) && isset( $input['timestamp'] )) ? TRUE : FALSE ;
  }
/**
 * controll if input string contains trailing UTC offset
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-19
 * @param string $input
 * @return bool
 */
  public static function _isOffset( $input ) {
    $input         = trim( (string) $input );
    if( 'Z' == substr( $input, -1 ))
      return TRUE;
    elseif((   5 <= strlen( $input )) &&
       ( in_array( substr( $input, -5, 1 ), array( '+', '-' ))) &&
       (   '0000'  < substr( $input, -4 )) && (   '9999' >= substr( $input, -4 )))
      return TRUE;
    elseif((    7 <= strlen( $input )) &&
       ( in_array( substr( $input, -7, 1 ), array( '+', '-' ))) &&
       ( '000000'  < substr( $input, -6 )) && ( '999999' >= substr( $input, -6 )))
      return TRUE;
    return FALSE;

  }
/**
 * remakes a recur pattern to an array of dates
 *
 * if missing, UNTIL is set 1 year from startdate (emergency break)
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.23 - 2010-10-24
 * @param array $result, array to update, array([timestamp] => timestamp)
 * @param array $recur, pattern for recurrency (only value part, params ignored)
 * @param array $wdate, component start date
 * @param array $startdate, start date
 * @param array $enddate, optional
 * @return array of recurrence (start-)dates as index
 * @todo BYHOUR, BYMINUTE, BYSECOND, ev. BYSETPOS due to ambiguity, WEEKLY at year end/start
 */
  public static function _recur2date( & $result, $recur, $wdate, $startdate, $enddate=FALSE ) {
    foreach( $wdate as $k => $v ) if( ctype_digit( $v )) $wdate[$k] = (int) $v;
    $wdatets     = iCalUtilityFunctions::_date2timestamp( $wdate );
    $startdatets = iCalUtilityFunctions::_date2timestamp( $startdate );
    if( !$enddate ) {
      $enddate = $startdate;
      $enddate['year'] += 1;
// echo "recur __in_ ".implode('-',$startdate)." period start ".implode('-',$wdate)." period end ".implode('-',$enddate)."<br />\n";print_r($recur);echo "<br />\n";//test###
    }
    $endDatets = iCalUtilityFunctions::_date2timestamp( $enddate ); // fix break
    if( !isset( $recur['COUNT'] ) && !isset( $recur['UNTIL'] ))
      $recur['UNTIL'] = $enddate; // create break
    if( isset( $recur['UNTIL'] )) {
      $tdatets = iCalUtilityFunctions::_date2timestamp( $recur['UNTIL'] );
      if( $endDatets > $tdatets ) {
        $endDatets = $tdatets; // emergency break
        $enddate   = iCalUtilityFunctions::_timestamp2date( $endDatets, 6 );
      }
      else
        $recur['UNTIL'] = iCalUtilityFunctions::_timestamp2date( $endDatets, 6 );
    }
    if( $wdatets > $endDatets ) {
     //echo "recur out of date ".implode('-',iCalUtilityFunctions::_date_time_string(date('Y-m-d H:i:s',$wdatets),6))."<br />\n";//test
      return array(); // nothing to do.. .
    }
    if( !isset( $recur['FREQ'] )) // "MUST be specified.. ."
      $recur['FREQ'] = 'DAILY'; // ??
    $wkst = ( isset( $recur['WKST'] ) && ( 'SU' == $recur['WKST'] )) ? 24*60*60 : 0; // ??
    if( !isset( $recur['INTERVAL'] ))
      $recur['INTERVAL'] = 1;
    $countcnt = ( !isset( $recur['BYSETPOS'] )) ? 1 : 0; // DTSTART counts as the first occurrence
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
      $bysetposWold = (int) date( 'W', ( $wdatets + $wkst ));
      $bysetposYold = $wdate['year'];
      $bysetposMold = $wdate['month'];
      $bysetposDold = $wdate['day'];
      if( is_array( $recur['BYSETPOS'] )) {
        foreach( $recur['BYSETPOS'] as $bix => $bval )
          $recur['BYSETPOS'][$bix] = (int) $bval;
      }
      else
        $recur['BYSETPOS'] = array( (int) $recur['BYSETPOS'] );
      iCalUtilityFunctions::_stepdate( $enddate, $endDatets, $step); // make sure to count whole last period
    }
    iCalUtilityFunctions::_stepdate( $wdate, $wdatets, $step);
    $year_old     = null;
    $daynames     = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );
             /* MAIN LOOP */
     // echo "recur start ".implode('-',$wdate)." end ".implode('-',$enddate)."<br />\n";//test
    while( TRUE ) {
      if( isset( $endDatets ) && ( $wdatets > $endDatets ))
        break;
      if( isset( $recur['COUNT'] ) && ( $countcnt >= $recur['COUNT'] ))
        break;
      if( $year_old != $wdate['year'] ) {
        $year_old   = $wdate['year'];
        $daycnts    = array();
        $yeardays   = $weekno = 0;
        $yeardaycnt = array();
        for( $m = 1; $m <= 12; $m++ ) { // count up and update up-counters
          $daycnts[$m] = array();
          $weekdaycnt = array();
          foreach( $daynames as $dn )
            $yeardaycnt[$dn] = $weekdaycnt[$dn] = 0;
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
          }
        }
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
        }
      }
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
    //echo "skip: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br />\n";//test
          iCalUtilityFunctions::_stepdate( $wdate, $wdatets, $step);
          continue;
        }
        else // continue within the selected interval
          $intervalarr[$intervalix] = 0;
   //echo "cont: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br />\n";//test
      }
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
    //echo "efter BYMONTHDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'TRUE' : 'FALSE'; echo "<br />\n";//test###
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
          }
        //echo "daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw<br />\n"; // test ###
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
        //echo "daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw<br />\n"; // test ###
            if((  $daynoexists &&  $daynosw && $daynamesw ) ||
               ( !$daynoexists && !$daynosw && $daynamesw )) {
              $updateOK = TRUE;
              break;
            }
          }
        }
      }
      //echo "efter BYDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'TRUE' : 'FALSE'; echo "<br />\n"; // test ###
            /* check BYSETPOS */
      if( $updateOK ) {
        if( isset( $recur['BYSETPOS'] ) &&
          ( in_array( $recur['FREQ'], array( 'YEARLY', 'MONTHLY', 'WEEKLY', 'DAILY' )))) {
          if( isset( $recur['WEEKLY'] )) {
            if( $bysetposWold == $daycnts[$wdate['month']][$wdate['day']]['weekno_up'] )
              $bysetposw1[] = $wdatets;
            else
              $bysetposw2[] = $wdatets;
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
                                            ( $bysetposDold == $wdate['day'] ))))
              $bysetposymd1[] = $wdatets;
            else
              $bysetposymd2[] = $wdatets;
          }
        }
        else {
            /* update result array if BYSETPOS is set */
          $countcnt++;
          if( $startdatets <= $wdatets ) { // only output within period
            $result[$wdatets] = TRUE;
          //echo "recur ".implode('-',iCalUtilityFunctions::_date_time_string(date('Y-m-d H:i:s',$wdatets),6))."<br />\n";//test
          }
         //else echo "recur undate ".implode('-',iCalUtilityFunctions::_date_time_string(date('Y-m-d H:i:s',$wdatets),6))." okdatstart ".implode('-',iCalUtilityFunctions::_date_time_string(date('Y-m-d H:i:s',$startdatets),6))."<br />\n";//test
          $updateOK = FALSE;
        }
      }
            /* step up date */
      iCalUtilityFunctions::_stepdate( $wdate, $wdatets, $step);
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
              if( $startdatets <= $bysetposarr1[$ix] ) { // only output within period
                $result[$bysetposarr1[$ix]] = TRUE;
       //echo "recur ".implode('-',iCalUtilityFunctions::_date_time_string(date('Y-m-d H:i:s',$bysetposarr1[$ix]),6))."<br />\n";//test
              }
              $countcnt++;
            }
            if( isset( $recur['COUNT'] ) && ( $countcnt >= $recur['COUNT'] ))
              break;
          }
          $bysetposarr1 = $bysetposarr2;
          $bysetposarr2 = array();
        }
      }
    }
  }
  public static function _recurBYcntcheck( $BYvalue, $upValue, $downValue ) {
    if( is_array( $BYvalue ) &&
      ( in_array( $upValue, $BYvalue ) || in_array( $downValue, $BYvalue )))
      return TRUE;
    elseif(( $BYvalue == $upValue ) || ( $BYvalue == $downValue ))
      return TRUE;
    else
      return FALSE;
  }
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
        $wdatets    = iCalUtilityFunctions::_date2timestamp( $date );
        $intervalix = (int) date( 'W', ( $wdatets + $wkst ));
       break;
      case 'DAILY':
           default:
        $intervalix = $date['year'].'-'.$date['month'].'-'.$date['day'];
        break;
    }
    return $intervalix;
  }
/**
 * convert input format for exrule and rrule to internal format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.23 - 2010-12-07
 * @param array $rexrule
 * @return array
 */
  public static function _setRexrule( $rexrule ) {
    $input          = array();
    if( empty( $rexrule ))
      return $input;
    foreach( $rexrule as $rexrulelabel => $rexrulevalue ) {
      $rexrulelabel = strtoupper( $rexrulelabel );
      if( 'UNTIL'  != $rexrulelabel )
        $input[$rexrulelabel]   = $rexrulevalue;
      else {
        if( iCalUtilityFunctions::_isArrayTimestampDate( $rexrulevalue )) // timestamp date
          $input[$rexrulelabel] = iCalUtilityFunctions::_timestamp2date( $rexrulevalue, 6 );
        elseif( iCalUtilityFunctions::_isArrayDate( $rexrulevalue )) // date-time
          $input[$rexrulelabel] = iCalUtilityFunctions::_date_time_array( $rexrulevalue, 6 );
        elseif( 8 <= strlen( trim( $rexrulevalue ))) // ex. 2006-08-03 10:12:18
          $input[$rexrulelabel] = iCalUtilityFunctions::_date_time_string( $rexrulevalue );
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
    if( isset( $input['BYDAY'] ))
      $input2['BYDAY']      = $input['BYDAY'];
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
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.22 - 2010-09-25
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param string $tz optional
 * @param array $params optional
 * @param string $caller optional
 * @param string $objName optional
 * @return array
 */
  public static function _setDate( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE, $caller=null, $objName=null ) {
    $input = $parno = null;
    $localtime = (( 'dtstart' == $caller ) && in_array( $objName, array( 'vtimezone', 'standard', 'daylight' ))) ? TRUE : FALSE;
    if( iCalUtilityFunctions::_isArrayDate( $year )) {
      if( $localtime ) unset ( $month['VALUE'], $month['TZID'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      if( isset( $input['params']['TZID'] )) {
        $input['params']['VALUE'] = 'DATE-TIME';
        unset( $year['tz'] );
      }
      $hitval          = (( !empty( $year['tz'] ) || !empty( $year[6] ))) ? 7 : 6;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval );
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3, count( $year ), $parno );
      $input['value']  = iCalUtilityFunctions::_date_time_array( $year, $parno );
    }
    elseif( iCalUtilityFunctions::_isArrayTimestampDate( $year )) {
      if( $localtime ) unset ( $month['VALUE'], $month['TZID'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      if( isset( $input['params']['TZID'] )) {
        $input['params']['VALUE'] = 'DATE-TIME';
        unset( $year['tz'] );
      }
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3 );
      $hitval          = ( isset( $year['tz'] )) ? 7 : 6;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval, $parno );
      $input['value']  = iCalUtilityFunctions::_timestamp2date( $year, $parno );
    }
    elseif( 8 <= strlen( trim( $year ))) { // ex. 2006-08-03 10:12:18
      if( $localtime ) unset ( $month['VALUE'], $month['TZID'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      if( isset( $input['params']['TZID'] )) {
        $input['params']['VALUE'] = 'DATE-TIME';
        $parno = 6;
      }
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', 7, $parno );
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3, $parno, $parno );
      $input['value']  = iCalUtilityFunctions::_date_time_string( $year, $parno );
    }
    else {
      if( is_array( $params )) {
        if( $localtime ) unset ( $params['VALUE'], $params['TZID'] );
        $input['params'] = iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' ));
      }
      elseif( is_array( $tz )) {
        $input['params'] = iCalUtilityFunctions::_setParams( $tz,     array( 'VALUE' => 'DATE-TIME' ));
        $tz = FALSE;
      }
      elseif( is_array( $hour )) {
        $input['params'] = iCalUtilityFunctions::_setParams( $hour,   array( 'VALUE' => 'DATE-TIME' ));
        $hour = $min = $sec = $tz = FALSE;
      }
      if( isset( $input['params']['TZID'] )) {
        $tz            = null;
        $input['params']['VALUE'] = 'DATE-TIME';
      }
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE', 3 );
      $hitval          = ( !empty( $tz )) ? 7 : 6;
      $parno           = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', $hitval, $parno, $parno );
      $input['value']  = array( 'year'  => $year, 'month' => $month, 'day'   => $day );
      if( 3 != $parno ) {
        $input['value']['hour'] = ( $hour ) ? $hour : '0';
        $input['value']['min']  = ( $min )  ? $min  : '0';
        $input['value']['sec']  = ( $sec )  ? $sec  : '0';
        if( !empty( $tz ))
          $input['value']['tz'] = $tz;
      }
    }
    if( 3 == $parno ) {
      $input['params']['VALUE'] = 'DATE';
      unset( $input['value']['tz'] );
      unset( $input['params']['TZID'] );
    }
    elseif( isset( $input['params']['TZID'] ))
      unset( $input['value']['tz'] );
    if( $localtime ) unset( $input['value']['tz'], $input['params']['TZID'] );
    if( isset( $input['value']['tz'] ))
      $input['value']['tz'] = (string) $input['value']['tz'];
    if( !empty( $input['value']['tz'] ) && ( 'Z' != $input['value']['tz'] ) &&
      ( !iCalUtilityFunctions::_isOffset( $input['value']['tz'] )))
      $input['params']['TZID'] = $input['value']['tz'];
    return $input;
  }
/**
 * convert format for input date (UTC) to internal date with parameters
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.17 - 2008-10-31
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
 * @return array
 */
  public static function _setDate2( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    $input = null;
    if( iCalUtilityFunctions::_isArrayDate( $year )) {
      $input['value']  = iCalUtilityFunctions::_date_time_array( $year, 7 );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ) );
    }
    elseif( iCalUtilityFunctions::_isArrayTimestampDate( $year )) {
      $input['value']  = iCalUtilityFunctions::_timestamp2date( $year, 7 );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ) );
    }
    elseif( 8 <= strlen( trim( $year ))) { // ex. 2006-08-03 10:12:18
      $input['value']  = iCalUtilityFunctions::_date_time_string( $year, 7 );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ) );
    }
    else {
      $input['value']  = array( 'year'  => $year
                              , 'month' => $month
                              , 'day'   => $day
                              , 'hour'  => $hour
                              , 'min'   => $min
                              , 'sec'   => $sec );
      $input['params'] = iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' ));
    }
    $parno = iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME', 7 ); // remove default
    if( !isset( $input['value']['hour'] ))
      $input['value']['hour'] = 0;
    if( !isset( $input['value']['min'] ))
      $input['value']['min'] = 0;
    if( !isset( $input['value']['sec'] ))
      $input['value']['sec'] = 0;
    if( !isset( $input['value']['tz'] ) || !iCalUtilityFunctions::_isOffset( $input['value']['tz'] ))
      $input['value']['tz'] = 'Z';
    return $input;
  }
/**
 * check index and set (an indexed) content in multiple value array
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param array $valArr
 * @param mixed $value
 * @param array $params
 * @param array $defaults
 * @param int $index
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
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 1.x.x - 2007-05-01
 * @param array $params
 * @param array $defaults
 * @return array
 */
  public static function _setParams( $params, $defaults=FALSE ) {
    if( !is_array( $params))
      $params = array();
    $input = array();
    foreach( $params as $paramKey => $paramValue ) {
      if( is_array( $paramValue )) {
        foreach( $paramValue as $pkey => $pValue ) {
          if(( '"' == substr( $pValue, 0, 1 )) && ( '"' == substr( $pValue, -1 )))
            $paramValue[$pkey] = substr( $pValue, 1, ( strlen( $pValue ) - 2 ));
        }
      }
      elseif(( '"' == substr( $paramValue, 0, 1 )) && ( '"' == substr( $paramValue, -1 )))
        $paramValue = substr( $paramValue, 1, ( strlen( $paramValue ) - 2 ));
      if( 'VALUE' == strtoupper( $paramKey ))
        $input['VALUE']                 = strtoupper( $paramValue );
      else
        $input[strtoupper( $paramKey )] = $paramValue;
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
 * step date, return updated date, array and timpstamp
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-18
 * @param array $date, date to step
 * @param int $timestamp
 * @param array $step, default array( 'day' => 1 )
 * @return void
 */
  public static function _stepdate( &$date, &$timestamp, $step=array( 'day' => 1 )) {
    foreach( $step as $stepix => $stepvalue )
      $date[$stepix] += $stepvalue;
    $timestamp  = iCalUtilityFunctions::_date2timestamp( $date );
    $date       = iCalUtilityFunctions::_timestamp2date( $timestamp, 6 );
    foreach( $date as $k => $v ) {
      if( ctype_digit( $v ))
        $date[$k] = (int) $v;
    }
  }
/**
 * convert timestamp to date array
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-11-01
 * @param mixed $timestamp
 * @param int $parno
 * @return array
 */
  public static function _timestamp2date( $timestamp, $parno=6 ) {
    if( is_array( $timestamp )) {
      if(( 7 == $parno ) && !empty( $timestamp['tz'] ))
        $tz = $timestamp['tz'];
      $timestamp = $timestamp['timestamp'];
    }
    $output = array( 'year'  => date( 'Y', $timestamp )
                   , 'month' => date( 'm', $timestamp )
                   , 'day'   => date( 'd', $timestamp ));
    if( 3 != $parno ) {
             $output['hour'] =  date( 'H', $timestamp );
             $output['min']  =  date( 'i', $timestamp );
             $output['sec']  =  date( 's', $timestamp );
      if( isset( $tz ))
        $output['tz'] = $tz;
    }
    return $output;
  }
/**
 * convert timestamp to duration in array format
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.6.23 - 2010-10-23
 * @param int $timestamp
 * @return array, duration format
 */
  function _timestamp2duration( $timestamp ) {
    $dur         = array();
    $dur['week'] = (int) floor( $timestamp / ( 7 * 24 * 60 * 60 ));
    $timestamp   =              $timestamp % ( 7 * 24 * 60 * 60 );
    $dur['day']  = (int) floor( $timestamp / ( 24 * 60 * 60 ));
    $timestamp   =              $timestamp % ( 24 * 60 * 60 );
    $dur['hour'] = (int) floor( $timestamp / ( 60 * 60 ));
    $timestamp   =              $timestamp % ( 60 * 60 );
    $dur['min']  = (int) floor( $timestamp / ( 60 ));
    $dur['sec']  = (int)        $timestamp % ( 60 );
    return $dur;
  }
/**
 * convert (numeric) local time offset to seconds correcting localtime to GMT
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-19
 * @param string $offset
 * @return integer
 */
  public static function _tz2offset( $tz ) {
    $tz           = trim( (string) $tz );
    $offset       = 0;
    if(((     5  != strlen( $tz )) && ( 7  != strlen( $tz ))) ||
       ((    '+' != substr( $tz, 0, 1 )) && ( '-' != substr( $tz, 0, 1 ))) ||
       (( '0000' >= substr( $tz, 1, 4 )) && ( '9999' < substr( $tz, 1, 4 ))) ||
           (( 7  == strlen( $tz )) && ( '00' > substr( $tz, 5, 2 )) && ( '99' < substr( $tz, 5, 2 ))))
      return $offset;
    $hours2sec    = (int) substr( $tz, 1, 2 ) * 3600;
    $min2sec      = (int) substr( $tz, 3, 2 ) *   60;
    $sec          = ( 7  == strlen( $tz )) ? (int) substr( $tz, -2 ) : '00';
    $offset       = $hours2sec + $min2sec + $sec;
    $offset       = ('-' == substr( $tz, 0, 1 )) ? $offset : -1 * $offset;
    return $offset;
  }
}
?>