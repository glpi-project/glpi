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

import ItemtypeCommentPopover from '/js/modules/Form/ItemtypeCommentPopover.js';

describe('Itemtype Comment Popover', () => {
    let comment_request;

    const hover = (selector) => $(selector).trigger('mouseenter');

    beforeEach(() => {
        $('body').html(`
            <div id="container">
                <span id="first-entry"></span>
                <span id="second-entry"></span>
            </div>
        `);
        $.fn.popover = vi.fn().mockReturnThis();

        comment_request = vi.fn(() => '<div class="user-info-card">John Doe</div>');
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse(
            '/ajax/comments.php',
            'POST',
            {itemtype: 'User', value: 2},
            comment_request,
            true
        ));
    });

    afterEach(() => {
        window.AjaxMock.end();
    });

    test('Comment is not requested when it is attached to an element', () => {
        const popover = new ItemtypeCommentPopover($('#container'));
        popover.attachTo($('#first-entry'), 'User', 2);

        expect(comment_request).not.toHaveBeenCalled();
        expect($('#first-entry').attr('data-glpi-popover-source')).toBeUndefined();
    });

    test('Comment is requested on the first hover', async () => {
        const popover = new ItemtypeCommentPopover($('#container'));
        popover.attachTo($('#first-entry'), 'User', 2);

        hover('#first-entry');
        await vi.waitFor(() => expect($('#first-entry').attr('data-glpi-popover-source')).toBeDefined());

        expect(comment_request).toHaveBeenCalledTimes(1);
        const source = document.getElementById($('#first-entry').attr('data-glpi-popover-source'));
        expect(source).not.toBeVisible();
        expect(source.querySelector('.user-info-card').textContent).toBe('John Doe');

        hover('#first-entry');
        expect(comment_request).toHaveBeenCalledTimes(1);
    });

    test('Comment is requested once for the elements of a same item', async () => {
        const popover = new ItemtypeCommentPopover($('#container'));
        popover.attachTo($('#first-entry'), 'User', 2);
        popover.attachTo($('#second-entry'), 'User', 2);

        hover('#first-entry');
        hover('#second-entry');
        await vi.waitFor(() => expect($('#second-entry').attr('data-glpi-popover-source')).toBeDefined());

        expect(comment_request).toHaveBeenCalledTimes(1);
        expect($('#second-entry').attr('data-glpi-popover-source')).toBe($('#first-entry').attr('data-glpi-popover-source'));
    });

    test('Element of an item without comment has no popover', async () => {
        comment_request.mockReturnValue('');

        const popover = new ItemtypeCommentPopover($('#container'));
        popover.attachTo($('#first-entry'), 'User', 2);

        hover('#first-entry');
        await vi.waitFor(() => expect(comment_request).toHaveBeenCalledTimes(1));
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect($('#first-entry').attr('data-glpi-popover-source')).toBeUndefined();
    });
});
