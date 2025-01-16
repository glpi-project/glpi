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

window.CustomFlatpickrButtons = (config = {}) => {

    return (fp) => {
        let wrapper;

        if (config.buttons === undefined) {
            config.buttons = [{
                label: fp.config.enableTime ? __('Now') : __("Today"),
                attributes: {
                    'class': 'btn btn-outline-secondary'
                },
                onClick: (e, fp) => {
                    fp.setDate(new Date());
                }
            },
            {
                label: __('Save'),
                attributes: {
                    'class': 'btn btn-primary'
                },
                onClick: (e, fp) => {
                    const current_date = new Date();
                    // If the input is empty, we need to set a date
                    if ($(fp.input).val().length == 0) {
                        if (fp.config.enableTime) {
                            // Reuse the default date that is displayed to the user
                            fp.setDate(`${current_date.getFullYear()}-${current_date.getMonth() + 1}-${current_date.getDate()} ${fp.config.defaultHour}:${fp.config.defaultMinute}:${fp.config.defaultSeconds}`);
                        } else {
                            // No time, we can directly use the current date as it is the default value
                            fp.setDate(new Date());
                        }
                    }
                    fp.close();
                }
            }];
        }

        return {
            onReady: () => {
                wrapper = `<div class="flatpickr-custom-buttons pb-1 text-start"><div class="buttons-container">`;

                (config.buttons).forEach((b, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.classList.add('ms-2');
                    button.innerHTML = b.label;
                    button.setAttribute('btn-id', index);
                    if (typeof b.attributes !== 'undefined') {
                        Object.keys(b.attributes).forEach((key) => {
                            if (key === 'class') {
                                button.classList.add(...b.attributes[key].split(' '));
                                return;
                            }

                            button.setAttribute(key, b.attributes[key]);
                        });
                    }

                    wrapper += button.outerHTML;

                    fp.pluginElements.push(button);
                });
                wrapper += '</div></div>';

                fp.calendarContainer.appendChild($.parseHTML(wrapper)[0]);

                $(fp.calendarContainer).on('click', '.flatpickr-custom-buttons button', (e) => {
                    e.stopPropagation();
                    e.preventDefault();

                    const btn = $(e.target);
                    const btn_id = btn.attr('btn-id');
                    const click_handler = config.buttons[btn_id].onClick;

                    if (typeof click_handler !== 'function') {
                        return;
                    }

                    click_handler(e, fp);
                });
            },

            onDestroy: () => {
                $(wrapper).remove();
            },
        };
    };
};
