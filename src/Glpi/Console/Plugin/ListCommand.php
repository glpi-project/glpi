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

namespace Glpi\Console\Plugin;

use Glpi\Console\AbstractCommand;
use Glpi\Marketplace\Controller;
use Plugin;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_encode;

class ListCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('plugin:list');
        $this->setDescription('List all plugins present in the plugins directory or from the marketplace');

        // Add option to change output format
        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'Output format (table or json)',
            'table'
        );

        // Add option to include the path to the plugins
        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_NONE,
            'Include the path to the plugins'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plug     = new Plugin();
        $pluglist = $plug->find([], "name, directory");
        $data = [];
        foreach ($pluglist as $plugin) {
            $record = [];
            $record['key'] = $plugin['directory'];
            $record['name'] = $plugin['name'];
            $record['version'] = $plugin['version'];
            $record['state'] = Plugin::getState($plug->isLoadable($plugin['directory']) ? $plugin['state'] : Plugin::TOBECLEANED);
            $is_marketplace = file_exists(GLPI_MARKETPLACE_DIR . "/" . $plugin['directory']);
            $record['install_method'] = $is_marketplace ? Controller::getTypeName() : __("Manually installed");
            if ($input->getOption('path')) {
                $dir_sep = DIRECTORY_SEPARATOR;
                $record['path'] = $is_marketplace ? GLPI_MARKETPLACE_DIR . $dir_sep . $plugin['directory'] : GLPI_ROOT . "{$dir_sep}plugins{$dir_sep}" . $plugin['directory'];
            }
            $data[] = $record;
        }
        $format = $input->getOption('format');

        if ($format === 'json') {
            $output->writeln(json_encode($data));
        } else {
            $table = new Table($output);
            $headers = [__('Plugin Key'), __('Name'), _n('Version', 'Versions', 1), __('Status'), __('Install method')];
            if ($input->getOption('path')) {
                $headers[] = __('Path');
            }
            $table->setHeaders($headers);
            $table->setRows($data);
            $table->render();
        }
        return 0;
    }
}
