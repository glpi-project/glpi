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

/* global _ */

class Screenhot {

    constructor() {

    }

    /**
     * Listen on the file upload element to handle removal of previews when the file is removed.
     * @param {HTMLElement} fileupload_element The file upload element to listen on.
     * */
    listenOnFileUpload(fileupload_element) {
        // Need to use a mutation observer as click handlers added after the upload is done are not triggered
        // We can restrict the observer to only watch DOM removals under the fileupload element
        const $fileupload_element = $(fileupload_element);
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.removedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.id !== undefined && node.id.startsWith('doc_uploader_filename')) {
                        // Enumerate preview items and remove the one whose data-filename appears at the end of the filename (the upload process adds a prefix)
                        let filename_input = null;
                        // find the input element under the removed node's children that has a name starting with _filename
                        for (let i = 0; i < node.children.length; i++) {
                            if (node.children[i].name !== undefined && node.children[i].name.startsWith('_filename')) {
                                filename_input = node.children[i];
                                break;
                            }
                        }
                        if (filename_input === null) {
                            return;
                        }
                        $fileupload_element.closest('form').find('.upload-preview-item').each((i, item) => {
                            if (filename_input.value.endsWith($(item).data('filename'))) {
                                $(item).remove();
                            }
                        });
                    }
                });
            });
        });
        observer.observe(fileupload_element, {childList: true, subtree: true});
    }

    /**
     * Check if the browser supports and will allow the required APIs to be used.
     * This includes a secure context check and a check that the user agent is not a mobile device.
     * @return {boolean}
     */
    isSupported() {
        // if not secure context, we can't use the screen capture API
        if (!window.isSecureContext) {
            return false;
        }
        const userAgent = navigator.userAgent.toLowerCase();
        return !userAgent.match(/ipad|iphone|ipod|android/i);
    }

    /**
     * Update a preview and full-size canvas based on the supplied image.
     * The canvas is used as an in-between step to convert the image to a blob.
     *
     * @param {ImageBitmap|HTMLVideoElement} img The image or video to draw into the canvas.
     * @param {HTMLCanvasElement} canvas The canvas that temporarily stores the image/frame data.
     */
    updateCanvas(img, canvas = null) {
        const sourceWidth = img.videoWidth ?? img.width;
        const sourceHeight = img.videoHeight ?? img.height;
        canvas.width = sourceWidth;
        canvas.height = sourceHeight;
        canvas.getContext('2d').clearRect(0, 0, sourceWidth, sourceHeight);
        canvas.getContext('2d').drawImage(img, 0, 0, sourceWidth, sourceHeight, 0, 0, sourceWidth, sourceHeight);
    }

    /**
     * Prompt the user to select a screen device, grab the first frame only, and return a temporary canvas with the image within a promise.
     */
    async captureScreenshot() {
        return navigator.mediaDevices.getDisplayMedia({video: true}).then(mediaStream => {
            const track = mediaStream.getVideoTracks()[0];
            // Create new canvas outside of the DOM
            const temp_canvas = document.createElement('canvas');
            temp_canvas.width = track.getSettings().width;
            temp_canvas.height = track.getSettings().height;

            if (typeof ImageCapture !== "undefined") {
                // If the browser supports ImageCapture (preferred method, but currently experimental web API), we will use that to grab the first frame from the track.
                const imageCapture = new ImageCapture(track);
                return imageCapture.grabFrame().then(img => {
                    this.updateCanvas(img, temp_canvas);
                    track.stop();
                    return temp_canvas;
                });
            } else {
                // Fall back to using a video element to grab the first frame from the track. Currently required for Firefox.
                const video = document.createElement('video');
                video.srcObject = mediaStream;

                return new Promise((resolve, reject) => {
                    try {
                        video.addEventListener('loadeddata', () => {
                            return video.play().then(() => {
                                this.updateCanvas(video, temp_canvas);
                                track.stop();
                                resolve(temp_canvas);
                            });
                        });
                    } catch(error) {
                        track.stop();
                        reject(error);
                    }
                });
            }
        });
    }

    /**
     * Return a bitrate value based on the track's resolution and framerate, and a hard-coded motion factor.
     * @param {MediaStreamTrack} track The track
     * @return {number} Bitrate
     */
    getPreferredBitrate(track) {
        // Reference Bitrates based on YouTube recommendations
        // 360@30 - 1 Mbps   | 360@60 - 1.5 Mbps | Pixel Count - 230400
        // 720@30 - 5 Mbps   | 720@60 - 7.5 Mbps | Pixel Count - 921600
        // 1080@30 - 8 Mbps  | 1080@60 - 12 Mbps | Pixel Count - 2073600
        // 1440@30 - 16 Mbps | 1440@60 - 24 Mbps | Pixel Count - 3686400
        // 2160@30 - 40 Mbps | 2160@60 - 60 Mbps | Pixel Count - 8294400

        const motion_factor = 0.5; // Weight value. How much activity we expect
        // br = (pixels * f * motion_factor) / 10;

        const settings = track.getSettings();
        return ((settings.width * settings.height) * settings.frameRate * motion_factor) / 10;
    }

    /**
     * Get a codec supported by the browser for the requested format.
     * @param requested_format Requested mime type
     * @return {string} Codec
     */
    getRecordingCodec(requested_format) {
        const codecs = ['vp9', 'vp8'];
        return codecs.find(c => MediaRecorder.isTypeSupported(`${requested_format};codecs=${c}`));
    }

    /**
     * Prompt the user to select a screen device, start the MediaRecorder and then return the recorder.
     * Then, this will continually grab frames from the video stream at a rate of 10 FPS and update the preview canvas until the user stops the recording.
     */
    startRecording() {
        return navigator.mediaDevices.getDisplayMedia({video: true, frameRate: 10}).then(mediaStream => {
            const track = mediaStream.getVideoTracks()[0];

            const recorder = new MediaRecorder(mediaStream, {
                mimeType: `video/webm;codecs=${this.getRecordingCodec('video/webm')}`,
                videoBitsPerSecond: this.getPreferredBitrate(track),
            });
            recorder.start();
            return recorder;
        });
    }

    /**
     * Add a preview image item to the preview container.
     * @param preview_container The preview container element
     * @param canvas The canvas element with the preview data
     * @param height The height of the preview item
     * @param filename The filename of the preview item
     */
    appendPreviewImg(preview_container, canvas, height, filename) {
        const preview_item = $(`
            <div class="position-relative d-inline-block overflow-hidden upload-preview-item" data-filename="${_.escape(filename)}" style="height: ${_.escape(height)}">
                <button class="btn btn-sm btn-danger position-absolute top-0 start-0" type="button" title="${__('Delete')}">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `).appendTo(preview_container);
        const img = document.createElement('img');
        img.src = canvas.toDataURL();
        img.style.height = '200px';
        img.classList.add('mx-2');
        preview_item.append(img);

        const form = preview_container.closest('form');
        const fileupload_btn_selector = `.fileupload input[name^="_filename"][value$="${CSS.escape(filename)}"]`;
        preview_item.find('button').on('click', () => {
            form.find(fileupload_btn_selector)
                .parent().find('.ti-circle-x').click();
            preview_item.remove();
        });
    }

    /**
     * Add a preview video item to the preview container.
     * @param preview_container The preview container element
     * @param blob The blob with the video data
     * @param height The height of the preview item
     * @param filename The filename of the preview item
     */
    appendPreviewVideo(preview_container, blob, height, filename) {
        const preview_item = $(`
            <div class="position-relative d-inline-block overflow-hidden upload-preview-item" data-filename="${_.escape(filename)}" style="height: ${_.escape(height)}">
                <button class="btn btn-sm btn-danger position-absolute top-0 start-0" type="button" title="${__('Delete')}">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `).appendTo(preview_container);
        const video = document.createElement('video');
        video.src = URL.createObjectURL(blob);
        video.style.height = '200px';
        video.classList.add('mx-2');
        video.controls = true;
        preview_item.append(video);

        const form = preview_container.closest('form');
        const fileupload_btn_selector = `.fileupload input[name^="_filename"][value$="${CSS.escape(filename)}"]`;
        preview_item.find('button').on('click', () => {
            form.find(fileupload_btn_selector)
                .parent().find('.ti-circle-x').click();
            preview_item.remove();
        });
    }
}

export default new Screenhot();
