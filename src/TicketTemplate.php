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

use Glpi\Features\Clonable;

/**
 * Ticket Template class
 *
 * since version 0.83
 **/
class TicketTemplate extends ITILTemplate
{
    use Clonable;

    #[Override]
    public static function getPredefinedFields(): ITILTemplatePredefinedField
    {
        return new TicketTemplatePredefinedField();
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket template', 'Ticket templates', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', Ticket::class, self::class];
    }

    public function getCloneRelations(): array
    {
        return [
            TicketTemplateHiddenField::class,
            TicketTemplateMandatoryField::class,
            TicketTemplatePredefinedField::class,
            TicketTemplateReadonlyField::class,
        ];
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                TicketTemplateHiddenField::class,
                TicketTemplateMandatoryField::class,
                TicketTemplatePredefinedField::class,
            ]
        );
    }

    public static function getExtraAllowedFields($withtypeandcategory = false, $withitemtype = false)
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
                'global_validation',
                'glpi_tickets'
            )   => 'global_validation',
            $itil_object->getSearchOptionIDByField(
                'field',
                'name',
                'glpi_contracts'
            )   => '_contracts_id',
            $itil_object->getSearchOptionIDByField(
                'field',
                'type',
                'glpi_tickets'
            )   => 'type',

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
}
