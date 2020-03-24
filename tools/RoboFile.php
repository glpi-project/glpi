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

use Robo\Result;
use Robo\ResultData;
use Robo\Task\Assets\Minify;
use Robo\Task\Base\Exec;
use Robo\Task\Composer\Update as ComposerUpdate;
use Robo\Task\Filesystem\CleanDir;
use Robo\Task\Filesystem\CopyDir;
use Robo\Task\Filesystem\DeleteDir;
use Robo\Task\Filesystem\FilesystemStack;
use Symfony\Component\Finder\Finder;

class RoboFile extends \Robo\Tasks {

   /**
    * Minify all
    *
    * @return void
    */
   public function minify() {
      return $this->doMinify(realpath(dirname(__DIR__)));
   }

   /**
    * Minify CSS stylesheets
    *
    * @return void
    */
   public function minifyCSS() {
      return $this->doMinify(realpath(dirname(__DIR__)), false, true);
   }

   /**
    * Minify JavaScript files
    *
    * @return void
    */
   public function minifyJS() {
      return $this->doMinify(realpath(dirname(__DIR__)), true, false);
   }

   /**
    * Build GLPI application from source code.
    *
    * @param string $src      Source path (absolute or relative to "tools" directory), defaults to parent dir.
    * @param string $dest     Destination path (absolute or relative to "tools" directory).
    * @param array  $options  Task options.
    */
   public function buildApp($src = null, $dest = '/tmp/glpi', array $options = ['format' => 'string']) {
      $task_collection = $this->collectionBuilder();

      $root_dir = realpath($src === null ? dirname(__DIR__) : $src);
      $work_dir = $task_collection->workDir($dest);

      // Check/prepare output dir
      if (file_exists($dest) && realpath($dest) === $root_dir) {
         return new Result(
            $task_collection,
            ResultData::EXITCODE_ERROR,
            'Destination directory must be different from source'
         );
      } else if (file_exists($dest)) {
         $task_collection->addTask(new CleanDir($dest));
      }

      // Copy source files
      $copy_task = new CopyDir([$root_dir => $work_dir]);
      $task_collection->addTask($copy_task);

      // Install dependencies
      $composer_options = '--ignore-platform-reqs --prefer-dist --no-progress';
      $deps_install_task = new Exec(
         sprintf(
            'php bin/console dependencies install --mode=production --composer-options="%s"',
            $composer_options
         )
      );
      $deps_install_task->dir($work_dir);
      $task_collection->addTask($deps_install_task);

      // Minify CSS/JS files
      // File list can be populated only after execution of previous tasks (dependencies install).
      // So list of files will be populated by a defered code execution
      // that will populate a sub task list which will be executed next to it.
      $min_task_collection = $this->collectionBuilder();
      $task_collection->addCode(
         function() use ($work_dir, $min_task_collection) {
            $files_finder = $this->getMinifiableFilesFinder($work_dir);
            foreach ($files_finder->getIterator() as $file) {
               $min_task_collection->addTask(new Minify($file->getRealPath()));
            }
         }
      );
      $task_collection->addTask($min_task_collection);

      // Compile SCSS
      $scss_compile_task = new Exec('php bin/console build:compile_scss');
      $scss_compile_task->dir($work_dir);
      $task_collection->addTask($scss_compile_task);

      // Compile locale files
      // File list can be populated only after execution of previous tasks (checkout).
      // So list of files will be populated by a defered code execution
      // that will populate a sub task list which will be executed next to it.
      $mo_task_collection = $this->collectionBuilder();
      $task_collection->addCode(
         function() use ($work_dir, $mo_task_collection) {
            $files_finder = new Finder();
            $files_finder->files()->name('*.po')->in($work_dir . '/locales')->sortByName();
            foreach ($files_finder->getIterator() as $file) {
               $mo_task_collection->addTask(
                  new Exec(
                     sprintf(
                        'msgfmt %s -o %s',
                        $file->getRealPath(),
                        preg_replace('/.po$/', '.mo', $file->getRealPath())
                     )
                  )
               );
            }
         }
      );
      $task_collection->addTask($mo_task_collection);

      // Remove PHP dev dependencies
      $composer_update_task = new ComposerUpdate();
      $composer_update_task->dir($work_dir)
         ->option('ignore-platform-reqs')
         ->option('no-dev')
         ->arg('nothing');
      $task_collection->addTask($composer_update_task);

      // Clean useless directories
      $clean_dir_task_collection = $this->collectionBuilder();
      $task_collection->addCode(
         function() use ($work_dir, $clean_dir_task_collection) {
            $directories_to_clean = [];

            $core_directories_finder = new Finder();
            $core_directories_finder->directories()
               ->ignoreDotFiles(false)
               ->ignoreVCS(false)
               ->path(
                  [
                     '/^\.[^\/]+$/', // all hidden directories
                     '/^files\/[^\/]+\/[^\/]*$/', // all user subdirectories
                     '/^node_modules$/',
                     '/^plugins\/[^\/]*$/', // all plugins
                     '/^tests$/',
                     '/^tools$/',
                     '/^vendor\/bin$/',
                  ]
               )
               ->in($work_dir)
               ->sortByName();
            foreach ($core_directories_finder->getIterator() as $dir) {
               $directories_to_clean[] = $dir->getRealPath();
            }

            $vendor_directories_finder = new Finder();
            $vendor_directories_finder->directories()
               ->path(
                  [
                     '/\/docs?$/',
                     '/\/examples?$/',
                     '/\/tests?$/',
                  ]
               )
               ->in($work_dir . '/vendor/**')
               ->sortByName();
            foreach ($vendor_directories_finder->getIterator() as $dir) {
               $directories_to_clean[] = $dir->getRealPath();
            }

            $clean_dir_task_collection->addTask(new DeleteDir($directories_to_clean));
         }
      );
      $task_collection->addTask($clean_dir_task_collection);

      // Clean useless files (do it ater dirs, to fasten cleanup)
      $clean_files_task_collection = $this->collectionBuilder();
      $task_collection->addCode(
         function() use ($work_dir, $clean_files_task_collection) {
            $files_to_clean = [];

            $core_files_finder = new Finder();
            $core_files_finder->files()
               ->ignoreDotFiles(false)
               ->ignoreVCS(false)
               ->path(
                  [
                     '/^\.[^\/]+$/', // all hidden files (except .htaccess, see notPath())
                     'CONTRIBUTING.md',
                     'ISSUE_TEMPLATE.md',
                     'PULL_REQUEST_TEMPLATE.md',
                     'SECURITY.md',
                     'composer.json',
                     'composer.lock',
                     'package.json',
                     'package-lock.json',
                     'webpack.config.js',
                     '/^config\/.*/', // all config files
                     '/^files\/[^\/]+\/.*/', // all user files
                     '/^pics\/.*\.eps$/i',
                  ]
               )
               ->notPath(
                  [
                     '.htaccess',
                  ]
               )
               ->in($work_dir)
               ->sortByName();
            foreach ($core_files_finder->getIterator() as $file) {
               $files_to_clean[] = $file->getRealPath();
            }

            $vendor_files_finder = new Finder();
            $vendor_files_finder->files()
               ->ignoreDotFiles(false)
               ->ignoreVCS(false)
               ->path(
                  [
                     '.gitignore',
                     'build.properties',
                     'build.xml',
                     '/^changelog\.md$/i',
                     'composer.json',
                     'composer.lock',
                     'phpunit.xml.dist',
                     '/^readme\.md$/i',
                  ]
               )
               ->in($work_dir . '/vendor/**')
               ->sortByName();
            foreach ($vendor_files_finder->getIterator() as $file) {
               $files_to_clean[] = $file->getRealPath();
            }

            $fs_remove_task = new FilesystemStack();
            $fs_remove_task->remove($files_to_clean);
            $clean_files_task_collection->addTask($fs_remove_task);
         }
      );
      $task_collection->addTask($clean_files_task_collection);

      $task_collection->run();

      return $this;
   }

   /**
    * Run minify task on given directory.
    *
    * @param string  $dir
    * @param boolean $minify_js
    * @param boolean $minify_css
    *
    * @return RoboFile
    */
   private function doMinify($dir, $minify_js = true, $minify_css = true) {
      $root_dir = realpath(dirname(__DIR__));

      $min_task_collection = $this->collectionBuilder();
      $files_finder = $this->getMinifiableFilesFinder($root_dir, $minify_js, $minify_css);
      foreach ($files_finder->getIterator() as $file) {
         $min_task_collection->addTask(new Minify($file->getRealPath()));
      }

      $min_task_collection->run();

      return $this;
   }

   /**
    * Returns minifiable files finder.
    *
    * @param string  $dir
    * @param boolean $include_js
    * @param boolean $include_css
    *
    * @return Finder
    */
   private function getMinifiableFilesFinder($dir, $include_js = true, $include_css = true) {
      $names = [];
      if ($include_js) {
         $names[] = '*.js';
      }
      if ($include_css) {
         $names[] = '*.css';
      }
      $files_finder = new Finder();
      $files_finder->files()
         ->name($names)
         ->notName(['*.min.css', '*.min.js'])
         ->in(
            [
               $dir . '/css',
               $dir . '/js',
               $dir . '/lib',
            ]
         )
         ->sortByName();

      return $files_finder;
   }
}
