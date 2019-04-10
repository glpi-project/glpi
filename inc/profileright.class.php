<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Profile class
 *
 * @since 0.85
**/
class ProfileRight extends CommonDBChild {

   // From CommonDBChild:
   static public $itemtype = 'Profile';
   static public $items_id = 'profiles_id'; // Field name
   public $dohistory       = true;


   /**
    * Get possible rights
    *
    * @return array
    */
   static function getAllPossibleRights() {
      global $DB;

      $appCache = Toolbox::getAppCache();

      $rights = [];

      if ($appCache->has('all_possible_rights')
         && count($appCache->get('all_possible_rights')) > 0) {
         return $appCache->get('all_possible_rights');
      }

      $iterator = $DB->request([
         'SELECT'          => 'name',
         'DISTINCT'        => true,
         'FROM'            => self::getTable()
      ]);
      while ($right = $iterator->next()) {
         // By default, all rights are NULL ...
         $rights[$right['name']] = '';
      }
      $appCache->set('all_possible_rights', $rights);

      return $rights;
   }


   static function cleanAllPossibleRights() {
      $appCache = Toolbox::getAppCache();
      $appCache->delete('all_possible_rights');
   }

   /**
    * Get rights for a profile
    *
    * @param integer $profiles_id Profile ID
    * @param array   $rights      Rihts
    *
    * @return array
    */
   static function getProfileRights($profiles_id, array $rights = []) {
      global $DB;

      if (!version_compare(Config::getCurrentDBVersion(), '0.84', '>=')) {
         //table does not exists.
         return [];
      }

      $query = [
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => ['profiles_id' => $profiles_id]
      ];
      if (count($rights) > 0) {
         $query['WHERE']['name'] = $rights;
      }
      $iterator = $DB->request($query);
      $rights = [];
      while ($right = $iterator->next()) {
         $rights[$right['name']] = $right['rights'];
      }
      return $rights;
   }


   /**
    * @param $rights   array
    *
    * @return boolean
   **/
   static function addProfileRights(array $rights) {
      global $DB;

      $appCache = Toolbox::getAppCache();
      $appCache->set('all_possible_rights', []);

      $ok = true;

      $iterator = $DB->request([
          'SELECT'   => ['id'],
          'FROM'     => Profile::getTable()
      ]);

      $stmt = null;
      while ($profile = $iterator->next()) {
         $profiles_id = $profile['id'];
         foreach ($rights as $name) {
            $params = [
               'profiles_id'  => $profiles_id,
               'name'         => $name
            ];

            if ($stmt === null) {
               $stmt = $DB->prepare($DB->buildInsert(self::getTable(), $params));
            }

            $res = $stmt->execute($params);

            if (!$res) {
               $ok = false;
            }
         }
      }
      return $ok;
   }


   /**
    * @param $rights   array
    *
    * @return boolean
   **/
   static function deleteProfileRights(array $rights) {
      global $DB;

      $appCache = Toolbox::getAppCache();
      $appCache->set('all_possible_rights', []);

      $ok = true;
      foreach ($rights as $name) {
         $result = $DB->delete(
            self::getTable(), [
               'name' => $name
            ]
         );
         if (!$result) {
            $ok = false;
         }
      }
      return $ok;
   }


   /**
    * @param $right
    * @param $value
    * @param $condition
    *
    * @return boolean
   **/
   static function updateProfileRightAsOtherRight($right, $value, $condition) {
      global $DB;

      $profiles = [];
      $ok       = true;
      foreach ($DB->request('glpi_profilerights', $condition) as $data) {
         $profiles[] = $data['profiles_id'];
      }
      if (count($profiles)) {
         $result = $DB->update(
            'glpi_profilerights', [
               'rights' => new \QueryExpression($DB->quoteName('rights') . ' | ' . (int)$value)
            ], [
               'name'         => $right,
               'profiles_id'  => $profiles
            ]
         );
         if (!$result) {
            $ok = false;
         }
      }
      return $ok;
   }


   /**
    * @since 0.85
    *
    * @param $newright      string   new right name
    * @param $initialright  string   right name to check
    * @param $condition              (default '')
    *
    * @return boolean
   **/
   static function updateProfileRightsAsOtherRights($newright, $initialright, $condition = '') {
      global $DB;

      $profiles = [];
      $ok       = true;
      if (empty($condition)) {
         $condition = "`name` = '$initialright'";
      } else {
         $condition = "`name` = '$initialright' AND $condition";
      }
      foreach ($DB->request('glpi_profilerights', $condition) as $data) {
         $profiles[$data['profiles_id']] = $data['rights'];
      }
      if (count($profiles)) {
         foreach ($profiles as $key => $val) {
            $res = $DB->update(
               self::getTable(), [
                  'rights' => $val
               ], [
                  'profiles_id'  => $key,
                  'name'         => $newright
               ]
            );
            if (!$res) {
               $ok = false;
            }
         }
      }
      return $ok;
   }

   /**
    * @param $profiles_id
   **/
   static function fillProfileRights($profiles_id) {
      global $DB;

      $subq =new \QuerySubQuery([
         'FROM'   => 'glpi_profilerights AS CURRENT',
         'WHERE'  => [
            'CURRENT.profiles_id'   => $profiles_id,
            'CURRENT.NAME'          => new \QueryExpression('POSSIBLE.NAME')
         ]
      ]);

      $expr = $DB->mergeStatementWithParams(
         'NOT EXISTS ' . $subq->getQuery(),
         $subq->getParameters()
      );
      $iterator = $DB->request([
         'SELECT'          => 'POSSIBLE.name AS NAME',
         'DISTINCT'        => true,
         'FROM'            => 'glpi_profilerights AS POSSIBLE',
         'WHERE'           => [
            new \QueryExpression($expr)
         ]
      ]);

      $stmt = null;
      while ($right = $iterator->next()) {
         $params = [
            'profiles_id'  => $profiles_id,
            'name'         => $right['NAME']
         ];

         if ($stmt === null) {
            $stmt = $DB->prepare($DB->buildInsert(self::getTable(), $params));
         }
         $stmt->execute($params);
      }
   }


   /**
    * Update the rights of a profile (static since 0.90.1)
    *
    * @param $profiles_id
    * @param $rights         array
    */
   public static function updateProfileRights($profiles_id, array $rights = []) {

      $me = new self();
      foreach ($rights as $name => $right) {
         if (isset($right)) {
            if ($me->getFromDBByCrit(['profiles_id'   => $profiles_id,
                                      'name'          => $name])) {

               $input = ['id'          => $me->getID(),
                         'rights'      => $right];
               $me->update($input);

            } else {
               $input = ['profiles_id' => $profiles_id,
                         'name'        => $name,
                         'rights'      => $right];
               $me->add($input);
            }
         }
      }

      // Don't forget to complete the profile rights ...
      self::fillProfileRights($profiles_id);
   }


   /**
    * To avoid log out and login when rights change (very useful in debug mode)
    *
    * @see CommonDBChild::post_updateItem()
   **/
   function post_updateItem($history = 1) {

      // update current profile
      if (isset($_SESSION['glpiactiveprofile']['id'])
          && $_SESSION['glpiactiveprofile']['id'] == $this->fields['profiles_id']
          && (!isset($_SESSION['glpiactiveprofile'][$this->fields['name']])
              || $_SESSION['glpiactiveprofile'][$this->fields['name']] != $this->fields['rights'])) {

         $_SESSION['glpiactiveprofile'][$this->fields['name']] = $this->fields['rights'];
         unset($_SESSION['glpimenu']);
      }
   }


   /**
    * @since 085
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      $itemtype = $options['searchopt']['rightclass'];
      $item     = new $itemtype();
      $rights   = '';
      $prem     = true;
      foreach ($item->getRights() as $val => $name) {
         if ((is_numeric($values['rights']) && $values['rights']) & $val) {
            if ($prem) {
               $prem = false;
            } else {
               $rights .= ", ";
            }
            if (is_array($name)) {
               $rights .= $name['long'];
            } else {
               $rights .= $name;
            }
         }
      }
      return ($rights ? $rights : __('None'));
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::getLogTypeID()
   **/
   function getLogTypeID() {
      return ['Profile', $this->fields['profiles_id']];
   }
}
