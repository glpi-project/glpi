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

use function Safe\getimagesize;

/**
 * Notepad class
 *
 * @since 0.85
 **/
class Notepad extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public $dohistory              = false;
    public $auto_message_on_action = false; // Link in message can't work'
    public static $logs_for_parent = true;


    public static function getTypeName($nb = 0)
    {
        //TRANS: Always plural
        return _n('Note', 'Notes', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-notes';
    }

    public function getLogTypeID()
    {
        return [$this->fields['itemtype'], $this->fields['items_id']];
    }


    public function canCreateItem(): bool
    {

        if (
            isset($this->fields['itemtype'])
            && ($item = getItemForItemtype($this->fields['itemtype']))
        ) {
            return Session::haveRight($item::$rightname, UPDATENOTE);
        }
        return false;
    }


    public function canUpdateItem(): bool
    {

        if (
            isset($this->fields['itemtype'])
            && ($item = getItemForItemtype($this->fields['itemtype']))
        ) {
            return Session::haveRight($item::$rightname, UPDATENOTE);
        }
        return false;
    }


    public function prepareInputForAdd($input)
    {

        $input['users_id']             = Session::getLoginUserID();
        $input['users_id_lastupdater'] = Session::getLoginUserID();
        return $input;
    }


    public function prepareInputForUpdate($input)
    {

        $input['users_id_lastupdater'] = Session::getLoginUserID();
        if (!isset($input['visible_from_ticket'])) {
            $input['visible_from_ticket'] = 0;
        }
        return $input;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (Session::haveRight($item::$rightname, READNOTE) && $item instanceof CommonDBTM) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        static::showForItem($item, $withtemplate);
        return true;
    }


    /**
     * @param $item    CommonDBTM object
     *
     * @return number
     **/
    public static function countForItem(CommonDBTM $item)
    {

        return countElementsInTable(
            'glpi_notepads',
            ['itemtype' => $item->getType(),
                'items_id' => $item->getID(),
            ]
        );
    }


    /**
     * @param $item   CommonDBTM object
     **/
    public static function getAllForItem(CommonDBTM $item, $target = null)
    {
        global $DB;

        $data = [];
        $query = [
            'SELECT'    => [
                'glpi_notepads.*',
                'glpi_users.picture',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_users'   => [
                    'ON' => [
                        self::getTable()  => 'users_id_lastupdater',
                        'glpi_users'      => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
            ],
            'ORDERBY'   => 'date_mod DESC',
        ];
        if ($target === Ticket::class) {
            $query['WHERE']['visible_from_ticket'] = true;
        }
        $iterator = $DB->request($query);
        $document_obj = new Document();

        foreach ($iterator as $note) {
            $document_items = Document_Item::getItemsAssociatedTo(self::class, $note['id']);
            foreach ($document_items as $document_item) {
                if (!$document_obj->getFromDB($document_item->fields['documents_id'])) {
                    continue;
                }

                $item = $document_obj->fields;
                $item['_can_edit'] = Document::canUpdate() && $document_obj->canUpdateItem();
                $item['_can_delete'] = Document::canDelete() && $document_obj->canDeleteItem();

                $docpath = GLPI_DOC_DIR . "/" . $item['filepath'];
                $is_image = Document::isImage($docpath);
                $sub_document = [
                    'type' => 'Document_Item',
                    'item' => $item,
                ];
                if ($is_image) {
                    $sub_document['_is_image'] = true;
                    $sub_document['_size'] = getimagesize($docpath);
                }
                $note['documents'][] = $sub_document;
            }
            $data[] = $note;
        }
        return $data;
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];
        $name = _n('Note', 'Notes', Session::getPluralNumber());

        $tab[] = [
            'id'                 => 'notepad',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '200',
            'table'              => 'glpi_notepads',
            'field'              => 'content',
            'name'               => $name,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '201',
            'table'              => 'glpi_notepads',
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '202',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => __('Writer'),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_notepads',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '203',
            'table'              => 'glpi_notepads',
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '204',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_lastupdater',
            'name'               => __('Last updater'),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_notepads',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        return $tab;
    }

    /**
     * Show notepads for an item
     *
     * @param $item                  CommonDBTM object
     * @param $withtemplate integer  template or basic item (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        if (!Session::haveRight($item::$rightname, READNOTE)) {
            return false;
        }
        $notes     = static::getAllForItem($item);
        $rand      = mt_rand();
        $canedit   = Session::haveRight($item::$rightname, UPDATENOTE);

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            TemplateRenderer::getInstance()->display('components/notepad/form.html.twig', [
                'rand'      => $rand,
                'url'       => Toolbox::getItemTypeFormURL('Notepad'),
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
                'notes'     => $notes,
                'canedit'   => $canedit,
                'candelete' => $canedit,
            ]);
        }
        return true;
    }

    public function post_updateItem($history = 1)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);
        parent::post_updateItem($history);
    }

    public function post_addItem()
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);
        parent::post_addItem();
    }
}
