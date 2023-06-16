<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Console\Cache;

use Glpi\Cache\CacheManager;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 10.0.0
 */
class ConfigureCommand extends AbstractCommand
{
    /**
     * Error code returned if cache configuration file cannot be write.
     *
     * @var int
     */
    const ERROR_UNABLE_TO_WRITE_CONFIG = 1;

    protected $requires_db = false;

    /**
     * Cache manager.
     * @var CacheManager
     */
    private $cache_manager;

    public function __construct()
    {
        $this->cache_manager = new CacheManager();

        parent::__construct();
    }

    protected function configure()
    {

        $this->setName('cache:configure');
        $this->setDescription('Define cache configuration');

        $this->addOption(
            'context',
            null,
            InputOption::VALUE_REQUIRED,
            __('Cache context (i.e. \'core\' or \'plugin:plugin_name\')'),
            'core'
        );

        $this->addOption(
            'dsn',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            __('Cache system DSN')
        );

        $this->addOption(
            'use-default',
            null,
            InputOption::VALUE_NONE,
            __('Unset cache configuration to use default filesystem cache for given context')
        );

        $this->addOption(
            'skip-connection-checks',
            null,
            InputOption::VALUE_NONE,
            __('Skip connection checks')
        );

        $this->addUsage('--use-default');
        $this->addUsage('--dsn=memcached://cache1.glpi-project.org --dsn=memcached://cache2.glpi-project.org');
        $this->addUsage('--dsn=redis://redis.glpi-project.org:6379/glpi');

        $adapters = $this->cache_manager->getAvailableAdapters();
        $help_lines = [
            sprintf(
                __('Valid cache systems are: %s.'),
                '<comment>' . implode('</comment>, <comment>', $adapters) . '</comment>'
            ),
            '',
            sprintf(__('%s DSN format: %s'), $adapters[CacheManager::SCHEME_MEMCACHED], 'memcached://[user:pass@][ip|host|socket[:port]][?weight=int]'),
            sprintf(__('%s DSN format: %s'), $adapters[CacheManager::SCHEME_REDIS], 'redis://[pass@][ip|host|socket[:port]][/db-index]'),
            sprintf(__('%s DSN format: %s'), $adapters[CacheManager::SCHEME_REDISS], 'rediss://[pass@][ip|host|socket[:port]][/db-index]'),
            '',
            __('Cache namespace can be use to ensure either separation or sharing of multiple GLPI instances data on same cache system.'),
        ];
        $this->setHelp(implode("\n", $help_lines));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $use_default = $input->getOption('use-default');
        $context     = $input->getOption('context');
        $dsn         = $input->getOption('dsn');

        if (!$this->cache_manager->isContextValid($context, true)) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                sprintf(__('Invalid cache context: "%s".'), $context)
            );
        }

        if (count($dsn) === 0 && !$use_default) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('Either --dsn or --use-default options have to be used.')
            );
        } else if (count($dsn) > 0 && $use_default) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('--dsn and --use-default options cannot be used simultaneously.')
            );
        }

        if ($use_default) {
           // Reset configuration for given context.
            $success = $this->cache_manager->unsetConfiguration($context);
            if (!$success) {
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . __('Unable to write cache configuration file.') . '</error>',
                    self::ERROR_UNABLE_TO_WRITE_CONFIG
                );
            }
            $output->writeln(
                '<info>' . __('Cache configuration saved successfully.') . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );
            return 0; // Success
        }

       // Transform $dsn into single string if only one value is passed
        if (count($dsn) === 1) {
            $dsn = reset($dsn);
        }

        if (!$this->cache_manager->isDsnValid($dsn)) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                sprintf(__('Invalid cache DSN: "%s".'), json_encode($dsn))
            );
        }

       // Check connection
        if (!$input->getOption('skip-connection-checks')) {
            try {
                $this->cache_manager->testConnection($dsn);
            } catch (\Throwable $e) {
                $error_msg = sprintf(__('An error occurred during connection to cache system: "%s"'), $e->getMessage());
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . $error_msg . '</error>',
                    self::ERROR_UNABLE_TO_WRITE_CONFIG
                );
            }
        }

       // Store configuration
        $success = $this->cache_manager->setConfiguration($context, $dsn, []);

        if (!$success) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to write cache configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_WRITE_CONFIG
            );
        }

        $output->writeln(
            '<info>' . __('Cache configuration saved successfully.') . '</info>',
            OutputInterface::VERBOSITY_NORMAL
        );

        return 0; // Success
    }
}
