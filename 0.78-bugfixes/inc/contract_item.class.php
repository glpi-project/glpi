<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

// Relation between Contracts and Items
class Contract_Item extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Contract';
   public $items_id_1 = 'contracts_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Check right on an contract - overloaded to check max_links_allowed
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
         $contract = new Contract();

         if (!$contract->getFromDB($input['contracts_id'])) {
            return false;
         }
         if ($contract->fields['max_links_allowed'] > 0
             && countElementsInTable($this->getTable(),
                                     "`contracts_id`='".$input['contracts_id']."'") >=
                                          $contract->fields['max_links_allowed']) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }

   static function getTypeName() {
      global $LANG;
      return $LANG['setup'][620].'-'.$LANG['financial'][1];
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();

      $tab[2]['table']        = $this->getTable();
      $tab[2]['field']        = 'id';
      $tab[2]['linkfield']    = '';
      $tab[2]['name']         = $LANG['common'][2];

      $tab[3]['table']        = $this->getTable();
      $tab[3]['field']        = 'items_id';
      $tab[3]['linkfield']    = '';
      $tab[3]['name']         = $LANG['common'][2].' '.$LANG['financial'][104];

      $tab[4]['table']        = $this->getTable();
      $tab[4]['field']        = 'itemtype';
      $tab[4]['linkfield']    = '';
      $tab[4]['name']         = $LANG['common'][17];

      return $tab;
   }
}

?>