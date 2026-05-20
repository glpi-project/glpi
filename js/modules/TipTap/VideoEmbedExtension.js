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

/**
 * VideoEmbed Tiptap node — stores videos as inert
 * `<div data-video-provider data-video-id></div>` placeholders. The iframe is
 * rebuilt server-side (RichText\VideoEmbedRenderer) or in the nodeView, never
 * stored, so user content never injects an iframe through the sanitizer.
 *
 * YouTube URL-parsing regexes adapted from the MIT @tiptap/extension-youtube
 * package (Copyright (c) Tiptap GmbH).
 */

const { Node, mergeAttributes } = TiptapCore;

const ALLOWED_PROVIDERS = new Set(['youtube', 'dailymotion', 'vimeo']);
const VALID_ID_PATTERN = /^[A-Za-z0-9_-]{1,32}$/;

/**
 * Parse a "t=" / "start=" query parameter into seconds. Accepts plain seconds
 * ("90") and the YouTube-style "1h2m3s" format.
 *
 * @param {URL} url
 * @returns {number|null}
 */
function parseStartParam(url) {
    const raw = url.searchParams.get('t') || url.searchParams.get('start');
    if (!raw) {
        return null;
    }
    if (/^\d+$/.test(raw)) {
        const n = parseInt(raw, 10);
        return n > 0 ? n : null;
    }
    const matches = raw.match(/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/);
    if (!matches) {
        return null;
    }
    const h = parseInt(matches[1] || '0', 10);
    const m = parseInt(matches[2] || '0', 10);
    const s = parseInt(matches[3] || '0', 10);
    const total = h * 3600 + m * 60 + s;
    return total > 0 ? total : null;
}

/**
 * @param {URL} url
 * @returns {{provider: string, videoId: string, start: number|null}|null}
 */
function parseYouTubeUrl(url) {
    const host = url.hostname.replace(/^www\./, '');

    if (host === 'youtu.be') {
        const id = url.pathname.slice(1).split('/')[0];
        return VALID_ID_PATTERN.test(id)
            ? { provider: 'youtube', videoId: id, start: parseStartParam(url) }
            : null;
    }

    if (host !== 'youtube.com' && host !== 'music.youtube.com' && host !== 'm.youtube.com') {
        return null;
    }

    if (url.pathname === '/watch') {
        const id = url.searchParams.get('v');
        return id && VALID_ID_PATTERN.test(id)
            ? { provider: 'youtube', videoId: id, start: parseStartParam(url) }
            : null;
    }

    const pathMatch = url.pathname.match(/^\/(?:shorts|embed|live|v)\/([^/?]+)/);
    if (pathMatch && VALID_ID_PATTERN.test(pathMatch[1])) {
        return { provider: 'youtube', videoId: pathMatch[1], start: parseStartParam(url) };
    }

    return null;
}

/**
 * @param {URL} url
 * @returns {{provider: string, videoId: string, start: number|null}|null}
 */
function parseDailymotionUrl(url) {
    const host = url.hostname.replace(/^www\./, '');

    if (host === 'dai.ly') {
        const id = url.pathname.slice(1).split(/[/?]/)[0];
        return VALID_ID_PATTERN.test(id)
            ? { provider: 'dailymotion', videoId: id, start: null }
            : null;
    }

    if (host !== 'dailymotion.com') {
        return null;
    }

    const match = url.pathname.match(/^\/(?:video|embed\/video)\/([^/?]+)/);
    if (match && VALID_ID_PATTERN.test(match[1])) {
        return { provider: 'dailymotion', videoId: match[1], start: null };
    }

    return null;
}

/**
 * @param {URL} url
 * @returns {{provider: string, videoId: string, start: number|null}|null}
 */
function parseVimeoUrl(url) {
    const host = url.hostname.replace(/^www\./, '');

    if (host === 'player.vimeo.com') {
        const match = url.pathname.match(/^\/video\/(\d+)/);
        return match && VALID_ID_PATTERN.test(match[1])
            ? { provider: 'vimeo', videoId: match[1], start: null }
            : null;
    }

    if (host !== 'vimeo.com') {
        return null;
    }

    // Last numeric segment — handles /ID, /channels/X/ID, /album/X/video/ID, etc.
    const segments = url.pathname.split('/').filter(Boolean);
    for (let i = segments.length - 1; i >= 0; i--) {
        if (/^\d+$/.test(segments[i]) && VALID_ID_PATTERN.test(segments[i])) {
            return { provider: 'vimeo', videoId: segments[i], start: null };
        }
    }

    return null;
}

/**
 * Parse any supported video URL into normalized attrs, or null if unrecognized.
 *
 * @param {string} rawUrl
 * @returns {{provider: string, videoId: string, start: number|null}|null}
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
        || parseDailymotionUrl(url)
        || parseVimeoUrl(url);
}

/**
 * Display the Insert Video dialog. On confirm, parses the URL and inserts a
 * videoEmbed node at the current selection.
 *
 * @param {object} editor - Tiptap editor instance
 */
export function showVideoDialog(editor) {
    const overlay = document.createElement('div');
    overlay.className = 'image-dialog-overlay video-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'image-dialog video-dialog';

    const header = document.createElement('div');
    header.className = 'image-dialog-header';
    const headerTitle = document.createElement('span');
    headerTitle.textContent = __('Insert video');
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'image-dialog-close';
    closeBtn.setAttribute('aria-label', __('Close'));
    const closeIcon = document.createElement('i');
    closeIcon.className = 'ti ti-x';
    closeBtn.appendChild(closeIcon);
    header.appendChild(headerTitle);
    header.appendChild(closeBtn);
    dialog.appendChild(header);

    const body = document.createElement('div');
    body.className = 'image-dialog-body';

    const uid = Math.random().toString(36).slice(2, 9);

    const urlGroup = document.createElement('div');
    urlGroup.className = 'image-dialog-field';
    const urlLabel = document.createElement('label');
    urlLabel.htmlFor = `video-url-${uid}`;
    urlLabel.textContent = __('Video URL');
    const urlInput = document.createElement('input');
    urlInput.type = 'url';
    urlInput.id = `video-url-${uid}`;
    urlInput.className = 'form-control';
    urlInput.placeholder = 'https://www.youtube.com/watch?v=...';
    urlInput.setAttribute('autocomplete', 'off');
    urlGroup.appendChild(urlLabel);
    urlGroup.appendChild(urlInput);
    body.appendChild(urlGroup);

    const help = document.createElement('p');
    help.className = 'text-muted small mt-1 mb-0';
    help.textContent = __('Supported providers: YouTube, Dailymotion, Vimeo.');
    body.appendChild(help);

    const errorMsg = document.createElement('p');
    errorMsg.className = 'text-danger small mt-1 mb-0';
    errorMsg.style.display = 'none';
    errorMsg.setAttribute('role', 'alert');
    body.appendChild(errorMsg);

    dialog.appendChild(body);

    const footer = document.createElement('div');
    footer.className = 'image-dialog-footer';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-outline-secondary';
    cancelBtn.textContent = __('Cancel');

    const insertBtn = document.createElement('button');
    insertBtn.type = 'button';
    insertBtn.className = 'btn btn-primary';
    insertBtn.textContent = __('Insert');

    footer.appendChild(cancelBtn);
    footer.appendChild(insertBtn);
    dialog.appendChild(footer);

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    urlInput.focus();

    const close = () => {
        document.removeEventListener('keydown', handleKeydown);
        overlay.remove();
        editor.commands.focus();
    };

    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'Enter' && document.activeElement === urlInput) {
            e.preventDefault();
            insert();
        }
    };
    document.addEventListener('keydown', handleKeydown);

    cancelBtn.addEventListener('click', close);
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            close();
        }
    });

    const insert = () => {
        const attrs = parseVideoUrl(urlInput.value);
        if (!attrs) {
            errorMsg.textContent = __('This video URL is not recognized. Use a YouTube, Dailymotion or Vimeo URL.');
            errorMsg.style.display = '';
            urlInput.focus();
            return;
        }
        editor.chain().focus().insertContent({
            type: 'videoEmbed',
            attrs,
        }).run();
        close();
    };
    insertBtn.addEventListener('click', insert);
}

/**
 * Mirrors VideoEmbedRenderer::PROVIDER_URL_TEMPLATES. Privacy-friendly defaults
 * (youtube-nocookie, vimeo dnt=1).
 */
const EMBED_URL_TEMPLATES = {
    youtube:     'https://www.youtube-nocookie.com/embed/{id}',
    dailymotion: 'https://www.dailymotion.com/embed/video/{id}',
    vimeo:       'https://player.vimeo.com/video/{id}?dnt=1',
};

/**
 * @param {string} provider
 * @param {string} videoId
 * @param {number|null} start
 * @returns {string|null}
 */
function buildEmbedSrc(provider, videoId, start) {
    const template = EMBED_URL_TEMPLATES[provider];
    if (!template || !VALID_ID_PATTERN.test(videoId || '')) {
        return null;
    }
    let src = template.replace('{id}', encodeURIComponent(videoId));
    if (start && start > 0) {
        src += `${src.includes('?') ? '&' : '?'}start=${Math.floor(start)}`;
    }
    return src;
}

/**
 * Reverse of buildEmbedSrc — used when re-entering the editor over already
 * rendered HTML from the server's `|safe_html` filter.
 *
 * @param {string} src
 * @returns {{provider: string, videoId: string, start: number|null}|null}
 */
function parseEmbedSrc(src) {
    if (typeof src !== 'string') {
        return null;
    }
    const patterns = [
        { provider: 'youtube',     re: /^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([^/?#&]+)/ },
        { provider: 'dailymotion', re: /^https?:\/\/(?:www\.)?dailymotion\.com\/embed\/video\/([^/?#&]+)/ },
        { provider: 'vimeo',       re: /^https?:\/\/player\.vimeo\.com\/video\/(\d+)/ },
    ];
    for (const { provider, re } of patterns) {
        const m = src.match(re);
        if (m && VALID_ID_PATTERN.test(m[1])) {
            const startMatch = src.match(/[?&]start=(\d+)/);
            const start = startMatch ? parseInt(startMatch[1], 10) : null;
            return { provider, videoId: m[1], start: start && start > 0 ? start : null };
        }
    }
    return null;
}

/**
 * Render the videoEmbed node inside the editor as the live iframe — used both
 * in edit mode and when Tiptap re-hydrates over the readonly view.
 *
 * @param {object} node - ProseMirror node
 * @returns {HTMLElement}
 */
function buildEditorPreview(node) {
    const src = buildEmbedSrc(node.attrs.provider, node.attrs.videoId, node.attrs.start);

    const wrapper = document.createElement('div');
    wrapper.className = 'video-embed-wrapper';
    wrapper.contentEditable = 'false';
    wrapper.setAttribute('data-video-provider', node.attrs.provider);
    wrapper.setAttribute('data-video-id', node.attrs.videoId);
    if (node.attrs.start) {
        wrapper.setAttribute('data-video-start', String(node.attrs.start));
    }

    if (!src) {
        // Fallback: provider/id invalid → show the dashed placeholder so the
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

    const iframe = document.createElement('iframe');
    iframe.src = src;
    iframe.loading = 'lazy';
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation allow-popups');
    wrapper.appendChild(iframe);
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
            start: {
                default: null,
                parseHTML: (element) => {
                    const raw = element.getAttribute('data-video-start');
                    const n = raw ? parseInt(raw, 10) : NaN;
                    return Number.isFinite(n) && n > 0 ? n : null;
                },
                renderHTML: (attrs) => attrs.start
                    ? { 'data-video-start': String(attrs.start) }
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
                    const videoId = dom.getAttribute('data-video-id');
                    if (!ALLOWED_PROVIDERS.has(provider) || !videoId || !VALID_ID_PATTERN.test(videoId)) {
                        return false;
                    }
                    return null;
                },
            },
            {
                // Rendered form coming from the server's `|safe_html` filter
                // (re-entering edit mode over the already-materialized iframe).
                tag: 'div.video-embed-wrapper',
                getAttrs: (dom) => {
                    const iframe = dom.querySelector('iframe');
                    if (!iframe) {
                        return false;
                    }
                    return parseEmbedSrc(iframe.getAttribute('src')) || false;
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
                if (!attrs || !ALLOWED_PROVIDERS.has(attrs.provider) || !VALID_ID_PATTERN.test(attrs.videoId)) {
                    return false;
                }
                return chain().insertContent({ type: this.name, attrs }).run();
            },
        };
    },
});
