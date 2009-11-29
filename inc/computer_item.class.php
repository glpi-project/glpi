<?php
/*
 * @version $Id: contract_item.class.php 9363 2009-11-26 21:02:42Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
class Computer_Item extends CommonDBRelation{

   // From CommonDBTM
   public $table = 'glpi_computers_items';
   public $type = COMPUTERITEM_TYPE;

   // From CommonDBRelation
   public $itemtype_1 = COMPUTER_TYPE;
   public $items_id_1 = 'computers_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Check right on an item - overloaded to is_global
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      if ($ID<0) {
         // Ajout
         $item = new CommonItem();

         if (!$item->getFromDB($input['itemtype'],$input['items_id'])) {
            return false;
         }
         if ($item->getField('is_global')==0
             && countElementsInTable($this->table,
                                     "`itemtype`='".$input['itemtype']."'
                                      AND `items_id`='".$input['items_id']."'") > 0) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }
}

?>