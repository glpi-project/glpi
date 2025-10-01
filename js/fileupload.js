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

/* eslint no-var: 0 */
/* global getExtIcon, getSize, isImage, stopEvent, _ */

var insertIntoEditor = []; // contains flags that indicate if uploaded file (image) should be added to editor contents

var uploaded_images = []; // Mapping between random identifier and image filename

function uploadFile(file, editor) {
    insertIntoEditor[file.name] = isImage(file);

    // Search for fileupload container.
    // First try to find an uplaoder having same name as editor element.
    var uploader = $(`[data-uploader-name="${CSS.escape(editor.getElement().name)}"]`);
    if (uploader.length === 0) {
        // Fallback to uploader using default name
        uploader = $(editor.getElement()).closest('form').find('[data-uploader-name="filename"]');
    }
    if (uploader.length === 0) {
        // Fallback to any uploader found in current form
        uploader = $(editor.getElement()).closest('form').find('[data-uploader-name=]').first();
    }

    uploader.fileupload('add', {files: [file]});
}

var handleUploadedFile = function (files, files_data, input_name, container, editor_id) {
    $.ajax(
        {
            type: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/getFileTag.php`,
            data: {data: files_data},
            dataType: 'JSON',
            success: function(tags) {
                $.each(
                    files,
                    (index, file) => {
                        if (files_data[index].error !== undefined) {
                            container.parent().find('.uploadbar')
                                .text(files_data[index].error)
                                .css('width', '100%');
                            return;
                        }

                        var tag_data = tags[index];

                        var editor = null;
                        if (editor_id) {
                            editor = tinyMCE.get(editor_id);
                            const uploaded_image = uploaded_images.find((entry) => entry.filename === file.name);
                            const matching_image = uploaded_image !== undefined
                                ? editor.dom.select(`img[data-upload_id="${CSS.escape(uploaded_image.upload_id)}"]`)
                                : [];
                            if (matching_image.length > 0) {
                                editor.dom.setAttrib(matching_image, 'id', tag_data.tag.replace(/#/g, ''));
                            }
                        }

                        displayUploadedFile(files_data[index], tag_data, editor, input_name, container);

                        container.parent().find('.uploadbar')
                            .text(__('Upload successful'))
                            .css('width', '100%')
                            .delay(2000)
                            .fadeOut('slow');
                    }
                );
            },
            error: function (request) {
                console.warn(request.responseText);
            },
            complete: function () {
                $.each(
                    files,
                    (index, file) => {
                        delete(insertIntoEditor[file.name]);
                    }
                );
            }
        }
    );
};

/**
 * Display list of uploaded file with their size
 *
 * @param      {JSON}    file          The file
 * @param      {String}  tag           The tag
 * @param      {Object}  editor        The TinyMCE editor instance
 * @param      {String}  input_name    Name of generated input hidden (default filename)
 * @param      {Object}  container     The fileinfo container
 */
var displayUploadedFile = function(file, tag, editor, input_name, filecontainer) {
    var fileindex = $(`input[name^="_${CSS.escape(input_name)}["]`).length;
    var ext = file.name.split('.').pop();

    var p = $('<p></p>')
        .attr('id',file.id)
        .html(`${getExtIcon(ext)}&nbsp;<b>${_.escape(file.display)}</b>&nbsp;(${getSize(file.size)})&nbsp;`).appendTo(filecontainer);

    // File
    $('<input/>')
        .attr('type', 'hidden')
        .attr('name', `_${input_name}[${fileindex}]`)
        .attr('value', file.name).appendTo(p);

    // Prefix
    $('<input/>')
        .attr('type', 'hidden')
        .attr('name', `_prefix_${input_name}[${fileindex}]`)
        .attr('value', file.prefix).appendTo(p);

    // Tag
    $('<input/>')
        .attr('type', 'hidden')
        .attr('name', `_tag_${input_name}[${fileindex}]`)
        .attr('value', tag.name)
        .appendTo(p);

    // Delete button
    var elementsIdToRemove = {0:file.id, 1:`${file.id}2`};
    $('<span class="ti ti-circle-x pointer remove_file_upload"></span>').on('click', () => {
        deleteImagePasted(elementsIdToRemove, tag.tag, editor);

        // Trigger an event to notify that an image has been removed
        $(document).trigger('glpi_fileupload_remove', {
            elementsIdToRemove: elementsIdToRemove,
            tagToRemove: tag.tag,
            editor: editor
        });
    }).appendTo(p);
};

/**
 * Remove image pasted or droped
 *
 * @param      {Array}   elementsIdToRemove  The elements identifier to remove
 * @param      {string}  tagToRemove         The tag to remove
 * @param      {Object}  editor              The editor
 */
var deleteImagePasted = function(elementsIdToRemove, tagToRemove, editor) {
    // Remove file display lines
    $.each(elementsIdToRemove, (index, element) => {
        $(`#${CSS.escape(element)}`).remove();
    });

    if (typeof editor !== "undefined" && editor !== null
       && typeof editor.dom !== "undefined") {
        var regex = new RegExp('#', 'g');
        editor.dom.remove(tagToRemove.replace(regex, ''));
    }
};

/**
 * Set given rich text editor content.
 */
const setRichTextEditorContent = function(editor_id, content) {
    if (typeof tinyMCE === 'undefined') {
        return;
    }
    const editor = tinyMCE.get(editor_id);
    if (editor) {
        editor.setContent('');
        // use paste command to force images registering
        editor.execCommand('mceInsertClipboardContent', false, {
            html: content,
            internal: true, // disable some filterings operations that would remove styles (maybe a bug)
        });
        // force trigger of event handlers that will save editor contents
        // and remove "required" state
        editor.fire('keyup');
    }
};

/**
 * Plugin for tinyMce editor who intercept paste event
 * to check if a file upload can be proceeded
 * @param  {[Object]} editor TinyMCE editor
 */
if (typeof tinyMCE != 'undefined') {
    tinyMCE.PluginManager.add('glpi_upload_doc', (editor) => {
        let last_paste_content = null;
        const rtf_img_types = {
            'pngblip': 'image/png',
            'jpegblip': 'image/jpeg',
        };
        editor.on('paste', (e) => {
            last_paste_content = e.clipboardData;
        });
        editor.on('PastePreProcess', (event) => {
            const base64_img_contents = [];
            if (last_paste_content !== null && last_paste_content.types.includes('text/rtf')) {
                // Extract all RTF images and remove line breaks
                const rtf_content = last_paste_content.getData('text/rtf');
                const rtf_content_no_line_break = rtf_content.replace(/(\r\n|\n|\r)/gm, "");
                const hex_binary = rtf_content_no_line_break.matchAll(/\\(pngblip|jpegblip)([a-z0-9]*)}/g);

                // For each match, convert to base64
                for (const match of hex_binary) {
                    const img_type = match[1];
                    const hex = match[2];
                    const hexToBase64 = (hexstring) => {
                        return btoa(hexstring.match(/\w{2}/g).map((a) => {
                            return String.fromCharCode(parseInt(a, 16));
                        }).join(""));
                    };
                    base64_img_contents.push({
                        type: rtf_img_types[img_type],
                        content: hexToBase64(hex)
                    });
                }
            }
            // Trigger upload process for each pasted image
            var fragment = $('<div></div>');
            fragment.append(event.content);
            fragment.find('img').each(function() {
                const image = $(this);
                let src = image.attr('src');
                const file_pattern = '^file://';

                if (src.match(file_pattern) !== null && base64_img_contents.length > 0) {
                    const rtf_content = base64_img_contents.shift();
                    src = `data:${rtf_content['type']};base64,${rtf_content['content']}`;
                    image.attr('src', src);
                }
                if (src.match(new RegExp('^(data|blob):')) !== null) {
                    const upload_id = Math.random().toString();
                    image.attr('data-upload_id', upload_id);
                    fetch(src).then((response) => {
                        return response.blob();
                    }
                    ).then((file) => {
                        if (/^image\/.+/.test(file.type) === false) {
                            return; //only process images
                        }

                        // In Firefox, when fetching a `blob://` URI genrated by a unique file pasting,
                        // `response.blob()` returns a `File`, instead of a `Blob`, with a read-only `name` property.
                        // So, to be able to force file.name, it have to be converted into a `Blob`.
                        if (file instanceof File) {
                            file = new Blob([file], {type: file.type});
                        }

                        const ext = file.type.replace('image/', '');
                        file.name = `image_paste${Math.floor((Math.random() * 10000000) + 1)}.${  ext}`;
                        uploaded_images.push(
                            {
                                upload_id: upload_id,
                                filename:  file.name
                            }
                        );
                        uploadFile(file, editor);
                    });
                }
            });

            // Update HTML to paste to include "data-upload_id" attributes on images.
            event.content = fragment.html();
        });
    });
}


$(() => {
    // set a function to track drag hover event
    $(document).bind('dragover', (event) => {
        event.preventDefault();

        var dropZone = $('.dropzone');
        var foundDropzone;
        var timeout = window.dropZoneTimeout;

        if (!timeout) {
            dropZone.addClass('dragin');
        } else {
            clearTimeout(timeout);
        }

        var found = false;
        var node = event.target;

        do {
            if ($(node).hasClass('draghoverable')) {
                found = true;
                foundDropzone = $(node);
                break;
            }

            node = node.parentNode;
        } while (node !== null);

        dropZone.removeClass('dragin draghover');

        if (found) {
            foundDropzone.addClass('draghover');
        }
    });

    // remove dragover styles on drop
    $(document).bind('drop', (event) => {
        event.preventDefault();
        $('.draghoverable').removeClass('draghover');
    });
});
