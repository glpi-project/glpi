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

use Glpi\Toolbox\Sanitizer;

/**
 * CommonTreeDropdown Class
 *
 * Hierarchical and cross entities
 **/
abstract class CommonTreeDropdown extends CommonDropdown
{
    public $can_be_translated = false;


    public function getAdditionalFields()
    {

        return [['name'  => $this->getForeignKeyField(),
            'label' => __('As child of'),
            'type'  => 'parent',
            'list'  => false
        ]
        ];
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);

        $this->addStandardTab($this->getType(), $ong, $options);
        if ($this->dohistory) {
            $this->addStandardTab('Log', $ong, $options);
        }

        if (DropdownTranslation::canBeTranslated($this)) {
            $this->addStandardTab('DropdownTranslation', $ong, $options);
        }

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            !$withtemplate
            && ($item instanceof static)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    $this->getTable(),
                    [$this->getForeignKeyField() => $item->getID()]
                );
            }
            return self::createTabEntry($this->getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof CommonTreeDropdown) {
            $item->showChildren();
        }
        return true;
    }


    /**
     * Compute completename based on parent one
     *
     * @param $parentCompleteName string parent complete name (need to be stripslashes / comes from DB)
     * @param $thisName           string item name (need to be addslashes : comes from input)
     **/
    public static function getCompleteNameFromParents($parentCompleteName, $thisName)
    {
        return addslashes($parentCompleteName) . " > " . $thisName;
    }


    /**
     * @param $input
     **/
    public function adaptTreeFieldsFromUpdateOrAdd($input)
    {

        $parent = clone $this;
       // Update case input['name'] not set :
        if (!isset($input['name']) && isset($this->fields['name'])) {
            $input['name'] = addslashes($this->fields['name']);
        }
       // leading/ending space will break findID/import
        $input['name'] = trim($input['name']);

        if (
            isset($input[$this->getForeignKeyField()])
            && !$this->isNewID($input[$this->getForeignKeyField()])
            && $parent->getFromDB($input[$this->getForeignKeyField()])
        ) {
            $input['level']        = $parent->fields['level'] + 1;
           // Sometimes (internet address), the complete name may be different ...
           /* if ($input[$this->getForeignKeyField()]==0) { // Root entity case
            $input['completename'] =  $input['name'];
           } else {*/
            $input['completename'] = self::getCompleteNameFromParents(
                $parent->fields['completename'],
                $input['name']
            );
           // }
        } else {
            $input[$this->getForeignKeyField()] = 0;
            $input['level']                     = 1;
            $input['completename']              = $input['name'];
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        return $this->adaptTreeFieldsFromUpdateOrAdd($input);
    }


    public function pre_deleteItem()
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Not set in case of massive delete : use parent
        if (isset($this->input['_replace_by']) && $this->input['_replace_by']) {
            $parent = $this->input['_replace_by'];
        } else {
            $parent = $this->fields[$this->getForeignKeyField()];
        }

        $this->cleanParentsSons();
        $tmp  = clone $this;

        $result = $DB->request(
            [
                'SELECT' => 'id',
                'FROM'   => $this->getTable(),
                'WHERE'  => [$this->getForeignKeyField() => $this->fields['id']]
            ]
        );

        foreach ($result as $data) {
            $data[$this->getForeignKeyField()] = $parent;
            $tmp->update($data);
        }

        return true;
    }


    public function prepareInputForUpdate($input)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        if (isset($input[$this->getForeignKeyField()])) {
           // Can't move a parent under a child
            if (
                in_array(
                    $input[$this->getForeignKeyField()],
                    getSonsOf($this->getTable(), $input['id'])
                )
            ) {
                return false;
            }
           // Parent changes => clear ancestors and update its level and completename
            if ($input[$this->getForeignKeyField()] != $this->fields[$this->getForeignKeyField()]) {
                $input["ancestors_cache"] = '';
                $ckey = 'ancestors_cache_' . $this->getTable() . '_' . $this->getID();
                $GLPI_CACHE->delete($ckey);
                return $this->adaptTreeFieldsFromUpdateOrAdd($input);
            }
        }

       // Name changes => update its completename (and its level : side effect ...)
        if ((isset($input['name'])) && ($input['name'] != $this->fields['name'])) {
            return $this->adaptTreeFieldsFromUpdateOrAdd($input);
        }
        return $input;
    }


    /**
     * @param $ID
     * @param $updateName
     * @param $changeParent
     **/
    public function regenerateTreeUnderID($ID, $updateName, $changeParent)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

       //drop from sons cache when needed
        if ($changeParent) {
            $ckey = 'ancestors_cache_' . $this->getTable() . '_' . $ID;
            $GLPI_CACHE->delete($ckey);
        }

        if (($updateName) || ($changeParent)) {
            $currentNode = clone $this;

            if ($currentNode->getFromDB($ID)) {
                $currentNodeCompleteName = $currentNode->getField("completename");
                $nextNodeLevel           = ($currentNode->getField("level") + 1);
            } else {
                $nextNodeLevel = 1;
            }

            $query = [
                'SELECT' => ['id', 'name'],
                'FROM'   => $this->getTable(),
                'WHERE'  => [$this->getForeignKeyField() => $ID]
            ];
            if (Session::haveTranslations($this->getType(), 'completename')) {
                DropdownTranslation::regenerateAllCompletenameTranslationsFor($this->getType(), $ID);
            }

            foreach ($DB->request($query) as $data) {
                $update = [];

                if ($updateName || $changeParent) {
                    if (isset($currentNodeCompleteName)) {
                        $update['completename'] = self::getCompleteNameFromParents(
                            $currentNodeCompleteName,
                            addslashes($data["name"])
                        );
                    } else {
                        $update['completename'] = addslashes($data["name"]);
                    }
                }

                if ($changeParent) {
                   // We have to reset the ancestors as only these changes (ie : not the children).
                    $update['ancestors_cache'] = 'NULL';
                   // And we must update the level of the current node ...
                    $update['level'] = $nextNodeLevel;
                }
                $DB->update(
                    $this->getTable(),
                    $update,
                    ['id' => $data['id']]
                );
               // Translations :
                if (Session::haveTranslations($this->getType(), 'completename')) {
                      DropdownTranslation::regenerateAllCompletenameTranslationsFor($this->getType(), $data['id']);
                }

                $this->regenerateTreeUnderID($data["id"], $updateName, $changeParent);
            }
        }
    }


    /**
     * Clean from database and caches the sons of the current entity and of all its parents.
     *
     * @param null|integer $id    ID of the entity that have its sons cache to be cleaned.
     * @param boolean      $cache Whether to clean cache (defaults to true)
     *
     * @return void
     */
    protected function cleanParentsSons($id = null, $cache = true)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        if ($id === null) {
            $id = $this->getID();
        }

        $ancestors = getAncestorsOf($this->getTable(), $id);
        $ancestors[$id] = "$id";

        $DB->update(
            $this->getTable(),
            [
                'sons_cache' => 'NULL'
            ],
            [
                'id' => $ancestors
            ]
        );

        // drop from sons cache when needed
        if ($cache) {
            foreach ($ancestors as $ancestor) {
                $GLPI_CACHE->delete('sons_cache_' . $this->getTable() . '_' . $ancestor);
            }
        }
    }


    /**
     * Add new son in its parent in cache
     *
     * @return void
     */
    protected function addSonInParents()
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

       //add sons cache when needed
        $ancestors = getAncestorsOf($this->getTable(), $this->getID());
        foreach ($ancestors as $ancestor) {
            $ckey = 'sons_cache_' . $this->getTable() . '_' . $ancestor;
            $sons = $GLPI_CACHE->get($ckey);
            if ($sons !== null && !isset($sons[$this->getID()])) {
                $sons[$this->getID()] = $this->getID();
                $GLPI_CACHE->set($ckey, $sons);
            }
        }
    }


    public function post_addItem()
    {

        $parent = $this->fields[$this->getForeignKeyField()];
       //do not clean cache, it will be updated
        $this->cleanParentsSons(null, false);
        $this->addSonInParents();
        if ($parent && $this->dohistory) {
            $changes = [
                0,
                '',
                addslashes($this->getNameID(['forceid' => true])),
            ];
            Log::history(
                $parent,
                $this->getType(),
                $changes,
                $this->getType(),
                Log::HISTORY_ADD_SUBITEM
            );
        }
    }


    public function post_updateItem($history = true)
    {

        $ID           = $this->getID();
        $changeParent = in_array($this->getForeignKeyField(), $this->updates);
        $this->regenerateTreeUnderID($ID, in_array('name', $this->updates), $changeParent);

        if ($changeParent) {
            $oldParentID     = $this->oldvalues[$this->getForeignKeyField()];
            $newParentID     = $this->fields[$this->getForeignKeyField()];
            $oldParentNameID = '';
            $newParentNameID = '';

            $parent = clone $this;
            if ($parent->getFromDB($oldParentID)) {
                $this->cleanParentsSons($oldParentID);
                if ($history) {
                    $oldParentNameID = $parent->getNameID(['forceid' => true]);
                    $changes = [
                        '0',
                        addslashes($this->getNameID(['forceid' => true])),
                        '',
                    ];
                    Log::history(
                        $oldParentID,
                        $this->getType(),
                        $changes,
                        $this->getType(),
                        Log::HISTORY_DELETE_SUBITEM
                    );
                }
            }

            if ($parent->getFromDB($newParentID)) {
                $this->cleanParentsSons();
                if ($history) {
                    $newParentNameID = $parent->getNameID(['forceid' => true]);
                    $changes = [
                        '0',
                        '',
                        addslashes($this->getNameID(['forceid' => true])),
                    ];
                    Log::history(
                        $newParentID,
                        $this->getType(),
                        $changes,
                        $this->getType(),
                        Log::HISTORY_ADD_SUBITEM
                    );
                }
            }

            if ($history) {
                $changes = [
                    '0',
                    $oldParentNameID,
                    $newParentNameID,
                ];
                Log::history(
                    $ID,
                    $this->getType(),
                    $changes,
                    $this->getType(),
                    Log::HISTORY_UPDATE_SUBITEM
                );
            }

            // Force DB cache refresh
            getAncestorsOf(getTableForItemType($this->getType()), $ID);
            getSonsOf(getTableForItemType($this->getType()), $ID);
        }
    }


    public function post_deleteFromDB()
    {

        $parent = $this->fields[$this->getForeignKeyField()];
        if ($parent && $this->dohistory) {
            $changes = [
                '0',
                addslashes($this->getNameID(['forceid' => true])),
                '',
            ];
            Log::history(
                $parent,
                $this->getType(),
                $changes,
                $this->getType(),
                Log::HISTORY_DELETE_SUBITEM
            );
        }
    }


    /**
     * Get the this for all the current item and all its parent
     *
     * @return string
     **/
    public function getTreeLink()
    {

        $link = '';
        if ($this->fields[$this->getForeignKeyField()]) {
            $papa = clone $this;

            if ($papa->getFromDB($this->fields[$this->getForeignKeyField()])) {
                $link = $papa->getTreeLink() . " > ";
            }
        }
        return $link . $this->getLink();
    }


    /**
     * Print the HTML array children of a TreeDropdown
     *
     * @return void
     */
    public function showChildren()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID            = $this->getID();
        $this->check($ID, READ);
        $fields = array_filter(
            $this->getAdditionalFields(),
            function ($field) {
                return isset($field['list']) && $field['list'];
            }
        );
        $nb            = count($fields);
        $entity_assign = $this->isEntityAssign();

       // Minimal form for quick input.
        if (static::canCreate()) {
            $link = $this->getFormURL();
            echo "<div class='firstbloc'>";
            echo "<form action='" . $link . "' method='post'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>" . __('New child heading') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td><td>";
            echo Html::input('name', ['value' => '']);

            if (
                $entity_assign
                && ($this->getForeignKeyField() != 'entities_id')
            ) {
                echo "<input type='hidden' name='entities_id' value='" . $_SESSION['glpiactive_entity'] . "'>";
            }

            if ($entity_assign && $this->isRecursive()) {
                echo "<input type='hidden' name='is_recursive' value='1'>";
            }
            echo "<input type='hidden' name='" . $this->getForeignKeyField() . "' value='$ID'></td>";
            echo "<td><input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>\n";
            echo "</table>";
            Html::closeForm();
            echo "</div>\n";
        }

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='" . ($nb + 3) . "'>" . sprintf(
            __('Sons of %s'),
            $this->getTreeLink()
        );
        echo "</th></tr>";

        $header = "<tr><th>" . __('Name') . "</th>";
        if ($entity_assign) {
            $header .= "<th>" . Entity::getTypeName(1) . "</th>";
        }
        foreach ($fields as $field) {
            $header .= "<th>" . $field['label'] . "</th>";
        }
        $header .= "<th>" . __('Comments') . "</th>";
        $header .= "</tr>\n";
        echo $header;

        $fk   = $this->getForeignKeyField();

        $result = $DB->request(
            [
                'FROM'  => $this->getTable(),
                'WHERE' => [$fk => $ID],
                'ORDER' => 'name',
            ]
        );

        $nb = 0;
        foreach ($result as $data) {
            $nb++;
            echo "<tr class='tab_bg_1'><td>";
            if (
                (($fk == 'entities_id') && in_array($data['id'], $_SESSION['glpiactiveentities']))
                || !$entity_assign
                || (($fk != 'entities_id') && in_array($data['entities_id'], $_SESSION['glpiactiveentities']))
            ) {
                echo "<a href='" . $this->getFormURL();
                echo '?id=' . $data['id'] . "'>" . $data['name'] . "</a>";
            } else {
                echo $data['name'];
            }
            echo "</td>";
            if ($entity_assign) {
                echo "<td>" . Dropdown::getDropdownName("glpi_entities", $data["entities_id"]) . "</td>";
            }

            foreach ($fields as $field) {
                echo "<td>";
                switch ($field['type']) {
                    case 'UserDropdown':
                        echo getUserName($data[$field['name']]);
                        break;

                    case 'bool':
                         echo Dropdown::getYesNo($data[$field['name']]);
                        break;

                    case 'dropdownValue':
                        echo Dropdown::getDropdownName(
                            getTableNameForForeignKeyField($field['name']),
                            $data[$field['name']]
                        );
                        break;

                    default:
                        echo $data[$field['name']];
                }
                echo "</td>";
            }
            echo "<td>" . $data['comment'] . "</td>";
            echo "</tr>\n";
        }
        if ($nb) {
            echo $header;
        }
        echo "</table></div>\n";
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'move_under']
                  = "<i class='fas fa-sitemap'></i>" .
                    _x('button', 'Move under');
        }

        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'move_under':
                $itemtype = $ma->getItemType(true);
                echo __('As child of');
                Dropdown::show($itemtype, ['name'     => 'parent',
                    'comments' => 0,
                    'entity'   => $_SESSION['glpiactive_entity'],
                    'entity_sons' => $_SESSION['glpiactive_entity_recursive']
                ]);
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='" .
                           _sx('button', 'Move') . "'>\n";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        $input = $ma->getInput();

        switch ($ma->getAction()) {
            case 'move_under':
                if (isset($input['parent'])) {
                    $fk     = $item->getForeignKeyField();
                    $parent = clone $item;
                    if (!$parent->getFromDB($input['parent'])) {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                        $ma->addMessage($parent->getErrorMessage(ERROR_NOT_FOUND));
                        return;
                    }
                    foreach ($ids as $id) {
                        if ($item->can($id, UPDATE)) {
                             // Check if parent is not a child of the original one
                            if (
                                !in_array($parent->getID(), getSonsOf(
                                    $item->getTable(),
                                    $item->getID()
                                ))
                            ) {
                                if (
                                    $item->update(['id' => $id,
                                        $fk  => $parent->getID()
                                    ])
                                ) {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                            }
                        } else {
                             $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                             $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics')
        ];

        $tab[] = [
            'id'                => '1',
            'table'              => $this->getTable(),
            'field'              => 'completename',
            'name'               => __('Complete name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                => '14',
            'table'             => $this->getTable(),
            'field'             => 'name',
            'name'              => __('Name'),
            'datatype'          => 'itemlink',
        ];

        $tab[] = [
            'id'                => '13',
            'table'             => $this->getTable(),
            'field'             => 'completename',
            'name'              => __('Father'),
            'datatype'          => 'dropdown',
            'massiveaction'     => false,
         // Add virtual condition to relink table
            'joinparams'        => ['condition' => [new QueryExpression("1=1")]]
        ];

        $tab[] = [
            'id'                => '16',
            'table'             => $this->getTable(),
            'field'             => 'comment',
            'name'              => __('Comments'),
            'datatype'          => 'text'
        ];

        if ($this->isEntityAssign()) {
            $tab[] = [
                'id'             => '80',
                'table'          => 'glpi_entities',
                'field'          => 'completename',
                'name'           => Entity::getTypeName(1),
                'massiveaction'  => false,
                'datatype'       => 'dropdown'
            ];
        }

        if ($this->maybeRecursive()) {
            $tab[] = [
                'id'             => '86',
                'table'          => $this->getTable(),
                'field'          => 'is_recursive',
                'name'           => __('Child entities'),
                'datatype'       => 'bool'
            ];
        }

        if ($this->isField('date_mod')) {
            $tab[] = [
                'id'             => '19',
                'table'          => $this->getTable(),
                'field'          => 'date_mod',
                'name'           => __('Last update'),
                'datatype'       => 'datetime',
                'massiveaction'  => false
            ];
        }

        if ($this->isField('date_creation')) {
            $tab[] = [
                'id'             => '121',
                'table'          => $this->getTable(),
                'field'          => 'date_creation',
                'name'           => __('Creation date'),
                'datatype'       => 'datetime',
                'massiveaction'  => false
            ];
        }

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }


    public function haveChildren()
    {

        $fk = $this->getForeignKeyField();
        $id = $this->fields['id'];

        return (countElementsInTable($this->getTable(), [$fk => $id]) > 0);
    }


    /**
     * reformat text field describing a tree (such as completename)
     *
     * @param $value string
     *
     * @return string
     **/
    public static function cleanTreeText($value)
    {

        $tmp = explode('>', $value);
        foreach ($tmp as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($tmp[$k]);
            } else {
                $tmp[$k] = $v;
            }
        }
        return implode(' > ', $tmp);
    }


    public function findID(array &$input)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($input['completename'])) {
           // Clean data
            $input['completename'] = self::cleanTreeText($input['completename']);
        }

        if (isset($input['completename']) && !empty($input['completename'])) {
            $criteria = [
                'SELECT' => 'id',
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    'completename' => $input['completename']
                ]
            ];
            if ($this->isEntityAssign()) {
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    $this->getTable(),
                    '',
                    $input['entities_id'],
                    $this->maybeRecursive()
                );
            }
           // Check twin :
            $iterator = $DB->request($criteria);
            if (count($iterator)) {
                $result = $iterator->current();
                return $result['id'];
            }
        } else if (isset($input['name']) && !empty($input['name'])) {
            $fk = $this->getForeignKeyField();

            $criteria = [
                'SELECT' => 'id',
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    'name'   => $input['name'],
                    $fk      => (isset($input[$fk]) ? $input[$fk] : 0)
                ]
            ];
            if ($this->isEntityAssign()) {
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    $this->getTable(),
                    '',
                    $input['entities_id'],
                    $this->maybeRecursive()
                );
            }
           // Check twin :
            $iterator = $DB->request($criteria);
            if (count($iterator)) {
                $result = $iterator->current();
                return $result['id'];
            }
        }
        return -1;
    }


    public function import(array $input)
    {
        if (empty($input['name']) && empty($input['completename'])) {
            return -1;
        }

        if (empty($input['completename'])) {
            $input['completename'] = $input['name'];
            unset($input['name']);
        }

       // Import a full tree from completename
        $names  = explode('>', self::unsanitizeSeparatorInCompletename($input['completename']));
        $fk     = $this->getForeignKeyField();
        $i      = count($names);
        $parent = 0;

        foreach ($names as $name) {
            $i--;
            $name = trim($name);
            if (empty($name)) {
               // Skip empty name (completename starting/endind with >, double >, ...)
                continue;
            }

            $tmp = [
                'name' => $name,
                $fk    => $parent,
            ];

            if (isset($input['is_recursive'])) {
                $tmp['is_recursive'] = $input['is_recursive'];
            }
            if (isset($input['entities_id'])) {
                $tmp['entities_id'] = $input['entities_id'];
            }

            if (!$i) {
               // Other fields (comment, ...) only for last node of the tree
                foreach ($input as $key => $val) {
                    if ($key != 'completename') {
                        $tmp[$key] = $val;
                    }
                }
            }

            $parent = parent::import($tmp);
        }
        return $parent;
    }


    public static function getIcon()
    {
        return "ti ti-subtask";
    }

    /**
     * Separator is not encoded in DB, and it could not be changed as this is mandatory to be able to split tree
     * correctly even if some tree elements are containing ">" char in their name (this one will be encoded).
     *
     * This method aims to sanitize the completename value in display context.
     *
     * @param string|null $completename
     *
     * @return string|null
     */
    public static function sanitizeSeparatorInCompletename(?string $completename): ?string
    {
        if (empty($completename)) {
            return $completename;
        }
        $separator = '>';
        return implode(Sanitizer::sanitize($separator), explode($separator, $completename));
    }

    /**
     * Separator may be encoded in input, but should sometimes be decoded to have a complename
     * that fits the value expected to be stored in DB.
     *
     * This method aims to normalize the completename value.
     *
     * @param string|null $completename
     *
     * @return string|null
     */
    public static function unsanitizeSeparatorInCompletename(?string $completename): ?string
    {
        if (empty($completename)) {
            return $completename;
        }
        $separator = '>';
        return implode($separator, explode(Sanitizer::sanitize($separator), $completename));
    }
}
