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

namespace Glpi\Console\Plugin;

use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Glpi\Marketplace\Controller;
use Plugin;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Toolbox;

class ListCommand extends AbstractCommand implements ForceNoPluginsOptionCommandInterface
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:plugin:list');
        $this->setAliases(['plugin:list']);
        $this->setDescription('List all plugins present in the plugins directory or from the marketplace');

        // Add option to change output format
        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            'Output format (table or json)',
            'table'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plug     = new Plugin();
        $pluglist = $plug->find([], "name, directory");
        $data = [];
        foreach ($pluglist as $plugin) {
            $record = [];
            $record['name'] = Toolbox::stripTags($plugin['name']);
            $record['version'] = Toolbox::stripTags($plugin['version']);
            $record['state'] = Plugin::getState($plug->isLoadable($plugin['directory']) ? $plugin['state'] : Plugin::TOBECLEANED);
            $record['install_method'] = file_exists(GLPI_MARKETPLACE_DIR . "/" . $plugin['directory']) ? Controller::getTypeName() : __("Manually installed");
            $data[] = $record;
        }
        $format = $input->getOption('format');

        if ($format === 'json') {
            $output->writeln(json_encode($data));
        } else {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Version', 'State', 'Install method']);
            $table->setRows($data);
            $table->render();
        }
        return 0;
    }

    public function getNoPluginsOptionValue()
    {
        return true;
    }
}
