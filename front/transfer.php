<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

$NEEDED_ITEMS=array("transfer","user","tracking","reservation","document","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","link","ocsng","consumable","cartridge","contract","enterprise","contact","group","profile","search","mailgate","typedoc","setup");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$transfer=new Transfer();

// Network links : 0 : delete 1 : keep disconnect 2 : keep connect
$options['keep_networklinks']=2;

// Tickets : 0 : delete 1 : keep and clean ref 2 : keep and move
$options['keep_tickets']=2;

// Reservations : 0 : delete 1 : keep
$options['keep_reservations']=1;

// Devices : 0 : delete 1 : keep
$options['keep_devices']=1;

// History : 0 : delete 1 : keep
$options['keep_history']=1;

// Infocoms : 0 : delete 1 : keep
$options['keep_infocoms']=1;

// Softwares : 0 : delete 1 : keep
$options['keep_softwares']=1;
$options['clean_softwares']=1;

// Contracts : 0 : delete 1 : keep
$options['keep_contracts']=1;
$options['clean_contracts']=1;

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

$entity_id=6;

$items[COMPUTER_TYPE]=array(1006);


$transfer->moveItems($items,$entity_id,$options);

?>
