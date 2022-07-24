<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/// Class Ticket links
class Ticket_Ticket extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1     = 'Ticket';
    public static $items_id_1     = 'tickets_id_1';
    public static $itemtype_2     = 'Ticket';
    public static $items_id_2     = 'tickets_id_2';

    public static $check_entity_coherency = false;

   // Ticket links
    const LINK_TO        = 1;
    const DUPLICATE_WITH = 2;
    const SON_OF         = 3;
    const PARENT_OF      = 4;


    public static function getTypeName($nb = 0)
    {
        return _n('Linked ticket', 'Linked tickets', $nb);
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add':
                Ticket_Ticket::dropdownLinks('link');
                printf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID'));
                echo "&nbsp;<input type='text' name='tickets_id_1' value='' size='10'>\n";
                echo "<br><br>";
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='" .
                           _sx('button', 'Post') . "'>";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'add':
                $input = $ma->getInput();
                $ticket = new Ticket();
                if (
                    isset($input['link'])
                    && isset($input['tickets_id_1'])
                ) {
                    if ($item->getFromDB($input['tickets_id_1'])) {
                        foreach ($ids as $id) {
                              $input2                          = [];
                              $input2['id']                    = $input['tickets_id_1'];
                              $input2['_link']['tickets_id_1'] = $id;
                              $input2['_link']['link']         = $input['link'];
                              $input2['_link']['tickets_id_2'] = $input['tickets_id_1'];
                            if ($item->can($input['tickets_id_1'], UPDATE)) {
                                if ($ticket->update($input2)) {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * Get linked tickets to a ticket
     *
     * @param $ID ID of the ticket id
     *
     * @return array of linked tickets  array(id=>linktype)
     **/
    public static function getLinkedTicketsTo($ID)
    {
        global $DB;

       // Make new database object and fill variables
        if (empty($ID)) {
            return false;
        }

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR'  => [
                    'tickets_id_1' => $ID,
                    'tickets_id_2' => $ID
                ]
            ]
        ]);
        $tickets = [];

        foreach ($iterator as $data) {
            if ($data['tickets_id_1'] != $ID) {
                $tickets[$data['id']] = [
                    'link'         => $data['link'],
                    'tickets_id_1' => $data['tickets_id_1'],
                    'tickets_id'   => $data['tickets_id_1']
                ];
            } else {
                $tickets[$data['id']] = [
                    'link'       => $data['link'],
                    'tickets_id' => $data['tickets_id_2']
                ];
            }
        }

        ksort($tickets);
        return $tickets;
    }


    /**
     * Dropdown for links between tickets
     *
     * @param string  $myname select name
     * @param integer $value  default value (default self::LINK_TO)
     *
     * @return void
     **/
    public static function dropdownLinks($myname, $value = self::LINK_TO)
    {

        $tmp[self::LINK_TO]        = __('Linked to');
        $tmp[self::DUPLICATE_WITH] = __('Duplicates');
        $tmp[self::SON_OF]         = __('Son of');
        $tmp[self::PARENT_OF]      = __('Parent of');
        Dropdown::showFromArray($myname, $tmp, ['value' => $value]);
    }


    /**
     * Get Link Name
     *
     * @param integer $value     Current value
     * @param boolean $inverted  Whether to invert label
     * @param boolean $with_icon prefix label with an icon
     *
     * @return string
     **/
    public static function getLinkName($value, bool $inverted = false, bool $with_icon = false): string
    {
        $tmp = [];

        if (!$inverted) {
            $tmp[self::LINK_TO]        = __('Linked to');
            $tmp[self::DUPLICATE_WITH] = __('Duplicates');
            $tmp[self::SON_OF]         = __('Son of');
            $tmp[self::PARENT_OF]      = __('Parent of');
        } else {
            $tmp[self::LINK_TO]        = __('Linked to');
            $tmp[self::DUPLICATE_WITH] = __('Duplicated by');
            $tmp[self::SON_OF]         = __('Parent of');
            $tmp[self::PARENT_OF]      = __('Son of');
        }

        if ($with_icon) {
            $icon_tag = '<i class="fas %1$s me-1" title="%2$s" data-bs-toggle="tooltip"></i>%2$s';
            $tmp[self::LINK_TO]        = sprintf($icon_tag, "fa-link", $tmp[self::LINK_TO]);
            $tmp[self::DUPLICATE_WITH] = sprintf($icon_tag, "fa-clone", $tmp[self::DUPLICATE_WITH]);
            $icon_son                  = $inverted ? "fa-level-down-alt" : "fa-level-up-alt fa-flip-horizontal";
            $tmp[self::SON_OF]         = sprintf($icon_tag, $icon_son, $tmp[self::SON_OF]);
            $icon_parent               = $inverted ? "fa-level-down-alt" : "fa-level-up-alt";
            $tmp[self::PARENT_OF]      = sprintf($icon_tag, $icon_parent, $tmp[self::PARENT_OF]);
        }

        if (isset($tmp[$value])) {
            return $tmp[$value];
        }
        return NOT_AVAILABLE;
    }


    public function prepareInputForAdd($input)
    {
       // Clean values
        $input['tickets_id_1'] = Toolbox::cleanInteger($input['tickets_id_1']);
        $input['tickets_id_2'] = Toolbox::cleanInteger($input['tickets_id_2']);

       // Check of existance of rights on both Ticket(s) is done by the parent
        if ($input['tickets_id_2'] == $input['tickets_id_1']) {
            return false;
        }

        if (!isset($input['link'])) {
            $input['link'] = self::LINK_TO;
        }

        $this->checkParentSon($input);

       // No multiple links
        $tickets = self::getLinkedTicketsTo($input['tickets_id_1']);
        if (count($tickets)) {
            foreach ($tickets as $key => $t) {
                if ($t['tickets_id'] == $input['tickets_id_2']) {
                    // Delete old simple link
                    if (
                        ($input['link'] == self::DUPLICATE_WITH)
                        && ($t['link'] == self::LINK_TO)
                    ) {
                        $tt = new Ticket_Ticket();
                        $tt->delete(["id" => $key]);
                    } else { // No duplicate link
                        return false;
                    }
                }
            }
        }

        return parent::prepareInputForAdd($input);
    }


    public function prepareInputForUpdate($input)
    {
        $this->checkParentSon($input);
        return parent::prepareInputForAdd($input);
    }


    /**
     * Check for parent relation (inverse of son)
     *
     * @param array $input Input
     *
     * @return void
     */
    public function checkParentSon(&$input)
    {
        if (isset($input['link']) && $input['link'] == Ticket_Ticket::PARENT_OF) {
           //a PARENT_OF relation is an inverted SON_OF one :)
            $id1 = $input['tickets_id_2'];
            $id2 = $input['tickets_id_1'];
            $input['tickets_id_1'] = $id1;
            $input['tickets_id_2'] = $id2;
            $input['link']         = Ticket_Ticket::SON_OF;
        }
    }


    public function post_deleteFromDB()
    {
        global $CFG_GLPI;

        $t = new Ticket();
        $t->updateDateMod($this->fields['tickets_id_1']);
        $t->updateDateMod($this->fields['tickets_id_2']);
        parent::post_deleteFromDB();

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];
        if ($donotif) {
            $t->getFromDB($this->fields['tickets_id_1']);
            NotificationEvent::raiseEvent("update", $t);
            $t->getFromDB($this->fields['tickets_id_2']);
            NotificationEvent::raiseEvent("update", $t);
        }
    }


    public function post_addItem()
    {
        global $CFG_GLPI;

        $t = new Ticket();
        $t->updateDateMod($this->fields['tickets_id_1']);
        $t->updateDateMod($this->fields['tickets_id_2']);
        parent::post_addItem();

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];
        if ($donotif) {
            $t->getFromDB($this->fields['tickets_id_1']);
            NotificationEvent::raiseEvent("update", $t);
            $t->getFromDB($this->fields['tickets_id_2']);
            NotificationEvent::raiseEvent("update", $t);
        }
    }


    /**
     * Count number of open children for a parent
     *
     * @param integer $pid Parent ID
     *
     * @return integer
     */
    public static function countOpenChildren($pid)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'        => 'cpt',
            'FROM'         => self::getTable() . ' AS links',
            'INNER JOIN'   => [
                Ticket::getTable() . ' AS tickets' => [
                    'ON' => [
                        'links'     => 'tickets_id_1',
                        'tickets'   => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                'links.link'         => self::SON_OF,
                'links.tickets_id_2' => $pid,
                'NOT'                => [
                    'tickets.status'  => Ticket::getClosedStatusArray() + Ticket::getSolvedStatusArray()
                ]
            ]
        ])->current();
        return (int)$result['cpt'];
    }


    /**
     * Affect the same solution/status for duplicates tickets.
     *
     * @param integer           $ID        ID of the ticket id
     * @param ITILSolution|null $solution  Ticket's solution
     *
     * @return void
     **/
    public static function manageLinkedTicketsOnSolved($ID, $solution = null)
    {

        $ticket = new Ticket();
        if (!$ticket->getfromDB($ID)) {
            return;
        }
        $tickets = self::getLinkedTicketsTo($ID);

        if (false === $tickets) {
            return;
        }

        $tickets = array_filter(
            $tickets,
            function ($data) {
                $linked_ticket = new Ticket();
                $linked_ticket->getFromDB($data['tickets_id']);
                return $linked_ticket->can($data['tickets_id'], UPDATE)
                && ($data['link'] == self::DUPLICATE_WITH)
                && ($linked_ticket->fields['status'] != CommonITILObject::SOLVED)
                && ($linked_ticket->fields['status'] != CommonITILObject::CLOSED);
            }
        );

        if (null === $solution) {
           // Change status without adding a solution
           // This will be done if a ticket is solved/closed without a solution
            foreach ($tickets as $data) {
                $linked_ticket = new Ticket();
                $linked_ticket->update(
                    [
                        'id'     => $data['tickets_id'],
                        'status' => $ticket->fields['status']
                    ]
                );
            }
        } else {
           // Add same solution to duplicates
            $solution_data = $solution->fields;
            unset($solution_data['id']);
            unset($solution_data['date_creation']);
            unset($solution_data['date_mod']);

            foreach ($tickets as $data) {
                $solution_data['items_id'] = $data['tickets_id'];
                $solution_data['_linked_ticket'] = true;
                $new_solution = new ITILSolution();
                $new_solution->add(Toolbox::addslashes_deep($solution_data));
            }
        }
    }
}
