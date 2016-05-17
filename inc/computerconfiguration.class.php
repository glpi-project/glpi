<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}

/**
 * ComputerConfiguration class
**/
class ComputerConfiguration extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';

   /**
    * Name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Computer Configuration', 'Computer Configurations', $nb);
   }

   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab("ComputerConfiguration_Computer", $ong, $options);
      $this->addStandardTab("ComputerConfiguration_ComputerConfiguration", $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {
         case __CLASS__:
            $ong = array();
            $nb = count($item->getCriteria()) + count($item->getMetaCriteria());
            $ong[1] = self::createTabEntry(_n('Criterion', 'Criteria', $nb), $nb);
            return $ong;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showCriteria();
                  return true;
            }
      }
      return false;
   }

   /**
    * Configuration principal form
    * @param  int $ID : id of the configuration
    * @param  array $options
    * @return nothing, displays a form
    */
   function showForm($ID, $options = array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'><td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>\n";

      echo "<td rowspan='2'>". __('Comments')."</td>";
      echo "<td rowspan='2'>
            <textarea cols='55' rows='5' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";


      echo "<tr class='tab_bg_2'><td>".__('Parent configurations (Inheritance)')."</td>";
      echo "<td>";
      // find all inheritances for this configuration
      $actives = array();
      if (!$this->isNewId($this->getID())) {
         $actives = self::getAncestors($ID);
      }

      // find all configuration to displays dropdown of inheritance
      $where = "";
      if (!$this->isNewId($this->getID())) {
         $where = "id != ".$this->getID();
      }
      $found_configurations = $this->find($where);
      $inheritance_options = array();
      foreach ($found_configurations as $computerconfigurations_id => $computerconfigurations) {
         $inheritance_options[$computerconfigurations_id] = $computerconfigurations['name'];
      }

      // displays dropdown of inheritance
      Dropdown::showFromArray('_inheritance', $inheritance_options, array('values'   => $actives,
                                                            'multiple' => true));
      echo "</td>\n";

      echo "<td>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>".__('View computers of children configurations')."</td>";
      echo "<td>";
      Dropdown::showYesNo('viewchilds', $this->fields["viewchilds"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * transform current configuration's criteria from url form to array
    * @return [array]
    */
   function getCriteria($get_ancestors = false) {
      if (!empty($this->fields['criteria'])) {
         parse_str($this->fields['criteria'], $criteria);

         if ($get_ancestors) {
            $ancestors = $this->getAncestors($this->getID());
            $self = new self;
            foreach ($ancestors as $ancestor ) {
               $self->getFromDB($ancestor);
               $ancestor_criteria = $self->getCriteria();
               $criteria = array_merge($ancestor_criteria, $criteria);
            }
         }

         return $criteria;
      }
      return array();
   }

   /**
    * transform current configuration's metacriteria from url form to array
    * @return [array]
    */
   function getMetaCriteria($get_ancestors = false) {
      if (!empty($this->fields['metacriteria'])) {
         parse_str($this->fields['metacriteria'], $metacriteria);

         if ($get_ancestors) {
            $ancestors = $this->getAncestors($this->getID());
            $self = new self;
            foreach ($ancestors as $ancestor ) {
               $self->getFromDB($ancestor);
               $ancestor_metacriteria = $self->getMetaCriteria();
               $metacriteria = array_merge($ancestor_metacriteria, $metacriteria);
            }
         }

         return $metacriteria;
      }
      return array();
   }


   /**
    * Displays tab content
    * This function adapted from Search::showGenericSearch with controls removed
    * @param  bool $formcontrol : display form buttons
    * @return nothing, displays a seach form
    */
   function showCriteria($formcontrol = true) {
      global $CFG_GLPI;

      $itemtype = "Computer";
      $p = array();

      // load saved criterias
      $p['criteria'] = $this->getCriteria();
      $p['metacriteria'] = $this->getMetaCriteria();

      //manage sessions
      $glpisearch_session = $_SESSION['glpisearch'];
      unset($_SESSION['glpisearch']);
      $p = Search::manageParams($itemtype, $p);

      if ($formcontrol) {
         //show generic search form (duplicated from Search class)
         echo "<form name='searchformComputerConfigurationCriteria' method='post'>";
         echo "<input type='hidden' name='id' value='".$this->getID()."'>";

         // add tow hidden fields to permit delete of (meta)criteria
         echo "<input type='hidden' name='criteria' value=''>";
         echo "<input type='hidden' name='metacriteria' value=''>";
      }

      echo "<div class='tabs_criteria'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>"._n('Criterion', 'Criteria', 2)."</th></tr>";
      echo "<tr><td>";

      echo "<div id='searchcriteria'>";
      $nb_criteria = count($p['criteria']);
      if ($nb_criteria == 0) $nb_criteria++;
      $nbsearchcountvar = 'nbcriteria'.strtolower($itemtype).mt_rand();
      $nbmetasearchcountvar = 'nbmetacriteria'.strtolower($itemtype).mt_rand();
      $searchcriteriatableid = 'criteriatable'.strtolower($itemtype).mt_rand();
      // init criteria count
      $js = "var $nbsearchcountvar=".$nb_criteria.";";
      $js .= "var $nbmetasearchcountvar=".count($p['metacriteria']).";";
      echo Html::scriptBlock($js);

      echo "<table class='tab_cadre_fixe' >";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";

      echo "<table class='tab_format' id='$searchcriteriatableid'>";

      // Displays normal search parameters
      for ($i=0 ; $i<$nb_criteria ; $i++) {
         $_POST['itemtype'] = $itemtype;
         $_POST['num'] = $i ;
         include(GLPI_ROOT.'/ajax/searchrow.php');
      }

      $metanames = array();
      $linked =  Search::getMetaItemtypeAvailable($itemtype);

      if (is_array($linked) && (count($linked) > 0)) {
         for ($i=0 ; $i<count($p['metacriteria']) ; $i++) {

            $_POST['itemtype'] = $itemtype;
            $_POST['num'] = $i ;
            include(GLPI_ROOT.'/ajax/searchmetarow.php');
         }
      }
      echo "</table>\n";
      echo "</td></tr>";
      echo "</table>\n";

      // For dropdown
      echo "<input type='hidden' name='itemtype' value='$itemtype'>";

      if ($formcontrol) {
         // add new button to search form (to store and preview)
         echo "<div class='center'>";
         echo "<input type='submit' value=\" "._sx('button', 'Save').
              " \" class='submit' name='update'>&nbsp;";
         echo "<input type='submit' value=\" ".__('Preview')." \" class='submit' name='preview'>";
         echo "</div>";
      }

      echo "</td></tr></table>";
      echo "</div>";

      //restore search session variables
      $_SESSION['glpisearch'] = $glpisearch_session;

      // Reset to start when submit new search
      echo "<input type='hidden' name='start' value='0'>";

      Html::closeForm();

      //show parent criteria
      if ($formcontrol) {
         if (count(self::getAncestors($this->getID())) > 0) {
            echo "<div id='parent_criteria'>";
            $this->showParentCriteria();
            echo "</div>";
         }
      }

      //clean with javascript search control
      $clean_script = "jQuery( document ).ready(function( $ ) {
         $('#parent_criteria img').remove();
         $('.tabs_criteria img[name=img_deleted').remove();
      });";
      echo Html::scriptBlock($clean_script);
   }


   /**
    * Displays tab content
    * Show inherited criteria. The content is hidden by default
    * @return nothing, displays a seach form
    */
   function showParentCriteria($level = 0) {
      if ($level == 0) {
         echo "<input type'button' id='toggleParentCriteria' value='".
                  __("Show/Hide parent criteria")."' class='submit'><br />";
         echo Html::scriptBlock("
            $('#toggleParentCriteria').click(function() {
               $('#parent_criteria_0').toggle();
            });
         ");
         echo "<div id='parent_criteria_$level' style='display:none; border:1px solid #D0D99D;
                                                       width:970px' class='tab_cadre_fixe'>";
      }

      $conf_ancestors = self::getAncestors($this->getID());
      $configuration = new self;

      foreach ($conf_ancestors as $ancestors_id) {
         $configuration->getFromDB($ancestors_id);
         echo $configuration->getLink();

         //recursive show of parent criteria
         $configuration->showCriteria(false);

         //display criteria form (without controls)
         $configuration->showParentCriteria($level+1);
      }

      if ($level == 0) {
         echo "</div>";
      }
   }

   /**
    * displays tab content, list of childs configurations
    * @return nothing, displays a table
    */
   function showChildsConfigurations() {
      $configuration = new self;
      $childs_configuration = self::getChildren($this->getID());
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      echo "<th>".__('Name')."</th>";
      echo "</tr>";
      foreach ($childs_configuration as $computerconfigurations_id) {
         $configuration->getFromDB($computerconfigurations_id);
         echo "<tr><td>".$configuration->getLink(array('comments' => true))."</td></tr>";
      }
      echo "</table>";
   }

   /**
    * displays tab content, list of computer associated to the current configuration
    * @return nothing, displays a table
    */
   function showComputers() {
      global $CFG_GLPI;

      // get all computers associated this configuration and their states
      $listofcomputer_withstate = self::getListComputerStates($this->getID());

      // get all children computers associated this configuration and their states
      if ($this->fields['viewchilds']) {
         $computers_id_list_childs = self::getListOfComputersOfChildsConfiguration($this->getID());
         $chlidren_configurations = array_unique($computers_id_list_childs);
         foreach ($chlidren_configurations as $tmp_conf_id) {
            $tmp_listofcomputer_withstate = self::getListComputerStates($tmp_conf_id);
            $listofcomputer_withstate = array_merge($tmp_listofcomputer_withstate, $listofcomputer_withstate);
         }
      }

      // init pager
      $number = count($listofcomputer_withstate);
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }
      Html::printAjaxPager(sprintf(__('%1$s (%2$s)'), ComputerConfiguration_Computer::getTypeName(2), __('D=Dynamic')),
                              $start, $number);
      Session::initNavigateListItems("ComputerConfiguration_Computer", sprintf(__('%1$s = %2$s'),
                                                   self::getTypeName(1), $this->getName()));

      // init massiveactions
      $rand = mt_rand();
      $classname = "ComputerConfiguration_Computer";
      $massiveactionparams
         = array('container'        => 'mass'.$classname.$rand,
                 'specific_actions' => array('MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.
                                                'purge' => _x('button', 'Delete permanently')));


      Html::openMassiveActionsForm('mass'.$classname.$rand);
      Html::showMassiveActions($massiveactionparams);
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.$classname.$rand)."</th>";
      echo "<th>".__('Name')."</th>";
      if ($this->fields['viewchilds']) {
         echo "<th>".__('associated to the configuration')."</th>";
      }
      echo "<th width='10'>"._x('item', 'State')."</th>";
      echo "<th>".__('do not match the configuration')."</th>";
      echo "</tr>";
      $computer = new Computer;
      $configuration = new self;
      for ($i=$start, $j=0 ; ($i < $number) && ($j < $_SESSION['glpilist_limit']) ; $i++, $j++) {
         $currentline = array_shift($listofcomputer_withstate);
         $computer->getFromDB($currentline['computers_id']);

         echo "<tr>";

         // displays massive actions checkboxes
         echo "<td>";
         if ($currentline['computerconfigurations_id'] === $this->getID()) {
            Html::showMassiveActionCheckBox($classname, $currentline['computerconfigurations_computers_id']);
         }
         echo "</td>";

         // echo computer name
         echo "<td>".$computer->getLink(array('comments' => true));
         if ($currentline['is_dynamic']) {
            echo "&nbsp;<b>(D)</b>";
         }
         echo "</td>";

         // displays inherited configuration
         if ($this->fields['viewchilds']) {
            if ($currentline['computerconfigurations_id'] != $this->getID()) {
               //get and display configuration name
               $configuration->getFromDB($currentline['computerconfigurations_id']);
               echo "<td>".$configuration->getLink(array('comments' => true))."</td>";
            } else echo "<td></td>";
         }

         // check if current computer match saved criterias
         if ($currentline['match']) {
            $pic = "greenbutton.png";
            $title = __('Yes');
         } else {
            $pic = "redbutton.png";
            $title = __('No');
         }
         echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/$pic' title='$title'></td>";

         //for mismatch computers, displays the configuration who trigger (and criteria who mismatch)
         echo "<td>";
         if (!$currentline['match']) {
            $out = "";
            foreach ($currentline['mismatch_configuration'] as $tmp_conf_id) {
               $configuration->getFromDB($tmp_conf_id);
               $out.= $configuration->getLink(array('comments' => true)).", ";
            }
            echo substr($out, 0, -2);
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      $massiveactionparams['ontop'] =false;
      Html::showMassiveActions($massiveactionparams);
   }

   function post_addItem() {
      if (isset($this->input['_inheritance'])) {
         $this->input['id'] = $this->fields['id'];
         $this->saveInheritance($this->input);
      }
   }

   function prepareInputForUpdate($input) {
      //serialize search parameters
      if (isset($input['criteria']) && is_array($input['criteria'])) {
         $input['criteria'] = http_build_query($input['criteria']);
      }

      if (isset($input['metacriteria']) && is_array($input['metacriteria'])) {
         $input['metacriteria'] = http_build_query($input['metacriteria']);
      }

      if (!isset($input['criteria'])) {
         $this->saveInheritance($input);
      }

      return $input;
   }

   /**
    * Clean associated computer and inheritance on purge configuration
    * @return nothing
    */
   function cleanDBonPurge() {
      $compconf_comp = new ComputerConfiguration_Computer();
      $compconf_comp->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $compconf_compconf = new ComputerConfiguration_ComputerConfiguration();
      $compconf_compconf->cleanDBonItemDelete(__CLASS__, $this->fields['id']);
   }

   /**
    * Save the current inherintance for specified form POST
    * @param  [type] $input form POST
    * @return nothing
    */
   function saveInheritance($input) {
      global $DB;

      //clear all old inheritance for this configuration
      $DB->query("DELETE FROM glpi_computerconfigurations_computerconfigurations
                         WHERE computerconfigurations_id_1 = ".$input['id']);

      //add new inheritance
      if (isset($input['_inheritance'])) {
         $compconf_compconf = new ComputerConfiguration_ComputerConfiguration;
         foreach ($input['_inheritance'] as $inheritance_options) {
            $compconf_compconf->add(array('computerconfigurations_id_1' => $input['id'],
                                          'computerconfigurations_id_2' => $inheritance_options));
         }
      }

   }

   /**
    * return ancestor of specified configuration
    * @param  [integer] $computerconfigurations_id
    * @param  [bool] $fullrecursive (get recursively all inheritance)
    * @return [array], list of ancestors configurations_id (ex array(incremental_index => configurations_id))
    */
   static function getAncestors($computerconfigurations_id, $fullrecursive = false) {
      $compconf_compconf = new ComputerConfiguration_ComputerConfiguration;
      $found_ancestors = $compconf_compconf->find("computerconfigurations_id_1 = ".
                                                $computerconfigurations_id);
      $listofancestors_id = array();
      foreach ($found_ancestors as $ancestor) {
         $listofancestors_id[] = $ancestor['computerconfigurations_id_2'];
      }

      if ($fullrecursive) {
         foreach ($listofancestors_id as $ancestors_id) {
            $recursive_listofancestors_id = self::getAncestors($ancestors_id, true);
            $listofancestors_id = array_merge($listofancestors_id,
                                              $recursive_listofancestors_id);
         }
      }

      return $listofancestors_id;
   }


   /**
    * return children of specified configuration
    * @param  [integer] $computerconfigurations_id
    * @return [array], list of children configurations_id (ex array(incremental_index => configurations_id))
    */
   static function getChildren($computerconfigurations_id) {
      $compconf_compconf = new ComputerConfiguration_ComputerConfiguration;
      $found_childs = $compconf_compconf->find("computerconfigurations_id_2 = ".
                                                $computerconfigurations_id);
      $childs_id = array();
      foreach ($found_childs as $child) {
         $childs_id[] = $child['computerconfigurations_id_1'];
      }
      return $childs_id;
   }

   /**
    * Retrieve the id of computers associated to this configuration
    *    indexed by glpi_computerconfigurations_computers.id
    * @param  int $computerconfigurations_id : id of the configuration
    * @param  string $filter: - none : no filter (default)
    *                         - match: computers who match criteria,
    *                         - notmatch : computers who not match criteria]
    * @param  bool $getChildren : retrieve also computers in childs configuration
    * @return array : array of computers_id (ex array(computerconfigurations_computers_id => computers_id))
    */
   static function getListOfComputersID($computerconfigurations_id, $filter = 'none',
                                        $getChildren = false) {

      $compconf_comp = new ComputerConfiguration_Computer;
      $found_comp = $compconf_comp->find("computerconfigurations_id = $computerconfigurations_id");
      $listofcomputers_id = array();
      foreach ($found_comp as $comp) {
         $listofcomputers_id[$comp['id']] = $comp['computers_id'];
      }

      // get computers associated to child configurations
      if ($getChildren) {
         $conf_childs = self::getChildren($computerconfigurations_id);
         foreach ($conf_childs as $childs_id) {
            $computers_id_child = self::getListOfComputersID($childs_id, $filter, $getChildren);

            // merge computer of child configuration with computer of current configuration
            $listofcomputers_id = array_merge($listofcomputers_id, $computers_id_child);
         }
      }

      // apply filter param
      if ($filter === "none") {
         return $listofcomputers_id;
      }

      $computers_criteria = self::getComputerFromSearchCriteria($computerconfigurations_id);
      if ($filter === "match") {
         return array_intersect($listofcomputers_id, $computers_criteria);
      }

      if ($filter === "notmatch") {
         return array_diff($listofcomputers_id, $computers_criteria);
      }

      return false;
   }

   /**
    * // retrieve list of association computers <=> configuration for childs
    * @param  int $computerconfigurations_id, id of the configuration
    * @return array : return list of computer associated to configuration (ex array(computers_id => conf_id))
    */
   static function getListOfComputersOfChildsConfiguration($computerconfigurations_id) {
      $listofcomputers_id = array();

      $conf_childs = self::getChildren($computerconfigurations_id);
      foreach ($conf_childs as $childs_id) {
         // use recursivity
         $listofcomputers_id = self::getListOfComputersOfChildsConfiguration($childs_id);

         // get list of computer for the current configuration
         $computers_id_child = self::getListOfComputersID($childs_id, "none", false);
         if (count($computers_id_child) > 0) {
            // fill list with computers_id in keys and configurations_id in value
            $computers_id_child_tmp = array();
            foreach ($computers_id_child as $computers_id) {
               $computers_id_child_tmp[$computers_id] = $childs_id;
            }

            // merge computers list from recursivity with current computer list
            $listofcomputers_id = $listofcomputers_id + $computers_id_child_tmp;
         }
      }

      return $listofcomputers_id;
   }

   /**
    * Return list of computer who match configuration
    * @param  int $computerconfigurations_id :id of the configuration
    * @param  array $computers_mismatch : output param who reference which configuration
    *                                       from inheritance mismatch each computer
    * @return array : list of computers_id (ex array(computers_id => computers_id))
    */
   static function getComputerFromSearchCriteria($computerconfigurations_id) {
      $configuration = new self;
      $configuration->getFromDB($computerconfigurations_id);

      // default parameter for search engine
      $p['sort']         = '';
      $p['list_limit']   = 999999999999; // how to get all ?
      $p['is_deleted']   = 0;
      $p['all_search']   = false;
      $p['no_search']    = false;

      // load saved criterias
      $p['criteria'] = $configuration->getCriteria();
      $p['metacriteria'] = $configuration->getMetaCriteria();

      // get all computers who match criteria (return only id column)
      $datas = Search::getDatas("Computer", $p, array(1));
      $computers_list = array();
      foreach ($datas['data']['items'] as $computers_id => $row_id) {
         $computers_list[$computers_id] = $computers_id;
      }

      return $computers_list;
   }


   /**
    * Return a list of computer (with state associated) for the given configuration
    * @param  [integer] $computerconfigurations_id, id of the configuration
    * @return [array]   array(computers_id => array(match => true/false,
    *                                                        computerconfigurations_computers_id => id of glpi_computerconfigurations_computers,
    *                                                        mismatch_configuration => list of configuration who not match computer,
    *                                                        computers_id => id of the computer))
    */
   static function getListComputerStates($computerconfigurations_id) {
      $listofcomputers_states = array();

      $listofancestors_id = self::getAncestors($computerconfigurations_id, true);
      $listofconfigurations_id = array_merge(array($computerconfigurations_id), $listofancestors_id);

      $listofcomputers_associated = $listofcomputers_id_associated = array();
      $compconf_comp = new ComputerConfiguration_Computer;
      $found_comp = $compconf_comp->find("computerconfigurations_id = $computerconfigurations_id");
      foreach ($found_comp as $comp) {
         $listofcomputers_associated[$comp['id']] = array('computers_id' => $comp['computers_id'],
                                                  'is_dynamic'   => $comp['is_dynamic']);
         $listofcomputers_id_associated[$comp['id']] = $comp['computers_id'];
      }


      foreach ($listofconfigurations_id as $tmp_conf_id) {
         $listofcomputers_criteria = self::getComputerFromSearchCriteria($tmp_conf_id);
         $listofcomputers_match = array_intersect($listofcomputers_id_associated, $listofcomputers_criteria);

         foreach ($listofcomputers_associated as $computerconfigurations_computers_id
                                           => $computer) {

            $computers_id = $computer['computers_id'];

            if (!isset($listofcomputers_states[$computers_id]['match'])) {
               $listofcomputers_states[$computers_id]['match'] = true;
            }

            if (!in_array($computers_id, $listofcomputers_match)) {
               $listofcomputers_states[$computers_id]['mismatch_configuration'][] = $tmp_conf_id;
               $listofcomputers_states[$computers_id]['match'] = false;

               $listofcomputers_states[$computers_id]['mismatch_configuration']
                  = array_unique($listofcomputers_states[$computers_id]['mismatch_configuration']);
            }

            $listofcomputers_states[$computers_id]['computerconfigurations_computers_id'] = $computerconfigurations_computers_id;
            $listofcomputers_states[$computers_id]['computers_id'] = $computers_id;
            $listofcomputers_states[$computers_id]['is_dynamic'] = $computer['is_dynamic'];
            $listofcomputers_states[$computers_id]['computerconfigurations_id'] = $computerconfigurations_id;
         }
      }

      return $listofcomputers_states;
   }


   /**
    * redirect to computer search and load the saved criterias in this configuration
    * @return nothing, redirect browser
    */
   function preview() {
      parse_str($this->fields['criteria'], $criteria['criteria']);
      parse_str($this->fields['metacriteria'], $metacriteria['metacriteria']);
      $criteria = http_build_query($criteria);
      $metacriteria = http_build_query($metacriteria);
      Html::redirect("computer.php?reset=reset&$criteria&$metacriteria");
   }


   /**
    * Check if a computer match given configuration
    * @param  integer  $computers_id,              id of computer
    * @param  integer  $computerconfigurations_id, id of configuration
    * @param  array   $detail,                     output paramater who can contains :
    *                                                 - mismatch_configuration : list of configurations_id who mismatch
    * @return boolean
    */
   static function isComputerMatchConfiguration($computers_id, $computerconfigurations_id,
                                                &$detail = array()) {

      $detail = array();

      $listofcomputer_withstate = self::getListComputerStates($computerconfigurations_id);

      $detail['is_dynamic'] = $listofcomputer_withstate[$computers_id]['is_dynamic'];

      if (!$listofcomputer_withstate[$computers_id]['match']) {
         $detail['mismatch_configuration'] = $listofcomputer_withstate[$computers_id]['mismatch_configuration'];
         return false;
      }

      return true;
   }



   /**
    * Check if a computer match a criteria (by search it in searchengine)
    * @param  [integer]  $computers_id [id of the computer]
    * @param  [array]  $criterion    Array(
    *                                     [field] => searchoption_num
    *                                     [searchtype] => is/contains
    *                                     [value] => value
    *                                 )
    *                                 See : https://forge.indepnet.net/projects/glpi/wiki/SearchEngine
    * @param  boolean $meta         [criterion is a metacriterion ?]
    * @return boolean
    */
   static function isComputerMatchCriterion($computers_id, $criterion, $meta = false) {
      $p['sort']         = '';
      $p['list_limit']   = 999999999999; // how to get all ?
      $p['is_deleted']   = 0;
      $p['all_search']   = false;
      $p['no_search']    = false;

      $p['criteria'] = array();
      if (!$meta) {
         $p['criteria'] = array($criterion);
      } else {
         $p['metacriteria'] = array($criterion);
      }

      //send params to search engine
      $datas = Search::getDatas("Computer", $p, array(1));
      if (isset($datas['data']['items'][$computers_id])) {
         return true;
      }

      return false;
   }
}

