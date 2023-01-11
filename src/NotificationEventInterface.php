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

interface NotificationEventInterface
{
    /**
     * Raise a notification event
     *
     * @param string               $event              Event
     * @param CommonGLPI           $item               Item
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
    );


    /**
     * Get target field name
     *
     * @return string
     */
    public static function getTargetFieldName();

    /**
     * Get (and populate if needed) target field for notification
     *
     * @param array $data Input event data
     *
     * @return string
     */
    public static function getTargetField(&$data);

    /**
     * Whether notifications can be handled by a crontab
     *
     * @return boolean
     */
    public static function canCron();

    /**
     * Get admin data
     *
     * @return array
     */
    public static function getAdminData();

    /**
     * Get entity admin data
     *
     * @param integer $entity Entity ID
     *
     * @return array
     */
    public static function getEntityAdminsData($entity);


    /**
     * Send notification
     *
     * @param array $data Data to send
     *
     * @return false|integer False if something went wrong, number of send notifications otherwise
     */
    public static function send(array $data);
}
