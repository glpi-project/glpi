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

define ("RULE_WILDCARD","*");
 // Get rules_option array

include_once (GLPI_ROOT."/inc/rules.constant.php");





function getRuleClass($type) {

   switch ($type) {
      case RULE_OCS_AFFECT_COMPUTER :
         return new RuleOcs();

      case RULE_AFFECT_RIGHTS :
         return new RuleRight();

      case RULE_TRACKING_AUTO_ACTION :
         return new RuleTicket();

      case RULE_SOFTWARE_CATEGORY :
         return new RuleSoftwareCategory();

      case RULE_DICTIONNARY_SOFTWARE :
         return new RuleDictionnarySoftware;

      case RULE_DICTIONNARY_MANUFACTURER :
      case RULE_DICTIONNARY_MODEL_NETWORKING :
      case RULE_DICTIONNARY_MODEL_COMPUTER :
      case RULE_DICTIONNARY_MODEL_MONITOR :
      case RULE_DICTIONNARY_MODEL_PRINTER :
      case RULE_DICTIONNARY_MODEL_PERIPHERAL :
      case RULE_DICTIONNARY_MODEL_PHONE :
      case RULE_DICTIONNARY_TYPE_NETWORKING :
      case RULE_DICTIONNARY_TYPE_COMPUTER :
      case RULE_DICTIONNARY_TYPE_PRINTER :
      case RULE_DICTIONNARY_TYPE_MONITOR :
      case RULE_DICTIONNARY_TYPE_PERIPHERAL :
      case RULE_DICTIONNARY_TYPE_PHONE :
      case RULE_DICTIONNARY_OS :
      case RULE_DICTIONNARY_OS_SP :
      case RULE_DICTIONNARY_OS_VERSION :
         return new RuleDictionnaryDropdown($type);
   }
}

function getRuleCollectionClass($type) {

   switch ($type) {
      case RULE_OCS_AFFECT_COMPUTER :
         return new RuleOcsCollection();

      case RULE_AFFECT_RIGHTS :
         return new RuleRightCollection();

      case RULE_TRACKING_AUTO_ACTION :
         return new RuleTicketCollection();

      case RULE_SOFTWARE_CATEGORY :
         return new RuleSoftwareCategoryCollection();

      case RULE_DICTIONNARY_SOFTWARE :
         return new RuleDictionnarySoftwareCollection;

      case RULE_DICTIONNARY_MANUFACTURER :
      case RULE_DICTIONNARY_MODEL_NETWORKING :
      case RULE_DICTIONNARY_MODEL_COMPUTER :
      case RULE_DICTIONNARY_MODEL_MONITOR :
      case RULE_DICTIONNARY_MODEL_PRINTER :
      case RULE_DICTIONNARY_MODEL_PERIPHERAL :
      case RULE_DICTIONNARY_MODEL_PHONE :
      case RULE_DICTIONNARY_TYPE_NETWORKING :
      case RULE_DICTIONNARY_TYPE_COMPUTER :
      case RULE_DICTIONNARY_TYPE_PRINTER :
      case RULE_DICTIONNARY_TYPE_MONITOR :
      case RULE_DICTIONNARY_TYPE_PERIPHERAL :
      case RULE_DICTIONNARY_TYPE_PHONE :
      case RULE_DICTIONNARY_OS :
      case RULE_DICTIONNARY_OS_SP :
      case RULE_DICTIONNARY_OS_VERSION :
         return new RuleDictionnaryDropdownCollection($type);
   }
}

function getRuleCollectionClassByTableName($tablename) {

   switch ($tablename) {
      case "glpi_softwares" :
         return getRuleCollectionClass(RULE_DICTIONNARY_SOFTWARE);

      case "glpi_manufacturers" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MANUFACTURER);

      case "glpi_computermodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_COMPUTER);

      case "glpi_monitormodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_MONITOR);

      case "glpi_printermodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PRINTER);

      case "glpi_peripheralmodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PERIPHERAL);

      case "glpi_networkequipmentmodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_NETWORKING);

      case "glpi_phonemodels" :
         return getRuleCollectionClass(RULE_DICTIONNARY_MODEL_PHONE);

      case "glpi_computertypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_COMPUTER);

      case "glpi_monitortypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_MONITOR);

      case "glpi_printertypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PRINTER);

      case "glpi_peripheraltypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PERIPHERAL);

      case "glpi_networkequipmenttypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_NETWORKING);

      case "glpi_phonetypes" :
         return getRuleCollectionClass(RULE_DICTIONNARY_TYPE_PHONE);

      case "glpi_operatingsystems" :
         return getRuleCollectionClass(RULE_DICTIONNARY_OS);

      case "glpi_operatingsystemservicepacks" :
         return getRuleCollectionClass(RULE_DICTIONNARY_OS_SP);

      case "glpi_operatingsystemversions" :
         return getRuleCollectionClass(RULE_DICTIONNARY_OS_VERSION);
   }
   return NULL;
}


?>
