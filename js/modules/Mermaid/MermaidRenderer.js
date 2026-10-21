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

/**
 * Lazy Mermaid rendering for the `<pre><code class="language-mermaid">` blocks
 * of the KB articles. The source is the stored content; the SVG is never saved.
 */

let load_promise = null;
let render_count = 0;
let current_theme = null;

/** Code elements of the read view that already got a rendering attempt. */
const handled_blocks = new WeakSet();

/**
 * @returns {Promise<object>} The initialized mermaid API, set to the current GLPI theme
 */
async function loadMermaid() {
    load_promise ??= import('/lib/mermaid.js')
        .then(() => window.mermaid)
        .catch((error) => {
            load_promise = null;
            throw error;
        });
    const mermaid = await load_promise;

    // The theme can change without a page reload.
    const theme = document.documentElement.dataset.glpiThemeDark === '1' ? 'dark' : 'default';
    if (theme !== current_theme) {
        current_theme = theme;
        mermaid.initialize({
            startOnLoad: false,
            // Diagrams are user content: strict mode sanitizes the labels and disables click callbacks.
            securityLevel: 'strict',
            theme,
            // Mermaid defaults, plus the styles a diagram directive could use to inject CSS.
            secure: [
                'secure', 'securityLevel', 'startOnLoad', 'maxTextSize', 'suppressErrorRendering', 'maxEdges',
                'themeCSS', 'themeVariables', 'fontFamily', 'altFontFamily',
            ],
        });
    }
    return mermaid;
}

/**
 * @param {string} source - Mermaid diagram definition
 * @returns {Promise<string>} SVG markup
 * @throws {Error} When the definition is invalid
 */
export async function renderMermaidSvg(source) {
    const mermaid = await loadMermaid();
    // parse() gives a readable error, where render() would leave an error SVG in the page.
    await mermaid.parse(source);
    render_count++;
    const { svg } = await mermaid.render(`kb-mermaid-${render_count}`, source);
    return svg;
}

/**
 * Draw a diagram after each Mermaid code block of the given read-only content.
 * The source block is hidden but kept for the comment anchors, which use its text.
 * An invalid block stays visible as code.
 *
 * @param {HTMLElement} root
 */
export async function renderMermaidBlocks(root) {
    const blocks = [...root.querySelectorAll('pre > code.language-mermaid')].filter((code) => (
        !handled_blocks.has(code)
        && code.closest('.ProseMirror') === null
        && !code.parentElement.nextElementSibling?.classList.contains('kb-mermaid-diagram')
        // A revision diff marks its changes in the source, a drawing would hide them.
        && code.querySelector('ins, del') === null
    ));
    // Mark them all now: a call that starts while this one waits must skip them.
    blocks.forEach((code) => handled_blocks.add(code));

    for (const code of blocks) {
        try {
            const svg = await renderMermaidSvg(code.textContent);
            const figure = document.createElement('figure');
            figure.className = 'kb-mermaid-diagram';
            figure.setAttribute('aria-label', __('Diagram'));
            figure.innerHTML = svg;
            code.parentElement.after(figure);
            code.parentElement.hidden = true;
        } catch {
            // Keep the source visible.
        }
    }
}

/**
 * Render the Mermaid blocks of the given content now and each time it is replaced
 * (translation switch, revision diff, ...). Only its direct children are watched,
 * so typing in the editor does not start a scan.
 *
 * @param {HTMLElement} root
 */
export function watchMermaidBlocks(root) {
    renderMermaidBlocks(root);

    let scheduled = false;
    new MutationObserver(() => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        requestAnimationFrame(() => {
            scheduled = false;
            renderMermaidBlocks(root);
        });
    }).observe(root, { childList: true });
}
