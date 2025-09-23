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

namespace Glpi\Features;

use CommonDBConnexity;
use CommonDBTM;
use Infocom;
use ReflectionMethod;
use Session;

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
            'is_default',
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
                if (method_exists($relation_item, 'clone')) {
                    $method = new ReflectionMethod($relation_item, 'clone');

                    // Since this code do not use a proper interface, we must
                    // take into account that users might have defined a clone
                    // method outside this trait with their own signature.
                    // To prevent BC break, we must check if the "clean_mapper"
                    // parameter exist.
                    $args = $method->getParameters();
                    if (isset($args[3]) && $args[3]->getName() == "clean_mapper") {
                        $cloned[$itemtype][$origin_id] = $relation_item->clone(
                            $override_input,
                            $history,
                            clean_mapper: false
                        );
                    } else {
                        $cloned[$itemtype][$origin_id] = $relation_item->clone(
                            $override_input,
                            $history,
                        );
                    }

                    $relation_item->getFromDB($cloned[$itemtype][$origin_id]);
                    $relation_newitems[] = $relation_item;
                } else {
                    trigger_error(
                        sprintf('Unable to clone %s', $itemtype),
                        E_USER_WARNING
                    );
                }
            }
            // Update relations between cloned items
            foreach ($relation_newitems as $relation_newitem) {
                $itemtype = $relation_newitem->getType();
                $foreignkey = getForeignKeyFieldForItemType($itemtype);
                if ($relation_newitem->isField($foreignkey) && isset($cloned[$itemtype][$relation_newitem->fields[$foreignkey]])) {
                    $relation_newitem->update([
                        'id' => $relation_newitem->getID(),
                        $foreignkey => $cloned[$itemtype][$relation_newitem->fields[$foreignkey]],
                    ]);
                }
            }
        }
    }

    /**
     * Prepare input datas for cloning the item.
     * The default implementation handles specific cases when the class uses the following trait(s):
     * - {@link AssignableItem}
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     * @since 10.0.0
     *
     */
    public function prepareInputForClone($input)
    {
        if ($this instanceof AssignableItemInterface) {
            $input = $this->prepareGroupFields($input);
        }
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
     * @param boolean $clone_as_template If true, the resulting clones will be templates
     *
     * @return bool the new ID of the clone (or false if fail)
     */
    public function cloneMultiple(
        int $n,
        array $override_input = [],
        bool $history = true,
        bool $clone_as_template = false
    ): bool {
        $failure = false;

        try {
            // Init index cache
            $this->last_clone_index = 0;
            for ($i = 0; $i < $n && !$failure; $i++) {
                if ($this->clone($override_input, $history, $clone_as_template) === false) {
                    // Increment clone index cache to use less SQL
                    // queries looking for an available unique clone name
                    $failure = true;
                }
            }
        } finally {
            // Make sure cache is cleaned even on exception
            $this->last_clone_index = null;
            CloneMapper::getInstance()->cleanMappedIds();
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
     * @param boolean $clone_as_template If true, the resulting clone will be a template
     *
     * @return false|integer The new ID of the clone (or false if fail)
     */
    public function clone(array $override_input = [], bool $history = true, bool $clone_as_template = false, bool $clean_mapper = true)
    {
        global $DB;

        if ($DB->isSlave()) {
            return false;
        }
        $new_item = new static();

        $old_id = $this->getID();
        $input = array_merge($this->fields, $override_input);

        $template_input = $clone_as_template ? [
            'template_name' => $input['template_name'],
            'is_template' => true,
        ] : [];

        $input = $new_item->cleanCloneInput($input);
        $input = array_merge($input, $template_input);

        // Do not compute a clone name if a new name is specified (Like creating from template)
        if (
            !$clone_as_template
            && !isset($override_input['name'])
            && $this->shouldGenerateUniqueCloneName()
        ) {
            if (($copy_name = $this->getUniqueCloneName($input)) !== null) {
                $input[static::getNameField()] = $copy_name;
            }
        } elseif ($clone_as_template && !isset($override_input['template_name'])) {
            if (($copy_name = $this->getUniqueCloneTemplateName($input)) !== null) {
                $input['template_name'] = $copy_name;
            }
        }

        $input = $new_item->prepareInputForClone($input);

        $input['clone'] = true;
        $newID = $new_item->add($input, [], $history);

        if ($newID !== false) {
            // Mapping the id make sure it is accessible to children clone and
            // post clone processes.
            CloneMapper::getInstance()->addMappedItem(static::class, $old_id, $newID);
            $new_item->cloneRelations($this, $history);
            $new_item->post_clone($this, $history);

            if (
                Infocom::canApplyOn($this)
                && isset($new_item->input['states_id'])
                && !($new_item->input['is_template'] ?? false)
            ) {
                //Check if we have to automatically fill dates
                Infocom::manageDateOnStatusChange($new_item);
            }
        }

        if ($clean_mapper) {
            CloneMapper::getInstance()->cleanMappedIds();
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
     * Returns unique clone name.
     *
     * @param array $input
     *
     * @return null|string
     */
    protected function getUniqueCloneTemplateName(array $input): ?string
    {
        // When creating a template from a template or a template from an item, we will try to name the new template from the original
        // template name. If the original template name is not set, we will try to name the new template from the original item name/placeholder name.
        $current_name = $input['template_name'] ?? $input['name'] ?? null;

        if (!isset($current_name)) {
            return null;
        }

        $table = static::getTable();

        $copy_index = 0;

        // Use index cache if defined
        if (!is_null($this->last_clone_index)) {
            $copy_index = $this->last_clone_index;
        }

        // Try to find an available name
        do {
            $copy_name = $this->computeCloneName($current_name, ++$copy_index);
        } while (countElementsInTable($table, ['template_name' => $copy_name]) > 0);

        // Update index cache
        $this->last_clone_index = $copy_index;

        return $copy_name;
    }

    /**
     * Compute the name of a copied item
     * The goal is to set the copy name as "{name} (copy {i})" unless it's
     * the first copy: in this case just "{name} (copy)" is acceptable
     *
     * @param string $current_name The name of the current item
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
     * The default implementation handles specific cases when the class uses the following trait(s):
     * - {@link AssignableItem}
     *
     * @param $source
     * @param $history
     */
    public function post_clone($source, $history)
    {
        if ($this instanceof AssignableItemInterface) {
            $this->updateGroupFields();
        }
    }

    private function shouldGenerateUniqueCloneName(): bool
    {
        return !CloneWithoutNameSuffix::objectHasAttribute($this);
    }
}
