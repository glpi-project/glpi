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

namespace tests\units\Glpi\Log;

use Glpi\Message\MessageType;
use Glpi\Progress\ConsoleProgressIndicator;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleProgressIndicatorTest extends GLPITestCase
{
    public static function messageProvider(): iterable
    {
        $verbosities = [
            OutputInterface::VERBOSITY_QUIET,
            OutputInterface::VERBOSITY_NORMAL,
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG,
        ];

        foreach ($verbosities as $verbosity) {
            yield [
                'verbosity'          => $verbosity,
                'type'               => MessageType::Error,
                'message'            => 'An unexpected error occured.',
                'expected_output'    => '<error>An unexpected error occured.</error>',
                'expected_verbosity' => OutputInterface::VERBOSITY_QUIET,
            ];

            yield [
                'verbosity'          => $verbosity,
                'type'               => MessageType::Warning,
                'message'            => 'Invalid foo has been ignored.',
                'expected_output'    => '<comment>Invalid foo has been ignored.</comment>',
                'expected_verbosity' => OutputInterface::VERBOSITY_QUIET,
            ];

            yield [
                'verbosity'          => $verbosity,
                'type'               => MessageType::Success,
                'message'            => 'Bar has been created successfully.',
                'expected_output'    => '<info>Bar has been created successfully.</info>',
                'expected_verbosity' => OutputInterface::VERBOSITY_NORMAL,
            ];

            yield [
                'verbosity'          => $verbosity,
                'type'               => MessageType::Notice,
                'message'            => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'expected_output'    => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'expected_verbosity' => OutputInterface::VERBOSITY_NORMAL,
            ];

            yield [
                'verbosity'          => $verbosity,
                'type'               => MessageType::Debug,
                'message'            => 'Bla bla bla.',
                'expected_output'    => '[DEBUG] Bla bla bla.',
                'expected_verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
            ];
        }
    }

    #[DataProvider('messageProvider')]
    public function testMessageOuput(
        int $verbosity,
        MessageType $type,
        string $message,
        string $expected_output,
        int $expected_verbosity,
    ): void {
        // Arrange
        $instance = new ConsoleProgressIndicator(
            $output = $this->createMock(OutputInterface::class),
            new ProgressBar($output) // ProgressBar cannot be mocked as it is final
        );

        $output->method('getVerbosity')->willReturn($verbosity);
        if ($verbosity >= $expected_verbosity) {
            // message will be output
            $output->expects($this->once())
                ->method('writeln')
                ->with($expected_output, $expected_verbosity);
        } else {
            // method is not called due to internal filtering if verbosity of the message is lower that the output verbosity
            $output->expects($this->never())->method('writeln');
        }

        // Act
        $instance->addMessage($type, $message);

        // Assert
        // assertions have been done through the $output mock
    }
}
