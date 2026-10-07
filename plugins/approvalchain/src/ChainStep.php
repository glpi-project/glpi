<?php

namespace GlpiPlugin\Approvalchain;

use CommonDBTM;
use CommonGLPI;
use Group;
use Session;
use User;

/**
 * One step of a chain definition. Exactly one approver: a user OR a group
 * (any member of the group may decide).
 */
class ChainStep extends CommonDBTM
{
    use ConfigRights;

    public static $rightname = 'config';
    public $dohistory        = false;

    public static function getTypeName($nb = 0): string
    {
        return _n('Step', 'Steps', $nb, 'approvalchain');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item instanceof Chain) {
            $nb = countElementsInTable(self::getTable(), ['plugin_approvalchain_chains_id' => $item->getID()]);
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Chain) {
            self::showForChain($item);
        }
        return true;
    }

    private function validateInput(array $input, array $current = []): bool
    {
        $merged = $input + $current;
        if (trim((string) ($merged['name'] ?? '')) === '') {
            Session::addMessageAfterRedirect(__('A step name is required.', 'approvalchain'), false, ERROR);
            return false;
        }
        if (((int) ($merged['users_id'] ?? 0) > 0) === ((int) ($merged['groups_id'] ?? 0) > 0)) {
            Session::addMessageAfterRedirect(
                __('Select exactly one approver: a user or a group.', 'approvalchain'),
                false,
                ERROR
            );
            return false;
        }
        return true;
    }

    public function prepareInputForAdd($input): array|false
    {
        global $DB;

        if ((int) ($input['plugin_approvalchain_chains_id'] ?? 0) <= 0 || !$this->validateInput($input)) {
            return false;
        }
        $max = 0;
        foreach ($DB->request([
            'SELECT' => ['position'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_approvalchain_chains_id' => (int) $input['plugin_approvalchain_chains_id']],
            'ORDER'  => 'position DESC',
            'LIMIT'  => 1,
        ]) as $row) {
            $max = (int) $row['position'];
        }
        $input['position'] = $max + 1;
        return $input;
    }

    public function prepareInputForUpdate($input): array|false
    {
        if (!$this->validateInput($input, $this->fields)) {
            return false;
        }
        unset($input['position'], $input['plugin_approvalchain_chains_id']);
        return $input;
    }

    /** Swap a step with its neighbour. $dir = 'up'|'down' */
    public static function move(int $id, string $dir): void
    {
        global $DB;

        $step = new self();
        if (!$step->getFromDB($id)) {
            return;
        }
        $up       = ($dir === 'up');
        $position = (int) $step->fields['position'];

        foreach ($DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'plugin_approvalchain_chains_id' => (int) $step->fields['plugin_approvalchain_chains_id'],
                'position' => [$up ? '<' : '>', $position],
            ],
            'ORDER' => 'position ' . ($up ? 'DESC' : 'ASC'),
            'LIMIT' => 1,
        ]) as $neighbour) {
            $DB->update(self::getTable(), ['position' => (int) $neighbour['position']], ['id' => $id]);
            $DB->update(self::getTable(), ['position' => $position], ['id' => (int) $neighbour['id']]);
            break;
        }
    }

    public static function showForChain(Chain $chain): void
    {
        global $DB;

        $cid     = (int) $chain->getID();
        $canedit = Chain::canUpdate();
        $url     = self::getFormURL();

        $steps = iterator_to_array($DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['plugin_approvalchain_chains_id' => $cid],
            'ORDER' => ['position ASC', 'id ASC'],
        ]), false);

        echo "<div class='p-3'>";
        echo "<p class='text-muted'>" . __('Steps run in order. A step becomes active only after the previous one is approved; a refusal stops the chain.', 'approvalchain') . "</p>";

        foreach ($steps as $i => $s) {
            $rand = mt_rand();
            if (!$canedit) {
                echo "<div class='card mb-2'><div class='card-body'><span class='badge bg-blue-lt me-2'>" . ($i + 1) . "</span>";
                echo "<strong>" . Util::e($s['name']) . "</strong> &mdash; " . Util::approver((int) $s['users_id'], (int) $s['groups_id']);
                echo "<div class='text-muted small'>" . Util::e($s['comment']) . "</div></div></div>";
                continue;
            }
            echo "<form method='post' action='" . $url . "' class='card mb-2'><div class='card-body'><div class='row g-2 align-items-end'>";
            echo "<input type='hidden' name='id' value='" . (int) $s['id'] . "'>";
            echo "<div class='col-auto'><span class='badge bg-blue-lt fs-3'>" . ($i + 1) . "</span></div>";
            echo "<div class='col-md-3'><label class='form-label'>" . __('Step name', 'approvalchain') . "</label>";
            echo "<input type='text' class='form-control' name='name' value='" . Util::e($s['name']) . "'></div>";
            echo "<div class='col-md-3'><label class='form-label'>" . __('Description', 'approvalchain') . "</label>";
            echo "<input type='text' class='form-control' name='comment' value='" . Util::e($s['comment']) . "'></div>";
            echo "<div class='col-md-2'><label class='form-label'>" . User::getTypeName(1) . "</label>";
            User::dropdown(['name' => 'users_id', 'value' => (int) $s['users_id'], 'right' => 'all', 'rand' => $rand]);
            echo "</div><div class='col-md-2'><label class='form-label'>" . Group::getTypeName(1) . "</label>";
            Group::dropdown(['name' => 'groups_id', 'value' => (int) $s['groups_id'], 'rand' => $rand + 1]);
            echo "</div><div class='col-auto d-flex gap-1'>";
            echo "<button type='submit' name='update' class='btn btn-primary' title='" . _sx('button', 'Save') . "'><i class='ti ti-device-floppy'></i></button>";
            if ($i > 0) {
                echo "<button type='submit' name='move_up' class='btn btn-outline-secondary'><i class='ti ti-arrow-up'></i></button>";
            }
            if ($i < count($steps) - 1) {
                echo "<button type='submit' name='move_down' class='btn btn-outline-secondary'><i class='ti ti-arrow-down'></i></button>";
            }
            echo "<button type='submit' name='purge' class='btn btn-outline-danger' onclick=\"return confirm('" . Util::e(__('Delete this step?', 'approvalchain')) . "')\"><i class='ti ti-trash'></i></button>";
            echo "</div></div></div>";
            \Html::closeForm();
        }

        if ($canedit) {
            $rand = mt_rand();
            echo "<form method='post' action='" . $url . "' class='card border-primary'><div class='card-body'>";
            echo "<h4>" . __('Add a step', 'approvalchain') . "</h4><div class='row g-2 align-items-end'>";
            echo "<input type='hidden' name='plugin_approvalchain_chains_id' value='" . $cid . "'>";
            echo "<div class='col-md-3'><label class='form-label'>" . __('Step name', 'approvalchain') . "</label>";
            echo "<input type='text' class='form-control' name='name' required></div>";
            echo "<div class='col-md-3'><label class='form-label'>" . __('Description', 'approvalchain') . "</label>";
            echo "<input type='text' class='form-control' name='comment'></div>";
            echo "<div class='col-md-2'><label class='form-label'>" . User::getTypeName(1) . "</label>";
            User::dropdown(['name' => 'users_id', 'value' => 0, 'right' => 'all', 'rand' => $rand]);
            echo "</div><div class='col-md-2'><label class='form-label'>" . Group::getTypeName(1) . "</label>";
            Group::dropdown(['name' => 'groups_id', 'value' => 0, 'rand' => $rand + 1]);
            echo "</div><div class='col-auto'><button type='submit' name='add' class='btn btn-primary'><i class='ti ti-plus'></i> " . _sx('button', 'Add') . "</button></div>";
            echo "</div></div>";
            \Html::closeForm();
        }
        echo "</div>";
    }
}
