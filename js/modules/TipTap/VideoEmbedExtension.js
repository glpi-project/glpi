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

/* global TiptapCore */

import { createEditorDialog, createDialogField } from '/js/modules/TipTap/EditorDialog.js';

/**
 * VideoEmbed Tiptap node — stores videos as inert
 * `<div data-video-provider data-video-id></div>` placeholders (YouTube), or
 * `<div data-video-provider="video" data-video-src></div>` for a direct video
 * file URL. The iframe / `<video>` is rebuilt server-side
 * (RichText\VideoEmbedRenderer) or in the nodeView, never stored, so user
 * content never injects media through the sanitizer.
 *
 * YouTube URL-parsing regexes adapted from the MIT @tiptap/extension-youtube
 * package (Copyright (c) Tiptap GmbH).
 */

const { Node, mergeAttributes } = TiptapCore;

// Direct video files are stored under this synthetic provider key, with the URL
// in data-video-src instead of an id.
const DIRECT_VIDEO_PROVIDER = 'video';

const ALLOWED_PROVIDERS = new Set(['youtube', DIRECT_VIDEO_PROVIDER]);
const VALID_ID_PATTERN = /^[A-Za-z0-9_-]{1,32}$/;
// Allowlisted extensions for a directly embedded <video> file URL.
const VIDEO_FILE_EXTENSION_PATTERN = /\.(?:mp4|webm|ogg|ogv|mov)$/i;

// Mirror of VideoEmbedRenderer::getProviderDisplayName — provider brand names
// aren't translated, so the values are safe to use as accessible-name fragments.
const PROVIDER_DISPLAY_NAMES = {
    youtube: 'YouTube',
};

/**
 * Validate a direct video file URL: http(s) scheme + allowlisted extension.
 *
 * @param {string} src
 * @returns {boolean}
 */
function isValidDirectVideoSrc(src) {
    if (typeof src !== 'string' || src.length === 0) {
        return false;
    }
    let url;
    try {
        url = new URL(src.trim());
    } catch {
        return false;
    }
    return (url.protocol === 'http:' || url.protocol === 'https:')
        && VIDEO_FILE_EXTENSION_PATTERN.test(url.pathname);
}

/**
 * @param {URL} url
 * @returns {{provider: string, videoId: string}|null}
 */
function parseYouTubeUrl(url) {
    const host = url.hostname.replace(/^www\./, '');

    if (host === 'youtu.be') {
        const id = url.pathname.slice(1).split('/')[0];
        return VALID_ID_PATTERN.test(id)
            ? { provider: 'youtube', videoId: id }
            : null;
    }

    if (host !== 'youtube.com' && host !== 'music.youtube.com' && host !== 'm.youtube.com') {
        return null;
    }

    if (url.pathname === '/watch') {
        const id = url.searchParams.get('v');
        return id && VALID_ID_PATTERN.test(id)
            ? { provider: 'youtube', videoId: id }
            : null;
    }

    const pathMatch = url.pathname.match(/^\/(?:shorts|embed|live|v)\/([^/?]+)/);
    if (pathMatch && VALID_ID_PATTERN.test(pathMatch[1])) {
        return { provider: 'youtube', videoId: pathMatch[1] };
    }

    return null;
}

/**
 * Recognize a direct video file URL (http(s) + allowlisted extension).
 *
 * @param {URL} url
 * @returns {{provider: string, videoId: null, src: string}|null}
 */
function parseDirectVideoUrl(url) {
    if (!VIDEO_FILE_EXTENSION_PATTERN.test(url.pathname)) {
        return null;
    }
    return { provider: DIRECT_VIDEO_PROVIDER, videoId: null, src: url.href };
}

/**
 * Parse any supported video URL into normalized attrs, or null if unrecognized.
 *
 * @param {string} rawUrl
 * @returns {{provider: string, videoId: string|null, src?: string}|null}
 */
function parseVideoUrl(rawUrl) {
    if (typeof rawUrl !== 'string' || rawUrl.length === 0) {
        return null;
    }
    let url;
    try {
        url = new URL(rawUrl.trim());
    } catch {
        return null;
    }
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return null;
    }
    return parseYouTubeUrl(url)
        || parseDirectVideoUrl(url);
}

/**
 * Display the Insert Video dialog. On confirm, parses the URL and inserts a
 * videoEmbed node at the current selection.
 *
 * @param {object} editor - Tiptap editor instance
 */
export function showVideoDialog(editor) {
    const uid = Math.random().toString(36).slice(2, 9);

    const url_group = createDialogField(__('Video URL'), 'url', `video-url-${uid}`, '');
    const url_input = url_group.querySelector('input');
    url_input.placeholder = 'https://www.youtube.com/watch?v=...';
    url_input.setAttribute('autocomplete', 'off');

    const help = document.createElement('p');
    help.className = 'text-muted small mt-1 mb-0';
    help.textContent = __('Supported: a YouTube URL, or a direct video file URL (MP4, WebM, Ogg).');

    const error_msg = document.createElement('p');
    error_msg.className = 'text-danger small mt-1 mb-0';
    error_msg.style.display = 'none';

    // showError() guards against re-mutating the live region on every
    // keystroke: re-assigning textContent on an aria-live node makes some
    // screen readers announce again.
    const showError = () => {
        if (error_msg.style.display !== 'none') {
            return;
        }
        error_msg.textContent = __('This video URL is not recognized. Use a YouTube URL or a direct video file URL (MP4, WebM, Ogg).');
        error_msg.setAttribute('role', 'alert');
        error_msg.style.display = '';
    };
    const hideError = () => {
        error_msg.removeAttribute('role');
        error_msg.style.display = 'none';
    };

    url_input.addEventListener('input', () => {
        const value = url_input.value.trim();
        if (value === '' || parseVideoUrl(value)) {
            hideError();
            return;
        }
        showError();
    });

    const { body } = createEditorDialog({
        editor,
        title: __('Insert video'),
        confirmLabel: __('Insert'),
        onConfirm: ({ close }) => {
            const attrs = parseVideoUrl(url_input.value.trim());
            if (!attrs) {
                showError();
                url_input.focus();
                return;
            }
            editor.chain().focus().insertContent({
                type: 'videoEmbed',
                attrs,
            }).run();
            close();
        },
    });

    body.appendChild(url_group);
    body.appendChild(help);
    body.appendChild(error_msg);

    url_input.focus();
}

/**
 * Mirrors VideoEmbedRenderer::PROVIDER_URL_TEMPLATES. Privacy-friendly default
 * (youtube-nocookie).
 */
const EMBED_URL_TEMPLATES = {
    youtube: 'https://www.youtube-nocookie.com/embed/{id}',
};

/**
 * @param {string} provider
 * @param {string} videoId
 * @returns {string|null}
 */
function buildEmbedSrc(provider, videoId) {
    const template = EMBED_URL_TEMPLATES[provider];
    if (!template || !VALID_ID_PATTERN.test(videoId || '')) {
        return null;
    }
    return template.replace('{id}', encodeURIComponent(videoId));
}

/**
 * Reverse of buildEmbedSrc — used when re-entering the editor over already
 * rendered HTML from the server's `|safe_html` filter.
 *
 * @param {string} src
 * @returns {{provider: string, videoId: string}|null}
 */
function parseEmbedSrc(src) {
    if (typeof src !== 'string') {
        return null;
    }
    const patterns = [
        { provider: 'youtube', re: /^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([^/?#&]+)/ },
    ];
    for (const { provider, re } of patterns) {
        const m = src.match(re);
        if (m && VALID_ID_PATTERN.test(m[1])) {
            return { provider, videoId: m[1] };
        }
    }
    return null;
}

/**
 * Render the videoEmbed node inside the editor as the live media element — used
 * both in edit mode and when Tiptap re-hydrates over the readonly view. YouTube
 * becomes a sandboxed iframe; a direct file URL becomes a `<video>`.
 *
 * @param {object} node - ProseMirror node
 * @returns {HTMLElement}
 */
function buildEditorPreview(node) {
    const isDirect = node.attrs.provider === DIRECT_VIDEO_PROVIDER;
    const providerName = PROVIDER_DISPLAY_NAMES[node.attrs.provider] || null;

    const wrapper = document.createElement('div');
    wrapper.className = 'video-embed-wrapper';
    wrapper.contentEditable = 'false';
    wrapper.setAttribute('role', 'figure');
    wrapper.setAttribute('data-video-provider', node.attrs.provider);

    let media = null;
    if (isDirect) {
        wrapper.setAttribute('aria-label', __('Video'));
        if (isValidDirectVideoSrc(node.attrs.src)) {
            wrapper.setAttribute('data-video-src', node.attrs.src);
            media = document.createElement('video');
            media.src = node.attrs.src;
            media.title = __('Embedded video');
            media.controls = true;
            media.preload = 'metadata';
        }
    } else {
        const src = buildEmbedSrc(node.attrs.provider, node.attrs.videoId);
        wrapper.setAttribute(
            'aria-label',
            providerName ? `${providerName} ${__('video')}` : __('Invalid video')
        );
        if (node.attrs.videoId) {
            wrapper.setAttribute('data-video-id', node.attrs.videoId);
        }
        if (src) {
            media = document.createElement('iframe');
            media.src = src;
            media.loading = 'lazy';
            media.title = providerName ? `${providerName} ${__('video player')}` : __('video player');
            media.setAttribute('allowfullscreen', '');
            media.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
        }
    }

    if (!media) {
        // Fallback: provider/id/src invalid → show the dashed placeholder so the
        // author still sees something they can delete.
        wrapper.classList.add('video-embed-edit-placeholder');
        const inner = document.createElement('div');
        inner.className = 'video-embed-placeholder-inner';
        const icon = document.createElement('i');
        icon.className = 'ti ti-alert-triangle video-embed-placeholder-icon';
        inner.appendChild(icon);
        const label = document.createElement('div');
        label.className = 'video-embed-placeholder-label';
        label.textContent = __('Invalid video');
        inner.appendChild(label);
        wrapper.appendChild(inner);
        return wrapper;
    }

    wrapper.appendChild(media);
    return wrapper;
}

/**
 * Video embed Tiptap node.
 */
export const VideoEmbed = Node.create({
    name: 'videoEmbed',
    group: 'block',
    atom: true,
    draggable: true,
    selectable: true,

    addAttributes() {
        return {
            provider: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-video-provider'),
                renderHTML: (attrs) => attrs.provider
                    ? { 'data-video-provider': attrs.provider }
                    : {},
            },
            videoId: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-video-id'),
                renderHTML: (attrs) => attrs.videoId
                    ? { 'data-video-id': attrs.videoId }
                    : {},
            },
            src: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-video-src'),
                renderHTML: (attrs) => attrs.src
                    ? { 'data-video-src': attrs.src }
                    : {},
            },
        };
    },

    parseHTML() {
        return [
            {
                // Canonical storage form (the inert placeholder div).
                tag: 'div[data-video-provider]',
                getAttrs: (dom) => {
                    const provider = dom.getAttribute('data-video-provider');
                    if (!ALLOWED_PROVIDERS.has(provider)) {
                        return false;
                    }
                    if (provider === DIRECT_VIDEO_PROVIDER) {
                        return isValidDirectVideoSrc(dom.getAttribute('data-video-src')) ? null : false;
                    }
                    const videoId = dom.getAttribute('data-video-id');
                    return videoId && VALID_ID_PATTERN.test(videoId) ? null : false;
                },
            },
            {
                // Rendered form coming from the server's `|safe_html` filter
                // (re-entering edit mode over the already-materialized media).
                tag: 'div.video-embed-wrapper',
                getAttrs: (dom) => {
                    const iframe = dom.querySelector('iframe');
                    if (iframe) {
                        return parseEmbedSrc(iframe.getAttribute('src')) || false;
                    }
                    const video = dom.querySelector('video');
                    if (video) {
                        const src = video.getAttribute('src');
                        return isValidDirectVideoSrc(src)
                            ? { provider: DIRECT_VIDEO_PROVIDER, videoId: null, src }
                            : false;
                    }
                    return false;
                },
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes({ class: 'video-embed' }, HTMLAttributes)];
    },

    addNodeView() {
        return ({ node }) => ({
            dom: buildEditorPreview(node),
        });
    },

    addCommands() {
        return {
            setVideoEmbed: (attrs) => ({ chain }) => {
                if (!attrs || !ALLOWED_PROVIDERS.has(attrs.provider)) {
                    return false;
                }
                const valid = attrs.provider === DIRECT_VIDEO_PROVIDER
                    ? isValidDirectVideoSrc(attrs.src)
                    : VALID_ID_PATTERN.test(attrs.videoId);
                if (!valid) {
                    return false;
                }
                return chain().insertContent({ type: this.name, attrs }).run();
            },
        };
    },
});
