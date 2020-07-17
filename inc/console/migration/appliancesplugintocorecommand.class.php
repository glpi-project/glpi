<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 * Yves TESNIERE
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
namespace Glpi\Console\Migration;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CommonDBTM;
use DB;
use Plugin;
use Appliance;
use ApplianceType;
use ApplianceRelation;
use applianceenvironment;
use appliance_item;
use State;
use Toolbox;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;

class AppliancesPluginToCoreCommand extends AbstractCommand {

   /**
    * Error code returned if plugin version or plugin data is invalid.
    *
    * @var integer
    */
   const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

   /**
    * Error code returned if import failed.
    *
    * @var integer
    */
   const ERROR_PLUGIN_IMPORT_FAILED = 1;

   /**list of usefull plugin tables and fields
    *
    *@var array
    *
    **/
   const PLUGIN_APPLIANCE_TABLES=array( 
 			"glpi_plugin_appliances_appliances"=>array("id","entities_id","is_recursive","name","is_deleted",
								"plugin_appliances_appliancetypes_id","comment","locations_id","plugin_appliances_environments_id","users_id","users_id_tech",
								"groups_id","groups_id_tech","relationtype","date_mod","states_id","externalid","serial","otherserial"),
 			"glpi_plugin_appliances_appliancetypes"=>array("id","entities_id","is_recursive","name","comment"),
 			"glpi_plugin_appliances_appliances_items"=>array("id", "plugin_appliances_appliances_id","items_id","itemtype"),
 			"glpi_plugin_appliances_environments"=>array("id","name","comment" ),
 			"glpi_plugin_appliances_relations"=>array("id","plugin_appliances_appliances_items_id","relations_id")
		);
	
	/**
	 * itemtype corresponding to appliance in plugin
	 *
	 *@var string
	 **/
   const PLUGIN_APPLIANCE_ITEMTYPE="pluginAppliancesappliance";

	/**
 	 * itemtype corresponding to appliance in core
 	 *
 	 *@var string
 	 **/
	const CORE_APPLIANCE_ITEMTYPE="Appliance";

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:appliances_plugin_to_core');
      $this->setDescription(__('Migrate Appliances plugin data into GLPI core tables'));

      $this->addOption(
         'skip-errors',
         's',
         InputOption::VALUE_NONE,
         __('Do not exit on import errors')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      $no_interaction = $input->getOption('no-interaction');
      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)
         $output->writeln(
            [
               __('You are about to launch migration of Appliances plugin data into GLPI core tables.'),
               __('Any previous appliance created in core will be lost.'),
               __('It is better to make a backup of your existing data before continuing.')
            ]
         );

         /** @var QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
               '<comment>' . __('Do you want to launch migration ?') . ' [yes/No]</comment>',
               false
            )
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Migration aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      if (!$this->checkPlugin()) {
         return self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID;
      }


      if (!$this->migratePlugin()) {
         return self::ERROR_PLUGIN_IMPORT_FAILED;
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }

   /**
    * Check that  required tables exists and fields are OK for migration.
    *
    * @throws LogicException
    *
    * @return boolean
    */
   private function checkPlugin() {

      $missing_tables = false;
     foreach (self::PLUGIN_APPLIANCE_TABLES as $table=>$fields) {
      	  if (!$this->db->tableExists($table)) {
            $this->output->writeln(
               '<error>' . sprintf(__('Appliances plugin table "%s" is missing.'), $table) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $missing_tables = true;
         }
            else {
      	foreach($fields as $field){
      		if (!$this->db->fieldExists($table,$field)){
      			$this->output->writeln(
               '<error>' . sprintf(__('Appliances plugin field "%s" is missing.'), $table.'.'.$field) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $missing_tables = true;

      		} ;
	      }
      }
     }
      if ($missing_tables) {
         $this->output->writeln(
            '<error>' . __('Migration cannot be done.') . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return false;
      }

      return true;
   }

   /**
    * Clean data from core tables.
    *
    * @throws RuntimeException
    */
   private function cleanCoreTables() {

       $core_tables = [
         'glpi_appliances',	
			'glpi_appliancerelations',     		
 			'glpi_appliancetypes',
 			'glpi_applianceenvironments',
 			'glpi_appliances_items'
      ];

      foreach ($core_tables as $table) {
         $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

         if (!$result) {
            throw new RuntimeException(
               sprintf('Unable to truncate table "%s"', $table)
            );
         }
      }
   }
   /**
    * copy data from plugin to core keeping same ID.
    *
    *
    * @throws LogicException
    *
    * @return boolean
    */

   private  function backupPluginTables() {
     
     foreach (self::PLUGIN_APPLIANCE_TABLES as $table=>$fields) {
      		$result = $this->db->query(sprintf('ALTER TABLE %s RENAME %s ', DB::quotename($table),DB::quotename('backup_'.$table)));
         if (false === $result) {
            $message = sprintf(
               __('Migration of table "%s"  failed with message "(%s) %s".'),
               $table,
               $this->db->errno(),
               $this->db->error()
            );
            $this->output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_TABLE_MIGRATION_FAILED; 
      	}
		}
		      
                  $this->output->writeln(
           '<info>' . __('plugin tables renamed as backup_*** can be deleted...') . '</info>',
            OutputInterface::VERBOSITY_NORMAL
     );
      return true;
   }
    /**
    * copy plugin tables to backup tables from plugin to core keeping same ID.
    *
    *
    * @throws LogicException
    *
    * @return boolean
    */

   private  function migratePlugin() {

      $no_interaction = $this->input->getOption('no-interaction');

      $skip_errors = $this->input->getOption('skip-errors');

		$this->cleanCoreTables();

      $failure = !$this->createApplianceType()
      || !$this->createApplianceEnvironment()
      || !$this->createApplianceRelation()
      || !$this->createApplianceItem()
      || !$this->createAppliance()
      || !$this->updateInfocoms()
      || !$this->updateItemType()
      || !$this->updateProfilesApplianceRights()
      || !$this->backupPluginTables();

      return !$failure;


   }
  private function updateInfocoms() {

    $table='glpi_infocoms';
    $where = [
       'itemtype'  => self::CORE_APPLIANCE_ITEMTYPE,
    ];
    $result = $this->db->delete($table, $where);
   
       if (false === $result) {
          $message = sprintf(
               __('Migration of table "%s"  failed with message "(%s) %s".'),
               $table,
               $this->db->errno(),
               $this->db->error()
          );
          $this->output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_TABLE_MIGRATION_FAILED;
         }

   return 1;
    }
 
 private function updateProfilesApplianceRights() {
   $table='glpi_profiles';
	$result = $this->db->query(sprintf('update %s set helpdesk_item_type=replace(helpdesk_item_type,\''.self::PLUGIN_APPLIANCE_ITEMTYPE.'\',\''.self::CORE_APPLIANCE_ITEMTYPE.'\')', DB::quotename($table)));
         if (false === $result) {
            $message = sprintf(
               __('Migration of table "%s"  failed with message "(%s) %s".'),
               $table,
               $this->db->errno(),
               $this->db->error()
            );
            $this->output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_TABLE_MIGRATION_FAILED;
         }
      
   return 1;
    }    
 private function updateItemType() {
    	$itemtype_tables=[
    	'glpi_items_tickets',
    	'glpi_items_problems',
    	'glpi_items_projects',
    	'glpi_logs',
    	'glpi_infocoms',
    	'glpi_documents_items',
    	'glpi_contracts_items',
    	'glpi_knowbaseitems_items'
    	];
    	foreach($itemtype_tables as $itemtype_table){

    		$where = [
         		'itemtype'  => self::PLUGIN_APPLIANCE_ITEMTYPE,
      	];
         $params = [
         		'itemtype'  => self::CORE_APPLIANCE_ITEMTYPE,
      	];

   		$result = $this->db->update($itemtype_table, $params, $where);
     		
         if (false === $result) {
            $message = sprintf(
               __('Migration of table "%s"  failed with message "(%s) %s".'),
               $itemtype_table,
               $this->db->errno(),
               $this->db->error()
            );
            $this->output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_TABLE_MIGRATION_FAILED;
         }
       }
   return 1;
    }   
  private function createApplianceItem() {
/*   insert into glpi_appliances_items (id,appliances_id,items_id,itemtype) 
SELECT id, plugin_appliances_appliances_id,items_id,itemtype 
FROM glpi_plugin_appliances_appliances_items WHERE 1=1;*/

      $this->output->writeln(
         '<comment>' . __('Creating Appliance Items...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      $this->output->writeln(
           '<info>' . __('Creating Appliance Items...') . '</info>',
            OutputInterface::VERBOSITY_NORMAL
     );
     $plugin_appliances_appliances_items  = $this->db->request(
          [
             'FROM'  => 'glpi_plugin_appliances_appliances_items'
          ]
            ); 
            foreach($plugin_appliances_appliances_items AS $plugin_appliances_appliances_item){
            	 $app = new applianceitem();
            	 $app_fields = toolbox::sanitize([
         			'id'=>$plugin_appliances_appliances_item['id'],         			
         			'appliances_id'=>$plugin_appliances_appliances_item['plugin_appliances_appliances_id'],         			
         			'items_id'=>$plugin_appliances_appliances_item['items_id'],
         			'itemtype'=>$plugin_appliances_appliances_item['itemtype']
         			]);

     			      if (!($app_id = $app->getFromDBByCrit($app_fields))) {
         						$app_id = $app->add($app_fields);
      					}

      				if (false === $app_id) {
         			$this->outputImportError(
            					'<error>' . __('Unable to create Appliance item .') . '</error>'
         				);
         				return null;
      				}


            } 
     return 1;
   }
    private function createApplianceEnvironment() {
/*INSERT INTO glpi_applianceenvironments (id,name,comment) SELECT id,name,comment 
FROM glpi_plugin_appliances_environments WHERE 1=1;*/
      $this->output->writeln(
         '<comment>' . __('Creating Appliance Environment...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
     $this->output->writeln(
         '<info>' . __('Creating Appliance Environment...') . '</info>',
           OutputInterface::VERBOSITY_NORMAL
      );
     $plugin_appliances_environments  = $this->db->request(
               [
                  'FROM'  => 'glpi_plugin_appliances_environments'
               ]
            ); 
       foreach($plugin_appliances_environments AS $plugin_appliances_environment){
            	 $app = new applianceenvironment();
            	 $app_fields = toolbox::sanitize([
         			'id'=>$plugin_appliances_environment['id'],         			
         			'name'=>$plugin_appliances_environment['name'],         			
         			'comment'=>$plugin_appliances_environment['comment']
         			]);

     			      if (!($app_id = $app->getFromDBByCrit($app_fields))) {
         						$app_id = $app->add($app_fields);
      					}
            		$this->output->writeln(
                  '<info>' . $plugin_appliances_environment['name']. '</info>',
                  OutputInterface::VERBOSITY_NORMAL
               );
      				if (false === $app_id) {
         			$this->outputImportError(
            					'<error>' . __('Unable to create Appliance environment .') . '</error>'
         				);
         				return null;
      				}


            } 
     return 1;
   }
   private function createAppliance() {
/*INSERT INTO glpi_appliances(id,entities_id,is_recursive,name,is_deleted,appliancetypes_id,comment,locations_id,manufacturers_id,applianceenvironments_id,
users_id,users_id_tech,groups_id,groups_id_tech,relationtype,date_mod,states_id,externalidentifier,serial,otherserial) 
select id,entities_id,is_recursive,name,is_deleted,
plugin_appliances_appliancetypes_id,comment,locations_id,0,plugin_appliances_environments_id,users_id,users_id_tech,
groups_id,groups_id_tech,relationtype,date_mod,states_id,externalid,serial,otherserial  FROM  glpi_plugin_appliances_appliances*/
      $this->output->writeln(
         '<comment>' . __('Creating Appliance...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      $this->output->writeln(
         '<info>'. __('Creating Appliance...') . '</info>',
         OutputInterface::VERBOSITY_NORMAL
      );
            $plugin_appliances_appliances  = $this->db->request(
               [
                  'FROM'  => 'glpi_plugin_appliances_appliances'
               ]
            ); 
            foreach($plugin_appliances_appliances AS $plugin_appliances_appliance){
            	 $app = new appliance();
            	 $app_fields = toolbox::sanitize([
         			'id'=>$plugin_appliances_appliance['id'],
         			'entities_id'=>$plugin_appliances_appliance['entities_id'],
        			 	'is_recursive'=>$plugin_appliances_appliance['is_recursive'],
         			'name'=>$plugin_appliances_appliance['name'],
         			'is_deleted'=>$plugin_appliances_appliance['is_deleted'],
         			'appliancetypes_id'=>$plugin_appliances_appliance['plugin_appliances_appliancetypes_id'],
         			'comment'=>$plugin_appliances_appliance['comment'],
         			'locations_id'=>$plugin_appliances_appliance['locations_id'],
         			'manufacturers_id'=>'0',
         			'applianceenvironments_id'=>$plugin_appliances_appliance['plugin_appliances_environments_id'],
         			'users_id'=>$plugin_appliances_appliance['users_id'],
         			'users_id_tech'=>$plugin_appliances_appliance['users_id_tech'],
         			'groups_id'=>$plugin_appliances_appliance['groups_id'],
         			'groups_id_tech'=>$plugin_appliances_appliance['groups_id_tech'],
         			'date_mod'=>$plugin_appliances_appliance['date_mod'],
         			'states_id'=>$plugin_appliances_appliance['states_id'],
         			'externalidentifier'=>$plugin_appliances_appliance['externalid'],
         			'serial'=>$plugin_appliances_appliance['serial'],
         			'otherserial'=>$plugin_appliances_appliance['otherserial']
         			]);

     			      if (!($app_id = $app->getFromDBByCrit($app_fields))) {
         						$app_id = $app->add($app_fields);
      					}
            		$this->output->writeln(
                  '<info>**********' . $plugin_appliances_appliance['name']. '</info>',
                  OutputInterface::VERBOSITY_NORMAL
               );
      				if (false === $app_id) {
         			$this->outputImportError(
            					'<error>' . __('Unable to create Appliance .') . '</error>'
         				);
         				return null;
      				}
            } 
      return 1;
   }
   private function createApplianceType() {
/*INSERT INTO glpi_appliancetypes(id,entities_id,is_recursive,name,comment,externalidentifier) 
SELECT id,entities_id,is_recursive,name,comment,externalid FROM glpi_plugin_appliances_appliancetype*/

      $this->output->writeln(
         '<comment>' . __('Creating Appliance types...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
     $this->output->writeln(
         '<info>' . __('Creating Appliance types...') . '</info>',
        OutputInterface::VERBOSITY_NORMAL
    ); 
        
            $plugin_appliances_types = $this->db->request(
               [
                  'FROM'  => 'glpi_plugin_appliances_appliancetypes'
               ]
            ); 
            foreach($plugin_appliances_types AS $plugin_appliances_type){

            	$name=$plugin_appliances_type['name'];
            	 $appt = new appliancetype();
            	 $appt_fields = Toolbox::sanitize([
         			'id'=>$plugin_appliances_type['id'],
         			'entities_id'=>$plugin_appliances_type['entities_id'],
        			 	'is_recursive'=>$plugin_appliances_type['is_recursive'],
         			'name'=>$plugin_appliances_type['name'],
         			'comment'=>$plugin_appliances_type['comment'],
         			'externalidentifier'=>$plugin_appliances_type['externalid']
         			]);
         			
     			      if (!($appt_id = $appt->getFromDBByCrit($appt_fields))) {
         						$appt_id = $appt->add($appt_fields);
      					}

      				if (false === $appt_id) {
         			$this->outputImportError(
            					'<error>' . __('Unable to create Appliance type.') . '</error>'
         				);
         				return null;
      				}

            }     
      return 1;

   }
   private function createApplianceRelation() {
/*INSERT INTO glpi_appliancerelations(id,appliances_items_id,relations_id) select  id,plugin_appliances_appliances_items_id,relations_id 
from glpi_plugin_appliances_relations wHERE 1=1;*/
      $this->output->writeln(
         '<comment>' . __('Creating Appliance relations...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
                $this->output->writeln(
                  '<info>' . __('Creating Appliance relations...') . '</info>',
                  OutputInterface::VERBOSITY_NORMAL
               ); 
            $plugin_appliances_relations = $this->db->request(
               [
                  'FROM'  => 'glpi_plugin_appliances_relations'
               ]
            ); 

            foreach($plugin_appliances_relations AS $plugin_appliances_relation){
                        		$this->output->writeln(
                  '<info> createApplianceRelation 2</info>',
                  OutputInterface::VERBOSITY_NORMAL
               ); 

            	 $appr = new appliancerelation();
            	 $appr_fields = toolbox::sanitize([
         			'id'=>$plugin_appliances_relation['id'],
         			'appliances_items_id'=>$plugin_appliances_relation['plugin_appliances_appliances_items_id'],
        			 	'relations_id'=>$plugin_appliances_relation['relations_id']
         			]);
     			      if (!($appr_id = $appr->getFromDBByCrit($appr_fields))) {
         						$appr_id = $appr->add($appr_fields);
      					}

      				if (false === $appr_id) {
         			$this->outputImportError(
            					'<error>' . __('Unable to create Appliance Relation.') . '</error>'
         				);
         				return null;
      				}

            }    

      return 1;

   }


   /**
    * Returns verbosity level for import errors.
    *
    * @return number
    */
   private function getImportErrorsVerbosity() {

      return $this->input->getOption('skip-errors')
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;
   }

   /**
    * Output import error message.
    *
    * @param string           $message
    * @param ProgressBar|null $progress_bar
    *
    * @return void
    */
   private function outputImportError($message, ProgressBar $progress_bar = null) {

      $skip_errors = $this->input->getOption('skip-errors');

      $verbosity = $skip_errors
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;

      $message = '<error>' . $message . '</error>';

      if ($skip_errors && $progress_bar instanceof ProgressBar) {
         $this->writelnOutputWithProgressBar(
            $message,
            $progress_bar,
            $verbosity
         );
      } else {
         if (!$skip_errors && $progress_bar instanceof ProgressBar) {
            $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
         }
         $this->output->writeln(
            $message,
            $verbosity
         );
      }
   }
}
