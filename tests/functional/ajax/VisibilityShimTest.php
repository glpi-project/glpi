<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\ajax;

use Glpi\Tests\DbTestCase;
use Psr\Log\LogLevel;

final class VisibilityShimTest extends DbTestCase
{
    public function testVisibilityShimEmitsDeprecationWarning(): void
    {
        $this->login();

        $reporting_level = error_reporting(E_ALL);
        $_POST = ['type' => 'User', 'right' => 'knowbase'];

        ob_start();
        try {
            require GLPI_ROOT . '/ajax/visibility.php';
        } finally {
            ob_end_clean();
            error_reporting($reporting_level);
            $_POST = [];
        }

        $this->hasPhpLogRecordThatContains(
            'ajax/visibility.php is deprecated',
            LogLevel::INFO
        );
    }

    public function testVisibilityShimRendersControllerOutput(): void
    {
        $this->login();

        $reporting_level = error_reporting(E_ALL);
        $_POST = ['type' => 'User', 'right' => 'knowbase'];
        ob_start();
        try {
            require GLPI_ROOT . '/ajax/visibility.php';
            $body = ob_get_contents();
        } finally {
            ob_end_clean();
            error_reporting($reporting_level);
            $_POST = [];
        }

        $this->hasPhpLogRecordThatContains(
            'ajax/visibility.php is deprecated',
            LogLevel::INFO
        );

        // Byte-for-byte equality with the controller is brittle (IDOR tokens
        // and `mt_rand()` differ per call). Asserting on the User-branch
        // signature is enough to prove the shim wired through to the modern
        // controller.
        $this->assertNotSame('', $body);
        $this->assertMatchesRegularExpression('/name=["\']users_id["\']/', $body);
    }

    public function testSubvisibilityShimEmitsDeprecationWarning(): void
    {
        $this->login();

        $reporting_level = error_reporting(E_ALL);
        $_POST = ['type' => 'Group', 'items_id' => 1];

        ob_start();
        try {
            require GLPI_ROOT . '/ajax/subvisibility.php';
        } finally {
            ob_end_clean();
            error_reporting($reporting_level);
            $_POST = [];
        }

        $this->hasPhpLogRecordThatContains(
            'ajax/subvisibility.php is deprecated',
            LogLevel::INFO
        );
    }

    public function testSubvisibilityShimRendersControllerOutput(): void
    {
        $this->login();

        $reporting_level = error_reporting(E_ALL);
        $_POST = ['type' => 'Group', 'items_id' => 1];
        ob_start();
        try {
            require GLPI_ROOT . '/ajax/subvisibility.php';
            $body = ob_get_contents();
        } finally {
            ob_end_clean();
            error_reporting($reporting_level);
            $_POST = [];
        }

        $this->hasPhpLogRecordThatContains(
            'ajax/subvisibility.php is deprecated',
            LogLevel::INFO
        );

        $this->assertNotSame('', $body);
        $this->assertMatchesRegularExpression('/name=["\']entities_id["\']/', $body);
        $this->assertMatchesRegularExpression('/name=["\']is_recursive["\']/', $body);
    }

    public function testSubvisibilityShimReturnsEmptyBodyForItemsIdZero(): void
    {
        $this->login();

        $reporting_level = error_reporting(E_ALL);
        $_POST = ['type' => 'Group', 'items_id' => 0];
        ob_start();
        try {
            require GLPI_ROOT . '/ajax/subvisibility.php';
            $body = ob_get_contents();
        } finally {
            ob_end_clean();
            error_reporting($reporting_level);
            $_POST = [];
        }

        $this->assertSame('', $body);

        $this->hasPhpLogRecordThatContains(
            'ajax/subvisibility.php is deprecated',
            LogLevel::INFO
        );
    }
}
