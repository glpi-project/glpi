<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Core and plugins command loader.
 *
 * @since 9.4.0
 */
class CommandLoader implements CommandLoaderInterface {

   /**
    * Root directory path to search on.
    * @var string
    */
   private $rootdir;

   /**
    * Found commands.
    *
    * @var Command[]
    */
   private $commands = [];

   /**
    * @param string $rootdir Root directory path of application.
    */
   public function __construct($include_plugins = true, $rootdir = GLPI_ROOT) {

      $this->rootdir = $rootdir;

      $this->findCoreCommands();
      $this->findToolsCommands();

      if ($include_plugins) {
         $this->findPluginCommands();
      }
   }

   public function get($name) {
      if (!array_key_exists($name, $this->commands)) {
         throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
      }

      return $this->commands[$name];
   }

   public function has($name) {
      return array_key_exists($name, $this->commands);
   }

   public function getNames() {
      return array_keys($this->commands);
   }

   /**
    * Find all core commands.
    *
    * return void
    */
   private function findCoreCommands() {

      $basedir = $this->rootdir . DIRECTORY_SEPARATOR . 'inc';

      $core_files = new RecursiveIteratorIterator(
         new RecursiveDirectoryIterator($basedir),
         RecursiveIteratorIterator::SELF_FIRST
      );
      /** @var SplFileInfo $file */
      foreach ($core_files as $file) {
         if (!$file->isReadable() || !$file->isFile()) {
            continue;
         }

         $is_in_namespace = strpos(
            $this->getRelativePath($basedir, $file->getPathname()),
            DIRECTORY_SEPARATOR
         ) > 0;

         $class = $this->getCommandClassnameFromFile(
            $file,
            $basedir,
            $is_in_namespace ? 'glpi\\' : null
         );

         if (null === $class) {
            continue;
         }

         $this->registerCommand(new $class());
      }
   }

   /**
    * Find all plugins (active or not) commands.
    *
    * @return void
    */
   private function findPluginCommands() {

      $basedir = $this->rootdir . DIRECTORY_SEPARATOR . 'plugins';

      $plugins_directories = new DirectoryIterator($basedir);
      /** @var SplFileInfo $plugin_directory */
      foreach ($plugins_directories as $plugin_directory) {
         if (in_array($plugin_directory->getFilename(), ['.', '..'])) {
            continue;
         }

         $plugin_basedir = $plugin_directory->getPathname() . DIRECTORY_SEPARATOR . 'inc';
         if (!is_readable($plugin_basedir) || !is_dir($plugin_basedir)) {
            continue;
         }

         $plugin_files = new DirectoryIterator($plugin_basedir);
         /** @var SplFileInfo $file */
         foreach ($plugin_files as $file) {
            if (!$file->isReadable() || !$file->isFile()) {
               continue;
            }

            $class = $this->getCommandClassnameFromFile(
               $file,
               $plugin_basedir,
               'plugin' . $plugin_directory->getFilename()
            );

            if (null === $class) {
               continue;
            }

            $this->registerCommand(new $class());
         }
      }
   }

   /**
    * Find all "tools" commands.
    *
    * return void
    */
   private function findToolsCommands() {

      $basedir = $this->rootdir . DIRECTORY_SEPARATOR . 'tools';

      if (!is_dir($basedir)) {
         return;
      }

      $tools_files = new DirectoryIterator($basedir);
      /** @var SplFileInfo $file */
      foreach ($tools_files as $file) {
         if (!$file->isReadable() || !$file->isFile()) {
            continue;
         }

         $class = $this->getCommandClassnameFromFile(
            $file,
            $basedir
         );

         if (null === $class) {
            continue;
         }

         $this->registerCommand(new $class());
      }
   }

   /**
    * Register a command on self.
    *
    * @param Command $command
    *
    * @return void
    */
   private function registerCommand(Command $command) {

      $this->commands[$command->getName()] = $command;

      $aliases = $command->getAliases();
      foreach ($aliases as $alias) {
         $this->commands[$alias] = $command;
      }
   }

   /**
    * Return classname of command contained in file, if file contains one.
    *
    * @param SplFileInfo $file    File to inspect
    * @param string      $basedir Directory containing classes (eg GLPI_ROOT . '/inc')
    * @param string      $prefix  Prefix to add to classname (eg 'PluginExample')
    *
    * @return null|string
    */
   private function getCommandClassnameFromFile(SplFileInfo $file, $basedir, $prefix = null) {

      // Check if file is readable and contained classname finishes by "command"
      if (!$file->isReadable() || !$file->isFile()
         || !preg_match('/^(.*)command\.class\.php$/', $file->getFilename())) {
         return null;
      }

      // Extract expected classname from filename.
      // Classname will be lowercased, but it is ok for PHP.
      $classname = str_replace(
         ['.class.php', DIRECTORY_SEPARATOR],
         ['', '\\'],
         $this->getRelativePath($basedir, $file->getPathname())
      );
      if (null !== $prefix) {
         $classname = $prefix . $classname;
      }

      include_once($file->getPathname()); // Required as ReflectionClass will not use autoload

      $reflectionClass = new ReflectionClass($classname);
      if ($reflectionClass->isInstantiable() && $reflectionClass->isSubclassOf(Command::class)) {
         return $classname;
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
   private function getRelativePath($basedir, $filepath) {

      // Strip (multiple) ending directory separator to normalize input
      while (strrpos($basedir, DIRECTORY_SEPARATOR) == strlen($basedir) - 1) {
         $basedir = substr($basedir, 0, -1);
      }

      // Assume that filepath is prefixed by basedir
      // Cannot use realpath to normalize path as it will not work when using a virtual fs (unit tests)
      return str_replace($basedir . DIRECTORY_SEPARATOR, '', $filepath);
   }

}
