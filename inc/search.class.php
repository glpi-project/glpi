<?php
/*
 * @version $Id$
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Generic class for Search Engine
class Search {

   /**
   * Display search engine for an type
   *
   * @param $itemtype item type to manage
   * @return nothing
   */
   static function show ($itemtype) {

      Search::manageGetValues($itemtype);
      searchForm($itemtype,$_GET);
      showList($itemtype,$_GET);
   }


   /**
   * Completion of the URL $_GET values with the $_SESSION values or define default values
   *
   * @param $itemtype item type to manage
   * @param $usesession Use datas save in session
   * @param $save Save params to session
   * @return nothing
   */
   static function manageGetValues($itemtype,$usesession=true,$save=true) {
      global $_GET,$DB;

      $tab=array();

      $default_values["start"]=0;
      $default_values["order"]="ASC";
      $default_values["is_deleted"]=0;
      $default_values["distinct"]="N";
      $default_values["link"]=array();
      $default_values["field"]=array(0=>"view");
      $default_values["contains"]=array(0=>"");
      $default_values["link2"]=array();
      $default_values["field2"]=array(0=>"view");
      $default_values["contains2"]=array(0=>"");
      $default_values["itemtype2"]="";
      $default_values["sort"]=1;

      // First view of the page : try to load a bookmark
      if ($usesession && !isset($_SESSION['glpisearch'][$itemtype])) {
         $query = "SELECT `bookmarks_id`
                  FROM `glpi_bookmarks_users`
                  WHERE `users_id`='".$_SESSION['glpiID']."'
                        AND `itemtype` = '$itemtype'";
         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)>0) {
               $IDtoload=$DB->result($result,0,0);
               // Set session variable
               $_SESSION['glpisearch'][$itemtype]=array();
               // Load bookmark on main window
               $bookmark=new Bookmark();
               $bookmark->load($IDtoload,false);
            }
         }
      }
      if ($usesession
         && (isset($_GET["reset_before"]) || (isset($_GET["reset"]) && $_GET["reset"]="reset_before"))) {

         if (isset($_SESSION['glpisearch'][$itemtype])) {
            unset($_SESSION['glpisearch'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount'][$itemtype])) {
            unset($_SESSION['glpisearchcount'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount2'][$itemtype])) {
            unset($_SESSION['glpisearchcount2'][$itemtype]);
         }
         // Bookmark use
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
         }
         // Bookmark use
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
         }
      }

      if (is_array($_GET) && $save) {
         foreach ($_GET as $key => $val) {
            $_SESSION['glpisearch'][$itemtype][$key]=$val;
         }
      }

      foreach ($default_values as $key => $val) {
         if (!isset($_GET[$key])) {
            if ($usesession && isset($_SESSION['glpisearch'][$itemtype][$key])) {
               $_GET[$key]=$_SESSION['glpisearch'][$itemtype][$key];
            } else {
               $_GET[$key] = $val;
               $_SESSION['glpisearch'][$itemtype][$key] = $val;
            }
         }
      }

      if (!isset($_SESSION["glpisearchcount"][$itemtype])) {
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
         } else {
            $_SESSION["glpisearchcount"][$itemtype]=1;
         }
      }
      if (!isset($_SESSION["glpisearchcount2"][$itemtype])) {
         // Set in URL for bookmark
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
         } else {
            $_SESSION["glpisearchcount2"][$itemtype]=0;
         }
      }
   }


   /**
   * Clean search options depending of user active profile
   *
   * @param $itemtype item type to manage
   * @param $action action which is used to manupulate searchoption (r/w)
   * @return clean $SEARCH_OPTION array
   */
   static function getCleanedOptions($itemtype,$action='r') {
      global $CFG_GLPI;

      $options=&Search::getOptions($itemtype);
      $todel=array();
      if (!haveRight('infocom',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
         $todel=array_merge($todel,array('financial',
                                       25,26,27,28,37,38,50,51,52,53,54,55,56,57,58,59,120,122));
      }

      if (!haveRight('contract',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
         $todel=array_merge($todel,array('financial',
                                       29,30,130,131,132,133,134,135,136,137,138));
      }

      if ($itemtype==COMPUTER_TYPE) {
         if (!haveRight('networking',$action)) {
            $todel=array_merge($todel,array('network',
                                          20,21,22,83,84,85));
         }
         if (!$CFG_GLPI['use_ocs_mode'] || !haveRight('view_ocsng',$action)) {
            $todel=array_merge($todel,array('ocsng',
                                          100,101,102,103));
         }
      }
      if (!haveRight('notes',$action)) {
         $todel[]=90;
      }

      if (count($todel)) {
         foreach ($todel as $ID) {
            if (isset($options[$ID])) {
               unset($options[$ID]);
            }
         }
      }

      return $options;
   }


   /**
   * Get the SEARCH_OPTION array
   *
   * @param $itemtype
   *
   * @return the reference to  array of search options for the given item type
   **/
   static function &getOptions($itemtype) {
      global $LANG, $CFG_GLPI;

      static $search = array();

      if (!isset($search[$itemtype])) {

         // standard type first
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            $search[$itemtype] = $item->getSearchOptions();
         } else if ($itemtype=='States') {
            $search[$itemtype]['common'] = $LANG['common'][32];

            $search['States'][1]['table']     = 'state_types';
            $search['States'][1]['field']     = 'name';
            $search['States'][1]['linkfield'] = 'name';
            $search['States'][1]['name']      = $LANG['common'][16];
            $search['States'][1]['datatype']  = 'itemlink';

            $search['States'][2]['table']     = 'state_types';
            $search['States'][2]['field']     = 'id';
            $search['States'][2]['linkfield'] = 'id';
            $search['States'][2]['name']      = $LANG['common'][2];

            $search['States'][31]['table']     = 'glpi_states';
            $search['States'][31]['field']     = 'name';
            $search['States'][31]['linkfield'] = 'states_id';
            $search['States'][31]['name']      = $LANG['state'][0];

            $search['States'][3]['table']     = 'glpi_locations';
            $search['States'][3]['field']     = 'completename';
            $search['States'][3]['linkfield'] = 'locations_id';
            $search['States'][3]['name']      = $LANG['common'][15];

            $search['States'][5]['table']     = 'state_types';
            $search['States'][5]['field']     = 'serial';
            $search['States'][5]['linkfield'] = 'serial';
            $search['States'][5]['name']      = $LANG['common'][19];

            $search['States'][6]['table']     = 'state_types';
            $search['States'][6]['field']     = 'otherserial';
            $search['States'][6]['linkfield'] = 'otherserial';
            $search['States'][6]['name']      = $LANG['common'][20];

            $search['States'][16]['table']     = 'state_types';
            $search['States'][16]['field']     = 'comment';
            $search['States'][16]['linkfield'] = 'comment';
            $search['States'][16]['name']      = $LANG['common'][25];
            $search['States'][16]['datatype']  = 'text';

            $search['States'][70]['table']     = 'glpi_users';
            $search['States'][70]['field']     = 'name';
            $search['States'][70]['linkfield'] = 'users_id';
            $search['States'][70]['name']      = $LANG['common'][34];

            $search['States'][71]['table']     = 'glpi_groups';
            $search['States'][71]['field']     = 'name';
            $search['States'][71]['linkfield'] = 'groups_id';
            $search['States'][71]['name']      = $LANG['common'][35];

            $search['States'][19]['table']     = 'state_types';
            $search['States'][19]['field']     = 'date_mod';
            $search['States'][19]['linkfield'] = '';
            $search['States'][19]['name']      = $LANG['common'][26];
            $search['States'][19]['datatype']  = 'datetime';

            $search['States'][23]['table']     = 'glpi_manufacturers';
            $search['States'][23]['field']     = 'name';
            $search['States'][23]['linkfield'] = 'manufacturers_id';
            $search['States'][23]['name']      = $LANG['common'][5];

            $search['States'][24]['table']     = 'glpi_users';
            $search['States'][24]['field']     = 'name';
            $search['States'][24]['linkfield'] = 'users_id_tech';
            $search['States'][24]['name']      = $LANG['common'][10];

            $search['States'][80]['table']     = 'glpi_entities';
            $search['States'][80]['field']     = 'completename';
            $search['States'][80]['linkfield'] = 'entities_id';
            $search['States'][80]['name']      = $LANG['entity'][0];
         }

         if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
            $search[$itemtype]['contract'] = $LANG['Menu'][25];

            $search[$itemtype][29]['table']         = 'glpi_contracts';
            $search[$itemtype][29]['field']         = 'name';
            $search[$itemtype][29]['linkfield']     = '';
            $search[$itemtype][29]['name']          = $LANG['common'][16]." ".$LANG['financial'][1];
            $search[$itemtype][29]['forcegroupby']  = true;
            $search[$itemtype][29]['datatype']      = 'itemlink';
            $search[$itemtype][29]['itemlink_type'] = CONTRACT_TYPE;

            $search[$itemtype][30]['table']        = 'glpi_contracts';
            $search[$itemtype][30]['field']        = 'num';
            $search[$itemtype][30]['linkfield']    = '';
            $search[$itemtype][30]['name']         = $LANG['financial'][4]." ".$LANG['financial'][1];
            $search[$itemtype][30]['forcegroupby'] = true;

            $search[$itemtype][130]['table']        = 'glpi_contracts';
            $search[$itemtype][130]['field']        = 'duration';
            $search[$itemtype][130]['linkfield']    = '';
            $search[$itemtype][130]['name']         = $LANG['financial'][8]." ".$LANG['financial'][1];
            $search[$itemtype][130]['forcegroupby'] = true;

            $search[$itemtype][131]['table']        = 'glpi_contracts';
            $search[$itemtype][131]['field']        = 'periodicity';
            $search[$itemtype][131]['linkfield']    = '';
            $search[$itemtype][131]['name']         = $LANG['financial'][69];
            $search[$itemtype][131]['forcegroupby'] = true;

            $search[$itemtype][132]['table']        = 'glpi_contracts';
            $search[$itemtype][132]['field']        = 'begin_date';
            $search[$itemtype][132]['linkfield']    = '';
            $search[$itemtype][132]['name']         = $LANG['search'][8]." ".$LANG['financial'][1];
            $search[$itemtype][132]['forcegroupby'] = true;
            $search[$itemtype][132]['datatype']     = 'date';

            $search[$itemtype][133]['table']        = 'glpi_contracts';
            $search[$itemtype][133]['field']        = 'accounting_number';
            $search[$itemtype][133]['linkfield']    = '';
            $search[$itemtype][133]['name']         = $LANG['financial'][13]." ".$LANG['financial'][1];
            $search[$itemtype][133]['forcegroupby'] = true;

            $search[$itemtype][134]['table']         = 'glpi_contracts';
            $search[$itemtype][134]['field']         = 'end_date';
            $search[$itemtype][134]['linkfield']     = '';
            $search[$itemtype][134]['name']          = $LANG['search'][9]." ".$LANG['financial'][1];
            $search[$itemtype][134]['forcegroupby']  = true;
            $search[$itemtype][134]['datatype']      = 'date_delay';
            $search[$itemtype][134]['datafields'][1] = 'begin_date';
            $search[$itemtype][134]['datafields'][2] = 'duration';

            $search[$itemtype][135]['table']        = 'glpi_contracts';
            $search[$itemtype][135]['field']        = 'notice';
            $search[$itemtype][135]['linkfield']    = '';
            $search[$itemtype][135]['name']         = $LANG['financial'][10]." ".$LANG['financial'][1];
            $search[$itemtype][135]['forcegroupby'] = true;

            $search[$itemtype][136]['table']        = 'glpi_contracts';
            $search[$itemtype][136]['field']        = 'cost';
            $search[$itemtype][136]['linkfield']    = '';
            $search[$itemtype][136]['name']         = $LANG['financial'][5]." ".$LANG['financial'][1];
            $search[$itemtype][136]['forcegroupby'] = true;
            $search[$itemtype][136]['datatype']     = 'decimal';

            $search[$itemtype][137]['table']        = 'glpi_contracts';
            $search[$itemtype][137]['field']        = 'billing';
            $search[$itemtype][137]['linkfield']    = '';
            $search[$itemtype][137]['name']       = $LANG['financial'][11]." ".$LANG['financial'][1];
            $search[$itemtype][137]['forcegroupby'] = true;

            $search[$itemtype][138]['table']        = 'glpi_contracts';
            $search[$itemtype][138]['field']        = 'renewal';
            $search[$itemtype][138]['linkfield']    = '';
            $search[$itemtype][138]['name']      = $LANG['financial'][107]." ".$LANG['financial'][1];
            $search[$itemtype][138]['forcegroupby'] = true;
         }

         // && $itemtype !=  CARTRIDGEITEM_TYPE && $itemtype !=  CONSUMABLEITEM_TYPE
         if (in_array($itemtype, $CFG_GLPI["infocom_types"])) {
            $search[$itemtype]['financial'] = $LANG['financial'][3];

            $search[$itemtype][25]['table']        = 'glpi_infocoms';
            $search[$itemtype][25]['field']        = 'immo_number';
            $search[$itemtype][25]['linkfield']    = '';
            $search[$itemtype][25]['name']         = $LANG['financial'][20];
            $search[$itemtype][25]['forcegroupby'] = true;

            $search[$itemtype][26]['table']        = 'glpi_infocoms';
            $search[$itemtype][26]['field']        = 'order_number';
            $search[$itemtype][26]['linkfield']    = '';
            $search[$itemtype][26]['name']         = $LANG['financial'][18];
            $search[$itemtype][26]['forcegroupby'] = true;

            $search[$itemtype][27]['table']        = 'glpi_infocoms';
            $search[$itemtype][27]['field']        = 'delivery_number';
            $search[$itemtype][27]['linkfield']    = '';
            $search[$itemtype][27]['name']         = $LANG['financial'][19];
            $search[$itemtype][27]['forcegroupby'] = true;

            $search[$itemtype][28]['table']        = 'glpi_infocoms';
            $search[$itemtype][28]['field']        = 'bill';
            $search[$itemtype][28]['linkfield']    = '';
            $search[$itemtype][28]['name']         = $LANG['financial'][82];
            $search[$itemtype][28]['forcegroupby'] = true;

            $search[$itemtype][37]['table']        = 'glpi_infocoms';
            $search[$itemtype][37]['field']        = 'buy_date';
            $search[$itemtype][37]['linkfield']    = '';
            $search[$itemtype][37]['name']         = $LANG['financial'][14];
            $search[$itemtype][37]['datatype']     = 'date';
            $search[$itemtype][37]['forcegroupby'] = true;

            $search[$itemtype][38]['table']        = 'glpi_infocoms';
            $search[$itemtype][38]['field']        = 'use_date';
            $search[$itemtype][38]['linkfield']    = '';
            $search[$itemtype][38]['name']         = $LANG['financial'][76];
            $search[$itemtype][38]['datatype']     = 'date';
            $search[$itemtype][38]['forcegroupby'] = true;

            $search[$itemtype][50]['table']        = 'glpi_budgets';
            $search[$itemtype][50]['field']        = 'name';
            $search[$itemtype][50]['linkfield']    = '';
            $search[$itemtype][50]['name']         = $LANG['financial'][87];
            $search[$itemtype][50]['forcegroupby'] = true;

            $search[$itemtype][51]['table']        = 'glpi_infocoms';
            $search[$itemtype][51]['field']        = 'warranty_duration';
            $search[$itemtype][51]['linkfield']    = '';
            $search[$itemtype][51]['name']         = $LANG['financial'][15];
            $search[$itemtype][51]['forcegroupby'] = true;

            $search[$itemtype][52]['table']        = 'glpi_infocoms';
            $search[$itemtype][52]['field']        = 'warranty_info';
            $search[$itemtype][52]['linkfield']    = '';
            $search[$itemtype][52]['name']         = $LANG['financial'][16];
            $search[$itemtype][52]['forcegroupby'] = true;

            $search[$itemtype][120]['table']         = 'glpi_infocoms';
            $search[$itemtype][120]['field']         = 'end_warranty';
            $search[$itemtype][120]['linkfield']     = '';
            $search[$itemtype][120]['name']          = $LANG['financial'][80];
            $search[$itemtype][120]['datatype']      = 'date';
            $search[$itemtype][120]['datatype']      = 'date_delay';
            $search[$itemtype][120]['datafields'][1] = 'buy_date';
            $search[$itemtype][120]['datafields'][2] = 'warranty_duration';
            $search[$itemtype][120]['forcegroupby']  = true;

            $search[$itemtype][53]['table']        = 'glpi_suppliers_infocoms';
            $search[$itemtype][53]['field']        = 'name';
            $search[$itemtype][53]['linkfield']    = '';
            $search[$itemtype][53]['name']         = $LANG['financial'][26];
            $search[$itemtype][53]['forcegroupby'] = true;

            $search[$itemtype][54]['table']        = 'glpi_infocoms';
            $search[$itemtype][54]['field']        = 'value';
            $search[$itemtype][54]['linkfield']    = '';
            $search[$itemtype][54]['name']         = $LANG['financial'][21];
            $search[$itemtype][54]['datatype']     = 'decimal';
            $search[$itemtype][54]['width']        = 100;
            $search[$itemtype][54]['forcegroupby'] = true;

            $search[$itemtype][55]['table']        = 'glpi_infocoms';
            $search[$itemtype][55]['field']        = 'warranty_value';
            $search[$itemtype][55]['linkfield']    = '';
            $search[$itemtype][55]['name']         = $LANG['financial'][78];
            $search[$itemtype][55]['datatype']     = 'decimal';
            $search[$itemtype][55]['width']        = 100;
            $search[$itemtype][55]['forcegroupby'] = true;

            $search[$itemtype][56]['table']        = 'glpi_infocoms';
            $search[$itemtype][56]['field']        = 'sink_time';
            $search[$itemtype][56]['linkfield']    = '';
            $search[$itemtype][56]['name']         = $LANG['financial'][23];
            $search[$itemtype][56]['forcegroupby'] = true;

            $search[$itemtype][57]['table']        = 'glpi_infocoms';
            $search[$itemtype][57]['field']        = 'sink_type';
            $search[$itemtype][57]['linkfield']    = '';
            $search[$itemtype][57]['name']         = $LANG['financial'][22];
            $search[$itemtype][57]['forcegroupby'] = true;

            $search[$itemtype][58]['table']        = 'glpi_infocoms';
            $search[$itemtype][58]['field']        = 'sink_coeff';
            $search[$itemtype][58]['linkfield']    = '';
            $search[$itemtype][58]['name']         = $LANG['financial'][77];
            $search[$itemtype][58]['forcegroupby'] = true;

            $search[$itemtype][59]['table']        = 'glpi_infocoms';
            $search[$itemtype][59]['field']        = 'alert';
            $search[$itemtype][59]['linkfield']    = '';
            $search[$itemtype][59]['name']         = $LANG['common'][41];
            $search[$itemtype][59]['forcegroupby'] = true;

            $search[$itemtype][122]['table']       = 'glpi_infocoms';
            $search[$itemtype][122]['field']       = 'comment';
            $search[$itemtype][122]['linkfield']   = '';
            $search[$itemtype][122]['name']        = $LANG['common'][25]." - ".$LANG['financial'][3];
            $search[$itemtype][122]['datatype']    = 'text';
            $search[$itemtype][122]['forcegroupby'] = true;
         }

         // Search options added by plugins
         $plugsearch=getPluginSearchOptions($itemtype);
         if (count($plugsearch)) {
            $search[$itemtype] += array('plugins' => $LANG['common'][29]);
            $search[$itemtype] += $plugsearch;
         }
      }
      return $search[$itemtype];
   }


}
?>
