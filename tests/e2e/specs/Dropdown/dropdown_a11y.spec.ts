import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { UserPage } from '../../pages/UserPage';

test('multi-select remove control is a labelled accessible button', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const substitute_name = `substitute_${crypto.randomUUID().slice(0, 8)}`;
    const substitute_id = await api.createItem('User', {
        'name': substitute_name,
        'password': 'testpassword',
        'password2': 'testpassword',
    });
    await api.createItem('Profile_User', {
        'users_id': substitute_id,
        'profiles_id': Profiles.SuperAdmin,
        'entities_id': getWorkerEntityId(),
        'is_recursive': 1,
    });

    const user_page = new UserPage(page);
    await user_page.gotoPreferences('ValidatorSubstitute$1');

    const substitutes_dropdown = user_page.getDropdownByLabel('Approval substitutes');
    await user_page.doSearchAndClickDropdownValue(substitutes_dropdown, substitute_name);

    const remove_button = substitutes_dropdown
        .getByTitle(substitute_name)
        .getByRole('button', {name: /remove item/i});
    await expect(remove_button).toBeVisible();

    await remove_button.click();
    await expect(substitutes_dropdown).not.toContainText(substitute_name);
});
