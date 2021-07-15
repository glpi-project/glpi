<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Agent\Credentials\AbstractCredential as Credential;

/**
 * @since 10.0.0
 **/
class AgentCredential extends CommonDBTM {

   /** @var boolean */
   public $dohistory = true;

   /** @var string */
   static $rightname = 'inventory';

   static function getTypeName($nb = 0) {
      return _n('Agent credential', 'Agent credentials', $nb);
   }

   static function canCreate() {
      return true;
   }

   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(AgentTask::class, $ong, $options);

      return $ong;
   }

   /**
    * Display form for credential configuration
    *
    * @param integer $id      ID of the agent
    * @param array   $options Options
    *
    * @return boolean
    */
   function showForm($id, $options = []) {
      global $CFG_GLPI;

      if (!empty($id)) {
         $this->getFromDB($id);
      } else {
         $this->getEmpty();
      }
      $this->initForm($id, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='name'>".__('Name')."</label></td>";
      echo "<td align='center'>";
      Html::autocompletionTextField($this, 'name', ['size' => 40]);
      echo "</td>";
      echo "<td>"._n('Type', 'Types', 1)."</td>";
      echo "<td align='center'>";
      if (!$this->isNewItem()) {
         echo Credential::getLabelledTypes()[$this->fields['type']];
      } else {
         Dropdown::showFromArray('type', Credential::getLabelledTypes());
      }
      echo "</td>";
      echo "</tr>";

      if (!$this->isNewItem()) {
         $credentials = $this->getCredentials();
         $credentials
            ->showForm();
      }

      $this->showFormButtons($options);

      return true;
   }

   public static function getIcon() {
      return "fas fa-key";
   }

   function post_getFromDB() {
      $this->getCredentials();
   }

   function post_updateItem($history = 1) {
      if (isset($this->input['agenttasks_id'])) {
         //link to task
         $task_creds = new AgentTask_Credential();
         $task_creds->add([
            'agenttasks_id' => $this->input['agenttasks_id'],
            'agentcredentials_id' => $this->fields['id']
         ]);
      }
   }

   function post_addItem() {
      $task_creds = new AgentTask_Credential();
      $task_creds->add([
         'agenttasks_id' => $this->input['agenttasks_id'],
         'agentcredentials_id' => $this->fields['id']
      ]);
   }

   public function getCredentials(int $type = null): Credential {
      return Credential::factory(
         $type ?? $this->fields['type'],
         importArrayFromDB(
            Toolbox::sodiumDecrypt($this->fields['credentials'] ?? '')
         )
      );
   }

   private function prepareInput(array $input): array {
      $credentials = $this->getCredentials($input['type'] ?? $this->fields['type']);
      $credentials->load($input);
      $input['credentials'] = Toolbox::sodiumEncrypt(
         exportArrayToDB($credentials->getCredentials())
      );
      return $input;
   }

   public function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);
      if ($input) {
         $input = $this->prepareInput($input);
      }
      return $input;
   }

   public function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      if ($input) {
         $input = $this->prepareInput($input);
      }
      return $input;
   }
}
