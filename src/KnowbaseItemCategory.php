<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\UI\IllustrationManager;

use function Safe\json_decode;
use function Safe\json_encode;

/// Class KnowbaseItemCategory
class KnowbaseItemCategory extends CommonTreeDropdown
{
    // From CommonDBTM
    public bool $dohistory          = true;
    public bool $can_be_translated  = true;

    public static string $rightname          = 'knowbasecategory';

    public const SEEALL = -1;

    public static function getTypeName($nb = 0)
    {
        return _n('Knowledge base category', 'Knowledge base categories', $nb);
    }

    public static function canView(): bool
    {
        if (Session::getCurrentInterface() == "helpdesk") {
            return true;
        }

        return parent::canView();
    }

    public static function getIcon()
    {
        return KnowbaseItem::getIcon();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdditionalFields()
    {
        $fields = parent::getAdditionalFields();

        $fields[] = [
            'name'                 => 'illustration',
            'type'                 => 'illustration',
            'label'                => __('Illustration'),
            'default_illustration' => '',
        ];

        return $fields;
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if (!is_array($input)) {
            return $input;
        }
        return $this->prepareIllustrationInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        if (!is_array($input)) {
            return $input;
        }
        return $this->prepareIllustrationInput($input);
    }

    /**
     * Drop the `illustration` field from $input when it is neither a known
     * native icon nor an existing custom illustration file. The picker UI
     * always submits valid values; this guards against direct POSTs.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function prepareIllustrationInput(array $input): array
    {
        if (!array_key_exists('illustration', $input)) {
            return $input;
        }

        $manager = new IllustrationManager();
        if (!$manager->isKnownIllustrationValue((string) $input['illustration'])) {
            unset($input['illustration']);
        }

        return $input;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                KnowbaseItem_KnowbaseItemCategory::class,
            ]
        );
    }

    /**
     * Ids of the KB aside categories the current user has collapsed.
     * @return int[]
     */
    public static function getFoldedCategoryIdsForCurrentUser(): array
    {
        $user_id = Session::getLoginUserID();
        if ($user_id === false) {
            return [];
        }

        $user = new User();
        if (!$user->getFromDB($user_id)) {
            return [];
        }

        $ids = json_decode($user->fields['folded_knowbaseitems'] ?? '[]', true);

        return array_values(is_array($ids) ? $ids : []);
    }

    /**
     * Persist whether a category is collapsed for the current user.
     */
    public static function setCategoryFoldedForCurrentUser(
        int $category_id,
        bool $folded
    ): void {
        $user_id = Session::getLoginUserID();
        if ($user_id === false) {
            return;
        }

        $ids = array_values(array_filter(
            self::getFoldedCategoryIdsForCurrentUser(),
            static fn(int $id): bool => $id !== $category_id,
        ));
        if ($folded) {
            $ids[] = $category_id;
        }

        (new User())->update([
            'id'                   => $user_id,
            'folded_knowbaseitems' => json_encode($ids),
        ]);
    }
}
