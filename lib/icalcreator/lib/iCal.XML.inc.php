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
/*          iCalcreator XML (rfc6321) helper functions                           */
/*********************************************************************************/
/**
 * format iCal XML output, rfc6321, using PHP SimpleXMLElement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.1 - 2013-08-18
 * @param object $calendar   iCalcreator vcalendar instance reference
 * @uses ICALCREATOR_VERSION
 * @uses vcalendar::getProperty()
 * @uses _addXMLchild()
 * @uses vcalendar::getConfig()
 * @uses vcalendar::getComponent()
 * @uses calendarComponent::$objName
 * @uses calendarComponent::getProperty()
 * @return string
 */
function iCal2XML( $calendar ) {
            /** fix an SimpleXMLElement instance and create root element */
  $xmlstr       = '<?xml version="1.0" encoding="utf-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">';
  $xmlstr      .= '<!-- created '.gmdate( 'Ymd\THis\Z' );
  $xmlstr      .= ' using kigkonsult.se '.ICALCREATOR_VERSION.' iCal2XMl (rfc6321) -->';
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
 * @since 2.18.10 - 2013-09-04
 * @param object $parent   reference to a SimpleXMLelement node
 * @param string $name     new element node name
 * @param string $type     content type, subelement(-s) name
 * @param string $content  new subelement content
 * @param array  $params   new element 'attributes'
 * @uses iCalUtilityFunctions::_duration2str()
 * @uses iCalUtilityFunctions::_geo2str2()
 * @uses iCalUtilityFunctions::$geoLatFmt
 * @uses iCalUtilityFunctions::$geoLongFmt
 * @return void
 */
function _addXMLchild( & $parent, $name, $type, $content, $params=array()) {
  static $fmtYmd    = '%04d-%02d-%02d';
  static $fmtYmdHis = '%04d-%02d-%02dT%02d:%02d:%02d';
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
        $str = sprintf( $fmtYmd, (int) $date['year'], (int) $date['month'], (int) $date['day'] );
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
        $str = sprintf( $fmtYmdHis, (int) $dt['year'], (int) $dt['month'], (int) $dt['day'], (int) $dt['hour'], (int) $dt['min'], (int) $dt['sec'] );
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
        $v1 = $child->addChild( 'latitude',  iCalUtilityFunctions::_geo2str2( $content['latitude'],  iCalUtilityFunctions::$geoLatFmt ));
        $v1 = $child->addChild( 'longitude', iCalUtilityFunctions::_geo2str2( $content['longitude'], iCalUtilityFunctions::$geoLongFmt ));
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
        $str = sprintf(   $fmtYmdHis, (int) $period[0]['year'], (int) $period[0]['month'], (int) $period[0]['day'], (int) $period[0]['hour'], (int) $period[0]['min'], (int) $period[0]['sec'] );
        if( isset( $period[0]['tz'] ) && ( 'Z' == $period[0]['tz'] ))
          $str .= 'Z';
        $v2 = $v1->addChild( 'start', $str );
        if( array_key_exists( 'year', $period[1] )) {
          $str = sprintf( $fmtYmdHis, (int) $period[1]['year'], (int) $period[1]['month'], (int) $period[1]['day'], (int) $period[1]['hour'], (int) $period[1]['min'], (int) $period[1]['sec'] );
          if( isset($period[1]['tz'] ) && ( 'Z' == $period[1]['tz'] ))
            $str .= 'Z';
          $v2 = $v1->addChild( 'end', $str );
        }
        else
          $v2 = $v1->addChild( 'duration', iCalUtilityFunctions::_duration2str( $period[1] ));
      }
      break;
    case 'recur':
      $content = array_change_key_case( $content );
      foreach( $content as $rulelabel => $rulevalue ) {
        switch( $rulelabel ) {
          case 'until':
            if( isset( $rulevalue['hour'] ))
              $str = sprintf( $fmtYmdHis, (int) $rulevalue['year'], (int) $rulevalue['month'], (int) $rulevalue['day'], (int) $rulevalue['hour'], (int) $rulevalue['min'], (int) $rulevalue['sec'] ).'Z';
            else
              $str = sprintf( $fmtYmd, (int) $rulevalue['year'], (int) $rulevalue['month'], (int) $rulevalue['day'] );
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
 * @uses vcalendar::vcalendar()
 * @uses XMLgetComps()
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
 * @since 2.21.11 - 2015-03-21
 * @param object $iCal   iCalcreator vcalendar or component object instance
 * @param string $xml
 * @uses iCalUtilityFunctions::$allComps
 * @uses XMLgetTagContent1()
 * @uses XMLgetProps()
 * @uses XMLgetTagContent2()
 * @uses vcalendar::newComponent()
 * @uses iCalUtilityFunctions::$allComps
 * @uses XMLgetComps()
 * @return object
 */
function XMLgetComps( $iCal, $xml ) {
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
    if( in_array( strtolower( $tagName ), iCalUtilityFunctions::$allComps ) &&
       ( FALSE !== ( $subComp = $iCal->newComponent( $tagName ))))
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
 * @since 2.21.11 - 2015-03-21
 * @param  array  $iCal iCalcreator calendar/component instance
 * @param  string $xml
 * @uses XMLgetTagContent2()
 * @uses vcalendar::setProperty()
 * @uses calendarComponent::setproperty()
 * @uses XMLgetTagContent1()
 * @uses vcalendar::setProperty()
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
        static $mParams = array( 'DELEGATED-FROM', 'DELEGATED-TO', 'MEMBER' );
        if( in_array( $paramKey, $mParams )) {
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
 * @param int    $endIx
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
