<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\CalDAV\Backend;

use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Node\Property;
use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use Glpi\Toolbox\Sanitizer;
use Ramsey\Uuid\Uuid;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Reader;

/**
 * Calendar backend for CalDAV server.
 *
 * @since 9.5.0
 *
 * @TODO Implement SyncSupport, SubscriptionSupport, SchedulingSupport, SharingSupport
 * @TODO Implement read/write of VALARM to define reminders.
 */
class Calendar extends AbstractBackend
{
    use CalDAVUriUtilTrait;

    const BASE_CALENDAR_URI = 'calendar';
    const CALENDAR_ROOT     = 'calendars';
    const PREFIX_GROUPS     = self::CALENDAR_ROOT . '/groups';
    const PREFIX_USERS      = self::CALENDAR_ROOT . '/users';

    public function getCalendarsForUser($principalPath)
    {
        global $CFG_GLPI;

        $principal_item = $this->getPrincipalItemFromUri($principalPath);

        if (null === $principal_item) {
            return [];
        }

        $principal_calendar_key = \Planning::getPlanningKeyForActor(
            $principal_item->getType(),
            $principal_item->fields['id']
        );

        $calendars_params = [
         // Calendar of current principal
            $principal_calendar_key => [
                'key'          => $principal_calendar_key,
                'uri'          => self::BASE_CALENDAR_URI,
                'principaluri' => $principalPath,
                'name'         => $principal_item->getName(),
                'desc'         => sprintf(__('Calendar of %s'), $principal_item->getFriendlyName()),
                'color'        => null,
            ]
        ];

        if ($principal_item instanceof \User) {
            $user_params = importArrayFromDB($principal_item->fields['plannings']);
            $user_calendars = is_array($user_params) && array_key_exists('plannings', $user_params)
            ? $user_params['plannings']
            : [];
            foreach ($user_calendars as $key => $calendar_params) {
                if ($principal_calendar_key === $key) {
                    $calendars_params[$principal_calendar_key]['color'] = $calendar_params['color'];
                    continue;
                }

                if ('group_users' === $calendar_params['type']) {
                    continue; // Ignore 'group_users' plannings
                }

                $item_type = \Planning::getActorTypeFromPlanningKey($key);
                $item_id   = \Planning::getActorIdFromPlanningKey($key);

                if (null === $item_type || !is_a($item_type, \CommonDBTM::class, true) || null === $item_id) {
                    continue;
                }
                $calendar_principal = new $item_type();
                if (!$calendar_principal->getFromDB($item_id)) {
                    continue;
                }

                $calendars_params[$key] = [
                    'key'          => $key,
                    'uri'          => \User::class === get_class($calendar_principal)
                  ? $calendar_principal->fields['name']
                  : $key,
                    'principaluri' => $this->getPrincipalUri($calendar_principal),
                    'name'         => $calendar_principal->getName(),
                    'desc'         => sprintf(__('Calendar of %s'), $calendar_principal->getFriendlyName()),
                    'color'        => $calendar_params['color'],
                ];
            }
        }

        $calendars = [];
        foreach ($calendars_params as $key => $calendar_data) {
            $calendars[] = [
                'id'                               => $key,
                'uri'                              => $calendar_data['uri'],
                'principaluri'                     => $calendar_data['principaluri'],
                Property::DISPLAY_NAME             => $calendar_data['name'],
                Property::CAL_COLOR                => $calendar_data['color'],
                Property::CAL_DESCRIPTION          => $calendar_data['desc'],
                Property::CAL_SUPPORTED_COMPONENTS => new SupportedCalendarComponentSet(
                    $CFG_GLPI['caldav_supported_components']
                ),
                Property::RESOURCE_TYPE            => new ResourceType(
                    ['{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar']
                ),
            ];
        }

        return $calendars;
    }

    public function createCalendar($principalPath, $calendarPath, array $properties)
    {
        throw new \Sabre\DAV\Exception\NotImplemented('Calendar creation is not implemented');
    }

    public function deleteCalendar($calendarId)
    {
        throw new \Sabre\DAV\Exception\NotImplemented('Calendar deletion is not implemented');
    }

    public function getCalendarObjects($calendarId)
    {

        global $CFG_GLPI;

        $principal_type = \Planning::getActorTypeFromPlanningKey($calendarId);
        $principal_id   = \Planning::getActorIdFromPlanningKey($calendarId);
        if (null !== $principal_type && is_a($principal_type, \CommonDBTM::class, true) && null !== $principal_id) {
            $item = new $principal_type();
            $exists = $item->getFromDB($principal_id);
        }

        if (!$exists) {
            throw new \Sabre\DAV\Exception\NotFound(sprintf('Calendar "%s" not found', $calendarId));
        }

        $objects = [];

        foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            if (!is_a($itemtype, CalDAVCompatibleItemInterface::class, true)) {
                continue;
            }

            $vcalendars = [];
            switch ($principal_type) {
                case \Group::class:
                    $vcalendars = $itemtype::getGroupItemsAsVCalendars($item->fields['id']);
                    break;
                case \User::class:
                    $vcalendars = $itemtype::getUserItemsAsVCalendars($item->fields['id']);
                    break;
            }
            foreach ($vcalendars as $vcalendar) {
                $objects[] = $this->convertVCalendarToCalendarObject($vcalendar);
            }
        }

        return $objects;
    }

    public function getCalendarObject($calendarId, $objectPath)
    {

        $item = $this->getCalendarItemForPath($objectPath);
        if (null === $item) {
            return null;
        }

        $vcalendar = $item->getAsVCalendar();

        return null !== $vcalendar ? $this->convertVCalendarToCalendarObject($vcalendar) : null;
    }

    public function createCalendarObject($calendarId, $objectPath, $calendarData)
    {

        if (!$this->storeCalendarObject($calendarId, $calendarData)) {
            throw new \Sabre\DAV\Exception('Error during object creation');
        }

        return null;
    }

    public function updateCalendarObject($calendarId, $objectPath, $calendarData)
    {

        $item = $this->getCalendarItemForPath($objectPath);
        if (null === $item) {
            throw new \Sabre\DAV\Exception\NotFound(sprintf('Object "%s" not found', $objectPath));
        }

        if (!$this->storeCalendarObject($calendarId, $calendarData, $item)) {
            throw new \Sabre\DAV\Exception('Error during object creation');
        }

        return null;
    }

    public function deleteCalendarObject($calendarId, $objectPath)
    {

        $item = $this->getCalendarItemForPath($objectPath);
        if (null === $item) {
            throw new \Sabre\DAV\Exception\NotFound(sprintf('Object "%s" not found', $objectPath));
        }

        if (!$item->deleteFromDB()) {
            throw new \Sabre\DAV\Exception('Error during object deletion');
        }
    }

    /**
     * Convert a VCalendar object to an object served by CalDAV server.
     *
     * @param VCalendar $vcalendar
     *
     * @return array
     */
    private function convertVCalendarToCalendarObject(VCalendar $vcalendar)
    {

        $vcalendar->PRODID = '-//glpi-project.org//GLPI CalDAV server//EN';

        $calendardata  = $vcalendar->serialize();
        $vcomponent    = $vcalendar->getBaseComponent();
       /* @var \DateTimeInterface $last_modified */
        $last_modified = $vcomponent->{'LAST-MODIFIED'} instanceof DateTime
         ? $vcomponent->{'LAST-MODIFIED'}->getDateTime()
         : new \DateTime();

        return  [
            'uri'          => $vcomponent->UID . '.ics',
            'lastmodified' => (new \DateTime('@' . $last_modified->getTimestamp())),
            'size'         => strlen($calendardata),
            'calendardata' => $calendardata
        ];
    }

    /**
     * Store calendar object into given item.
     * If no item is specified, a new one (PlanningExternalEvent) will be created.
     *
     * @param string                             $calendarId    Calendar identifier
     * @param string                             $calendarData  Seialized VCalendar object
     * @param CalDAVCompatibleItemInterface|null $item          Item on which input will be stored
     *
     * @return boolean
     */
    private function storeCalendarObject($calendarId, $calendarData, CalDAVCompatibleItemInterface $item = null)
    {

        global $CFG_GLPI;

       /* @var \Sabre\VObject\Component\VCalendar $vcalendar */
        $vcalendar = Reader::read($calendarData);
        $vcomponent = $vcalendar->getBaseComponent();

        if (!in_array($vcomponent->name, $CFG_GLPI['caldav_supported_components'])) {
            throw new \Sabre\DAV\Exception\UnsupportedMediaType('Component "%s" is not supported');
        }

        $input = [];

        if (null === $item) {
           // $item is null when a new calendar item is created
           // New objects are handled as PlanningExternalEvent
            $item = new \PlanningExternalEvent();

            $principal_id   = \Planning::getActorIdFromPlanningKey($calendarId);
            $principal_type = \Planning::getActorTypeFromPlanningKey($calendarId);

            switch ($principal_type) {
                case \Group::class:
                    $input['users_id'] = \Session::getLoginUserID();  // Owner is current logged user
                    $input['groups_id'] = $principal_id;
                    break;
                case \User::class:
                    $input['users_id'] = $principal_id;
                    break;
            }
        }

        $input += $item->getInputFromVCalendar($vcalendar);

        if ($vcomponent->UID instanceof FlatText) {
            $input['uuid'] = $vcomponent->UID->getValue();
        } else {
           // Generate a new UUID if none exists.
            $input['uuid'] = Uuid::uuid4();
        }

        $input = Sanitizer::sanitize($input);

        if ($item->isNewItem()) {
           // Auto set entities_id if exists and not set
            if (
                !array_key_exists('entities_id', $input)
                && $item->isField('entities_id')
                && array_key_exists('glpiactive_entity', $_SESSION)
            ) {
                $input['entities_id'] = $_SESSION['glpiactive_entity'];
            }
            if (!$item->can(-1, CREATE, $input)) {
                throw new \Sabre\DAV\Exception\Forbidden();
            }
            $items_id = $item->add($input);
            if (false === $items_id) {
                return false;
            }
            return $this->storeVCalendarData($calendarData, $items_id, $item->getType());
        }

        $input['id'] = $item->fields['id'];
        if (!$item->can($item->fields['id'], UPDATE, $input)) {
            throw new \Sabre\DAV\Exception\Forbidden();
        }
        if (array_key_exists('date_creation', $input)) {
            unset($input['date_creation']); // Prevent date creation override
        }
        $update = $item->update($input);
        if (false === $update) {
            return false;
        }
        return $this->storeVCalendarData($calendarData, $item->fields['id'], $item->getType());
    }

    /**
     * Store raw VCalendar data and attach it to given item.
     *
     * @param string  $calendarData
     * @param integer $items_id
     * @param string  $itemtype
     *
     * @return boolean
     */
    private function storeVCalendarData($calendarData, $items_id, $itemtype)
    {

        $vobject = new \VObject();

       // Load existing object if exists.
        $vobject->getFromDBByCrit(
            [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ]
        );

        $input = [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'data'     => $calendarData,
        ];

        $input = \Toolbox::addslashes_deep($input);

        if ($vobject->isNewItem()) {
            return $vobject->add($input);
        }

        $input['id'] = $vobject->fields['id'];
        return $vobject->update($input);
    }
}
