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
 * Links CommonITILObjects to other CommonITILObjects
 * @since 11.0.0
 */
abstract class CommonITILObject_CommonITILObject extends CommonDBRelation
{
    public const LINK_TO        = 1;
    public const DUPLICATE_WITH = 2;
    public const SON_OF         = 3;
    public const PARENT_OF      = 4;

    public static function getTypeName($nb = 0)
    {
        return _n('Linked assistance object', 'Linked assistance objects', $nb);
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input[static::$items_id_1], $input[static::$items_id_2])) {
            return false;
        }

        // Clean values
        $input[static::$items_id_1] = (int) $input[static::$items_id_1];
        $input[static::$items_id_2] = (int) $input[static::$items_id_2];

        // Prevent self-link
        if (static::$itemtype_1 === static::$itemtype_2 && $input[static::$items_id_1] === $input[static::$items_id_2]) {
            return false;
        }

        if (!isset($input['link'])) {
            $input['link'] = self::LINK_TO;
        }

        $input = self::normalizeParentSonRelation($input);

        // No multiple links
        $links = static::getLinkedTo(static::$itemtype_1, $input[static::$items_id_1]);
        if (count($links)) {
            foreach ($links as $link) {
                // Allow reclassifying LINK_TO as DUPLICATE_WITH, but otherwise, no duplicates allowed
                if ($link['items_id'] === $input[static::$items_id_1] || $link['items_id'] === $input[static::$items_id_2]) {
                    if ((int) $link['link'] === self::LINK_TO && (int) $input['link'] === self::DUPLICATE_WITH) {
                        $link_item = getItemForItemtype($link['link_class']);
                        $link_item->delete(['id' => $link['id']]);
                        return $input;
                    }
                    // Even if link is updated, cancel the addition of the duplicate link
                    return false;
                }
            }
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = self::normalizeParentSonRelation($input);

        return parent::prepareInputForAdd($input);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case 'add':
                Dropdown::showSelectItemFromItemtypes([
                    'items_id_name'   => 'items_id_2',
                    'itemtype_name'   => 'itemtype_2',
                    'itemtypes'       => $CFG_GLPI['itil_types'],
                    'checkright'      => true,
                    'entity_restrict' => $_SESSION['glpiactive_entity'],
                ]);
                self::dropdownLinks('link');
                echo "<br><input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
                return true;
            case 'delete':
                Dropdown::showSelectItemFromItemtypes([
                    'items_id_name'   => 'items_id_2',
                    'itemtype_name'   => 'itemtype_2',
                    'itemtypes'       => $CFG_GLPI['itil_types'],
                    'checkright'      => true,
                    'entity_restrict' => $_SESSION['glpiactive_entity'],
                ]);
                echo "<br><input type='submit' name='delete' value=\"" . _sx('button', 'Delete permanently') . "\" class='btn btn-primary'>";
                return true;
        }
        return false;
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'add':
                $input = $ma->getInput();

                foreach ($ids as $id) {
                    if (empty($input['itemtype_2']) || empty($input['items_id_2']) || $item->getFromDB($id) === false) {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        return;
                    }
                    // Use ITIL object update method to use `CommonITILObject::manageITILObjectLinkInput()` logic.
                    $update_input = [
                        'id' => $id,
                        '_link' => [
                            'itemtype_1' => $item::class,
                            'items_id_1' => $id,
                            'itemtype_2' => $input['itemtype_2'],
                            'items_id_2' => $input['items_id_2'],
                            'link'       => $input['link'],
                        ],
                    ];
                    if ($item->can($id, UPDATE)) {
                        if ($item->update($update_input)) {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'delete':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    if (empty($input['itemtype_2']) || empty($input['items_id_2']) || $item->getFromDB($id) === false) {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        return;
                    }
                    $input['itemtype_1'] = $item::class;
                    $input['items_id_1'] = $id;

                    $link_class = self::getLinkClass($input['itemtype_1'], $input['itemtype_2']);

                    if (is_a($link_class, CommonDBRelation::class, true)) {
                        $condition = [];
                        $link = new $link_class();
                        if ($link_class::$itemtype_1 == $link_class::$itemtype_2) {
                            // Handle Change_Change, Problem_Problem, Ticket_Ticket
                            $condition = [
                                'OR' => [
                                    [
                                        $link_class::$items_id_1 => $input['items_id_1'],
                                        $link_class::$items_id_2 => $input['items_id_2'],
                                    ],
                                    [
                                        $link_class::$items_id_1 => $input['items_id_2'],
                                        $link_class::$items_id_2 => $input['items_id_1'],
                                    ],
                                ],
                            ];
                        } else {
                            $condition = [
                                $input['itemtype_1']::getForeignKeyField() => $input['items_id_1'],
                                $input['itemtype_2']::getForeignKeyField() => $input['items_id_2'],
                            ];
                        }

                        $link_found = $link->find($condition);

                        if (!empty($link_found)) {
                            $link_id = array_key_first($link_found);
                            if ($link->can($link_id, DELETE)) {
                                if ($link->delete(['id' => $link_id])) {
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
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        }
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function post_addItem()
    {
        $this->updateParentItems();

        parent::post_addItem();
    }

    public function post_deleteFromDB()
    {
        $this->updateParentItems();

        parent::post_deleteFromDB();
    }

    /**
     * Update parent items when relation is added/deleted.
     *
     * @return void
     */
    private function updateParentItems(): void
    {

        $item_1 = getItemForItemtype(static::$itemtype_1);
        $item_2 = getItemForItemtype(static::$itemtype_2);

        if (
            $item_1 instanceof CommonITILObject
            && $item_2 instanceof CommonITILObject
            && $item_1->getFromDB($this->fields[static::$items_id_1])
            && $item_2->getFromDB($this->fields[static::$items_id_2])
        ) {
            $item_1->updateDateMod($this->fields[static::$items_id_1]);
            $item_2->updateDateMod($this->fields[static::$items_id_2]);

            if (!isset($this->input['_disablenotif'])) {
                NotificationEvent::raiseEvent('update', $item_1);
                NotificationEvent::raiseEvent('update', $item_2);
            }
        }
    }

    /**
     * Get links to given item.
     *
     * @param string $itemtype Itemtype of the ITIL Object (Ticket, Change, or Problem)
     * @param int $items_id ID of the ITIL Object
     *
     * @return array Array of linked ITIL Objects  array(id=>linktype)
     **/
    public static function getLinkedTo(string $itemtype, int $items_id): array
    {
        if (static::class === self::class) {
            throw new LogicException(sprintf('%s should be called only from sub classes.', __METHOD__));
        }

        global $DB;

        $links = [];

        // Check if link is between 2 items of the same type
        $is_same_itemtype = static::$itemtype_1 === $itemtype && static::$itemtype_2 === $itemtype;

        if ($is_same_itemtype) {
            // Fetch items from Change_Change, Problem_Problem, or Ticket_Ticket
            $iterator = $DB->request([
                'FROM' => static::getTable(),
                'WHERE' => [
                    'OR' => [
                        static::$items_id_1 => $items_id,
                        static::$items_id_2 => $items_id,
                    ],
                ],
            ]);
        } else {
            // Fetch items from Change_Problem, Change_Ticket, or Problem_Ticket
            $item_fk = static::$itemtype_1 === $itemtype ? static::$items_id_1 : static::$items_id_2;
            $iterator = $DB->request([
                'FROM' => static::getTable(),
                'WHERE' => [
                    $item_fk => $items_id,
                ],
            ]);
        }

        foreach ($iterator as $data) {
            // Map data to array with itemtype_1, itemtype_2, items_id_1, items_id_2
            $link = [
                'id'            => $data['id'],
                'link_class'    => static::class,
                'itemtype_1'    => static::$itemtype_1,
                'itemtype_2'    => static::$itemtype_2,
                'items_id_1'    => $data[static::$items_id_1],
                'items_id_2'    => $data[static::$items_id_2],
                'link'          => $data['link'],
            ];
            if ($is_same_itemtype) {
                $link['itemtype'] = $link['itemtype_1'];
                if ($link['items_id_1'] === $items_id) {
                    $link['items_id'] = $link['items_id_2'];
                } else {
                    $link['items_id'] = $link['items_id_1'];
                }
            } else {
                if (static::$itemtype_1 === $itemtype) {
                    $link['itemtype'] = $link['itemtype_2'];
                    $link['items_id'] = $link['items_id_2'];
                } else {
                    $link['itemtype'] = $link['itemtype_1'];
                    $link['items_id'] = $link['items_id_1'];
                }
            }

            $links[static::class . '_' . $link['id']] = $link;
        }

        ksort($links);
        return $links;
    }

    /**
     * Get all links between given item and any CommonITILObject item.
     * Get linked CommonITILObjects to a specific CommonITILObject
     *
     * @param string $itemtype Itemtype of the ITIL Object (Ticket, Change, or Problem)
     * @param int $items_id ID of the ITIL Object
     *
     * @return array Array of linked ITIL Objects  array(id=>linktype)
     **/
    public static function getAllLinkedTo(string $itemtype, int $items_id): array
    {
        $link_classes = self::getAllLinkClasses();
        $links = [];

        foreach ($link_classes as $link_class) {
            // If the link class is for the given itemtype
            if ($link_class::$itemtype_1 === $itemtype || $link_class::$itemtype_2 === $itemtype) {
                $links = array_merge($links, $link_class::getLinkedTo($itemtype, $items_id));
            }
        }

        ksort($links);
        return $links;
    }

    public static function getITILLinkTypes(): array
    {
        return [
            self::LINK_TO => [
                'name' => __('Linked to'),
                'icon' => 'ti ti-link',
            ],
            self::DUPLICATE_WITH => [
                'name' => __('Duplicates'),
                'icon' => 'ti ti-copy-plus-filled',
            ],
            self::SON_OF => [
                'name' => __('Son of'),
                'icon' => 'ti ti-corner-left-up',
                'inverse' => self::PARENT_OF,
            ],
            self::PARENT_OF => [
                'name' => __('Parent of'),
                'icon' => 'ti ti-corner-left-down',
                'inverse' => self::SON_OF,
            ],
        ];
    }

    /**
     * Dropdown for link types
     *
     * @param string  $myname select name
     * @param integer $value  default value (default self::LINK_TO)
     *
     * @return void
     **/
    public static function dropdownLinks($myname, $value = self::LINK_TO)
    {
        $link_options = array_map(static fn($link) => $link['name'], self::getITILLinkTypes());
        Dropdown::showFromArray($myname, $link_options, ['value' => $value]);
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
        $link_types = static::getITILLinkTypes();

        if (!isset($link_types[$value])) {
            return htmlescape(NOT_AVAILABLE);
        }

        $icon_tag = '<i class="fas %1$s me-1" title="%2$s" data-bs-toggle="tooltip"></i>%2$s';

        $link_type = $link_types[$value];
        $resolved_value = $inverted && isset($link_type['inverse']) ? $link_types[$link_type['inverse']] : $link_type;

        // Handle simple label change for DUPLICATE_WITH when inverted
        if ($inverted && $value === self::DUPLICATE_WITH) {
            $resolved_value['name'] = __('Duplicated by');
        }
        return !$with_icon
            ? htmlescape($resolved_value['name'])
            : sprintf($icon_tag, htmlescape($resolved_value['icon']), htmlescape($resolved_value['name']));
    }

    /**
     * Get the name of the class that represents a link of the specified ITIL Object itemtypes.
     *
     * @param string $itemtype_1 The first itemtype
     * @param string $itemtype_2 The second itemtype
     * @return class-string<self>|null The name of the class representing the link or null if the provided itemtypes are not valid
     */
    public static function getLinkClass(string $itemtype_1, string $itemtype_2): ?string
    {
        $itemtypes = [$itemtype_1, $itemtype_2];
        if (in_array(Change::class, $itemtypes, true) && in_array(Problem::class, $itemtypes, true)) {
            return Change_Problem::class;
        } elseif (in_array(Change::class, $itemtypes, true) && in_array(Ticket::class, $itemtypes, true)) {
            return Change_Ticket::class;
        } elseif (in_array(Problem::class, $itemtypes, true) && in_array(Ticket::class, $itemtypes, true)) {
            return Problem_Ticket::class;
        } elseif ($itemtype_1 === $itemtype_2) {
            if ($itemtype_1 === Change::class) {
                return Change_Change::class;
            } elseif ($itemtype_1 === Problem::class) {
                return Problem_Problem::class;
            } elseif ($itemtype_1 === Ticket::class) {
                return Ticket_Ticket::class;
            }
        }
        return null;
    }

    /**
     * Get array of all ITIL Object link classes
     * @return class-string<CommonITILObject_CommonITILObject>[]
     */
    public static function getAllLinkClasses(): array
    {
        return [
            Change_Change::class,
            Change_Problem::class,
            Change_Ticket::class,
            Problem_Problem::class,
            Problem_Ticket::class,
            Ticket_Ticket::class,
        ];
    }

    /**
     * Count all links between given item and any CommonITILObject item.
     *
     * @param string $itemtype
     * @param int $items_id
     *
     * @return int
     */
    public static function countAllLinks(string $itemtype, int $items_id): int
    {
        $links = static::getAllLinkedTo($itemtype, $items_id);
        return count($links);
    }

    /**
     * Count linked ITIL Objects.
     *
     * @param class-string<CommonITILObject> $itemtype The given item's type
     * @param int $items_id The given item's ID
     * @param array<int> $status Optional array of statuses that the linked item must have to be included.
     *  If no statuses are specified, then linked items of all statuses will be included.
     * @param array<int> $link_types Optional array of link types that the linked item must have to be included.
     *  If no link types are specified, then linked items of all link types will be included.
     * @return int The number of linked ITIL Objects
     */
    public static function countLinksByStatus(string $itemtype, int $items_id, array $status = [], array $link_types = []): int
    {
        if (static::class === self::class) {
            throw new LogicException(sprintf('%s should be called only from sub classes.', __METHOD__));
        }

        global $DB;

        $count = 0;

        if (static::$itemtype_1 === static::$itemtype_2) {
            $linked_table = $itemtype::getTable();
            $linked_fk    = static::$items_id_1;

            $where = [
                'links.' . static::$items_id_2 => $items_id,
            ];

            if (in_array(self::PARENT_OF, $link_types, true)) {
                if (($key = array_search(self::PARENT_OF, $link_types, true)) !== false) {
                    unset($link_types[$key]);
                }
                $other_link_types = $link_types;
                if (($key = array_search(self::SON_OF, $other_link_types, true)) !== false) {
                    unset($other_link_types[$key]);
                }
                // Count everything except SON_OF links using original parameters
                if ($other_link_types !== []) {
                    $count = static::countLinksByStatus($itemtype, $items_id, $status, $other_link_types);
                }

                // Count only SON_OF links here using swapped parameters
                $where = [
                    'links.' . static::$items_id_1 => $items_id,
                ];
                $link_types = [self::SON_OF];
                $linked_fk  = static::$items_id_2;
            }
        } else {
            $linked_table = static::$itemtype_1 === $itemtype ? static::$itemtype_2::getTable() : static::$itemtype_1::getTable();
            $linked_fk    = static::$itemtype_1 === $itemtype ? static::$items_id_2 : static::$items_id_1;

            $where_fk = static::$itemtype_1 === $itemtype ? static::$items_id_1 : static::$items_id_2;
            $where = [
                'links.' . $where_fk => $items_id,
            ];
        }

        if ($link_types !== []) {
            $where['links.link'] = $link_types;
        }

        if ($status !== []) {
            $where['items.status'] = $status;
        }

        $result = $DB->request([
            'COUNT'        => 'cpt',
            'FROM'         => static::getTable() . ' AS links',
            'INNER JOIN'   => [
                $linked_table . ' AS items' => [
                    'ON' => [
                        'links' => $linked_fk,
                        'items' => 'id',
                    ],
                ],
            ],
            'WHERE'        => $where,
        ])->current();
        return ((int) $result['cpt']) + $count;
    }

    public static function manageLinksOnChange($itemtype, $items_id, $changes): void
    {
        if ($itemtype === Ticket::class) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($items_id)) {
                $linked_tickets = Ticket_Ticket::getLinkedTo($itemtype, $items_id);
                if (count($linked_tickets)) {
                    $tickets = array_filter($linked_tickets, static function ($data) {
                        $linked_ticket = new Ticket();
                        $linked_ticket->getFromDB($data['items_id']);
                        return $linked_ticket->can($data['items_id'], UPDATE)
                            && ($data['link'] === self::DUPLICATE_WITH)
                            && ($linked_ticket->fields['status'] !== CommonITILObject::SOLVED)
                            && ($linked_ticket->fields['status'] !== CommonITILObject::CLOSED);
                    });

                    if (isset($changes['_solution']) && $changes['_solution'] instanceof ITILSolution) {
                        // Add same solution to duplicates
                        $solution = $changes['_solution'];
                        $solution_data = $solution->fields;
                        unset($solution_data['id'], $solution_data['date_creation'], $solution_data['date_mod']);

                        foreach ($tickets as $data) {
                            $solution_data['items_id'] = $data['items_id'];
                            $solution_data['_linked_ticket'] = true;
                            $new_solution = new ITILSolution();
                            $new_solution->add($solution_data);
                        }
                    } elseif (isset($changes['status']) && in_array($changes['status'], Ticket::getSolvedStatusArray())) {
                        $linked_ticket = new Ticket();
                        foreach ($tickets as $data) {
                            $linked_ticket->update([
                                'id'     => $data['items_id'],
                                'status' => $changes['status'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Normalize input from generic format to expected format.
     * e.g. [`itemtype_1`, `items_id_1`, `itemtype_2`, `items_id_2`, `link`] -> [`changes_id`, `problems_id`, `link`]
     *
     * @param array $input
     *
     * @return array
     */
    final public function normalizeInput(array $input): array
    {
        if (!isset($input['itemtype_1'], $input['items_id_1'], $input['itemtype_2'], $input['items_id_2'])) {
            // Not enough data, cannot normalize
            return $input;
        }

        if (!array_key_exists('link', $input)) {
            $input['link'] = self::LINK_TO;
        }

        if (
            $input['itemtype_1'] !== $input['itemtype_2']
            && $input['itemtype_1'] === static::$itemtype_2 && $input['itemtype_2'] === static::$itemtype_1
        ) {
            // Ensure that link always qualifies relation FROM `$items_id_1` TO `$items_id_2`.
            $input = [
                'itemtype_1' => $input['itemtype_2'],
                'items_id_1' => $input['items_id_2'],
                'itemtype_2' => $input['itemtype_1'],
                'items_id_2' => $input['items_id_1'],
                'link'       => in_array((int) $input['link'], [self::SON_OF, self::PARENT_OF])
                    ? ((int) $input['link'] === self::SON_OF ? self::PARENT_OF : self::SON_OF)
                    : $input['link'],
            ];
        }

        // Transform itemtype/items_id to foreign keys
        $input = [
            static::$items_id_1 => $input['items_id_1'],
            static::$items_id_2 => $input['items_id_2'],
            'link'              => $input['link'],
        ];

        $input = self::normalizeParentSonRelation($input);

        return $input;
    }

    /**
     * Normalize PARENT_OF/SON_OF relation.
     *
     * This method ensure to always use SON_OF relations for relation between 2 identical itemtypes, as it is
     * a prerequisite for "Parent/Child" search options that cannot use conditional `linkfield`.
     *
     * @param array $input
     *
     * @return array
     */
    final protected static function normalizeParentSonRelation(array $input): array
    {
        if (static::$itemtype_1 !== static::$itemtype_2) {
            return $input; // Normalization only affects relation between same itemtypes
        }

        if (array_key_exists('link', $input) && $input['link'] == self::PARENT_OF) {
            $input = array_merge(
                $input,
                [
                    static::$items_id_1 => $input[static::$items_id_2],
                    static::$items_id_2 => $input[static::$items_id_1],
                    'link' => self::SON_OF,
                ]
            );
        }

        return $input;
    }
}
