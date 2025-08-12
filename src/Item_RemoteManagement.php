<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class Item_RemoteManagement extends CommonDBChild
{
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public $dohistory              = true;

    public const TEAMVIEWER = 'teamviewer';
    public const LITEMANAGER = 'litemanager';
    public const ANYDESK = 'anydesk';
    public const MESHCENTRAL = 'meshcentral';
    public const SUPREMO = 'supremo';
    public const RUSTDESK = 'rustdesk';


    public static function getTypeName($nb = 0)
    {
        return __('Remote management');
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if (
            $_SESSION['glpishow_count_on_tabs']
            && ($item instanceof CommonDBTM)
        ) {
            $nb = countElementsInTable(
                self::getTable(),
                [
                    'items_id'     => $item->getID(),
                    'itemtype'     => $item->getType(),
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item, $withtemplate);
        return true;
    }


    /**
     * Get remote managements related to a given item
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
            'FROM'      => self::getTable(),
            'WHERE'     => [
                'itemtype'     => $item->getType(),
                'items_id'     => $item->fields['id'],
                'is_deleted'   => 0,
            ],
        ]);
        return $iterator;
    }

    /**
     * Print the remote management
     *
     * @param CommonDBTM $item          Item object
     * @param integer    $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $ID = $item->fields['id'];
        $itemtype = $item->getType();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return;
        }
        $canedit = $item->canEdit($ID);

        $entries = [];
        foreach (self::getFromItem($item) as $data) {
            $mgmt = new self();
            $mgmt->getFromResultSet($data);
            $entries[] = [
                'id'        => $mgmt->getID(),
                'items_id'  => $mgmt->fields['items_id'],
                'itemtype'  => self::getType(),
                'remoteid'  => $mgmt->getRemoteLink(),
                'type'      => $mgmt->fields['type'],
                'comment'   => Dropdown::getYesNo($data['is_dynamic']),
            ];
        }

        TemplateRenderer::getInstance()->display('components/form/item_remotemanagement_list.html.twig', [
            'canedit'  => $canedit && !(!empty($withtemplate) && $withtemplate == 2),
            'form_url' => self::getFormURL() . "?itemtype=$itemtype&items_id=$ID&withtemplate=$withtemplate",
            'entries'  => $entries,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }


    /**
     * Get remote management system link
     *
     * @return string
     */
    public function getRemoteLink(): string
    {
        $link = '<a href="%s" target="_blank">%s</a>';
        $id = htmlescape($this->fields['remoteid']);
        $href = null;
        switch ($this->fields['type']) {
            case self::TEAMVIEWER:
                $href = "https://start.teamviewer.com/$id";
                break;
            case self::ANYDESK:
                $href = "anydesk:$id";
                break;
            case self::SUPREMO:
                $href = "supremo:$id";
                break;
            case self::RUSTDESK:
                $href = "rustdesk://$id";
                break;
        }

        if ($href === null) {
            return $id;
        } else {
            return sprintf(
                $link,
                $href,
                $id
            );
        }
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
            'field'              => 'remoteid',
            'name'               => __('ID'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        $tab = [];

        $name = self::getTypeName(Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'remote_management',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '1220',
            'table'              => self::getTable(),
            'field'              => 'remoteid',
            'name'               => __('ID'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '1221',
            'table'              => self::getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'forcegroupby'       => true,
            'width'              => 1000,
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        return $tab;
    }


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
            throw new RuntimeException(
                sprintf(
                    'Item type %s is not a valid item type',
                    $itemtype
                )
            );
        }

        if (!Session::haveRight($itemtype::$rightname, READ)) {
            return false;
        }

        $item = new $itemtype();
        if ($ID > 0) {
            $this->check($ID, READ);
            $item->getFromDB($this->fields['items_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $item->getFromDB($options['items_id']);
        }

        $types = [
            self::TEAMVIEWER => 'TeamViewer',
            self::LITEMANAGER => 'LiteManager',
            self::ANYDESK => 'AnyDesk',
            self::MESHCENTRAL => 'MeshCentral',
            self::SUPREMO => 'SupRemo',
            self::RUSTDESK => 'RustDesk',
        ];

        $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('components/form/item_remotemanagement_form.html.twig', [
            'parent_item'   => $item,
            'item'          => $this,
            'types'         => $types,
        ]);
        return true;
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getIcon()
    {
        return "ti ti-screen-share";
    }
}
