<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Html::header(__('Transfer'), '', 'admin', 'rule', 'transfer');

$transfer = new Transfer();

$transfer->checkGlobal(READ);

if (isset($_POST['transfer'])) {
   if (isset($_SESSION['glpitransfer_list'])) {
      if (!Session::haveAccessToEntity($_POST['to_entity'])) {
         Html::displayRightError();
      }
      $transfer->moveItems($_SESSION['glpitransfer_list'], $_POST['to_entity'], $_POST);
      unset($_SESSION['glpitransfer_list']);
      echo "<div class='b center'>".__('Operation successful')."<br>";
      echo "<a href='central.php'>".__('Back')."</a></div>";
      Html::footer();
      exit();
   }
} else if (isset($_POST['clear'])) {
   unset($_SESSION['glpitransfer_list']);
   echo "<div class='b center'>".__('Operation successful')."<br>";
   echo "<a href='central.php'>".__('Back')."</a></div>";
   echo "</div>";
   Html::footer();
   exit();
}

unset($_SESSION['glpimassiveactionselected']);

$transfer->showTransferList();

Html::footer();
/*
// Network links : 0 : delete 1 : keep disconnect 2 : keep connect
$options['keep_networklink']=0;

// Tickets : 0 : delete 1 : keep and clean ref 2 : keep and move
$options['keep_ticket']=2;

// Reservations : 0 : delete 1 : keep
$options['keep_reservation']=1;

// Devices : 0 : delete 1 : keep
$options['keep_device']=1;

// History : 0 : delete 1 : keep
$options['keep_history']=1;

// Infocoms : 0 : delete 1 : keep
$options['keep_infocom']=1;

// enterprises : 0 : delete 1 : keep
$options['keep_supplier']=1;
$options['clean_supplier']=1;

// Contacts for enterprises : 0 : delete 1 : keep
$options['keep_contact']=1;
$options['clean_contact']=1;

// Softwares : 0 : delete 1 : keep
$options['keep_software']=1;
$options['clean_software']=1;

// Contracts : 0 : delete 1 : keep
$options['keep_contract']=1;
$options['clean_contract']=1;

// Documents : 0 : delete 1 : keep
$options['keep_document']=1;
$options['clean_document']=1;

// Monitor Direct Connect : keep_dc -> tranfer / clean_dc : delete if unused : 1 = delete, 2 = purge
$options['keep_dc_monitor']=1;
$options['clean_dc_monitor']=1;

// Phone Direct Connect : keep_dc -> tranfer / clean_dc : delete if unused : 1 = delete, 2 = purge
$options['keep_dc_phone']=1;
$options['clean_dc_phone']=1;

// Peripheral Direct Connect : keep_dc -> tranfer / clean_dc : delete if unused : 1 = delete, 2 = purge
$options['keep_dc_peripheral']=1;
$options['clean_dc_peripheral']=1;

// Printer Direct Connect : keep_dc -> tranfer / clean_dc : delete if unused : 1 = delete, 2 = purge
$options['keep_dc_printer']=1;
$options['clean_dc_printer']=1;

$options['keep_cartridgesitem']=1;
$options['clean_cartridgesitem']=1;
$options['keep_cartridge']=1;

//$options['keep_consumablesitem']=1; // Not needed
$options['keep_consumable']=1;

//$entity_id=4;
//$items['Computer']=array(403);
//$transfer->moveItems($items,$entity_id,$options);
*/
?>
