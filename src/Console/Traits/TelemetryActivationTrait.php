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

namespace Glpi\Console\Traits;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Telemetry;

trait TelemetryActivationTrait
{
    /**
     * Register options related to Telemetry service enablement.
     *
     * @param InputDefinition $definition
     *
     * @return void
     */
    protected function registerTelemetryActivationOptions(InputDefinition $definition): void
    {
        $definition->addOption(
            new InputOption(
                'enable-telemetry',
                null,
                InputOption::VALUE_NONE,
                sprintf(__('Allow usage statistics sending to Telemetry service (%s)'), GLPI_TELEMETRY_URI)
            )
        );

        $definition->addOption(
            new InputOption(
                'no-telemetry',
                null,
                InputOption::VALUE_NONE,
                sprintf(__('Disallow usage statistics sending to Telemetry service (%s)'), GLPI_TELEMETRY_URI)
            )
        );
    }

    /**
     * Handle telemetry service enablement, depending on
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function handTelemetryActivation(InputInterface $input, OutputInterface $output): void
    {

        $is_already_enabled = Telemetry::isEnabled();

        $disable_telemetry = false;
        $enable_telemetry  = false;

       // Handle Telemetry service status
        if (
            !$input->getOption('no-telemetry') && !$input->getOption('enable-telemetry')
            && !$is_already_enabled && !$input->getOption('no-interaction')
        ) {
           // Ask user its consent if no related option was provided (unless service is already active)
            $output->writeln(
                [
                    '<comment>' . __('We need your help to improve GLPI and the plugins ecosystem!') . '</comment>',
                    '<comment>' . __('Since GLPI 9.2, we’ve introduced a new statistics feature called “Telemetry”, that anonymously with your permission, sends data to our telemetry website.') . '</comment>',
                    '<comment>' . __('Once sent, usage statistics are aggregated and made available to a broad range of GLPI developers.') . '</comment>',
                    '<comment>' . __('Let us know your usage to improve future versions of GLPI and its plugins!') . '</comment>',
                ],
                OutputInterface::VERBOSITY_QUIET
            );

            $question_helper = new QuestionHelper();
            $enable_telemetry = $question_helper->ask(
                $input,
                $output,
                new ConfirmationQuestion(__('Do you want to send "usage statistics"?') . ' [Yes/no]', true)
            );
        } else if ($input->getOption('no-telemetry')) {
            $disable_telemetry = true;
        } else if ($input->getOption('enable-telemetry')) {
            $enable_telemetry = true;
        }

        if (!$is_already_enabled && $enable_telemetry) {
            Telemetry::enable();
        } else if ($is_already_enabled && $disable_telemetry) {
            Telemetry::disable();
        }
    }
}
