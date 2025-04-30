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

/**
 * @since 9.1
 */


/**
 * Summary of NotificationTargetObjectLock
 *
 * Notifications for ObjectLock
 *
 * @since 9.1
 **/
class NotificationTargetObjectLock extends NotificationTarget
{
    public function getEvents()
    {
        return ['unlock'               => __('Unlock Item Request')];
    }


    public function getTags()
    {

        $tags = ['objectlock.action'               => _n('Event', 'Events', 1),
            'objectlock.name'                 => __('Item Name'),
            'objectlock.id'                   => __('Item ID'),
            'objectlock.type'                 => __('Item Type'),
            'objectlock.date'                 => __('Lock date'),
            'objectlock.date_mod'             => __('Lock date'), // old field name
            'objectlock.lockedby.lastname'    => __('Lastname of locking user'),
            'objectlock.lockedby.firstname'   => __('Firstname of locking user'),
            'objectlock.requester.lastname'   => __('Requester Lastname'),
            'objectlock.requester.firstname'  => __('Requester Firstname'),
            'objectlock.url'                  => __('Item URL'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }
        asort($this->tag_descriptions);
    }


    /**
     * @see NotificationTarget::addNotificationTargets()
     **/
    public function addNotificationTargets($entity)
    {
        $this->addTarget(Notification::USER, __('Locking User'));
    }


    /**
     * @see NotificationTarget::addSpecificTargets()
     **/
    public function addSpecificTargets($data, $options)
    {

        $user = new User();
        if ($user->getFromDB($this->obj->fields['users_id'])) {
            $this->addToRecipientsList(['language' => $user->getField('language'),
                'users_id' => $user->getID(),
            ]);
        }
    }


    public function addDataForTemplate($event, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $events = $this->getEvents();

        $object = getItemForItemtype($options['item']->fields['itemtype']);
        $object->getFromDB($options['item']->fields['items_id']);
        $user = new User();
        $user->getFromDB($options['item']->fields['users_id']);

        $this->data['##objectlock.action##']   = $events[$event];
        $this->data['##objectlock.name##']     = $object->fields['name'];
        $this->data['##objectlock.id##']       = $options['item']->fields['items_id'];
        $this->data['##objectlock.type##']     = $options['item']->fields['itemtype'];
        $this->data['##objectlock.date##']     = Html::convDateTime(
            $options['item']->fields['date_mod'],
            $user->fields['date_format']
        );
        $this->data['##objectlock.date_mod##'] = $this->data['##objectlock.date##'];
        $this->data['##objectlock.lockedby.lastname##']
                                              = $user->fields['realname'];
        $this->data['##objectlock.lockedby.firstname##']
                                              = $user->fields['firstname'];
        $this->data['##objectlock.requester.lastname##']
                                              = $_SESSION['glpirealname'];
        $this->data['##objectlock.requester.firstname##']
                                              = $_SESSION['glpifirstname'];
        $this->data['##objectlock.url##']      = $CFG_GLPI['url_base'] . "/?redirect=" .
                                                   $options['item']->fields['itemtype'] . "_" .
                                                   $options['item']->fields['items_id'];

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    public function getSender(): array
    {

        $mails = new UserEmail();
        if (
            isset($_SESSION['glpiID']) && ($_SESSION['glpiID'] > 0)
            && isset($_SESSION['glpilock_directunlock_notification'])
            && ($_SESSION['glpilock_directunlock_notification'] > 0)
            && $mails->getFromDBByCrit([
                'users_id'    => $_SESSION['glpiID'],
                'is_default'  => 1,
            ])
        ) {
            $ret = ['email' => $mails->fields['email'],
                'name'  => formatUserName(
                    0,
                    $_SESSION["glpiname"],
                    $_SESSION["glpirealname"],
                    $_SESSION["glpifirstname"]
                ),
            ];
        } else {
            $ret = parent::getSender();
        }

        return $ret;
    }


    public function getReplyTo($options = []): array
    {

        return $this->getSender();
    }
}
