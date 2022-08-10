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
                $substitute->showForUser($user);
                return true;
        }
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

    public function showForUser(CommonDBTM $item)
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
            Session::addMessageAfterRedirect(__('You cannot change substitutes for this user'));
            return false;
        }

        // Get the old substitutes list
        $old_substitutes = self::getSubstitutes($validator);

        // Store the overall sucess of the changes below
        $success = true;

        // Delete old substitutes which are not in the new substitutes list
        $substitutes_to_delete = array_diff($old_substitutes, $input['substitutes']);
        if (count($substitutes_to_delete) > 0) {
            $success = $success && $validator_substitute->deleteByCriteria([
                'users_id' => $validator,
                'users_id_substitute' => $substitutes_to_delete,
            ]);
        }

        // Find the delegators of the validator
        $delegators = self::getDelegators($validator);

        // Add the new substitutes which are not in the old substitutes list
        $substitutes_to_add = array_diff($input['substitutes'], $old_substitutes);
        foreach ($substitutes_to_add as $substitute) {
            $success = $success && $validator_substitute->add([
                'users_id' => $validator,
                'users_id_substitute' => $substitute,
            ]);
        }

        // Check sanity of substitution date range
        if ($input['substitution_start_date'] != '' && $input['substitution_end_date'] != '') {
            if ($input['substitution_end_date'] < $input['substitution_start_date']) {
                $input['substitution_end_date'] = $input['substitution_start_date'];
            }
        }
        $input['substitution_start_date'] = $input['substitution_start_date'] == '' ? 'NULL' : $input['substitution_start_date'];
        $input['substitution_end_date'] = $input['substitution_end_date'] == '' ? 'NULL' : $input['substitution_end_date'];

        // Update begin and end date to apply substitutes
        $success = $success && (new User())->update([
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
}
