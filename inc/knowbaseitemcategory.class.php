<?php
/*
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class KnowbaseItemCategory
class KnowbaseItemCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'knowbasecategory';



   static function getTypeName($nb=0) {
      return _n('Knowledge base category', 'Knowledge base categories', $nb);
   }


   /**
    * Show KB categories
    *
    * @param $options   $_GET
    *
    * @return nothing (display the form)
   **/
   static function showFirstLevel($options) {
      global $DB, $CFG_GLPI;

      $faq = !Session::haveRight("knowbase", READ);

      // Default values of parameters
      $params["knowbaseitemcategories_id"] = "0";
      $params["target"]                    = KnowbaseItem::getSearchURL();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key]=$val;
         }
      }

      $faq_limit = '';

      $parameters = '';
      // Manage search solution
      if (isset($options['item_itemtype'])
            && isset($options['item_items_id'])) {
         $parameters = "&amp;item_items_id=".$options['item_items_id']."&amp;item_itemtype=".
                           $options['item_itemtype'];
      }

      if ($faq) {
         if (!$CFG_GLPI["use_public_faq"]
             && !Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
            return false;
         }

         if (Session::getLoginUserID()) {
            $faq_limit = getEntitiesRestrictRequest("AND", "glpi_knowbaseitemcategories", "", "",
                                                    true);
         } else {
            // Anonymous access
            if (Session::isMultiEntitiesMode()) {
               $faq_limit = " AND (`glpi_knowbaseitemcategories`.`entities_id` = '0'
                                   AND `glpi_knowbaseitemcategories`.`is_recursive` = '1')";
            }
         }

         // Get All FAQ categories
         if (!isset($_SESSION['glpi_faqcategories'])) {

            $_SESSION['glpi_faqcategories'] = '(0)';
            $tmp   = array();
            $query = "SELECT DISTINCT `glpi_knowbaseitems`.`knowbaseitemcategories_id`
                      FROM `glpi_knowbaseitems`
                      ".KnowbaseItem::addVisibilityJoins()."
                      LEFT JOIN `glpi_knowbaseitemcategories`
                           ON (`glpi_knowbaseitemcategories`.`id`
                                 = `glpi_knowbaseitems`.`knowbaseitemcategories_id`)
                      WHERE `glpi_knowbaseitems`.`is_faq` = '1'
                            AND ".KnowbaseItem::addVisibilityRestrict()."
                            $faq_limit";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)) {
                  while ($data = $DB->fetch_assoc($result)) {
                     if (!in_array($data['knowbaseitemcategories_id'], $tmp)) {
                        $tmp[] = $data['knowbaseitemcategories_id'];
                        $tmp   = array_merge($tmp,
                                             getAncestorsOf('glpi_knowbaseitemcategories',
                                                            $data['knowbaseitemcategories_id']));
                     }
                  }
               }
               if (count($tmp)) {
                  $_SESSION['glpi_faqcategories'] = "('".implode("','",$tmp)."')";
               }
            }
         }
         $query = "SELECT DISTINCT `glpi_knowbaseitemcategories`.*
                   FROM `glpi_knowbaseitemcategories`
                   WHERE `glpi_knowbaseitemcategories`.`id` IN ".$_SESSION['glpi_faqcategories']."
                         AND (`glpi_knowbaseitemcategories`.`knowbaseitemcategories_id`
                                 = '".$params["knowbaseitemcategories_id"]."')
                         $faq_limit
                   ORDER BY `name` ASC";

      } else {
         if (!Session::haveRight("knowbase", READ)) {
            return false;
         }
         $faq_limit = getEntitiesRestrictRequest("AND", "glpi_knowbaseitemcategories", "entities_id",
                                                 $_SESSION['glpiactiveentities'], true);

         $query = "SELECT *
                   FROM `glpi_knowbaseitemcategories`
                   WHERE `glpi_knowbaseitemcategories`.`knowbaseitemcategories_id`
                              = '".$params["knowbaseitemcategories_id"]."'
                         $faq_limit
                   ORDER BY `name` ASC";
      }

      // Show category
      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_central'>";
         echo "<tr><td colspan='3'><a href='".$params['target']."?knowbaseitemcategories_id=0$parameters'>";
         echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/folder-open.png' class='bottom'></a>";

         // Display Category
         if ($params["knowbaseitemcategories_id"]!=0) {
            $tmpID     = $params["knowbaseitemcategories_id"];
            $todisplay = "";

            while ($tmpID != 0) {
               $query2 = "SELECT *
                          FROM `glpi_knowbaseitemcategories`
                          WHERE `glpi_knowbaseitemcategories`.`id` = '$tmpID'
                                $faq_limit";
               $result2 = $DB->query($query2);

               if ($DB->numrows($result2) == 1) {
                  $data      = $DB->fetch_assoc($result2);
                  $tmpID     = $data["knowbaseitemcategories_id"];
                  $todisplay = "<a href='".$params['target']."?knowbaseitemcategories_id=".
                                 $data["id"]."$parameters'>".$data["name"]."</a>".
                                 (empty($todisplay)?"":" > "). $todisplay;
               } else {
                  $tmpID = 0;
               }
            }
            echo " > ".$todisplay;
         }

         if ($DB->numrows($result) > 0) {
            $i = 0;
            while ($row=$DB->fetch_assoc($result)) {
               // on affiche les résultats sur trois colonnes
               if (($i%3) == 0) {
                  echo "<tr>";
               }
               $ID = $row["id"];
               echo "<td class='tdkb_result'>";
               echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/folder.png' hspace='5'>";
               echo "<span class='b'>".
                    "<a href='".$params['target']."?knowbaseitemcategories_id=".$row["id"]."$parameters'>".
                      $row["name"]."</a></span>";
               echo "<div class='kb_resume'>".Html::resume_text($row['comment'],60)."</div>";

               if (($i%3) == 2) {
                  echo "</tr>";
               }
               $i++;
            }
         }
         echo "<tr><td colspan='3'>&nbsp;</td></tr></table><br>";
      }
   }

}
