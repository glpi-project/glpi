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
    const ENCRYPTION_STATUS_NO = 0;
    const ENCRYPTION_STATUS_YES = 1;
    const ENCRYPTION_STATUS_PARTIALLY = 2;

    public static function getTypeName($nb = 0)
    {
        return _n('Volume', 'Volumes', $nb);
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
        if ($item::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    [
                        'items_id'     => $item->getID(),
                        'itemtype'     => $item->getType(),
                        'is_deleted'   => 0
                    ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum          (default 1)
     * @param $withtemplate    (default 0)
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForItem($item, $withtemplate);
        return true;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Lock', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * Print the version form
     *
     * @param $ID        integer ID of the item
     * @param $options   array
     *     - target for the Form
     *     - itemtype type of the item for add process
     *     - items_id ID of the item for add process
     *
     * @return true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        $itemtype = null;
        if (isset($options['itemtype']) && !empty($options['itemtype'])) {
            $itemtype = $options['itemtype'];
        } else if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
        } else {
            throw new \RuntimeException('Unable to retrieve itemtype');
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
                self::getTable() . '.*'
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                Filesystem::getTable() => [
                    'FKEY' => [
                        self::getTable()        => 'filesystems_id',
                        Filesystem::getTable()  => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype'     => $item->getType(),
                'items_id'     => $item->fields['id']
            ]
        ]);
        return $iterator;
    }

    /**
     * Print the disks
     *
     * @param CommonDBTM $item          Item object
     * @param boolean    $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        $ID = $item->fields['id'];
        $itemtype = $item->getType();

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
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='" . self::getFormURL() . "?itemtype=$itemtype&items_id=$ID&amp;withtemplate=" .
                  $withtemplate . "'>";
            echo __('Add a volume');
            echo "</a></div>\n";
        }

        echo "<div class='center table-responsive'>";

        $iterator = self::getFromItem($item);
        echo "<table class='tab_cadre_fixehov'>";
        $colspan = 9;
        echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName(count($iterator)) .
            "</th></tr>";

        if (count($iterator)) {
            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . __('Automatic inventory') . "</th>";
            $header .= "<th>" . __('Partition') . "</th>";
            $header .= "<th>" . __('Mount point') . "</th>";
            $header .= "<th>" . Filesystem::getTypeName(1) . "</th>";
            $header .= "<th>" . __('Global size') . "</th>";
            $header .= "<th>" . __('Free size') . "</th>";
            $header .= "<th>" . __('Free percentage') . "</th>";
            $header .= "<th>" . __('Encryption') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                          sprintf(
                                              __('%1$s = %2$s'),
                                              $item::getTypeName(1),
                                              $item->getName()
                                          )
            );

            $disk = new self();
            foreach ($iterator as $data) {
                $disk->getFromResultSet($data);
                echo "<tr class='tab_bg_2" . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'") . "'>";
                echo "<td>" . $disk->getLink() . "</td>";
                echo "<td>" . Dropdown::getYesNo($data['is_dynamic']) . "</td>";
                echo "<td>" . $data['device'] . "</td>";
                echo "<td>" . $data['mountpoint'] . "</td>";
                echo "<td>" . $data['fsname'] . "</td>";
                //TRANS: %s is a size
                $tmp = Toolbox::getSize($data['totalsize'] * 1024 * 1024);
                echo "<td>$tmp</td>";
                $tmp = Toolbox::getSize($data['freesize'] * 1024 * 1024);
                echo "<td>$tmp</td>";
                echo "<td>";
                $percent = 0;
                if ($data['totalsize'] > 0) {
                    $percent = round(100 * $data['freesize'] / $data['totalsize']);
                }
                $rand = mt_rand();
                Html::progressBar("percent$rand", [
                    'create'  => true,
                    'percent' => $percent,
                    'message' => "$percent %",
                ]);
                 echo "</td>";
                 echo "<td class=\"center\">";

                if ($data['encryption_status'] != self::ENCRYPTION_STATUS_NO) {
                     $encryptionTooltip = "<strong>" . __('Partial encryption') . "</strong> : " .
                        Dropdown::getYesNo($data['encryption_status'] == self::ENCRYPTION_STATUS_PARTIALLY) .
                        "<br/>" .
                        "<strong>" . __('Encryption tool') . "</strong> : " . $data['encryption_tool'] .
                        "</br>" .
                        "<strong>" . __('Encryption algorithm') . "</strong> : " .
                        $data['encryption_algorithm'] . "</br>" .
                        "<strong>" . __('Encryption type') . "</strong> : " . $data['encryption_type'] .
                        "</br>";

                     Html::showTooltip($encryptionTooltip, [
                         'awesome-class' => "fas fa-lock"
                     ]);
                }

                 echo "</td>";
                 echo "</tr>";
                 Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
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
        $tab = [];

        $name = _n('Volume', 'Volumes', Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'disk',
            'name'               => $name
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
            'computation'        => 'LPAD(ROUND(100*TABLE.freesize/NULLIF(TABLE.totalsize, 0)), 3, 0)',
            'computationgroupby' => true,
            'unit'               => '%',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                        'jointype'           => 'itemtype_item'
                    ]
                ]
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
                'jointype'           => 'itemtype_item'
            ]
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
        $name = 'encryption_status';
        if (isset($options['name'])) {
            $name = $options['name'];
        }
        $values = self::getAllEncryptionStatus();

        return Dropdown::showFromArray(
            $name,
            $values,
            [
                'value'   => $value,
                'display' => false
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
                return self::getEncryptionStatus($values[$field]);
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
