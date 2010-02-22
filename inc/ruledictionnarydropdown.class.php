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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

class RuleDictionnaryDropdown extends RuleCached {

   // From Rule
   public $right='rule_dictionnary_dropdown';
   public $can_sort=true;

   /**
    * Constructor
    * @param $type dropdown type
   **/
   function __construct($type) {
      parent::__construct($type);
   }

   function maxActionsCount() {
      return 1;
   }

   function getTitle() {
      global $LANG;

      switch ($this->sub_type) {
         case Rule::RULE_DICTIONNARY_MANUFACTURER :
            return $LANG['rulesengine'][36];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_COMPUTER :
            return $LANG['rulesengine'][50];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_COMPUTER :
            return $LANG['rulesengine'][60];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_MONITOR :
            return $LANG['rulesengine'][51];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_MONITOR :
            return $LANG['rulesengine'][61];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_PRINTER :
            return $LANG['rulesengine'][54];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_PRINTER :
            return $LANG['rulesengine'][64];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_PHONE :
            return $LANG['rulesengine'][52];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_PHONE :
            return $LANG['rulesengine'][62];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_PERIPHERAL :
            return $LANG['rulesengine'][53];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_PERIPHERAL :
            return $LANG['rulesengine'][63];
            break;

         case Rule::RULE_DICTIONNARY_MODEL_NETWORKING :
            return $LANG['rulesengine'][55];
            break;

         case Rule::RULE_DICTIONNARY_TYPE_NETWORKING :
            return $LANG['rulesengine'][65];
            break;

         case Rule::RULE_DICTIONNARY_OS :
            return $LANG['rulesengine'][67];
            break;

         case Rule::RULE_DICTIONNARY_OS_SP :
            return $LANG['rulesengine'][68];
            break;

         case Rule::RULE_DICTIONNARY_OS_VERSION :
            return $LANG['rulesengine'][69];
            break;
      }
   }

   function showCacheRuleHeader() {
      global $LANG;

      if (in_array($this->sub_type,array(Rule::RULE_DICTIONNARY_MODEL_COMPUTER,
                                         Rule::RULE_DICTIONNARY_MODEL_MONITOR,
                                         Rule::RULE_DICTIONNARY_MODEL_PRINTER,
                                         Rule::RULE_DICTIONNARY_MODEL_PHONE,
                                         Rule::RULE_DICTIONNARY_MODEL_PERIPHERAL,
                                         Rule::RULE_DICTIONNARY_MODEL_NETWORKING))) {
         echo "<tr><th colspan='3'>".$LANG['rulesengine'][100]."&nbsp;: ".$this->fields["name"]."</th></tr>";
         echo "<tr><td class='tab_bg_1 b'>".$LANG['rulesengine'][104]."</td>";
         echo "<td class='tab_bg_1 b'>".$LANG['common'][5]."</td>";
         echo "<td class='tab_bg_1 b'>".$LANG['rulesengine'][105]."</td></tr>";
      } else {
         parent::showCacheRuleHeader();
      }
   }

   function showCacheRuleDetail($fields) {
      global $LANG;

      if (in_array($this->sub_type,array(Rule::RULE_DICTIONNARY_MODEL_COMPUTER,
                                         Rule::RULE_DICTIONNARY_MODEL_MONITOR,
                                         Rule::RULE_DICTIONNARY_MODEL_PRINTER,
                                         Rule::RULE_DICTIONNARY_MODEL_PHONE,
                                         Rule::RULE_DICTIONNARY_MODEL_PERIPHERAL,
                                         Rule::RULE_DICTIONNARY_MODEL_NETWORKING))) {
         echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
         echo "<td class='tab_bg_2'>".($fields["manufacturer"]!=''?$fields["manufacturer"]:'')."</td>";
         echo "<td class='tab_bg_2'>".
                ($fields["new_value"]!=''?$fields["new_value"]:$LANG['rulesengine'][106])."</td>";
      } else {
         parent::showCacheRuleDetail($fields);
      }
   }

}

?>
