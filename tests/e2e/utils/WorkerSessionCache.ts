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

export class WorkerSessionCache
{
    private current_profile_id: number|null = null;

    private csrf_token: string|null = null;

    public setCurrentProfileId(current_profile_id: number): void
    {
        this.current_profile_id = current_profile_id;
    }

    public getCurrentProfileId(): number|null
    {
        return this.current_profile_id;
    }

    public setCsrfToken(token: string): void
    {
        this.csrf_token = token;
    }

    public getCsrfToken(): string|null
    {
        return this.csrf_token;
    }
}
