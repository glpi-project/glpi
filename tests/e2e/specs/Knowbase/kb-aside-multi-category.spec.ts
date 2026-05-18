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

import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { KnowbaseItemPage } from '../../pages/KnowbaseItemPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Article linked to several categories appears under each one', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Multi Cat A ${token}`;
    const category_b   = `E2E Multi Cat B ${token}`;
    const article_name = `E2E Multi Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id],
    });

    await kb.goto(article_id);

    await expect(kb.getAsideCategoryArticle(category_a, article_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(category_b, article_name)).toBeVisible();
});

test('Hovering a multi-category article reveals its other categories in a tooltip', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Hover Cat A ${token}`;
    const category_b   = `E2E Hover Cat B ${token}`;
    const category_c   = `E2E Hover Cat C ${token}`;
    const article_name = `E2E Hover Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const category_c_id = await api.createItem('KnowbaseItemCategory', {
        name: category_c,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id, category_c_id],
    });

    await kb.goto(article_id);

    const tooltip = page.getByRole('tooltip');

    // Hover the instance under category A → tooltip mentions B and C, not A
    await kb.getAsideCategoryArticle(category_a, article_name).hover();
    await expect(tooltip).toBeVisible();
    // Guard against missing styles: tooltip CSS must apply (position is the load-bearing
    // property — without it the tooltip falls into the document flow, breaking the UX
    // even though Playwright considers the element "visible" in the DOM sense).
    await expect(tooltip).toHaveCSS('position', 'fixed');
    await expect(tooltip).toContainText(category_b);
    await expect(tooltip).toContainText(category_c);
    await expect(tooltip).not.toContainText(category_a);

    // Move away — tooltip disappears
    await page.mouse.move(0, 0);
    await expect(tooltip).toBeHidden();

    // Hover the instance under category B → tooltip now mentions A and C, not B
    await kb.getAsideCategoryArticle(category_b, article_name).hover();
    await expect(tooltip).toBeVisible();
    await expect(tooltip).toContainText(category_a);
    await expect(tooltip).toContainText(category_c);
    await expect(tooltip).not.toContainText(category_b);
});

test('Focusing a multi-category article via keyboard surfaces the tooltip and Escape dismisses it', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Kbd Cat A ${token}`;
    const category_b   = `E2E Kbd Cat B ${token}`;
    const article_name = `E2E Kbd Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id],
    });

    await kb.goto(article_id);

    const tooltip = page.getByRole('tooltip');
    const article_link = kb.getAsideCategoryArticle(category_a, article_name);

    // Tab focus is the realistic keyboard path; .focus() also works for the assertion.
    await article_link.focus();
    await expect(tooltip).toBeVisible();
    await expect(tooltip).toContainText(category_b);

    // The focused link must advertise the tooltip to assistive tech.
    await expect(article_link).toHaveAttribute('aria-describedby', /kb-aside-multi-tooltip-/);

    // Escape dismisses the tooltip without moving focus (ARIA Tooltip pattern).
    await page.keyboard.press('Escape');
    await expect(tooltip).toBeHidden();
    await expect(article_link).not.toHaveAttribute('aria-describedby', /.+/);
    await expect(article_link).toBeFocused();
});

test('Single-category article shows no tooltip on hover', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Single Cat ${token}`;
    const article_name = `E2E Single Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id],
    });

    await kb.goto(article_id);

    await kb.getAsideCategoryArticle(category_a, article_name).hover();
    await expect(page.getByRole('tooltip')).toBeHidden();
});
