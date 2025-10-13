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

class Problem_Ticket extends CommonITILObject_CommonITILObject
{
    // From CommonDBRelation
    public static $itemtype_1   = 'Problem';
    public static $items_id_1   = 'problems_id';

    public static $itemtype_2   = 'Ticket';
    public static $items_id_2   = 'tickets_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Link Ticket/Problem', 'Links Ticket/Problem', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Ticket::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $problems = self::getTicketProblemsData($item->getID());
                        $nb = count($problems);
                    }
                    return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb, $item::class);

                case Problem::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $tickets = self::getProblemTicketsData($item->getID());
                        $nb = count($tickets);
                    }
                    return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case Ticket::class:
                self::showForTicket($item);
                break;

            case Problem::class:
                self::showForProblem($item);
                break;
        }
        return true;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'add_task':
                (new TicketTask())->showMassiveActionAddTaskForm();
                return true;

            case "solveticket":
                $problem = new Problem();
                $input = $ma->getInput();
                if (isset($input['problems_id']) && $problem->getFromDB($input['problems_id'])) {
                    $problem::showMassiveSolutionForm($problem);
                    echo "<br>";
                    echo Html::submit(_x('button', 'Post'), [
                        'name'  => 'massiveaction',
                        'class' => 'btn btn-primary',
                    ]);
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
                $task = new TicketTask();
                $ticket = new Ticket();
                $field = $ticket::getForeignKeyField();

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
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
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
     * Show tickets for a problem
     *
     * @param Problem $problem
     **/
    public static function showForProblem(Problem $problem)
    {
        $ID = $problem->getField('id');

        if (!static::canView() || !$problem->can($ID, READ)) {
            return false;
        }

        $canedit = $problem->canEdit($ID);

        $rand = mt_rand();

        $tickets = self::getProblemTicketsData($ID);
        $used    = [];
        foreach ($tickets as $ticket) {
            $used[$ticket['id']] = $ticket['id'];
        }

        $link_types = array_map(static fn($link_type) => $link_type['name'], CommonITILObject_CommonITILObject::getITILLinkTypes());

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Problem::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Ticket::class,
                'dropdown_options' => [
                    'entity'      => $problem->getEntityID(),
                    'entity_sons' => $problem->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                ],
                'create_link' => false,
                'form_label' => __('Add a ticket'),
                'button_label' => __('Create a ticket from this problem'),
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
                'extraparams'      => ['problems_id' => $problem->getID()],
            ],
        ]);
    }

    /**
     * Show problems for a ticket
     *
     * @param Ticket $ticket object
     **/
    public static function showForTicket(Ticket $ticket)
    {

        $ID = $ticket->getField('id');

        if (!static::canView() || !$ticket->can($ID, READ)) {
            return false;
        }

        $canedit = $ticket->can($ID, UPDATE);

        $rand = mt_rand();

        $problems = self::getTicketProblemsData($ID);
        $used     = [];
        foreach ($problems as $problem) {
            $used[$problem['id']] = $problem['id'];
        }

        $link_types = array_map(static fn($link_type) => $link_type['name'], CommonITILObject_CommonITILObject::getITILLinkTypes());

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Ticket::class,
                'source_items_id' => $ID,
                'link_types' => $link_types,
                'target_itemtype' => Problem::class,
                'dropdown_options' => [
                    'entity'      => $ticket->getEntityID(),
                    'entity_sons' => $ticket->isRecursive(),
                    'condition'   => Problem::getOpenCriteria(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                ],
                'create_link' => Session::haveRight(Problem::$rightname, CREATE),
                'form_label' => __('Add a problem'),
                'button_label' => __('Create a problem from this ticket'),
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

    /**
     * Returns problems data for given ticket.
     * Returned data is usable by `Problem::showShort()` method.
     *
     * @param integer $tickets_id
     *
     * @return array
     */
    private static function getTicketProblemsData($tickets_id): array
    {
        $ticket = new Ticket();
        $ticket->fields['id'] = $tickets_id;
        $iterator = self::getListForItem($ticket);

        $problems = [];
        foreach ($iterator as $data) {
            $problem = new Problem();
            $problem->getFromDB($data['id']);
            if ($problem->canViewItem()) {
                $problems[$data['id']] = $data;
            }
        }

        return $problems;
    }

    /**
     * Returns tickets data for given problem.
     * Returned data is usable by `Ticket::showShort()` method.
     *
     * @param integer $problems_id
     *
     * @return array
     */
    private static function getProblemTicketsData($problems_id): array
    {
        $problem = new Problem();
        $problem->fields['id'] = $problems_id;
        $iterator = self::getListForItem($problem);

        $tickets = [];
        foreach ($iterator as $data) {
            $ticket = new Ticket();
            $ticket->getFromDB($data['id']);
            if ($ticket->canViewItem()) {
                $tickets[$data['id']] = $data;
            }
        }

        return $tickets;
    }
}
