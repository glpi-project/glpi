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
         } else if ($itemtype==RESERVATION_TYPE) {

            $search[RESERVATION_TYPE][4]['table']     = 'glpi_reservationitems';
            $search[RESERVATION_TYPE][4]['field']     = 'comment';
            $search[RESERVATION_TYPE][4]['linkfield'] = 'comment';
            $search[RESERVATION_TYPE][4]['name']      = $LANG['common'][25];
            $search[RESERVATION_TYPE][4]['datatype']  = 'text';

            $search[RESERVATION_TYPE]['common'] = $LANG['common'][32];

            $search[RESERVATION_TYPE][1]['table']     = 'reservation_types';
            $search[RESERVATION_TYPE][1]['field']     = 'name';
            $search[RESERVATION_TYPE][1]['linkfield'] = 'name';
            $search[RESERVATION_TYPE][1]['name']      = $LANG['common'][16];
            $search[RESERVATION_TYPE][1]['datatype']  = 'itemlink';

            $search[RESERVATION_TYPE][2]['table']     = 'reservation_types';
            $search[RESERVATION_TYPE][2]['field']     = 'id';
            $search[RESERVATION_TYPE][2]['linkfield'] = 'id';
            $search[RESERVATION_TYPE][2]['name']      = $LANG['common'][2];

            $search[RESERVATION_TYPE][3]['table']     = 'glpi_locations';
            $search[RESERVATION_TYPE][3]['field']     = 'completename';
            $search[RESERVATION_TYPE][3]['linkfield'] = 'locations_id';
            $search[RESERVATION_TYPE][3]['name']      = $LANG['common'][15];

            $search[RESERVATION_TYPE][16]['table']     = 'reservation_types';
            $search[RESERVATION_TYPE][16]['field']     = 'comment';
            $search[RESERVATION_TYPE][16]['linkfield'] = 'comment';
            $search[RESERVATION_TYPE][16]['name']      = $LANG['common'][25];
            $search[RESERVATION_TYPE][16]['datatype']  = 'text';

            $search[RESERVATION_TYPE][70]['table']     = 'glpi_users';
            $search[RESERVATION_TYPE][70]['field']     = 'name';
            $search[RESERVATION_TYPE][70]['linkfield'] = 'users_id';
            $search[RESERVATION_TYPE][70]['name']      = $LANG['common'][34];

            $search[RESERVATION_TYPE][71]['table']     = 'glpi_groups';
            $search[RESERVATION_TYPE][71]['field']     = 'name';
            $search[RESERVATION_TYPE][71]['linkfield'] = 'groups_id';
            $search[RESERVATION_TYPE][71]['name']      = $LANG['common'][35];

            $search[RESERVATION_TYPE][19]['table']     = 'reservation_types';
            $search[RESERVATION_TYPE][19]['field']     = 'date_mod';
            $search[RESERVATION_TYPE][19]['linkfield'] = '';
            $search[RESERVATION_TYPE][19]['name']      = $LANG['common'][26];
            $search[RESERVATION_TYPE][19]['datatype']  = 'datetime';

            $search[RESERVATION_TYPE][23]['table']     = 'glpi_manufacturers';
            $search[RESERVATION_TYPE][23]['field']     = 'name';
            $search[RESERVATION_TYPE][23]['linkfield'] = 'manufacturers_id';
            $search[RESERVATION_TYPE][23]['name']      = $LANG['common'][5];

            $search[RESERVATION_TYPE][24]['table']     = 'glpi_users';
            $search[RESERVATION_TYPE][24]['field']     = 'name';
            $search[RESERVATION_TYPE][24]['linkfield'] = 'users_id_tech';
            $search[RESERVATION_TYPE][24]['name']      = $LANG['common'][10];

            $search[RESERVATION_TYPE][80]['table']     = 'glpi_entities';
            $search[RESERVATION_TYPE][80]['field']     = 'completename';
            $search[RESERVATION_TYPE][80]['linkfield'] = 'entities_id';
            $search[RESERVATION_TYPE][80]['name']      = $LANG['entity'][0];
         } else if ($itemtype==STATE_TYPE) {
            $search[STATE_TYPE]['common'] = $LANG['common'][32];

            $search[STATE_TYPE][1]['table']     = 'state_types';
            $search[STATE_TYPE][1]['field']     = 'name';
            $search[STATE_TYPE][1]['linkfield'] = 'name';
            $search[STATE_TYPE][1]['name']      = $LANG['common'][16];
            $search[STATE_TYPE][1]['datatype']  = 'itemlink';

            $search[STATE_TYPE][2]['table']     = 'state_types';
            $search[STATE_TYPE][2]['field']     = 'id';
            $search[STATE_TYPE][2]['linkfield'] = 'id';
            $search[STATE_TYPE][2]['name']      = $LANG['common'][2];

            $search[STATE_TYPE][31]['table']     = 'glpi_states';
            $search[STATE_TYPE][31]['field']     = 'name';
            $search[STATE_TYPE][31]['linkfield'] = 'states_id';
            $search[STATE_TYPE][31]['name']      = $LANG['state'][0];

            $search[STATE_TYPE][3]['table']     = 'glpi_locations';
            $search[STATE_TYPE][3]['field']     = 'completename';
            $search[STATE_TYPE][3]['linkfield'] = 'locations_id';
            $search[STATE_TYPE][3]['name']      = $LANG['common'][15];

            $search[STATE_TYPE][5]['table']     = 'state_types';
            $search[STATE_TYPE][5]['field']     = 'serial';
            $search[STATE_TYPE][5]['linkfield'] = 'serial';
            $search[STATE_TYPE][5]['name']      = $LANG['common'][19];

            $search[STATE_TYPE][6]['table']     = 'state_types';
            $search[STATE_TYPE][6]['field']     = 'otherserial';
            $search[STATE_TYPE][6]['linkfield'] = 'otherserial';
            $search[STATE_TYPE][6]['name']      = $LANG['common'][20];

            $search[STATE_TYPE][16]['table']     = 'state_types';
            $search[STATE_TYPE][16]['field']     = 'comment';
            $search[STATE_TYPE][16]['linkfield'] = 'comment';
            $search[STATE_TYPE][16]['name']      = $LANG['common'][25];
            $search[STATE_TYPE][16]['datatype']  = 'text';

            $search[STATE_TYPE][70]['table']     = 'glpi_users';
            $search[STATE_TYPE][70]['field']     = 'name';
            $search[STATE_TYPE][70]['linkfield'] = 'users_id';
            $search[STATE_TYPE][70]['name']      = $LANG['common'][34];

            $search[STATE_TYPE][71]['table']     = 'glpi_groups';
            $search[STATE_TYPE][71]['field']     = 'name';
            $search[STATE_TYPE][71]['linkfield'] = 'groups_id';
            $search[STATE_TYPE][71]['name']      = $LANG['common'][35];

            $search[STATE_TYPE][19]['table']     = 'state_types';
            $search[STATE_TYPE][19]['field']     = 'date_mod';
            $search[STATE_TYPE][19]['linkfield'] = '';
            $search[STATE_TYPE][19]['name']      = $LANG['common'][26];
            $search[STATE_TYPE][19]['datatype']  = 'datetime';

            $search[STATE_TYPE][23]['table']     = 'glpi_manufacturers';
            $search[STATE_TYPE][23]['field']     = 'name';
            $search[STATE_TYPE][23]['linkfield'] = 'manufacturers_id';
            $search[STATE_TYPE][23]['name']      = $LANG['common'][5];

            $search[STATE_TYPE][24]['table']     = 'glpi_users';
            $search[STATE_TYPE][24]['field']     = 'name';
            $search[STATE_TYPE][24]['linkfield'] = 'users_id_tech';
            $search[STATE_TYPE][24]['name']      = $LANG['common'][10];

            $search[STATE_TYPE][80]['table']     = 'glpi_entities';
            $search[STATE_TYPE][80]['field']     = 'completename';
            $search[STATE_TYPE][80]['linkfield'] = 'entities_id';
            $search[STATE_TYPE][80]['name']      = $LANG['entity'][0];
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
