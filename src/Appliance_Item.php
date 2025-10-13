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
use Glpi\Features\Clonable;

class Appliance_Item extends CommonDBRelation
{
    use Clonable;

    public static $itemtype_1 = 'Appliance';
    public static $items_id_1 = 'appliances_id';
    public static $take_entity_1 = false;

    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
    public static $take_entity_2 = true;

    public function getCloneRelations(): array
    {
        return [
            Appliance_Item_Relation::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Item', 'Items', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!Appliance::canView() || !$item instanceof CommonDBTM) {
            return '';
        }

        $nb = 0;
        if ($item->getType() == Appliance::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                if (!$item->isNewItem()) {
                    $nb = self::countForMainItem($item);
                }
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');
        } elseif (in_array($item->getType(), Appliance::getTypes(true))) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(Appliance::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch (true) {
            case $item instanceof Appliance:
                self::showItems($item);
                break;
            default:
                if (in_array($item->getType(), Appliance::getTypes())) {
                    self::showForItem($item, $withtemplate);
                }
        }
        return true;
    }

    /**
     * Print enclosure items
     *
     * @param Appliance $appliance  Appliance object wanted
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showItems(Appliance $appliance)
    {
        global $DB;

        $ID = $appliance->fields['id'];
        $rand = mt_rand();

        if (
            !$appliance->getFromDB($ID)
            || !$appliance->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $appliance->canEdit($ID);

        $items = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                self::$items_id_1 => $ID,
            ],
        ]);

        Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $appliance->getTypeName(1),
                $appliance->getName()
            )
        );

        if ($appliance->canAddItem('itemtype')) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' name='appliances_form$rand'
                     id='appliances_form$rand'
                     action='" . htmlescape(Toolbox::getItemTypeFormURL(self::class)) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th colspan='2'>"
               . __s('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";
            Dropdown::showSelectItemFromItemtypes(
                ['items_id_name'   => 'items_id',
                    'itemtypes'       => Appliance::getTypes(true),
                    'entity_restrict' => ($appliance->fields['is_recursive']
                                      ? getSonsOf(
                                          'glpi_entities',
                                          $appliance->fields['entities_id']
                                      )
                                       : $appliance->fields['entities_id']),
                    'checkright'      => true,
                ]
            );
            echo "</td><td class='center' class='tab_bg_1'>";
            echo Html::hidden('appliances_id', ['value' => $ID]);
            echo Html::submit(_x('button', 'Add'), ['name' => 'add']);
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        $entries = [];
        foreach ($items as $row) {
            $item = getItemForItemtype($row['itemtype']);
            $item->getFromDB($row['items_id']);
            $entries[] = [
                'itemtype' => self::class,
                'id' => $row['id'],
                'item_type' => $item->getTypeName(1),
                'item' => $item->getLink(),
                'serial' => $item->fields['serial'] ?? "",
                'otherserial' => $item->fields['otherserial'] ?? "",
                'relations' => "<div class='relations_list'>" . Appliance_Item_Relation::showListForApplianceItem($row["id"], $canedit) . "</div>",
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'item_type' => __('Itemtype'),
                'item' => _n('Item', 'Items', 1),
                'serial' => __('Serial'),
                'otherserial' => __('Inventory number'),
                'relations' => Appliance_Item_Relation::getTypeName(Session::getPluralNumber()),
            ],
            'formatters' => [
                'item' => 'raw_html',
                'relations' => 'raw_html',
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
        echo Appliance_Item_Relation::getListJSForApplianceItem($appliance, $canedit);
    }

    /**
     * Print an HTML array of appliances associated to an object
     *
     * @since 9.5.2
     *
     * @param CommonDBTM $item         CommonDBTM object wanted
     * @param integer    $withtemplate not used (to be deleted)
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $ID = $item->getID();

        if (
            !Appliance::canView()
            || !$item->can($ID, READ)
        ) {
            return;
        }

        $canedit = $item->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($item);

        $appliances = [];
        $used      = [];
        foreach ($iterator as $data) {
            $appliances[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ($withtemplate != 2)) {
            echo "<div class='firstbloc'>";
            echo "<form name='applianceitem_form$rand' id='applianceitem_form$rand' method='post'
                action='" . htmlescape(Toolbox::getItemTypeFormURL(self::class)) . "'>";
            echo __s('Add to an appliance');
            echo "<div class='d-flex'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='" . htmlescape($item::class) . "'>";
            echo "<div class='auto'>";
            Appliance::dropdown([
                'entity'  => $item->getEntityID(),
                'used'    => $used,
            ]);
            echo "</div>";
            echo "<div class='auto'>";
            echo "<button type='submit' name='add' value='1' class='btn btn-primary ms-1'>";
            echo "<i class='ti ti-link'></i>" . _sx('button', 'Add');
            echo "</button>";
            echo "</div>";
            echo "</div>"; //d-flex
            Html::closeForm();
            echo "</div>"; //firstbloc
        }

        $entries = [];
        foreach ($appliances as $data) {
            $assocID = $data["linkid"];
            $app = new Appliance();
            $app->getFromResultSet($data);

            $entries[] = [
                'itemtype' => self::class,
                'id' => $assocID,
                'name' => $app->getLink(),
                'relations' => "<div class='relations_list'>" . Appliance_Item_Relation::showListForApplianceItem($assocID, $canedit) . "</div>",
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'relations' => Appliance_Item_Relation::getTypeName(Session::getPluralNumber()),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'relations' => 'raw_html',
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
        echo Appliance_Item_Relation::getListJSForApplianceItem($item, $canedit);
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
     * @param array $input Input data
     *
     * @return false|array
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
            ($this->isNewItem() && (!isset($input[self::$items_id_1]) || empty($input[self::$items_id_1])))
            || (isset($input[self::$items_id_1]) && empty($input[self::$items_id_1]))
        ) {
            $error_detected[] = __s('An appliance is required');
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

    public static function countForMainItem(CommonDBTM $item, $extra_types_where = [])
    {
        $types = Appliance::getTypes();
        $clause = [];
        if (count($types)) {
            $clause = ['itemtype' => $types];
        } else {
            $clause = [new QueryExpression('true = false')];
        }
        $extra_types_where = array_merge(
            $extra_types_where,
            $clause
        );
        return parent::countForMainItem($item, $extra_types_where);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'CommonDBConnexity:unaffect';
        $forbidden[] = 'CommonDBConnexity:affect';
        return $forbidden;
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = Appliance::getTypes();

        return $specificities;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Appliance_Item_Relation::class,
            ]
        );
    }
}
