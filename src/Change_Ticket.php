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
class Change_Ticket extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1   = 'Change';
    public static $items_id_1   = 'changes_id';

    public static $itemtype_2   = 'Ticket';
    public static $items_id_2   = 'tickets_id';



    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Link Ticket/Change', 'Links Ticket/Change', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            $nb = 0;
            switch (get_class($item)) {
                case Change::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_tickets',
                            ['changes_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);

                case Ticket::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_changes_tickets',
                            ['tickets_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Change':
                self::showForChange($item);
                break;

            case 'Ticket':
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
                    $change->showMassiveSolutionForm($change);
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
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
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
                $input  = $ma->getInput();
                $ticket = new Ticket();
                foreach ($ids as $id) {
                    if ($item->can($id, READ)) {
                        if (
                            $ticket->getFromDB($item->fields['tickets_id'])
                            && $ticket->canSolve()
                        ) {
                            $solution = new ITILSolution();
                            $added = $solution->add([
                                'itemtype'  => $ticket->getType(),
                                'items_id'  => $ticket->getID(),
                                'solutiontypes_id'   => $input['solutiontypes_id'],
                                'content'            => $input['content'],
                            ]);

                            if ($added) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($ticket->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($ticket->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($ticket->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * Show tickets for a change
     *
     * @param $change Change object
     **/
    public static function showForChange(Change $change)
    {
        /** @var \DBmysql $DB */
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
        $numrows = count($iterator);

        foreach ($iterator as $data) {
            $tickets[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => __CLASS__,
                'source_itemtype' => Change::class,
                'source_items_id' => $ID,
                'target_itemtype' => Ticket::class,
                'dropdown_options' => [
                    'entity'      => $change->getEntityID(),
                    'entity_sons' => $change->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                ],
                'create_link' => false,
            ]);
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams
            = ['num_displayed'    => min($_SESSION['glpilist_limit'], $numrows),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently'),
                    __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'solveticket'
                                                        => __('Solve tickets'),
                    __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_task'
                                                        => __('Add a new task'),
                ],
                'container'        => 'mass' . __CLASS__ . $rand,
                'extraparams'      => ['changes_id' => $change->getID()],
                'width'            => 1000,
                'height'           => 500,
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='12'>" . Ticket::getTypeName($numrows) . "</th>";
        echo "</tr>";
        if ($numrows) {
            Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
            Session::initNavigateListItems(
                'Ticket',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Change::getTypeName(1),
                    $change->fields["name"]
                )
            );

            $i = 0;
            foreach ($tickets as $data) {
                Session::addToNavigateListItems('Ticket', $data["id"]);
                Ticket::showShort(
                    $data['id'],
                    [
                        'row_num'                => $i,
                        'type_for_massiveaction' => __CLASS__,
                        'id_for_massiveaction'   => $data['linkid'],
                    ]
                );
                $i++;
            }
            Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
        }
        echo "</table>";
        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Show changes for a ticket
     *
     * @param $ticket Ticket object
     **/
    public static function showForTicket(Ticket $ticket)
    {
        /** @var \DBmysql $DB */
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
        $numrows = count($iterator);

        foreach ($iterator as $data) {
            $changes[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        if ($canedit) {
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => __CLASS__,
                'source_itemtype' => Ticket::class,
                'source_items_id' => $ID,
                'target_itemtype' => Change::class,
                'dropdown_options' => [
                    'entity'      => $ticket->getEntityID(),
                    'entity_sons' => $ticket->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                    'condition'   => Change::getOpenCriteria(),
                ],
                'create_link' => Session::haveRight(Change::$rightname, CREATE),
            ]);
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . __CLASS__ . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='12'>" . Change::getTypeName($numrows) . "</th>";
        echo "</tr>";
        if ($numrows) {
            Change::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
            Session::initNavigateListItems(
                'Change',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Ticket::getTypeName(1),
                    $ticket->fields["name"]
                )
            );

            $i = 0;
            foreach ($changes as $data) {
                Session::addToNavigateListItems('Change', $data["id"]);
                Change::showShort($data['id'], ['row_num'                => $i,
                    'type_for_massiveaction' => __CLASS__,
                    'id_for_massiveaction'   => $data['linkid'],
                ]);
                $i++;
            }
            Change::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
        }
        echo "</table>";

        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }

    public function post_addItem()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

        if ($donotif) {
            $change = new Change();
            $ticket  = new Ticket();
            if ($change->getFromDB($this->input["changes_id"]) && $ticket->getFromDB($this->input["tickets_id"])) {
                NotificationEvent::raiseEvent("update", $change);
                NotificationEvent::raiseEvent('update', $ticket);
            }
        }

        parent::post_addItem();
    }
}
