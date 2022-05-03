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

abstract class NotificationEventAbstract
{
    /**
     * Raise an ajax notification event
     *
     * @param string               $event              Event
     * @param CommonGLPI           $item               Notification data
     * @param array                $options            Options
     * @param string               $label              Label
     * @param array                $data               Notification data
     * @param NotificationTarget   $notificationtarget Target
     * @param NotificationTemplate $template           Template
     * @param boolean              $notify_me          Whether to notify current user
     *
     * @return void
     */
    public static function raise(
        $event,
        CommonGLPI $item,
        array $options,
        $label,
        array $data,
        NotificationTarget $notificationtarget,
        NotificationTemplate $template,
        $notify_me,
        $emitter = null
    ) {
        global $CFG_GLPI, $DB;
        if ($CFG_GLPI['notifications_' . $options['mode']]) {
            $entity = $notificationtarget->getEntity();
            if (isset($options['processed'])) {
                $processed = &$options['processed'];
                unset($options['processed']);
            } else { // Compat with GLPI < 9.4.2 TODO: remove in 9.5
                $processed = [];
            }
            $notprocessed = [];

            $targets = getAllDataFromTable(
                'glpi_notificationtargets',
                ['notifications_id' => $data['id']]
            );

            static::extraRaise([
                'event'              => $event,
                'item'               => $item,
                'options'            => $options,
                'data'               => $data,
                'notificationtarget' => $notificationtarget,
                'template'           => $template,
                'notify_me'          => $notify_me
            ]);

           // get original timezone
            $orig_tz = $DB->guessTimezone();

           //Foreach notification targets
            foreach ($targets as $target) {
                 //Get all users affected by this notification
                 $notificationtarget->addForTarget($target, $options);

                foreach ($notificationtarget->getTargets() as $users_infos) {
                    $key = $users_infos[static::getTargetFieldName()];
                    if (
                        $label
                        || $notificationtarget->validateSendTo($event, $users_infos, $notify_me, $emitter)
                    ) {
                        //If the user have not yet been notified
                        if (!isset($processed[$users_infos['language']][$key])) {
                      //If ther user's language is the same as the template's one
                            if (
                                isset($notprocessed[$users_infos['language']]
                                                  [$key])
                            ) {
                                  unset($notprocessed[$users_infos['language']]
                                                   [$key]);
                            }
                            $options['item'] = $item;

                      // set timezone from user
                      // as we work on a copy of the item object, no reload is required after
                            if (isset($users_infos['additionnaloption']['timezone'])) {
                                 $DB->setTimezone($users_infos['additionnaloption']['timezone']);
                                 // reload object for get timezone correct dates
                                 $options['item']->getFromDB($item->fields['id']);

                                 $DB->setTimezone($orig_tz);
                            }

                            if (
                                $tid = $template->getTemplateByLanguage(
                                    $notificationtarget,
                                    $users_infos,
                                    $event,
                                    $options
                                )
                            ) {
                                //Send notification to the user
                                if ($label == '') {
                                    $send_data = $template->getDataToSend(
                                        $notificationtarget,
                                        $tid,
                                        $key,
                                        $users_infos,
                                        $options
                                    );
                                    $send_data['_notificationtemplates_id'] = $data['notificationtemplates_id'];
                                    $send_data['_itemtype']                 = $item->getType();
                                    $send_data['_items_id']                 = method_exists($item, "getID")
                                        ? max($item->getID(), 0)
                                        : 0;
                                    $send_data['_entities_id']              = $entity;
                                    $send_data['mode']                      = $data['mode'];

                                    Notification::send($send_data);
                                } else {
                                    $notificationtarget->getFromDB($target['id']);
                                    echo "<tr class='tab_bg_2'><td>" . $label . "</td>";
                                    echo "<td>" . $notificationtarget->getNameID() . "</td>";
                                    echo "<td>" . sprintf(
                                        __('%1$s (%2$s)'),
                                        $template->getName(),
                                        $users_infos['language']
                                    ) . "</td>";
                                    echo "<td>" . $options['mode'] . "</td>";
                                    echo "<td>" . $key . "</td>";
                                    echo "</tr>";
                                }
                                $processed[$users_infos['language']][$key]
                                                                  = $users_infos;
                            } else {
                                $notprocessed[$users_infos['language']][$key]
                                                               = $users_infos;
                            }
                        }
                    }
                }
            }

            unset($processed);
            unset($notprocessed);
        }
    }

    /**
     * Extra steps raising
     *
     * @param array $params All parameters send to raise() method
     *
     * @return void
     */
    protected static function extraRaise($params)
    {
       //does nothing; designed to be overriden
    }
}
