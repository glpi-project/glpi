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
import { test, expect } from '../../fixtures/glpi_fixture';
import { FormPage } from '../../pages/FormPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

interface NoValueCondition {
    operator: string;
    valueType: null;
    value: null;
}

interface StringCondition {
    operator: string;
    valueType: 'string';
    value: string;
}

interface NumberCondition {
    operator: string;
    valueType: 'number';
    value: number;
}

interface DateCondition {
    operator: string;
    valueType: 'date';
    value: string;
}

interface DropdownCondition {
    operator: string;
    valueType: 'dropdown';
    value: string;
}

interface DropdownMultipleCondition {
    operator: string;
    valueType: 'dropdown_multiple';
    value: string[];
}

type Condition =
    | NoValueCondition
    | StringCondition
    | NumberCondition
    | DateCondition
    | DropdownCondition
    | DropdownMultipleCondition;

interface QuestionType {
    file: string;
    conditions: Condition[];
}

const types: Map<string, QuestionType> = new Map();
types.set('QuestionTypeShortText', {
    file: 'question_types/short-text-question.json',
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Is not equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Length is greater than',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Length is greater than or equals to',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than or equals to',
            value: 10,
            valueType: 'number'
        },
    ],
});
types.set('QuestionTypeNumber', {
    file: "question_types/number-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Is not equal to',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Is greater than',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Is less than',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Is greater than or equals to',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Is less than or equals to',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Match regular expression',
            value: '/^[0-9]$/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^[0-9]$/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeEmail', {
    file: "question_types/email-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Is not equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Length is greater than',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Length is greater than or equals to',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than or equals to',
            value: 10,
            valueType: 'number'
        },
    ]
});
types.set('QuestionTypeLongText', {
    file: "question_types/long-text-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Is not equal to',
            value: 'Exact match',
            valueType: 'string'
        },
        {
            operator: 'Contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Expected answer',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Expected answer/',
            valueType: 'string'
        },
        {
            operator: 'Length is greater than',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than',
            value: 10,
            valueType: 'number'
        },
        {
            operator: 'Length is greater than or equals to',
            value: 5,
            valueType: 'number'
        },
        {
            operator: 'Length is less than or equals to',
            value: 10,
            valueType: 'number'
        },
    ]
});
types.set('QuestionTypeDate', {
    file: "question_types/date-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Is not equal to',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Is greater than',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Is less than',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Is greater than or equals to',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Is less than or equals to',
            value: '2021-01-01',
            valueType: 'date'
        },
        {
            operator: 'Match regular expression',
            value: '/^2021-01-01$/',
            valueType: 'date'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^2021-01-01$/',
            valueType: 'date'
        },
    ]
});
types.set('QuestionTypeTime', {
    file: "question_types/time-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Is not equal to',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Is greater than',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Is less than',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Is greater than or equals to',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Is less than or equals to',
            value: '12:00',
            valueType: 'date'
        },
        {
            operator: 'Match regular expression',
            value: '/^12:00$/',
            valueType: 'date'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^12:00$/',
            valueType: 'date'
        },
    ]
});
types.set('QuestionTypeDateTime', {
    file: "question_types/datetime-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Is not equal to',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Is greater than',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Is less than',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Is greater than or equals to',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Is less than or equals to',
            value: '2021-01-01T12:00',
            valueType: 'date'
        },
        {
            operator: 'Match regular expression',
            value: '/^2021-01-01T12:00$/',
            valueType: 'date'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^2021-01-01T12:00$/',
            valueType: 'date'
        },
    ]
});
types.set('QuestionTypeRequester', {
    file: "question_types/requester-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Do not contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeObserver', {
    file: "question_types/observer-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Do not contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeAssignee', {
    file: "question_types/assignee-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Do not contains',
            value: 'glpi',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/glpi/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeUrgency', {
    file: "question_types/urgency-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Is greater than',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Is greater than or equals to',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than or equals to',
            value: 'Very high',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '/^1$/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^1$/',
            valueType: 'string'
        }
    ]
});
types.set('QuestionTypeRequestType', {
    file: "question_types/request-type-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Request',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Request',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '/^1$/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^1$/',
            valueType: 'string'
        }
    ]
});
types.set('QuestionTypeFile', {
    file: "question_types/file-question.json",
    conditions: [
        {
            operator: 'Match regular expression',
            value: '/^file_[0-9]+\\.txt$/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/^file_[0-9]+\\.txt$/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeRadio', {
    file: "question_types/radio-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Option 3',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Option 2',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Is greater than',
            value: 'Option 1',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than',
            value: 'Option 2',
            valueType: 'dropdown'
        },
        {
            operator: 'Is greater than or equals to',
            value: 'Option 3',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than or equals to',
            value: 'Option 4',
            valueType: 'dropdown'
        },
    ]
});
types.set('QuestionTypeCheckbox', {
    file: "question_types/checkbox-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Is not equal to',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Contains',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Do not contains',
            value: ['Option 1', 'Option 3'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        }
    ]
});
types.set('QuestionTypeDropdownSingle', {
    file: "question_types/dropdown-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Option 3',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Option 2',
            valueType: 'dropdown'
        },
        {
            operator: 'Match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Is greater than',
            value: 'Option 1',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than',
            value: 'Option 2',
            valueType: 'dropdown'
        },
        {
            operator: 'Is greater than or equals to',
            value: 'Option 3',
            valueType: 'dropdown'
        },
        {
            operator: 'Is less than or equals to',
            value: 'Option 4',
            valueType: 'dropdown'
        },
    ]
});
types.set('QuestionTypeDropdownMultiple', {
    file: "question_types/dropdown-multiple-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Is not equal to',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Contains',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Do not contains',
            value: ['Option 2', 'Option 4'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '^Option [1-4]$',
            valueType: 'string'
        }
    ]
});
types.set('QuestionTypeItem', {
    file: "question_types/item-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Computer - {uuid}',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Computer - {uuid}',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'Computer - {uuid}',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Computer - {uuid}',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Computer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Computer/',
            valueType: 'string'
        }
    ]
});
types.set('QuestionTypeItemDropdown', {
    file: "question_types/item-dropdown-question.json",
    conditions: [
        {
            operator: 'Is equal to',
            value: 'Location - {uuid}',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not equal to',
            value: 'Location - {uuid}',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'Location - {uuid}',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Location - {uuid}',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Location/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Location/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeUserDeviceSingle', {
    file: "question_types/user-device-question.json",
    conditions: [
        {
            operator: 'Is of itemtype',
            value: 'Computer',
            valueType: 'dropdown'
        },
        {
            operator: 'Is not of itemtype',
            value: 'Computer',
            valueType: 'dropdown'
        },
        {
            operator: 'Contains',
            value: 'Computer',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Computer',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Computer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Computer/',
            valueType: 'string'
        },
    ]
});
types.set('QuestionTypeUserDeviceMultiple', {
    file: "question_types/user-device-multiple-question.json",
    conditions: [
        {
            operator: 'At least one item of itemtype',
            value: ['Computer'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'All items of itemtype',
            value: ['Computer'],
            valueType: 'dropdown_multiple'
        },
        {
            operator: 'Contains',
            value: 'Computer',
            valueType: 'string'
        },
        {
            operator: 'Do not contains',
            value: 'Computer',
            valueType: 'string'
        },
        {
            operator: 'Match regular expression',
            value: '/Computer/',
            valueType: 'string'
        },
        {
            operator: 'Do not match regular expression',
            value: '/Computer/',
            valueType: 'string'
        },
    ]
});

// All questions implement "Is visible", "Is not visible", "Is empty" and "Is not empty" conditions
for (const [label] of types) {
    types.get(label)?.conditions.push(...[
        {
            operator: 'Is visible',
            value: null,
            valueType: null
        },
        {
            operator: 'Is not visible',
            value: null,
            valueType: null
        },
        {
            operator: 'Is empty',
            value: null,
            valueType: null
        },
        {
            operator: 'Is not empty',
            value: null,
            valueType: null
        }
    ]);
}

// WIP: will refactor to clean the code before the PR will be reviewed

// Note: we have a few eslint rules disabled related to conditions.
// The conditions are based on static test data, not runtime state so this is a
// valid case to disable these rules.
/* eslint-disable playwright/no-conditional-in-test */
/* eslint-disable playwright/no-conditional-expect */
for (const [label, params] of types) {
    test(`Try to configure conditions on ${label}`, async ({
        page,
        profile,
        formImporter,
        api
    }) => {
        // This test is quite slow because we interact with many select2
        // instances.
        test.slow();

        // Import and go to test form
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);
        const info = await formImporter.importForm(params.file);
        await form.goto(info.getId());

        // Specific code for a few cases, could be removed if we setup this
        // data in the install process instead.
        if (label == 'QuestionTypeItem') {
            const uuid = randomUUID();
            await api.createItem('Computer', {
                name: `Computer - ${uuid}`,
                entities_id: getWorkerEntityId(),
            });
            for (const condition of params.conditions) {
                if (condition.valueType == 'dropdown' || condition.valueType == 'string') {
                    condition.value = condition.value.replace('{uuid}', uuid);
                }
            }
        } else if (label == 'QuestionTypeItemDropdown') {
            const uuid = randomUUID();
            await api.createItem('Location', {
                name: `Location - ${uuid}`,
                entities_id: getWorkerEntityId(),
            });
            for (const condition of params.conditions) {
                if (condition.valueType == 'dropdown'  || condition.valueType == 'string') {
                    condition.value = condition.value.replace('{uuid}', uuid);
                }
            }
        }

        // Enable conditional visibility for our test question
        await form.doInitVisibilityConditionsDropdown(1);
        await form.doSetVisibilityStrategy('Visible if...');

        let index = 0;
        for (const condition of params.conditions) {
            if (index !== 0) {
                await form.doAddNewCondition();
            }

            const params = [index, "Or", "My question", condition.operator] as const;
            switch (condition.valueType) {
                case null:
                    await form.doFillConditionWithoutValue(...params);
                    break;
                case 'number':
                    await form.doFillNumberCondition(...params, condition.value);
                    break;
                case 'string':
                    await form.doFillStringCondition(...params, condition.value);
                    break;
                case 'date':
                    await form.doFillDateCondition(...params, condition.value);
                    break;
                case 'dropdown':
                    await form.doFillDropdownCondition(...params, condition.value);
                    break;
                case 'dropdown_multiple':
                    await form.doFillMultipleDropdownCondition(...params, condition.value);
                    break;
            }

            index++;
        }

        // Make sure we tested all possible operators
        const existing_options = await form.getDropdownOptions(
            form.getDropdownByLabel('Value operator').last()
        );
        const tested_options = params.conditions.map(
            (condition) => condition.operator
        );
        expect(tested_options.sort()).toEqual(existing_options.sort());

        // Save and reload
        await form.doSaveFormEditor();
        await page.reload();

        // Focus last question of the form
        const question = form.getLastQuestion();
        await question.click();

        // Validate each conditions again
        await form.doOpenVisibilityConditionsConfiguration();

        index = 0;
        const saved_conditions = form.getConditions();
        for (const condition of params.conditions) {
            const saved_condition = saved_conditions.nth(index);

            // Validate logic operator
            if (index !== 0) {
                const logic_dropdown = form.getDropdownByLabel(
                    'Logic operator',
                    saved_condition,
                );
                await expect(logic_dropdown).toHaveText("Or");
            }

            // Validate target question
            const question_dropdown = form.getDropdownByLabel(
                'Item',
                saved_condition,
            );
            await expect(question_dropdown).toHaveText("Questions - My question");

            // Validate value operator
            const value_operator_dropdown = form.getDropdownByLabel(
                'Value operator',
                saved_condition,
            );
            await expect(value_operator_dropdown).toHaveText(condition.operator);

            // Validate value
            switch (condition.valueType) {
                case 'number': {
                    const input = saved_condition.getByRole('spinbutton', {
                        name: "Value",
                        exact: true,
                    }).filter({visible: true});
                    await expect(input).toHaveValue(condition.value.toString());
                    break;
                }
                case 'string': {
                    const input = saved_condition.getByRole('textbox', {
                        name: "Value",
                        exact: true,
                    }).filter({visible: true});
                    await expect(input).toHaveValue(condition.value);
                    break;
                }
                case 'date': {
                    const input = saved_condition
                        .getByLabel('Value', {
                            exact: true,
                        })
                        .filter({visible: true})
                    ;
                    await expect(input).toHaveValue(condition.value);
                    break;
                }
                case 'dropdown': {
                    const dropdown = form.getDropdownByLabel('Value', saved_condition);
                    await expect(dropdown).toHaveText(condition.value);
                    break;
                }
                case 'dropdown_multiple': {
                    const dropdown = form.getDropdownByLabel('Value', saved_condition);
                    await expect(dropdown).toHaveText(`×${condition.value.join('×')}`);
                    break;
                }
            }

            index++;
        }
    });
}
