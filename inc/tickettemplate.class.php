<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Ticket Template class
 *
 * since version 0.83
**/
class TicketTemplate extends ITILTemplate {
   use Glpi\Features\Clonable;

   public $second_level_menu         = "ticket";
   public $third_level_menu          = "TicketTemplate";

   static function getTypeName($nb = 0) {
      return _n('Ticket template', 'Ticket templates', $nb);
   }

   public function getCloneRelations() :array {
      return [
         TicketTemplateHiddenField::class,
         TicketTemplateMandatoryField::class,
         TicketTemplatePredefinedField::class,
      ];
   }

   public static function getExtraAllowedFields($withtypeandcategory = 0, $withitemtype = 0) {
      $itil_object = new Ticket();
      $tab =  [
         $itil_object->getSearchOptionIDByField('field', 'name',
                                                       'glpi_requesttypes')
                                                       => 'requesttypes_id',
         $itil_object->getSearchOptionIDByField('field', 'completename',
                                             'glpi_locations') => 'locations_id',
         $itil_object->getSearchOptionIDByField('field', 'slas_id_tto',
                                             'glpi_slas')      => 'slas_id_tto',
         $itil_object->getSearchOptionIDByField('field', 'slas_id_ttr',
                                             'glpi_slas')      => 'slas_id_ttr',
         $itil_object->getSearchOptionIDByField('field', 'olas_id_tto',
                                             'glpi_olas')      => 'olas_id_tto',
         $itil_object->getSearchOptionIDByField('field', 'olas_id_ttr',
                                             'glpi_olas')      => 'olas_id_ttr',
         $itil_object->getSearchOptionIDByField('field', 'time_to_resolve',
                                             'glpi_tickets')   => 'time_to_resolve',
         $itil_object->getSearchOptionIDByField('field', 'time_to_own',
                                             'glpi_tickets')   => 'time_to_own',
         $itil_object->getSearchOptionIDByField('field', 'internal_time_to_resolve',
                                             'glpi_tickets')   => 'internal_time_to_resolve',
         $itil_object->getSearchOptionIDByField('field', 'internal_time_to_own',
                                             'glpi_tickets')   => 'internal_time_to_own',
         $itil_object->getSearchOptionIDByField('field', 'actiontime',
                                             'glpi_tickets')   => 'actiontime',
         $itil_object->getSearchOptionIDByField('field', 'global_validation',
                                             'glpi_tickets')   => 'global_validation',

      ];

      if ($withtypeandcategory) {
         $tab[$itil_object->getSearchOptionIDByField('field', 'type',
                                                $itil_object->getTable())]         = 'type';
      }

      return $tab;
   }


   /**
    * Retrieve an item from the database with additional datas
    *
    * @since 0.83
    * @deprecated 9.5.0
    *
    * @param $ID                    integer  ID of the item to get
    * @param $withtypeandcategory   boolean  with type and category (true by default)
    *
    * @return true if succeed else false
   **/
   function getFromDBWithDatas($ID, $withtypeandcategory = true) {
      Toolbox::deprecated('Use getFromDBWithData');
      return $this->getFromDBWithData($ID, $withtypeandcategory);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item instanceof ITILTemplate) {
         switch ($tabnum) {
            case 1 :
               $item->showCentralPreview($item);
               return true;

            case 2 :
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
   static function showHelpdeskPreview(ITILTemplate $tt) {

      if (!$tt->getID()) {
         return false;
      }
      if ($tt->getFromDBWithData($tt->getID())) {
         $ticket = new  Ticket();
         $ticket->showFormHelpdesk(Session::getLoginUserID(), $tt->getID());
      }
   }

}
