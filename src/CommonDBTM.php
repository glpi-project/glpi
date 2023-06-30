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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Glpi\Features\CacheableListInterface;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\RichText\UserMention;
use Glpi\Socket;
use Glpi\Toolbox\Sanitizer;

/**
 * Common DataBase Table Manager Class - Persistent Object
 */
class CommonDBTM extends CommonGLPI
{
    /**
     * Data fields of the Item.
     *
     * @var mixed[]
     */
    public $fields = [];

    /**
     * Add/Update fields input. Filled during add/update process.
     *
     * @var mixed[]
     */
    public $input = [];

    /**
     * Updated fields keys. Filled during update process.
     *
     * @var mixed[]
     */
    public $updates = [];

    /**
     * Previous values of updated fields. Filled during update process.
     *
     * @var mixed[]
     */
    public $oldvalues = [];


    /**
     * Flag to determine whether or not changes must be logged into history.
     *
     * @var boolean
     */
    public $dohistory = false;

    /**
     * List of fields that must not be taken into account when logging history or computating last
     * modification date.
     *
     * @var string[]
     */
    public $history_blacklist = [];

    /**
     * Flag to determine whether or not automatic messages must be generated on actions.
     *
     * @var boolean
     */
    public $auto_message_on_action = true;

    /**
     * Flag to determine whether or not a link to item form can be automatically generated via
     * self::getLink() method.
     *
     * @var boolean
     */
    public $no_form_page = false;

    /**
     * Flag to determine whether or not table name of item can be automatically generated via
     * self::getTable() method.
     *
     * @var boolean
     */
    protected static $notable = false;

    /**
     * List of fields that must not be taken into account for dictionnary processing.
     *
     * @var string[]
     */
    public $additional_fields_for_dictionnary = [];

    /**
     * List of linked item types on which entities information should be forwarded on update.
     *
     * @var string[]
     */
    protected static $forward_entity_to = [];

    /**
     * Foreign key field cache : set dynamically calling getForeignKeyField
     *
     * @TODO Remove this variable as it is not used ?
     */
    protected $fkfield = "";

    /**
     * Search option of item. Initialized on first call to self::getOptions() and used as cache.
     *
     * @var array
     *
     * @TODO Should be removed and replaced by real cache usage.
     */
    protected $searchopt = false;

    /**
     * {@inheritDoc}
     */
    public $taborientation = 'vertical';

    /**
     * {@inheritDoc}
     */
    public $get_item_to_display_tab = true;

    /**
     * List of linked item types from plugins on which entities information should be forwarded on update.
     *
     * @var array
     */
    protected static $plugins_forward_entity = [];

    /**
     * Flag to determine whether or not table name of item has a notepad.
     *
     * @var boolean
     */
    protected $usenotepad = false;

    /**
     * Flag to determine whether or not queued notifications should be deduplicated.
     * Deduplication is done when a new notification is raised.
     * Any existing notification for same object, event and recipient is dropped to be replaced by the new one.
     *
     * @var boolean
     */
    public $deduplicate_queued_notifications = true;

    /**
     * Computed/forced values of classes tables.
     * @var string[]
     */
    protected static $tables_of = [];

    /**
     * Computed values of classes foreign keys.
     * @var string[]
     */
    protected static $foreign_key_fields_of = [];


    /**
     * Fields to remove when querying data with api
     * @var array
     */
    public static $undisclosedFields = [];

    /**
     * Current right that can be evaluated in "item_can" hook.
     * Variable is set prior to hook call then unset.
     * @var int
     */
    public $right;


    /**
     * Return the table used to store this object
     *
     * @param string $classname Force class (to avoid late_binding on inheritance)
     *
     * @return string
     **/
    public static function getTable($classname = null)
    {
        if ($classname === null) {
            $classname = get_called_class();
        }

        if (!class_exists($classname) || $classname::$notable) {
            return '';
        }

        if (!isset(self::$tables_of[$classname]) || empty(self::$tables_of[$classname])) {
            self::$tables_of[$classname] = getTableForItemType($classname);
        }

        return self::$tables_of[$classname];
    }


    /**
     * force table value (used for config management for old versions)
     *
     * @param string $table name of the table to be forced
     *
     * @return void
     **/
    public static function forceTable($table)
    {
        self::$tables_of[get_called_class()] = $table;
    }


    public static function getForeignKeyField()
    {
        $classname = get_called_class();

        if (
            !isset(self::$foreign_key_fields_of[$classname])
            || empty(self::$foreign_key_fields_of[$classname])
        ) {
            self::$foreign_key_fields_of[$classname] = getForeignKeyFieldForTable(static::getTable());
        }

        return self::$foreign_key_fields_of[$classname];
    }

    /**
     * Return SQL path to access a field.
     *
     * @param string      $field     Name of the field (or SQL keyword like '*')
     * @param string|null $classname Forced classname (to avoid late_binding on inheritance)
     *
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     **/
    public static function getTableField($field, $classname = null)
    {

        if (empty($field)) {
            throw new \InvalidArgumentException('Argument $field cannot be empty.');
        }

        $tablename = self::getTable($classname);
        if (empty($tablename)) {
            throw new \LogicException('Invalid table name.');
        }

        return sprintf('%s.%s', $tablename, $field);
    }

    /**
     * Retrieve an item from the database
     *
     * @param integer $ID ID of the item to get
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDB($ID)
    {
        global $DB;
       // Make new database object and fill variables

       // != 0 because 0 is considered as empty
        if (strlen((string)$ID) == 0) {
            return false;
        }

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                $this->getTable() . '.' . $this->getIndexName() => Toolbox::cleanInteger($ID)
            ],
            'LIMIT'  => 1
        ]);

        if (count($iterator) == 1) {
            $this->fields = $iterator->current();
            $this->post_getFromDB();
            return true;
        } else if (count($iterator) > 1) {
            trigger_error(
                sprintf(
                    'getFromDB expects to get one result, %1$s found in query "%2$s".',
                    count($iterator),
                    $iterator->getSql()
                ),
                E_USER_WARNING
            );
        }

        return false;
    }


    /**
     * Hydrate an object from a resultset row
     *
     * @param array $rs The row
     *
     * @return void
     */
    public function getFromResultSet($rs)
    {
       //just set fields!
        $this->fields = $rs;
    }


    /**
     * Generator to browse object from an iterator
     * @see http://php.net/manual/en/language.generators.syntax.php
     *
     * @since 9.2
     *
     * @param DBmysqlIterator $iter Iterator instance
     *
     * @return CommonDBTM
     */
    public static function getFromIter(DBmysqlIterator $iter)
    {
        $item = new static();

        foreach ($iter as $row) {
            if (!isset($row["id"])) {
                continue;
            }
            if ($item->getFromDB($row["id"])) {
                yield $item;
            }
        }
    }


    /**
     * Get an object using some criteria
     *
     * @since 9.2
     *
     * @param array $crit search criteria
     *
     * @return boolean|array
     */
    public function getFromDBByCrit(array $crit)
    {
        global $DB;

        $crit = ['SELECT' => 'id',
            'FROM'   => $this->getTable(),
            'WHERE'  => $crit
        ];

        $iter = $DB->request($crit);
        if (count($iter) == 1) {
            $row = $iter->current();
            return $this->getFromDB($row['id']);
        } else if (count($iter) > 1) {
            trigger_error(
                sprintf(
                    'getFromDBByCrit expects to get one result, %1$s found in query "%2$s".',
                    count($iter),
                    $iter->getSql()
                ),
                E_USER_WARNING
            );
        }
        return false;
    }


    /**
     * Retrieve an item from the database by request. The request is an array
     * similar to the one expected in DB::request().
     *
     * @since 9.3
     *
     * @see DB::request()
     *
     * @param array $request expression
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBByRequest(array $request)
    {
        global $DB;

       // Limit the request to the useful expressions
        $request = array_diff_key($request, [
            'FROM' => '',
            'SELECT' => '',
            'COUNT' => '',
            'GROUPBY' => '',
        ]);
        $request['FROM'] = $this->getTable();
        $request['SELECT'] = $this->getTable() . '.*';

        $iterator = $DB->request($request);
        if (count($iterator) == 1) {
            $this->fields = $iterator->current();
            $this->post_getFromDB();
            return true;
        } else if (count($iterator) > 1) {
            trigger_error(
                sprintf(
                    'getFromDBByRequest expects to get one result, %1$s found in query "%2$s".',
                    count($iterator),
                    $iterator->getSql()
                ),
                E_USER_WARNING
            );
        }
        return false;
    }

    /**
     * Get the identifier of the current item
     *
     * @return integer ID
     **/
    public function getID()
    {

        if (isset($this->fields[static::getIndexName()])) {
            return (int)$this->fields[static::getIndexName()];
        }
        return -1;
    }


    /**
     * Actions done at the end of the getFromDB function
     *
     * @return void
     **/
    public function post_getFromDB()
    {
    }


    /**
     * Print the item generic form
     * Use a twig template to detect automatically fields and display them in a two column layout
     *
     * @param int   $ID        ID of the item
     * @param array $options   possible optional options:
     *     - target for the Form
     *     - withtemplate : 1 for newtemplate, 2 for newobject from template
     *
     * @return bool true if displayed  false if item not found or not right to display
     */
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $new_item = static::isNewID($ID);
        $in_modal = (bool) ($_GET['_in_modal'] ?? false);
        TemplateRenderer::getInstance()->display('generic_show_form.html.twig', [
            'item'   => $this,
            'params' => $options,
            'no_header' => !$new_item && !$in_modal
        ]);
        return true;
    }


    public function getSNMPCredential()
    {
        if ($this->isField('snmpcredentials_id') && $this->fields['snmpcredentials_id']) {
            $snmp_credential = new SNMPCredential();
            $snmp_credential->getFromDB($this->fields['snmpcredentials_id']);
            return $snmp_credential;
        }
        return false;
    }


    /**
     * Retrieve locked field for the current item
     *
     * @return array
     */
    public function getLockedFields()
    {
        $locks = [];
        $lockedfield = new Lockedfield();
        if (
            !$this instanceof Lockedfield
            && !$this->isNewItem()
            && $lockedfield->isHandled($this)
        ) {
            $locks = $lockedfield->getLockedValues($this->getType(), $this->fields['id']);
        }

        return $locks;
    }


    /**
     * Actions done to not show some fields when getting a single item from API calls
     *
     * @param array $fields Fields to unset undiscloseds
     *
     * @return void
     */
    public static function unsetUndisclosedFields(&$fields)
    {
        foreach (static::$undisclosedFields as $key) {
            unset($fields[$key]);
        }
    }


    /**
     * Retrieve all items from the database
     *
     * @param array        $condition condition used to search if needed (empty get all) (default '')
     * @param array|string $order     order field if needed (default '')
     * @param integer      $limit     limit retrieved data if needed (default '')
     *
     * @return array all retrieved data in a associative array by id
     **/
    public function find($condition = [], $order = [], $limit = null)
    {
        global $DB;

        $criteria = [
            'FROM'   => $this->getTable()
        ];

        if (count($condition)) {
            $criteria['WHERE'] = $condition;
        }

        if (!is_array($order)) {
            $order = [$order];
        }
        if (count($order)) {
            $criteria['ORDERBY'] = $order;
        }

        if ((int)$limit > 0) {
            $criteria['LIMIT'] = (int)$limit;
        }

        $data = [];
        $iterator = $DB->request($criteria);
        foreach ($iterator as $line) {
            $data[$line['id']] = $line;
        }

        return $data;
    }


    /**
     * Get the name of the index field
     *
     * @return string name of the index field
     **/
    public static function getIndexName()
    {
        return "id";
    }


    /**
     * Get an empty item
     *
     *@return boolean true if succeed else false
     **/
    public function getEmpty()
    {
        global $DB;

       //make an empty database object
        $table = $this->getTable();

        if (
            !empty($table) &&
            ($fields = $DB->listFields($table))
        ) {
            foreach (array_keys($fields) as $key) {
                $this->fields[$key] = "";
            }
        } else {
            return false;
        }

        if (
            array_key_exists('entities_id', $this->fields)
            && isset($_SESSION["glpiactive_entity"])
        ) {
            $this->fields['entities_id'] = $_SESSION["glpiactive_entity"];
        }

        $this->post_getEmpty();

       // Call the plugin hook - $this->fields can be altered
        Plugin::doHook(Hooks::ITEM_EMPTY, $this);
        return true;
    }


    /**
     * Actions done at the end of the getEmpty function
     *
     * @return void
     **/
    public function post_getEmpty()
    {
    }


    /**
     * Get type to register log on
     *
     * @since 0.83
     *
     * @return array array of type + ID
     **/
    public function getLogTypeID()
    {
        return [$this->getType(), $this->fields['id']];
    }


    /**
     * Update the item in the database
     *
     * @param string[] $updates   fields to update
     * @param string[] $oldvalues array of old values of the updated fields
     *
     * @return bool
     **/
    public function updateInDB($updates, $oldvalues = [])
    {
        global $DB;

        $tobeupdated = [];
        foreach ($updates as $field) {
            if (isset($this->fields[$field])) {
                if (isset($oldvalues[$field]) && $this->fields[$field] == $oldvalues[$field]) {
                    unset($oldvalues[$field]);
                }
                $tobeupdated[$field] = $this->fields[$field];
            } else {
                // Clean oldvalues
                unset($oldvalues[$field]);
            }
        }
        $result = $DB->update(
            $this->getTable(),
            $tobeupdated,
            ['id' => $this->fields['id']]
        );
        if ($result === false) {
            return false;
        }
        $affected_rows = $DB->affectedRows();

        if (count($oldvalues) && $affected_rows > 0) {
            Log::constructHistory($this, $oldvalues, $this->fields);
            $this->getFromDB($this->fields[$this->getIndexName()]);
        }

        return ($affected_rows >= 0);
    }


    /**
     * Add an item to the database
     *
     * @return integer|boolean new ID of the item is insert successfull else false
     **/
    public function addToDB()
    {
        global $DB;

        $nb_fields = count($this->fields);
        if ($nb_fields > 0) {
            $params = [];
            foreach ($this->fields as $key => $value) {
                //FIXME: why is that handled here?
                if (($this->getType() == 'ProfileRight') && ($value == '')) {
                    $value = 0;
                }
                $params[$key] = $value;
            }

            $result = $DB->insert($this->getTable(), $params);
            if ($result) {
                if (
                    !isset($this->fields['id'])
                    || is_null($this->fields['id'])
                    || ($this->fields['id'] == 0)
                ) {
                    $this->fields['id'] = $DB->insertId();
                }

                $this->getFromDB($this->fields[$this->getIndexName()]);

                return $this->fields['id'];
            }
        }
        return false;
    }


    /**
     * Restore item = set deleted flag to 0
     *
     * @return boolean true if succeed else false
     **/
    public function restoreInDB()
    {
        global $DB;

        if ($this->maybeDeleted()) {
            $params = ['is_deleted' => 0];
           // Auto set date_mod if exsist
            if (isset($this->fields['date_mod'])) {
                $params['date_mod'] = $_SESSION["glpi_currenttime"];
            }

            if ($DB->update($this->getTable(), $params, ['id' => $this->fields['id']])) {
                return true;
            }
        }
        return false;
    }


    /**
     * Mark deleted or purge an item in the database
     *
     * @param boolean $force force the purge of the item (not used if the table do not have a deleted field)
     *               (default 0)
     *
     * @return boolean true if succeed else false
     **/
    public function deleteFromDB($force = 0)
    {
        global $DB;

        if (
            ($force == 1)
            || !$this->maybeDeleted()
            || ($this->useDeletedToLockIfDynamic()
              && !$this->isDynamic())
        ) {
            $this->cleanDBonPurge();
            if ($this instanceof CommonDropdown) {
                $this->cleanTranslations();
            }
            $this->cleanHistory();
            $this->cleanRelationData();
            $this->cleanRelationTable();

            $result = $DB->delete(
                $this->getTable(),
                [
                    'id' => $this->fields['id']
                ]
            );
            if ($result) {
                $this->post_deleteFromDB();
                if ($this instanceof CacheableListInterface) {
                    $this->invalidateListCache();
                }
                return true;
            }
        } else {
           // Auto set date_mod if exsist
            $toadd = [];
            if (isset($this->fields['date_mod'])) {
                $toadd['date_mod'] = $_SESSION["glpi_currenttime"];
            }

            $result = $DB->update(
                $this->getTable(),
                [
                    'is_deleted' => 1
                ] + $toadd,
                [
                    'id' => $this->fields['id']
                ]
            );
            $this->cleanDBonMarkDeleted();

            if ($result) {
                return true;
            }
        }

        return false;
    }


    /**
     * Clean data in the tables which have linked the deleted item
     *
     * @return void
     **/
    public function cleanHistory()
    {
        global $DB;

        if ($this->dohistory) {
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'  => $this->getType(),
                    'items_id'  => $this->fields['id']
                ]
            );
        }
    }


    /**
     * Detach items related to current item.
     * Related items will be either:
     * - attached to replacement item having ID specified in `_replace_by` input;
     * - detached from the item (foreign key field will be set to empty).
     *
     * @FIXME Method should be renamed to reflect its precise role (e.g. `detachRelatedItems()`).
     *
     * @return void
     **/
    public function cleanRelationData()
    {
        global $DB;

        $RELATION = getDbRelations();
        if (isset($RELATION[$this->getTable()])) {
            $newval = (isset($this->input['_replace_by']) ? (int)$this->input['_replace_by'] : 0);

            foreach ($RELATION[$this->getTable()] as $tablename => $fields) {
                if ($tablename[0] == '_') {
                    // Relation in tables prefixed by `_` are manualy handled.
                    continue;
                }

                $itemtype = getItemTypeForTable($tablename);
                if (!is_a($itemtype, CommonDBTM::class, true)) {
                    trigger_error(
                        sprintf('Unable to update relations between %s and %s tables.', $this->getTable(), $tablename),
                        E_USER_WARNING
                    );
                    continue;
                }

                $id_field = $itemtype::getIndexName();

                foreach ($fields as $field) {
                    if (is_array($field)) {
                        // Relation based on 'itemtype'/'items_id' (polymorphic relationship)
                        if ($itemtype instanceof IPAddress && in_array('mainitemtype', $field) && in_array('mainitems_id', $field)) {
                            // glpi_ipaddresses relationship that does not respect naming conventions
                            $itemtype_field = 'mainitemtype';
                            $items_id_field = 'mainitems_id';
                        } else {
                            $itemtype_matches = preg_grep('/^itemtype/', $field);
                            $items_id_matches = preg_grep('/^items_id/', $field);
                            $itemtype_field = reset($itemtype_matches);
                            $items_id_field = reset($items_id_matches);
                        }
                        $criteria = [
                            $itemtype_field => $this->getType(),
                            $items_id_field => $this->getID(),
                        ];
                        $update = [
                            $items_id_field => $newval,
                        ];
                        if ($newval === 0) {
                            $update[$itemtype_field] = 'NULL';
                        }
                    } else {
                        // Relation based on single foreign key
                        $criteria = [
                            $field => $this->getID(),
                        ];
                        $update = [
                            $field => $newval,
                        ];
                    }

                    $result = $DB->request(
                        [
                            'FROM'  => $tablename,
                            'WHERE' => $criteria,
                        ]
                    );
                    foreach ($result as $data) {
                        $item = new $itemtype();
                        $input =  [
                            $id_field       => $data[$id_field],
                            '_disablenotif' => true,
                        ] + $update;

                        //prevent lock if item is dynamic
                        //as the dictionary rules are played out during the inventory anyway
                        if (isset($data['is_dynamic'])) {
                            $input['is_dynamic'] = $data['is_dynamic'];
                        }

                        $item->update($input);
                    }
                }
            }
        }
    }


    /**
     * Actions done after the DELETE of the item in the database
     *
     * @return void
     **/
    public function post_deleteFromDB()
    {
    }


    /**
     * Actions done when item is deleted from the database
     *
     * @return void
     **/
    public function cleanDBonPurge()
    {
    }


    /**
     * Delete children items and relation with other items from database.
     *
     * @param array $relations_classes List of classname on which deletion will be done
     *                                 Classes needs to extends CommonDBConnexity.
     *
     * @return void
     **/
    protected function deleteChildrenAndRelationsFromDb(array $relations_classes)
    {

        foreach ($relations_classes as $classname) {
            if (!is_a($classname, CommonDBConnexity::class, true)) {
                trigger_error(
                    sprintf(
                        'Unable to clean elements of class %s as it does not extends "CommonDBConnexity"',
                        $classname
                    ),
                    E_USER_WARNING
                );
                continue;
            }

            /** @var CommonDBConnexity $relation_item */
            $relation_item = new $classname();
            $relation_item->cleanDBonItemDelete($this->getType(), $this->fields['id']);
        }
    }


    /**
     * Clean translations associated to a dropdown
     *
     * @since 0.85
     *
     * @return void
     **/
    public function cleanTranslations()
    {

       //Do not try to clean is dropdown translation is globally off
        if (DropdownTranslation::isDropdownTranslationActive()) {
            $translation = new DropdownTranslation();
            $translation->deleteByCriteria(['itemtype' => get_class($this),
                'items_id' => $this->getID()
            ]);
        }
    }


    /**
     * Purge items related to current item.
     *
     * @FIXME Method should be renamed to reflect its precise role (e.g. `purgeRelatedItems()`).
     *
     * @return void
     */
    public function cleanRelationTable()
    {
        global $CFG_GLPI, $DB;

        if (in_array($this->getType(), $CFG_GLPI['agent_types'])) {
           // Agent does not extends CommonDBConnexity
            $agent = new Agent();
            $agent->deleteByCriteria(['itemtype' => $this->getType(), 'items_id' => $this->getID()]);
        }

        if (in_array($this->getType(), $CFG_GLPI['itemdevices_types'])) {
            Item_Devices::cleanItemDeviceDBOnItemDelete(
                $this->getType(),
                $this->getID(),
                !empty($this->input['keep_devices'])
            );
        }

        if (in_array($this->getType(), $CFG_GLPI['networkport_types'])) {
            // Manage networkportmigration if exists
            if ($DB->tableExists('glpi_networkportmigrations')) {
                $networkPortMigObject = new NetworkPortMigration();
                $networkPortMigObject->cleanDBonItemDelete($this->getType(), $this->getID());
            }
        }

       // If this type have NOTEPAD, clean one associated to purged item
        if ($this->usenotepad) {
            $note = new Notepad();
            $note->cleanDBonItemDelete($this->getType(), $this->fields['id']);
        }

        if (in_array($this->getType(), $CFG_GLPI['ticket_types'])) {
            // Clean ticket open against the item
            $job         = new Ticket();
            $itemsticket = new Item_Ticket();

            $iterator = $DB->request([
                'FROM'   => 'glpi_items_tickets',
                'WHERE'  => [
                    'items_id'  => $this->getID(),
                    'itemtype'  => $this->getType()
                ]
            ]);

            foreach ($iterator as $data) {
                $cnt = countElementsInTable('glpi_items_tickets', ['tickets_id' => $data['tickets_id']]);
                $itemsticket->delete(["id" => $data["id"]]);
                if ($cnt == 1 && !$CFG_GLPI["keep_tickets_on_delete"]) {
                    $job->delete(["id" => $data["tickets_id"]]);
                }
            }
        }

        $lockedfield = new Lockedfield();
        if ($lockedfield->isHandled($this)) {
            $lockedfield->itemDeleted();
        }

        // Delete relation items and child items from DB
        $polymorphic_types_mapping = [
            Appliance_Item::class          => $CFG_GLPI['appliance_types'],
            Appliance_Item_Relation::class => $CFG_GLPI['appliance_relation_types'],
            Certificate_Item::class        => $CFG_GLPI['certificate_types'],
            Change_Item::class             => $CFG_GLPI['ticket_types'],
            Computer_Item::class           => $CFG_GLPI['directconnect_types'],
            Consumable::class              => $CFG_GLPI['consumables_types'],
            Contract_Item::class           => $CFG_GLPI['contract_types'],
            Document_Item::class           => \Document::getItemtypesThatCanHave(),
            Domain_Item::class             => $CFG_GLPI['domain_types'],
            Infocom::class                 => \Infocom::getItemtypesThatCanHave(),
            Item_Cluster::class            => $CFG_GLPI['cluster_types'],
            Item_Disk::class               => $CFG_GLPI['disk_types'],
            Item_Enclosure::class          => $CFG_GLPI['rackable_types'],
            Item_Kanban::class             => $CFG_GLPI['kanban_types'],
            Item_OperatingSystem::class    => $CFG_GLPI['operatingsystem_types'],
            Item_Problem::class            => $CFG_GLPI['ticket_types'],
            Item_Project::class            => $CFG_GLPI['project_asset_types'],
            Item_Rack::class               => $CFG_GLPI['rackable_types'],
            Item_SoftwareLicense::class    => $CFG_GLPI['software_types'],
            Item_SoftwareVersion::class    => $CFG_GLPI['software_types'],
            // specific case, see above Item_Ticket::class             => $CFG_GLPI['ticket_types'],
            KnowbaseItem_Item::class       => $CFG_GLPI['kb_types'],
            NetworkPort::class             => $CFG_GLPI['networkport_types'],
            ReservationItem::class         => $CFG_GLPI['reservation_types'],
            Socket::class                   => $CFG_GLPI['socket_types'],
            VObject::class                 => $CFG_GLPI['planning_types'],
        ];

        $to_delete = [];
        foreach ($polymorphic_types_mapping as $target_itemtype => $source_itemtypes) {
            if (in_array($this->getType(), $source_itemtypes)) {
                $to_delete[] = $target_itemtype;
            }
        }
        $this->deleteChildrenAndRelationsFromDb($to_delete);
    }


    /**
     * Actions done when item flag deleted is set to an item
     *
     * @return void
     **/
    public function cleanDBonMarkDeleted()
    {
    }


    /**
     * Save the input data in the Session
     *
     * @since 0.84
     *
     * @return void
     **/
    protected function saveInput()
    {
        $_SESSION['saveInput'][$this->getType()] = $this->input;
    }


    /**
     * Clear the saved data stored in the session
     *
     * @since 0.84
     *
     * @return void
     **/
    protected function clearSavedInput()
    {
        unset($_SESSION['saveInput'][$this->getType()]);
    }


    /**
     * Get the data saved in the session
     *
     * @since 0.84
     *
     * @param array $default Array of value used if session is empty
     *
     * @return array Array of value
     **/
    protected function restoreInput(array $default = [])
    {

        if (isset($_SESSION['saveInput'][$this->getType()])) {
            $saved = Html::cleanPostForTextArea($_SESSION['saveInput'][$this->getType()]);

           // clear saved data when restored (only need once)
            $this->clearSavedInput();

            return $saved;
        }

        return $default;
    }

    /**
     * Restore data saved in the session to $this->input
     *
     * @since 9.5.5
     *
     * @param array $saved Array of values saved in session
     *
     * @return void
     **/
    protected function restoreSavedValues(array $saved = [])
    {
        if (count($saved)) {
           //restore saved values as input (to manage uploaded img)
            $this->input = $saved;

            foreach ($saved as $name => $value) {
                if (
                    $this instanceof CommonITILObject
                    && $name === 'status'
                    && !CommonITILObject::isAllowedStatus($this->fields[$name], $value)
                ) {
                    continue;
                }
                if (isset($this->fields[$name])) {
                    $this->fields[$name] = $saved[$name];
                }
            }
        }
    }


   // Common functions
    /**
     * Add an item in the database with all it's items.
     *
     * @param array   $input   the _POST vars returned by the item form when press add
     * @param array   $options with the insert options
     *   - unicity_message : do not display message if item it a duplicate (default is yes)
     * @param boolean $history do history log ? (true by default)
     *
     * @return integer the new ID of the added item (or false if fail)
     **/
    public function add(array $input, $options = [], $history = true)
    {
        global $DB, $CFG_GLPI;

        if ($DB->isSlave()) {
            return false;
        }

        // This means we are not adding a cloned object
        if (
            (!Toolbox::hasTrait($this, \Glpi\Features\Clonable::class) || !isset($input['clone']))
            && method_exists($this, 'clone')
        ) {
            // This means we are asked to clone the object (old way). This will clone the clone method
            // that will set the clone parameter to true
            if (isset($input['_oldID'])) {
                $id_to_clone = $input['_oldID'];
            }
            if (isset($input['id'])) {
                $id_to_clone = $input['id'];
            }
            if (isset($id_to_clone) && $this->getFromDB($id_to_clone)) {
                if ($clone_id = $this->clone($input, $history)) {
                    $this->getFromDB($clone_id); // Load created items fields
                }
                return $clone_id;
            }
        }

        // Store input in the object to be available in all sub-method / hook
        $this->input = $input;

        // Manage the _no_history
        if (!isset($this->input['_no_history'])) {
            $this->input['_no_history'] = !$history;
        }

        if (isset($this->input['add'])) {
            // Input from the interface
            // Save this data to be available if add fail
            $this->saveInput();
        }

        if (isset($this->input['add'])) {
            $this->input['_add'] = $this->input['add'];
            unset($this->input['add']);
        }

        // Call the plugin hook - $this->input can be altered
        // This hook get the data from the form, not yet altered
        Plugin::doHook(Hooks::PRE_ITEM_ADD, $this);

        if ($this->input && is_array($this->input)) {
            $this->input = $this->prepareInputForAdd($this->input);
        }

        if ($this->input && is_array($this->input)) {
            // Call the plugin hook - $this->input can be altered
            // This hook get the data altered by the object method
            Plugin::doHook(Hooks::POST_PREPAREADD, $this);
        }

        if ($this->input && is_array($this->input)) {
           //Check values to inject
            $this->filterValues(!isCommandLine());
        }

        //Process business rules for assets
        $this->assetBusinessRules(\RuleAsset::ONADD);

        if ($this->input && is_array($this->input)) {
            $this->fields = [];
            $table_fields = $DB->listFields($this->getTable());

            $this->pre_addInDB();

            // fill array for add
            $this->cleanLockedsOnAdd();
            foreach (array_keys($this->input) as $key) {
                if (
                    ($key[0] != '_')
                    && isset($table_fields[$key])
                ) {
                    $this->fields[$key] = $this->input[$key];
                }
            }

            // Auto set date_creation if exsist
            if (isset($table_fields['date_creation']) && !isset($this->input['date_creation'])) {
                $this->fields['date_creation'] = $_SESSION["glpi_currenttime"];
            }

            // Auto set date_mod if exsist
            if (isset($table_fields['date_mod']) && !isset($this->input['date_mod'])) {
                $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
            }

            if ($this->checkUnicity(true, $options)) {
                if ($this->addToDB() !== false) {
                    $this->post_addItem();
                    if ($this instanceof CacheableListInterface) {
                        $this->invalidateListCache();
                    }
                    $this->addMessageOnAddAction();

                    if ($this->dohistory && $history) {
                        $changes = [
                            0,
                            '',
                            '',
                        ];
                        Log::history(
                            $this->fields["id"],
                            $this->getType(),
                            $changes,
                            0,
                            Log::HISTORY_CREATE_ITEM
                        );
                    }

                    // Auto create infocoms
                    if (
                        isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]
                        && (!isset($input['clone']) || !$input['clone'])
                        && Infocom::canApplyOn($this)
                    ) {
                        $ic = new Infocom();
                        if (!$ic->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                            $ic->add(['itemtype' => $this->getType(),
                                'items_id' => $this->fields['id']
                            ]);
                        }
                    }

                    // If itemtype is in infocomtype and if states_id field is filled
                    // and item is not a template
                    if (
                        Infocom::canApplyOn($this)
                        && isset($this->input['states_id'])
                            && (!isset($this->input['is_template'])
                                || !$this->input['is_template'])
                    ) {
                        //Check if we have to automatically fill dates
                        Infocom::manageDateOnStatusChange($this);
                    }
                    Plugin::doHook(Hooks::ITEM_ADD, $this);

                    // As add have succeeded, clean the old input value
                    if (isset($this->input['_add'])) {
                        $this->clearSavedInput();
                    }
                    return $this->fields['id'];
                }
            }
        }

        return false;
    }


    /**
     * Get the link to an item
     *
     * @param array $options array of options
     *    - comments     : boolean / display comments
     *    - complete     : boolean / display completename instead of name
     *    - additional   : boolean / display additionals information
     *    - linkoption   : string  / additional options to add to <a>
     *    - icon         : boolean  / display item icon next to label
     *
     * @return string HTML link
     **/
    public function getLink($options = [])
    {

        $p = [
            'linkoption' => '',
        ];

        if (isset($options['linkoption'])) {
            $p['linkoption'] = $options['linkoption'];
        }
        if (isset($options['icon'])) {
            $p['icon'] = $options['icon'];
        }

        if (!isset($this->fields['id'])) {
            return '';
        }

        if (
            $this->no_form_page
            || !$this->can($this->fields['id'], READ)
        ) {
            return $this->getNameID($options);
        }

        $link = $this->getLinkURL();

        $label = $this->getNameID($options);
        $title = '';
        if (!preg_match('/title=/', $p['linkoption'])) {
            $thename = $this->getName(['complete' => true]);
            $thename = Sanitizer::getVerbatimValue($thename); // Prevent double encoding of special chars
            if ($thename != NOT_AVAILABLE) {
                $title = ' title="' . htmlentities($thename, ENT_QUOTES, 'utf-8') . '"';
            }
        }

        return "<a " . $p['linkoption'] . " href='$link' $title>$label</a>";
    }


    /**
     * Get the link url to an item
     *
     * @return string HTML link
     **/
    public function getLinkURL()
    {

        if (!isset($this->fields['id'])) {
            return '';
        }

        $link  = $this->getFormURLWithID($this->getID());
        $link .= ($this->isTemplate() ? "&withtemplate=1" : "");

        return $link;
    }


    /**
     * Add a message on add action
     *
     * @return void
     **/
    public function addMessageOnAddAction()
    {

        $addMessAfterRedirect = false;
        if (isset($this->input['_add'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            $link = $this->getFormURL();
            if ($this->getName() == NOT_AVAILABLE) {
               //TRANS: %1$s is the itemtype, %2$d is the id of the item
                $this->fields['name'] = sprintf(
                    __('%1$s - ID %2$d'),
                    $this->getTypeName(1),
                    $this->fields['id']
                );
            }
            $opt = [ 'forceid' => $this instanceof CommonITILObject ];
            $display = (isset($this->input['_no_message_link']) ? $this->getNameID($opt)
                                                            : $this->getLink($opt));

           // Do not display quotes
           //TRANS : %s is the description of the added item
            Session::addMessageAfterRedirect(sprintf(
                __('%1$s: %2$s'),
                __('Item successfully added'),
                stripslashes($display)
            ));
        }
    }


    /**
     * Add needed information to $input (example entities_id)
     *
     * @param array $input datas used to add the item
     *
     * @since 0.84
     *
     * @return array the modified $input array
     **/
    public function addNeededInfoToInput($input)
    {
        return $input;
    }


    /**
     * Prepare input datas for adding the item
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForAdd($input)
    {
        return $input;
    }


    /**
     * Actions done after the ADD of the item in the database
     *
     * @return void
     **/
    public function post_addItem()
    {

        UserMention::handleUserMentions($this);
    }


    /**
     * Update some elements of an item in the database.
     *
     * @param array   $input   the _POST vars returned by the item form when press update
     * @param boolean $history do history log ? (default 1)
     * @param array   $options with the insert options
     *
     * @return boolean true on success
     **/
    public function update(array $input, $history = 1, $options = [])
    {
        global $DB, $GLPI_CACHE;

        if ($DB->isSlave()) {
            return false;
        }

        if (!array_key_exists(static::getIndexName(), $input)) {
            return false;
        }

        if (!$this->getFromDB($input[static::getIndexName()])) {
            return false;
        }

       // Store input in the object to be available in all sub-method / hook
        $this->input = $input;

       // Manage the _no_history
        if (!isset($this->input['_no_history'])) {
            $this->input['_no_history'] = !$history;
        }

        if (isset($this->input['update'])) {
           // Input from the interface
           // Save this data to be available if add fail
            $this->saveInput();
        }

        if (isset($this->input['update'])) {
            $this->input['_update'] = $this->input['update'];
            unset($this->input['update']);
        }

       // Plugin hook - $this->input can be altered
        Plugin::doHook(Hooks::PRE_ITEM_UPDATE, $this);
        if ($this->input && is_array($this->input)) {
            $this->input = $this->prepareInputForUpdate($this->input);
            $this->filterValues(!isCommandLine());
        }

       //Process business rules for assets
        $this->assetBusinessRules(\RuleAsset::ONUPDATE);

       // Valid input for update
        if ($this->checkUnicity(false, $options)) {
            if ($this->input && is_array($this->input)) {
               // Fill the update-array with changes
                $x               = 0;
                $this->updates   = [];
                $this->oldvalues = [];

                foreach (array_keys($this->input) as $key) {
                    if ($DB->fieldExists(static::getTable(), $key)) {
                      // Prevent history for date statement (for date for example)
                        if (
                            is_null($this->fields[$key])
                            && ($this->input[$key] == 'NULL')
                        ) {
                             $this->fields[$key] = 'NULL';
                        }
                      // Compare item
                        $ischanged = true;
                        $searchopt = $this->getSearchOptionByField('field', $key, $this->getTable());
                        if (isset($searchopt['datatype'])) {
                            switch ($searchopt['datatype']) {
                                case 'string':
                                case 'text':
                                    $ischanged = (strcmp(
                                        (string)$DB->escape($this->fields[$key]),
                                        (string)$this->input[$key]
                                    ) != 0);
                                    break;

                                case 'itemlink':
                                    if ($key == 'name') {
                                        $ischanged = (strcmp(
                                            (string)$DB->escape($this->fields[$key]),
                                            (string)$this->input[$key]
                                        ) != 0);
                                        break;
                                    }
                               // else default

                                default:
                                    $ischanged = ($DB->escape($this->fields[$key]) != $this->input[$key]);
                                    break;
                            }
                        } else {
                         // No searchoption case
                            $ischanged = ($DB->escape($this->fields[$key]) != $this->input[$key]);
                        }
                        if ($ischanged) {
                            if ($key != "id") {
                         // Store old values
                                if (!in_array($key, $this->history_blacklist)) {
                                     $this->oldvalues[$key] = $this->fields[$key];
                                }

                                $this->fields[$key] = $this->input[$key];
                                $this->updates[$x]  = $key;
                                $x++;
                            }
                        }
                    }
                }
                if (count($this->updates)) {
                    if (array_key_exists('date_mod', $this->fields)) {
                        // is a non blacklist field exists
                        if (count(array_diff($this->updates, $this->history_blacklist)) > 0) {
                            $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
                            $this->updates[$x++]      = 'date_mod';
                        }
                    }
                    $this->pre_updateInDB();

                    $this->cleanLockeds();
                    if (count($this->updates)) {
                        $updated = false;
                        if (
                            $updated = $this->updateInDB(
                                $this->updates,
                                ($this->dohistory && $history ? $this->oldvalues
                                : [])
                            )
                        ) {
                            $this->manageLocks();
                            $this->addMessageOnUpdateAction();
                            Plugin::doHook(Hooks::ITEM_UPDATE, $this);

                            //Fill forward_entity_to array with itemtypes coming from plugins
                            if (isset(self::$plugins_forward_entity[$this->getType()])) {
                                foreach (self::$plugins_forward_entity[$this->getType()] as $itemtype) {
                                    static::$forward_entity_to[] = $itemtype;
                                }
                            }
                           // forward entity information if needed
                            if (
                                count(static::$forward_entity_to)
                                && (in_array("entities_id", $this->updates)
                                || in_array("is_recursive", $this->updates))
                            ) {
                                 $this->forwardEntityInformations();
                            }

                           // If itemtype is in infocomtype and if states_id field is filled
                           // and item not a template
                            if (
                                Infocom::canApplyOn($this)
                                && in_array('states_id', $this->updates)
                                && ($this->getField('is_template') != NOT_AVAILABLE)
                            ) {
                               //Check if we have to automatical fill dates
                                Infocom::manageDateOnStatusChange($this, false);
                            }
                        }

                        if (!$updated) {
                            $this->restoreInput();
                            return $updated;
                        }
                    }
                }

                // As update have suceed, clean the old input value
                if (isset($this->input['_update'])) {
                    $this->clearSavedInput();
                }

                $this->post_updateItem($history);
                if ($this instanceof CacheableListInterface) {
                    $this->invalidateListCache();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Clean locked fields from add, if needed
     *
     * @return void
     */
    protected function cleanLockedsOnAdd()
    {
        if (isset($this->input['is_dynamic']) && $this->input['is_dynamic'] == true) {
            $lockedfield = new Lockedfield();
            $config = Config::getConfigurationValues('inventory');
            $locks = $lockedfield->getLockedNames($this->getType(), 0);
            foreach ($locks as $lock) {
                //bypass for states_id if default value is define from inventory conf
                if ($lock == 'states_id' && $config['states_id_default']) {
                    continue;
                }
                if (array_key_exists($lock, $this->input)) {
                    $lockedfield->setLastValue($this->getType(), 0, $lock, $this->input[$lock]);
                    unset($this->input[$lock]);
                }
            }
        }
    }


    /**
     * Clean locked fields from update, if needed
     *
     * @return void
     */
    protected function cleanLockeds()
    {
        if (
            (
                (
                    isset($this->input['_transfer'])
                    // lock updated fields in transfer only if requested
                    && (isset($this->input['_lock_updated_fields']) && $this->input['_lock_updated_fields'])
                )
                || !isset($this->input['_transfer'])
            )
            && $this->isDynamic()
            && (in_array('is_dynamic', $this->updates) || isset($this->input['is_dynamic'])
            && $this->input['is_dynamic'] == true)
        ) {
            $lockedfield = new Lockedfield();
            $locks = $lockedfield->getLockedNames($this->getType(), $this->fields['id']);
            foreach ($locks as $lock) {
                $idx = array_search($lock, $this->updates);
                if ($idx !== false) {
                    $lockedfield->setLastValue($this->getType(), $this->fields['id'], $lock, $this->input[$lock]);
                    unset($this->updates[$idx]);
                    unset($this->input[$lock]);
                    $this->fields[$lock] = $this->oldvalues[$lock];
                    unset($this->oldvalues[$lock]);
                }
            }
            $this->updates = array_values($this->updates);
        }
    }

    /**
     * Manage fields that should be marked as locked
     *
     * @return void
     */
    protected function manageLocks()
    {
        global $DB;

        $lockedfield = new Lockedfield();
        if (
            (
                (
                    isset($this->input['_transfer'])
                    // lock updated fields in transfer only if requested
                    && (isset($this->input['_lock_updated_fields']) && $this->input['_lock_updated_fields'])
                )
                || !isset($this->input['_transfer'])
            )
            && $lockedfield->isHandled($this)
            && (!isset($this->input['is_dynamic']) || $this->input['is_dynamic'] == false)
        ) {
            $fields = array_values($this->updates);
            $idx = array_search('date_mod', $fields);
            if ($idx !== false) {
                unset($fields[$idx]);
            }
            $stmt = $DB->prepare(
                $DB->buildInsert(
                    $lockedfield->getTable(),
                    [
                        'itemtype'        => $this->getType(),
                        'items_id'        => $this->fields['id'],
                        'date_creation'   => $_SESSION["glpi_currenttime"],
                        'field'           => new QueryParam()
                    ]
                )
            );
            foreach ($fields as $field) {
                 $stmt->bind_param('s', $field);
                 $res = $stmt->execute();
                if ($res === false) {
                    if ($DB->errno() != 1062) {
                        trigger_error('Unable to add locked field!', E_USER_WARNING);
                    }
                }
            }
        }
    }

    /**
     * Forward entity information to linked items
     *
     * @return void
     **/
    protected function forwardEntityInformations()
    {
        global $DB;

        if (!isset($this->fields['id']) || !($this->fields['id'] >= 0)) {
            return false;
        }

        if (count(static::$forward_entity_to)) {
            foreach (static::$forward_entity_to as $type) {
                $item  = new $type();
                $query = [
                    'SELECT' => ['id'],
                    'FROM'   => $item->getTable()
                ];

                $OR = [];
                if ($item->isField('itemtype')) {
                    $OR[] = [
                        'itemtype'  => $this->getType(),
                        'items_id'  => $this->getID()
                    ];
                }
                if ($item->isField($this->getForeignKeyField())) {
                    $OR[] = [$this->getForeignKeyField() => $this->getID()];
                }
                $query['WHERE'][] = ['OR' => $OR];

                $input = [
                    'entities_id'  => $this->getEntityID(),
                    '_transfer'    => 1
                ];
                if ($this->maybeRecursive()) {
                    $input['is_recursive'] = $this->isRecursive();
                }

                $iterator = $DB->request($query);
                foreach ($iterator as $data) {
                    $input['id'] = $data['id'];
                   // No history for such update
                    $item->update($input, 0);
                }
            }
        }
    }


    /**
     * Add a message on update action
     *
     * @return void
     **/
    public function addMessageOnUpdateAction()
    {

        $addMessAfterRedirect = false;

        if (isset($this->input['_update'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            // Do not display quotes
            if (isset($this->fields['name'])) {
                $this->fields['name'] = stripslashes($this->fields['name']);
            } else {
               //TRANS: %1$s is the itemtype, %2$d is the id of the item
                $this->fields['name'] = sprintf(
                    __('%1$s - ID %2$d'),
                    $this->getTypeName(1),
                    $this->fields['id']
                );
            }

            if (isset($this->input['_no_message_link'])) {
                $display = $this->getNameID();
            } else {
                $display = $this->getLink();
            }
           //TRANS : %s is the description of the updated item
            Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully updated'), $display));
        }
    }


    /**
     * Prepare input datas for updating the item
     *
     * @param array $input data used to update the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForUpdate($input)
    {
        return $input;
    }


    /**
     * Actions done after the UPDATE of the item in the database
     *
     * @param boolean $history store changes history ? (default 1)
     *
     * @return void
     **/
    public function post_updateItem($history = 1)
    {
        if (count($this->updates) > 0) {
            UserMention::handleUserMentions($this);
        }
    }


    /**
     * Actions done before the ADD of the item in the database
     *
     * @since 10.0.3
     *
     * @return void
     */
    public function pre_addInDB()
    {
    }


     /**
     * Actions done before the UPDATE of the item in the database
     *
     * @return void
     **/
    public function pre_updateInDB()
    {
    }


    /**
     * Delete an item in the database.
     *
     * @param array   $input   the _POST vars returned by the item form when press delete
     * @param boolean $force   force deletion (default 0)
     * @param boolean $history do history log ? (default 1)
     *
     * @return boolean true on success
     **/
    public function delete(array $input, $force = 0, $history = 1)
    {
        global $DB;

        if ($DB->isSlave()) {
            return false;
        }

        if (!$this->getFromDB($input[static::getIndexName()])) {
            return false;
        }

       // Force purge for templates / may not to be deleted / not dynamic lockable items
        if (
            $this->isTemplate()
            || !$this->maybeDeleted()
            // Do not take into account deleted field if maybe dynamic but not dynamic
            || ($this->useDeletedToLockIfDynamic()
              && !$this->isDynamic())
        ) {
            $force = 1;
        }

       // Store input in the object to be available in all sub-method / hook
        $this->input = $input;

        if (isset($this->input['purge'])) {
            $this->input['_purge'] = $this->input['purge'];
            unset($this->input['purge']);
        } else if ($force) {
            $this->input['_purge'] = 1;
            $this->input['_no_message'] = $this->input['_no_message'] ?? 1;
        }

        if (isset($this->input['delete'])) {
            $this->input['_delete'] = $this->input['delete'];
            unset($this->input['delete']);
        } else if (!$force) {
            $this->input['_delete'] = 1;
            $this->input['_no_message'] = $this->input['_no_message'] ?? 1;
        }

        if (!isset($this->input['_no_history'])) {
            $this->input['_no_history'] = !$history;
        }

        if ($force && method_exists($this, 'pre_purgeInventory')) {
            $this->pre_purgeInventory();
        }

       // Purge
        if ($force) {
            Plugin::doHook(Hooks::PRE_ITEM_PURGE, $this);
        } else {
            Plugin::doHook(Hooks::PRE_ITEM_DELETE, $this);
        }

        if (!is_array($this->input)) {
           // $input clear by a hook to cancel delete
            return false;
        }

        if ($this->pre_deleteItem()) {
            if ($this->deleteFromDB($force)) {
                if ($force) {
                    $this->addMessageOnPurgeAction();
                    $this->post_purgeItem();
                    if ($this instanceof CacheableListInterface) {
                        $this->invalidateListCache();
                    }
                    Plugin::doHook(Hooks::ITEM_PURGE, $this);
                    Impact::clean($this);
                } else {
                    $this->addMessageOnDeleteAction();

                    if ($this->dohistory && $history) {
                        $changes = [
                            0,
                            '',
                            '',
                        ];
                        $logaction  = Log::HISTORY_DELETE_ITEM;
                        if (
                            $this->useDeletedToLockIfDynamic()
                            && $this->isDynamic()
                        ) {
                            $logaction = Log::HISTORY_LOCK_ITEM;
                        }

                        Log::history(
                            $this->fields["id"],
                            $this->getType(),
                            $changes,
                            0,
                            $logaction
                        );
                    }
                    $this->post_deleteItem();
                    if ($this instanceof CacheableListInterface) {
                        $this->invalidateListCache();
                    }
                    Plugin::doHook(Hooks::ITEM_DELETE, $this);
                }

                return true;
            }
        }
        return false;
    }


    /**
     * Actions done after the DELETE (mark as deleted) of the item in the database
     *
     * @return void
     **/
    public function post_deleteItem()
    {
    }


    /**
     * Actions done after the PURGE of the item in the database
     *
     * @return void
     **/
    public function post_purgeItem()
    {
    }


    /**
     * Add a message on delete action
     *
     * @return void
     **/
    public function addMessageOnDeleteAction()
    {

        if (!$this->maybeDeleted()) {
            return;
        }

        $addMessAfterRedirect = false;
        if (isset($this->input['_delete'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            if (isset($this->input['_no_message_link'])) {
                $display = $this->getNameID();
            } else {
                $display = $this->getLink();
            }
           //TRANS : %s is the description of the updated item
            Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully deleted'), $display));
        }
    }


    /**
     * Add a message on purge action
     *
     * @return void
     **/
    public function addMessageOnPurgeAction()
    {

        $addMessAfterRedirect = false;

        if (
            isset($this->input['_purge'])
            || isset($this->input['_delete'])
        ) {
            $addMessAfterRedirect = true;
        }

        if (isset($this->input['_purge'])) {
            $this->input['_no_message_link'] = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            if (isset($this->input['_no_message_link'])) {
                $display = $this->getNameID();
            } else {
                $display = $this->getLink();
            }
           //TRANS : %s is the description of the updated item
            Session::addMessageAfterRedirect(sprintf(
                __('%1$s: %2$s'),
                __('Item successfully purged'),
                $display
            ));
        }
    }


    /**
     * Actions done before the DELETE of the item in the database /
     * Maybe used to add another check for deletion
     *
     * @return boolean true if item need to be deleted else false
     **/
    public function pre_deleteItem()
    {
        return true;
    }


    /**
     * Restore an item put in the trashbin in the database.
     *
     * @param array   $input   the _POST vars returned by the item form when press restore
     * @param boolean $history do history log ? (default 1)
     *
     * @return boolean true on success
     **/
    public function restore(array $input, $history = 1)
    {

        if (!$this->getFromDB($input[static::getIndexName()])) {
            return false;
        }

        if (isset($input['restore'])) {
            $input['_restore'] = $input['restore'];
            unset($input['restore']);
        } else {
            $this->input['_restore'] = 1;
            $this->input['_no_message'] = $this->input['_no_message'] ?? 1;
        }

       // Store input in the object to be available in all sub-method / hook
        $this->input = $input;
        Plugin::doHook(Hooks::PRE_ITEM_RESTORE, $this);
        if (!is_array($this->input)) {
           // $input clear by a hook to cancel retore
            return false;
        }

        if ($this->restoreInDB()) {
            $this->addMessageOnRestoreAction();

            if ($this->dohistory && $history) {
                $changes = [
                    0,
                    '',
                    '',
                ];
                $logaction  = Log::HISTORY_RESTORE_ITEM;
                if (
                    $this->useDeletedToLockIfDynamic()
                    && $this->isDynamic()
                ) {
                    $logaction = Log::HISTORY_UNLOCK_ITEM;
                }
                Log::history($this->input["id"], $this->getType(), $changes, 0, $logaction);
            }

            $this->post_restoreItem();
            if ($this instanceof CacheableListInterface) {
                $this->invalidateListCache();
            }
            Plugin::doHook(Hooks::ITEM_RESTORE, $this);
            return true;
        }

        return false;
    }


    /**
     * Actions done after the restore of the item
     *
     * @return void
     **/
    public function post_restoreItem()
    {
    }


    /**
     * Add a message on restore action
     *
     * @return void
     **/
    public function addMessageOnRestoreAction()
    {

        $addMessAfterRedirect = false;
        if (isset($this->input['_restore'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            if (isset($this->input['_no_message_link'])) {
                $display = $this->getNameID();
            } else {
                $display = $this->getLink();
            }
           //TRANS : %s is the description of the updated item
            Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully restored'), $display));
        }
    }


    /**
     * Reset fields of the item
     *
     * @return void
     **/
    public function reset()
    {
        $this->fields = [];
    }


    /**
     * Have I the global right to add an item for the Object
     * May be overloaded if needed (ex Ticket)
     *
     * @since 0.83
     *
     * @param string $type itemtype of object to add
     *
     * @return boolean
     **/
    public function canAddItem($type)
    {
        return $this->can($this->getID(), UPDATE);
    }


    /**
     * Have I the right to "create" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public function canCreateItem()
    {

        if (!$this->checkEntity()) {
            return false;
        }
        return true;
    }


    /**
     * Have I the right to "update" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public function canUpdateItem()
    {

        if (!$this->checkEntity()) {
            return false;
        }
        return true;
    }


    /**
     * Have I the right to "delete" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public function canDeleteItem()
    {

        if (!$this->checkEntity()) {
            return false;
        }
        return true;
    }


    /**
     * Have I the right to "purge" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public function canPurgeItem()
    {

        if (!$this->checkEntity()) {
            return false;
        }

       // Can purge an object with Infocom only if can purge Infocom
        if (Infocom::canApplyOn($this)) {
            $infocom = new Infocom();

            if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                return $infocom->canPurge();
            }
        }
        return true;
    }


    /**
     * Have I the right to "view" the Object
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public function canViewItem()
    {

        if (!$this->checkEntity(true)) {
            return false;
        }

       // else : Global item
        return true;
    }


    /**
     * Have i right to see action button
     *
     * @param integer $ID ID to check
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public function canEdit($ID)
    {

        if ($this->maybeDeleted()) {
            return ($this->can($ID, CREATE)
                 || $this->can($ID, UPDATE)
                 || $this->can($ID, DELETE)
                 || $this->can($ID, PURGE));
        }
        return ($this->can($ID, CREATE)
              || $this->can($ID, UPDATE)
              || $this->can($ID, PURGE));
    }


    /**
     * Can I change recursive flag to false
     * check if there is "linked" object in another entity
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public function canUnrecurs()
    {
        global $DB, $CFG_GLPI;

        $ID  = $this->fields['id'];
        if (
            ($ID < 0)
            || !$this->fields['is_recursive']
        ) {
            return true;
        }

        $entities = getAncestorsOf('glpi_entities', $this->fields['entities_id']);
        $entities[] = $this->fields['entities_id'];
        $RELATION  = getDbRelations();

        if ($this instanceof CommonTreeDropdown) {
            $f = getForeignKeyFieldForTable($this->getTable());

            if (
                countElementsInTable(
                    $this->getTable(),
                    [ $f => $ID, 'NOT' => [ 'entities_id' => $entities ]]
                ) > 0
            ) {
                return false;
            }
        }

        if (isset($RELATION[$this->getTable()])) {
            foreach ($RELATION[$this->getTable()] as $tablename => $fields) {
                if ($tablename[0] != '_') {
                    $itemtype = getItemTypeForTable($tablename);
                    $item     = new $itemtype();

                    $or_criteria = [];
                    foreach ($fields as $field) {
                        // 1->N Relation
                        if (is_array($field)) {
                            // Relation based on 'itemtype'/'items_id' (polymorphic relationship)
                            if ($item instanceof IPAddress && in_array('mainitemtype', $field) && in_array('mainitems_id', $field)) {
                                // glpi_ipaddresses relationship that does not respect naming conventions
                                $itemtype_field = 'mainitemtype';
                                $items_id_field = 'mainitems_id';
                            } else {
                                $itemtype_matches = preg_grep('/^itemtype/', $field);
                                $items_id_matches = preg_grep('/^items_id/', $field);
                                $itemtype_field = reset($itemtype_matches);
                                $items_id_field = reset($items_id_matches);
                            }
                            $or_criteria[] = [
                                $tablename . "." . $itemtype_field => $this->getType(),
                                $tablename . "." . $items_id_field => $this->getID(),
                            ];
                        } else {
                            // Relation based on single foreign key
                            $or_criteria[] = [
                                $tablename . "." . $field => $this->getID(),
                            ];
                        }
                    }
                    if (count($or_criteria) === 0) {
                        continue; // Empty fields mapping
                    }

                    $item_criteria = ['OR' => $or_criteria];

                    if ($item->isEntityAssign()) {
                        // 1->N Relation
                        if (
                            countElementsInTable(
                                $tablename,
                                [ $item_criteria, 'NOT' => [ 'entities_id' => $entities ]]
                            ) > 0
                        ) {
                            return false;
                        }
                    } else {
                        foreach ($RELATION as $othertable => $rel) {
                            // Search for a N->N Relation
                            if (
                                ($othertable != $this->getTable())
                                && isset($rel[$tablename])
                            ) {
                                $otheritemtype = getItemTypeForTable($othertable);
                                $otheritem     = new $otheritemtype();

                                if ($otheritem->isEntityAssign()) {
                                    foreach ($rel[$tablename] as $otherfield) {
                                        if (is_array($otherfield)) {
                                            // Relation based on 'itemtype'/'items_id' (polymorphic relationship)
                                            if ($item instanceof IPAddress && in_array('mainitemtype', $otherfield) && in_array('mainitems_id', $otherfield)) {
                                                // glpi_ipaddresses relationship that does not respect naming conventions
                                                $otheritemtype_field = 'mainitemtype';
                                                $otheritems_id_field = 'mainitems_id';
                                            } else {
                                                $otheritemtype_matches = preg_grep('/^itemtype/', $otherfield);
                                                $otheritems_id_matches = preg_grep('/^items_id/', $otherfield);
                                                $otheritemtype_field = reset($otheritemtype_matches);
                                                $otheritems_id_field = reset($otheritems_id_matches);
                                            }
                                            $fkey = [
                                                $tablename  => $otheritems_id_field,
                                                $othertable => 'id',
                                                [
                                                    'AND' => [$tablename . '.' . $otheritemtype_field => $this->getType()],
                                                ],
                                            ];
                                        } else {
                                            // Relation based on single foreign key
                                            $fkey = [
                                                $tablename  => $otherfield,
                                                $othertable => 'id',
                                            ];
                                        }
                                        if (
                                            countElementsInTable(
                                                [$tablename, $othertable],
                                                [
                                                    $item_criteria,
                                                    'FKEY' => $fkey,
                                                    'NOT'  => [$othertable . '.entities_id' => $entities ]
                                                ]
                                            ) > '0'
                                        ) {
                                            return false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

       // Doc links to this item
        if (
            ($this->getType() > 0)
            && countElementsInTable(
                ['glpi_documents_items', 'glpi_documents'],
                ['glpi_documents_items.items_id' => $ID,
                    'glpi_documents_items.itemtype' => $this->getType(),
                    'FKEY' => ['glpi_documents_items' => 'documents_id','glpi_documents' => 'id'],
                    'NOT'  => ['glpi_documents.entities_id' => $entities]
                ]
            ) > '0'
        ) {
            return false;
        }
       // TODO : do we need to check all relations in $RELATION["_virtual_device"] for this item

       // check connections of a computer
        $connectcomputer = $CFG_GLPI["directconnect_types"];
        if ($this->getType() === Computer::class || in_array($this->getType(), $connectcomputer)) {
            return Computer_Item::canUnrecursSpecif($this, $entities);
        }
        return true;
    }


    /**
     * check if this action can be done on this field of this item by massive actions
     *
     * @since 0.83
     *
     * @param string  $action name of the action
     * @param integer $field  id of the field
     * @param string  $value  value of the field
     *
     * @return boolean
     **/
    public function canMassiveAction($action, $field, $value)
    {
        if (static::maybeRecursive()) {
            if ($field === 'is_recursive' && (int) $value === 0) {
                return $this->canUnrecurs();
            }
        }
        return true;
    }

    /**
     * Display a 2 columns Footer for Form buttons
     * Close the form is user can edit
     *
     * @param array $options array of possible options:
     *     - withtemplate : 1 for newtemplate, 2 for newobject from template
     *     - colspan for each column (default 2)
     *     - candel : set to false to hide "delete" button
     *     - canedit : set to false to hide all buttons
     *     - addbuttons : array of buttons to add
     *
     * @return void
     **/
    public function showFormButtons($options = [])
    {
        $params = [
            'colspan'      => 2,
            'withtemplate' => '',
            'candel'       => true,
            'canedit'      => true,
            'addbuttons'   => [],
            'formfooter'   => null,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        echo "</table>";

        TemplateRenderer::getInstance()->display('components/form/buttons.html.twig', [
            'item'   => $this,
            'params' => $params,
        ]);

        echo "</div>"; //.asset
    }


    /**
     * Initialize item and check right before managing the edit form
     *
     * @since 0.84
     *
     * @param integer $ID      ID of the item/template
     * @param array   $options Array of possible options:
     *     - withtemplate : 1 for newtemplate, 2 for newobject from template
     *
     * @return integer|void value of withtemplate option (exit of no right)
     **/
    public function initForm($ID, array $options = [])
    {

        if (
            isset($options['withtemplate'])
            && ($options['withtemplate'] == 2)
            && !$this->isNewID($ID)
        ) {
           // Create item from template
           // Check read right on the template
            $this->check($ID, READ);

           // Restore saved input or template data
            $input = $this->restoreInput($this->fields);

           // If entity assign force current entity to manage recursive templates
            if ($this->isEntityAssign()) {
                $input['entities_id'] = $_SESSION['glpiactive_entity'];
            }

           // Check create right
            $this->check(-1, CREATE, $input);
        } else if ($this->isNewID($ID)) {
           // Restore saved input if available
            $input = $this->restoreInput($options);
           // Create item
            $this->check(-1, CREATE, $input);
        } else {
           // Existing item
            $this->check($ID, READ);
        }

        return (isset($options['withtemplate']) ? $options['withtemplate'] : '');
    }


    /**
     *
     * Display a 2 columns Header 1 for ID, 1 for recursivity menu
     * Open the form is user can edit
     *
     * @param array $options array of possible options:
     *     - target for the Form
     *     - withtemplate : 1 for newtemplate, 2 for newobject from template
     *     - colspan for each column (default 2)
     *     - formoptions string (javascript p.e.)
     *     - canedit boolean edit mode of form ?
     *     - formtitle specific form title
     *     - noid Set to true if ID should not be append (eg. already done in formtitle)
     *     - header_toolbar Array of header toolbar elements (HTML code)
     *
     * @return void
     **/
    public function showFormHeader($options = [])
    {
        $params = [
            'target'         => $this->getFormURL(),
            'colspan'        => 2,
            'withtemplate'   => '',
            'formoptions'    => '',
            'canedit'        => true,
            'formtitle'      => null,
            'no_header'      => false,
            'noid'           => false,
            'header_toolbar' => [],
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

       // Template case : clean entities data
        if (
            ($params['withtemplate'] == 2)
            && $this->isEntityAssign()
        ) {
            $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
        }

        $header_toolbar = $params['header_toolbar'];
        unset($params['header_toolbar']);

        echo "<div class='asset'>";
        TemplateRenderer::getInstance()->display('components/form/header.html.twig', [
            'item'           => $this,
            'params'         => $params,
            'no_header'      => $params['no_header'],
            'header_toolbar' => $header_toolbar,
        ]);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='" . ($params['colspan'] * 2) . "'>";
    }


    /**
     * is the parameter ID must be considered as new one ?
     * Default is empty of <0 may be overriden (for entity for example)
     *
     * @param integer $ID ID of the item (-1 if new item)
     *
     * @return boolean
     **/
    public static function isNewID($ID)
    {
        return (empty($ID) || ($ID <= 0));
    }


    /**
     * is the current object a new  one
     *
     * @since 0.83
     *
     * @return boolean
     **/
    public function isNewItem()
    {

        if (isset($this->fields['id'])) {
            return $this->isNewID($this->fields['id']);
        }
        return true;
    }


    /**
     * Check right on an item
     *
     * @param integer $ID    ID of the item (-1 if new item)
     * @param mixed   $right Right to check : r / w / recursive / READ / UPDATE / DELETE
     * @param array   $input array of input data (used for adding item) (default NULL)
     *
     * @return boolean
     **/
    public function can($ID, $right, array &$input = null)
    {
        if (Session::isInventory()) {
            return true;
        }

       // Clean ID :
        $ID = Toolbox::cleanInteger($ID);

       // Create process
        if ($this->isNewID($ID)) {
            if (!isset($this->fields['id'])) {
                // Only once
                $this->getEmpty();
            }

            if (is_array($input)) {
                $input = $this->addNeededInfoToInput($input);
               // Copy input field to allow getEntityID() to work
               // from entites_id field or from parent item ref
                foreach ($input as $key => $val) {
                    if (isset($this->fields[$key])) {
                        $this->fields[$key] = $val;
                    }
                }
               // Store to be available for others functions
                $this->input = $input;
            }

            if (
                $this->isPrivate()
                && ($this->fields['users_id'] == Session::getLoginUserID())
            ) {
                return true;
            }
            return (static::canCreate() && $this->canCreateItem());
        }
       // else : Get item if not already loaded
        if (!isset($this->fields['id']) || ($this->fields['id'] != $ID)) {
           // Item not found : no right
            if (!$this->getFromDB($ID)) {
                return false;
            }
        }

       /* Hook to restrict user right on current item @since 9.2 */
        $this->right = $right;
        Plugin::doHook(Hooks::ITEM_CAN, $this);
        if ($this->right !== $right) {
            return false;
        }
        $this->right = null;

        switch ($right) {
            case READ:
               // Personnal item
                if (
                    $this->isPrivate()
                    && ($this->fields['users_id'] === Session::getLoginUserID())
                ) {
                    return true;
                }
                return (static::canView() && $this->canViewItem());

            case UPDATE:
               // Personnal item
                if (
                    $this->isPrivate()
                    && ($this->fields['users_id'] === Session::getLoginUserID())
                ) {
                    return true;
                }
                return (static::canUpdate() && $this->canUpdateItem());

            case DELETE:
               // Personnal item
                if (
                    $this->isPrivate()
                    && ($this->fields['users_id'] === Session::getLoginUserID())
                ) {
                    return true;
                }
                return (static::canDelete() && $this->canDeleteItem());

            case PURGE:
               // Personnal item
                if (
                    $this->isPrivate()
                    && ($this->fields['users_id'] === Session::getLoginUserID())
                ) {
                    return true;
                }
                return (static::canPurge() && $this->canPurgeItem());

            case CREATE:
               // Personnal item
                if (
                    $this->isPrivate()
                    && ($this->fields['users_id'] === Session::getLoginUserID())
                ) {
                    return true;
                }
                return (static::canCreate() && $this->canCreateItem());

            case 'recursive':
                if (
                    $this->isEntityAssign()
                    && $this->maybeRecursive()
                ) {
                    if (
                        static::canCreate()
                        && Session::haveAccessToEntity($this->getEntityID())
                    ) {
                       // Can make recursive if recursive access to entity
                        return Session::haveRecursiveAccessToEntity($this->getEntityID());
                    }
                }
                break;
        }
        return false;
    }


    /**
     * Check right on an item with block
     *
     * @param integer $ID    ID of the item (-1 if new item)
     * @param mixed   $right Right to check : r / w / recursive
     * @param array   $input array of input data (used for adding item) (default NULL)
     *
     * @return void
     **/
    public function check($ID, $right, array &$input = null)
    {

       // Check item exists
        if (
            !$this->isNewID($ID)
            && (!isset($this->fields['id']) || $this->fields['id'] != $ID)
            && !$this->getFromDB($ID)
        ) {
           // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            Html::displayNotFoundError();
        } else {
            if (!$this->can($ID, $right, $input)) {
               // Gestion timeout session
                Session::redirectIfNotLoggedIn();
                $right_name = Session::getRightNameForError($right);
                $itemtype = static::getType();
                $info = "User failed a can* method check for right $right ($right_name) on item Type: $itemtype ID: $ID";
                Html::displayRightError($info);
            }
        }
    }


    /**
     * Check if have right on this entity
     *
     * @param boolean $recursive set true to accept recursive items of ancestors
     *                           of active entities (View case for example) (default false)
     * @since 0.85
     *
     * @return boolean
     **/
    public function checkEntity($recursive = false)
    {

       // Is an item assign to an entity
        if ($this->isEntityAssign()) {
           // Can be recursive check
            if ($recursive && $this->maybeRecursive()) {
                return Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive());
            }
           //  else : No recursive item         // Have access to entity
            return Session::haveAccessToEntity($this->getEntityID());
        }
       // else : Global item
        return true;
    }


    /**
     * Check global right on an object
     *
     * @param mixed $right Right to check : c / r / w / d
     *
     * @return void
     **/
    public function checkGlobal($right)
    {

        if (!$this->canGlobal($right)) {
           // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            $right_name = Session::getRightNameForError($right);
            $itemtype = static::getType();
            $info = "User failed a global can* method check for right $right ($right_name) on item Type: $itemtype";
            Html::displayRightError($info);
        }
    }


    /**
     * Get global right on an object
     *
     * @param mixed $right Right to check : c / r / w / d / READ / UPDATE / CREATE / DELETE
     *
     * @return bool
     **/
    public function canGlobal($right)
    {

        switch ($right) {
            case READ:
                return static::canView();

            case UPDATE:
                return static::canUpdate();

            case CREATE:
                return static::canCreate();

            case DELETE:
                return static::canDelete();

            case PURGE:
                return static::canPurge();
        }

        return false;
    }


    /**
     * Get the ID of entity assigned to the object
     *
     * Can be overloaded (ex : infocom)
     *
     * @return integer ID of the entity
     **/
    public function getEntityID()
    {

        if ($this->isEntityAssign()) {
            return $this->fields["entities_id"];
        }
        return  -1;
    }


    /**
     * Is the object assigned to an entity
     *
     * Can be overloaded (ex : infocom)
     *
     * @return boolean
     **/
    public function isEntityAssign()
    {

        if (!array_key_exists('id', $this->fields)) {
            $this->getEmpty();
        }
        return array_key_exists('entities_id', $this->fields);
    }


    /**
     * Is the object may be recursive
     *
     * Can be overloaded (ex : infocom)
     *
     * @return boolean
     **/
    public function maybeRecursive()
    {

        if (!array_key_exists('id', $this->fields)) {
            $this->getEmpty();
        }
        return array_key_exists('is_recursive', $this->fields);
    }


    /**
     * Is the object recursive
     *
     * Can be overloaded (ex : infocom)
     *
     * @return boolean
     **/
    public function isRecursive()
    {

        if ($this->maybeRecursive()) {
            return $this->fields["is_recursive"];
        }
       // Return integer value to be used to fill is_recursive field
        return 0;
    }


    /**
     * Is the object may be deleted
     *
     * @return boolean
     **/
    public function maybeDeleted()
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return array_key_exists('is_deleted', $this->fields);
    }


    /**
     * Is the object deleted
     *
     * @return boolean
     **/
    public function isDeleted()
    {

        if ($this->maybeDeleted()) {
            return $this->fields["is_deleted"];
        }
       // Return integer value to be used to fill is_deleted field
        return 0;
    }


    /**
     * Can object be activated
     *
     * @since 9.2
     *
     * @return boolean
     **/
    public function maybeActive()
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return array_key_exists('is_active', $this->fields);
    }


    /**
     * Is the object active
     *
     * @since 9.2
     *
     * @return boolean
     **/
    public function isActive()
    {

        if ($this->maybeActive()) {
            return $this->fields["is_active"];
        }
       // Return integer value to be used to fill is_active field
        return 1;
    }


    /**
     * Is the object may be a template
     *
     * @return boolean
     **/
    public function maybeTemplate()
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return isset($this->fields['is_template']);
    }


    /**
     * Is the object a template
     *
     * @return boolean
     **/
    public function isTemplate()
    {

        if ($this->maybeTemplate()) {
            return $this->fields["is_template"];
        }
       // Return integer value to be used to fill is_template field
        return 0;
    }


    /**
     * Can the object be dynamic
     *
     * @since 0.84
     *
     * @return boolean
     **/
    public function maybeDynamic()
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return array_key_exists('is_dynamic', $this->fields);
    }


    /**
     * Use deleted field in case of dynamic management to lock ?
     *
     * need to be overriden if object need to use standard deleted management (Computer...)
     * @since 0.84
     *
     * @return boolean
     **/
    public function useDeletedToLockIfDynamic()
    {
        return $this->maybeDynamic();
    }


    /**
     * Is an object dynamic or not
     *
     * @since 0.84
     *
     * @return boolean
     **/
    public function isDynamic()
    {

        if ($this->maybeDynamic()) {
            return (bool)$this->fields['is_dynamic'];
        }
        return false;
    }


    /**
     * Is the object may be private
     *
     * @return boolean
     **/
    public function maybePrivate()
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return (array_key_exists('is_private', $this->fields)
              && array_key_exists('users_id', $this->fields));
    }


    /**
     * Is the object private
     *
     * @return boolean
     **/
    public function isPrivate()
    {

        if ($this->maybePrivate()) {
            return (bool)$this->fields["is_private"];
        }
        return false;
    }

    /**
     * Can object have a location
     *
     * @since 9.3
     *
     * @return boolean
     */
    public function maybeLocated()
    {

        if (!array_key_exists('id', $this->fields)) {
            $this->getEmpty();
        }
        return array_key_exists('locations_id', $this->fields);
    }

    /**
     * Return the linked items (in computers_items)
     *
     * @return array an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
     * @since 0.84.4
     **/
    public function getLinkedItems()
    {
        return [];
    }


    /**
     * Return the count of linked items (in computers_items)
     *
     * @return integer number of linked items
     * @since 0.84.4
     **/
    public function getLinkedItemsCount()
    {

        $linkeditems = $this->getLinkedItems();
        $nb          = 0;
        if (count($linkeditems)) {
            foreach ($linkeditems as $tab) {
                $nb += count($tab);
            }
        }
        return $nb;
    }


    /**
     * Return a field Value if exists
     *
     * @param string $field field name
     *
     * @return mixed value of the field / false if not exists
     **/
    public function getField($field)
    {

        if (array_key_exists($field, $this->fields)) {
            return $this->fields[$field];
        }
        return NOT_AVAILABLE;
    }


    /**
     * Determine if a field exists
     *
     * @param string $field field name
     *
     * @return boolean
     **/
    public function isField($field)
    {

        if (!isset($this->fields['id'])) {
            $this->getEmpty();
        }
        return array_key_exists($field, $this->fields);
    }


    /**
     * Get comments of the Object
     *
     * @return string comments of the object in the current language (HTML)
     **/
    public function getComments()
    {

        $comment = "";
        $toadd   = [];
        if ($this->isField('completename')) {
            $toadd[] = ['name'  => __('Complete name'),
                'value' => nl2br((string) $this->getField('completename'))
            ];
        }

        if ($this->isField('serial')) {
            $toadd[] = ['name'  => __('Serial number'),
                'value' => nl2br((string) $this->getField('serial'))
            ];
        }

        if ($this->isField('otherserial')) {
            $toadd[] = ['name'  => __('Inventory number'),
                'value' => nl2br((string) $this->getField('otherserial'))
            ];
        }

        if ($this->isField('states_id') && $this->getType() != 'State') {
            $tmp = Dropdown::getDropdownName('glpi_states', $this->getField('states_id'));
            if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
                $toadd[] = ['name'  => __('Status'),
                    'value' => $tmp
                ];
            }
        }

        if ($this->isField('locations_id') && $this->getType() != 'Location') {
            $tmp = Dropdown::getDropdownName("glpi_locations", $this->getField('locations_id'));
            if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
                $toadd[] = ['name'  => Location::getTypeName(1),
                    'value' => $tmp
                ];
            }
        }

        if ($this->isField('users_id')) {
            $tmp = getUserName($this->getField('users_id'));
            if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
                $toadd[] = ['name'  => User::getTypeName(1),
                    'value' => $tmp
                ];
            }
        }

        if (
            $this->isField('groups_id')
            && ($this->getType() != 'Group')
        ) {
            $tmp = Dropdown::getDropdownName("glpi_groups", $this->getField('groups_id'));
            if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
                $toadd[] = ['name'  => Group::getTypeName(1),
                    'value' => $tmp
                ];
            }
        }

        if ($this->isField('users_id_tech')) {
            $tmp = getUserName($this->getField('users_id_tech'));
            if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
                $toadd[] = ['name'  => __('Technician in charge of the hardware'),
                    'value' => $tmp
                ];
            }
        }

        if ($this->isField('contact')) {
            $toadd[] = ['name'  => __('Alternate username'),
                'value' => nl2br((string) $this->getField('contact'))
            ];
        }

        if ($this->isField('contact_num')) {
            $toadd[] = ['name'  => __('Alternate username number'),
                'value' => nl2br((string) $this->getField('contact_num'))
            ];
        }

        if (Infocom::canApplyOn($this)) {
            $infocom = new Infocom();
            if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                $toadd[] = ['name'  => __('Warranty expiration date'),
                    'value' => Infocom::getWarrantyExpir(
                        $infocom->fields["warranty_date"],
                        $infocom->fields["warranty_duration"],
                        0,
                        true
                    )
                ];
            }
        }

        if (
            ($this instanceof CommonDropdown)
            && $this->isField('comment')
        ) {
            $toadd[] = ['name'  => __('Comments'),
                'value' => nl2br((string) $this->getField('comment'))
            ];
        }

        if (count($toadd)) {
            foreach ($toadd as $data) {
               // Do not use SPAN here
                $comment .= sprintf(
                    __('%1$s: %2$s') . "<br>",
                    "<strong>" . $data['name'],
                    "</strong>" . $data['value']
                );
            }
        }

        if (!empty($comment)) {
            return Html::showToolTip($comment, ['display' => false]);
        }

        return $comment;
    }


    /**
     * @since 0.84
     *
     * Get field used for name
     *
     * @return string
     **/
    public static function getNameField()
    {
        return 'name';
    }


    /**
     * @since 0.84
     *
     * Get field used for completename
     *
     * @return string
     **/
    public static function getCompleteNameField()
    {
        return 'completename';
    }


    /** Get raw completename of the object
     * Maybe overloaded
     *
     * @see CommonDBTM::getCompleteNameField
     *
     * @since 0.85
     *
     * @return string
     **/
    public function getRawCompleteName()
    {

        if (isset($this->fields[static::getCompleteNameField()])) {
            return $this->fields[static::getCompleteNameField()];
        }
        return '';
    }


    /**
     * Get the name of the object
     *
     * @param array $options array of options
     *    - comments     : boolean / display comments
     *    - icon         : boolean / display icon
     *    - complete     : boolean / display completename instead of name
     *    - additional   : boolean / display aditionals information
     *
     * @return string name of the object in the current language
     *
     * @see CommonDBTM::getRawCompleteName
     * @see CommonDBTM::getFriendlyName
     **/
    public function getName($options = [])
    {

        $p = [
            'comments'   => false,
            'complete'   => false,
            'additional' => false,
            'icon'       => false,
        ];

        if (is_array($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $name = '';
        if ($p['complete']) {
            $name = $this->getRawCompleteName();
        }
        if (empty($name)) {
            $name = $this->getFriendlyName();
        }

        if (strlen($name) != 0) {
            if ($p['additional']) {
                $pre = $this->getPreAdditionalInfosForName();
                if (!empty($pre)) {
                    $name = sprintf(__('%1$s - %2$s'), $pre, $name);
                }
                $post = $this->getPostAdditionalInfosForName();
                if (!empty($post)) {
                    $name = sprintf(__('%1$s - %2$s'), $name, $post);
                }
            }
            if ($p['comments']) {
                $comment = $this->getComments();
                if (!empty($comment)) {
                    $name = sprintf(__('%1$s - %2$s'), $name, $comment);
                }
            }

            if ($p['icon']) {
                $icon = $this->getIcon();
                if (!empty($icon)) {
                    $name = sprintf(__('%1$s %2$s'), "<i class='$icon'></i>", $name);
                }
            }
            return $name;
        }
        return NOT_AVAILABLE;
    }


    /**
     * Get additionals information to add before name
     *
     * @since 0.84
     *
     * @return string string to add
     **/
    public function getPreAdditionalInfosForName()
    {
        return '';
    }

    /**
     * Get additionals information to add after name
     *
     * @since 0.84
     *
     * @return string string to add
     **/
    public function getPostAdditionalInfosForName()
    {
        return '';
    }


    /**
     * Get the name of the object with the ID if the config is set
     * Should Not be overloaded (overload getName() instead)
     *
     * @see CommonDBTM::getName
     *
     * @param array $options array of options
     *    - comments     : boolean / display comments
     *    - icon         : boolean / display icon
     *    - complete     : boolean / display completename instead of name
     *    - additional   : boolean / display aditionals information
     *    - forceid      : boolean  override config and display item's ID (false by default)
     *
     * @return string name of the object in the current language
     **/
    public function getNameID($options = [])
    {

        $p = [
            'forceid'  => false,
            'comments' => false,
            'icon'     => false,
        ];

        if (is_array($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if (
            $p['forceid']
            ||
            (
                isset($_SESSION['glpiis_ids_visible'])
                && $_SESSION['glpiis_ids_visible']
            )
        ) {
            $addcomment = $p['comments'];

           // unset comment
            $p['comments'] = false;
            $name = $this->getName($p);

           //TRANS: %1$s is a name, %2$s is ID
            $name = sprintf(__('%1$s (%2$s)'), $name, $this->getField('id'));

            if ($addcomment) {
                $comment = $this->getComments();
                if (!empty($comment)) {
                    $name = sprintf(__('%1$s - %2$s'), $name, $comment);
                }
            }

            return $name;
        }
        return $this->getName($options);
    }

    /**
     * Get the Search options for the given Type
     * If you want to work on search options, @see CommonDBTM::rawSearchOptions
     *
     * @return array an *indexed* array of search options
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    final public function searchOptions()
    {
        static $options = [];

        $type = $this->getType();

        if (isset($options[$type])) {
            return $options[$type];
        }

        $options[$type] = [];

        foreach ($this->rawSearchOptions() as $opt) {
            $missingFields = [];
            if (!isset($opt['id'])) {
                $missingFields[] = 'id';
            }
            if (!isset($opt['name'])) {
                $missingFields[] = 'name';
            }
            if (count($missingFields) > 0) {
                throw new \Exception(
                    vsprintf(
                        'Invalid search option in "%1$s": missing "%2$s" field(s). %3$s',
                        [
                            get_called_class(),
                            implode('", "', $missingFields),
                            print_r($opt, true)
                        ]
                    )
                );
            }

            $optid = $opt['id'];
            unset($opt['id']);

            if (isset($options[$type][$optid])) {
                $message = "Duplicate key $optid ({$options[$type][$optid]['name']}/{$opt['name']}) in " .
                  get_class($this) . " searchOptions!";

                trigger_error($message, E_USER_WARNING);
            }

            foreach ($opt as $k => $v) {
                $options[$type][$optid][$k] = $v;
            }
        }

        return $options[$type];
    }


    /**
     * Provides search options configuration. Do not rely directly
     * on this, @see CommonDBTM::searchOptions instead.
     *
     * @since 9.3
     *
     * This should be overloaded in Class
     *
     * @return array a *not indexed* array of search options
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    public function rawSearchOptions()
    {
        global $CFG_GLPI;

        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics')
        ];

        if ($this->isField('name')) {
            $tab[] = [
                'id'            => 1,
                'table'         => $this->getTable(),
                'field'         => 'name',
                'name'          => __('Name'),
                'datatype'      => 'itemlink',
                'massiveaction' => false,
            ];
        }

        if ($this->isField('is_recursive')) {
            $tab[] = [
                'id'       => 86,
                'table'      => $this->getTable(),
                'field'      => 'is_recursive',
                'name'       => __('Child entities'),
                'datatype'   => 'bool',
                'searchtype' => 'equals',
            ];
        }

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        // Add project for assets
        $projects_itemtypes = $CFG_GLPI["project_asset_types"] ?? [];
        if (in_array(static::class, $projects_itemtypes)) {
            $tab = array_merge($tab, Project::rawSearchOptionsToAdd(static::class));
        }

        return $tab;
    }

    /**
     * Summary of getSearchOptionsToAdd
     * @since 9.2
     *
     * @param string $itemtype Item type, defaults to null
     *
     * @return array
     **/
    public static function getSearchOptionsToAdd($itemtype = null)
    {
        $options = [];

        $classname = get_called_class();
        $method_name = 'rawSearchOptionsToAdd';
        if (!method_exists($classname, $method_name)) {
            return $options;
        }

        if (defined('TU_USER') && $itemtype != null && $itemtype != AllAssets::getType()) {
            $item = new $itemtype();
            $all_options = $item->searchOptions();
        }

        foreach ($classname::$method_name($itemtype) as $opt) {
            if (!isset($opt['id'])) {
                throw new \Exception(get_called_class() . ': invalid search option! ' . print_r($opt, true));
            }
            $optid = $opt['id'];
            unset($opt['id']);

            if (defined('TU_USER') && $itemtype != null) {
                if (isset($all_options[$optid])) {
                    $message = "Duplicate key $optid ({$all_options[$optid]['name']}/{$opt['name']}) in " .
                    self::class . " searchOptionsToAdd for $itemtype!";

                    trigger_error($message, E_USER_WARNING);
                }
            }

            foreach ($opt as $k => $v) {
                $options[$optid][$k] = $v;
                if (defined('TU_USER') && $itemtype != null) {
                    $all_options[$optid][$k] = $v;
                }
            }
        }

        return $options;
    }

    /**
     * Get all the massive actions available for the current class regarding given itemtype
     *
     * @since 0.85
     *
     * @param array      $actions    array of the actions to update
     * @param string     $itemtype   the type of the item for which we want the actions
     * @param boolean    $is_deleted (default 0)
     * @param CommonDBTM $checkitem  (default NULL)
     *
     * @return void (update is set inside $actions)
     **/
    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = 0,
        CommonDBTM $checkitem = null
    ) {
    }


    /**
     * Class-specific method used to show the fields to specify the massive action
     *
     * @since 0.85
     *
     * @param MassiveAction $ma the current massive action object
     *
     * @return boolean false if parameters displayed ?
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        return false;
    }


    /**
     * Class specific execution of the massive action (new system) by itemtypes
     *
     * @since 0.85
     *
     * @param MassiveAction $ma   the current massive action object
     * @param CommonDBTM    $item the item on which apply the massive action
     * @param array         $ids  an array of the ids of the item on which apply the action
     *
     * @return void (direct submit to $ma object)
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
    }


    /**
     * Get the standard massive actions which are forbidden
     *
     * @since 0.84
     *
     * This should be overloaded in Class
     *
     * @return array an array of massive actions
     **/
    public function getForbiddenStandardMassiveAction()
    {
        return [];
    }


    /**
     * Get forbidden single action
     *
     * @since 9.5.0
     *
     * @return array
     **/
    public function getForbiddenSingleMassiveActions()
    {
        $excluded = [
            '*:update',
            '*:delete',
            '*:remove',
            '*:purge',
            '*:unlock'
        ];

        if (Infocom::canApplyOn($this)) {
            $ic = new Infocom();
            if ($ic->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                $excluded[] = 'Infocom:activate';
            }
        }

        return $excluded;
    }

    /**
     * Get whitelisted single actions
     *
     * @since 9.5.0
     *
     * @return array
     **/
    public function getWhitelistedSingleMassiveActions()
    {
        global $CFG_GLPI;

        $actions = ['MassiveAction:add_transfer_list'];

        if (in_array(static::getType(), $CFG_GLPI['rackable_types'])) {
            $actions[] = 'Item_Rack:delete';
        }

        return $actions;
    }


    /**
     * Get the specific massive actions
     *
     * @since 0.84
     *
     * This should be overloaded in Class
     *
     * @param object $checkitem link item to check right (default NULL)
     *
     * @return array an array of massive actions
     **/
    public function getSpecificMassiveActions($checkitem = null)
    {
        global $DB, $CFG_GLPI;

        $actions = [];
       // test if current profile has rights to unlock current item type
        if (Session::haveRight(static::$rightname, UNLOCK)) {
            $actions['ObjectLock' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlock']
                        = _x('button', 'Unlock items');
        }

        if (static::canUpdate()) {
            if ($DB->fieldExists(static::getTable(), 'entities_id')) {
                MassiveAction::getAddTransferList($actions);
            }

            if (in_array(static::getType(), Appliance::getTypes(true))) {
                $actions['Appliance' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_item'] =
                "<i class='fa-fw " . Appliance::getIcon() . "'></i>" . _x('button', 'Associate to an appliance');
            }

            if (in_array(static::getType(), $CFG_GLPI['rackable_types'])) {
                $actions['Item_Rack' . MassiveAction::CLASS_ACTION_SEPARATOR . 'delete'] =
                "<i class='fa-fw ti ti-server-off'></i>" . _x('button', 'Remove from a rack');
            }
        }

        return $actions;
    }


    /**
     * Print out an HTML "<select>" for a dropdown
     *
     * This should be overloaded in Class
     *
     * @param array $options array of possible options:
     * Parameters which could be used in options array :
     *    - name : string / name of the select (default is depending itemtype)
     *    - value : integer / preselected value (default 0)
     *    - comments : boolean / is the comments displayed near the dropdown (default true)
     *    - entity : integer or array / restrict to a defined entity or array of entities
     *                   (default -1 : no restriction)
     *    - toupdate : array / Update a specific item on select change on dropdown
     *                   (need value_fieldname, to_update, url (see Ajax::updateItemOnSelectEvent for information)
     *                   and may have moreparams)
     *    - used : array / Already used items ID: not to display in dropdown (default empty)
     *    - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *
     * @return string|void display the dropdown
     **/
    public static function dropdown($options = [])
    {
       /// TODO try to revert usage : Dropdown::show calling this function
       /// TODO use this function instead of Dropdown::show
        return Dropdown::show(get_called_class(), $options);
    }


    /**
     * Return a search option by looking for a value of a specific field and maybe a specific table
     *
     * @param string $field the field in which looking for the value (for example : table, name, etc)
     * @param string $value the value to look for in the field
     * @param string $table the table (default '')
     *
     * @return array the search option array, or an empty array if not found
     **/
    public function getSearchOptionByField($field, $value, $table = '')
    {

        foreach ($this->searchOptions() as $id => $searchOption) {
            if (
                (isset($searchOption['linkfield']) && ($searchOption['linkfield'] == $value))
                || (isset($searchOption[$field]) && ($searchOption[$field] == $value))
            ) {
                if (
                    ($table == '')
                    || (($table != '') && ($searchOption['table'] == $table))
                ) {
                  // Set ID;
                    $searchOption['id'] = $id;
                    return $searchOption;
                }
            }
        }
        return [];
    }


    /**
     * Get search options
     *
     * @since 0.85
     *
     * @return array the search option array
     **/
    public function getOptions()
    {

        if (!$this->searchopt) {
            $this->searchopt = Search::getOptions($this->getType());
        }

        return $this->searchopt;
    }


    /**
     * Return a search option ID by looking for a value of a specific field and maybe a specific table
     *
     * @since 0.83
     *
     * @param string $field the field in which looking for the value (for example : table, name, etc)
     * @param string $value the value to look for in the field
     * @param string $table the table (default '')
     *
     * @return mixed the search option id, or -1 if not found
     **/
    public function getSearchOptionIDByField($field, $value, $table = '')
    {

        $tab = $this->getSearchOptionByField($field, $value, $table);
        if (isset($tab['id'])) {
            return $tab['id'];
        }
        return -1;
    }


    /**
     * Check float and decimal values
     *
     * @param boolean $display display or not messages in and addAfterRedirect (true by default)
     *
     * @return void
     **/
    public function filterValues($display = true)
    {
       // MoYo : comment it because do not understand why filtering is disable
       // if (in_array('CommonDBRelation', class_parents($this))) {
       //    return true;
       // }
       //Type mismatched fields
        $fails = [];
        if (is_array($this->input) && count($this->input)) {
            foreach ($this->input as $key => $value) {
                $unset        = false;
                $regs         = [];
                $searchOption = $this->getSearchOptionByField('field', $key);

                if (
                    isset($searchOption['datatype'])
                    && (is_null($value) || ($value == '') || ($value == 'NULL'))
                ) {
                    switch ($searchOption['datatype']) {
                        case 'date':
                        case 'datetime':
                              // don't use $unset', because this is not a failure
                              $this->input[$key] = 'NULL';
                            break;
                    }
                } else if (
                    isset($searchOption['datatype'])
                       && !is_null($value)
                       && ($value != '')
                       && ($value != 'NULL')
                ) {
                    switch ($searchOption['datatype']) {
                        case 'integer':
                        case 'count':
                        case 'number':
                        case 'decimal':
                              $value = str_replace(',', '.', $value);
                            if ($searchOption['datatype'] == 'decimal') {
                                $this->input[$key] = floatval(Toolbox::cleanDecimal($value));
                            } else {
                                $this->input[$key] = intval(Toolbox::cleanInteger($value));
                            }
                            if (!is_numeric($this->input[$key])) {
                                 $unset = true;
                            }
                            break;

                        case 'bool':
                            if (!in_array($value, [0,1])) {
                                $unset = true;
                            }
                            break;

                        case 'ip':
                            $address = new IPAddress();
                            if (!$address->setAddressFromString($value)) {
                                $unset = true;
                            } else if (!$address->is_ipv4()) {
                                $unset = true;
                            }
                            break;

                        case 'mac':
                            preg_match("/([0-9a-fA-F]{1,2}([:-]|$)){6}$/", $value, $regs);
                            if (empty($regs)) {
                                $unset = true;
                            }
                           // Define the MAC address to lower to reduce complexity of SQL queries
                            $this->input[$key] = strtolower($value);
                            break;

                        case 'date':
                        case 'datetime':
                           // Date is already "reformat" according to getDateFormat()
                            $pattern  = "/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})";
                            $pattern .= "([_][01][0-9]|2[0-3]:[0-5][0-9]:[0-5]?[0-9])?/";
                            preg_match($pattern, $value, $regs);
                            if (empty($regs)) {
                                $unset = true;
                            }
                            break;

                        case 'itemtype':
                           //Want to insert an itemtype, but the associated class doesn't exists
                            if (!class_exists($value)) {
                                $unset = true;
                            }
                            break;

                        case 'email':
                        case 'string':
                        case 'itemlink':
                            if (is_string($value) && ($length = mb_strlen($value, 'UTF-8')) > 255) {
                                trigger_error(
                                    "{$value} exceed 255 characters long ({$length}), it will be truncated.",
                                    E_USER_WARNING
                                );
                                $this->input[$key] = mb_substr($value, 0, 255, 'UTF-8');
                            }
                            break;

                        default:
                           //Plugins can implement their own checks
                            if (!$this->checkSpecificValues($searchOption['datatype'], $value)) {
                                $unset = true;
                            }
                           // Copy value if check have update it
                            $this->input[$key] = $value;
                            break;
                    }
                }

                if ($unset) {
                    $fails[] = $searchOption['name'];
                    unset($this->input[$key]);
                }
            }
        }
        if ($display && count($fails)) {
           //Display a message to indicate that one or more value where filtered
           //TRANS: %s is the list of the failed fields
            $message = sprintf(
                __('%1$s: %2$s'),
                __('At least one field has an incorrect value'),
                implode(',', $fails)
            );
            Session::addMessageAfterRedirect($message, INFO, true);
        }
    }


    /**
     * Add more check for values
     *
     * @param string $datatype datatype of the value
     * @param array  $value    value to check (pass by reference)
     *
     * @return boolean true if value is ok, false if not
     **/
    public function checkSpecificValues($datatype, &$value)
    {
        return true;
    }


    /**
     * Get fields to display in the unicity error message
     *
     * @return array an array which contains field => label
     **/
    public function getUnicityFieldsToDisplayInErrorMessage()
    {

        return ['id'          => __('ID'),
            'serial'      => __('Serial number'),
            'entities_id' => Entity::getTypeName(1)
        ];
    }


    public function getUnallowedFieldsForUnicity()
    {
        return ['alert', 'comment', 'date_mod', 'id', 'is_recursive', 'items_id'];
    }


    /**
     * Build an unicity error message
     *
     * @param array $msgs    the string not transleted to be display on the screen, or to be sent in a notification
     * @param array $unicity the unicity criterion that failed to match
     * @param array $doubles the items that are already present in DB
     *
     * @return string
     **/
    public function getUnicityErrorMessage($msgs, $unicity, $doubles)
    {

        $message = [];
        foreach ($msgs as $field => $value) {
            $table = getTableNameForForeignKeyField($field);
            if ($table != '') {
                $searchOption = $this->getSearchOptionByField('field', 'name', $table);
            } else {
                $searchOption = $this->getSearchOptionByField('field', $field);
            }
            $message[] = sprintf(__('%1$s = %2$s'), $searchOption['name'], $value);
        }

        if ($unicity['action_refuse']) {
            $message_text = sprintf(
                __('Impossible record for %s'),
                implode('&nbsp;&amp;&nbsp;', $message)
            );
        } else {
            $message_text = sprintf(
                __('Item successfully added but duplicate record on %s'),
                implode('&nbsp;&amp;&nbsp;', $message)
            );
        }
        $message_text .= '<br>' . __('Other item exist');

        foreach ($doubles as $double) {
            if ($this instanceof CommonDBChild) {
                if ($this->getField($this->itemtype)) {
                    $item = new $double['itemtype']();
                } else {
                    $item = new $this->itemtype();
                }

                $item->getFromDB($double['items_id']);
            } else {
                $item = clone $this;
                $item->getFromDB($double['id']);
            }

            $double_text = '';
            if ($item->canView() && $item->canViewItem()) {
                $double_text = $item->getLink();
            }

            foreach ($this->getUnicityFieldsToDisplayInErrorMessage() as $key => $value) {
                $field_value = $item->getField($key);
                if ($field_value != NOT_AVAILABLE) {
                    if (getTableNameForForeignKeyField($key) != '') {
                        $field_value = Dropdown::getDropdownName(
                            getTableNameForForeignKeyField($key),
                            $field_value
                        );
                    }
                    $new_text = sprintf(__('%1$s: %2$s'), $value, $field_value);
                    if (empty($double_text)) {
                        $double_text = $new_text;
                    } else {
                        $double_text = sprintf(__('%1$s - %2$s'), $double_text, $new_text);
                    }
                }
            }
           // Add information on item in trashbin
            if ($item->isField('is_deleted') && $item->getField('is_deleted')) {
                $double_text = sprintf(__('%1$s - %2$s'), $double_text, __('Item in the trashbin'));
            }

            $message_text .= "<br>[$double_text]";
        }
        return $message_text;
    }


    /**
     * Check field unicity before insert or update
     *
     * @param boolean $add     true for insert, false for update (false by default)
     * @param array   $options array
     *
     * @return boolean true if item can be written in DB, false if not
     **/
    public function checkUnicity($add = false, $options = [])
    {
        global $CFG_GLPI;

        $p = [
            'unicity_error_message'  => true,
            'add_event_on_duplicate' => true,
            'disable_unicity_check'  => false,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $value) {
                $p[$key] = $value;
            }
        }

       // Do not check for template
        if (isset($this->input['is_template']) && $this->input['is_template']) {
            return true;
        }

        $result = true;

       //Do not check unicity when creating infocoms or if checking is expliclty disabled
        if ($p['disable_unicity_check']) {
            return $result;
        }

       //Get all checks for this itemtype and this entity
        if (in_array(get_class($this), $CFG_GLPI["unicity_types"])) {
           // Get input entities if set / else get object one
            if (isset($this->input['entities_id'])) {
                $entities_id = $this->input['entities_id'];
            } else if (isset($this->fields['entities_id'])) {
                $entities_id = $this->fields['entities_id'];
            } else {
                $entities_id = 0;
                $message = 'Missing entity ID!';
                trigger_error($message, E_USER_WARNING);
            }

            $all_fields =  FieldUnicity::getUnicityFieldsConfig(get_class($this), $entities_id);
            foreach ($all_fields as $key => $fields) {
               //If there's fields to check
                if (!empty($fields) && !empty($fields['fields'])) {
                    $where    = [];
                    $continue = true;
                    foreach (explode(',', $fields['fields']) as $field) {
                        if (
                            isset($this->input[$field]) //Field is set
                            //Standard field not null
                            && (((getTableNameForForeignKeyField($field) == '')
                            && ($this->input[$field] != ''))
                            //Foreign key and value is not 0
                            || ((getTableNameForForeignKeyField($field) != '')
                              && ($this->input[$field] > 0)))
                            && !Fieldblacklist::isFieldBlacklisted(
                                get_class($this),
                                $entities_id,
                                $field,
                                $this->input[$field]
                            )
                        ) {
                            $where[$this->getTable() . '.' . $field] = $this->input[$field];
                        } else {
                            $continue = false;
                        }
                    }

                    if (
                        $continue
                        && count($where)
                    ) {
                        $entities = $fields['entities_id'];
                        if ($fields['is_recursive']) {
                            $entities = getSonsOf('glpi_entities', $fields['entities_id']);
                        }
                        $where[] = getEntitiesRestrictCriteria($this->getTable(), '', $entities);

                        $tmp = clone $this;
                        if ($tmp->maybeTemplate()) {
                            $where['is_template'] = 0;
                        }

                       //If update, exclude ID of the current object
                        if (!$add) {
                            $where['NOT'] = [$this->getTable() . '.id' => $this->input['id']];
                        }

                        $doubles = getAllDataFromTable($this->getTable(), $where);
                        if (count($doubles) > 0) {
                            $message = [];
                            if (
                                $p['unicity_error_message']
                                || $p['add_event_on_duplicate']
                            ) {
                                foreach (explode(',', $fields['fields']) as $field) {
                                    $message[$field] = $this->input[$field];
                                }

                                $message_text = $this->getUnicityErrorMessage($message, $fields, $doubles);
                                if ($p['unicity_error_message']) {
                                    if (!$fields['action_refuse']) {
                                        $show_other_messages = ($fields['action_refuse'] ? true : false);
                                    } else {
                                        $show_other_messages = true;
                                    }
                                    Session::addMessageAfterRedirect(
                                        $message_text,
                                        true,
                                        $show_other_messages,
                                        $show_other_messages
                                    );
                                }
                                if ($p['add_event_on_duplicate']) {
                                    Event::log(
                                        (!$add ? $this->fields['id'] : 0),
                                        get_class($this),
                                        4,
                                        'inventory',
                                        //TRANS: %1$s is the user login, %2$s the message
                                        sprintf(
                                            __('%1$s trying to add an item that already exists: %2$s'),
                                            $_SESSION["glpiname"],
                                            $message_text
                                        )
                                    );
                                }
                            }
                            if ($fields['action_refuse']) {
                                $result = false;
                            }
                            if ($fields['action_notify']) {
                                $params = [
                                    'action_type' => $add,
                                    'action_user' => getUserName(Session::getLoginUserID()),
                                    'entities_id' => $entities_id,
                                    'itemtype'    => get_class($this),
                                    'date'        => $_SESSION['glpi_currenttime'],
                                    'refuse'      => $fields['action_refuse'],
                                    'label'       => $message,
                                    'field'       => $fields,
                                    'double'      => $doubles
                                ];
                                NotificationEvent::raiseEvent('refuse', new FieldUnicity(), $params);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Clean all infos which match some criteria
     *
     * @param array   $crit    array of criteria (ex array('is_active'=>'1'))
     * @param boolean $force   force purge not on put in trashbin (default 0)
     * @param boolean $history do history log ? (true by default)
     *
     * @return boolean
     **/
    public function deleteByCriteria($crit = [], $force = 0, $history = 1)
    {
        global $DB;

        $ok = false;
        if (is_array($crit) && (count($crit) > 0)) {
            $crit['FIELDS'] = [$this::getTable() => 'id'];
            $ok = true;
            $iterator = $DB->request($this->getTable(), $crit);
            foreach ($iterator as $row) {
                if (!$this->delete($row, $force, $history)) {
                    $ok = false;
                }
            }
        }
        return $ok;
    }


    /**
     * get the Entity of an Item
     *
     * @param string  $itemtype item type
     * @param integer $items_id id of the item
     *
     * @return integer ID of the entity or -1
     **/
    public static function getItemEntity($itemtype, $items_id)
    {

        if (
            $itemtype
            && ($item = getItemForItemtype($itemtype))
        ) {
            if ($item->getFromDB($items_id)) {
                return $item->getEntityID();
            }
        }
        return -1;
    }


    /**
     * display a specific field value
     *
     * @since 0.83
     *
     * @param string       $field   name of the field
     * @param string|array $values  with the value to display or a Single value
     * @param array        $options Array of options
     *
     * @return string the string to display
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        switch ($field) {
            case '_virtual_datacenter_position':
                $static = new static();
                if (method_exists($static, 'getDcBreadcrumbSpecificValueToDisplay')) {
                    return $static::getDcBreadcrumbSpecificValueToDisplay($values['id']);
                }
        }

        return '';
    }


    /**
     * display a field using standard system
     *
     * @since 0.83
     *
     * @param integer|string|array $field_id_or_search_options id of the search option field
     *                                                             or field name
     *                                                             or search option array
     * @param mixed                $values                     value to display
     * @param array                $options                    array of possible options:
     * Parameters which could be used in options array :
     *    - comments : boolean / is the comments displayed near the value (default false)
     *    - any others options passed to specific display method
     *
     * @return string the string to display
     **/
    public function getValueToDisplay($field_id_or_search_options, $values, $options = [])
    {
        global $CFG_GLPI;

        $param = [
            'comments' => false,
            'html'     => false,
        ];
        foreach ($param as $key => $val) {
            if (!isset($options[$key])) {
                $options[$key] = $val;
            }
        }

        $searchoptions = [];
        if (is_array($field_id_or_search_options)) {
            $searchoptions = $field_id_or_search_options;
        } else {
            $searchopt = $this->searchOptions();

           // Get if id of search option is passed
            if (is_numeric($field_id_or_search_options)) {
                if (isset($searchopt[$field_id_or_search_options])) {
                    $searchoptions = $searchopt[$field_id_or_search_options];
                }
            } else { // Get if field name is passed
                $searchoptions = $this->getSearchOptionByField(
                    'field',
                    $field_id_or_search_options,
                    $this->getTable()
                );
            }
        }

        $value = $values;

        if (count($searchoptions)) {
            $field = $searchoptions['field'];

           // Normalize option
            if (is_array($values)) {
                $value = $values[$field];
            } else {
                $values = [$field => $value];
            }

            if (isset($searchoptions['datatype'])) {
                $unit = '';
                if (isset($searchoptions['unit'])) {
                    $unit = $searchoptions['unit'];
                }

                switch ($searchoptions['datatype']) {
                    case "count":
                    case "number":
                        if (isset($searchoptions['toadd']) && isset($searchoptions['toadd'][$value])) {
                             return $searchoptions['toadd'][$value];
                        }
                        if ($options['html']) {
                            return Dropdown::getValueWithUnit($value, $unit);
                        }
                        return $value;

                    case "decimal":
                        if ($options['html']) {
                            return Dropdown::getValueWithUnit($value, $unit, $CFG_GLPI["decimal_number"]);
                        }
                        return $value;

                    case "string":
                    case "mac":
                    case "ip":
                        return $value;

                    case "text":
                        if (isset($searchoptions['htmltext']) && $searchoptions['htmltext']) {
                            $value = RichText::getTextFromHtml($value, true, false, true);
                        }

                        return $options['html'] ? nl2br($value) : $value;

                    case "bool":
                        return Dropdown::getYesNo($value);

                    case "date":
                    case "date_delay":
                        if (isset($options['relative_dates']) && $options['relative_dates']) {
                            $dates = Html::getGenericDateTimeSearchItems(['with_time'   => true,
                                'with_future' => true
                            ]);
                            return $dates[$value];
                        }
                        return empty($value) ? $value : Html::convDate(Html::computeGenericDateTimeSearch($value, true));

                    case "datetime":
                        if (isset($options['relative_dates']) && $options['relative_dates']) {
                            $dates = Html::getGenericDateTimeSearchItems(['with_time'   => true,
                                'with_future' => true
                            ]);
                            return $dates[$value];
                        }
                        return empty($value) ? $value : Html::convDateTime(Html::computeGenericDateTimeSearch($value, false));

                    case "timestamp":
                        if (
                            ($value == 0)
                            && isset($searchoptions['emptylabel'])
                        ) {
                            return $searchoptions['emptylabel'];
                        }
                        $withseconds = false;
                        if (isset($searchoptions['withseconds'])) {
                            $withseconds = $searchoptions['withseconds'];
                        }
                        return Html::timestampToString($value, $withseconds);

                    case "email":
                        if ($options['html']) {
                            return "<a href='mailto:$value'>$value</a>";
                        }
                        return $value;

                    case "weblink":
                        $orig_link = trim($value);
                        if (!empty($orig_link)) {
                           // strip begin of link
                            $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                            $link = preg_replace('/\/$/', '', $link);
                            if (Toolbox::strlen($link) > $CFG_GLPI["url_maxlength"]) {
                                $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"]) . "...";
                            }
                            return "<a href=\"" . Toolbox::formatOutputWebLink($orig_link) . "\" target='_blank'>$link" .
                            "</a>";
                        }
                        return "&nbsp;";

                    case "itemlink":
                        if ($searchoptions['table'] == $this->getTable()) {
                            break;
                        }
                       //use dropdown per default

                    case "dropdown":
                        if (isset($searchoptions['toadd']) && isset($searchoptions['toadd'][$value])) {
                            return $searchoptions['toadd'][$value];
                        }
                        if (!is_numeric($value)) {
                            return $value;
                        }

                        if (
                            ($value == 0)
                            && isset($searchoptions['emptylabel'])
                        ) {
                            return $searchoptions['emptylabel'];
                        }

                        if ($searchoptions['table'] == 'glpi_users') {
                            if ($param['comments']) {
                                $tmp = getUserName($value, 2);
                                return $tmp['name'] . '&nbsp;' . Html::showToolTip(
                                    $tmp['comment'],
                                    ['display' => false]
                                );
                            }
                            return getUserName($value);
                        }
                        if ($param['comments']) {
                            $tmp = Dropdown::getDropdownName($searchoptions['table'], $value, 1);
                            return $tmp['name'] . '&nbsp;' . Html::showToolTip(
                                $tmp['comment'],
                                ['display' => false]
                            );
                        }
                        return Dropdown::getDropdownName($searchoptions['table'], $value);

                    case "itemtypename":
                        if ($obj = getItemForItemtype($value)) {
                            return $obj->getTypeName(1);
                        }
                        break;

                    case "language":
                        if (isset($CFG_GLPI['languages'][$value])) {
                            return $CFG_GLPI['languages'][$value][0];
                        }
                        return __('Default value');
                }
            }
           // Get specific display if available
            $itemtype = getItemTypeForTable($searchoptions['table']);
            if ($item = getItemForItemtype($itemtype)) {
                $options['searchopt'] = $searchoptions;
                $specific = $item->getSpecificValueToDisplay($field, $values, $options);
                if (!empty($specific)) {
                    return $specific;
                }
            }
        }
        return $value;
    }

    /**
     * display a specific field selection system
     *
     * @since 0.83
     *
     * @param string       $field   name of the field
     * @param string       $name    name of the select (if empty use linkfield) (default '')
     * @param string|array $values  with the value to select or a Single value (default '')
     * @param array        $options aArray of options
     *
     * @return string the string to display
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        return '';
    }


    /**
     * Select a field using standard system
     *
     * @since 0.83
     *
     * @param integer|string|array $field_id_or_search_options id of the search option field
     *                                                             or field name
     *                                                             or search option array
     * @param string               $name                       name of the select (if empty use linkfield)
     *                                                         (default '')
     * @param mixed                $values                     default value to display
     *                                                         (default '')
     * @param array                $options                    array of possible options:
     * Parameters which could be used in options array :
     *    - comments : boolean / is the comments displayed near the value (default false)
     *    - any others options passed to specific display method
     *
     * @return string the string to display
     **/
    public function getValueToSelect($field_id_or_search_options, $name = '', $values = '', $options = [])
    {
        global $CFG_GLPI;

        $param = [
            'comments' => false,
            'html'     => false,
        ];
        foreach ($param as $key => $val) {
            if (!isset($options[$key])) {
                $options[$key] = $val;
            }
        }

        $searchoptions = [];
        if (is_array($field_id_or_search_options)) {
            $searchoptions = $field_id_or_search_options;
        } else {
            $searchopt = $this->searchOptions();

           // Get if id of search option is passed
            if (is_numeric($field_id_or_search_options)) {
                if (isset($searchopt[$field_id_or_search_options])) {
                    $searchoptions = $searchopt[$field_id_or_search_options];
                }
            } else { // Get if field name is passed
                $searchoptions = $this->getSearchOptionByField(
                    'field',
                    $field_id_or_search_options,
                    $this->getTable()
                );
            }
        }

        $value  = $values;

        if (count($searchoptions)) {
            $field = $searchoptions['field'];
           // Normalize option
            if (is_array($values)) {
                $value = $values[$field];
            } else {
                $values = [$field => $value];
            }

            if (empty($name)) {
                $name = $searchoptions['linkfield'];
            }
           // If not set : set to specific
            if (!isset($searchoptions['datatype'])) {
                $searchoptions['datatype'] = 'specific';
            }

            $options['display'] = false;

            if (isset($options[$searchoptions['table'] . '.' . $searchoptions['field']])) {
                $options = array_merge(
                    $options,
                    $options[$searchoptions['table'] . '.' . $searchoptions['field']]
                );
            }

            switch ($searchoptions['datatype']) {
                case "count":
                case "number":
                case "integer":
                    $copytooption = ['min', 'max', 'step', 'toadd', 'unit'];
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    $options['value'] = $value;
                    return Dropdown::showNumber($name, $options);

                case "decimal":
                case "mac":
                case "ip":
                case "string":
                case "email":
                case "weblink":
                    return Html::input($name, ['value' => $value]);

                case "text":
                    $is_htmltext = isset($searchoptions['htmltext']) && $searchoptions['htmltext'];
                    if ($is_htmltext) {
                        $value = RichText::getSafeHtml($value, true);
                    }

                    return Html::textarea(
                        [
                            'display'           => false,
                            'name'              => $name,
                            'value'             => $value,
                            'enable_fileupload' => false,
                            'enable_richtext'   => $is_htmltext,
                        // For now, this textarea is displayed only in the "update" massive action form, for fields
                        // corresponding to a search option having "htmltext" property.
                        // Uploaded images processing is not able to handle multiple use of same uploaded file, so until this is fixed,
                        // it is preferable to disable image pasting in rich text inside massive actions.
                            'enable_images'     => false,
                            'cols'              => 45,
                            'rows'              => 5,
                        ]
                    );

                case "bool":
                    return Dropdown::showYesNo($name, $value, -1, $options);

                case "color":
                    return Html::showColorField($name, $options);

                case "date":
                case "date_delay":
                    if (isset($options['relative_dates']) && $options['relative_dates']) {
                        if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                             $options['with_future'] = true;
                        }
                        return Html::showGenericDateTimeSearch($name, $value, $options);
                    }
                    $copytooption = ['min', 'max', 'maybeempty', 'showyear'];
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    $options['value'] = $value;
                    return Html::showDateField($name, $options);

                case "datetime":
                    if (isset($options['relative_dates']) && $options['relative_dates']) {
                        if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                             $options['with_future'] = true;
                        }
                        $options['with_time'] = true;
                        return Html::showGenericDateTimeSearch($name, $value, $options);
                    }
                    $copytooption = ['mindate', 'maxdate', 'mintime', 'maxtime',
                        'maybeempty', 'timestep'
                    ];
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    $options['value'] = $value;
                    return Html::showDateTimeField($name, $options);

                case "timestamp":
                    $copytooption = ['addfirstminutes', 'emptylabel', 'inhours',  'max', 'min',
                        'step', 'toadd', 'display_emptychoice'
                    ];
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    $options['value'] = $value;
                    return Dropdown::showTimeStamp($name, $options);

                case "itemlink":
                    if (isset($options['itemlink_as_string']) && $options['itemlink_as_string']) {
                       // Do not use dropdown if wanted to select string value instead of ID
                        break;
                    }
                   //use dropdown case per default

                case "dropdown":
                    $copytooption     = ['condition', 'displaywith', 'emptylabel',
                        'right', 'toadd'
                    ];
                    $options['name']  = $name;
                    $options['value'] = $value;
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    if (!isset($options['entity'])) {
                        $options['entity'] = $_SESSION['glpiactiveentities'];
                    }
                    $itemtype = getItemTypeForTable($searchoptions['table']);

                    return $itemtype::dropdown($options);

                case "right":
                    return Profile::dropdownRights(
                        Profile::getRightsFor($searchoptions['rightclass']),
                        $name,
                        $value,
                        ['multiple' => false,
                            'display'  => false
                        ]
                    );

                case "itemtypename":
                    if (isset($searchoptions['itemtype_list'])) {
                        $options['types'] = $CFG_GLPI[$searchoptions['itemtype_list']];
                    }
                     $copytooption     = ['types'];
                     $options['value'] = $value;
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    if (isset($options['types'])) {
                        return Dropdown::showItemTypes(
                            $name,
                            $options['types'],
                            $options
                        );
                    }
                    return false;

                case "language":
                    $copytooption = ['emptylabel', 'display_emptychoice'];
                    foreach ($copytooption as $key) {
                        if (isset($searchoptions[$key]) && !isset($options[$key])) {
                            $options[$key] = $searchoptions[$key];
                        }
                    }
                    $options['value'] = $value;
                    return Dropdown::showLanguages($name, $options);
            }
           // Get specific display if available
            $itemtype = getItemTypeForTable($searchoptions['table']);
            if ($item = getItemForItemtype($itemtype)) {
                $options['searchopt'] = $searchoptions;
                $specific = $item->getSpecificValueToSelect(
                    $searchoptions['field'],
                    $name,
                    $values,
                    $options
                );
                if (strlen($specific)) {
                     return $specific;
                }
            }
        }
       // default case field text
        return Html::input($name, ['value' => $value]);
    }


    /**
     * @param string  $itemtype Item type
     * @param string  $target   Target
     * @param boolean $add      (default 0)
     *
     * @return false|void
     */
    public static function listTemplates($itemtype, $target, $add = 0)
    {
        global $DB;

        if (!($item = getItemForItemtype($itemtype))) {
            return false;
        }

        if (!$item->maybeTemplate()) {
            return false;
        }

       // Avoid to get old data
        $item->clearSavedInput();

       //Check is user have minimum right r
        if (
            !$item->canView()
            && !$item->canCreate()
        ) {
            return false;
        }

        $request = [
            'FROM'   => $item->getTable(),
            'WHERE'  => [
                'is_template'  => 1
            ],
            'ORDER'  => ['template_name']
        ];

        if ($item->isEntityAssign()) {
            $request['WHERE'] = $request['WHERE'] + getEntitiesRestrictCriteria(
                $item->getTable(),
                'entities_id',
                $_SESSION['glpiactiveentities'],
                $item->maybeRecursive()
            );
        }

        if (Session::isMultiEntitiesMode()) {
            $colspan = 3;
        } else {
            $colspan = 2;
        }

        $iterator = $DB->request($request);
        $blank_params = (strpos($target, '?') ? '&' : '?') . "id=-1&withtemplate=2";
        $target_blank = $target . $blank_params;

        if ($add && count($iterator) == 0) {
           //if there is no template, just use blank
            Html::redirect($target_blank);
        }

        echo "<div class='center'><table class='tab_cadre'>";
        if ($add) {
            echo "<tr><th>" . $item->getTypeName(1) . "</th>";
            echo "<th>" . __('Choose a template') . "</th></tr>";
            echo "<tr><td class='tab_bg_1 center' colspan='$colspan'>";
            echo "<a href=\"" . Html::entities_deep($target_blank) . "\">" . __('Blank Template') . "</a></td>";
            echo "</tr>";
        } else {
            echo "<tr><th>" . $item->getTypeName(1) . "</th>";
            if (Session::isMultiEntitiesMode()) {
                echo "<th>" . Entity::getTypeName(1) . "</th>";
            }
            echo "<th>" . __('Templates') . "</th></tr>";
        }

        foreach ($iterator as $data) {
            $templname = $data["template_name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["template_name"])) {
                $templname = sprintf(__('%1$s (%2$s)'), $templname, $data["id"]);
            }
            if ($item->canCreate() && !$add) {
                $modify_params =
                (strpos($target, '?') ? '&amp;' : '?')
                . "id=" . $data['id']
                . "&amp;withtemplate=1";
                $target_modify = $target . $modify_params;

                echo "<tr><td class='tab_bg_1 center'>";
                echo "<a href=\"$target_modify\">";
                echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
                if (Session::isMultiEntitiesMode()) {
                    $entity = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                    echo "<td class='tab_bg_1 center'>$entity</td>";
                }
                echo "<td class='tab_bg_2 center b'>";
                if ($item->can($data['id'], PURGE)) {
                    Html::showSimpleForm(
                        $target,
                        'purge',
                        _x('button', 'Delete permanently'),
                        ['withtemplate' => 1,
                            'id'           => $data['id']
                        ]
                    );
                }
                echo "</td>";
            } else {
                $add_params =
                (strpos($target, '?') ? '&amp;' : '?')
                . "id=" . $data['id']
                . "&amp;withtemplate=2";
                $target_add = $target . $add_params;

                echo "<tr><td class='tab_bg_1 center' colspan='2'>";
                echo "<a href=\"$target_add\">";
                echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
            }
            echo "</tr>";
        }

        if ($item->canCreate() && !$add) {
            $create_params =
            (strpos($target, '?') ? '&amp;' : '?')
            . "withtemplate=1";
            $target_create = $target . $create_params;
            echo "<tr><td class='tab_bg_2 center b' colspan='3'>";
            echo "<a href=\"$target_create\">" . __('Add a template...') . "</a>";
            echo "</td></tr>";
        }
        echo "</table></div>\n";
    }


    /**
     * Specificy a plugin itemtype for which entities_id and is_recursive should be forwarded
     *
     * @since 0.83
     *
     * @param string $for_itemtype change of entity for this itemtype will be forwarder
     * @param string $to_itemtype  change of entity will affect this itemtype
     *
     * @return void
     **/
    public static function addForwardEntity($for_itemtype, $to_itemtype)
    {
        self::$plugins_forward_entity[$for_itemtype][] = $to_itemtype;
    }


    /**
     * Is entity information forward To ?
     *
     * @since 0.84
     *
     * @param string $itemtype itemtype to check
     *
     * @return boolean
     **/
    public static function isEntityForwardTo($itemtype)
    {

        if (in_array($itemtype, static::$forward_entity_to)) {
            return true;
        }
       //Fill forward_entity_to array with itemtypes coming from plugins
        if (
            isset(static::$plugins_forward_entity[static::getType()])
            && in_array($itemtype, static::$plugins_forward_entity[static::getType()])
        ) {
            return true;
        }
        return false;
    }


    /**
     * Get rights for an item _ may be overload by object
     *
     * @since 0.85
     *
     * @param string $interface (defalt 'central')
     *
     * @return array array of rights to display
     **/
    public function getRights($interface = 'central')
    {

        $values = [CREATE  => __('Create'),
            READ    => __('Read'),
            UPDATE  => __('Update'),
            PURGE   => ['short' => __('Purge'),
                'long'  => _x('button', 'Delete permanently')
            ]
        ];

        $values += ObjectLock::getRightsToAdd(get_class($this), $interface);

        if ($this->maybeDeleted()) {
            $values[DELETE] = ['short' => __('Delete'),
                'long'  => _x('button', 'Put in trashbin')
            ];
        }
        if ($this->usenotepad) {
            $values[READNOTE] = ['short' => __('Read notes'),
                'long' => __("Read the item's notes")
            ];
            $values[UPDATENOTE] = ['short' => __('Update notes'),
                'long' => __("Update the item's notes")
            ];
        }

        return $values;
    }

    /**
     * Generate link(s).
     *
     * @since 9.1
     *
     * @param string        $link       original string content
     * @param CommonDBTM    $item       item used to make replacements
     * @param bool          $safe_url   indicates whether URL should be sanitized or not
     *
     * @return array of link contents (may have several when item have several IP / MAC cases)
     *
     * @FIXME Uncomment $safe_url parameter declaration in GLPI 10.1.
     */
    public static function generateLinkContents($link, CommonDBTM $item/*, bool $safe_url = true*/)
    {
        $safe_url = func_num_args() === 3 ? func_get_arg(2) : true;
        return Link::generateLinkContents($link, $item, $safe_url);
    }


    /**
     * add files from a textarea (from $this->input['content'])
     * or a file input (from $this->input['_filename']) to an CommonDBTM object
     * create document if needed
     * create link from document to CommonDBTM object
     *
     * @since 9.2
     *
     * @param array $input   Input data
     * @param array $options array with theses keys
     *                        - force_update (default false) update the content field of the object
     *                        - content_field (default content) the field who receive the main text
     *                                                          (with images)
     *                        - name (default filename) name of the HTML input containing files
     *                        - date  Date to set on document_items
     * @return array the input param transformed
     **/
    public function addFiles(array $input, $options = [])
    {
        global $CFG_GLPI;

        $default_options = [
            'force_update'  => false,
            'content_field' => 'content',
            'name'          => 'filename',
            'date'          => null,
        ];
        $options = array_merge($default_options, $options);

        $uploadName = '_' . $options['name'];
        $tagUploadName = '_tag_' . $options['name'];
        $prefixUploadName = '_prefix_' . $options['name'];

        if (
            !isset($input[$uploadName])
            || (count($input[$uploadName]) == 0)
        ) {
            return $input;
        }
        $docadded     = [];
        $donotif      = isset($input['_donotif']) ? $input['_donotif'] : 0;
        $disablenotif = isset($input['_disablenotif']) ? $input['_disablenotif'] : 0;

        foreach ($input[$uploadName] as $key => $file) {
            $doc      = new Document();
            $docitem  = new Document_Item();
            $docID    = 0;
            $filename = GLPI_TMP_DIR . "/" . $file;
            $input2   = [];

           //If file tag is present
            if (
                isset($input[$tagUploadName])
                && !empty($input[$tagUploadName][$key])
            ) {
                $input['_tag'][$key] = $input[$tagUploadName][$key];
            }

           //retrieve entity
            $entities_id = isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0;
            if (isset($this->fields["entities_id"])) {
                $entities_id = $this->fields["entities_id"];
            } else if (isset($input['entities_id'])) {
                $entities_id = $input['entities_id'];
            } else if (isset($input['_job']->fields['entities_id'])) {
                $entities_id = $input['_job']->fields['entities_id'];
            }

           //retrieve is_recursive
            $is_recursive = 0;
            if (isset($this->fields["is_recursive"])) {
                $is_recursive = $this->fields["is_recursive"];
            } else if (isset($input['is_recursive'])) {
                $is_recursive = $input['is_recursive'];
            } else if (isset($input['_job']->fields['is_recursive'])) {
                $is_recursive = $input['_job']->fields['is_recursive'];
            }

           // Check for duplicate and availability (e.g. file deleted in _files)
            if ($doc->getDuplicateOf($entities_id, $filename)) {
                $docID = $doc->fields["id"];
               // File already exist, we replace the tag by the existing one
                if (
                    isset($input['_tag'][$key])
                    && ($docID > 0)
                    && isset($input[$options['content_field']])
                ) {
                    $input[$options['content_field']] = str_replace(
                        $input['_tag'][$key],
                        $doc->fields["tag"],
                        $input[$options['content_field']]
                    );
                    $docadded[$docID]['tag'] = $doc->fields["tag"];
                }
                if (!$doc->checkAvailability($filename)) {
                    $doc->update([
                        'id'                      => $docID,
                        '_only_if_upload_succeed' => 1,
                        '_filename'               => [$file],
                        'current_filepath'        => $filename,
                    ]);
                }
            } else {
                if ($this->getType() == 'Ticket') {
                   //TRANS: Default document to files attached to tickets : %d is the ticket id
                    $input2["name"] = addslashes(sprintf(__('Document Ticket %d'), $this->getID()));
                    $input2["tickets_id"] = $this->getID();
                }

                if (isset($input['_tag'][$key])) {
                   // Insert image tag
                    $input2["tag"] = $input['_tag'][$key];
                }

                $input2["entities_id"]             = $entities_id;
                $input2["is_recursive"]            = $is_recursive;
                $input2["documentcategories_id"]   = $CFG_GLPI["documentcategories_id_forticket"];
                $input2["_only_if_upload_succeed"] = 1;
                $input2["_filename"]               = [$file];
                if (isset($this->input[$prefixUploadName][$key])) {
                    $input2[$prefixUploadName]  = [$this->input[$prefixUploadName][$key]];
                }
                $docID = $doc->add($input2);

                if (isset($input['_tag'][$key])) {
                   // Store image tag
                    $docadded[$docID]['tag'] = $doc->fields["tag"];
                }
            }

            if ($docID > 0) {
               // complete doc information
                $docadded[$docID]['data'] = sprintf(
                    __('%1$s - %2$s'),
                    stripslashes($doc->fields["name"]),
                    stripslashes($doc->fields["filename"])
                );
                $docadded[$docID]['filepath'] = $doc->fields["filepath"];

               // add doc - item link
                $toadd = [
                    'documents_id'  => $docID,
                    '_do_notif'     => $donotif,
                    '_disablenotif' => $disablenotif,
                    'itemtype'      => $this->getType(),
                    'items_id'      => $this->getID()
                ];
               // Set date, needed if it differs from the creation date
                $toadd['date'] = $options['date'] ?? $_SESSION['glpi_currenttime'];

                if (isset($input['users_id'])) {
                    $toadd['users_id'] = $input['users_id'];
                }
                if (
                    isset($input[$options['content_field']])
                    && strpos($input[$options['content_field']], $doc->fields["tag"]) !== false
                    && strpos($doc->fields['mime'], 'image/') !== false
                ) {
                    //do not display inline docs in timeline
                    $toadd['timeline_position'] = CommonITILObject::NO_TIMELINE;
                } else {
                    //get timeline_position from parent (followup  / task / doc)
                    if (isset($input['timeline_position'])) {
                        $toadd['timeline_position'] = $input['timeline_position'];
                    }
                }

                $docitem->add($toadd);
            }
           // Only notification for the first New doc
            $donotif = false;
        }

       // manage content transformation
        if (isset($input[$options['content_field']])) {
            $input[$options['content_field']] = Toolbox::convertTagToImage(
                $input[$options['content_field']],
                $this,
                $docadded,
                $options['_add_link'] ?? true,
            );

            if (isset($this->input['_forcenotif'])) {
                $input['_forcenotif'] = $this->input['_forcenotif'];
                unset($input['_disablenotif']);
            }

           // force update of content on add process (we are in post_addItem function)
            if ($options['force_update']) {
                $this->fields[$options['content_field']] = $input[$options['content_field']];
                $this->updateInDB([$options['content_field']]);
            }
        }

        return $input;
    }

    /**
     * Get autofill mark for/from templates
     *
     * @param string $field   Field name
     * @param array  $options Withtemplate parameter
     * @param string $value   Optional value (if field to check is not part of current itemtype)
     *
     * @return string
     */
    public function getAutofillMark($field, $options, $value = null)
    {
        $mark = '';
        $title = null;
        if (($this->isTemplate() || $this->isNewItem()) && $options['withtemplate'] == 1) {
            $title = __s('You can define an autofill template');
        } else if ($this->isTemplate()) {
            if ($value === null) {
                $value = $this->getField($field);
            }
            $len = Toolbox::strlen($value);
            if (
                $len > 8
                && Toolbox::substr($value, 0, 4) === '&lt;'
                && Toolbox::substr($value, $len - 4, 4) === '&gt;'
                && preg_match("/\\#{1,10}/", Toolbox::substr($value, 4, $len - 8))
            ) {
                $title = __s('Autofilled from template');
            } else {
                return '';
            }
        }
        if ($title !== null) {
            $mark = "<i class='fa fa-magic' title='$title'></i>";
        }
        return $mark;
    }

    /**
     * Manage business rules for assets
     *
     * @since 9.4
     *
     * @param boolean $condition the condition (RuleAsset::ONADD or RuleAsset::ONUPDATE)
     *
     * @return void
     */
    private function assetBusinessRules($condition)
    {
        global $CFG_GLPI;

        if ($this->input === false) {
            return;
        }

        // Only process itemtype that are assets
        if (in_array($this->getType(), $CFG_GLPI['asset_types'])) {
            $ruleasset          = new RuleAssetCollection();
            $input              = $this->input;
            $input['_itemtype'] = $this->getType();

            $user = new User();
            if (
                isset($input["users_id"]) && $input["users_id"] != 0
                && $user->getFromDB($input["users_id"])
            ) {
                $group_user  = new Group_User();
                $groups_user = $group_user->find(['users_id' => $input["users_id"]]);
                $input['_groups_id_of_user'] = [];
                foreach ($groups_user as $group) {
                    $input['_groups_id_of_user'][] = $group['groups_id'];
                }
                $input['_locations_id_of_user']      = $user->fields['locations_id'];
                $input['_default_groups_id_of_user'] = $user->fields['groups_id'];
            }

            // If _auto is not defined : it's a manual process : set it's value to 0
            if (!isset($this->input['_auto'])) {
                $input['_auto'] = 0;
            }

            //if agent exist pass the 'tag' to RuleAssetCollection
            if (
                Toolbox::hasTrait($this, \Glpi\Features\Inventoriable::class)
                && method_exists($this, 'getInventoryAgent')
            ) {
                $agent = $this->getInventoryAgent();
                if ($agent !== null) {
                    $input['_tag'] = $agent->fields['tag'];
                }
            }

            // Set the condition (add or update)
            $output = $ruleasset->processAllRules($input, [], [], [
                'condition' => $condition
            ]);

            // If at least one rule has matched
            if (isset($output['_rule_process'])) {
                foreach ($output as $key => $value) {
                    if ($key == '_rule_process' || $key == '_no_rule_matches') {
                        continue;
                    }
                    // Add the rule output to the input array
                    $this->input[$key] = $value;
                }
            }
        }
    }

    /**
     * Ensure the relation would not create a circular parent-child relation.
     * @since 9.5.0
     * @param int    $items_id The ID of the item to evaluate.
     * @param int    $parents_id  The wanted parent of the specified item.
     * @return bool True if there is a circular relation.
     */
    public static function checkCircularRelation($items_id, $parents_id)
    {
        global $DB;

        $fk = static::getForeignKeyField();
        if ($items_id == 0 || $parents_id == 0 || !$DB->fieldExists(static::getTable(), $fk)) {
            return false;
        }

        $next_parent = $parents_id;
        while ($next_parent > 0) {
            if ($next_parent == $items_id) {
               // This item is a parent higher up
                return true;
            }
            $iterator = $DB->request([
                'SELECT' => [$fk],
                'FROM'   => static::getTable(),
                'WHERE'  => ['id' => $next_parent]
            ]);
            if ($iterator->count()) {
                $next_parent = $iterator->current()[$fk];
            } else {
               // Invalid parent
                return false;
            }
        }
       // No circular relations
        return false;
    }

    /**
     * Get incidents, request, changes and problem linked to this object
     *
     * @return array
     */
    public function getITILTickets(bool $count = false)
    {
        $ticket = new Ticket();
        $problem = new Problem();
        $change = new Change();

        $data = [
            'incidents' => iterator_to_array(
                $ticket->getActiveTicketsForItem(
                    get_class($this),
                    $this->getID(),
                    Ticket::INCIDENT_TYPE
                ),
                false
            ),
            'requests'  => iterator_to_array(
                $ticket->getActiveTicketsForItem(
                    get_class($this),
                    $this->getID(),
                    Ticket::DEMAND_TYPE
                ),
                false
            ),
            'changes'   => iterator_to_array(
                $change->getActiveChangesForItem(
                    get_class($this),
                    $this->getID()
                ),
                false
            ),
            'problems'  => iterator_to_array(
                $problem->getActiveProblemsForItem(
                    get_class($this),
                    $this->getID()
                ),
                false
            )
        ];

        if ($count) {
            $data['count'] = count($data['incidents'])
            + count($data['requests'])
            + count($data['changes'])
            + count($data['problems']);
        }

        return $data;
    }

    public static function getIcon()
    {
        return "fas fa-empty-icon";
    }

    /**
     * Get cache key containing raw name for a given itemtype and id
     *
     * @since 9.5
     *
     * @param string  $itemtype
     * @param int     $id
     */
    public static function getCacheKeyForFriendlyName($itemtype, $id)
    {
        return "raw_name__{$itemtype}__{$id}";
    }

    /**
     * Get friendly name by items id
     * The purpose of this function is to try to access the friendly name
     * without having to read the object from the database
     *
     * @since 9.5
     *
     * @param int $id
     *
     * @return string Friendly name of the object
     */
    public static function getFriendlyNameById($id)
    {
        $item = new static();
        $item->getFromDB($id);
        return $item->getFriendlyName();
    }

    /**
     * Return the computed friendly name and set the cache.
     *
     * @since 9.5
     *
     * @return string
     */
    final public function getFriendlyName()
    {
        return $this->computeFriendlyName();
    }

    /**
     * Compute the friendly name of the object
     *
     * @since 9.5
     *
     * @return string
     */
    protected function computeFriendlyName()
    {
        if (isset($this->fields[static::getNameField()])) {
            return $this->fields[static::getNameField()];
        }
        return '';
    }

    /**
     * Retrieve an item from the database
     *
     * @param integer $ID ID of the item to get
     *
     * @return static|boolean false on failure
     */
    public static function getById(?int $id)
    {
        if (is_null($id)) {
            return false;
        }

        $item = new static();

        if (!$item->getFromDB($id)) {
            return false;
        }

        return $item;
    }

    /**
     * Correct entity id if needed when cloning a template
     *
     * @param array  $data
     * @param string $parent_field
     *
     * @return array
     */
    public static function checkTemplateEntity(
        array $data,
        $parent_id,
        $parent_itemtype
    ) {
       // No entity field -> no modification needed
        if (!isset($data['entities_id'])) {
            return $data;
        }

       // If the entity used in the template in not allowed for our current user,
       // fallback to the parent template entity
        if (!Session::haveAccessToEntity($data['entities_id'])) {
           // Load parent
            $parent = new $parent_itemtype();

            if (!$parent->getFromDB($parent_id)) {
               // Can't load parent -> no modification
                return $data;
            }

            $data['entities_id'] = $parent->getEntityID();
        }

        return $data;
    }

    /**
     * Friendly names may uses multiple fields (e.g user: first name + last name)
     * Return the computed criteria to use in a WHERE clause.
     *
     * @param string $filter
     * @return array
     */
    public static function getFriendlyNameSearchCriteria(string $filter): array
    {
        $table      = static::getTable();
        $name_field = static::getNameField();
        $name       = DBmysql::quoteName("$table.$name_field");
        $filter     = strtolower($filter);

        return [
            'RAW' => [
                "LOWER($name)" => ['LIKE', "%$filter%"],
            ]
        ];
    }

    /**
     * Friendly names may uses multiple fields (e.g user: first name + last name)
     * Return the computed field name to use in a SELECT clause.
     *
     * @param string $alias
     * @return mixed
     */
    public static function getFriendlyNameFields(string $alias = "name")
    {
        $table = static::getTable();
        $name_field = static::getNameField();

        return "$table.$name_field AS $alias";
    }

    /**
     * Get non logged fields
     *
     * @return array
     */
    public function getNonLoggedFields(): array
    {
        return [];
    }

    /**
     * Returns model class, or null if item has no model class.
     *
     * @return string|null
     */
    public function getModelClass(): ?string
    {
        $model_class = get_called_class() . 'Model';
        if (!is_a($model_class, CommonDBTM::class, true)) {
            return null;
        }

        $model_fk = $model_class::getForeignKeyField();
        return $this->isField($model_fk) ? $model_class : null;
    }

    /**
     * Returns model class foreign key field name, or null if item has no model class.
     *
     * @return string|null
     */
    public function getModelForeignKeyField(): ?string
    {
        $model_class = $this->getModelClass();
        return $model_class !== null ? $model_class::getForeignKeyField() : null;
    }

    /**
     * Returns type class, or null if item has no type class.
     *
     * @return string|null
     */
    public function getTypeClass(): ?string
    {
        $type_class = get_called_class() . 'Type';
        if (!is_a($type_class, CommonDBTM::class, true)) {
            return null;
        }

        $type_fk = $type_class::getForeignKeyField();
        return $this->isField($type_fk) ? $type_class : null;
    }

    /**
     * Returns type class foreign key field name, or null if item has no type class.
     *
     * @return string|null
     */
    public function getTypeForeignKeyField(): ?string
    {
        $type_class = $this->getTypeClass();
        return $type_class !== null ? $type_class::getForeignKeyField() : null;
    }

    public function hasItemtypeOrModelPictures(array $picture_fields = ['picture_front', 'picture_rear', 'pictures']): bool
    {
        $itemtype = $this->getType();
        $modeltype = $itemtype . "Model";
        $fk = getForeignKeyFieldForItemType($modeltype);
        $has_model = class_exists($modeltype) && isset($this->fields[$fk]) && $this->fields[$fk] > 0;
        if ($has_model) {
            /** @var CommonDBTM $model */
            $model = new $modeltype();
        }

        $has_pictures = false;

        foreach ($picture_fields as $picture_field) {
            if ($this->isField($picture_field)) {
                if ($picture_field === 'pictures') {
                    $urls = importArrayFromDB($this->fields[$picture_field]);
                    if (!empty($urls)) {
                        $has_pictures = true;
                        break;
                    }
                } else if (!empty($this->fields[$picture_field])) {
                    $has_pictures = true;
                    break;
                }
            }
        }
        if (!$has_pictures && $has_model && $model->getFromDB(($this->fields[$fk]))) {
            foreach ($picture_fields as $picture_field) {
                if ($model->isField($picture_field)) {
                    if ($picture_field === 'pictures') {
                        $urls = importArrayFromDB($model->fields[$picture_field]);
                        if (!empty($urls)) {
                             $has_pictures = true;
                             break;
                        }
                    } else if (!empty($model->fields[$picture_field])) {
                        $has_pictures = true;
                        break;
                    }
                }
            }
        }
        return $has_pictures;
    }

    public function getItemtypeOrModelPicture(string $picture_field = 'picture_front', array $params = []): array
    {
        $p = [
            'thumbnail_w'  => 'auto',
            'thumbnail_h'  => 'auto'
        ];
        $p = array_replace($p, $params);

        $urls = [];
        $itemtype = $this->getType();
        $pictures = [];
        $clearable = false;

        if ($this->isField($picture_field)) {
            if ($picture_field === 'pictures') {
                $urls = importArrayFromDB($this->fields[$picture_field]);
            } else {
                $urls = [$this->fields[$picture_field]];
            }
            $clearable = $this->canUpdate();
        } else {
            $modeltype = $itemtype . "Model";
            if (class_exists($modeltype)) {
                /** @var CommonDBTM $model */
                $model = new $modeltype();
                if (!$model->isField($picture_field)) {
                    return [];
                }

                $fk = getForeignKeyFieldForItemType($modeltype);
                if ($model->getFromDB(($this->fields[$fk]) ?? 0)) {
                    if ($picture_field === 'pictures') {
                        $urls = importArrayFromDB($model->fields[$picture_field]);
                    } else {
                        $urls = [$model->fields[$picture_field]];
                    }
                }
            }
        }

        foreach ($urls as $url) {
            if (!empty($url)) {
                $resolved_url = \Toolbox::getPictureUrl($url);
                $src_file = GLPI_PICTURE_DIR . '/' . $url;
                if (file_exists($src_file)) {
                    $size = getimagesize($src_file);
                    $pictures[] = [
                        'src'             => $resolved_url,
                        'w'               => $size[0],
                        'h'               => $size[1],
                        'clearable'       => $clearable,
                        '_is_model_img'   => isset($model)
                    ] + $p;
                } else {
                    $owner_type = isset($model) ? $model::getType() : $itemtype;
                    $owner_id = isset($model) ? $model->getID() : $this->getID();

                    trigger_error(
                        "The picture '{$src_file}' referenced by the {$owner_type} with ID {$owner_id} does not exist",
                        E_USER_WARNING
                    );
                }
            }
        }

        return $pictures;
    }

    public function getMassiveActionsForItem(): MassiveAction
    {
        $params = [
            'item' => [
                $this->getType() => [
                    $this->fields['id'] => 1
                ]
            ]
        ];
        if ($this->isEntityAssign()) {
            $params['entity_restrict'] = $this->getEntityID();
        }

        return new MassiveAction($params, [], 'initial', $this->getID());
    }

    /**
     * Check whether actions are allowed for given item.
     */
    public static function isMassiveActionAllowed(int $items_id): bool
    {
        return true;
    }

    /**
     * Automatically update 1-N links tables for the current item.
     *
     * @param string $commondb_relation Valid class extending CommonDBRelation
     * @param string $field             Target field in the item input
     * @param array  $extra_input       Fixed value to be used when searching
     *                                  for existing values or inserting new ones
     *
     * @return void
     */
    protected function update1NTableData(
        string $commondb_relation,
        string $field,
        array $extra_input = []
    ): void {
        // Check $commondb_connexity parameter
        if (!is_a($commondb_relation, CommonDBRelation::class, true)) {
            $error = "$commondb_relation is not a CommonDBRelation item";
            throw new InvalidArgumentException($error);
        }

        /** @var CommonDBRelation */
        $commondb_relation = new $commondb_relation();

        // Compute which item is item_1 and item_2
        $relation_position = $commondb_relation::getMemberPosition(static::class);
        if ($relation_position == 1) {
            $item_1_fk = $commondb_relation::$items_id_1;
            $item_1_id = $this->getID();
            $item_2_fk = $commondb_relation::$items_id_2;
        } elseif ($relation_position == 2) {
            $item_1_fk = $commondb_relation::$items_id_2;
            $item_1_id = $this->getID();
            $item_2_fk = $commondb_relation::$items_id_1;
        } else {
            $error = static::class . " is not part of the " . get_class($commondb_relation) . " relation";
            throw new InvalidArgumentException($error);
        }

        // Get input value
        $input_value = $this->input[$field] ?? null;

        // See dropdownField twig macro, needed for empty values as an empty
        // array wont be sent in the HTML form
        $input_defined = (bool) ($this->input["_{$field}_defined"] ?? false);

        // Load existing value
        $existing_relations = $commondb_relation->find(
            array_merge($extra_input, [
                $item_1_fk => $item_1_id,
            ])
        );

        // Case 1: no updates -> do nothing
        if ($input_value === null && !$input_defined) {
            return;
        }

        // Case 2: input was emptied -> remove all values
        if (
            ($input_value === null && $input_defined)
            || (is_array($input_value) && ! count($input_value))
        ) {
            foreach ($existing_relations as $relation) {
                $success = $commondb_relation->delete([
                    'id' => $relation['id']
                ]);
                if (!$success) {
                    $warning = "Failed to delete " . get_class($commondb_relation);
                    trigger_error($warning, E_USER_WARNING);
                }
            }
            return;
        }

        // Case 3: input was maybe modified -> delete missing values and add new ones
        foreach ($existing_relations as $relation) {
            $item_2_id = $relation[$item_2_fk];
            // Delete missing value
            if (!in_array($item_2_id, $input_value)) {
                $success = $commondb_relation->delete([
                    'id' => $relation['id']
                ]);
                if (!$success) {
                    $warning = "Failed to delete " . get_class($commondb_relation);
                    trigger_error($warning, E_USER_WARNING);
                }
            }
        }

        // Get existing values
        $item_2_ids_db = array_column($existing_relations, $item_2_fk);

        // Add new values
        foreach ($input_value as $item_2_id) {
            if (in_array($item_2_id, $item_2_ids_db)) {
                // Value exist
                continue;
            }

            $success = $commondb_relation->add(array_merge($extra_input, [
                $item_1_fk => $item_1_id,
                $item_2_fk => $item_2_id
            ]));
            if (!$success) {
                $warning = "Failed to add " . get_class($commondb_relation);
                trigger_error($warning, E_USER_WARNING);
            }
        }

        unset($this->input[$field]);
    }

    /**
     * Automatically load 1-N links values for the current item.
     *
     * @param string $commondb_relation Valid class extending CommonDBRelation
     * @param string $field             Target field in the item input
     * @param array  $extra_input       Fixed value to be used when searching
     *                                  for existing valuess
     *
     * @return void
     */
    protected function load1NTableData(
        string $commondb_relation,
        string $field,
        array $extra_input = []
    ): void {
        // Check $commondb_connexity parameter
        if (!is_a($commondb_relation, CommonDBRelation::class, true)) {
            $error = "$commondb_relation is not a CommonDBRelation item";
            throw new InvalidArgumentException($error);
        }

        /** @var CommonDBRelation */
        $commondb_relation = new $commondb_relation();

        // Compute which item is item_1 and item_2
        $relation_position = $commondb_relation::getMemberPosition(static::class);
        if ($relation_position == 1) {
            $item_1_fk = $commondb_relation::$items_id_1;
            $item_1_id = $this->getID();
            $item_2_fk = $commondb_relation::$items_id_2;
        } elseif ($relation_position == 2) {
            $item_1_fk = $commondb_relation::$items_id_2;
            $item_1_id = $this->getID();
            $item_2_fk = $commondb_relation::$items_id_1;
        } else {
            $error = static::class . " is not part of the " . get_class($commondb_relation) . " relation";
            throw new InvalidArgumentException($error);
        }

        // Load existing value
        $existing_relations = $commondb_relation->find(
            array_merge($extra_input, [
                $item_1_fk => $item_1_id,
            ])
        );

        $this->fields[$field] = array_column($existing_relations, $item_2_fk);
    }

    /**
     * Display an error page (item not found)
     *
     * @param array $menus Menu path used to load specific JS file and show
     *                     breadcumbs, see $CFG_GLPI['javascript'] and
     *                     Html::includeHeader()
     *
     * @return void
     */
    public static function displayItemNotFoundPage(array $menus): void
    {
        $helpdesk = Session::getCurrentInterface() == "helpdesk";
        $title = __('Item not found');

        if (!$helpdesk) {
            static::displayCentralHeader($title, $menus);
        } else {
            static::displayHelpdeskHeader($title, $menus);
        }

        Html::displayNotFoundError('The item could not be found in the database');
    }

    /**
     * Display an error page (access denied)
     *
     * @param array $menus   Menu path used to load specific JS file and show
     *                       breadcumbs, see $CFG_GLPI['javascript'] and
     *                       Html::includeHeader()
     * @param string $additional_info Additional information about the error for the access log
     * @return void
     */
    public static function displayAccessDeniedPage(array $menus, string $additional_info = ''): void
    {
        $helpdesk = Session::getCurrentInterface() == "helpdesk";
        $title = __('Access denied');

        if (!$helpdesk) {
            Toolbox::handleProfileChangeRedirect();
            static::displayCentralHeader($title, $menus);
        } else {
            static::displayHelpdeskHeader($title, $menus);
        }

        Html::displayRightError($additional_info);
    }

    /**
     * Get the browser tab name for a new item: "{itemtype} - New item"
     * To be overriden by child classes if they want to display something else
     *
     * @return string
     */
    public static function getBrowserTabNameForNewItem(): string
    {
        return sprintf(
            __('%1$s - %2$s'),
            static::getTypeName(1),
            __("New item")
        );
    }

    /**
     * Get the browser tab name for an item: "{itemtype} - {header name}"
     * {Header name} is usually the item name (see $this->getName())
     * To be overriden by child classes if they want to display something else
     *
     * @return string
     */
    public function getBrowserTabName(): string
    {
        return sprintf(
            __('%1$s - %2$s'),
            static::getTypeName(1),
            $this->getHeaderName()
        );
    }

    /**
     * Display a full helpdesk page (header + content + footer) for a given item
     *
     * @param int|string  $id      Id of the item to be displayed, may be a
     *                             string due to some weird default values.
     *                             Will be cast to int straight away.
     * @param null|array  $menus   Menu path used to load specific JS file and
     *                             show breadcumbs, see $CFG_GLPI['javascript']
     *                             and Html::includeHeader()
     *                             Three possible formats:
     *                             - [menu 1, menu 2, menu 3]
     *                             - [
     *                                'central'  => [menu 1, menu 2, menu 3],
     *                                'helpdesk' => [menu 1, menu 2, menu 3],
     *                               ]
     *                             - null (use auto computed values, mainly
     *                             used for children of commondropdown that can
     *                             define their menus as object properties)
     * @param array      $options  Display options
     *
     * @return void
     */
    public static function displayFullPageForItem(
        $id,
        ?array $menus = null,
        array $options = []
    ): void {
        \Glpi\Debug\Profiler::getInstance()->start(static::class . '::displayFullPageForItem');
        $id = (int) $id;
        $item = new static();

        $menus = is_array($menus) ? $menus : [];

        // Check current interface
        $interface = Session::getCurrentInterface();
        if ($interface !== false && isset($menus[$interface])) {
            // Load specific menus for this interface
            $menus = $menus[$interface];
        }

        if (static::isNewID($id)) {
            // New item, check create rights
            if (!static::canCreate()) {
                static::displayAccessDeniedPage($menus, 'Missing CREATE right. Cannot view the new item form.');
                \Glpi\Debug\Profiler::getInstance()->stop(static::class . '::displayFullPageForItem');
                return;
            }

            // Tab name will be generic (item isn't saved yet)
            $title = static::getBrowserTabNameForNewItem();
        } else {
            // Existing item, try to load it and check read rights
            if (!$item->getFromDB($id)) {
                static::displayItemNotFoundPage($menus);
                \Glpi\Debug\Profiler::getInstance()->stop(static::class . '::displayFullPageForItem');
                return;
            }

            if (!$item->can($id, READ)) {
                static::displayAccessDeniedPage($menus, 'Missing READ right. Cannot view the item.');
                \Glpi\Debug\Profiler::getInstance()->stop(static::class . '::displayFullPageForItem');
                return;
            }

            // Tab name will be specific to the loaded item
            $title = $item->getBrowserTabName();
        }

        // Show header
        if ($interface == 'central') {
            \Glpi\Debug\Profiler::getInstance()->start(static::class . '::displayCentralHeader');
            static::displayCentralHeader($title, $menus);
            \Glpi\Debug\Profiler::getInstance()->stop(static::class . '::displayCentralHeader');
        } else {
            static::displayHelpdeskHeader($title, $menus);
        }

        if (!isset($options['id'])) {
            $options['id'] = $id;
        }
        // Show item
        $options['loaded'] = true;
        \Glpi\Debug\Profiler::getInstance()->start(static::class . '::display');
        $item->display($options);
        \Glpi\Debug\Profiler::getInstance()->stop(static::class . '::display');

        // Display extra html if needed
        if (!empty($options['after_display'] ?? "")) {
            echo $options['after_display'];
        }

        // Show footer
        if ($interface == 'central') {
            // No need to stop profiler here. The footer ends every section still running.
            Html::footer();
        } else {
            Html::helpFooter();
        }
    }

    /**
     * Display a header for the "central" interface
     *
     * @param null|string  $title
     * @param array|string $menus
     *
     * @return void
     */
    public static function displayCentralHeader(
        ?string $title = null,
        ?array $menus = null
    ): void {
        // Default title if not specified: current itemtype
        if (is_null($title)) {
            $title = static::getTypeName(1);
        }

        \Glpi\Debug\Profiler::getInstance()->start('Html::header');
        Html::header(
            $title,
            $_SERVER['PHP_SELF'],
            $menus[0] ?? 'none',
            $menus[1] ?? 'none',
            $menus[2] ?? '',
            false
        );
        \Glpi\Debug\Profiler::getInstance()->stop('Html::header');
    }

    /**
     * Display a header for the "helpdesk" interface
     *
     * @param null|string  $title
     * @param array|string $menus
     *
     * @return void
     */
    public static function displayHelpdeskHeader(
        ?string $title = null,
        ?array $menus = null
    ): void {
        // Default title if not specified: itemtype
        if (is_null($title)) {
            $title = static::getTypeName(1);
        }

        Html::helpHeader(
            $title,
            $menus[0] ?? 'self-service',
            $menus[1] ?? 'none',
            $menus[2] ?? '',
            false
        );
    }

    /**
     * Delete alerts of given types related to current item.
     *
     * @param array $types
     *
     * @return void
     *
     * @since 10.0.0
     */
    final public function cleanAlerts(array $types): void
    {
        if (in_array('date_expiration', $this->updates)) {
            $input = [
                'type'     => $types,
                'itemtype' => $this->getType(),
                'items_id' => $this->fields['id'],
            ];
            $alert = new Alert();
            $alert->deleteByCriteria($input, 1);
        }
    }

    public function isGlobal(): bool
    {
        if (!$this->isField('is_global')) {
            return false;
        }

        $confname = strtolower($this->gettype()) . 's_management_restrict';
        if (\Config::getConfigurationValue('core', $confname) == Config::GLOBAL_MANAGEMENT) {
            $is_global = true;
        } else if (\Config::getConfigurationValue('core', $confname) == Config::UNIT_MANAGEMENT) {
            $is_global = false;
        } else {
            $is_global = ($this->fields['is_global'] ?? false) == 1;
        }

        return $is_global;
    }

    /**
     * Return reference event name for given event.
     *
     * @param string $event
     *
     * @since 10.0.7
     */
    public static function getMessageReferenceEvent(string $event): ?string
    {
        switch ($event) {
            case 'new':
            case 'update':
            case 'delete':
            case 'user_mention':
                // Add the CRUD actions and the `user_mention` notifications to thread instanciated by `new` event
                $reference_event = 'new';
                break;
            default:
                // Other actions should have their own thread
                $reference_event = null;
                break;
        }
        return $reference_event;
    }
}
