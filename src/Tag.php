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
     * Get itemtypes can be tagged with this tag
     *
     * @return array List of itemtypes
     */
    public function getItemtypes(): array
    {
        return Tag_Itemtype::getItemtypesByTag($this);
    }

    /**
     * Format and check itemtypes input for add and update
     *
     * @param array $input Input data
     * @return array Formatted itemtypes
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
     * Prepare input for add action
      *
      * @param array $input Input data
      * @return array Prepared input
      */
    public function prepareInputForAdd($input): array
    {
        $input = parent::prepareInputForAdd($input);
        return $this->prepareItemtypes($input);
    }

    /**
     * Prepare input for update action
      *
      * @param array $input Input data
      * @return array Prepared input
      */
    public function prepareInputForUpdate($input): array
    {
        $input = parent::prepareInputForUpdate($input);
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

    /**
     * Add itemtypes associations after adding a tag
      *
      * @param array $input Input data
     */
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

    /**
     * Update itemtypes associations after updating a tag
      *
      * @param bool $history Whether to keep history of changes
     */
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
     * Show form for tag
      *
      * @param int $ID ID of the tag to show
      * @param array $options Additional options for the form

      * @return bool Whether the form was successfully shown
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
