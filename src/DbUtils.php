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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;

/**
 * Database utilities
 *
 * @since 9.2
 */
final class DbUtils
{
    /**
     * Return foreign key field name for a table
     *
     * @param string $table table name
     *
     * @return string field name used for a foreign key to the parameter table
     */
    public function getForeignKeyFieldForTable($table)
    {
        if (!str_starts_with($table, 'glpi_')) {
            return "";
        }
        return substr($table, 5) . "_id";
    }


    /**
     * Check if field is a foreign key field
     *
     * @param string $field field name
     *
     * @return boolean
     */
    public function isForeignKeyField($field)
    {
       //check empty, then strpos, then regexp; for performances
        return !empty($field) && strpos($field, '_id', 1) !== false && preg_match("/._id(_.+)?$/", $field);
    }


    /**
     * Return table name for a given foreign key name
     *
     * @param string $fkname foreign key name
     *
     * @return string table name corresponding to a foreign key name
     */
    public function getTableNameForForeignKeyField($fkname)
    {
        if (!$this->isForeignKeyField($fkname)) {
            return '';
        }

       // If $fkname begin with _ strip it
        if (str_starts_with($fkname, '_')) {
            $fkname = substr($fkname, 1);
        }

        return "glpi_" . preg_replace("/_id.*/", "", $fkname);
    }

    /**
     * Return the plural of a string
     *
     * @param string $string input string
     *
     * @return string plural of the parameter string
     */
    public function getPlural($string)
    {
        $rules = [
         //'singular'         => 'plural'
         // special case for acronym pdu (to avoid us rule)
            'pdus$'              => 'pdus',
            'pdu$'               => 'pdus',
         //FIXME: singular is criterion, plural is criteria
            'criterias$'         => 'criterias',// Special case (criterias) when getPlural is called on already plural form
            'ch$'                => 'ches',
            'ches$'              => 'ches',
            'sh$'                => 'shes',
            'shes$'              => 'shes',
            'sses$'              => 'sses', // Case like addresses
            'ss$'                => 'sses', // Special case (addresses) when getSingular is called on already singular form
            'uses$'              => 'uses', // Case like statuses
            'us$'                => 'uses', // Case like status
            '([^aeiou])y$'       => '\1ies', // special case : category (but not key)
            '([^aeiou])ies$'     => '\1ies', // special case : category (but not key)
            '([aeiou]{2})ses$'   => '\1ses', // Case like aliases
            '([aeiou]{2})s$'     => '\1ses', // Case like aliases
            'x$'                 => 'xes',
         // 's$'              =>'ses',
            '([^s])$'            => '\1s',   // Add at the end if not exists
        ];

        foreach ($rules as $singular => $plural) {
            $count = 0;
            $string = preg_replace("/$singular/", "$plural", $string, -1, $count);
            if ($count > 0) {
                break;
            }
        }
        return $string;
    }

    /**
     * Return the singular of a string
     *
     * @param string $string input string
     *
     * @return string singular of the parameter string
     */
    public function getSingular($string)
    {

        $rules = [
         //'plural'           => 'singular'
            'pdus$'              => 'pdu', // special case for acronym pdu (to avoid us rule)
            'Metrics$'           => 'Metrics',// Special case
            'metrics$'           => 'metrics',// Special case
            'ches$'              => 'ch',
            'ch$'                => 'ch',
            'shes$'              => 'sh',
            'sh$'                => 'sh',
            'sses$'              => 'ss', // Case like addresses
            'ss$'                => 'ss', // Special case (addresses) when getSingular is called on already singular form
            'uses$'              => 'us', // Case like statuses
            'us$'                => 'us', // Case like status
            '([aeiou]{2})ses$'   => '\1s', // Case like aliases
            'lias$'              => 'lias', // Special case (aliases) when getSingular is called on already singular form
            '([^aeiou])ies$'     => '\1y', // special case : category
            '([^aeiou])y$'       => '\1y', // special case : category
            'xes$'               => 'x',
            's$'                 => ''
        ]; // Add at the end if not exists

        foreach ($rules as $plural => $singular) {
            $count = 0;
            $string = preg_replace("/$plural/", "$singular", $string, -1, $count);
            if ($count > 0) {
                break;
            }
        }
        return $string;
    }


    /**
     * Return table name for an item type
     *
     * @param string $itemtype itemtype
     *
     * @return string table name corresponding to the itemtype  parameter
     */
    public function getTableForItemType($itemtype)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // Force singular for itemtype : States case
        $itemtype = $this->getSingular($itemtype);

        if (isset($CFG_GLPI['glpitablesitemtype'][$itemtype])) {
            return $CFG_GLPI['glpitablesitemtype'][$itemtype];
        } else {
            $prefix = "glpi_";

            if ($plug = isPluginItemType($itemtype)) {
               /* PluginFooBar   => glpi_plugin_foos_bars */
               /* GlpiPlugin\Foo\Bar => glpi_plugin_foos_bars */
                $prefix .= "plugin_" . strtolower($plug['plugin']) . "_";
                $table   = strtolower($plug['class']);
            } else {
                $table = strtolower($itemtype);
                if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
                    $table = substr($table, \strlen(NS_GLPI));
                }
            }
            //handle PHPUnit mocks
            if (str_starts_with($table, 'mock_')) {
                $table = preg_replace('/^mock_(.+)_.+$/', '$1', $table);
            }
            $table = str_replace(['mock\\', '\\'], ['', '_'], $table);
            if (strstr($table, '_')) {
                $split = explode('_', $table);

                foreach ($split as $key => $part) {
                    $split[$key] = $this->getPlural($part);
                }
                $table = implode('_', $split);
            } else {
                $table = $this->getPlural($table);
            }

            $CFG_GLPI['glpitablesitemtype'][$itemtype]      = $prefix . $table;
            $CFG_GLPI['glpiitemtypetables'][$prefix . $table] = $itemtype;
            return $prefix . $table;
        }
    }


    /**
     * Return ItemType  for a table
     *
     * @param string $table table name
     *
     * @return string itemtype corresponding to a table name parameter
     */
    public function getItemTypeForTable($table)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isset($CFG_GLPI['glpiitemtypetables'][$table])) {
            return $CFG_GLPI['glpiitemtypetables'][$table];
        } else {
            $inittable = $table;
            $table     = str_replace("glpi_", "", $table);
            $prefix    = "";
            $pref2     = NS_GLPI;
            $is_plugin = false;

            $matches = [];
            if (preg_match('/^plugin_([a-z0-9]+)_/', $table, $matches)) {
                $table  = preg_replace('/^plugin_[a-z0-9]+_/', '', $table);
                $prefix = "Plugin" . Toolbox::ucfirst($matches[1]);
                $pref2  = NS_PLUG . ucfirst($matches[1]) . '\\';
                $is_plugin = true;
            }

            if (strstr($table, '_')) {
                $split = explode('_', $table);

                foreach ($split as $key => $part) {
                    $split[$key] = Toolbox::ucfirst($this->getSingular($part));
                }
                $table = implode('_', $split);
            } else {
                $table = Toolbox::ucfirst($this->getSingular($table));
            }

            $base_itemtype = $this->fixItemtypeCase($prefix . $table);

            $itemtype = null;
            if (class_exists($base_itemtype)) {
                $class_file = (new ReflectionClass($base_itemtype))->getFileName();
                $is_glpi_class = $class_file !== false && (
                    str_starts_with(realpath($class_file), realpath(GLPI_ROOT))
                    || str_starts_with(realpath($class_file), realpath(GLPI_MARKETPLACE_DIR))
                    || str_starts_with(realpath($class_file), realpath(GLPI_PLUGIN_DOC_DIR))
                );
                if ($is_glpi_class) {
                    $itemtype = $base_itemtype;
                }
            }

            // Handle namespaces
            if ($itemtype === null) {
                $namespaced_itemtype = $this->fixItemtypeCase($pref2 . str_replace('_', '\\', $table));

                if (class_exists($namespaced_itemtype)) {
                    $itemtype = $namespaced_itemtype;
                } else {
                    // Handle namespace + db relation
                    // On the previous step we converted all '_' into '\'
                    // However some '_' must be kept in case of an item relation
                    // For example, with the `glpi_namespace1_namespace2_items_filters` table
                    // the expected itemtype is Glpi\Namespace1\Namespace2\Item_Filter
                    // NOT Glpi\Namespace1\Namespace2\Item\Filter
                    // To avoid this, we can revert the last '_' and check if the itemtype exists
                    $check_alternative = $is_plugin
                        ? substr_count($table, '_') > 1 // for plugin classes, always keep the first+second namespace levels (GlpiPlugin\\PluginName\\)
                        : substr_count($table, '_') > 0 // for GLPI classes, always keep the first namespace level (Glpi\\)
                    ;
                    if ($check_alternative) {
                        $last_backslash_position = strrpos($namespaced_itemtype, "\\");
                        // Replace last '\' into '_'
                        $alternative_namespaced_itemtype = substr_replace(
                            $namespaced_itemtype,
                            '_',
                            $last_backslash_position,
                            1
                        );
                        $alternative_namespaced_itemtype = $this->fixItemtypeCase($alternative_namespaced_itemtype);

                        if (class_exists($alternative_namespaced_itemtype)) {
                            $itemtype = $alternative_namespaced_itemtype;
                        }
                    }
                }
            }

            if ($itemtype !== null && $item = $this->getItemForItemtype($itemtype)) {
                $itemtype                                   = get_class($item);
                $CFG_GLPI['glpiitemtypetables'][$inittable] = $itemtype;
                $CFG_GLPI['glpitablesitemtype'][$itemtype]  = $inittable;
                return $itemtype;
            }

            return "UNKNOWN";
        }
    }

    /**
     * Try to fix itemtype case.
     * PSR-4 loading requires classnames to be used with their correct case.
     *
     * @param string $itemtype
     * @param string $root_dir
     *
     * @return string
     */
    public function fixItemtypeCase(string $itemtype, $root_dir = GLPI_ROOT)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        // If a class exists for this itemtype, just return the declared class name.
        $matches = preg_grep('/^' . preg_quote($itemtype, '/') . '$/i', get_declared_classes());
        if (count($matches) === 1) {
            return current($matches);
        }

        static $mapping = []; // Mappings already retrieved in current request
        static $already_scanned = []; // Directories already scanned directories in current request

        $context = 'glpi-core';
        $plugin_matches = [];
        if (preg_match('/^Plugin(?<plugin>[A-Z][a-z]+)(?<class>[A-Z][a-z]+)/', $itemtype, $plugin_matches)) {
           // Nota: plugin classes that does not use any namespace cannot be completely case insensitive
           // indeed, we must be able to separate plugin name (directory) from class name (file)
           // so pattern must be the one provided by getItemTypeForTable: PluginDirectorynameClassname
            $context = strtolower($plugin_matches['plugin']);
        } else if (preg_match('/^' . preg_quote(NS_PLUG, '/') . '(?<plugin>[a-z]+)\\\/i', $itemtype, $plugin_matches)) {
            $context = strtolower($plugin_matches['plugin']);
        }

        $namespace      = $context === 'glpi-core' ? NS_GLPI : NS_PLUG . ucfirst($context) . '\\';
        $uses_namespace = preg_match('/^(' . preg_quote($namespace, '/') . ')/i', $itemtype);

        $expected_lc_path = str_ireplace(
            [$namespace, '\\'],
            [''        , DIRECTORY_SEPARATOR],
            strtolower($itemtype) . '.php'
        );

        $cache_key = sprintf('itemtype-case-mapping-%s', $context);

        if (!array_key_exists($context, $mapping)) {
            // Initialize mapping from persistent cache if it has not been done yet in current request
            $mapping[$context] = $GLPI_CACHE->get($cache_key);
        }

        if ($mapping[$context] !== null && array_key_exists($expected_lc_path, $mapping[$context])) {
            // Return known value, if any
            return ($uses_namespace ? $namespace : '') . $mapping[$context][$expected_lc_path];
        }

        if (
            !defined('TU_USER')
            && (
                (
                    $mapping[$context] !== null
                    && ($_SESSION['glpi_use_mode'] ?? null) !== Session::DEBUG_MODE
                )
                || in_array($context, $already_scanned)
            )
        ) {
            // Do not scan class files if mapping was already cached, unless debug mode is used.
            //
            // It will prevent a scan on all files when method is used on an unexisting itemtype
            // which would never be present in cached mapping as it would have no matching file.
            // This case can happen when database contains obsolete data, or when a plugin calls
            // `getItemForItemtype()` for an invalid itemtype.
            //
            // Skip also files scan if it has already been done in current request.
            return $itemtype;
        }

        // Fetch filenames from "src" directory of context (GLPI core or given plugin).
        $mapping[$context] = [];
        $srcdir = $root_dir . ($context === 'glpi-core' ? '' : '/plugins/' . $context) . '/src';
        if (is_dir($srcdir)) {
            $files_iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcdir),
                RecursiveIteratorIterator::SELF_FIRST
            );
            /** @var SplFileInfo $file */
            foreach ($files_iterator as $file) {
                if (!$file->isReadable() || !$file->isFile() || '.php' === !$file->getExtension()) {
                    continue;
                }
                $relative_path = str_replace($srcdir . DIRECTORY_SEPARATOR, '', $file->getPathname());

                // Store entry into mapping:
                // - key is the lowercased filepath;
                // - value is the classname with correct case.
                $mapping[$context][strtolower($relative_path)] = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\',                ''],
                    $relative_path
                );
            }
        }

        $already_scanned[] = $context;

        $GLPI_CACHE->set($cache_key, $mapping[$context]);

        return array_key_exists($expected_lc_path, $mapping[$context])
            ? ($uses_namespace ? $namespace : '') . $mapping[$context][$expected_lc_path]
            : $itemtype;
    }


    /**
     * Get new item objet for an itemtype
     *
     * @param string $itemtype itemtype
     *
     * @return CommonDBTM|false itemtype instance or false if class does not exists
     * @template T
     * @phpstan-param class-string<T> $itemtype
     * @phpstan-return T|false
     */
    public function getItemForItemtype($itemtype)
    {
        if (empty($itemtype)) {
            return false;
        }

       // If itemtype starts with "Glpi\" or "GlpiPlugin\" followed by a "\",
       // then it is a namespaced itemtype that has been "sanitized".
       // Strip slashes to get its actual value.
        $sanitized_namespaced_pattern = '/^'
         . '(' . preg_quote(NS_GLPI, '/') . '|' . preg_quote(NS_PLUG, '/') . ')' // start with GLPI core or plugin namespace
         . preg_quote('\\', '/') // followed by an additionnal \
         . '/';
        if (preg_match($sanitized_namespaced_pattern, $itemtype)) {
            trigger_error(sprintf('Unexpected sanitized itemtype "%s" encountered.', $itemtype), E_USER_WARNING);
            $itemtype = stripslashes($itemtype);
        }

        $itemtype = $this->fixItemtypeCase($itemtype);

        if ($itemtype === 'Event') {
           //to avoid issues when pecl-event is installed...
            $itemtype = 'Glpi\\Event';
        }

        if (!is_subclass_of($itemtype, CommonGLPI::class, true)) {
           // Only CommonGLPI sublasses are valid itemtypes
            return false;
        }

        $item_class = new ReflectionClass($itemtype);
        if ($item_class->isAbstract()) {
            trigger_error(
                sprintf('Cannot instanciate "%s" as it is an abstract class.', $itemtype),
                E_USER_WARNING
            );
            return false;
        }

        return new $itemtype();
    }

    /**
     * Count the number of elements in a table.
     *
     * @param string|array   $table     table name(s)
     * @param string|array   $condition array of criteria
     *
     * @return integer Number of elements in table
     */
    public function countElementsInTable($table, $condition = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!is_array($table)) {
            $table = [$table];
        }

       /*foreach ($table as $t) {
         if (!$DB->tableExists($table)) {
            throw new \RuntimeException("$t is not an existing table!");
         }
       }*/

        if (!is_array($condition)) {
            Toolbox::Deprecated('Condition must be an array!');
            if (empty($condition)) {
                $condition = [];
            }
        }
        $condition['COUNT'] = 'cpt';

        $row = $DB->request($table, $condition)->current();
        return ($row ? (int)$row['cpt'] : 0);
    }

    /**
     * Count the number of elements in a table.
     *
     * @param string|array   $table     table name(s)
     * @param string         $field     field name
     * @param array|string|null          $condition array of criteria
     *
     * @return int nb of elements in table
     */
    public function countDistinctElementsInTable($table, $field, $condition = [])
    {

        if (!is_array($condition)) {
            Toolbox::Deprecated('Condition must be an array!');
            if (empty($condition)) {
                $condition = [];
            }
        }
        $condition['COUNT'] = 'cpt';
        $condition['FIELDS'] = $field;
        $condition['DISTINCT'] = true;

        return $this->countElementsInTable($table, $condition);
    }

    /**
     * Count the number of elements in a table for a specific entity
     *
     * @param string|array $table     table name(s)
     * @param array        $condition array of criteria
     *
     * @return integer Number of elements in table
     */
    public function countElementsInTableForMyEntities($table, $condition = [])
    {

       /// TODO clean it / maybe include when review of SQL requests
        $itemtype = $this->getItemTypeForTable($table);
        $item     = new $itemtype();

        $criteria = $this->getEntitiesRestrictCriteria($table, '', '', $item->maybeRecursive());
        $criteria = array_merge($condition, $criteria);
        return $this->countElementsInTable($table, $criteria);
    }


    /**
     * Count the number of elements in a table for a specific entity
     *
     * @param string|array $table     table name(s)
     * @param integer      $entity    the entity ID
     * @param array        $condition condition to use (default '') or array of criteria
     * @param boolean      $recursive Whether to recurse or not. If true, will be conditionned on item recursivity
     *
     * @return integer number of elements in table
     */
    public function countElementsInTableForEntity($table, $entity, $condition = [], $recursive = true)
    {

       /// TODO clean it / maybe include when review of SQL requests
        $itemtype = $this->getItemTypeForTable($table);
        $item     = new $itemtype();

        if ($recursive) {
            $recursive = $item->maybeRecursive();
        }

        $criteria = $this->getEntitiesRestrictCriteria($table, '', $entity, $recursive);
        $criteria = array_merge($condition, $criteria);
        return $this->countElementsInTable($table, $criteria);
    }

    /**
     * Get data from a table in an array :
     * CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
     *
     * @param string         $table    Table name
     * @param array|string|null          $criteria Request criteria
     * @param boolean        $usecache Use cache (false by default)
     * @param string         $order    Result order (default '')
     *
     * @return array containing all the datas
     */
    public function getAllDataFromTable($table, $criteria = [], $usecache = false, $order = '')
    {
        /** @var \DBmysql $DB */
        global $DB;

        static $cache = [];

        if (empty($criteria) && empty($order) && $usecache && isset($cache[$table])) {
            return $cache[$table];
        }

        $data = [];

        if (!is_array($criteria)) {
            Toolbox::Deprecated('Criteria must be an array!');
            if (empty($criteria)) {
                $criteria = [];
            }
        }

        if (!empty($order)) {
            Toolbox::Deprecated('Order should be defined in criteria!');
            $criteria['ORDER'] = $order; // Deprecated use case
        }

        $iterator = $DB->request($table, $criteria);

        foreach ($iterator as $row) {
            $data[$row['id']] = $row;
        }

        if (empty($criteria) && empty($order) && $usecache) {
            $cache[$table] = $data;
        }
        return $data;
    }

    /**
     * Determine if an index exists in database
     *
     * @param string $table table of the index
     * @param string $field name of the index
     *
     * @return boolean
     */
    public function isIndex($table, $field)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!$DB->tableExists($table)) {
            trigger_error("Table $table does not exists", E_USER_WARNING);
            return false;
        }

        $result = $DB->doQuery("SHOW INDEX FROM `$table`");

        if ($result && $DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
                if ($data["Key_name"] == $field) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if a foreign key exists in database
     *
     * @param string $table
     * @param string $keyname
     *
     * @return boolean
     */
    public function isForeignKeyContraint($table, $keyname)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $query = [
            'FROM'   => 'information_schema.key_column_usage',
            'WHERE'  => [
                'constraint_schema' => $DB->dbdefault,
                'table_name'        => $table,
                'constraint_name'   => $keyname,
            ]
        ];

        $iterator = $DB->request($query);

        return $iterator->count() > 0;
    }

    /**
     * Get SQL request to restrict to current entities of the user
     *
     * @param string  $separator        separator in the begin of the request (default AND)
     * @param string  $table            table where apply the limit (if needed, multiple tables queries)
     *                                  (default '')
     * @param string  $field            field where apply the limit (id != entities_id) (default '')
     * @param mixed   $value            entity to restrict (if not set use $_SESSION['glpiactiveentities_string']).
     *                                  single item or array (default '')
     * @param boolean $is_recursive     need to use recursive process to find item
     *                                  (field need to be named recursive) (false by default)
     * @param boolean $complete_request need to use a complete request and not a simple one
     *                                  when have acces to all entities (used for reminders)
     *                                  (false by default)
     *
     * @return string the WHERE clause to restrict
     */
    public function getEntitiesRestrictRequest(
        $separator = "AND",
        $table = "",
        $field = "",
        $value = '',
        $is_recursive = false,
        $complete_request = false
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        $query = $separator . " ( ";

       // !='0' needed because consider as empty
        if (
            !$complete_request
            && ($value != '0')
            && empty($value)
            && isset($_SESSION['glpishowallentities'])
            && $_SESSION['glpishowallentities']
        ) {
           // Not ADD "AND 1" if not needed
            if (trim($separator) == "AND") {
                return "";
            }
            return $query . " 1 ) ";
        }

        if (empty($field)) {
            if ($table == 'glpi_entities') {
                $field = "id";
            } else {
                $field = "entities_id";
            }
        }
        if (empty($table)) {
            $field = $DB->quoteName($field);
        } else {
            $field = $DB->quoteName("$table.$field");
        }

        $query .= "$field";

        if (is_array($value)) {
            $query .= " IN ('" . implode("','", $value) . "') ";
        } else {
            if (strlen($value) == 0 && !isset($_SESSION['glpiactiveentities_string'])) {
               //set root entity if not set
                $value = 0;
            }
            if (strlen($value) == 0) {
                $query .= " IN (" . $_SESSION['glpiactiveentities_string'] . ") ";
            } else {
                $query .= " = '$value' ";
            }
        }

        if ($is_recursive) {
            $ancestors = [];
            if (
                isset($_SESSION['glpiactiveentities'])
                && isset($_SESSION['glpiparententities'])
                && $value == $_SESSION['glpiactiveentities']
            ) {
                $ancestors = $_SESSION['glpiparententities'];
            } else {
                if (is_array($value)) {
                    $ancestors = $this->getAncestorsOf("glpi_entities", $value);
                    $ancestors = array_diff($ancestors, $value);
                } else if (strlen($value) == 0 && isset($_SESSION['glpiparententities'])) {
                    $ancestors = $_SESSION['glpiparententities'];
                } else {
                    $ancestors = $this->getAncestorsOf("glpi_entities", $value);
                }
            }

            if (count($ancestors)) {
                if ($table == 'glpi_entities') {
                    $query .= " OR $field IN ('" . implode("','", $ancestors) . "')";
                } else {
                    $recur = $DB->quoteName((empty($table) ? 'is_recursive' : "$table.is_recursive"));
                    $query .= " OR ($recur='1' AND $field IN (" . implode(', ', $ancestors) . '))';
                }
            }
        }
        $query .= " ) ";

        return $query;
    }

    /**
     * Get criteria to restrict to current entities of the user
     *
     * @since 9.2
     *
     * @param string $table             table where apply the limit (if needed, multiple tables queries)
     *                                  (default '')
     * @param string $field             field where apply the limit (id != entities_id) (default '')
     * @param mixed $value              entity to restrict (if not set use $_SESSION['glpiactiveentities']).
     *                                  single item or array (default '')
     * @param boolean $is_recursive     need to use recursive process to find item
     *                                  (field need to be named recursive) (false by default, set to auto to automatic detection)
     * @param boolean $complete_request need to use a complete request and not a simple one
     *                                  when have acces to all entities (used for reminders)
     *                                  (false by default)
     *
     * @return array of criteria
     */
    public function getEntitiesRestrictCriteria(
        $table = '',
        $field = '',
        $value = '',
        $is_recursive = false,
        $complete_request = false
    ) {

       // !='0' needed because consider as empty
        if (
            !$complete_request
            && ($value != '0')
            && empty($value)
            && isset($_SESSION['glpishowallentities'])
            && $_SESSION['glpishowallentities']
        ) {
            return [];
        }

        if (empty($field)) {
            if ($table == 'glpi_entities') {
                $field = "id";
            } else {
                $field = "entities_id";
            }
        }
        if (!empty($table)) {
            $field = "$table.$field";
        }

        if (!is_array($value) && strlen($value) == 0) {
            if (isset($_SESSION['glpiactiveentities'])) {
                $value = $_SESSION['glpiactiveentities'];
            } else if (isCommandLine() || Session::isCron()) {
                $value = '0'; // If value is not set, fallback to root entity in cron / command line
            }
        }

        $crit = [$field => $value];

        if ($is_recursive === 'auto' && !empty($table) && $table != 'glpi_entities') {
            $item = $this->getItemForItemtype($this->getItemTypeForTable($table));
            if ($item !== false) {
                $is_recursive = $item->maybeRecursive();
            }
        }

        if ($is_recursive) {
            $ancestors = [];
            if (is_array($value)) {
                $ancestors = $this->getAncestorsOf("glpi_entities", $value);
                $ancestors = array_diff($ancestors, $value);
            } else if (strlen($value) == 0) {
                $ancestors = $_SESSION['glpiparententities'] ?? [];
            } else {
                $ancestors = $this->getAncestorsOf('glpi_entities', $value);
            }

            if (count($ancestors)) {
                if ($table == 'glpi_entities') {
                    if (!is_array($value)) {
                        $value = [$value => $value];
                    }
                    $crit = ['OR' => [$field => $value + $ancestors]];
                } else {
                    $recur = (empty($table) ? 'is_recursive' : "$table.is_recursive");
                    $crit = [
                        'OR' => [
                            $field => $value,
                            [$recur => 1, $field => $ancestors]
                        ]
                    ];
                }
            }
        }
        return $crit;
    }

    /**
     * Get the sons of an item in a tree dropdown. Get datas in cache if available
     *
     * @param string  $table table name
     * @param integer $IDf   The ID of the father
     *
     * @return array of IDs of the sons
     */
    public function getSonsOf($table, $IDf)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $ckey = 'sons_cache_' . $table . '_' . $IDf;

        $sons = $GLPI_CACHE->get($ckey);
        if ($sons !== null) {
            return $sons;
        }

        $parentIDfield = $this->getForeignKeyFieldForTable($table);
        $use_cache     = $DB->fieldExists($table, "sons_cache");

        if (
            $use_cache
            && ($IDf > 0)
        ) {
            $iterator = $DB->request([
                'SELECT' => 'sons_cache',
                'FROM'   => $table,
                'WHERE'  => ['id' => $IDf]
            ]);

            if (count($iterator) > 0) {
                $db_sons = $iterator->current()['sons_cache'];
                $db_sons = $db_sons !== null ? trim($db_sons) : null;
                if (!empty($db_sons)) {
                    $sons = $this->importArrayFromDB($db_sons);
                }
            }
        }

        if (!is_array($sons)) {
           // IDs to be present in the final array
            $sons = [
                $IDf => $IDf,
            ];
           // current ID found to be added
            $found = [];
           // First request init the  varriables
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $table,
                'WHERE'  => [$parentIDfield => $IDf],
                'ORDER'  => 'name'
            ]);

            if (count($iterator) > 0) {
                foreach ($iterator as $row) {
                    $sons[$row['id']]    = $row['id'];
                    $found[$row['id']]   = $row['id'];
                }
            }

           // Get the leafs of previous found item
            while (count($found) > 0) {
               // Get next elements
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => $table,
                    'WHERE'  => [$parentIDfield => $found]
                ]);

               // CLear the found array
                unset($found);
                $found = [];

                if (count($iterator) > 0) {
                    foreach ($iterator as $row) {
                        if (!isset($sons[$row['id']])) {
                            $sons[$row['id']]    = $row['id'];
                            $found[$row['id']]   = $row['id'];
                        }
                    }
                }
            }

           // Store cache data in DB
            if (
                $use_cache
                && ($IDf > 0)
            ) {
                $DB->update(
                    $table,
                    [
                        'sons_cache' => $this->exportArrayToDB($sons)
                    ],
                    [
                        'id' => $IDf
                    ]
                );
            }
        }

        $GLPI_CACHE->set($ckey, $sons);

        return $sons;
    }

    /**
     * Get the ancestors of an item in a tree dropdown
     *
     * @param string       $table    Table name
     * @param array|string $items_id The IDs of the items
     *
     * @return array of IDs of the ancestors
     */
    public function getAncestorsOf($table, $items_id)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        if ($items_id === null) {
            return [];
        }
        $ckey = 'ancestors_cache_';
        if (is_array($items_id)) {
            $ckey .= $table . '_' . md5(implode('|', $items_id));
        } else {
            $ckey .= $table . '_' . $items_id;
        }

        $ancestors = $GLPI_CACHE->get($ckey);
        if ($ancestors !== null) {
            return $ancestors;
        }

        $ancestors = [];

       // IDs to be present in the final array
        $parentIDfield = $this->getForeignKeyFieldForTable($table);
        $use_cache     = $DB->fieldExists($table, "ancestors_cache");

        if (!is_array($items_id)) {
            $items_id = (array)$items_id;
        }

        if ($use_cache) {
            $iterator = $DB->request([
                'SELECT' => ['id', 'ancestors_cache', $parentIDfield],
                'FROM'   => $table,
                'WHERE'  => ['id' => $items_id]
            ]);

            foreach ($iterator as $row) {
                if ($row['id'] > 0) {
                    $rancestors = $row['ancestors_cache'];
                    $parent     = $row[$parentIDfield];

                  // Return datas from cache in DB
                    if (!empty($rancestors)) {
                        $ancestors = array_replace($ancestors, $this->importArrayFromDB($rancestors));
                    } else {
                        $loc_id_found = [];
                     // Recursive solution for table with-cache
                        if ($parent > 0) {
                              $loc_id_found = $this->getAncestorsOf($table, $parent);
                        }

                     // ID=0 only exists for Entities
                        if (
                            ($parent > 0)
                            || ($table == 'glpi_entities')
                        ) {
                            $loc_id_found[$parent] = $parent;
                        }

                     // Store cache datas in DB
                        $DB->update(
                            $table,
                            [
                                'ancestors_cache' => $this->exportArrayToDB($loc_id_found)
                            ],
                            [
                                'id' => $row['id']
                            ]
                        );

                        $ancestors = array_replace($ancestors, $loc_id_found);
                    }
                }
            }
        } else {
           // Get the ancestors
           // iterative solution for table without cache
            foreach ($items_id as $id) {
                $IDf = $id;
                while ($IDf > 0) {
                    // Get next elements
                    $iterator = $DB->request([
                        'SELECT' => [$parentIDfield],
                        'FROM'   => $table,
                        'WHERE'  => ['id' => $IDf]
                    ]);

                    if (count($iterator) > 0) {
                        $result = $iterator->current();
                        $IDf = $result[$parentIDfield];
                    } else {
                        $IDf = 0;
                    }

                    if (
                        !isset($ancestors[$IDf])
                         && (($IDf > 0) || ($table == 'glpi_entities'))
                    ) {
                        $ancestors[$IDf] = $IDf;
                    } else {
                        $IDf = 0;
                    }
                }
            }
        }

        $GLPI_CACHE->set($ckey, $ancestors);

        return $ancestors;
    }

    /**
     * Get the sons and the ancestors of an item in a tree dropdown. Rely on getSonsOf and getAncestorsOf
     *
     * @since 0.84
     *
     * @param string $table table name
     * @param string $IDf   The ID of the father
     *
     * @return array of IDs of the sons and the ancestors
     */
    public function getSonsAndAncestorsOf($table, $IDf)
    {
        return $this->getAncestorsOf($table, $IDf) + $this->getSonsOf($table, $IDf);
    }

    /**
     * Get the Name of the element of a Dropdown Tree table
     *
     * @param string  $table       Dropdown Tree table
     * @param integer $ID          ID of the element
     * @param boolean $withcomment 1 if you want to give the array with the comments (false by default)
     * @param boolean $translate   (true by default)
     *
     * @return string name of the element
     *
     * @see DbUtils::getTreeValueCompleteName
     */
    public function getTreeLeafValueName($table, $ID, $withcomment = false, $translate = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $name    = "";
        $comment = "";

        $SELECTNAME    = new \QueryExpression("'' AS " . $DB->quoteName('transname'));
        $SELECTCOMMENT = new \QueryExpression("'' AS " . $DB->quoteName('transcomment'));
        $JOIN          = [];
        $JOINS         = [];
        if ($translate) {
            if (Session::haveTranslations($this->getItemTypeForTable($table), 'name')) {
                $SELECTNAME = 'namet.value AS transname';
                $JOINS['glpi_dropdowntranslations AS namet'] = [
                    'ON' => [
                        'namet'  => 'items_id',
                        $table   => 'id', [
                            'AND' => [
                                'namet.itemtype'  => $this->getItemTypeForTable($table),
                                'namet.language'  => $_SESSION['glpilanguage'],
                                'namet.field'     => 'name'
                            ]
                        ]
                    ]
                ];
            }
            if (Session::haveTranslations($this->getItemTypeForTable($table), 'comment')) {
                $SELECTCOMMENT = 'namec.value AS transcomment';
                $JOINS['glpi_dropdowntranslations AS namec'] = [
                    'ON' => [
                        'namec'  => 'items_id',
                        $table   => 'id', [
                            'AND' => [
                                'namec.itemtype'  => $this->getItemTypeForTable($table),
                                'namec.language'  => $_SESSION['glpilanguage'],
                                'namec.field'     => 'comment'
                            ]
                        ]
                    ]
                ];
            }

            if (count($JOINS)) {
                $JOIN = ['LEFT JOIN' => $JOINS];
            }
        }

        $criteria = [
            'SELECT' => [
                "$table.name",
                "$table.comment",
                $SELECTNAME,
                $SELECTCOMMENT
            ],
            'FROM'   => $table,
            'WHERE'  => ["$table.id" => $ID]
        ] + $JOIN;
        $iterator = $DB->request($criteria);
        $result = $iterator->current();

        if (count($iterator) == 1) {
            $transname = $result['transname'];
            if ($translate && !empty($transname)) {
                $name = $transname;
            } else {
                $name = $result['name'];
            }

            $comment      = $name . " :<br/>";
            $transcomment = $result['transcomment'];

            if ($translate && !empty($transcomment)) {
                $comment .= nl2br($transcomment);
            } else if (!empty($result['comment'])) {
                $comment .= nl2br($result['comment']);
            }
        }

        if ($withcomment) {
            return [
                'name'      => $name,
                'comment'   => $comment
            ];
        }
        return $name;
    }

    /**
     * Get completename of a Dropdown Tree table
     *
     * @param string  $table       Dropdown Tree table
     * @param integer $ID          ID of the element
     * @param boolean $withcomment 1 if you want to give the array with the comments (false by default)
     * @param boolean $translate   (true by default)
     * @param boolean $tooltip     (true by default) returns a tooltip, else returns only 'comment'
     * @param string  $default     default value returned when item not exists
     *
     * @return string completename of the element
     *
     * @see DbUtils::getTreeLeafValueName
     */
    public function getTreeValueCompleteName($table, $ID, $withcomment = false, $translate = true, $tooltip = true, string $default = '&nbsp;')
    {
        /** @var \DBmysql $DB */
        global $DB;

        $name    = "";
        $comment = "";

        $SELECTNAME    = new \QueryExpression("'' AS " . $DB->quoteName('transname'));
        $SELECTCOMMENT = new \QueryExpression("'' AS " . $DB->quoteName('transcomment'));
        $JOIN          = [];
        $JOINS         = [];
        if ($translate) {
            if (Session::haveTranslations($this->getItemTypeForTable($table), 'completename')) {
                $SELECTNAME = 'namet.value AS transname';
                $JOINS['glpi_dropdowntranslations AS namet'] = [
                    'ON' => [
                        'namet'  => 'items_id',
                        $table   => 'id', [
                            'AND' => [
                                'namet.itemtype'  => $this->getItemTypeForTable($table),
                                'namet.language'  => $_SESSION['glpilanguage'],
                                'namet.field'     => 'completename'
                            ]
                        ]
                    ]
                ];
            }
            if (Session::haveTranslations($this->getItemTypeForTable($table), 'comment')) {
                $SELECTCOMMENT = 'namec.value AS transcomment';
                $JOINS['glpi_dropdowntranslations AS namec'] = [
                    'ON' => [
                        'namec'  => 'items_id',
                        $table   => 'id', [
                            'AND' => [
                                'namec.itemtype'  => $this->getItemTypeForTable($table),
                                'namec.language'  => $_SESSION['glpilanguage'],
                                'namec.field'     => 'comment'
                            ]
                        ]
                    ]
                ];
            }

            if (count($JOINS)) {
                $JOIN = ['LEFT JOIN' => $JOINS];
            }
        }

        $criteria = [
            'SELECT' => [
                "$table.completename",
                "$table.comment",
                $SELECTNAME,
                $SELECTCOMMENT
            ],
            'FROM'   => $table,
            'WHERE'  => ["$table.id" => $ID]
        ] + $JOIN;

        if ($table == Location::getTable()) {
            $criteria['SELECT'] = array_merge(
                $criteria['SELECT'],
                [
                    "$table.address",
                    "$table.town",
                    "$table.country"
                ]
            );
        }

        $iterator = $DB->request($criteria);
        $result = $iterator->current();

        if (count($iterator) == 1) {
            $transname = $result['transname'];
            if ($translate && !empty($transname)) {
                $name = $transname;
            } else {
                $name = $result['completename'];
            }

            $name = CommonTreeDropdown::sanitizeSeparatorInCompletename($name);

            if ($tooltip) {
                $comment  = sprintf(
                    __('%1$s: %2$s') . "<br>",
                    "<span class='b'>" . __('Complete name') . "</span>",
                    $name
                );
                if ($table == Location::getTable()) {
                     $acomment = '';
                     $address = $result['address'];
                     $town    = $result['town'];
                     $country = $result['country'];
                    if (!empty($address)) {
                        $acomment .= $address;
                    }
                    if (
                        !empty($address) &&
                        (!empty($town) || !empty($country))
                    ) {
                        $acomment .= '<br/>';
                    }
                    if (!empty($town)) {
                        $acomment .= $town;
                    }
                    if (!empty($country)) {
                        if (!empty($town)) {
                            $acomment .= ' - ';
                        }
                        $acomment .= $country;
                    }
                    if (trim($acomment) != '') {
                        $comment .= "<span class='b'>&nbsp;" . __('Address:') . "</span> " . $acomment . "<br/>";
                    }
                }
                $comment .= "<span class='b'>&nbsp;" . __('Comments') . "&nbsp;</span>";
            }
            $transcomment = $result['transcomment'];
            if ($translate && !empty($transcomment)) {
                $comment .= nl2br($transcomment);
            } else if (!empty($result['comment'])) {
                $comment .= nl2br($result['comment']);
            }
        }

        if (empty($name)) {
            $name = $default;
        }

        if ($withcomment) {
            return [
                'name'      => $name,
                'comment'   => $comment
            ];
        }
        return $name;
    }


    /**
     * show name category
     * DO NOT DELETE THIS FUNCTION : USED IN THE UPDATE
     *
     * @param string  $table     table name
     * @param integer $ID        integer  value ID
     * @param string  $wholename current name to complete (use for recursivity) (default '')
     * @param integer $level     current level of recursion (default 0)
     *
     * @return string name
     */
    public function getTreeValueName($table, $ID, $wholename = "", $level = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $parentIDfield = $this->getForeignKeyFieldForTable($table);

        $iterator = $DB->request([
            'SELECT' => ['name', $parentIDfield],
            'FROM'   => $table,
            'WHERE'  => ['id' => $ID]
        ]);
        $name = "";

        if (count($iterator) > 0) {
            $row      = $iterator->current();
            $parentID = $row[$parentIDfield];

            if ($wholename == "") {
                $name = $row["name"];
            } else {
                $name = $row["name"] . " > ";
            }

            $level++;
            list($tmpname, $level)  = $this->getTreeValueName($table, $parentID, $name, $level);
            $name                   = $tmpname . $name;
        }
        return [$name, $level];
    }

    /**
     * Get the sons of an item in a tree dropdown
     *
     * @param string  $table table name
     * @param integer $IDf   The ID of the father
     *
     * @return array of IDs of the sons
     */
    public function getTreeForItem($table, $IDf)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $parentIDfield = $this->getForeignKeyFieldForTable($table);

       // IDs to be present in the final array
        $id_found = [];
       // current ID found to be added
        $found = [];

       // First request init the  variables
        $iterator = $DB->request([
            'FROM'   => $table,
            'WHERE'  => [$parentIDfield => $IDf],
            'ORDER'  => 'name'
        ]);

        foreach ($iterator as $row) {
            $id_found[$row['id']]['parent'] = $IDf;
            $id_found[$row['id']]['name']   = $row['name'];
            $found[$row['id']]              = $row['id'];
        }

       // Get the leafs of previous founded item
        while (count($found) > 0) {
           // Get next elements
            $iterator = $DB->request([
                'FROM'   => $table,
                'WHERE'  => [$parentIDfield => $found],
                'ORDER'  => 'name'
            ]);

           // CLear the found array
            unset($found);
            $found = [];

            foreach ($iterator as $row) {
                if (!isset($id_found[$row['id']])) {
                    $id_found[$row['id']]['parent'] = $row[$parentIDfield];
                    $id_found[$row['id']]['name']   = $row['name'];
                    $found[$row['id']]              = $row['id'];
                }
            }
        }
        $tree = [
            $IDf => [
                'name' => Dropdown::getDropdownName($table, $IDf),
                'tree' => $this->constructTreeFromList($id_found, $IDf),
            ],
        ];
        return $tree;
    }

    /**
     * Construct a tree from a list structure
     *
     * @param array   $list the list
     * @param integer $root root of the tree
     *
     * @return array list of items in the tree
     */
    public function constructTreeFromList($list, $root)
    {

        $tree = [];
        foreach ($list as $ID => $data) {
            if ($data['parent'] == $root) {
                unset($list[$ID]);
                $tree[$ID]['name'] = $data['name'];
                $tree[$ID]['tree'] = $this->constructTreeFromList($list, $ID);
            }
        }
        return $tree;
    }

    /**
     * Construct a list from a tree structure
     *
     * @param array   $tree   the tree
     * @param integer $parent root of the tree (default =0)
     *
     * @return array list of items in the tree
     */
    public function constructListFromTree($tree, $parent = 0)
    {
        $list = [];
        foreach ($tree as $root => $data) {
            $list[$root] = $parent;

            if (is_array($data['tree']) && count($data['tree'])) {
                foreach ($data['tree'] as $ID => $underdata) {
                    $list[$ID] = $root;

                    if (is_array($underdata['tree']) && count($underdata['tree'])) {
                        $list += $this->constructListFromTree($underdata['tree'], $ID);
                    }
                }
            }
        }
        return $list;
    }


    /**
     * Compute all completenames of Dropdown Tree table
     *
     * @param string $table dropdown tree table to compute
     *
     * @return void
     **/
    public function regenerateTreeCompleteName($table)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $table
        ]);

        foreach ($iterator as $data) {
            list($name, $level) = $this->getTreeValueName($table, $data['id']);
            $DB->update(
                $table,
                [
                    'completename' => addslashes($name),
                    'level'        => $level
                ],
                [
                    'id' => $data['id']
                ]
            );
        }
    }


    /**
     * Format a user name
     *
     * @param integer $ID           ID of the user.
     * @param string|null  $login        login of the user
     * @param string|null  $realname     realname of the user
     * @param string|null  $firstname    firstname of the user
     * @param integer $link         include link (only if $link==1) (default =0)
     * @param integer $cut          limit string length (0 = no limit) (default =0)
     * @param boolean $force_config force order and id_visible to use common config (false by default)
     *
     * @return string formatted username
     */
    public function formatUserName($ID, $login, $realname, $firstname, $link = 1, $cut = 0, $force_config = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $before = "";
        $after  = "";

        $order = isset($CFG_GLPI["names_format"]) ? $CFG_GLPI["names_format"] : User::REALNAME_BEFORE;
        if (isset($_SESSION["glpinames_format"]) && !$force_config) {
            $order = $_SESSION["glpinames_format"];
        }

        $id_visible = isset($CFG_GLPI["is_ids_visible"]) ? $CFG_GLPI["is_ids_visible"] : 0;
        if (isset($_SESSION["glpiis_ids_visible"]) && !$force_config) {
            $id_visible = $_SESSION["glpiis_ids_visible"];
        }

        if (strlen($realname ?? '') > 0) {
            $formatted = $realname;

            if (strlen($firstname ?? '') > 0) {
                if ($order == User::FIRSTNAME_BEFORE) {
                    $formatted = $firstname . " " . $formatted;
                } else {
                    $formatted .= " " . $firstname;
                }
            }

            if (
                ($cut > 0)
                && (Toolbox::strlen($formatted) > $cut)
            ) {
                $formatted = Toolbox::substr($formatted, 0, $cut) . " ...";
            }
        } else {
            $formatted = $login ?? '';
        }

        if (
            $ID > 0
            && ((strlen($formatted) == 0) || $id_visible)
        ) {
            $formatted = sprintf(__('%1$s (%2$s)'), $formatted, $ID);
        }

        if (
            ($link == 1)
            && ($ID > 0)
        ) {
            $before = "<a title=\"" . htmlspecialchars($formatted) . "\"
                       href='" . User::getFormURLWithID($ID) . "'>";
            $after  = "</a>";
        }

        $username = $before . $formatted . $after;
        return $username;
    }


    /**
     * Get name of the user with ID=$ID (optional with link to user.form.php)
     *
     * @param integer|string $ID   ID of the user.
     * @param integer $link 1 = Show link to user.form.php 2 = return array with comments and link
     *                      (default =0)
     * @param $disable_anon   bool  disable anonymization of username.
     *
     * @return string[]|string username string (realname if not empty and name if realname is empty).
     */
    public function getUserName($ID, $link = 0, $disable_anon = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $user = "";
        if ($link == 2) {
            $user = ["name"    => "",
                "link"    => "",
                "comment" => ""
            ];
        }

        if ($ID === 'myself') {
            $name = __('Myself');
            if (isset($user['name'])) {
                $user['name'] = $name;
            } else {
                $user = $name;
            }
        } else if ($ID) {
            $iterator = $DB->request(
                'glpi_users',
                [
                    'WHERE' => ['id' => $ID]
                ]
            );

            if ($link == 2) {
                $user = ["name"    => "",
                    "comment" => "",
                    "link"    => ""
                ];
            }

            if (count($iterator) == 1) {
                $data     = $iterator->current();

                $anon_name = !$disable_anon && $ID != ($_SESSION['glpiID'] ?? 0) && Session::getCurrentInterface() == 'helpdesk' ? User::getAnonymizedNameForUser($ID) : null;
                if ($anon_name !== null) {
                    $username = $anon_name;
                } else {
                    $username = $this->formatUserName(
                        $data["id"],
                        $data["name"],
                        $data["realname"],
                        $data["firstname"],
                        $link
                    );
                }

                if ($link == 2) {
                    $user["name"]    = $username;
                    $user["link"]    = User::getFormURLWithID($ID);
                    $user['comment'] = '';

                    $user_params = [
                        'id'                 => $ID,
                        'user_name'          => $username,
                    ];

                    if ($anon_name === null) {
                        $user_params = array_merge($user_params, [
                            'email'              => UserEmail::getDefaultForUser($ID),
                            'phone'              => $data["phone"],
                            'phone2'             => $data["phone2"],
                            'mobile'             => $data["mobile"],
                            'locations_id'       => $data['locations_id'],
                            'usertitles_id'      => $data['usertitles_id'],
                            'usercategories_id'  => $data['usercategories_id'],
                        ]);

                        if (Session::haveRight('user', READ)) {
                             $user_params['login'] = $data['name'];
                        }
                        if (!empty($data["groups_id"])) {
                            $user_params['groups_id'] = $data["groups_id"];
                        }
                        $user['comment'] = TemplateRenderer::getInstance()->render('components/user/info_card.html.twig', [
                            'user'                 => $user_params,
                            'enable_anonymization' => Session::getCurrentInterface() == 'helpdesk',
                        ]);
                    }
                } else {
                    $user = $username;
                }
            }
        }
        return $user;
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
    public function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id = -1)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (!$isTemplate) {
            return $objectName;
        }

        $base_name = $objectName;

        $objectName = Sanitizer::decodeHtmlSpecialChars($objectName);
        $was_sanitized = $objectName !== $base_name;
        if ($was_sanitized) {
            Toolbox::deprecated('Handling of encoded/escaped value in autoName() is deprecated.');
        }

        $matches = [];
        if (preg_match('/^<[^#]*(#{1,10})[^#]*>$/', $objectName, $matches) !== 1) {
            return $base_name;
        }

        $autoNum = Toolbox::substr($objectName, 1, Toolbox::strlen($objectName) - 2);
        $mask    = $matches[1];
        $global  = ((strpos($autoNum, '\\g') !== false) && ($itemtype != 'Infocom')) ? 1 : 0;

       //do not add extra escapements for now
       //substring position would be wrong if name contains "_"
        $autoNum = str_replace(
            [
                '\\y',
                '\\Y',
                '\\m',
                '\\d',
                '\\g'
            ],
            [
                date('y'),
                date('Y'),
                date('m'),
                date('d'),
                ''
            ],
            $autoNum
        );

        $pos  = strpos($autoNum, $mask) + 1;

       //got substring position, add extra escapements
        $autoNum = str_replace(
            ['_', '%'],
            ['\\_', '\\%'],
            $autoNum
        );
        $len  = Toolbox::strlen($mask);
        $like = str_replace('#', '_', $autoNum);

        if ($global == 1) {
            $types = [
                'Computer',
                'Monitor',
                'NetworkEquipment',
                'Peripheral',
                'Phone',
                'Printer'
            ];

            $subqueries = [];
            foreach ($types as $t) {
                $table = $this->getTableForItemType($t);
                $criteria = [
                    'SELECT' => ["$field AS code"],
                    'FROM'   => $table,
                    'WHERE'  => [
                        $field         => ['LIKE', $like],
                        'is_deleted'   => 0,
                        'is_template'  => 0
                    ]
                ];

                if (
                    $CFG_GLPI["use_autoname_by_entity"]
                    && ($entities_id >= 0)
                ) {
                    $criteria['WHERE']['entities_id'] = $entities_id;
                }

                $subqueries[] = new \QuerySubQuery($criteria);
            }

            $criteria = [
                'SELECT' => [
                    new \QueryExpression(
                        "CAST(SUBSTRING(" . $DB->quoteName('code') . ", $pos, $len) AS " .
                        "unsigned) AS " . $DB->quoteName('no')
                    )
                ],
                'FROM'   => new \QueryUnion($subqueries, false, 'codes')
            ];
        } else {
            $table = $this->getTableForItemType($itemtype);
            $criteria = [
                'SELECT' => [
                    new \QueryExpression(
                        "CAST(SUBSTRING(" . $DB->quoteName($field) . ", $pos, $len) AS " .
                        "unsigned) AS " . $DB->quoteName('no')
                    )
                ],
                'FROM'   => $table,
                'WHERE'  => [
                    $field   => ['LIKE', $like]
                ]
            ];

            if ($itemtype != 'Infocom') {
                $criteria['WHERE']['is_deleted'] = 0;
                $criteria['WHERE']['is_template'] = 0;

                if (
                    $CFG_GLPI["use_autoname_by_entity"]
                    && ($entities_id >= 0)
                ) {
                    $criteria['WHERE']['entities_id'] = $entities_id;
                }
            }
        }

        $subquery = new \QuerySubQuery($criteria, 'Num');
        $iterator = $DB->request([
            'SELECT' => ['MAX' => 'Num.no AS lastNo'],
            'FROM'   => $subquery
        ]);

        if (count($iterator)) {
            $result = $iterator->current();
            $newNo = $result['lastNo'] + 1;
        } else {
            $newNo = 0;
        }

        $objectName = str_replace(
            [
                $mask,
                '\\_',
                '\\%'
            ],
            [
                Toolbox::str_pad($newNo, $len, '0', STR_PAD_LEFT),
                '_',
                '%'
            ],
            $autoNum
        );

        if ($was_sanitized) {
            $objectName = Sanitizer::encodeHtmlSpecialChars($objectName);
        }

        return $objectName;
    }

    /**
     * Close active DB connections
     *
     * @return void
     */
    public function closeDBConnections()
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Case of not init $DB object
        if ($DB !== null && method_exists($DB, "close")) {
            $DB->close();
        }
    }

    /**
     * Get dates conditions to use in 'WHERE' clause
     *
     * @param string $field table.field to request
     * @param string $begin begin date
     * @param string $end   end date
     *
     * @return array
     */
    public function getDateCriteria($field, $begin, $end)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $date_pattern = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/'; // `YYYY-mm-dd` optionaly followed by ` HH:ii:ss`

        $criteria = [];
        if (is_string($begin) && preg_match($date_pattern, $begin) === 1) {
            $criteria[] = [$field => ['>=', $begin]];
        } elseif ($begin !== null && $begin !== '') {
            trigger_error(
                sprintf('Invalid %s date value.', json_encode($begin)),
                E_USER_WARNING
            );
        }

        if (is_string($end) && preg_match($date_pattern, $end) === 1) {
            $end_expr = new QueryExpression(
                'ADDDATE(' . $DB->quoteValue($end) . ', INTERVAL 1 DAY)'
            );
            $criteria[] = [$field => ['<=', $end_expr]];
        } elseif ($end !== null && $end !== '') {
            trigger_error(
                sprintf('Invalid %s date value.', json_encode($end)),
                E_USER_WARNING
            );
        }

        return $criteria;
    }


    /**
     * Export an array to be stored in a simple field in the database
     *
     * @param array $array Array to export / encode (one level depth)
     *
     * @return string containing encoded array
     */
    public function exportArrayToDB($array)
    {
        return json_encode($array);
    }

    /**
     * Import an array encoded in a simple field in the database
     *
     * @param string $data data readed in DB to import
     *
     * @return array containing datas
     */
    public function importArrayFromDB($data)
    {
        if ($data === null) {
            return [];
        }

        $tab = json_decode($data, true);

       // Use old scheme to decode
        if (!is_array($tab)) {
            $tab = [];

            foreach (explode(" ", $data) as $item) {
                $a = explode("=>", $item);

                if (
                    (strlen($a[0]) > 0)
                    && isset($a[1])
                ) {
                    $tab[urldecode($a[0])] = urldecode($a[1]);
                }
            }
        }
        return $tab;
    }

    /**
     * Get hour from sql
     *
     * @param string $time datetime time
     *
     * @return  array
     */
    public function getHourFromSql($time)
    {
        $t = explode(" ", $time);
        $p = explode(":", $t[1]);
        return $p[0] . ":" . $p[1];
    }

    /**
     * Get the $RELATION array. It defines all relations between tables in the DB;
     * plugins may add their own stuff
     *
     * @return array the $RELATION array
     */
    public function getDbRelations()
    {
        $RELATION = []; // Redefined inside /inc/relation.constant.php

        include(GLPI_ROOT . "/inc/relation.constant.php");

       // Add plugins relations
        $plug_rel = Plugin::getDatabaseRelations();
        if (count($plug_rel) > 0) {
            $RELATION = array_merge_recursive($RELATION, $plug_rel);
        }

        $normalized_relations = [];
        foreach ($RELATION as $source_table => $table_relations) {
            $source_itemtype = getItemTypeForTable($source_table);
            if (!is_a($source_itemtype, CommonDBTM::class, true)) {
                trigger_error(
                    sprintf(
                        'Invalid relations declared for "%s" table. Table does not correspond to a known itemtype.',
                        $source_table
                    ),
                    E_USER_WARNING
                );
                continue;
            }

            $normalized_relations[$source_table] = [];

            foreach ($table_relations as $target_table_key => $target_fields) {
                $normalized_relations[$source_table][$target_table_key] = [];

                $target_table = preg_replace('/^_/', '', $target_table_key);

                // Harmonize relations specs.
                // Can be:
                // 1 - a string representing a unique forign key relation: e.g. 'users_id'
                // 2 - an array representing a unique polymorphic relation: e.g. ['itemtype', 'items_id']
                // 3 - an array containing one element per relation: e.g. ['users_id', 'users_id_tech', ['itemtype', 'items_id']]
                //
                // Result should always be an array containing one element per relation.
                if (
                    !is_array($target_fields)
                    || (
                        // 'itemtype'/'items_id' (polymorphic relationship)
                        count($target_fields) === 2
                        && count(array_filter($target_fields, 'is_array')) === 0 // ensure array elements are only strings
                        && count(preg_grep('/^itemtype/', $target_fields)) === 1
                        && count(preg_grep('/^items_id/', $target_fields)) === 1
                    )
                    || (
                        // glpi_ipaddresses relationship that does not respect naming conventions
                        count($target_fields) === 2
                        && count(array_filter($target_fields, 'is_array')) === 0 // ensure array elements are only strings
                        && in_array('mainitemtype', $target_fields)
                        && in_array('mainitems_id', $target_fields)
                    )
                ) {
                    $target_fields = [$target_fields];
                }

                $target_itemtype = getItemTypeForTable($target_table);
                if (!is_a($target_itemtype, CommonDBTM::class, true)) {
                    trigger_error(
                        sprintf(
                            'Invalid relations declared for "%s" table. Target table "%s" does not correspond to a known itemtype.',
                            $source_table,
                            $target_table
                        ),
                        E_USER_WARNING
                    );
                    continue;
                }

                foreach ($target_fields as $target_field) {
                    if (is_string($target_field)) {
                        if (!str_starts_with($target_table_key, '_') && $target_itemtype::getIndexName() === $target_field) {
                            // Relation is declared on ID field of the item.
                            // This is an unexpected case that we cannot support.
                            // Indeed, we would have to pass the current ID value (used to load the item before saving it)
                            // and the new field value (used to update the value) in the same array key. This is not possible.
                            trigger_error(
                                sprintf(
                                    'Relation between "%s" and "%s" table based on "%s" field cannot be handled automatically as "%s" also corresponds to index field of the target table.',
                                    $source_table,
                                    $target_table,
                                    $target_field,
                                    $target_field
                                ),
                                E_USER_WARNING
                            );
                            continue;
                        }

                        if (
                            in_array($source_table, ['glpi_authldaps', 'glpi_authmails'])
                            && $target_table === 'glpi_users'
                            && $target_field === 'auths_id'
                        ) {
                            // Ignore this specific case.
                            // FIXME `auths_id` should be replaced by a polymorphic `itemtype_auth`/`items_id_auth` relation.
                            continue;
                        }
                        if (
                            $source_table === 'glpi_requesttypes'
                            && $target_table === 'glpi_users'
                            && $target_field === 'default_requesttypes_id'
                        ) {
                            // Ignore this specific case.
                            // FIXME `default_requesttypes_id` should be renamed to `requesttypes_id_default` to respect naming conventions.
                            continue;
                        }
                        if (
                            $source_table === 'glpi_knowbaseitems_comments'
                            && $target_table === 'glpi_knowbaseitems_comments'
                            && $target_field === 'parent_comment_id'
                        ) {
                            // Ignore this specific case.
                            // FIXME `parent_comment_id` should be renamed to `knowbaseitems_comments_id_parent` to respect naming conventions.
                            continue;
                        }

                        $target_field_itemtype = isForeignKeyField($target_field)
                            ? getItemtypeForForeignKeyField($target_field)
                            : null;
                        if (!is_a($target_field_itemtype, CommonDBTM::class, true)) {
                            // Relation is declared in a field that does not seems to be a foreign key.
                            trigger_error(
                                sprintf(
                                    'Invalid relations declared between "%s" and "%s" table. Target field "%s" is not a foreign key field.',
                                    $source_table,
                                    $target_table,
                                    $target_field
                                ),
                                E_USER_WARNING
                            );
                            continue;
                        }

                        if ($target_field_itemtype !== $source_itemtype) {
                            // Relation is made on a field that is not a foreign key of the source object.
                            trigger_error(
                                sprintf(
                                    'Invalid relations declared between "%s" and "%s" table. Target field "%s" is not a foreign key field of "%s".',
                                    $source_table,
                                    $target_table,
                                    $target_field,
                                    $source_itemtype
                                ),
                                E_USER_WARNING
                            );
                            continue;
                        }
                    } else {
                        $is_array = is_array($target_field);
                        $is_polymorphic_relation = $is_array
                            && count($target_field) === 2
                            && count(preg_grep('/^itemtype/', $target_field)) === 1
                            && count(preg_grep('/^items_id/', $target_field)) === 1;
                        $is_ipaddress_relation = $is_array
                            && $target_table === 'glpi_ipaddresses'
                            && count($target_field) === 2
                            && in_array('mainitemtype', $target_field)
                            && in_array('mainitems_id', $target_field);
                        if (!$is_array && !$is_polymorphic_relation && !$is_ipaddress_relation) {
                            trigger_error(
                                sprintf(
                                    'Invalid relations declared between "%s" and "%s" table. %s is not valid a valid relation.',
                                    $source_table,
                                    $target_table,
                                    json_encode($target_field)
                                ),
                                E_USER_WARNING
                            );
                            continue;
                        }
                    }

                    // If code reach this point, then no exception case was detected.
                    // Relation si so preserved.
                    $normalized_relations[$source_table][$target_table_key][] = $target_field;
                }
            }
        }
        return $normalized_relations;
    }

    /**
     * Return ItemType for a foreign key
     *
     * @param string $fkname Foreign key
     *
     * @return string ItemType name for the fkname parameter
     */
    public function getItemtypeForForeignKeyField($fkname)
    {
        $table = $this->getTableNameForForeignKeyField($fkname);
        return $this->getItemTypeForTable($table);
    }
}
