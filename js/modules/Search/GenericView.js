/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/* global bootstrap */

// Explicitly bind to window so Jest tests work properly
window.GLPI = window.GLPI || {};
window.GLPI.Search = window.GLPI.Search || {};

window.GLPI.Search.GenericView = class GenericView {

    constructor(element_id) {
        this.element_id = element_id;

        if (this.getElement()) {
            this.registerListeners();
        }
    }

    postInit() {}

    getElement() {
        return $('#'+this.element_id);
    }

    getResultsView() {
        return this.getElement().closest('.ajax-container.search-display-data').data('js_class');
    }

    showLoadingSpinner() {
        const el = this.getElement();
        const container = el.parent();
        let loading_overlay = container.find('div.spinner-overlay');

        if (loading_overlay.length === 0) {
            container.append(`
            <div class="spinner-overlay text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">${__('Loading...')}</span>
                </div>
            </div>`);
            loading_overlay = container.find('div.spinner-overlay');
        } else {
            loading_overlay.css('visibility', 'visible');
        }
    }

    hideLoadingSpinner() {
        const loading_overlay = this.getElement().parent().find('div.spinner-overlay');
        loading_overlay.css('visibility', 'hidden');
    }

    registerListeners() {
        const ajax_container = this.getResultsView().getAJAXContainer();
        const search_container = ajax_container.closest('.search-container');

        $(search_container).on('click', 'a.bookmark_record.save', () => {
            const modal = $('#savedsearch-modal');
            //move the modal to the body so it can be displayed above the rest of the page
            modal.appendTo('body');
            modal.empty();
            modal.html(`
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">${__('Save current search')}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${__('Close')}"></button>
                    </div>
                    <div class="modal-body"></div>
                </div>
            </div>
            `);
            const bs_modal = new bootstrap.Modal(modal.get(0), {show: false});
            modal.on('show.bs.modal', () => {
                const params = JSON.parse(modal.attr('data-params'));
                params['url'] = window.location.pathname + window.location.search;
                modal.find('.modal-body').load(CFG_GLPI.root_doc + '/ajax/savedsearch.php', params);
            });
            bs_modal.show();
        });
    }

    onSearch() {
        this.refreshResults();
    }

    refreshResults() {}
};
export default window.GLPI.Search.GenericView;
