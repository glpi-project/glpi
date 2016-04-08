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
 *  abstract class for calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.11 - 2015-03-31
 */
class calendarComponent  extends iCalBase {
/** @var array component property UID value */
  protected $uid;
/** @var array component property DTSTAMP value */
  protected $dtstamp;
/** @var string component type */
  public $objName;
/**
 * constructor for calendar component object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-17
 * @uses calendarComponent::$objName
 * @uses calendarComponent::$timezonetype
 * @uses calendarComponent::$uid
 * @uses calendarComponent::$dtstamp
 * @uses calendarComponent::$language
 * @uses calendarComponent::$nl
 * @uses calendarComponent::$unique_id
 * @uses calendarComponent::$format
 * @uses calendarComponent::$dtzid
 * @uses calendarComponent::$allowEmpty
 * @uses calendarComponent::$xcaldecl
 * @uses calendarComponent::_createFormat()
 * @uses calendarComponent::_makeDtstamp()
 */
  function __construct() {
    $this->objName         = ( isset( $this->timezonetype )) ?
                          strtolower( $this->timezonetype )  :  get_class ( $this );
    $this->uid             = array();
    $this->dtstamp         = array();

    $this->language        = null;
    $this->nl              = null;
    $this->unique_id       = null;
    $this->format          = null;
    $this->dtzid           = null;
    $this->allowEmpty      = TRUE;
    $this->xcaldecl        = array();

    $this->_createFormat();
    $this->_makeDtstamp();
  }
/*********************************************************************************/
/**
 * Property Name: ACTION
 */
/**
 * creates formatted output for calendar component property action
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-22
 * @uses calendarComponent::$action
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createAction() {
    if( empty( $this->action )) return FALSE;
    if( empty( $this->action['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'ACTION' ) : FALSE;
    $attributes = $this->_createParams( $this->action['params'] );
    return $this->_createElement( 'ACTION', $attributes, $this->action['value'] );
  }
/**
 * set calendar component property action
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string $value  "AUDIO" / "DISPLAY" / "EMAIL" / "PROCEDURE"
 * @param mixed $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$action
 * @uses calendarComponent::$action
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setAction( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->action = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: ATTACH
 */
/**
 * creates formatted output for calendar component property attach
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.16 - 2012-02-04
 * @uses calendarComponent::$attach
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::$format
 * @uses calendarComponent::$intAttrDelimiter
 * @uses calendarComponent::$attributeDelimiter
 * @uses calendarComponent::$valueInit
 * @uses calendarComponent::$nl
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createAttach() {
    if( empty( $this->attach )) return FALSE;
    $output       = null;
    foreach( $this->attach as $attachPart ) {
      if( !empty( $attachPart['value'] )) {
        $attributes = $this->_createParams( $attachPart['params'] );
        if(( 'xcal' != $this->format ) && isset( $attachPart['params']['VALUE'] ) && ( 'BINARY' == $attachPart['params']['VALUE'] )) {
          $attributes = str_replace( $this->intAttrDelimiter, $this->attributeDelimiter, $attributes );
          $str        = 'ATTACH'.$attributes.$this->valueInit.$attachPart['value'];
          $output     = substr( $str, 0, 75 ).$this->nl;
          $str        = substr( $str, 75 );
          $output    .= ' '.chunk_split( $str, 74, $this->nl.' ' );
          if( ' ' == substr( $output, -1 ))
            $output   = rtrim( $output );
          if( $this->nl != substr( $output, ( 0 - strlen( $this->nl ))))
            $output  .= $this->nl;
          return $output;
        }
        $output    .= $this->_createElement( 'ATTACH', $attributes, $attachPart['value'] );
      }
      elseif( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'ATTACH' );
    }
    return $output;
  }
/**
 * set calendar component property attach
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::$attach
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setAttach( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->attach, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: ATTENDEE
 */
/**
 * creates formatted output for calendar component property attendee
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.12 - 2012-01-31
 * @uses calendarComponent::$attendee
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::$intAttrDelimiter
 * @return string
 */
  function createAttendee() {
    if( empty( $this->attendee )) return FALSE;
    $output = null;
    foreach( $this->attendee as $attendeePart ) {                      // start foreach 1
      if( empty( $attendeePart['value'] )) {
        if( $this->getConfig( 'allowEmpty' ))
          $output .= $this->_createElement( 'ATTENDEE' );
        continue;
      }
      $attendee1 = $attendee2 = null;
      foreach( $attendeePart as $paramlabel => $paramvalue ) {         // start foreach 2
        if( 'value' == $paramlabel )
          $attendee2     .= $paramvalue;
        elseif(( 'params' == $paramlabel ) && ( is_array( $paramvalue ))) { // start elseif
          $mParams = array( 'MEMBER', 'DELEGATED-TO', 'DELEGATED-FROM' );
          foreach( $paramvalue as $pKey => $pValue ) {                 // fix (opt) quotes
            if( is_array( $pValue ) || in_array( $pKey, $mParams ))
              continue;
            if(( FALSE !== strpos( $pValue, ':' )) ||
               ( FALSE !== strpos( $pValue, ';' )) ||
               ( FALSE !== strpos( $pValue, ',' )))
              $paramvalue[$pKey] = '"'.$pValue.'"';
          }
        // set attenddee parameters in rfc2445 order
          if( isset( $paramvalue['CUTYPE'] ))
            $attendee1   .= $this->intAttrDelimiter.'CUTYPE='.$paramvalue['CUTYPE'];
          if( isset( $paramvalue['MEMBER'] )) {
            $attendee1   .= $this->intAttrDelimiter.'MEMBER=';
            foreach( $paramvalue['MEMBER'] as $cix => $opv )
              $attendee1 .= ( $cix ) ? ',"'.$opv.'"' : '"'.$opv.'"' ;
          }
          if( isset( $paramvalue['ROLE'] ))
            $attendee1   .= $this->intAttrDelimiter.'ROLE='.$paramvalue['ROLE'];
          if( isset( $paramvalue['PARTSTAT'] ))
            $attendee1   .= $this->intAttrDelimiter.'PARTSTAT='.$paramvalue['PARTSTAT'];
          if( isset( $paramvalue['RSVP'] ))
            $attendee1   .= $this->intAttrDelimiter.'RSVP='.$paramvalue['RSVP'];
          if( isset( $paramvalue['DELEGATED-TO'] )) {
            $attendee1   .= $this->intAttrDelimiter.'DELEGATED-TO=';
            foreach( $paramvalue['DELEGATED-TO'] as $cix => $opv )
              $attendee1 .= ( $cix ) ? ',"'.$opv.'"' : '"'.$opv.'"' ;
          }
          if( isset( $paramvalue['DELEGATED-FROM'] )) {
            $attendee1   .= $this->intAttrDelimiter.'DELEGATED-FROM=';
            foreach( $paramvalue['DELEGATED-FROM'] as $cix => $opv )
              $attendee1 .= ( $cix ) ? ',"'.$opv.'"' : '"'.$opv.'"' ;
          }
          if( isset( $paramvalue['SENT-BY'] ))
            $attendee1   .= $this->intAttrDelimiter.'SENT-BY='.$paramvalue['SENT-BY'];
          if( isset( $paramvalue['CN'] ))
            $attendee1   .= $this->intAttrDelimiter.'CN='.$paramvalue['CN'];
          if( isset( $paramvalue['DIR'] )) {
            $delim = ( FALSE === strpos( $paramvalue['DIR'], '"' )) ? '"' : '';
            $attendee1   .= $this->intAttrDelimiter.'DIR='.$delim.$paramvalue['DIR'].$delim;
          }
          if( isset( $paramvalue['LANGUAGE'] ))
            $attendee1   .= $this->intAttrDelimiter.'LANGUAGE='.$paramvalue['LANGUAGE'];
          $xparams = array();
          foreach( $paramvalue as $optparamlabel => $optparamvalue ) { // start foreach 3
            if( ctype_digit( (string) $optparamlabel )) {
              $xparams[]  = $optparamvalue;
              continue;
            }
            if( !in_array( $optparamlabel, array( 'CUTYPE', 'MEMBER', 'ROLE', 'PARTSTAT', 'RSVP', 'DELEGATED-TO', 'DELEGATED-FROM', 'SENT-BY', 'CN', 'DIR', 'LANGUAGE' )))
              $xparams[$optparamlabel] = $optparamvalue;
          } // end foreach 3
          ksort( $xparams, SORT_STRING );
          foreach( $xparams as $paramKey => $paramValue ) {
            if( ctype_digit( (string) $paramKey ))
              $attendee1 .= $this->intAttrDelimiter.$paramValue;
            else
              $attendee1 .= $this->intAttrDelimiter."$paramKey=$paramValue";
          }      // end foreach 3
        }        // end elseif(( 'params' == $paramlabel ) && ( is_array( $paramvalue )))
      }          // end foreach 2
      $output .= $this->_createElement( 'ATTENDEE', $attendee1, $attendee2 );
    }              // end foreach 1
    return $output;
  }
/**
 * set calendar component property attach
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.13 - 2013-09-22
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$objName
 * @uses iCalUtilityFunctions::_existRem()
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setAttendee( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
          // ftp://, http://, mailto:, file://, gopher://, news:, nntp://, telnet://, wais://, prospero://  may exist.. . also in params
    if( !empty( $value )) {
      if( FALSE === ( $pos = strpos( substr( $value, 0, 9 ), ':' )))
        $value = 'MAILTO:'.$value;
      elseif( !empty( $value ))
        $value = strtolower( substr( $value, 0, $pos )).substr( $value, $pos );
      $value = str_replace( 'mailto:', 'MAILTO:', $value );
    }
    $params2 = array();
    if( is_array($params )) {
      $optarrays = array();
      $params = array_change_key_case( $params, CASE_UPPER );
      foreach( $params as $optparamlabel => $optparamvalue ) {
        if(( 'X-' != substr( $optparamlabel, 0, 2 )) && (( 'vfreebusy' == $this->objName ) || ( 'valarm' == $this->objName )))
          continue;
        switch( $optparamlabel ) {
          case 'MEMBER':
          case 'DELEGATED-TO':
          case 'DELEGATED-FROM':
            if( !is_array( $optparamvalue ))
              $optparamvalue = array( $optparamvalue );
            foreach( $optparamvalue as $part ) {
              $part = trim( $part );
              if(( '"' == substr( $part, 0, 1 )) &&
                 ( '"' == substr( $part, -1 )))
                $part = substr( $part, 1, ( strlen( $part ) - 2 ));
              if( 'mailto:' != strtolower( substr( $part, 0, 7 )))
                $part = "MAILTO:$part";
              else
                $part = 'MAILTO:'.substr( $part, 7 );
              $optarrays[$optparamlabel][] = $part;
            }
            break;
          default:
            if(( '"' == substr( $optparamvalue, 0, 1 )) &&
               ( '"' == substr( $optparamvalue, -1 )))
              $optparamvalue = substr( $optparamvalue, 1, ( strlen( $optparamvalue ) - 2 ));
            if( 'SENT-BY' ==  $optparamlabel ) {
              if( 'mailto:' != strtolower( substr( $optparamvalue, 0, 7 )))
                $optparamvalue = "MAILTO:$optparamvalue";
              else
                $optparamvalue = 'MAILTO:'.substr( $optparamvalue, 7 );
            }
            $params2[$optparamlabel] = $optparamvalue;
            break;
        } // end switch( $optparamlabel.. .
      } // end foreach( $optparam.. .
      foreach( $optarrays as $optparamlabel => $optparams )
        $params2[$optparamlabel] = $optparams;
    }
        // remove defaults
    iCalUtilityFunctions::_existRem( $params2, 'CUTYPE',   'INDIVIDUAL' );
    iCalUtilityFunctions::_existRem( $params2, 'PARTSTAT', 'NEEDS-ACTION' );
    iCalUtilityFunctions::_existRem( $params2, 'ROLE',     'REQ-PARTICIPANT' );
    iCalUtilityFunctions::_existRem( $params2, 'RSVP',     'FALSE' );
        // check language setting
    if( isset( $params2['CN' ] )) {
      $lang = $this->getConfig( 'language' );
      if( !isset( $params2['LANGUAGE' ] ) && !empty( $lang ))
        $params2['LANGUAGE' ] = $lang;
    }
    iCalUtilityFunctions::_setMval( $this->attendee, $value, $params2, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: CATEGORIES
 */
/**
 * creates formatted output for calendar component property categories
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$categories
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::$format
 * @uses calendarComponent::$nl
 * @return string
 */
  function createCategories() {
    if( empty( $this->categories )) return FALSE;
    $output = null;
    foreach( $this->categories as $category ) {
      if( empty( $category['value'] )) {
        if ( $this->getConfig( 'allowEmpty' ))
          $output .= $this->_createElement( 'CATEGORIES' );
        continue;
      }
      $attributes = $this->_createParams( $category['params'], array( 'LANGUAGE' ));
      if( is_array( $category['value'] )) {
        foreach( $category['value'] as $cix => $categoryPart )
          $category['value'][$cix] = iCalUtilityFunctions::_strrep( $categoryPart, $this->format, $this->nl );
        $content  = implode( ',', $category['value'] );
      }
      else
        $content  = iCalUtilityFunctions::_strrep( $category['value'], $this->format, $this->nl );
      $output    .= $this->_createElement( 'CATEGORIES', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property categories
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed   $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$categories
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setCategories( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->categories, $value, $params, FALSE, $index );
    return TRUE;
 }
/*********************************************************************************/
/**
 * Property Name: CLASS
 */
/**
 * creates formatted output for calendar component property class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.9.7 - 2006-11-20
 * @uses calendarComponent::$class
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createClass() {
    if( empty( $this->class )) return FALSE;
    if( empty( $this->class['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'CLASS' ) : FALSE;
    $attributes = $this->_createParams( $this->class['params'] );
    return $this->_createElement( 'CLASS', $attributes, $this->class['value'] );
  }
/**
 * set calendar component property class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string $value "PUBLIC" / "PRIVATE" / "CONFIDENTIAL" / iana-token / x-name
 * @param array  $params optional
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$class
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setClass( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->class = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: COMMENT
 */
/**
 * creates formatted output for calendar component property comment
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$comment
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::$format
 * @uses calendarComponent::$nl
 * @return string
 */
  function createComment() {
    if( empty( $this->comment )) return FALSE;
    $output = null;
    foreach( $this->comment as $commentPart ) {
      if( empty( $commentPart['value'] )) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'COMMENT' );
        continue;
      }
      $attributes = $this->_createParams( $commentPart['params'], array( 'ALTREP', 'LANGUAGE' ));
      $content    = iCalUtilityFunctions::_strrep( $commentPart['value'], $this->format, $this->nl );
      $output    .= $this->_createElement( 'COMMENT', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property comment
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$comment
 * @return bool
 */
  function setComment( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->comment, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: COMPLETED
 */
/**
 * creates formatted output for calendar component property completed
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-22
 * @return string
 * @uses calendarComponent::$completed
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_date2strdate();
 * @uses calendarComponent::_createParams()
 */
  function createCompleted( ) {
    if( empty( $this->completed )) return FALSE;
    if( !isset( $this->completed['value']['year'] )  &&
        !isset( $this->completed['value']['month'] ) &&
        !isset( $this->completed['value']['day'] )   &&
        !isset( $this->completed['value']['hour'] )  &&
        !isset( $this->completed['value']['min'] )   &&
        !isset( $this->completed['value']['sec'] ))
      if( $this->getConfig( 'allowEmpty' ))
        return $this->_createElement( 'COMPLETED' );
      else return FALSE;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->completed['value'], 7 );
    $attributes = $this->_createParams( $this->completed['params'] );
    return $this->_createElement( 'COMPLETED', $attributes, $formatted );
  }
/**
 * set calendar component property completed
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed $year
 * @param mixed $month
 * @param int   $day
 * @param int   $hour
 * @param int   $min
 * @param int   $sec
 * @param array $params
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses calendarComponent::$completed
 * @uses iCalUtilityFunctions::_setDate2()
 * @return bool
 */
  function setCompleted( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( empty( $year )) {
      if( $this->getConfig( 'allowEmpty' )) {
        $this->completed = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $params ));
        return TRUE;
      }
      else
        return FALSE;
    }
    $this->completed = iCalUtilityFunctions::_setDate2( $year, $month, $day, $hour, $min, $sec, $params );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: CONTACT
 */
/**
 * creates formatted output for calendar component property contact
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$contact
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createContact() {
    if( empty( $this->contact )) return FALSE;
    $output = null;
    foreach( $this->contact as $contact ) {
      if( !empty( $contact['value'] )) {
        $attributes = $this->_createParams( $contact['params'], array( 'ALTREP', 'LANGUAGE' ));
        $content    = iCalUtilityFunctions::_strrep( $contact['value'], $this->format, $this->nl );
        $output    .= $this->_createElement( 'CONTACT', $attributes, $content );
      }
      elseif( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'CONTACT' );
    }
    return $output;
  }
/**
 * set calendar component property contact
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$contact
 * @return bool
 */
  function setContact( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->contact, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: CREATED
 */
/**
 * creates formatted output for calendar component property created
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$created
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createCreated() {
    if( empty( $this->created )) return FALSE;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->created['value'], 7 );
    $attributes = $this->_createParams( $this->created['params'] );
    return $this->_createElement( 'CREATED', $attributes, $formatted );
  }
/**
 * set calendar component property created
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.5 - 2014-03-29
 * @param mixed $year
 * @param mixed $month
 * @param int   $day
 * @param int   $hour
 * @param int   $min
 * @param int   $sec
 * @param mixed $params
 * @uses calendarComponent::$created
 * @uses iCalUtilityFunctions::_setDate2()
 * @return bool
 */
  function setCreated( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( !isset( $year ))
      $year = gmdate( 'Ymd\THis' );
    $this->created = iCalUtilityFunctions::_setDate2( $year, $month, $day, $hour, $min, $sec, $params );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DESCRIPTION
 */
/**
 * creates formatted output for calendar component property description
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$description
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::getConfig()
 * @return string
 */
  function createDescription() {
    if( empty( $this->description )) return FALSE;
    $output       = null;
    foreach( $this->description as $description ) {
      if( !empty( $description['value'] )) {
        $attributes = $this->_createParams( $description['params'], array( 'ALTREP', 'LANGUAGE' ));
        $content    = iCalUtilityFunctions::_strrep( $description['value'], $this->format, $this->nl );
        $output    .= $this->_createElement( 'DESCRIPTION', $attributes, $content );
      }
      elseif( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'DESCRIPTION' );
    }
    return $output;
  }
/**
 * set calendar component property description
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$objName
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$description
 * @return bool
 */
  function setDescription( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) { if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE; }
    if( 'vjournal' != $this->objName )
      $index = 1;
    iCalUtilityFunctions::_setMval( $this->description, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DTEND
 */
/**
 * creates formatted output for calendar component property dtend
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.4 - 2012-09-26
 * @uses calendarComponent::$dtend
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createDtend() {
    if( empty( $this->dtend )) return FALSE;
    if( !isset( $this->dtend['value']['year'] )  &&
        !isset( $this->dtend['value']['month'] ) &&
        !isset( $this->dtend['value']['day'] )   &&
        !isset( $this->dtend['value']['hour'] )  &&
        !isset( $this->dtend['value']['min'] )   &&
        !isset( $this->dtend['value']['sec'] ))
      if( $this->getConfig( 'allowEmpty' ))
        return $this->_createElement( 'DTEND' );
      else return FALSE;
    $parno      = ( isset( $this->dtend['params']['VALUE'] ) && ( 'DATE' == $this->dtend['params']['VALUE'] )) ? 3 : null;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->dtend['value'], $parno );
    $attributes = $this->_createParams( $this->dtend['params'] );
    return $this->_createElement( 'DTEND', $attributes, $formatted );
  }
/**
 * set calendar component property dtend
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param string $tz
 * @param array  $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$dtend
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_setDate()
 * @return bool
 */
  function setDtend( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ) {
    if( empty( $year )) {
      if( $this->getConfig( 'allowEmpty' )) {
        $this->dtend = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $params ));
        return TRUE;
      }
      else
        return FALSE;
    }
    $this->dtend = iCalUtilityFunctions::_setDate( $year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig( 'TZID' ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DTSTAMP
 */
/**
 * creates formatted output for calendar component property dtstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.4 - 2008-03-07
 * @uses calendarComponent::$dtstamp
 * @uses calendarComponent::_makeDtstamp()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createDtstamp() {
    if( !isset( $this->dtstamp['value']['year'] )  &&
        !isset( $this->dtstamp['value']['month'] ) &&
        !isset( $this->dtstamp['value']['day'] )   &&
        !isset( $this->dtstamp['value']['hour'] )  &&
        !isset( $this->dtstamp['value']['min'] )   &&
        !isset( $this->dtstamp['value']['sec'] ))
      $this->_makeDtstamp();
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->dtstamp['value'], 7 );
    $attributes = $this->_createParams( $this->dtstamp['params'] );
    return $this->_createElement( 'DTSTAMP', $attributes, $formatted );
  }
/**
 * computes datestamp for calendar component object instance dtstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @uses iCalUtilityFunctions::$fmt
 * @uses calendarComponent::$dtstamp
 * @return void
 */
  function _makeDtstamp() {
    $d    = gmdate( iCalUtilityFunctions::$fmt['YmdHis3'], time());
    $date = explode( '-', $d );
    $this->dtstamp['value'] = array( 'year' => $date[0], 'month' => $date[1], 'day' => $date[2], 'hour' => $date[3], 'min' => $date[4], 'sec' => $date[5], 'tz' => 'Z' );
    $this->dtstamp['params'] = null;
  }
/**
 * set calendar component property dtstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-23
 * @param mixed $year
 * @param mixed $month
 * @param int   $day
 * @param int   $hour
 * @param int   $min
 * @param int   $sec
 * @param array $params
 * @uses calendarComponent::_makeDtstamp()
 * @uses calendarComponent::$dtstamp
 * @uses iCalUtilityFunctions::_setDate2()
 * @return TRUE
 */
  function setDtstamp( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( empty( $year ))
      $this->_makeDtstamp();
    else
      $this->dtstamp = iCalUtilityFunctions::_setDate2( $year, $month, $day, $hour, $min, $sec, $params );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DTSTART
 */
/**
 * creates formatted output for calendar component property dtstart
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.4 - 2012-09-26
 * @uses calendarComponent::$dtstart
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createDtstart() {
    if( empty( $this->dtstart )) return FALSE;
    if( !isset( $this->dtstart['value']['year'] )  &&
        !isset( $this->dtstart['value']['month'] ) &&
        !isset( $this->dtstart['value']['day'] )   &&
        !isset( $this->dtstart['value']['hour'] )  &&
        !isset( $this->dtstart['value']['min'] )   &&
        !isset( $this->dtstart['value']['sec'] )) {
      if( $this->getConfig( 'allowEmpty' ))
        return $this->_createElement( 'DTSTART' );
      else return FALSE;
    }
    if( in_array( $this->objName, array( 'vtimezone', 'standard', 'daylight' )))
       unset( $this->dtstart['value']['tz'], $this->dtstart['params']['TZID'] );
    $parno      = ( isset( $this->dtstart['params']['VALUE'] ) && ( 'DATE' == $this->dtstart['params']['VALUE'] )) ? 3 : null;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->dtstart['value'], $parno );
    $attributes = $this->_createParams( $this->dtstart['params'] );
    return $this->_createElement( 'DTSTART', $attributes, $formatted );
  }
/**
 * set calendar component property dtstart
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param string $tz
 * @param array  $params
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses calendarComponent::$dtstart
 * @uses iCalUtilityFunctions::_setDate()
 * @return bool
 */
  function setDtstart( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ) {
    if( empty( $year )) {
      if( $this->getConfig( 'allowEmpty' )) {
        $this->dtstart = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $params ));
        return TRUE;
      }
      else
        return FALSE;
    }
    $this->dtstart = iCalUtilityFunctions::_setDate( $year, $month, $day, $hour, $min, $sec, $tz, $params, 'dtstart', $this->objName, $this->getConfig( 'TZID' ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DUE
 */
/**
 * creates formatted output for calendar component property due
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.4 - 2012-09-26
 * @uses calendarComponent::$due
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createDue() {
    if( empty( $this->due )) return FALSE;
    if( !isset( $this->due['value']['year'] )  &&
        !isset( $this->due['value']['month'] ) &&
        !isset( $this->due['value']['day'] )   &&
        !isset( $this->due['value']['hour'] )  &&
        !isset( $this->due['value']['min'] )   &&
        !isset( $this->due['value']['sec'] )) {
      if( $this->getConfig( 'allowEmpty' ))
        return $this->_createElement( 'DUE' );
      else
       return FALSE;
    }
    $parno      = ( isset( $this->due['params']['VALUE'] ) && ( 'DATE' == $this->due['params']['VALUE'] )) ? 3 : null;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->due['value'], $parno );
    $attributes = $this->_createParams( $this->due['params'] );
    return $this->_createElement( 'DUE', $attributes, $formatted );
  }
/**
 * set calendar component property due
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param string $tz
 * @param array  $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$due
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_setDate()
 * @return bool
 */
  function setDue( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ) {
    if( empty( $year )) {
      if( $this->getConfig( 'allowEmpty' )) {
        $this->due = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $params ));
        return TRUE;
      }
      else
        return FALSE;
    }
    $this->due = iCalUtilityFunctions::_setDate( $year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig( 'TZID' ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: DURATION
 */
/**
 * creates formatted output for calendar component property duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-05-25
 * @uses calendarComponent::$duration
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_duration2str()
 * @return string
 */
  function createDuration() {
    if( empty( $this->duration )) return FALSE;
    if( !isset( $this->duration['value']['week'] ) &&
        !isset( $this->duration['value']['day'] )  &&
        !isset( $this->duration['value']['hour'] ) &&
        !isset( $this->duration['value']['min'] )  &&
        !isset( $this->duration['value']['sec'] ))
      if( $this->getConfig( 'allowEmpty' ))
        return $this->_createElement( 'DURATION' );
      else return FALSE;
    $attributes = $this->_createParams( $this->duration['params'] );
    return $this->_createElement( 'DURATION', $attributes, iCalUtilityFunctions::_duration2str( $this->duration['value'] ));
  }
/**
 * set calendar component property duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-05-25
 * @param mixed $week
 * @param mixed $day
 * @param int   $hour
 * @param int   $min
 * @param int   $sec
 * @param array $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$duration
 * @uses iCalUtilityFunctions::_duration2arr()
 * @uses iCalUtilityFunctions::_durationStr2arr()
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_duration2arr()
 * @return bool
 */
  function setDuration( $week, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( empty( $week ) && empty( $day ) && empty( $hour ) && empty( $min ) && empty( $sec )) {
      if( $this->getConfig( 'allowEmpty' ))
        $week = $day = null;
      else
        return FALSE;
    }
    if( is_array( $week ) && ( 1 <= count( $week )))
      $this->duration = array( 'value' => iCalUtilityFunctions::_duration2arr( $week ), 'params' => iCalUtilityFunctions::_setParams( $day ));
    elseif( is_string( $week ) && ( 3 <= strlen( trim( $week )))) {
      $week = trim( $week );
      if( in_array( substr( $week, 0, 1 ), array( '+', '-' )))
        $week = substr( $week, 1 );
      $this->duration = array( 'value' => iCalUtilityFunctions::_durationStr2arr( $week ), 'params' => iCalUtilityFunctions::_setParams( $day ));
    }
    else
      $this->duration = array( 'value' => iCalUtilityFunctions::_duration2arr( array( 'week' => $week, 'day' => $day, 'hour' => $hour, 'min' => $min, 'sec' => $sec ))
                             , 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: EXDATE
 */
/**
 * creates formatted output for calendar component property exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.5 - 2012-12-28
 * @uses calendarComponent::$exdate
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_sortExdate1()
 * @uses iCalUtilityFunctions::_sortExdate2()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createExdate() {
    if( empty( $this->exdate )) return FALSE;
    $output  = null;
    $exdates = array();
    foreach( $this->exdate as $theExdate ) {
      if( empty( $theExdate['value'] )) {
        if( $this->getConfig( 'allowEmpty' ))
          $output .= $this->_createElement( 'EXDATE' );
        continue;
      }
      if( 1 < count( $theExdate['value'] ))
        usort( $theExdate['value'], array( 'iCalUtilityFunctions', '_sortExdate1' ));
      $exdates[] = $theExdate;
    }
    if( 1 < count( $exdates ))
      usort( $exdates, array( 'iCalUtilityFunctions', '_sortExdate2' ));
    foreach( $exdates as $theExdate ) {
      $content = $attributes = null;
      foreach( $theExdate['value'] as $eix => $exdatePart ) {
        $parno = count( $exdatePart );
        $formatted = iCalUtilityFunctions::_date2strdate( $exdatePart, $parno );
        if( isset( $theExdate['params']['TZID'] ))
          $formatted = str_replace( 'Z', '', $formatted);
        if( 0 < $eix ) {
          if( isset( $theExdate['value'][0]['tz'] )) {
            if( ctype_digit( substr( $theExdate['value'][0]['tz'], -4 )) ||
               ( 'Z' == $theExdate['value'][0]['tz'] )) {
              if( 'Z' != substr( $formatted, -1 ))
                $formatted .= 'Z';
            }
            else
              $formatted = str_replace( 'Z', '', $formatted );
          }
          else
            $formatted = str_replace( 'Z', '', $formatted );
        } // end if( 0 < $eix )
        $content .= ( 0 < $eix ) ? ','.$formatted : $formatted;
      } // end foreach( $theExdate['value'] as $eix => $exdatePart )
      $attributes .= $this->_createParams( $theExdate['params'] );
      $output .= $this->_createElement( 'EXDATE', $attributes, $content );
    } // end foreach( $exdates as $theExdate )
    return $output;
  }
/**
 * set calendar component property exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array   $exdates
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$exdate
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_chkdatecfg()
 * @uses iCalUtilityFunctions::_existRem()
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_strdate2date()
 * @return bool
 */
  function setExdate( $exdates, $params=FALSE, $index=FALSE ) {
    if( empty( $exdates )) {
      if( $this->getConfig( 'allowEmpty' )) {
        iCalUtilityFunctions::_setMval( $this->exdate, '', $params, FALSE, $index );
        return TRUE;
      }
      else
        return FALSE;
    }
    $input  = array( 'params' => iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' )));
    $toZ = ( isset( $input['params']['TZID'] ) && in_array( strtoupper( $input['params']['TZID'] ), array( 'GMT', 'UTC', 'Z' ))) ? TRUE : FALSE;
            /* ev. check 1:st date and save ev. timezone **/
    iCalUtilityFunctions::_chkdatecfg( reset( $exdates ), $parno, $input['params'] );
    iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME' ); // remove default parameter
    foreach( $exdates as $eix => $theExdate ) {
      iCalUtilityFunctions::_strDate2arr( $theExdate );
      if( iCalUtilityFunctions::_isArrayTimestampDate( $theExdate )) {
        if( isset( $theExdate['tz'] ) && !iCalUtilityFunctions::_isOffset( $theExdate['tz'] )) {
          if( isset( $input['params']['TZID'] ))
            $theExdate['tz'] = $input['params']['TZID'];
          else
            $input['params']['TZID'] = $theExdate['tz'];
        }
        $exdatea = iCalUtilityFunctions::_timestamp2date( $theExdate, $parno );
      }
      elseif(  is_array( $theExdate )) {
        $d = iCalUtilityFunctions::_chkDateArr( $theExdate, $parno );
        if( isset( $d['tz'] ) && ( 'Z' != $d['tz'] ) && iCalUtilityFunctions::_isOffset( $d['tz'] )) {
          $strdate = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
          $exdatea = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
          unset( $exdatea['unparsedtext'] );
        }
        else
          $exdatea = $d;
      }
      elseif( 8 <= strlen( trim( $theExdate ))) { // ex. 2006-08-03 10:12:18
        $exdatea = iCalUtilityFunctions::_strdate2date( $theExdate, $parno );
        unset( $exdatea['unparsedtext'] );
      }
      if( 3 == $parno )
        unset( $exdatea['hour'], $exdatea['min'], $exdatea['sec'], $exdatea['tz'] );
      elseif( isset( $exdatea['tz'] ))
        $exdatea['tz'] = (string) $exdatea['tz'];
      if(  isset( $input['params']['TZID'] ) ||
         ( isset( $exdatea['tz'] ) && !iCalUtilityFunctions::_isOffset( $exdatea['tz'] )) ||
         ( isset( $input['value'][0] ) && ( !isset( $input['value'][0]['tz'] ))) ||
         ( isset( $input['value'][0]['tz'] ) && !iCalUtilityFunctions::_isOffset( $input['value'][0]['tz'] )))
        unset( $exdatea['tz'] );
      if( $toZ ) // time zone Z
        $exdatea['tz'] = 'Z';
      $input['value'][] = $exdatea;
    }
    if( 0 >= count( $input['value'] ))
      return FALSE;
    if( 3 == $parno ) {
      $input['params']['VALUE'] = 'DATE';
      unset( $input['params']['TZID'] );
    }
    if( $toZ ) // time zone Z
      unset( $input['params']['TZID'] );
    iCalUtilityFunctions::_setMval( $this->exdate, $input['value'], $input['params'], FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: EXRULE
 */
/**
 * creates formatted output for calendar component property exrule
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-22
 * @uses calendarComponent::$exrule
 * @uses calendarComponent::_format_recur()
 * @return string
 */
  function createExrule() {
    if( empty( $this->exrule )) return FALSE;
    return $this->_format_recur( 'EXRULE', $this->exrule );
  }
/**
 * set calendar component property exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param array   $exruleset
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$exrule
 * @uses iCalUtilityFunctions::_setRexrule()
 * @return bool
 */
  function setExrule( $exruleset, $params=FALSE, $index=FALSE ) {
    if( empty( $exruleset )) if( $this->getConfig( 'allowEmpty' )) $exruleset = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->exrule, iCalUtilityFunctions::_setRexrule( $exruleset ), $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: FREEBUSY
 */
/**
 * creates formatted output for calendar component property freebusy
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.27 - 2013-07-05
 * @uses calendarComponent::$freebusy
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::$intAttrDelimiter
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_sortRdate1()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses iCalUtilityFunctions::_duration2str()
 * @return string
 */
  function createFreebusy() {
    if( empty( $this->freebusy )) return FALSE;
    $output = null;
    foreach( $this->freebusy as $freebusyPart ) {
      if( empty( $freebusyPart['value'] ) || (( 1 == count( $freebusyPart['value'] )) && isset( $freebusyPart['value']['fbtype'] ))) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'FREEBUSY' );
        continue;
      }
      $attributes = $content = null;
      if( isset( $freebusyPart['value']['fbtype'] )) {
          $attributes .= $this->intAttrDelimiter.'FBTYPE='.$freebusyPart['value']['fbtype'];
        unset( $freebusyPart['value']['fbtype'] );
        $freebusyPart['value'] = array_values( $freebusyPart['value'] );
      }
      else
        $attributes .= $this->intAttrDelimiter.'FBTYPE=BUSY';
      $attributes .= $this->_createParams( $freebusyPart['params'] );
      $fno = 1;
      $cnt = count( $freebusyPart['value']);
      if( 1 < $cnt )
        usort( $freebusyPart['value'], array( 'iCalUtilityFunctions', '_sortRdate1' ));
      foreach( $freebusyPart['value'] as $periodix => $freebusyPeriod ) {
        $formatted   = iCalUtilityFunctions::_date2strdate( $freebusyPeriod[0] );
        $content .= $formatted;
        $content .= '/';
        $cnt2 = count( $freebusyPeriod[1]);
        if( array_key_exists( 'year', $freebusyPeriod[1] ))      // date-time
          $cnt2 = 7;
        elseif( array_key_exists( 'week', $freebusyPeriod[1] ))  // duration
          $cnt2 = 5;
        if(( 7 == $cnt2 )   &&    // period=  -> date-time
            isset( $freebusyPeriod[1]['year'] )  &&
            isset( $freebusyPeriod[1]['month'] ) &&
            isset( $freebusyPeriod[1]['day'] )) {
          $content .= iCalUtilityFunctions::_date2strdate( $freebusyPeriod[1] );
        }
        else {                                  // period=  -> dur-time
          $content .= iCalUtilityFunctions::_duration2str( $freebusyPeriod[1] );
        }
        if( $fno < $cnt )
          $content .= ',';
        $fno++;
      }
      $output .= $this->_createElement( 'FREEBUSY', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property freebusy
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $fbType
 * @param array   $fbValues
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$freebusy
 * @uses iCalUtilityFunctions::_isArrayDate()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_duration2arr()
 * @uses iCalUtilityFunctions::_durationStr2arr()
 * @uses iCalUtilityFunctions::_strdate2date()
 * @return bool
 */
  function setFreebusy( $fbType, $fbValues, $params=FALSE, $index=FALSE ) {
    if( empty( $fbValues )) {
      if( $this->getConfig( 'allowEmpty' )) {
        iCalUtilityFunctions::_setMval( $this->freebusy, '', $params, FALSE, $index );
        return TRUE;
      }
      else
        return FALSE;
    }
    $fbType = strtoupper( $fbType );
    if(( !in_array( $fbType, array( 'FREE', 'BUSY', 'BUSY-UNAVAILABLE', 'BUSY-TENTATIVE' ))) &&
       ( 'X-' != substr( $fbType, 0, 2 )))
      $fbType = 'BUSY';
    $input = array( 'fbtype' => $fbType );
    foreach( $fbValues as $fbPeriod ) {   // periods => period
      if( empty( $fbPeriod ))
        continue;
      $freebusyPeriod = array();
      foreach( $fbPeriod as $fbMember ) { // pairs => singlepart
        $freebusyPairMember = array();
        if( is_array( $fbMember )) {
          if( iCalUtilityFunctions::_isArrayDate( $fbMember )) { // date-time value
            $freebusyPairMember       = iCalUtilityFunctions::_chkDateArr( $fbMember, 7 );
            $freebusyPairMember['tz'] = 'Z';
          }
          elseif( iCalUtilityFunctions::_isArrayTimestampDate( $fbMember )) { // timestamp value
            $freebusyPairMember       = iCalUtilityFunctions::_timestamp2date( $fbMember['timestamp'], 7 );
            $freebusyPairMember['tz'] = 'Z';
          }
          else {                                         // array format duration
            $freebusyPairMember = iCalUtilityFunctions::_duration2arr( $fbMember );
          }
        }
        elseif(( 3 <= strlen( trim( $fbMember ))) &&    // string format duration
               ( in_array( $fbMember{0}, array( 'P', '+', '-' )))) {
          if( 'P' != $fbMember{0} )
            $fbmember = substr( $fbMember, 1 );
          $freebusyPairMember = iCalUtilityFunctions::_durationStr2arr( $fbMember );
        }
        elseif( 8 <= strlen( trim( $fbMember ))) { // text date ex. 2006-08-03 10:12:18
          $freebusyPairMember       = iCalUtilityFunctions::_strdate2date( $fbMember, 7 );
          unset( $freebusyPairMember['unparsedtext'] );
          $freebusyPairMember['tz'] = 'Z';
        }
        $freebusyPeriod[]   = $freebusyPairMember;
      }
      $input[]              = $freebusyPeriod;
    }
    iCalUtilityFunctions::_setMval( $this->freebusy, $input, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: GEO
 */
/**
 * creates formatted output for calendar component property geo
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.10 - 2013-09-03
 * @uses calendarComponent::$geo
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_geo2str2()
 * @uses iCalUtilityFunctions::$geoLongFmt
 * @uses iCalUtilityFunctions::$geoLatFmt
 * @return string
 */
  function createGeo() {
    if( empty( $this->geo )) return FALSE;
    if( empty( $this->geo['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'GEO' ) : FALSE;
    return $this->_createElement( 'GEO', $this->_createParams( $this->geo['params'] ),
                                  iCalUtilityFunctions::_geo2str2( $this->geo['value']['latitude'],  iCalUtilityFunctions::$geoLatFmt ).
                              ';'.iCalUtilityFunctions::_geo2str2( $this->geo['value']['longitude'], iCalUtilityFunctions::$geoLongFmt ));
  }
/**
 * set calendar component property geo
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.10 - 2013-09-03
 * @param mixed $latitude
 * @param mixed $longitude
 * @param array $params
 * @uses calendarComponent::$geo
 * @uses iCalUtilityFunctions::_setParams()
 * @uses calendarComponent::getConfig()
 * @return bool
 */
  function setGeo( $latitude, $longitude, $params=FALSE ) {
    if( isset( $latitude ) && isset( $longitude )) {
      if( !is_array( $this->geo )) $this->geo = array();
      $this->geo['value']['latitude']  = floatval( $latitude );
      $this->geo['value']['longitude'] = floatval( $longitude );
      $this->geo['params'] = iCalUtilityFunctions::_setParams( $params );
    }
    elseif( $this->getConfig( 'allowEmpty' ))
      $this->geo = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $params ) );
    else
      return FALSE;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: LAST-MODIFIED
 */
/**
 * creates formatted output for calendar component property last-modified
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$lastmodified
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createLastModified() {
    if( empty( $this->lastmodified )) return FALSE;
    $attributes = $this->_createParams( $this->lastmodified['params'] );
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->lastmodified['value'], 7 );
    return $this->_createElement( 'LAST-MODIFIED', $attributes, $formatted );
  }
/**
 * set calendar component property completed
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.5 - 2014-03-29
 * @param mixed $year optional
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
 * @uses calendarComponent::$lastmodified
 * @uses iCalUtilityFunctions::_setDate2()
 * @return boll
 */
  function setLastModified( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( empty( $year ))
      $year = gmdate( 'Ymd\THis' );
    $this->lastmodified = iCalUtilityFunctions::_setDate2( $year, $month, $day, $hour, $min, $sec, $params );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: LOCATION
 */
/**
 * creates formatted output for calendar component property location
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$location
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @return string
 */
  function createLocation() {
    if( empty( $this->location )) return FALSE;
    if( empty( $this->location['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'LOCATION' ) : FALSE;
    $attributes = $this->_createParams( $this->location['params'], array( 'ALTREP', 'LANGUAGE' ));
    $content    = iCalUtilityFunctions::_strrep( $this->location['value'], $this->format, $this->nl );
    return $this->_createElement( 'LOCATION', $attributes, $content );
  }
/**
 * set calendar component property location
 '
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::$location
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setLocation( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->location = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: ORGANIZER
 */
/**
 * creates formatted output for calendar component property organizer
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.33 - 2010-12-17
 * @uses calendarComponent::$organizer
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createOrganizer() {
    if( empty( $this->organizer )) return FALSE;
    if( empty( $this->organizer['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'ORGANIZER' ) : FALSE;
    $attributes = $this->_createParams( $this->organizer['params']
                                      , array( 'CN', 'DIR', 'SENT-BY', 'LANGUAGE' ));
    return $this->_createElement( 'ORGANIZER', $attributes, $this->organizer['value'] );
  }
/**
 * set calendar component property organizer
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$organizer
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setOrganizer( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    if( !empty( $value )) {
      if( FALSE === ( $pos = strpos( substr( $value, 0, 9 ), ':' )))
        $value = 'MAILTO:'.$value;
      elseif( !empty( $value ))
        $value = strtolower( substr( $value, 0, $pos )).substr( $value, $pos );
      $value = str_replace( 'mailto:', 'MAILTO:', $value );
    }
    $this->organizer = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    if( isset( $this->organizer['params']['SENT-BY'] )){
      if( 'mailto:' !== strtolower( substr( $this->organizer['params']['SENT-BY'], 0, 7 )))
        $this->organizer['params']['SENT-BY'] = 'MAILTO:'.$this->organizer['params']['SENT-BY'];
      else
        $this->organizer['params']['SENT-BY'] = 'MAILTO:'.substr( $this->organizer['params']['SENT-BY'], 7 );
    }
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: PERCENT-COMPLETE
 */
/**
 * creates formatted output for calendar component property percent-complete
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createPercentComplete() {
    if( !isset($this->percentcomplete) || ( empty( $this->percentcomplete ) && !is_numeric( $this->percentcomplete ))) return FALSE;
    if( !isset( $this->percentcomplete['value'] ) || ( empty( $this->percentcomplete['value'] ) && !is_numeric( $this->percentcomplete['value'] )))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'PERCENT-COMPLETE' ) : FALSE;
    $attributes = $this->_createParams( $this->percentcomplete['params'] );
    return $this->_createElement( 'PERCENT-COMPLETE', $attributes, $this->percentcomplete['value'] );
  }
/**
 * set calendar component property percent-complete
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param int    $value
 * @param array  $params
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setPercentComplete( $value, $params=FALSE ) {
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->percentcomplete = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: PRIORITY
 */
/**
 * creates formatted output for calendar component property priority
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @uses calendarComponent::$priority
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createPriority() {
    if( !isset($this->priority) || ( empty( $this->priority ) && !is_numeric( $this->priority ))) return FALSE;
    if( !isset( $this->priority['value'] ) || ( empty( $this->priority['value'] ) && !is_numeric( $this->priority['value'] )))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'PRIORITY' ) : FALSE;
    $attributes = $this->_createParams( $this->priority['params'] );
    return $this->_createElement( 'PRIORITY', $attributes, $this->priority['value'] );
  }
/**
 * set calendar component property priority
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param int    $value
 * @param array  $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$priority
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setPriority( $value, $params=FALSE  ) {
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->priority = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: RDATE
 */
/**
 * creates formatted output for calendar component property rdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.9 - 2013-01-09
 * @uses calendarComponent::$rdate
 * @uses calendarComponent::$objName
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_sortRdate1()
 * @uses iCalUtilityFunctions::_sortRdate2()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @return string
 */
  function createRdate() {
    if( empty( $this->rdate )) return FALSE;
    $utctime = ( in_array( $this->objName, array( 'vtimezone', 'standard', 'daylight' ))) ? TRUE : FALSE;
    $output = null;
    $rdates = array();
    foreach( $this->rdate as $rpix => $theRdate ) {
      if( empty( $theRdate['value'] )) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'RDATE' );
        continue;
      }
      if( $utctime  )
        unset( $theRdate['params']['TZID'] );
      if( 1 < count( $theRdate['value'] ))
        usort( $theRdate['value'], array( 'iCalUtilityFunctions', '_sortRdate1' ));
      $rdates[] = $theRdate;
    }
    if( 1 < count( $rdates ))
      usort( $rdates, array( 'iCalUtilityFunctions', '_sortRdate2' ));
    foreach( $rdates as $rpix => $theRdate ) {
      $attributes = $this->_createParams( $theRdate['params'] );
      $cnt = count( $theRdate['value'] );
      $content = null;
      $rno = 1;
      foreach( $theRdate['value'] as $rix => $rdatePart ) {
        $contentPart = null;
        if( is_array( $rdatePart ) &&
            isset( $theRdate['params']['VALUE'] ) && ( 'PERIOD' == $theRdate['params']['VALUE'] )) { // PERIOD
          if( $utctime )
            unset( $rdatePart[0]['tz'] );
          $formatted = iCalUtilityFunctions::_date2strdate( $rdatePart[0] ); // PERIOD part 1
          if( $utctime || !empty( $theRdate['params']['TZID'] ))
            $formatted = str_replace( 'Z', '', $formatted);
          $contentPart .= $formatted;
          $contentPart .= '/';
          $cnt2 = count( $rdatePart[1]);
          if( array_key_exists( 'year', $rdatePart[1] )) {
            if( array_key_exists( 'hour', $rdatePart[1] ))
              $cnt2 = 7;                                      // date-time
            else
              $cnt2 = 3;                                      // date
          }
          elseif( array_key_exists( 'week', $rdatePart[1] ))  // duration
            $cnt2 = 5;
          if(( 7 == $cnt2 )   &&    // period=  -> date-time
              isset( $rdatePart[1]['year'] )  &&
              isset( $rdatePart[1]['month'] ) &&
              isset( $rdatePart[1]['day'] )) {
            if( $utctime )
              unset( $rdatePart[1]['tz'] );
            $formatted = iCalUtilityFunctions::_date2strdate( $rdatePart[1] ); // PERIOD part 2
            if( $utctime || !empty( $theRdate['params']['TZID'] ))
              $formatted = str_replace( 'Z', '', $formatted );
           $contentPart .= $formatted;
          }
          else {                                  // period=  -> dur-time
            $contentPart .= iCalUtilityFunctions::_duration2str( $rdatePart[1] );
          }
        } // PERIOD end
        else { // SINGLE date start
          if( $utctime )
            unset( $rdatePart['tz'] );
          $parno = ( isset( $theRdate['params']['VALUE'] ) && ( 'DATE' == isset( $theRdate['params']['VALUE'] ))) ? 3 : null;
          $formatted = iCalUtilityFunctions::_date2strdate( $rdatePart, $parno );
          if( $utctime || !empty( $theRdate['params']['TZID'] ))
            $formatted = str_replace( 'Z', '', $formatted);
          $contentPart .= $formatted;
        }
        $content .= $contentPart;
        if( $rno < $cnt )
          $content .= ',';
        $rno++;
      }
      $output    .= $this->_createElement( 'RDATE', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property rdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array   $rdates
 * @param array   $params
 * @param integer $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$rdate
 * @uses iCalUtilityFunctions::_setParams()
 * @uses calendarComponent::$objName
 * @uses iCalUtilityFunctions::_isArrayDate()
 * @uses iCalUtilityFunctions::_chkdatecfg()
 * @uses iCalUtilityFunctions::_existRem()
 * @uses iCalUtilityFunctions::_strDate2arr()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_isOffset()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_chkDateArr()
 * @uses iCalUtilityFunctions::$fmt
 * @uses iCalUtilityFunctions::_strdate2date()
 * @uses iCalUtilityFunctions::_duration2arr()
 * @uses iCalUtilityFunctions::_durationStr2arr()
 * @return bool
 */
  function setRdate( $rdates, $params=FALSE, $index=FALSE ) {
    if( empty( $rdates )) {
      if( $this->getConfig( 'allowEmpty' )) {
        iCalUtilityFunctions::_setMval( $this->rdate, '', $params, FALSE, $index );
        return TRUE;
      }
      else
        return FALSE;
    }
    $input = array( 'params' => iCalUtilityFunctions::_setParams( $params, array( 'VALUE' => 'DATE-TIME' )));
    if( in_array( $this->objName, array( 'vtimezone', 'standard', 'daylight' ))) {
      unset( $input['params']['TZID'] );
      $input['params']['VALUE'] = 'DATE-TIME';
    }
    $zArr = array( 'GMT', 'UTC', 'Z' );
    $toZ = ( isset( $params['TZID'] ) && in_array( strtoupper( $params['TZID'] ), $zArr )) ? TRUE : FALSE;
            /*  check if PERIOD, if not set */
    if((!isset( $input['params']['VALUE'] ) || !in_array( $input['params']['VALUE'], array( 'DATE', 'PERIOD' ))) &&
          isset( $rdates[0] )    && is_array( $rdates[0] ) && ( 2 == count( $rdates[0] )) &&
          isset( $rdates[0][0] ) &&    isset( $rdates[0][1] ) && !isset( $rdates[0]['timestamp'] ) &&
    (( is_array( $rdates[0][0] ) && ( isset( $rdates[0][0]['timestamp'] ) ||
                                      iCalUtilityFunctions::_isArrayDate( $rdates[0][0] ))) ||
                                    ( is_string( $rdates[0][0] ) && ( 8 <= strlen( trim( $rdates[0][0] )))))  &&
     ( is_array( $rdates[0][1] ) || ( is_string( $rdates[0][1] ) && ( 3 <= strlen( trim( $rdates[0][1] ))))))
      $input['params']['VALUE'] = 'PERIOD';
            /* check 1:st date, upd. $parno (opt) and save ev. timezone **/
    $date  = reset( $rdates );
    if( isset( $input['params']['VALUE'] ) && ( 'PERIOD' == $input['params']['VALUE'] )) // PERIOD
      $date  = reset( $date );
    iCalUtilityFunctions::_chkdatecfg( $date, $parno, $input['params'] );
    iCalUtilityFunctions::_existRem( $input['params'], 'VALUE', 'DATE-TIME' ); // remove default
    foreach( $rdates as $rpix => $theRdate ) {
      $inputa = null;
      iCalUtilityFunctions::_strDate2arr( $theRdate );
      if( is_array( $theRdate )) {
        if( isset( $input['params']['VALUE'] ) && ( 'PERIOD' == $input['params']['VALUE'] )) { // PERIOD
          foreach( $theRdate as $rix => $rPeriod ) {
            iCalUtilityFunctions::_strDate2arr( $theRdate );
            if( is_array( $rPeriod )) {
              if( iCalUtilityFunctions::_isArrayTimestampDate( $rPeriod )) {    // timestamp
                if( isset( $rPeriod['tz'] ) && !iCalUtilityFunctions::_isOffset( $rPeriod['tz'] )) {
                  if( isset( $input['params']['TZID'] ))
                    $rPeriod['tz'] = $input['params']['TZID'];
                  else
                    $input['params']['TZID'] = $rPeriod['tz'];
                }
                $inputab = iCalUtilityFunctions::_timestamp2date( $rPeriod, $parno );
              }
              elseif( iCalUtilityFunctions::_isArrayDate( $rPeriod )) {
                $d = ( 3 < count ( $rPeriod )) ? iCalUtilityFunctions::_chkDateArr( $rPeriod, $parno ) : iCalUtilityFunctions::_chkDateArr( $rPeriod, 6 );
                if( isset( $d['tz'] ) && ( 'Z' != $d['tz'] ) && iCalUtilityFunctions::_isOffset( $d['tz'] )) {
                  $strdate = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $d['year'], (int) $d['month'], (int) $d['day'], (int) $d['hour'], (int) $d['min'], (int) $d['sec'], $d['tz'] );
                  $inputab = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
                  unset( $inputab['unparsedtext'] );
                }
                else
                  $inputab = $d;
              }
              elseif (( 1 == count( $rPeriod )) && ( 8 <= strlen( reset( $rPeriod )))) { // text-date
                $inputab   = iCalUtilityFunctions::_strdate2date( reset( $rPeriod ), $parno );
                unset( $inputab['unparsedtext'] );
              }
              else                                               // array format duration
                $inputab   = iCalUtilityFunctions::_duration2arr( $rPeriod );
            }
            elseif(( 3 <= strlen( trim( $rPeriod ))) &&          // string format duration
                   ( in_array( $rPeriod[0], array( 'P', '+', '-' )))) {
              if( 'P' != $rPeriod[0] )
                $rPeriod   = substr( $rPeriod, 1 );
              $inputab     = iCalUtilityFunctions::_durationStr2arr( $rPeriod );
            }
            elseif( 8 <= strlen( trim( $rPeriod ))) {            // text date ex. 2006-08-03 10:12:18
              $inputab     = iCalUtilityFunctions::_strdate2date( $rPeriod, $parno );
              unset( $inputab['unparsedtext'] );
            }
            if(( 0 == $rpix ) && ( 0 == $rix )) {
              if( isset( $inputab['tz'] ) && in_array( strtoupper( $inputab['tz'] ), $zArr )) {
                $inputab['tz'] = 'Z';
                $toZ = TRUE;
              }
            }
            else {
              if( isset( $inputa[0]['tz'] ) && ( 'Z' == $inputa[0]['tz'] ) && isset( $inputab['year'] ))
                $inputab['tz'] = 'Z';
              else
                unset( $inputab['tz'] );
            }
            if( $toZ && isset( $inputab['year'] ) )
              $inputab['tz'] = 'Z';
            $inputa[]      = $inputab;
          }
        } // PERIOD end
        elseif ( iCalUtilityFunctions::_isArrayTimestampDate( $theRdate )) {    // timestamp
          if( isset( $theRdate['tz'] ) && !iCalUtilityFunctions::_isOffset( $theRdate['tz'] )) {
            if( isset( $input['params']['TZID'] ))
              $theRdate['tz'] = $input['params']['TZID'];
            else
              $input['params']['TZID'] = $theRdate['tz'];
          }
          $inputa = iCalUtilityFunctions::_timestamp2date( $theRdate, $parno );
        }
        else {                                                                  // date[-time]
          $inputa = iCalUtilityFunctions::_chkDateArr( $theRdate, $parno );
          if( isset( $inputa['tz'] ) && ( 'Z' != $inputa['tz'] ) && iCalUtilityFunctions::_isOffset( $inputa['tz'] )) {
            $strdate = sprintf( iCalUtilityFunctions::$fmt['YmdHise'], (int) $inputa['year'], (int) $inputa['month'], (int) $inputa['day'], (int) $inputa['hour'], (int) $inputa['min'], (int) $inputa['sec'], $inputa['tz'] );
            $inputa  = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
            unset( $inputa['unparsedtext'] );
          }
        }
      }
      elseif( 8 <= strlen( trim( $theRdate ))) {                 // text date ex. 2006-08-03 10:12:18
        $inputa       = iCalUtilityFunctions::_strdate2date( $theRdate, $parno );
        unset( $inputa['unparsedtext'] );
        if( $toZ )
          $inputa['tz'] = 'Z';
      }
      if( !isset( $input['params']['VALUE'] ) || ( 'PERIOD' != $input['params']['VALUE'] )) { // no PERIOD
        if(( 0 == $rpix ) && !$toZ )
          $toZ = ( isset( $inputa['tz'] ) && in_array( strtoupper( $inputa['tz'] ), $zArr )) ? TRUE : FALSE;
        if( $toZ )
          $inputa['tz']    = 'Z';
        if( 3 == $parno )
          unset( $inputa['hour'], $inputa['min'], $inputa['sec'], $inputa['tz'] );
        elseif( isset( $inputa['tz'] ))
          $inputa['tz']    = (string) $inputa['tz'];
        if( isset( $input['params']['TZID'] ) || ( isset( $input['value'][0] ) && ( !isset( $input['value'][0]['tz'] ))))
          if( !$toZ )
            unset( $inputa['tz'] );
      }
      $input['value'][]    = $inputa;
    }
    if( 3 == $parno ) {
      $input['params']['VALUE'] = 'DATE';
      unset( $input['params']['TZID'] );
    }
    if( $toZ )
      unset( $input['params']['TZID'] );
    iCalUtilityFunctions::_setMval( $this->rdate, $input['value'], $input['params'], FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: RECURRENCE-ID
 */
/**
 * creates formatted output for calendar component property recurrence-id
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.4 - 2012-09-26
 * @uses calendarComponent::$recurrenceid
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createRecurrenceid() {
    if( empty( $this->recurrenceid )) return FALSE;
    if( empty( $this->recurrenceid['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'RECURRENCE-ID' ) : FALSE;
    $parno      = ( isset( $this->recurrenceid['params']['VALUE'] ) && ( 'DATE' == $this->recurrenceid['params']['VALUE'] )) ? 3 : null;
    $formatted  = iCalUtilityFunctions::_date2strdate( $this->recurrenceid['value'], $parno );
    $attributes = $this->_createParams( $this->recurrenceid['params'] );
    return $this->_createElement( 'RECURRENCE-ID', $attributes, $formatted );
  }
/**
 * set calendar component property recurrence-id
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed   $year
 * @param mixed   $month
 * @param int     $day
 * @param int     $hour
 * @param int     $min
 * @param int     $sec
 * @param string  $tz
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$recurrenceid
 * @uses iCalUtilityFunctions::_setDate()
 * @return bool
 */
  function setRecurrenceid( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE ) {
    if( empty( $year )) {
      if( $this->getConfig( 'allowEmpty' )) {
        $this->recurrenceid = array( 'value' => '', 'params' => null );
        return TRUE;
      }
      else
        return FALSE;
    }
    $this->recurrenceid = iCalUtilityFunctions::_setDate( $year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig( 'TZID' ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: RELATED-TO
 */
/**
 * creates formatted output for calendar component property related-to
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-05-25
 * @uses calendarComponent::$relatedto
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::getConfig()
 * @return string
 */
  function createRelatedTo() {
    if( empty( $this->relatedto )) return FALSE;
    $output = null;
    foreach( $this->relatedto as $relation ) {
      if( !empty( $relation['value'] ))
        $output .= $this->_createElement( 'RELATED-TO', $this->_createParams( $relation['params'] ), iCalUtilityFunctions::_strrep( $relation['value'], $this->format, $this->nl ));
      elseif( $this->getConfig( 'allowEmpty' ))
        $output .= $this->_createElement( 'RELATED-TO' );
    }
    return $output;
  }
/**
 * set calendar component property related-to
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @param int     $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_existRem()
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setRelatedTo( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_existRem( $params, 'RELTYPE', 'PARENT', TRUE ); // remove default
    iCalUtilityFunctions::_setMval( $this->relatedto, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: REPEAT
 */
/**
 * creates formatted output for calendar component property repeat
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @uses calendarComponent::$repeat
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createRepeat() {
    if( !isset( $this->repeat ) || ( empty( $this->repeat ) && !is_numeric( $this->repeat ))) return FALSE;
    if( !isset( $this->repeat['value']) || ( empty( $this->repeat['value'] ) && !is_numeric( $this->repeat['value'] )))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'REPEAT' ) : FALSE;
    $attributes = $this->_createParams( $this->repeat['params'] );
    return $this->_createElement( 'REPEAT', $attributes, $this->repeat['value'] );
  }
/**
 * set calendar component property repeat
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$repeat
 * @uses iCalUtilityFunctions::_setParams()
 * @return void
 */
  function setRepeat( $value, $params=FALSE ) {
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->repeat = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: REQUEST-STATUS
 */
/**
 * creates formatted output for calendar component property request-status
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$requeststatus
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @return string
 */
  function createRequestStatus() {
    if( empty( $this->requeststatus )) return FALSE;
    $output = null;
    foreach( $this->requeststatus as $rstat ) {
      if( empty( $rstat['value']['statcode'] )) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'REQUEST-STATUS' );
        continue;
      }
      $attributes  = $this->_createParams( $rstat['params'], array( 'LANGUAGE' ));
      $content     = number_format( (float) $rstat['value']['statcode'], 2, '.', '');
      $content    .= ';'.iCalUtilityFunctions::_strrep( $rstat['value']['text'], $this->format, $this->nl );
      if( isset( $rstat['value']['extdata'] ))
        $content  .= ';'.iCalUtilityFunctions::_strrep( $rstat['value']['extdata'], $this->format, $this->nl );
      $output     .= $this->_createElement( 'REQUEST-STATUS', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property request-status
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param float    $statcode
 * @param string   $text
 * @param string   $extdata
 * @param array    $params
 * @param integer  $index
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$requeststatus
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setRequestStatus( $statcode, $text, $extdata=FALSE, $params=FALSE, $index=FALSE ) {
    if( empty( $statcode ) || empty( $text )) if( $this->getConfig( 'allowEmpty' )) $statcode = $text = ''; else return FALSE;
    $input              = array( 'statcode' => $statcode, 'text' => $text );
    if( $extdata )
      $input['extdata'] = $extdata;
    iCalUtilityFunctions::_setMval( $this->requeststatus, $input, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: RESOURCES
 */
/**
 * creates formatted output for calendar component property resources
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$resources
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @return string
 */
  function createResources() {
    if( empty( $this->resources )) return FALSE;
    $output = null;
    foreach( $this->resources as $resource ) {
      if( empty( $resource['value'] )) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'RESOURCES' );
        continue;
      }
      $attributes  = $this->_createParams( $resource['params'], array( 'ALTREP', 'LANGUAGE' ));
      if( is_array( $resource['value'] )) {
        foreach( $resource['value'] as $rix => $resourcePart )
          $resource['value'][$rix] = iCalUtilityFunctions::_strrep( $resourcePart, $this->format, $this->nl );
        $content   = implode( ',', $resource['value'] );
      }
      else
        $content   = iCalUtilityFunctions::_strrep( $resource['value'], $this->format, $this->nl );
      $output     .= $this->_createElement( 'RESOURCES', $attributes, $content );
    }
    return $output;
  }
/**
 * set calendar component property recources
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed    $value
 * @param array    $params
 * @param integer  $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses calendarComponent::$resources
 * @return bool
 */
  function setResources( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->resources, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: RRULE
 */
/**
 * creates formatted output for calendar component property rrule
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$rrule
 * @uses calendarComponent::_format_recur()
 * @return string
 */
  function createRrule() {
    if( empty( $this->rrule )) return FALSE;
    return $this->_format_recur( 'RRULE', $this->rrule );
  }
/**
 * set calendar component property rrule
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param array    $rruleset
 * @param array    $params
 * @param integer  $index
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setMval()
 * @uses iCalUtilityFunctions::_setRexrule()
 * @return void
 */
  function setRrule( $rruleset, $params=FALSE, $index=FALSE ) {
    if( empty( $rruleset )) if( $this->getConfig( 'allowEmpty' )) $rruleset = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->rrule, iCalUtilityFunctions::_setRexrule( $rruleset ), $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: SEQUENCE
 */
/**
 * creates formatted output for calendar component property sequence
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.3 - 2011-05-14
 * @uses calendarComponent::$sequence
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createSequence() {
    if( !isset( $this->sequence ) || ( empty( $this->sequence ) && !is_numeric( $this->sequence ))) return FALSE;
    if(( !isset($this->sequence['value'] ) || ( empty( $this->sequence['value'] ) && !is_numeric( $this->sequence['value'] ))) &&
       ( '0' != $this->sequence['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'SEQUENCE' ) : FALSE;
    $attributes = $this->_createParams( $this->sequence['params'] );
    return $this->_createElement( 'SEQUENCE', $attributes, $this->sequence['value'] );
  }
/**
 * set calendar component property sequence
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.8 - 2011-09-19
 * @param int    $value
 * @param array  $params
 * @uses calendarComponent::$sequence
 * @uses iCalUtilityFunctions::_setParams();
 * @return bool
 */
  function setSequence( $value=FALSE, $params=FALSE ) {
    if(( empty( $value ) && !is_numeric( $value )) && ( '0' != $value ))
      $value = ( isset( $this->sequence['value'] ) && ( -1 < $this->sequence['value'] )) ? $this->sequence['value'] + 1 : '0';
    $this->sequence = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: STATUS
 */
/**
 * creates formatted output for calendar component property status
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$status
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createStatus() {
    if( empty( $this->status )) return FALSE;
    if( empty( $this->status['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'STATUS' ) : FALSE;
    $attributes = $this->_createParams( $this->status['params'] );
    return $this->_createElement( 'STATUS', $attributes, $this->status['value'] );
  }
/**
 * set calendar component property status
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$status
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setStatus( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->status = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: SUMMARY
 */
/**
 * creates formatted output for calendar component property summary
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$summary
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @return string
 */
  function createSummary() {
    if( empty( $this->summary )) return FALSE;
    if( empty( $this->summary['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'SUMMARY' ) : FALSE;
    $attributes = $this->_createParams( $this->summary['params'], array( 'ALTREP', 'LANGUAGE' ));
    $content    = iCalUtilityFunctions::_strrep( $this->summary['value'], $this->format, $this->nl );
    return $this->_createElement( 'SUMMARY', $attributes, $content );
  }
/**
 * set calendar component property summary
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param string  $params
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setSummary( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->summary = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: TRANSP
 */
/**
 * creates formatted output for calendar component property transp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$transp
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createTransp() {
    if( empty( $this->transp )) return FALSE;
    if( empty( $this->transp['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TRANSP' ) : FALSE;
    $attributes = $this->_createParams( $this->transp['params'] );
    return $this->_createElement( 'TRANSP', $attributes, $this->transp['value'] );
  }
/**
 * set calendar component property transp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param string  $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$transp
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setTransp( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->transp = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: TRIGGER
 */
/**
 * creates formatted output for calendar component property trigger
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-21
 * @uses calendarComponent::$trigger
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses calendarComponent::$intAttrDelimiter
 * @uses iCalUtilityFunctions::_duration2str()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createTrigger() {
    if( empty( $this->trigger )) return FALSE;
    if( empty( $this->trigger['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TRIGGER' ) : FALSE;
    $content = $attributes = null;
    if( isset( $this->trigger['value']['year'] )   &&
        isset( $this->trigger['value']['month'] )  &&
        isset( $this->trigger['value']['day'] ))
      $content      .= iCalUtilityFunctions::_date2strdate( $this->trigger['value'] );
    else {
      if( TRUE !== $this->trigger['value']['relatedStart'] )
        $attributes .= $this->intAttrDelimiter.'RELATED=END';
      if( $this->trigger['value']['before'] )
        $content    .= '-';
      $content      .= iCalUtilityFunctions::_duration2str( $this->trigger['value'] );
    }
    $attributes     .= $this->_createParams( $this->trigger['params'] );
    return $this->_createElement( 'TRIGGER', $attributes, $content );
  }
/**
 * set calendar component property trigger
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $week
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param bool   $relatedStart
 * @param bool   $before
 * @param array  $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$trigger
 * @uses iCalUtilityFunctions::_setParams()
 * @uses iCalUtilityFunctions::_isArrayTimestampDate()
 * @uses iCalUtilityFunctions::_timestamp2date()
 * @uses iCalUtilityFunctions::_strdate2date()
 * @uses iCalUtilityFunctions::_duration2arr()
 * @return bool
 */
  function setTrigger( $year, $month=null, $day=null, $week=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $relatedStart=TRUE, $before=TRUE, $params=FALSE ) {
    if( empty( $year ) && ( empty( $month ) || is_array( $month )) && empty( $day ) && empty( $week ) && empty( $hour ) && empty( $min ) && empty( $sec ))
      if( $this->getConfig( 'allowEmpty' )) {
        $this->trigger = array( 'value' => '', 'params' => iCalUtilityFunctions::_setParams( $month ) );
        return TRUE;
      }
      else
        return FALSE;
    if( iCalUtilityFunctions::_isArrayTimestampDate( $year )) { // timestamp UTC
      $params = iCalUtilityFunctions::_setParams( $month );
      $date   = iCalUtilityFunctions::_timestamp2date( $year, 7 );
      foreach( $date as $k => $v )
        $$k = $v;
    }
    elseif( is_array( $year ) && ( is_array( $month ) || empty( $month ))) {
      $params = iCalUtilityFunctions::_setParams( $month );
      if(!(array_key_exists( 'year',  $year ) &&   // exclude date-time
           array_key_exists( 'month', $year ) &&
           array_key_exists( 'day',   $year ))) {  // when this must be a duration
        if( isset( $params['RELATED'] ) && ( 'END' == strtoupper( $params['RELATED'] )))
          $relatedStart = FALSE;
        else
          $relatedStart = ( array_key_exists( 'relatedStart', $year ) && ( TRUE !== $year['relatedStart'] )) ? FALSE : TRUE;
        $before         = ( array_key_exists( 'before', $year )       && ( TRUE !== $year['before'] ))       ? FALSE : TRUE;
      }
      $SSYY  = ( array_key_exists( 'year',  $year )) ? $year['year']  : null;
      $month = ( array_key_exists( 'month', $year )) ? $year['month'] : null;
      $day   = ( array_key_exists( 'day',   $year )) ? $year['day']   : null;
      $week  = ( array_key_exists( 'week',  $year )) ? $year['week']  : null;
      $hour  = ( array_key_exists( 'hour',  $year )) ? $year['hour']  : 0; //null;
      $min   = ( array_key_exists( 'min',   $year )) ? $year['min']   : 0; //null;
      $sec   = ( array_key_exists( 'sec',   $year )) ? $year['sec']   : 0; //null;
      $year  = $SSYY;
    }
    elseif(is_string( $year ) && ( is_array( $month ) || empty( $month ))) {  // duration or date in a string
      $params = iCalUtilityFunctions::_setParams( $month );
      if( in_array( $year{0}, array( 'P', '+', '-' ))) { // duration
        $relatedStart = ( isset( $params['RELATED'] ) && ( 'END' == strtoupper( $params['RELATED'] ))) ? FALSE : TRUE;
        $before       = ( '-'  == $year[0] ) ? TRUE : FALSE;
        if(     'P'  != $year[0] )
          $year       = substr( $year, 1 );
        $date         = iCalUtilityFunctions::_durationStr2arr( $year);
      }
      else   // date
        $date    = iCalUtilityFunctions::_strdate2date( $year, 7 );
      unset( $year, $month, $day, $date['unparsedtext'] );
      if( empty( $date ))
        $sec = 0;
      else
        foreach( $date as $k => $v )
          $$k = $v;
    }
    else // single values in function input parameters
      $params = iCalUtilityFunctions::_setParams( $params );
    if( !empty( $year ) && !empty( $month ) && !empty( $day )) { // date
      $params['VALUE'] = 'DATE-TIME';
      $hour = ( $hour ) ? $hour : 0;
      $min  = ( $min  ) ? $min  : 0;
      $sec  = ( $sec  ) ? $sec  : 0;
      $this->trigger = array( 'params' => $params );
      $this->trigger['value'] = array( 'year'  => $year
                                     , 'month' => $month
                                     , 'day'   => $day
                                     , 'hour'  => $hour
                                     , 'min'   => $min
                                     , 'sec'   => $sec
                                     , 'tz'    => 'Z' );
      return TRUE;
    }
    elseif(( empty( $year ) && empty( $month )) &&    // duration
           (( !empty( $week ) || ( 0 == $week )) ||
            ( !empty( $day )  || ( 0 == $day  )) ||
            ( !empty( $hour ) || ( 0 == $hour )) ||
            ( !empty( $min )  || ( 0 == $min  )) ||
            ( !empty( $sec )  || ( 0 == $sec  )))) {
      unset( $params['RELATED'] ); // set at output creation (END only)
      unset( $params['VALUE'] );   // 'DURATION' default
      $this->trigger = array( 'params' => $params );
      $this->trigger['value']  = array();
      if( !empty( $week )) $this->trigger['value']['week'] = $week;
      if( !empty( $day  )) $this->trigger['value']['day']  = $day;
      if( !empty( $hour )) $this->trigger['value']['hour'] = $hour;
      if( !empty( $min  )) $this->trigger['value']['min']  = $min;
      if( !empty( $sec  )) $this->trigger['value']['sec']  = $sec;
      if( empty( $this->trigger['value'] )) {
        $this->trigger['value']['sec'] = 0;
        $before                        = FALSE;
      }
      else
        $this->trigger['value'] = iCalUtilityFunctions::_duration2arr( $this->trigger['value'] );
      $relatedStart = ( FALSE !== $relatedStart ) ? TRUE : FALSE;
      $before       = ( FALSE !== $before )       ? TRUE : FALSE;
      $this->trigger['value']['relatedStart'] = $relatedStart;
      $this->trigger['value']['before']       = $before;
      return TRUE;
    }
    return FALSE;
  }
/*********************************************************************************/
/**
 * Property Name: TZID
 */
/**
 * creates formatted output for calendar component property tzid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$tzid
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_strrep()
 * @uses calendarComponent::$format
 * @uses calendarComponent::$nl
 * @return string
 */
  function createTzid() {
    if( empty( $this->tzid )) return FALSE;
    if( empty( $this->tzid['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TZID' ) : FALSE;
    $attributes = $this->_createParams( $this->tzid['params'] );
    return $this->_createElement( 'TZID', $attributes, iCalUtilityFunctions::_strrep( $this->tzid['value'], $this->format, $this->nl ));
  }
/**
 * set calendar component property tzid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$tzid
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setTzid( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->tzid = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * .. .
 * Property Name: TZNAME
 */
/**
 * creates formatted output for calendar component property tzname
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @uses calendarComponent::$tzname
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @uses iCalUtilityFunctions::_strrep(
 * @uses calendarComponent::$format
 * @uses calendarComponent::$nl
 * @uses calendarComponent::getConfig()
 * @return string
 */
  function createTzname() {
    if( empty( $this->tzname )) return FALSE;
    $output = null;
    foreach( $this->tzname as $theName ) {
      if( !empty( $theName['value'] )) {
        $attributes = $this->_createParams( $theName['params'], array( 'LANGUAGE' ));
        $output    .= $this->_createElement( 'TZNAME', $attributes, iCalUtilityFunctions::_strrep( $theName['value'], $this->format, $this->nl ));
      }
      elseif( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( 'TZNAME' );
    }
    return $output;
  }
/**
 * set calendar component property tzname
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string   $value
 * @param array    $params
 * @param integer  $index
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::$tzname
 * @uses iCalUtilityFunctions::_setMval()
 * @return bool
 */
  function setTzname( $value, $params=FALSE, $index=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    iCalUtilityFunctions::_setMval( $this->tzname, $value, $params, FALSE, $index );
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: TZOFFSETFROM
 */
/**
 * creates formatted output for calendar component property tzoffsetfrom
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$tzoffsetfrom
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createTzoffsetfrom() {
    if( empty( $this->tzoffsetfrom )) return FALSE;
    if( empty( $this->tzoffsetfrom['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TZOFFSETFROM' ) : FALSE;
    $attributes = $this->_createParams( $this->tzoffsetfrom['params'] );
    return $this->_createElement( 'TZOFFSETFROM', $attributes, $this->tzoffsetfrom['value'] );
  }
/**
 * set calendar component property tzoffsetfrom
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$tzoffsetfrom
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setTzoffsetfrom( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->tzoffsetfrom = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: TZOFFSETTO
 */
/**
 * creates formatted output for calendar component property tzoffsetto
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$tzoffsetto
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createTzoffsetto() {
    if( empty( $this->tzoffsetto )) return FALSE;
    if( empty( $this->tzoffsetto['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TZOFFSETTO' ) : FALSE;
    $attributes = $this->_createParams( $this->tzoffsetto['params'] );
    return $this->_createElement( 'TZOFFSETTO', $attributes, $this->tzoffsetto['value'] );
  }
/**
 * set calendar component property tzoffsetto
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$tzoffsetto
 * @uses iCalUtilityFunctions::_setParams()
 * @return bool
 */
  function setTzoffsetto( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->tzoffsetto = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: TZURL
 */
/**
 * creates formatted output for calendar component property tzurl
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$tzurl
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createTzurl() {
    if( empty( $this->tzurl )) return FALSE;
    if( empty( $this->tzurl['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'TZURL' ) : FALSE;
    $attributes = $this->_createParams( $this->tzurl['params'] );
    return $this->_createElement( 'TZURL', $attributes, $this->tzurl['value'] );
  }
/**
 * set calendar component property tzurl
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::$tzurl
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$tzurl
 * @uses iCalUtilityFunctions::_setParams()
 * @return boll
 */
  function setTzurl( $value, $params=FALSE ) {
    if( empty( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $this->tzurl = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: UID
 */
/**
 * creates formatted output for calendar component property uid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.1 - 2014-02-21
 * @uses calendarComponent::$uid
 * @uses calendarComponent::_makeuid();
 * @uses calendarComponent::_createParams()
 * @uses calendarComponent::_createElement()
 * @return string
 */
  function createUid() {
    if( empty( $this->uid ))
      $this->_makeuid();
    $attributes = $this->_createParams( $this->uid['params'] );
    return $this->_createElement( 'UID', $attributes, $this->uid['value'] );
  }
/**
 * create an unique id for this calendar component object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.7 - 2007-09-04
 * @uses calendarComponent::$uid
 * @uses calendarComponent::getConfig()
 * @return void
 */
  function _makeUid() {
    $date   = date('Ymd\THisT');
    $unique = substr(microtime(), 2, 4);
    $base   = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
    $start  = 0;
    $end    = strlen( $base ) - 1;
    $length = 6;
    $str    = null;
    for( $p = 0; $p < $length; $p++ )
      $unique .= $base{mt_rand( $start, $end )};
    $this->uid = array( 'params' => null );
    $this->uid['value']  = $date.'-'.$unique.'@'.$this->getConfig( 'unique_id' );
  }
/**
 * set calendar component property uid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.1 - 2014-02-21
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::$uid
 * @uses calendarComponent::_setParams()
 * @return bool
 */
  function setUid( $value, $params=FALSE ) {
    if( empty( $value ) && ( '0' != $value )) return FALSE; // no allowEmpty check here !!!!
    $this->uid = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: URL
 */
/**
 * creates formatted output for calendar component property url
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @uses calendarComponent::$url
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @return string
 */
  function createUrl() {
    if( empty( $this->url )) return FALSE;
    if( empty( $this->url['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'URL' ) : FALSE;
    $attributes = $this->_createParams( $this->url['params'] );
    return $this->_createElement( 'URL', $attributes, $this->url['value'] );
  }
/**
 * set calendar component property url
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string  $value
 * @param array   $params
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$url
 * @uses calendarComponent::_setParams()
 * @return bool
 */
  function setUrl( $value, $params=FALSE ) {
    if( !empty( $value )) {
      if( !filter_var( $value, FILTER_VALIDATE_URL ) && ( 'urn' != strtolower( substr( $value, 0, 3 ))))
        return FALSE;
    }
    elseif( $this->getConfig( 'allowEmpty' ))
      $value = '';
    else
      return FALSE;
    $this->url = array( 'value' => $value, 'params' => iCalUtilityFunctions::_setParams( $params ));
    return TRUE;
  }
/*********************************************************************************/
/*********************************************************************************/
/**
 * create element format parts
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.0.6 - 2006-06-20
 * @uses iCalBase::_createFormat()
 * @uses calendarComponent::$timezonetype
 * @return string
 */
  function _createFormat() {
    parent::_createFormat();
    $objectname     = null;
    switch( $this->format ) {
      case 'xcal':
        return ( isset( $this->timezonetype )) ? strtolower( $this->timezonetype ) : strtolower( $this->objName );
        break;
      default:
        return ( isset( $this->timezonetype )) ? strtoupper( $this->timezonetype ) : strtoupper( $this->objName );
        break;
    }
  }
/**
 * creates formatted output for calendar component property data value type recur
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.25 - 2013-06-30
 * @param array $recurlabel
 * @param array $recurdata
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::_createElement()
 * @uses calendarComponent::_createParams()
 * @uses iCalUtilityFunctions::_date2strdate()
 * @uses iCalUtilityFunctions::_recurBydaySort()
 * @return string
 */
  function _format_recur( $recurlabel, $recurdata ) {
    $output = null;
    foreach( $recurdata as $therule ) {
      if( empty( $therule['value'] )) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( $recurlabel );
        continue;
      }
      $attributes = ( isset( $therule['params'] )) ? $this->_createParams( $therule['params'] ) : null;
      $content1  = $content2  = null;
      foreach( $therule['value'] as $rulelabel => $rulevalue ) {
        switch( strtoupper( $rulelabel )) {
          case 'FREQ': {
            $content1 .= "FREQ=$rulevalue";
            break;
          }
          case 'UNTIL': {
            $parno     = ( isset( $rulevalue['hour'] )) ? 7 : 3;
            $content2 .= ';UNTIL='.iCalUtilityFunctions::_date2strdate( $rulevalue, $parno );
            break;
          }
          case 'COUNT':
          case 'INTERVAL':
          case 'WKST': {
            $content2 .= ";$rulelabel=$rulevalue";
            break;
          }
          case 'BYSECOND':
          case 'BYMINUTE':
          case 'BYHOUR':
          case 'BYMONTHDAY':
          case 'BYYEARDAY':
          case 'BYWEEKNO':
          case 'BYMONTH':
          case 'BYSETPOS': {
            $content2 .= ";$rulelabel=";
            if( is_array( $rulevalue )) {
              foreach( $rulevalue as $vix => $valuePart ) {
                $content2 .= ( $vix ) ? ',' : null;
                $content2 .= $valuePart;
              }
            }
            else
             $content2 .= $rulevalue;
            break;
          }
          case 'BYDAY': {
            $byday          = array( '' );
            $bx             = 0;
            foreach( $rulevalue as $bix => $bydayPart ) {
              if( ! empty( $byday[$bx] ) && ! ctype_digit( substr( $byday[$bx], -1 ))) // new day
                $byday[++$bx] = '';
              if( ! is_array( $bydayPart ))   // day without order number
                $byday[$bx] .= (string) $bydayPart;
              else {                          // day with order number
                foreach( $bydayPart as $bix2 => $bydayPart2 )
                  $byday[$bx] .= (string) $bydayPart2;
              }
            } // end foreach( $rulevalue as $bix => $bydayPart )
            if( 1 < count( $byday ))
              usort( $byday, array( 'iCalUtilityFunctions', '_recurBydaySort' ));
            $content2      .= ';BYDAY='.implode( ',', $byday );
            break;
          }
          default: {
            $content2 .= ";$rulelabel=$rulevalue";
            break;
          }
        }
      }
      $output .= $this->_createElement( $recurlabel, $attributes, $content1.$content2 );
    }
    return $output;
  }
/**
 * check if property not exists within component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-15
 * @param string $propName
 * @uses calendarComponent::$lastmodified
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::$recurrenceid
 * @uses calendarComponent::$relatedto
 * @uses calendarComponent::$requeststatus
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::{$propname}
 * @return bool
 */
  function _notExistProp( $propName ) {
    if( empty( $propName )) return FALSE; // when deleting x-prop, an empty propName may be used=allowed
    $propName = strtolower( $propName );
    if(     'last-modified'    == $propName )  { if( !isset( $this->lastmodified ))    return TRUE; }
    elseif( 'percent-complete' == $propName )  { if( !isset( $this->percentcomplete )) return TRUE; }
    elseif( 'recurrence-id'    == $propName )  { if( !isset( $this->recurrenceid ))    return TRUE; }
    elseif( 'related-to'       == $propName )  { if( !isset( $this->relatedto ))       return TRUE; }
    elseif( 'request-status'   == $propName )  { if( !isset( $this->requeststatus ))   return TRUE; }
    elseif((       'x-' != substr($propName,0,2)) && !isset( $this->$propName ))       return TRUE;
    return FALSE;
  }
/*********************************************************************************/
/*********************************************************************************/
/**
 * get general component config variables or info about subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param mixed $config
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$allowEmpty
 * @uses calendarComponent::$compix
 * @uses calendarComponent::$components
 * @uses calendarComponent::$objName
 * @uses calendarComponent::getProperty()
 * @uses calendarComponent::$format
 * @uses calendarComponent::$language
 * @uses calendarComponent::$nl
 * @uses iCalUtilityFunctions::$miscComps
 * @uses calendarComponent::$uid
 * @uses calendarComponent::_makeuid()
 * @uses calendarComponent::dtstamp
 * @uses calendarComponent::_makeDtstamp()
 * @uses calendarComponent::$summary
 * @uses calendarComponent::$description
 * @uses calendarComponent::$dtstart
 * @uses calendarComponent::$dtend
 * @uses calendarComponent::$due
 * @uses calendarComponent::$duration
 * @uses calendarComponent::$rrule
 * @uses calendarComponent::$rdate
 * @uses calendarComponent::$exdate
 * @uses calendarComponent::$exrule
 * @uses calendarComponent::$action
 * @uses calendarComponent::$attach
 * @uses calendarComponent::$attendee
 * @uses calendarComponent::$categories
 * @uses calendarComponent::$class
 * @uses calendarComponent::$comment
 * @uses calendarComponent::$completed
 * @uses calendarComponent::$contact
 * @uses calendarComponent::$created
 * @uses calendarComponent::$freebusy
 * @uses calendarComponent::$geo
 * @uses calendarComponent::$lastmodified
 * @uses calendarComponent::$location
 * @uses calendarComponent::$organizer
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::$priority
 * @uses calendarComponent::$recurrenceid
 * @uses calendarComponent::$relatedto
 * @uses calendarComponent::$repeat
 * @uses calendarComponent::$requeststatus
 * @uses calendarComponent::$resources
 * @uses calendarComponent::$sequence
 * @uses calendarComponent::$sequence
 * @uses calendarComponent::$status
 * @uses calendarComponent::$transp
 * @uses calendarComponent::$trigger
 * @uses calendarComponent::$tzid
 * @uses calendarComponent::$tzname
 * @uses calendarComponent::$tzoffsetfrom
 * @uses calendarComponent::$tzoffsetto
 * @uses calendarComponent::$tzurl
 * @uses calendarComponent::$url
 * @uses calendarComponent::$xprop
 * @uses calendarComponent::$dtzid
 * @uses calendarComponent::$unique_id
 * @return value
 */
  function getConfig( $config = FALSE) {
    if( !$config ) {
      $return = array();
      $return['ALLOWEMPTY']  = $this->getConfig( 'ALLOWEMPTY' );
      $return['FORMAT']      = $this->getConfig( 'FORMAT' );
      if( FALSE !== ( $lang  = $this->getConfig( 'LANGUAGE' )))
        $return['LANGUAGE']  = $lang;
      $return['NEWLINECHAR'] = $this->getConfig( 'NEWLINECHAR' );
      $return['TZTD']        = $this->getConfig( 'TZID' );
      $return['UNIQUE_ID']   = $this->getConfig( 'UNIQUE_ID' );
      return $return;
    }
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        return $this->allowEmpty;
        break;
      case 'COMPSINFO':
        unset( $this->compix );
        $info = array();
        if( isset( $this->components )) {
          foreach( $this->components as $cix => $component ) {
            if( empty( $component )) continue;
            $info[$cix]['ordno'] = $cix + 1;
            $info[$cix]['type']  = $component->objName;
            $info[$cix]['uid']   = $component->getProperty( 'uid' );
            $info[$cix]['props'] = $component->getConfig( 'propinfo' );
            $info[$cix]['sub']   = $component->getConfig( 'compsinfo' );
          }
        }
        return $info;
        break;
      case 'FORMAT':
        return $this->format;
        break;
      case 'LANGUAGE':
         // get language for calendar component as defined in [RFC 1766]
        return $this->language;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        return $this->nl;
        break;
      case 'PROPINFO':
        $output = array();
        if( ! in_array( $this->objName, iCalUtilityFunctions::$miscComps )) {
          if( empty( $this->uid ))            $this->_makeuid();
                                              $output['UID']              = 1;
          if( empty( $this->dtstamp ))        $this->_makeDtstamp();
                                              $output['DTSTAMP']          = 1;
        }
        if( !empty( $this->summary ))         $output['SUMMARY']          = 1;
        if( !empty( $this->description ))     $output['DESCRIPTION']      = count( $this->description );
        if( !empty( $this->dtstart ))         $output['DTSTART']          = 1;
        if( !empty( $this->dtend ))           $output['DTEND']            = 1;
        if( !empty( $this->due ))             $output['DUE']              = 1;
        if( !empty( $this->duration ))        $output['DURATION']         = 1;
        if( !empty( $this->rrule ))           $output['RRULE']            = count( $this->rrule );
        if( !empty( $this->rdate ))           $output['RDATE']            = count( $this->rdate );
        if( !empty( $this->exdate ))          $output['EXDATE']           = count( $this->exdate );
        if( !empty( $this->exrule ))          $output['EXRULE']           = count( $this->exrule );
        if( !empty( $this->action ))          $output['ACTION']           = 1;
        if( !empty( $this->attach ))          $output['ATTACH']           = count( $this->attach );
        if( !empty( $this->attendee ))        $output['ATTENDEE']         = count( $this->attendee );
        if( !empty( $this->categories ))      $output['CATEGORIES']       = count( $this->categories );
        if( !empty( $this->class ))           $output['CLASS']            = 1;
        if( !empty( $this->comment ))         $output['COMMENT']          = count( $this->comment );
        if( !empty( $this->completed ))       $output['COMPLETED']        = 1;
        if( !empty( $this->contact ))         $output['CONTACT']          = count( $this->contact );
        if( !empty( $this->created ))         $output['CREATED']          = 1;
        if( !empty( $this->freebusy ))        $output['FREEBUSY']         = count( $this->freebusy );
        if( !empty( $this->geo ))             $output['GEO']              = 1;
        if( !empty( $this->lastmodified ))    $output['LAST-MODIFIED']    = 1;
        if( !empty( $this->location ))        $output['LOCATION']         = 1;
        if( !empty( $this->organizer ))       $output['ORGANIZER']        = 1;
        if( !empty( $this->percentcomplete )) $output['PERCENT-COMPLETE'] = 1;
        if( !empty( $this->priority ))        $output['PRIORITY']         = 1;
        if( !empty( $this->recurrenceid ))    $output['RECURRENCE-ID']    = 1;
        if( !empty( $this->relatedto ))       $output['RELATED-TO']       = count( $this->relatedto );
        if( !empty( $this->repeat ))          $output['REPEAT']           = 1;
        if( !empty( $this->requeststatus ))   $output['REQUEST-STATUS']   = count( $this->requeststatus );
        if( !empty( $this->resources ))       $output['RESOURCES']        = count( $this->resources );
        if( !empty( $this->sequence ))        $output['SEQUENCE']         = 1;
        if( !empty( $this->sequence ))        $output['SEQUENCE']         = 1;
        if( !empty( $this->status ))          $output['STATUS']           = 1;
        if( !empty( $this->transp ))          $output['TRANSP']           = 1;
        if( !empty( $this->trigger ))         $output['TRIGGER']          = 1;
        if( !empty( $this->tzid ))            $output['TZID']             = 1;
        if( !empty( $this->tzname ))          $output['TZNAME']           = count( $this->tzname );
        if( !empty( $this->tzoffsetfrom ))    $output['TZOFFSETFROM']     = 1;
        if( !empty( $this->tzoffsetto ))      $output['TZOFFSETTO']       = 1;
        if( !empty( $this->tzurl ))           $output['TZURL']            = 1;
        if( !empty( $this->url ))             $output['URL']              = 1;
        if( !empty( $this->xprop ))           $output['X-PROP']           = count( $this->xprop );
        return $output;
        break;
      case 'SETPROPERTYNAMES':
        return array_keys( $this->getConfig( 'propinfo' ));
        break;
      case 'TZID':
        return $this->dtzid;
        break;
      case 'UNIQUE_ID':
        if( empty( $this->unique_id ))
          $this->unique_id  = ( isset( $_SERVER['SERVER_NAME'] )) ? gethostbyname( $_SERVER['SERVER_NAME'] ) : 'localhost';
        return $this->unique_id;
        break;
    }
  }
/**
 * general component config setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.18 - 2013-09-06
 * @param mixed   $config
 * @param string  $value
 * @param bool    $softUpdate
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$allowEmpty
 * @uses calendarComponent::$format
 * @uses calendarComponent::$language
 * @uses calendarComponent::$nl
 * @uses calendarComponent::$dtzid
 * @uses calendarComponent::$unique_id
 * @uses calendarComponent::$components
 * @uses calendarComponent::copy()
 * @return void
 */
  function setConfig( $config, $value = FALSE, $softUpdate = FALSE ) {
    if( is_array( $config )) {
      $config  = array_change_key_case( $config, CASE_UPPER );
      if( isset( $config['NEWLINECHAR'] ) || isset( $config['NL'] )) {
        $k = ( isset( $config['NEWLINECHAR'] )) ? 'NEWLINECHAR' : 'NL';
        if( FALSE === $this->setConfig( 'NL', $config[$k] ))
          return FALSE;
        unset( $config[$k] );
      }
      foreach( $config as $cKey => $cValue ) {
        if( FALSE === $this->setConfig( $cKey, $cValue, $softUpdate ))
          return FALSE;
      }
      return TRUE;
    }
    else
      $config = strtoupper( $config );
    $res = FALSE;
    switch( $config ) {
      case 'ALLOWEMPTY':
        $this->allowEmpty = $value;
        $subcfg = array( 'ALLOWEMPTY' => $value );
        $res    = TRUE;
        break;
      case 'FORMAT':
        $value  = trim( strtolower( $value ));
        $this->format = $value;
        $this->_createFormat();
        $subcfg = array( 'FORMAT' => $value );
        $res    = TRUE;
        break;
      case 'LANGUAGE':
         // set language for calendar component as defined in [RFC 1766]
        $value  = trim( $value );
        if( empty( $this->language ) || !$softUpdate )
          $this->language = $value;
        $subcfg = array( 'LANGUAGE' => $value );
        $res    = TRUE;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        $this->nl = $value;
        $this->_createFormat();
        $subcfg = array( 'NL' => $value );
        $res    = TRUE;
        break;
      case 'TZID':
        $this->dtzid = $value;
        $subcfg = array( 'TZID' => $value );
        $res    = TRUE;
        break;
      case 'UNIQUE_ID':
        $value  = trim( $value );
        $this->unique_id = $value;
        $subcfg = array( 'UNIQUE_ID' => $value );
        $res    = TRUE;
        break;
      default:  // any unvalid config key.. .
        return TRUE;
    }
    if( !$res ) return FALSE;
    if( isset( $subcfg ) && !empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgvalue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $component->setConfig( $cfgkey, $cfgvalue, $softUpdate );
          if( !$res )
            break 2;
          $this->components[$cix] = $component->copy(); // PHP4 compliant
        }
      }
    }
    return $res;
  }
/*********************************************************************************/
/**
 * delete component property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed  $propName  bool FALSE => X-property
 * @param int    $propix    specific property in case of multiply occurences
 * @uses calendarComponent::_notExistProp()
 * @uses iCalUtilityFunctions::$mProps2
 * @uses calendarComponent::$propdelix
 * @uses calendarComponent::$action
 * @uses calendarComponent::deletePropertyM()
 * @uses calendarComponent::$attendee
 * @uses calendarComponent::$categories
 * @uses calendarComponent::$class
 * @uses calendarComponent::$comment
 * @uses calendarComponent::$completed
 * @uses calendarComponent::$contact
 * @uses calendarComponent::$created
 * @uses calendarComponent::$description
 * @uses calendarComponent::$dtend
 * @uses iCalUtilityFunctions::$miscComps
 * @uses calendarComponent::$objName
 * @uses calendarComponent::$dtstamp
 * @uses calendarComponent::$dtstart
 * @uses calendarComponent::$due
 * @uses calendarComponent::$duration
 * @uses calendarComponent::$exdate
 * @uses calendarComponent::$exrule
 * @uses calendarComponent::$freebusy
 * @uses calendarComponent::$geo
 * @uses calendarComponent::$lastmodified
 * @uses calendarComponent::$location
 * @uses calendarComponent::$organizer
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::$priority
 * @uses calendarComponent::$rdate
 * @uses calendarComponent::$recurrenceid
 * @uses calendarComponent::$relatedto
 * @uses calendarComponent::$repeat
 * @uses calendarComponent::$requeststatus
 * @uses calendarComponent::$resources
 * @uses calendarComponent::$rrule
 * @uses calendarComponent::$sequence
 * @uses calendarComponent::$status
 * @uses calendarComponent::$summary
 * @uses calendarComponent::$transp
 * @uses calendarComponent::$trigger
 * @uses calendarComponent::$tzid
 * @uses calendarComponent::$tzname
 * @uses calendarComponent::$tzoffsetfrom
 * @uses calendarComponent::$tzoffsetto
 * @uses calendarComponent::$tzurl
 * @uses calendarComponent::$uid
 * @uses calendarComponent::$url
 * @uses calendarComponent::$xprop
 * @return bool, if successfull delete TRUE
 */
  function deleteProperty( $propName=FALSE, $propix=FALSE ) {
    if( $this->_notExistProp( $propName )) return FALSE;
    $propName = strtoupper( $propName );
    if( in_array( $propName, iCalUtilityFunctions::$mProps2 )) {
      if( !$propix )
        $propix = ( isset( $this->propdelix[$propName] ) && ( 'X-PROP' != $propName )) ? $this->propdelix[$propName] + 2 : 1;
      $this->propdelix[$propName] = --$propix;
    }
    $return = FALSE;
    switch( $propName ) {
      case 'ACTION':
        if( !empty( $this->action )) {
          $this->action = '';
          $return = TRUE;
        }
        break;
      case 'ATTACH':
        return $this->deletePropertyM( $this->attach, $this->propdelix[$propName] );
        break;
      case 'ATTENDEE':
        return $this->deletePropertyM( $this->attendee, $this->propdelix[$propName] );
        break;
      case 'CATEGORIES':
        return $this->deletePropertyM( $this->categories, $this->propdelix[$propName] );
        break;
      case 'CLASS':
        if( !empty( $this->class )) {
          $this->class = '';
          $return = TRUE;
        }
        break;
      case 'COMMENT':
        return $this->deletePropertyM( $this->comment, $this->propdelix[$propName] );
        break;
      case 'COMPLETED':
        if( !empty( $this->completed )) {
          $this->completed = '';
          $return = TRUE;
        }
        break;
      case 'CONTACT':
        return $this->deletePropertyM( $this->contact, $this->propdelix[$propName] );
        break;
      case 'CREATED':
        if( !empty( $this->created )) {
          $this->created = '';
          $return = TRUE;
        }
        break;
      case 'DESCRIPTION':
        return $this->deletePropertyM( $this->description, $this->propdelix[$propName] );
        break;
      case 'DTEND':
        if( !empty( $this->dtend )) {
          $this->dtend = '';
          $return = TRUE;
        }
        break;
      case 'DTSTAMP':
        if( in_array( $this->objName, iCalUtilityFunctions::$miscComps ))
          return FALSE;
        if( !empty( $this->dtstamp )) {
          $this->dtstamp = '';
          $return = TRUE;
        }
        break;
      case 'DTSTART':
        if( !empty( $this->dtstart )) {
          $this->dtstart = '';
          $return = TRUE;
        }
        break;
      case 'DUE':
        if( !empty( $this->due )) {
          $this->due = '';
          $return = TRUE;
        }
        break;
      case 'DURATION':
        if( !empty( $this->duration )) {
          $this->duration = '';
          $return = TRUE;
        }
        break;
      case 'EXDATE':
        return $this->deletePropertyM( $this->exdate, $this->propdelix[$propName] );
        break;
      case 'EXRULE':
        return $this->deletePropertyM( $this->exrule, $this->propdelix[$propName] );
        break;
      case 'FREEBUSY':
        return $this->deletePropertyM( $this->freebusy, $this->propdelix[$propName] );
        break;
      case 'GEO':
        if( !empty( $this->geo )) {
          $this->geo = '';
          $return = TRUE;
        }
        break;
      case 'LAST-MODIFIED':
        if( !empty( $this->lastmodified )) {
          $this->lastmodified = '';
          $return = TRUE;
        }
        break;
      case 'LOCATION':
        if( !empty( $this->location )) {
          $this->location = '';
          $return = TRUE;
        }
        break;
      case 'ORGANIZER':
        if( !empty( $this->organizer )) {
          $this->organizer = '';
          $return = TRUE;
        }
        break;
      case 'PERCENT-COMPLETE':
        if( !empty( $this->percentcomplete )) {
          $this->percentcomplete = '';
          $return = TRUE;
        }
        break;
      case 'PRIORITY':
        if( !empty( $this->priority )) {
          $this->priority = '';
          $return = TRUE;
        }
        break;
      case 'RDATE':
        return $this->deletePropertyM( $this->rdate, $this->propdelix[$propName] );
        break;
      case 'RECURRENCE-ID':
        if( !empty( $this->recurrenceid )) {
          $this->recurrenceid = '';
          $return = TRUE;
        }
        break;
      case 'RELATED-TO':
        return $this->deletePropertyM( $this->relatedto, $this->propdelix[$propName] );
        break;
      case 'REPEAT':
        if( !empty( $this->repeat )) {
          $this->repeat = '';
          $return = TRUE;
        }
        break;
      case 'REQUEST-STATUS':
        return $this->deletePropertyM( $this->requeststatus, $this->propdelix[$propName] );
        break;
      case 'RESOURCES':
        return $this->deletePropertyM( $this->resources, $this->propdelix[$propName] );
        break;
      case 'RRULE':
        return $this->deletePropertyM( $this->rrule, $this->propdelix[$propName] );
        break;
      case 'SEQUENCE':
        if( !empty( $this->sequence )) {
          $this->sequence = '';
          $return = TRUE;
        }
        break;
      case 'STATUS':
        if( !empty( $this->status )) {
          $this->status = '';
          $return = TRUE;
        }
        break;
      case 'SUMMARY':
        if( !empty( $this->summary )) {
          $this->summary = '';
          $return = TRUE;
        }
        break;
      case 'TRANSP':
        if( !empty( $this->transp )) {
          $this->transp = '';
          $return = TRUE;
        }
        break;
      case 'TRIGGER':
        if( !empty( $this->trigger )) {
          $this->trigger = '';
          $return = TRUE;
        }
        break;
      case 'TZID':
        if( !empty( $this->tzid )) {
          $this->tzid = '';
          $return = TRUE;
        }
        break;
      case 'TZNAME':
        return $this->deletePropertyM( $this->tzname, $this->propdelix[$propName] );
        break;
      case 'TZOFFSETFROM':
        if( !empty( $this->tzoffsetfrom )) {
          $this->tzoffsetfrom = '';
          $return = TRUE;
        }
        break;
      case 'TZOFFSETTO':
        if( !empty( $this->tzoffsetto )) {
          $this->tzoffsetto = '';
          $return = TRUE;
        }
        break;
      case 'TZURL':
        if( !empty( $this->tzurl )) {
          $this->tzurl = '';
          $return = TRUE;
        }
        break;
      case 'UID':
        if( in_array( $this->objName, iCalUtilityFunctions::$miscComps ))
          return FALSE;
        if( ! empty( $this->uid )) {
          $this->uid = '';
          $return = TRUE;
        }
        break;
      case 'URL':
        if( !empty( $this->url )) {
          $this->url = '';
          $return = TRUE;
        }
        break;
      default:
        $reduced = '';
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) return FALSE;
          foreach( $this->xprop as $k => $a ) {
            if(( $k != $propName ) && !empty( $a ))
              $reduced[$k] = $a;
          }
        }
        else {
          if( count( $this->xprop ) <= $propix ) { unset( $this->propdelix[$propName] ); return FALSE; }
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix != $xpropno )
              $reduced[$xpropkey] = $xpropvalue;
            $xpropno++;
          }
        }
        $this->xprop = $reduced;
        if( empty( $this->xprop )) {
          unset( $this->propdelix[$propName] );
          return FALSE;
        }
        return TRUE;
    }
    return $return;
  }
/*********************************************************************************/
/**
 * delete component property value, fixing components with multiple occurencies
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param array  $multiprop  component (multi-)property
 * @param int    $propix     removal counter
 * @return bool TRUE
 */
  function deletePropertyM( & $multiprop, & $propix ) {
    if( isset( $multiprop[$propix] ))
      unset( $multiprop[$propix] );
    if( empty( $multiprop )) {
      $multiprop = '';
      unset( $propix );
      return FALSE;
    }
    else
      return TRUE;
  }
/**
 * get component property value/params
 *
 * if property has multiply values, consequtive function calls are needed
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.13 - 2015-03-29
 * @param string  $propName
 * @param int     $propix   specific property in case of multiply occurences
 * @param bool    $inclParam
 * @param bool    $specform
 * @uses calendarComponent::getProperty()
 * @uses iCalUtilityFunctions::_geo2str2()
 * @uses iCalUtilityFunctions::$geoLatFmt
 * @uses iCalUtilityFunctions::$geoLongFmt
 * @uses calendarComponent::_notExistProp()
 * @uses iCalUtilityFunctions::$mProps2
 * @uses calendarComponent::$propix
 * @uses calendarComponent::$action
 * @uses calendarComponent::$attendee
 * @uses calendarComponent::$categories
 * @uses calendarComponent::$class
 * @uses calendarComponent::$comment
 * @uses calendarComponent::$completed
 * @uses calendarComponent::$contact
 * @uses calendarComponent::$created
 * @uses calendarComponent::$description
 * @uses calendarComponent::$dtend
 * @uses iCalUtilityFunctions::$miscComps
 * @uses calendarComponent::$dtstamp
 * @uses calendarComponent::_makeDtstamp()
 * @uses calendarComponent::$dtstart
 * @uses calendarComponent::$due
 * @uses calendarComponent::$duration
 * @uses iCalUtilityFunctions::_duration2date()
 * @uses calendarComponent::$exdate
 * @uses calendarComponent::$exrule
 * @uses calendarComponent::$freebusy
 * @uses calendarComponent::$geo
 * @uses calendarComponent::$lastmodified
 * @uses calendarComponent::$location
 * @uses calendarComponent::$organizer
 * @uses calendarComponent::$percentcomplete
 * @uses calendarComponent::$priority
 * @uses calendarComponent::$rdate
 * @uses calendarComponent::$recurrenceid
 * @uses calendarComponent::$relatedto
 * @uses calendarComponent::$repeat
 * @uses calendarComponent::$requeststatus
 * @uses calendarComponent::$resources
 * @uses calendarComponent::$rrule
 * @uses calendarComponent::$sequence
 * @uses calendarComponent::$status
 * @uses calendarComponent::$summary
 * @uses calendarComponent::$transp
 * @uses calendarComponent::$trigger
 * @uses calendarComponent::$tzid
 * @uses calendarComponent::$tzname
 * @uses calendarComponent::$tzoffsetfrom
 * @uses calendarComponent::$tzoffsetto
 * @uses calendarComponent::$tzurl
 * @uses calendarComponent::$uid
 * @uses calendarComponent::$objName
 * @uses calendarComponent::_makeuid()
 * @uses calendarComponent::$url
 * @uses calendarComponent::$xprop
 * @return mixed
 */
  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE, $specform=FALSE ) {
    if( 'GEOLOCATION' == strtoupper( $propName )) {
      $content = ( FALSE === ( $loc = $this->getProperty( 'LOCATION' ))) ? '' : $loc.' ';
      if( FALSE === ( $geo = $this->getProperty( 'GEO' )))
        return FALSE;
      return $content.
             iCalUtilityFunctions::_geo2str2( $geo['latitude'],  iCalUtilityFunctions::$geoLatFmt ).
             iCalUtilityFunctions::_geo2str2( $geo['longitude'], iCalUtilityFunctions::$geoLongFmt ).'/';
    }
    if( $this->_notExistProp( $propName )) return FALSE;
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( in_array( $propName, iCalUtilityFunctions::$mProps2 )) {
      if( empty( $propix ))
        $propix = ( isset( $this->propix[$propName] )) ? $this->propix[$propName] + 2 : 1;
      $this->propix[$propName] = --$propix;
    }
    switch( $propName ) {
      case 'ACTION':
        if( isset( $this->action['value'] )) return ( $inclParam ) ? $this->action : $this->action['value'];
        break;
      case 'ATTACH':
        $ak = ( is_array( $this->attach )) ? array_keys( $this->attach ) : array();
        while( is_array( $this->attach ) && !isset( $this->attach[$propix] ) && ( 0 < count( $this->attach )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->attach[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->attach[$propix] : $this->attach[$propix]['value'];
        break;
      case 'ATTENDEE':
        $ak = ( is_array( $this->attendee )) ? array_keys( $this->attendee ) : array();
        while( is_array( $this->attendee ) && !isset( $this->attendee[$propix] ) && ( 0 < count( $this->attendee )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->attendee[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->attendee[$propix] : $this->attendee[$propix]['value'];
        break;
      case 'CATEGORIES':
        $ak = ( is_array( $this->categories )) ? array_keys( $this->categories ) : array();
        while( is_array( $this->categories ) && !isset( $this->categories[$propix] ) && ( 0 < count( $this->categories )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->categories[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->categories[$propix] : $this->categories[$propix]['value'];
        break;
      case 'CLASS':
        if( isset( $this->class['value'] )) return ( $inclParam ) ? $this->class : $this->class['value'];
        break;
      case 'COMMENT':
        $ak = ( is_array( $this->comment )) ? array_keys( $this->comment ) : array();
        while( is_array( $this->comment ) && !isset( $this->comment[$propix] ) && ( 0 < count( $this->comment )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->comment[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->comment[$propix] : $this->comment[$propix]['value'];
        break;
      case 'COMPLETED':
        if( isset( $this->completed['value'] )) return ( $inclParam ) ? $this->completed : $this->completed['value'];
        break;
      case 'CONTACT':
        $ak = ( is_array( $this->contact )) ? array_keys( $this->contact ) : array();
        while( is_array( $this->contact ) && !isset( $this->contact[$propix] ) && ( 0 < count( $this->contact )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->contact[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->contact[$propix] : $this->contact[$propix]['value'];
        break;
      case 'CREATED':
        if( isset( $this->created['value'] )) return ( $inclParam ) ? $this->created : $this->created['value'];
        break;
      case 'DESCRIPTION':
        $ak = ( is_array( $this->description )) ? array_keys( $this->description ) : array();
        while( is_array( $this->description ) && !isset( $this->description[$propix] ) && ( 0 < count( $this->description )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->description[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->description[$propix] : $this->description[$propix]['value'];
        break;
      case 'DTEND':
        if( isset( $this->dtend['value'] )) return ( $inclParam ) ? $this->dtend : $this->dtend['value'];
        break;
      case 'DTSTAMP':
        if( in_array( $this->objName, iCalUtilityFunctions::$miscComps ))
          return;
        if( !isset( $this->dtstamp['value'] ))
          $this->_makeDtstamp();
        return ( $inclParam ) ? $this->dtstamp : $this->dtstamp['value'];
        break;
      case 'DTSTART':
        if( isset( $this->dtstart['value'] )) return ( $inclParam ) ? $this->dtstart : $this->dtstart['value'];
        break;
      case 'DUE':
        if( isset( $this->due['value'] )) return ( $inclParam ) ? $this->due : $this->due['value'];
        break;
      case 'DURATION':
        if( ! isset( $this->duration['value'] )) return FALSE;
        $value  = ( $specform && isset( $this->dtstart['value'] ) && isset( $this->duration['value'] )) ? iCalUtilityFunctions::_duration2date( $this->dtstart['value'], $this->duration['value'] ) : $this->duration['value'];
        $params = ( $specform && $inclParam && isset( $this->dtstart['params']['TZID'] )) ? array_merge((array) $this->duration['params'], $this->dtstart['params'] ) : $this->duration['params'];
        return ( $inclParam ) ? array( 'value' => $value, 'params' =>  $params ) : $value;
        break;
      case 'EXDATE':
        $ak = ( is_array( $this->exdate )) ? array_keys( $this->exdate ) : array();
        while( is_array( $this->exdate ) && !isset( $this->exdate[$propix] ) && ( 0 < count( $this->exdate )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->exdate[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->exdate[$propix] : $this->exdate[$propix]['value'];
        break;
      case 'EXRULE':
        $ak = ( is_array( $this->exrule )) ? array_keys( $this->exrule ) : array();
        while( is_array( $this->exrule ) && !isset( $this->exrule[$propix] ) && ( 0 < count( $this->exrule )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->exrule[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->exrule[$propix] : $this->exrule[$propix]['value'];
        break;
      case 'FREEBUSY':
        $ak = ( is_array( $this->freebusy )) ? array_keys( $this->freebusy ) : array();
        while( is_array( $this->freebusy ) && !isset( $this->freebusy[$propix] ) && ( 0 < count( $this->freebusy )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->freebusy[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->freebusy[$propix] : $this->freebusy[$propix]['value'];
        break;
      case 'GEO':
        if( isset( $this->geo['value'] )) return ( $inclParam ) ? $this->geo : $this->geo['value'];
        break;
      case 'LAST-MODIFIED':
        if( isset( $this->lastmodified['value'] )) return ( $inclParam ) ? $this->lastmodified : $this->lastmodified['value'];
        break;
      case 'LOCATION':
        if( isset( $this->location['value'] )) return ( $inclParam ) ? $this->location : $this->location['value'];
        break;
      case 'ORGANIZER':
        if( isset( $this->organizer['value'] )) return ( $inclParam ) ? $this->organizer : $this->organizer['value'];
        break;
      case 'PERCENT-COMPLETE':
        if( isset( $this->percentcomplete['value'] )) return ( $inclParam ) ? $this->percentcomplete : $this->percentcomplete['value'];
        break;
      case 'PRIORITY':
        if( isset( $this->priority['value'] )) return ( $inclParam ) ? $this->priority : $this->priority['value'];
        break;
      case 'RDATE':
        $ak = ( is_array( $this->rdate )) ? array_keys( $this->rdate ) : array();
        while( is_array( $this->rdate ) && !isset( $this->rdate[$propix] ) && ( 0 < count( $this->rdate )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->rdate[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->rdate[$propix] : $this->rdate[$propix]['value'];
        break;
      case 'RECURRENCE-ID':
        if( isset( $this->recurrenceid['value'] )) return ( $inclParam ) ? $this->recurrenceid : $this->recurrenceid['value'];
        break;
      case 'RELATED-TO':
        $ak = ( is_array( $this->relatedto )) ? array_keys( $this->relatedto ) : array();
        while( is_array( $this->relatedto ) && !isset( $this->relatedto[$propix] ) && ( 0 < count( $this->relatedto )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->relatedto[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->relatedto[$propix] : $this->relatedto[$propix]['value'];
        break;
      case 'REPEAT':
        if( isset( $this->repeat['value'] )) return ( $inclParam ) ? $this->repeat : $this->repeat['value'];
        break;
      case 'REQUEST-STATUS':
        $ak = ( is_array( $this->requeststatus )) ? array_keys( $this->requeststatus ) : array();
        while( is_array( $this->requeststatus ) && !isset( $this->requeststatus[$propix] ) && ( 0 < count( $this->requeststatus )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->requeststatus[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->requeststatus[$propix] : $this->requeststatus[$propix]['value'];
        break;
      case 'RESOURCES':
        $ak = ( is_array( $this->resources )) ? array_keys( $this->resources ) : array();
        while( is_array( $this->resources ) && !isset( $this->resources[$propix] ) && ( 0 < count( $this->resources )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->resources[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->resources[$propix] : $this->resources[$propix]['value'];
        break;
      case 'RRULE':
        $ak = ( is_array( $this->rrule )) ? array_keys( $this->rrule ) : array();
        while( is_array( $this->rrule ) && !isset( $this->rrule[$propix] ) && ( 0 < count( $this->rrule )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->rrule[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->rrule[$propix] : $this->rrule[$propix]['value'];
        break;
      case 'SEQUENCE':
        if( isset( $this->sequence['value'] )) return ( $inclParam ) ? $this->sequence : $this->sequence['value'];
        break;
      case 'STATUS':
        if( isset( $this->status['value'] )) return ( $inclParam ) ? $this->status : $this->status['value'];
        break;
      case 'SUMMARY':
        if( isset( $this->summary['value'] )) return ( $inclParam ) ? $this->summary : $this->summary['value'];
        break;
      case 'TRANSP':
        if( isset( $this->transp['value'] )) return ( $inclParam ) ? $this->transp : $this->transp['value'];
        break;
      case 'TRIGGER':
        if( isset( $this->trigger['value'] )) return ( $inclParam ) ? $this->trigger : $this->trigger['value'];
        break;
      case 'TZID':
        if( isset( $this->tzid['value'] )) return ( $inclParam ) ? $this->tzid : $this->tzid['value'];
        break;
      case 'TZNAME':
        $ak = ( is_array( $this->tzname )) ? array_keys( $this->tzname ) : array();
        while( is_array( $this->tzname ) && !isset( $this->tzname[$propix] ) && ( 0 < count( $this->tzname )) && ( $propix < end( $ak )))
          $propix++;
        $this->propix[$propName] = $propix;
        if( !isset( $this->tzname[$propix] )) { unset( $this->propix[$propName] ); return FALSE; }
        return ( $inclParam ) ? $this->tzname[$propix] : $this->tzname[$propix]['value'];
        break;
      case 'TZOFFSETFROM':
        if( isset( $this->tzoffsetfrom['value'] )) return ( $inclParam ) ? $this->tzoffsetfrom : $this->tzoffsetfrom['value'];
        break;
      case 'TZOFFSETTO':
        if( isset( $this->tzoffsetto['value'] )) return ( $inclParam ) ? $this->tzoffsetto : $this->tzoffsetto['value'];
        break;
      case 'TZURL':
        if( isset( $this->tzurl['value'] )) return ( $inclParam ) ? $this->tzurl : $this->tzurl['value'];
        break;
      case 'UID':
        if( in_array( $this->objName, iCalUtilityFunctions::$miscComps ))
          return FALSE;
        if( empty( $this->uid ))
          $this->_makeuid();
        return ( $inclParam ) ? $this->uid : $this->uid['value'];
        break;
      case 'URL':
        if( isset( $this->url['value'] )) return ( $inclParam ) ? $this->url : $this->url['value'];
        break;
      default:
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) return FALSE;
          return ( $inclParam ) ? array( $propName, $this->xprop[$propName] )
                                : array( $propName, $this->xprop[$propName]['value'] );
        }
        else {
          if( empty( $this->xprop )) return FALSE;
          $xpropno = 0;
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix == $xpropno )
              return ( $inclParam ) ? array( $xpropkey, $this->xprop[$xpropkey] )
                                    : array( $xpropkey, $this->xprop[$xpropkey]['value'] );
            else
              $xpropno++;
          }
          return FALSE; // not found ??
        }
    }
    return FALSE;
  }
/**
 * returns calendar property unique values for 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO' or 'RESOURCES' and for each, number of occurrence
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param string  $propName
 * @param array   $output    incremented result array
 * @uses iCalUtilityFunctions::$mProps1
 * @uses calendarComponent::getProperty()
 * return void
 */
  function _getProperties( $propName, & $output ) {
    if( empty( $output ))
      $output = array();
    if( !in_array( strtoupper( $propName ), iCalUtilityFunctions::$mProps1 ))
      return $output;
    while( FALSE !== ( $content = $this->getProperty( $propName ))) {
      if( empty( $content ))
        continue;
      if( is_array( $content )) {
        foreach( $content as $part ) {
          if( FALSE !== strpos( $part, ',' )) {
            $part = explode( ',', $part );
            foreach( $part as $thePart ) {
              $thePart = trim( $thePart );
              if( !empty( $thePart )) {
                if( !isset( $output[$thePart] ))
                  $output[$thePart] = 1;
                else
                  $output[$thePart] += 1;
              }
            }
          }
          else {
            $part = trim( $part );
            if( !isset( $output[$part] ))
              $output[$part] = 1;
            else
              $output[$part] += 1;
          }
        }
      } // end if( is_array( $content ))
      elseif( FALSE !== strpos( $content, ',' )) {
        $content = explode( ',', $content );
        foreach( $content as $thePart ) {
          $thePart = trim( $thePart );
          if( !empty( $thePart )) {
            if( !isset( $output[$thePart] ))
              $output[$thePart] = 1;
            else
              $output[$thePart] += 1;
          }
        }
      } // end elseif( FALSE !== strpos( $content, ',' ))
      else {
        $content = trim( $content );
        if( !empty( $content )) {
          if( !isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        }
      }
    }
    ksort( $output );
  }
/**
 * general component property setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-11-05
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @uses calendarComponent::getProperty()
 * @uses calendarComponent::_notExistProp()
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::setAction()
 * @uses calendarComponent::setAttendee()
 * @uses calendarComponent::setCategories()
 * @uses calendarComponent::setClass()
 * @uses calendarComponent::setComment()
 * @uses calendarComponent::setCompleted()
 * @uses calendarComponent::setContact()
 * @uses calendarComponent::setCreated()
 * @uses calendarComponent::setDescription()
 * @uses calendarComponent::setDtend()
 * @uses calendarComponent::setDtstamp()
 * @uses calendarComponent::setDtstart()
 * @uses calendarComponent::setDue()
 * @uses calendarComponent::setDuration()
 * @uses calendarComponent::setExdate()
 * @uses calendarComponent::setExrule()
 * @uses calendarComponent::setFreebusy()
 * @uses calendarComponent::setGeo()
 * @uses calendarComponent::setLastmodified()
 * @uses calendarComponent::setLocation()
 * @uses calendarComponent::setOrganizer()
 * @uses calendarComponent::setPercentcomplete()
 * @uses calendarComponent::setPriority()
 * @uses calendarComponent::setRdate()
 * @uses calendarComponent::setRecurrenceid()
 * @uses calendarComponent::setRelatedto()
 * @uses calendarComponent::setRepeat()
 * @uses calendarComponent::setRequeststatus()
 * @uses calendarComponent::setResources()
 * @uses calendarComponent::setRrule()
 * @uses calendarComponent::setSequence()
 * @uses calendarComponent::setStatus()
 * @uses calendarComponent::setSummary()
 * @uses calendarComponent::setTransp()
 * @uses calendarComponent::setTrigger()
 * @uses calendarComponent::setTzid()
 * @uses calendarComponent::setTzname()
 * @uses calendarComponent::setTzoffsetfrom()
 * @uses calendarComponent::setTzoffsetto()
 * @uses calendarComponent::setTzurl()
 * @uses calendarComponent::setUid()
 * @uses calendarComponent::$objName
 * @uses calendarComponent::setUrl()
 * @uses calendarComponent::setXprop()
 * @return void
 */
  function setProperty() {
    $numargs    = func_num_args();
    if( 1 > $numargs ) return FALSE;
    $arglist    = func_get_args();
    if( $this->_notExistProp( $arglist[0] )) return FALSE;
    if( !$this->getConfig( 'allowEmpty' ) && ( !isset( $arglist[1] ) || empty( $arglist[1] )))
      return FALSE;
    $arglist[0] = strtoupper( $arglist[0] );
    for( $argix=$numargs; $argix < 12; $argix++ ) {
      if( !isset( $arglist[$argix] ))
        $arglist[$argix] = null;
    }
    switch( $arglist[0] ) {
      case 'ACTION':
        return $this->setAction(          $arglist[1], $arglist[2] );
      case 'ATTACH':
        return $this->setAttach(          $arglist[1], $arglist[2], $arglist[3] );
      case 'ATTENDEE':
        return $this->setAttendee(        $arglist[1], $arglist[2], $arglist[3] );
      case 'CATEGORIES':
        return $this->setCategories(      $arglist[1], $arglist[2], $arglist[3] );
      case 'CLASS':
        return $this->setClass(           $arglist[1], $arglist[2] );
      case 'COMMENT':
        return $this->setComment(         $arglist[1], $arglist[2], $arglist[3] );
      case 'COMPLETED':
        return $this->setCompleted(       $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7] );
      case 'CONTACT':
        return $this->setContact(         $arglist[1], $arglist[2], $arglist[3] );
      case 'CREATED':
        return $this->setCreated(         $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7] );
      case 'DESCRIPTION':
        return $this->setDescription(     $arglist[1], $arglist[2], $arglist[3] );
      case 'DTEND':
        return $this->setDtend(           $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8] );
      case 'DTSTAMP':
        return $this->setDtstamp(         $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7] );
      case 'DTSTART':
        return $this->setDtstart(         $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8] );
      case 'DUE':
        return $this->setDue(             $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8] );
      case 'DURATION':
        return $this->setDuration(        $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6] );
      case 'EXDATE':
        return $this->setExdate(          $arglist[1], $arglist[2], $arglist[3] );
      case 'EXRULE':
        return $this->setExrule(          $arglist[1], $arglist[2], $arglist[3] );
      case 'FREEBUSY':
        return $this->setFreebusy(        $arglist[1], $arglist[2], $arglist[3], $arglist[4] );
      case 'GEO':
        return $this->setGeo(             $arglist[1], $arglist[2], $arglist[3] );
      case 'LAST-MODIFIED':
        return $this->setLastModified(    $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7] );
      case 'LOCATION':
        return $this->setLocation(        $arglist[1], $arglist[2] );
      case 'ORGANIZER':
        return $this->setOrganizer(       $arglist[1], $arglist[2] );
      case 'PERCENT-COMPLETE':
        return $this->setPercentComplete( $arglist[1], $arglist[2] );
      case 'PRIORITY':
        return $this->setPriority(        $arglist[1], $arglist[2] );
      case 'RDATE':
        return $this->setRdate(           $arglist[1], $arglist[2], $arglist[3] );
      case 'RECURRENCE-ID':
       return $this->setRecurrenceid(     $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8] );
      case 'RELATED-TO':
        return $this->setRelatedTo(       $arglist[1], $arglist[2], $arglist[3] );
      case 'REPEAT':
        return $this->setRepeat(          $arglist[1], $arglist[2] );
      case 'REQUEST-STATUS':
        return $this->setRequestStatus(   $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5] );
      case 'RESOURCES':
        return $this->setResources(       $arglist[1], $arglist[2], $arglist[3] );
      case 'RRULE':
        return $this->setRrule(           $arglist[1], $arglist[2], $arglist[3] );
      case 'SEQUENCE':
        return $this->setSequence(        $arglist[1], $arglist[2] );
      case 'STATUS':
        return $this->setStatus(          $arglist[1], $arglist[2] );
      case 'SUMMARY':
        return $this->setSummary(         $arglist[1], $arglist[2] );
      case 'TRANSP':
        return $this->setTransp(          $arglist[1], $arglist[2] );
      case 'TRIGGER':
        return $this->setTrigger(         $arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8], $arglist[9], $arglist[10], $arglist[11] );
      case 'TZID':
        return $this->setTzid(            $arglist[1], $arglist[2] );
      case 'TZNAME':
        return $this->setTzname(          $arglist[1], $arglist[2], $arglist[3] );
      case 'TZOFFSETFROM':
        return $this->setTzoffsetfrom(    $arglist[1], $arglist[2] );
      case 'TZOFFSETTO':
        return $this->setTzoffsetto(      $arglist[1], $arglist[2] );
      case 'TZURL':
        return $this->setTzurl(           $arglist[1], $arglist[2] );
      case 'UID':
        return $this->setUid(             $arglist[1], $arglist[2] );
      case 'URL':
        return $this->setUrl(             $arglist[1], $arglist[2] );
      default:
        return $this->setXprop(           $arglist[0], $arglist[1], $arglist[2] );
    }
    return FALSE;
  }
/*********************************************************************************/
/**
 * parse component unparsed data into properties
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.20.3 - 2015-03-05
 * @param mixed $unparsedtext   strict rfc2445 formatted, single property string or array of strings
 * @uses calendarComponent::getConfig()
 * @uses iCalUtilityFunctions::convEolChar()
 * @uses calendarComponent::$unparsed
 * @uses calendarComponent::$components
 * @uses calendarComponent::copy()
 * @uses iCalUtilityFunctions::_splitContent()
 * @uses calendarComponent::setProperty()
 * @uses iCalUtilityFunctions::_strunrep()
 * @uses calendarComponent::parse()
 * @return bool FALSE if error occurs during parsing
 */
  function parse( $unparsedtext=null ) {
    $nl = $this->getConfig( 'nl' );
    if( !empty( $unparsedtext )) {
      if( is_array( $unparsedtext ))
        $unparsedtext = implode( '\n'.$nl, $unparsedtext );
      $unparsedtext = iCalUtilityFunctions::convEolChar( $unparsedtext, $nl );
    }
    elseif( !isset( $this->unparsed ))
      $unparsedtext = array();
    else
      $unparsedtext = $this->unparsed;
            /* skip leading (empty/invalid) lines */
    foreach( $unparsedtext as $lix => $line ) {
      if( FALSE !== ( $pos = stripos( $line, 'BEGIN:' ))) {
        $unparsedtext[$lix] = substr( $unparsedtext[$lix], $pos );
        break;
      }
      $tst = trim( $line );
      if(( '\n' == $tst ) || empty( $tst ))
        unset( $unparsedtext[$lix] );
    }
    $this->unparsed = array();
    $comp           = & $this;
    $config         = $this->getConfig();
    $compsync = $subsync = 0;
    foreach ( $unparsedtext as $lix => $line ) {
      if( 'END:VALARM'         == strtoupper( substr( $line, 0, 10 ))) {
        if( 1 != $subsync ) return FALSE;
        $this->components[]     = $comp->copy();
        $subsync--;
      }
      elseif( 'END:DAYLIGHT'   == strtoupper( substr( $line, 0, 12 ))) {
        if( 1 != $subsync ) return FALSE;
        $this->components[]     = $comp->copy();
        $subsync--;
      }
      elseif( 'END:STANDARD'   == strtoupper( substr( $line, 0, 12 ))) {
        if( 1 != $subsync ) return FALSE;
        array_unshift( $this->components, $comp->copy());
        $subsync--;
      }
      elseif( 'END:'           == strtoupper( substr( $line, 0, 4 ))) { // end:<component>
        if( 1 != $compsync ) return FALSE;
        if( 0 < $subsync )
          $this->components[]   = $comp->copy();
        $compsync--;
        break;                       /* skip trailing empty lines */
      }
      elseif( 'BEGIN:VALARM'   == strtoupper( substr( $line, 0, 12 ))) {
        $comp = new valarm( $config);
        $subsync++;
      }
      elseif( 'BEGIN:STANDARD' == strtoupper( substr( $line, 0, 14 ))) {
        $comp = new vtimezone( 'standard', $config );
        $subsync++;
      }
      elseif( 'BEGIN:DAYLIGHT' == strtoupper( substr( $line, 0, 14 ))) {
        $comp = new vtimezone( 'daylight', $config );
        $subsync++;
      }
      elseif( 'BEGIN:'         == strtoupper( substr( $line, 0, 6 )))  // begin:<component>
        $compsync++;
      else
        $comp->unparsed[]       = $line;
    }
    if( 0 < $subsync )
      $this->components[]   = $comp->copy();
    unset( $config );
            /* concatenate property values spread over several lines */
    $lastix    = -1;
    $propnames = array( 'action', 'attach', 'attendee', 'categories', 'comment', 'completed'
                      , 'contact', 'class', 'created', 'description', 'dtend', 'dtstart'
                      , 'dtstamp', 'due', 'duration', 'exdate', 'exrule', 'freebusy', 'geo'
                      , 'last-modified', 'location', 'organizer', 'percent-complete'
                      , 'priority', 'rdate', 'recurrence-id', 'related-to', 'repeat'
                      , 'request-status', 'resources', 'rrule', 'sequence', 'status'
                      , 'summary', 'transp', 'trigger', 'tzid', 'tzname', 'tzoffsetfrom'
                      , 'tzoffsetto', 'tzurl', 'uid', 'url', 'x-' );
    $proprows  = array();
    for( $i = 0; $i < count( $this->unparsed ); $i++ ) { // concatenate lines
      $line = rtrim( $this->unparsed[$i], $nl );
      while( isset( $this->unparsed[$i+1] ) && !empty( $this->unparsed[$i+1] ) && ( ' ' == $this->unparsed[$i+1]{0} ))
        $line .= rtrim( substr( $this->unparsed[++$i], 1 ), $nl );
      $proprows[] = $line;
    }
            /* parse each property 'line' */
    foreach( $proprows as $line ) {
      if( '\n' == substr( $line, -2 ))
        $line = substr( $line, 0, -2 );
            /* get propname */
      $propname = null;
      $cix = 0;
      while( isset( $line[$cix] )) {
        if( in_array( $line[$cix], array( ':', ';' )))
          break;
        else
          $propname .= $line[$cix];
        $cix++;
      }
      if(( 'x-' == substr( $propname, 0, 2 )) || ( 'X-' == substr( $propname, 0, 2 ))) {
        $propname2 = $propname;
        $propname  = 'X-';
      }
      if( !in_array( strtolower( $propname ), $propnames )) // skip non standard property names
        continue;
            /* rest of the line is opt.params and value */
      $line = substr( $line, $cix );
            /* separate attributes from value */
      iCalUtilityFunctions::_splitContent( $line, $propAttr );
            /* call setProperty( $propname.. . */
      switch( strtoupper( $propname )) {
        case 'ATTENDEE':
          foreach( $propAttr as $pix => $attr ) {
            if( !in_array( strtoupper( $pix ), array( 'MEMBER', 'DELEGATED-TO', 'DELEGATED-FROM' )))
              continue;
            $attr2 = explode( ',', $attr );
              if( 1 < count( $attr2 ))
                $propAttr[$pix] = $attr2;
          }
          $this->setProperty( $propname, $line, $propAttr );
          break;
        case 'CATEGORIES':
        case 'RESOURCES':
          if( FALSE !== strpos( $line, ',' )) {
            $content  = array( 0 => '' );
            $cix = $lix = 0;
            while( FALSE !== substr( $line, $lix, 1 )) {
              if(( ',' == $line[$lix] ) && ( "\\" != $line[( $lix - 1 )])) {
                $cix++;
                $content[$cix] = '';
              }
              else
                $content[$cix] .= $line[$lix];
              $lix++;
            }
            if( 1 < count( $content )) {
              $content = array_values( $content );
              foreach( $content as $cix => $contentPart )
                $content[$cix] = iCalUtilityFunctions::_strunrep( $contentPart );
              $this->setProperty( $propname, $content, $propAttr );
              break;
            }
            else
              $line = reset( $content );
          }
        case 'COMMENT':
        case 'CONTACT':
        case 'DESCRIPTION':
        case 'LOCATION':
        case 'SUMMARY':
          if( empty( $line ))
            $propAttr = null;
          $this->setProperty( $propname, iCalUtilityFunctions::_strunrep( $line ), $propAttr );
          break;
        case 'REQUEST-STATUS':
          $values    = explode( ';', $line, 3 );
          $values[1] = ( !isset( $values[1] )) ? null : iCalUtilityFunctions::_strunrep( $values[1] );
          $values[2] = ( !isset( $values[2] )) ? null : iCalUtilityFunctions::_strunrep( $values[2] );
          $this->setProperty( $propname
                            , $values[0]  // statcode
                            , $values[1]  // statdesc
                            , $values[2]  // extdata
                            , $propAttr );
          break;
        case 'FREEBUSY':
          $fbtype = ( isset( $propAttr['FBTYPE'] )) ? $propAttr['FBTYPE'] : ''; // force setting default, if missing
          unset( $propAttr['FBTYPE'] );
          $values = explode( ',', $line );
          foreach( $values as $vix => $value ) {
            $value2 = explode( '/', $value );
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propname, $fbtype, $values, $propAttr );
          break;
        case 'GEO':
          $value = explode( ';', $line, 2 );
          if( 2 > count( $value ))
            $value[1] = null;
          $this->setProperty( $propname, $value[0], $value[1], $propAttr );
          break;
        case 'EXDATE':
          $values = ( !empty( $line )) ? explode( ',', $line ) : null;
          $this->setProperty( $propname, $values, $propAttr );
          break;
        case 'RDATE':
          if( empty( $line )) {
            $this->setProperty( $propname, $line, $propAttr );
            break;
          }
          $values = explode( ',', $line );
          foreach( $values as $vix => $value ) {
            $value2 = explode( '/', $value );
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propname, $values, $propAttr );
          break;
        case 'EXRULE':
        case 'RRULE':
          $values = explode( ';', $line );
          $recur = array();
          foreach( $values as $value2 ) {
            if( empty( $value2 ))
              continue; // ;-char in ending position ???
            $value3 = explode( '=', $value2, 2 );
            $rulelabel = strtoupper( $value3[0] );
            switch( $rulelabel ) {
              case 'BYDAY': {
                $value4 = explode( ',', $value3[1] );
                if( 1 < count( $value4 )) {
                  foreach( $value4 as $v5ix => $value5 ) {
                    $value6 = array();
                    $dayno = $dayname = null;
                    $value5 = trim( (string) $value5 );
                    if(( ctype_alpha( substr( $value5, -1 ))) &&
                       ( ctype_alpha( substr( $value5, -2, 1 )))) {
                      $dayname = substr( $value5, -2, 2 );
                      if( 2 < strlen( $value5 ))
                        $dayno = substr( $value5, 0, ( strlen( $value5 ) - 2 ));
                    }
                    if( $dayno )
                      $value6[] = $dayno;
                    if( $dayname )
                      $value6['DAY'] = $dayname;
                    $value4[$v5ix] = $value6;
                  }
                }
                else {
                  $value4 = array();
                  $dayno  = $dayname = null;
                  $value5 = trim( (string) $value3[1] );
                  if(( ctype_alpha( substr( $value5, -1 ))) &&
                     ( ctype_alpha( substr( $value5, -2, 1 )))) {
                      $dayname = substr( $value5, -2, 2 );
                    if( 2 < strlen( $value5 ))
                      $dayno = substr( $value5, 0, ( strlen( $value5 ) - 2 ));
                  }
                  if( $dayno )
                    $value4[] = $dayno;
                  if( $dayname )
                    $value4['DAY'] = $dayname;
                }
                $recur[$rulelabel] = $value4;
                break;
              }
              default: {
                $value4 = explode( ',', $value3[1] );
                if( 1 < count( $value4 ))
                  $value3[1] = $value4;
                $recur[$rulelabel] = $value3[1];
                break;
              }
            } // end - switch $rulelabel
          } // end - foreach( $values.. .
          $this->setProperty( $propname, $recur, $propAttr );
          break;
        case 'X-':
          $propname = ( isset( $propname2 )) ? $propname2 : $propname;
          unset( $propname2 );
        case 'ACTION':
        case 'CLASSIFICATION':
        case 'STATUS':
        case 'TRANSP':
        case 'UID':
        case 'TZID':
        case 'RELATED-TO':
        case 'TZNAME':
          $line = iCalUtilityFunctions::_strunrep( $line );
        default:
          $this->setProperty( $propname, $line, $propAttr );
          break;
      } // end  switch( $propname.. .
    } // end - foreach( $proprows.. .
    unset( $unparsedtext, $this->unparsed, $proprows );
    if( isset( $this->components ) && is_array( $this->components ) && ( 0 < count( $this->components ))) {
      $ckeys = array_keys( $this->components );
      foreach( $ckeys as $ckey ) {
        if( !empty( $this->components[$ckey] ) && !empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    return TRUE;
  }
/*********************************************************************************/
/*********************************************************************************/
/**
 * return a copy of this component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.4 - 2012-10-18
 * @return object
 */
  function copy() {
    return unserialize( serialize( $this ));
 }
/*********************************************************************************/
/*********************************************************************************/
/**
 * delete calendar subcomponent from component container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed  $arg1 ordno / component type / component uid
 * @param mixed  $arg2 ordno if arg1 = component type
 * @uses calendarComponent::$components
 * @uses calendarComponent::$objName
 * @uses calendarComponent::getProperty()
 * @return void
 */
  function deleteComponent( $arg1, $arg2=FALSE  ) {
    if( !isset( $this->components )) return FALSE;
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) {
      $argType = 'INDEX';
      $index   = (int) $arg1 - 1;
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) {
      $argType = strtolower( $arg1 );
      $index   = ( !empty( $arg2 ) && ctype_digit( (string) $arg2 )) ? (( int ) $arg2 - 1 ) : 0;
    }
    $cix2dC = 0;
    foreach ( $this->components as $cix => $component) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) {
        unset( $this->components[$cix] );
        return TRUE;
      }
      elseif( $argType == $component->objName ) {
        if( $index == $cix2dC ) {
          unset( $this->components[$cix] );
          return TRUE;
        }
        $cix2dC++;
      }
      elseif( !$argType && ($arg1 == $component->getProperty( 'uid' ))) {
        unset( $this->components[$cix] );
        return TRUE;
      }
    }
    return FALSE;
  }
/**
 * get calendar component subcomponent from component container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $arg1  ordno/component type/ component uid
 * @param mixed $arg2  ordno if arg1 = component type
 * @uses calendarComponent::$components
 * @uses calendarComponent::$compix
 * @uses calendarComponent::$objName
 * @uses calendarComponent::copy()
 * @uses calendarComponent::getProperty()
 * @return object
 */
  function getComponent ( $arg1=FALSE, $arg2=FALSE ) {
    if( !isset( $this->components )) return FALSE;
    $index = $argType = null;
    if ( !$arg1 ) {
      $argType = 'INDEX';
      $index   = $this->compix['INDEX'] =
        ( isset( $this->compix['INDEX'] )) ? $this->compix['INDEX'] + 1 : 1;
    }
    elseif ( ctype_digit( (string) $arg1 )) {
      $argType = 'INDEX';
      $index   = (int) $arg1;
      unset( $this->compix );
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) {
      unset( $this->compix['INDEX'] );
      $argType = strtolower( $arg1 );
      if( !$arg2 )
        $index = $this->compix[$argType] = ( isset( $this->compix[$argType] )) ? $this->compix[$argType] + 1 : 1;
      else
        $index = (int) $arg2;
    }
    $index  -= 1;
    $ckeys = array_keys( $this->components );
    if( !empty( $index) && ( $index > end( $ckeys )))
      return FALSE;
    $cix2gC = 0;
    foreach( $this->components as $cix => $component ) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix ))
        return $component->copy();
      elseif( $argType == $component->objName ) {
         if( $index == $cix2gC )
           return $component->copy();
         $cix2gC++;
      }
      elseif( !$argType && ( $arg1 == $component->getProperty( 'uid' )))
        return $component->copy();
    }
            /* not found.. . */
    unset( $this->compix );
    return false;
  }
/**
 * add calendar component as subcomponent to container for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 * @uses calendarComponent::setComponent( $component )
 * @return void
 */
  function addSubComponent ( $component ) {
    $this->setComponent( $component );
  }
/**
 * create new calendar component subcomponent, already included within component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.33 - 2011-01-03
 * @param string $compType  subcomponent type
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$components
 * @uses calendarComponent::calendarComponent()
 * @return object (reference)
 */
  function & newComponent( $compType ) {
    $config = $this->getConfig();
    $keys   = array_keys( $this->components );
    $ix     = end( $keys) + 1;
    switch( strtoupper( $compType )) {
      case 'ALARM':
      case 'VALARM':
        $this->components[$ix] = new valarm( $config );
        break;
      case 'STANDARD':
        array_unshift( $this->components, new vtimezone( 'STANDARD', $config ));
        $ix = 0;
        break;
      case 'DAYLIGHT':
        $this->components[$ix] = new vtimezone( 'DAYLIGHT', $config );
        break;
      default:
        return FALSE;
    }
    return $this->components[$ix];
  }
/**
 * add calendar component as subcomponent to container for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param object  $component calendar component
 * @param mixed   $arg1      ordno/component type/ component uid
 * @param mixed   $arg2      ordno if arg1 = component type
 * @uses calendarComponent::$components
 * @uses calendarComponent::setConfig()
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::$objName
 * @uses iCalUtilityFunctions::$miscComps
 * @uses calendarComponent::getProperty()
 * @uses calendarComponent::copy()
 * @uses iCalUtilityFunctions::$mComps
 * @return bool
 */
  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ) {
    if( !isset( $this->components )) return FALSE;
    $component->setConfig( $this->getConfig(), FALSE, TRUE );
    if( ! in_array( $component->objName, iCalUtilityFunctions::$miscComps )) {
            /* make sure dtstamp and uid is set */
      $dummy = $component->getProperty( 'dtstamp' );
      $dummy = $component->getProperty( 'uid' );
    }
    if( !$arg1 ) { // plain insert, last in chain
      $this->components[] = $component->copy();
      return TRUE;
    }
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) { // index insert/replace
      $argType = 'INDEX';
      $index   = (int) $arg1 - 1;
    }
    elseif( in_array( strtolower( $arg1 ), iCalUtilityFunctions::$mComps )) {
      $argType = strtolower( $arg1 );
      $index = ( ctype_digit( (string) $arg2 )) ? ((int) $arg2) - 1 : 0;
    }
    // else if arg1 is set, arg1 must be an UID
    $cix2sC = 0;
    foreach ( $this->components as $cix => $component2 ) {
      if( empty( $component2 )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) { // index insert/replace
        $this->components[$cix] = $component->copy();
        return TRUE;
      }
      elseif( $argType == $component2->objName ) { // component Type index insert/replace
        if( $index == $cix2sC ) {
          $this->components[$cix] = $component->copy();
          return TRUE;
        }
        $cix2sC++;
      }
      elseif( !$argType && ( $arg1 == $component2->getProperty( 'uid' ))) { // UID insert/replace
        $this->components[$cix] = $component->copy();
        return TRUE;
      }
    }
            /* arg1=index and not found.. . insert at index .. .*/
    if( 'INDEX' == $argType ) {
      $this->components[$index] = $component->copy();
      ksort( $this->components, SORT_NUMERIC );
    }
    else    /* not found.. . insert last in chain anyway .. .*/
    $this->components[] = $component->copy();
    return TRUE;
  }
/**
 * creates formatted output for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array $xcaldecl
 * @uses calendarComponent::$objName
 * @uses calendarComponent::$components
 * @uses calendarComponent::getProperty()
 * @uses iCalUtilityFunctions::$fmt
 * @uses calendarComponent::copy()
 * @uses calendarComponent::setConfig()
 * @uses calendarComponent::getConfig()
 * @uses calendarComponent::createComponent()
 * @uses calendarComponent::$xcaldecl()
 * @return string
 */
  function createSubComponent() {
    $output = null;
    if( 'vtimezone' == $this->objName ) { // sort subComponents, first standard, then daylight, in dtstart order
      $stdarr = $dlarr = array();
      foreach( $this->components as $component ) {
        if( empty( $component ))
          continue;
        $dt  = $component->getProperty( 'dtstart' );
        $key = (int) sprintf( iCalUtilityFunctions::$fmt['dateKey'], (int) $dt['year'], (int) $dt['month'], (int) $dt['day'], (int) $dt['hour'], (int) $dt['min'], (int) $dt['sec'] );
        if( 'standard' == $component->objName ) {
          while( isset( $stdarr[$key] ))
            $key += 1;
          $stdarr[$key] = $component->copy();
        }
        elseif( 'daylight' == $component->objName ) {
          while( isset( $dlarr[$key] ))
            $key += 1;
          $dlarr[$key] = $component->copy();
        }
      } // end foreach( $this->components as $component )
      $this->components = array();
      ksort( $stdarr, SORT_NUMERIC );
      foreach( $stdarr as $std )
        $this->components[] = $std->copy();
      unset( $stdarr );
      ksort( $dlarr,  SORT_NUMERIC );
      foreach( $dlarr as $dl )
        $this->components[] = $dl->copy();
      unset( $dlarr );
    } // end if( 'vtimezone' == $this->objName )
    foreach( $this->components as $component ) {
      $component->setConfig( $this->getConfig(), FALSE, TRUE );
      $output .= $component->createComponent( $this->xcaldecl );
    }
    return $output;
  }
}
