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

import Screenshot from '/js/modules/Screenshot/Screenshot.js';
import {jest} from '@jest/globals';

describe('Screenshot', () => {
    let user_agent_getter;

    beforeEach(() => {
        user_agent_getter = jest.spyOn(navigator, 'userAgent', 'get');
        window.isSecureContext = true;
        $('body').empty();
    });

    test('getPreferredBitrate', () => {
        expect(Screenshot.getPreferredBitrate({
            getSettings: () => {
                return {
                    width: 1920,
                    height: 1080,
                    frameRate: 10
                };
            }
        })).toBe(1036800);
        expect(Screenshot.getPreferredBitrate({
            getSettings: () => {
                return {
                    width: 1280,
                    height: 720,
                    frameRate: 30
                };
            }
        })).toBe(1382400);
    });

    test('Secure context support check', () => {
        window.isSecureContext = false;
        expect(Screenshot.isSupported()).toBe(false);
        window.isSecureContext = true;
        expect(Screenshot.isSupported()).toBe(true);
    });

    test('Mobile browser support check', () => {
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36');
        expect(Screenshot.isSupported()).toBe(false);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Linux; Android 13; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36');
        expect(Screenshot.isSupported()).toBe(false);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (iPhone14,3; U; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19A346 Safari/602.1');
        expect(Screenshot.isSupported()).toBe(false);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (iPhone12,1; U; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1');
        expect(Screenshot.isSupported()).toBe(false);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Linux; Android 11; Lenovo YT-J706X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36');
        expect(Screenshot.isSupported()).toBe(false);
    });

    test('Desktop browser support check', () => {
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246');
        expect(Screenshot.isSupported()).toBe(true);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36');
        expect(Screenshot.isSupported()).toBe(true);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9');
        expect(Screenshot.isSupported()).toBe(true);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36');
        expect(Screenshot.isSupported()).toBe(true);
        user_agent_getter.mockReturnValue('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1');
        expect(Screenshot.isSupported()).toBe(true);
    });

    test('Get recording codec', () => {
        // MediaRecorder not defined in jest environment
        window.MediaRecorder = {
            isTypeSupported: () => {
            }
        };
        const type_supported = jest.spyOn(window.MediaRecorder, 'isTypeSupported');
        type_supported.mockReturnValueOnce(true);
        expect(Screenshot.getRecordingCodec('video/webm')).toBe('vp9');
        type_supported.mockReturnValueOnce(false);
        type_supported.mockReturnValueOnce(true);
        expect(Screenshot.getRecordingCodec('video/webm')).toBe('vp8');
    });

    test('Show screenshot preview', () => {
        $('body').append('<div id="screenshot-previews"></div>');
        const fake_canvas = {
            toDataURL: () => {
                return 'data:image/png;base64,FAKE_BASE64_DATA';
            }
        };
        Screenshot.appendPreviewImg($('#screenshot-previews'), fake_canvas, '200px', 'test.png');
        expect($('#screenshot-previews').children().length).toBe(1);
        const preview_item = $('#screenshot-previews').children().first();
        expect(preview_item.hasClass('position-relative')).toBe(true);
        expect(preview_item.css('height')).toBe('200px');
        expect(preview_item.children().length).toBe(2);
        const delete_button = preview_item.children().first();
        expect(delete_button.hasClass('btn')).toBe(true);
        const img = preview_item.children().last();
        expect(img.prop('tagName')).toBe('IMG');
        expect(img.css('height')).toBe('200px');
        expect(img.attr('src')).toBe('data:image/png;base64,FAKE_BASE64_DATA');
    });

    test('Show recording preview', () => {
        const fake_blob = new Blob(['FAKE_BLOB_DATA'], {type: 'video/webm'});
        $('body').append('<div id="screenshot-previews"></div>');
        URL.createObjectURL = jest.fn().mockReturnValueOnce('blob:FAKE_BLOB_DATA');
        Screenshot.appendPreviewVideo($('#screenshot-previews'), fake_blob, '200px', 'test.webm');
        expect($('#screenshot-previews').children().length).toBe(1);
        const preview_item = $('#screenshot-previews').children().first();
        expect(preview_item.hasClass('position-relative')).toBe(true);
        expect(preview_item.css('height')).toBe('200px');
        expect(preview_item.children().length).toBe(2);
        const delete_button = preview_item.children().first();
        expect(delete_button.hasClass('btn')).toBe(true);
        const video = preview_item.children().last();
        expect(video.prop('tagName')).toBe('VIDEO');
        expect(video.css('height')).toBe('200px');
        expect(video.attr('src')).toBe('blob:FAKE_BLOB_DATA');
        expect(video.attr('controls')).toBeDefined();
    });

    test('Screenshot preview delete button', () => {
        $('body').append(`
            <form>
                <div id="screenshot-previews"></div>
                <div class="fileupload">
                    <div>
                        <input type="hidden" name="_filename[0]" value="randomprefixtest.png">
                        <span class="ti ti-circle-x" onclick="$(this).parent().remove()"></span>
                    </div>
                </div>
            </form>
        `);
        const fake_canvas = {
            toDataURL: () => {
                return 'data:image/png;base64,FAKE_BASE64_DATA';
            }
        };
        Screenshot.appendPreviewImg($('#screenshot-previews'), fake_canvas, '200px', 'test.png');
        const delete_button = $('#screenshot-previews').find('button').first();
        delete_button.click();
        expect($('#screenshot-previews').children().length).toBe(0);
        expect($('#screenshot-previews').parent().find('.fileupload').children().length).toBe(0);
    });

    test('Recording preview delete button', () => {
        $('body').append(`
            <form>
                <div id="screenshot-previews"></div>
                <div class="fileupload">
                    <div>
                        <input type="hidden" name="_filename[0]" value="randomprefixtest.webm">
                        <span class="ti ti-circle-x" onclick="$(this).parent().remove()"></span>
                    </div>
                </div>
            </form>
        `);
        const fake_blob = new Blob(['FAKE_BLOB_DATA'], {type: 'video/webm'});
        Screenshot.appendPreviewVideo($('#screenshot-previews'), fake_blob, '200px', 'test.webm');
        const delete_button = $('#screenshot-previews').find('button').first();
        delete_button.click();
        expect($('#screenshot-previews').children().length).toBe(0);
        expect($('#screenshot-previews').parent().find('.fileupload').children().length).toBe(0);
    });

    test('Screenshot preview removed after file deleted', async () => {
        $('body').append(`
            <form>
                <div id="screenshot-previews"></div>
                <div class="fileupload">
                    <div id="doc_uploader_filename89f8sef9s8df9j">
                        <input type="hidden" name="_filename[0]" value="randomprefixtest.png">
                        <span class="ti ti-circle-x" onclick="$(this).parent().remove()"></span>
                    </div>
                </div>
            </form>
        `);
        const fake_canvas = {
            toDataURL: () => {
                return 'data:image/png;base64,FAKE_BASE64_DATA';
            }
        };
        Screenshot.listenOnFileUpload(document.querySelector('.fileupload'));
        Screenshot.appendPreviewImg($('#screenshot-previews'), fake_canvas, '200px', 'test.png');
        expect($('#screenshot-previews').children().length).toBe(1);
        $('.fileupload .ti-circle-x').click();
        await new Promise(process.nextTick);
        expect($('#screenshot-previews').children().length).toBe(0);
    });

    test('Recording preview removed after file deleted', async () => {
        $('body').append(`
            <form>
                <div id="screenshot-previews"></div>
                <div class="fileupload">
                    <div id="doc_uploader_filename89f8sef9s8df9j">
                        <input type="hidden" name="_filename[0]" value="randomprefixtest.webm">
                        <span class="ti ti-circle-x" onclick="$(this).parent().remove()"></span>
                    </div>
                </div>
            </form>
        `);
        const fake_blob = new Blob(['FAKE_BLOB_DATA'], {type: 'video/webm'});
        Screenshot.listenOnFileUpload(document.querySelector('.fileupload'));
        Screenshot.appendPreviewVideo($('#screenshot-previews'), fake_blob, '200px', 'test.webm');
        expect($('#screenshot-previews').children().length).toBe(1);
        $('.fileupload .ti-circle-x').click();
        await new Promise(process.nextTick);
        expect($('#screenshot-previews').children().length).toBe(0);
    });
});
