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
 * CommonITILActor Class
 **/
abstract class CommonITILActor extends CommonDBRelation
{
    // items_id_1, items_id_2, itemtype_1 and itemtype_2 are defined inside the inherited classes
    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;
    public $auto_message_on_action     = false;

    public const REQUESTER = 1;
    public const ASSIGN    = 2;
    public const OBSERVER  = 3;

    public function getActorForeignKey()
    {
        return static::$items_id_2;
    }

    public static function getItilObjectForeignKey()
    {
        return static::$items_id_1;
    }

    public function isAttach2Valid(array &$input)
    {
        // Anonymous user is valid if 'alternative_email' field is not empty
        if (
            isset($input['users_id']) && (int) $input['users_id'] === 0 && !empty($input['alternative_email'])
        ) {
            return true;
        }
        // Anonymous supplier is valid if 'alternative_email' field is not empty
        if (
            isset($input['suppliers_id']) && (int) $input['suppliers_id'] === 0 && !empty($input['alternative_email'])
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param int $items_id
     * @phpstan-param positive-int $items_id
     * @return array Array of actors
     **/
    public function getActors(int $items_id): array
    {
        global $DB;

        if ($items_id <= 0) {
            return [];
        }

        $users = [];
        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [static::getItilObjectForeignKey() => $items_id],
            'ORDER'  => 'id ASC',
        ]);
        foreach ($iterator as $data) {
            $users[$data['type']][] = $data;
        }
        return $users;
    }

    /**
     * @param $items_id
     * @param $email
     * @return bool
     */
    public function isAlternateEmailForITILObject($items_id, $email)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::getItilObjectForeignKey()   => $items_id,
                'alternative_email'                 => $email,
            ],
            'START'  => 0,
            'LIMIT'  => 1,
        ]);
        return count($iterator) > 0;
    }

    public function canUpdateItem(): bool
    {
        return (parent::canUpdateItem()
              || (isset($this->fields['users_id'])
                  && ((int) $this->fields['users_id'] === Session::getLoginUserID())));
    }

    public function canDeleteItem(): bool
    {
        return (parent::canDeleteItem()
              || (isset($this->fields['users_id'])
                  && ((int) $this->fields['users_id'] === Session::getLoginUserID())));
    }

    public function post_deleteFromDB()
    {
        global $CFG_GLPI;

        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

        $item = $this->getConnexityItem(static::$itemtype_1, static::getItilObjectForeignKey());

        if ($item instanceof CommonITILObject) {
            if (
                ($item->countSuppliers(self::ASSIGN) === 0)
                && ($item->countUsers(self::ASSIGN) === 0)
                && ($item->countGroups(self::ASSIGN) === 0)
                && ((int) $item->fields['status'] !== CommonITILObject::CLOSED)
                && ((int) $item->fields['status'] !== CommonITILObject::SOLVED)
            ) {
                $status = CommonITILObject::INCOMING;
                if (in_array((int) $item->fields['status'], Change::getNewStatusArray(), true)) {
                    $status = $item->fields['status'];
                }
                $item->update([
                    'id'     => $this->fields[static::getItilObjectForeignKey()],
                    'status' => $status,
                ]);
            } else {
                $item->updateDateMod($this->fields[static::getItilObjectForeignKey()]);

                if ($donotif) {
                    $options = [];
                    if (isset($this->fields['users_id'])) {
                        $options = ['_old_user' => $this->fields];
                    }
                    NotificationEvent::raiseEvent("update", $item, $options);
                }
            }
        }
        parent::post_deleteFromDB();
    }

    public function prepareInputForAdd($input)
    {
        // don't duplicate actors (search for existing before adding)
        // actors with $fk_field=0 are "email" actors
        $fk_field = $this->getActorForeignKey();
        if (isset($input[$fk_field]) && $input[$fk_field] > 0) {
            $current_type    = $input['type'] ?? 0;
            $actor_id        = $input[$fk_field];

            // check if the actor exists in database
            $actor = getItemForForeignKeyField($fk_field);
            if (!$actor->getFromDB($actor_id)) {
                return false;
            }

            $itil_items_id = $input[static::getItilObjectForeignKey()];
            $existing_actors = [];
            if (is_numeric($itil_items_id) && $itil_items_id > 0) {
                $existing_actors = $this->getActors((int) $itil_items_id);
            }
            $existing_ids    = array_column($existing_actors[$current_type] ?? [], $fk_field);

            // actor already exists
            if (in_array($actor_id, $existing_ids)) {
                return false;
            }
        }

        if (!isset($input['alternative_email']) || is_null($input['alternative_email'])) {
            $input['alternative_email'] = '';
        } elseif ($input['alternative_email'] != '' && !NotificationMailing::isUserAddressValid($input['alternative_email'])) {
            Session::addMessageAfterRedirect(
                __s('Invalid email address'),
                false,
                ERROR
            );
            return false;
        }
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['alternative_email']) && $input['alternative_email'] == '') {
            if (isset($input['users_id'])) {
                $actor = new User();
                if ($actor->getFromDB($input["users_id"])) {
                    $input['alternative_email'] = $actor->getDefaultEmail();
                }
            }
            if (isset($input['suppliers_id'])) {
                $actor = new Supplier();
                if ($actor->getFromDB($input["suppliers_id"])) {
                    $input['alternative_email'] = $actor->fields['email'];
                }
            }
        }
        if (
            isset($input['alternative_email']) && $input['alternative_email'] != ''
            && !NotificationMailing::isUserAddressValid($input['alternative_email'])
        ) {
            Session::addMessageAfterRedirect(
                __s('Invalid email address'),
                false,
                ERROR
            );
            return false;
        }

        $input = parent::prepareInputForUpdate($input);
        return $input;
    }

    public function post_addItem()
    {
        $item = getItemForItemtype(static::$itemtype_1);

        if (!($item instanceof CommonITILObject)) {
            throw new RuntimeException();
        }

        $no_stat_computation = true;
        if ($this->input['type'] == CommonITILActor::ASSIGN) {
            // Compute "take into account delay" unless "do not compute" flag was set by business rules
            $no_stat_computation = $item->isTakeIntoAccountComputationBlocked($this->input);
        }
        $item->updateDateMod($this->fields[static::getItilObjectForeignKey()], $no_stat_computation);

        if ($item->getFromDB($this->fields[static::getItilObjectForeignKey()])) {
            // Check object status and update it if needed
            if (
                $this->input['type'] == CommonITILActor::ASSIGN
                && !isset($this->input['_from_object'])
                && in_array($item->fields["status"], $item->getNewStatusArray())
                && in_array(CommonITILObject::ASSIGNED, array_keys($item->getAllStatusArray()))
            ) {
                $item->update(['id'               => $item->getID(),
                    'status'           => CommonITILObject::ASSIGNED,
                    '_from_assignment' => true,
                ]);
            }

            // raise notification for this actor addition
            if (!isset($this->input['_disablenotif'])) {
                $string_type = match ($this->input['type']) {
                    self::REQUESTER => 'requester',
                    self::OBSERVER  => 'observer',
                    self::ASSIGN    => 'assign',
                    default         => '',
                };
                // example for event: assign_group
                $event = $string_type . "_" . strtolower($this::$itemtype_2);
                NotificationEvent::raiseEvent($event, $item);
            }
        }

        parent::post_addItem();
    }
}
