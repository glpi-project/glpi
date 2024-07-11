<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Features;

use CommonDBConnexity;
use CommonDBTM;
use Session;
use Toolbox;

/**
 * Clonable objects
 **/
trait Clonable
{
    /**
     * Cache used to keep track of the last clone index
     * Usefull when cloning an item multiple time to avoid iterating on the sql
     * results multiple time while looking for an available unique name
     * @var null|int
     */
    protected $last_clone_index = null;

    /**
     * Get relations class to clone along with current element.
     *
     * @return CommonDBTM::class[]
     */
    abstract public function getCloneRelations(): array;

    /**
     * Clean input used to clone.
     *
     * @param array $input
     *
     * @return array
     *
     * @since 10.0.0
     */
    private function cleanCloneInput(array $input): array
    {
        $properties_to_clean = [
            'id',
            'date_mod',
            'date_creation',
            'template_name',
            'is_template',
            'sons_cache',
        ];
        foreach ($properties_to_clean as $property) {
            if (array_key_exists($property, $input)) {
                unset($input[$property]);
            }
        }

        return $input;
    }

    /**
     * Clone the item's relations.
     *
     * @param CommonDBTM $source
     * @param bool       $history
     *
     * @return void
     *
     * @since 10.0.0
     */
    private function cloneRelations(CommonDBTM $source, bool $history): void
    {
        $clone_relations = $this->getCloneRelations();
        foreach ($clone_relations as $classname) {
            $override_input = [];

            if (!is_a($classname, CommonDBConnexity::class, true)) {
                trigger_error(
                    sprintf(
                        'Unable to clone elements of class %s as it does not extends "CommonDBConnexity"',
                        $classname
                    ),
                    E_USER_WARNING
                );
                continue;
            }

            $override_input[$classname::getItemField($this->getType())] = $this->getID();

           // Force entity / recursivity based on cloned parent, with fallback on session values
            if ($classname::$disableAutoEntityForwarding !== true) {
                $override_input['entities_id'] = $this->isEntityAssign() ? $this->getEntityID() : Session::getActiveEntity();
                $override_input['is_recursive'] = $this->maybeRecursive() ? $this->isRecursive() : Session::getIsActiveEntityRecursive();
            }

            $cloned = []; // Link between old and new ID
            $relation_newitems = [];

            $relation_items = $classname::getItemsAssociatedTo($this->getType(), $source->getID());
            /** @var CommonDBTM $relation_item */
            foreach ($relation_items as $relation_item) {
                if ($source->isTemplate() && isset($relation_item->fields['name'])) {
                    // Force-set name to avoid adding a "(copy)" suffix to the cloned item
                    $override_input['name'] = $relation_item->fields['name'];
                }
                $origin_id = $relation_item->getID();
                $itemtype = $relation_item->getType();
                $cloned[$itemtype][$origin_id] = $relation_item->clone($override_input, $history);
                $relation_item->getFromDB($cloned[$itemtype][$origin_id]);
                $relation_newitems[] = $relation_item;
            }
            // Update relations between cloned items
            foreach ($relation_newitems as $relation_newitem) {
                $itemtype = $relation_newitem->getType();
                $foreignkey = getForeignKeyFieldForItemType($itemtype);
                if ($relation_newitem->isField($foreignkey) && isset($cloned[$itemtype][$relation_newitem->fields[$foreignkey]])) {
                    $relation_newitem->update([
                        'id' => $relation_newitem->getID(),
                        $foreignkey => $cloned[$itemtype][$relation_newitem->fields[$foreignkey]]
                    ]);
                }
            }
        }
    }

    /**
     * Prepare input datas for cloning the item.
     * This empty method is meant to be redefined in objects that need a specific prepareInputForClone logic.
     *
     * @since 10.0.0
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     */
    public function prepareInputForClone($input)
    {
        return $input;
    }

    /**
     * Clone the current item multiple times
     *
     * @since 9.5
     *
     * @param int     $n              Number of clones
     * @param array   $override_input Custom input to override
     * @param boolean $history        Do history log ? (true by default)
     *
     * @return int|bool the new ID of the clone (or false if fail)
     */
    public function cloneMultiple(
        int $n,
        array $override_input = [],
        bool $history = true
    ): bool {
        $failure = false;

        try {
            // Init index cache
            $this->last_clone_index = 0;
            for ($i = 0; $i < $n && !$failure; $i++) {
                if ($this->clone($override_input, $history) === false) {
                    // Increment clone index cache to use less SQL
                    // queries looking for an available unique clone name
                    $failure = true;
                }
            }
        } finally {
            // Make sure cache is cleaned even on exception
            $this->last_clone_index = null;
        }

        return !$failure;
    }

    /**
     * Clones the current item
     *
     * @since 10.0.0
     *
     * @param array $override_input custom input to override
     * @param boolean $history do history log ?
     *
     * @return false|integer The new ID of the clone (or false if fail)
     */
    public function clone(array $override_input = [], bool $history = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($DB->isSlave()) {
            return false;
        }
        $new_item = new static();
        $input = Toolbox::addslashes_deep($this->fields);
        foreach ($override_input as $key => $value) {
            $input[$key] = Toolbox::addslashes_deep($value);
        }
        $input = $new_item->cleanCloneInput($input);

        // Do not compute a clone name if a new name is specified (Like creating from template)
        if (!isset($override_input['name'])) {
            if (($copy_name = $this->getUniqueCloneName($input)) !== null) {
                $input[static::getNameField()] = $copy_name;
            }
        }

        $input = $new_item->prepareInputForClone($input);

        $input['clone'] = true;
        $newID = $new_item->add($input, [], $history);

        if ($newID !== false) {
            $new_item->cloneRelations($this, $history);
            $new_item->post_clone($this, $history);
        }

        return $newID;
    }

    /**
     * Returns unique clone name.
     *
     * @param array $input
     *
     * @return null|string
     */
    protected function getUniqueCloneName(array $input): ?string
    {
        $copy_name = null;

        $name_field = static::getNameField();

        // Force uniqueness for the name field
        if (isset($input[$name_field])) {
            $copy_name = "";
            $current_name = $input[$name_field];
            $table = static::getTable();

            $copy_index = 0;

            // Use index cache if defined
            if (!is_null($this->last_clone_index)) {
                $copy_index = $this->last_clone_index;
            }

            // Try to find an available name
            do {
                $copy_name = $this->computeCloneName($current_name, ++$copy_index);
            } while (countElementsInTable($table, [$name_field => $copy_name]) > 0);

            // Update index cache
            $this->last_clone_index = $copy_index;
        }

        return $copy_name;
    }

    /**
     * Compute the name of a copied item
     * The goal is to set the copy name as "{name} (copy {i})" unless it's
     * the first copy: in this case just "{name} (copy)" is acceptable
     *
     * @param string $current_item The item being copied
     * @param int    $copy_index   The index to append to the copy's name
     *
     * @return string The computed name of the new item to be created
     */
    public function computeCloneName(
        string $current_name,
        int $copy_index
    ): string {
        // First copy
        if ($copy_index == 1) {
            return sprintf(__("%s (copy)"), $current_name);
        }

        // Second+ copies, add index
        return sprintf(__("%s (copy %d)"), $current_name, $copy_index);
    }

    /**
     * Post clone logic.
     * This empty method is meant to be redefined in objects that need a specific post_clone logic.
     *
     * @param $source
     * @param $history
     */
    public function post_clone($source, $history)
    {
    }
}
