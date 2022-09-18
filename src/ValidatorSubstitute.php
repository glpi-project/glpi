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

use Glpi\Application\View\TemplateRenderer;

class ValidatorSubstitute extends CommonDBTM
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Preference::class:
                return __('Authorized substitute');
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Preference::class:
                $user = User::getById(Session::getLoginUserID());
                $substitute = new ValidatorSubstitute();
                return $substitute->showForUser($user);
        }

        return false;
    }

    public static function canView()
    {
        return true;
    }

    public static function canCreate()
    {
        return true;
    }

    public static function canDelete()
    {
        return true;
    }

    public static function canPurge()
    {
        return true;
    }

    public function canViewItem()
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canCreateItem()
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }

        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canUpdateItem()
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canDeleteItem()
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canPurgeItem()
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canEdit($ID)
    {
        if ($ID == Session::getLoginUserID()) {
            return true;
        }

        $user = new User();
        if ($user->currentUserHaveMoreRightThan($ID)) {
            return true;
        }

        return false;
    }

    public function showForUser(CommonDBTM $item): bool
    {
        if ($item->isNewItem()) {
            return false;
        }

        /** @var User $item */
        if (
            ($item->fields['id'] != Session::getLoginUserID())
            && !$item->currentUserHaveMoreRightThan($item->fields['id'])
        ) {
            return false;
        }

        // the user cannot select himself as a substitute
        TemplateRenderer::getInstance()->display('pages/admin/user.substitute.html.twig', [
            'item'        => $this,
            'user'        => $item,
            'substitutes' => self::getSubstitutes($item->fields['id']),
            'delegators'  => self::getDelegators($item->fields['id']),
            'params'      => [
                'target'      => self::getFormURL(),
                'canedit'     => true,
                'candel'      => false,
            ]
        ]);

        return true;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['users_id']) && $input['users_id'] != $this->fields['users_id']) {
            // Do not change the user.
            Session::addMessageAfterRedirect(__('Cannot change the validation delegator'));
            return [];
        }

        return $input;
    }

    /**
     * Update the substitutes list for the current user
     *
     * @param array $input
     * @return boolean
     */
    public function updateSubstitutes($input): bool
    {
        $validator_substitute = new self();

        // The user to be substituted
        $validator = $input['users_id'];
        if ($validator != Session::getLoginUserID()) {
            Session::addMessageAfterRedirect(__('You cannot change substitutes for this user'), true, ERROR);
            return false;
        }

        // Get the old substitutes list
        $old_substitutes = self::getSubstitutes($validator);

        // Store the overall sucess of the changes below
        $success = true;

        if (isset($input['substitutes'])) {
            if (in_array($input['users_id'], $input['substitutes'])) {
                Session::addMessageAfterRedirect(__('Cannot add a user as substitute of himself'), true, ERROR);
                return false;
            }

            // Delete old substitutes which are not in the new substitutes list
            $substitutes_to_delete = array_diff($old_substitutes, $input['substitutes']);
            if (count($substitutes_to_delete) > 0) {
                $success = $success && $validator_substitute->deleteByCriteria([
                    'users_id' => $validator,
                    'users_id_substitute' => $substitutes_to_delete,
                ]);
            }

            // Add the new substitutes which are not in the old substitutes list
            $substitutes_to_add = array_diff($input['substitutes'], $old_substitutes);
            foreach ($substitutes_to_add as $substitute) {
                $success = $success && $validator_substitute->add([
                    'users_id' => $validator,
                    'users_id_substitute' => $substitute,
                ]);
            }
        }

        $user = User::getById($input['users_id']);

        $input['substitution_start_date'] = $input['substitution_start_date'] ?? $user->fields['substitution_start_date'];
        $input['substitution_start_date'] = $input['substitution_start_date'] ?? '';

        $input['substitution_end_date'] = $input['substitution_end_date'] ?? $user->fields['substitution_end_date'];
        $input['substitution_end_date'] = $input['substitution_end_date'] ?? '';

        // Check sanity of substitution date range
        if ($input['substitution_start_date'] != '' && $input['substitution_end_date'] != '') {
            if ($input['substitution_end_date'] < $input['substitution_start_date']) {
                $input['substitution_end_date'] = $input['substitution_start_date'];
            }
        } else {
            $input['substitution_start_date'] = $input['substitution_start_date'] == '' ? 'NULL' : $input['substitution_start_date'];
            $input['substitution_end_date']   = $input['substitution_end_date'] == '' ? 'NULL' : $input['substitution_end_date'];
        }

        // Update begin and end date to apply substitutes
        $success = $success && $user->update([
            'id'                      => $input['users_id'],
            'substitution_start_date' => $input['substitution_start_date'],
            'substitution_end_date'   => $input['substitution_end_date'],
        ]);

        return $success;
    }

    /**
     * Get all valdiation substitutes for the given user ID
     *
     * @param int $user_id
     * @return array
     */
    public static function getSubstitutes(int $user_id): array
    {
        $substitutes = [];
        $rows = (new self())->find([
            'users_id' => $user_id,
        ]);
        foreach ($rows as $row) {
            $substitutes[] = $row['users_id_substitute'];
        }

        return $substitutes;
    }

    /**
     * Get all delegators for the given user ID
     *
     * @param integer $user_id
     * @return array
     */
    public static function getDelegators(int $user_id): array
    {
        $delegators = [];
        $rows = (new self())->find([
            'users_id_substitute' => $user_id,
        ]);
        foreach ($rows as $row) {
            $delegators[] = $row['users_id'];
        }

        return $delegators;
    }

    /**
     * Is the user a substitute of an other user ?
     *
     * @param integer $users_id
     * @param integer $users_id_delegator
     * @param bool    $use_date_range
     * @return boolean
     */
    public static function isUserSubstituteOf(int $users_id, int $users_id_delegator, bool $use_date_range = true): bool
    {
        global $DB;

        $request = [
            'FROM' => self::getTable(),
            'WHERE' => [
                self::getTableField('users_id')            => $users_id_delegator,
                self::getTableField('users_id_substitute') => $users_id,
            ],
        ];
        if ($use_date_range) {
            // add date range check
            $request['INNER JOIN'] = [
                User::getTable() => [
                    'ON' => [
                        User::getTable() => 'id',
                        self::getTable() => 'users_id',
                    ],
                    'AND' => [
                        [
                            'OR' => [
                                [
                                    User::getTableField('substitution_end_date') => null
                                ], [
                                    User::getTableField('substitution_end_date') => ['>=', new QueryExpression('NOW()')],
                                ],
                            ],
                        ], [
                            'OR' => [
                                [
                                    User::getTableField('substitution_start_date') => null,
                                ], [
                                    User::getTableField('substitution_start_date') => ['<=', new QueryExpression('NOW()')],
                                ],
                            ],
                        ]
                    ]
                ],
            ];
        }

        $result = $DB->request($request);

        return (count($result) > 0);
    }
}
