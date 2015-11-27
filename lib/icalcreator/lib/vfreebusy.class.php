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
 * class for calendar component VFREEBUSY
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vfreebusy extends calendarComponent {
/**
 * @var array $attendee       component property value
 * @var array $comment        component property value
 * @var array $contact        component property value
 * @var array $dtend          component property value
 * @var array $dtstart        component property value
 * @var array $duration       component property value
 * @var array $freebusy       component property value
 * @var array $organizer      component property value
 * @var array $requeststatus  component property value
 * @var array $url            component property value
 * @access protected
 */
  protected $attendee;
  protected $comment;
  protected $contact;
  protected $dtend;
  protected $dtstart;
  protected $duration;
  protected $freebusy;
  protected $organizer;
  protected $requeststatus;
  protected $url;
/**
 * constructor for calendar component VFREEBUSY object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @uses vjournal::calendarComponent()
 * @uses vjournal::$attendee
 * @uses vjournal::$comment
 * @uses vjournal::$contact
 * @uses vjournal::$dtend
 * @uses vjournal::$dtstart
 * @uses vjournal::$dtduration
 * @uses vjournal::$organizer
 * @uses vjournal::$requeststatus
 * @uses vjournal::$url
 * @uses vjournal::$xprop
 * @uses calendarComponent::setConfig()
 */
  function __construct( $config = array()) {
    parent::__construct();
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
 * @uses calendarComponent::_createFormat()
 * @uses calendarComponent::createUid()
 * @uses calendarComponent::createDtstamp()
 * @uses calendarComponent::createAttendee()
 * @uses calendarComponent::createComment()
 * @uses calendarComponent::createContact()
 * @uses calendarComponent::createDtstart()
 * @uses calendarComponent::createDtend()
 * @uses calendarComponent::createDuration()
 * @uses calendarComponent::createFreebusy()
 * @uses calendarComponent::createOrganizer()
 * @uses calendarComponent::createRequestStatus()
 * @uses calendarComponent::createUrl()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::createUrl()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::$componentEnd1
 * @uses calendarComponent::$componentEnd2
 * @uses calendarComponent::$xcaldecl
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
