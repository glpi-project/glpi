<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\CalDAV\Traits;

use Glpi\Application\ErrorHandler;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use RRule\RRule;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VJournal;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Property\ICalendar\Recur;
use Sabre\VObject\Reader;

/**
 * Trait containing methods to convert properties from/to a VObject component.
 *
 * @since 9.5.0
 */
trait VobjectConverterTrait
{
    /**
     * Get VCalendar object for given item.
     *
     * @param \CommonDBTM $item
     * @param string      $component_type  Base component type (i.e. VEVENT, VTODO, ...).
     *
     * @return VCalendar
     */
    protected function getVCalendarForItem(\CommonDBTM $item, $component_type): VCalendar
    {
        global $CFG_GLPI;

        if (!array_key_exists($component_type, VCalendar::$componentMap)) {
            throw new \InvalidArgumentException(sprintf('Invalid component type "%s"', $component_type));
        }

        $vobject = new \VObject();
        $vobject_crit = [
            'items_id' => $item->fields['id'],
            'itemtype' => $item->getType(),
        ];

       // Restore previously saved VCalendar if available
        $vcalendar = null;
        $vcomp     = null;
        if ($vobject->getFromDBByCrit($vobject_crit) && !empty($vobject->fields['data'])) {
            $vcalendar = Reader::read($vobject->fields['data']);
            $vcomp = $vcalendar->getBaseComponent();
            if (VCalendar::$componentMap[$component_type] !== get_class($vcomp)) {
               // Remove existing base component if it has changed.
               // For instance component can change when depending on state of a PlanningExternalEvent.
                $vcalendar->remove($vcomp);
                $vcomp = null;
            }
        }
        if (!($vcalendar instanceof VCalendar)) {
            $vcalendar = new VCalendar();
        }
        if (!($vcomp instanceof Component)) {
            $vcomp = $vcalendar->add($component_type);
        }

        $fields = Sanitizer::unsanitize($item->fields);
        $utc_tz = new \DateTimeZone('UTC');

        if (array_key_exists('uuid', $fields)) {
            $vcomp->UID = $fields['uuid'];
        }

        if (array_key_exists('date_creation', $fields)) {
            $vcomp->CREATED = (new \DateTime($fields['date_creation']))->setTimeZone($utc_tz);
        } else if (array_key_exists('date', $fields)) {
            $vcomp->CREATED = (new \DateTime($fields['date']))->setTimeZone($utc_tz);
        }

        if (array_key_exists('date_mod', $fields)) {
            $vcomp->DTSTAMP           = (new \DateTime($fields['date_mod']))->setTimeZone($utc_tz);
            $vcomp->{'LAST-MODIFIED'} = (new \DateTime($fields['date_mod']))->setTimeZone($utc_tz);
        }

        if (array_key_exists('name', $fields)) {
            $vcomp->SUMMARY = $fields['name'];
        }

        $description = null;
        if (array_key_exists('content', $fields)) {
            $description = $fields['content'];
        } else if (array_key_exists('text', $fields)) {
            $description = $fields['text'];
        }
        if ($description !== null) {
           // Transform HTML text to plain text
            $vcomp->DESCRIPTION = RichText::getTextFromHtml($description, true);
        }

        $vcomp->URL = $CFG_GLPI['url_base'] . $this->getFormURLWithID($fields['id'], false);

        if (array_key_exists('begin', $fields) && !empty($fields['begin'])) {
            $vcomp->DTSTART = (new \DateTime($fields['begin']))->setTimeZone($utc_tz);
        }

        if (array_key_exists('end', $fields) && !empty($fields['end'])) {
            $end_date = (new \DateTime($fields['end']))->setTimeZone($utc_tz);
            if ('VTODO' === $component_type) {
                $vcomp->DUE = $end_date;
            } else {
                $vcomp->DTEND = $end_date;
            }
        }

        if (array_key_exists('rrule', $fields) && !empty($fields['rrule'])) {
            $rrule_specs = json_decode($fields['rrule'], true);
            try {
                if (array_key_exists('byweekday', $rrule_specs)) {
                    $rrule_specs['byday'] = $rrule_specs['byweekday'];
                    unset($rrule_specs['byweekday']);
                }
                if (array_key_exists('until', $rrule_specs) && empty($rrule_specs['until'])) {
                    unset($rrule_specs['until']);
                }
                if (array_key_exists('exceptions', $rrule_specs)) {
                    foreach ($rrule_specs['exceptions'] as $exdate) {
                        $vcomp->add('EXDATE', (new \DateTime($exdate))->setTimeZone($utc_tz));
                    }
                    unset($rrule_specs['exceptions']);
                }
                $rrule = new RRule($rrule_specs);
                $vcomp->RRULE = $rrule->rfcString();
            } catch (\InvalidArgumentException $e) {
                ErrorHandler::getInstance()->handleException($e, true);
            }
        }

        if ('VTODO' === $component_type && array_key_exists('state', $fields)) {
            if (\Planning::TODO == $fields['state']) {
                $vcomp->STATUS = 'NEEDS-ACTION';
            } else if (\Planning::DONE == $fields['state']) {
                $vcomp->STATUS = 'COMPLETED';
            }
        }

        return $vcalendar;
    }

    /**
     * Return the most relevant caldav component according to configuration.
     *
     * @param boolean $is_planned
     * @param boolean $is_task
     *
     * @return string|null
     */
    protected function getTargetCaldavComponent(bool $is_planned, bool $is_task)
    {
        global $CFG_GLPI;

       // Use VTODO for tasks if available.
        if ($is_task && in_array('VTODO', $CFG_GLPI['caldav_supported_components'])) {
            return 'VTODO';
        }

       // Use VEVENT for planned items if available (it includes tasks if VTODO is not available).
        if ($is_planned && in_array('VEVENT', $CFG_GLPI['caldav_supported_components'])) {
            return 'VEVENT';
        }

       // Use VJOURNAL for unplanned items if available (it includes tasks if VTODO is not available).
        if (!$is_planned && in_array('VJOURNAL', $CFG_GLPI['caldav_supported_components'])) {
            return 'VJOURNAL';
        }

       // No component fits item properties
        return null;
    }

    /**
     * Get common item input for given component.
     *
     * @param Component $vcomponent
     * @param bool      $is_new_item
     *
     * @return array
     */
    protected function getCommonInputFromVcomponent(Component $vcomponent, bool $is_new_item = true)
    {
        if (
            !($vcomponent instanceof VEvent)
            && !($vcomponent instanceof VTodo)
            && !($vcomponent instanceof VJournal)
        ) {
            throw new \UnexpectedValueException(
                'Component object must be a VEVENT, a VJOURNAL, or a VTODO'
            );
        }

        $input = [];

        if ($vcomponent->CREATED instanceof DateTime) {
           /* @var \DateTime|\DateTimeImmutable|null $created_datetime */
            $user_tz = new \DateTimeZone(date_default_timezone_get());
            $created_datetime = $vcomponent->CREATED->getDateTime();
            $created_datetime = $created_datetime->setTimeZone($user_tz);
            $input['date_creation'] = $created_datetime->format('Y-m-d H:i:s');
        }

        if ($vcomponent->SUMMARY instanceof FlatText) {
            $input['name'] = $vcomponent->SUMMARY->getValue();
        }

        $input['content'] = $this->getContentRichTextInputFromVComponent($vcomponent);

        $plan = $this->getPlanInputFromVComponent($vcomponent);
        if (null !== $plan) {
            $input['plan'] = $plan;
        }

        $input['rrule'] = $this->getRRuleInputFromVComponent($vcomponent);
        if ($input['rrule'] === null) {
            $input['rrule'] = 'NULL'; // Ensure rrule is set to null on update.
        }

        $state = $this->getStateInputFromVComponent($vcomponent);
        if ($state !== null) {
            $input['state'] = $state;
        } else if ($is_new_item) {
            $input['state'] = GLPI_CALDAV_IMPORT_STATE;
        }

        return $input;
    }

    /**
     * Get content input from component converted into HTML format.
     *
     * @param Component $vcomponent
     *
     * @return string|null
     */
    private function getContentRichTextInputFromVComponent(Component $vcomponent)
    {
        if (!($vcomponent->DESCRIPTION instanceof FlatText)) {
            return null;
        }

        $content = $vcomponent->DESCRIPTION->getValue();

       // Content is handled as plain text in CalDAV client and will be handled as rich text on GLPI side,
       // so special chars have to be encoded in html entities.
        $content = \Html::entities_deep($content);

        return $content;
    }

    /**
     * Get state input from component (see Planning constants).
     *
     * @param Component $vcomponent
     *
     * @return integer|null
     */
    private function getStateInputFromVComponent(Component $vcomponent)
    {
        if (!($vcomponent->STATUS instanceof FlatText)) {
            return null;
        }

        return 'COMPLETED' === $vcomponent->STATUS->getValue() ? \Planning::DONE : \Planning::TODO;
    }

    /**
     * Return begin/end date from component as an array object containing:
     *  - 'begin': begin date in 'Y-m-d H:i:s' format;
     *  - 'end':   end date in 'Y-m-d H:i:s' format.
     * If object does not contain plan information, return null.
     *
     * @param Component $vcomponent
     *
     * @return array|null
     */
    private function getPlanInputFromVComponent(Component $vcomponent)
    {
        if (!($vcomponent->DTSTART instanceof DateTime)) {
            return null;
        }

       /* @var \DateTime|\DateTimeImmutable|null $begin_datetime */
       /* @var \DateTime|\DateTimeImmutable|null $end_datetime */
        $user_tz        = new \DateTimeZone(date_default_timezone_get());

        $begin_datetime = $vcomponent->DTSTART->getDateTime();
        $begin_datetime = $begin_datetime->setTimeZone($user_tz);

        $end_datetime   = null;
        if ($vcomponent instanceof VTodo) {
            if ($vcomponent->DUE instanceof DateTime) {
                $end_datetime = $vcomponent->DUE->getDateTime();
                $end_datetime = $end_datetime->setTimeZone($user_tz);
            }
        } else {
            if ($vcomponent->DTEND instanceof DateTime) {
                $end_datetime = $vcomponent->DTEND->getDateTime();
                $end_datetime = $end_datetime->setTimeZone($user_tz);
            }
        }
        if (!($end_datetime instanceof \DateTimeInterface)) {
           // Event/Task objects does not accept empty end date, so set it to "+1 hour" by default.
            $end_datetime = clone $begin_datetime;
            $end_datetime = $end_datetime->add(new \DateInterval('PT1H'));
        }

        return [
            'begin' => $begin_datetime->format('Y-m-d H:i:s'),
            'end'   => $end_datetime->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get rrule input from component in format expected by events methods.
     *
     * @param Component $vcomponent
     *
     * @return array|null
     */
    private function getRRuleInputFromVComponent(Component $vcomponent)
    {
        if (!($vcomponent->RRULE instanceof Recur)) {
            return null;
        }

       // Get first array element which actually correspond to rrule specs
        $rrule = current($vcomponent->RRULE->getJsonValue());

        if (array_key_exists('byday', $rrule) && !is_array($rrule['byday'])) {
           // When only one day is set, sabre/vobject return a string instead of an array
            $rrule['byday'] = [$rrule['byday']];
        }

        if (array_key_exists('until', $rrule)) {
            $user_tz        = new \DateTimeZone(date_default_timezone_get());
            $until_datetime = new \DateTime($rrule['until']);
            $until_datetime->setTimezone($user_tz);
            $rrule['until'] = $until_datetime->format('Y-m-d H:i:s');
        }

        $exceptions = $vcomponent->select('EXDATE');
        if (count($exceptions) > 0) {
            $rrule['exceptions'] = [];
            foreach ($exceptions as $exdate) {
                $rrule['exceptions'][] = $exdate->getDateTime()->format('Y-m-d');
            }
            $rrule['exceptions'] = implode(', ', $rrule['exceptions']);
        }

        return $rrule;
    }
}
