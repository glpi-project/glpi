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

import { Locator } from "@playwright/test";
import { randomUUID } from "crypto";
import { expect, test } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { Api } from "../../../utils/Api";
import {
    getWorkerEntityId,
    getWorkerIndex,
    getWorkerLogin,
} from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

test('Can clear the default value of an actor question in the form editor', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const question = await form.addQuestion('Actor question');
    await form.setQuestionType(question, 'Actors');

    const default_value_dropdown = form
        .getDropdownByLabel('Select an actor...', question)
        .filter({ visible: true });
    await form.doSetDropdownValue(default_value_dropdown, 'glpi', false);

    await form.assertDropdownIsClearable(default_value_dropdown);
});

test('Can clear a selected actor when answering a form', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const question = await form.addQuestion('Actor question');
    await form.setQuestionType(question, 'Actors');
    await form.doSetActive();
    await form.doSaveFormEditor();

    await form.doPreviewForm();

    const answer_dropdown = form.getDropdownByLabel('Actor question');
    await form.doSetDropdownValue(answer_dropdown, 'glpi', false);

    await form.assertDropdownIsClearable(answer_dropdown);
});

test.describe('Actor form question type', () => {
    // The worker account replaces the "E2E Tests" user of the cypress dataset.
    // Its friendly name is displayed when the actor is picked in the dropdown,
    // but the login is displayed once the question has been saved and reloaded.
    const getWorkerFriendlyName = (): string => {
        const index = String(getWorkerIndex()).padStart(2, '0');
        return `E2E worker account ${index}`;
    };

    const setupForm = async (form: FormPage, api: Api): Promise<{
        form_id: number,
        question: Locator,
    }> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': `Tests form for the actor form question type suite - ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        });
        await form.goto(form_id);

        // Add a question
        const question = await form.addQuestion('Test actor question');

        // Change question type
        await form.setQuestionType(question, 'Actors');

        // Define question sub type
        const sub_type = form
            .getDropdownByLabel('Question sub type', question)
            .filter({ visible: true })
        ;
        await expect(sub_type).toContainText('Requesters');
        await form.setSubQuestionType(question, 'Assignees');
        await expect(sub_type).toContainText('Assignees');

        return { form_id, question };
    };

    const getActorsDropdown = (form: FormPage, question: Locator): Locator => {
        return form.getDropdownByLabel('Select an actor...', question)
            .filter({ visible: true })
        ;
    };

    const getShowMoreSettingsButton = (question: Locator): Locator => {
        return question.getByRole('button', {
            name: 'Show more settings',
            exact: true,
        }).filter({ visible: true });
    };

    const getAllowMultipleActorsCheckbox = (question: Locator): Locator => {
        return question.getByRole('checkbox', {
            name: 'Allow multiple actors',
            exact: true,
        }).filter({ visible: true });
    };

    test('should be able to define an actor as default value', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        let { question } = await setupForm(form, api);

        // Ensure we don't allow multiple actors
        await getShowMoreSettingsButton(question).click();
        await expect(getAllowMultipleActorsCheckbox(question))
            .not.toBeChecked()
        ;

        // Define default value
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            getWorkerFriendlyName(),
            false
        );

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Check the default value
        question = form.getNthQuestion(0);
        await expect(getActorsDropdown(form, question))
            .toHaveText(`×${getWorkerLogin()}`)
        ;
    });

    test('should be able to define multiple actors as default value', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        let { question } = await setupForm(form, api);

        // Allow multiple actors
        await getShowMoreSettingsButton(question).click();
        await getAllowMultipleActorsCheckbox(question).check();

        // Define default values
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            getWorkerFriendlyName(),
            false
        );
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            'glpi',
            false
        );

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Check the default values
        question = form.getNthQuestion(0);
        await expect(getActorsDropdown(form, question))
            .toContainText(getWorkerLogin())
        ;
        await expect(getActorsDropdown(form, question)).toContainText('glpi');
    });

    test('should be able to switch between multiple actors and single actor', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        let { question } = await setupForm(form, api);

        // Double switch
        await getShowMoreSettingsButton(question).click();
        await getAllowMultipleActorsCheckbox(question).check();
        await getAllowMultipleActorsCheckbox(question).uncheck();

        // Define default value
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            getWorkerFriendlyName(),
            false
        );

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Check the default value
        question = form.getNthQuestion(0);
        await expect(getActorsDropdown(form, question))
            .toHaveText(`×${getWorkerLogin()}`)
        ;

        // Focus on the question
        await question.getByRole('textbox', { name: 'Question name' }).click();

        // Switch to multiple actors
        await getShowMoreSettingsButton(question).click();
        await getAllowMultipleActorsCheckbox(question).check();

        // Check the default value
        await expect(getActorsDropdown(form, question))
            .toHaveText(`×${getWorkerLogin()}`)
        ;

        // Add another actor
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            'glpi',
            false
        );

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Check the default values
        question = form.getNthQuestion(0);
        await expect(getActorsDropdown(form, question))
            .toContainText(getWorkerLogin())
        ;
        await expect(getActorsDropdown(form, question)).toContainText('glpi');
    });

    test('can duplicate a single actor question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { question } = await setupForm(form, api);

        // Define default value
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            getWorkerFriendlyName(),
            false
        );

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // The original question and its copy. Asserting the count first
        // prevents the loop below from silently doing nothing.
        const questions = form.getRegion('Question details');
        await expect(questions).toHaveCount(2);

        for (const duplicated_question of await questions.all()) {
            await expect(getActorsDropdown(form, duplicated_question))
                .toContainText(getWorkerFriendlyName())
            ;
        }
    });

    test('can duplicate a multiple actors question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { question } = await setupForm(form, api);

        // Allow multiple actors
        await getShowMoreSettingsButton(question).click();
        await getAllowMultipleActorsCheckbox(question).check();

        // Define default values
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            getWorkerFriendlyName(),
            false
        );
        await form.doSetDropdownValue(
            getActorsDropdown(form, question),
            'glpi',
            false
        );

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // The original question and its copy. Asserting the count first
        // prevents the loop below from silently doing nothing.
        const questions = form.getRegion('Question details');
        await expect(questions).toHaveCount(2);

        for (const duplicated_question of await questions.all()) {
            await expect(getActorsDropdown(form, duplicated_question))
                .toContainText(getWorkerFriendlyName())
            ;
            await expect(getActorsDropdown(form, duplicated_question))
                .toContainText('glpi')
            ;
        }
    });

    test('check available actors according to the question sub type', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { question } = await setupForm(form, api);

        const uuid = randomUUID();
        const first_group_name = `Test Group that can be assigned, added as observer and requester - ${uuid}`;
        const second_group_name = `Test Group that can't be assigned, added as observer and requester - ${uuid}`;

        const createGroup = async (
            name: string,
            is_assign: boolean,
            is_watcher: boolean,
            is_requester: boolean,
        ): Promise<void> => {
            await api.createItem('Group', {
                'name': name,
                'is_assign': is_assign ? 1 : 0,
                'is_watcher': is_watcher ? 1 : 0,
                'is_requester': is_requester ? 1 : 0,
                'entities_id': getWorkerEntityId(),
            });
        };

        const assertActorOptions = async (
            target_question: Locator,
            user_options_exist: { glpi: boolean, postOnly: boolean },
            group_options_exist: { firstGroup: boolean, secondGroup: boolean },
        ): Promise<void> => {
            const dropdown = getActorsDropdown(form, target_question);
            await dropdown.click();

            const user_group = page.getByRole('group', { name: 'User' });
            await expect(
                user_group.getByRole('option', { name: 'glpi' })
            ).toHaveCount(user_options_exist.glpi ? 1 : 0);
            await expect(
                user_group.getByRole('option', { name: 'post-only' })
            ).toHaveCount(user_options_exist.postOnly ? 1 : 0);

            const group_group = page.getByRole('group', { name: 'Group' });
            await expect(
                group_group.getByRole('option', { name: first_group_name })
            ).toHaveCount(group_options_exist.firstGroup ? 1 : 0);
            await expect(
                group_group.getByRole('option', { name: second_group_name })
            ).toHaveCount(group_options_exist.secondGroup ? 1 : 0);

            await dropdown.click(); // Close the dropdown
        };

        const addNewQuestion = async (
            question_name: string,
            sub_type: string,
        ): Promise<Locator> => {
            const new_question = await form.addQuestion(question_name);
            await form.setQuestionType(new_question, 'Actors');
            await form.setSubQuestionType(new_question, sub_type);
            return new_question;
        };

        await createGroup(first_group_name, true, true, true);
        await createGroup(second_group_name, false, false, false);

        // Verify the assignee question
        await assertActorOptions(
            question,
            { glpi: true, postOnly: false },
            { firstGroup: true, secondGroup: false }
        );

        // Add and verify the observer question
        const observer_question = await addNewQuestion(
            "Test observer question",
            'Observers'
        );
        await assertActorOptions(
            observer_question,
            { glpi: true, postOnly: true },
            { firstGroup: true, secondGroup: false }
        );

        // Add and verify the requester question
        const requester_question = await addNewQuestion(
            "Test requestor question",
            'Requesters'
        );
        await assertActorOptions(
            requester_question,
            { glpi: true, postOnly: true },
            { firstGroup: true, secondGroup: false }
        );
    });

    test('can submit a form with an empty actor question with multiple actors allowed', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { question } = await setupForm(form, api);

        // Allow multiple actors
        await getShowMoreSettingsButton(question).click();
        await getAllowMultipleActorsCheckbox(question).check();

        // Save
        await form.doSaveFormEditor();

        // Go to preview
        await form.doPreviewForm();

        // Submit the form
        await form.getButton('Submit').click();

        // Check the form was submitted
        await expect(form.getAlert('Item successfully created')).toBeVisible();
    });

    test('can submit a form with an empty actor question with simple actor', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Save
        await form.doSaveFormEditor();

        // Go to preview
        await form.doPreviewForm();

        // Submit the form
        await form.getButton('Submit').click();

        // Check the form was submitted
        await expect(form.getAlert('Item successfully created')).toBeVisible();
    });

    test('can disable itemtypes', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { question } = await setupForm(form, api);

        // The cypress test entity contained groups, the worker entities are
        // empty: a group must be created or the dropdown would have no group
        // to display at all.
        await api.createItem('Group', {
            'name': `Test group for the actor form question type suite - ${randomUUID()}`,
            'is_assign': 1,
            'entities_id': getWorkerEntityId(),
        });

        // Disable users
        await getShowMoreSettingsButton(question).click();
        await question.getByRole('checkbox', { name: 'Users' })
            .filter({ visible: true })
            .uncheck()
        ;

        // Save
        await form.doSaveFormEditor();

        // Go to preview
        await form.doPreviewForm();

        // Display the dropdown, only groups should be found
        await form.getDropdownByLabel('Test actor question').click();
        await expect(page.getByRole('group', { name: 'Group' })).toBeAttached();
        await expect(page.getByRole('group', { name: 'User' }))
            .not.toBeAttached()
        ;
    });
});
