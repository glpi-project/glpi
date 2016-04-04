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
/*          iCalcreator vCard helper functions                                   */
/*********************************************************************************/
/**
 * convert single ATTENDEE, CONTACT or ORGANIZER (in email format) to vCard
 * returns vCard/TRUE or if directory (if set) or file write is unvalid, FALSE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.2 - 2012-07-11
 * @param string $email
 * @param string $version    vCard version (default 2.1)
 * @param string $directory  where to save vCards (default FALSE)
 * @param string $ext        vCard file extension (default 'vcf')
 * @uses ICALCREATOR_VERSION
 * @return mixed
 */
function iCal2vCard( $email, $version='2.1', $directory=FALSE, $ext='vcf' ) {
  if( FALSE === ( $pos = strpos( $email, '@' )))
    return FALSE;
  if( $directory ) {
    if( DIRECTORY_SEPARATOR != substr( $directory, ( 0 - strlen( DIRECTORY_SEPARATOR ))))
      $directory .= DIRECTORY_SEPARATOR;
    if( !is_dir( $directory ) || !is_writable( $directory ))
      return FALSE;
  }
            /* prepare vCard */
  $email  = str_replace( 'MAILTO:', '', $email );
  $name   = $person = substr( $email, 0, $pos );
  if( ctype_upper( $name ) || ctype_lower( $name ))
    $name = array( $name );
  else {
    if( FALSE !== ( $pos = strpos( $name, '.' ))) {
      $name = explode( '.', $name );
      foreach( $name as $k => $part )
        $name[$k] = ucfirst( $part );
    }
    else { // split camelCase
      $chars = $name;
      $name  = array( $chars[0] );
      $k     = 0;
      $x     = 1;
      while( FALSE !== ( $char = substr( $chars, $x, 1 ))) {
        if( ctype_upper( $char )) {
          $k += 1;
          $name[$k] = '';
        }
        $name[$k]  .= $char;
        $x++;
      }
    }
  }
  $nl     = "\r\n";
  $FN     = 'FN:'.implode( ' ', $name ).$nl;
  $name   = array_reverse( $name );
  $N      = 'N:'.array_shift( $name );
  $scCnt  = 0;
  while( NULL != ( $part = array_shift( $name ))) {
    if(( '4.0' != $version ) || ( 4 > $scCnt ))
      $scCnt += 1;
    $N   .= ';'.$part;
  }
  while(( '4.0' == $version ) && ( 4 > $scCnt )) {
    $N   .= ';';
    $scCnt += 1;
  }
  $N     .= $nl;
  $EMAIL  = 'EMAIL:'.$email.$nl;
           /* create vCard */
  $vCard  = 'BEGIN:VCARD'.$nl;
  $vCard .= "VERSION:$version$nl";
  $vCard .= 'PRODID:-//kigkonsult.se '.ICALCREATOR_VERSION."//$nl";
  $vCard .= $N;
  $vCard .= $FN;
  $vCard .= $EMAIL;
  $vCard .= 'REV:'.gmdate( 'Ymd\THis\Z' ).$nl;
  $vCard .= 'END:VCARD'.$nl;
            /* save each vCard as (unique) single file */
  if( $directory ) {
    $fname = $directory.preg_replace( '/[^a-z0-9.]/i', '', $email );
    $cnt   = 1;
    $dbl   = '';
    while( is_file ( $fname.$dbl.'.'.$ext )) {
      $cnt += 1;
      $dbl = "_$cnt";
    }
    if( FALSE === file_put_contents( $fname, $fname.$dbl.'.'.$ext ))
      return FALSE;
    return TRUE;
  }
            /* return vCard */
  else
    return $vCard;
}
/**
 * convert ATTENDEEs, CONTACTs and ORGANIZERs (in email format) to vCards
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.2 - 2012-05-07
 * @param object $calendar   iCalcreator vcalendar instance reference
 * @param string $version    vCard version (default 2.1)
 * @param string $directory  where to save vCards (default FALSE)
 * @param string $ext        vCard file extension (default 'vcf')
 * @uses vcalendar::getProperty()
 * @return mixed
 */
function iCal2vCards( & $calendar, $version='2.1', $directory=FALSE, $ext='vcf' ) {
  $hits   = array();
  $vCardP = array( 'ATTENDEE', 'CONTACT', 'ORGANIZER' );
  foreach( $vCardP as $prop ) {
    $hits2 = $calendar->getProperty( $prop );
    foreach( $hits2 as $propValue => $occCnt ) {
      if( FALSE === ( $pos = strpos( $propValue, '@' )))
        continue;
      $propValue = str_replace( 'MAILTO:', '', $propValue );
      if( isset( $hits[$propValue] ))
        $hits[$propValue] += $occCnt;
      else
        $hits[$propValue]  = $occCnt;
    }
  }
  if( empty( $hits ))
    return FALSE;
  ksort( $hits );
  $output   = '';
  foreach( $hits as $email => $skip ) {
    $res = iCal2vCard( $email, $version, $directory, $ext );
    if( $directory && !$res )
      return FALSE;
    elseif( !$res )
      return $res;
    else
      $output .= $res;
  }
  if( $directory )
    return TRUE;
  if( !empty( $output ))
    return $output;
  return FALSE;
}
