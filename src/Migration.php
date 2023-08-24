<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration Class
 *
 * @since 0.80
 **/
class Migration
{
    private $change    = [];
    private $fulltexts = [];
    private $uniques   = [];
    private $search_opts = [];
    protected $version;
    private $deb;
    private $lastMessage;
    private $log_errors = 0;
    private $current_message_area_id;
    private $queries = [
        'pre'    => [],
        'post'   => []
    ];

    /**
     * List (name => value) of configuration options to add, if they're missing
     * @var array
     */
    private $configs = [];

    /**
     * Configuration context
     * @var string
     */
    private $context = 'core';

    const PRE_QUERY = 'pre';
    const POST_QUERY = 'post';

    /**
     * Output handler to use. If not set, output will be directly echoed on a format depending on
     * execution context (Web VS CLI).
     *
     * @var OutputInterface|null
     */
    protected $output_handler;

    /**
     * @param integer $ver Version number
     **/
    public function __construct($ver)
    {

        $this->deb = time();
        $this->version = $ver;

        global $application;
        if ($application instanceof Application) {
           // $application global variable will be available if Migration is called from a CLI console command
            $this->output_handler = $application->getOutput();
        }
    }

    /**
     * Set version
     *
     * @since 0.84
     *
     * @param integer $ver Version number
     *
     * @return void
     **/
    public function setVersion($ver)
    {

        $this->version = $ver;
        $this->addNewMessageArea("migration_message_$ver");
    }


    /**
     * Add new message
     *
     * @since 0.84
     *
     * @param string $id Area ID
     *
     * @return void
     **/
    public function addNewMessageArea($id)
    {

        if (!isCommandLine() && $id != $this->current_message_area_id) {
            $this->current_message_area_id = $id;
            echo "<div id='" . $this->current_message_area_id . "'></div>";
        }

        $this->displayMessage(__('Work in progress...'));
    }


    /**
     * Flush previous displayed message in log file
     *
     * @since 0.84
     *
     * @return void
     **/
    public function flushLogDisplayMessage()
    {

        if (isset($this->lastMessage)) {
            $tps = Html::timestampToString(time() - $this->lastMessage['time']);
            $this->log($tps . ' for "' . $this->lastMessage['msg'] . '"', false);
            unset($this->lastMessage);
        }
    }


    /**
     * Additional message in global message
     *
     * @param string $msg text  to display
     *
     * @return void
     **/
    public function displayMessage($msg)
    {

        $this->flushLogDisplayMessage();

        $now = time();
        $tps = Html::timestampToString($now - $this->deb);

        $this->outputMessage("{$msg} ({$tps})", null, $this->current_message_area_id);

        $this->lastMessage = ['time' => time(),
            'msg'  => $msg
        ];
    }


    /**
     * Log message for this migration
     *
     * @since 0.84
     *
     * @param string  $message Message to display
     * @param boolean $warning Is a warning
     *
     * @return void
     **/
    public function log($message, $warning)
    {

        if ($warning) {
            $log_file_name = 'warning_during_migration_to_' . $this->version;
        } else {
            $log_file_name = 'migration_to_' . $this->version;
        }

       // Do not log if more than 3 log error
        if (
            $this->log_errors < 3
            && !Toolbox::logInFile($log_file_name, $message . ' @ ', true)
        ) {
            $this->log_errors++;
        }
    }


    /**
     * Display a title
     *
     * @param string $title Title to display
     *
     * @return void
     **/
    public function displayTitle($title)
    {
        $this->flushLogDisplayMessage();

        $this->outputMessage($title, 'title');
    }


    /**
     * Display a Warning
     *
     * @param string  $msg Message to display
     * @param boolean $red Displays with red class (false by default)
     *
     * @return void
     **/
    public function displayWarning($msg, $red = false)
    {
        $this->outputMessage($msg, $red ? 'warning' : 'strong');
        $this->log($msg, true);
    }


    /**
     * Display an error
     *
     * @param string  $msg Message to display
     *
     * @return void
     **/
    public function displayError(string $message): void
    {
        $this->outputMessage($message, 'error');
        $this->log($message, true);
    }


    /**
     * Define field's format
     *
     * @param string  $type          can be bool, char, string, integer, date, datetime, text, longtext or autoincrement
     * @param string  $default_value new field's default value,
     *                               if a specific default value needs to be used
     * @param boolean $nodefault     No default value (false by default)
     *
     * @return string
     **/
    private function fieldFormat($type, $default_value, $nodefault = false)
    {

        $format = '';
        $collate = DBConnection::getDefaultCollation();
        switch ($type) {
            case 'bool':
            case 'boolean':
                $format = "TINYINT NOT NULL";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT '0'";
                    } else if (in_array($default_value, ['0', '1'])) {
                        $format .= " DEFAULT '$default_value'";
                    } else {
                        throw new \LogicException('Default value must be 0 or 1.');
                    }
                }
                break;

            case 'char':
            case 'character':
                $format = "CHAR(1)";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT NULL";
                    } else {
                        $format .= " NOT NULL DEFAULT '$default_value'";
                    }
                }
                break;

            case 'str':
            case 'string':
                $format = "VARCHAR(255) COLLATE $collate";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT NULL";
                    } else {
                        $format .= " NOT NULL DEFAULT '$default_value'";
                    }
                }
                break;

            case 'int':
            case 'integer':
                $format = "INT NOT NULL";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT '0'";
                    } else if (is_numeric($default_value)) {
                        $format .= " DEFAULT '$default_value'";
                    } else {
                        throw new \LogicException('Default value must be numeric.');
                    }
                }
                break;

            case 'date':
                $format = "DATE";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT NULL";
                    } else {
                        $format .= " DEFAULT '$default_value'";
                    }
                }
                break;

            case 'time':
                $format = "TIME";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT NULL";
                    } else {
                        $format .= " NOT NULL DEFAULT '$default_value'";
                    }
                }
                break;

            case 'timestamp':
            case 'datetime':
                $format = "TIMESTAMP";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " NULL DEFAULT NULL";
                    } else {
                        $format .= " DEFAULT '$default_value'";
                    }
                }
                break;

            case 'text':
            case 'mediumtext':
            case 'longtext':
                $format = sprintf('%s COLLATE %s', strtoupper($type), $collate);
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT NULL";
                    } else {
                        if (empty($default_value)) {
                            $format .= " NOT NULL";
                        } else {
                            $format .= " NOT NULL DEFAULT '$default_value'";
                        }
                    }
                }
                break;

           // for plugins
            case 'autoincrement':
                $format = "INT " . DBConnection::getDefaultPrimaryKeySignOption() . " NOT NULL AUTO_INCREMENT";
                break;

            case 'fkey':
                $format = "INT " . DBConnection::getDefaultPrimaryKeySignOption() . " NOT NULL DEFAULT 0";
                break;

            default:
               // for compatibility with old 0.80 migrations
                $format = $type;
                break;
        }
        return $format;
    }


    /**
     * Add a new GLPI normalized field
     *
     * @param string $table   Table name
     * @param string $field   Field name
     * @param string $type    Field type, @see Migration::fieldFormat()
     * @param array  $options Options:
     *                         - update    : if not empty = value of $field (must be protected)
     *                         - condition : if needed
     *                         - value     : default_value new field's default value, if a specific default value needs to be used
     *                         - nodefault : do not define default value (default false)
     *                         - comment   : comment to be added during field creation
     *                         - first     : add the new field at first column
     *                         - after     : where adding the new field
     *                         - null      : value could be NULL (default false)
     *
     * @return boolean
     **/
    public function addField($table, $field, $type, $options = [])
    {
        global $DB;

        $params['update']    = '';
        $params['condition'] = '';
        $params['value']     = null;
        $params['nodefault'] = false;
        $params['comment']   = '';
        $params['after']     = '';
        $params['first']     = '';
        $params['null']      = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $format = $this->fieldFormat($type, $params['value'], $params['nodefault']);

        if (!empty($params['comment'])) {
            $params['comment'] = " COMMENT '" . addslashes($params['comment']) . "'";
        }

        if (!empty($params['after'])) {
            $params['after'] = " AFTER `" . $params['after'] . "`";
        } else if (!empty($params['first'])) {
            $params['first'] = " FIRST ";
        }

        if ($params['null']) {
            $params['null'] = 'NULL ';
        }

        if ($format) {
            if (!$DB->fieldExists($table, $field, false)) {
                $this->change[$table][] = "ADD `$field` $format " . $params['comment'] . " " .
                                      $params['null'] . $params['first'] . $params['after'];

                if ($params['update'] !== '') {
                    $this->migrationOneTable($table);
                    $query = "UPDATE `$table`
                        SET `$field` = " . $params['update'] . " " .
                        $params['condition'] . "";
                    $DB->queryOrDie($query, $this->version . " set $field in $table");
                }
                return true;
            }
        }
        return false;
    }


    /**
     * Modify field for migration
     *
     * @param string $table    Table name
     * @param string $oldfield Old name of the field
     * @param string $newfield New name of the field
     * @param string $type     Field type, {@see Migration::fieldFormat()}
     * @param array  $options  Options:
     *                         - value     : new field's default value, if a specific default value needs to be used
     *                         - first     : add the new field at first column
     *                         - after     : where adding the new field
     *                         - null      : value could be NULL (default false)
     *                         - comment comment to be added during field creation
     *                         - nodefault : do not define default value (default false)
     *
     * @return boolean
     **/
    public function changeField($table, $oldfield, $newfield, $type, $options = [])
    {
        global $DB;

        $params['value']     = null;
        $params['nodefault'] = false;
        $params['comment']   = '';
        $params['after']     = '';
        $params['first']     = '';
        $params['null']      = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $format = $this->fieldFormat($type, $params['value'], $params['nodefault']);

        if ($params['comment']) {
            $params['comment'] = " COMMENT '" . addslashes($params['comment']) . "'";
        }

        if (!empty($params['after'])) {
            $params['after'] = " AFTER `" . $params['after'] . "`";
        } else if (!empty($params['first'])) {
            $params['first'] = " FIRST ";
        }

        if ($params['null']) {
            $params['null'] = 'NULL ';
        }

        if ($DB->fieldExists($table, $oldfield, false)) {
           // in order the function to be replayed
           // Drop new field if name changed
            if (
                ($oldfield != $newfield)
                && $DB->fieldExists($table, $newfield)
            ) {
                $this->change[$table][] = "DROP `$newfield` ";
            }

            if ($format) {
                $this->change[$table][] = "CHANGE `$oldfield` `$newfield` $format " . $params['comment'] . " " .
                                      $params['null'] . $params['first'] . $params['after'];
            }
            return true;
        }

        return false;
    }


    /**
     * Drop field for migration
     *
     * @param string $table Table name
     * @param string $field Field name
     *
     * @return void
     **/
    public function dropField($table, $field)
    {
        global $DB;

        if ($DB->fieldExists($table, $field, false)) {
            $this->change[$table][] = "DROP `$field`";
        }
    }


    /**
     * Drop immediately a table if it exists
     *
     * @param string $table Table name
     *
     * @return void
     **/
    public function dropTable($table)
    {
        global $DB;

        if ($DB->tableExists($table)) {
            $DB->dropTable($table);
        }
    }


    /**
     * Add index for migration
     *
     * @param string       $table     Table name
     * @param string|array $fields    Field(s) name(s)
     * @param string       $indexname Index name, $fields if empty, defaults to empty
     * @param string       $type      Index type (index or unique - default 'INDEX')
     * @param integer      $len       Field length (default 0)
     *
     * @return void
     **/
    public function addKey($table, $fields, $indexname = '', $type = 'INDEX', $len = 0)
    {

       // si pas de nom d'index, on prend celui du ou des champs
        if (!$indexname) {
            if (is_array($fields)) {
                $indexname = implode("_", $fields);
            } else {
                $indexname = $fields;
            }
        }

        if (!isIndex($table, $indexname)) {
            if (is_array($fields)) {
                if ($len) {
                    $fields = "`" . implode("`($len), `", $fields) . "`($len)";
                } else {
                    $fields = "`" . implode("`, `", $fields) . "`";
                }
            } else if ($len) {
                $fields = "`$fields`($len)";
            } else {
                $fields = "`$fields`";
            }

            if ($type == 'FULLTEXT') {
                $this->fulltexts[$table][] = "ADD $type `$indexname` ($fields)";
            } else if ($type == 'UNIQUE') {
                $this->uniques[$table][] = "ADD $type `$indexname` ($fields)";
            } else {
                $this->change[$table][] = "ADD $type `$indexname` ($fields)";
            }
        }
    }


    /**
     * Drop index for migration
     *
     * @param string $table     Table name
     * @param string $indexname Index name
     *
     * @return void
     **/
    public function dropKey($table, $indexname)
    {

        if (isIndex($table, $indexname)) {
            $this->change[$table][] = "DROP INDEX `$indexname`";
        }
    }


    /**
     * Drop foreign key for migration
     *
     * @param string $table
     * @param string $keyname
     *
     * @return void
     **/
    public function dropForeignKeyContraint($table, $keyname)
    {

        if (isForeignKeyContraint($table, $keyname)) {
            $this->change[$table][] = "DROP FOREIGN KEY `$keyname`";
        }
    }


    /**
     * Rename table for migration
     *
     * @param string $oldtable Old table name
     * @param string $newtable new table name
     *
     * @return void
     **/
    public function renameTable($oldtable, $newtable)
    {
        global $DB;

        if (!$DB->tableExists("$newtable") && $DB->tableExists("$oldtable")) {
            $query = "RENAME TABLE `$oldtable` TO `$newtable`";
            $DB->queryOrDie($query, $this->version . " rename $oldtable");

           // Clear possibly forced value of table name.
           // Actually the only forced value in core is for config table.
            $itemtype = getItemTypeForTable($newtable);
            if (class_exists($itemtype)) {
                $itemtype::forceTable($newtable);
            }

           // Update target of "buffered" schema updates
            if (isset($this->change[$oldtable])) {
                $this->change[$newtable] = $this->change[$oldtable];
                unset($this->change[$oldtable]);
            }
            if (isset($this->fulltexts[$oldtable])) {
                $this->fulltexts[$newtable] = $this->fulltexts[$oldtable];
                unset($this->fulltexts[$oldtable]);
            }
            if (isset($this->uniques[$oldtable])) {
                $this->uniques[$newtable] = $this->uniques[$oldtable];
                unset($this->uniques[$oldtable]);
            }
        } else {
            if (
                str_starts_with($oldtable, 'glpi_plugin_')
                || str_starts_with($newtable, 'glpi_plugin_')
            ) {
                return;
            }
            $message = sprintf(
                __('Unable to rename table %1$s (%2$s) to %3$s (%4$s)!'),
                $oldtable,
                ($DB->tableExists($oldtable) ? __('ok') : __('nok')),
                $newtable,
                ($DB->tableExists($newtable) ? __('nok') : __('ok'))
            );
            if (isCommandLine()) {
                throw new \RuntimeException($message);
            } else {
                echo $message . "\n";
                die(1);
            }
        }
    }


    /**
     * Copy table for migration
     *
     * @since 0.84
     *
     * @param string $oldtable The name of the table already inside the database
     * @param string $newtable The copy of the old table
     * @param bool   $insert   Copy content ? True by default
     *
     * @return void
     **/
    public function copyTable($oldtable, $newtable, bool $insert = true)
    {
        global $DB;

        if (
            !$DB->tableExists($newtable)
            && $DB->tableExists($oldtable)
        ) {
           // Try to do a flush tables if RELOAD privileges available
           // $query = "FLUSH TABLES `$oldtable`, `$newtable`";
           // $DB->query($query);

            $query = "CREATE TABLE `$newtable` LIKE `$oldtable`";
            $DB->queryOrDie($query, $this->version . " create $newtable");

            if ($insert) {
               //needs DB::insert to support subqueries to get migrated
                $query = "INSERT INTO `$newtable` (SELECT * FROM `$oldtable`)";
                $DB->queryOrDie($query, $this->version . " copy from $oldtable to $newtable");
            }
        }
    }


    /**
     * Insert an entry inside a table
     *
     * @since 0.84
     *
     * @param string $table The table to alter
     * @param array  $input The elements to add inside the table
     *
     * @return integer|null id of the last item inserted by mysql
     **/
    public function insertInTable($table, array $input)
    {
        global $DB;

        if (
            $DB->tableExists("$table")
            && is_array($input) && (count($input) > 0)
        ) {
            $values = [];
            foreach ($input as $field => $value) {
                if ($DB->fieldExists($table, $field)) {
                    $values[$field] = $value;
                }
            }

            $DB->insertOrDie($table, $values, $this->version . " insert in $table");

            return $DB->insertId();
        }

        return null;
    }


    /**
     * Execute migration for only one table
     *
     * @param string $table Table name
     *
     * @return void
     **/
    public function migrationOneTable($table)
    {
        global $DB;

        if (isset($this->change[$table])) {
            $query = "ALTER TABLE `$table` " . implode(" ,\n", $this->change[$table]) . " ";
            $this->displayMessage(sprintf(__('Change of the database layout - %s'), $table));
            $DB->queryOrDie($query, $this->version . " multiple alter in $table");
            unset($this->change[$table]);
        }

        if (isset($this->fulltexts[$table])) {
            $this->displayMessage(sprintf(__('Adding fulltext indices - %s'), $table));
            foreach ($this->fulltexts[$table] as $idx) {
                $query = "ALTER TABLE `$table` " . $idx;
                $DB->queryOrDie($query, $this->version . " $idx");
            }
            unset($this->fulltexts[$table]);
        }

        if (isset($this->uniques[$table])) {
            $this->displayMessage(sprintf(__('Adding unicity indices - %s'), $table));
            foreach ($this->uniques[$table] as $idx) {
                $query = "ALTER TABLE `$table` " . $idx;
                $DB->queryOrDie($query, $this->version . " $idx");
            }
            unset($this->uniques[$table]);
        }
    }


    /**
     * Execute global migration
     *
     * @return void
     **/
    public function executeMigration()
    {
        global $DB;

        foreach ($this->queries[self::PRE_QUERY] as $query) {
            $DB->queryOrDie($query['query'], $query['message']);
        }
        $this->queries[self::PRE_QUERY] = [];

        $tables = array_merge(
            array_keys($this->change),
            array_keys($this->fulltexts),
            array_keys($this->uniques)
        );
        foreach ($tables as $table) {
            $this->migrationOneTable($table);
        }

        foreach ($this->queries[self::POST_QUERY] as $query) {
            $DB->queryOrDie($query['query'], $query['message']);
        }
        $this->queries[self::POST_QUERY] = [];

        $this->storeConfig();
        $this->migrateSearchOptions();

       // end of global message
        $this->displayMessage(__('Task completed.'));
    }


    /**
     * Register a new rule
     *
     * @since 0.84
     *
     * @param array $rule     Array of fields of glpi_rules
     * @param array $criteria Array of Array of fields of glpi_rulecriterias
     * @param array $actions  Array of Array of fields of glpi_ruleactions
     *
     * @return integer new rule id
     **/
    public function createRule(array $rule, array $criteria, array $actions)
    {
        global $DB;

       // Avoid duplicate - Need to be improved using a rule uuid of other
        if (countElementsInTable('glpi_rules', ['name' => $DB->escape($rule['name'])])) {
            return 0;
        }
        $rule['comment']     = sprintf(__('Automatically generated by GLPI %s'), $this->version);
        $rule['description'] = '';

        // Compute ranking
        $ruleinst = new $rule['sub_type']();
        $ranking = $ruleinst->getNextRanking();
        if (!$ranking) {
            $ranking = 1;
        }

        // The rule itself
        $values = ['ranking' => $ranking];
        foreach ($rule as $field => $value) {
            $values[$field] = $value;
        }
        $DB->insertOrDie('glpi_rules', $values);
        $rid = $DB->insertId();

        // The rule criteria
        foreach ($criteria as $criterion) {
            $values = ['rules_id' => $rid];
            foreach ($criterion as $field => $value) {
                $values[$field] = $value;
            }
            $DB->insertOrDie('glpi_rulecriterias', $values);
        }

        // The rule criteria actions
        foreach ($actions as $action) {
            $values = ['rules_id' => $rid];
            foreach ($action as $field => $value) {
                $values[$field] = $value;
            }
            $DB->insertOrDie('glpi_ruleactions', $values);
        }

        return $rid;
    }


    /**
     * Update display preferences
     *
     * @since 0.85
     *
     * @param array $toadd items to add : itemtype => array of values
     * @param array $todel items to del : itemtype => array of values
     * @param bool $only_default : add the display pref only on global view
     *
     * @return void
     **/
    public function updateDisplayPrefs($toadd = [], $todel = [], bool $only_default = false)
    {
        global $DB;

        //TRANS: %s is the table or item to migrate
        $this->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));
        foreach ($toadd as $itemtype => $searchoptions_ids) {
            $criteria = [
                'SELECT'   => 'users_id',
                'DISTINCT' => true,
                'FROM'     => 'glpi_displaypreferences',
                'WHERE'    => ['itemtype' => $itemtype]
            ];
            if ($only_default) {
                $criteria['WHERE']['users_id'] = 0;
            }

            $iterator = $DB->request($criteria);

            if (count($iterator) > 0) {
                // There are already existing display preferences for this itemtype.
                // Add new search options with an higher rank.
                foreach ($iterator as $data) {
                    $max_rank = $DB->request([
                        'SELECT' => ['MAX' => 'rank AS max_rank'],
                        'FROM'   => 'glpi_displaypreferences',
                        'WHERE'  => [
                            'users_id' => $data['users_id'],
                            'itemtype' => $itemtype,
                        ]
                    ])->current()['max_rank'];

                    $rank = $max_rank + 1;

                    foreach ($searchoptions_ids as $searchoption_id) {
                        $exists = countElementsInTable(
                            'glpi_displaypreferences',
                            [
                                'users_id' => $data['users_id'],
                                'itemtype' => $itemtype,
                                'num'      => $searchoption_id,
                            ]
                        ) > 0;

                        if (!$exists) {
                            $DB->insert(
                                'glpi_displaypreferences',
                                [
                                    'itemtype'  => $itemtype,
                                    'num'       => $searchoption_id,
                                    'rank'      => $rank++,
                                    'users_id'  => $data['users_id']
                                ]
                            );
                        }
                    }
                }
            } else {
                // There are not yet any display preference for this itemtype.
                // Add new search options with a rank starting to 1.
                $rank = 1;
                foreach ($searchoptions_ids as $searchoption_id) {
                    $DB->insert(
                        'glpi_displaypreferences',
                        [
                            'itemtype'  => $itemtype,
                            'num'       => $searchoption_id,
                            'rank'      => $rank++,
                            'users_id'  => 0
                        ]
                    );
                }
            }
        }

        // delete display preferences
        foreach ($todel as $itemtype => $searchoptions_ids) {
            if (count($searchoptions_ids) > 0) {
                $DB->delete(
                    'glpi_displaypreferences',
                    [
                        'itemtype'  => $itemtype,
                        'num'       => $searchoptions_ids
                    ]
                );
            }
        }
    }

    /**
     * Add a migration SQL query
     *
     * @param string $type    Either self::PRE_QUERY or self::POST_QUERY
     * @param string $query   Query to execute
     * @param string $message Message to display on error, defaults to null
     *
     * @return Migration
     */
    private function addQuery($type, $query, $message = null)
    {
        $this->queries[$type][] =  [
            'query'     => $query,
            'message'   => $message
        ];
        return $this;
    }

    /**
     * Add a pre migration SQL query
     *
     * @param string $query   Query to execute
     * @param string $message Mesage to display on error, defaults to null
     *
     * @return Migration
     */
    public function addPreQuery($query, $message = null)
    {
        return $this->addQuery(self::PRE_QUERY, $query, $message);
    }

    /**
     * Add a post migration SQL query
     *
     * @param string $query   Query to execute
     * @param string $message Mesage to display on error, defaults to null
     *
     * @return Migration
     */
    public function addPostQuery($query, $message = null)
    {
        return $this->addQuery(self::POST_QUERY, $query, $message);
    }

    /**
     * Backup existing tables
     *
     * @param array $tables Existing tables to backup
     *
     * @return boolean
     */
    public function backupTables($tables)
    {
        global $DB;

        $backup_tables = false;
        foreach ($tables as $table) {
           // rename new tables if exists ?
            if ($DB->tableExists($table)) {
                $this->dropTable("backup_$table");
                $this->displayWarning(sprintf(
                    __('%1$s table already exists. A backup have been done to %2$s'),
                    $table,
                    "backup_$table"
                ));
                $backup_tables = true;
                $this->renameTable("$table", "backup_$table");
            }
        }
        if ($backup_tables) {
            $this->displayWarning("You can delete backup tables if you have no need of them.", true);
        }
        return $backup_tables;
    }

    /**
     * Add configuration value(s) to current context; @see Migration::addConfig()
     *
     * @since 9.2
     *
     * @param array  $values  Value(s) to add
     * @param string $context Context to add on (optional)
     *
     * @return Migration
     */
    public function addConfig($values, $context = null)
    {
        $context = $context ?? $this->context;
        if (!isset($this->configs[$context])) {
            $this->configs[$context] = [];
        }
        $this->configs[$context] += (array)$values;
        return $this;
    }

    /**
     * Store configuration values that does not exists
     *
     * @since 9.2
     *
     * @return void
     */
    private function storeConfig()
    {
        global $DB;

        foreach ($this->configs as $context => $config) {
            if (count($config)) {
                $existing = $DB->request(
                    "glpi_configs",
                    [
                        'context'   => $context,
                        'name'      => array_keys($config)
                    ]
                );
                foreach ($existing as $conf) {
                     unset($config[$conf['name']]);
                }
                if (count($config)) {
                    Config::setConfigurationValues($context, $config);
                    $this->displayMessage(sprintf(
                        __('Configuration values added for %1$s (%2$s).'),
                        implode(', ', array_keys($config)),
                        $context
                    ));
                }
            }
            unset($this->configs[$context]);
        }
    }


    /**
     * Add new right to profiles that match rights requirements
     *    Default is to give rights to profiles with READ and UPDATE rights on config
     *
     * @param string  $name   Right name
     * @param integer $rights Right to set (defaults to ALLSTANDARDRIGHT)
     * @param array   $requiredrights Array of right name => value
     *                   A profile must have these rights in order to get the new right.
     *                   This array can be empty to add the right to every profile.
     *                   Default is ['config' => READ | UPDATE].
     *
     * @return void
     */
    public function addRight($name, $rights = ALLSTANDARDRIGHT, $requiredrights = ['config' => READ | UPDATE])
    {
        global $DB;

       // Get all profiles where new rights has not been added yet
        $prof_iterator = $DB->request(
            [
                'SELECT'    => 'glpi_profiles.id',
                'FROM'      => 'glpi_profiles',
                'LEFT JOIN' => [
                    'glpi_profilerights' => [
                        'ON' => [
                            'glpi_profilerights' => 'profiles_id',
                            'glpi_profiles'      => 'id',
                            [
                                'AND' => ['glpi_profilerights.name' => $name]
                            ]
                        ]
                    ],
                ],
                'WHERE'     => [
                    'glpi_profilerights.id' => null,
                ]
            ]
        );

        if ($prof_iterator->count() === 0) {
            return;
        }

        $where = [];
        foreach ($requiredrights as $reqright => $reqvalue) {
            $where['OR'][] = [
                'name'   => $reqright,
                new QueryExpression("{$DB->quoteName('rights')} & $reqvalue = $reqvalue")
            ];
        }

        foreach ($prof_iterator as $profile) {
            if (empty($requiredrights)) {
                $reqmet = true;
            } else {
                $iterator = $DB->request([
                    'SELECT' => [
                        'name',
                        'rights'
                    ],
                    'FROM'   => 'glpi_profilerights',
                    'WHERE'  => $where + ['profiles_id' => $profile['id']]
                ]);

                $reqmet = (count($iterator) == count($requiredrights));
            }

            $DB->insertOrDie(
                'glpi_profilerights',
                [
                    'id'           => null,
                    'profiles_id'  => $profile['id'],
                    'name'         => $name,
                    'rights'       => $reqmet ? $rights : 0
                ],
                sprintf('%1$s add right for %2$s', $this->version, $name)
            );
        }

        $this->displayWarning(
            sprintf(
                'New rights has been added for %1$s, you should review ACLs after update',
                $name
            ),
            true
        );
    }

    /**
     * Add specific right to profiles that match interface
     *
     * @param string  $name      Right name
     * @param integer $right     Right to add
     * @param string  $interface Interface to set (defaults to central)
     *
     * @return void
     */
    public function addRightByInterface($name, $right, $interface = 'central')
    {
        global $DB;

        $prof_iterator = $DB->request([
            'SELECT'    => [
                'glpi_profiles.id',
                'glpi_profilerights.rights',
            ],
            'FROM'      => 'glpi_profiles',
            'JOIN'      => [
                'glpi_profilerights' => [
                    'FKEY' => [
                        'glpi_profilerights'      => 'profiles_id',
                        'glpi_profiles'           => 'id',
                        [
                            'AND' => [
                                'glpi_profilerights.name' => $name
                            ]
                        ]
                    ]
                ]
            ],
            'WHERE'     => [
                'interface' => $interface,
            ]
        ]);

        foreach ($prof_iterator as $profile) {
            if (intval($profile['rights']) & $right) {
                continue;
            }
            $DB->updateOrInsert(
                'glpi_profilerights',
                [
                    'rights'       => $profile['rights'] | $right,
                ],
                [
                    'profiles_id'  => $profile['id'],
                    'name'         => $name
                ],
                sprintf('%1$s update right for %2$s', $this->version, $name)
            );
        }

        $this->displayWarning(
            sprintf(
                'Rights has been updated for %1$s, you should review ACLs after update',
                $name
            ),
            true
        );
    }

    /**
     * Update right to profiles that match rights requirements
     *    Default is to update rights of profiles with READ and UPDATE rights on config
     *
     * @param string  $name   Right name
     * @param integer $rights Right to set
     * @param array   $requiredrights Array of right name => value
     *                   A profile must have these rights in order to get its rights updated.
     *                   This array can be empty to add the right to every profile.
     *                   Default is ['config' => READ | UPDATE].
     *
     * @return void
     */
    public function updateRight($name, $rights, $requiredrights = ['config' => READ | UPDATE])
    {
        global $DB;

       // Get all profiles with required rights

        $join = [];
        $i = 1;
        foreach ($requiredrights as $reqright => $reqvalue) {
            $join["glpi_profilerights as right$i"] = [
                'ON' => [
                    "right$i"       => 'profiles_id',
                    'glpi_profiles' => 'id',
                    [
                        'AND' => [
                            "right$i.name"   => $reqright,
                            new QueryExpression("{$DB->quoteName("right$i.rights")} & $reqvalue = $reqvalue"),
                        ]
                    ]
                ]
            ];
            $i++;
        }

        $prof_iterator = $DB->request(
            [
                'SELECT'     => 'glpi_profiles.id',
                'FROM'       => 'glpi_profiles',
                'INNER JOIN' => $join,
            ]
        );

        foreach ($prof_iterator as $profile) {
            $DB->updateOrInsert(
                'glpi_profilerights',
                [
                    'rights'       => $rights
                ],
                [
                    'profiles_id'  => $profile['id'],
                    'name'         => $name
                ],
                sprintf('%1$s update right for %2$s', $this->version, $name)
            );
        }

        $this->displayWarning(
            sprintf(
                'Rights has been updated for %1$s, you should review ACLs after update',
                $name
            ),
            true
        );
    }

    public function setOutputHandler($output_handler)
    {

        $this->output_handler = $output_handler;
    }

    /**
     * Output a message.
     *
     * @param string $msg      Message to output.
     * @param string $style    Style to use, value can be 'title', 'warning', 'strong' or null.
     * @param string $area_id  Display area to use.
     *
     * @return void
     */
    protected function outputMessage($msg, $style = null, $area_id = null)
    {
        if (isCommandLine()) {
            $this->outputMessageToCli($msg, $style);
        } else {
            $this->outputMessageToHtml($msg, $style, $area_id);
        }
    }

    /**
     * Output a message in console output.
     *
     * @param string $msg    Message to output.
     * @param string $style  Style to use, see self::outputMessage() for possible values.
     *
     * @return void
     */
    private function outputMessageToCli($msg, $style = null)
    {

        $format = null;
        $verbosity = OutputInterface::VERBOSITY_NORMAL;
        switch ($style) {
            case 'title':
                $msg       = str_pad(" $msg ", 100, '=', STR_PAD_BOTH);
                $format    = 'info';
                $verbosity = OutputInterface::VERBOSITY_NORMAL;
                break;
            case 'warning':
                $msg       = str_pad("** {$msg}", 100);
                $format    = 'comment';
                $verbosity = OutputInterface::VERBOSITY_NORMAL;
                break;
            case 'strong':
                $msg       = str_pad($msg, 100);
                $format    = 'comment';
                $verbosity = OutputInterface::VERBOSITY_NORMAL;
                break;
            case 'error':
                $msg       = str_pad("!! {$msg}", 100);
                $format    = 'error';
                $verbosity = OutputInterface::VERBOSITY_QUIET;
                break;
            default:
                $msg       = str_pad($msg, 100);
                $format    = 'comment';
                $verbosity = OutputInterface::VERBOSITY_VERBOSE;
                break;
        }

        if ($this->output_handler instanceof OutputInterface) {
            if (null !== $format) {
                $msg = sprintf('<%1$s>%2$s</%1$s>', $format, $msg);
            }
            $this->output_handler->writeln($msg, $verbosity);
        } else {
            echo $msg . PHP_EOL;
        }
    }

    /**
     * Output a message in html page.
     *
     * @param string $msg      Message to output.
     * @param string $style    Style to use, see self::outputMessage() for possible values.
     * @param string $area_id  Display area to use.
     *
     * @return void
     */
    private function outputMessageToHtml($msg, $style = null, $area_id = null)
    {

        $msg = Html::entities_deep($msg);

        switch ($style) {
            case 'title':
                $msg = '<h3>' . $msg . '</h3>';
                break;
            case 'warning':
            case 'error':
                $msg = '<div class="migred"><p>' . $msg . '</p></div>';
                break;
            case 'strong':
                $msg = '<p><span class="b">' . $msg . '</span></p>';
                break;
            default:
                $msg = '<p class="center">' . $msg . '</p>';
                break;
        }

        if (null !== $area_id) {
            echo "<script type='text/javascript'>
                  document.getElementById('{$area_id}').innerHTML = '{$msg}';
               </script>\n";
            Html::glpi_flush();
        } else {
            echo $msg;
        }
    }

    /**
     * Rename an itemtype an update database structure and data to use the new itemtype name.
     * Changes done by this method:
     *  - renaming of itemtype table;
     *  - renaming of foreign key fields corresponding to this itemtype;
     *  - update of "itemtype" column values in all tables.
     *
     * @param string  $old_itemtype
     * @param string  $new_itemtype
     * @param boolean $update_structure
     *    Whether to update or not DB structure (itemtype table name and foreign key fields)
     *
     * @return void
     *
     * @since 9.5.0
     */
    public function renameItemtype($old_itemtype, $new_itemtype, $update_structure = true)
    {
        global $DB;

        if ($old_itemtype == $new_itemtype) {
           // Do nothing if new value is same as old one
            return;
        }

        $this->displayMessage(sprintf(__('Renaming "%s" itemtype to "%s"...'), $old_itemtype, $new_itemtype));

        $old_table = getTableForItemType($old_itemtype);
        $new_table = getTableForItemType($new_itemtype);
        if ($old_table !== $new_table && $update_structure) {
            $old_fkey  = getForeignKeyFieldForTable($old_table);
            $new_fkey  = getForeignKeyFieldForTable($new_table);

           // Check prerequisites
            if (!$DB->tableExists($old_table)) {
                throw new \RuntimeException(
                    sprintf(
                        'Table "%s" does not exists.',
                        $old_table
                    )
                );
            }
            if ($DB->tableExists($new_table)) {
                throw new \RuntimeException(
                    sprintf(
                        'Table "%s" cannot be renamed as table "%s" already exists.',
                        $old_table,
                        $new_table
                    )
                );
            }
            $fkey_column_iterator = $DB->request(
                [
                    'SELECT' => [
                        'table_name AS TABLE_NAME',
                        'column_name AS COLUMN_NAME',
                    ],
                    'FROM'   => 'information_schema.columns',
                    'WHERE'  => [
                        'table_schema' => $DB->dbdefault,
                        'table_name'   => ['LIKE', 'glpi\_%'],
                        'OR' => [
                            ['column_name'  => $old_fkey],
                            ['column_name'  => ['LIKE', $old_fkey . '_%']],
                        ],
                    ],
                    'ORDER'  => 'TABLE_NAME',
                ]
            );
            $fkey_column_array = iterator_to_array($fkey_column_iterator); // Convert to array to be able to loop twice
            foreach ($fkey_column_array as $fkey_column) {
                $fkey_table   = $fkey_column['TABLE_NAME'];
                $fkey_oldname = $fkey_column['COLUMN_NAME'];
                $fkey_newname = preg_replace('/^' . preg_quote($old_fkey, '/') . '/', $new_fkey, $fkey_oldname);
                if ($DB->fieldExists($fkey_table, $fkey_newname)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Field "%s" cannot be renamed in table "%s" as "%s" is field already exists.',
                            $fkey_oldname,
                            $fkey_table,
                            $fkey_newname
                        )
                    );
                }
            }

           //1. Rename itemtype table
            $this->displayMessage(sprintf(__('Renaming "%s" table to "%s"...'), $old_table, $new_table));
            $this->renameTable($old_table, $new_table);

           //2. Rename foreign key fields
            $this->displayMessage(
                sprintf(__('Renaming "%s" foreign keys to "%s" in all tables...'), $old_fkey, $new_fkey)
            );
            foreach ($fkey_column_array as $fkey_column) {
                $fkey_table   = $fkey_column['TABLE_NAME'];
                $fkey_oldname = $fkey_column['COLUMN_NAME'];
                $fkey_newname = preg_replace('/^' . preg_quote($old_fkey, '/') . '/', $new_fkey, $fkey_oldname);

                if ($fkey_table == $old_table) {
                  // Special case, foreign key is inside renamed table, use new name
                    $fkey_table = $new_table;
                }

                $this->changeField(
                    $fkey_table,
                    $fkey_oldname,
                    $fkey_newname,
                    "int " . DBConnection::getDefaultPrimaryKeySignOption() . " NOT NULL DEFAULT '0'" // assume that foreign key always uses GLPI conventions
                );
            }
        }

       //3. Update "itemtype" values in all tables
        $this->displayMessage(
            sprintf(__('Renaming "%s" itemtype to "%s" in all tables...'), $old_itemtype, $new_itemtype)
        );
        $itemtype_column_iterator = $DB->request(
            [
                'SELECT' => [
                    'table_name AS TABLE_NAME',
                    'column_name AS COLUMN_NAME',
                ],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                    'table_schema' => $DB->dbdefault,
                    'table_name'   => ['LIKE', 'glpi\_%'],
                    'OR' => [
                        ['column_name'  => 'itemtype'],
                        ['column_name'  => ['LIKE', 'itemtype_%']],
                    ],
                ],
                'ORDER'  => 'TABLE_NAME',
            ]
        );
        foreach ($itemtype_column_iterator as $itemtype_column) {
            $this->addPostQuery(
                $DB->buildUpdate(
                    $itemtype_column['TABLE_NAME'],
                    [$itemtype_column['COLUMN_NAME'] => $new_itemtype],
                    [$itemtype_column['COLUMN_NAME'] => $old_itemtype]
                )
            );
        }
    }

    /**
     * Migrate search option values in various locations in the database.
     * This does not change the actual search option ID. This must still be changed manually in the itemtype's class file.
     * The changes made by this function will only be applied when the migration is finalized through {@link Migration::executeMigration()}.
     *
     * @param string $itemtype The itemtype
     * @param int    $old_search_opt The old search option ID
     * @param int    $new_search_opt The new search option ID
     *
     * @return void
     * @since 9.5.6
     */
    public function changeSearchOption(string $itemtype, int $old_search_opt, int $new_search_opt)
    {
        if (!isset($this->search_opts[$itemtype])) {
            $this->search_opts[$itemtype] = [];
        }
        $this->search_opts[$itemtype][] = [
            'old' => $old_search_opt,
            'new' => $new_search_opt
        ];
    }

    /**
     * Finalize search option migrations
     *
     * @return void
     * @since 9.5.6
     */
    private function migrateSearchOptions()
    {
        global $DB;

        if (empty($this->search_opts)) {
            return;
        }

        foreach ($this->search_opts as $itemtype => $changes) {
            foreach ($changes as $p) {
                $old_search_opt = $p['old'];
                $new_search_opt = $p['new'];

                // Remove duplicates (a display preference exists for both old key and new key for a same user).
                // Removes existing SO using new ID as they are probably corresponding to an ID that existed before and
                // was not cleaned correctly.
                $duplicates_iterator = $DB->request([
                    'SELECT'     => ['new.id'],
                    'FROM'       => DisplayPreference::getTable() . ' AS new',
                    'INNER JOIN' => [
                        DisplayPreference::getTable() . ' AS old' => [
                            'ON' => [
                                'new' => 'itemtype',
                                'old' => 'itemtype',
                                [
                                    'AND' => [
                                        'new.users_id' => new QueryExpression($DB->quoteName('old.users_id')),
                                        'new.itemtype' => $itemtype,
                                        'new.num'      => $new_search_opt,
                                        'old.num'      => $old_search_opt,
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]);
                if ($duplicates_iterator->count() > 0) {
                    $ids = array_column(iterator_to_array($duplicates_iterator), 'id');
                    $DB->deleteOrDie(DisplayPreference::getTable(), ['id' => $ids]);
                }

                // Update display preferences
                $DB->updateOrDie(DisplayPreference::getTable(), [
                    'num' => $new_search_opt
                ], [
                    'itemtype' => $itemtype,
                    'num'      => $old_search_opt
                ]);
            }
        }

       // Update saved searches. We have to parse every query to account for the search option in meta criteria
        $iterator = $DB->request([
            'SELECT' => ['id', 'itemtype', 'query'],
            'FROM'   => SavedSearch::getTable(),
        ]);

        foreach ($iterator as $data) {
            $query = [];
            parse_str($data['query'], $query);
            $is_changed = false;

            foreach ($this->search_opts as $itemtype => $changes) {
                foreach ($changes as $p) {
                    $old_search_opt = $p['old'];
                    $new_search_opt = $p['new'];

                    if ($data['itemtype'] === $itemtype) {
                        // Fix sort
                        if (isset($query['sort']) && (int)$query['sort'] === $old_search_opt) {
                            $query['sort'] = $new_search_opt;
                            $is_changed = true;
                        }
                    }

                   // Fix criteria
                    if (isset($query['criteria'])) {
                        foreach ($query['criteria'] as $cid => $criterion) {
                             $is_meta = isset($criterion['meta']) && (int)$criterion['meta'] === 1;
                            if (
                                ($is_meta &&
                                 isset($criterion['itemtype'], $criterion['field']) &&
                                 $criterion['itemtype'] === $itemtype &&
                                 (int)$criterion['field'] === $old_search_opt) ||
                                 (!$is_meta &&
                                 $data['itemtype'] === $itemtype &&
                                 isset($criterion['field']) &&
                                 (int)$criterion['field'] === $old_search_opt)
                            ) {
                                $query['criteria'][$cid]['field'] = $new_search_opt;
                                $is_changed = true;
                            }
                        }
                    }
                }
            }

           // Write changes if any were made
            if ($is_changed) {
                $DB->updateOrDie(SavedSearch::getTable(), [
                    'query'  => http_build_query($query)
                ], [
                    'id'     => $data['id']
                ]);
            }
        }
    }

    /**
     * Helper to create a simple link table between two itemtypes
     * The table will contain 3 columns :
     * - id (primary key)
     * - foreign key 1 (first itemtype)
     * - foreign key 2 (second itemtype)
     *
     * @param string $table Table name
     * @param string $class_1 First itemtype (CommonDBTM)
     * @param string $class_2 Second itemtype (CommonDBTM)
     */
    public function createLinkTable(
        string $table,
        string $class_1,
        string $class_2
    ) {
        global $DB;
        if ($DB->tableExists($table)) {
            return;
        }

        $fk_1 = $class_1::getForeignKeyField();
        $fk_2 = $class_2::getForeignKeyField();

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $DB->queryOrDie("
            CREATE TABLE `$table` (
                `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                `$fk_1` int {$default_key_sign} NOT NULL DEFAULT '0',
                `$fk_2` int {$default_key_sign} NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `$fk_1` (`$fk_1`),
                KEY `$fk_2` (`$fk_2`)
            ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;
        ", "Create link table between $class_1 and $class_2");
    }
}
