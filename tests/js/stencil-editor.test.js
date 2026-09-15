/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

require('@jest/globals');
require('/lib/cropper.js');
const StencilEditor = require('/js/stencil-editor.js');

const rand = 1234;

/**
 * Build the minimal markup of templates/stencil/editor.html.twig for a model
 * having both a front (side 0) and a rear (side 1) image.
 */
const buildEditor = (zones) => {
    document.body.innerHTML = `
        <div id="stencil-editor-${rand}">
            <form id="stencil-editor-form-${rand}">
                <input type="hidden" name="id" value="42">
                <button type="button" data-zone-index="1" class="btn set-zone-data">
                    <span>1</span><i class="ti ti-file-unknown"></i>
                </button>
                <div id="zone-data-${rand}" class="row d-none">
                    <input type="text" id="zone_label-${rand}" value="">
                    <input type="text" id="zone_number-${rand}" value="1">
                </div>
                <div id="general-submit-${rand}"></div>
            </form>
            <div class="cropper-container">
                <img src="front.png" class="stencil-image" data-side="0">
            </div>
            <div class="cropper-container">
                <img src="rear.png" class="stencil-image" data-side="1">
            </div>
        </div>
    `;

    const editor = new StencilEditor(document.getElementById(`stencil-editor-${rand}`), rand, zones);
    const images = document.querySelectorAll('.stencil-image');

    return {
        editor,
        front: images[0].cropper,
        rear: images[1].cropper,
    };
};

/**
 * jsdom has no layout engine, so canvas and image boxes must be faked to be
 * able to check the percent based coordinates.
 */
const fakeLayout = (cropper) => {
    cropper.getCropperCanvas().getBoundingClientRect = () => ({ left: 0, top: 0, width: 200, height: 100 });
    cropper.getCropperImage().getBoundingClientRect = () => ({ left: 10, top: 20, width: 200, height: 100 });
};

const selectionOf = (cropper) => {
    const selection = cropper.getCropperSelection();

    return { x: selection.x, y: selection.y, width: selection.width, height: selection.height };
};

describe('StencilEditor', () => {
    beforeEach(() => {
        window.AjaxMock.start();
    });

    afterEach(() => {
        window.AjaxMock.end();
        document.body.innerHTML = '';
    });

    it('drawing a zone on an image clears the selection made on the other one', () => {
        const { editor, front, rear } = buildEditor({});
        editor.editorEnable(1);

        front.getCropperSelection().$change(10, 10, 20, 20);
        expect(selectionOf(front)).toEqual({ x: 10, y: 10, width: 20, height: 20 });

        rear.getCropperSelection().$change(30, 40, 50, 60);

        expect(selectionOf(front)).toEqual({ x: 0, y: 0, width: 0, height: 0 });
        expect(selectionOf(rear)).toEqual({ x: 30, y: 40, width: 50, height: 60 });
    });

    it('an empty selection does not clear the other image', () => {
        const { editor, front, rear } = buildEditor({});
        editor.editorEnable(1);

        front.getCropperSelection().$change(10, 10, 20, 20);

        // a simple click on the other image produces an empty selection
        rear.getCropperSelection().$clear();

        expect(selectionOf(front)).toEqual({ x: 10, y: 10, width: 20, height: 20 });
    });

    it('saves the last drawn zone, whatever the image it has been drawn on', () => {
        const zones = {};
        const { editor, front, rear } = buildEditor(zones);
        fakeLayout(rear);

        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/stencil.php', 'POST', {}, () => ''));

        editor.editorEnable(1);
        $(`#zone_label-${rand}`).val('Port 1');
        front.getCropperSelection().$change(10, 10, 20, 20);
        rear.getCropperSelection().$change(30, 40, 50, 60);
        editor.saveZoneData();

        expect(zones[1]).toMatchObject({
            selection: { x: 30, y: 40, width: 50, height: 60 },
            side: 1,
            label: 'Port 1',
            number: '1',
            x_percent: 10,
            y_percent: 20,
            width_percent: 25,
            height_percent: 60,
        });
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });
});
