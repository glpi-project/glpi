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

use Glpi\Application\View\TemplateRenderer;

final class ValidatorSubstitute extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n('Authorized substitute', 'Authorized substitutes', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Preference) {
            $user = User::getById(Session::getLoginUserID());
            if ($user instanceof User) {
                $nb = $_SESSION['glpishow_count_on_tabs'] ? count($user->getSubstitutes()) : 0;
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
            }
        }

        return '';
    }

    public static function getIcon()
    {
        return 'ti ti-replace-user';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Preference) {
            $user = User::getById(Session::getLoginUserID());
            if ($user instanceof User) {
                $substitute = new ValidatorSubstitute();
                return $substitute->showForUser($user);
            }
        }

        return false;
    }

    public static function canView(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canDelete(): bool
    {
        return true;
    }

    public static function canPurge(): bool
    {
        return true;
    }

    public function canViewItem(): bool
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canCreateItem(): bool
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }

        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canUpdateItem(): bool
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canDeleteItem(): bool
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function canPurgeItem(): bool
    {
        if (!isset($this->fields['users_id'])) {
            return false;
        }
        return $this->fields['users_id'] == Session::getLoginUserID();
    }

    public function showForUser(User $user): bool
    {
        if ($user->isNewItem()) {
            return false;
        }

        $can_edit = ($user->fields['id'] == Session::getLoginUserID());
        if (!$can_edit) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/admin/user.substitute.html.twig', [
            'item'        => $this,
            'user'        => $user,
            'substitutes' => $user->getSubstitutes(),
            'delegators'  => $user->getDelegators(),
            'canedit'     => $can_edit,
        ]);

        return true;
    }

    public function prepareInputForUpdate($input): array
    {
        if (isset($input['users_id']) && $input['users_id'] != $this->fields['users_id']) {
            // Do not change the user.
            Session::addMessageAfterRedirect(__s('Cannot change the approval delegator'));
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
        if ($input['users_id'] != Session::getLoginUserID()) {
            Session::addMessageAfterRedirect(__s('You cannot change substitutes for this user.'), true, ERROR);
            return false;
        }
        $user = User::getById($input['users_id']);
        if (!($user instanceof User)) {
            return false;
        }

        // Get the old substitutes list
        $old_substitutes = $user->getSubstitutes();

        // Store the overall success of the changes below
        $success = true;

        if (isset($input['substitutes'])) {
            if (empty($input['substitutes'])) {
                $input['substitutes'] = [];
            }
            if (in_array($input['users_id'], $input['substitutes'])) {
                Session::addMessageAfterRedirect(__s('A user cannot be their own substitute.'), true, ERROR);
                return false;
            }

            // Delete old substitutes which are not in the new substitutes list
            $substitutes_to_delete = array_diff($old_substitutes, $input['substitutes']);
            if (count($substitutes_to_delete) > 0) {
                $success = $validator_substitute->deleteByCriteria([
                    'users_id' => $user->fields['id'],
                    'users_id_substitute' => $substitutes_to_delete,
                ]);
            }

            // Add the new substitutes which are not in the old substitutes list
            $substitutes_to_add = array_diff($input['substitutes'], $old_substitutes);
            foreach ($substitutes_to_add as $substitute) {
                $success = $validator_substitute->add([
                    'users_id' => $user->fields['id'],
                    'users_id_substitute' => $substitute,
                ]) && $success;
            }
        }

        $start_date = $input['substitution_start_date'] ?? $user->fields['substitution_start_date'];
        $input['substitution_start_date'] = is_string($start_date) && strtotime($start_date) !== false ? $start_date : null; //@phpstan-ignore theCodingMachineSafe.function (false is explicitly tested)

        $end_date = $input['substitution_end_date'] ?? $user->fields['substitution_end_date'];
        $input['substitution_end_date'] = is_string($end_date) && strtotime($end_date) !== false ? $end_date : null; //@phpstan-ignore theCodingMachineSafe.function (false is explicitly tested)

        // Check sanity of substitution date range
        if ($input['substitution_start_date'] !== null && $input['substitution_end_date'] !== null) {
            if ($input['substitution_end_date'] < $input['substitution_start_date']) {
                $input['substitution_end_date'] = $input['substitution_start_date'];
            }
        } else {
            $input['substitution_start_date'] ??= 'NULL';
            $input['substitution_end_date'] ??= 'NULL';
        }

        // Update begin and end date to apply substitutes
        $success = $user->update([
            'id'                      => $input['users_id'],
            'substitution_start_date' => $input['substitution_start_date'],
            'substitution_end_date'   => $input['substitution_end_date'],
        ]) && $success;

        return $success;
    }
}
