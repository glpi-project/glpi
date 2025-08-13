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

class Item_Line extends CommonDBRelation
{
    public static $itemtype_1 = 'Line';
    public static $items_id_1 = 'lines_id';
    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Line item', 'Line items', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        $nb = 0;
        if ($item instanceof Line) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForMainItem($item) + self::countSimcardItemsForLine($item);
            }
            return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');
        } else {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item) + self::countSimcardLinesForItem($item);
            }
            return self::createTabEntry(Line::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Line) {
            self::showItemsForLine($item);
        } else {
            self::showLinesForItem($item);
        }
        return true;
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'MassiveAction:update';
        $forbidden[] = 'CommonDBConnexity:affect';
        $forbidden[] = 'CommonDBConnexity:unaffect';

        return $forbidden;
    }

    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add':
            case 'remove':
                return 1;

            case 'add_item':
            case 'remove_item':
                return 2;
        }
        return 0;
    }


    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = $CFG_GLPI['line_types'];

        // Define normalized action for add_item and remove_item
        $specificities['normalized']['add'][]          = 'add_item';
        $specificities['normalized']['remove'][]       = 'remove_item';

        // Set the labels for add_item and remove_item
        $specificities['button_labels']['add_item']    = $specificities['button_labels']['add'];
        $specificities['button_labels']['remove_item'] = $specificities['button_labels']['remove'];

        return $specificities;
    }

    /**
     * Count the number of lines associated to an item through a simcard.
     *
     * @param CommonDBTM $item
     * @return int
     */
    protected static function countSimcardLinesForItem(CommonDBTM $item)
    {
        return countElementsInTable(Item_DeviceSimcard::getTable(), [
            'items_id' => $item->getID(),
            'itemtype' => $item->getType(),
            'NOT'   => [
                'lines_id' => 0,
            ],
        ]);
    }

    /**
     * Count the number of items associated to a line through a simcard.
     *
     * @param Line $line
     * @return int
     */
    protected static function countSimcardItemsForLine(Line $line)
    {
        return countElementsInTable(Item_DeviceSimcard::getTable(), [
            'lines_id' => $line->getID(),
        ]);
    }

    /**
     * Show a list of items linked to a Line
     *
     * This includes directly linked items and items linked by a simcard.
     * It allows linking items directly to a line.
     *
     * @return void|false False if the line is not valid or the user does not have the right to view the line
     **/
    public static function showItemsForLine(Line $line)
    {
        global $DB;

        $ID = $line->fields['id'];

        if (
            !$line->getFromDB($ID)
            || !$line->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $line->canEdit($ID);

        $items = $DB->request([
            'SELECT' => ['id', 'itemtype', 'items_id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'lines_id' => $ID,
            ],
        ]);

        $simcards = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Item_DeviceSimcard::getTable(),
            'WHERE'  => [
                'lines_id' => $ID,
            ],
        ]);

        $simcard_entries = [];
        foreach ($simcards as $row) {
            $item = new Item_DeviceSimcard();
            $item->getFromDB($row['id']);
            $simcard_entries[] = [
                'itemtype' => Item_DeviceSimcard::class,
                'id'      => $row['id'],
                'name'    => $item->getLink(),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => [
                'label' => Item_DeviceSimcard::getTypeName(Session::getPluralNumber()),
            ],
            'columns' => [
                'name' => __('Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $simcard_entries,
            'total_number' => count($simcard_entries),
            'filtered_number' => count($simcard_entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($simcard_entries),
                'container'     => 'mass' . Item_DeviceSimcard::class . mt_rand(),
            ],
        ]);

        if (static::canCreate()) {
            //get all used items
            $used = [];
            $iterator = $DB->request([
                'FROM'   => static::getTable(),
                'WHERE'  => [
                    'lines_id' => $line->getID(),
                ],
            ]);
            foreach ($iterator as $row) {
                $used[$row['itemtype']][$row['items_id']] = $row['items_id'];
            }

            TemplateRenderer::getInstance()->display('pages/management/item_line.html.twig', [
                'from_line' => true,
                'peer_id' => $line->getID(),
                'used' => $used,
                'entity_restrict' => $line->isRecursive() ? getSonsOf('glpi_entities', $line->getEntityID()) : $line->getEntityID(),
            ]);
        }

        $item_entries = [];
        foreach ($items as $row) {
            if (!is_a($row['itemtype'], CommonDBTM::class, true)) {
                continue;
            }
            $item = getItemForItemtype($row['itemtype']);
            $item->getFromDB($row['items_id']);
            $item_entries[] = [
                'itemtype' => static::class,
                'id'      => $row['id'],
                'name'    => $item->getLink(),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => [
                'label' => _n('Item', 'Items', Session::getPluralNumber()),
            ],
            'columns' => [
                'name' => __('Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $item_entries,
            'total_number' => count($item_entries),
            'filtered_number' => count($item_entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($item_entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    /**
     * Show a list of lines linked to an item.
     *
     * This includes directly linked lines and lines linked by a simcard.
     * It allows linking lines directly to an item.
     *
     * @param CommonDBTM $item
     * @return void|false False if the item is not valid or the user does not have the right to view the item
     **/
    public static function showLinesForItem(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item::getType();
        $ID = $item->fields['id'];

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        $lines = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $ID,
            ],
        ]);

        $lines_from_sim = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Item_DeviceSimcard::getTable(),
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $ID,
                'NOT'   => [
                    'lines_id' => 0,
                ],
            ],
        ]);

        $simcard_entries = [];
        foreach ($lines_from_sim as $row) {
            $item = new Item_DeviceSimcard();
            $item->getFromDB($row['id']);
            $simcard_entries[] = [
                'itemtype' => Item_DeviceSimcard::class,
                'id'      => $row['id'],
                'name'    => $item->getLink(),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => [
                'label' => Item_DeviceSimcard::getTypeName(Session::getPluralNumber()),
            ],
            'columns' => [
                'name' => __('Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $simcard_entries,
            'total_number' => count($simcard_entries),
            'filtered_number' => count($simcard_entries),
            'showmassiveactions' => $simcard_entries,
            'massiveactionparams' => [
                'num_displayed' => count($simcard_entries),
                'container'     => 'mass' . Item_DeviceSimcard::class . mt_rand(),
            ],
        ]);

        if (static::canCreate()) {
            //get all used items
            $used = [];
            $iterator = $DB->request([
                'FROM'   => static::getTable(),
                'WHERE'  => [
                    'itemtype' => $itemtype,
                    'items_id' => $ID,
                ],
            ]);
            foreach ($iterator as $row) {
                $used[] = $row['lines_id'];
            }

            TemplateRenderer::getInstance()->display('pages/management/item_line.html.twig', [
                'from_line' => false,
                'peer_itemtype' => $itemtype,
                'peer_id' => $ID,
                'used' => $used,
                'entity_restrict' => $item->isRecursive() ? getSonsOf('glpi_entities', $item->getEntityID()) : $item->getEntityID(),
            ]);
        }

        $line_entries = [];
        foreach ($lines as $row) {
            $line = new Line();
            $line->getFromDB($row['lines_id']);
            $line_entries[] = [
                'itemtype' => static::class,
                'id'      => $row['id'],
                'name'    => $line->getLink(),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => [
                'label' => Line::getTypeName(Session::getPluralNumber()),
            ],
            'columns' => [
                'name' => __('Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $line_entries,
            'total_number' => count($line_entries),
            'filtered_number' => count($line_entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($line_entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    public function showForm($ID, array $options = [])
    {
        return false;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * Prepares input (for update and add)
     *
     * @param array<string, mixed> $input data used to update the item
     *
     * @return false|array<string, mixed> the modified $input array
     */
    private function prepareInput($input)
    {
        $error_detected = [];

        //check for requirements
        if (
            ($this->isNewItem() && (!isset($input['itemtype']) || empty($input['itemtype'])))
            || (isset($input['itemtype']) && empty($input['itemtype']))
        ) {
            $error_detected[] = __s('An item type is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['items_id']) || empty($input['items_id'])))
            || (isset($input['items_id']) && empty($input['items_id']))
        ) {
            $error_detected[] = __s('An item is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['lines_id']) || empty($input['lines_id'])))
            || (isset($input['lines_id']) && empty($input['lines_id']))
        ) {
            $error_detected[] = __s('A line is required');
        }

        if (count($error_detected)) {
            foreach ($error_detected as $error) {
                Session::addMessageAfterRedirect(
                    $error,
                    true,
                    ERROR
                );
            }
            return false;
        }

        return $input;
    }


    public static function getIcon()
    {
        return Line::getIcon();
    }
}
