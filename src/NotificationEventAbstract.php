<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

abstract class NotificationEventAbstract implements NotificationEventInterface
{
    public static function raise(
        $event,
        CommonGLPI $item,
        array $options,
        $label,
        array $data,
        NotificationTarget $notificationtarget,
        NotificationTemplate $template,
        $notify_me,
        $emitter = null,
        ?CommonDBTM $trigger = null
    ) {
        global $CFG_GLPI;
        if ($CFG_GLPI['notifications_' . $options['mode']]) {
            $entity = $notificationtarget->getEntity();
            if (isset($options['processed'])) {
                $processed = &$options['processed'];
                unset($options['processed']);
            }

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
                'notify_me'          => $notify_me,
            ]);

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
                            $options['item'] = $item;

                            if (
                                $tid = $template->getTemplateByLanguage(
                                    $notificationtarget,
                                    $users_infos,
                                    $event,
                                    $options
                                )
                            ) {
                                //Send notification to the user
                                if ($label === '') {
                                    $itemtype = $item::class;
                                    $items_id = method_exists($item, "getID") ? max($item->getID(), 0) : 0;

                                    $send_data = $template->getDataToSend(
                                        $notificationtarget,
                                        $tid,
                                        $key,
                                        $users_infos,
                                        $options
                                    );
                                    $send_data['_notificationtemplates_id'] = $data['notificationtemplates_id'];
                                    $send_data['_itemtype']                 = $itemtype;
                                    $send_data['_items_id']                 = $items_id;
                                    $send_data['_entities_id']              = $entity;
                                    $send_data['mode']                      = $data['mode'];
                                    $send_data['event']                     = $event;
                                    $send_data['attach_documents']          = $data['attach_documents'] === NotificationSetting::ATTACH_INHERIT
                                        ? $CFG_GLPI['attach_ticket_documents_to_mail']
                                        : $data['attach_documents'];
                                    $send_data['itemtype_trigger']          = $trigger !== null ? $trigger::class : $itemtype;
                                    $send_data['items_id_trigger']          = $trigger !== null ? $trigger->getID() : $items_id;

                                    Notification::send($send_data);
                                } else {
                                    // This is only used in the debug tab of some forms
                                    $notificationtarget->getFromDB($target['id']);
                                    echo "<tr class='tab_bg_2'><td>" . htmlescape($label) . "</td>";
                                    echo "<td>" . htmlescape($notificationtarget->getNameID()) . "</td>";
                                    echo "<td>" . htmlescape(sprintf(
                                        __('%1$s (%2$s)'),
                                        $template->getName(),
                                        $users_infos['language']
                                    )) . "</td>";
                                    echo "<td>" . htmlescape($options['mode']) . "</td>";
                                    echo "<td>" . htmlescape($key) . "</td>";
                                    echo "</tr>";
                                }
                                $processed[$users_infos['language']][$key]
                                                                  = $users_infos;
                            }
                        }
                    }
                }
            }

            unset($processed);
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
