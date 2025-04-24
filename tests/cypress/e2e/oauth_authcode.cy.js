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
        function doGLPILogin(redirect_url, csrf_token) {
            // cy.findByRole('textbox', {'name': "Login"}).type('e2e_tests');
            // cy.findByLabelText("Password").type('glpi');
            // if (remember_me) {
            //     cy.findByRole('checkbox', {name: "Remember me"}).check();
            // } else {
            //     cy.findByRole('checkbox', {name: "Remember me"}).uncheck();
            // }
            // cy.getDropdownByLabelText("Login source").selectDropdownValue('GLPI internal database');
            // cy.findByRole('button', {name: "Sign in"}).click();

            // Do login as request instead of visit
            const body = {
                login_name: 'e2e_tests',
                login_password: 'glpi',
                auth: 0,
                _glpi_csrf_token: csrf_token,
                redirect: redirect_url,
                noAUTO: 0
            };
            if (remember_me) {
                body.login_remember = 1;
            }
            cy.request({
                method: 'POST',
                url: '/front/login.php',
                form: true,
                body: body,
            }).then((response) => {
                expect(response.status).to.eq(200);
            });
        }

        function doAuthorization() {

        }

        cy.request({
            method: 'GET',
            url: '/api.php/Authorize',
            qs: {
                response_type: 'code',
                client_id: oauthclient_id,
                scope: 'api user',
                redirect_uri: '/api.php/oauth2/redirection',
                //state: 'test_state'
            },
        }).then((response) => {
            expect(response.status).to.eq(200);
            expect(response.allRequestResponses).to.have.length(2);

            expect(response.allRequestResponses[1]['Request URL']).to.contain(
                encodeURIComponent(`/api.php/v2/authorize?scope=api+user&client_id=${oauthclient_id}&response_type=code&redirect_uri=${encodeURIComponent('/api.php/oauth2/redirection')}`)
            );

            if (!expect_already_logged_in) {
                // Should be on a GLPI login page
                const parsed_html = Cypress.$(`<div>${response.body}</div>`);
                expect(parsed_html.find('title').text()).to.eq('Authentication - GLPI');
                const redirect_url = parsed_html.find('input[name="redirect"]').val();
                const csrf_token = parsed_html.find('input[name="_glpi_csrf_token"]').val();
                doGLPILogin(redirect_url, csrf_token);
            }
            doAuthorization();
        });
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
