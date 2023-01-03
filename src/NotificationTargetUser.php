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

// Class NotificationTarget
class NotificationTargetUser extends NotificationTarget
{
    public function getEvents()
    {
        return [
            'passwordexpires' => __('Password expires'),
            'passwordforget'  => __('Forgotten password?'),
        ];
    }


    /**
     * @see NotificationTarget::addNotificationTargets()
     **/
    public function addNotificationTargets($entity)
    {
        $this->addTarget(Notification::USER, User::getTypeName(1));

        if ($this->raiseevent == 'passwordexpires') {
            parent::addNotificationTargets($entity);
        }
    }


    /**
     * @see NotificationTarget::addSpecificTargets()
     **/
    public function addSpecificTargets($data, $options)
    {

       //Look for all targets whose type is Notification::ITEM_USER
        switch ($data['type']) {
            case Notification::USER_TYPE:
                switch ($data['items_id']) {
                    case Notification::USER:
                        $usertype = self::GLPI_USER;
                        if ($this->obj->fields['authtype'] != Auth::DB_GLPI) {
                            $usertype = self::EXTERNAL_USER;
                        }
                        // Send to user without any check on profile / entity
                        // Do not set users_id
                        $data = ['name'     => $this->obj->getName(),
                            'email'    => $this->obj->getDefaultEmail(),
                            'language' => $this->obj->getField('language'),
                            'usertype' => $usertype
                        ];
                        $this->addToRecipientsList($data);
                }
        }
    }


    public function addDataForTemplate($event, $options = [])
    {
        global $CFG_GLPI;

        $events = $this->getEvents();

        $this->data['##user.name##']      = $this->obj->getField("name");
        $this->data['##user.realname##']  = $this->obj->getField("realname");
        $this->data['##user.firstname##'] = $this->obj->getField("firstname");
        $this->data['##user.action##']    = $events[$event];

        switch ($event) {
            case 'passwordexpires':
                $expiration_time = $this->obj->getPasswordExpirationTime();
                $this->data['##user.password.expiration.date##'] = Html::convDateTime(
                    date('Y-m-d H:i:s', $expiration_time)
                );

                $this->data['##user.account.lock.date##']  = null;
                $lock_delay = (int)$CFG_GLPI['password_expiration_lock_delay'];
                if (-1 !== $lock_delay) {
                     $this->data['##user.account.lock.date##'] = Html::convDateTime(
                         date(
                             'Y-m-d H:i:s',
                             strtotime(
                                 sprintf(
                                     '+ %s days',
                                     $lock_delay
                                 ),
                                 $expiration_time
                             )
                         )
                     );
                }
                $this->data['##user.password.has_expired##'] = $this->obj->hasPasswordExpired() ? '1' : '0';
                $this->data['##user.password.update.url##'] = urldecode(
                    $CFG_GLPI["url_base"] . "/front/updatepassword.php"
                );
                break;
            case 'passwordforget':
                $this->data['##user.token##']             = $this->obj->getField("password_forget_token");
                $this->data['##user.passwordforgeturl##'] = urldecode($CFG_GLPI["url_base"]
                . "/front/lostpassword.php?password_forget_token="
                . $this->obj->getField("password_forget_token"));
                break;
        }

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    public function getTags()
    {

       // Common value tags
        $tags = [
            'user.name'      => __('Login'),
            'user.realname'  => __('Name'),
            'user.firstname' => __('First name'),
            'user.action'    => _n('Event', 'Events', 1),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(
                [
                    'tag'    => $tag,
                    'label'  => $label,
                    'value'  => true,
                ]
            );
        }

        foreach ($this->getEvents() as $event => $name) {
            $this->addTagsForEvent($event);
        }

        asort($this->tag_descriptions);
        return $this->tag_descriptions;
    }

    /**
     * Add tags for given event.
     *
     * @param string $event
     *
     * @return void
     */
    private function addTagsForEvent($event)
    {
        $lang_tags = [];
        $values_tags = [];

        switch ($event) {
            case 'passwordexpires':
                $values_tags = [
                    'user.account.lock.date'        => __('Account lock date if password is not changed'),
                    'user.password.expiration.date' => __('Password expiration date'),
                    'user.password.has_expired'     => __('Password has expired'),
                    'user.password.update.url'      => __('URL'),
                ];
                $lang_tags = [
                    'password.expires_soon.information' => __('We inform you that your password will expire soon.'),
                    'password.has_expired.information'  => __('We inform you that your password has expired.'),
                    'password.update.link'              => __('To update your password, please follow this link:'),
                ];
                break;
            case 'passwordforget':
                $values_tags = [
                    'user.token'             => __('Token'),
                    'user.passwordforgeturl' => __('URL'),
                ];

                $lang_tags = [
                    'passwordforget.information' => __('You have been made a request to reset your account password.'),
                    'passwordforget.link'        => __('Just follow this link (you have one day):'),
                ];
                break;
        }

        foreach ($values_tags as $tag => $label) {
            $this->addTagToList(
                [
                    'tag'    => $tag,
                    'label'  => $label,
                    'value'  => true,
                    'events' => [$event],
                ]
            );
        }

        foreach ($lang_tags as $tag => $label) {
            $this->addTagToList(
                [
                    'tag'    => $tag,
                    'label'  => $label,
                    'value'  => false,
                    'lang'   => true,
                    'events' => [$event],
                ]
            );
        }
    }
}
