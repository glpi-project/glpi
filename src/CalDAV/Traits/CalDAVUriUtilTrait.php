<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\CalDAV\Backend\Principal;
use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;

/**
 * Trait used for CalDAV URI utilities, like generation and parsing.
 *
 * @since 9.5.0
 */
trait CalDAVUriUtilTrait
{
    /**
     * Get principal URI, relative to CalDAV server root.
     *
     * @param \CommonDBTM $item
     *
     * @return string|null
     */
    protected function getPrincipalUri(\CommonDBTM $item)
    {

        $principal_uri = null;

        switch (get_class($item)) {
            case \Group::class:
                $principal_uri = $this->getGroupPrincipalUri($item->fields['id']);
                break;
            case \User::class:
                $principal_uri = $this->getUserPrincipalUri($item->fields['name']);
                break;
        }

        return $principal_uri;
    }

    /**
     * Get principal URI for a group, relative to CalDAV server root.
     *
     * @param integer $group_id
     *
     * @return string
     */
    protected function getGroupPrincipalUri($group_id)
    {
        return Principal::PREFIX_GROUPS . '/' . $group_id;
    }

    /**
     * Get principal URI for a user, relative to CalDAV server root.
     *
     * @param string $username
     *
     * @return string|null
     */
    protected function getUserPrincipalUri($username)
    {
        return Principal::PREFIX_USERS . '/' . $username;
        ;
    }

    /**
     * Return item corresponding to given URI.
     *
     * @param string $uri
     *
     * @return \CommonDBTM|null
     */
    protected function getPrincipalItemFromUri($uri)
    {
        $principal_itemtype = $this->getPrincipalItemtypeFromUri($uri);

        if (
            null === $principal_itemtype || !class_exists($principal_itemtype)
            || !is_a($principal_itemtype, \CommonDBTM::class, true)
        ) {
            return null;
        }

        $item  = new $principal_itemtype();
        $found = false;
        switch ($principal_itemtype) {
            case \Group::class:
                $found = $item->getFromDB($this->getGroupIdFromPrincipalUri($uri));
                break;
            case \User::class:
                $found = $item->getFromDBbyName($this->getUsernameFromPrincipalUri($uri));
                break;
        }

        return $found ? $item : null;
    }

    /**
     * Return itemtype corresponding to given URI.
     *
     * @param string $uri
     *
     * @return string|null
     */
    protected function getPrincipalItemtypeFromUri($uri)
    {
        $uri_parts = \Sabre\Uri\split($uri);
        $prefix = $uri_parts[0];

        $itemtype = null;

        switch ($prefix) {
            case Principal::PREFIX_GROUPS:
                $itemtype = \Group::class;
                break;
            case Principal::PREFIX_USERS:
                $itemtype = \User::class;
                break;
        }

        return $itemtype;
    }

    /**
     * Return group id corresponding to given principal URI.
     *
     * @param string $uri
     *
     * @return string|null
     */
    protected function getGroupIdFromPrincipalUri($uri)
    {
        $uri_parts = \Sabre\Uri\split($uri);
        return \Group::class === $this->getPrincipalItemtypeFromUri($uri) ? $uri_parts[1] : null;
    }

    /**
     * Return user name corresponding to given principal URI.
     *
     * @param string $uri
     *
     * @return string|null
     */
    protected function getUsernameFromPrincipalUri($uri)
    {
        $uri_parts = \Sabre\Uri\split($uri);
        return \User::class === $this->getPrincipalItemtypeFromUri($uri) ? $uri_parts[1] : null;
    }

    /**
     * Returns calendar item for given UID.
     *
     * @param string  $uid
     *
     * @return CalDAVCompatibleItemInterface|null
     */
    protected function getCalendarItemForUid($uid)
    {

        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $union = new \QueryUnion();
        foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            if (!is_a($itemtype, CalDAVCompatibleItemInterface::class, true)) {
                continue;
            }

            $union->addQuery(
                [
                    'SELECT' => [
                        'id',
                        new \QueryExpression(
                            $DB->quoteValue($itemtype) . ' AS ' . $DB->quoteName('itemtype')
                        ),
                    ],
                    'FROM'   => getTableForItemType($itemtype),
                    'WHERE'  => [
                        'uuid' => $uid,
                    ]
                ]
            );
        }

        $items_iterator = $DB->request(
            [
                'SELECT'   => [
                    'id',
                    'itemtype'
                ],
                'DISTINCT' => true,
                'FROM'     => $union,
            ]
        );

        if ($items_iterator->count() !== 1) {
            if ($items_iterator->count() > 1) {
                // Ambiguous response, unable to return matching element.
                // Should never happens as UID has very very low probability to not be unique.
                trigger_error(
                    sprintf(
                        'Multiple calendar items found with uuid %s. Unable to determine which item should be returned.',
                        $uid
                    ),
                    E_USER_WARNING
                );
            }
            return null;
        }

        $item_specs = $items_iterator->current();
        if (!is_a($item_specs['itemtype'], CalDAVCompatibleItemInterface::class, true)) {
            return null;
        }

        if (!$item = getItemForItemtype($item_specs['itemtype'])) {
            return null;
        }

        if (!$item->getFromDB($item_specs['id'])) {
            return null;
        }

        return $item;
    }

    /**
     * Returns calendar item for given path.
     *
     * @param string  $path
     *
     * @return CalDAVCompatibleItemInterface|null
     */
    protected function getCalendarItemForPath($path)
    {
        return $this->getCalendarItemForUid(preg_replace('/\.ics$/', '', $path));
    }
}
