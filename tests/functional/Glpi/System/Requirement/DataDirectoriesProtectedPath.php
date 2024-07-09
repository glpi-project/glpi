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

namespace tests\units\Glpi\System\Requirement;

class DataDirectoriesProtectedPath extends \GLPITestCase
{
    public function testCheckOnSecuredDirs()
    {
        $tmp_dir = sys_get_temp_dir();

        $secure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        $secure_var_root_path = sprintf('%s/glpi_test_%08x', $tmp_dir, rand());
        $this->boolean(mkdir($secure_var_root_path))->isTrue();
        define($secure_var_root_constant, $secure_var_root_path);

        $secure_dir_constant1 = sprintf('GLPI_TEST_%08x', rand());
        $this->boolean(mkdir($secure_var_root_path . '/_cache'))->isTrue();
        define($secure_dir_constant1, $secure_var_root_path . '/_cache'); // Inside var root

        $secure_dir_constant2 = sprintf('GLPI_TEST_%08x', rand());
        $secure_dir_path2     = sprintf('%s/glpi_test_%08x', $tmp_dir, rand());
        $this->boolean(mkdir($secure_dir_path2))->isTrue();
        define($secure_dir_constant2, $secure_dir_path2); // Outside var root

        $this->newTestedInstance(
            [$secure_dir_constant1, $secure_dir_constant2],
            $secure_var_root_constant
        );

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
            ->isEqualTo(
                [
                    'GLPI data directories are located in a secured path.',
                ]
            );
    }

    public function testCheckOnUnsecureDirsWithUnsecureVarRoot()
    {
        $root_path = realpath(GLPI_ROOT);

        $unsecure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        $unsecure_var_root_path = $root_path . '/files';
        define($unsecure_var_root_constant, $unsecure_var_root_path);

        $unsecure_dir_constant1 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant1, $unsecure_var_root_path . '/_cache'); // Inside var root

        $unsecure_dir_constant2 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant2, $unsecure_var_root_path . '/_log'); // Inside var root

        $unsecure_dir_constant3 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant3, $root_path . '/config'); // Outside var root

        $this->newTestedInstance([$unsecure_dir_constant1, $unsecure_dir_constant2, $unsecure_dir_constant3], $unsecure_var_root_constant);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 sprintf('The following directories should be placed outside "%s":', $root_path),
                 sprintf('‣ "%s" ("%s")', $unsecure_var_root_path, $unsecure_var_root_constant),
                 // $unsecure_dir_constant1 and $unsecure_dir_constant2 are ignored as they are nested in var root
                 sprintf('‣ "%s/config" ("%s")', $root_path, $unsecure_dir_constant3),
                 sprintf('You can ignore this suggestion if your web server root directory is "%s/public".', $root_path),
             ]
         );
    }

    public function testCheckOnUnsecureDirsWithSecureVarRoot()
    {
        $root_path = realpath(GLPI_ROOT);

        $secure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        define($secure_var_root_constant, sys_get_temp_dir()); // tmp dir will always be existing and outside web root dir

        $unsecure_dir_constant1 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant1, $root_path . '/files/_cache'); // Outside var root

        $unsecure_dir_constant2 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant2, $root_path . '/files/_log'); // Outside var root

        $unsecure_dir_constant3 = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_dir_constant3, $root_path . '/config'); // Outside var root

        $this->newTestedInstance([$unsecure_dir_constant1, $unsecure_dir_constant2, $unsecure_dir_constant3], $secure_var_root_constant);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 sprintf('The following directories should be placed outside "%s":', $root_path),
                 sprintf('‣ "%s/files/_cache" ("%s")', $root_path, $unsecure_dir_constant1),
                 sprintf('‣ "%s/files/_log" ("%s")', $root_path, $unsecure_dir_constant2),
                 sprintf('‣ "%s/config" ("%s")', $root_path, $unsecure_dir_constant3),
                 sprintf('You can ignore this suggestion if your web server root directory is "%s/public".', $root_path),
             ]
         );
    }

    public function testCheckOnMissingDirs()
    {
        $root_path = realpath(GLPI_ROOT);
        $tmp_dir = sys_get_temp_dir();

        $secure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        define($secure_var_root_constant, $tmp_dir); // tmp dir will always be existing and outside web root dir

        $missing_dir_constant1 = sprintf('GLPI_TEST_%08x', rand());
        define($missing_dir_constant1, '/this/dir/not/exists'); // Outside var root

        $missing_dir_constant2 = sprintf('GLPI_TEST_%08x', rand());
        define($missing_dir_constant2, $tmp_dir . '/not/exists'); // Inside var root

        $this->newTestedInstance([$missing_dir_constant1, $missing_dir_constant2], $secure_var_root_constant);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 'The following directories do not exist and cannot be tested:',
                 sprintf('‣ "/this/dir/not/exists" ("%s")', $missing_dir_constant1),
                 sprintf('‣ "%s/not/exists" ("%s")', $tmp_dir, $missing_dir_constant2),
                 sprintf('You can ignore this suggestion if your web server root directory is "%s/public".', $root_path),
             ]
         );
    }

    public function testCheckWithAnotherRootPath()
    {
        $root_path = sys_get_temp_dir();

        $unsecure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        $unsecure_var_root_path = sprintf('%s/glpi_test_%08x', $root_path, rand());
        $this->boolean(mkdir($unsecure_var_root_path))->isTrue();
        define($unsecure_var_root_constant, $unsecure_var_root_path); // inside root dir

        $this->newTestedInstance([$unsecure_var_root_constant], $unsecure_var_root_constant, $root_path);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 sprintf('The following directories should be placed outside "%s":', $root_path),
                 sprintf('‣ "%s" ("%s")', $unsecure_var_root_path, $unsecure_var_root_constant),
                 sprintf('You can ignore this suggestion if your web server root directory is "%s/public".', $root_path),
             ]
         );
    }

    public function testCheckWithPathThatIsSameAsRoot()
    {
        $root_path = sys_get_temp_dir();

        $unsecure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        define($unsecure_var_root_constant, $root_path); // constant points to root dir itself, it cannot be safe

        $this->newTestedInstance([$unsecure_var_root_constant], $unsecure_var_root_constant, $root_path);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
            ->isEqualTo(
                [
                    sprintf('The following directories should be placed outside "%s":', $root_path),
                    sprintf('‣ "%s" ("%s")', $root_path, $unsecure_var_root_constant),
                    sprintf('You can ignore this suggestion if your web server root directory is "%s/public".', $root_path),
                ]
            );
    }

    public function testCheckWithSameDirectoryPrefix()
    {
        $tmp_dir = sys_get_temp_dir();

        $root_path = sprintf('%s/glpi_root_%08x', $tmp_dir, rand());
        $this->boolean(mkdir($root_path))->isTrue();

        $secure_var_root_constant = sprintf('GLPI_TEST_%08x', rand());
        // generate a directory on same level starting with root path (e.g. `/var/www/glpi_files` when root is `/var/www/glpi`)
        $secure_var_root_path = sprintf('%s_%08x', $root_path, rand());
        $this->boolean(mkdir($secure_var_root_path))->isTrue();
        define($secure_var_root_constant, $secure_var_root_path);

        $secure_dir_constant1 = sprintf('GLPI_TEST_%08x', rand());
        $this->boolean(mkdir($secure_var_root_path . '/_cache'))->isTrue();
        define($secure_dir_constant1, $secure_var_root_path . '/_cache'); // Inside var root

        $secure_dir_constant2 = sprintf('GLPI_TEST_%08x', rand());
        $secure_dir_path2     = sprintf('%s/glpi_test_%08x', $tmp_dir, rand());
        $this->boolean(mkdir($secure_dir_path2))->isTrue();
        define($secure_dir_constant2, $secure_dir_path2); // Outside var root

        $this->newTestedInstance([$secure_dir_constant1, $secure_dir_constant2], $secure_var_root_constant, $root_path);

        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
            ->isEqualTo(
                [
                    'GLPI data directories are located in a secured path.',
                ]
            );
    }
}
