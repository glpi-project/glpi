<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 *  Locked fields for inventory
 **/
class Lockedfield extends CommonDBTM
{
    /** @var CommonDBTM */
    private $item;

   // From CommonDBTM
    public $dohistory                   = false;

    public static $rightname                   = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Locked field', 'Locked fields', $nb);
    }

    public function rawSearchOptions()
    {
        $tab = [];

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
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => __('Last inventoried value'),
         //'datatype'           => '',
            'nosort'             => true,
            'nosearch'           => true,
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
                'jointype'           => 'child'
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
        return (bool)$item->isDynamic();
    }

    /**
     * Get locked fields
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * return array
     */
    public function getLocks($itemtype, $items_id)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id
            ]
        ]);

        $locks = [];
        foreach ($iterator as $row) {
            $locks[] = $row['field'];
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
                'items_id'  => $this->item->fields['id']
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
                'value'  => $value
            ],
            [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'field'     => $field
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
                if (isset($values['itemtype'])) {
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

    public static function canPurge()
    {
        return true;
    }
}
