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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

$supplier=new Supplier();

if (!isset($_POST["start"])) {
   $_POST["start"]=0;
}
if (!isset($_POST["sort"])) {
   $_POST["sort"]="";
}
if (!isset($_POST["order"])) {
   $_POST["order"]="";
}

if ($_POST["id"]>0 && $supplier->can($_POST["id"],'r')) {
   switch($_REQUEST['glpi_tab']) {
      case -1 :
         $supplier->showContacts();
         $supplier->showContracts();
         Document::showAssociated($supplier);
         Ticket::showListForSupplier($_POST["id"]);
         Link::showForItem('Supplier',$_POST["id"]);
         Plugin::displayAction($supplier, $_REQUEST['glpi_tab']);
         break;

      case 4 :
         $supplier->showContracts();
         break;

      case 5 :
         Document::showAssociated($supplier);
         break;

      case 6 :
         Ticket::showListForSupplier($_POST["id"]);
         break;

      case 7 :
         Link::showForItem('Supplier',$_POST["id"]);
         break;

      case 10 :
         showNotesForm($_POST['target'],'Supplier',$_POST["id"]);
         break;

      case 12 :
         Log::showForItem($supplier);
         break;

      case 15 :
         $supplier->showInfocoms();
         break;

      default :
         if (!Plugin::displayAction($supplier, $_REQUEST['glpi_tab'])) {
            $supplier->showContacts();
         }
   }
}

ajaxFooter();

?>
