<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

class Tag_Itemtype extends CommonDBChild
{
    // From CommonDbChild
    public static string $itemtype = Tag::class;
    public static string $items_id = 'tags_id';

    /**
     * Get itemtypes for a given tag
     *
     * @param Tag $tag Tag for which itemtypes must be retrieved
     *
     * @return list<class-string<CommonDBTM>>
     */
    public static function getItemtypesByTag(Tag $tag): array
    {
        if ($tag->getID() <= 0) {
            return [];
        }

        $tag_itemtype = new self();
        return array_column($tag_itemtype->find(['tags_id' => $tag->getID()]), 'itemtype');
    }

    /**
     * Remove all associations for an itemtype
     *
     * @param class-string<CommonDBTM> $itemtype  itemtype for which all tag associations must be removed
     *
     * @return void
     */
    public static function deleteForItemtype($itemtype)
    {
        global $DB;

        $DB->delete(
            self::getTable(),
            [
                'itemtype'  => ['LIKE', "%Plugin$itemtype%"],
            ]
        );
    }
}
