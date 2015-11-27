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
 * class for calendar component VALARM
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class valarm extends calendarComponent {
/**
 * @var array $action       component property value
 * @var array $attach       component property value
 * @var array $attendee     component property value
 * @var array $description  component property value
 * @var array $duration     component property value
 * @var array $repeat       component property value
 * @var array $summary      component property value
 * @var array $trigger      component property value
 * @access protected
 */
  protected $action;
  protected $attach;
  protected $attendee;
  protected $description;
  protected $duration;
  protected $repeat;
  protected $summary;
  protected $trigger;
/**
 * constructor for calendar component VALARM object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @uses valarm::calendarComponent()
 * @uses valarm::$action
 * @uses valarm::$attach
 * @uses valarm::$attendee
 * @uses valarm::$description
 * @uses valarm::$duration
 * @uses valarm::$repeat
 * @uses valarm::$summary
 * @uses valarm::$trigger
 * @uses valarm::$xprop
 * @uses calendarComponent::setConfig()
 */
  function __construct( $config = array()) {
    parent::__construct();
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
 * @uses calendarComponent::_createFormat()
 * @uses calendarComponent::$componentStart1
 * @uses calendarComponent::$componentStart2
 * @uses calendarComponent::$nl
 * @uses calendarComponent::createAction()
 * @uses calendarComponent::createAttach()
 * @uses calendarComponent::createAttendee()
 * @uses calendarComponent::createDescription()
 * @uses calendarComponent::createDuration()
 * @uses calendarComponent::createRepeat()
 * @uses calendarComponent::createSummary()
 * @uses calendarComponent::createTrigger()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::$componentEnd1
 * @uses calendarComponent::$componentEnd2
 * @uses calendarComponent::$xcaldecl
 * @return string
 */
  function createComponent( & $xcaldecl ) {
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
