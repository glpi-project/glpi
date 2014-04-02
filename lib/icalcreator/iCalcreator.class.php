<?php
/*********************************************************************************/
/**
 * iCalcreator v2.18
 * copyright (c) 2007-2013 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * kigkonsult.se/iCalcreator/index.php
 * ical@kigkonsult.se
 *
 * Description:
 * This file is a PHP implementation of rfc2445/rfc5545.
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
/*********************************************************************************/
/*********************************************************************************/
/*         A little setup                                                        */
/*********************************************************************************/
            /* your local language code */
// define( 'ICAL_LANG', 'sv' );
            // alt. autosetting
/*
$langstr     = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$pos         = strpos( $langstr, ';' );
if ($pos   !== false) {
  $langstr   = substr( $langstr, 0, $pos );
  $pos       = strpos( $langstr, ',' );
  if ($pos !== false) {
    $pos     = strpos( $langstr, ',' );
    $langstr = substr( $langstr, 0, $pos );
  }
  define( 'ICAL_LANG', $langstr );
}
*/
/*********************************************************************************/
/*         version, do NOT remove!!                                              */
define( 'ICALCREATOR_VERSION', 'iCalcreator 2.18' );
/*********************************************************************************/
/*********************************************************************************/
/**
 * vcalendar class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 */
class vcalendar {
            //  calendar property variables
  var $calscale;
  var $method;
  var $prodid;
  var $version;
  var $xprop;
            //  container for calendar components
  var $components;
            //  component config variables
  var $allowEmpty;
  var $unique_id;
  var $language;
  var $directory;
  var $filename;
  var $url;
  var $delimiter;
  var $nl;
  var $format;
  var $dtzid;
            //  component internal variables
  var $attributeDelimiter;
  var $valueInit;
            //  component xCal declaration container
  var $xcaldecl;
/**
 * constructor for calendar object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 * @param array $config
 * @return void
 */
  function vcalendar ( $config = array()) {
    $this->_makeVersion();
    $this->calscale   = null;
    $this->method     = null;
    $this->_makeUnique_id();
    $this->prodid     = null;
    $this->xprop      = array();
    $this->language   = null;
    $this->directory  = null;
    $this->filename   = null;
    $this->url        = null;
    $this->dtzid      = null;
/**
 *   language = <Text identifying a language, as defined in [RFC 1766]>
 */
    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

    $this->xcaldecl   = array();
    $this->components = array();
  }
/*********************************************************************************/
/**
 * Property Name: CALSCALE
 */
/**
 * creates formatted output for calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createCalscale() {
    if( empty( $this->calscale )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' calscale="'.$this->calscale.'"';
        break;
      default:
        return 'CALSCALE:'.$this->calscale.$this->nl;
        break;
    }
  }
/**
 * set calendar property calscale
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-21
 * @param string $value
 * @return void
 */
  function setCalscale( $value ) {
    if( empty( $value )) return FALSE;
    $this->calscale = $value;
  }
/*********************************************************************************/
/**
 * Property Name: METHOD
 */
/**
 * creates formatted output for calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createMethod() {
    if( empty( $this->method )) return FALSE;
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' method="'.$this->method.'"';
        break;
      default:
        return 'METHOD:'.$this->method.$this->nl;
        break;
    }
  }
/**
 * set calendar property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-20-23
 * @param string $value
 * @return bool
 */
  function setMethod( $value ) {
    if( empty( $value )) return FALSE;
    $this->method = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: PRODID
 *
 *  The identifier is RECOMMENDED to be the identical syntax to the
 * [RFC 822] addr-spec. A good method to assure uniqueness is to put the
 * domain name or a domain literal IP address of the host on which.. .
 */
/**
 * creates formatted output for calendar property prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.11 - 2012-05-13
 * @return string
 */
  function createProdid() {
    if( !isset( $this->prodid ))
      $this->_makeProdid();
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' prodid="'.$this->prodid.'"';
        break;
      default:
        $toolbox = new calendarComponent();
        $toolbox->setConfig( $this->getConfig());
        return $toolbox->_createElement( 'PRODID', '', $this->prodid );
        break;
    }
  }
/**
 * make default value for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.8 - 2009-12-30
 * @return void
 */
  function _makeProdid() {
    $this->prodid  = '-//'.$this->unique_id.'//NONSGML kigkonsult.se '.ICALCREATOR_VERSION.'//'.strtoupper( $this->language );
  }
/**
 * Conformance: The property MUST be specified once in an iCalendar object.
 * Description: The vendor of the implementation SHOULD assure that this
 * is a globally unique identifier; using some technique such as an FPI
 * value, as defined in [ISO 9070].
 */
/**
 * make default unique_id for calendar prodid
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeUnique_id() {
    $this->unique_id  = ( isset( $_SERVER['SERVER_NAME'] )) ? gethostbyname( $_SERVER['SERVER_NAME'] ) : 'localhost';
  }
/*********************************************************************************/
/**
 * Property Name: VERSION
 *
 * Description: A value of "2.0" corresponds to this memo.
 */
/**
 * creates formatted output for calendar property version

 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createVersion() {
    if( empty( $this->version ))
      $this->_makeVersion();
    switch( $this->format ) {
      case 'xcal':
        return $this->nl.' version="'.$this->version.'"';
        break;
      default:
        return 'VERSION:'.$this->version.$this->nl;
        break;
    }
  }
/**
 * set default calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 0.3.0 - 2006-08-10
 * @return void
 */
  function _makeVersion() {
    $this->version = '2.0';
  }
/**
 * set calendar version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.8 - 2008-10-23
 * @param string $value
 * @return void
 */
  function setVersion( $value ) {
    if( empty( $value )) return FALSE;
    $this->version = $value;
    return TRUE;
  }
/*********************************************************************************/
/**
 * Property Name: x-prop
 */
/**
 * creates formatted output for calendar property x-prop, iCal format only
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-05-25
 * @return string
 */
  function createXprop() {
    if( empty( $this->xprop ) || !is_array( $this->xprop )) return FALSE;
    $output        = null;
    $toolbox       = new calendarComponent();
    $toolbox->setConfig( $this->getConfig());
    foreach( $this->xprop as $label => $xpropPart ) {
      if( !isset($xpropPart['value']) || ( empty( $xpropPart['value'] ) && !is_numeric( $xpropPart['value'] ))) {
        if( $this->getConfig( 'allowEmpty' ))
          $output .= $toolbox->_createElement( $label );
        continue;
      }
      $attributes  = $toolbox->_createParams( $xpropPart['params'], array( 'LANGUAGE' ));
      if( is_array( $xpropPart['value'] )) {
        foreach( $xpropPart['value'] as $pix => $theXpart )
          $xpropPart['value'][$pix] = iCalUtilityFunctions::_strrep( $theXpart, $this->format, $this->nl );
        $xpropPart['value']  = implode( ',', $xpropPart['value'] );
      }
      else
        $xpropPart['value'] = iCalUtilityFunctions::_strrep( $xpropPart['value'], $this->format, $this->nl );
      $output     .= $toolbox->_createElement( $label, $attributes, $xpropPart['value'] );
      if( is_array( $toolbox->xcaldecl ) && ( 0 < count( $toolbox->xcaldecl ))) {
        foreach( $toolbox->xcaldecl as $localxcaldecl )
          $this->xcaldecl[] = $localxcaldecl;
      }
    }
    return $output;
  }
/**
 * set calendar property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string $label
 * @param string $value
 * @param array $params optional
 * @return bool
 */
  function setXprop( $label, $value, $params=FALSE ) {
    if( empty( $label ))
      return FALSE;
    if( 'X-' != strtoupper( substr( $label, 0, 2 )))
      return FALSE;
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $xprop           = array( 'value' => $value );
    $xprop['params'] = iCalUtilityFunctions::_setParams( $params );
    if( !is_array( $this->xprop )) $this->xprop = array();
    $this->xprop[strtoupper( $label )] = $xprop;
    return TRUE;
  }
/*********************************************************************************/
/**
 * delete calendar property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $propName, bool FALSE => X-property
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @return bool, if successfull delete
 */
  function deleteProperty( $propName=FALSE, $propix=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( !$propix )
      $propix = ( isset( $this->propdelix[$propName] ) && ( 'X-PROP' != $propName )) ? $this->propdelix[$propName] + 2 : 1;
    $this->propdelix[$propName] = --$propix;
    $return = FALSE;
    switch( $propName ) {
      case 'CALSCALE':
        if( isset( $this->calscale )) {
          $this->calscale = null;
          $return = TRUE;
        }
        break;
      case 'METHOD':
        if( isset( $this->method )) {
          $this->method   = null;
          $return = TRUE;
        }
        break;
      default:
        $reduced = array();
        if( $propName != 'X-PROP' ) {
          if( !isset( $this->xprop[$propName] )) { unset( $this->propdelix[$propName] ); return FALSE; }
          foreach( $this->xprop as $k => $a ) {
            if(( $k != $propName ) && !empty( $a ))
              $reduced[$k] = $a;
          }
        }
        else {
          if( count( $this->xprop ) <= $propix )  return FALSE;
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
/**
 * get calendar property value/params
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.13.4 - 2012-08-08
 * @param string $propName, optional
 * @param int $propix, optional, if specific property is wanted in case of multiply occurences
 * @param bool $inclParam=FALSE
 * @return mixed
 */
  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( 'X-PROP' == $propName ) {
      if( !$propix )
        $propix  = ( isset( $this->propix[$propName] )) ? $this->propix[$propName] + 2 : 1;
      $this->propix[$propName] = --$propix;
    }
    else
      $mProps    = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES' );
    switch( $propName ) {
      case 'ATTENDEE':
      case 'CATEGORIES':
      case 'CONTACT':
      case 'DTSTART':
      case 'GEOLOCATION':
      case 'LOCATION':
      case 'ORGANIZER':
      case 'PRIORITY':
      case 'RESOURCES':
      case 'STATUS':
      case 'SUMMARY':
      case 'RECURRENCE-ID-UID':
      case 'RELATED-TO':
      case 'R-UID':
      case 'UID':
      case 'URL':
        $output  = array();
        foreach ( $this->components as $cix => $component) {
          if( !in_array( $component->objName, array('vevent', 'vtodo', 'vjournal', 'vfreebusy' )))
            continue;
          if( in_array( strtoupper( $propName ), $mProps )) {
            $component->_getProperties( $propName, $output );
            continue;
          }
          elseif(( 3 < strlen( $propName )) && ( 'UID' == substr( $propName, -3 ))) {
            if( FALSE !== ( $content = $component->getProperty( 'RECURRENCE-ID' )))
              $content = $component->getProperty( 'UID' );
          }
          elseif( 'GEOLOCATION' == $propName ) {
            $content = $component->getProperty( 'LOCATION' );
            $content = ( !empty( $content )) ? $content.' ' : '';
            if(( FALSE === ( $geo     = $component->getProperty( 'GEO' ))) || empty( $geo ))
              continue;
            if( 0.0 < $geo['latitude'] )
              $sign   = '+';
            else
              $sign   = ( 0.0 > $geo['latitude'] ) ? '-' : '';
            $content .= ' '.$sign.sprintf( "%09.6f", abs( $geo['latitude'] ));
            $content  = rtrim( rtrim( $content, '0' ), '.' );
            if( 0.0 < $geo['longitude'] )
              $sign   = '+';
            else
              $sign   = ( 0.0 > $geo['longitude'] ) ? '-' : '';
            $content .= $sign.sprintf( '%8.6f', abs( $geo['longitude'] )).'/';
          }
          elseif( FALSE === ( $content = $component->getProperty( $propName )))
            continue;
          if(( FALSE === $content ) || empty( $content ))
            continue;
          elseif( is_array( $content )) {
            if( isset( $content['year'] )) {
              $key  = sprintf( '%04d%02d%02d', $content['year'], $content['month'], $content['day'] );
              if( !isset( $output[$key] ))
                $output[$key] = 1;
              else
                $output[$key] += 1;
            }
            else {
              foreach( $content as $partValue => $partCount ) {
                if( !isset( $output[$partValue] ))
                  $output[$partValue] = $partCount;
                else
                  $output[$partValue] += $partCount;
              }
            }
          } // end elseif( is_array( $content )) {
          elseif( !isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        } // end foreach ( $this->components as $cix => $component)
        if( !empty( $output ))
          ksort( $output );
        return $output;
        break;
      case 'CALSCALE':
        return ( !empty( $this->calscale )) ? $this->calscale : FALSE;
        break;
      case 'METHOD':
        return ( !empty( $this->method )) ? $this->method : FALSE;
        break;
      case 'PRODID':
        if( empty( $this->prodid ))
          $this->_makeProdid();
        return $this->prodid;
        break;
      case 'VERSION':
        return ( !empty( $this->version )) ? $this->version : FALSE;
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
          unset( $this->propix[$propName] );
          return FALSE; // not found ??
        }
    }
    return FALSE;
  }
/**
 * general vcalendar property setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.13 - 2007-11-04
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @return bool
 */
  function setProperty () {
    $numargs    = func_num_args();
    if( 1 > $numargs )
      return FALSE;
    $arglist    = func_get_args();
    $arglist[0] = strtoupper( $arglist[0] );
    switch( $arglist[0] ) {
      case 'CALSCALE':
        return $this->setCalscale( $arglist[1] );
      case 'METHOD':
        return $this->setMethod( $arglist[1] );
      case 'VERSION':
        return $this->setVersion( $arglist[1] );
      default:
        if( !isset( $arglist[1] )) $arglist[1] = null;
        if( !isset( $arglist[2] )) $arglist[2] = null;
        return $this->setXprop( $arglist[0], $arglist[1], $arglist[2] );
    }
    return FALSE;
  }
/*********************************************************************************/
/**
 * get vcalendar config values or * calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.7 - 2012-01-12
 * @param mixed $config
 * @return value
 */
  function getConfig( $config = FALSE ) {
    if( !$config ) {
      $return = array();
      $return['ALLOWEMPTY']  = $this->getConfig( 'ALLOWEMPTY' );
      $return['DELIMITER']   = $this->getConfig( 'DELIMITER' );
      $return['DIRECTORY']   = $this->getConfig( 'DIRECTORY' );
      $return['FILENAME']    = $this->getConfig( 'FILENAME' );
      $return['DIRFILE']     = $this->getConfig( 'DIRFILE' );
      $return['FILESIZE']    = $this->getConfig( 'FILESIZE' );
      $return['FORMAT']      = $this->getConfig( 'FORMAT' );
      if( FALSE !== ( $lang  = $this->getConfig( 'LANGUAGE' )))
        $return['LANGUAGE']  = $lang;
      $return['NEWLINECHAR'] = $this->getConfig( 'NEWLINECHAR' );
      $return['UNIQUE_ID']   = $this->getConfig( 'UNIQUE_ID' );
      if( FALSE !== ( $url   = $this->getConfig( 'URL' )))
        $return['URL']       = $url;
      $return['TZID']        = $this->getConfig( 'TZID' );
      return $return;
    }
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        return $this->allowEmpty;
        break;
      case 'COMPSINFO':
        unset( $this->compix );
        $info = array();
        foreach( $this->components as $cix => $component ) {
          if( empty( $component )) continue;
          $info[$cix]['ordno'] = $cix + 1;
          $info[$cix]['type']  = $component->objName;
          $info[$cix]['uid']   = $component->getProperty( 'uid' );
          $info[$cix]['props'] = $component->getConfig( 'propinfo' );
          $info[$cix]['sub']   = $component->getConfig( 'compsinfo' );
        }
        return $info;
        break;
      case 'DELIMITER':
        return $this->delimiter;
        break;
      case 'DIRECTORY':
        if( empty( $this->directory ) && ( '0' != $this->directory ))
          $this->directory = '.';
        return $this->directory;
        break;
      case 'DIRFILE':
        return $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$this->getConfig( 'filename' );
        break;
      case 'FILEINFO':
        return array( $this->getConfig( 'directory' )
                    , $this->getConfig( 'filename' )
                    , $this->getConfig( 'filesize' ));
        break;
      case 'FILENAME':
        if( empty( $this->filename ) && ( '0' != $this->filename )) {
          if( 'xcal' == $this->format )
            $this->filename = date( 'YmdHis' ).'.xml'; // recommended xcs.. .
          else
            $this->filename = date( 'YmdHis' ).'.ics';
        }
        return $this->filename;
        break;
      case 'FILESIZE':
        $size    = 0;
        if( empty( $this->url )) {
          $dirfile = $this->getConfig( 'dirfile' );
          if( !is_file( $dirfile ) || ( FALSE === ( $size = filesize( $dirfile ))))
            $size = 0;
          clearstatcache();
        }
        return $size;
        break;
      case 'FORMAT':
        return ( $this->format == 'xcal' ) ? 'xCal' : 'iCal';
        break;
      case 'LANGUAGE':
         /* get language for calendar component as defined in [RFC 1766] */
        return $this->language;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        return $this->nl;
        break;
      case 'TZID':
        return $this->dtzid;
        break;
      case 'UNIQUE_ID':
        return $this->unique_id;
        break;
      case 'URL':
        if( !empty( $this->url ))
          return $this->url;
        else
          return FALSE;
        break;
    }
  }
/**
 * general vcalendar config setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.7 - 2013-01-11
 * @param mixed  $config
 * @param string $value
 * @return void
 */
  function setConfig( $config, $value = FALSE) {
    if( is_array( $config )) {
      $ak = array_keys( $config );
      foreach( $ak as $k ) {
        if( 'DIRECTORY' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'DIRECTORY', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
        }
        elseif( 'NEWLINECHAR' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'NEWLINECHAR', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
        }
      }
      foreach( $config as $cKey => $cValue ) {
        if( FALSE === $this->setConfig( $cKey, $cValue ))
          return FALSE;
      }
      return TRUE;
    }
    $res = FALSE;
    switch( strtoupper( $config )) {
      case 'ALLOWEMPTY':
        $this->allowEmpty = $value;
        $subcfg  = array( 'ALLOWEMPTY' => $value );
        $res = TRUE;
        break;
      case 'DELIMITER':
        $this->delimiter = $value;
        return TRUE;
        break;
      case 'DIRECTORY':
        $value   = trim( $value );
        $del     = $this->getConfig('delimiter');
        if( $del == substr( $value, ( 0 - strlen( $del ))))
          $value = substr( $value, 0, ( strlen( $value ) - strlen( $del )));
        if( is_dir( $value )) {
            /* local directory */
          clearstatcache();
          $this->directory = $value;
          $this->url       = null;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FILENAME':
        $value   = trim( $value );
        if( !empty( $this->url )) {
            /* remote directory+file -> URL */
          $this->filename = $value;
          return TRUE;
        }
        $dirfile = $this->getConfig( 'directory' ).$this->getConfig( 'delimiter' ).$value;
        if( file_exists( $dirfile )) {
            /* local file exists */
          if( is_readable( $dirfile ) || is_writable( $dirfile )) {
            clearstatcache();
            $this->filename = $value;
            return TRUE;
          }
          else
            return FALSE;
        }
        elseif( is_readable($this->getConfig( 'directory' ) ) || is_writable( $this->getConfig( 'directory' ) )) {
            /* read- or writable directory */
          $this->filename = $value;
          return TRUE;
        }
        else
          return FALSE;
        break;
      case 'FORMAT':
        $value   = trim( strtolower( $value ));
        if( 'xcal' == $value ) {
          $this->format             = 'xcal';
          $this->attributeDelimiter = $this->nl;
          $this->valueInit          = null;
        }
        else {
          $this->format             = null;
          $this->attributeDelimiter = ';';
          $this->valueInit          = ':';
        }
        $subcfg  = array( 'FORMAT' => $value );
        $res = TRUE;
        break;
      case 'LANGUAGE': // set language for calendar component as defined in [RFC 1766]
        $value   = trim( $value );
        $this->language = $value;
        $this->_makeProdid();
        $subcfg  = array( 'LANGUAGE' => $value );
        $res = TRUE;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        $this->nl = $value;
        if( 'xcal' == $value ) {
          $this->attributeDelimiter = $this->nl;
          $this->valueInit          = null;
        }
        else {
          $this->attributeDelimiter = ';';
          $this->valueInit          = ':';
        }
        $subcfg  = array( 'NL' => $value );
        $res = TRUE;
        break;
      case 'TZID':
        $this->dtzid = $value;
        $subcfg  = array( 'TZID' => $value );
        $res = TRUE;
        break;
      case 'UNIQUE_ID':
        $value   = trim( $value );
        $this->unique_id = $value;
        $this->_makeProdid();
        $subcfg  = array( 'UNIQUE_ID' => $value );
        $res = TRUE;
        break;
      case 'URL':
            /* remote file - URL */
        $value     = str_replace( array( 'HTTP://', 'WEBCAL://', 'webcal://' ), 'http://', trim( $value ));
        if( 'http://' != substr( $value, 0, 7 ))
          return FALSE;
        $s1        = $this->url;
        $this->url = $value;
        $s2        = $this->directory;
        $this->directory = null;
        $parts     = pathinfo( $value );
        if( FALSE === $this->setConfig( 'filename',  $parts['basename'] )) {
          $this->url       = $s1;
          $this->directory = $s2;
          return FALSE;
        }
        else
          return TRUE;
        break;
      default:  // any unvalid config key.. .
        return TRUE;
    }
    if( !$res ) return FALSE;
    if( isset( $subcfg ) && !empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgvalue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $component->setConfig( $cfgkey, $cfgvalue, TRUE );
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
 * add calendar component to container
 *
 * alias to setComponent
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 * @return void
 */
  function addComponent( $component ) {
    $this->setComponent( $component );
  }
/**
 * delete calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $arg1 ordno / component type / component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function deleteComponent( $arg1, $arg2=FALSE  ) {
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) {
      $argType = 'INDEX';
      $index   = (int) $arg1 - 1;
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) {
      $argType = strtolower( $arg1 );
      $index   = ( !empty( $arg2 ) && ctype_digit( (string) $arg2 )) ? (( int ) $arg2 - 1 ) : 0;
    }
    $cix1dC = 0;
    foreach ( $this->components as $cix => $component) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) {
        unset( $this->components[$cix] );
        return TRUE;
      }
      elseif( $argType == $component->objName ) {
        if( $index == $cix1dC ) {
          unset( $this->components[$cix] );
          return TRUE;
        }
        $cix1dC++;
      }
      elseif( !$argType && ($arg1 == $component->getProperty( 'uid' ))) {
        unset( $this->components[$cix] );
        return TRUE;
      }
    }
    return FALSE;
  }
/**
 * get calendar component from container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.15 - 2013-04-25
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return object
 */
  function getComponent( $arg1=FALSE, $arg2=FALSE ) {
    $index = $argType = null;
    if ( !$arg1 ) { // first or next in component chain
      $argType = 'INDEX';
      $index   = $this->compix['INDEX'] = ( isset( $this->compix['INDEX'] )) ? $this->compix['INDEX'] + 1 : 1;
    }
    elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
      $arg2  = implode( '-', array_keys( $arg1 ));
      $index = $this->compix[$arg2] = ( isset( $this->compix[$arg2] )) ? $this->compix[$arg2] + 1 : 1;
      $dateProps  = array( 'DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED', 'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID' );
      $otherProps = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL' );
      $mProps     = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES' );
    }
    elseif ( ctype_digit( (string) $arg1 )) { // specific component in chain
      $argType = 'INDEX';
      $index   = (int) $arg1;
      unset( $this->compix );
    }
    elseif(( strlen( $arg1 ) <= strlen( 'vfreebusy' )) && ( FALSE === strpos( $arg1, '@' ))) { // object class name
      unset( $this->compix['INDEX'] );
      $argType = strtolower( $arg1 );
      if( !$arg2 )
        $index = $this->compix[$argType] = ( isset( $this->compix[$argType] )) ? $this->compix[$argType] + 1 : 1;
      elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
        $index = (int) $arg2;
    }
    elseif(( strlen( $arg1 ) > strlen( 'vfreebusy' )) && ( FALSE !== strpos( $arg1, '@' ))) { // UID as 1st argument
      if( !$arg2 )
        $index = $this->compix[$arg1] = ( isset( $this->compix[$arg1] )) ? $this->compix[$arg1] + 1 : 1;
      elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
        $index = (int) $arg2;
    }
    if( isset( $index ))
      $index  -= 1;
    $ckeys = array_keys( $this->components );
    if( !empty( $index) && ( $index > end(  $ckeys )))
      return FALSE;
    $cix1gC = 0;
    foreach ( $this->components as $cix => $component) {
      if( empty( $component )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix ))
        return $component->copy();
      elseif( $argType == $component->objName ) {
        if( $index == $cix1gC )
          return $component->copy();
        $cix1gC++;
      }
      elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
        $hit = array();
        foreach( $arg1 as $pName => $pValue ) {
          $pName = strtoupper( $pName );
          if( !in_array( $pName, $dateProps ) && !in_array( $pName, $otherProps ))
            continue;
          if( in_array( $pName, $mProps )) { // multiple occurrence
            $propValues = array();
            $component->_getProperties( $pName, $propValues );
            $propValues = array_keys( $propValues );
            $hit[] = ( in_array( $pValue, $propValues )) ? TRUE : FALSE;
            continue;
          } // end   if(.. .// multiple occurrence
          if( FALSE === ( $value = $component->getProperty( $pName ))) { // single occurrence
            $hit[] = FALSE; // missing property
            continue;
          }
          if( 'SUMMARY' == $pName ) { // exists within (any case)
            $hit[] = ( FALSE !== stripos( $value, $pValue )) ? TRUE : FALSE;
            continue;
          }
          if( in_array( strtoupper( $pName ), $dateProps )) {
            $valuedate = sprintf( '%04d%02d%02d', $value['year'], $value['month'], $value['day'] );
            if( 8 < strlen( $pValue )) {
              if( isset( $value['hour'] )) {
                if( 'T' == substr( $pValue, 8, 1 ))
                  $pValue = str_replace( 'T', '', $pValue );
                $valuedate .= sprintf( '%02d%02d%02d', $value['hour'], $value['min'], $value['sec'] );
              }
              else
                $pValue = substr( $pValue, 0, 8 );
            }
            $hit[] = ( $pValue == $valuedate ) ? TRUE : FALSE;
            continue;
          }
          elseif( !is_array( $value ))
            $value = array( $value );
          foreach( $value as $part ) {
            $part = ( FALSE !== strpos( $part, ',' )) ? explode( ',', $part ) : array( $part );
            foreach( $part as $subPart ) {
              if( $pValue == $subPart ) {
                $hit[] = TRUE;
                continue 3;
              }
            }
          } // end foreach( $value as $part )
          $hit[] = FALSE; // no hit in property
        } // end  foreach( $arg1 as $pName => $pValue )
        if( in_array( TRUE, $hit )) {
          if( $index == $cix1gC )
            return $component->copy();
          $cix1gC++;
        }
      } // end elseif( is_array( $arg1 )) { // array( *[propertyName => propertyValue] )
      elseif( !$argType && ($arg1 == $component->getProperty( 'uid' ))) { // UID
        if( $index == $cix1gC )
          return $component->copy();
        $cix1gC++;
      }
    } // end foreach ( $this->components.. .
            /* not found.. . */
    unset( $this->compix );
    return FALSE;
  }
/**
 * create new calendar component, already included within calendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.33 - 2011-01-03
 * @param string $compType component type
 * @return object (reference)
 */
  function & newComponent( $compType ) {
    $config = $this->getConfig();
    $keys   = array_keys( $this->components );
    $ix     = end( $keys) + 1;
    switch( strtoupper( $compType )) {
      case 'EVENT':
      case 'VEVENT':
        $this->components[$ix] = new vevent( $config );
        break;
      case 'TODO':
      case 'VTODO':
        $this->components[$ix] = new vtodo( $config );
        break;
      case 'JOURNAL':
      case 'VJOURNAL':
        $this->components[$ix] = new vjournal( $config );
        break;
      case 'FREEBUSY':
      case 'VFREEBUSY':
        $this->components[$ix] = new vfreebusy( $config );
        break;
      case 'TIMEZONE':
      case 'VTIMEZONE':
        array_unshift( $this->components, new vtimezone( $config ));
        $ix = 0;
        break;
      default:
        return FALSE;
    }
    return $this->components[$ix];
  }
/**
 * select components from calendar on date or selectOption basis
 *
 * Ensure DTSTART is set for every component.
 * No date controls occurs.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.13 - 2013-03-16
 * @param mixed $startY optional, start Year,  default current Year ALT. array selecOptions ( *[ <propName> => <uniqueValue> ] )
 * @param int   $startM optional, start Month, default current Month
 * @param int   $startD optional, start Day,   default current Day
 * @param int   $endY   optional, end   Year,  default $startY
 * @param int   $endY   optional, end   Month, default $startM
 * @param int   $endY   optional, end   Day,   default $startD
 * @param mixed $cType  optional, calendar component type(-s), default FALSE=all else string/array type(-s)
 * @param bool  $flat   optional, FALSE (default) => output : array[Year][Month][Day][]
 *                                TRUE            => output : array[] (ignores split)
 * @param bool  $any    optional, TRUE (default) - select component(-s) that occurs within period
 *                                FALSE          - only component(-s) that starts within period
 * @param bool  $split  optional, TRUE (default) - one component copy every DAY it occurs during the
 *                                                 period (implies flat=FALSE)
 *                                FALSE          - one occurance of component only in output array
 * @return array or FALSE
 */
  function selectComponents( $startY=FALSE, $startM=FALSE, $startD=FALSE, $endY=FALSE, $endM=FALSE, $endD=FALSE, $cType=FALSE, $flat=FALSE, $any=TRUE, $split=TRUE ) {
            /* check  if empty calendar */
    if( 0 >= count( $this->components )) return FALSE;
    if( is_array( $startY ))
      return $this->selectComponents2( $startY );
            /* check default dates */
    if( !$startY ) $startY = date( 'Y' );
    if( !$startM ) $startM = date( 'm' );
    if( !$startD ) $startD = date( 'd' );
    $startDate = mktime( 0, 0, 0, $startM, $startD, $startY );
    if( !$endY )   $endY   = $startY;
    if( !$endM )   $endM   = $startM;
    if( !$endD )   $endD   = $startD;
    $endDate   = mktime( 23, 59, 59, $endM, $endD, $endY );
// echo 'selectComp arg='.date( 'Y-m-d H:i:s', $startDate).' -- '.date( 'Y-m-d H:i:s', $endDate)."<br>\n"; $tcnt = 0;// test ###
            /* check component types */
    $validTypes = array('vevent', 'vtodo', 'vjournal', 'vfreebusy' );
    if( is_array( $cType )) {
      foreach( $cType as $cix => $theType ) {
        $cType[$cix] = $theType = strtolower( $theType );
        if( !in_array( $theType, $validTypes ))
          $cType[$cix] = 'vevent';
      }
      $cType = array_unique( $cType );
    }
    elseif( !empty( $cType )) {
      $cType = strtolower( $cType );
      if( !in_array( $cType, $validTypes ))
        $cType = array( 'vevent' );
      else
        $cType = array( $cType );
    }
    else
      $cType = $validTypes;
    if( 0 >= count( $cType ))
      $cType = $validTypes;
    if(( FALSE === $flat ) && ( FALSE === $any )) // invalid combination
      $split = FALSE;
    if(( TRUE === $flat ) && ( TRUE === $split )) // invalid combination
      $split = FALSE;
            /* iterate components */
    $result       = array();
    $this->sort( 'UID' );
    $compUIDcmp   = null;
    $recurridList = array();
    foreach ( $this->components as $cix => $component ) {
      if( empty( $component )) continue;
      unset( $start );
            /* deselect unvalid type components */
      if( !in_array( $component->objName, $cType ))
        continue;
      $start = $component->getProperty( 'dtstart' );
            /* select due when dtstart is missing */
      if( empty( $start ) && ( $component->objName == 'vtodo' ) && ( FALSE === ( $start = $component->getProperty( 'due' ))))
        continue;
      if( empty( $start ))
        continue;
      $compUID      = $component->getProperty( 'UID' );
      if( $compUIDcmp != $compUID ) {
        $compUIDcmp = $compUID;
        unset( $exdatelist, $recurridList );
      }
      $dtendExist = $dueExist = $durationExist = $endAllDayEvent = $recurrid = FALSE;
      unset( $end, $startWdate, $endWdate, $rdurWsecs, $rdur, $workstart, $workend, $endDateFormat ); // clean up
      $startWdate = iCalUtilityFunctions::_date2timestamp( $start );
      $startDateFormat = ( isset( $start['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
            /* get end date from dtend/due/duration properties */
      $end = $component->getProperty( 'dtend' );
      if( !empty( $end )) {
        $dtendExist = TRUE;
        $endDateFormat = ( isset( $end['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
      }
      if( empty( $end ) && ( $component->objName == 'vtodo' )) {
        $end = $component->getProperty( 'due' );
        if( !empty( $end )) {
          $dueExist = TRUE;
          $endDateFormat = ( isset( $end['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
        }
      }
      if( !empty( $end ) && !isset( $end['hour'] )) {
          /* a DTEND without time part regards an event that ends the day before,
             for an all-day event DTSTART=20071201 DTEND=20071202 (taking place 20071201!!! */
        $endAllDayEvent = TRUE;
        $endWdate = mktime( 23, 59, 59, $end['month'], ($end['day'] - 1), $end['year'] );
        $end['year']  = date( 'Y', $endWdate );
        $end['month'] = date( 'm', $endWdate );
        $end['day']   = date( 'd', $endWdate );
        $end['hour']  = 23;
        $end['min']   = $end['sec'] = 59;
      }
      if( empty( $end )) {
        $end = $component->getProperty( 'duration', FALSE, FALSE, TRUE );// in dtend (array) format
        if( !empty( $end ))
          $durationExist = TRUE;
          $endDateFormat = ( isset( $start['hour'] )) ? 'Y-m-d H:i:s' : 'Y-m-d';
// if( !empty($end))  echo 'selectComp 4 start='.implode('-',$start).' end='.implode('-',$end)."<br>\n"; // test ###
      }
      if( empty( $end )) { // assume one day duration if missing end date
        $end = array( 'year' => $start['year'], 'month' => $start['month'], 'day' => $start['day'], 'hour' => 23, 'min' => 59, 'sec' => 59 );
      }
// if( isset($end))  echo 'selectComp 5 start='.implode('-',$start).' end='.implode('-',$end)."<br>\n"; // test ###
      $endWdate = iCalUtilityFunctions::_date2timestamp( $end );
      if( $endWdate < $startWdate ) { // MUST be after start date!!
        $end = array( 'year' => $start['year'], 'month' => $start['month'], 'day' => $start['day'], 'hour' => 23, 'min' => 59, 'sec' => 59 );
        $endWdate = iCalUtilityFunctions::_date2timestamp( $end );
      }
      $rdurWsecs  = $endWdate - $startWdate; // compute event (component) duration in seconds
            /* make a list of optional exclude dates for component occurence from exrule and exdate */
      $exdatelist = array();
      $workstart  = iCalUtilityFunctions::_timestamp2date(( $startDate - $rdurWsecs ), 6);
      $workend    = iCalUtilityFunctions::_timestamp2date(( $endDate + $rdurWsecs ), 6);
      while( FALSE !== ( $exrule = $component->getProperty( 'exrule' )))    // check exrule
        iCalUtilityFunctions::_recur2date( $exdatelist, $exrule, $start, $workstart, $workend );
      while( FALSE !== ( $exdate = $component->getProperty( 'exdate' ))) {  // check exdate
        foreach( $exdate as $theExdate ) {
          $exWdate = iCalUtilityFunctions::_date2timestamp( $theExdate );
          $exWdate = mktime( 0, 0, 0, date( 'm', $exWdate ), date( 'd', $exWdate ), date( 'Y', $exWdate )); // on a day-basis !!!
          if((( $startDate - $rdurWsecs ) <= $exWdate ) && ( $endDate >= $exWdate ))
            $exdatelist[$exWdate] = TRUE;
        } // end - foreach( $exdate as $theExdate )
      }  // end - check exdate
            /* check recurrence-id (note, a missing sequence is the same as sequence=0 so don't test for sequence), remove hit with reccurr-id date */
      if( FALSE !== ( $t = $recurrid = $component->getProperty( 'recurrence-id' ))) {
        $recurrid = iCalUtilityFunctions::_date2timestamp( $recurrid );
        $recurrid = mktime( 0, 0, 0, date( 'm', $recurrid ), date( 'd', $recurrid ), date( 'Y', $recurrid )); // on a day-basis !!!
        $recurridList[$recurrid] = TRUE;                                             // no recurring to start this day
// echo "adding comp no:$cix with date=".implode($start)." and recurrid=".implode($t)." to recurridList id=$recurrid<br>\n"; // test ###
      } // end recurrence-id/sequence test
            /* select only components with.. . */
      if(( !$any && ( $startWdate >= $startDate ) && ( $startWdate <= $endDate )) || // (dt)start within the period
         (  $any && ( $startWdate < $endDate ) && ( $endWdate >= $startDate ))) {    // occurs within the period
            /* add the selected component (WITHIN valid dates) to output array */
        if( $flat ) { // any=true/false, ignores split
          if( !$recurrid )
            $result[$compUID] = $component->copy(); // copy original to output (but not anyone with recurrence-id)
        }
        elseif( $split ) { // split the original component
          if( $endWdate > $endDate )
            $endWdate = $endDate;     // use period end date
          $rstart   = $startWdate;
          if( $rstart < $startDate )
            $rstart = $startDate; // use period start date
          $startYMD = $rstartYMD = date( 'Ymd', $rstart );
          $endYMD   = date( 'Ymd', $endWdate );
          $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
// echo "going to test comp no:$cix with rstartYMD=$rstartYMD, endYMD=$endYMD and checkDate($checkDate) with recurridList=".implode(',',array_keys($recurridList))."<br>\n"; // test ###
          if( !isset( $exdatelist[$checkDate] )) { // exclude any recurrence START date, found in exdatelist
            while( $rstartYMD <= $endYMD ) { // iterate
              if( isset( $exdatelist[$checkDate] ) ||                   // exclude any recurrence date, found in the exdatelist
                ( isset( $recurridList[$checkDate] ) && !$recurrid )) { // or in the recurridList, but not itself
// echo "skipping comp no:$cix with datestart=$rstartYMD and checkdate=$checkDate<br>\n"; // test ###
                $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                $rstartYMD = date( 'Ymd', $rstart );
                continue;
              }
              if( $rstartYMD > $startYMD ) // date after dtstart
                $datestring = date( $startDateFormat, $checkDate ); // mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart )));
              else
                $datestring = date( $startDateFormat, $rstart );
              if( isset( $start['tz'] ))
                $datestring .= ' '.$start['tz'];
// echo "split org comp no:$cix rstartYMD=$rstartYMD (datestring=$datestring)<br>\n"; // test ###
              $component->setProperty( 'X-CURRENT-DTSTART', $datestring );
              if( $dtendExist || $dueExist || $durationExist ) {
                if( $rstartYMD < $endYMD ) // not the last day
                  $tend = mktime( 23, 59, 59, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ));
                else
                  $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                if( $endAllDayEvent && $dtendExist )
                  $tend += ( 24 * 3600 ); // alldaysevents has an end date 'day after' meaning this day
                $datestring = date( $endDateFormat, $tend );
                if( isset( $end['tz'] ))
                  $datestring .= ' '.$end['tz'];
                $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                $component->setProperty( $propName, $datestring );
              } // end if( $dtendExist || $dueExist || $durationExist )
              $wd        = getdate( $rstart );
              $result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component->copy(); // copy to output
              $rstart    = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
              $rstartYMD = date( 'Ymd', $rstart );
              $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
            } // end while( $rstart <= $endWdate )
          } // end if( !isset( $exdatelist[$checkDate] ))
        } // end elseif( $split )   -  else use component date
        elseif( $recurrid && !$flat && !$any && !$split )
          $continue = TRUE;
        else { // !$flat && !$split, i.e. no flat array and DTSTART within period
          $checkDate = mktime( 0, 0, 0, date( 'm', $startWdate ), date( 'd', $startWdate ), date( 'Y', $startWdate ) ); // on a day-basis !!!
// echo "going to test comp no:$cix with checkDate=$checkDate with recurridList=".implode(',',array_keys($recurridList)); // test ###
          if(( !$any || !isset( $exdatelist[$checkDate] )) &&   // exclude any recurrence date, found in exdatelist
              ( !isset( $recurridList[$checkDate] ) || $recurrid )) { // or in the recurridList, but not itself
// echo " and copied to output<br>\n"; // test ###
            $wd = getdate( $startWdate );
            $result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component->copy(); // copy to output
          }
        }
      } // end if(( $startWdate >= $startDate ) && ( $startWdate <= $endDate ))
            /* if 'any' components, check components with reccurrence rules, removing all excluding dates */
      if( TRUE === $any ) {
            /* make a list of optional repeating dates for component occurence, rrule, rdate */
        $recurlist = array();
        while( FALSE !== ( $rrule = $component->getProperty( 'rrule' )))    // check rrule
          iCalUtilityFunctions::_recur2date( $recurlist, $rrule, $start, $workstart, $workend );
        foreach( $recurlist as $recurkey => $recurvalue )                   // key=match date as timestamp
          $recurlist[$recurkey] = $rdurWsecs;                               // add duration in seconds
        while( FALSE !== ( $rdate = $component->getProperty( 'rdate' ))) {  // check rdate
          foreach( $rdate as $theRdate ) {
            if( is_array( $theRdate ) && ( 2 == count( $theRdate )) &&      // all days within PERIOD
                   array_key_exists( '0', $theRdate ) &&  array_key_exists( '1', $theRdate )) {
              $rstart = iCalUtilityFunctions::_date2timestamp( $theRdate[0] );
              if(( $rstart < ( $startDate - $rdurWsecs )) || ( $rstart > $endDate ))
                continue;
              if( isset( $theRdate[1]['year'] )) // date-date period
                $rend = iCalUtilityFunctions::_date2timestamp( $theRdate[1] );
              else {                             // date-duration period
                $rend = iCalUtilityFunctions::_duration2date( $theRdate[0], $theRdate[1] );
                $rend = iCalUtilityFunctions::_date2timestamp( $rend );
              }
              while( $rstart < $rend ) {
                $recurlist[$rstart] = $rdurWsecs; // set start date for recurrence instance + rdate duration in seconds
                $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
              }
            } // PERIOD end
            else { // single date
              $theRdate = iCalUtilityFunctions::_date2timestamp( $theRdate );
              if((( $startDate - $rdurWsecs ) <= $theRdate ) && ( $endDate >= $theRdate ))
                $recurlist[$theRdate] = $rdurWsecs; // set start date for recurrence instance + event duration in seconds
            }
          }
        }  // end - check rdate
        foreach( $recurlist as $recurkey => $durvalue ) { // remove all recurrence START dates found in the exdatelist
          $checkDate = mktime( 0, 0, 0, date( 'm', $recurkey ), date( 'd', $recurkey ), date( 'Y', $recurkey ) ); // on a day-basis !!!
          if( isset( $exdatelist[$checkDate] )) // no recurring to start this day
            unset( $recurlist[$recurkey] );
        }
        if( 0 < count( $recurlist )) {
          ksort( $recurlist );
          $xRecurrence = 1;
          $component2  = $component->copy();
          $compUID     = $component2->getProperty( 'UID' );
          foreach( $recurlist as $recurkey => $durvalue ) {
// echo "recurKey=".date( 'Y-m-d H:i:s', $recurkey ).' dur='.iCalUtilityFunctions::offsetSec2His( $durvalue )."<br>\n"; // test ###;
            if((( $startDate - $rdurWsecs ) > $recurkey ) || ( $endDate < $recurkey )) // not within period
              continue;
            $checkDate = mktime( 0, 0, 0, date( 'm', $recurkey ), date( 'd', $recurkey ), date( 'Y', $recurkey ) ); // on a day-basis !!!
            if( isset( $recurridList[$checkDate] )) // no recurring to start this day
              continue;
            if( isset( $exdatelist[$checkDate] ))   // check excluded dates
              continue;
            if( $startWdate >= $recurkey )          // exclude component start date
              continue;
            $rstart = $recurkey;
            $rend   = $recurkey + $durvalue;
           /* add repeating components within valid dates to output array, only start date set */
            if( $flat ) {
              if( !isset( $result[$compUID] )) // only one comp
                $result[$compUID] = $component2->copy(); // copy to output
            }
           /* add repeating components within valid dates to output array, one each day */
            elseif( $split ) {
              $xRecurrence += 1;
              if( $rend > $endDate )
                $rend = $endDate;
              $startYMD = $rstartYMD = date( 'Ymd', $rstart );
              $endYMD   = date( 'Ymd', $rend );
// echo "splitStart=".date( 'Y-m-d H:i:s', $rstart ).' end='.date( 'Y-m-d H:i:s', $rend )."<br>\n"; // test ###;
              while( $rstart <= $rend ) { // iterate.. .
                $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                if( isset( $recurridList[$checkDate] )) // no recurring to start this day
                  break;
                if( isset( $exdatelist[$checkDate] ))   // exclude any recurrence START date, found in exdatelist
                  break;
// echo "checking date after startdate=".date( 'Y-m-d H:i:s', $rstart ).' mot '.date( 'Y-m-d H:i:s', $startDate )."<br>"; // test ###;
                if( $rstart >= $startDate ) {           // date after dtstart
                  if( $rstartYMD > $startYMD )          // date after dtstart
                    $datestring = date( $startDateFormat, $checkDate );
                  else
                    $datestring = date( $startDateFormat, $rstart );
                  if( isset( $start['tz'] ))
                    $datestring .= ' '.$start['tz'];
// echo "spliting = $datestring<BR>\n"; // test ###
                  $component2->setProperty( 'X-CURRENT-DTSTART', $datestring );
                  if( $dtendExist || $dueExist || $durationExist ) {
                    if( $rstartYMD < $endYMD ) // not the last day
                      $tend = mktime( 23, 59, 59, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ));
                    else
                      $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
                    if( $endAllDayEvent && $dtendExist )
                      $tend += ( 24 * 3600 );           // alldaysevents has an end date 'day after' meaning this day
                    $datestring = date( $endDateFormat, $tend );
                    if( isset( $end['tz'] ))
                      $datestring .= ' '.$end['tz'];
                    $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                    $component2->setProperty( $propName, $datestring );
                  } // end if( $dtendExist || $dueExist || $durationExist )
                  $component2->setProperty( 'X-RECURRENCE', $xRecurrence );
                  $wd = getdate( $rstart );
                  $result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component2->copy(); // copy to output
                } // end if( $checkDate > $startYMD ) { // date after dtstart
                $rstart = mktime( date( 'H', $rstart ), date( 'i', $rstart ), date( 's', $rstart ), date( 'm', $rstart ), date( 'd', $rstart ) + 1, date( 'Y', $rstart ) ); // step one day
                $rstartYMD = date( 'Ymd', $rstart );
              } // end while( $rstart <= $rend )
            } // end elseif( $split )
            elseif( $rstart >= $startDate ) {           // date within period   //* flat=FALSE && split=FALSE => one comp every recur startdate *//
              $xRecurrence += 1;
              $checkDate = mktime( 0, 0, 0, date( 'm', $rstart ), date( 'd', $rstart ), date( 'Y', $rstart ) ); // on a day-basis !!!
              if( !isset( $exdatelist[$checkDate] )) {  // exclude any recurrence START date, found in exdatelist
                $datestring = date( $startDateFormat, $rstart );
                if( isset( $start['tz'] ))
                  $datestring .= ' '.$start['tz'];
//echo "X-CURRENT-DTSTART 2 = $datestring xRecurrence=$xRecurrence tcnt =".++$tcnt."<br>";$component2->setProperty( 'X-CNT', $tcnt ); // test ###
                $component2->setProperty( 'X-CURRENT-DTSTART', $datestring );
                if( $dtendExist || $dueExist || $durationExist ) {
                  $tend = $rstart + $rdurWsecs;
                  if( date( 'Ymd', $tend ) < date( 'Ymd', $endWdate ))
                    $tend = mktime( 23, 59, 59, date( 'm', $tend ), date( 'd', $tend ), date( 'Y', $tend ));
                  else
                    $tend = mktime( date( 'H', $endWdate ), date( 'i', $endWdate ), date( 's', $endWdate ), date( 'm', $tend ), date( 'd', $tend ), date( 'Y', $tend ) ); // on a day-basis !!!
                  if( $endAllDayEvent && $dtendExist )
                    $tend += ( 24 * 3600 ); // alldaysevents has an end date 'day after' meaning this day
                  $datestring = date( $endDateFormat, $tend );
                  if( isset( $end['tz'] ))
                    $datestring .= ' '.$end['tz'];
                  $propName = ( !$dueExist ) ? 'X-CURRENT-DTEND' : 'X-CURRENT-DUE';
                  $component2->setProperty( $propName, $datestring );
                } // end if( $dtendExist || $dueExist || $durationExist )
                $component2->setProperty( 'X-RECURRENCE', $xRecurrence );
                $wd = getdate( $rstart );
                $result[$wd['year']][$wd['mon']][$wd['mday']][$compUID] = $component2->copy(); // copy to output
              } // end if( !isset( $exdatelist[$checkDate] ))
            } // end elseif( $rstart >= $startDate )
          } // end foreach( $recurlist as $recurkey => $durvalue )
          unset( $component2 );
        } // end if( 0 < count( $recurlist ))
            /* deselect components with startdate/enddate not within period */
        if(( $endWdate < $startDate ) || ( $startWdate > $endDate ))
          continue;
      } // end if( TRUE === $any )
    } // end foreach ( $this->components as $cix => $component )
    unset( $dtendExist, $dueExist, $durationExist, $endAllDayEvent, $recurrid, $recurridList,
           $end, $startWdate, $endWdate, $rdurWsecs, $rdur, $exdatelist, $recurlist, $workstart, $workend, $endDateFormat ); // clean up
    if( 0 >= count( $result )) return FALSE;
    elseif( !$flat ) {
      foreach( $result as $y => $yeararr ) {
        foreach( $yeararr as $m => $montharr ) {
          foreach( $montharr as $d => $dayarr ) {
            if( empty( $result[$y][$m][$d] ))
                unset( $result[$y][$m][$d] );
            else
              $result[$y][$m][$d] = array_values( $dayarr ); // skip tricky UID-index, hoping they are in hour order.. .
          }
          if( empty( $result[$y][$m] ))
              unset( $result[$y][$m] );
          else
            ksort( $result[$y][$m] );
        }
        if( empty( $result[$y] ))
            unset( $result[$y] );
        else
          ksort( $result[$y] );
      }
      if( empty( $result ))
          unset( $result );
      else
        ksort( $result );
    } // end elseif( !$flat )
    if( 0 >= count( $result ))
      return FALSE;
    return $result;
  }
/**
 * select components from calendar on based on specific property value(-s)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.6 - 2012-12-26
 * @param array $selectOptions, (string) key => (mixed) value, (key=propertyName)
 * @return array
 */
  function selectComponents2( $selectOptions ) {
    $output = array();
    $allowedComps      = array('vevent', 'vtodo', 'vjournal', 'vfreebusy' );
    $allowedProperties = array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL' );
    foreach( $this->components as $cix => $component3 ) {
      if( !in_array( $component3->objName, $allowedComps ))
        continue;
      $uid = $component3->getProperty( 'UID' );
      foreach( $selectOptions as $propName => $pvalue ) {
        $propName = strtoupper( $propName );
        if( !in_array( $propName, $allowedProperties ))
          continue;
        if( !is_array( $pvalue ))
          $pvalue = array( $pvalue );
        if(( 'UID' == $propName ) && in_array( $uid, $pvalue )) {
          $output[$uid][] = $component3->copy();
          continue;
        }
        elseif(( 'ATTENDEE' == $propName ) || ( 'CATEGORIES' == $propName ) || ( 'CONTACT' == $propName ) || ( 'RELATED-TO' == $propName ) || ( 'RESOURCES' == $propName )) { // multiple occurrence?
          $propValues = array();
          $component3->_getProperties( $propName, $propValues );
          $propValues = array_keys( $propValues );
          foreach( $pvalue as $theValue ) {
            if( in_array( $theValue, $propValues )) { //  && !isset( $output[$uid] )) {
              $output[$uid][] = $component3->copy();
              break;
            }
          }
          continue;
        } // end   elseif( // multiple occurrence?
        elseif( FALSE === ( $d = $component3->getProperty( $propName ))) // single occurrence
          continue;
        if( is_array( $d )) {
          foreach( $d as $part ) {
            if( in_array( $part, $pvalue ) && !isset( $output[$uid] ))
              $output[$uid][] = $component3->copy();
          }
        }
        elseif(( 'SUMMARY' == $propName ) && !isset( $output[$uid] )) {
          foreach( $pvalue as $pval ) {
            if( FALSE !== stripos( $d, $pval )) {
              $output[$uid][] = $component3->copy();
              break;
            }
          }
        }
        elseif( in_array( $d, $pvalue ) && !isset( $output[$uid] ))
          $output[$uid][] = $component3->copy();
      } // end foreach( $selectOptions as $propName => $pvalue ) {
    } // end foreach( $this->components as $cix => $component3 ) {
    if( !empty( $output )) {
      ksort( $output ); // uid order
      $output2 = array();
      foreach( $output as $uid => $components ) {
        foreach( $components as $component )
          $output2[] = $component;
      }
      $output = $output2;
    }
    return $output;
  }
/**
 * add calendar component to container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param object $component calendar component
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return void
 */
  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ) {
    $component->setConfig( $this->getConfig(), FALSE, TRUE );
    if( !in_array( $component->objName, array( 'valarm', 'vtimezone' ))) {
            /* make sure dtstamp and uid is set */
      $dummy1 = $component->getProperty( 'dtstamp' );
      $dummy2 = $component->getProperty( 'uid' );
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
    elseif( in_array( strtolower( $arg1 ), array( 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone' ))) {
      $argType = strtolower( $arg1 );
      $index = ( ctype_digit( (string) $arg2 )) ? ((int) $arg2) - 1 : 0;
    }
    // else if arg1 is set, arg1 must be an UID
    $cix1sC = 0;
    foreach ( $this->components as $cix => $component2) {
      if( empty( $component2 )) continue;
      if(( 'INDEX' == $argType ) && ( $index == $cix )) { // index insert/replace
        $this->components[$cix] = $component->copy();
        return TRUE;
      }
      elseif( $argType == $component2->objName ) { // component Type index insert/replace
        if( $index == $cix1sC ) {
          $this->components[$cix] = $component->copy();
          return TRUE;
        }
        $cix1sC++;
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
 * sort iCal compoments
 *
 * ascending sort on properties (if exist) x-current-dtstart, dtstart,
 * x-current-dtend, dtend, x-current-due, due, duration, created, dtstamp, uid if called without arguments,
 * otherwise sorting on specific (argument) property values
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.4 - 2012-12-17
 * @param string $sortArg, optional
 * @return void
 *
 */
  function sort( $sortArg=FALSE ) {
    if( ! is_array( $this->components ) || ( 2 > count( $this->components )))
      return;
    if( $sortArg ) {
      $sortArg = strtoupper( $sortArg );
      if( !in_array( $sortArg, array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'DTSTAMP', 'LOCATION', 'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES', 'STATUS', 'SUMMARY', 'UID', 'URL' )))
        $sortArg = FALSE;
    }
            /* set sort parameters for each component */
    foreach( $this->components as $cix => & $c ) {
      $c->srtk = array( '0', '0', '0', '0' );
      if( 'vtimezone' == $c->objName ) {
        if( FALSE === ( $c->srtk[0] = $c->getProperty( 'tzid' )))
          $c->srtk[0] = 0;
        continue;
      }
      elseif( $sortArg ) {
        if(( 'ATTENDEE' == $sortArg ) || ( 'CATEGORIES' == $sortArg ) || ( 'CONTACT' == $sortArg ) || ( 'RELATED-TO' == $sortArg ) || ( 'RESOURCES' == $sortArg )) {
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
        continue;
      } // end elseif( $sortArg )
      if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTSTART' ))) {
        $c->srtk[0] = iCalUtilityFunctions::_strdate2date( $d[1] );
        unset( $c->srtk[0]['unparsedtext'] );
      }
      elseif( FALSE === ( $c->srtk[0] = $c->getProperty( 'dtstart' )))
        $c->srtk[0] = 0;                                                  // sortkey 0 : dtstart

      if( FALSE !== ( $d = $c->getProperty( 'X-CURRENT-DTEND' ))) {
        $c->srtk[1] = iCalUtilityFunctions::_strdate2date( $d[1] );   // sortkey 1 : dtend/due(/duration)
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
    } // end foreach( $this->components as & $c
            /* sort */
    usort( $this->components, array( 'iCalUtilityFunctions', '_cmpfcn' ));
  }
/**
 * parse iCal text/file into vcalendar, components, properties and parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @param mixed $unparsedtext, optional, strict rfc2445 formatted, single property string or array of property strings
 * @return bool FALSE if error occurs during parsing
 *
 */
  function parse( $unparsedtext=FALSE ) {
    $nl = $this->getConfig( 'nl' );
    if(( FALSE === $unparsedtext ) || empty( $unparsedtext )) {
            /* directory+filename is set previously via setConfig directory+filename or url */
      if( FALSE === ( $filename = $this->getConfig( 'url' )))
        $filename = $this->getConfig( 'dirfile' );
            /* READ FILE */
      if( FALSE === ( $rows = file_get_contents( $filename )))
        return FALSE;                 /* err 1 */
    }
    elseif( is_array( $unparsedtext ))
      $rows =  implode( '\n'.$nl, $unparsedtext );
    else
      $rows = & $unparsedtext;
            /* fix line folding */
    $rows = explode( $nl, iCalUtilityFunctions::convEolChar( $rows, $nl ));
            /* skip leading (empty/invalid) lines */
    foreach( $rows as $lix => $line ) {
      if( FALSE !== stripos( $line, 'BEGIN:VCALENDAR' ))
        break;
      unset( $rows[$lix] );
    }
    $rcnt = count( $rows );
    if( 3 > $rcnt )                  /* err 10 */
      return FALSE;
            /* skip trailing empty lines and ensure an end row */
    $lix  = array_keys( $rows );
    $lix  = end( $lix );
    while( 3 < $lix ) {
      $tst = trim( $rows[$lix] );
      if(( '\n' == $tst ) || empty( $tst )) {
        unset( $rows[$lix] );
        $lix--;
        continue;
      }
      if( FALSE === stripos( $rows[$lix], 'END:VCALENDAR' ))
        $rows[] = 'END:VCALENDAR';
      break;
    }
    $comp    = & $this;
    $calsync = $compsync = 0;
            /* identify components and update unparsed data within component */
    $config = $this->getConfig();
    $endtxt = array( 'END:VE', 'END:VF', 'END:VJ', 'END:VT' );
    foreach( $rows as $lix => $line ) {
      if(     'BEGIN:VCALENDAR' == strtoupper( substr( $line, 0, 15 ))) {
        $calsync++;
        continue;
      }
      elseif( 'END:VCALENDAR'   == strtoupper( substr( $line, 0, 13 ))) {
        if( 0 < $compsync )
          $this->components[] = $comp->copy();
        $compsync--;
        $calsync--;
        break;
      }
      elseif( 1 != $calsync )
        return FALSE;                 /* err 20 */
      elseif( in_array( strtoupper( substr( $line, 0, 6 )), $endtxt )) {
        $this->components[] = $comp->copy();
        $compsync--;
        continue;
      }
      if(     'BEGIN:VEVENT'    == strtoupper( substr( $line, 0, 12 ))) {
        $comp = new vevent( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VFREEBUSY' == strtoupper( substr( $line, 0, 15 ))) {
        $comp = new vfreebusy( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VJOURNAL'  == strtoupper( substr( $line, 0, 14 ))) {
        $comp = new vjournal( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VTODO'     == strtoupper( substr( $line, 0, 11 ))) {
        $comp = new vtodo( $config );
        $compsync++;
      }
      elseif( 'BEGIN:VTIMEZONE' == strtoupper( substr( $line, 0, 15 ))) {
        $comp = new vtimezone( $config );
        $compsync++;
      }
      else { /* update component with unparsed data */
        $comp->unparsed[] = $line;
      }
    } // end foreach( $rows as $line )
    unset( $config, $endtxt );
            /* parse data for calendar (this) object */
    if( isset( $this->unparsed ) && is_array( $this->unparsed ) && ( 0 < count( $this->unparsed ))) {
            /* concatenate property values spread over several lines */
      $propnames = array( 'calscale','method','prodid','version','x-' );
      $proprows  = array();
      for( $i = 0; $i < count( $this->unparsed ); $i++ ) { // concatenate lines
        $line = rtrim( $this->unparsed[$i], $nl );
        while( isset( $this->unparsed[$i+1] ) && !empty( $this->unparsed[$i+1] ) && ( ' ' == $this->unparsed[$i+1]{0} ))
          $line .= rtrim( substr( $this->unparsed[++$i], 1 ), $nl );
        $proprows[] = $line;
      }
      $paramMStz   = array( 'utc-', 'utc+', 'gmt-', 'gmt+' );
      $paramProto3 = array( 'fax:', 'cid:', 'sms:', 'tel:', 'urn:' );
      $paramProto4 = array( 'crid:', 'news:', 'pres:' );
      foreach( $proprows as $line ) {
        if( '\n' == substr( $line, -2 ))
          $line = substr( $line, 0, -2 );
            /* get property name */
        $propname  = '';
        $cix       = 0;
        while( FALSE !== ( $char = substr( $line, $cix, 1 ))) {
          if( in_array( $char, array( ':', ';' )))
            break;
          else
            $propname .= $char;
          $cix++;
        }
            /* skip non standard property names */
        if(( 'x-' != strtolower( substr( $propname, 0, 2 ))) && !in_array( strtolower( $propname ), $propnames ))
          continue;
            /* ignore version/prodid properties */
        if( in_array( strtolower( $propname ), array( 'version', 'prodid' )))
          continue;
            /* rest of the line is opt.params and value */
        $line = substr( $line, $cix);
            /* separate attributes from value */
        $attr         = array();
        $attrix       = -1;
        $strlen       = strlen( $line );
        $WithinQuotes = FALSE;
        $cix          = 0;
        while( FALSE !== substr( $line, $cix, 1 )) {
          if(                       ( ':'  == $line[$cix] )                         &&
                                    ( substr( $line,$cix,     3 )  != '://' )       &&
             ( !in_array( strtolower( substr( $line,$cix - 6, 4 )), $paramMStz ))   &&
             ( !in_array( strtolower( substr( $line,$cix - 3, 4 )), $paramProto3 )) &&
             ( !in_array( strtolower( substr( $line,$cix - 4, 5 )), $paramProto4 )) &&
                        ( strtolower( substr( $line,$cix - 6, 7 )) != 'mailto:' )   &&
               !$WithinQuotes ) {
            $attrEnd = TRUE;
            if(( $cix < ( $strlen - 4 )) &&
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
          }
          if( '"' == $line[$cix] )
            $WithinQuotes = ( FALSE === $WithinQuotes ) ? TRUE : FALSE;
          if( ';' == $line[$cix] )
            $attr[++$attrix] = null;
          else
            $attr[$attrix] .= $line[$cix];
          $cix++;
        }
            /* make attributes in array format */
        $propattr = array();
        foreach( $attr as $attribute ) {
          $attrsplit = explode( '=', $attribute, 2 );
          if( 1 < count( $attrsplit ))
            $propattr[$attrsplit[0]] = $attrsplit[1];
          else
            $propattr[] = $attribute;
        }
            /* update Property */
        if( FALSE !== strpos( $line, ',' )) {
          $content  = array( 0 => '' );
          $cix = $lix = 0;
          while( FALSE !== substr( $line, $lix, 1 )) {
            if(( 0 < $lix ) && ( ',' == $line[$lix] ) && ( "\\" != $line[( $lix - 1 )])) {
              $cix++;
              $content[$cix] = '';
            }
            else
              $content[$cix] .= $line[$lix];
            $lix++;
          }
          if( 1 < count( $content )) {
            foreach( $content as $cix => $contentPart )
              $content[$cix] = iCalUtilityFunctions::_strunrep( $contentPart );
            $this->setProperty( $propname, $content, $propattr );
            continue;
          }
          else
            $line = reset( $content );
          $line = iCalUtilityFunctions::_strunrep( $line );
        }
        $this->setProperty( $propname, rtrim( $line, "\x00..\x1F" ), $propattr );
      } // end - foreach( $this->unparsed.. .
    } // end - if( is_array( $this->unparsed.. .
    unset( $unparsedtext, $rows, $this->unparsed, $proprows );
            /* parse Components */
    if( is_array( $this->components ) && ( 0 < count( $this->components ))) {
      $ckeys = array_keys( $this->components );
      foreach( $ckeys as $ckey ) {
        if( !empty( $this->components[$ckey] ) && !empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    else
      return FALSE;                   /* err 91 or something.. . */
    return TRUE;
  }
/*********************************************************************************/
/**
 * creates formatted output for calendar object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @return string
 */
  function createCalendar() {
    $calendarInit = $calendarxCaldecl = $calendarStart = $calendar = '';
    switch( $this->format ) {
      case 'xcal':
        $calendarInit  = '<?xml version="1.0" encoding="UTF-8"?>'.$this->nl.
                         '<!DOCTYPE vcalendar PUBLIC "-//IETF//DTD XCAL/iCalendar XML//EN"'.$this->nl.
                         '"http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"';
        $calendarStart = '>'.$this->nl.'<vcalendar';
        break;
      default:
        $calendarStart = 'BEGIN:VCALENDAR'.$this->nl;
        break;
    }
    $calendarStart .= $this->createVersion();
    $calendarStart .= $this->createProdid();
    $calendarStart .= $this->createCalscale();
    $calendarStart .= $this->createMethod();
    if( 'xcal' == $this->format )
      $calendarStart .= '>'.$this->nl;
    $calendar .= $this->createXprop();

    foreach( $this->components as $component ) {
      if( empty( $component )) continue;
      $component->setConfig( $this->getConfig(), FALSE, TRUE );
      $calendar .= $component->createComponent( $this->xcaldecl );
    }
    if(( 'xcal' == $this->format ) && ( 0 < count( $this->xcaldecl ))) { // xCal only
      $calendarInit .= ' [';
      $old_xcaldecl  = array();
      foreach( $this->xcaldecl as $declix => $declPart ) {
        if(( 0 < count( $old_xcaldecl))    &&
             isset( $declPart['uri'] )     && isset( $declPart['external'] )     &&
             isset( $old_xcaldecl['uri'] ) && isset( $old_xcaldecl['external'] ) &&
           ( in_array( $declPart['uri'],      $old_xcaldecl['uri'] ))            &&
           ( in_array( $declPart['external'], $old_xcaldecl['external'] )))
          continue; // no duplicate uri and ext. references
        if(( 0 < count( $old_xcaldecl))    &&
            !isset( $declPart['uri'] )     && !isset( $declPart['uri'] )         &&
             isset( $declPart['ref'] )     && isset( $old_xcaldecl['ref'] )      &&
           ( in_array( $declPart['ref'],      $old_xcaldecl['ref'] )))
          continue; // no duplicate element declarations
        $calendarxCaldecl .= $this->nl.'<!';
        foreach( $declPart as $declKey => $declValue ) {
          switch( $declKey ) {                    // index
            case 'xmldecl':                       // no 1
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'uri':                           // no 2
              $calendarxCaldecl .= $declValue.' ';
              $old_xcaldecl['uri'][] = $declValue;
              break;
            case 'ref':                           // no 3
              $calendarxCaldecl .= $declValue.' ';
              $old_xcaldecl['ref'][] = $declValue;
              break;
            case 'external':                      // no 4
              $calendarxCaldecl .= '"'.$declValue.'" ';
              $old_xcaldecl['external'][] = $declValue;
              break;
            case 'type':                          // no 5
              $calendarxCaldecl .= $declValue.' ';
              break;
            case 'type2':                         // no 6
              $calendarxCaldecl .= $declValue;
              break;
          }
        }
        $calendarxCaldecl .= '>';
      }
      $calendarxCaldecl .= $this->nl.']';
    }
    switch( $this->format ) {
      case 'xcal':
        $calendar .= '</vcalendar>'.$this->nl;
        break;
      default:
        $calendar .= 'END:VCALENDAR'.$this->nl;
        break;
    }
    return $calendarInit.$calendarxCaldecl.$calendarStart.$calendar;
  }
/**
 * a HTTP redirect header is sent with created, updated and/or parsed calendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.24 - 2011-12-23
 * @param bool $utf8Encode
 * @param bool $gzip
 * @return redirect
 */
  function returnCalendar( $utf8Encode=FALSE, $gzip=FALSE ) {
    $filename = $this->getConfig( 'filename' );
    $output   = $this->createCalendar();
    if( $utf8Encode )
      $output = utf8_encode( $output );
    if( $gzip ) {
      $output = gzencode( $output, 9 );
      header( 'Content-Encoding: gzip' );
      header( 'Vary: *' );
      header( 'Content-Length: '.strlen( $output ));
    }
    if( 'xcal' == $this->format )
      header( 'Content-Type: application/calendar+xml; charset=utf-8' );
    else
      header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
    header( 'Cache-Control: max-age=10' );
    die( $output );
  }
/**
 * save content in a file
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.12 - 2007-12-30
 * @param string $directory optional
 * @param string $filename optional
 * @param string $delimiter optional
 * @return bool
 */
  function saveCalendar( $directory=FALSE, $filename=FALSE, $delimiter=FALSE ) {
    if( $directory )
      $this->setConfig( 'directory', $directory );
    if( $filename )
      $this->setConfig( 'filename',  $filename );
    if( $delimiter && ($delimiter != DIRECTORY_SEPARATOR ))
      $this->setConfig( 'delimiter', $delimiter );
    if( FALSE === ( $dirfile = $this->getConfig( 'url' )))
      $dirfile = $this->getConfig( 'dirfile' );
    $iCalFile = @fopen( $dirfile, 'w' );
    if( $iCalFile ) {
      if( FALSE === fwrite( $iCalFile, $this->createCalendar() ))
        return FALSE;
      fclose( $iCalFile );
      return TRUE;
    }
    else
      return FALSE;
  }
/**
 * if recent version of calendar file exists (default one hour), an HTTP redirect header is sent
 * else FALSE is returned
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.12 - 2007-10-28
 * @param string $directory optional alt. int timeout
 * @param string $filename optional
 * @param string $delimiter optional
 * @param int timeout optional, default 3600 sec
 * @return redirect/FALSE
 */
  function useCachedCalendar( $directory=FALSE, $filename=FALSE, $delimiter=FALSE, $timeout=3600) {
    if ( $directory && ctype_digit( (string) $directory ) && !$filename ) {
      $timeout   = (int) $directory;
      $directory = FALSE;
    }
    if( $directory )
      $this->setConfig( 'directory', $directory );
    if( $filename )
      $this->setConfig( 'filename',  $filename );
    if( $delimiter && ( $delimiter != DIRECTORY_SEPARATOR ))
      $this->setConfig( 'delimiter', $delimiter );
    $filesize    = $this->getConfig( 'filesize' );
    if( 0 >= $filesize )
      return FALSE;
    $dirfile     = $this->getConfig( 'dirfile' );
    if( time() - filemtime( $dirfile ) < $timeout) {
      clearstatcache();
      $dirfile   = $this->getConfig( 'dirfile' );
      $filename  = $this->getConfig( 'filename' );
//    if( headers_sent( $filename, $linenum ))
//      die( "Headers already sent in $filename on line $linenum\n" );
      if( 'xcal' == $this->format )
        header( 'Content-Type: application/calendar+xml; charset=utf-8' );
      else
        header( 'Content-Type: text/calendar; charset=utf-8' );
      header( 'Content-Length: '.$filesize );
      header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
      header( 'Cache-Control: max-age=10' );
      $fp = @fopen( $dirfile, 'r' );
      if( $fp ) {
        fpassthru( $fp );
        fclose( $fp );
      }
      die();
    }
    else
      return FALSE;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 *  abstract class for calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-14
 */
class calendarComponent {
            //  component property variables
  var $uid;
  var $dtstamp;

            //  component config variables
  var $allowEmpty;
  var $language;
  var $nl;
  var $unique_id;
  var $format;
  var $objName; // created automatically at instance creation
  var $dtzid;   // default (local) timezone
            //  component internal variables
  var $componentStart1;
  var $componentStart2;
  var $componentEnd1;
  var $componentEnd2;
  var $elementStart1;
  var $elementStart2;
  var $elementEnd1;
  var $elementEnd2;
  var $intAttrDelimiter;
  var $attributeDelimiter;
  var $valueInit;
            //  component xCal declaration container
  var $xcaldecl;
/**
 * constructor for calendar component object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.9.6 - 2011-05-17
 */
  function calendarComponent() {
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
 * @param string $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @since 2.16.21 - 2013-06-23
 * @param string $value
 * @param array $params, optional
 * @param integer $index, optional
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
      foreach( $params as $optparamlabel => $optparamvalue ) {
        $optparamlabel = strtoupper( $optparamlabel );
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
 * @param mixed $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param array $params optional
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
 * @param string $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
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
 * @param string $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @since 2.4.8 - 2008-10-23
 * @param mixed $year optional
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param mixed $params optional
 * @return bool
 */
  function setCreated( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( !isset( $year )) {
      $year = date('Ymd\THis', mktime( date( 'H' ), date( 'i' ), date( 's' ) - date( 'Z'), date( 'm' ), date( 'd' ), date( 'Y' )));
    }
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
 * @param string $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param string $tz optional
 * @param array params optional
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
 * @since 2.14.1 - 2012-09-29
 * @return void
 */
  function _makeDtstamp() {
    $d    = date( 'Y-m-d-H-i-s', mktime( date('H'), date('i'), (date('s') - date( 'Z' )), date('m'), date('d'), date('Y')));
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
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
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
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param string $tz optional
 * @param array $params optional
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
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
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
 * @param mixed $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
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
 * @since 2.16.21 - 2013-06-23
 * @param array exdates
 * @param array $params, optional
 * @param integer $index, optional
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
          $strdate = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
 * @param array $exruleset
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param string $fbType
 * @param array $fbValues
 * @param array $params, optional
 * @param integer $index, optional
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
 * @since 2.12.6 - 2012-04-21
 * @return string
 */
  function createGeo() {
    if( empty( $this->geo )) return FALSE;
    if( empty( $this->geo['value'] ))
      return ( $this->getConfig( 'allowEmpty' )) ? $this->_createElement( 'GEO' ) : FALSE;
    $attributes = $this->_createParams( $this->geo['params'] );
    if( 0.0 < $this->geo['value']['latitude'] )
      $sign   = '+';
    else
      $sign   = ( 0.0 > $this->geo['value']['latitude'] ) ? '-' : '';
    $content  = $sign.sprintf( "%09.6f", abs( $this->geo['value']['latitude'] ));       // sprintf && lpad && float && sign !"#¤%&/(
    $content  = rtrim( rtrim( $content, '0' ), '.' );
    if( 0.0 < $this->geo['value']['longitude'] )
      $sign   = '+';
    else
      $sign   = ( 0.0 > $this->geo['value']['longitude'] ) ? '-' : '';
    $content .= ';'.$sign.sprintf( '%8.6f', abs( $this->geo['value']['longitude'] ));   // sprintf && lpad && float && sign !"#¤%&/(
    $content  = rtrim( rtrim( $content, '0' ), '.' );
    return $this->_createElement( 'GEO', $attributes, $content );
  }
/**
 * set calendar component property geo
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param float $latitude
 * @param float $longitude
 * @param array $params optional
 * @return bool
 */
  function setGeo( $latitude, $longitude, $params=FALSE ) {
    if( isset( $latitude ) && isset( $longitude )) {
      if( !is_array( $this->geo )) $this->geo = array();
      $this->geo['value']['latitude']  = (float) $latitude;
      $this->geo['value']['longitude'] = (float) $longitude;
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
 * @since 2.4.8 - 2008-10-23
 * @param mixed $year optional
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
 * @return boll
 */
  function setLastModified( $year=FALSE, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $params=FALSE ) {
    if( empty( $year ))
      $year = date('Ymd\THis', mktime( date( 'H' ), date( 'i' ), date( 's' ) - date( 'Z'), date( 'm' ), date( 'd' ), date( 'Y' )));
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
 * @param string $value
 * @param array params optional
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
 * @param string $value
 * @param array params optional
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
 * @param int $value
 * @param array $params optional
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
 * @param int $value
 * @param array $params optional
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
 * @since 2.16.21 - 2013-06-23
 * @param array $rdates
 * @param array $params, optional
 * @param integer $index, optional
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
                  $strdate = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
            $strdate = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $inputa['year'], $inputa['month'], $inputa['day'], $inputa['hour'], $inputa['min'], $inputa['sec'], $inputa['tz'] );
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
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param array $params optional
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
 * @param float $relid
 * @param array $params, optional
 * @param index $index, optional
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
 * @param string $value
 * @param array $params optional
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
 * @param float $statcode
 * @param string $text
 * @param string $extdata, optional
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param mixed $value
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param array $rruleset
 * @param array $params, optional
 * @param integer $index, optional
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
 * @param int $value optional
 * @param array $params optional
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
 * @param string $value
 * @param array $params optional
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
 * @param string $value
 * @param string $params optional
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
 * @param string $value
 * @param string $params optional
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
 * @param mixed $year
 * @param mixed $month optional
 * @param int $day optional
 * @param int $week optional
 * @param int $hour optional
 * @param int $min optional
 * @param int $sec optional
 * @param bool $relatedStart optional
 * @param bool $before optional
 * @param array $params optional
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
 * @param string $value
 * @param array $params optional
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
 * @param string $value
 * @param string $params, optional
 * @param integer $index, optional
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
 * @param string $value
 * @param string $params optional
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
 * @param string $value
 * @param string $params optional
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
 * @param string $value
 * @param string $params optional
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
 * @since 0.9.7 - 2006-11-20
 * @return string
 */
  function createUid() {
    if( 0 >= count( $this->uid ))
      $this->_makeuid();
    $attributes = $this->_createParams( $this->uid['params'] );
    return $this->_createElement( 'UID', $attributes, $this->uid['value'] );
  }
/**
 * create an unique id for this calendar component object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.7 - 2007-09-04
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
 * @since 2.4.8 - 2008-11-04
 * @param string $value
 * @param string $params optional
 * @return bool
 */
  function setUid( $value, $params=FALSE ) {
    if( empty( $value )) return FALSE; // no allowEmpty check here !!!!
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
 * @param string $value
 * @param string $params optional
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
/**
 * Property Name: x-prop
 */
/**
 * creates formatted output for calendar component property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @return string
 */
  function createXprop() {
    if( empty( $this->xprop )) return FALSE;
    $output = null;
    foreach( $this->xprop as $label => $xpropPart ) {
      if( !isset($xpropPart['value']) || ( empty( $xpropPart['value'] ) && !is_numeric( $xpropPart['value'] ))) {
        if( $this->getConfig( 'allowEmpty' )) $output .= $this->_createElement( $label );
        continue;
      }
      $attributes = $this->_createParams( $xpropPart['params'], array( 'LANGUAGE' ));
      if( is_array( $xpropPart['value'] )) {
        foreach( $xpropPart['value'] as $pix => $theXpart )
          $xpropPart['value'][$pix] = iCalUtilityFunctions::_strrep( $theXpart, $this->format, $this->format );
        $xpropPart['value']  = implode( ',', $xpropPart['value'] );
      }
      else
        $xpropPart['value'] = iCalUtilityFunctions::_strrep( $xpropPart['value'], $this->format, $this->nl );
      $output    .= $this->_createElement( $label, $attributes, $xpropPart['value'] );
    }
    return $output;
  }
/**
 * set calendar component property x-prop
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param string $label
 * @param mixed $value
 * @param array $params optional
 * @return bool
 */
  function setXprop( $label, $value, $params=FALSE ) {
    if( empty( $label ))
      return FALSE;
    if( 'X-' != strtoupper( substr( $label, 0, 2 )))
      return FALSE;
    if( empty( $value ) && !is_numeric( $value )) if( $this->getConfig( 'allowEmpty' )) $value = ''; else return FALSE;
    $xprop           = array( 'value' => $value );
    $xprop['params'] = iCalUtilityFunctions::_setParams( $params );
    if( !is_array( $this->xprop )) $this->xprop = array();
    $this->xprop[strtoupper( $label )] = $xprop;
    return TRUE;
  }
/*********************************************************************************/
/*********************************************************************************/
/**
 * create element format parts
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.0.6 - 2006-06-20
 * @return string
 */
  function _createFormat() {
    $objectname                   = null;
    switch( $this->format ) {
      case 'xcal':
        $objectname               = ( isset( $this->timezonetype )) ?
                                 strtolower( $this->timezonetype )  :  strtolower( $this->objName );
        $this->componentStart1    = $this->elementStart1 = '<';
        $this->componentStart2    = $this->elementStart2 = '>';
        $this->componentEnd1      = $this->elementEnd1   = '</';
        $this->componentEnd2      = $this->elementEnd2   = '>'.$this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = $this->nl;
        $this->valueInit          = null;
        break;
      default:
        $objectname               = ( isset( $this->timezonetype )) ?
                                 strtoupper( $this->timezonetype )  :  strtoupper( $this->objName );
        $this->componentStart1    = 'BEGIN:';
        $this->componentStart2    = null;
        $this->componentEnd1      = 'END:';
        $this->componentEnd2      = $this->nl;
        $this->elementStart1      = null;
        $this->elementStart2      = null;
        $this->elementEnd1        = null;
        $this->elementEnd2        = $this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = ';';
        $this->valueInit          = ':';
        break;
    }
    return $objectname;
  }
/**
 * creates formatted output for calendar component property
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.2 - 2012-12-18
 * @param string $label property name
 * @param string $attributes property attributes
 * @param string $content property content (optional)
 * @return string
 */
  function _createElement( $label, $attributes=null, $content=FALSE ) {
    switch( $this->format ) {
      case 'xcal':
        $label = strtolower( $label );
        break;
      default:
        $label = strtoupper( $label );
        break;
    }
    $output = $this->elementStart1.$label;
    $categoriesAttrLang = null;
    $attachInlineBinary = FALSE;
    $attachfmttype      = null;
    if (( 'xcal' == $this->format) && ( 'x-' == substr( $label, 0, 2 ))) {
      $this->xcaldecl[] = array( 'xmldecl'  => 'ELEMENT'
                               , 'ref'      => $label
                               , 'type2'    => '(#PCDATA)' );
    }
    if( !empty( $attributes ))  {
      $attributes  = trim( $attributes );
      if ( 'xcal' == $this->format ) {
        $attributes2 = explode( $this->intAttrDelimiter, $attributes );
        $attributes  = null;
        foreach( $attributes2 as $aix => $attribute ) {
          $attrKVarr = explode( '=', $attribute );
          if( empty( $attrKVarr[0] ))
            continue;
          if( !isset( $attrKVarr[1] )) {
            $attrValue = $attrKVarr[0];
            $attrKey   = $aix;
          }
          elseif( 2 == count( $attrKVarr)) {
            $attrKey   = strtolower( $attrKVarr[0] );
            $attrValue = $attrKVarr[1];
          }
          else {
            $attrKey   = strtolower( $attrKVarr[0] );
            unset( $attrKVarr[0] );
            $attrValue = implode( '=', $attrKVarr );
          }
          if(( 'attach' == $label ) && ( in_array( $attrKey, array( 'fmttype', 'encoding', 'value' )))) {
            $attachInlineBinary = TRUE;
            if( 'fmttype' == $attrKey )
              $attachfmttype = $attrKey.'='.$attrValue;
            continue;
          }
          elseif(( 'categories' == $label ) && ( 'language' == $attrKey ))
            $categoriesAttrLang = $attrKey.'='.$attrValue;
          else {
            $attributes .= ( empty( $attributes )) ? ' ' : $this->attributeDelimiter.' ';
            $attributes .= ( !empty( $attrKey )) ? $attrKey.'=' : null;
            if(( '"' == substr( $attrValue, 0, 1 )) && ( '"' == substr( $attrValue, -1 ))) {
              $attrValue = substr( $attrValue, 1, ( strlen( $attrValue ) - 2 ));
              $attrValue = str_replace( '"', '', $attrValue );
            }
            $attributes .= '"'.htmlspecialchars( $attrValue ).'"';
          }
        }
      }
      else {
        $attributes = str_replace( $this->intAttrDelimiter, $this->attributeDelimiter, $attributes );
      }
    }
    if(( 'xcal' == $this->format) &&
       ((( 'attach' == $label ) && !$attachInlineBinary ) || ( in_array( $label, array( 'tzurl', 'url' ))))) {
      $pos = strrpos($content, "/");
      $docname = ( $pos !== false) ? substr( $content, (1 - strlen( $content ) + $pos )) : $content;
      $this->xcaldecl[] = array( 'xmldecl'  => 'ENTITY'
                               , 'uri'      => $docname
                               , 'ref'      => 'SYSTEM'
                               , 'external' => $content
                               , 'type'     => 'NDATA'
                               , 'type2'    => 'BINERY' );
      $attributes .= ( empty( $attributes )) ? ' ' : $this->attributeDelimiter.' ';
      $attributes .= 'uri="'.$docname.'"';
      $content = null;
      if( 'attach' == $label ) {
        $attributes = str_replace( $this->attributeDelimiter, $this->intAttrDelimiter, $attributes );
        $content = $this->nl.$this->_createElement( 'extref', $attributes, null );
        $attributes = null;
      }
    }
    elseif(( 'xcal' == $this->format) && ( 'attach' == $label ) && $attachInlineBinary ) {
      $content = $this->nl.$this->_createElement( 'b64bin', $attachfmttype, $content ); // max one attribute
    }
    $output .= $attributes;
    if( !$content && ( '0' != $content )) {
      switch( $this->format ) {
        case 'xcal':
          $output .= ' /';
          $output .= $this->elementStart2.$this->nl;
          return $output;
          break;
        default:
          $output .= $this->elementStart2.$this->valueInit;
          return iCalUtilityFunctions::_size75( $output, $this->nl );
          break;
      }
    }
    $output .= $this->elementStart2;
    $output .= $this->valueInit.$content;
    switch( $this->format ) {
      case 'xcal':
        return $output.$this->elementEnd1.$label.$this->elementEnd2;
        break;
      default:
        return iCalUtilityFunctions::_size75( $output, $this->nl );
        break;
    }
  }
/**
 * creates formatted output for calendar component property parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.27 - 2012-01-16
 * @param array $params  optional
 * @param array $ctrKeys optional
 * @return string
 */
  function _createParams( $params=array(), $ctrKeys=array() ) {
    if( !is_array( $params ) || empty( $params ))
      $params = array();
    $attrLANG = $attr1 = $attr2 = $lang = null;
    $CNattrKey   = ( in_array( 'CN',       $ctrKeys )) ? TRUE : FALSE ;
    $LANGattrKey = ( in_array( 'LANGUAGE', $ctrKeys )) ? TRUE : FALSE ;
    $CNattrExist = $LANGattrExist = FALSE;
    $xparams = array();
    foreach( $params as $paramKey => $paramValue ) {
      if(( FALSE !== strpos( $paramValue, ':' )) ||
         ( FALSE !== strpos( $paramValue, ';' )) ||
         ( FALSE !== strpos( $paramValue, ',' )))
        $paramValue = '"'.$paramValue.'"';
      if( ctype_digit( (string) $paramKey )) {
        $xparams[]          = $paramValue;
        continue;
      }
      $paramKey             = strtoupper( $paramKey );
      if( !in_array( $paramKey, array( 'ALTREP', 'CN', 'DIR', 'ENCODING', 'FMTTYPE', 'LANGUAGE', 'RANGE', 'RELTYPE', 'SENT-BY', 'TZID', 'VALUE' )))
        $xparams[$paramKey] = $paramValue;
      else
        $params[$paramKey]  = $paramValue;
    }
    ksort( $xparams, SORT_STRING );
    foreach( $xparams as $paramKey => $paramValue ) {
      if( ctype_digit( (string) $paramKey ))
        $attr2             .= $this->intAttrDelimiter.$paramValue;
      else
        $attr2             .= $this->intAttrDelimiter."$paramKey=$paramValue";
    }
    if( isset( $params['FMTTYPE'] )  && !in_array( 'FMTTYPE', $ctrKeys )) {
      $attr1               .= $this->intAttrDelimiter.'FMTTYPE='.$params['FMTTYPE'].$attr2;
      $attr2                = null;
    }
    if( isset( $params['ENCODING'] ) && !in_array( 'ENCODING',   $ctrKeys )) {
      if( !empty( $attr2 )) {
        $attr1             .= $attr2;
        $attr2              = null;
      }
      $attr1               .= $this->intAttrDelimiter.'ENCODING='.$params['ENCODING'];
    }
    if( isset( $params['VALUE'] )    && !in_array( 'VALUE',   $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'VALUE='.$params['VALUE'];
    if( isset( $params['TZID'] )     && !in_array( 'TZID',    $ctrKeys )) {
      $attr1               .= $this->intAttrDelimiter.'TZID='.$params['TZID'];
    }
    if( isset( $params['RANGE'] )    && !in_array( 'RANGE',   $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'RANGE='.$params['RANGE'];
    if( isset( $params['RELTYPE'] )  && !in_array( 'RELTYPE', $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'RELTYPE='.$params['RELTYPE'];
    if( isset( $params['CN'] )       && $CNattrKey ) {
      $attr1                = $this->intAttrDelimiter.'CN='.$params['CN'];
      $CNattrExist          = TRUE;
    }
    if( isset( $params['DIR'] )      && in_array( 'DIR',      $ctrKeys )) {
      $delim = ( FALSE !== strpos( $params['DIR'], '"' )) ? '' : '"';
      $attr1               .= $this->intAttrDelimiter.'DIR='.$delim.$params['DIR'].$delim;
    }
    if( isset( $params['SENT-BY'] )  && in_array( 'SENT-BY',  $ctrKeys ))
      $attr1               .= $this->intAttrDelimiter.'SENT-BY='.$params['SENT-BY'];
    if( isset( $params['ALTREP'] )   && in_array( 'ALTREP',   $ctrKeys )) {
      $delim = ( FALSE !== strpos( $params['ALTREP'], '"' )) ? '' : '"';
      $attr1               .= $this->intAttrDelimiter.'ALTREP='.$delim.$params['ALTREP'].$delim;
    }
    if( isset( $params['LANGUAGE'] ) && $LANGattrKey ) {
      $attrLANG            .= $this->intAttrDelimiter.'LANGUAGE='.$params['LANGUAGE'];
      $LANGattrExist        = TRUE;
    }
    if( !$LANGattrExist ) {
      $lang = $this->getConfig( 'language' );
      if(( $CNattrExist || $LANGattrKey ) && $lang )
        $attrLANG .= $this->intAttrDelimiter.'LANGUAGE='.$lang;
    }
    return $attr1.$attrLANG.$attr2;
  }
/**
 * creates formatted output for calendar component property data value type recur
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.25 - 2013-06-30
 * @param array $recurlabel
 * @param array $recurdata
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
 * @since 2.9.6 - 2011-05-14
 * @param mixed $config
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
        if( !in_array( $this->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' ))) {
          if( empty( $this->uid['value'] ))   $this->_makeuid();
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
 * @since 2.10.18 - 2011-10-28
 * @param mixed  $config
 * @param string $value
 * @param bool   $softUpdate
 * @return void
 */
  function setConfig( $config, $value = FALSE, $softUpdate = FALSE ) {
    if( is_array( $config )) {
      $ak = array_keys( $config );
      foreach( $ak as $k ) {
        if( 'NEWLINECHAR' == strtoupper( $k )) {
          if( FALSE === $this->setConfig( 'NEWLINECHAR', $config[$k] ))
            return FALSE;
          unset( $config[$k] );
          break;
        }
      }
      foreach( $config as $cKey => $cValue ) {
        if( FALSE === $this->setConfig( $cKey, $cValue, $softUpdate ))
          return FALSE;
      }
      return TRUE;
    }
    $res = FALSE;
    switch( strtoupper( $config )) {
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
 * @param mixed $propName, bool FALSE => X-property
 * @param int   $propix, optional, if specific property is wanted in case of multiply occurences
 * @return bool, if successfull delete TRUE
 */
  function deleteProperty( $propName=FALSE, $propix=FALSE ) {
    if( $this->_notExistProp( $propName )) return FALSE;
    $propName = strtoupper( $propName );
    if( in_array( $propName, array( 'ATTACH',   'ATTENDEE', 'CATEGORIES', 'COMMENT',   'CONTACT', 'DESCRIPTION',    'EXDATE', 'EXRULE',
                                    'FREEBUSY', 'RDATE',    'RELATED-TO', 'RESOURCES', 'RRULE',   'REQUEST-STATUS', 'TZNAME', 'X-PROP'  ))) {
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
        if( in_array( $this->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' )))
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
        if( in_array( $this->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' )))
          return FALSE;
        if( !empty( $this->uid )) {
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
 * @param array $multiprop, reference to a component property
 * @param int   $propix, reference to removal counter
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
 * @since 2.16.21 - 2013-06-23
 * @param string $propName, optional
 * @param int @propix, optional, if specific property is wanted in case of multiply occurences
 * @param bool $inclParam=FALSE
 * @param bool $specform=FALSE
 * @return mixed
 */
  function getProperty( $propName=FALSE, $propix=FALSE, $inclParam=FALSE, $specform=FALSE ) {
    if( 'GEOLOCATION' == strtoupper( $propName )) {
      $content = $this->getProperty( 'LOCATION' );
      $content = ( !empty( $content )) ? $content.' ' : '';
      if(( FALSE === ( $geo     = $this->getProperty( 'GEO' ))) || empty( $geo ))
        return FALSE;
      if( 0.0 < $geo['latitude'] )
        $sign   = '+';
      else
        $sign   = ( 0.0 > $geo['latitude'] ) ? '-' : '';
      $content .= $sign.sprintf( "%09.6f", abs( $geo['latitude'] ));   // sprintf && lpad && float && sign !"#¤%&/(
      $content  = rtrim( rtrim( $content, '0' ), '.' );
      if( 0.0 < $geo['longitude'] )
        $sign   = '+';
      else
       $sign   = ( 0.0 > $geo['longitude'] ) ? '-' : '';
      return $content.$sign.sprintf( '%8.6f', abs( $geo['longitude'] )).'/';   // sprintf && lpad && float && sign !"#¤%&/(
    }
    if( $this->_notExistProp( $propName )) return FALSE;
    $propName = ( $propName ) ? strtoupper( $propName ) : 'X-PROP';
    if( in_array( $propName, array( 'ATTACH',   'ATTENDEE', 'CATEGORIES', 'COMMENT',   'CONTACT', 'DESCRIPTION',    'EXDATE', 'EXRULE',
                                    'FREEBUSY', 'RDATE',    'RELATED-TO', 'RESOURCES', 'RRULE',   'REQUEST-STATUS', 'TZNAME', 'X-PROP'  ))) {
      if( !$propix )
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
        if( in_array( $this->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' )))
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
        $value = ( $specform && isset( $this->dtstart['value'] ) && isset( $this->duration['value'] )) ? iCalUtilityFunctions::_duration2date( $this->dtstart['value'], $this->duration['value'] ) : $this->duration['value'];
        return ( $inclParam ) ? array( 'value' => $value, 'params' =>  $this->duration['params'] ) : $value;
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
        if( in_array( $this->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' )))
          return FALSE;
        if( empty( $this->uid['value'] ))
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
 * @since 2.13.4 - 2012-08-07
 * @param string $propName
 * @param array  $output, incremented result array
 */
  function _getProperties( $propName, & $output ) {
    if( empty( $output ))
      $output = array();
    if( !in_array( strtoupper( $propName ), array( 'ATTENDEE', 'CATEGORIES', 'CONTACT', 'RELATED-TO', 'RESOURCES' )))
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
 * @since 2.16.26 - 2013-07-02
 * @param mixed $unparsedtext, optional, strict rfc2445 formatted, single property string or array of strings
 * @return bool FALSE if error occurs during parsing
 *
 */
  function parse( $unparsedtext=null ) {
    $nl = $this->getConfig( 'nl' );
    if( !empty( $unparsedtext )) {
      if( is_array( $unparsedtext ))
        $unparsedtext = implode( '\n'.$nl, $unparsedtext );
      $unparsedtext = explode( $nl, iCalUtilityFunctions::convEolChar( $unparsedtext, $nl ));
    }
    elseif( !isset( $this->unparsed ))
      $unparsedtext = array();
    else
      $unparsedtext = $this->unparsed;
            /* skip leading (empty/invalid) lines */
    foreach( $unparsedtext as $lix => $line ) {
      $tst = trim( $line );
      if(( '\n' == $tst ) || empty( $tst ))
        unset( $unparsedtext[$lix] );
      else
        break;
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
    $paramMStz   = array( 'utc-', 'utc+', 'gmt-', 'gmt+' );
    $paramProto3 = array( 'fax:', 'cid:', 'sms:', 'tel:', 'urn:' );
    $paramProto4 = array( 'crid:', 'news:', 'pres:' );
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
      $attr         = array();
      $attrix       = -1;
      $clen         = strlen( $line );
      $WithinQuotes = FALSE;
      $cix          = 0;
      while( FALSE !== substr( $line, $cix, 1 )) {
        if(                       (  ':' == $line[$cix] )                         &&
                                  ( substr( $line,$cix,     3 )  != '://' )       &&
           ( !in_array( strtolower( substr( $line,$cix - 6, 4 )), $paramMStz ))   &&
           ( !in_array( strtolower( substr( $line,$cix - 3, 4 )), $paramProto3 )) &&
           ( !in_array( strtolower( substr( $line,$cix - 4, 5 )), $paramProto4 )) &&
                      ( strtolower( substr( $line,$cix - 6, 7 )) != 'mailto:' )   &&
             !$WithinQuotes ) {
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
          $WithinQuotes = ( FALSE === $WithinQuotes ) ? TRUE : FALSE;
        if( ';' == $line[$cix] )
          $attr[++$attrix] = null;
        else
          $attr[$attrix] .= $line[$cix];
        $cix++;
      }
            /* make attributes in array format */
      $propattr = array();
      foreach( $attr as $attribute ) {
        $attrsplit = explode( '=', $attribute, 2 );
        if( 1 < count( $attrsplit ))
          $propattr[$attrsplit[0]] = $attrsplit[1];
        else
          $propattr[] = $attribute;
      }
            /* call setProperty( $propname.. . */
      switch( strtoupper( $propname )) {
        case 'ATTENDEE':
          foreach( $propattr as $pix => $attr ) {
            if( !in_array( strtoupper( $pix ), array( 'MEMBER', 'DELEGATED-TO', 'DELEGATED-FROM' )))
              continue;
            $attr2 = explode( ',', $attr );
              if( 1 < count( $attr2 ))
                $propattr[$pix] = $attr2;
          }
          $this->setProperty( $propname, $line, $propattr );
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
              $this->setProperty( $propname, $content, $propattr );
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
            $propattr = null;
          $this->setProperty( $propname, iCalUtilityFunctions::_strunrep( $line ), $propattr );
          break;
        case 'REQUEST-STATUS':
          $values    = explode( ';', $line, 3 );
          $values[1] = ( !isset( $values[1] )) ? null : iCalUtilityFunctions::_strunrep( $values[1] );
          $values[2] = ( !isset( $values[2] )) ? null : iCalUtilityFunctions::_strunrep( $values[2] );
          $this->setProperty( $propname
                            , $values[0]  // statcode
                            , $values[1]  // statdesc
                            , $values[2]  // extdata
                            , $propattr );
          break;
        case 'FREEBUSY':
          $fbtype = ( isset( $propattr['FBTYPE'] )) ? $propattr['FBTYPE'] : ''; // force setting default, if missing
          unset( $propattr['FBTYPE'] );
          $values = explode( ',', $line );
          foreach( $values as $vix => $value ) {
            $value2 = explode( '/', $value );
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propname, $fbtype, $values, $propattr );
          break;
        case 'GEO':
          $value = explode( ';', $line, 2 );
          if( 2 > count( $value ))
            $value[1] = null;
          $this->setProperty( $propname, $value[0], $value[1], $propattr );
          break;
        case 'EXDATE':
          $values = ( !empty( $line )) ? explode( ',', $line ) : null;
          $this->setProperty( $propname, $values, $propattr );
          break;
        case 'RDATE':
          if( empty( $line )) {
            $this->setProperty( $propname, $line, $propattr );
            break;
          }
          $values = explode( ',', $line );
          foreach( $values as $vix => $value ) {
            $value2 = explode( '/', $value );
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propname, $values, $propattr );
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
          $this->setProperty( $propname, $recur, $propattr );
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
          $this->setProperty( $propname, $line, $propattr );
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
 * @param mixed $arg1 ordno / component type / component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
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
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
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
 * @param string $compType subcomponent type
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
 * @since 2.8.8 - 2011-03-15
 * @param object $component calendar component
 * @param mixed $arg1 optional, ordno/component type/ component uid
 * @param mixed $arg2 optional, ordno if arg1 = component type
 * @return bool
 */
  function setComponent( $component, $arg1=FALSE, $arg2=FALSE  ) {
    if( !isset( $this->components )) return FALSE;
    $component->setConfig( $this->getConfig(), FALSE, TRUE );
    if( !in_array( $component->objName, array( 'valarm', 'vtimezone', 'standard', 'daylight' ))) {
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
    elseif( in_array( strtolower( $arg1 ), array( 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone' ))) {
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
 * @since 2.11.20 - 2012-02-06
 * @param array $xcaldecl
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
        $key = sprintf( '%04d%02d%02d%02d%02d%02d000', $dt['year'], $dt['month'], $dt['day'], $dt['hour'], $dt['min'], $dt['sec'] );
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
/*********************************************************************************/
/*********************************************************************************/
/**
 * class for calendar component VEVENT
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vevent extends calendarComponent {
  var $attach;
  var $attendee;
  var $categories;
  var $comment;
  var $contact;
  var $class;
  var $created;
  var $description;
  var $dtend;
  var $dtstart;
  var $duration;
  var $exdate;
  var $exrule;
  var $geo;
  var $lastmodified;
  var $location;
  var $organizer;
  var $priority;
  var $rdate;
  var $recurrenceid;
  var $relatedto;
  var $requeststatus;
  var $resources;
  var $rrule;
  var $sequence;
  var $status;
  var $summary;
  var $transp;
  var $url;
  var $xprop;
            //  component subcomponents container
  var $components;
/**
 * constructor for calendar component VEVENT object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param  array $config
 * @return void
 */
  function vevent( $config = array()) {
    $this->calendarComponent();

    $this->attach          = '';
    $this->attendee        = '';
    $this->categories      = '';
    $this->class           = '';
    $this->comment         = '';
    $this->contact         = '';
    $this->created         = '';
    $this->description     = '';
    $this->dtstart         = '';
    $this->dtend           = '';
    $this->duration        = '';
    $this->exdate          = '';
    $this->exrule          = '';
    $this->geo             = '';
    $this->lastmodified    = '';
    $this->location        = '';
    $this->organizer       = '';
    $this->priority        = '';
    $this->rdate           = '';
    $this->recurrenceid    = '';
    $this->relatedto       = '';
    $this->requeststatus   = '';
    $this->resources       = '';
    $this->rrule           = '';
    $this->sequence        = '';
    $this->status          = '';
    $this->summary         = '';
    $this->transp          = '';
    $this->url             = '';
    $this->xprop           = '';

    $this->components      = array();

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VEVENT object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.16 - 2011-10-28
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname    = $this->_createFormat();
    $component     = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component    .= $this->createUid();
    $component    .= $this->createDtstamp();
    $component    .= $this->createAttach();
    $component    .= $this->createAttendee();
    $component    .= $this->createCategories();
    $component    .= $this->createComment();
    $component    .= $this->createContact();
    $component    .= $this->createClass();
    $component    .= $this->createCreated();
    $component    .= $this->createDescription();
    $component    .= $this->createDtstart();
    $component    .= $this->createDtend();
    $component    .= $this->createDuration();
    $component    .= $this->createExdate();
    $component    .= $this->createExrule();
    $component    .= $this->createGeo();
    $component    .= $this->createLastModified();
    $component    .= $this->createLocation();
    $component    .= $this->createOrganizer();
    $component    .= $this->createPriority();
    $component    .= $this->createRdate();
    $component    .= $this->createRrule();
    $component    .= $this->createRelatedTo();
    $component    .= $this->createRequestStatus();
    $component    .= $this->createRecurrenceid();
    $component    .= $this->createResources();
    $component    .= $this->createSequence();
    $component    .= $this->createStatus();
    $component    .= $this->createSummary();
    $component    .= $this->createTransp();
    $component    .= $this->createUrl();
    $component    .= $this->createXprop();
    $component    .= $this->createSubComponent();
    $component    .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 * class for calendar component VTODO
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vtodo extends calendarComponent {
  var $attach;
  var $attendee;
  var $categories;
  var $comment;
  var $completed;
  var $contact;
  var $class;
  var $created;
  var $description;
  var $dtstart;
  var $due;
  var $duration;
  var $exdate;
  var $exrule;
  var $geo;
  var $lastmodified;
  var $location;
  var $organizer;
  var $percentcomplete;
  var $priority;
  var $rdate;
  var $recurrenceid;
  var $relatedto;
  var $requeststatus;
  var $resources;
  var $rrule;
  var $sequence;
  var $status;
  var $summary;
  var $url;
  var $xprop;
            //  component subcomponents container
  var $components;
/**
 * constructor for calendar component VTODO object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @return void
 */
  function vtodo( $config = array()) {
    $this->calendarComponent();

    $this->attach          = '';
    $this->attendee        = '';
    $this->categories      = '';
    $this->class           = '';
    $this->comment         = '';
    $this->completed       = '';
    $this->contact         = '';
    $this->created         = '';
    $this->description     = '';
    $this->dtstart         = '';
    $this->due             = '';
    $this->duration        = '';
    $this->exdate          = '';
    $this->exrule          = '';
    $this->geo             = '';
    $this->lastmodified    = '';
    $this->location        = '';
    $this->organizer       = '';
    $this->percentcomplete = '';
    $this->priority        = '';
    $this->rdate           = '';
    $this->recurrenceid    = '';
    $this->relatedto       = '';
    $this->requeststatus   = '';
    $this->resources       = '';
    $this->rrule           = '';
    $this->sequence        = '';
    $this->status          = '';
    $this->summary         = '';
    $this->url             = '';
    $this->xprop           = '';

    $this->components      = array();

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VTODO object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-11-07
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname    = $this->_createFormat();
    $component     = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component    .= $this->createUid();
    $component    .= $this->createDtstamp();
    $component    .= $this->createAttach();
    $component    .= $this->createAttendee();
    $component    .= $this->createCategories();
    $component    .= $this->createClass();
    $component    .= $this->createComment();
    $component    .= $this->createCompleted();
    $component    .= $this->createContact();
    $component    .= $this->createCreated();
    $component    .= $this->createDescription();
    $component    .= $this->createDtstart();
    $component    .= $this->createDue();
    $component    .= $this->createDuration();
    $component    .= $this->createExdate();
    $component    .= $this->createExrule();
    $component    .= $this->createGeo();
    $component    .= $this->createLastModified();
    $component    .= $this->createLocation();
    $component    .= $this->createOrganizer();
    $component    .= $this->createPercentComplete();
    $component    .= $this->createPriority();
    $component    .= $this->createRdate();
    $component    .= $this->createRelatedTo();
    $component    .= $this->createRequestStatus();
    $component    .= $this->createRecurrenceid();
    $component    .= $this->createResources();
    $component    .= $this->createRrule();
    $component    .= $this->createSequence();
    $component    .= $this->createStatus();
    $component    .= $this->createSummary();
    $component    .= $this->createUrl();
    $component    .= $this->createXprop();
    $component    .= $this->createSubComponent();
    $component    .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 * class for calendar component VJOURNAL
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vjournal extends calendarComponent {
  var $attach;
  var $attendee;
  var $categories;
  var $comment;
  var $contact;
  var $class;
  var $created;
  var $description;
  var $dtstart;
  var $exdate;
  var $exrule;
  var $lastmodified;
  var $organizer;
  var $rdate;
  var $recurrenceid;
  var $relatedto;
  var $requeststatus;
  var $rrule;
  var $sequence;
  var $status;
  var $summary;
  var $url;
  var $xprop;
/**
 * constructor for calendar component VJOURNAL object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @return void
 */
  function vjournal( $config = array()) {
    $this->calendarComponent();

    $this->attach          = '';
    $this->attendee        = '';
    $this->categories      = '';
    $this->class           = '';
    $this->comment         = '';
    $this->contact         = '';
    $this->created         = '';
    $this->description     = '';
    $this->dtstart         = '';
    $this->exdate          = '';
    $this->exrule          = '';
    $this->lastmodified    = '';
    $this->organizer       = '';
    $this->rdate           = '';
    $this->recurrenceid    = '';
    $this->relatedto       = '';
    $this->requeststatus   = '';
    $this->rrule           = '';
    $this->sequence        = '';
    $this->status          = '';
    $this->summary         = '';
    $this->url             = '';
    $this->xprop           = '';

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VJOURNAL object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname = $this->_createFormat();
    $component  = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component .= $this->createUid();
    $component .= $this->createDtstamp();
    $component .= $this->createAttach();
    $component .= $this->createAttendee();
    $component .= $this->createCategories();
    $component .= $this->createClass();
    $component .= $this->createComment();
    $component .= $this->createContact();
    $component .= $this->createCreated();
    $component .= $this->createDescription();
    $component .= $this->createDtstart();
    $component .= $this->createExdate();
    $component .= $this->createExrule();
    $component .= $this->createLastModified();
    $component .= $this->createOrganizer();
    $component .= $this->createRdate();
    $component .= $this->createRequestStatus();
    $component .= $this->createRecurrenceid();
    $component .= $this->createRelatedTo();
    $component .= $this->createRrule();
    $component .= $this->createSequence();
    $component .= $this->createStatus();
    $component .= $this->createSummary();
    $component .= $this->createUrl();
    $component .= $this->createXprop();
    $component .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 * class for calendar component VFREEBUSY
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vfreebusy extends calendarComponent {
  var $attendee;
  var $comment;
  var $contact;
  var $dtend;
  var $dtstart;
  var $duration;
  var $freebusy;
  var $organizer;
  var $requeststatus;
  var $url;
  var $xprop;
            //  component subcomponents container
  var $components;
/**
 * constructor for calendar component VFREEBUSY object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @return void
 */
  function vfreebusy( $config = array()) {
    $this->calendarComponent();

    $this->attendee        = '';
    $this->comment         = '';
    $this->contact         = '';
    $this->dtend           = '';
    $this->dtstart         = '';
    $this->duration        = '';
    $this->freebusy        = '';
    $this->organizer       = '';
    $this->requeststatus   = '';
    $this->url             = '';
    $this->xprop           = '';

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VFREEBUSY object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.3.1 - 2007-11-19
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname = $this->_createFormat();
    $component  = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component .= $this->createUid();
    $component .= $this->createDtstamp();
    $component .= $this->createAttendee();
    $component .= $this->createComment();
    $component .= $this->createContact();
    $component .= $this->createDtstart();
    $component .= $this->createDtend();
    $component .= $this->createDuration();
    $component .= $this->createFreebusy();
    $component .= $this->createOrganizer();
    $component .= $this->createRequestStatus();
    $component .= $this->createUrl();
    $component .= $this->createXprop();
    $component .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 * class for calendar component VALARM
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class valarm extends calendarComponent {
  var $action;
  var $attach;
  var $attendee;
  var $description;
  var $duration;
  var $repeat;
  var $summary;
  var $trigger;
  var $xprop;
/**
 * constructor for calendar component VALARM object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @return void
 */
  function valarm( $config = array()) {
    $this->calendarComponent();

    $this->action          = '';
    $this->attach          = '';
    $this->attendee        = '';
    $this->description     = '';
    $this->duration        = '';
    $this->repeat          = '';
    $this->summary         = '';
    $this->trigger         = '';
    $this->xprop           = '';

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VALARM object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-22
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname    = $this->_createFormat();
    $component     = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component    .= $this->createAction();
    $component    .= $this->createAttach();
    $component    .= $this->createAttendee();
    $component    .= $this->createDescription();
    $component    .= $this->createDuration();
    $component    .= $this->createRepeat();
    $component    .= $this->createSummary();
    $component    .= $this->createTrigger();
    $component    .= $this->createXprop();
    $component    .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/**********************************************************************************
/*********************************************************************************/
/**
 * class for calendar component VTIMEZONE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vtimezone extends calendarComponent {
  var $timezonetype;

  var $comment;
  var $dtstart;
  var $lastmodified;
  var $rdate;
  var $rrule;
  var $tzid;
  var $tzname;
  var $tzoffsetfrom;
  var $tzoffsetto;
  var $tzurl;
  var $xprop;
            //  component subcomponents container
  var $components;
/**
 * constructor for calendar component VTIMEZONE object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param mixed $timezonetype optional, default FALSE ( STANDARD / DAYLIGHT )
 * @param array $config
 * @return void
 */
  function vtimezone( $timezonetype=FALSE, $config = array()) {
    if( is_array( $timezonetype )) {
      $config       = $timezonetype;
      $timezonetype = FALSE;
    }
    if( !$timezonetype )
      $this->timezonetype = 'VTIMEZONE';
    else
      $this->timezonetype = strtoupper( $timezonetype );
    $this->calendarComponent();

    $this->comment         = '';
    $this->dtstart         = '';
    $this->lastmodified    = '';
    $this->rdate           = '';
    $this->rrule           = '';
    $this->tzid            = '';
    $this->tzname          = '';
    $this->tzoffsetfrom    = '';
    $this->tzoffsetto      = '';
    $this->tzurl           = '';
    $this->xprop           = '';

    $this->components      = array();

    if( defined( 'ICAL_LANG' ) && !isset( $config['language'] ))
                                          $config['language']   = ICAL_LANG;
    if( !isset( $config['allowEmpty'] ))  $config['allowEmpty'] = TRUE;
    if( !isset( $config['nl'] ))          $config['nl']         = "\r\n";
    if( !isset( $config['format'] ))      $config['format']     = 'iCal';
    if( !isset( $config['delimiter'] ))   $config['delimiter']  = DIRECTORY_SEPARATOR;
    $this->setConfig( $config );

  }
/**
 * create formatted output for calendar component VTIMEZONE object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-25
 * @param array $xcaldecl
 * @return string
 */
  function createComponent( &$xcaldecl ) {
    $objectname    = $this->_createFormat();
    $component     = $this->componentStart1.$objectname.$this->componentStart2.$this->nl;
    $component    .= $this->createTzid();
    $component    .= $this->createLastModified();
    $component    .= $this->createTzurl();
    $component    .= $this->createDtstart();
    $component    .= $this->createTzoffsetfrom();
    $component    .= $this->createTzoffsetto();
    $component    .= $this->createComment();
    $component    .= $this->createRdate();
    $component    .= $this->createRrule();
    $component    .= $this->createTzname();
    $component    .= $this->createXprop();
    $component    .= $this->createSubComponent();
    $component    .= $this->componentEnd1.$objectname.$this->componentEnd2;
    if( is_array( $this->xcaldecl ) && ( 0 < count( $this->xcaldecl ))) {
      foreach( $this->xcaldecl as $localxcaldecl )
        $xcaldecl[] = $localxcaldecl;
    }
    return $component;
  }
}
/*********************************************************************************/
/*********************************************************************************/
/**
 * moving all utility (static) functions to a utility class
 * 20111223 - move iCalUtilityFunctions class to the end of the iCalcreator class file
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.10.1 - 2011-07-16
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
 * ensures internal date-time/date format (keyed array) for an input date-time/date array (keyed or unkeyed)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.24 - 2013-06-26
 * @param array $datetime
 * @param int $parno optional, default FALSE
 * @return array
 */
  public static function _date_time_array( $datetime, $parno=FALSE ) {
    return iCalUtilityFunctions::_chkDateArr( $datetime, $parno );
  }
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
 * @param array $date, date to check
 * @param int $parno, no of date parts (i.e. year, month.. .)
 * @param array $params, property parameters
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
 * @since 2.12.17 - 2012-07-12
 * @param string $text
 * @param string $nl
 * @return string
 */
  public static function convEolChar( & $text, $nl ) {
    $outp = '';
    $cix  = 0;
    while(    isset(   $text[$cix] )) {
      if(     isset(   $text[$cix + 2] ) &&  ( "\r" == $text[$cix] ) && ( "\n" == $text[$cix + 1] ) &&
        ((    " " ==   $text[$cix + 2] ) ||  ( "\t" == $text[$cix + 2] )))                    // 2 pos eolchar + ' ' or '\t'
        $cix  += 2;                                                                           // skip 3
      elseif( isset(   $text[$cix + 1] ) &&  ( "\r" == $text[$cix] ) && ( "\n" == $text[$cix + 1] )) {
        $outp .= $nl;                                                                         // 2 pos eolchar
        $cix  += 1;                                                                           // replace with $nl
      }
      elseif( isset(   $text[$cix + 1] ) && (( "\r" == $text[$cix] ) || ( "\n" == $text[$cix] )) &&
           (( " " ==   $text[$cix + 1] ) ||  ( "\t" == $text[$cix + 1] )))                     // 1 pos eolchar + ' ' or '\t'
        $cix  += 1;                                                                            // skip 2
      elseif(( "\r" == $text[$cix] )     ||  ( "\n" == $text[$cix] ))                          // 1 pos eolchar
        $outp .= $nl;                                                                          // replace with $nl
      else
        $outp .= $text[$cix];                                                                  // add any other byte
      $cix    += 1;
    }
    return $outp;
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
 * @since 2.16.1 - 2012-11-26
 * Generates components for all transitions in a date range, based on contribution by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * Additional changes jpirkey
 * @param object $calendar, reference to an iCalcreator calendar instance
 * @param string $timezone, a PHP5 (DateTimeZone) valid timezone
 * @param array  $xProp,    *[x-propName => x-propValue], optional
 * @param int    $from      a unix timestamp
 * @param int    $to        a unix timestamp
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
    if( empty( $to )) {
      $dates             = array_keys( $calendar->getProperty( 'dtstart' ));
      if( empty( $dates ))
        $dates           = array( date( 'Ymd' ));
    }
    if( !empty( $from ))
      $dateFrom          = new DateTime( "@$from" );             // set lowest date (UTC)
    else {
      $from              = reset( $dates );                      // set lowest date to the lowest dtstart date
      $dateFrom          = new DateTime( $from.'T000000', $dtz );
      $dateFrom->modify( '-1 month' );                           // set $dateFrom to one month before the lowest date
      $dateFrom->setTimezone( $utcTz );                          // convert local date to UTC
    }
    $dateFromYmd         = $dateFrom->format('Y-m-d' );
    if( !empty( $to ))
      $dateTo            = new DateTime( "@$to" );               // set end date (UTC)
    else {
      $to                = end( $dates );                        // set highest date to the highest dtstart date
      $dateTo            = new DateTime( $to.'T235959', $dtz );
      $dateTo->modify( '+1 year' );                              // set $dateTo to one year after the highest date
      $dateTo->setTimezone( $utcTz );                            // convert local date to UTC
    }
    $dateToYmd           = $dateTo->format('Y-m-d' );
    unset( $dtz );
    $transTemp           = array();
    $prevOffsetfrom      = 0;
    $stdIx  = $dlghtIx   = null;
    $prevTrans           = FALSE;
    foreach( $transitions as $tix => $trans ) {                  // all transitions in date-time order!!
      $date              = new DateTime( "@{$trans['ts']}" );    // set transition date (UTC)
      $transDateYmd      = $date->format('Y-m-d' );
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
        $d = $date->format( 'Y-n-j-G-i-s' );                     // set date to array to ease up dtstart and (opt) rdate setting
        $d = explode( '-', $d );
        $trans['time']   = array( 'year' => $d[0], 'month' => $d[1], 'day' => $d[2], 'hour' => $d[3], 'min' => $d[4], 'sec' => $d[5] );
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
    $tz  = & $calendar->newComponent( 'vtimezone' );
    $tz->setproperty( 'tzid', $timezone );
    if( !empty( $xProp )) {
      foreach( $xProp as $xPropName => $xPropValue )
        if( 'x-' == strtolower( substr( $xPropName, 0, 2 )))
          $tz->setproperty( $xPropName, $xPropValue );
    }
    if( empty( $transTemp )) {      // if no match found
      if( $prevTrans ) {            // then we use the last transition (before startdate) for the tz info
        $date = new DateTime( "@{$prevTrans['ts']}" );           // set transition date (UTC)
        $date->modify( $prevTrans['offsetfrom'].'seconds' );     // convert utc date to local date
        $d = $date->format( 'Y-n-j-G-i-s' );                     // set date to array to ease up dtstart setting
        $d = explode( '-', $d );
        $prevTrans['time'] = array( 'year' => $d[0], 'month' => $d[1], 'day' => $d[2], 'hour' => $d[3], 'min' => $d[4], 'sec' => $d[5] );
        $transTemp[0] = $prevTrans;
      }
      else {                        // or we use the timezone identifier to BUILD the standard tz info (?)
        $date = new DateTime( 'now', new DateTimeZone( $timezone ));
        $transTemp[0] = array( 'time'       => $date->format( 'Y-m-d\TH:i:s O' )
                             , 'offset'     => $date->format( 'Z' )
                             , 'offsetfrom' => $date->format( 'Z' )
                             , 'isdst'      => FALSE );
      }
    }
    unset( $transitions, $date, $prevTrans );
    foreach( $transTemp as $tix => $trans ) {
      $type  = ( TRUE !== $trans['isdst'] ) ? 'standard' : 'daylight';
      $scomp = & $tz->newComponent( $type );
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
 * @since 2.14.1 - 2012-09-17
 * @param array   $datetime
 * @param int     $parno, optional, default 6
 * @return string
 */
  public static function _format_date_time( $datetime, $parno=6 ) {
    return iCalUtilityFunctions::_date2strdate( $datetime, $parno );
  }
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
      if( 'tz' != $dkey ) $dvalue = (integer) $dvalue;
    $output = sprintf( '%04d%02d%02d', $datetime['year'], $datetime['month'], $datetime['day'] );
    if( 3 == $parno )
      return $output;
    if( !isset( $datetime['hour'] )) $datetime['hour'] = 0;
    if( !isset( $datetime['min'] ))  $datetime['min']  = 0;
    if( !isset( $datetime['sec'] ))  $datetime['sec']  = 0;
    $output    .= sprintf( 'T%02d%02d%02d', $datetime['hour'], $datetime['min'], $datetime['sec'] );
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
 * convert a date/datetime (array) to timestamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-29
 * @param array  $datetime  datetime(/date)
 * @param string $wtz       timezone
 * @return int
 */
  public static function _date2timestamp( $datetime, $wtz=null ) {
    if( !isset( $datetime['hour'] )) $datetime['hour'] = 0;
    if( !isset( $datetime['min'] ))  $datetime['min']  = 0;
    if( !isset( $datetime['sec'] ))  $datetime['sec']  = 0;
    if( empty( $wtz ) && ( !isset( $datetime['tz'] ) || empty(  $datetime['tz'] )))
      return mktime( $datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year'] );
    $output = $offset = 0;
    if( empty( $wtz )) {
      if( iCalUtilityFunctions::_isOffset( $datetime['tz'] )) {
        $offset = iCalUtilityFunctions::_tz2offset( $datetime['tz'] ) * -1;
        $wtz    = 'UTC';
      }
      else
        $wtz    = $datetime['tz'];
    }
    if(( 'Z' == $wtz ) || ( 'GMT' == strtoupper( $wtz )))
      $wtz      = 'UTC';
    try {
      $strdate  = sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $datetime['year'], $datetime['month'], $datetime['day'], $datetime['hour'], $datetime['min'], $datetime['sec'] );
      $d        = new DateTime( $strdate, new DateTimeZone( $wtz ));
      if( 0    != $offset )  // adjust for offset
        $d->modify( $offset.' seconds' );
      $output   = $d->format( 'U' );
      unset( $d );
    }
    catch( Exception $e ) {
      $output = mktime( $datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year'] );
    }
    return $output;
  }
/**
 * ensures internal duration format for input in array format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.23 - 2013-06-23
 * @param array $duration
 * @return array
 */
  public static function _duration_array( $duration ) {
    return iCalUtilityFunctions::_duration2arr( $duration );
  }
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
    $seconds        =            ( $seconds % ( 60 * 60 * 24 * 7 ));
    $output['day']  = (int) floor( $seconds / ( 60 * 60 * 24 ));
    $seconds        =            ( $seconds % ( 60 * 60 * 24 ));
    $output['hour'] = (int) floor( $seconds / ( 60 * 60 ));
    $seconds        =            ( $seconds % ( 60 * 60 ));
    $output['min']  = (int) floor( $seconds /   60 );
    $output['sec']  =            ( $seconds %   60 );
    if( !empty( $output['week'] ))
      unset( $output['day'], $output['hour'], $output['min'], $output['sec'] );
    else {
      unset( $output['week'] );
      if( empty( $output['day'] ))
        unset( $output['day'] );
      if(( 0 == $output['hour'] ) && ( 0 == $output['min'] ) && ( 0 == $output['sec'] ))
        unset( $output['hour'], $output['min'], $output['sec'] );
    }
    return $output;
  }
/**
 * convert startdate+duration to a array format datetime
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.12 - 2012-10-31
 * @param array   $startdate
 * @param array   $duration
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
    $date     = date( 'Y-m-d-H-i-s', mktime((int) $startdate['hour'], (int) $startdate['min'], (int) ( $startdate['sec'] + $dtend ), (int) $startdate['month'], (int) $startdate['day'], (int) $startdate['year'] ));
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
 * @return array
 */
  public static function _duration_string( $duration ) {
    return iCalUtilityFunctions::_durationStr2arr( $duration );
  }
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
  public static function _format_duration( $duration ) {
    return iCalUtilityFunctions::_duration2str( $duration );
  }
  public static function _duration2str( $duration ) {
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
 * checks if input contains a (array formatted) date/time
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.24 - 2013-07-02
 * @param array $input
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
 * @param string $timezone, input/output variable reference
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
 * @since 2.10.19 - 2011-10-31
 * @param array $result, array to update, array([timestamp] => timestamp)
 * @param array $recur, pattern for recurrency (only value part, params ignored)
 * @param array $wdate, component start date
 * @param array $startdate, start date
 * @param array $enddate, optional
 * @return void
 * @todo BYHOUR, BYMINUTE, BYSECOND, WEEKLY at year end/start
 */
  public static function _recur2date( & $result, $recur, $wdate, $startdate, $enddate=FALSE ) {
    foreach( $wdate as $k => $v ) if( ctype_digit( $v )) $wdate[$k] = (int) $v;
    $wdateStart  = $wdate;
    $wdatets     = iCalUtilityFunctions::_date2timestamp( $wdate );
    $startdatets = iCalUtilityFunctions::_date2timestamp( $startdate );
    if( !$enddate ) {
      $enddate = $startdate;
      $enddate['year'] += 1;
    }
// echo "recur __in_ comp start ".implode('-',$wdate)." period start ".implode('-',$startdate)." period end ".implode('-',$enddate)."<br>\n";print_r($recur);echo "<br>\n";//test###
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
// echo "recur out of date ".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
      return array(); // nothing to do.. .
    }
    if( !isset( $recur['FREQ'] )) // "MUST be specified.. ."
      $recur['FREQ'] = 'DAILY'; // ??
    $wkst = ( isset( $recur['WKST'] ) && ( 'SU' == $recur['WKST'] )) ? 24*60*60 : 0; // ??
    $weekStart = (int) date( 'W', ( $wdatets + $wkst ));
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
// echo "bysetposXold_start=$bysetposYold $bysetposMold $bysetposDold<br>\n"; // test ###
      if( is_array( $recur['BYSETPOS'] )) {
        foreach( $recur['BYSETPOS'] as $bix => $bval )
          $recur['BYSETPOS'][$bix] = (int) $bval;
      }
      else
        $recur['BYSETPOS'] = array( (int) $recur['BYSETPOS'] );
      if( 'YEARLY' == $recur['FREQ'] ) {
        $wdate['month'] = $wdate['day'] = 1; // start from beginning of year
        $wdatets        = iCalUtilityFunctions::_date2timestamp( $wdate );
        iCalUtilityFunctions::_stepdate( $enddate, $endDatets, array( 'year' => 1 )); // make sure to count whole last year
      }
      elseif( 'MONTHLY' == $recur['FREQ'] ) {
        $wdate['day']   = 1; // start from beginning of month
        $wdatets        = iCalUtilityFunctions::_date2timestamp( $wdate );
        iCalUtilityFunctions::_stepdate( $enddate, $endDatets, array( 'month' => 1 )); // make sure to count whole last month
      }
      else
        iCalUtilityFunctions::_stepdate( $enddate, $endDatets, $step); // make sure to count whole last period
// echo "BYSETPOS endDat++ =".implode('-',$enddate).' step='.var_export($step,TRUE)."<br>\n";//test###
      $bysetposWold = (int) date( 'W', ( $wdatets + $wkst ));
      $bysetposYold = $wdate['year'];
      $bysetposMold = $wdate['month'];
      $bysetposDold = $wdate['day'];
    }
    else
      iCalUtilityFunctions::_stepdate( $wdate, $wdatets, $step);
    $year_old     = null;
    $daynames     = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );
             /* MAIN LOOP */
// echo "recur start ".implode('-',$wdate)." end ".implode('-',$enddate)."<br>\n";//test
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
// echo "skip: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n";//test
          iCalUtilityFunctions::_stepdate( $wdate, $wdatets, $step);
          continue;
        }
        else // continue within the selected interval
          $intervalarr[$intervalix] = 0;
// echo "cont: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n";//test
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
                                            ( $bysetposDold == $wdate['day'] )))) {
// echo "bysetposymd1[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
              $bysetposymd1[] = $wdatets;
            }
            else {
// echo "bysetposymd2[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
              $bysetposymd2[] = $wdatets;
            }
          }
        }
        else {
            /* update result array if BYSETPOS is set */
          $countcnt++;
          if( $startdatets <= $wdatets ) { // only output within period
            $result[$wdatets] = TRUE;
// echo "recur ".date('Y-m-d H:i:s',$wdatets)."<br>\n";//test
          }
// echo "recur undate ".date('Y-m-d H:i:s',$wdatets)." okdatstart ".date('Y-m-d H:i:s',$startdatets)."<br>\n";//test
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
// echo 'test före out startYMD (weekno)='.$wdateStart['year'].':'.$wdateStart['month'].':'.$wdateStart['day']." ($weekStart) "; // test ###
          foreach( $recur['BYSETPOS'] as $ix ) {
            if( 0 > $ix ) // both positive and negative BYSETPOS allowed
              $ix = ( count( $bysetposarr1 ) + $ix + 1);
            $ix--;
            if( isset( $bysetposarr1[$ix] )) {
              if( $startdatets <= $bysetposarr1[$ix] ) { // only output within period
//                $testdate   = iCalUtilityFunctions::_timestamp2date( $bysetposarr1[$ix], 6 );                // test ###
//                $testweekno = (int) date( 'W', mktime( 0, 0, $wkst, $testdate['month'], $testdate['day'], $testdate['year'] )); // test ###
// echo " testYMD (weekno)=".$testdate['year'].':'.$testdate['month'].':'.$testdate['day']." ($testweekno)";   // test ###
                $result[$bysetposarr1[$ix]] = TRUE;
// echo " recur ".date('Y-m-d H:i:s',$bysetposarr1[$ix]); // test ###
              }
              $countcnt++;
            }
            if( isset( $recur['COUNT'] ) && ( $countcnt >= $recur['COUNT'] ))
              break;
          }
// echo "<br>\n"; // test ###
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
  public static function _recurBydaySort( $bydaya, $bydayb ) {
    static $days = array( 'SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6 );
    return ( $days[substr( $bydaya, -2 )] < $days[substr( $bydayb, -2 )] ) ? -1 : 1;
  }
/**
 * convert input format for exrule and rrule to internal format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-24
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
        iCalUtilityFunctions::_strDate2arr( $rexrulevalue );
        if( iCalUtilityFunctions::_isArrayTimestampDate( $rexrulevalue )) // timestamp, always date-time UTC
          $input[$rexrulelabel] = iCalUtilityFunctions::_timestamp2date( $rexrulevalue, 7, 'UTC' );
        elseif( iCalUtilityFunctions::_isArrayDate( $rexrulevalue )) { // date or UTC date-time
          $parno = ( isset( $rexrulevalue['hour'] ) || isset( $rexrulevalue[4] )) ? 7 : 3;
          $d = iCalUtilityFunctions::_chkDateArr( $rexrulevalue, $parno );
          if(( 3 < $parno ) && isset( $d['tz'] ) && ( 'Z' != $d['tz'] ) && iCalUtilityFunctions::_isOffset( $d['tz'] )) {
            $strdate              = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
 * @since 2.16.24 - 2013-06-26
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
 * @return array
 */
  public static function _setDate( $year, $month=FALSE, $day=FALSE, $hour=FALSE, $min=FALSE, $sec=FALSE, $tz=FALSE, $params=FALSE, $caller=null, $objName=null, $tzid=FALSE ) {
    $input = $parno = null;
    $localtime = (( 'dtstart' == $caller ) && in_array( $objName, array( 'vtimezone', 'standard', 'daylight' ))) ? TRUE : FALSE;
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
        $strdate       = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
    elseif( 8 <= strlen( trim( $year ))) { // ex. 2006-08-03 10:12:18 [[[+/-]1234[56]] / timezone]
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
          $strdate     = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
        $strdate       = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $input['params']['TZID'] );
        $input['value'] = iCalUtilityFunctions::_strdate2date( $strdate, 7 );
        unset( $input['value']['unparsedtext'], $input['params']['TZID'] );
      }
    } // end elseif( 8 <= strlen( trim( $year )))
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
            $strdate     = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $d['tz'] );
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
          $strdate       = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $input['params']['TZID'] );
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
 * @since 2.16.24 - 2013-07-01
 * @param mixed $year
 * @param mixed $month  optional
 * @param int   $day    optional
 * @param int   $hour   optional
 * @param int   $min    optional
 * @param int   $sec    optional
 * @param array $params optional
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
        $strdate       = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $tzid );
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
    elseif( 8 <= strlen( trim( $year ))) { // ex. 2006-08-03 10:12:18
      $input['value']  = iCalUtilityFunctions::_strdate2date( $year, 7 );
      unset( $input['value']['unparsedtext'] );
      $input['params'] = iCalUtilityFunctions::_setParams( $month, array( 'VALUE' => 'DATE-TIME' ));
      if(( !isset( $input['value']['tz'] ) || empty( $input['value']['tz'] )) && isset( $input['params']['TZID'] ) && iCalUtilityFunctions::_isOffset( $input['params']['TZID'] )) {
        $d             = $input['value'];
        $strdate       = sprintf( '%04d-%02d-%02d %02d:%02d:%02d %s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['min'], $d['sec'], $input['params']['TZID'] );
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
 * @param string $value
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
 * sort callback functions for exdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.11 - 2013-01-12
 * @param array $a
 * @param array $b
 * @return int
 */
  public static function _sortExdate1( $a, $b ) {
    $as  = sprintf( '%04d%02d%02d', $a['year'], $a['month'], $a['day'] );
    $as .= ( isset( $a['hour'] )) ? sprintf( '%02d%02d%02d', $a['hour'], $a['min'], $a['sec'] ) : '';
    $bs  = sprintf( '%04d%02d%02d', $b['year'], $b['month'], $b['day'] );
    $bs .= ( isset( $b['hour'] )) ? sprintf( '%02d%02d%02d', $b['hour'], $b['min'], $b['sec'] ) : '';
    return strcmp( $as, $bs );
  }
  public static function _sortExdate2( $a, $b ) {
    $val = reset( $a['value'] );
    $as  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
    $as .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    $val = reset( $b['value'] );
    $bs  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
    $bs .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    return strcmp( $as, $bs );
  }
/**
 * sort callback functions for rdate
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.27 - 2013-07-05
 * @param array $a
 * @param array $b
 * @return int
 */
  public static function _sortRdate1( $a, $b ) {
    $val = isset( $a['year'] ) ? $a : $a[0];
    $as  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
    $as .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    $val = isset( $b['year'] ) ? $b : $b[0];
    $bs  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
    $bs .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    return strcmp( $as, $bs );
  }
  public static function _sortRdate2( $a, $b ) {
    $val   = isset( $a['value'][0]['year'] ) ? $a['value'][0] : $a['value'][0][0];
    if( empty( $val ))
      $as  = '';
    else {
      $as  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
      $as .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    }
    $val   = isset( $b['value'][0]['year'] ) ? $b['value'][0] : $b['value'][0][0];
    if( empty( $val ))
      $bs  = '';
    else {
      $bs  = sprintf( '%04d%02d%02d', $val['year'], $val['month'], $val['day'] );
      $bs .= ( isset( $val['hour'] )) ? sprintf( '%02d%02d%02d', $val['hour'], $val['min'], $val['sec'] ) : '';
    }
    return strcmp( $as, $bs );
  }
/**
 * step date, return updated date, array and timpstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-24
 * @param array $date, date to step
 * @param int   $timestamp
 * @param array $step, default array( 'day' => 1 )
 * @return void
 */
  public static function _stepdate( &$date, &$timestamp, $step=array( 'day' => 1 )) {
    if( !isset( $date['hour'] )) $date['hour'] = 0;
    if( !isset( $date['min'] ))  $date['min']  = 0;
    if( !isset( $date['sec'] ))  $date['sec']  = 0;
    foreach( $step as $stepix => $stepvalue )
      $date[$stepix] += $stepvalue;
    $timestamp  = mktime( $date['hour'], $date['min'], $date['sec'], $date['month'], $date['day'], $date['year'] );
    $d          = date( 'Y-m-d-H-i-s', $timestamp);
    $d          = explode( '-', $d );
    $date       = array( 'year' => $d[0], 'month' => $d[1], 'day' => $d[2], 'hour' => $d[3], 'min' => $d[4], 'sec' => $d[5] );
    foreach( $date as $k => $v )
      $date[$k] = (int) $v;
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
 * @since 2.16.24 - 2013-06-26
 * Modified to also return original string value by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * @param array $datetime
 * @param int   $parno optional, default FALSE
 * @param moxed $wtz optional, default null
 * @return array
 */
  public static function _date_time_string( $datetime, $parno=FALSE ) {
    return iCalUtilityFunctions::_strdate2date( $datetime, $parno, null );
  }
  public static function _strdate2date( $datetime, $parno=FALSE, $wtz=null ) {
    // save original input string to return it later
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
        $datestring = $d->format( 'Y-m-d-H-i-s' );
        unset( $d );
      }
      catch( Exception $e ) {
        $datestring = date( 'Y-m-d-H-i-s', strtotime( $datetime ));
      }
    } // end if( !empty( $tz ))
    else
      $datestring = date( 'Y-m-d-H-i-s', strtotime( $datetime ));
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
 * @since 2.15.1 - 2012-10-17
 * @param mixed   $timestamp
 * @param int     $parno
 * @param string  $wtz
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
//      $tz        = 'UTC';
    }
    try {
      $d         = new DateTime( "@$timestamp" );  // set UTC date
      if(  0 != $offset )                          // adjust for offset
        $d->modify( $offset.' seconds' );
      elseif( 'UTC' != $tz )
        $d->setTimezone( new DateTimeZone( $tz )); // convert to local date
      $date      = $d->format( 'Y-m-d-H-i-s' );
      unset( $d );
    }
    catch( Exception $e ) {
      $date      = date( 'Y-m-d-H-i-s', $timestamp );
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
 * convert timestamp (seconds) to duration in array format
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.23 - 2010-10-23
 * @param int $timestamp
 * @return array, duration format
 */
  public static function _timestamp2duration( $timestamp ) {
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
 * transforms a dateTime from a timezone to another using PHP DateTime and DateTimeZone class (PHP >= PHP 5.2.0)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.1 - 2012-10-17
 * @param mixed  $date,   date to alter
 * @param string $tzFrom, PHP valid 'from' timezone
 * @param string $tzTo,   PHP valid 'to' timezone, default 'UTC'
 * @param string $format, date output format, default 'Ymd\THis'
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
 * @param string $offset
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
/*********************************************************************************/
/*          iCalcreator vCard helper functions                                   */
/*********************************************************************************/
/**
 * convert single ATTENDEE, CONTACT or ORGANIZER (in email format) to vCard
 * returns vCard/TRUE or if directory (if set) or file write is unvalid, FALSE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.12.2 - 2012-07-11
 * @param object $email
 * $param string $version, vCard version (default 2.1)
 * $param string $directory, where to save vCards (default FALSE)
 * $param string $ext, vCard file extension (default 'vcf')
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
 * @param object $calendar, iCalcreator vcalendar instance reference
 * $param string $version, vCard version (default 2.1)
 * $param string $directory, where to save vCards (default FALSE)
 * $param string $ext, vCard file extension (default 'vcf')
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
/*********************************************************************************/
/*          iCalcreator XML (rfc6321) helper functions                           */
/*********************************************************************************/
/**
 * format iCal XML output, rfc6321, using PHP SimpleXMLElement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-27
 * @param object $calendar, iCalcreator vcalendar instance reference
 * @return string
 */
function iCal2XML( & $calendar ) {
            /** fix an SimpleXMLElement instance and create root element */
  $xmlstr       = '<?xml version="1.0" encoding="utf-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">';
  $xmlstr      .= '<!-- created '.date( 'Ymd\THis\Z', time());
  $xmlstr      .= ' utilizing kigkonsult.se '.ICALCREATOR_VERSION.' iCal2XMl (rfc6321) -->';
  $xmlstr      .= '</icalendar>';
  $xml          = new SimpleXMLElement( $xmlstr );
  $vcalendar    = $xml->addChild( 'vcalendar' );
            /** fix calendar properties */
  $properties   = $vcalendar->addChild( 'properties' );
  $calProps     = array( 'version', 'prodid', 'calscale', 'method' );
  foreach( $calProps as $calProp ) {
    if( FALSE !== ( $content = $calendar->getProperty( $calProp )))
      _addXMLchild( $properties, $calProp, 'text', $content );
  }
  while( FALSE !== ( $content = $calendar->getProperty( FALSE, FALSE, TRUE )))
    _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
  $langCal = $calendar->getConfig( 'language' );
            /** prepare to fix components with properties */
  $components   = $vcalendar->addChild( 'components' );
            /** fix component properties */
  while( FALSE !== ( $component = $calendar->getComponent())) {
    $compName   = $component->objName;
    $child      = $components->addChild( $compName );
    $properties = $child->addChild( 'properties' );
    $langComp   = $component->getConfig( 'language' );
    $props      = $component->getConfig( 'setPropertyNames' );
    foreach( $props as $prop ) {
      switch( strtolower( $prop )) {
        case 'attach':          // may occur multiple times, below
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            $type = ( isset( $content['params']['VALUE'] ) && ( 'BINARY' == $content['params']['VALUE'] )) ? 'binary' : 'uri';
            unset( $content['params']['VALUE'] );
            _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
          }
          break;
        case 'attendee':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
              if( $langComp )
                $content['params']['LANGUAGE'] = $langComp;
              elseif( $langCal )
                $content['params']['LANGUAGE'] = $langCal;
            }
            _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
          }
          break;
        case 'exdate':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            $type = ( isset( $content['params']['VALUE'] ) && ( 'DATE' == $content['params']['VALUE'] )) ? 'date' : 'date-time';
            unset( $content['params']['VALUE'] );
            _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
          }
          break;
        case 'freebusy':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if( is_array( $content ) && isset( $content['value']['fbtype'] )) {
              $content['params']['FBTYPE'] = $content['value']['fbtype'];
              unset( $content['value']['fbtype'] );
            }
            _addXMLchild( $properties, $prop, 'period', $content['value'], $content['params'] );
          }
          break;
        case 'request-status':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if( !isset( $content['params']['LANGUAGE'] )) {
              if( $langComp )
                $content['params']['LANGUAGE'] = $langComp;
              elseif( $langCal )
                $content['params']['LANGUAGE'] = $langCal;
            }
            _addXMLchild( $properties, $prop, 'rstatus', $content['value'], $content['params'] );
          }
          break;
        case 'rdate':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            $type = 'date-time';
            if( isset( $content['params']['VALUE'] )) {
              if( 'DATE' == $content['params']['VALUE'] )
                $type = 'date';
              elseif( 'PERIOD' == $content['params']['VALUE'] )
                $type = 'period';
            }
            unset( $content['params']['VALUE'] );
            _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
          }
          break;
        case 'categories':
        case 'comment':
        case 'contact':
        case 'description':
        case 'related-to':
        case 'resources':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if(( 'related-to' != $prop ) && !isset( $content['params']['LANGUAGE'] )) {
              if( $langComp )
                $content['params']['LANGUAGE'] = $langComp;
              elseif( $langCal )
                $content['params']['LANGUAGE'] = $langCal;
            }
            _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
          }
          break;
        case 'x-prop':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
          break;
        case 'created':         // single occurence below, if set
        case 'completed':
        case 'dtstamp':
        case 'last-modified':
          $utcDate = TRUE;
        case 'dtstart':
        case 'dtend':
        case 'due':
        case 'recurrence-id':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            $type = ( isset( $content['params']['VALUE'] ) && ( 'DATE' == $content['params']['VALUE'] )) ? 'date' : 'date-time';
            unset( $content['params']['VALUE'] );
            if(( isset( $content['params']['TZID'] ) && empty( $content['params']['TZID'] )) || @is_null( $content['params']['TZID'] ))
              unset( $content['params']['TZID'] );
            _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
          }
          unset( $utcDate );
          break;
        case 'duration':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $prop, 'duration', $content['value'], $content['params'] );
          break;
        case 'exrule':
        case 'rrule':
          while( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $prop, 'recur', $content['value'], $content['params'] );
          break;
        case 'class':
        case 'location':
        case 'status':
        case 'summary':
        case 'transp':
        case 'tzid':
        case 'uid':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if((( 'location' == $prop ) || ( 'summary' == $prop )) && !isset( $content['params']['LANGUAGE'] )) {
              if( $langComp )
                $content['params']['LANGUAGE'] = $langComp;
              elseif( $langCal )
                $content['params']['LANGUAGE'] = $langCal;
            }
            _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
          }
          break;
        case 'geo':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $prop, 'geo', $content['value'], $content['params'] );
          break;
        case 'organizer':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE ))) {
            if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
              if( $langComp )
                $content['params']['LANGUAGE'] = $langComp;
              elseif( $langCal )
                $content['params']['LANGUAGE'] = $langCal;
            }
            _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
          }
          break;
        case 'percent-complete':
        case 'priority':
        case 'sequence':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $prop, 'integer', $content['value'], $content['params'] );
          break;
        case 'tzurl':
        case 'url':
          if( FALSE !== ( $content = $component->getProperty( $prop, FALSE, TRUE )))
            _addXMLchild( $properties, $prop, 'uri', $content['value'], $content['params'] );
          break;
      } // end switch( $prop )
    } // end foreach( $props as $prop )
            /** fix subComponent properties, if any */
    while( FALSE !== ( $subcomp = $component->getComponent())) {
      $subCompName  = $subcomp->objName;
      $child2       = $child->addChild( $subCompName );
      $properties   = $child2->addChild( 'properties' );
      $langComp     = $subcomp->getConfig( 'language' );
      $subCompProps = $subcomp->getConfig( 'setPropertyNames' );
      foreach( $subCompProps as $prop ) {
        switch( strtolower( $prop )) {
          case 'attach':          // may occur multiple times, below
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              $type = ( isset( $content['params']['VALUE'] ) && ( 'BINARY' == $content['params']['VALUE'] )) ? 'binary' : 'uri';
              unset( $content['params']['VALUE'] );
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'attendee':
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              if( isset( $content['params']['CN'] ) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'cal-address', $content['value'], $content['params'] );
            }
            break;
          case 'comment':
          case 'tzname':
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              if( !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
            }
            break;
          case 'rdate':
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              $type = 'date-time';
              if( isset( $content['params']['VALUE'] )) {
                if( 'DATE' == $content['params']['VALUE'] )
                  $type = 'date';
                elseif( 'PERIOD' == $content['params']['VALUE'] )
                  $type = 'period';
              }
              unset( $content['params']['VALUE'] );
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'x-prop':
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $content[0], 'unknown', $content[1]['value'], $content[1]['params'] );
            break;
          case 'action':      // single occurence below, if set
          case 'description':
          case 'summary':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              if(( 'action' != $prop ) && !isset( $content['params']['LANGUAGE'] )) {
                if( $langComp )
                  $content['params']['LANGUAGE'] = $langComp;
                elseif( $langCal )
                  $content['params']['LANGUAGE'] = $langCal;
              }
              _addXMLchild( $properties, $prop, 'text', $content['value'], $content['params'] );
            }
            break;
          case 'dtstart':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              unset( $content['value']['tz'], $content['params']['VALUE'] ); // always local time
              _addXMLchild( $properties, $prop, 'date-time', $content['value'], $content['params'] );
            }
            break;
          case 'duration':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'duration', $content['value'], $content['params'] );
            break;
          case 'repeat':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'integer', $content['value'], $content['params'] );
            break;
          case 'trigger':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE ))) {
              if( isset( $content['value']['year'] )   &&
                  isset( $content['value']['month'] )  &&
                  isset( $content['value']['day'] ))
                $type = 'date-time';
              else {
                $type = 'duration';
                if( !isset( $content['value']['relatedStart'] ) || ( TRUE !== $content['value']['relatedStart'] ))
                  $content['params']['RELATED'] = 'END';
              }
              _addXMLchild( $properties, $prop, $type, $content['value'], $content['params'] );
            }
            break;
          case 'tzoffsetto':
          case 'tzoffsetfrom':
            if( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'utc-offset', $content['value'], $content['params'] );
            break;
          case 'rrule':
            while( FALSE !== ( $content = $subcomp->getProperty( $prop, FALSE, TRUE )))
              _addXMLchild( $properties, $prop, 'recur', $content['value'], $content['params'] );
            break;
        } // switch( $prop )
      } // end foreach( $subCompProps as $prop )
    } // end while( FALSE !== ( $subcomp = $component->getComponent()))
  } // end while( FALSE !== ( $component = $calendar->getComponent()))
  return $xml->asXML();
}
/**
 * Add children to a SimpleXMLelement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-24
 * @param object $parent,  reference to a SimpleXMLelement node
 * @param string $name,    new element node name
 * @param string $type,    content type, subelement(-s) name
 * @param string $content, new subelement content
 * @param array  $params,  new element 'attributes'
 * @return void
 */
function _addXMLchild( & $parent, $name, $type, $content, $params=array()) {
            /** create new child node */
  $name  = strtolower( $name );
  $child = $parent->addChild( $name );
  if( !empty( $params )) {
    $parameters = $child->addChild( 'parameters' );
    foreach( $params as $param => $parVal ) {
      if( 'VALUE' == $param )
        continue;
      $param = strtolower( $param );
      if( 'x-' == substr( $param, 0, 2  )) {
        $p1 = $parameters->addChild( $param );
        $p2 = $p1->addChild( 'unknown', htmlspecialchars( $parVal ));
      }
      else {
        $p1 = $parameters->addChild( $param );
        switch( $param ) {
          case 'altrep':
          case 'dir':            $ptype = 'uri';            break;
          case 'delegated-from':
          case 'delegated-to':
          case 'member':
          case 'sent-by':        $ptype = 'cal-address';    break;
          case 'rsvp':           $ptype = 'boolean';        break ;
          default:               $ptype = 'text';           break;
        }
        if( is_array( $parVal )) {
          foreach( $parVal as $pV )
            $p2 = $p1->addChild( $ptype, htmlspecialchars( $pV ));
        }
        else
          $p2 = $p1->addChild( $ptype, htmlspecialchars( $parVal ));
      }
    }
  } // end if( !empty( $params ))
  if(( empty( $content ) && ( '0' != $content )) || ( !is_array( $content) && ( '-' != substr( $content, 0, 1 ) && ( 0 > $content ))))
    return;
            /** store content */
  switch( $type ) {
    case 'binary':
      $v = $child->addChild( $type, $content );
      break;
    case 'boolean':
      break;
    case 'cal-address':
      $v = $child->addChild( $type, $content );
      break;
    case 'date':
      if( array_key_exists( 'year', $content ))
        $content = array( $content );
      foreach( $content as $date ) {
        $str = sprintf( '%04d-%02d-%02d', $date['year'], $date['month'], $date['day'] );
        $v = $child->addChild( $type, $str );
      }
      break;
    case 'date-time':
      if( array_key_exists( 'year', $content ))
        $content = array( $content );
      foreach( $content as $dt ) {
        if( !isset( $dt['hour'] )) $dt['hour'] = 0;
        if( !isset( $dt['min'] ))  $dt['min']  = 0;
        if( !isset( $dt['sec'] ))  $dt['sec']  = 0;
        $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $dt['year'], $dt['month'], $dt['day'], $dt['hour'], $dt['min'], $dt['sec'] );
        if( isset( $dt['tz'] ) && ( 'Z' == $dt['tz'] ))
          $str .= 'Z';
        $v = $child->addChild( $type, $str );
      }
      break;
    case 'duration':
      $output = (( 'trigger' == $name ) && ( FALSE !== $content['before'] )) ? '-' : '';
      $v = $child->addChild( $type, $output.iCalUtilityFunctions::_duration2str( $content ) );
      break;
    case 'geo':
      if( !empty( $content )) {
        $v1 = $child->addChild( 'latitude',  number_format( (float) $content['latitude'],  6, '.', '' ));
        $v1 = $child->addChild( 'longitude', number_format( (float) $content['longitude'], 6, '.', '' ));
      }
      break;
    case 'integer':
      $v = $child->addChild( $type, (string) $content );
      break;
    case 'period':
      if( !is_array( $content ))
        break;
      foreach( $content as $period ) {
        $v1 = $child->addChild( $type );
        $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $period[0]['year'], $period[0]['month'], $period[0]['day'], $period[0]['hour'], $period[0]['min'], $period[0]['sec'] );
        if( isset( $period[0]['tz'] ) && ( 'Z' == $period[0]['tz'] ))
          $str .= 'Z';
        $v2 = $v1->addChild( 'start', $str );
        if( array_key_exists( 'year', $period[1] )) {
          $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', $period[1]['year'], $period[1]['month'], $period[1]['day'], $period[1]['hour'], $period[1]['min'], $period[1]['sec'] );
          if( isset($period[1]['tz'] ) && ( 'Z' == $period[1]['tz'] ))
            $str .= 'Z';
          $v2 = $v1->addChild( 'end', $str );
        }
        else
          $v2 = $v1->addChild( 'duration', iCalUtilityFunctions::_duration2str( $period[1] ));
      }
      break;
    case 'recur':
      foreach( $content as $rulelabel => $rulevalue ) {
        $rulelabel = strtolower( $rulelabel );
        switch( $rulelabel ) {
          case 'until':
            if( isset( $rulevalue['hour'] ))
              $str = sprintf( '%04d-%02d-%02dT%02d:%02d:%02dZ', $rulevalue['year'], $rulevalue['month'], $rulevalue['day'], $rulevalue['hour'], $rulevalue['min'], $rulevalue['sec'] );
            else
              $str = sprintf( '%04d-%02d-%02d', $rulevalue['year'], $rulevalue['month'], $rulevalue['day'] );
            $v = $child->addChild( $rulelabel, $str );
            break;
          case 'bysecond':
          case 'byminute':
          case 'byhour':
          case 'bymonthday':
          case 'byyearday':
          case 'byweekno':
          case 'bymonth':
          case 'bysetpos': {
            if( is_array( $rulevalue )) {
              foreach( $rulevalue as $vix => $valuePart )
                $v = $child->addChild( $rulelabel, $valuePart );
            }
            else
              $v = $child->addChild( $rulelabel, $rulevalue );
            break;
          }
          case 'byday': {
            if( isset( $rulevalue['DAY'] )) {
              $str  = ( isset( $rulevalue[0] )) ? $rulevalue[0] : '';
              $str .= $rulevalue['DAY'];
              $p    = $child->addChild( $rulelabel, $str );
            }
            else {
              foreach( $rulevalue as $valuePart ) {
                if( isset( $valuePart['DAY'] )) {
                  $str  = ( isset( $valuePart[0] )) ? $valuePart[0] : '';
                  $str .= $valuePart['DAY'];
                  $p    = $child->addChild( $rulelabel, $str );
                }
                else
                  $p    = $child->addChild( $rulelabel, $valuePart );
              }
            }
            break;
          }
          case 'freq':
          case 'count':
          case 'interval':
          case 'wkst':
          default:
            $p = $child->addChild( $rulelabel, $rulevalue );
            break;
        } // end switch( $rulelabel )
      } // end foreach( $content as $rulelabel => $rulevalue )
      break;
    case 'rstatus':
      $v = $child->addChild( 'code', number_format( (float) $content['statcode'], 2, '.', ''));
      $v = $child->addChild( 'description', htmlspecialchars( $content['text'] ));
      if( isset( $content['extdata'] ))
        $v = $child->addChild( 'data', htmlspecialchars( $content['extdata'] ));
      break;
    case 'text':
      if( !is_array( $content ))
        $content = array( $content );
      foreach( $content as $part )
        $v = $child->addChild( $type, htmlspecialchars( $part ));
      break;
    case 'time':
      break;
    case 'uri':
      $v = $child->addChild( $type, $content );
      break;
    case 'utc-offset':
      if( in_array( substr( $content, 0, 1 ), array( '-', '+' ))) {
        $str     = substr( $content, 0, 1 );
        $content = substr( $content, 1 );
      }
      else
        $str     = '+';
      $str .= substr( $content, 0, 2 ).':'.substr( $content, 2, 2 );
      if( 4 < strlen( $content ))
        $str .= ':'.substr( $content, 4 );
      $v = $child->addChild( $type, $str );
      break;
    case 'unknown':
    default:
      if( is_array( $content ))
        $content = implode( '', $content );
      $v = $child->addChild( 'unknown', htmlspecialchars( $content ));
      break;
  }
}
/**
 * parse xml file into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-18
 * @param  string $xmlfile
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixediCalcreator instance or FALSE on error
 */
function XMLfile2iCal( $xmlfile, $iCalcfg=array()) {
  if( FALSE === ( $xmlstr = file_get_contents( $xmlfile )))
    return FALSE;
  return xml2iCal( $xmlstr, $iCalcfg );
}
/**
 * parse xml string into iCalcreator instance, alias of XML2iCal
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-18
 * @param  string $xmlstr
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or FALSE on error
 */
function XMLstr2iCal( $xmlstr, $iCalcfg=array()) {
  return XML2iCal( $xmlstr, $iCalcfg);
}
/**
 * parse xml string into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-20
 * @param  string $xmlstr
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or FALSE on error
 */
function XML2iCal( $xmlstr, $iCalcfg=array()) {
  $xmlstr  = str_replace( array( "\r\n", "\n\r", "\n", "\r" ), '', $xmlstr );
  $xml     = XMLgetTagContent1( $xmlstr, 'vcalendar', $endIx );
  $iCal    = new vcalendar( $iCalcfg );
  XMLgetComps( $iCal, $xmlstr );
  unset( $xmlstr );
  return $iCal;
}
/**
 * parse XML string into iCalcreator components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-20
 * @param object $iCal, iCalcreator vcalendar or component object instance
 * @param string $xml
 * @return bool
 */
function XMLgetComps( $iCal, $xml ) {
  static $comps = array( 'vtimezone', 'standard', 'daylight', 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm' );
  $sx      = 0;
  while(( FALSE !== substr( $xml, ( $sx + 11 ), 1 )) &&
        ( '<properties>' != substr( $xml, $sx, 12 )) && ( '<components>' != substr( $xml, $sx, 12 )))
    $sx   += 1;
  if( FALSE === substr( $xml, ( $sx + 11 ), 1 ))
    return FALSE;
  if( '<properties>' == substr( $xml, $sx, 12 )) {
    $xml2  = XMLgetTagContent1( $xml, 'properties', $endIx );
    XMLgetProps( $iCal, $xml2 );
    $xml   = substr( $xml, $endIx );
  }
  if( '<components>' == substr( $xml, 0, 12 ))
    $xml     = XMLgetTagContent1( $xml, 'components', $endIx );
  while( ! empty( $xml )) {
    $xml2  = XMLgetTagContent2( $xml, $tagName, $endIx );
    if( in_array( strtolower( $tagName ), $comps ) && ( FALSE !== ( $subComp = $iCal->newComponent( $tagName ))))
      XMLgetComps( $subComp, $xml2 );
    $xml   = substr( $xml, $endIx);
  }
  unset( $xml );
  return $iCal;
}
/**
 * parse XML into iCalcreator properties
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.21 - 2013-06-23
 * @param  array  $iCal iCalcreator calendar/component instance
 * @param  string $xml
 * @return void
 */
function XMLgetProps( $iCal, $xml) {
  while( ! empty( $xml )) {
    $xml2         = XMLgetTagContent2( $xml, $propName, $endIx );
    $propName     = strtoupper( $propName );
    if( empty( $xml2 ) && ( '0' != $xml2 )) {
      $iCal->setProperty( $propName );
      $xml        = substr( $xml, $endIx);
      continue;
    }
    $params       = array();
    if( '<parameters/>' == substr( $xml2, 0, 13 ))
      $xml2       = substr( $xml2, 13 );
    elseif( '<parameters>' == substr( $xml2, 0, 12 )) {
      $xml3       = XMLgetTagContent1( $xml2, 'parameters', $endIx2 );
      while( ! empty( $xml3 )) {
        $xml4     = XMLgetTagContent2( $xml3, $paramKey, $endIx3 );
        $pType    = FALSE; // skip parameter valueType
        $paramKey = strtoupper( $paramKey );
        if( in_array( $paramKey, array( 'DELEGATED-FROM', 'DELEGATED-TO', 'MEMBER' ))) {
          while( ! empty( $xml4 )) {
            if( ! isset( $params[$paramKey] ))
              $params[$paramKey]   = array( XMLgetTagContent1( $xml4, 'cal-address', $endIx4 ));
            else
              $params[$paramKey][] = XMLgetTagContent1( $xml4, 'cal-address', $endIx4 );
            $xml4     = substr( $xml4, $endIx4 );
          }
        }
        else {
          if( ! isset( $params[$paramKey] ))
            $params[$paramKey]  = html_entity_decode( XMLgetTagContent2( $xml4, $pType, $endIx4 ));
          else
            $params[$paramKey] .= ','.html_entity_decode( XMLgetTagContent2( $xml4, $pType, $endIx4 ));
        }
        $xml3     = substr( $xml3, $endIx3 );
      }
      $xml2       = substr( $xml2, $endIx2 );
    } // if( '<parameters>' == substr( $xml2, 0, 12 ))
    $valueType    = FALSE;
    $value        = ( ! empty( $xml2 ) || ( '0' == $xml2 )) ? XMLgetTagContent2( $xml2, $valueType, $endIx3 ) : '';
    switch( $propName ) {
      case 'CATEGORIES':
      case 'RESOURCES':
        $tValue      = array();
        while( ! empty( $xml2 )) {
          $tValue[]  = html_entity_decode( XMLgetTagContent2( $xml2, $valueType, $endIx4 ));
          $xml2      = substr( $xml2, $endIx4 );
        }
        $value       = $tValue;
        break;
      case 'EXDATE':   // multiple single-date(-times) may exist
      case 'RDATE':
        if( 'period' != $valueType ) {
          if( 'date' == $valueType )
            $params['VALUE'] = 'DATE';
          $t         = array();
          while( ! empty( $xml2 ) && ( '<date' == substr( $xml2, 0, 5 ))) {
            $t[]     = XMLgetTagContent2( $xml2, $pType, $endIx4 );
            $xml2    = substr( $xml2, $endIx4 );
          }
          $value = $t;
          break;
        }
      case 'FREEBUSY':
        if( 'RDATE' == $propName )
          $params['VALUE'] = 'PERIOD';
        $value       = array();
        while( ! empty( $xml2 ) && ( '<period>' == substr( $xml2, 0, 8 ))) {
          $xml3      = XMLgetTagContent1( $xml2, 'period', $endIx4 ); // period
          $t         = array();
          while( ! empty( $xml3 )) {
            $t[]     = XMLgetTagContent2( $xml3, $pType, $endIx5 ); // start - end/duration
            $xml3    = substr( $xml3, $endIx5 );
          }
          $value[]   = $t;
          $xml2      = substr( $xml2, $endIx4 );
        }
        break;
      case 'TZOFFSETTO':
      case 'TZOFFSETFROM':
        $value       = str_replace( ':', '', $value );
        break;
      case 'GEO':
        $tValue      = array( 'latitude' => $value );
        $tValue['longitude'] = XMLgetTagContent1( substr( $xml2, $endIx3 ), 'longitude', $endIx3 );
        $value       = $tValue;
        break;
      case 'EXRULE':
      case 'RRULE':
        $tValue      = array( $valueType => $value );
        $xml2        = substr( $xml2, $endIx3 );
        $valueType   = FALSE;
        while( ! empty( $xml2 )) {
          $t         = XMLgetTagContent2( $xml2, $valueType, $endIx4 );
          switch( $valueType ) {
            case 'freq':
            case 'count':
            case 'until':
            case 'interval':
            case 'wkst':
              $tValue[$valueType] = $t;
              break;
            case 'byday':
              if( 2 == strlen( $t ))
                $tValue[$valueType][] = array( 'DAY' => $t );
              else {
                $day = substr( $t, -2 );
                $key = substr( $t, 0, ( strlen( $t ) - 2 ));
                $tValue[$valueType][] = array( $key, 'DAY' => $day );
              }
              break;
            default:
              $tValue[$valueType][] = $t;
          }
          $xml2      = substr( $xml2, $endIx4 );
        }
        $value       = $tValue;
        break;
      case 'REQUEST-STATUS':
        $tValue      = array();
        while( ! empty( $xml2 )) {
          $t         = html_entity_decode( XMLgetTagContent2( $xml2, $valueType, $endIx4 ));
          $tValue[$valueType] = $t;
          $xml2    = substr( $xml2, $endIx4 );
        }
        if( ! empty( $tValue ))
          $value   = $tValue;
        else
          $value   = array( 'code' => null, 'description' => null );
        break;
      default:
        switch( $valueType ) {
          case 'binary':    $params['VALUE'] = 'BINARY';           break;
          case 'date':      $params['VALUE'] = 'DATE';             break;
          case 'date-time': $params['VALUE'] = 'DATE-TIME';        break;
          case 'text':
          case 'unknown':   $value = html_entity_decode( $value ); break;
        }
        break;
    } // end switch( $propName )
    if( 'FREEBUSY' == $propName ) {
      $fbtype = $params['FBTYPE'];
      unset( $params['FBTYPE'] );
      $iCal->setProperty( $propName, $fbtype, $value, $params );
    }
    elseif( 'GEO' == $propName )
      $iCal->setProperty( $propName, $value['latitude'], $value['longitude'], $params );
    elseif( 'REQUEST-STATUS' == $propName ) {
      if( !isset( $value['data'] ))
        $value['data'] = FALSE;
      $iCal->setProperty( $propName, $value['code'], $value['description'], $value['data'], $params );
    }
    else {
      if( empty( $value ) && ( is_array( $value ) || ( '0' > $value )))
        $value = '';
      $iCal->setProperty( $propName, $value, $params );
    }
    $xml        = substr( $xml, $endIx);
  } // end while( ! empty( $xml ))
}
/**
 * fetch a specific XML tag content
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-20
 * @param string $xml
 * @param string $tagName
 * @param int $endIx
 * @return mixed
 */
function XMLgetTagContent1( $xml, $tagName, & $endIx=0 ) {
  $strlen    = strlen( $tagName );
  $sx1       = 0;
  while( FALSE !== substr( $xml, $sx1, 1 )) {
    if(( FALSE !== substr( $xml, ( $sx1 + $strlen + 1 ), 1 )) &&
       ( strtolower( "<$tagName>" )   == strtolower( substr( $xml, $sx1, ( $strlen + 2 )))))
      break;
    if(( FALSE !== substr( $xml, ( $sx1 + $strlen + 3 ), 1 )) &&
       ( strtolower( "<$tagName />" ) == strtolower( substr( $xml, $sx1, ( $strlen + 4 ))))) { // empty tag
      $endIx = $strlen + 5;
      return '';
    }
    if(( FALSE !== substr( $xml, ( $sx1 + $strlen + 2 ), 1 )) &&
       ( strtolower( "<$tagName/>" )  == strtolower( substr( $xml, $sx1, ( $strlen + 3 ))))) { // empty tag
      $endIx = $strlen + 4;
      return '';
    }
    $sx1    += 1;
  }
  if( FALSE === substr( $xml, $sx1, 1 )) {
    $endIx   = ( empty( $sx )) ? 0 : $sx - 1;
    return '';
  }
  if( FALSE === ( $pos = stripos( $xml, "</$tagName>" ))) { // missing end tag??
    $endIx   = strlen( $xml ) + 1;
    return '';
  }
  $endIx     = $pos + $strlen + 3;
  return substr( $xml, ( $sx1 + $strlen + 2 ), ( $pos - $sx1 - 2 - $strlen ));
}
/**
 * fetch next (unknown) XML tagname AND content
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-20
 * @param string $xml
 * @param string $tagName
 * @param int $endIx
 * @return mixed
 */
function XMLgetTagContent2( $xml, & $tagName, & $endIx ) {
  $endIx       = strlen( $xml ) + 1; // just in case.. .
  $sx1         = 0;
  while( FALSE !== substr( $xml, $sx1, 1 )) {
    if( '<' == substr( $xml, $sx1, 1 )) {
      if(( FALSE !== substr( $xml, ( $sx1 + 3 ), 1 )) && ( '<!--' == substr( $xml, $sx1, 4 ))) // skip comment
        $sx1  += 1;
      else
        break; // tagname start here
    }
    else
      $sx1    += 1;
  }
  $sx2         = $sx1;
  while( FALSE !== substr( $xml, $sx2 )) {
    if(( FALSE !== substr( $xml, ( $sx2 + 1 ), 1 )) && ( '/>' == substr( $xml, $sx2, 2 ))) { // empty tag
      $tagName = trim( substr( $xml, ( $sx1 + 1 ), ( $sx2 - $sx1 - 1 )));
      $endIx   = $sx2 + 2;
      return '';
    }
    if( '>' == substr( $xml, $sx2, 1 )) // tagname ends here
      break;
    $sx2      += 1;
  }
  $tagName     = substr( $xml, ( $sx1 + 1 ), ( $sx2 - $sx1 - 1 ));
  $endIx       = $sx2 + 1;
  if( FALSE === substr( $xml, $sx2, 1 )) {
    return '';
  }
  $strlen      = strlen( $tagName );
  if(( 'duration' == $tagName ) &&
     ( FALSE !== ( $pos1 = stripos( $xml, "<duration>",  $sx1+1  ))) &&
     ( FALSE !== ( $pos2 = stripos( $xml, "</duration>", $pos1+1 ))) &&
     ( FALSE !== ( $pos3 = stripos( $xml, "</duration>", $pos2+1 ))) &&
     ( $pos1 < $pos2 ) && ( $pos2 < $pos3 ))
    $pos = $pos3;
  elseif( FALSE === ( $pos = stripos( $xml, "</$tagName>", $sx2 )))
    return '';
  $endIx       = $pos + $strlen + 3;
  return substr( $xml, ( $sx1 + $strlen + 2 ), ( $pos - $strlen - 2 ));
}
/*********************************************************************************/
/*          Additional functions to use with vtimezone components                */
/*********************************************************************************/
/**
 * For use with
 * iCalcreator (kigkonsult.se/iCalcreator/index.php)
 * copyright (c) 2011 Yitzchok Lavi
 * icalcreator@onebigsystem.com
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
 * $param array  $timezonesarray, output from function getTimezonesAsDateArrays (below)
 * $param string $tzid,           time zone identifier
 * $param mixed  $timestamp,      timestamp or a UTC datetime (in array format)
 * @return array, time zone data with keys for 'offsetHis', 'offsetSec' and 'tzname'
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
 * @param object $vcalendar, iCalcreator calendar instance
 * @return array, time zone transition timestamp, array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
 *                based on the timezone data in the vcalendar object
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
 * @param object $vtzc, an iCalcreator calendar standard/daylight instance
 * @return array, time zone data; array before(offsetHis, offsetSec), array after(offsetHis, offsetSec, tzname)
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
?>
