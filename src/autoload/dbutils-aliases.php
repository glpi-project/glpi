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

/**
 * Return foreign key field name for a table
 *
 * @param string $table table name
 *
 * @return string|''
 *      field name used for a foreign key to the parameter table,
 *      or an empty string if the table name does match the GLPI table name pattern
 */
function getForeignKeyFieldForTable($table)
{
    $dbu = new DbUtils();
    return $dbu->getForeignKeyFieldForTable($table);
}


/**
 * Check if field is a foreign key field
 *
 * @since 0.84
 *
 * @param string $field field name
 *
 * @return bool
 */
function isForeignKeyField($field)
{
    $dbu = new DbUtils();
    return $dbu->isForeignKeyField($field);
}


/**
 * Return foreign key field name for an itemtype
 *
 * @param class-string<CommonDBTM> $itemtype itemtype
 *
 * @return string field name used for a foreign key to the parameter itemtype
 */
function getForeignKeyFieldForItemType($itemtype)
{
    return getForeignKeyFieldForTable(getTableForItemType($itemtype));
}


/**
 * Return table name for a given foreign key name
 *
 * @param string $fkname foreign key name
 *
 * @return string|''
 *      table name corresponding to a foreign key name
 *      or an empty string if the foreign key name does match the GLPI foreign key name pattern
 */
function getTableNameForForeignKeyField($fkname)
{
    $dbu = new DbUtils();
    return $dbu->getTableNameForForeignKeyField($fkname);
}


/**
 * Return ItemType for a table
 *
 * @param string $table table name
 *
 * @return class-string<CommonDBTM>|null
 *      itemtype corresponding to a table name parameter,
 *      or null if no valid itemtype is attached to the table
 */
function getItemTypeForTable($table)
{
    $dbu = new DbUtils();
    return $dbu->getItemTypeForTable($table);
}

/**
 * Return an item instance for the corresponding table.
 */
function getItemForTable(string $table): ?CommonDBTM
{
    $dbu = new DbUtils();
    return $dbu->getItemForTable($table);
}

/**
 * Return ItemType for a foreign key
 *
 * @param string $fkname
 *
 * @return class-string<CommonDBTM>|null
 *      Itemtype class for the fkname parameter,
 *      or null if no valid itemtype is attached to the foreign key field
 */
function getItemtypeForForeignKeyField($fkname)
{
    $dbu = new DbUtils();
    return $dbu->getItemtypeForForeignKeyField($fkname);
}

/**
 * Return an item instance for the corresponding foreign key field.
 */
function getItemForForeignKeyField(string $fkname): ?CommonDBTM
{
    $dbu = new DbUtils();
    return $dbu->getItemForForeignKeyField($fkname);
}

/**
 * Return table name for an item type
 *
 * @param class-string<CommonDBTM> $itemtype itemtype
 *
 * @return string table name corresponding to the itemtype parameter
 */
function getTableForItemType($itemtype)
{
    $dbu = new DbUtils();
    return $dbu->getTableForItemType($itemtype);
}


/**
 * Get new item objet for an itemtype
 *
 * @since 0.83
 *
 * @template T of CommonDBTM
 * @param class-string<T>|string $itemtype
 * @return ($itemtype is class-string<T> ? T : false)
 */
function getItemForItemtype($itemtype)
{
    $dbu = new DbUtils();
    return $dbu->getItemForItemtype($itemtype);
}


/**
 * Return the plural of a string
 *
 * @param string $string input string
 *
 * @return string plural of the parameter string
 */
function getPlural($string)
{
    $dbu = new DbUtils();
    return $dbu->getPlural($string);
}


/**
 * Return the singular of a string
 *
 * @param string $string input string
 *
 * @return string singular of the parameter string
 */
function getSingular($string)
{
    $dbu = new DbUtils();
    return $dbu->getSingular($string);
}


/**
 * Count the number of elements in a table.
 *
 * @param string|string[]            $table     table name(s)
 * @param string|array<mixed, mixed> $condition filtering criteria
 *
 * @return int Number of elements in table
 */
function countElementsInTable($table, $condition = [])
{
    $dbu = new DbUtils();
    return $dbu->countElementsInTable($table, $condition);
}

/**
 * Count the number of elements in a table.
 *
 * @param string|string[]            $table     table name(s)
 * @param string                     $field     field name
 * @param string|array<mixed, mixed> $condition filtering criteria
 *
 * @return int nb of elements in table
 */
function countDistinctElementsInTable($table, $field, $condition = [])
{
    $dbu = new DbUtils();
    return $dbu->countDistinctElementsInTable($table, $field, $condition);
}

/**
 * Count the number of elements in a table for a specific entity
 *
 * @param string|string[]            $table     table name(s)
 * @param string|array<mixed, mixed> $condition filtering criteria
 *
 * @return int Number of elements in table
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function countElementsInTableForMyEntities($table, $condition = [])
{
    $dbu = new DbUtils();
    return $dbu->countElementsInTableForMyEntities($table, $condition);
}


/**
 * Count the number of elements in a table for a specific entity
 *
 * @param string|string[]            $table     table name(s)
 * @param int                        $entity    the entity ID
 * @param string|array<mixed, mixed> $condition filtering criteria
 * @param bool                       $recursive Whether to recurse or not. If true, will be conditionned on item recursivity
 *
 * @return int number of elements in table
 */
function countElementsInTableForEntity($table, $entity, $condition = [], $recursive = true)
{
    $dbu = new DbUtils();
    return $dbu->countElementsInTableForEntity($table, $entity, $condition, $recursive);
}

/**
 * Get data from a table in an array.
 * /!\ CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
 *
 * @param string                     $table    Table name
 * @param string|array<mixed, mixed> $criteria filtering criteria
 * @param bool                       $usecache Use cache (false by default)
 * @param string                     $order    Result order (default '')
 *
 * @return array containing all the data
 *
 * @since 9.5.0
 */
function getAllDataFromTable($table, $criteria = [], $usecache = false, $order = '')
{
    $dbu = new DbUtils();
    return $dbu->getAllDataFromTable($table, $criteria, $usecache, $order);
}

/**
 * Get the Name of the element of a Dropdown Tree table
 *
 * @param string  $table       Dropdown Tree table
 * @param int     $ID          ID of the element
 * @param bool    $withcomment whether to get the array with the comments
 * @param bool    $translate   whether to get translated values
 *
 * @return ($withcomment is true ? array{name: string, comment: string} : string)
 *
 * @see DbUtils::getTreeValueCompleteName
 *
 * @TODO Deprecate the `$withcomment` parameter, it is never used.
 * @TODO Deprecate the `$translate` parameter, it is never used.
 */
function getTreeLeafValueName($table, $ID, $withcomment = false, $translate = true)
{
    $dbu = new DbUtils();
    return $dbu->getTreeLeafValueName($table, $ID, $withcomment, $translate);
}


/**
 * Get completename of a Dropdown Tree table
 *
 * @param string  $table       Dropdown Tree table
 * @param int     $ID          ID of the element
 * @param bool    $withcomment whether to get the array with the comments
 * @param bool    $translate   whether to get translated values
 * @param bool    $tooltip     whether to get a tooltip for additional comments
 * @param string  $default     default value returned when item not exists
 *
 * @return ($withcomment is true ? array{name: string, comment: string} : string)
 *
 * @see DbUtils::getTreeLeafValueName
 *
 * @since 11.0.0 Usage of the `$withcomment` parameter is deprecated.
 */
function getTreeValueCompleteName($table, $ID, $withcomment = false, $translate = true, $tooltip = true, string $default = '&nbsp;')
{
    if ($withcomment) {
        Toolbox::deprecated('Usage of the `$withcomment` parameter is deprecated. Use `Dropdown::getDropdownComments()` instead.');
    }

    $dbu = new DbUtils();
    return $dbu->getTreeValueCompleteName($table, $ID, $withcomment, $translate, $tooltip, $default);
}


/**
 * Get the tree value name (corresponds to the relative completename).
 *
 * @param string  $table     table name
 * @param int     $ID        integer  value ID
 * @param string  $wholename current name to complete (use for recursivity) (default '')
 * @param int     $level     current level of recursion (default 0)
 *
 * @return array{0: string, 1:int}
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function getTreeValueName($table, $ID, $wholename = "", $level = 0)
{
    $dbu = new DbUtils();
    return $dbu->getTreeValueName($table, $ID, $wholename, $level);
}


/**
 * Get the ancestors of an item in a tree dropdown.
 *
 * @param string    $table    Table name
 * @param int|int[] $items_id The IDs of the items. If an array is passed, the result will be the union of the ancestors of each item.
 *
 * @return int[] IDs of the ancestors.
 */
function getAncestorsOf($table, $items_id)
{
    $dbu = new DbUtils();
    return $dbu->getAncestorsOf($table, $items_id);
}


/**
 * Get the sons of an item in a tree dropdown.
 *
 * @param string  $table table name
 * @param int     $IDf   The ID of the father
 *
 * @return int[] IDs of the sons
 */
function getSonsOf($table, $IDf)
{
    $dbu = new DbUtils();
    return $dbu->getSonsOf($table, $IDf);
}


/**
 * Get the sons and the ancestors of an item in a tree dropdown.
 *
 * @since 0.84
 *
 * @param string $table table name
 * @param int    $IDf   The ID of the father
 *
 * @return int[] IDs of the sons and the ancestors
 */
function getSonsAndAncestorsOf($table, $IDf)
{
    $dbu = new DbUtils();
    return $dbu->getSonsAndAncestorsOf($table, $IDf);
}


/**
 * Get the sons of an item in a tree dropdown
 *
 * @param string  $table table name
 * @param int     $IDf   The ID of the father
 *
 * @return array<int, array{name: string, tree: array<int, mixed>}> Recursive tree
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function getTreeForItem($table, $IDf)
{
    $dbu = new DbUtils();
    return $dbu->getTreeForItem($table, $IDf);
}


/**
 * Construct a tree from a list structure
 *
 * @param array<int, array{name: string, parent: int}>  $list
 * @param int                                           $root root of the tree
 *
 * @return array<int, array{name: string, tree: array<int, mixed>}> Recursive tree
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function contructTreeFromList($list, $root)
{
    $dbu = new DbUtils();
    return $dbu->constructTreeFromList($list, $root);
}


/**
 * Construct a list from a tree structure
 *
 * @param array<int, array{tree: array<int, mixed>}> $tree   recursive tree
 * @param int                                        $parent root of the tree
 *
 * @return array<int, int> list of items in the tree
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function contructListFromTree($tree, $parent = 0)
{
    $dbu = new DbUtils();
    return $dbu->constructListFromTree($tree, $parent);
}


/**
 * Format a user name.
 *
 * @param integer       $ID           ID of the user.
 * @param string|null   $login        login of the user
 * @param string|null   $realname     realname of the user
 * @param string|null   $firstname    firstname of the user
 * @param integer       $link         include link
 * @param integer       $cut          IGNORED PARAMETER
 * @param boolean       $force_config force order and id_visible to use common config
 *
 * @return string
 *
 * @since 11.0 `$link` parameter is deprecated
 * @since 11.0 `$cut` parameter is ignored
 */
function formatUserName($ID, $login, $realname, $firstname, $link = 0, $cut = 0, $force_config = false)
{
    $dbu = new DbUtils();

    if ((bool) $cut) {
        trigger_error('`$cut` parameter is now ignored.', E_USER_WARNING);
    }

    if ((bool) $link) {
        Toolbox::deprecated('`$link` parameter is deprecated. Use `formatUserLink()` instead.');
        return $dbu->formatUserLink($ID, $login, $realname, $firstname);
    }

    return $dbu->formatUserName($ID, $login, $realname, $firstname, 0, 0, $force_config);
}

/**
 * Format a user link.
 *
 * @param integer       $id           ID of the user.
 * @param string|null   $login        login of the user
 * @param string|null   $realname     realname of the user
 * @param string|null   $firstname    firstname of the user
 *
 * @return string
 */
function formatUserLink(int $id, ?string $login, ?string $realname, ?string $firstname)
{
    $dbu = new DbUtils();
    return $dbu->formatUserLink($id, $login, $realname, $firstname);
}


/**
 * Get name of the user with the given ID.
 *
 * @param int       $ID
 * @param int<0, 2> $link
 *      0 = No link
 *      1 = Show link to user.form.php
 *      2 = return array with comments and link
 * @param bool      $disable_anon   disable anonymization of username
 *
 * @return ($link is 2 ? array{name: string, link: string, comment: string} : string)
 *
 * @since 11.0 `$link` parameter is deprecated.
 */
function getUserName($ID, $link = 0, $disable_anon = false)
{
    if ($link != 0) {
        Toolbox::deprecated('Usage of `$link` parameter is deprecated. See `DbUtils::getUserName()`.');
    }
    $dbu = new DbUtils();
    return $dbu->getUserName($ID, $link, $disable_anon);
}

/**
 * Get link of the given user.
 *
 * @param int $id
 *
 * @return string
 */
function getUserLink(int $id): string
{
    $dbu = new DbUtils();
    return $dbu->getUserLink($id);
}


/**
 * Determine if an index exists in database
 *
 * @param string $table table of the index
 * @param string $field name of the index
 *
 * @return boolean
 */
function isIndex($table, $field)
{
    $dbu = new DbUtils();
    return $dbu->isIndex($table, $field);
}


/**
 * Determine if a foreign key exists in database
 *
 * @param string $table
 * @param string $keyname
 *
 * @return boolean
 */
function isForeignKeyContraint($table, $keyname)
{
    $dbu = new DbUtils();
    return $dbu->isForeignKeyContraint($table, $keyname);
}


/**
 * Create a new name using a autoname field defined in a template
 *
 * @param string  $objectName  autoname template
 * @param string  $field       field to autoname
 * @param boolean $isTemplate  true if create an object from a template
 * @param string  $itemtype    item type
 * @param integer $entities_id limit generation to an entity (default -1)
 *
 * @return string new auto string
 */
function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id = -1)
{
    $dbu = new DbUtils();
    return $dbu->autoName($objectName, $field, $isTemplate, $itemtype, $entities_id);
}

/**
 * Add dates for request
 *
 * @param string $field  table.field to request
 * @param string $begin  begin date
 * @param string $end    end date
 *
 * @return array
 */
function getDateCriteria($field, $begin, $end)
{
    $dbu = new DbUtils();
    return $dbu->getDateCriteria($field, $begin, $end);
}

/**
 * Export an array to be stored in a simple field in the database
 *
 * @param array|'' $TAB Array to export / encode (one level depth)
 *
 * @return string containing encoded array
 */
function exportArrayToDB($TAB)
{
    $dbu = new DbUtils();
    return $dbu->exportArrayToDB($TAB);
}


/**
 * Import an array encoded in a simple field in the database
 *
 * @param string $DATA data readed in DB to import
 *
 * @return array containing datas
 */
function importArrayFromDB($DATA)
{
    $dbu = new DbUtils();
    return $dbu->importArrayFromDB($DATA);
}


/**
 * Get hour from sql
 *
 * @param string $time datetime time
 *
 * @return string
 *
 * @TODO This method is not used, deprecate it in GLPI 12.0.
 */
function get_hour_from_sql($time)
{
    $dbu = new DbUtils();
    return $dbu->getHourFromSql($time);
}


/**
 * Get the $RELATION array. It defines all relations between tables in the DB;
 * plugins may add their own stuff
 *
 * @return array<string, array<string, string|list<string|array{0: string, 1: string}>>>
 */
function getDbRelations()
{
    $dbu = new DbUtils();
    return $dbu->getDbRelations();
}


/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param string        $separator        separator in the begin of the request (default AND)
 * @param string        $table            table where apply the limit (if needed, multiple tables queries)
 * @param string        $field            field where apply the limit (id != entities_id)
 * @param int|int[]|''  $value            entity to restrict (if not set use $_SESSION['glpiactiveentities_string'])
 * @param bool          $is_recursive     need to use recursive process to find item
 *                                        (field need to be named recursive)
 * @param bool          $complete_request need to use a complete request and not a simple one
 *                                        when have acces to all entities (used for reminders)
 *
 * @return string the WHERE clause to restrict
 *
 * @TODO Deprecate this method in GLPI 12.0, usages should be replaced by `getEntitiesRestrictCriteria()`.
 */
function getEntitiesRestrictRequest(
    $separator = "AND",
    $table = "",
    $field = "",
    $value = '',
    $is_recursive = false,
    $complete_request = false
) {
    $dbu = new DbUtils();
    return $dbu->getEntitiesRestrictRequest(
        $separator,
        $table,
        $field,
        $value,
        $is_recursive,
        $complete_request
    );
}


/**
 * Get criteria to restrict to current entities of the user
 *
 * @since 9.2
 *
 * @param string        $table            table where apply the limit (if needed, multiple tables queries)
 * @param string        $field            field where apply the limit (id != entities_id)
 * @param int|int[]|''  $value            entity to restrict (if not set use $_SESSION['glpiactiveentities'])
 * @param bool|'auto'   $is_recursive     need to use recursive process to find item
 *                                        (field need to be named recursive) (false by default, set to 'auto' to automatic detection)
 * @param bool          $complete_request need to use a complete request and not a simple one
 *                                        when have acces to all entities (used for reminders)
 *
 * @return array<mixed, mixed>
 */
function getEntitiesRestrictCriteria(
    $table = '',
    $field = '',
    $value = '',
    $is_recursive = false,
    $complete_request = false
) {
    $dbu = new DbUtils();
    $res = $dbu->getEntitiesRestrictCriteria(
        $table,
        $field,
        $value,
        $is_recursive,
        $complete_request
    );

    // Add another layer to the array to prevent losing duplicates keys if the
    // result of the function is merged with another array
    if (count($res)) {
        $res = [crc32(serialize($res)) => $res];
    }
    return $res;
}
