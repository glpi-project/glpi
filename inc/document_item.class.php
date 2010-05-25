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

// Relation between Documents and Items
class Document_Item extends CommonDBRelation{


   // From CommonDBRelation
   public $itemtype_1 = 'Document';
   public $items_id_1 = 'documents_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   function prepareInputForAdd($input) {

      if (empty($input['itemtype']) || empty($input['items_id']) || $input['items_id']==0
         || empty($input['documents_id']) || $input['documents_id']==0) {
         return false;
      }

      // Do not insert circular link for document
      if ($input['itemtype'] == 'Document' && $input['items_id']==$input['documents_id']) {
         return false;
      }
      // Avoid duplicate entry
      $restrict = "`documents_id` = '".$input['documents_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(),$restrict)>0) {
         return false;
      }
      return $input;
   }
}
?>
