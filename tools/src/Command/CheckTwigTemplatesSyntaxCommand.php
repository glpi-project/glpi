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

namespace Glpi\Tools\Command;

use FilesystemIterator;
use Glpi\Application\View\TemplateRenderer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTwigTemplatesSyntaxCommand extends Command
{
    /**
     * Error code returned when some templates have invalid yntax.
     *
     * @var integer
     */
    const ERROR_INVALID_TEMPLATES = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName(self::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $environment = TemplateRenderer::getInstance()->getEnvironment();

        $error_messages = [];

        $tpl_dir = realpath(GLPI_ROOT . '/templates');
        $tpl_files_iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(GLPI_ROOT . '/templates', FilesystemIterator::UNIX_PATHS)
            ),
            '/\.twig$/i'
        );
       /* @var \SplFileInfo $tpl_file */
        foreach ($tpl_files_iterator as $tpl_file) {
            $output->writeln(
                sprintf('<comment>Parsing %s...</comment>', $tpl_file->getPathname()),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $tpl_path = str_replace($tpl_dir . '/', '', $tpl_file->getPathname());
            $source = $environment->getLoader()->getSourceContext($tpl_path);
            try {
                 $token_stream = $environment->tokenize($source);
                 $environment->parse($token_stream);
            } catch (\Twig\Error\Error $e) {
                $error_messages[] = sprintf(
                    '"%s" in template "%s" at line %s',
                    $e->getRawMessage(),
                    $source->getPath(),
                    $e->getTemplateLine()
                );
            }
        }

        if (count($error_messages) > 0) {
            $output->writeln('<comment>Error found while parsing Twig templates:</comment>', OutputInterface::VERBOSITY_QUIET);
            foreach ($error_messages as $msg) {
                $output->writeln('<error>‣ ' . $msg . '</error>', OutputInterface::VERBOSITY_QUIET);
            }
            return self::ERROR_INVALID_TEMPLATES;
        }

        $output->writeln('<info>No error found while parsing Twig templates.</info>', OutputInterface::VERBOSITY_QUIET);
        return 0; // Success
    }
}
