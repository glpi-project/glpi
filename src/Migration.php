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
use Glpi\Message\MessageType;
use Glpi\Progress\AbstractProgressIndicator;

use function Safe\preg_replace;

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
    private $lastMessage;
    private $log_errors = 0;
    private $queries = [
        'pre'    => [],
        'post'   => [],
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

    public const PRE_QUERY = 'pre';
    public const POST_QUERY = 'post';

    private ?AbstractProgressIndicator $progress_indicator;

    protected DBmysql $db;

    /**
     * @param string $ver Version number
     **/
    public function __construct($ver, ?AbstractProgressIndicator $progress_indicator = null)
    {
        global $DB;
        $this->db = $DB;
        $this->version = $ver;
        $this->progress_indicator = $progress_indicator;
    }

    /**
     * Set version
     *
     * @since 0.84
     *
     * @param string $ver Version number
     *
     * @return void
     **/
    public function setVersion($ver)
    {
        $this->version = $ver;
    }

    /**
     * Add new message
     *
     * @since 0.84
     *
     * @param string $id Area ID
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public function addNewMessageArea($id)
    {
        Toolbox::deprecated();
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

        $this->progress_indicator?->setProgressBarMessage($msg);

        $this->lastMessage = [
            'time' => time(),
            'msg'  => $msg,
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
            && !Toolbox::logInFile($log_file_name, $message . "\n", true, output: false)
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
     *
     * @deprecated 11.0.0
     */
    public function displayTitle($title): void
    {
        Toolbox::deprecated();

        $this->flushLogDisplayMessage();

        $this->progress_indicator?->setProgressBarMessage($title);
    }

    /**
     * Display a Warning
     *
     * @param string  $msg Message to display
     * @param boolean $red Displays with red class (false by default)
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public function displayWarning($msg, $red = false): void
    {
        Toolbox::deprecated();

        $this->addMessage($red ? MessageType::Warning : MessageType::Notice, (string) $msg);
    }

    /**
     * Display an error
     *
     * @param string  $message Message to display
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public function displayError(string $message): void
    {
        Toolbox::deprecated();

        $this->addMessage(MessageType::Error, $message);
    }

    /**
     * Add a message.
     * This message will be added to the progress indicator and will be written in the logs.
     */
    final public function addMessage(MessageType $type, string $message): void
    {
        $this->progress_indicator?->addMessage($type, $message);

        $this->log($message, in_array($type, [MessageType::Error, MessageType::Warning], true));
    }

    /**
     * Add a success message.
     */
    final public function addSuccessMessage(string $message): void
    {
        $this->addMessage(MessageType::Success, $message);
    }

    /**
     * Add an error message.
     */
    final public function addErrorMessage(string $message): void
    {
        $this->addMessage(MessageType::Error, $message);
    }

    /**
     * Add a warning message.
     */
    final public function addWarningMessage(string $message): void
    {
        $this->addMessage(MessageType::Warning, $message);
    }

    /**
     * Add an informative message.
     */
    final public function addInfoMessage(string $message): void
    {
        $this->addMessage(MessageType::Notice, $message);
    }

    /**
     * Add a debug message.
     */
    final public function addDebugMessage(string $message): void
    {
        $this->addMessage(MessageType::Debug, $message);
    }

    /**
     * Get formated SQL field
     *
     * @param string  $type          can be "bool"|"boolean", "char"|"character", "str"|"string", "int"|"integer", "date", "time", "timestamp"|"datetime", "text"|"mediumtext"|"longtext", "autoincrement", "fkey", "json", or a complete type definition like "decimal(20,4) NOT NULL DEFAULT '0.0000'"
     * @param string  $default_value new field's default value,
     *                               if a specific default value needs to be used
     * @param boolean $nodefault     No default value (false by default)
     *
     * @return string
     **/
    private function fieldFormat($type, $default_value, $nodefault = false): string
    {

        $format = '';
        $collate = $this->getDefaultCollation();
        switch ($type) {
            case 'bool':
            case 'boolean':
                $format = "TINYINT NOT NULL";
                if (!$nodefault) {
                    if (is_null($default_value)) {
                        $format .= " DEFAULT '0'";
                    } elseif (in_array($default_value, ['0', '1'])) {
                        $format .= " DEFAULT '$default_value'";
                    } else {
                        throw new LogicException('Default value must be 0 or 1.');
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
                    } elseif (is_numeric($default_value)) {
                        $format .= " DEFAULT '$default_value'";
                    } else {
                        throw new LogicException('Default value must be numeric.');
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
                $format = "INT " . $this->getDefaultPrimaryKeySignOption() . " NOT NULL AUTO_INCREMENT";
                break;

            case 'fkey':
                $format = "INT " . $this->getDefaultPrimaryKeySignOption() . " NOT NULL DEFAULT 0";
                break;

            case 'json':
                $format = "JSON NOT NULL";
                break;

            default:
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
     * @param array{update?: string|int, condition?: string, value?: string|int|null, nodefault?: bool, comment?: string, first?: string, after?: string, null?: bool} $options
     *                         - update    : value to set after field creation (update query)
     *                         - condition : sql condition to apply for update query
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
        $params['update']    = '';
        $params['condition'] = '';
        $params['value']     = null;
        $params['nodefault'] = false;
        $params['comment']   = '';
        $params['after']     = '';
        $params['first']     = '';
        $params['null']      = false;

        $params = array_merge($params, $options);

        $format = $this->fieldFormat($type, $params['value'], $params['nodefault']);

        if (!empty($params['comment'])) {
            $params['comment'] = " COMMENT " . $this->db->quote($params['comment']);
        }

        if (!empty($params['after'])) {
            $params['after'] = " AFTER `" . $params['after'] . "`";
        } elseif (!empty($params['first'])) {
            $params['first'] = " FIRST ";
        }

        if ($params['null']) {
            $params['null'] = 'NULL ';
        }

        if ($format) {
            if (!$this->db->fieldExists($table, $field, false)) {
                $this->change[$table][] = "ADD `$field` $format " . $params['comment'] . " "
                                      . $params['null'] . $params['first'] . $params['after'];

                if ($params['update'] !== '') {
                    $this->migrationOneTable($table);
                    $query = "UPDATE `$table`
                        SET `$field` = " . $params['update'] . " "
                        . $params['condition'] . "";
                    $this->db->doQuery($query);
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
            $params['comment'] = " COMMENT " . $this->db->quote($params['comment']);
        }

        if (!empty($params['after'])) {
            $params['after'] = " AFTER `" . $params['after'] . "`";
        } elseif (!empty($params['first'])) {
            $params['first'] = " FIRST ";
        }

        if ($params['null']) {
            $params['null'] = 'NULL ';
        }

        if ($this->db->fieldExists($table, $oldfield, false)) {
            // in order the function to be replayed
            // Drop new field if name changed
            if (
                ($oldfield !== $newfield)
                && $this->db->fieldExists($table, $newfield)
            ) {
                $this->change[$table][] = $this->db->buildDrop($newfield, 'FIELD');
            }

            if ($format) {
                $this->change[$table][] = "CHANGE `$oldfield` `$newfield` $format " . $params['comment'] . " "
                                      . $params['null'] . $params['first'] . $params['after'];
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
        if ($this->db->fieldExists($table, $field, false)) {
            $this->change[$table][] = $this->db->buildDrop($field, 'FIELD');
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
        if ($this->db->tableExists($table)) {
            $this->db->dropTable($table);
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
     * The table must exist before calling this function.
     *
     * @return void
     **/
    public function addKey($table, $fields, $indexname = '', $type = 'INDEX', $len = 0)
    {
        // if no index name, we take that of the field(s)
        if (!$indexname) {
            if (is_array($fields)) {
                $indexname = implode("_", $fields);
            } else {
                $indexname = $fields;
            }
        }

        if (!$this->hasKey($table, $indexname)) {
            if (is_array($fields)) {
                if ($len) {
                    $fields = "`" . implode("`($len), `", $fields) . "`($len)";
                } else {
                    $fields = "`" . implode("`, `", $fields) . "`";
                }
            } elseif ($len) {
                $fields = "`$fields`($len)";
            } else {
                $fields = "`$fields`";
            }

            if ($type === 'FULLTEXT') {
                $this->fulltexts[$table][] = "ADD $type `$indexname` ($fields)";
            } elseif ($type === 'UNIQUE') {
                $this->uniques[$table][] = "ADD $type `$indexname` ($fields)";
            } else {
                $this->change[$table][] = "ADD $type `$indexname` ($fields)";
            }
        }
    }

    /**
     * Mockable function to check if a key already exists
     * @param string $table Table name
     * @param string $indexname Index name
     * @return bool
     * @see isIndex()
     * @note Could be removed when using dependency injection or some other refactoring
     */
    protected function hasKey($table, $indexname): bool
    {
        return isIndex($table, $indexname);
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
        if ($this->hasKey($table, $indexname)) {
            $this->change[$table][] = $this->db->buildDrop($indexname, 'INDEX');
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
            $this->change[$table][] = $this->db->buildDrop($keyname, 'FOREIGN KEY');
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
        if (!$this->db->tableExists("$newtable") && $this->db->tableExists("$oldtable")) {
            $query = "RENAME TABLE `$oldtable` TO `$newtable`";
            $this->db->doQuery($query);

            // Clear possibly forced value of table name.
            // Actually the only forced value in core is for config table.
            $itemtype = getItemTypeForTable($newtable);
            if ($itemtype !== null && class_exists($itemtype)) {
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
                ($this->db->tableExists($oldtable) ? __('ok') : __('nok')),
                $newtable,
                ($this->db->tableExists($newtable) ? __('nok') : __('ok'))
            );
            throw new RuntimeException($message);
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
        if (
            !$this->db->tableExists($newtable)
            && $this->db->tableExists($oldtable)
        ) {
            // Try to do a flush tables if RELOAD privileges available
            // $query = "FLUSH TABLES `$oldtable`, `$newtable`";
            // $this->db->doQuery($query);

            $query = "CREATE TABLE `$newtable` LIKE `$oldtable`";
            $this->db->doQuery($query);

            if ($insert) {
                //needs DB::insert to support subqueries to get migrated
                $query = "INSERT INTO `$newtable` (SELECT * FROM `$oldtable`)";
                $this->db->doQuery($query);
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
        if (
            $this->db->tableExists("$table")
            && (count($input) > 0)
        ) {
            $values = [];
            foreach ($input as $field => $value) {
                if ($this->db->fieldExists($table, $field)) {
                    $values[$field] = $value;
                }
            }

            $this->db->insert($table, $values);

            return $this->db->insertId();
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
        if (isset($this->change[$table])) {
            $query = "ALTER TABLE `$table` " . implode(" ,\n", $this->change[$table]) . " ";
            $this->addDebugMessage(sprintf(__('Change of the database layout - %s'), $table));
            $this->db->doQuery($query);
            unset($this->change[$table]);
        }

        if (isset($this->fulltexts[$table])) {
            $this->addDebugMessage(sprintf(__('Adding fulltext indices - %s'), $table));
            foreach ($this->fulltexts[$table] as $idx) {
                $query = "ALTER TABLE `$table` " . $idx;
                $this->db->doQuery($query);
            }
            unset($this->fulltexts[$table]);
        }

        if (isset($this->uniques[$table])) {
            $this->addDebugMessage(sprintf(__('Adding unicity indices - %s'), $table));
            foreach ($this->uniques[$table] as $idx) {
                $query = "ALTER TABLE `$table` " . $idx;
                $this->db->doQuery($query);
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
        foreach ($this->queries[self::PRE_QUERY] as $query) {
            $this->db->doQuery($query['query']);
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
            $this->db->doQuery($query['query']);
        }
        $this->queries[self::POST_QUERY] = [];

        $this->storeConfig();
        $this->migrateSearchOptions();

        // end of global message
        $this->addSuccessMessage(sprintf(__('Update to %s version completed.'), $this->version));
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
        // Avoid duplicate - Need to be improved using a rule uuid of other
        if (countElementsInTable('glpi_rules', ['name' => $rule['name']])) {
            return 0;
        }
        $rule['comment']     = sprintf(__('Automatically generated by GLPI %s'), $this->version);
        $rule['description'] = '';

        // Compute ranking
        if (!is_a($rule['sub_type'], Rule::class)) {
            return 0;
        }
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
        $this->db->insert('glpi_rules', $values);
        $rid = $this->db->insertId();

        // The rule criteria
        foreach ($criteria as $criterion) {
            $values = ['rules_id' => $rid];
            foreach ($criterion as $field => $value) {
                $values[$field] = $value;
            }
            $this->db->insert('glpi_rulecriterias', $values);
        }

        // The rule criteria actions
        foreach ($actions as $action) {
            $values = ['rules_id' => $rid];
            foreach ($action as $field => $value) {
                $values[$field] = $value;
            }
            $this->db->insert('glpi_ruleactions', $values);
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
        //TRANS: %s is the table or item to migrate
        $this->addDebugMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));
        foreach ($toadd as $itemtype => $searchoptions_ids) {
            $criteria = [
                'SELECT'   => 'users_id',
                'DISTINCT' => true,
                'FROM'     => 'glpi_displaypreferences',
                'WHERE'    => ['itemtype' => $itemtype],
            ];
            if ($only_default) {
                $criteria['WHERE']['users_id'] = 0;
            }

            $iterator = $this->db->request($criteria);

            if (count($iterator) > 0) {
                // There are already existing display preferences for this itemtype.
                // Add new search options with an higher rank.
                foreach ($iterator as $data) {
                    $max_rank = $this->db->request([
                        'SELECT' => [
                            QueryFunction::max('rank', 'max_rank'),
                        ],
                        'FROM'   => 'glpi_displaypreferences',
                        'WHERE'  => [
                            'users_id' => $data['users_id'],
                            'itemtype' => $itemtype,
                        ],
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
                            $this->db->insert(
                                'glpi_displaypreferences',
                                [
                                    'itemtype'  => $itemtype,
                                    'num'       => $searchoption_id,
                                    'rank'      => $rank++,
                                    'users_id'  => $data['users_id'],
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
                    $this->db->insert(
                        'glpi_displaypreferences',
                        [
                            'itemtype'  => $itemtype,
                            'num'       => $searchoption_id,
                            'rank'      => $rank++,
                            'users_id'  => 0,
                        ]
                    );
                }
            }
        }

        // delete display preferences
        foreach ($todel as $itemtype => $searchoptions_ids) {
            if (count($searchoptions_ids) > 0) {
                $this->db->delete(
                    'glpi_displaypreferences',
                    [
                        'itemtype'  => $itemtype,
                        'num'       => $searchoptions_ids,
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
            'message'   => $message,
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
        $backup_tables = false;
        foreach ($tables as $table) {
            // rename new tables if exists ?
            if ($this->db->tableExists($table)) {
                $this->dropTable("backup_$table");
                $this->addInfoMessage(sprintf(
                    __('%1$s table already exists. A backup have been done to %2$s'),
                    $table,
                    "backup_$table"
                ));
                $backup_tables = true;
                $this->renameTable("$table", "backup_$table");
            }
        }
        if ($backup_tables) {
            $this->addInfoMessage("You can delete backup tables if you have no need of them.");
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
        $context ??= $this->context;
        if (!isset($this->configs[$context])) {
            $this->configs[$context] = [];
        }
        $this->configs[$context] += (array) $values;
        return $this;
    }

    /**
     * Remove configuration value(s) to current context; @see Migration::removeConfig()
     *
     * @since 11.0.0
     *
     * @param array  $values  Value(s) to remove
     * @param ?string $context Context to remove on. Defaults to the context of this migration instance.
     *
     * @return Migration
     */
    public function removeConfig(array $values, ?string $context = null)
    {
        if ($values === []) {
            return $this;
        }

        $context ??= $this->context;
        $this->db->delete(
            'glpi_configs',
            [
                'context' => $context,
                'name'    => $values,
            ]
        );
        return $this;
    }

    /**
     * Store configuration values that does not exist
     *
     * @since 9.2
     *
     * @return void
     */
    private function storeConfig()
    {
        foreach ($this->configs as $context => $config) {
            if (count($config)) {
                $existing = $this->db->request([
                    'FROM' => 'glpi_configs',
                    'WHERE' => [
                        'context'   => $context,
                        'name'      => array_keys($config),
                    ],
                ]);
                foreach ($existing as $conf) {
                    unset($config[$conf['name']]);
                }
                if (count($config)) {
                    foreach ($config as $name => $value) {
                        $this->db->insert(
                            'glpi_configs',
                            [
                                'context' => $context,
                                'name'    => $name,
                                'value'   => $value,
                            ]
                        );
                    }
                    $this->addDebugMessage(sprintf(
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
        // Get all profiles where new rights has not been added yet
        $prof_iterator = $this->db->request(
            [
                'SELECT'    => 'glpi_profiles.id',
                'FROM'      => 'glpi_profiles',
                'LEFT JOIN' => [
                    'glpi_profilerights' => [
                        'ON' => [
                            'glpi_profilerights' => 'profiles_id',
                            'glpi_profiles'      => 'id',
                            [
                                'AND' => ['glpi_profilerights.name' => $name],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    'glpi_profilerights.id' => null,
                ],
            ]
        );

        if ($prof_iterator->count() === 0) {
            return;
        }

        $where = [];
        foreach ($requiredrights as $reqright => $reqvalue) {
            $where['OR'][] = [
                'name'   => $reqright,
                new QueryExpression("{$this->db::quoteName('rights')} & $reqvalue = $reqvalue"),
            ];
        }

        foreach ($prof_iterator as $profile) {
            if (empty($requiredrights)) {
                $reqmet = true;
            } else {
                $iterator = $this->db->request([
                    'SELECT' => [
                        'name',
                        'rights',
                    ],
                    'FROM'   => 'glpi_profilerights',
                    'WHERE'  => $where + ['profiles_id' => $profile['id']],
                ]);

                $reqmet = (count($iterator) === count($requiredrights));
            }

            $this->db->insert(
                'glpi_profilerights',
                [
                    'id'           => null,
                    'profiles_id'  => $profile['id'],
                    'name'         => $name,
                    'rights'       => $reqmet ? $rights : 0,
                ]
            );

            $this->updateProfileLastRightsUpdate($profile['id']);
        }

        $this->addWarningMessage(
            sprintf(
                'New rights has been added for %1$s, you should review ACLs after update',
                $name
            ),
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
        $prof_iterator = $this->db->request([
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
                                'glpi_profilerights.name' => $name,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'interface' => $interface,
            ],
        ]);

        foreach ($prof_iterator as $profile) {
            if ((int) $profile['rights'] & $right) {
                continue;
            }
            $this->db->updateOrInsert(
                'glpi_profilerights',
                [
                    'rights'       => $profile['rights'] | $right,
                ],
                [
                    'profiles_id'  => $profile['id'],
                    'name'         => $name,
                ],
            );

            $this->updateProfileLastRightsUpdate($profile['id']);
        }

        $this->addWarningMessage(
            sprintf(
                'Rights has been updated for %1$s, you should review ACLs after update',
                $name
            ),
        );
    }

    /**
     * Replace right to profiles that match rights requirements.
     * Default is to update rights of profiles with READ and UPDATE rights on config.
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
    public function replaceRight($name, $rights, $requiredrights = ['config' => READ | UPDATE])
    {
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
                            new QueryExpression($this->db::quoteName("right$i.rights") . " & $reqvalue = $reqvalue"),
                        ],
                    ],
                ],
            ];
            $i++;
        }

        $prof_iterator = $this->db->request(
            [
                'SELECT'     => 'glpi_profiles.id',
                'FROM'       => 'glpi_profiles',
                'INNER JOIN' => $join,
            ]
        );

        foreach ($prof_iterator as $profile) {
            $this->db->updateOrInsert(
                'glpi_profilerights',
                [
                    'rights'       => $rights,
                ],
                [
                    'profiles_id'  => $profile['id'],
                    'name'         => $name,
                ],
            );

            $this->updateProfileLastRightsUpdate($profile['id']);
        }

        $this->addWarningMessage(
            sprintf(
                'Rights has been updated for %1$s, you should review ACLs after update',
                $name
            ),
        );
    }

    /**
     * Give right to profiles that match rights requirements
     *   Default is to give rights to profiles with READ and UPDATE rights on config
     *
     * @param string  $name   Right name
     * @param integer $rights Right to set
     * @param array   $requiredrights Array of right name => value
     *                   A profile must have these rights in order to get its rights added.
     *                   This array can be empty to add the right to every profile.
     *                   Default is ['config' => READ | UPDATE].
     *
     * @return void
     */
    public function giveRight($name, $rights, $requiredrights = ['config' => READ | UPDATE])
    {
        // Build JOIN clause to get all profiles with required rights
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
                            new QueryExpression($this->db::quoteName("right$i.rights") . " & $reqvalue = $reqvalue"),
                        ],
                    ],
                ],
            ];
            $i++;
        }

        // Get all profiles with required rights
        $prof_iterator = $this->db->request(
            [
                'SELECT'     => 'glpi_profiles.id',
                'FROM'       => 'glpi_profiles',
                'INNER JOIN' => $join,
            ]
        );

        $added = false;
        foreach ($prof_iterator as $profile) {
            // Check if the right is already present
            $existingRight = $this->db->request([
                'FROM'  => 'glpi_profilerights',
                'WHERE' => [
                    'profiles_id' => $profile['id'],
                    'name'        => $name,
                ],
            ]);

            if ($existingRight->numrows() > 0) {
                $profile_right = $existingRight->current();
                // If the value specified is not already included, update the rights by adding the value
                if (($profile_right['rights'] & $rights) !== $rights) {
                    // Mettre à jour les droits en ajoutant la valeur spécifiée
                    $newRights = $profile_right['rights'] | $rights;
                    $this->db->update(
                        'glpi_profilerights',
                        ['rights' => $newRights],
                        ['id' => $profile_right['id']]
                    );
                    $added = true;
                }
                // If the value specified is already included, do nothing
            } else {
                // If the right does not exist, add it
                $this->db->insert(
                    'glpi_profilerights',
                    [
                        'profiles_id'  => $profile['id'],
                        'name'         => $name,
                        'rights'       => $rights,
                    ]
                );
                $added = true;
            }

            // Update last rights update for the profile
            $this->updateProfileLastRightsUpdate($profile['id']);
        }

        // Display a warning message if rights have been given
        if ($added) {
            $this->addWarningMessage(
                sprintf(
                    'Rights have been given for %1$s, you should review ACLs after update',
                    $name
                ),
            );
        }
    }

    /**
     * Update last rights update for given profile.
     *
     * @param int $profile_id
     * @return void
     */
    private function updateProfileLastRightsUpdate(int $profile_id): void
    {
        // Check if the 'last_rights_update' field exists before trying to update it.
        // This field may not exist yet as it is added by a migration, and other migrations
        // that add a right could be executed before the migration that adds this field.
        if (!$this->db->fieldExists('glpi_profiles', 'last_rights_update')) {
            return;
        }

        $this->db->update(
            'glpi_profiles',
            [
                'last_rights_update' => Session::getCurrentTime(),
            ],
            [
                'id' => $profile_id,
            ]
        );
    }

    /**
     * @deprecated 11.0.0
     */
    public function setOutputHandler($output_handler): void
    {
        Toolbox::deprecated();
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
        if ($old_itemtype == $new_itemtype) {
            // Do nothing if new value is same as old one
            return;
        }

        $this->addDebugMessage(sprintf(__('Renaming "%s" itemtype to "%s"...'), $old_itemtype, $new_itemtype));

        $old_table = getTableForItemType($old_itemtype);
        $new_table = getTableForItemType($new_itemtype);
        if ($old_table !== $new_table && $update_structure) {
            $old_fkey  = getForeignKeyFieldForTable($old_table);
            $new_fkey  = getForeignKeyFieldForTable($new_table);

            // Check prerequisites
            if (!$this->db->tableExists($old_table)) {
                throw new RuntimeException(
                    sprintf(
                        'Table "%s" does not exists.',
                        $old_table
                    )
                );
            }
            if ($this->db->tableExists($new_table)) {
                throw new RuntimeException(
                    sprintf(
                        'Table "%s" cannot be renamed as table "%s" already exists.',
                        $old_table,
                        $new_table
                    )
                );
            }
            $fkey_column_iterator = $this->db->request(
                [
                    'SELECT' => [
                        'table_name AS TABLE_NAME',
                        'column_name AS COLUMN_NAME',
                    ],
                    'FROM'   => 'information_schema.columns',
                    'WHERE'  => [
                        'table_schema' => $this->db->dbdefault,
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
                if ($this->db->fieldExists($fkey_table, $fkey_newname)) {
                    throw new RuntimeException(
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
            $this->addDebugMessage(sprintf(__('Renaming "%s" table to "%s"...'), $old_table, $new_table));
            $this->renameTable($old_table, $new_table);

            //2. Rename foreign key fields
            $this->addDebugMessage(
                sprintf(__('Renaming "%s" foreign keys to "%s" in all tables...'), $old_fkey, $new_fkey)
            );
            foreach ($fkey_column_array as $fkey_column) {
                $fkey_table   = $fkey_column['TABLE_NAME'];
                $fkey_oldname = $fkey_column['COLUMN_NAME'];
                $fkey_newname = preg_replace('/^' . preg_quote($old_fkey, '/') . '/', $new_fkey, $fkey_oldname);

                if ($fkey_table === $old_table) {
                    // Special case, foreign key is inside renamed table, use new name
                    $fkey_table = $new_table;
                }

                $this->changeField(
                    $fkey_table,
                    $fkey_oldname,
                    $fkey_newname,
                    "int " . $this->getDefaultPrimaryKeySignOption() . " NOT NULL DEFAULT '0'" // assume that foreign key always uses GLPI conventions
                );
            }
        }

        //3. Update "itemtype" values in all tables
        $this->addDebugMessage(
            sprintf(__('Renaming "%s" itemtype to "%s" in all tables...'), $old_itemtype, $new_itemtype)
        );
        $itemtype_column_iterator = $this->db->request(
            [
                'SELECT' => [
                    'table_name AS TABLE_NAME',
                    'column_name AS COLUMN_NAME',
                ],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                    'table_schema' => $this->db->dbdefault,
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
            $this->db->update(
                $itemtype_column['TABLE_NAME'],
                [$itemtype_column['COLUMN_NAME'] => $new_itemtype],
                [$itemtype_column['COLUMN_NAME'] => $old_itemtype]
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
            'new' => $new_search_opt,
        ];
    }

    /**
     * Remove a search option from various locations in the database including display preferences and saved searches.
     * The changes made by this function will only be applied when the migration is finalized through {@link Migration::executeMigration()}.
     *
     * @param string $itemtype The itemtype
     * @param int $search_opt The search option ID to remove
     * @return void
     */
    public function removeSearchOption(string $itemtype, int $search_opt)
    {
        if (!isset($this->search_opts[$itemtype])) {
            $this->search_opts[$itemtype] = [];
        }
        $this->search_opts[$itemtype][] = [
            'old' => $search_opt,
            'new' => null,
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
        if (empty($this->search_opts)) {
            return;
        }

        foreach ($this->search_opts as $itemtype => $changes) {
            foreach ($changes as $p) {
                $old_search_opt = $p['old'];
                $new_search_opt = $p['new'];

                if ($new_search_opt !== null) {
                    // Remove duplicates (a display preference exists for both old key and new key for a same user).
                    // Removes existing SO using new ID as they are probably corresponding to an ID that existed before and
                    // was not cleaned correctly.
                    $duplicates_iterator = $this->db->request([
                        'SELECT' => ['new.id'],
                        'FROM' => DisplayPreference::getTable() . ' AS new',
                        'INNER JOIN' => [
                            DisplayPreference::getTable() . ' AS old' => [
                                'ON' => [
                                    'new' => 'itemtype',
                                    'old' => 'itemtype',
                                    [
                                        'AND' => [
                                            'new.users_id' => new QueryExpression($this->db::quoteName('old.users_id')),
                                            'new.itemtype' => $itemtype,
                                            'new.num' => $new_search_opt,
                                            'old.num' => $old_search_opt,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]);
                    if ($duplicates_iterator->count() > 0) {
                        $ids = array_column(iterator_to_array($duplicates_iterator), 'id');
                        $this->db->delete(DisplayPreference::getTable(), ['id' => $ids]);
                    }
                }

                // Update display preferences
                if ($new_search_opt === null) {
                    $this->db->delete(DisplayPreference::getTable(), ['itemtype' => $itemtype, 'num' => $old_search_opt]);
                } else {
                    $this->db->update(DisplayPreference::getTable(), [
                        'num' => $new_search_opt,
                    ], [
                        'itemtype' => $itemtype,
                        'num' => $old_search_opt,
                    ]);
                }

                // Update template fields
                if (is_a($itemtype, 'CommonITILObject', true)) {
                    $tables = [
                        'glpi_' . strtolower($itemtype) . 'templatehiddenfields',
                        'glpi_' . strtolower($itemtype) . 'templatemandatoryfields',
                        'glpi_' . strtolower($itemtype) . 'templatepredefinedfields',
                    ];
                    foreach ($tables as $table) {
                        if (!$this->db->tableExists($table)) {
                            continue;
                        }
                        if ($new_search_opt === null) {
                            $this->db->delete($table, ['num' => $old_search_opt]);
                        } else {
                            $this->db->update($table, [
                                'num' => $new_search_opt,
                            ], [
                                'num' => $old_search_opt,
                            ]);
                        }
                    }
                }
            }
        }

        // Update saved searches. We have to parse every query to account for the search option in meta criteria
        $iterator = $this->db->request([
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
                        if (isset($query['sort']) && (int) $query['sort'] === $old_search_opt) {
                            if ($new_search_opt === null) {
                                unset($query['sort']);
                            } else {
                                $query['sort'] = $new_search_opt;
                            }
                            $is_changed = true;
                        }
                    }

                    // Fix criteria
                    if (isset($query['criteria'])) {
                        foreach ($query['criteria'] as $cid => $criterion) {
                            $is_meta = isset($criterion['meta']) && (int) $criterion['meta'] === 1;
                            if (
                                ($is_meta
                                 && isset($criterion['itemtype'], $criterion['field'])
                                 && $criterion['itemtype'] === $itemtype
                                 && (int) $criterion['field'] === $old_search_opt)
                                 || (!$is_meta
                                 && $data['itemtype'] === $itemtype
                                 && isset($criterion['field'])
                                 && (int) $criterion['field'] === $old_search_opt)
                            ) {
                                if ($new_search_opt === null) {
                                    unset($query['criteria'][$cid]);
                                } else {
                                    $query['criteria'][$cid]['field'] = $new_search_opt;
                                }
                                $is_changed = true;
                            }
                        }
                    }
                }
            }

            // Write changes if any were made
            if ($is_changed) {
                $this->db->update(SavedSearch::getTable(), [
                    'query'  => http_build_query($query),
                ], [
                    'id'     => $data['id'],
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
     * @param class-string<CommonDBTM> $class_1 First itemtype (CommonDBTM)
     * @param class-string<CommonDBTM> $class_2 Second itemtype (CommonDBTM)
     */
    public function createLinkTable(
        string $table,
        string $class_1,
        string $class_2
    ) {
        if ($this->db->tableExists($table)) {
            return;
        }

        $fk_1 = $class_1::getForeignKeyField();
        $fk_2 = $class_2::getForeignKeyField();

        $default_charset = $this->getDefaultCharset();
        $default_collation = $this->getDefaultCollation();
        $default_key_sign = $this->getDefaultPrimaryKeySignOption();

        $this->db->doQuery("
            CREATE TABLE `$table` (
                `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                `$fk_1` int {$default_key_sign} NOT NULL DEFAULT '0',
                `$fk_2` int {$default_key_sign} NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `$fk_1` (`$fk_1`),
                KEY `$fk_2` (`$fk_2`)
            ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;
        ");
    }

    /**
     * Add a crontask to register.
     *
     * @since 11.0.0
     *
     * @param string $itemtype Usually a class-string<CommonDBTM> but may be an itemtype that doesn't exist anymore for old migrations
     * @param string $name
     * @param int $frequency
     * @param int|null $param
     * @param array{mode?: int, state?: int, hourmin?: int, hourmax?: int, logs_lifetime?: int, allowmode?: int} $options
     */
    public function addCrontask(string $itemtype, string $name, int $frequency, ?int $param = null, array $options = []): void
    {
        $existing_task = $this->db->request([
            'FROM' => 'glpi_crontasks',
            'WHERE' => [
                'itemtype' => $itemtype,
                'name'     => $name,
            ],
        ]);
        if ($existing_task->count() !== 0) {
            // Cron task is already registered, do nothing.
            return;
        }

        $defaults = [
            'mode'          => 2, // CronTask::MODE_EXTERNAL
            'state'         => 1, // CronTask::STATE_WAITING
            'hourmin'       => 0,
            'hourmax'       => 24,
            'logs_lifetime' => 30,
            'allowmode'     => 3, // CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL
            'comment'       => '',
        ];

        $input = [
            'itemtype'      => $itemtype,
            'name'          => $name,
            'frequency'     => $frequency,
            'param'         => $param,
            'date_creation' => new QueryExpression('NOW()'),
            'date_mod'      => new QueryExpression('NOW()'),
        ];
        foreach ($defaults as $key => $default_value) {
            $input[$key] = $options[$key] ?? $default_value;
        }

        $this->db->insert('glpi_crontasks', $input);
    }

    /**
     * Mockable method to get the default collation of the database.
     * @return string
     * @see DBConnection::getDefaultCollation()
     * @note Could be removed when using dependency injection or some other refactoring
     */
    protected function getDefaultCollation(): string
    {
        return DBConnection::getDefaultCollation();
    }

    /**
     * Mockable method to get the default charset of the database.
     * @return string
     * @see DBConnection::getDefaultCharset()
     * @note Could be removed when using dependency injection or some other refactoring
     */
    protected function getDefaultCharset(): string
    {
        return DBConnection::getDefaultCharset();
    }

    /**
     * Mockable method to get the default key sign of the database.
     * @return string
     * @see DBConnection::getDefaultPrimaryKeySignOption()
     * @note Could be removed when using dependency injection or some other refactoring
     */
    protected function getDefaultPrimaryKeySignOption(): string
    {
        return DBConnection::getDefaultPrimaryKeySignOption();
    }
}
