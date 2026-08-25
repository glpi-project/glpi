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

class Tag_Item extends CommonDBRelation
{
    public static ?string $itemtype_1 = Tag::class;
    public static ?string $items_id_1 = 'tags_id';
    public static bool $take_entity_1 = true;

    public static ?string $itemtype_2 = 'itemtype';
    public static ?string $items_id_2 = 'items_id';
    public static bool $take_entity_2 = false;

    /**
     * Attach a tag to an item.
     *
     * @param CommonDBTM $asset The item to which the tag should be attached.
     * @param int $tag_id The ID of the tag to attach.
     *
     * @return bool True if the tag was successfully attached, false otherwise.
     */
    public static function addTag(CommonDBTM $asset, int $tag_id): bool
    {
        if (!$asset->isTaggable() || $asset->isNewItem() || self::hasTag($asset, $tag_id)) {
            return false;
        }

        $tag = new Tag();
        if (!$tag->getFromDB($tag_id) || !$tag->checkEntity(true)) {
            return false;
        }

        // Check if the tag is allowed to be attached to the given itemtype
        $allowed_itemtypes = $tag->getItemtypes();
        if ($allowed_itemtypes === []) {
            $allowed_itemtypes = Tag_Itemtype::getTaggableItems();
        }

        // Check if the asset's itemtype is in the list of allowed itemtypes
        if (!in_array($asset::class, $allowed_itemtypes, true)) {
            return false;
        }

        return (bool) (new self())->add([
            'tags_id'  => $tag_id,
            'itemtype' => $asset::class,
            'items_id' => $asset->getID(),
        ]);
    }

    /**
     * Detach a tag from an item.
     *
     * @param CommonDBTM $asset The item from which the tag should be detached.
     * @param int $tag_id The ID of the tag to detach.
     *
     * @return bool True if the tag was successfully detached, false otherwise.
     */
    public static function removeTag(CommonDBTM $asset, int $tag_id): bool
    {
        if (!$asset->isTaggable() || $asset->isNewItem() || !self::hasTag($asset, $tag_id)) {
            return false;
        }

        return (new self())->deleteByCriteria([
            'tags_id'  => $tag_id,
            'itemtype' => $asset::class,
            'items_id' => $asset->getID(),
        ]);
    }

    /**
     * Replace a tag on an item with another tag.
     *
     * @param CommonDBTM $asset The item on which the tag should be replaced.
     * @param int $old_tag_id The ID of the tag to be replaced.
     * @param int $new_tag_id The ID of the new tag.
     *
     * @return bool True if the tag was successfully replaced, false otherwise.
     */
    public static function replaceTag(CommonDBTM $asset, int $old_tag_id, int $new_tag_id): bool
    {
        return self::removeTag($asset, $old_tag_id) && self::addTag($asset, $new_tag_id);
    }

    /**
     * Clean all tags attached to an item.
     *
     * @param CommonDBTM $asset The item from which all tags should be detached.
     *
     * @return bool True if all tags were successfully detached, false otherwise.
     */
    public static function cleanTag(CommonDBTM $asset): bool
    {
        if (!$asset->isTaggable() || $asset->isNewItem()) {
            return false;
        }

        return (new self())->deleteByCriteria([
            'itemtype' => $asset::class,
            'items_id' => $asset->getID(),
        ]);
    }

    /**
     * Check if an item has a specific tag attached.
     *
     * @param CommonDBTM $asset The item to check.
     * @param int $tag_id The ID of the tag to check for.
     *
     * @return bool True if the tag is attached to the item, false otherwise.
     */
    public static function hasTag(CommonDBTM $asset, int $tag_id): bool
    {
        if (!$asset->isTaggable() || $asset->isNewItem()) {
            return false;
        }

        return count((new self())->find([
            'tags_id'  => $tag_id,
            'itemtype' => $asset::class,
            'items_id' => $asset->getID(),
        ])) > 0;
    }

    /**
     * Get IDs of tags currently attached to the given item.
     *
     * @param CommonDBTM $asset The item for which to retrieve attached tag IDs.
     *
     * @return list<int>
     */
    public static function getTagsForItem(CommonDBTM $item): array
    {
        if (!$item->isTaggable() || $item->isNewItem()) {
            return [];
        }

        return array_map('intval', array_column(
            (new self())->find([
                'itemtype' => $item::class,
                'items_id' => $item->getID(),
            ]),
            'tags_id'
        ));
    }

    public static function getRelationMassiveActionsSpecificities(): array
    {
        $specificities = parent::getRelationMassiveActionsSpecificities();

        $specificities['itemtypes'] = Tag_Itemtype::getTaggableItems();
        $specificities['button_labels']['add']    = _sx('button', 'Add a tag');
        $specificities['button_labels']['remove'] = _sx('button', 'Remove a tag');

        return $specificities;
    }

    /**
     * @param MassiveAction $ma
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        $action = $ma->getAction();

        if (!in_array($action, ['add', 'remove'], true)) {
            return parent::showMassiveActionsSubForm($ma);
        }

        $specificities = static::getRelationMassiveActionsSpecificities();

        $options = ['name' => 'peer_tags_id'];
        if ($action === 'remove' && $specificities['can_remove_all_at_once']) {
            $options['emptylabel'] = __('Remove all at once');
        }

        $itemtypes = array_keys($ma->getItems());
        $options['_itemtype'] = $itemtypes;

        Tag::dropdown($options);

        echo "<br><br>" . Html::submit(
            $specificities['button_labels'][$action],
            ['name' => 'massiveaction']
        );

        return true;
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array<int> $ids
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ): void {
        $action = $ma->getAction();
        $input = $ma->getInput();

        switch ($action) {
            case 'add':
                $tag_id = (int) ($input['peer_tags_id'] ?? 0);
                foreach ($ids as $id) {
                    $item->fields['id'] = $id;
                    if ($tag_id > 0 && self::addTag($item, $tag_id)) {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                    }
                }
                break;
            case 'remove':
                $tag_id = (int) ($input['peer_tags_id'] ?? 0);
                foreach ($ids as $id) {
                    $item->fields['id'] = $id;
                    $success = $tag_id > 0
                        ? self::removeTag($item, $tag_id)
                        : self::cleanTag($item);
                    if ($success) {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                    }
                }
                break;
            default:
                parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
                break;
        }
    }
}
