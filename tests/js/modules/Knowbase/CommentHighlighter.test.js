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

import { highlightComments } from '/js/modules/Knowbase/CommentHighlighter.js';

describe('highlightComments', () => {
    it('wraps the anchored quote in a highlight mark', () => {
        document.body.innerHTML = '<div id="root"><p>The quick brown fox jumps</p></div>';
        const root = document.getElementById('root');

        highlightComments(root, [
            { id: 1, prefix: 'quick ', exact: 'brown fox', suffix: ' jumps', occurrence: 0 },
        ]);

        const mark = root.querySelector('mark.kb-comment-highlight');
        expect(mark.textContent).toBe('brown fox');
        expect(mark.dataset.commentId).toBe('1');
    });

    it('skips anchors whose quote can no longer be found (orphaned)', () => {
        document.body.innerHTML = '<div id="root"><p>Completely different text</p></div>';
        const root = document.getElementById('root');

        highlightComments(root, [
            { id: 1, prefix: 'quick ', exact: 'brown fox', suffix: ' jumps', occurrence: 0 },
        ]);

        expect(root.querySelector('mark.kb-comment-highlight')).toBeNull();
    });

    it('is idempotent: re-running with the same anchors does not duplicate marks', () => {
        document.body.innerHTML = '<div id="root"><p>The quick brown fox jumps</p></div>';
        const root = document.getElementById('root');
        const anchors = [{ id: 1, prefix: 'quick ', exact: 'brown fox', suffix: ' jumps', occurrence: 0 }];

        highlightComments(root, anchors);
        highlightComments(root, anchors);

        expect(root.querySelectorAll('mark.kb-comment-highlight')).toHaveLength(1);
        expect(root.textContent).toBe('The quick brown fox jumps');
    });

    it('does nothing when there are no anchors', () => {
        document.body.innerHTML = '<div id="root"><p>Some text</p></div>';
        const root = document.getElementById('root');

        highlightComments(root, []);

        expect(root.querySelector('mark.kb-comment-highlight')).toBeNull();
    });
});
