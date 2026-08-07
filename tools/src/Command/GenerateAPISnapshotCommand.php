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

namespace Glpi\Tools\Command;

use Glpi\Api\HL\OpenAPIGenerator;
use Glpi\Api\HL\Router;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Exception\EarlyExitException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GenerateAPISnapshotCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_api_snapshot');
        $this->setDescription('Updates a snapshot used for API version tests');
        $this->addArgument('version', InputArgument::REQUIRED, 'Version of the API to update the snapshot for');
    }

    /**
     * @throws EarlyExitException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $question_helper = new QuestionHelper();
        $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Did you run the HLAPI tests to ensure the schema changes were expected and comply with the version contract? [yes/No]')
        );
        if (!$run) {
            throw new EarlyExitException('<comment>' . __('Aborted.') . '</comment>', 0);
        }

        $selected_version = $input->getArgument('version');
        $supported_versions = array_filter(array_column(Router::getAPIVersions(), 'version'), static fn($version) => !str_starts_with($version, '1.0'));
        if (str_starts_with($selected_version, 'v')) {
            $selected_version = substr($selected_version, 1);
        }
        if ($selected_version === 'all') {
            $versions_to_generate = $supported_versions;
        } else {
            if (!in_array($selected_version, $supported_versions, true)) {
                throw new EarlyExitException("<error>Version $selected_version is not a supported API version. Supported versions are: " . implode(', ', $supported_versions) . "</error>", 1);
            }
            $versions_to_generate = [$selected_version];
        }

        foreach ($versions_to_generate as $version) {
            $output->writeln("<info>Generating API snapshot for version $version</info>");
            $oapi = new OpenAPIGenerator(Router::getInstance(), $version);

            $SNAPSHOT_DIR = GLPI_ROOT . "/tests/fixtures/hlapi/snapshots/{$version}";
            $PATHS_FILE = $SNAPSHOT_DIR . '/paths.json';
            $COMPONENTS_FILE = $SNAPSHOT_DIR . '/components.json';
            if (!is_dir($SNAPSHOT_DIR) && !mkdir($SNAPSHOT_DIR, 0o755, true) && !is_dir($SNAPSHOT_DIR)) {
                throw new \RuntimeException(sprintf('Directory "%s" was missing and not able to be created', $SNAPSHOT_DIR));
            }

            $paths = $oapi->generatePathSnapshot();
            file_put_contents($PATHS_FILE, json_encode($paths, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            unset($path_item, $paths);

            $schema_components = $oapi->generateComponentsSnapshot();
            file_put_contents($COMPONENTS_FILE, json_encode(['schemas' => $schema_components], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            unset($component_schema, $schema_components);
        }

        return 0;
    }
}
