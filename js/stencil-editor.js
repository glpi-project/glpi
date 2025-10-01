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

// Needed for JS lint validation
/* eslint no-var: 0 */
/* global _ */

/**
 * Creates a new StencilEditor instance.
 *
 * @constructor
 * @param {HTMLElement} container - The container element for the editor.
 * @param {integer} rand - A random string used for generating unique IDs.
 * @param {Object} zones_definition - The initial definition of zones.
 * @returns {StencilEditor} A new StencilEditor instance.
 */
const StencilEditor = function (container, rand, zones_definition) {
    rand = parseInt(rand);

    const zones = zones_definition;

    const croppers = [];

    const _this = this;

    _this.init = function () {

        // define set of croppers (and initialize them)
        Array.prototype.forEach.call(document.querySelectorAll('.stencil-image'), (img) => {
            const cropper = new window.Cropper(img);
            croppers.push(cropper);
            img.cropper = cropper;
        });

        // set default state of croppers objects
        croppers.forEach((cropper) => {
            setTimeout(() => {
                const cr_selection = cropper.getCropperSelection();
                cr_selection.$clear();

                cropper.container.querySelector('cropper-canvas').disabled = true;
                cropper.container.querySelector('cropper-shade').hidden = true;

                const cr_image = cropper.getCropperImage();
                cr_image.$ready(() => {
                    // center image (by stretching y axis to max)
                    cr_image.$center('cover');

                    // adapt canvas to have the same height as the image
                    const img_rect = cr_image.getBoundingClientRect();
                    const cr_canvas = cropper.getCropperCanvas();
                    $(container).find(cr_canvas).css('height', img_rect.height);

                    // re-center image (after heigh adjust of container, the image is off on top)
                    cr_image.$center();
                });
            });
        });

        // global events
        $(container)
            .on('click', '.set-zone-data', function (e) {
                e.preventDefault();
                _this.editorEnable(parseInt($(this).data('zone-index')));
            })
            .on('click', `#save-zone-data-${rand}`, () => {
                _this.saveZoneData();
            })
            .on('click', `#reset-zone-data-${rand}`, () => {
                _this.resetZoneData();
            })
            .on('click', `#cancel-zone-data-${rand}`, () => {
                _this.editorDisable();
            })
            .on('click', 'button[name="add-new-zone"]', () => {
                _this.addNewZone();
            })
            .on('click', 'button[name="remove-zone"]', () => {
                _this.removeZone();
            });

        $(`#clear-data-${rand}`)
            .on('click', (e) => {
                const submitButton = $(`#clear-data-${rand}`);

                if (submitButton.data('delete') != '1') {
                    const originalText = submitButton.text();

                    submitButton.data('delete', '1');
                    submitButton.text(_.unescape(_x('button', 'Are you sure?')));
                    setInterval(() => {
                        submitButton.data('delete', '0');
                        submitButton.text(originalText);
                    }, 10000);

                    e.preventDefault();
                }
            });

        // keyboard events
        $(document)
            .on('keyup', (e) => {
                if (!_this.isEditorActive()) {
                    return;
                }

                var keycode = (e.keyCode ? e.keyCode : e.which);

                // Check if one of the cropper as a selection
                const hasSelection = croppers.some((cropper) => {
                    return cropper.getCropperSelection() !== undefined
                        && cropper.getCropperSelection().height > 0
                        && cropper.getCropperSelection().width > 0;
                });

                if (keycode == 13 && hasSelection) {
                    _this.saveZoneData();
                    e.preventDefault();
                } else if (keycode == 27) {
                    _this.editorDisable();
                    e.preventDefault();
                }
            })
            .on('keypress', (e) => {
                if (!_this.isEditorActive()) {
                    return;
                }

                var keycode = (e.keyCode ? e.keyCode : e.which);

                if (keycode == 13) {
                    const hasSelection = croppers.some((cropper) => {
                        return cropper.getCropperSelection() !== undefined
                            && cropper.getCropperSelection().height > 0
                            && cropper.getCropperSelection().width > 0;
                    });

                    if (hasSelection) {
                        _this.saveZoneData();
                        e.preventDefault();
                    }
                }
            });
    }();

    // open zone definition control (and enable croppers)
    _this.editorEnable = function (current_zone) {
        const zone = zones[current_zone] ?? { 'side': 0 };

        // Hide tooltips to avoid bug : tooltip doesn't disappear when dom is altered
        $(container).find('a.defined-zone[data-bs-toggle="tooltip"]').tooltip('hide');

        $(container).find('.set-zone-data').removeClass('btn-warning'); // remove old active definition
        $(container).find(".defined-zone").remove();
        $(container).find(`.set-zone-data[data-zone-index=${ CSS.escape(current_zone) }]`).addClass('btn-warning');
        $(container).find(`#general-submit-${rand}`).addClass('d-none');
        $(container).find(`#zone-data-${rand}`).removeClass('d-none');
        $(container).find(`#zone_label-${rand}`).val(zone['label'] ?? current_zone);
        $(container).find(`#zone_number-${rand}`).val(zone['number'] ?? current_zone).data('zone-index', current_zone);

        croppers.forEach((cropper, side) => {
            cropper.getCropperSelection()
                .$clear()
                .hidden = false;
            cropper.getCropperCanvas()
                .disabled = false;

            // load existing data
            if (Object.keys(zone).length > 1 && side == zone['side']) {
                // set image in the state it was saved
                cropper.getCropperImage().$setTransform(zone['image']);

                // restore the selection
                const data_sel = zone['selection'];
                cropper.getCropperSelection().$change(
                    data_sel['x'],
                    data_sel['y'],
                    data_sel['width'],
                    data_sel['height']
                );
            }
        });
    };

    // save zone definition
    _this.saveZoneData = function () {
        // get the good cropper instance
        const cropper = croppers.filter(cropper => {
            return cropper.getCropperSelection() !== undefined
                && cropper.getCropperSelection().height > 0
                && cropper.getCropperSelection().width > 0;
        })[0];

        // get the different dom object
        const cr_canvas = cropper.getCropperCanvas();
        const cr_selection = cropper.getCropperSelection();
        const cr_image = cropper.getCropperImage();

        // get the selection coordinates (relative to canvas)
        const sel_x = cr_selection.x;
        const sel_y = cr_selection.y;
        const sel_h = cr_selection.height;
        const sel_w = cr_selection.width;

        // get rect for canvas and images
        const can_rect = cr_canvas.getBoundingClientRect();
        const img_rect = cr_image.getBoundingClientRect();

        // get images coordinates (relative to viewport)
        const img_x = img_rect.left - can_rect.left;
        const img_y = img_rect.top - can_rect.top;
        const img_w = img_rect.width;
        const img_h = img_rect.height;

        // get the selection coordinates (relative to image)
        const sel_rel_x = sel_x - img_x;
        const sel_rel_y = sel_y - img_y;

        // get zone index
        const zoneIndex = $(container).find(`#zone_number-${rand}`).data('zone-index');

        // get zone number
        const zoneNumber = $(container).find(`#zone_number-${rand}`).val();

        // set raw cropper data
        zones[zoneIndex] = {
            'selection': {
                'x': sel_x,
                'y': sel_y,
                'height': sel_h,
                'width': sel_w,
            },
            'image': cr_image.$getTransform(),
            'label': $(container).find(`#zone_label-${rand}`).val(),
            'number': zoneNumber,
            'side': croppers.indexOf(cropper),
        };

        // save percent data (relative to the image)
        zones[zoneIndex]['x_percent'] = sel_rel_x * 100 / img_w;
        zones[zoneIndex]['y_percent'] = sel_rel_y * 100 / img_h;
        zones[zoneIndex]['height_percent'] = sel_h * 100 / img_h;
        zones[zoneIndex]['width_percent'] = sel_w * 100 / img_w;

        _this.editorDisable();

        // indicate visually that data are saved
        $(container).find(`.set-zone-data[data-zone-index=${zoneIndex}]`)
            .removeClass('btn-warning')
            .addClass('btn-success')
            .find('i').removeClass('ti-file-unknown').addClass('ti-check');

        // update label
        $(container).find(`.set-zone-data[data-zone-index=${zoneIndex}]`)
            .find('span').text(zones[zoneIndex]['label']);

        // update data on server
        _this.sendDataForm();
    };

    // send data to server
    _this.sendDataForm = function () {
        $.ajax({
            type: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/stencil.php`,
            data: {
                'update': '',
                'id': $(container).find('input[name=id]').val(),
                'zones': JSON.stringify(zones),
                '_no_message': 1, // prevent Session::addMessageAfterRedirect()
            },
        });
    };

    // reset definition and close controls
    _this.editorDisable = function () {
        croppers.forEach((cropper) => {
            cropper.getCropperSelection()
                .$reset()
                .hidden = true;
            cropper.getCropperCanvas()
                .disabled = true;
            cropper.getCropperImage()
                .$center('contain');

            cropper.container.querySelector('cropper-shade').hidden = true;
        });

        $(container).find('.set-zone-data').removeClass('btn-warning');
        $(container).find(`#general-submit-${rand}`).removeClass('d-none');
        $(container).find(`#zone-data-${rand}`).addClass('d-none');

        _this.redoZones();
    };

    // check if editor is active
    _this.isEditorActive = function () {
        return croppers.some((cropper) => {
            return cropper.getCropperCanvas().disabled === false;
        });
    };

    // redraw zones
    _this.redoZones = function () {
        $(container).find(".defined-zone").remove();

        for (const [zone_number, zone] of Object.entries(zones)) {
            $(container).find(`.stencil-image[data-side=${zone['side']}]`).parent().append(`
                <a href="#zone_number_-${rand}${_.escape(zone_number)}"
                class="defined-zone set-zone-data d-inline-flex align-items-center justify-content-center"
                data-zone-index="${_.escape(zone_number)}"
                data-bs-toggle="tooltip"
                data-bs-title="${_.escape(zone['label'])}"
                data-bs-placement="auto"
                style="left: ${_.escape(zone['x_percent'])}%; top: ${_.escape(zone['y_percent'])}%; width: ${_.escape(zone['width_percent'])}%; height: ${_.escape(zone['height_percent'])}%;">
                    <span class="zone-number" style="max-height: 90%;
                        max-width: 90%;
                        overflow: hidden;
                        display: inline-block;
                        vertical-align: middle;
                        text-align: center;
                        white-space: nowrap;
                        font-size: 0.8em;">
                        ${_.escape(zone['label'])}
                    </span>
                </a>
            `);
        }

        // Enable tooltips
        $(container).find('a.defined-zone[data-bs-toggle="tooltip"]').tooltip('enable');
    };

    // add a new zone
    _this.addNewZone = function () {
        $.ajax({
            type: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/stencil.php`,
            data: {
                'add-new-zone': '',
                'id': $(container).find('input[name=id]').val(),
                '_no_message': 1, // prevent Session::addMessageAfterRedirect()
            },
            success: function () {
                // Hide tooltip to avoid bug : tooltip doesn't disappear when dom is altered
                $(`form#stencil-editor-form-${rand} button[name="add-new-zone"][data-bs-toggle="tooltip"]`).tooltip('hide');

                var index = $(`form#stencil-editor-form-${rand} button.set-zone-data`).length + 1;
                var template = $('#zone-number-template');
                var newZoneButton = $(template.html());
                $(newZoneButton).attr('data-zone-index', index);
                $(newZoneButton).find('span').text(index);
                newZoneButton.insertBefore(template);
            },
        });
    };

    // remove a zone
    _this.removeZone = function () {
        $.ajax({
            type: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/stencil.php`,
            data: {
                'remove-zone': '',
                'id': $(container).find('input[name=id]').val(),
                '_no_message': 1, // prevent Session::addMessageAfterRedirect()
            },
            success: function () {
                // Hide tooltip to avoid bug : tooltip doesn't disappear when dom is altered
                $(`form#stencil-editor-form-${rand} button[name="remove-zone"][data-bs-toggle="tooltip"]`).tooltip('hide');
                $(`form#stencil-editor-form-${rand} button.set-zone-data`).sort((a, b) => {
                    return $(a).data('zone-index') - $(b).data('zone-index');
                }).last().remove();
            },
        });
    };

    // reset zone data
    _this.resetZoneData = function () {
        const zoneId = $(container).find(`#zone_number-${rand}`).data('zone-index');
        $.ajax({
            type: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/stencil.php`,
            data: {
                'reset-zone': '',
                'id': $(container).find('input[name=id]').val(),
                'zone-id': zoneId,
                '_no_message': 1, // prevent Session::addMessageAfterRedirect()
            },
            success: function () {
                // Hide tooltip to avoid bug : tooltip doesn't disappear when dom is altered
                $(`form#stencil-editor-form-${rand} button[name="reset-zone-data"][data-bs-toggle="tooltip"]`).tooltip('hide');

                // Reset zone data
                var zoneData = $(`.set-zone-data[data-zone-index="${CSS.escape(zoneId)}"]`);
                zoneData.removeClass('btn-success').removeClass('btn-warning').addClass('btn-outline-secondary');
                zoneData.find('span').text(zoneId);
                zoneData.find('i').removeClass('ti-check').addClass('ti-file-unknown');

                // Remove zone data from zones
                delete zones[zoneId];

                // Disable editor
                _this.editorDisable();

                // Redraw zones
                _this.redoZones();
            },
        });
    };
};
