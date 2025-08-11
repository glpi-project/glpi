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
use Glpi\Features\AssignableItem;
use Glpi\Inventory\Inventory;
use Glpi\Search\SearchOption;

/**
 *  Locked fields for inventory
 **/
class Lockedfield extends CommonDBTM
{
    /** @var CommonDBTM */
    private $item;

    // From CommonDBTM
    public $dohistory                   = false;

    public static $rightname                   = 'locked_field';

    public static function getTypeName($nb = 0)
    {
        return _n('Locked field', 'Locked fields', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['admin', Inventory::class, self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    public static function canView(): bool
    {
        return self::canUpdate();
    }

    public static function canPurge(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canCreate(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public function canCreateItem(): bool
    {
        if (empty($this->fields['itemtype'])) {
            return true;
        }
        return $this->canAccessItemEntity($this->fields['itemtype'], $this->fields['items_id']);
    }

    public function canUpdateItem(): bool
    {
        return $this->canAccessItemEntity($this->fields['itemtype'], $this->fields['items_id']);
    }

    public function canPurgeItem(): bool
    {
        return $this->canAccessItemEntity($this->fields['itemtype'], $this->fields['items_id']);
    }

    public static function isMassiveActionAllowed(int $items_id): bool
    {
        $lock = new self();
        $lock->getFromDB($items_id);
        if ($lock->canAccessItemEntity($lock->fields['itemtype'], $lock->fields['items_id'])) {
            return true;
        }
        return false;
    }

    public static function getPostFormAction(string $form_action, bool $action_success): ?string
    {
        // Always return to the locked fields list page
        return 'list';
    }

    /**
     * Check if user can access main item entity
     *
     * @param string $itemtype
     * @param int    $items_id
     *
     * @return bool
     */
    private function canAccessItemEntity(string $itemtype, int $items_id): bool
    {
        if (!($item = getItemForItemtype($itemtype))) {
            return false;
        }
        if (
            $item->getFromDB($items_id) //not a global lock
            && $item->isEntityAssign()
            && !Session::haveAccessToEntity($item->getEntityID(), $item->isRecursive()) // no access to main item entity
        ) {
            return false;
        }
        return true;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => 1,
            'table'              => $this->getTable(),
            'field'              => 'field',
            'name'               => __('Field name'),
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => __('Last inventoried value'),
            'nosort'             => true,
            'nosearch'           => true,
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'is_global',
            'name' => __('Global'),
            'datatype' => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', 1),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'nosearch'           => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-lock";
    }

    /**
     * Can item have locked fields
     *
     * @param CommonDBTM $item Item instance
     *
     * @retrun boolean
     */
    public function isHandled(CommonGLPI $item)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        $this->item = $item;
        return (bool) $item->isDynamic();
    }

    public function getLockedNames($itemtype, $items_id)
    {
        return $this->getLocks($itemtype, $items_id, true);
    }

    public function getLockedValues($itemtype, $items_id)
    {
        return $this->getLocks($itemtype, $items_id, false);
    }


    /**
     * Get locked fields
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * return array
     */
    final public function getFullLockedFields($itemtype, $items_id): array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                [
                    'OR' => [
                        'items_id'  => $items_id,
                        'is_global' => 1,
                    ],
                ],
            ],
        ]);

        $locks = [];
        foreach ($iterator as $row) {
            $locks[$row['id']] = $row;
        }
        return $locks;
    }

    /**
     * Get locked fields
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * return array
     */
    public function getLocks($itemtype, $items_id, bool $fields_only = true)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                [
                    'OR' => [
                        'items_id'  => $items_id,
                        'is_global' => 1,
                    ],
                ],
            ],
        ]);

        $locks = [];
        foreach ($iterator as $row) {
            if ($fields_only === true) {
                $locks[] = $row['field'];
            } else {
                $locks[$row['field']] = $row['value'];
            }
        }
        return $locks;
    }

    /**
     * Item has been deleted, remove all locks
     *
     * @return boolean
     */
    public function itemDeleted()
    {
        global $DB;
        return $DB->delete(
            $this->getTable(),
            [
                'itemtype'  => $this->item->getType(),
                'items_id'  => $this->item->fields['id'],
            ]
        );
    }

    /**
     * Store value from inventory on locked fields
     *
     * @return boolean
     */
    public function setLastValue($itemtype, $items_id, $field, $value)
    {
        global $DB;
        return $DB->update(
            $this->getTable(),
            [
                'value'  => $value,
            ],
            [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'field'     => $field,
            ]
        );
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'items_id':
                if (isset($values['items_id']) && !$values['items_id']) {
                    return '-';
                }
                if (isset($values['itemtype']) && is_a($values['itemtype'], CommonDBTM::class, true)) {
                    $itemtype = $values['itemtype'];
                    $item = new $itemtype();
                    $item->getFromDB($values['items_id']);
                    return $item->getLink(['comments' => $options['comments'] ?? false]);
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function getForbiddenStandardMassiveAction()
    {
        return ['update', 'clone'];
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    protected function prepareInput($input)
    {
        if (isset($input['item'])) {
            [$itemtype, $field] = explode(' - ', $input['item']);
            $input['itemtype'] = $itemtype;
            $input['items_id'] = 0;
            $input['field'] = $field;
            $input['is_global'] = 1;
        }

        return $input;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        unset($this->fields['is_global']);
        TemplateRenderer::getInstance()->display('pages/admin/inventory/lockedfield.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public function getFormFields(): array
    {
        $fields = parent::getFormFields();
        return array_filter($fields, static fn($field) => $field !== 'is_global');
    }


    /**
     * List of itemtypes/fields that can be locked globally
     *
     * @return array
     */
    public function getFieldsToLock(?string $specific_itemtype = null): array
    {
        global $CFG_GLPI, $DB;

        $iterator = $DB->request([
            'SELECT' => ['itemtype', 'field'],
            'FROM'   => $this->getTable(),
            'WHERE'  => ['is_global' => 1],
        ]);

        $lockeds = [];
        foreach ($iterator as $row) {
            $lockeds[$row['itemtype']][$row['field']] = true;
        }

        $lockable = [];
        $std_fields = [
            'name',
            'serial',
            'otherserial',
            'contact',
            'contactnum',
            'users_id_tech',
            'groups_id_tech',
            'users_id',
            'groups_id',
            'states_id',
            'locations_id',
            'networks_id',
            'manufacturers_id',
            'uuid',
            'comment',
        ];
        $itemtypes = $CFG_GLPI['inventory_types'] + $CFG_GLPI['inventory_lockable_objects'];

        if ($specific_itemtype !== null && in_array($specific_itemtype, $itemtypes)) {
            $itemtypes = [$specific_itemtype];
        }

        foreach ($itemtypes as $itemtype) {
            $search_options = SearchOption::getOptionsForItemtype($itemtype);
            $fields = $std_fields;
            $fields[] = strtolower($itemtype) . 'models_id'; //model relation field
            $fields[] = strtolower($itemtype) . 'types_id'; //type relation field

            foreach ($fields as $field) {
                $field_lockable = $DB->fieldExists($itemtype::getTable(), $field)
                    || (in_array($field, ['groups_id', 'groups_id_tech'], true) && Toolbox::hasTrait($itemtype, AssignableItem::class));
                if ($field_lockable && !isset($lockeds[$itemtype][$field])) {
                    $name = sprintf(
                        '%1$s - %2$s',
                        $itemtype,
                        $field
                    );

                    $field_name = $field;
                    foreach ($search_options as $search_option) {
                        if (isset($search_option['linkfield']) && $search_option['linkfield'] == $field) {
                            $field_name = $search_option['name'];
                            break;
                        } elseif (isset($search_option['field']) && $search_option['field'] == $field) {
                            $field_name = $search_option['name'];
                            break;
                        }
                    }

                    if ($field_name === $field) {
                        //name not found :(
                        $table = getTableNameForForeignKeyField($field);
                        if ($table !== '') {
                            $type = getItemTypeForTable($table);
                            $field_name = $type::getTypeName(1);
                        }
                    }

                    $dname = sprintf(
                        '%1$s - %2$s',
                        $itemtype::getTypeName(1),
                        $field_name
                    );

                    $lockable[$name] = $dname;
                }
            }
        }

        return $lockable;
    }
}
