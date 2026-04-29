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


/**
 * Class KnowbaseItem_Revision
 * @since 9.2.0
 * @todo Extend CommonDBChild
 */
final class KnowbaseItem_Revision extends CommonDBTM
{
    public static string $rightname   = 'knowbase';

    #[Override]
    public static function getTypeName($nb = 0): string
    {
        return _n('Revision', 'Revisions', $nb);
    }

    #[Override]
    public static function getIcon(): string
    {
        return 'ti ti-history';
    }

    /**
     * Populate and create a new revision from KnowbaseItem or KnowbaseItemTranslation information
     *
     * @param KnowbaseItem|KnowbaseItemTranslation $item Knowledge base item
     *
     * @return int|false ID of the revision created, or false on error
     */
    public function createNew(KnowbaseItem|KnowbaseItemTranslation $item): int|false
    {
        $this->getEmpty();
        unset($this->fields['id']);
        $is_translation = $item::class === KnowbaseItemTranslation::class;
        $this->fields['knowbaseitems_id'] = $item->fields[$is_translation ? 'knowbaseitems_id' : 'id'];
        $this->fields['name'] = $item->fields['name'];
        $this->fields['answer'] = $item->fields['answer'];
        $this->fields['date'] = $item->fields['date_mod'];
        if ($is_translation) {
            $this->fields['language'] = $item->fields['language'];
        }
        $this->fields['revision'] = $this->getNewRevision();
        $this->fields['users_id'] = $item->fields['users_id'];
        return $this->addToDB();
    }

    /**
     * Get new revision number for item
     */
    private function getNewRevision(): int
    {
        global $DB;

        $result = $DB->request([
            'SELECT' => ['MAX' => 'revision AS revision'],
            'FROM'   => 'glpi_knowbaseitems_revisions',
            'WHERE'  => [
                'knowbaseitems_id'   => $this->fields['knowbaseitems_id'],
                'language'           => $this->fields['language'],
            ],
        ])->current();

        $rev = $result['revision'];
        if ($rev === null) {
            // no revisions yet
            $rev = 1;
        } else {
            ++$rev;
        }

        return $rev;
    }
}
