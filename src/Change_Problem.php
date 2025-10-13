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

/**
 * @since 0.84
 *
 * Change_Problem Class
 *
 * Relation between Changes and Problems
 **/
class Change_Problem extends CommonITILObject_CommonITILObject
{
    // From CommonDBRelation
    public static $itemtype_1   = 'Change';
    public static $items_id_1   = 'changes_id';

    public static $itemtype_2   = 'Problem';
    public static $items_id_2   = 'problems_id';


    public static function getTypeName($nb = 0)
    {
        return _n('Link Problem/Change', 'Links Problem/Change', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Change::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_problems',
                            ['changes_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb, $item::class);

                case Problem::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_problems',
                            ['problems_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case Change::class:
                self::showForChange($item);
                break;

            case Problem::class:
                self::showForProblem($item);
                break;
        }
        return true;
    }

    /**
     * Show tickets for a problem
     *
     * @param Problem $problem
     **/
    public static function showForProblem(Problem $problem)
    {
        global $DB;

        $ID = $problem->getField('id');
        if (!$problem->can($ID, READ)) {
            return false;
        }

        $canedit = $problem->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_changes_problems.id AS linkid',
                'glpi_changes.*',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_changes_problems',
            'LEFT JOIN'       => [
                'glpi_changes' => [
                    'ON' => [
                        'glpi_changes_problems' => 'changes_id',
                        'glpi_changes'          => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_changes_problems.problems_id' => $ID,
            ],
            'ORDERBY'         => 'glpi_changes.name',
        ]);

        $changes = [];
        $used    = [];
        foreach ($iterator as $data) {
            $changes[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        $link_types = array_map(static fn($link_type) => $link_type['name'], CommonITILObject_CommonITILObject::getITILLinkTypes());

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Problem::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Change::class,
                'dropdown_options' => [
                    'entity'      => $problem->getEntityID(),
                    'entity_sons' => $problem->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                    'condition'   => Change::getOpenCriteria(),
                ],
                'create_link' => Session::haveRight(Change::$rightname, CREATE),
                'form_label' => __('Add a change'),
                'button_label' => __('Create a change from this problem'),
            ]);
        }

        [$columns, $formatters] = array_values(Change::getCommonDatatableColumns());
        $entries = Change::getDatatableEntries(array_map(static function ($c) {
            $c['itemtype'] = Change::class;
            $c['item_id'] = $c['id'];
            return $c;
        }, $changes));

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Show problems for a change
     *
     * @param Change $change object
     **/
    public static function showForChange(Change $change)
    {
        global $DB;

        $ID = $change->getField('id');
        if (!$change->can($ID, READ)) {
            return false;
        }

        $canedit = $change->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_changes_problems.id AS linkid',
                'glpi_problems.*',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_changes_problems',
            'LEFT JOIN'       => [
                'glpi_problems' => [
                    'ON' => [
                        'glpi_changes_problems' => 'problems_id',
                        'glpi_problems'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_changes_problems.changes_id' => $ID,
            ],
            'ORDERBY'         => 'glpi_problems.name',
        ]);

        $problems = [];
        $used     = [];
        foreach ($iterator as $data) {
            $problems[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
        }

        $link_types = array_map(static fn($link_type) => $link_type['name'], CommonITILObject_CommonITILObject::getITILLinkTypes());

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Change::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Problem::class,
                'dropdown_options' => [
                    'entity'      => $change->getEntityID(),
                    'entity_sons' => $change->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                ],
                'create_link' => false,
                'form_label' => __('Add a problem'),
                'button_label' => __('Create a problem from this change'),
            ]);
        }

        [$columns, $formatters] = array_values(Problem::getCommonDatatableColumns());
        $entries = Problem::getDatatableEntries(array_map(static function ($p) {
            $p['itemtype'] = Problem::class;
            $p['item_id'] = $p['id'];
            return $p;
        }, $problems));

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }
}
