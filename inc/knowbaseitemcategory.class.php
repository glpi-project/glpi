<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
   die("Sorry. You can't access this file directly");
}

/// Class KnowbaseItemCategory
class KnowbaseItemCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'knowbasecategory';



   static function getTypeName($nb = 0) {
      return _n('Knowledge base category', 'Knowledge base categories', $nb);
   }


   /**
    * Show KB categories
    *
    * @param $options   $_GET
    *
    * @return nothing (display the form)
    *
    * @deprecated 9.4.0
   **/
   static function showFirstLevel($options) {
      global $DB, $CFG_GLPI;

      Toolbox::deprecated();

      $faq = !Session::haveRight("knowbase", READ);

      // Default values of parameters
      $params["knowbaseitemcategories_id"] = "0";
      $params["target"]                    = KnowbaseItem::getSearchURL();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key]=$val;
         }
      }

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

         $where = [];
         if (Session::getLoginUserID()) {
            $where = getEntitiesRestrictCriteria('glpi_knowbaseitemcategories', '', '', true);
         } else {
            // Anonymous access
            if (Session::isMultiEntitiesMode()) {
               $where['glpi_knowbaseitemcategories.entities_id'] = 0;
               $where['glpi_knowbaseitemcategories.is_recursive'] = 1;
            }
         }

         // Get All FAQ categories
         $categories = [];

         $criteria = [
            'SELECT DISTINCT' => 'glpi_knowbaseitems.knowbaseitemcategories_id',
            'FROM'            => 'glpi_knowbaseitems',
            'LEFT JOIN'       => [
               'glpi_knowbaseitemcategories', [
                  'ON' => [
                     'glpi_knowbaseitemcategories' => 'id',
                     'glpi_knowbaseitems'          => 'knowbaseitemcategories_id'
                  ]
               ]
            ],
            'WHERE'           => $where + ['glpi_knowbaseitems.is_faq' => 1]
         ];
         $criteria = array_merge(
            $criteria,
            KnowbaseItem::getVisibilityCriteria()
         );

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            if (!in_array($data['knowbaseitemcategories_id'], $categories)) {
               $categories[] = $data['knowbaseitemcategories_id'];
               $categories = array_merge(
                  $categories,
                  getAncestorsOf(
                     'glpi_knowbaseitemcategories',
                     $data['knowbaseitemcategories_id']
                  )
               );
            }
         }

         $criteria = [
            'SELECT DISTINCT' => 'glpi_knowbaseitemcategories.*',
            'FROM'            => 'glpi_knowbaseitemcategories',
            'WHERE'           => [
               'id'                          => $categories,
               'knowbaseitemcategories_id'   => $params['knowbaseitemcategories_id']
            ] + $where,
            'ORDERBY'         => 'name ASC'
         ];
      } else {
         if (!Session::haveRight("knowbase", READ)) {
            return false;
         }
         $where = getEntitiesRestrictCriteria(
            'glpi_knowbaseitemcategories',
            'entities_id',
            $_SESSION['glpiactiveentities'],
            true
         );

         $criteria = [
            'FROM'   => 'glpi_knowbaseitemcategories',
            'WHERE'  => [
               'knowbaseitemcategories_id'   => $params['knowbaseitemcategories_id']
            ] + $where,
            'ORDER'  => 'name ASC'
         ];
      }
      $iterator = $DB->request($criteria);

      // Show category
      echo "<table class='tab_cadre_central'>";
      echo "<tr><td colspan='3'><a href='".$params['target']."?knowbaseitemcategories_id=0$parameters'>";
      echo "<i class='far fa-folder-open'></i> " . __('Root category')  . "</a>";

      // Display Category
      if ($params["knowbaseitemcategories_id"]!=0) {
         $tmpID     = $params["knowbaseitemcategories_id"];
         $todisplay = "";

         while ($tmpID != 0) {
            $cat_iterator = $DB->request([
               'FROM'   => 'glpi_knowbaseitemcategories',
               'WHERE'  => [
                  'knowbaseitemcategories_id'   => $tmpID
               ] + $where
            ]);

            if (count($cat_iterator) == 1) {
               $data      = $cat_iterator->next();
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

      if (count($iterator) > 0) {
         $i = 0;
         while ($row = $iterator->next()) {
            // on affiche les résultats sur trois colonnes
            if (($i%3) == 0) {
               echo "<tr>";
            }
            $ID = $row["id"];
            echo "<td class='tdkb_result'>";
            echo "<i class='far fa-folder'></i> ";
            echo "<span class='b'>".
                  "<a href='".$params['target']."?knowbaseitemcategories_id=".$row["id"]."$parameters'>".
                     $row["name"]."</a></span>";
            echo "<div class='kb_resume'>".Html::resume_text($row['comment'], 60)."</div>";

            if (($i%3) == 2) {
               echo "</tr>";
            }
            $i++;
         }
      }
      echo "<tr><td colspan='3'>&nbsp;</td></tr></table><br>";
   }

}
