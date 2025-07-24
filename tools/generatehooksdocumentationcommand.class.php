<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use Glpi\Plugin\Hooks;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateHooksDocumentationCommand extends AbstractCommand
{
    protected $requires_db = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_hooks_documentation');
        $this->setDescription('Generate plugin hooks documentation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hooks_rc = new ReflectionClass(Hooks::class);
        $hook_constants = $hooks_rc->getReflectionConstants();

        $hooks_docs = [];
        foreach ($hook_constants as $hook) {
            $comment = $hook->getDocComment();
            if ($comment) {
                // Remove the first and last line
                $comment = preg_replace('/^\/\*\*|\*\/$/m', '', $comment);
                // Remove the leading asterisks and whitespace from each line
                $comment = preg_replace('/^\s*\*\s?/m', '', $comment);

                // Convert lists to RST format
                // Add blank line between lines ending with ':' and the next line
                $comment = preg_replace('/(\w+:\s*)\n/', "$1\n\n", $comment);
                // Convert bullet points to RST format
                $comment = preg_replace('/^(\s*)-\s/m', '$1* ', $comment);
                // Add blank line between last asterisk list item and the next line if the next line isn't also a list item
                $comment = preg_replace('/(^\*\s.*(?:\n\*\s.*)*)(?=\n(?!\*\s)|\z)/m', "$1\n", $comment);


                // Replace PHPDoc links with the content and replace "self" with "Hooks" ({@link self::AUTO_GET_RULE_CRITERIA} => "Hooks::AUTO_GET_RULE_CRITERIA")
                $comment = preg_replace_callback(
                    '/\{@link\s+([^\s]+)\s*\}/',
                    static function ($matches) {
                        $link = $matches[1];
                        if (str_starts_with($link, 'self::')) {
                            return str_replace('self::', 'Hooks::', $link);
                        }
                        return $link;
                    },
                    $comment
                );
                // Convert @since to plain text
                $comment = preg_replace('/^\s*@since\s+([^\s]+)\s*$/m', 'Added in version $1', $comment);
                // Convert @link tags with http(s) URLs to RST format
                $comment = preg_replace_callback(
                    '/^\s*@link\s+([^\s]+)(?:\s+(.*))?$/m',
                    static function ($matches) {
                        $url = $matches[1];
                        $text = !empty($matches[2]) ? trim($matches[2]) : $url;
                        return sprintf('`%s <%s>`_', $text, $url);
                    },
                    $comment
                );
                // Convert @deprecated tags to RST warnings
                $comment = preg_replace(
                    '/^\s*@deprecated\s+(.*)$/m',
                    '.. warning::\nDeprecated: $1\n',
                    $comment
                );

                // Remove all remaining PHPDoc tag lines
                $comment = preg_replace('/^\s*@[a-zA-Z0-9_]+.*$/m', '', $comment);
            } else {
                $comment = '';
            }

            $hooks_docs[] = [
                'name' => $hook->getName(),
                'description' => $comment,
            ];
        }

        $output->writeln('Hooks');
        $output->writeln('#####');
        foreach ($hooks_docs as $hook) {
            $output->writeln($hook['name']);
            $output->writeln(str_repeat('*', strlen($hook['name'])) . "\n");
            $output->writeln($hook['description'] . "\n");
        }

        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);

        return 0;
    }
}
