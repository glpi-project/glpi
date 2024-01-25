<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Domain;
use Domain_Item;
use DomainType;
use Session;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Toolbox;

class DomainsPluginToCoreCommand extends AbstractPluginToCoreCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('migration:domains_plugin_to_core');
        $this->setDescription(__('Migrate Domains plugin data into GLPI core tables'));
    }

    protected function getPluginKey(): string
    {
        return 'domains';
    }

    protected function getRequiredMinimalPluginVersion(): ?string
    {
        return '2.1.0';
    }

    protected function getRequiredDatabasePluginFields(): array
    {
        return [
            'glpi_plugin_domains_domains.id',
            'glpi_plugin_domains_domains.name',
            'glpi_plugin_domains_domains.comment',
            'glpi_plugin_domains_domains.entities_id',
            'glpi_plugin_domains_domains.is_recursive',
            'glpi_plugin_domains_domains.is_deleted',
            'glpi_plugin_domains_domains.date_creation',
            'glpi_plugin_domains_domains.date_mod',
            'glpi_plugin_domains_domains.date_expiration',
            'glpi_plugin_domains_domains.plugin_domains_domaintypes_id',
            'glpi_plugin_domains_domains.users_id_tech',
            'glpi_plugin_domains_domains.groups_id_tech',
            'glpi_plugin_domains_domains.suppliers_id',

            'glpi_plugin_domains_domaintypes.id',
            'glpi_plugin_domains_domaintypes.name',
            'glpi_plugin_domains_domaintypes.entities_id',
            'glpi_plugin_domains_domaintypes.comment',

            'glpi_plugin_domains_domains_items.plugin_domains_domains_id',
            'glpi_plugin_domains_domains_items.itemtype',
            'glpi_plugin_domains_domains_items.items_id',
        ];
    }

    protected function migratePlugin(): void
    {
        //prevent infocom creation from general setup
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
            $CFG_GLPI['auto_create_infocoms'] = false;
        }

        $this->importDomainTypes();
        $this->importDomains();
        $this->importDomainItems();
    }

    /**
     * Migrate domain types
     *
     * @return void
     */
    protected function importDomainTypes(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), DomainType::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $types_iterator = $this->db->request([
            'FROM'   => 'glpi_plugin_domains_domaintypes',
            'ORDER'  => 'id ASC'
        ]);

        if ($types_iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($types_iterator) as $type_data) {
            $core_type_id = $this->getMatchingElementId(DomainType::class, ['name' => $type_data['name']]);

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_type_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    DomainType::getTypeName(),
                    $type_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $domaintype = $this->storeItem(
                Domain::class,
                $core_type_id,
                Toolbox::addslashes_deep(
                    [
                        'name'         => $type_data['name'],
                        'entities_id'  => $type_data['entities_id'],
                        'comment'      => $type_data['comment'],
                    ]
                ),
                $progress_bar
            );

            if ($domaintype !== null) {
                $this->defineTargetItem(
                    'PluginDomainsDomaintype',
                    $type_data['id'],
                    DomainType::class,
                    $domaintype->getID()
                );
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Migrate domains.
     *
     * @return void
     */
    protected function importDomains(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), Domain::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $domains_iterator = $this->db->request([
            'FROM'   => 'glpi_plugin_domains_domains',
            'ORDER'  => 'id ASC'
        ]);

        if ($domains_iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($domains_iterator) as $domain_data) {
            $core_domain_id = $this->getMatchingElementId(DomainType::class, ['name' => $domain_data['name']]);

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_domain_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    Domain::getTypeName(),
                    $domain_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $mapped_type = $this->getTargetItem('PluginDomainsDomaintype', $domain_data['plugin_domains_domaintypes_id']);
            if ($domain_data['plugin_domains_domaintypes_id'] != 0 && $mapped_type === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDomainsDomaintype',
                        $domain_data['plugin_domains_domaintypes_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
            }

            $domain = $this->storeItem(
                Domain::class,
                $core_domain_id,
                Toolbox::addslashes_deep(
                    [
                        'name'                  => $domain_data['name'],
                        'entities_id'           => $domain_data['entities_id'],
                        'is_recursive'          => $domain_data['is_recursive'],
                        'domaintypes_id'        => $mapped_type !== null ? $mapped_type->getID() : 0,
                        'date_domaincreation'   => $domain_data['date_creation'],
                        'date_expiration'       => $domain_data['date_expiration'],
                        'users_id_tech'         => $domain_data['users_id_tech'],
                        'groups_id_tech'        => $domain_data['groups_id_tech'],
                        //suppliers_id handled in infocom
                        'comment'               => $domain_data['comment'],
                        'date_mod'              => $domain_data['date_mod'],
                        'is_deleted'            => $domain_data['is_deleted']
                    ]
                ),
                $progress_bar
            );

            if ($domain !== null) {
                $this->defineTargetItem(
                    'PluginDomainsDomains',
                    $domain_data['id'],
                    Domain::class,
                    $domain->getID()
                );
                if (!empty($domain_data['suppliers_id'])) {
                    $this->storeInfocomForItem($domain, ['suppliers_id' => $domain_data['suppliers_id']], $progress_bar);
                }
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Migrate domain items
     *
     * @return void
     */
    protected function importDomainItems(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), Domain_Item::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $items_iterator = $this->db->request([
            'FROM'   => 'glpi_plugin_domains_domains_items',
            'ORDER'  => 'id ASC'
        ]);

        if ($items_iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($items_iterator) as $relation_data) {
            $mapped_domain = $this->getTargetItem('PluginDomainsDomains', $relation_data['plugin_domains_domains_id']);
            if ($mapped_domain === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDomainsDomains',
                        $relation_data['plugin_domains_domains_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
                continue;
            }
            $domains_id = $mapped_domain->fields['id'];

            $core_relation_id = $this->getMatchingElementId(
                Domain_Item::class,
                [
                    'domains_id' => $domains_id,
                    'itemtype'   => $relation_data['itemtype'],
                    'items_id'   => $relation_data['items_id'],
                ]
            );

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_relation_id !== null ? __('Skip existing %s "%s".') : __('Importing %s "%s"...'),
                    Domain_Item::getTypeName(),
                    $domains_id . ' ' . $relation_data['itemtype'] . ' ' . $relation_data['items_id']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            if ($core_relation_id !== null) {
                 //if it already exist in DB, there is nothing to change
                 continue;
            }

            $item_input = Toolbox::addslashes_deep([
                'domains_id'            => $domains_id,
                'itemtype'              => $relation_data['itemtype'],
                'items_id'              => $relation_data['items_id'],
                'domainrelations_id'    => 0
            ]);

            $item = new Domain_Item();
            if ($item->add($item_input) === false) {
                $message = sprintf(
                    __('Unable to create %s "%s" (%d).'),
                    Domain_Item::getTypeName(),
                    $domains_id . ' ' . $relation_data['itemtype'] . ' ' . $relation_data['items_id']
                );
                $this->handleImportError($message, $progress_bar);
            }
        }

        $this->output->write(PHP_EOL);
    }
}
