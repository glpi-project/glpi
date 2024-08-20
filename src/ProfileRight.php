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

/**
 * Profile class
 *
 * @since 0.85
 **/
class ProfileRight extends CommonDBChild
{
   // From CommonDBChild:
    public static $itemtype = 'Profile';
    public static $items_id = 'profiles_id'; // Field name
    public $dohistory       = true;


    /**
     * Get possible rights
     *
     * @return array
     */
    public static function getAllPossibleRights()
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $rights = $GLPI_CACHE->get('all_possible_rights', []);

        if (count($rights) == 0) {
            $iterator = $DB->request([
                'SELECT'          => 'name',
                'DISTINCT'        => true,
                'FROM'            => self::getTable()
            ]);
            foreach ($iterator as $right) {
                // By default, all rights are NULL ...
                $rights[$right['name']] = '';
            }
            $GLPI_CACHE->set('all_possible_rights', $rights);
        }

        return $rights;
    }


    public static function cleanAllPossibleRights()
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;
        $GLPI_CACHE->delete('all_possible_rights');
    }

    /**
     * @param $profiles_id
     * @param $rights         array
     **/
    public static function getProfileRights($profiles_id, array $rights = [])
    {
        /** @var \DBmysql $DB */
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
        foreach ($iterator as $right) {
            $rights[$right['name']] = $right['rights'];
        }
        return $rights;
    }


    /**
     * @param $rights   array
     *
     * @return boolean
     **/
    public static function addProfileRights(array $rights)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $ok = true;
        $GLPI_CACHE->set('all_possible_rights', []);

        $iterator = $DB->request([
            'SELECT'   => ['id'],
            'FROM'     => Profile::getTable()
        ]);

        foreach ($iterator as $profile) {
            $profiles_id = $profile['id'];
            foreach ($rights as $name) {
                $res = $DB->insert(
                    self::getTable(),
                    [
                        'profiles_id'  => $profiles_id,
                        'name'         => $name
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
     * @param $rights   array
     *
     * @return boolean
     **/
    public static function deleteProfileRights(array $rights)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $GLPI_CACHE->set('all_possible_rights', []);
        $ok = true;
        foreach ($rights as $name) {
            $result = $DB->delete(
                self::getTable(),
                [
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
    public static function updateProfileRightAsOtherRight($right, $value, $condition)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $profiles = [];
        $ok       = true;
        foreach ($DB->request(self::getTable(), $condition) as $data) {
            $profiles[] = $data['profiles_id'];
        }
        if (count($profiles)) {
            $result = $DB->update(
                'glpi_profilerights',
                [
                    'rights' => new \QueryExpression($DB->quoteName('rights') . ' | ' . (int)$value)
                ],
                [
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
    public static function updateProfileRightsAsOtherRights($newright, $initialright, array $condition = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $profiles = [];
        $ok       = true;

        $criteria = [
            'FROM'   => self::getTable(),
            'WHERE'  => ['name' => $initialright] + $condition
        ];
        $iterator = $DB->request($criteria);

        foreach ($iterator as $data) {
            $profiles[$data['profiles_id']] = $data['rights'];
        }
        if (count($profiles)) {
            foreach ($profiles as $key => $val) {
                $res = $DB->update(
                    self::getTable(),
                    [
                        'rights' => $val
                    ],
                    [
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
    public static function fillProfileRights($profiles_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $subq = new \QuerySubQuery([
            'FROM'   => 'glpi_profilerights AS CURRENT',
            'WHERE'  => [
                'CURRENT.profiles_id'   => $profiles_id,
                'CURRENT.NAME'          => new \QueryExpression('POSSIBLE.NAME')
            ]
        ]);

        $expr = 'NOT EXISTS ' . $subq->getQuery();
        $iterator = $DB->request([
            'SELECT'          => 'POSSIBLE.name AS NAME',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_profilerights AS POSSIBLE',
            'WHERE'           => [
                new \QueryExpression($expr)
            ]
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $query = $DB->buildInsert(
            self::getTable(),
            [
                'profiles_id' => new QueryParam(),
                'name'        => new QueryParam(),
            ]
        );
        $stmt = $DB->prepare($query);
        foreach ($iterator as $right) {
            $stmt->bind_param('ss', $profiles_id, $right['NAME']);
            $DB->executeStatement($stmt);
        }
    }


    /**
     * Update the rights of a profile (static since 0.90.1)
     *
     * @param $profiles_id
     * @param $rights         array
     */
    public static function updateProfileRights($profiles_id, array $rights = [])
    {

        $me = new self();
        foreach ($rights as $name => $right) {
            if (isset($right)) {
                if (
                    $me->getFromDBByCrit(['profiles_id'   => $profiles_id,
                        'name'          => $name
                    ])
                ) {
                    $input = ['id'          => $me->getID(),
                        'rights'      => $right
                    ];
                    $me->update($input);
                } else {
                    $input = ['profiles_id' => $profiles_id,
                        'name'        => $name,
                        'rights'      => $right
                    ];
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
    public function post_updateItem($history = true)
    {

       // update current profile
        if (
            isset($_SESSION['glpiactiveprofile']['id'])
            && $_SESSION['glpiactiveprofile']['id'] == $this->fields['profiles_id']
            && (!isset($_SESSION['glpiactiveprofile'][$this->fields['name']])
              || $_SESSION['glpiactiveprofile'][$this->fields['name']] != $this->fields['rights'])
        ) {
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
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        $itemtype = $options['searchopt']['rightclass'];
        $item     = new $itemtype();
        $rights   = '';
        $prem     = true;
        foreach ($item->getRights() as $val => $name) {
            if (is_numeric($values['rights']) && ((int)$values['rights'] & $val)) {
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
    public function getLogTypeID()
    {
        return ['Profile', $this->fields['profiles_id']];
    }
}
