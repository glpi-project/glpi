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


    public function getLogTypeID()
    {
        return [$this->fields['itemtype'], $this->fields['items_id']];
    }


    public function canCreateItem()
    {

        if (
            isset($this->fields['itemtype'])
            && ($item = getItemForItemtype($this->fields['itemtype']))
        ) {
            return Session::haveRight($item::$rightname, UPDATENOTE);
        }
        return false;
    }


    public function canUpdateItem()
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
        return $input;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (Session::haveRight($item::$rightname, READNOTE)) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return false;
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum          (default 1)
     * @param $withtemplate    (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
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
                'items_id' => $item->getID()
            ]
        );
    }


    /**
     * @param $item   CommonDBTM object
     **/
    public static function getAllForItem(CommonDBTM $item)
    {
        global $DB;

        $data = [];
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_notepads.*',
                'glpi_users.picture'
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_users'   => [
                    'ON' => [
                        self::getTable()  => 'users_id_lastupdater',
                        'glpi_users'      => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID()
            ],
            'ORDERBY'   => 'date_mod DESC'
        ]);

        foreach ($iterator as $note) {
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
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '200',
            'table'              => 'glpi_notepads',
            'field'              => 'content',
            'name'               => $name,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ],
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '201',
            'table'              => 'glpi_notepads',
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
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
                        'jointype'           => 'itemtype_item'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '203',
            'table'              => 'glpi_notepads',
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
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
                        'jointype'           => 'itemtype_item'
                    ]
                ]
            ]
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
        $notes   = static::getAllForItem($item);
        $rand    = mt_rand();
        $canedit = Session::haveRight($item::$rightname, UPDATENOTE);

        $showuserlink = 0;
        if (User::canView()) {
            $showuserlink = 1;
        }

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            echo "<div class='boxnote center'>";

            echo "<div class='boxnoteleft'></div>";
            echo "<form name='addnote_form$rand' id='addnote_form$rand' ";
            echo " method='post' action='" . Toolbox::getItemTypeFormURL('Notepad') . "'>";
            echo Html::hidden('itemtype', ['value' => $item->getType()]);
            echo Html::hidden('items_id', ['value' => $item->getID()]);

            echo "<div class='boxnotecontent'>";
            echo "<textarea name='content' class='form-control' rows='7'></textarea>";
            echo "</div>"; // box notecontent

            echo "<div class='boxnoteright'><br>";
            echo Html::submit(_x('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
            echo "</div>";

            Html::closeForm();
            echo "</div>"; // boxnote
        }

        if (count($notes)) {
            foreach ($notes as $note) {
                $id = 'note' . $note['id'] . $rand;
                $classtoadd = '';
                if ($canedit) {
                    $classtoadd = " pointer";
                }
                echo "<div class='boxnote' id='view$id'>";

                echo "<div class='boxnoteleft'>";
                $thumbnail_url = User::getThumbnailURLForPicture($note['picture']);
                $user = new User();
                $user->getFromDB($note['users_id_lastupdater']);
                $style = !empty($thumbnail_url) ? "background-image: url('$thumbnail_url'); background-color: inherit;" : ("background-color: " . $user->getUserInitialsBgColor());
                echo '<a href="' . $user->getLinkURL() . '">';
                $user_name = formatUserName(
                    $user->getID(),
                    $user->fields['name'],
                    $user->fields['realname'],
                    $user->fields['firstname']
                );
                echo '<span class="avatar avatar-md rounded" style="' . $style . '" title="' . $user_name . '">';
                if (empty($thumbnail_url)) {
                    echo $user->getUserInitials();
                }
                echo '</span>';
                echo '</a>';
                echo "</div>"; // boxnoteleft

                echo "<div class='boxnotecontent'>";

                echo "<div class='boxnotefloatright'>";
                $username = NOT_AVAILABLE;
                if ($note['users_id_lastupdater']) {
                    $username = getUserName($note['users_id_lastupdater'], $showuserlink);
                }
                $update = sprintf(
                    __('Last update by %1$s on %2$s'),
                    $username,
                    Html::convDateTime($note['date_mod'])
                );
                $username = NOT_AVAILABLE;
                if ($note['users_id']) {
                     $username = getUserName($note['users_id'], $showuserlink);
                }
                $create = sprintf(
                    __('Create by %1$s on %2$s'),
                    $username,
                    Html::convDateTime($note['date_creation'])
                );
                printf(__('%1$s / %2$s'), $update, $create);
                echo "</div>"; // floatright

                echo "<div class='boxnotetext $classtoadd' ";
                if ($canedit) {
                     echo "onclick=\"" . Html::jsHide("view$id") . " " .
                              Html::jsShow("edit$id") . "\"";
                }
                echo ">";
                $content = nl2br($note['content']);
                if (empty($content)) {
                    $content = NOT_AVAILABLE;
                }
                echo $content . '</div>'; // boxnotetext

                echo "</div>"; // boxnotecontent
                echo "<div class='boxnoteright'>";
                if ($canedit) {
                    Html::showSimpleForm(
                        Toolbox::getItemTypeFormURL('Notepad'),
                        ['purge' => 'purge'],
                        _x('button', 'Delete permanently'),
                        ['id'   => $note['id']],
                        'ti-circle-x',
                        '',
                        __('Confirm the final deletion?')
                    );
                }
                echo "</div>"; // boxnoteright
                echo "</div>"; // boxnote

                if ($canedit) {
                    echo "<div class='boxnote starthidden' id='edit$id'>";
                    echo "<form name='update_form$id$rand' id='update_form$id$rand' ";
                    echo " method='post' action='" . Toolbox::getItemTypeFormURL('Notepad') . "'>";

                    echo "<div class='boxnoteleft'></div>";
                    echo "<div class='boxnotecontent'>";
                    echo Html::hidden('id', ['value' => $note['id']]);
                    echo "<textarea name='content' rows=5 cols=100>" . $note['content'] . "</textarea>";
                    echo "</div>"; // boxnotecontent

                    echo "<div class='boxnoteright'><br>";
                    echo Html::submit(_x('button', 'Update'), ['name' => 'update']);
                    echo "</div>"; // boxnoteright

                    Html::closeForm();
                    echo "</div>"; // boxnote
                }
            }
        }
        return true;
    }
}
