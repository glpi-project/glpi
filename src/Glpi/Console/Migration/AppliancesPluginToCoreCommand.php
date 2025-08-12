<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

use Appliance;
use Appliance_Item;
use Appliance_Item_Relation;
use ApplianceEnvironment;
use ApplianceType;
use Change_Item;
use Contract_Item;
use Document_Item;
use Domain;
use Glpi\Console\AbstractCommand;
use Infocom;
use Item_Problem;
use Item_Project;
use Item_Ticket;
use KnowbaseItem_Item;
use Location;
use Log;
use Network;
use Profile;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppliancesPluginToCoreCommand extends AbstractCommand
{
    /**
     * Error code returned if plugin version or plugin data is invalid.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

    /**
     * Error code returned if import failed.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_IMPORT_FAILED = 2;

    /**
     * list of possible relations of the plugin indexed by their correspond integer in the plugin
     *
     * @var array
     */
    public const PLUGIN_RELATION_TYPES = [
        1 => Location::class,
        2 => Network::class,
        3 => Domain::class,
    ];

    /**
     * list of usefull plugin tables and fields
     *
     * @var array
     */
    public const PLUGIN_APPLIANCE_TABLES = [
        "glpi_plugin_appliances_appliances"       => [
            "id",
            "entities_id",
            "is_recursive",
            "name",
            "is_deleted",
            "plugin_appliances_appliancetypes_id",
            "comment",
            "locations_id",
            "plugin_appliances_environments_id",
            "users_id",
            "users_id_tech",
            "groups_id",
            "groups_id_tech",
            "relationtype",
            "date_mod",
            "states_id",
            "externalid",
            "serial",
            "otherserial",
        ],
        "glpi_plugin_appliances_appliancetypes"   => ["id","entities_id","is_recursive","name","comment"],
        "glpi_plugin_appliances_appliances_items" => ["id", "plugin_appliances_appliances_id","items_id","itemtype"],
        "glpi_plugin_appliances_environments"     => ["id","name","comment" ],
        "glpi_plugin_appliances_relations"        => ["id","plugin_appliances_appliances_items_id","relations_id"],
    ];

    /**
     * itemtype corresponding to appliance in plugin
     *
     * @var string
     */
    public const PLUGIN_APPLIANCE_ITEMTYPE = "PluginAppliancesAppliance";

    /**
     * itemtype corresponding to appliance in core
     *
     * @var string
     */
    public const CORE_APPLIANCE_ITEMTYPE = "Appliance";

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:appliances_plugin_to_core');
        $this->setDescription(sprintf(__('Migrate %s plugin data into GLPI core tables'), 'Appliances'));

        $this->addOption(
            'skip-errors',
            's',
            InputOption::VALUE_NONE,
            __('Do not stop on import errors')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $no_interaction = $input->getOption('no-interaction');
        if (!$no_interaction) {
            // Ask for confirmation (unless --no-interaction)
            $output->writeln([
                __('You are about to launch migration of Appliances plugin data into GLPI core tables.'),
                __('Any previous appliance created in core will be lost.'),
                __('It is better to make a backup of your existing data before continuing.'),
            ]);

            $this->askForConfirmation(false);
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
     * Check that required tables exists and fields are OK for migration.
     *
     * @return bool
     */
    private function checkPlugin(): bool
    {
        $missing_tables = false;
        foreach (self::PLUGIN_APPLIANCE_TABLES as $table => $fields) {
            if (!$this->db->tableExists($table)) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Appliances plugin table "%s" is missing.'), $table) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $missing_tables = true;
            } else {
                foreach ($fields as $field) {
                    if (!$this->db->fieldExists($table, $field)) {
                        $this->output->writeln(
                            '<error>' . sprintf(__('Appliances plugin field "%s" is missing.'), $table . '.' . $field) . '</error>',
                            OutputInterface::VERBOSITY_QUIET
                        );
                        $missing_tables = true;
                    }
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
    private function cleanCoreTables()
    {
        $core_tables = [
            Appliance::getTable(),
            ApplianceType::getTable(),
            ApplianceEnvironment::getTable(),
            Appliance_Item::getTable(),
            Appliance_Item_Relation::getTable(),
        ];

        foreach ($core_tables as $table) {
            $result = $this->db->delete($table, [1]);

            if (!$result) {
                throw new RuntimeException(
                    sprintf('Unable to truncate table "%s"', $table)
                );
            }
        }

        $table  = Infocom::getTable();
        $result = $this->db->delete($table, [
            'itemtype' => self::CORE_APPLIANCE_ITEMTYPE,
        ]);
        if (!$result) {
            throw new RuntimeException(
                sprintf('Unable to clean table "%s"', $table)
            );
        }
    }


    /**
     * Copy plugin tables to backup tables from plugin to core keeping same ID.
     *
     * @return bool
     */
    private function migratePlugin(): bool
    {
        global $CFG_GLPI;

        //prevent infocom creation from general setup
        if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
            $CFG_GLPI['auto_create_infocoms'] = false;
        }
        $this->cleanCoreTables();

        return $this->createApplianceTypes()
         && $this->createApplianceEnvironments()
         && $this->createApplianceRelations()
         && $this->createApplianceItems()
         && $this->createAppliances()
         && $this->updateItemtypes()
         && $this->updateProfilesApplianceRights();
    }

    /**
     * Update profile rights (Associable items to a ticket).
     *
     * @return bool
     */
    private function updateProfilesApplianceRights(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Updating profiles...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $table  = Profile::getTable();
        $result = $this->db->doQuery(
            sprintf(
                "UPDATE %s SET helpdesk_item_type = REPLACE(helpdesk_item_type, '%s', '%s')",
                $this->db->quoteName($table),
                self::PLUGIN_APPLIANCE_ITEMTYPE,
                self::CORE_APPLIANCE_ITEMTYPE
            )
        );
        if (false === $result) {
            $this->outputImportError(
                sprintf(__('Unable to update "%s" in profiles.'), __('Associable items to a ticket'))
            );
            if (!$this->input->getOption('skip-errors')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rename itemtype in core tables.
     *
     * @return bool
     */
    private function updateItemtypes(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Updating GLPI itemtypes...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $itemtypes_tables = [
            Item_Ticket::getTable(),
            Item_Problem::getTable(),
            Change_Item::getTable(),
            Item_Project::getTable(),
            Log::getTable(),
            Infocom::getTable(),
            Document_Item::getTable(),
            Contract_Item::getTable(),
            KnowbaseItem_Item::getTable(),
        ];

        foreach ($itemtypes_tables as $itemtype_table) {
            $result = $this->db->update($itemtype_table, [
                'itemtype' => self::CORE_APPLIANCE_ITEMTYPE,
            ], [
                'itemtype' => self::PLUGIN_APPLIANCE_ITEMTYPE,
            ]);

            if (false === $result) {
                $this->outputImportError(
                    sprintf(
                        __('Migration of table "%s" failed with message "(%s) %s".'),
                        $itemtype_table,
                        $this->db->errno(),
                        $this->db->error()
                    )
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create appliance items.
     *
     * @return bool
     */
    private function createApplianceItems(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Appliance Items...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_appliances_appliances_items',
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $item) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing Appliance item "%d"...'),
                    (int) $item['id']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = [
                'id'            => $item['id'],
                'appliances_id' => $item['plugin_appliances_appliances_id'],
                'items_id'      => $item['items_id'],
                'itemtype'      => $item['itemtype'],
            ];

            $appi = new Appliance_Item();
            if (!($appi_id = $appi->getFromDBByCrit($app_fields))) {
                $appi_id = $appi->add($app_fields);
            }

            if (false === $appi_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Appliance item %d.'), (int) $item['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create appliance environments.
     *
     * @return bool
     */
    private function createApplianceEnvironments(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Appliance Environment...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_appliances_environments',
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $env) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing environment "%s"...'),
                    $env['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = [
                'id'      => $env['id'],
                'name'    => $env['name'],
                'comment' => $env['comment'],
            ];

            $appe = new ApplianceEnvironment();
            if (!($appe_id = $appe->getFromDBByCrit($app_fields))) {
                $appe_id = $appe->add($app_fields);
            }

            if (false === $appe_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Appliance environment %s (%d).'), $env['name'], (int) $env['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create appliances.
     *
     * @return bool
     */
    private function createAppliances(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Appliances...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_appliances_appliances',
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $appliance) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing appliance "%s"...'),
                    $appliance['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = [
                'id'                       => $appliance['id'],
                'entities_id'              => $appliance['entities_id'],
                'is_recursive'             => $appliance['is_recursive'],
                'name'                     => $appliance['name'],
                'is_deleted'               => $appliance['is_deleted'],
                'appliancetypes_id'        => $appliance['plugin_appliances_appliancetypes_id'],
                'comment'                  => $appliance['comment'],
                'locations_id'             => $appliance['locations_id'],
                'manufacturers_id'         => '0',
                'applianceenvironments_id' => $appliance['plugin_appliances_environments_id'],
                'users_id'                 => $appliance['users_id'],
                'users_id_tech'            => $appliance['users_id_tech'],
                'groups_id'                => $appliance['groups_id'],
                'groups_id_tech'           => $appliance['groups_id_tech'],
                'date_mod'                 => $appliance['date_mod'],
                'is_helpdesk_visible'      => $appliance['is_helpdesk_visible'],
                'states_id'                => $appliance['states_id'],
                'externalidentifier'       => $appliance['externalid'],
                'serial'                   => $appliance['serial'],
                'otherserial'              => $appliance['otherserial'],
            ];

            $app = new Appliance();
            if (!($app_id = $app->getFromDBByCrit($app_fields))) {
                $app_id = $app->add($app_fields);
            }

            if (false === $app_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Appliance %s (%d).'), $appliance['name'], (int) $appliance['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create appliance types.
     *
     * @return bool
     */
    private function createApplianceTypes(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Appliance types...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_appliances_appliancetypes',
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $type) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing type "%s"...'),
                    $type['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $appt_fields = [
                'id'                 => $type['id'],
                'entities_id'        => $type['entities_id'],
                'is_recursive'       => $type['is_recursive'],
                'name'               => $type['name'],
                'comment'            => $type['comment'],
                'externalidentifier' => $type['externalid'],
            ];

            $appt = new ApplianceType();
            if (!($appt_id = $appt->getFromDBByCrit($appt_fields))) {
                $appt_id = $appt->add($appt_fields);
            }

            if (false === $appt_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Appliance environment %s (%d).'), $type['name'], (int) $type['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create appliance relations.
     *
     * @return bool
     */
    private function createApplianceRelations(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Appliance relations...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'SELECT'       => ['rel.*', 'app.relationtype'],
            'FROM'         => 'glpi_plugin_appliances_relations AS rel',
            'INNER JOIN'   => [
                'glpi_plugin_appliances_appliances_items AS items' => [
                    'ON'  => [
                        'items' => 'id',
                        'rel'   => 'plugin_appliances_appliances_items_id',
                    ],
                ],
                'glpi_plugin_appliances_appliances AS app' => [
                    'ON'  => [
                        'app'   => 'id',
                        'items' => 'plugin_appliances_appliances_id',
                    ],
                ],
            ],
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $row) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing relation "%s"...'),
                    $row['id']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $itemtype = self::PLUGIN_RELATION_TYPES[$row['relationtype']] ?? "";
            if ($itemtype == "") {
                $this->outputImportError(
                    sprintf(
                        __('Unable to found relation type %s from Appliance Item Relation %d.'),
                        $row['relationtype'],
                        (int) $row['id']
                    ),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                } else {
                    continue; // Skip this relation
                }
            }

            $appr_fields = [
                'id'                  => $row['id'],
                'appliances_items_id' => $row['plugin_appliances_appliances_items_id'],
                'itemtype'            => $itemtype,
                'items_id'            => $row['relations_id'],
            ];

            $appr = new Appliance_Item_Relation();
            if (!($appr_id = $appr->getFromDBByCrit($appr_fields))) {
                $appr_id = $appr->add($appr_fields);
            }

            if (false === $appr_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Appliance Item Relation %d.'), (int) $row['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                    return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }


    /**
     * Output import error message.
     *
     * @param string           $message
     * @param ProgressBar|null $progress_bar
     *
     * @return void
     */
    private function outputImportError($message, ?ProgressBar $progress_bar = null)
    {

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
