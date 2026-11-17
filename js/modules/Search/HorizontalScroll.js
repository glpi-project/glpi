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

const instances = new Set();
let frame = null;
let removal_observer = null;

// Share page listeners between embedded Search tables, and release them when
// results or tabs are removed from the document.
function scheduleUpdate() {
    if (frame !== null) {
        return;
    }
    frame = requestAnimationFrame(() => {
        frame = null;
        instances.forEach(instance => instance.update());
    });
}

export default class HorizontalScroll {
    constructor(table) {
        this.table = table;
        this.controls = table?.parentElement.nextElementSibling;
        if (!this.controls?.matches('.search-horizontal-scroll')) {
            this.controls = null;
            return;
        }
        this.buttons = [...this.controls.querySelectorAll('button')];
        this.onClick = event => {
            const button = event.target.closest('button');
            if (!button || button.disabled || !this.scroller) {
                return;
            }
            this.scroller.scrollBy({
                left: Number(button.dataset.scrollDirection) * this.visible_width / 2,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'
            });
        };
        this.controls.addEventListener('click', this.onClick);
        // Keep fixed controls outside ancestors that can establish a containing block.
        document.body.append(this.controls);
        this.resize_observer = new ResizeObserver(scheduleUpdate);
        for (let ancestor = table; ancestor; ancestor = ancestor.parentElement) {
            this.resize_observer.observe(ancestor);
        }
        if (instances.size === 0) {
            document.addEventListener('scroll', scheduleUpdate, true);
            window.addEventListener('resize', scheduleUpdate);
            removal_observer = new MutationObserver(scheduleUpdate);
            removal_observer.observe(document.body, {childList: true, subtree: true});
        }
        instances.add(this);
        scheduleUpdate();
    }

    update() {
        if (!this.table.isConnected) {
            this.destroy();
            return;
        }
        const body = this.table.tBodies[0];
        if (!body) {
            this.controls.hidden = true;
            return;
        }
        const bounds = body.getBoundingClientRect();
        let left = Math.max(0, bounds.left);
        let right = Math.min(document.documentElement.clientWidth, bounds.right);
        let top = Math.max(0, bounds.top);
        let bottom = Math.min(document.documentElement.clientHeight, bounds.bottom);
        this.scroller = null;
        for (let ancestor = this.table.parentElement; ancestor; ancestor = ancestor.parentElement) {
            const style = getComputedStyle(ancestor);
            const rect = ancestor.getBoundingClientRect();
            if (/auto|scroll|hidden|clip/.test(style.overflowX)) {
                left = Math.max(left, rect.left + ancestor.clientLeft);
                right = Math.min(right, rect.left + ancestor.clientLeft + ancestor.clientWidth);
            }
            if (/auto|scroll|hidden|clip/.test(style.overflowY)) {
                top = Math.max(top, rect.top + ancestor.clientTop);
                bottom = Math.min(bottom, rect.top + ancestor.clientTop + ancestor.clientHeight);
            }
            if (!this.scroller && /auto|scroll/.test(style.overflowX) && ancestor.scrollWidth > ancestor.clientWidth + 1) {
                this.scroller = ancestor;
            }
        }
        // Sticky headings and pagination occupy part of the visible table.
        const card = this.table.closest('.search-card');
        const overlays = [
            card?.querySelector('.search-header'),
            this.table.tHead?.querySelector('th'),
            card?.querySelector('.search-footer')
        ];
        for (const overlay of overlays.filter(Boolean)) {
            const rect = overlay.getBoundingClientRect();
            if (rect.top <= top && rect.bottom > top) {
                top = rect.bottom;
            } else if (rect.top < bottom && rect.bottom >= bottom) {
                bottom = rect.top;
            }
        }
        this.visible_width = right - left;
        this.controls.hidden = !this.scroller || this.visible_width < 64 || bottom - top < 48;
        if (this.controls.hidden) {
            return;
        }
        const max_scroll = this.scroller.scrollWidth - this.scroller.clientWidth;
        const rtl = getComputedStyle(this.scroller).direction === 'rtl';
        const position = this.scroller.scrollLeft;
        this.buttons[0].disabled = position <= (rtl ? -max_scroll : 0) + 1;
        this.buttons[1].disabled = position >= (rtl ? 0 : max_scroll) - 1;
        for (const button of this.buttons) {
            button.style.top = `${(top + bottom) / 2}px`;
        }
        // Use the space outside the table without changing its layout or column widths.
        this.buttons[0].style.left = `${Math.max(0, left - this.buttons[0].getBoundingClientRect().width)}px`;
        this.buttons[1].style.left = `${Math.min(document.documentElement.clientWidth - this.buttons[1].getBoundingClientRect().width, right)}px`;
    }

    destroy() {
        if (!this.controls) {
            return;
        }
        this.resize_observer.disconnect();
        this.controls.removeEventListener('click', this.onClick);
        this.controls.remove();
        this.controls = null;
        instances.delete(this);
        if (instances.size === 0) {
            document.removeEventListener('scroll', scheduleUpdate, true);
            window.removeEventListener('resize', scheduleUpdate);
            removal_observer.disconnect();
            cancelAnimationFrame(frame);
            frame = null;
        }
    }
}
