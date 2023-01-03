<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * NotImportedEmail Class
 **/
class NotImportedEmail extends CommonDBTM
{
    public static $rightname = 'config';

    const MATCH_NO_RULE     = 0;
    const USER_UNKNOWN      = 1;
    const FAILED_OPERATION  = 2;
    const FAILED_INSERT     = self::FAILED_OPERATION;
    const NOT_ENOUGH_RIGHTS = 3;


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'delete';
        $forbidden[] = 'purge';
        $forbidden[] = 'restore';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Refused email', 'Refused emails', $nb);
    }


    /**
     * @see CommonDBTM::getSpecificMassiveActions()
     **/
    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $prefix                          = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'delete_email'] = __('Delete emails');
            $actions[$prefix . 'import_email'] = _x('button', 'Import');
        }
        return $actions;
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'import_email':
                Entity::dropdown();
                echo "<br><br>";
                echo Html::submit(_x('button', 'Import'), ['name' => 'massiveaction']);
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
            case 'delete_email':
            case 'import_email':
                if (!$item->canUpdate()) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                } else {
                    $input = $ma->getInput();
                    if (count($ids)) {
                        $mailcollector = new MailCollector();
                        if ($ma->getAction() == 'delete_email') {
                              $mailcollector->deleteOrImportSeveralEmails($ids, 0);
                        } else {
                             $mailcollector->deleteOrImportSeveralEmails($ids, 1, $input['entities_id']);
                        }
                    }
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'from',
            'name'               => __('From email header'),
            'massiveaction'      => false,
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'to',
            'name'               => __('To email header'),
            'massiveaction'      => false,
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'subject',
            'name'               => __('Subject email header'),
            'massiveaction'      => false,
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_mailcollectors',
            'field'              => 'name',
            'name'               => __('Mails receiver'),
            'datatype'           => 'itemlink'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'messageid',
            'name'               => __('Message-ID email header'),
            'massiveaction'      => false,
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => _n('Requester', 'Requesters', 1),
            'datatype'           => 'dropdown',
            'right'              => 'all'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'reason',
            'name'               => __('Reason of rejection'),
            'datatype'           => 'specific',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date',
            'name'               => _n('Date', 'Dates', 1),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        return $tab;
    }


    public static function deleteLog()
    {
        global $DB;

        $DB->truncate('glpi_notimportedemails');
    }


    /**
     * @param $reason_id
     **/
    public static function getReason($reason_id)
    {

        $tab = self::getAllReasons();
        if (isset($tab[$reason_id])) {
            return $tab[$reason_id];
        }
        return NOT_AVAILABLE;
    }


    /**
     * @since versin 0.84
     *
     * Get All possible reasons array
     **/
    public static function getAllReasons()
    {

        return [
            self::MATCH_NO_RULE     => __('Unable to affect the email to an entity'),
            self::USER_UNKNOWN      => __('Email not found. Impossible import'),
            self::FAILED_OPERATION  => __('Failed operation'),
            self::NOT_ENOUGH_RIGHTS => __('Not enough rights'),
        ];
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'reason':
                return self::getReason($values[$field]);

            case 'messageid':
                $clean = ['<' => '',
                    '>' => ''
                ];
                return strtr($values[$field], $clean);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'reason':
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, self::getAllReasons(), $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }
}
