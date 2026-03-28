<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Console\Security;

use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\OAuth\Server;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeOAuthKeyCommand extends AbstractCommand implements ConfigurationCommandInterface
{
    /**
     * Error code returned when unable to renew key.
     *
     * @var int
     */
    public const ERROR_UNABLE_TO_RENEW_KEY = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName('security:change_oauth_key');
        $this->setDescription(__('(Re)generate OAuth keys'));
        $this->setHelp(__('This command will regenerate the OAuth keys. All existing access tokens will be invalidated. This only generates missing keys unless the --force option is used.'));
        $this->addOption('force', 'f', InputOption::VALUE_NONE, __('Force the regeneration of OAuth keys even if they already exist.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        if (!$force && Server::checkKeys()) {
            $output->writeln('<comment>' . __('OAuth keys already exist. Use --force option to regenerate them.') . '</comment>', OutputInterface::VERBOSITY_QUIET);
            return 0;
        }

        $this->askForConfirmation();

        if (!Server::generateKeys($force)) {
            $output->writeln('<error>' . __('Unable to generate OAuth keys.') . '</error>', OutputInterface::VERBOSITY_QUIET);

            return self::ERROR_UNABLE_TO_RENEW_KEY;
        }

        $this->output->write(PHP_EOL);
        $output->writeln('<info>' . __('OAuth keys have been successfully generated.') . '</info>');
        return 0;
    }

    public function getConfigurationFilesToUpdate(InputInterface $input): array
    {
        return ['oauth.pem', 'oauth.pub'];
    }
}
