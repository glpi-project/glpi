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
use Glpi\DBAL\QueryFunction;
use Glpi\Features\Clonable;
use Glpi\Features\StateInterface;

use function Safe\strtotime;

/**
 *  Contract class
 */
class Contract extends CommonDBTM implements StateInterface
{
    use Clonable;
    use Glpi\Features\State;

    // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['ContractCost'];

    public static $rightname                   = 'contract';
    protected $usenotepad               = true;

    public const RENEWAL_NEVER = 0;
    public const RENEWAL_TACIT = 1;
    public const RENEWAL_EXPRESS = 2;

    public function getCloneRelations(): array
    {
        return [
            Contract_Item::class,
            Contract_Supplier::class,
            ContractCost::class,
            KnowbaseItem_Item::class,
            ManualLink::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Contract', 'Contracts', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'financial';
    }

    public function post_getEmpty()
    {
        if (isset($_SESSION['glpiactive_entity'])) {
            $this->fields["alert"] = Entity::getUsedConfig(
                "use_contracts_alert",
                $_SESSION['glpiactive_entity'],
                "default_contract_alert",
                0
            );
        }
        $this->fields["notice"] = 0;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Contract_Item::class,
                Contract_Supplier::class,
                ContractCost::class,
            ]
        );

        // Alert does not extends CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete(static::class, $this->fields['id']);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(ContractCost::class, $ong, $options);
        $this->addStandardTab(Contract_Supplier::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Ticket_Contract::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function pre_updateInDB()
    {
        // Clean end alert if begin_date is after old one
        // Or if duration is greater than old one
        if (
            (isset($this->oldvalues['begin_date'])
            && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
            || (isset($this->oldvalues['duration'])
              && ($this->oldvalues['duration'] < $this->fields['duration']))
        ) {
            $alert = new Alert();
            $alert->clear(static::class, $this->fields['id'], Alert::END);
        }

        // Clean notice alert if begin_date is after old one
        // Or if duration is greater than old one
        // Or if notice is lesser than old one
        if (
            (isset($this->oldvalues['begin_date'])
            && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
            || (isset($this->oldvalues['duration'])
              && ($this->oldvalues['duration'] < $this->fields['duration']))
            || (isset($this->oldvalues['notice'])
              && ($this->oldvalues['notice'] > $this->fields['notice']))
        ) {
            $alert = new Alert();
            $alert->clear(static::class, $this->fields['id'], Alert::NOTICE);
        }
    }

    /**
     * Print the contract form
     *
     * @inheritDoc
     */
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/management/contract.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $joinparams = [
            'beforejoin' => [
                'table'      => 'glpi_contracts_items',
                'joinparams' => [
                    'jointype' => 'itemtype_item',
                ],
            ],
        ];

        $joinparamscost = [
            'jointype'   => 'child',
            'beforejoin' => [
                'table'      => 'glpi_contracts',
                'joinparams' => $joinparams,
            ],
        ];

        $tab[] = [
            'id'                 => 'contract',
            'name'               => self::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '139',
            'table'              => 'glpi_contracts_items',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of contracts'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => 'glpi_contracts',
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => 'glpi_contracts',
            'field'              => 'num',
            'name'               => __('Number'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '129',
            'table'              => 'glpi_contracttypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contracts',
                    'joinparams'         => $joinparams,
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '130',
            'table'              => 'glpi_contracts',
            'field'              => 'duration',
            'name'               => __('Duration'),
            'datatype'           => 'number',
            'max'                => '120',
            'unit'               => 'month',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '131',
            'table'              => 'glpi_contracts',
            'field'              => 'periodicity',
            //TRANS: %1$s is Contract, %2$s is field name
            'name'               => __('Periodicity'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
            'datatype'           => 'number',
            'min'                => '1',
            'max'                => '60',
            'step'               => '1',
            'toadd'              => [
                0 => Dropdown::EMPTY_VALUE,
            ],
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '132',
            'table'              => 'glpi_contracts',
            'field'              => 'begin_date',
            'name'               => __('Start date'),
            'forcegroupby'       => true,
            'datatype'           => 'date',
            'maybefuture'        => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '133',
            'table'              => 'glpi_contracts',
            'field'              => 'accounting_number',
            'name'               => __('Account number'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '134',
            'table'              => 'glpi_contracts',
            'field'              => 'end_date',
            'name'               => __('End date'),
            'forcegroupby'       => true,
            'datatype'           => 'date_delay',
            'maybefuture'        => true,
            'datafields'         => [
                '1'                  => 'begin_date',
                '2'                  => 'duration',
            ],
            'searchunit'         => 'MONTH',
            'delayunit'          => 'MONTH',
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '135',
            'table'              => 'glpi_contracts',
            'field'              => 'notice',
            'name'               => __('Notice'),
            'datatype'           => 'number',
            'max'                => '120',
            'unit'               => 'month',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '136',
            'table'              => 'glpi_contractcosts',
            'field'              => 'totalcost',
            'name'               => _n('Cost', 'Costs', 1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'decimal',
            'massiveaction'      => false,
            'joinparams'         => $joinparamscost,
            'computation'        => '(' . QueryFunction::sum('TABLE.cost') . ' / '
                . QueryFunction::count('TABLE.id') . ') * '
                . QueryFunction::count('TABLE.id', distinct: true),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '137',
            'table'              => 'glpi_contracts',
            'field'              => 'billing',
            'name'               => __('Invoice period'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
            'datatype'           => 'number',
            'min'                => '1',
            'max'                => '60',
            'step'               => '1',
            'toadd'              => [
                0 => Dropdown::EMPTY_VALUE,
            ],
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '138',
            'table'              => 'glpi_contracts',
            'field'              => 'renewal',
            'name'               => __('Renewal'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '139',
            'table'              => 'glpi_locations',
            'field'              => 'completename',
            'name'               => Location::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contracts',
                    'joinparams'         => $joinparams,
                ],
            ],
        ];

        return $tab;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $prefix                    = 'Contract_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'add']    = "<i class='ti ti-package'></i>" . _sx('button', 'Add an item');
            $actions[$prefix . 'remove'] = "<i class='ti ti-package-off'></i>" . _sx('button', 'Remove an item');
            $actions['Contract_Supplier' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
               = "<i class='" . htmlescape(Supplier::getIcon()) . "'></i>" . _sx('button', 'Add a supplier');
        }

        return $actions;
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'alert':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return self::dropdownAlert($options);

            case 'renewal':
                $options['name']  = $name;
                return self::dropdownContractRenewal($name, $values[$field], false);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        return match ($field) {
            'alert' => htmlescape(self::getAlertName($values[$field])),
            'renewal' => htmlescape(self::getContractRenewalName((int) $values[$field])),
            '_virtual_expiration' => Infocom::getWarrantyExpir(
                $values['begin_date'],
                $values['renewal'] == self::RENEWAL_EXPRESS ? $values['duration'] + $values['periodicity'] : $values['duration'],
                0,
                true,
                (int) $values['renewal'] === self::RENEWAL_TACIT,
                $values['periodicity']
            ),
            default => parent::getSpecificValueToDisplay($field, $values, $options),
        };
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $locations_sos = Location::rawSearchOptionsToAdd();
        foreach ($locations_sos as &$locations_so) {
            if ($locations_so['id'] == '3') {
                //initially used in contracts
                $locations_so['id'] = '8';
            }
        }
        $tab = array_merge($tab, $locations_sos);

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'num',
            'name'               => _x('phone', 'Number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_contracttypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'begin_date',
            'name'               => __('Start date'),
            'datatype'           => 'date',
            'maybefuture'        => true,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'duration',
            'name'               => __('Duration'),
            'datatype'           => 'number',
            'max'                => 120,
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'date_delay',
            'datafields'         => [
                '1'                  => 'begin_date',
                '2'                  => 'duration',
            ],
            'searchunit'         => 'MONTH',
            'delayunit'          => 'MONTH',
            'maybefuture'        => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'notice',
            'name'               => __('Notice'),
            'datatype'           => 'number',
            'max'                => 120,
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'periodicity',
            'name'               => __('Periodicity'),
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 60,
            'step'               => 1,
            'toadd'              => [
                0 => Dropdown::EMPTY_VALUE,
            ],
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => static::getTable(),
            'field'              => 'billing',
            'name'               => __('Invoice period'),
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 60,
            'step'               => 1,
            'toadd'              => [
                0 => Dropdown::EMPTY_VALUE,
            ],
            'unit'               => 'month',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'accounting_number',
            'name'               => __('Account number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => static::getTable(),
            'field'              => 'renewal',
            'name'               => __('Renewal'),
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => '_virtual_expiration', // virtual field
            'additionalfields'   => [
                'begin_date',
                'duration',
                'renewal',
                'periodicity',
            ],
            'name'               => __('Expiration'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'expire_notice',
            'name'               => __('Expiration date + notice'),
            'datatype'           => 'date_delay',
            'datafields'         => [
                '1'                  => 'begin_date',
                '2'                  => 'duration',
                '3'                  => 'notice',
            ],
            'searchunit'         => 'DAY',
            'delayunit'          => 'MONTH',
            'maybefuture'        => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '59',
            'table'              => static::getTable(),
            'field'              => 'alert',
            'name'               => __('Email alarms'),
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '72',
            'table'              => 'glpi_contracts_items',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of items'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => 'glpi_suppliers',
            'field'              => 'name',
            'name'               => _n(
                'Associated supplier',
                'Associated suppliers',
                Session::getPluralNumber()
            ),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contracts_suppliers',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => static::getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(static::class));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => 'cost',
            'name'               => _n('Cost', 'Costs', 1),
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => 'glpi_contractcosts',
            'field'              => 'totalcost',
            'name'               => __('Total cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation'        => '(' . QueryFunction::sum('TABLE.cost') . ' / '
                . QueryFunction::count('TABLE.id') . ') * '
                . QueryFunction::count('TABLE.id', distinct: true),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '41',
            'table'              => 'glpi_contractcosts',
            'field'              => 'cost',
            'name'               => _n('Cost', 'Costs', Session::getPluralNumber()),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => 'glpi_contractcosts',
            'field'              => 'begin_date',
            'name'               => sprintf(__('%1$s - %2$s'), _n('Cost', 'Costs', 1), __('Begin date')),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '43',
            'table'              => 'glpi_contractcosts',
            'field'              => 'end_date',
            'name'               => sprintf(__('%1$s - %2$s'), _n('Cost', 'Costs', 1), __('End date')),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => 'glpi_contractcosts',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s - %2$s'), _n('Cost', 'Costs', 1), __('Name')),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => 'glpi_budgets',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s - %2$s'), _n('Cost', 'Costs', 1), Budget::getTypeName(1)),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contractcosts',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        return $tab;
    }

    /**
     * Show central contract resume
     * HTML array
     *
     * @param bool $display if false, return html
     *
     * @return string|void Return generated content if `display` parameter is true.
     **/
    public static function showCentral(bool $display = true)
    {
        global $CFG_GLPI, $DB;

        if (!self::canView()) {
            return;
        }

        $end_date = QueryFunction::dateAdd(
            date: 'begin_date',
            interval: new QueryExpression($DB::quoteName('duration')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_now = QueryFunction::dateDiff(
            expression1: $end_date,
            expression2: QueryFunction::curDate()
        );

        // No recursive contract, not in local management
        // contrats echus depuis moins de 30j
        $table = self::getTable();
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted'   => 0,
                new QueryExpression("$end_date_diff_now  > -30"),
                new QueryExpression("$end_date_diff_now < 0"),
            ] + getEntitiesRestrictCriteria($table),
        ])->current();
        $contract0 = $result['cpt'];

        // contrats  echeance j-7
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted'   => 0,
                new QueryExpression("$end_date_diff_now > 0"),
                new QueryExpression("$end_date_diff_now  <= 7"),
            ] + getEntitiesRestrictCriteria($table),
        ])->current();
        $contract7 = $result['cpt'];

        // contrats echeance j -30
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted'   => 0,
                new QueryExpression("$end_date_diff_now > 7"),
                new QueryExpression("$end_date_diff_now < 30"),
            ] + getEntitiesRestrictCriteria($table),
        ])->current();
        $contract30 = $result['cpt'];

        $notice_date = QueryFunction::dateAdd(
            date: 'begin_date',
            interval: new QueryExpression($DB::quoteName('duration') . ' - ' . $DB::quoteName('notice')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_notice = QueryFunction::dateDiff(
            expression1: $notice_date,
            expression2: QueryFunction::curDate()
        );

        // contrats avec pr??avis echeance j-7
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted'   => 0,
                'notice'       => ['<>', 0],
                new QueryExpression("$end_date_diff_notice > 0"),
                new QueryExpression("$end_date_diff_notice <= 7"),
            ] + getEntitiesRestrictCriteria($table),
        ])->current();
        $contractpre7 = $result['cpt'];

        // contrats avec pr??avis echeance j -30
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted'   => 0,
                'notice'       => ['<>', 0],
                new QueryExpression("$end_date_diff_notice > 7"),
                new QueryExpression("$end_date_diff_notice < 30"),
            ] + getEntitiesRestrictCriteria($table),
        ])->current();
        $contractpre30 = $result['cpt'];

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?reset=reset",
                'text'   =>  self::getTypeName(1),
                'icon'   => self::getIcon(),
            ],
            'items'     => [],
        ];

        $options = [
            'reset' => 'reset',
            'sort'  => 20,
            'order' => 'DESC',
            'start' => 0,
            'criteria' => [
                [
                    'field'      => 20,
                    'value'      => 'NOW',
                    'searchtype' => 'lessthan',
                ],
                [
                    'field'      => 20,
                    'link'       => 'AND',
                    'searchtype' => 'morethan',
                    'value'      => '-1MONTH',
                ],
            ],
        ];

        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expired in the last 30 days'),
            'count'  => $contract0,
        ];

        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value']      = 'NOW';
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value']      = '7DAY';
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expiring in less than 7 days'),
            'count'  => $contract7,
        ];

        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value']      = '6DAY';
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value']      = '1MONTH';
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expiring in less than 30 days'),
            'count'  => $contract30,
        ];

        $options['criteria'][0]['field'] = 13;
        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value'] = 'NOW';
        $options['criteria'][1]['field'] = 13;
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value'] = '7DAY';
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts where notice begins in less than 7 days'),
            'count'  => $contractpre7,
        ];

        $options['criteria'][0]['value'] = '6DAY';
        $options['criteria'][1]['value'] = '1MONTH';
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts where notice begins in less than 30 days'),
            'count'  => $contractpre30,
        ];

        $output = TemplateRenderer::getInstance()->render('central/lists/itemtype_count.html.twig', $twig_params);
        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * Get the supplier name(s) for the contract
     *
     * @return string The name(s) of the suppliers for this contract (HTML content)
     **/
    public function getSuppliersNames()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'       => 'glpi_suppliers.id',
            'FROM'         => 'glpi_suppliers',
            'INNER JOIN'   => [
                'glpi_contracts_suppliers' => [
                    'ON' => [
                        'glpi_contracts_suppliers' => 'suppliers_id',
                        'glpi_suppliers'           => 'id',
                    ],
                ],
            ],
            'WHERE'        => ['contracts_id' => $this->fields['id']],
        ]);
        $out    = "";
        foreach ($iterator as $data) {
            $out .= htmlescape(Dropdown::getDropdownName("glpi_suppliers", $data['id'])) . "<br>";
        }
        return $out;
    }

    public static function cronInfo($name)
    {
        return ['description' => __('Send alarms on contracts')];
    }

    /**
     * Cron action on contracts : alert depending of the config : on notice and expire
     *
     * @param CronTask|null $task CronTask for log, if NULL display (default NULL)
     *
     * @return integer
     * @used-by CronTask
     **/
    public static function cronContract(?CronTask $task = null)
    {
        global $CFG_GLPI, $DB;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0;
        }

        $message       = [];
        $cron_status   = 0;

        $contract_infos    = [
            Alert::END    => [],
            Alert::NOTICE => [],
        ];
        $contract_messages = [];

        $end_date = QueryFunction::dateAdd(
            date: 'glpi_contracts.begin_date',
            interval: new QueryExpression($DB::quoteName('glpi_contracts.duration')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_now = QueryFunction::dateDiff(
            expression1: $end_date,
            expression2: QueryFunction::curDate()
        );

        $notice_date = QueryFunction::dateAdd(
            date: 'glpi_contracts.begin_date',
            interval: new QueryExpression($DB::quoteName('glpi_contracts.duration') . ' - ' . $DB::quoteName('glpi_contracts.notice')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_notice = QueryFunction::dateDiff(
            expression1: $notice_date,
            expression2: QueryFunction::curDate()
        );

        foreach (Entity::getEntitiesToNotify('use_contracts_alert') as $entity => $value) {
            $before       = Entity::getUsedConfig('send_contracts_alert_before_delay', $entity);

            $query_notice = [
                'SELECT'    => [
                    'glpi_contracts.*',
                ],
                'FROM'      => self::getTable(),
                'LEFT JOIN' => [
                    'glpi_alerts' => [
                        'FKEY' => [
                            'glpi_alerts'    => 'items_id',
                            'glpi_contracts' => 'id',
                            [
                                'AND' => [
                                    'glpi_alerts.itemtype' => 'Contract',
                                    'glpi_alerts.type'     => Alert::NOTICE,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    [
                        'RAW' => [
                            DBmysql::quoteName('glpi_contracts.alert') . ' & ' . 2 ** Alert::NOTICE => ['>', 0],
                        ],
                    ],
                    'glpi_alerts.date'           => null,
                    'glpi_contracts.is_deleted'  => 0,
                    [
                        'NOT' => ['glpi_contracts.begin_date' => null],
                    ],
                    'glpi_contracts.duration'    => ['!=', 0],
                    'glpi_contracts.notice'      => ['!=', 0],
                    'glpi_contracts.entities_id' => $entity,
                    new QueryExpression("$end_date_diff_now > 0"),
                    new QueryExpression("$end_date_diff_notice <= $before"),
                ],
            ];

            $query_end = [
                'SELECT'    => [
                    'glpi_contracts.*',
                ],
                'FROM'      => self::getTable(),
                'LEFT JOIN' => [
                    'glpi_alerts' => [
                        'FKEY' => [
                            'glpi_alerts'    => 'items_id',
                            'glpi_contracts' => 'id',
                            [
                                'AND' => [
                                    'glpi_alerts.itemtype' => 'Contract',
                                    'glpi_alerts.type'     => Alert::END,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    [
                        'RAW' => [
                            DBmysql::quoteName('glpi_contracts.alert') . ' & ' . 2 ** Alert::END => ['>', 0],
                        ],
                    ],
                    'glpi_alerts.date'           => null,
                    'glpi_contracts.is_deleted'  => 0,
                    [
                        'NOT' => ['glpi_contracts.begin_date' => null],
                    ],
                    'glpi_contracts.duration'    => ['!=', 0],
                    'glpi_contracts.entities_id' => $entity,
                    new QueryExpression("$end_date_diff_now <= $before"),
                ],
            ];

            $querys = ['notice' => $query_notice,
                'end'    => $query_end,
            ];

            foreach ($querys as $type => $query) {
                $result = $DB->request($query);
                foreach ($result as $data) {
                    $entity  = $data['entities_id'];

                    $message = sprintf(
                        __('%1$s: %2$s'),
                        $data["name"],
                        Infocom::getWarrantyExpir(
                            $data["begin_date"],
                            $data["duration"],
                            $data["notice"]
                        )
                    );
                    $data['items']      = Contract_Item::getItemsForContract($data['id'], $entity);
                    $contract_infos[$type][$entity][$data['id']] = $data;

                    if (!isset($contract_messages[$type][$entity])) {
                        switch ($type) {
                            case 'notice':
                                $contract_messages[$type][$entity][] = __('Contract entered in notice time');
                                break;

                            case 'end':
                                $contract_messages[$type][$entity][] = __('Contract ended');
                                break;
                        }
                    }
                    $contract_messages[$type][$entity][] = $message;
                }
            }

            // Get contrats with periodicity alerts
            $valPow = 2 ** Alert::PERIODICITY;
            $query_periodicity = ['FROM' => 'glpi_contracts',
                'WHERE' => ['alert' => ['&', $valPow],
                    'entities_id' => $entity,
                    'is_deleted' => 0,
                ],
            ];

            // Foreach ones :
            foreach ($DB->request($query_periodicity) as $data) {
                $entity = $data['entities_id'];

                // For contracts with begin date and periodicity
                if (!empty($data['begin_date']) && $data['periodicity']) {
                    $todo = ['periodicity' => Alert::PERIODICITY];
                    if ($data['alert'] & 2 ** Alert::NOTICE) {
                        $todo['periodicitynotice'] = Alert::NOTICE;
                    }

                    // For the todo...
                    foreach ($todo as $type => $event) {
                        /**
                         * Previous alert
                         */
                        // Get previous alerts from DB
                        $previous_alert = [
                            $type => Alert::getAlertDate(self::class, $data['id'], $event),
                        ];
                        // If alert never occurs...
                        if (empty($previous_alert[$type])) {
                            // We define it a long time ago [in a galaxy far, far away... ;-)]
                            $previous_alert[$type] = date('Y-m-d', 0);
                        }

                        /**
                         * Next alert
                         */
                        // Computation of first alert : Contract [begin date + initial duration] - Config [alert xxx days before]
                        $initial_duration = $data['duration'] != 0 ? $data['duration'] : $data['periodicity'];
                        $next_alert = [
                            $type => date('Y-m-d', strtotime($data['begin_date'] . " +" . $initial_duration . " month -" . ($before) . " day")),
                        ];
                        // If a notice is defined
                        if ($event == Alert::NOTICE) {
                            // Will decrease of the Contract notice duration
                            $next_alert[$type] = date('Y-m-d', strtotime($next_alert[$type] . " -" . ($data['notice']) . " month"));
                        }

                        // Computation of contract renewal
                        while ($next_alert[$type] < $previous_alert[$type]) {
                            // Increasing of Contract periodicity...
                            $next_alert[$type] = date('Y-m-d', strtotime($next_alert[$type] . " +" . ($data['periodicity']) . " month"));
                        }

                        // If this date is passed : clean alerts and send again
                        if ($next_alert[$type] <= date('Y-m-d')) {
                            $alert = new Alert();
                            $alert->clear(self::class, $data['id'], $event);
                            // Computation of the real date => add Config [alert xxx days before]
                            $real_alert_date = date('Y-m-d', strtotime($next_alert[$type] . " +" . ($before) . " day"));
                            $message = sprintf(__('%1$s: %2$s'), $data["name"], Html::convDate($real_alert_date));
                            $data['alert_date'] = $real_alert_date;
                            $contract_infos[$type][$entity][$data['id']] = $data;

                            switch ($type) {
                                case 'periodicitynotice':
                                    $contract_messages[$type][$entity][] = __('Contract entered in notice time for period');
                                    break;

                                case 'periodicity':
                                    $contract_messages[$type][$entity][] = __('Contract period ended');
                                    break;
                            }
                            $contract_messages[$type][$entity][] = $message;
                        }
                    }
                }
            }
        }
        foreach (
            [
                'notice'            => Alert::NOTICE,
                'end'               => Alert::END,
                'periodicity'       => Alert::PERIODICITY,
                'periodicitynotice' => Alert::NOTICE,
            ] as $event => $type
        ) {
            if (isset($contract_infos[$event]) && count($contract_infos[$event])) {
                foreach ($contract_infos[$event] as $entity => $contracts) {
                    if (
                        NotificationEvent::raiseEvent(
                            $event,
                            new self(),
                            ['entities_id' => $entity,
                                'items'       => $contracts,
                            ]
                        )
                    ) {
                        $messages    = $contract_messages[$event][$entity];
                        $cron_status = 1;
                        $entityname  = Dropdown::getDropdownName("glpi_entities", $entity);
                        if ($task) {
                            $task->log(sprintf(__('%1$s: %2$s') . "\n", $entityname, implode("\n", $messages)));
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect(sprintf(
                                __s('%1$s: %2$s'),
                                htmlescape($entityname),
                                implode('<br>', array_map('htmlescape', $messages))
                            ));
                        }

                        $alert = new Alert();
                        $input = [
                            'itemtype' => self::class,
                            'type'     => $type,
                        ];
                        foreach (array_keys($contracts) as $id) {
                            $input["items_id"] = $id;

                            $alert->add($input);
                            unset($alert->fields['id']);
                        }
                    } else {
                        $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
                        //TRANS: %1$s is entity name, %2$s is the message
                        $msg = sprintf(__('%1$s: %2$s'), $entityname, __('send contract alert failed'));
                        if ($task) {
                            $task->log($msg);
                        } else {
                            Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                        }
                    }
                }
            }
        }

        return $cron_status;
    }

    /**
     * Print a select with contracts
     *
     * Print a select named $name with contracts options and selected value $value
     * @param array $options
     *    - name          : string / name of the select (default is contracts_id)
     *    - value         : integer / preselected value (default 0)
     *    - entity        : integer or array / restrict to a defined entity or array of entities
     *                      (default -1 : no restriction)
     *    - rand          : (defauolt mt_rand)
     *    - entity_sons   : boolean / if entity restrict specified auto select its sons
     *                      only available if entity is a single value not an array (default false)
     *    - used          : array / Already used items ID: not to display in dropdown (default empty)
     *    - nochecklimit  : boolean / disable limit for nomber of device (for supplier, default false)
     *    - on_change     : string / value to transmit to "onChange"
     *    - display       : boolean / display or return string (default true)
     *    - expired       : boolean / display expired contract (default false)
     *    - toadd         : array / array of specific values to add at the beginning
     *    - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *
     * @return string|integer HTML output, or random part of dropdown ID.
     **/
    public static function dropdown($options = [])
    {
        global $DB;

        //$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
        $p = [
            'name'           => 'contracts_id',
            'value'          => '',
            'entity'         => '',
            'rand'           => mt_rand(),
            'entity_sons'    => false,
            'used'           => [],
            'nochecklimit'   => false,
            'on_change'      => '',
            'display'        => true,
            'expired'        => false,
            'toadd'          => [],
            'class'          => "form-select",
            'width'          => "",
            'hide_if_no_elements' => false,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if (
            $p['entity'] >= 0
            && $p['entity_sons']
        ) {
            if (is_array($p['entity'])) {
                trigger_error('entity_sons options is not available with array of entity', E_USER_WARNING);
            } else {
                $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
            }
        }

        $WHERE = [];
        if ($p['entity'] >= 0) {
            $WHERE += getEntitiesRestrictCriteria('glpi_contracts', 'entities_id', $p['entity'], true);
        }
        if (count($p['used'])) {
            $WHERE['NOT'] = ['glpi_contracts.id' => $p['used']];
        }
        if (!$p['expired']) {
            $WHERE[] = self::getNotExpiredCriteria();
        }

        $iterator = $DB->request([
            'SELECT'    => 'glpi_contracts.*',
            'FROM'      => 'glpi_contracts',
            'LEFT JOIN' => [
                'glpi_entities'   => [
                    'ON' => [
                        'glpi_contracts'  => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => array_merge([
                'glpi_contracts.is_deleted'   => 0,
                'glpi_contracts.is_template'  => 0,
            ], $WHERE),
            'ORDERBY'   => [
                'glpi_entities.completename',
                'glpi_contracts.name ASC',
                'glpi_contracts.begin_date DESC',
            ],
        ]);

        if ($p['hide_if_no_elements'] && $iterator->count() === 0) {
            return '';
        }

        $group  = '';
        $prev   = -1;
        $values = $p['toadd'];
        foreach ($iterator as $data) {
            if (
                $p['nochecklimit']
                || ($data["max_links_allowed"] == 0)
                || ($data["max_links_allowed"] > countElementsInTable(
                    'glpi_contracts_items',
                    ['contracts_id' => $data['id']]
                ))
            ) {
                if ($data["entities_id"] != $prev) {
                    $group = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
                    $prev = $data["entities_id"];
                }

                $name = $data["name"];
                if (
                    $_SESSION["glpiis_ids_visible"]
                    || empty($data["name"])
                ) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                }

                $tmp = sprintf(__('%1$s - %2$s'), $name, $data["num"]);
                $tmp = sprintf(__('%1$s - %2$s'), $tmp, Html::convDateTime($data["begin_date"]));
                $values[$group][$data['id']] = $tmp;
            }
        }
        return Dropdown::showFromArray($p['name'], $values, [
            'value'               => $p['value'],
            'on_change'           => $p['on_change'],
            'display'             => $p['display'],
            'display_emptychoice' => true,
            'class'               => $p['class'],
            'width'               => $p['width'],
        ]);
    }

    /**
     * Print a select with contract renewal
     *
     * Print a select named $name with contract renewal options and selected value $value
     *
     * @param string  $name    HTML select name
     * @param integer $value   HTML select selected value (default = 0)
     * @param boolean $display get or display string ? (true by default)
     *
     * @return string|integer HTML output, or random part of dropdown ID.
     **/
    public static function dropdownContractRenewal($name, $value = 0, $display = true)
    {
        $values = [
            self::RENEWAL_NEVER => __('Never'),
            self::RENEWAL_TACIT => __('Tacit'),
            self::RENEWAL_EXPRESS => __('Express'),
        ];
        return Dropdown::showFromArray($name, $values, ['value'   => $value,
            'display' => $display,
        ]);
    }

    /**
     * Get the renewal type name
     *
     * @param integer $value HTML select selected value
     *
     * @return string
     **/
    public static function getContractRenewalName(int $value): string
    {
        return match ($value) {
            0 => __('Never'),
            1 => __('Tacit'),
            2 => __('Express'),
            default => "",
        };
    }

    /**
     * @param array $options
     *
     * @return string|integer HTML output, or random part of dropdown ID.
     **/
    public static function dropdownAlert(array $options)
    {
        $p = array_replace([
            'name'           => 'alert',
            'value'          => 0,
            'display'        => true,
            'inherit_parent' => false,
        ], $options);

        $tab = [];
        if ($p['inherit_parent']) {
            $tab[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        $tab += self::getAlertName();

        return Dropdown::showFromArray($p['name'], $tab, $p);
    }

    /**
     * Get the possible value for contract alert
     *
     * @since 0.83
     *
     * @param string|integer|null $val if not set, ask for all values, else for 1 value (default NULL)
     *
     * @return string|string[]
     **/
    public static function getAlertName($val = null)
    {
        $names = [
            0                                                  => Dropdown::EMPTY_VALUE,
            2 ** Alert::END                                     => __('End'),
            2 ** Alert::NOTICE                                  => __('Notice'),
            (2 ** Alert::END) + (2 ** Alert::NOTICE)            => __('End + Notice'),
            2 ** Alert::PERIODICITY                             => __('Period end'),
            (2 ** Alert::PERIODICITY) + (2 ** Alert::NOTICE)    => __('Period end + Notice'),
        ];

        if (is_null($val)) {
            return $names;
        }
        // Default value for display
        $names[0] = __('None');

        if (isset($names[$val])) {
            return $names[$val];
        }
        // If not set and is a string return value
        if (is_string($val)) {
            return $val;
        }
        return NOT_AVAILABLE;
    }

    public function getUnallowedFieldsForUnicity()
    {
        return array_merge(
            parent::getUnallowedFieldsForUnicity(),
            [
                'begin_date', 'duration', 'entities_id', 'sunday_begin_hour',
                'sunday_end_hour', 'saturday_begin_hour', 'saturday_end_hour',
                'week_begin_hour',
                'week_end_hour',
            ]
        );
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {
        global $CFG_GLPI;

        if (in_array($itemtype, $CFG_GLPI["contract_types"], true)) {
            if (self::canUpdate()) {
                $action_prefix                    = 'Contract_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;
                $actions[$action_prefix . 'add']    = "<i class='" . htmlescape(self::getIcon()) . "'></i>"
                                                . _sx('button', 'Add a contract');
                $actions[$action_prefix . 'remove'] = _sx('button', 'Remove a contract');
            }
        }
    }

    public static function getIcon()
    {
        return "ti ti-writing-sign";
    }

    public static function getNotExpiredCriteria()
    {
        global $DB;
        return [
            'OR' => [
                'glpi_contracts.renewal' => 1,
                new QueryExpression(
                    QueryFunction::dateDiff(
                        expression1: QueryFunction::dateAdd(
                            date: 'glpi_contracts.begin_date',
                            interval: new QueryExpression($DB::quoteName('glpi_contracts.duration')),
                            interval_unit: 'MONTH'
                        ),
                        expression2: QueryFunction::curDate()
                    ) . ' > 0'
                ),
            ],
        ];
    }
}
