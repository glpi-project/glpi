/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
/* global _, StencilAutoDetect */

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

    const MAX_COLOR_DISTANCE = Math.sqrt(3) * 255;

    let last_detected_box = null;

    let last_auto_detect_seed = null;

    const _this = this;

    /**
     * Returns the current tolerance value from the tolerance input element.
     * The slider value (0–100 %) is mapped linearly to the maximum possible
     * Euclidean colour distance (~441.67).  Falls back to 10 % when the
     * element is not present.
     *
     * @returns {number}
     */
    const getTolerance = function () {
        const el = container.querySelector(`#auto-detect-tolerance-${rand}`);
        const pct = el ? parseInt(el.value, 10) : 10;
        return (pct / 100) * MAX_COLOR_DISTANCE;
    };

    /**
     * Reads pixel data from the <img> element backing a cropper instance by
     * drawing it onto an off-screen canvas.
     *
     * @param {object} cropper   Cropper.js instance.
     * @returns {{image_data: ImageData, natural_width: number, natural_height: number}|null}
     */
    const getCropperImageData = function (cropper) {
        const cr_image = cropper.getCropperImage();
        if (!cr_image) {
            return null;
        }

        const img_el = cr_image.querySelector('img') ?? cr_image;
        if (!img_el || !img_el.naturalWidth) {
            return null;
        }

        const natural_width = img_el.naturalWidth;
        const natural_height = img_el.naturalHeight;

        const offscreen = document.createElement('canvas');
        offscreen.width = natural_width;
        offscreen.height = natural_height;
        const ctx = offscreen.getContext('2d');
        ctx.drawImage(img_el, 0, 0, natural_width, natural_height);

        let image_data = null;
        try {
            image_data = ctx.getImageData(0, 0, natural_width, natural_height);
        } catch (_e) {
            // Cross-origin image – pixel data unavailable.
            return null;
        }

        return { image_data, natural_width, natural_height };
    };

    /**
     * Converts canvas-viewport coordinates (relative to the cropper canvas
     * element) to natural-image coordinates.
     *
     * @param {object} cropper
     * @param {number} canvas_x
     * @param {number} canvas_y
     * @returns {{x: number, y: number}|null}
     */
    const canvasToNaturalCoords = function (cropper, canvas_x, canvas_y) {
        const cr_image = cropper.getCropperImage();
        const cr_canvas = cropper.getCropperCanvas();
        if (!cr_image || !cr_canvas) {
            return null;
        }

        const canvas_rect = cr_canvas.getBoundingClientRect();
        const img_rect = cr_image.getBoundingClientRect();

        const rel_x = canvas_x - (img_rect.left - canvas_rect.left);
        const rel_y = canvas_y - (img_rect.top - canvas_rect.top);

        const scale_x = cr_image.querySelector('img')?.naturalWidth / img_rect.width ?? 1;
        const scale_y = cr_image.querySelector('img')?.naturalHeight / img_rect.height ?? 1;

        return {
            x: rel_x * scale_x,
            y: rel_y * scale_y,
        };
    };

    /**
     * Converts a bounding box expressed in natural-image pixels to percentages
     * relative to the rendered image dimensions.
     *
     * @param {object} cropper
     * @param {{x,y,width,height}} box   Natural-pixel bounding box.
     * @returns {{x_pct, y_pct, w_pct, h_pct}|null}
     */
    const boxToPercent = function (cropper, box) {
        const cr_image = cropper.getCropperImage();
        if (!cr_image) {
            return null;
        }
        const img_el = cr_image.querySelector('img') ?? cr_image;
        const natural_width = img_el.naturalWidth;
        const natural_height = img_el.naturalHeight;
        if (!natural_width || !natural_height) {
            return null;
        }

        return {
            x_pct: (box.x / natural_width) * 100,
            y_pct: (box.y / natural_height) * 100,
            w_pct: (box.width / natural_width) * 100,
            h_pct: (box.height / natural_height) * 100,
        };
    };

    /**
     * Converts a natural-pixel box to cropper-canvas selection coordinates.
     *
     * @param {object} cropper
     * @param {{x,y,width,height}} box
     * @returns {{x,y,width,height}|null}
     */
    const boxToCanvasCoords = function (cropper, box) {
        const cr_image = cropper.getCropperImage();
        const cr_canvas = cropper.getCropperCanvas();
        if (!cr_image || !cr_canvas) {
            return null;
        }

        const canvas_rect = cr_canvas.getBoundingClientRect();
        const img_rect = cr_image.getBoundingClientRect();

        const img_el = cr_image.querySelector('img') ?? cr_image;
        const scale_x = img_rect.width / (img_el.naturalWidth || 1);
        const scale_y = img_rect.height / (img_el.naturalHeight || 1);

        const origin_x = img_rect.left - canvas_rect.left;
        const origin_y = img_rect.top - canvas_rect.top;

        return {
            x: origin_x + box.x * scale_x,
            y: origin_y + box.y * scale_y,
            width: box.width * scale_x,
            height: box.height * scale_y,
        };
    };

    /**
     * Returns true when GLPI debug mode is active (body has the debug-active class).
     *
     * @returns {boolean}
     */
    const isDebugMode = function () {
        return document.body.classList.contains('debug-active');
    };

    /**
     * Converts the current cropper selection to natural-image coordinates.
     *
     * @param {object} cropper
     * @returns {{x: number, y: number, width: number, height: number}|null}
     */
    const selectionToNaturalBox = function (cropper) {
        const cr_selection = cropper.getCropperSelection();
        if (!cr_selection || cr_selection.width <= 0 || cr_selection.height <= 0) {
            return null;
        }

        const top_left = canvasToNaturalCoords(cropper, cr_selection.x, cr_selection.y);
        const bottom_right = canvasToNaturalCoords(
            cropper,
            cr_selection.x + cr_selection.width,
            cr_selection.y + cr_selection.height
        );

        if (!top_left || !bottom_right) {
            return null;
        }

        return {
            x: Math.max(0, Math.round(top_left.x)),
            y: Math.max(0, Math.round(top_left.y)),
            width: Math.max(1, Math.round(bottom_right.x - top_left.x)),
            height: Math.max(1, Math.round(bottom_right.y - top_left.y)),
        };
    };

    /**
     * Runs auto-detection from a given natural-image seed point using the current
     * tolerance value, updates the cropper selection, and shows the tolerance
     * slider.  Returns true on success.
     *
     * @param {object} cropper
     * @param {number} side
     * @param {number} natural_x
     * @param {number} natural_y
     * @returns {boolean}
     */
    const runAutoDetectAtPoint = function (cropper, side, natural_x, natural_y) {
        const pixel_data = getCropperImageData(cropper);
        if (!pixel_data) {
            return false;
        }

        const tolerance = getTolerance();

        if (isDebugMode()) {
            console.log('[StencilEditor] Auto-detect at natural coords', {
                x: natural_x,
                y: natural_y,
                tolerance_pct: Math.round((tolerance / MAX_COLOR_DISTANCE) * 100),
            });
        }

        const detected = (typeof StencilAutoDetect !== 'undefined')
            ? StencilAutoDetect.detectBoundingBox(
                pixel_data.image_data,
                pixel_data.natural_width,
                pixel_data.natural_height,
                natural_x,
                natural_y,
                tolerance
            )
            : null;

        if (isDebugMode()) {
            console.log('[StencilEditor] Auto-detect result', detected);
        }

        if (!detected) {
            $(`#auto-detect-tolerance-col-${rand}`).removeClass('d-none');
            return false;
        }

        last_detected_box = { box: detected, side, cropper };

        const canvas_box = boxToCanvasCoords(cropper, detected);
        if (!canvas_box) {
            return false;
        }

        cropper.getCropperSelection().$change(
            canvas_box.x,
            canvas_box.y,
            canvas_box.width,
            canvas_box.height
        );

        $(`#auto-detect-tolerance-col-${rand}`).removeClass('d-none');
        $(`#grid-replicate-${rand}`).removeClass('d-none');
        return true;
    };

    /**
     * Runs auto-detection based on the most prominent colour inside the current
     * selection of the active cropper.  Stores the seed point so the tolerance
     * slider can re-run detection on the fly.
     */
    const runAutoDetectFromSelection = function () {
        let active_cropper = null;
        let active_side = 0;
        croppers.forEach((cropper, side) => {
            const sel = cropper.getCropperSelection();
            if (sel && sel.width > 0 && sel.height > 0) {
                active_cropper = cropper;
                active_side = side;
            }
        });

        if (!active_cropper) {
            if (isDebugMode()) {
                console.log('[StencilEditor] Auto-detect from selection: no active selection');
            }
            return;
        }

        const nat_box = selectionToNaturalBox(active_cropper);
        if (!nat_box) {
            if (isDebugMode()) {
                console.log('[StencilEditor] Auto-detect from selection: could not convert selection to natural coords');
            }
            return;
        }

        const pixel_data = getCropperImageData(active_cropper);
        if (!pixel_data) {
            if (isDebugMode()) {
                console.log('[StencilEditor] Auto-detect from selection: could not get image pixel data (cross-origin?)');
            }
            return;
        }

        const x = Math.max(0, nat_box.x);
        const y = Math.max(0, nat_box.y);
        const w = Math.min(nat_box.width, pixel_data.natural_width - x);
        const h = Math.min(nat_box.height, pixel_data.natural_height - y);

        if (w <= 0 || h <= 0) {
            if (isDebugMode()) {
                console.log('[StencilEditor] Auto-detect from selection: selection out of image bounds', nat_box);
            }
            return;
        }

        if (isDebugMode()) {
            console.log('[StencilEditor] Auto-detect from selection', { nat_box, image: { w: pixel_data.natural_width, h: pixel_data.natural_height } });
        }

        const prominent = (typeof StencilAutoDetect !== 'undefined')
            ? StencilAutoDetect.getMostProminentColor(
                pixel_data.image_data,
                pixel_data.natural_width,
                x, y, w, h
            )
            : null;

        if (!prominent) {
            if (isDebugMode()) {
                console.log('[StencilEditor] Auto-detect from selection: no prominent colour found (transparent region?)');
            }
            return;
        }

        if (isDebugMode()) {
            console.log('[StencilEditor] Dominant colour', prominent.color, 'seed', { x: prominent.seed_x, y: prominent.seed_y });
        }

        last_auto_detect_seed = {
            natural_x: prominent.seed_x,
            natural_y: prominent.seed_y,
            cropper: active_cropper,
            side: active_side,
        };

        runAutoDetectAtPoint(active_cropper, active_side, prominent.seed_x, prominent.seed_y);
    };

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
            })
            .on('click', `#grid-replicate-${rand}`, () => {
                _this.openGridDialog();
            })
            .on('click', `#grid-apply-${rand}`, () => {
                _this.applyGrid();
            })
            .on('click', `#grid-cancel-${rand}`, () => {
                _this.closeGridDialog();
            })
            .on('click', `#auto-detect-btn-${rand}`, () => {
                runAutoDetectFromSelection();
            });

        // sync tolerance output display and re-run detection on change
        const tolerance_input = container.querySelector(`#auto-detect-tolerance-${rand}`);
        const tolerance_output = container.querySelector(`#auto-detect-tolerance-output-${rand}`);
        if (tolerance_input && tolerance_output) {
            tolerance_input.addEventListener('input', function () {
                tolerance_output.value = tolerance_input.value + '%';
                if (last_auto_detect_seed) {
                    runAutoDetectAtPoint(
                        last_auto_detect_seed.cropper,
                        last_auto_detect_seed.side,
                        last_auto_detect_seed.natural_x,
                        last_auto_detect_seed.natural_y
                    );
                }
            });
        }

        $(`#clear-data-${rand}`)
            .on('click', (e) => {
                const submitButton = $(`#clear-data-${rand}`);

                if (submitButton.data('delete') != '1') {
                    const originalText = submitButton.text();
                    const originalAriaLabel = submitButton.attr('aria-label');
                    const confirmText = _.unescape(_x('button', 'Are you sure?'));

                    submitButton.data('delete', '1');
                    submitButton.text(confirmText);
                    submitButton.attr('aria-label', confirmText);
                    setInterval(() => {
                        submitButton.data('delete', '0');
                        submitButton.text(originalText);
                        submitButton.attr('aria-label', originalAriaLabel);
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

        // ensure tolerance slider is visible when opening the editor
        $(container).find(`#auto-detect-tolerance-col-${rand}`).removeClass('d-none');

        last_auto_detect_seed = null;

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

        _this.bindAutoDetectClicks();
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
            .attr('aria-label', zones[zoneIndex]['label'])
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
        _this.unbindAutoDetectClicks();

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
                $(newZoneButton).attr('aria-label', String(index));
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
                zoneData.attr('aria-label', String(zoneId));
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

    // bind click events on cropper canvases for auto-detect and drag detection
    _this.bindAutoDetectClicks = function () {
        _this.unbindAutoDetectClicks();

        croppers.forEach((cropper, side) => {
            const canvas_el = cropper.getCropperCanvas();

            // detect manual drag: hide tolerance slider when user draws a selection by hand
            let drag_start_x = 0, drag_start_y = 0, is_dragging = false;
            $(canvas_el)
                .on(`pointerdown.stencil-drag-${rand}`, function (e) {
                    drag_start_x = e.clientX;
                    drag_start_y = e.clientY;
                    is_dragging = false;
                })
                .on(`pointermove.stencil-drag-${rand}`, function (e) {
                    if (e.buttons > 0) {
                        const dx = e.clientX - drag_start_x;
                        const dy = e.clientY - drag_start_y;
                        if (Math.sqrt(dx * dx + dy * dy) > 4) {
                            is_dragging = true;
                        }
                    }
                })
                .on(`pointerup.stencil-drag-${rand}`, function () {
                    if (is_dragging) {
                        $(`#auto-detect-tolerance-col-${rand}`).addClass('d-none');
                    }
                    is_dragging = false;
                });

            // auto-detect on click
            $(canvas_el).on(`click.stencil-auto-detect-${rand}`, function (e) {
                if (!_this.isEditorActive()) {
                    return;
                }

                const canvas_rect = canvas_el.getBoundingClientRect();
                const click_x = e.clientX - canvas_rect.left;
                const click_y = e.clientY - canvas_rect.top;

                const natural = canvasToNaturalCoords(cropper, click_x, click_y);
                if (!natural) {
                    return;
                }

                last_auto_detect_seed = { natural_x: natural.x, natural_y: natural.y, cropper, side };
                runAutoDetectAtPoint(cropper, side, natural.x, natural.y);
            });
        });
    };

    // unbind auto-detect click events and drag detection
    _this.unbindAutoDetectClicks = function () {
        croppers.forEach((cropper) => {
            const canvas_el = cropper.getCropperCanvas();
            $(canvas_el).off(`.stencil-auto-detect-${rand}`);
            $(canvas_el).off(`.stencil-drag-${rand}`);
        });
        $(`#grid-replicate-${rand}`).addClass('d-none');
    };

    // open grid replication dialog
    _this.openGridDialog = function () {
        $(`#grid-dialog-${rand}`).removeClass('d-none');
    };

    // close grid replication dialog
    _this.closeGridDialog = function () {
        $(`#grid-dialog-${rand}`).addClass('d-none');
    };

    // apply grid replication
    _this.applyGrid = function () {
        if (!last_detected_box) {
            return;
        }

        const cols = parseInt($(`#grid-cols-${rand}`).val(), 10) || 1;
        const rows = parseInt($(`#grid-rows-${rand}`).val(), 10) || 1;
        const spacing_x = parseInt($(`#grid-spacing-x-${rand}`).val(), 10) || 0;
        const spacing_y = parseInt($(`#grid-spacing-y-${rand}`).val(), 10) || 0;

        const pixel_data = getCropperImageData(last_detected_box.cropper);
        const img_width = pixel_data ? pixel_data.natural_width : Infinity;
        const img_height = pixel_data ? pixel_data.natural_height : Infinity;

        const boxes = (typeof StencilAutoDetect !== 'undefined')
            ? StencilAutoDetect.generateGrid(
                last_detected_box.box,
                cols,
                rows,
                spacing_x,
                spacing_y,
                img_width,
                img_height
            )
            : [];

        const side = last_detected_box.side;
        const cropper = last_detected_box.cropper;

        boxes.forEach((box, idx) => {
            const zone_buttons = $(container).find('button.set-zone-data');
            const zone_index = zone_buttons.eq(idx).data('zone-index');
            if (zone_index === undefined) {
                return;
            }

            const pct = boxToPercent(cropper, box);
            if (!pct) {
                return;
            }

            const canvas_box = boxToCanvasCoords(cropper, box);
            if (!canvas_box) {
                return;
            }

            const cr_image = cropper.getCropperImage();
            zones[zone_index] = {
                'selection': {
                    'x': canvas_box.x,
                    'y': canvas_box.y,
                    'height': canvas_box.height,
                    'width': canvas_box.width,
                },
                'image': cr_image.$getTransform(),
                'label': $(container).find(`.set-zone-data[data-zone-index="${CSS.escape(zone_index)}"]`).find('span').text() || String(zone_index),
                'number': zone_index,
                'side': side,
                'x_percent': pct.x_pct,
                'y_percent': pct.y_pct,
                'width_percent': pct.w_pct,
                'height_percent': pct.h_pct,
            };

            $(container).find(`.set-zone-data[data-zone-index="${CSS.escape(zone_index)}"]`)
                .removeClass('btn-outline-secondary btn-warning')
                .addClass('btn-success')
                .find('i').removeClass('ti-file-unknown').addClass('ti-check');
        });

        _this.closeGridDialog();
        _this.redoZones();
        _this.sendDataForm();
    };
};
