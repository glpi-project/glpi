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

describe('OAuth - Authorization Code Grant', () => {
    const oauthclient_id = '9246d35072ff62193330003a8106d947fafe5ac036d11a51ebc7ca11b9bc135e';
    const oauthclient_secret = 'd2c4f3b8a0e1f7b5c6a9d1e4f3b8a0e1f7b5c6a9d1e4f3b8a0e1f7b5c6a9d1';

    function doAuthCodeGrant(expect_already_logged_in = false, remember_me = false) {
        function doGLPILogin() {
            cy.findByRole('textbox', {'name': "Login"}).type('e2e_tests');
            cy.findByLabelText("Password").type('glpi');
            if (remember_me) {
                cy.findByRole('checkbox', {name: "Remember me"}).check();
            } else {
                cy.findByRole('checkbox', {name: "Remember me"}).uncheck();
            }
            cy.getDropdownByLabelText("Login source").selectDropdownValue('GLPI internal database');
            cy.findByRole('button', {name: "Sign in"}).click();
        }

        function doAuthorization() {

        }

        cy.visit(`/api.php/Authorize?response_type=code&client_id=${oauthclient_id}&scope=api user&redirect_uri=/api.php/oauth2/redirection`);
        if (!expect_already_logged_in) {
            doGLPILogin();
        }
        doAuthorization();
    }

    it('Should authorize without cookie - no remember me', () => {
        doAuthCodeGrant();
    });
    // it('Should authorize without cookie - remember me', () => {
    //     doAuthCodeGrant(false, true);
    // });
    // it('Should authorize with cookie', () => {
    //     cy.login();
    //     doAuthCodeGrant(true);
    // });
});
