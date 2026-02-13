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

namespace tests\units\Glpi\System\Requirement;

use Glpi\System\Requirement\LogsWriteAccess;
use Glpi\Tests\GLPITestCase;
use org\bovigo\vfs\vfsStream;

class LogsWriteAccessTest extends GLPITestCase
{
    public function testCheckOnExistingWritableDir()
    {
        vfsStream::setup('root', 0o777, ['php-errors.log' => '']);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Write access to log files has been validated.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnExistingProtectedDir()
    {
        vfsStream::setup('root', 0o555, []);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The log directory ' . vfsStream::url('root') . ' is not writable.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnExistingNonWritableLogFile()
    {
        $structure = vfsStream::setup('root', 0o777, ['php-errors.log' => '']);
        $structure->getChild('php-errors.log')->chmod(0o444);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The log file ' . vfsStream::url('root/php-errors.log') . ' is not writable.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingLogFileInWritableDir()
    {
        vfsStream::setup('root', 0o777, []);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Write access to log files has been validated.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckAllLogFilesWritable()
    {
        vfsStream::setup('root', 0o777, [
            'php-errors.log'    => '',
            'access-errors.log' => '',
            'api.log'           => '',
            'cron.log'          => '',
            'event.log'         => '',
            'mail-error.log'    => '',
            'mailgate.log'      => '',
            'webhook.log'       => '',
        ]);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Write access to log files has been validated.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithOneUnwritableFile()
    {
        $structure = vfsStream::setup('root', 0o777, [
            'php-errors.log'    => '',
            'access-errors.log' => '',
            'api.log'           => '',
            'cron.log'          => '',
            'event.log'         => '',
            'mail-error.log'    => '',
            'mailgate.log'      => '',
            'webhook.log'       => '',
        ]);
        $structure->getChild('mailgate.log')->chmod(0o444);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The log file ' . vfsStream::url('root/mailgate.log') . ' is not writable.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithMultipleUnwritableFiles()
    {
        $structure = vfsStream::setup('root', 0o777, [
            'php-errors.log'    => '',
            'access-errors.log' => '',
            'api.log'           => '',
            'cron.log'          => '',
            'event.log'         => '',
            'mail-error.log'    => '',
            'mailgate.log'      => '',
            'webhook.log'       => '',
        ]);
        $structure->getChild('cron.log')->chmod(0o444);
        $structure->getChild('webhook.log')->chmod(0o444);

        $instance = new LogsWriteAccess(vfsStream::url('root'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            [
                'The log file ' . vfsStream::url('root/cron.log') . ' is not writable.',
                'The log file ' . vfsStream::url('root/webhook.log') . ' is not writable.',
            ],
            $instance->getValidationMessages()
        );
    }
}
