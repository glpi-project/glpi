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
 * Change_Ticket Class
 *
 * Relation between Changes and Tickets
 **/
class Change_Ticket extends CommonITILObject_CommonITILObject
{
    // From CommonDBRelation
    public static $itemtype_1   = 'Change';
    public static $items_id_1   = 'changes_id';

    public static $itemtype_2   = 'Ticket';
    public static $items_id_2   = 'tickets_id';


    public static function getTypeName($nb = 0)
    {
        return _n('Link Ticket/Change', 'Links Ticket/Change', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Change::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_tickets',
                            ['changes_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb, $item::class);

                case Ticket::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_tickets',
                            ['tickets_id' => $item->getID()]
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

            case Ticket::class:
                self::showForTicket($item);
                break;
        }
        return true;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'add_task':
                $tasktype = 'TicketTask';
                if ($ttype = getItemForItemtype($tasktype)) {
                    /** @var CommonITILTask $ttype */
                    $ttype->showMassiveActionAddTaskForm();
                    return true;
                }
                return false;

            case "solveticket":
                $change = new Change();
                $input = $ma->getInput();
                if (isset($input['changes_id']) && $change->getFromDB($input['changes_id'])) {
                    $change::showMassiveSolutionForm($change);
                    echo "<br>";
                    echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                    return true;
                }
                return false;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'add_task':
                if (!($task = getItemForItemtype('TicketTask'))) {
                    $ma->itemDone($item::class, $ids, MassiveAction::ACTION_KO);
                    break;
                }
                $ticket = new Ticket();
                $field = $ticket->getForeignKeyField();

                $input = $ma->getInput();

                foreach ($ids as $id) {
                    if ($item->can($id, READ)) {
                        if ($ticket->getFromDB($item->fields['tickets_id'])) {
                            $input2 = [$field              => $item->fields['tickets_id'],
                                'taskcategories_id' => $input['taskcategories_id'],
                                'actiontime'        => $input['actiontime'],
                                'content'           => $input['content'],
                            ];
                            if ($task->can(-1, CREATE, $input2)) {
                                if ($task->add($input2)) {
                                    $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        } else {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                }
                return;
            case 'solveticket':
                if (!$item instanceof Ticket) {
                    throw new InvalidArgumentException();
                }

                $input  = $ma->getInput();
                foreach ($ids as $id) {
                    if ($item->can($id, READ)) {
                        if ($item->canSolve()) {
                            $solution = new ITILSolution();
                            $added = $solution->add([
                                'itemtype'         => $item::class,
                                'items_id'         => $item->getID(),
                                'solutiontypes_id' => $input['solutiontypes_id'],
                                'content'          => $input['content'],
                            ]);

                            if ($added) {
                                $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Show tickets for a change
     *
     * @param Change $change
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
                'glpi_changes_tickets.id AS linkid',
                'glpi_tickets.*',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_changes_tickets',
            'LEFT JOIN'       => [
                'glpi_tickets' => [
                    'ON' => [
                        'glpi_changes_tickets'  => 'tickets_id',
                        'glpi_tickets'          => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_changes_tickets.changes_id'   => $ID,
            ],
            'ORDERBY'          => [
                'glpi_tickets.name',
            ],
        ]);

        $tickets = [];
        $used    = [];

        foreach ($iterator as $data) {
            $tickets[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        $link_types = array_map(static fn($link_type) => $link_type['name'], CommonITILObject_CommonITILObject::getITILLinkTypes());

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Change::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Ticket::class,
                'dropdown_options' => [
                    'entity'      => $change->getEntityID(),
                    'entity_sons' => $change->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                ],
                'create_link' => false,
                'form_label' => __('Add a change'),
                'button_label' => __('Create a change from this ticket'),
            ]);
        }

        [$columns, $formatters] = array_values(Ticket::getCommonDatatableColumns());
        $entries = Ticket::getDatatableEntries(array_map(static function ($t) {
            $t['itemtype'] = Ticket::class;
            $t['item_id'] = $t['id'];
            return $t;
        }, $tickets));

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
                'specific_actions' => [
                    'purge' => _sx('button', 'Delete permanently'),
                    self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'solveticket' => __s('Solve tickets'),
                    self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_task' => __s('Add a new task'),
                ],
                'extraparams'      => ['changes_id' => $change->getID()],
            ],
        ]);
    }


    /**
     * Show changes for a ticket
     *
     * @param Ticket $ticket object
     **/
    public static function showForTicket(Ticket $ticket)
    {
        global $DB;

        $ID = $ticket->getField('id');
        if (!$ticket->can($ID, READ)) {
            return false;
        }

        $canedit = $ticket->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT'          => [
                'glpi_changes_tickets.id AS linkid',
                'glpi_changes.*',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_changes_tickets',
            'LEFT JOIN'       => [
                'glpi_changes' => [
                    'ON' => [
                        'glpi_changes_tickets'  => 'changes_id',
                        'glpi_changes'          => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_changes_tickets.tickets_id'   => $ID,
            ],
            'ORDERBY'          => [
                'glpi_changes.name',
            ],
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
                'source_itemtype' => Ticket::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Change::class,
                'dropdown_options' => [
                    'entity'      => $ticket->getEntityID(),
                    'entity_sons' => $ticket->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                    'condition'   => Change::getOpenCriteria(),
                ],
                'create_link' => Session::haveRight(Change::$rightname, CREATE),
                'form_label' => __('Add a change'),
                'button_label' => __('Create a change from this ticket'),
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
}
