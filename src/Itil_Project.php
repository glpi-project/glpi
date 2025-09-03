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
use Glpi\DBAL\QueryUnion;

/**
 * Relation between Itil items and Projects
 *
 * @since 9.4.0
 **/
class Itil_Project extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype';
    public static $items_id_1 = 'items_id';
    public static $itemtype_2 = 'Project';
    public static $items_id_2 = 'projects_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Link Project/Itil', 'Links Project/Itil', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $label = '';

        if (static::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Change::class:
                case Problem::class:
                case Ticket::class:
                    /** @var Change|Problem|Ticket $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            self::getTable(),
                            [
                                'itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                            ]
                        );
                    }
                    $label = self::createTabEntry(Project::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    break;

                case Project::class:
                    /** @var Project $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(self::getTable(), ['projects_id' => $item->getID()]);
                    }
                    $label = self::createTabEntry(
                        _n('Itil item', 'Itil items', Session::getPluralNumber()),
                        $nb,
                        $item::getType(),
                        Ticket::getIcon()
                    );
                    break;
            }
        }

        return $label;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case Change::class:
            case Problem::class:
            case Ticket::class:
                return self::showForItil($item);

            case Project::class:
                return self::showForProject($item);
        }
        return false;
    }

    /**
     * Show ITIL items for a project.
     *
     * @param Project $project
     *
     * @return bool
     **/
    public static function showForProject(Project $project): bool
    {
        global $DB, $CFG_GLPI;

        $ID = $project->getField('id');
        if (!$project->can($ID, READ)) {
            return false;
        }

        $canedit = $project->canEdit($ID);

        $queries = [];
        /** @var class-string<CommonITILObject> $itemtype */
        foreach ($CFG_GLPI['itil_types'] as $itemtype) {
            $link_table = self::getTable();
            $itil_table = $itemtype::getTable();
            $queries[] = [
                'SELECT'          => [
                    "$link_table.id AS linkid",
                    "$link_table.items_id AS id",
                    new QueryExpression($DB::quoteValue($itemtype), 'itemtype'),
                ],
                'DISTINCT'        => true,
                'FROM'            => $link_table,
                'LEFT JOIN'       => [
                    $itil_table => [
                        'FKEY' => [
                            $link_table => 'items_id',
                            $itil_table => 'id',
                        ],
                    ],
                ],
                'WHERE'           => [
                    "{$link_table}.projects_id" => $ID,
                    "{$link_table}.itemtype"    => $itemtype,
                    'NOT'                      => ["{$itil_table}.id" => null],
                ],
            ];
        }

        $it = $DB->request([
            'FROM' => new QueryUnion($queries),
        ]);
        $entries_by_itemtype = [];
        $used  = [];
        foreach ($it as $data) {
            $used[$data['itemtype']][$data['id']]  = $data['id'];
            $entries_by_itemtype[$data['itemtype']][] = [
                'id'             => $data['linkid'],
                'itemtype'       => $data['itemtype'],
                'item_id'        => $data['id'],
                'itemtype_label' => $data['itemtype']::getTypeName(1),
            ];
        }

        if ($canedit) {
            $twig_params = [
                'btn_msg' => _x('button', 'Add'),
                'used'    => $used,
                'ID'      => $ID,
                'entity_restrict' => $project->getEntityID(),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    <div class="mb-3">
                        <form method="post" action="{{ 'Itil_Project'|itemtype_form_path }}">
                            <input type="hidden" name="projects_id" value="{{ ID }}"/>
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}"/>
                            <div class="d-flex">
                                {{ fields.dropdownItemsFromItemtypes('items_id', null, {
                                    add_field_class: 'd-inline',
                                    no_label: true,
                                    itemtypes: config('itil_types'),
                                    used: used,
                                    entity_restrict: entity_restrict
                                }) }}
                                <div>
                                    <button class="btn btn-primary ms-3" type="submit" name="add" value="">{{ btn_msg }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
TWIG, $twig_params);
        }

        $cols = CommonITILObject::getCommonDatatableColumns();
        // insert 'itemtype_label' column after 'item_id' column
        $cols['columns'] = array_merge(
            ['item_id' => $cols['columns']['item_id']],
            ['itemtype_label' => _n('Type', 'Types', 1)],
            array_slice($cols['columns'], 1)
        );
        $entries = [];
        /** @var class-string<CommonITILObject> $itemtype */
        foreach ($entries_by_itemtype as $itemtype => $v) {
            $entries = [...$entries, ...$itemtype::getDatatableEntries($v)];
        }
        // add itemtype for MA
        foreach ($entries as &$entry) {
            $entry['itemtype'] = self::class;
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $cols['columns'],
            'formatters' => $cols['formatters'],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . self::class . mt_rand(),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);

        return true;
    }

    /**
     * Show projects for an ITIL item.
     *
     * @param CommonITILObject $itil
     *
     * @return bool
     **/
    public static function showForItil(CommonITILObject $itil): bool
    {
        global $DB;

        $ID = $itil->getID();
        if (!$itil->can($ID, READ)) {
            return false;
        }

        $canedit = $itil->canEdit($ID);

        $selfTable = self::getTable();
        $projectTable = Project::getTable();

        $iterator = $DB->request([
            'SELECT'          => [
                "$selfTable.id AS linkid",
                "$projectTable.*",
            ],
            'DISTINCT'        => true,
            'FROM'            => $selfTable,
            'LEFT JOIN'       => [
                $projectTable => [
                    'FKEY' => [
                        $selfTable    => 'projects_id',
                        $projectTable => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "{$selfTable}.itemtype" => $itil->getType(),
                "{$selfTable}.items_id" => $ID,
                'NOT'                   => ["{$projectTable}.id" => null],
            ],
            'ORDER'  => "{$projectTable}.name",
        ]);

        $projects = [];
        $used     = [];
        foreach ($iterator as $data) {
            $projects[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
        }

        if ($canedit && !$itil->isSolved(true)) {
            $twig_params = [
                'btn_msg' => _x('button', 'Add'),
                'used' => $used,
                'itemtype' => $itil::class,
                'items_id' => $ID,
                'entities_id' => $itil->getEntityID(),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    <div class="mb-3">
                        <form method="post" action="{{ 'Itil_Project'|itemtype_form_path }}">
                            <div class="d-flex">
                                <input type="hidden" name="itemtype" value="{{ itemtype }}"/>
                                <input type="hidden" name="items_id" value="{{ items_id }}"/>
                                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}"/>
                                <div class="col-auto">
                                    {{ fields.dropdownField('Project', 'projects_id', '', null, {
                                        add_field_class: 'd-inline',
                                        no_label: true,
                                        used: used,
                                        entity: entities_id
                                    }) }}
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary ms-1" type="submit" name="add" value="">
                                        <i class="ti ti-link"></i>
                                        {{ btn_msg }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
TWIG, $twig_params);
        }

        $entries_to_fetch = [];
        foreach ($projects as $data) {
            $entries_to_fetch[] = [
                'item_id' => $data['id'],
                'id' => $data['linkid'],
                'itemtype' => self::class,
            ];
        }

        $cols = Project::getCommonDatatableColumns();
        $entries = Project::getDatatableEntries($entries_to_fetch);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $cols['columns'],
            'formatters' => $cols['formatters'],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . self::class . mt_rand(),
            ],
        ]);

        return true;
    }
}
