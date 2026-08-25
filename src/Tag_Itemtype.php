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
    // From CommonDBChild
    public static string $itemtype = Tag::class;
    public static string $items_id = 'tags_id';

    /**
     * @param array<string, mixed> $input
     * @return false|array<string, mixed>
     */
    public function prepareInputForAdd($input)
    {
        global $CFG_GLPI;

        $input = parent::prepareInputForAdd($input);

        if (!is_array($input)) {
            return false;
        }

        if (!isset($input['itemtype']) || !in_array($input['itemtype'], $CFG_GLPI['taggable_types'], true)) {
            return false;
        }

        return $input;
    }

    /**
     * Get itemtypes for a given tag
     *
     * @param Tag  $tag          Tag for which itemtypes must be retrieved
     * @param bool $all_if_empty If true and the tag has no restriction in DB, return all taggable itemtypes instead of an empty list.
     *
     * @return list<class-string<CommonDBTM>>
     */
    public static function getItemtypesByTag(Tag $tag, bool $all_if_empty = true): array
    {
        if ($tag->getID() <= 0) {
            return [];
        }

        $tag_itemtype = new self();
        $itemtypes = array_column($tag_itemtype->find(['tags_id' => $tag->getID()]), 'itemtype');

        if ($all_if_empty && count($itemtypes) === 0) {
            return self::getTaggableItems();
        }

        return $itemtypes;
    }

    /**
     * Get tags allowed to be attached to items of the given itemtype.
     *
     * @param class-string<CommonGLPI> $itemtype
     *
     * @return iterable<Tag>
     */
    public static function getTagsByItemtype(string $itemtype): iterable
    {
        global $DB;

        $tag_table = Tag::getTable();

        if (!Tag_Itemtype::isTaggableItemtype($itemtype)) {
            return [];
        }

        return Tag::getFromIter($DB->request([
            'SELECT' => [$tag_table . '.id'],
            'FROM' => Tag::getTable(),
            'LEFT JOIN' => [
                self::getTable() => [
                    'ON' => [
                        $tag_table => 'id',
                        self::getTable() => 'tags_id',
                    ],
                ],
            ],
            'WHERE' => [
                'is_active' => 1,
                [
                    'OR' => [
                        ['itemtype' => $itemtype],
                        ['itemtype' => null],
                    ],
                ],
                getEntitiesRestrictCriteria($tag_table, '', '', true),
            ],
        ]));
    }

    /**
     * Get tags allowed to be attached to items of ALL the given itemtypes (intersection).
     *
     * @param list<class-string<CommonGLPI>> $itemtypes
     *
     * @return list<Tag>
     */
    public static function getTagsByItemtypes(array $itemtypes): array
    {
        if ($itemtypes === []) {
            return [];
        }

        $common_tags = null;

        foreach ($itemtypes as $itemtype) {
            $tags_for_itemtype = [];
            foreach (self::getTagsByItemtype($itemtype) as $tag) {
                $tags_for_itemtype[$tag->getID()] = clone $tag;
            }

            if ($common_tags === null) {
                $common_tags = $tags_for_itemtype;
            } else {
                $common_tags = array_intersect_key($common_tags, $tags_for_itemtype);
            }
        }

        return $common_tags;
    }

    /**
     * Remove all tag associations for a plugin's itemtypes.
     *
     * @param string $plugin_directory  plugin directory for which all tag associations must be removed
     *
     * @return void
     */
    public static function deleteForItemtype(string $plugin_directory): void
    {
        global $DB;

        $tag_itemtype = new self();
        $legacy_prefix = 'Plugin' . ucfirst($plugin_directory);
        $namespace_prefix = 'GlpiPlugin\\' . ucfirst($plugin_directory) . '\\';

        $iterator = $DB->request([
            'SELECT' => ['id', 'itemtype'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR' => [
                    ['itemtype'  => ['LIKE', '%' . $legacy_prefix . '%']],
                    ['itemtype'  => ['LIKE', 'GlpiPlugin\\\\' . ucfirst($plugin_directory) . '\\\\%']],
                ],
            ],
        ]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];

            // Check if the itemtype is a legacy plugin class or a namespaced plugin class
            $is_legacy_class = str_starts_with($itemtype, $legacy_prefix)
                && ctype_upper($itemtype[strlen($legacy_prefix)]);
            $is_namespace = str_starts_with($itemtype, $namespace_prefix);

            if ($is_legacy_class || $is_namespace) {
                $tag_itemtype->delete([
                    'id' => $row['id'],
                ], true);
            }
        }
    }

    /**
     * Check if an itemtype is allowed to be tagged.
     *
     * @param string $itemtype The itemtype to check.
     *
     * @return bool True if the itemtype is allowed to be tagged, false otherwise.
     */
    public static function isTaggableItemtype(string $itemtype): bool
    {
        return in_array($itemtype, self::getTaggableItems(), true);
    }

    /**
     * Get all itemtypes that can be tagged, regardless of any per-tag restriction.
     *
     * @return list<class-string<CommonDBTM>>
     */
    public static function getTaggableItems(): array
    {
        global $CFG_GLPI;

        return $CFG_GLPI['taggable_types'];
    }
}
