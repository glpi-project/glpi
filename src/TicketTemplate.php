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

/**
 * Ticket Template class
 *
 * since version 0.83
 **/
class TicketTemplate extends ITILTemplate
{
    use Glpi\Features\Clonable;

    public $second_level_menu         = "ticket";
    public $third_level_menu          = "TicketTemplate";

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket template', 'Ticket templates', $nb);
    }

    public function getCloneRelations(): array
    {
        return [
            TicketTemplateHiddenField::class,
            TicketTemplateMandatoryField::class,
            TicketTemplatePredefinedField::class,
        ];
    }

    public static function getExtraAllowedFields($withtypeandcategory = 0, $withitemtype = 0)
    {
        $itil_object = new Ticket();
        $tab =  [
            $itil_object->getSearchOptionIDByField(
                'field',
                'name',
                'glpi_requesttypes'
            )
                                                       => 'requesttypes_id',
            $itil_object->getSearchOptionIDByField(
                'field',
                'completename',
                'glpi_locations'
            ) => 'locations_id',
            $itil_object->getSearchOptionIDByField(
                'field',
                'slas_id_tto',
                'glpi_slas'
            )      => 'slas_id_tto',
            $itil_object->getSearchOptionIDByField(
                'field',
                'slas_id_ttr',
                'glpi_slas'
            )      => 'slas_id_ttr',
            $itil_object->getSearchOptionIDByField(
                'field',
                'olas_id_tto',
                'glpi_olas'
            )      => 'olas_id_tto',
            $itil_object->getSearchOptionIDByField(
                'field',
                'olas_id_ttr',
                'glpi_olas'
            )      => 'olas_id_ttr',
            $itil_object->getSearchOptionIDByField(
                'field',
                'time_to_resolve',
                'glpi_tickets'
            )   => 'time_to_resolve',
            $itil_object->getSearchOptionIDByField(
                'field',
                'time_to_own',
                'glpi_tickets'
            )   => 'time_to_own',
            $itil_object->getSearchOptionIDByField(
                'field',
                'internal_time_to_resolve',
                'glpi_tickets'
            )   => 'internal_time_to_resolve',
            $itil_object->getSearchOptionIDByField(
                'field',
                'internal_time_to_own',
                'glpi_tickets'
            )   => 'internal_time_to_own',
            $itil_object->getSearchOptionIDByField(
                'field',
                'actiontime',
                'glpi_tickets'
            )   => 'actiontime',
            $itil_object->getSearchOptionIDByField(
                'field',
                'global_validation',
                'glpi_tickets'
            )   => 'global_validation',
            $itil_object->getSearchOptionIDByField(
                'field',
                'name',
                'glpi_contracts'
            )   => '_contracts_id',

        ];

        if ($withtypeandcategory) {
            $tab[$itil_object->getSearchOptionIDByField(
                'field',
                'type',
                $itil_object->getTable()
            )]         = 'type';
        }

        return $tab;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof ITILTemplate) {
            switch ($tabnum) {
                case 1:
                    $item->showCentralPreview($item);
                    return true;

                case 2:
                    $item->showHelpdeskPreview($item);
                    return true;
            }
        }
        return false;
    }


    /**
     * Print preview for Ticket template
     *
     * @param $tt ITILTemplate object
     *
     * @return void
     **/
    public static function showHelpdeskPreview(ITILTemplate $tt)
    {

        if (!$tt->getID()) {
            return false;
        }
        if ($tt->getFromDBWithData($tt->getID())) {
            $ticket = new  Ticket();
            $ticket->showFormHelpdesk(Session::getLoginUserID(), $tt->getID());
        }
    }
}
