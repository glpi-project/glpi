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

use Glpi\Application\View\TemplateRenderer;

/**
 * Tag class
 */
class Tag extends CommonDropdown
{
    public static string $rightname = 'tag';

    public static function getTypeName($nb = 0): string
    {
        return _n('Tag', 'Tags', $nb);
    }

    /**
     * Get itemtypes that can be tagged with this tag.
     *
     * @return list<class-string<CommonDBTM>>
     */
    public function getItemtypes(): array
    {
        return Tag_Itemtype::getItemtypesByTag($this);
    }

    /**
     * Format and check itemtypes input for add and update
     *
     * @param array<string, mixed> $input Input data
     * @return array<string, mixed> Formatted input
     */
    private function prepareItemtypes(array $input): array
    {
        global $CFG_GLPI;

        if (!isset($input['itemtypes'])) {
            return $input;
        }

        if (!is_array($input['itemtypes'])) {
            $input['itemtypes'] = [];
        }

        $input['itemtypes'] = array_unique($input['itemtypes']);

        // Remove itemtypes which are not taggable
        foreach ($input['itemtypes'] as $key => $itemtype) {
            if (!in_array($itemtype, $CFG_GLPI['taggable_types'])) {
                unset($input['itemtypes'][$key]);
            }
        }

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     * @return false|array<string, mixed>
     */
    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareItemtypes($input);
    }

    /**
     * @param array<string, mixed> $input
     * @return false|array<string, mixed>
     */
    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareItemtypes($input);
    }

    /**
     * Clean all related data in database when purging a tag
     */
    public function cleanDBonPurge(): void
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Tag_Itemtype::class,
            ]
        );
    }

    public function post_addItem(): void
    {
        parent::post_addItem();

        if (!isset($this->input['itemtypes'])) {
            return;
        }

        $tag_itemtype = new Tag_Itemtype();
        foreach ($this->input['itemtypes'] as $itemtype) {
            $tag_itemtype->add([
                'tags_id' => $this->getID(),
                'itemtype' => $itemtype,
            ]);
        }
    }

    public function post_updateItem($history = true): void
    {
        parent::post_updateItem($history);

        if (!isset($this->input['itemtypes'])) {
            return;
        }

        $old_itemtypes = $this->getItemtypes();
        $tag_itemtype = new Tag_Itemtype();
        foreach ($old_itemtypes as $itemtype) {
            if (!in_array($itemtype, $this->input['itemtypes'], true)) {
                $tag_itemtype->deleteByCriteria([
                    'tags_id' => $this->getID(),
                    'itemtype' => $itemtype,
                ]);
            }
        }
        foreach ($this->input['itemtypes'] as $itemtype) {
            if (!in_array($itemtype, $old_itemtypes, true)) {
                $tag_itemtype->add([
                    'tags_id' => $this->getID(),
                    'itemtype' => $itemtype,
                ]);
            }
        }
    }

    /**
     * @param int $ID
     * @param array<string, mixed> $options
     */
    public function showForm($ID, array $options = []): bool
    {
        TemplateRenderer::getInstance()->display('pages/setup/tag.html.twig', [
            'item' => $this,
            'params' => $options,
        ]);
        return true;
    }
}
