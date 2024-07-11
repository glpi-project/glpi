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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;

class LogsWriteAccess extends \GLPITestCase
{
    public function testCheckOnExistingWritableDir()
    {

        vfsStream::setup('root', 0777, []);

        $logger = new Logger('test_log');
        $logger->pushHandler(new StreamHandler(vfsStream::url('root/test.log')));

        $this->newTestedInstance($logger);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['The log file has been created successfully.']);
    }

    public function testCheckOnExistingProtectedDir()
    {

        vfsStream::setup('root', 0555, []);

        $logger = new Logger('test_log');
        $logger->pushHandler(new StreamHandler(vfsStream::url('root/test.log')));

        $this->newTestedInstance($logger);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['The log file could not be created in ' . GLPI_LOG_DIR . '.']);
    }
}
