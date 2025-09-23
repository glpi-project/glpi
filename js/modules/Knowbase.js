/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global glpi_alert, glpi_html_dialog */
/* global _ */

class Knowbase {
    constructor() {
        this.#registerListeners();
    }

    #registerListeners() {
        $(document).on('click', '.restore_rev', () => {
            return window.confirm(__('Do you want to restore the selected revision?'));
        });

        $(document).on('click', '.show_rev', (e) => {
            e.preventDefault();
            const _this = $(e.currentTarget);

            $.ajax({
                url: `${CFG_GLPI.root_doc}/ajax/getKbRevision.php`,
                method: 'GET',
                cache: false,
                data: {
                    revid: _this.data('revid')
                }
            })
                .done((data) => {
                    glpi_html_dialog({
                        title: __('Show revision %d').replace('%d', _this.data('rev')),
                        body: `
                            <div>
                                <h2>${__('Subject')}</h2>
                                <div>${_.escape(data.name)}</div>
                                <h2>${__('Content')}</h2>
                                <div>${data.answer}</div>
                            </div>
                        `,
                    });
                })
                .fail(() => {
                    glpi_alert({
                        title: __('Contact your GLPI admin!'),
                        message: __('Unable to load revision!'),
                    });
                });
        });

        $(document).on('click', '.compare', (e) => {
            e.preventDefault();
            const _oldid = $('[name="oldid"]:checked').val();
            const _diffid = $('[name="diff"]:checked').val();
            const kbitem_id = $(e.currentTarget).data('kbitem_id');
            this.#showRevisionComparison(kbitem_id, _oldid, _diffid);
        });

        $('[name="diff"]:gt(0)').css('visibility', 'hidden');
        $('[name="oldid"]').on('click', (e) => {
            const _index = $(e.target).index('[name="oldid"]');

            const _checked_index = $('[name="diff"]:checked').index('[name="diff"]');
            if (_checked_index >= _index) {
                $(`[name="diff"]:eq(${_index - 1})`).prop('checked', true);
            }

            $(`[name="diff"]:gt(${_index}), [name="diff"]:eq(${_index})`).css('visibility', 'hidden');
            $(`[name="diff"]:lt(${_index})`).css('visibility', 'visible');
        });
    }

    #showRevisionComparison(kb_item_id, old_id, new_id) {
        // We will need the lib/jquery-prettytextdiff.js script to display the differences once the data is retrieved
        // from the server. We can load the library (if it isn't already and the data at the same time and await both promises.
        // The dynamic import will not load the library again if it is already loaded, it will simply resolve immediately.

        const lib_import = import('/lib/jquery-prettytextdiff.js');
        const data_promise = $.ajax({
            url: `${CFG_GLPI.root_doc}/ajax/compareKbRevisions.php`,
            method: 'post',
            cache: false,
            data: {
                oldid:  old_id,
                diffid: new_id,
                kbid: kb_item_id
            }
        });

        Promise.all([lib_import, data_promise]).then(([, data]) => {
            if (new_id === 0) {
                new_id = __('current');
            }

            glpi_html_dialog({
                title: __('Compare revisions %1$d and %2$d')
                    .replace("%1$d", old_id)
                    .replace("%2$d", new_id),
                body: `
                    <div id="compare_view_${kb_item_id}">
                        <table class="table">
                            <tr>
                                <th></th>
                                <th>${__('Original')}</th>
                                <th>${__('Changed')}</th>
                                <th>${__('Differences')}</th>
                            </tr>
                            <tr>
                                <th>${__('Subject')}</th>
                                <td class="original">${_.escape(data['old']['name'])}</td>
                                <td class="changed">${_.escape(data['diff']['name'])}</td>
                                <td class="diff"></td>
                            </tr>
                            <tr>
                                <th>${__('Content')}</th>
                                <td class="original">${data['old']['answer']}</td>
                                <td class="changed">${data['diff']['answer']}</td>
                                <td class="diff"></td>
                            </tr>
                        </table>
                    </div>
                `,
            });

            $(`#compare_view_${kb_item_id} tr`).prettyTextDiff();
        }, () => {
            glpi_alert({
                title: __('Contact your GLPI admin!'),
                message: __('Unable to load diff!'),
            });
        });
    }
}

export default new Knowbase();
