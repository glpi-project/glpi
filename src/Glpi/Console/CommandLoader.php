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

namespace Glpi\Console;

use DirectoryIterator;
use Glpi\Application\Environment;
use Glpi\Kernel\Kernel;
use Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

use function Safe\preg_match;

/**
 * Core and plugins command loader.
 *
 * @since 9.4.0
 */
class CommandLoader implements CommandLoaderInterface
{
    /**
     * Indicates if plugin commands should be included.
     *
     * @var bool
     */
    private $include_plugins;

    /**
     * Root directory path to search on.
     * @var string
     */
    private $rootdir;

    /**
     * Found commands.
     *
     * @var Command[]|null
     */
    private $commands = null;

    /**
     * Plugins info services
     *
     * @var Plugin|null
     */
    private $plugin = null;

    /**
     * @param bool          $include_plugins
     * @param string        $rootdir         Root directory path of application.
     * @param Plugin|null   $plugin          Needed for units test as we lack DI.
     */
    public function __construct($include_plugins = true, $rootdir = GLPI_ROOT, ?Plugin $plugin = null)
    {
        $this->include_plugins = $include_plugins;
        $this->rootdir         = $rootdir;
        $this->plugin          = $plugin;
    }

    public function get(string $name): Command
    {
        $commands = $this->getCommands();

        if (!array_key_exists($name, $commands)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        return $commands[$name];
    }

    public function has(string $name): bool
    {
        $commands = $this->getCommands();

        return array_key_exists($name, $commands);
    }

    public function getNames(): array
    {
        $commands = $this->getCommands();

        return array_keys($commands);
    }

    /**
     * Indicates if plugin commands should be included.
     *
     * @param bool $include_plugins
     *
     * @return void
     */
    public function setIncludePlugins(bool $include_plugins)
    {
        $this->include_plugins = $include_plugins;

        $this->commands = null; // Reset registered command list to force (un)registration of plugins commands
    }

    /**
     * Get registered commands.
     *
     * @return Command[]
     */
    private function getCommands()
    {
        if ($this->commands === null) {
            $this->findCoreCommands();
            $this->findToolsCommands();

            if ($this->include_plugins) {
                $this->findPluginCommands();
            }

            $this->findSymfonyCommands();
        }

        return $this->commands;
    }

    /**
     * Find all core commands.
     *
     * return void
     */
    private function findCoreCommands()
    {

        $basedir = $this->rootdir . DIRECTORY_SEPARATOR . 'src';

        $core_files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basedir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var SplFileInfo $file */
        foreach ($core_files as $file) {
            if (!$file->isReadable() || !$file->isFile()) {
                continue;
            }

            $command = $this->getCommandFromFile(
                $file,
                $basedir,
                ['', NS_GLPI]
            );

            if (null === $command) {
                continue;
            }

            $names = [$command->getName(), ...$command->getAliases()];
            foreach ($names as $name) {
                foreach (['tools', 'plugins'] as $protected_namespaces) {
                    if (preg_match('/^' . $protected_namespaces . ':/', $name) === 1) {
                        // Do not register commands that are using a protected pattern
                        trigger_error(
                            sprintf(
                                'GLPI command `%s` must be moved outside the protected `%s` namespace.',
                                $name,
                                $protected_namespaces
                            ),
                            E_USER_WARNING
                        );
                        continue 2;
                    }
                }
            }

            $this->registerCommand($command);
        }
    }

    /**
     * Find all plugins (active or not) commands.
     *
     * @return void
     */
    private function findPluginCommands()
    {

        if ($this->plugin === null) {
            $this->plugin = new Plugin();
        }

        if ($this->plugin->isPluginsExecutionSuspended()) {
            return;
        }

        foreach ($this->plugin->getPlugins() as $plugin_key) {
            $plugin_directory = $this->plugin->getPhpDir($plugin_key);

            foreach (['inc', 'src'] as $source_dir) {
                $plugin_basedir = $plugin_directory . DIRECTORY_SEPARATOR . $source_dir;
                if (!is_readable($plugin_basedir) || !is_dir($plugin_basedir)) {
                    continue;
                }

                $plugin_files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($plugin_basedir),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                /** @var SplFileInfo $file */
                foreach ($plugin_files as $file) {
                    if (!$file->isReadable() || !$file->isFile()) {
                        continue;
                    }

                    // Prefixes can be:
                    // - GlpiPlugin\Myplugin if class is namespaced
                    // - PluginMyplugin if class is not namespaced nor PSR-4 compliant
                    // - empty if class is not namespaced but PSR-4 compliant
                    $command = $this->getCommandFromFile(
                        $file,
                        $plugin_basedir,
                        [
                            NS_PLUG . ucfirst($plugin_key) . '\\',
                            'Plugin' . ucfirst($plugin_key),
                            '',
                        ]
                    );

                    if (null === $command) {
                        continue;
                    }

                    $expected_pattern = '/^'
                        . 'plugins:'            // starts with `plugins:` prefix
                        . $plugin_key       // followed by plugin key (directory name)
                        . '(:[^:]+)+'           // followed by, at least, another command name part
                        . '$/';
                    $names = [$command->getName(), ...$command->getAliases()];
                    foreach ($names as $name) {
                        if (preg_match($expected_pattern, $name) !== 1) {
                            // Do not register commands that are not using the expected prefix
                            trigger_error(
                                sprintf(
                                    'Plugin command `%s` must be moved in the `plugins:%s` namespace.',
                                    $name,
                                    $plugin_key
                                ),
                                E_USER_WARNING
                            );
                            continue 2;
                        }
                    }

                    $this->registerCommand($command);
                }
            }
        }
    }

    /**
     * Find all "tools" commands.
     *
     * return void
     */
    private function findToolsCommands()
    {

        $basedir = $this->rootdir . DIRECTORY_SEPARATOR . 'tools';

        if (!is_dir($basedir)) {
            return;
        }

        $tools_files = new DirectoryIterator($basedir);
        foreach ($tools_files as $file) {
            /** @var DirectoryIterator $file */
            if (!$file->isReadable() || !$file->isFile()) {
                continue;
            }

            $command = $this->getCommandFromFile(
                $file,
                $basedir
            );

            if (null === $command) {
                continue;
            }

            $expected_pattern = '/^'
                . 'tools'               // starts with `tools:` prefix
                . '(:[^:]+)+'           // followed by, at least, another command name part
                . '$/';
            $names = [$command->getName(), ...$command->getAliases()];
            foreach ($names as $name) {
                if (preg_match($expected_pattern, $name) !== 1) {
                    // Do not register commands that are not using the expected prefix
                    trigger_error(
                        sprintf(
                            'Tools command `%s` must be moved in the `tools` namespace.',
                            $name
                        ),
                        E_USER_WARNING
                    );
                    continue 2;
                }
            }

            $this->registerCommand($command);
        }
    }

    private function findSymfonyCommands(): void
    {
        /** @var Kernel|null $kernel */
        global $kernel;

        if (!$kernel instanceof Kernel) {
            return;
        }

        if (!Environment::get()->shouldEnableExtraDevAndDebugTools()) {
            return;
        }

        /** @var CommandLoaderInterface $base_loader */
        $base_loader = $kernel->getContainer()->get('console.command_loader');

        foreach ($base_loader->getNames() as $name) {
            $command = $base_loader->get($name);
            $command->setName('symfony:' . $name);
            $this->registerCommand($command);
        }
    }

    /**
     * Register a command on self.
     *
     * @param Command $command
     *
     * @return void
     */
    private function registerCommand(Command $command)
    {

        $this->commands[$command->getName()] = $command;

        $aliases = $command->getAliases();
        foreach ($aliases as $alias) {
            $this->commands[$alias] = $command;
        }
    }

    /**
     * Return class instance of command contained in file, if file contains one.
     *
     * @param SplFileInfo $file      File to inspect
     * @param string      $basedir   Directory containing classes (eg GLPI_ROOT . '/inc')
     * @param array       $prefixes  Possible prefixes to add to classname (eg 'PluginExample', 'GlpiPlugin\Example')
     *
     * @return null|Command
     */
    private function getCommandFromFile(SplFileInfo $file, $basedir, array $prefixes = []): ?Command
    {

        // Check if file is readable
        if (!$file->isReadable() || !$file->isFile()) {
            return null;
        }

        // Check if is a class file and finishes by "command"
        if (
            !preg_match('/^(.*)command\.class\.php$/', $file->getFilename())
            && !preg_match('/^(.*)Command\.php$/', $file->getFilename())
        ) {
            return null;
        }

        // Classname will be lowercased, but it is ok for PHP.
        $classname = str_replace(
            ['.class.php', '.php', DIRECTORY_SEPARATOR],
            ['', '', '\\'],
            $this->getRelativePath($basedir, $file->getPathname())
        );

        if ($prefixes === []) {
            $prefixes = [''];
        }
        foreach ($prefixes as $prefix) {
            $classname_to_check = $prefix . $classname;

            include_once($file->getPathname()); // Required as ReflectionClass will not use autoload

            if (!class_exists($classname_to_check, false)) {
                // Try with other prefixes.
                // Needed as a file located in root source dir of Glpi can be either namespaced either not.
                continue;
            }
            if (!is_a($classname_to_check, Command::class, true)) {
                // Not a console command.
                continue;
            }

            $reflectionClass = new ReflectionClass($classname_to_check);
            if ($reflectionClass->isInstantiable()) {
                return new $classname_to_check();
            }
        }

        return null;
    }

    /**
     * Returns path relative to basedir.
     *
     * @param string $basedir
     * @param string $filepath
     * @return string
     */
    private function getRelativePath($basedir, $filepath)
    {

        // Strip (multiple) ending directory separator to normalize input
        while (strrpos($basedir, DIRECTORY_SEPARATOR) == strlen($basedir) - 1) {
            $basedir = substr($basedir, 0, -1);
        }

        // Assume that filepath is prefixed by basedir
        // Cannot use realpath to normalize path as it will not work when using a virtual fs (unit tests)
        return str_replace($basedir . DIRECTORY_SEPARATOR, '', $filepath);
    }
}
