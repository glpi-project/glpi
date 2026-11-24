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

namespace tests\units\Glpi\Controller;

use Glpi\Controller\LegacyFileLoadController;
use Glpi\Exception\RedirectException;
use Glpi\Tests\GLPITestCase;
use Symfony\Component\HttpFoundation\Request;

use function Safe\file_put_contents;
use function Safe\tempnam;
use function Safe\unlink;

final class LegacyFileLoadControllerTest extends GLPITestCase
{
    public function testOutputIsDiscardedWhenScriptThrows(): void
    {
        $script_path = tempnam(GLPI_TMP_DIR, 'legacy_script_');
        file_put_contents(
            $script_path,
            '<?php echo "partial page"; throw new \Glpi\Exception\RedirectException("/front/central.php");'
        );

        $request = new Request();
        $request->attributes->set(LegacyFileLoadController::REQUEST_FILE_KEY, $script_path);

        $this->expectOutputString('');

        $initial_buffer_level = ob_get_level();
        try {
            (new LegacyFileLoadController())($request);
            $this->fail('The exception thrown by the legacy script was not propagated.');
        } catch (RedirectException) {
            $this->assertSame($initial_buffer_level, ob_get_level());
        } finally {
            unlink($script_path);
        }
    }
}
