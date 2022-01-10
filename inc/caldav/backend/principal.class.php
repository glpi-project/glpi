<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\CalDAV\Backend;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\CalDAV\Node\Property;
use Glpi\CalDAV\Traits\CalDAVPrincipalsTrait;
use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;

/**
 * Principal backend for CalDAV server.
 *
 * @see http://sabre.io/dav/principals/
 *
 * @since 9.5.0
 */
class Principal extends AbstractBackend {

   use CalDAVPrincipalsTrait;
   use CalDAVUriUtilTrait;

   const PRINCIPALS_ROOT = 'principals';
   const PREFIX_GROUPS   = self::PRINCIPALS_ROOT . '/groups';
   const PREFIX_USERS    = self::PRINCIPALS_ROOT . '/users';

   public function getPrincipalsByPrefix($prefixPath) {

      $principals = [];

      switch ($prefixPath) {
         case self::PREFIX_GROUPS:
            $groups_iterator = $this->getVisibleGroupsIterator();
            foreach ($groups_iterator as $group_fields) {
               $principals[] = $this->getPrincipalFromGroupFields($group_fields);
            }
            break;
         case self::PREFIX_USERS:
            $users_iterator = $this->getVisibleUsersIterator();
            foreach ($users_iterator as $user_fields) {
               $principals[] = $this->getPrincipalFromUserFields($user_fields);
            }
            break;
      }

      usort(
         $principals,
         function ($p1, $p2) {
            return $p1['id'] - $p2['id'];
         }
      );

      return $principals;
   }

   public function getPrincipalByPath($path) {

      $item = $this->getPrincipalItemFromUri($path);

      if (null === $item) {
         return;
      }

      return $this->getPrincipalFromItem($item);
   }

   public function updatePrincipal($path, PropPatch $propPatch) {
      throw new \Sabre\DAV\Exception\NotImplemented('Principal update is not implemented');
   }

   public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {

      throw new \Sabre\DAV\Exception\NotImplemented('Principal search is not implemented');
   }

   public function findByUri($uri, $principalPrefix) {
      throw new \Sabre\DAV\Exception\NotImplemented('Principal findByUri is not implemented');
   }

   public function getGroupMemberSet($path) {

      global $DB;

      $principal_itemtype = $this->getPrincipalItemtypeFromUri($path);
      $group_id           = $this->getGroupIdFromPrincipalUri($path);

      if (\Group::class !== $principal_itemtype) {
         return [];
      }

      $members_uris = [];

      $groups_iterator = $DB->request(
         [
            'FROM'  => \Group::getTable(),
            'WHERE' => [
               'is_task'   => 1,
               'groups_id' => $group_id,
            ] + getEntitiesRestrictCriteria(
               \Group::getTable(),
               'entities_id',
               $_SESSION['glpiactiveentities'],
               true
            ),
         ]
      );
      foreach ($groups_iterator as $group_fields) {
         $members_uris[] = $this->getGroupPrincipalUri($group_fields['id']);
      }

      $users_iterator = $DB->request(
         [
            'SELECT'     => [\User::getTableField('name')],
            'FROM'       => \User::getTable(),
            'INNER JOIN' => [
               \Group_User::getTable() => [
                  'ON' => [
                     \User::getTable()       => 'id',
                     \Group_User::getTable() => 'users_id',
                  ],
               ],
            ],
            'WHERE'     => [
               \Group_User::getTableField('groups_id') => $group_id,
            ]
         ]
      );
      foreach ($users_iterator as $user_fields) {
         $members_uris[] = $this->getUserPrincipalUri($user_fields['name']);
      }

      return $members_uris;
   }

   public function getGroupMembership($path) {

      global $DB;

      $groups_query = [
         'SELECT'     => [\Group::getTableField('id')],
         'FROM'       => \Group::getTable(),
         'INNER JOIN' => [],
         'WHERE'      => [
            'is_task' => 1,
         ] + getEntitiesRestrictCriteria(
            \Group::getTable(),
            'entities_id',
            $_SESSION['glpiactiveentities'],
            true
         ),
      ];

      $principal_itemtype = $this->getPrincipalItemtypeFromUri($path);
      switch ($principal_itemtype) {
         case \Group::class:
            $groups_query['WHERE']['groups_id'] = $this->getGroupIdFromPrincipalUri($path);
            break;
         case \User::class:
            $groups_query['INNER JOIN'][\Group_User::getTable()] = [
               'ON' => [
                  \Group::getTable()       => 'id',
                  \Group_User::getTable()  => 'groups_id',
                  [
                     'AND' => [
                        \Group_User::getTableField('users_id') => new \QuerySubQuery(
                           [
                              'SELECT' => 'id',
                              'FROM'   => \User::getTable(),
                              'WHERE'  => ['name' => $this->getUsernameFromPrincipalUri($path)],
                           ]
                        ),
                     ],
                  ],
               ]
            ];
            break;
         default:
            return []; // No groups if principal is not a user or a group
            break;
      }

      $groups_iterator = $DB->request($groups_query);

      $groups_uris = [];
      foreach ($groups_iterator as $group_fields) {
         $groups_uris[] = $this->getGroupPrincipalUri($group_fields['id']);
      }
      return $groups_uris;
   }

   public function setGroupMemberSet($path, array $members) {
      throw new \Sabre\DAV\Exception\NotImplemented('Group member set update is not implemented');
   }

   /**
    * Get principal object based on item.
    *
    * @param \CommonDBTM $item
    *
    * @return null|array
    */
   private function getPrincipalFromItem(\CommonDBTM $item) {

      $principal = null;

      switch (get_class($item)) {
         case \Group::class:
            $principal = $this->getPrincipalFromGroupFields($item->fields);
            break;
         case \User::class:
            $principal = $this->getPrincipalFromUserFields($item->fields);
            break;
      }

      return $principal;
   }

   /**
    * Get principal object based on user fields.
    *
    * @param array $user_fields
    *
    * @return array
    */
   private function getPrincipalFromUserFields(array $user_fields) {
      return [
         'id'                    => $user_fields['id'],
         'uri'                   => $this->getUserPrincipalUri($user_fields['name']),
         Property::USERNAME      => $user_fields['name'],
         Property::DISPLAY_NAME  => formatUserName(
            $user_fields['id'],
            $user_fields['name'],
            $user_fields['realname'],
            $user_fields['firstname']
         ),
         Property::PRIMARY_EMAIL => \UserEmail::getDefaultForUser($user_fields['id']),
         Property::CAL_USER_TYPE => 'INDIVIDUAL',
      ];
   }

   /**
    * Get principal object based on user fields.
    *
    * @param array $group_fields
    *
    * @return array
    */
   private function getPrincipalFromGroupFields(array $group_fields) {
      return [
         'id'                    => $group_fields['id'],
         'uri'                   => $this->getGroupPrincipalUri($group_fields['id']),
         Property::DISPLAY_NAME  => $group_fields['name'],
         Property::CAL_USER_TYPE => 'GROUP',
      ];
   }
}
