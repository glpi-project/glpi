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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryParam;
use Glpi\DBAL\QuerySubQuery;

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
     * {@inheritDoc}
     * @note Unlike the default implementation, this one handles the fact that some or all profile rights
     *       are already in the DB (but set to 0) when the cloned profile is created.
     *       Therefore, we need to use update or insert DB queries rather than `CommonDBTM::add`.
     *       The $clone_as_template parameter is ignored.
     */
    public function clone(array $override_input = [], bool $history = true, bool $clone_as_template = false, bool $clean_mapper = true)
    {
        global $DB;

        if ($DB->isSlave()) {
            return false;
        }
        $new_item = new static();
        $input = $this->fields;
        $input['profiles_id'] = $override_input['profiles_id'];
        unset($input['id']);

        $input = $new_item->prepareInputForClone($input);

        $result = $DB->updateOrInsert(static::getTable(), $input, [
            'name' => $input['name'],
            'profiles_id' => $input['profiles_id'],
        ]);
        if ($result !== false) {
            $new_item->getFromDBByCrit([
                'name' => $input['name'],
                'profiles_id' => $input['profiles_id'],
            ]);
            $new_item->post_clone($this, $history);
        }

        return $new_item->fields['id'];
    }

    /**
     * Get possible rights
     *
     * @return array
     */
    public static function getAllPossibleRights()
    {
        global $DB, $GLPI_CACHE;

        $rights = $GLPI_CACHE->get('all_possible_rights', []);

        if (count($rights) == 0) {
            $iterator = $DB->request([
                'SELECT'          => 'name',
                'DISTINCT'        => true,
                'FROM'            => self::getTable(),
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
        global $GLPI_CACHE;
        $GLPI_CACHE->delete('all_possible_rights');
    }

    /**
     * @param $profiles_id
     * @param $rights         array
     **/
    public static function getProfileRights($profiles_id, array $rights = [])
    {
        global $DB;

        $query = [
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => ['profiles_id' => $profiles_id],
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
        global $DB, $GLPI_CACHE;

        $ok = true;
        $GLPI_CACHE->set('all_possible_rights', []);

        $iterator = $DB->request([
            'SELECT'   => ['id'],
            'FROM'     => Profile::getTable(),
        ]);

        foreach ($iterator as $profile) {
            $profiles_id = $profile['id'];
            foreach ($rights as $name) {
                $res = $DB->insert(
                    self::getTable(),
                    [
                        'profiles_id'  => $profiles_id,
                        'name'         => $name,
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
        global $DB, $GLPI_CACHE;

        $GLPI_CACHE->set('all_possible_rights', []);
        $ok = true;
        foreach ($rights as $name) {
            $result = $DB->delete(
                self::getTable(),
                [
                    'name' => $name,
                ]
            );
            if (!$result) {
                $ok = false;
            }
        }
        return $ok;
    }

    /**
     * @param $profiles_id
     **/
    public static function fillProfileRights($profiles_id)
    {
        global $DB;

        $subq = new QuerySubQuery([
            'FROM'   => 'glpi_profilerights AS CURRENT',
            'WHERE'  => [
                'CURRENT.profiles_id'   => $profiles_id,
                'CURRENT.NAME'          => new QueryExpression('POSSIBLE.NAME'),
            ],
        ]);

        $expr = 'NOT EXISTS ' . $subq->getQuery();
        $iterator = $DB->request([
            'SELECT'          => 'POSSIBLE.name AS NAME',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_profilerights AS POSSIBLE',
            'WHERE'           => [
                new QueryExpression($expr),
            ],
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
                        'name'          => $name,
                    ])
                ) {
                    $input = ['id'          => $me->getID(),
                        'rights'      => $right,
                    ];
                    $me->update($input);
                } else {
                    $input = ['profiles_id' => $profiles_id,
                        'name'        => $name,
                        'rights'      => $right,
                    ];
                    $me->add($input);
                }
            }
        }

        // Don't forget to complete the profile rights ...
        self::fillProfileRights($profiles_id);
    }


    public function post_addItem($history = true)
    {
        // Refresh session rights to avoid log out and login when rights change
        $this->updateProfileLastRightsUpdate($this->fields['profiles_id']);
    }

    public function post_updateItem($history = true)
    {
        // Refresh session rights to avoid log out and login when rights change
        $this->updateProfileLastRightsUpdate($this->fields['profiles_id']);
    }

    /**
     * Update last rights update for given profile.
     *
     * @param int $profile_id
     * @return void
     */
    private function updateProfileLastRightsUpdate(int $profile_id): void
    {
        Profile::getById($profile_id)->update([
            'id'                 => $profile_id,
            'last_rights_update' => Session::getCurrentTime(),
        ]);
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
        if (!($item = getItemForItemtype($itemtype))) {
            return __s('None');
        }
        $rights   = '';
        $prem     = true;
        foreach ($item->getRights() as $val => $name) {
            if (is_numeric($values['rights']) && ((int) $values['rights'] & $val)) {
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
        return htmlescape($rights ?: __('None'));
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
