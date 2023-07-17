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

// Needed for JS lint validation
/* global _ */

const StencilEditor = function (container, rand, zones_definition) {
    let zones = zones_definition;

    let croppers = [];

    let _this = this;

    _this.init = function () {

        // define set of croppers (and initialize them)
        Array.prototype.forEach.call(document.querySelectorAll('.stencil-image'), function (img) {
            croppers.push(new window.Cropper(img));
        });

        // set default state of croppers objects
        croppers.forEach(function (cropper) {
            const cr_selection = cropper.getCropperSelection();
            cr_selection.hidden = true;
            cr_selection
                .$reset()
                .hidden = true;

            cropper.getCropperCanvas().disabled = true;

            const cr_image = cropper.getCropperImage();
            cr_image.$ready(function () {
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

        // global events
        $(container)
            .on('click', '.set-zone-data', function (e) {
                e.preventDefault();
                _this.editorEnable(parseInt($(this).data('zone-index')));
            })
            .on('click', '#save-zone-data-' + rand, function () {
                _this.saveZoneData();
            })
            .on('click', '#cancel-zone-data-' + rand, function () {
                _this.editorDisable();
            });

        $('#clear-data-' + rand)
            .on('click', function (e) {
                const submitButton = $('#clear-data-' + rand);

                if (submitButton.data('delete') != '1') {
                    const originalText = submitButton.text();

                    submitButton.data('delete', '1');
                    submitButton.text(__('Are you sure?'));
                    setInterval(() => {
                        submitButton.data('delete', '0');
                        submitButton.text(originalText);
                    }, 10000);

                    e.preventDefault();
                }
            });

        // keyboard events
        $('#zone_label-' + rand)
            .on('keypress', function (e) {
                var keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode == 13) {
                    _this.saveZoneData();
                }
            });
        $('#zone_number-' + rand)
            .on('keypress', function (e) {
                var keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode == 13) {
                    _this.saveZoneData();
                }
            });
    }();

    // open zone definition control (and enable croppers)
    _this.editorEnable = function (current_zone) {
        let zone = zones[current_zone] ?? { 'side': 0 };

        $(container).find('.set-zone-data').removeClass('btn-warning'); // remove old active definition
        $(container).find(".defined-zone").remove();
        $(container).find(".set-zone-data[data-zone-index=" + current_zone + "]").addClass('btn-warning');
        $(container).find('#general-submit-' + rand).addClass('d-none');
        $(container).find('#zone-data-' + rand).removeClass('d-none');
        $(container).find('#zone_label-' + rand).val(zone['label'] ?? current_zone);
        $(container).find('#zone_number-' + rand).val(zone['number'] ?? current_zone).data('zone-index', current_zone);

        croppers.forEach(function (cropper, side) {
            cropper.getCropperSelection()
                .$reset()
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
        const zoneIndex = $(container).find('#zone_number-' + rand).data('zone-index');

        // get zone number
        const zoneNumber = $(container).find('#zone_number-' + rand).val();

        // set raw cropper data
        zones[zoneIndex] = {
            'selection': {
                'x': sel_x,
                'y': sel_y,
                'height': sel_h,
                'width': sel_w,
            },
            'image': cr_image.$getTransform(),
            'label': $(container).find('#zone_label-' + rand).val(),
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
        $(container).find(".set-zone-data[data-zone-index=" + zoneIndex + "]")
            .removeClass('btn-outline-secondary')
            .addClass('btn-success')
            .find('i').removeClass('ti-file-unknown').addClass('ti-check');

        // update label
        $(container).find(".set-zone-data[data-zone-index=" + zoneIndex + "]")
            .find('span').text(zones[zoneIndex]['label']);

        // update data on server
        _this.sendDataForm();
    };

    // send data to server
    _this.sendDataForm = function () {
        $.ajax({
            type: 'POST',
            url: CFG_GLPI.root_doc + "/ajax/stencil.php",
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
        croppers.forEach(function (cropper) {
            cropper.getCropperSelection()
                .$reset()
                .hidden = true;
            cropper.getCropperCanvas()
                .disabled = true;
            cropper.getCropperImage()
                .$center('contain');
        });

        $(container).find('.set-zone-data').removeClass('btn-warning');
        $(container).find('#general-submit-' + rand).removeClass('d-none');
        $(container).find('#zone-data-' + rand).addClass('d-none');

        _this.redoZones();
    };

    _this.redoZones = function () {
        $(container).find(".defined-zone").remove();

        for (const [zone_number, zone] of Object.entries(zones)) {
            $(container).find('.stencil-image[data-side=' + zone['side'] + ']').parent().append(`
                <a href="#zone_number_-${rand}${zone_number}"
                class="defined-zone set-zone-data d-inline-flex align-items-center justify-content-center"
                data-zone-index="${zone_number}"
                data-bs-toggle="tooltip"
                data-bs-title="${_.escape(zone['label'])}"
                style="left: ${zone['x_percent']}%; top: ${zone['y_percent']}%; width: ${zone['width_percent']}%; height: ${zone['height_percent']}%;">
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
    };
};
