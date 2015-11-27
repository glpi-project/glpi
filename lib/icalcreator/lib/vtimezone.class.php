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
 * class for calendar component VTIMEZONE
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vtimezone extends calendarComponent {
 /** @var array $timezonetype  component property value */
  public  $timezonetype;
/**
 * @var array $comment       component property value
 * @var array $dtstart       component property value
 * @var array $lastmodified  component property value
 * @var array $rdate         component property value
 * @var array $rrule         component property value
 * @var array $tzid          component property value
 * @var array $tzname        component property value
 * @var array $tzoffsetfrom  component property value
 * @var array $tzoffsetto    component property value
 * @var array $tzurl         component property value
 * @access protected
 */
  protected $comment;
  protected $dtstart;
  protected $lastmodified;
  protected $rdate;
  protected $rrule;
  protected $tzid;
  protected $tzname;
  protected $tzoffsetfrom;
  protected $tzoffsetto;
  protected $tzurl;
/**
 * constructor for calendar component VTIMEZONE object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param mixed $timezonetype  default FALSE ( STANDARD / DAYLIGHT )
 * @param array $config
 * @uses vtimezone::$timezonetype
 * @uses vtimezone::calendarComponent()
 * @uses vtimezone::$comment
 * @uses vtimezone::$dtstart
 * @uses vtimezone::$lastmodified
 * @uses vtimezone::$rdate
 * @uses vtimezone::$rrule
 * @uses vtimezone::$tzid
 * @uses vtimezone::$tzname
 * @uses vtimezone::$tzoffsetfrom
 * @uses vtimezone::$tzoffsetto
 * @uses vtimezone::$tzurl
 * @uses vtimezone::$xprop
 * @uses vtimezone::$components
 * @uses calendarComponent::setConfig()
 */
  function __construct( $timezonetype=FALSE, $config = array()) {
    if( is_array( $timezonetype )) {
      $config       = $timezonetype;
      $timezonetype = FALSE;
    }
    if( !$timezonetype )
      $this->timezonetype = 'VTIMEZONE';
    else
      $this->timezonetype = strtoupper( $timezonetype );
    parent::__construct();
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
 * @uses calendarComponent::_createFormat()
 * @uses calendarComponent::$componentStart1
 * @uses calendarComponent::$componentStart2
 * @uses calendarComponent::$nl
 * @uses calendarComponent::createTzid()
 * @uses calendarComponent::createLastModified()
 * @uses calendarComponent::createTzurl()
 * @uses calendarComponent::createDtstart()
 * @uses calendarComponent::createTzoffsetfrom()
 * @uses calendarComponent::createTzoffsetto()
 * @uses calendarComponent::createComment()
 * @uses calendarComponent::createRdate()
 * @uses calendarComponent::createRrule()
 * @uses calendarComponent::createTzname()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::createSubComponent()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::$componentEnd1
 * @uses calendarComponent::$componentEnd2
 * @uses calendarComponent::$xcaldecl
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
