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
import { expect, test } from '../../fixtures/glpi_fixture';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Planning availability', () => {
    test('is not reachable anonymously', async ({ anonymousPage, api }) => {
        const realname = `e2e_availability_${Date.now()}`;
        const users_id = await api.createItem('User', {
            name: realname,
            realname: realname,
            entities_id: getWorkerEntityId(),
        });

        // The `genical` parameter must not let an anonymous caller reach the availability screen:
        // the request is sent back to the login page instead.
        const response = await anonymousPage.request.get(
            `/front/planning.php?genical=1&checkavailability=1&itemtype=User&users_id=${users_id}&uID=0&token=invalid`,
            { maxRedirects: 0 }
        );
        expect(response.status()).toBe(302);
        expect(response.headers()['location']).toContain('error=3');
        expect(await response.text()).not.toContain(realname);
    });
});
