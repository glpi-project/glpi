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

/// Class CommonImplicitTreeDropdown : Manage implicit tree, ie., trees that cannot be manage by
/// the user. For instance, Network hierarchy only depends on network addresses and netmasks.
/// @since 0.84
class CommonImplicitTreeDropdown extends CommonTreeDropdown
{
    public $can_be_translated = true;



    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'CommonTreeDropdown' . MassiveAction::CLASS_ACTION_SEPARATOR . 'move_under';
        return $forbidden;
    }


    /**
     * Method that must be overloaded. This method provides the ancestor of the current item
     * according to $this->input
     *
     * @return integer the id of the current object ancestor
     **/
    public function getNewAncestor()
    {
        return 0; // By default, we rattach to the root element
    }


    /**
     * Method that must be overloaded. This method provides the list of all potential sons of the
     * current item according to $this->fields.
     *
     * @return array of IDs of the potential sons
     **/
    public function getPotentialSons()
    {
        return []; // By default, we don't have any son
    }

    public function prepareInputForAdd($input)
    {

        $input[$this->getForeignKeyField()] = $this->getNewAncestor();
        // We call the parent to manage tree
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {

        $input[$this->getForeignKeyField()] = $this->getNewAncestor();
        // We call the parent to manage tree
        return parent::prepareInputForUpdate($input);
    }

    public function post_addItem()
    {

        $this->alterElementInsideTree("add");
        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {

        $this->alterElementInsideTree("update");
        parent::post_updateItem($history);
    }

    public function pre_deleteItem()
    {

        $this->alterElementInsideTree("delete");
        return parent::pre_deleteItem();
    }


    /**
     * The haveChildren=false must be define to be sure that CommonDropdown allows the deletion of a
     * node of the tree
     **/
    public function haveChildren()
    {
        return false;
    }


    // Key function to manage the children of the node
    private function alterElementInsideTree($step)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $oldParent     = null;
        $newParent     = null;
        $potentialSons = [];

        switch ($step) {
            case 'add':
                $newParent     = $this->input[$this->getForeignKeyField()];
                $potentialSons = $this->getPotentialSons();
                break;

            case 'update':
                $oldParent     = $this->fields[$this->getForeignKeyField()];
                $newParent     = $this->input[$this->getForeignKeyField()];
                $potentialSons = $this->getPotentialSons();
                break;

            case 'delete':
                $oldParent     = $this->fields[$this->getForeignKeyField()];
                $potentialSons = []; // Because there is no future sons !
                break;
        }

        /** Here :
         * $oldParent contains the old parent, to check its sons to attach them to it
         * $newParent contains the new parent, to check its sons to potentially attach them to this
         *            item.
         * $potentialSons list ALL potential childrens (sons as well as grandsons). That is use to
         *                update them. (See getPotentialSons())
         **/

        if ($step != "add" && count($potentialSons)) { // Because there is no old sons of new node
            // First, get all my current direct sons (old ones) that are not new potential sons
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    $this->getForeignKeyField()   => $this->getID(),
                    'NOT'                         => ['id' => $potentialSons],
                ],
            ]);
            $oldSons = [];
            foreach ($iterator as $oldSon) {
                $oldSons[] = $oldSon["id"];
            }
            if (count($oldSons) > 0) { // Then make them pointing to old parent
                $DB->update(
                    $this->getTable(),
                    [
                        $this->getForeignKeyField() => $oldParent,
                    ],
                    [
                        'id' => $oldSons,
                    ]
                );
                // Then, regenerate the old sons to reflect there new ancestors
                $this->regenerateTreeUnderID($oldParent, true, true);
                $this->cleanParentsSons($oldParent);
            }
        }

        if ($step != "delete" && count($potentialSons)) { // Because ther is no new sons for deleted nodes
            // And, get all direct sons of my new Father that must be attached to me (ie : that are
            // potential sons
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    $this->getForeignKeyField()   => $newParent,
                    'id'                          => $potentialSons,
                ],
            ]);
            $newSons = [];
            foreach ($iterator as $newSon) {
                $newSons[] = $newSon["id"];
            }
            if (count($newSons) > 0) { // Then make them pointing to me
                $DB->update(
                    $this->getTable(),
                    [
                        $this->getForeignKeyField() => $this->getID(),
                    ],
                    [
                        'id' => $newSons,
                    ]
                );
                // Then, regenerate the new sons to reflect there new ancestors
                $this->regenerateTreeUnderID($this->getID(), true, true);
                $this->cleanParentsSons();
            }
        }
    }
}
