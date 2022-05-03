<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    /**
     * Error code returned when failed to clear chache.
     *
     * @var integer
     */
    const ERROR_CACHE_CLEAR_FAILURE = 1;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:cache:clear');
        $this->setAliases(
            [
                'cache:clear',
            // Old command name/alias
                'glpi:system:clear_cache',
                'system:clear_cache'
            ]
        );
        $this->setDescription('Clear GLPI cache.');

        $this->addOption(
            'context',
            'c',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            __('Cache context to clear (i.e. \'core\' or \'plugin:plugin_name\'). All contexts are cleared by default.')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $cache_manager = new CacheManager();

        $success = true;

        $contexts = $input->getOption('context');
        if (empty($contexts)) {
            $success = $cache_manager->resetAllCaches();
        } else {
            foreach ($contexts as $context) {
                if (!in_array($context, $cache_manager->getKnownContexts())) {
                    throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                        sprintf(__('Invalid cache context: "%s".'), $context)
                    );
                }
            }
            foreach ($contexts as $context) {
                $success = $cache_manager->getCacheInstance($context)->clear() && $success;
            }
        }

        if (!$success) {
            $output->writeln(
                '<error>' . __('Failed to clear cache.') . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_CACHE_CLEAR_FAILURE;
        }

        $output->writeln('<info>' . __('Cache cleared successfully.') . '</info>');

        return 0; // Success
    }
}
