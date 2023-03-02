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

/* global tinymce */

$(function () {
    $('main').on('glpi.tab.loaded', function () {
        // save itil object form data
        const main_form = $('.new-itil-object');
        $(document).on('submit', main_form, function () {
            saveFormData('Ticket', main_form);
        });
        main_form.find('[name="name"]').on('input', function() {
            saveFormData('Ticket', main_form, true);
        });
        // let's delay the event binding to let tinymce initialize (race condition)
        setTimeout(() => {
            let mainform_tinymce = tinymce.get($(main_form).find('[name="content"]').attr('id'));
            mainform_tinymce.on('input', function() {
                saveFormData('Ticket', main_form, true);
            });
        }, 200);

        // Restore itil object data
        restoreFormData('Ticket', $('.new-itil-object'));

        // track sub forms only when on an existing itil object
        if ($('.new-itil-object').length === 0) {
            // manage subforms
            $.each({
                'followup': '#new-ITILFollowup-block form',
                'task': '#new-TicketTask-block form',
                'solution': '#new-ITILSolution-block form',
            }, function (store_index_prefix, form_selector) {
                trackSubForm($(form_selector), store_index_prefix);
                restoreSubForm($(form_selector), store_index_prefix);
            });
        }
    });
});

const trackSubForm = function(form, store_index_prefix) {
    const items_id = form.find('[name="items_id"]').val();
    const itemtype = form.find('[name="itemtype"]').val();
    const store_index = itemtype + '_' + items_id + "_" + store_index_prefix;

    $(form).on('submit', function() {
        saveFormData(store_index, form);
    });

    // let's delay the event binding to let tinymce initialize (race condition)
    setTimeout(() => {
        let form_tinymce = tinymce.get($(form).find('[name="content"]').attr('id'));
        form_tinymce.on('input', function() {
            saveFormData(store_index, form, true);
        });
    }, 200);
};


const restoreSubForm = function(form, store_index_prefix) {
    const items_id = form.find('[name="items_id"]').val();
    const itemtype = form.find('[name="itemtype"]').val();
    const store_index = itemtype + '_' + items_id + "_" + store_index_prefix;

    restoreFormData(store_index, form);
};


const saveFormData = function (store_index, form, debounce = false) {
    const callSave = function () {
        const ttl = 10 * 60 * 1000; // 10 minutes
        let form_tinymce = tinymce.get(form.find('[name="content"]').attr('id'));
        const form_data = {
            'name': form.find('[name="name"]').val(),
            'content': form_tinymce.getContent(),
            'expiry': new Date().getTime() + ttl,
        };

        localStorage.setItem(store_index, JSON.stringify(form_data));
    };

    if (debounce) {
        let timer;
        setTimeout(function() {
            clearTimeout(timer);
            callSave();
        }, 200);
    } else {
        callSave();
    }
};


var restoreFormData = function (store_index, form) {
    const form_data = JSON.parse(localStorage.getItem(store_index));

    if (form_data) {
        if (form_data.expiry < new Date().getTime()) {
            localStorage.removeItem(store_index);
            return;
        }

        $.each(form_data, function (key, value) {
            const input = form.find('[name="' + key + '"]');

            // restore only if the field exist and is empty
            if (input.length > 0 && input.val().length === 0) {
                input.val(value);
            }
        });
    }
};
