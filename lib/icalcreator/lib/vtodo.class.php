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
 * class for calendar component VTODO
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-12
 */
class vtodo extends calendarComponent {
/**
 * @var array $attach           component property value
 * @var array $attendee         component property value
 * @var array $categories       component property value
 * @var array $comment          component property value
 * @var array $completed        component property value
 * @var array $contact          component property value
 * @var array $class            component property value
 * @var array $created          component property value
 * @var array $description      component property value
 * @var array $dtstart          component property value
 * @var array $due              component property value
 * @var array $duration         component property value
 * @var array $exdate           component property value
 * @var array $exrule           component property value
 * @var array $geo              component property value
 * @var array $lastmodified     component property value
 * @var array $location         component property value
 * @var array $organizer        component property value
 * @var array $percentcomplete  component property value
 * @var array $priority         component property value
 * @var array $rdate            component property value
 * @var array $recurrenceid     component property value
 * @var array $relatedto        component property value
 * @var array $requeststatus    component property value
 * @var array $resources        component property value
 * @var array $rrule            component property value
 * @var array $sequence         component property value
 * @var array $status           component property value
 * @var array $summary          component property value
 * @var arrayr $url;
 * @access protected
 */
  protected $attach;
  protected $attendee;
  protected $categories;
  protected $comment;
  protected $completed;
  protected $contact;
  protected $class;
  protected $created;
  protected $description;
  protected $dtstart;
  protected $due;
  protected $duration;
  protected $exdate;
  protected $exrule;
  protected $geo;
  protected $lastmodified;
  protected $location;
  protected $organizer;
  protected $percentcomplete;
  protected $priority;
  protected $rdate;
  protected $recurrenceid;
  protected $relatedto;
  protected $requeststatus;
  protected $resources;
  protected $rrule;
  protected $sequence;
  protected $status;
  protected $summary;
  protected $url;
/**
 * constructor for calendar component VTODO object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.2 - 2011-05-01
 * @param array $config
 * @uses vtodo::calendarComponent()
 * @uses vtodo::$attach
 * @uses vtodo::$attendee
 * @uses vtodo::$categories
 * @uses vtodo::$class
 * @uses vtodo::$comment
 * @uses vtodo::$completed
 * @uses vtodo::$contact
 * @uses vtodo::$created
 * @uses vtodo::$description
 * @uses vtodo::$dtstart
 * @uses vtodo::$due
 * @uses vtodo::$duration
 * @uses vtodo::$exdate
 * @uses vtodo::$exrule
 * @uses vtodo::$geo
 * @uses vtodo::$lastmodified
 * @uses vtodo::$location
 * @uses vtodo::$organizer
 * @uses vtodo::$percentcomplete
 * @uses vtodo::$priority
 * @uses vtodo::$rdate
 * @uses vtodo::$recurrenceid
 * @uses vtodo::$relatedto
 * @uses vtodo::$requeststatus
 * @uses vtodo::$resources
 * @uses vtodo::$rrule
 * @uses vtodo::$sequence
 * @uses vtodo::$status
 * @uses vtodo::$summary
 * @uses vtodo::$url
 * @uses vtodo::$xprop
 * @uses vtodo::$components
 * @uses calendarComponent::setConfig()
 */
  function __construct( $config = array()) {
    parent::__construct();
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
 * @uses calendarComponent::_createFormat()
 * @uses calendarComponent::$componentStart1
 * @uses calendarComponent::$componentStart2
 * @uses calendarComponent::$nl;
 * @uses calendarComponent::createUid()
 * @uses calendarComponent::createDtstamp()
 * @uses calendarComponent::createAttach()
 * @uses calendarComponent::createAttendee()
 * @uses calendarComponent::createCategories()
 * @uses calendarComponent::createClass()
 * @uses calendarComponent::createComment()
 * @uses calendarComponent::createCompleted()
 * @uses calendarComponent::createContact()
 * @uses calendarComponent::createDescription()
 * @uses calendarComponent::createDtstart()
 * @uses calendarComponent::createDtend()
 * @uses calendarComponent::createDuration()
 * @uses calendarComponent::createExdate()
 * @uses calendarComponent::createExrule()
 * @uses calendarComponent::createGeo()
 * @uses calendarComponent::createLastModified()
 * @uses calendarComponent::createLocation()
 * @uses calendarComponent::createOrganizer()
 * @uses calendarComponent::createPriority()
 * @uses calendarComponent::createRdate()
 * @uses calendarComponent::createRelatedTo()
 * @uses calendarComponent::createRequestStatus()
 * @uses calendarComponent::createRecurrenceid()
 * @uses calendarComponent::createResources()
 * @uses calendarComponent::createRrule()
 * @uses calendarComponent::createSequence()
 * @uses calendarComponent::createStatus()
 * @uses calendarComponent::createSummary()
 * @uses calendarComponent::createUrl()
 * @uses calendarComponent::createXprop()
 * @uses calendarComponent::createSubComponent()
 * @uses calendarComponent::$componentEnd1
 * @uses calendarComponent::$componentEnd2
 * @uses calendarComponent::$xcaldecl
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
