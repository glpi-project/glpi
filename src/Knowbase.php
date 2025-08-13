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
 * Knowbase Class
 *
 * @since 0.84
 **/
class Knowbase extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        // No plural
        return __('Knowledge base');
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);

        $ong['no_all_tab'] = true;
        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            $tabs[1] = self::createTabEntry(_x('button', 'Search'), icon: 'ti ti-search');
            $tabs[2] = self::createTabEntry(_x('button', 'Browse'), icon: 'ti ti-list-tree');

            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            switch ($tabnum) {
                case 1: // all
                    $item->showSearchView();
                    break;

                case 2:
                    Search::show('KnowbaseItem');
                    break;
            }
        }
        return true;
    }

    /**
     * Show the knowbase search view
     **/
    public static function showSearchView()
    {
        global $CFG_GLPI;

        // Search a solution
        if (isset($_GET["itemtype"], $_GET["items_id"]) && !isset($_GET["contains"])) {
            if (in_array($_GET["item_itemtype"], $CFG_GLPI['kb_types'], true) && $item = getItemForItemtype($_GET["itemtype"])) {
                if ($item->can($_GET["item_items_id"], READ)) {
                    $_GET["contains"] = $item->getField('name');
                }
            }
        }

        if (isset($_GET["contains"])) {
            $_SESSION['kbcontains'] = $_GET["contains"];
        } elseif (isset($_SESSION['kbcontains'])) {
            $_GET['contains'] = $_SESSION["kbcontains"];
        }
        $ki = new KnowbaseItem();
        $ki->searchForm($_GET);

        if (empty($_GET['contains'])) {
            echo '<div class="d-flex flex-wrap mt-3">';
            KnowbaseItem::showRecentPopular("recent");
            KnowbaseItem::showRecentPopular("lastupdate");
            KnowbaseItem::showRecentPopular("popular");
            echo '</div>';
        } else {
            KnowbaseItem::showList($_GET, 'search');
        }
    }
}
