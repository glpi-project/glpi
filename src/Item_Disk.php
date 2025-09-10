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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

/**
 * Disk Class
 **/
class Item_Disk extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = true;

    // Encryption status
    public const ENCRYPTION_STATUS_NO = 0;
    public const ENCRYPTION_STATUS_YES = 1;
    public const ENCRYPTION_STATUS_PARTIALLY = 2;

    public static function getTypeName($nb = 0)
    {
        return _n('Volume', 'Volumes', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-server-2';
    }

    public function post_getEmpty()
    {
        $this->fields["totalsize"] = '0';
        $this->fields["freesize"]  = '0';
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // can exists for template
        if (
            ($item instanceof CommonDBTM)
            && $item::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    [
                        'items_id'     => $item->getID(),
                        'itemtype'     => $item->getType(),
                        'is_deleted'   => 0,
                    ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM) {
            return self::showForItem($item, $withtemplate);
        }
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    /**
     * Print the version form
     *
     * @param integer $ID ID of the item
     * @param array $options
     *     - target for the Form
     *     - itemtype type of the item for add process
     *     - items_id ID of the item for add process
     *
     * @return boolean true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        $itemtype = null;
        if (isset($options['itemtype']) && !empty($options['itemtype'])) {
            $itemtype = $options['itemtype'];
        } elseif (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
        } else {
            throw new RuntimeException('Unable to retrieve itemtype');
        }

        if (!is_a($itemtype, CommonDBTM::class, true)) {
            throw new RuntimeException(sprintf(
                'Item type %s is not a valid item type',
                $itemtype
            ));
        }

        if (!Session::haveRight($itemtype::$rightname, READ)) {
            return false;
        }

        $asset_item = new $itemtype();
        if ($ID > 0) {
            $this->check($ID, READ);
            $asset_item->getFromDB($this->fields['items_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $asset_item->getFromDB($options['items_id']);
        }

        $itemtype = $this->fields['itemtype'];
        $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);

        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('components/form/item_disk.html.twig', [
            'item'                      => $this,
            'asset_item'                => $asset_item,
            'encryption_status_list'    => self::getAllEncryptionStatus(),
            'params'                    => $options,
        ]);

        return true;
    }

    /**
     * Get disks related to a given item
     *
     * @param CommonDBTM $item  Item instance
     * @param string     $sort  Field to sort on
     * @param string     $order Sort order
     *
     * @return DBmysqlIterator
     */
    public static function getFromItem(CommonDBTM $item, $sort = null, $order = null): DBmysqlIterator
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => [
                Filesystem::getTable() . '.name AS fsname',
                self::getTable() . '.*',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                Filesystem::getTable() => [
                    'FKEY' => [
                        self::getTable()        => 'filesystems_id',
                        Filesystem::getTable()  => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'itemtype'     => $item->getType(),
                'items_id'     => $item->fields['id'],
            ],
        ]);
        return $iterator;
    }

    /**
     * Print the disks
     *
     * @param CommonDBTM $item          Item object
     * @param integer    $withtemplate  Template or basic item (default 0)
     *
     * @return bool
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0): bool
    {
        $ID = $item->getID();
        $rand = mt_rand();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            $link = self::getFormURL() . '?itemtype=' . $item::class . '&items_id=' . $ID . '&withtemplate=' . (int) $withtemplate;
            echo "<div class='mt-1 mb-3 text-center'>"
               . "<a class='btn btn-primary' href='" . htmlescape($link) . "'>";
            echo __s('Add a volume');
            echo "</a></div>\n";
        }

        $iterator = self::getFromItem($item);

        $disk = new self();
        $entries = [];
        foreach ($iterator as $data) {
            $disk->getFromResultSet($data);
            $used = $data['totalsize'] - $data['freesize'];
            $usedpercent = 0;
            if ($data['totalsize'] > 0) {
                $usedpercent = round(100 * $used / $data['totalsize']);
            }

            $encryption_label = '';
            if ($data['encryption_status'] !== self::ENCRYPTION_STATUS_NO) {
                $twig_params = [
                    'encryption_status_label'    => __('Partial encryption'),
                    'encryption_status_value'    => Dropdown::getYesNo($data['encryption_status'] === self::ENCRYPTION_STATUS_YES),
                    'encryption_tool_label'      => __('Encryption tool'),
                    'encryption_tool_value'      => $data['encryption_tool'],
                    'encryption_algorithm_label' => __('Encryption algorithm'),
                    'encryption_algorithm_value' => $data['encryption_algorithm'],
                    'encryption_type_label'      => __('Encryption type'),
                    'encryption_type_value'      => $data['encryption_type'],
                ];
                $encryptionTooltip = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    <strong>{{ encryption_status_label }}</strong> : {{ encryption_status_value }}<br/>
                    <strong>{{ encryption_tool_label }}</strong> : {{ encryption_tool_value }}</br>
                    <strong>{{ encryption_algorithm_label }}</strong> : {{ encryption_algorithm_value }}<br/>
                    <strong>{{ encryption_type_label }}</strong> : {{ encryption_type_value }}
TWIG, $twig_params);

                $encryption_label = Html::showTooltip($encryptionTooltip, [
                    'awesome-class' => "ti ti-lock-password",
                    'display' => false,
                ]);
            }
            $entries[] = [
                'itemtype' => self::class,
                'id' => $data['id'],
                'name' => $disk->getLink(),
                'is_dynamic' => Dropdown::getYesNo($data['is_dynamic']),
                'device' => $data['device'],
                'mountpoint' => $data['mountpoint'],
                'fsname' => $data['fsname'],
                'totalsize' => $data['totalsize'] * 1024 * 1024, //size in MiB in DB
                'freesize' => $data['freesize'] * 1024 * 1024, //size in MiB in DB
                'usedpercent' => $usedpercent,
                'encryption_status' => $encryption_label,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'is_dynamic' => __('Automatic inventory'),
                'device' => __('Partition'),
                'mountpoint' => __('Mount point'),
                'fsname' => Filesystem::getTypeName(1),
                'totalsize' => __('Global size'),
                'freesize' => __('Free size'),
                'usedpercent' => __('Used percentage'),
                'encryption_status' => __('Encryption'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'totalsize' => 'bytesize',
                'freesize' => 'bytesize',
                'usedpercent' => 'progress',
                'encryption_status' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);

        return true;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'device',
            'name'               => __('Partition'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'mountpoint',
            'name'               => __('Mount point'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'totalsize',
            'unit'               => 'auto',
            'name'               => __('Global size'),
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'freesize',
            'unit'               => 'auto',
            'name'               => __('Free size'),
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        global $DB;
        $tab = [];

        $name = _n('Volume', 'Volumes', Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'disk',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '156',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '150',
            'table'              => self::getTable(),
            'field'              => 'totalsize',
            'unit'               => 'auto',
            'name'               => __('Global size'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '151',
            'table'              => self::getTable(),
            'field'              => 'freesize',
            'unit'               => 'auto',
            'name'               => __('Free size'),
            'forcegroupby'       => true,
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '152',
            'table'              => self::getTable(),
            'field'              => 'freepercent',
            'name'               => __('Free percentage'),
            'forcegroupby'       => true,
            'datatype'           => 'progressbar',
            'width'              => 2,
            // NULLIF -> avoid divizion by zero by replacing it by null (division by null return null without warning)
            'computation'        => QueryFunction::lpad(
                expression: QueryFunction::round(new QueryExpression('100*TABLE.freesize/' . QueryFunction::nullif('TABLE.totalsize', new QueryExpression('0')))),
                length: 3,
                pad_string: '0'
            ),
            'computationgroupby' => true,
            'unit'               => '%',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '153',
            'table'              => self::getTable(),
            'field'              => 'mountpoint',
            'name'               => __('Mount point'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '154',
            'table'              => self::getTable(),
            'field'              => 'device',
            'name'               => __('Partition'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '155',
            'table'              => 'glpi_filesystems',
            'field'              => 'name',
            'name'               => Filesystem::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '174',
            'table'              => self::getTable(),
            'field'              => 'encryption_status',
            'name'               => __('Encryption status'),
            'searchtype'         => 'equals',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'searchequalsonfield' => true,
            'datatype'           => 'specific',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '175',
            'table'              => self::getTable(),
            'field'              => 'encryption_tool',
            'name'               => __('Encryption tool'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '176',
            'table'              => self::getTable(),
            'field'              => 'encryption_algorithm',
            'name'               => __('Encryption algorithm'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '177',
            'table'              => self::getTable(),
            'field'              => 'encryption_type',
            'name'               => __('Encryption type'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        return $tab;
    }

    /**
     * Get all the possible value for the "encryption_status" field
     *
     * @return array The list of possible values
     */
    public static function getAllEncryptionStatus()
    {
        return [
            self::ENCRYPTION_STATUS_NO          => __('Not encrypted'),
            self::ENCRYPTION_STATUS_PARTIALLY   => __('Partially encrypted'),
            self::ENCRYPTION_STATUS_YES         => __('Encrypted'),
        ];
    }

    /**
     * Get the correct label for each encryption status
     *
     * @param integer $status The status
     * @phpstan-param self::ENCRYPTION_STATUS_* $status
     * @return string The appropriate label
     */
    public static function getEncryptionStatus($status)
    {
        if ($status === "") {
            return NOT_AVAILABLE;
        }

        $all = self::getAllEncryptionStatus();
        if (!isset($all[$status])) {
            trigger_error(
                sprintf(
                    'Encryption status %1$s does not exists!',
                    $status
                ),
                E_USER_WARNING
            );
            return NOT_AVAILABLE;
        }
        return $all[$status];
    }

    /**
     * Print the encryption status dropdown
     *
     * @param integer $value   Current value (defaut self::ENCRYPTION_STATUS_NO)
     * @param array   $options Array of possible options:
     *    - name : name of the dropdown (default encryption_status)
     *
     * @return string the string to display
     */
    public static function getEncryptionStatusDropdown($value = self::ENCRYPTION_STATUS_NO, $options = [])
    {
        $name = $options['name'] ?? 'encryption_status';
        $values = self::getAllEncryptionStatus();

        return Dropdown::showFromArray(
            $name,
            $values,
            [
                'value'   => $value,
                'display' => false,
            ]
        );
    }

    /**
     * List specifics value for selection
     *
     * @param string       $field   Name of the field
     * @param string       $name    Name of the select (if empty use linkfield) (default '')
     * @param string|array $values  Value(s) to select (default '')
     * @param array        $options Array of options
     *
     * @return string the string to display
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'encryption_status':
                return self::getEncryptionStatusDropdown($values[$field], [
                    'name'  => $name,
                ]);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Display a specific field value
     *
     * @param string       $field   Name of the field
     * @param string|array $values  Value(s) to display
     * @param array        $options Array of options
     *
     * @return string the string to display
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'encryption_status':
                return htmlescape(self::getEncryptionStatus($values[$field]));
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function getNonLoggedFields(): array
    {
        // we don't want to log at all changes of available space for a drive
        // as it's likely to change every time
        $exclude = [
            'freesize',
        ];

        // logging total size of zfs mount points make no sense as it's equal to the used space of the point + available space for the pool
        // it's likely to have this key changing on each automatic inventory
        // so we don't want to pollute logs with these frequent changes.
        // to note, `$this->input['filesystem']` will only be present on inventory request
        if (in_array(($this->input['filesystem'] ?? ""), ['zfs', 'fuse.zfs'])) {
            $exclude[] = 'totalsize';
        }

        return $exclude;
    }
}
