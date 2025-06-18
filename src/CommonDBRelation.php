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

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

/// Common DataBase Relation Table Manager Class
abstract class CommonDBRelation extends CommonDBConnexity
{
    // Item 1 information
    // * definition
    public static $itemtype_1; // Type ref or field name (must start with itemtype)
    public static $items_id_1; // Field name
    // * entity inheritance
    public static $take_entity_1          = true;
    // * rights
    public static $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
    public static $mustBeAttached_1       = true;
    // * log
    public static $logs_for_item_1        = true;
    public static $log_history_1_add      = Log::HISTORY_ADD_RELATION;
    public static $log_history_1_update   = Log::HISTORY_UPDATE_RELATION;
    public static $log_history_1_delete   = Log::HISTORY_DEL_RELATION;
    public static $log_history_1_lock     = Log::HISTORY_LOCK_RELATION;
    public static $log_history_1_unlock   = Log::HISTORY_UNLOCK_RELATION;

    // Item 2 information
    // * definition
    public static $itemtype_2; // Type ref or field name (must start with itemtype)
    public static $items_id_2; // Field name
    // * entity inheritance
    public static $take_entity_2          = false;
    // * rights
    public static $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
    public static $mustBeAttached_2       = true;
    // * log
    public static $logs_for_item_2        = true;
    public static $log_history_2_add      = Log::HISTORY_ADD_RELATION;
    public static $log_history_2_update   = Log::HISTORY_UPDATE_RELATION;
    public static $log_history_2_delete   = Log::HISTORY_DEL_RELATION;
    public static $log_history_2_lock     = Log::HISTORY_LOCK_RELATION;
    public static $log_history_2_unlock   = Log::HISTORY_UNLOCK_RELATION;

    // Relation between items to check
    /// If both items must be checked for rights (default is only one)
    public static $checkAlwaysBothItems   = false;
    /// If both items must be in viewable each other entities
    public static $check_entity_coherency = true;

    public $no_form_page                  = true;

    /**
     * Search option number to use in parent item log.
     * Value is defined during logging process and unset after it.
     * @var int
     */
    protected $_force_log_option = 0;

    /**
     * Get request cirteria to search for an item
     *
     * @since 9.4
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return array|null
     **/
    public static function getSQLCriteriaToSearchForItem($itemtype, $items_id)
    {
        $table = static::getTable();

        $conditions = [];
        $fields     = [
            static::getIndexName(),
            static::$items_id_1 . ' AS items_id_1',
            static::$items_id_2 . ' AS items_id_2',
        ];

        // Check item 1 type
        $where1 = [
            $table . '.' . static::$items_id_1  => $items_id,
        ];

        $request = false;
        if (preg_match('/^itemtype/', static::$itemtype_1)) {
            $fields[] = static::$itemtype_1 . ' AS itemtype_1';
            $where1[$table . '.' . static::$itemtype_1] = $itemtype;
            $request = true;
        } else {
            $fields[] = new QueryExpression("'" . static::$itemtype_1 . "' AS itemtype_1");
            if (
                ($itemtype ==  static::$itemtype_1)
                || is_subclass_of($itemtype, static::$itemtype_1)
            ) {
                $request = true;
            }
        }
        if ($request === true) {
            $conditions[] = $where1;
            $fields[] = QueryFunction::if($where1, new QueryExpression('1'), new QueryExpression('0'), 'is_1');
        } else {
            $fields[] = new QueryExpression('0 AS is_1');
        }

        // Check item 2 type
        $where2 = [
            $table . '.' . static::$items_id_2 => $items_id,
        ];
        $request = false;
        if (preg_match('/^itemtype/', static::$itemtype_2)) {
            $fields[] = static::$itemtype_2 . ' AS itemtype_2';
            $where2[$table . '.' . static::$itemtype_2] = $itemtype;
            $request = true;
        } else {
            $fields[] = new QueryExpression("'" . static::$itemtype_2 . "' AS itemtype_2");
            if (
                ($itemtype ==  static::$itemtype_2)
                || is_subclass_of($itemtype, static::$itemtype_2)
            ) {
                $request = true;
            }
        }
        if ($request === true) {
            $conditions[] = $where2;
            $fields[] = QueryFunction::if($where2, new QueryExpression('1'), new QueryExpression('0'), 'is_2');
        } else {
            $fields[] = new QueryExpression('0 AS is_2');
        }

        if (count($conditions) != 0) {
            $criteria = [
                'SELECT' => $fields,
                'FROM'   => $table,
                'WHERE'  => ['OR' => $conditions],
            ];
            return $criteria;
        }
        return null;
    }


    /**
     * @since 0.84
     *
     * @param $item            CommonDBTM object
     * @param $relations_id    (default NULL)
     **/
    public static function getOpposite(CommonDBTM $item, &$relations_id = null)
    {
        return static::getOppositeByTypeAndID($item->getType(), $item->getID(), $relations_id);
    }


    /**
     * @since 0.84
     *
     * @param string       $itemtype        Type of the item to search for its opposite
     * @param integer      $items_id        ID of the item to search for its opposite
     * @param integer|null $relations_id
     **/
    public static function getOppositeByTypeAndID($itemtype, $items_id, &$relations_id = null)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($items_id < 0) {
            return false;
        }

        $criteria = self::getSQLCriteriaToSearchForItem($itemtype, $items_id);

        if ($criteria !== null) {
            $iterator = $DB->request($criteria);
            if (count($iterator) == 1) {
                $line = $iterator->current();
                if ($line['is_1'] == $line['is_2']) {
                    return false;
                }
                if ($line['is_1'] == 0) {
                    $opposites_id = $line['items_id_1'];
                    $oppositetype = $line['itemtype_1'];
                }
                if ($line['is_2'] == 0) {
                    $opposites_id = $line['items_id_2'];
                    $oppositetype = $line['itemtype_2'];
                }
                if ((isset($oppositetype)) && (isset($opposites_id))) {
                    $opposite = getItemForItemtype($oppositetype);
                    if ($opposite !== false) {
                        if ($opposite->getFromDB($opposites_id)) {
                            if (!is_null($relations_id)) {
                                $relations_id = $line[static::getIndexName()];
                            }
                            return $opposite;
                        }
                        unset($opposite);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get the opposite itemtype
     * @param class-string<CommonDBTM>|null $itemtype The itemtype to get the opposite of (may be null)
     * @return class-string<CommonDBTM>|null The opposite itemtype or null if not found
     */
    public static function getOppositeItemtype(?string $itemtype): ?string
    {
        if (static::$itemtype_1 === $itemtype || (static::$itemtype_1 === 'itemtype' && static::$itemtype_2 !== null)) {
            return static::$itemtype_2;
        }

        if (static::$itemtype_2 === $itemtype || (static::$itemtype_2 === 'itemtype' && static::$itemtype_1 !== null)) {
            return static::$itemtype_1;
        }
        return null;
    }

    /**
     * @since 0.84
     *
     * @param $number
     *
     * @return CommonDBTM|false
     **/
    public function getOnePeer($number)
    {

        if ($number == 0) {
            $itemtype = static::$itemtype_1;
            $items_id = static::$items_id_1;
        } elseif ($number == 1) {
            $itemtype = static::$itemtype_2;
            $items_id = static::$items_id_2;
        } else {
            return false;
        }
        return $this->getConnexityItem($itemtype, $items_id);
    }


    /**
     * Get link object between 2 items
     *
     * @since 0.84
     *
     * @param CommonDBTM $item1 object 1
     * @param CommonDBTM $item2 object 2
     *
     * @return boolean
     **/
    public function getFromDBForItems(CommonDBTM $item1, CommonDBTM $item2)
    {

        // Check items ID
        if (($item1->getID() < 0) || ($item2->getID() < 0)) {
            return false;
        }

        $wheres = [];
        $wheres[static::$items_id_1] = $item1->getID();
        $wheres[static::$items_id_2] = $item2->getID();

        // Check item 1 type
        if (preg_match('/^itemtype/', static::$itemtype_1)) {
            $wheres[static::$itemtype_1] = $item1->getType();
        } elseif (!is_a($item1, static::$itemtype_1)) {
            return false;
        }

        // Check item 1 type
        if (preg_match('/^itemtype/', static::$itemtype_2)) {
            $wheres[static::$itemtype_2] = $item2->getType();
        } elseif (!is_a($item2, static::$itemtype_2)) {
            return false;
        }
        return $this->getFromDBByCrit($wheres);
    }


    /**
     * Get search function for the class
     *
     * @return array of search option
     **/
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $itemtype1 = static::$itemtype_1;
        if (!preg_match('/^itemtype/', $itemtype1)) {
            $tab[] = [
                'id'                 => '3',
                'table'              => getTableForItemType($itemtype1),
                'field'              => $itemtype1::getIndexName(),
                'linkfield'          => static::$items_id_1,
                'name'               => call_user_func([$itemtype1, 'getTypeName']),
                'datatype'           => 'text',
                'massiveaction'      => false,
            ];
        }

        $itemtype2 = static::$itemtype_2;
        if (!preg_match('/^itemtype/', $itemtype2)) {
            $tab[] = [
                'id'                 => '4',
                'table'              => getTableForItemType($itemtype2),
                'field'              => $itemtype2::getIndexName(),
                'linkfield'          => static::$items_id_2,
                'name'               => call_user_func([$itemtype2, 'getTypeName']),
                'datatype'           => 'text',
                'massiveaction'      => false,
            ];
        }

        return $tab;
    }


    /**
     * Specific check for check attach for relation 2
     *
     * @since 0.84
     *
     * @param $input Array of data to be added
     *
     * @return boolean
     **/
    public function isAttach2Valid(array &$input)
    {
        return false;
    }


    /**
     * Specific check for check attach for relation 1
     *
     * @since 0.84
     *
     * @param $input Array of data to be added
     *
     * @return boolean
     **/
    public function isAttach1Valid(array &$input)
    {
        return false;
    }


    /**
     * @since 0.84
     *
     * @param $method
     * @param $forceCheckBoth boolean force check both items(false by default)
     *
     * @return boolean
     **/
    public static function canRelation($method, $forceCheckBoth = false)
    {

        $can1 = static::canConnexity(
            $method,
            static::$checkItem_1_Rights,
            static::$itemtype_1,
            static::$items_id_1
        );
        $can2 = static::canConnexity(
            $method,
            static::$checkItem_2_Rights,
            static::$itemtype_2,
            static::$items_id_2
        );

        /// Check only one if SAME RIGHT for both items and not force checkBoth
        if (
            ((static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_1_Rights)
            && (static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_2_Rights))
            && !$forceCheckBoth
        ) {
            if ($can1) {
                // Can view the second one ?
                if (
                    !static::canConnexity(
                        $method,
                        static::HAVE_VIEW_RIGHT_ON_ITEM,
                        static::$itemtype_2,
                        static::$items_id_2
                    )
                ) {
                    return false;
                }
                return true;
            } elseif ($can2) {
                // Can view the first one ?
                if (
                    !static::canConnexity(
                        $method,
                        static::HAVE_VIEW_RIGHT_ON_ITEM,
                        static::$itemtype_1,
                        static::$items_id_1
                    )
                ) {
                    return false;
                }
                return true;
            } else {
                // No item have right
                return false;
            }
        }

        return ($can1 && $can2);
    }


    /**
     * @since 0.84
     *
     * @param $method
     * @param $methodNotItem
     * @param $check_entity            (true by default)
     * @param $forceCheckBoth boolean  force check both items (false by default)
     *
     * @return boolean
     **/
    public function canRelationItem($method, $methodNotItem, $check_entity = true, $forceCheckBoth = false)
    {

        $OneWriteIsEnough = (!$forceCheckBoth
                           && ((static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_1_Rights)
                               || (static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_2_Rights)));

        $view1 = false;
        $view2 = false;

        try {
            $item1 = null;
            $can1  = $this->canConnexityItem(
                $method,
                $methodNotItem,
                static::$checkItem_1_Rights,
                static::$itemtype_1,
                static::$items_id_1,
                $item1
            );
            if ($OneWriteIsEnough) {
                $view1 = $this->canConnexityItem(
                    $method,
                    $methodNotItem,
                    static::HAVE_VIEW_RIGHT_ON_ITEM,
                    static::$itemtype_1,
                    static::$items_id_1,
                    $item1
                );
            }
        } catch (CommonDBConnexityItemNotFound $e) {
            if (static::$mustBeAttached_1 && !$this->isAttach1Valid($this->fields)) {
                return false;
            }
            $can1         = true;
            $view1        = true;
            $check_entity = false; // If no item, then, we cannot check entities
        }

        try {
            $item2 = null;
            $can2  = $this->canConnexityItem(
                $method,
                $methodNotItem,
                static::$checkItem_2_Rights,
                static::$itemtype_2,
                static::$items_id_2,
                $item2
            );
            if ($OneWriteIsEnough) {
                $view2 = $this->canConnexityItem(
                    $method,
                    $methodNotItem,
                    static::HAVE_VIEW_RIGHT_ON_ITEM,
                    static::$itemtype_2,
                    static::$items_id_2,
                    $item2
                );
            }
        } catch (CommonDBConnexityItemNotFound $e) {
            if (static::$mustBeAttached_2 && !$this->isAttach2Valid($this->fields)) {
                return false;
            }
            $can2         = true;
            $view2        = true;
            $check_entity = false; // If no item, then, we cannot check entities
        }

        if ($OneWriteIsEnough) {
            if (
                (!$can1 && !$can2)
                || ($can1 && !$view2)
                || ($can2 && !$view1)
            ) {
                return false;
            }
        } else {
            if (!$can1 || !$can2) {
                return false;
            }
        }

        // Check coherency of entities
        if ($check_entity && static::$check_entity_coherency) {
            // If one of both extremity is not valid => not allowed !
            // (default is only to check on create and update not for view and delete)
            if (
                (!$item1 instanceof CommonDBTM)
                || (!$item2 instanceof CommonDBTM)
            ) {
                return false;
            }
            if ($item1->isEntityAssign() && $item2->isEntityAssign()) {
                $entity1 = $item1->getEntityID();
                $entity2 = $item2->getEntityID();

                if ($entity1 == $entity2) {
                    return true;
                }
                if (
                    ($item1->isRecursive())
                    && in_array($entity1, getAncestorsOf("glpi_entities", $entity2))
                ) {
                    return true;
                }
                if (
                    ($item2->isRecursive())
                    && in_array($entity2, getAncestorsOf("glpi_entities", $entity1))
                ) {
                    return true;
                }
                return false;
            }
        }

        return true;
    }


    /**
     * @since 0.84
     **/
    public static function canCreate(): bool
    {

        if ((static::$rightname) && (!Session::haveRight(static::$rightname, CREATE))) {
            return false;
        }
        return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
    }


    /**
     * @since 0.84
     **/
    public static function canView(): bool
    {
        if ((static::$rightname) && (!Session::haveRight(static::$rightname, READ))) {
            return false;
        }
        // Always both checks for view
        return static::canRelation('canView', true);
    }


    /**
     * @since 0.84
     **/
    public static function canUpdate(): bool
    {
        if ((static::$rightname) && (!Session::haveRight(static::$rightname, UPDATE))) {
            return false;
        }
        return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
    }


    /**
     * @since 0.84
     **/
    public static function canDelete(): bool
    {
        if ((static::$rightname) && (!Session::haveRight(static::$rightname, DELETE))) {
            return false;
        }
        return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
    }


    /**
     * @since 0.85
     **/
    public static function canPurge(): bool
    {
        if ((static::$rightname) && (!Session::haveRight(static::$rightname, PURGE))) {
            return false;
        }
        return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
    }


    /**
     * @since 0.84
     **/
    public function canCreateItem(): bool
    {
        return $this->canRelationItem(
            'canUpdateItem',
            'canUpdate',
            true,
            static::$checkAlwaysBothItems
        );
    }


    /**
     * @since 0.84
     **/
    public function canViewItem(): bool
    {
        return $this->canRelationItem('canViewItem', 'canView', false, true);
    }


    /**
     * @since 0.84
     **/
    public function canUpdateItem(): bool
    {

        return $this->canRelationItem(
            'canUpdateItem',
            'canUpdate',
            true,
            static::$checkAlwaysBothItems
        );
    }


    /**
     * @since 0.84
     **/
    public function canDeleteItem(): bool
    {

        return $this->canRelationItem(
            'canUpdateItem',
            'canUpdate',
            false,
            static::$checkAlwaysBothItems
        );
    }


    /**
     * @since 9.3.2
     */
    public function canPurgeItem(): bool
    {

        return $this->canRelationItem(
            'canUpdateItem',
            'canUpdate',
            false,
            static::$checkAlwaysBothItems
        );
    }


    public function addNeededInfoToInput($input)
    {

        // is entity missing and forwarding on ?
        if ($this->tryEntityForwarding() && !isset($input['entities_id'])) {
            // Merge both arrays to ensure all the fields are defined for the following checks
            $completeinput = array_merge($this->fields, $input);

            $itemToGetEntity = false;
            // Set the item to allow parent::prepareinputforadd to get the right item ...
            if (static::$take_entity_1) {
                $itemToGetEntity = static::getItemFromArray(
                    static::$itemtype_1,
                    static::$items_id_1,
                    $completeinput
                );
            } elseif (static::$take_entity_2) {
                $itemToGetEntity = static::getItemFromArray(
                    static::$itemtype_2,
                    static::$items_id_2,
                    $completeinput
                );
            }

            // Set the item to allow parent::prepareinputforadd to get the right item ...
            if (
                ($itemToGetEntity instanceof CommonDBTM)
                && $itemToGetEntity->isEntityForwardTo(get_called_class())
            ) {
                $input['entities_id']  = $itemToGetEntity->getEntityID();
                $input['is_recursive'] = intval($itemToGetEntity->isRecursive());
            } else {
                // No entity link : set default values
                $input['entities_id']  = Session::getActiveEntity();
                $input['is_recursive'] = 0;
            }
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {

        if (!is_array($input)) {
            return false;
        }

        return $this->addNeededInfoToInput($input);
    }


    public function prepareInputForUpdate($input)
    {

        if (!is_array($input)) {
            return false;
        }

        // True if item changed
        if (
            !$this->checkAttachedItemChangesAllowed($input, [static::$itemtype_1,
                static::$items_id_1,
                static::$itemtype_2,
                static::$items_id_2,
            ])
        ) {
            return false;
        }

        return parent::addNeededInfoToInput($input);
    }


    /**
     * Get the history name of first item
     *
     * @since 0.84
     *
     * @param CommonDBTM $item    CommonDBTM object   the other item (ie. : $item2)
     * @param string     $case : can be overwritten by object
     *                            - 'add' when this CommonDBRelation is added (to and item)
     *                            - 'update item previous' transfert : this is removed from the old item
     *                            - 'update item next' transfert : this is added to the new item
     *                            - 'delete' when this CommonDBRelation is remove (from an item)
     *
     * @return string The name of the entry for the database (ie. : correctly slashed)
     **/
    public function getHistoryNameForItem1(CommonDBTM $item, $case)
    {

        return $item->getNameID(['forceid'    => true,
            'additional' => true,
        ]);
    }


    /**
     * Get the history name of second item
     *
     * @since 0.84
     *
     * @param CommonDBTM $item the other item (ie. : $item1)
     * @param string     $case : can be overwritten by object
     *                            - 'add' when this CommonDBRelation is added (to and item)
     *                            - 'update item previous' transfert : this is removed from the old item
     *                            - 'update item next' transfert : this is added to the new item
     *                            - 'delete' when this CommonDBRelation is remove (from an item)
     *
     * @return string the name of the entry for the database (ie. : correctly slashed)
     **/
    public function getHistoryNameForItem2(CommonDBTM $item, $case)
    {

        return $item->getNameID(['forceid'    => true,
            'additional' => true,
        ]);
    }


    public function post_addItem()
    {

        if (
            (isset($this->input['_no_history']) && $this->input['_no_history'])
            || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)
        ) {
            return;
        }

        $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
        $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

        if ($item1 instanceof CommonDBTM && $item2 instanceof CommonDBTM) {
            if (
                $item1->dohistory
                && static::$logs_for_item_1
            ) {
                $changes = [
                    $this->_force_log_option,
                    '',
                    $this->getHistoryNameForItem1($item2, 'add'),
                ];
                Log::history(
                    $item1->getID(),
                    $item1->getType(),
                    $changes,
                    $item2->getType(),
                    static::$log_history_1_add
                );
            }

            if ($item2->dohistory && static::$logs_for_item_2) {
                $changes = [
                    '0',
                    '',
                    $this->getHistoryNameForItem2($item1, 'add'),
                ];
                Log::history(
                    $item2->getID(),
                    $item2->getType(),
                    $changes,
                    $item1->getType(),
                    static::$log_history_2_add
                );
            }
        }
    }

    public function post_updateItem($history = true)
    {

        if (
            (isset($this->input['_no_history']) && $this->input['_no_history'])
            || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)
        ) {
            return;
        }

        $items_1 = $this->getItemsForLog(static::$itemtype_1, static::$items_id_1);
        $items_2 = $this->getItemsForLog(static::$itemtype_2, static::$items_id_2);

        $new1 = $items_1['new'];
        if (isset($items_1['previous'])) {
            $previous1 = $items_1['previous'];
        } else {
            $previous1 = $items_1['new'];
        }

        $new2 = $items_2['new'];
        if (isset($items_2['previous'])) {
            $previous2 = $items_2['previous'];
        } else {
            $previous2 = $items_2['new'];
        }

        $oldvalues = $this->oldvalues;
        unset($oldvalues[static::$itemtype_1]);
        unset($oldvalues[static::$items_id_1]);
        unset($oldvalues[static::$itemtype_2]);
        unset($oldvalues[static::$items_id_2]);

        foreach (array_keys($oldvalues) as $field) {
            $changes = $this->getHistoryChangeWhenUpdateField($field);
            if ((!is_array($changes)) || (count($changes) != 3)) {
                continue;
            }
            /// TODO clean management of it
            if (
                $new1 && $new1->dohistory
                && static::$logs_for_item_1
            ) {
                Log::history(
                    $new1->getID(),
                    $new1->getType(),
                    $changes,
                    get_called_class() . '#' . $field,
                    static::$log_history_1_update
                );
            }
            if (
                $new2 && $new2->dohistory
                && static::$logs_for_item_2
            ) {
                Log::history(
                    $new2->getID(),
                    $new2->getType(),
                    $changes,
                    get_called_class() . '#' . $field,
                    static::$log_history_2_update
                );
            }
        }

        if (isset($items_1['previous']) || isset($items_2['previous'])) {
            if (
                $previous2
                && $previous1 && $previous1->dohistory
                && static::$logs_for_item_1
            ) {
                $changes[0] = '0';
                $changes[1] = $this->getHistoryNameForItem1(
                    $previous2,
                    'update item previous'
                );
                $changes[2] = "";
                Log::history(
                    $previous1->getID(),
                    $previous1->getType(),
                    $changes,
                    $previous2->getType(),
                    static::$log_history_1_delete
                );
            }

            if (
                $previous1
                && $previous2 && $previous2->dohistory
                && static::$logs_for_item_2
            ) {
                $changes[0] = '0';
                $changes[1] = $this->getHistoryNameForItem2(
                    $previous1,
                    'update item previous'
                );
                $changes[2] = "";
                Log::history(
                    $previous2->getID(),
                    $previous2->getType(),
                    $changes,
                    $previous1->getType(),
                    static::$log_history_2_delete
                );
            }

            if (
                $new2
                && $new1 && $new1->dohistory
                && static::$logs_for_item_1
            ) {
                $changes[0] = '0';
                $changes[1] = "";
                $changes[2] = $this->getHistoryNameForItem1($new2, 'update item next');
                Log::history(
                    $new1->getID(),
                    $new1->getType(),
                    $changes,
                    $new2->getType(),
                    static::$log_history_1_add
                );
            }

            if (
                $new1
                && $new2 && $new2->dohistory
                && static::$logs_for_item_2
            ) {
                $changes[0] = '0';
                $changes[1] = "";
                $changes[2] = $this->getHistoryNameForItem2($new1, 'update item next');
                Log::history(
                    $new2->getID(),
                    $new2->getType(),
                    $changes,
                    $new1->getType(),
                    static::$log_history_2_add
                );
            }
        }
    }

    public function cleanDBonMarkDeleted()
    {

        if (
            (isset($this->input['_no_history']) && $this->input['_no_history'])
            || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)
        ) {
            return;
        }

        if (
            $this->useDeletedToLockIfDynamic()
            && $this->isDynamic()
        ) {
            $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
            $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

            if ($item1 instanceof CommonDBTM && $item2 instanceof CommonDBTM) {
                if (
                    $item1->dohistory
                    && static::$logs_for_item_1
                ) {
                    $changes = [
                        '0',
                        $this->getHistoryNameForItem1($item2, 'lock'),
                        '',
                    ];

                    Log::history(
                        $item1->getID(),
                        $item1->getType(),
                        $changes,
                        $item2->getType(),
                        static::$log_history_1_lock
                    );
                }

                if (
                    $item2->dohistory
                    && static::$logs_for_item_2
                ) {
                    $changes = [
                        '0',
                        $this->getHistoryNameForItem2($item1, 'lock'),
                        '',
                    ];
                    Log::history(
                        $item2->getID(),
                        $item2->getType(),
                        $changes,
                        $item1->getType(),
                        static::$log_history_2_lock
                    );
                }
            }
        }
    }

    public function post_restoreItem()
    {

        if (
            (isset($this->input['_no_history']) && $this->input['_no_history'])
            || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)
        ) {
            return;
        }

        if (
            $this->useDeletedToLockIfDynamic()
            && $this->isDynamic()
        ) {
            $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
            $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

            if ($item1 instanceof CommonDBTM && $item2 instanceof CommonDBTM) {
                if (
                    $item1->dohistory
                    && static::$logs_for_item_1
                ) {
                    $changes = [
                        '0',
                        '',
                        $this->getHistoryNameForItem1($item2, 'unlock'),
                    ];
                    Log::history(
                        $item1->getID(),
                        $item1->getType(),
                        $changes,
                        $item2->getType(),
                        static::$log_history_1_unlock
                    );
                }

                if (
                    $item2->dohistory
                    && static::$logs_for_item_2
                ) {
                    $changes = [
                        '0',
                        '',
                        $this->getHistoryNameForItem2($item1, 'unlock'),
                    ];
                    Log::history(
                        $item2->getID(),
                        $item2->getType(),
                        $changes,
                        $item1->getType(),
                        static::$log_history_2_unlock
                    );
                }
            }
        }
    }

    public function post_deleteFromDB()
    {

        if (
            (isset($this->input['_no_history']) && $this->input['_no_history'])
            || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)
        ) {
            return;
        }

        $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
        $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

        if ($item1 instanceof CommonDBTM && $item2 instanceof CommonDBTM) {
            if (
                $item1->dohistory
                && static::$logs_for_item_1
            ) {
                $changes = [
                    '0',
                    $this->getHistoryNameForItem1($item2, 'delete'),
                    '',
                ];
                Log::history(
                    $item1->getID(),
                    $item1->getType(),
                    $changes,
                    $item2->getType(),
                    static::$log_history_1_delete
                );
            }

            if (
                $item2->dohistory
                && static::$logs_for_item_2
            ) {
                $changes = [
                    '0',
                    $this->getHistoryNameForItem2($item1, 'delete'),
                    '',
                ];
                Log::history(
                    $item2->getID(),
                    $item2->getType(),
                    $changes,
                    $item1->getType(),
                    static::$log_history_2_delete
                );
            }
        }
    }


    /**
     * @since 0.84
     *
     * @param string               $itemtype
     * @param HTMLTableBase        $base      HTMLTableBase object
     * @param HTMLTableSuperHeader $super     HTMLTableSuperHeader object (default NULL)
     * @param HTMLTableHeader      $father    HTMLTableHeader object (default NULL)
     * @param array                $options
     **/
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {

        if (isset($options[get_called_class() . '_side'])) {
            $side = $options[get_called_class() . '_side'];
        } else {
            $side = 0;
        }
        $oppositetype = '';
        if (
            ($side == 1)
            || ($itemtype == static::$itemtype_1)
        ) {
            $oppositetype = static::$itemtype_2;
        }
        if (
            ($side == 2)
            || ($itemtype == static::$itemtype_2)
        ) {
            $oppositetype = static::$itemtype_1;
        }
        if (
            class_exists($oppositetype)
            && method_exists($oppositetype, 'getHTMLTableHeader')
        ) {
            $oppositetype::getHTMLTableHeader(get_called_class(), $base, $super, $father, $options);
        }
    }


    /**
     * @since 0.84
     *
     * @param HTMLTableRow  $row      HTMLTableRow object (default NULL)
     * @param CommonDBTM    $item     CommonDBTM object (default NULL)
     * @param HTMLTableCell $father   HTMLTableCell object (default NULL)
     * @param array         $options
     **/
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        if (empty($item)) {
            if (empty($father)) {
                return;
            }
            $item = $father->getItem();
        }

        $criteria = self::getSQLCriteriaToSearchForItem($item->getType(), $item->getID());
        if ($criteria !== null) {
            $relation = new static();
            $iterator = $DB->request($criteria);
            foreach ($iterator as $line) {
                if ($line['is_1'] != $line['is_2']) {
                    if ($line['is_1'] == 0) {
                        $options['items_id'] = $line['items_id_1'];
                        $oppositetype        = $line['itemtype_1'];
                    } else {
                        $options['items_id'] = $line['items_id_2'];
                        $oppositetype        = $line['itemtype_2'];
                    }
                    if (
                        class_exists($oppositetype)
                        && method_exists($oppositetype, 'getHTMLTableCellsForItem')
                        && $relation->getFromDB($line[static::getIndexName()])
                    ) {
                        $oppositetype::getHTMLTableCellsForItem($row, $relation, $father, $options);
                    }
                }
            }
        }
    }


    /**
     * Affect a CommonDBRelation to a given item. By default, unaffect it
     *
     * @param integer $id       the id of the CommonDBRelation to affect
     * @param integer $peer     the number of the peer (ie.: 0 or 1)
     * @param integer $items_id the id of the new item
     * @param string  $itemtype the type of the new item
     *
     * @return boolean : true on success
     **/
    public function affectRelation($id, $peer, $items_id = 0, $itemtype = '')
    {

        $input = [static::getIndexName() => $id];

        if ($peer == 0) {
            $input[static::$items_id_1] = $items_id;

            if (preg_match('/^itemtype/', static::$itemtype_1)) {
                $input[static::$itemtype_1] = $itemtype;
            }
        } else {
            $input[static::$items_id_2] = $items_id;
            if (preg_match('/^itemtype/', static::$itemtype_2)) {
                $input[static::$itemtype_2] = $itemtype;
            }
        }

        return $this->update($input);
    }


    /**
     * Get all specificities of the current itemtype concerning the massive actions
     *
     * @since 0.85
     *
     * @return array of the specificities:
     *        'select_items_options_1' Base options for item_1 select
     *        'select_items_options_2' Base options for item_2 select
     *        'can_remove_all_at_once' Is it possible to remove all links at once ?
     *        'only_remove_all_at_once' Do we only allow to remove all links at once ?
     *                                  (implies 'can_remove_all_at_once')
     *        'itemtypes'              array of kind of items in case of itemtype as one item
     *        'button_labels'          array of the labels of the button indexed by the action name
     *        'normalized'             array('add', 'remove') of arrays containing each action
     *        'check_both_items_if_same_type' to check if the link already exists, also care of both
     *                                        items are of the same type, then switch them
     *        'can_link_several_times' Is it possible to link items several times ?
     *        'update_id_different'    Do we update the link if it already exists (not used in case
     *                                 of 'can_link_several_times')
     **/
    public static function getRelationMassiveActionsSpecificities()
    {

        return ['select_items_options_1'        => [],
            'dropdown_method_1'             => 'dropdown',
            'select_items_options_2'        => [],
            'dropdown_method_2'             => 'dropdown',
            'can_remove_all_at_once'        => true,
            'only_remove_all_at_once'       => false,
            'itemtypes'                     => [],
            'button_labels'                 => ['add'    => _sx('button', 'Add'),
                'remove' => _sx(
                    'button',
                    'Delete permanently'
                ),
            ],
            'normalized'                    => ['add'    => ['add'],
                'remove' => ['remove'],
            ],
            'check_both_items_if_same_type' => false,
            'can_link_several_times'        => false,
            'update_if_different'           => false,
        ];
    }


    /**
     * Display subForm of the massive action
     *
     * @param MassiveAction $ma           current massive action
     * @param integer       $peer_number  the number of the concerned peer
     *
     * @return void
     **/
    public static function showRelationMassiveActionsSubForm(MassiveAction $ma, $peer_number) {}


    /**
     * get the type of the item with the name of the action or the types of the input
     *
     * @since 0.85
     *
     * @param MassiveAction $ma current massive action
     *
     * @return int number of the peer
     **/
    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {

        $items = $ma->getItems();
        // If direct itemtype, then, its easy to find !
        if (isset($items[static::$itemtype_1])) {
            return 2;
        }
        if (isset($items[static::$itemtype_2])) {
            return 1;
        }

        // Else, check if one of both peer is 'itemtype*'
        if (preg_match('/^itemtype/', static::$itemtype_1)) {
            return 2;
        }
        if (preg_match('/^itemtype/', static::$itemtype_2)) {
            return 1;
        }

        // Else we cannot define !
        return 0;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        $specificities = static::getRelationMassiveActionsSpecificities();
        $action        = $ma->getAction();

        // First, get normalized action : add or remove
        if (in_array($action, $specificities['normalized']['add'])) {
            $normalized_action = 'add';
        } elseif (in_array($action, $specificities['normalized']['remove'])) {
            $normalized_action = 'remove';
        } else {
            // If we cannot get normalized action, then, it's not for this method!
            return parent::showMassiveActionsSubForm($ma);
        }

        switch ($normalized_action) {
            case 'add':
            case 'remove':
                // Get the peer number. For Document_Item, it depends on the action's name
                $peer_number = static::getRelationMassiveActionsPeerForSubForm($ma);
                switch ($peer_number) {
                    case 1:
                        $peertype = static::$itemtype_1;
                        $peers_id = static::$items_id_1;
                        break;
                    case 2:
                        $peertype = static::$itemtype_2;
                        $peers_id = static::$items_id_2;
                        break;
                    default:
                        throw new \LogicException();
                }
                if (
                    ($normalized_action == 'remove')
                    && ($specificities['only_remove_all_at_once'])
                ) {
                    // If we just want to remove all the items, then just set hidden fields
                    echo Html::hidden('peer_' . $peertype, ['value' => '']);
                    echo Html::hidden('peer_' . $peers_id, ['value' => -1]);
                } else {
                    // Else, it depends if the peer is an itemtype or not
                    $options = $specificities['select_items_options_' . $peer_number];
                    // Do we allow to remove all the items at once ? Then, rename the default value !
                    if (
                        ($normalized_action == 'remove')
                        && $specificities['can_remove_all_at_once']
                    ) {
                        $options['emptylabel'] = __('Remove all at once');
                    }
                    if (preg_match('/^itemtype/', $peertype)) {
                        if (count($specificities['itemtypes']) > 0) {
                            $options['itemtype_name'] = 'peer_' . $peertype;
                            $options['items_id_name'] = 'peer_' . $peers_id;
                            $options['itemtypes']     = $specificities['itemtypes'];
                            // At least, if not forced by user, 'checkright' == true
                            if (!isset($options['checkright'])) {
                                $options['checkright']    = true;
                            }
                            Dropdown::showSelectItemFromItemtypes($options);
                        }
                    } else {
                        $options['name'] = 'peer_' . $peers_id;
                        if (isset($_POST['entity_restrict'])) {
                            $options['entity'] = Session::getMatchingActiveEntities($_POST['entity_restrict']);
                        }
                        if ($normalized_action == 'remove') {
                            $options['nochecklimit'] = true;
                        }
                        $dropdown_method = $specificities['dropdown_method_' . $peer_number];
                        $peertype::$dropdown_method($options);
                    }
                }
                // Allow any relation to display its own fields (NetworkPort_Vlan for tagged ...)
                static::showRelationMassiveActionsSubForm($ma, $peer_number);
                echo "<br><br>" . Html::submit(
                    $specificities['button_labels'][$action],
                    ['name' => 'massiveaction']
                );
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    /**
     * @since 0.85
     *
     * Set based array for static::add or static::update in case of massive actions are doing
     * something.
     *
     * @param string     $action  the name of the action
     * @param CommonDBTM $item    the item on which apply the massive action
     * @param integer[]  $ids     ids of the item on which apply the action
     * @param array      $input   input provided by the form ($_POST, $_GET ...)
     *
     * @return array containing the elements
     **/
    public static function getRelationInputForProcessingOfMassiveActions(
        $action,
        CommonDBTM $item,
        array $ids,
        array $input
    ) {
        return [];
    }


    /**
     * @warning this is not valid if $itemtype_1 == $itemtype_2 !
     *
     * @since 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        $action        = $ma->getAction();
        $input         = $ma->getInput();

        $specificities = static::getRelationMassiveActionsSpecificities();

        // First, get normalized action : add or remove
        if (in_array($action, $specificities['normalized']['add'])) {
            $normalized_action = 'add';
        } elseif (in_array($action, $specificities['normalized']['remove'])) {
            $normalized_action = 'remove';
        } else {
            // If we cannot get normalized action, then, its not for this method !
            parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
            return;
        }

        $link     = new static();

        // Get the default 'input' entries from the relation
        $input2   = static::getRelationInputForProcessingOfMassiveActions(
            $action,
            $item,
            $ids,
            $input
        );

        // complete input2 with the right fields from input and define the peer with this information
        foreach ([static::$itemtype_1, static::$items_id_1] as $field) {
            if (isset($input['peer_' . $field])) {
                $input2[$field] = $input['peer_' . $field];
                $item_number = 2;
            }
        }

        foreach ([static::$itemtype_2, static::$items_id_2] as $field) {
            if (isset($input['peer_' . $field])) {
                $input2[$field] = $input['peer_' . $field];
                $item_number = 1;
            }
        }

        // If the fields provided by showMassiveActionsSubForm are not valid then quit !
        if (!isset($item_number)) {
            $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            $ma->addMessage($link->getErrorMessage(ERROR_NOT_FOUND));
            return;
        }

        if ($item_number == 1) {
            $itemtype = static::$itemtype_1;
            $items_id = static::$items_id_1;
            $peertype = static::$itemtype_2;
            $peers_id = static::$items_id_2;
        } else {
            $itemtype = static::$itemtype_2;
            $items_id = static::$items_id_2;
            $peertype = static::$itemtype_1;
            $peers_id = static::$items_id_1;
        }

        if (preg_match('/^itemtype/', $itemtype)) {
            $input2[$itemtype] = $item->getType();
        }

        // Get the peer from the $input2 and the name of its fields
        $peer = static::getItemFromArray($peertype, $peers_id, $input2, true, true, true);

        // $peer not valid => not in DB or try to remove all at once !
        if (!($peer instanceof CommonDBTM) || $peer->isNewItem()) {
            if ((isset($input2[$peers_id])) && ($input2[$peers_id] > 0)) {
                $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                if ($peer instanceof CommonDBTM) {
                    $ma->addMessage($peer->getErrorMessage(ERROR_NOT_FOUND));
                } else {
                    $ma->addMessage($link->getErrorMessage(ERROR_NOT_FOUND));
                }
                return;
            }
            if (
                !$specificities['can_remove_all_at_once']
                && !$specificities['only_remove_all_at_once']
            ) {
                return false;
            }
            $peer = false;
        }

        // Make a link between $item_1, $item_2 and $item and $peer. Thus, we will be able to update
        // $item without having to care about the number of the item
        if ($item_number == 1) {
            $item_1 = &$item;
            $item_2 = &$peer;
        } else {
            $item_1 = &$peer;
            $item_2 = &$item;
        }

        switch ($normalized_action) {
            case 'add':
                // remove all at once only available for remove !
                if (!$peer) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    $ma->addMessage($link->getErrorMessage(ERROR_ON_ACTION));
                    return;
                }
                foreach ($ids as $key) {
                    if (!$item->getFromDB($key)) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        continue;
                    }
                    $input2[$items_id] = $item->getID();
                    // If 'can_link_several_times', then, we add the elements !
                    if ($specificities['can_link_several_times']) {
                        if ($link->can(-1, CREATE, $input2)) {
                            if ($link->add($input2)) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($link->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($link->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $link->getEmpty();
                        if (!$link->getFromDBForItems($item_1, $item_2)) {
                            if (
                                ($specificities['check_both_items_if_same_type'])
                                && ($item_1->getType() == $item_2->getType())
                            ) {
                                $link->getFromDBForItems($item_2, $item_1);
                            }
                        }
                        if (!$link->isNewItem()) {
                            if (!$specificities['update_if_different']) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($link->getErrorMessage(ERROR_ALREADY_DEFINED));
                                continue;
                            }
                            $input2[static::getIndexName()] = $link->getID();
                            if ($link->can($link->getID(), UPDATE, $input2)) {
                                if ($link->update($input2)) {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                    $ma->addMessage($link->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($link->getErrorMessage(ERROR_RIGHT));
                            }
                            // if index defined, then cannot not add any other link due to index unicity
                            unset($input2[static::getIndexName()]);
                        } else {
                            if ($link->can(-1, CREATE, $input2)) {
                                if ($link->add($input2)) {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                    $ma->addMessage($link->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($link->getErrorMessage(ERROR_RIGHT));
                            }
                        }
                    }
                }
                return;

            case 'remove':
                foreach ($ids as $key) {
                    // First, get the query to find all occurences of the link item<=>key
                    if (!$peer) {
                        $criteria = static::getSQLCriteriaToSearchForItem($item->getType(), $key);
                    } else {
                        if (!$item->getFromDB($key)) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                            continue;
                        }

                        $WHERE = [
                            static::$items_id_1  => $item_1->getID(),
                            static::$items_id_2  => $item_2->getID(),
                        ];
                        if (preg_match('/^itemtype/', static::$itemtype_1)) {
                            $WHERE[static::$itemtype_1] = $item_1->getType();
                        }
                        if (preg_match('/^itemtype/', static::$itemtype_2)) {
                            $WHERE[static::$itemtype_2] = $item_2->getType();
                        }

                        if (
                            ($specificities['check_both_items_if_same_type'])
                            && ($item_1->getType() == $item_2->getType())
                        ) {
                            $ORWHERE = [
                                static::$items_id_1 = $item_2->getID(),
                                static::$items_id_2 = $item_2->getID(),
                            ];
                            if (preg_match('/^itemtype/', static::$itemtype_1)) {
                                $ORWHERE[static::$itemtype_1] = $item_2->getType();
                            }
                            if (preg_match('/^itemtype/', static::$itemtype_2)) {
                                $ORWHERE[static::$itemtype_2] = $item_2->getType();
                            }
                            $WHERE = [
                                'OR' => [
                                    $WHERE,
                                    $ORWHERE,
                                ],
                            ];
                        }

                        $criteria = [
                            'SELECT' => static::getIndexName(),
                            'FROM'   => static::getTable(),
                            'WHERE'  => $WHERE,
                        ];
                    }
                    $request        = $DB->request($criteria);
                    $number_results = count($request);
                    if ($number_results == 0) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($link->getErrorMessage(ERROR_NOT_FOUND));
                        continue;
                    }
                    $ok      = 0;
                    $ko      = 0;
                    $noright = 0;
                    foreach ($request as $line) {
                        if ($link->can($line[static::getIndexName()], DELETE)) {
                            if ($link->delete(['id' => $line[static::getIndexName()]])) {
                                $ok++;
                            } else {
                                $ko++;
                                $ma->addMessage($link->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $noright++;
                            $ma->addMessage($link->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                    if ($ok == $number_results) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                    } else {
                        if ($noright > 0) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        } elseif ($ko > 0) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                return;
        }

        // @phpstan-ignore deadCode.unreachable (defensive programming)
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Get linked items list for specified item
     *
     * @since 9.3.1
     *
     * @param CommonDBTM $item  Item instance
     * @param boolean    $noent Flag to not compute entity information (see Document_Item::getListForItemParams)
     *
     * @return array
     */
    protected static function getListForItemParams(CommonDBTM $item, $noent = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (Session::isCron()) {
            $noent = true;
        }

        $inverse = $item->getType() == static::$itemtype_1 || static::$itemtype_1 === 'itemtype';

        $link_type  = static::$itemtype_1;
        $link_id    = static::$items_id_1;
        $where_id   = static::$items_id_2;

        if ($inverse === true) {
            $link_type  = static::$itemtype_2;
            if ($link_type == 'itemtype') {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot use getListForItemParams() for a %s',
                        $item->getType()
                    )
                );
            }
            $link_id    = static::$items_id_2;
            $where_id   = static::$items_id_1;
        }

        $link = getItemForItemtype($link_type);
        $link_table = $link::getTable();

        $params = [
            'SELECT'    => [static::getTable() . '.id AS linkid', $link_table . '.*'],
            'FROM'      => static::getTable(),
            'LEFT JOIN' => [
                $link_table  => [
                    'FKEY'   => [
                        static::getTable()   => $link_id,
                        $link_table          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                static::getTable() . '.' . $where_id => $item->fields['id'],
            ],
            'ORDER'     => $link_table . '.name',
        ];

        $rel_class = static::class;
        $rel = new $rel_class();
        if ($rel->maybeDynamic()) {
            $params['SELECT'][] = static::getTable() . '.is_dynamic';
        }

        if ($rel->maybeRecursive()) {
            $params['SELECT'][] = static::getTable() . '.is_recursive';
        }

        if ($DB->fieldExists(static::getTable(), 'itemtype')) {
            $params['WHERE'][static::getTable() . '.itemtype'] = $item->getType();
        }

        if ($noent === false && $link->isEntityAssign() && $link_type != Entity::getType()) {
            $params['SELECT'][] = 'glpi_entities.id AS entity';
            $params['INNER JOIN']['glpi_entities'] = [
                'FKEY'   => [
                    $link_table       => 'entities_id',
                    'glpi_entities'   => 'id',
                ],
            ];
            $params['WHERE'] += getEntitiesRestrictCriteria($link_table, '', '', 'auto');
            $params['ORDER'] = ['glpi_entities.completename', $params['ORDER']];
        }

        return $params;
    }

    /**
     * Get linked items list for specified item.
     *
     * The returned data contains the fields of the linked items, plus a reference to the linktable ($this) : 'linkid'.
     *
     * @since 9.3.1
     *
     * @param CommonDBTM $item Item instance
     * @param integer    $start Start index
     * @param integer    $limit Limit of results. If 0, no limit.
     * @param array      $order The order for the results where the first element is the column name that will be sorted and the second element is the direction of the sorting (ASC or DESC)
     *
     * @return DBmysqlIterator
     */
    public static function getListForItem(CommonDBTM $item, int $start = 0, int $limit = 0, array $order = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $params = static::getListForItemParams($item);
        $params['START'] = $start;
        if ($limit > 0) {
            $params['LIMIT'] = $limit;
        }
        if (!empty($order)) {
            $params['ORDER'] = $order;
        }
        return $DB->request($params);
    }

    /**
     * Get distinct item types query parameters
     *
     * @since 9.3.1
     *
     * @param integer $items_id    Object id to restrict on
     * @param array   $extra_where Extra where clause
     *
     * @return array
     */
    protected static function getDistinctTypesParams($items_id, $extra_where = [])
    {
        $params = [
            'SELECT'          => static::$itemtype_2,
            'DISTINCT'        => true,
            'FROM'            => static::getTable(),
            'WHERE'           => [
                static::$items_id_1  => $items_id,
            ] + $extra_where,
            'ORDER'           => static::$itemtype_2,
        ];
        return $params;
    }

    /**
     * Get distinct item types
     *
     * @since 9.3.1
     *
     * @param integer $items_id    Object id to restrict on
     * @param array   $extra_where Extra where clause
     *
     * @return DBmysqlIterator
     */
    public static function getDistinctTypes($items_id, $extra_where = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $params = static::getDistinctTypesParams($items_id, $extra_where);
        $types_iterator = $DB->request($params);
        return $types_iterator;
    }

    /**
     * Get SELECT param for getTypeItemsQueryParams
     *
     * @param CommonDBTM $item
     *
     * @return array
     */
    public static function getTypeItemsQueryParams_Select(CommonDBTM $item): array
    {
        return [
            $item->getTable() . '.*',
            static::getTable() . '.id AS linkid',
        ];
    }

    /**
     * Get items for an itemtype
     *
     * @since 9.3.1
     *
     * @param integer $items_id Object id to restrict on
     * @param string  $itemtype Type for items to retrieve
     * @param boolean $noent    Flag to not compute entity information (see Document_Item::getTypeItemsQueryParams)
     * @param array   $where    Inital WHERE clause. Defaults to []
     *
     * @return array
     */
    protected static function getTypeItemsQueryParams($items_id, $itemtype, $noent = false, $where = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $item = getItemForItemtype($itemtype);
        $order_col = $item->getNameField();

        if ($item instanceof CommonDevice) {
            $order_col = "designation";
        } elseif ($item instanceof Item_Devices) {
            $order_col = "itemtype";
        } elseif ($item instanceof Ticket || $item instanceof CommonITILValidation || $item instanceof Notepad) {
            $order_col = 'id';
        }

        if (!count($where)) {
            $where = [static::getTable() . '.' . static::$items_id_1  => $items_id];
        }

        $params = [
            'SELECT' => static::getTypeItemsQueryParams_Select($item),
            'FROM'   => $item->getTable(),
            'WHERE'  => $where,
            'LEFT JOIN' => [
                static::getTable() => [
                    'FKEY' => [
                        static::getTable()   => 'items_id',
                        $item->getTable()    => 'id',
                    ],
                ],
            ],
            'ORDER'     => $item->getTable() . '.' . $order_col,
        ];

        if ($DB->fieldExists(static::getTable(), 'is_deleted')) {
            $params['WHERE'][static::getTable() . '.is_deleted'] = 0;
        }

        if ($DB->fieldExists(static::getTable(), 'itemtype')) {
            $params['WHERE'][static::getTable() . '.itemtype'] = $itemtype;
        }

        if ($item->maybeTemplate()) {
            $params['WHERE'][$item->getTable() . '.is_template'] = 0;
        }

        if ($noent === false && $item->isEntityAssign() && $itemtype != Entity::getType()) {
            $params['SELECT'][] = 'glpi_entities.id AS entity';
            $params['LEFT JOIN']['glpi_entities'] = [
                'FKEY'   => [
                    $item->getTable() => 'entities_id',
                    'glpi_entities'   => 'id',
                ],
            ];
            $params['WHERE'] += getEntitiesRestrictCriteria($item->getTable(), '', '', $item->maybeRecursive());
            $params['ORDER'] = ['glpi_entities.completename', $params['ORDER']];
        }

        return $params;
    }

    /**
     * Get items for an itemtype
     *
     * @since 9.3.1
     *
     * @param integer $items_id Object id to restrict on
     * @param string  $itemtype Type for items to retrieve
     *
     * @return DBmysqlIterator
     */
    public static function getTypeItems($items_id, $itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $params = static::getTypeItemsQueryParams($items_id, $itemtype);
        $iterator = $DB->request($params);

        return $iterator;
    }

    /**
     * Count for item
     *
     * @param CommonDBTM $item CommonDBTM object
     *
     * @return integer
     */
    public static function countForItem(CommonDBTM $item)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $params = static::getListForItemParams($item);
        unset($params['SELECT']);
        $params['COUNT'] = 'cpt';
        $iterator = $DB->request($params);

        $cpt = 0;
        foreach ($iterator as $row) {
            $cpt += $row['cpt'];
        }

        return $cpt;
    }

    /**
     * Count items for main itemtype
     *
     * @param CommonDBTM $item              Item instance
     * @param array      $extra_types_where Extra WHERE clause on types
     *
     * @return integer
     **/
    public static function countForMainItem(CommonDBTM $item, $extra_types_where = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $nb = 0;

        $types_iterator = static::getDistinctTypes($item->fields['id'], $extra_types_where);
        foreach ($types_iterator as $data) {
            if (!getItemForItemtype($data['itemtype'])) {
                continue;
            }

            $params = static::getTypeItemsQueryParams($item->fields['id'], $data['itemtype']);
            unset($params['SELECT']);
            $params['COUNT'] = 'cpt';
            $iterator = $DB->request($params);

            foreach ($iterator as $row) {
                $nb += $row['cpt'];
            }
        }
        return $nb;
    }

    public static function getItemField($itemtype): string
    {
        if (isset(static::$items_id_1) && getItemtypeForForeignKeyField(static::$items_id_1) == $itemtype) {
            return static::$items_id_1;
        }
        if (isset(static::$items_id_2) && getItemtypeForForeignKeyField(static::$items_id_2) == $itemtype) {
            return static::$items_id_2;
        }

        if (isset(static::$itemtype_1) && isset(static::$itemtype_2) && preg_match('/^itemtype/', static::$itemtype_1) && preg_match('/^itemtype/', static::$itemtype_2)) {
            throw new \RuntimeException('Bad relation (' . $itemtype . ', ' . static::class . ', ' . static::$itemtype_1 . ', ' . static::$itemtype_2 . ')');
        }

        if (isset(static::$itemtype_1) && preg_match('/^itemtype/', static::$itemtype_1)) {
            return static::$items_id_1;
        }
        if (isset(static::$itemtype_2) && preg_match('/^itemtype/', static::$itemtype_2)) {
            return static::$items_id_2;
        }

        throw new \RuntimeException('Cannot guess ');
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'clone';
        return $forbidden;
    }

    /**
     * Check if the given class match $itemtype_1 or $itemtype_2
     *
     * @param string $class
     *
     * @return int 0 (not a part of the relation), 1 ($itemtype_1) or 2 ($itemtype_2)
     */
    public static function getMemberPosition(string $class): int
    {
        if (is_a($class, static::$itemtype_1, true)) {
            return 1;
        } elseif (is_a($class, static::$itemtype_2, true)) {
            return 2;
        } elseif (
            preg_match('/^itemtype/', static::$itemtype_1) === 1
            && preg_match('/^itemtype/', static::$itemtype_2) === 0
        ) {
            return 1;
        } elseif (
            preg_match('/^itemtype/', static::$itemtype_2) === 1
            && preg_match('/^itemtype/', static::$itemtype_1) === 0
        ) {
            return 2;
        } else {
            // Not a member of this relation
            return 0;
        }
    }
}
