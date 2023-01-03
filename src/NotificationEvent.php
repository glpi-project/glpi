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

/**
 * Class which manages notification events
 **/
class NotificationEvent extends CommonDBTM
{
    protected static $notable = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Event', 'Events', $nb);
    }


    /**
     * @param string $itemtype Item type
     * @param array  $options  array to pass to showFromArray or $value
     *
     * @return string
     **/
    public static function dropdownEvents($itemtype, $options = [])
    {

        $p['name']                = 'event';
        $p['display']             = true;
        $p['value']               = '';
        $p['display_emptychoice'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $events = [];
        $target = NotificationTarget::getInstanceByType($itemtype);
        if ($target) {
            $events = $target->getAllEvents();
        }
        return Dropdown::showFromArray($p['name'], $events, $p);
    }


    /**
     * retrieve the label for an event
     *
     * @since 0.83
     *
     * @param string $itemtype name of the type
     * @param string $event    name of the event
     *
     * @return string
     **/
    public static function getEventName($itemtype, $event)
    {

        $events = [];
        $target = NotificationTarget::getInstanceByType($itemtype);
        if ($target) {
            $events = $target->getAllEvents();
            if (isset($events[$event])) {
                return $events[$event];
            }
        }
        return NOT_AVAILABLE;
    }


    /**
     * Raise a notification event
     *
     * @param string     $event   the event raised for the itemtype
     * @param CommonGLPI $item    the object which raised the event
     * @param array      $options array   of options used
     * @param string     $label   used for debugEvent() (default '')
     *
     * @return boolean
     **/
    public static function raiseEvent($event, $item, $options = [], $label = '')
    {
        global $CFG_GLPI;

       //If notifications are enabled in GLPI's configuration
        if ($CFG_GLPI["use_notifications"] && Notification_NotificationTemplate::hasActiveMode()) {
            $notificationtarget = NotificationTarget::getInstance($item, $event, $options);
            if (!$notificationtarget) {
                return false;
            }

           //Process more infos (for example for tickets)
            $notificationtarget->addAdditionnalInfosForTarget();

           //Foreach notification
            $notifications = Notification::getNotificationsByEventAndType(
                $event,
                addslashes($item->getType()),
                $notificationtarget->getEntity()
            );

            $processed = []; // targets list
            foreach ($notifications as $data) {
                 $notificationtarget->clearAddressesList();
                 $notificationtarget->setMode($data['mode']);
                 $notificationtarget->setAllowResponse($data['allow_response']);

                 //Get template's information
                 $template = new NotificationTemplate();
                 $template->getFromDB($data['notificationtemplates_id']);
                 $template->resetComputedTemplates();

                 $notify_me = false;
                 $emitter = null;

                if (Session::isCron()) {
                   // Cron notify me
                    $notify_me = true;

                   // If mailcollector_user is set, use the given user preferences
                    if (isset($_SESSION['mailcollector_user'])) {
                        $mailcollector_user = $_SESSION['mailcollector_user'];

                        if (is_int($mailcollector_user)) {
                         // Try to load the given user and his preferences
                            $user = new User();
                            $res = $user->getFromDB($_SESSION['mailcollector_user']);

                            if ($res) {
                                 $user->computePreferences();
                                 $notify_me = $user->fields['notification_to_myself'];
                                 $emitter = $_SESSION['mailcollector_user'];
                            }
                        } else {
                     // Special case for anonymous helpdesk, we have an email
                     // instead of an ID
                     // -> load the global conf and use the email as the emitter
                            $notify_me = $CFG_GLPI['notification_to_myself'];
                            $emitter = $mailcollector_user;
                        }
                    }
                } else {
                 // Not cron see my pref
                    $notify_me = $_SESSION['glpinotification_to_myself'];
                }

                $options['mode'] = $data['mode'];
                if (!isset($processed[$data['mode']])) { // targets list per mode to avoid spam
                     $processed[$data['mode']] = [];
                }
                $options['processed'] = &$processed[$data['mode']];
                $eventclass = Notification_NotificationTemplate::getModeClass($data['mode'], 'event');
                if (class_exists($eventclass)) {
                    $eventclass::raise(
                        $event,
                        $item,
                        $options,
                        $label,
                        $data,
                        $notificationtarget->setEvent($eventclass),
                        $template,
                        $notify_me,
                        $emitter
                    );
                } else {
                    trigger_error(
                        'Missing event class for mode ' . $data['mode'] . ' (' . $eventclass . ')',
                        E_USER_WARNING
                    );
                    $label = Notification_NotificationTemplate::getMode($data['mode'])['label'];
                    Session::addMessageAfterRedirect(
                        sprintf(__('Unable to send notification using %1$s'), $label),
                        true,
                        ERROR
                    );
                }
            }
        }
        $template = null;
        return true;
    }


    /**
     * Display debug information for an object
     *
     * @param CommonDBTM $item    Object instance
     * @param array      $options Options
     *
     * @return void
     **/
    public static function debugEvent($item, $options = [])
    {

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='3'>" . _n('Notification', 'Notifications', Session::getPluralNumber()) .
            "</th><th colspan='2'><font color='blue'> (" . $item->getTypeName(1) . ")</font></th></tr>";

        $events = [];
        if ($target = NotificationTarget::getInstanceByType(get_class($item))) {
            $events = $target->getAllEvents();

            if (count($events) > 0) {
                echo "<tr><th>" . self::getTypeName(Session::getPluralNumber()) . '</th><th>' . _n('Recipient', 'Recipients', Session::getPluralNumber()) . "</th>";
                echo "<th>" . _n('Notification template', 'Notification templates', Session::getPluralNumber()) . "</th>" .
                 "<th>" . __('Mode') . "</th>" .
                 "<th>" . _n('Recipient', 'Recipients', 1) . "</th></tr>";

                foreach ($events as $event => $label) {
                    self::raiseEvent($event, $item, $options, $label);
                }
            } else {
                echo "<tr class='tab_bg_2 center'><td colspan='4'>" . __('No item to display') . "</td></tr>";
            }
        }
        echo "</table></div>";
    }
}
