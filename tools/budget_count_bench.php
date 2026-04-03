<?php

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

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use Glpi\Kernel\Kernel;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

/**
 * Parse CLI options.
 *
 * Supported options:
 *   --env=production|development|testing|...
 *   --budget-id=<existing budget id>
 *   --source=infocom|contract
 *   --items=<number of associated items to generate>
 *   --costs-per-item=<number of costs rows per contract>
 *   --runs=<benchmark iterations>
 *   --warmup=<warmup iterations>
 *   --entity-id=<target entity>
 *   --batch-size=<insert batch size>
 *   --keep-data
 *   --help
 *
 * @param string[] $argv
 * @return array<string, bool|int|string|null>
 */
function parseOptions(array $argv): array
{
    $options = [
        'env'            => null,
        'budget-id'      => null,
        'source'         => 'infocom',
        'items'          => 50000,
        'costs-per-item' => 2,
        'runs'           => 5,
        'warmup'         => 1,
        'entity-id'      => 0,
        'batch-size'     => 1000,
        'keep-data'      => false,
        'help'           => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }

        if ($arg === '--keep-data') {
            $options['keep-data'] = true;
            continue;
        }

        if (!str_starts_with($arg, '--') || !str_contains($arg, '=')) {
            throw new InvalidArgumentException(sprintf('Unknown option "%s".', $arg));
        }

        [$name, $value] = explode('=', substr($arg, 2), 2);
        if (!array_key_exists($name, $options)) {
            throw new InvalidArgumentException(sprintf('Unknown option "--%s".', $name));
        }

        $options[$name] = match ($name) {
            'budget-id', 'items', 'costs-per-item', 'runs', 'warmup', 'entity-id', 'batch-size'
                => (int) $value,
            default => $value,
        };
    }

    if (!in_array($options['source'], ['infocom', 'contract'], true)) {
        throw new InvalidArgumentException('Option --source must be either "infocom" or "contract".');
    }
    if ((int) $options['items'] < 1) {
        throw new InvalidArgumentException('Option --items must be greater than 0.');
    }
    if ((int) $options['costs-per-item'] < 1) {
        throw new InvalidArgumentException('Option --costs-per-item must be greater than 0.');
    }
    if ((int) $options['runs'] < 1) {
        throw new InvalidArgumentException('Option --runs must be greater than 0.');
    }
    if ((int) $options['warmup'] < 0) {
        throw new InvalidArgumentException('Option --warmup must be 0 or greater.');
    }
    if ((int) $options['batch-size'] < 1) {
        throw new InvalidArgumentException('Option --batch-size must be greater than 0.');
    }

    return $options;
}

function printUsage(): void
{
    echo <<<TXT
Usage:
  php tools/budget_count_bench.php [options]

Examples:
  php tools/budget_count_bench.php --items=50000
  php tools/budget_count_bench.php --source=contract --items=50000 --costs-per-item=3
  php tools/budget_count_bench.php --budget-id=12345 --runs=10
  php tools/budget_count_bench.php --env=testing --items=50000 --keep-data

Notes:
  - By default the script creates synthetic data inside a transaction and rolls it back at the end.
  - Use --keep-data to commit the generated dataset.
  - When --budget-id is provided, no synthetic data is created.
  - The benchmark compares:
      1. legacy count strategy: COUNT(*) over the detailed UNION used by showItems()
      2. current optimized strategy: Budget::countForBudget()

TXT;
}

/**
 * Initialize a CLI session that bypasses rights like cron jobs do.
 */
function bootstrapCliSession(int $entity_id): void
{
    global $CFG_GLPI;

    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = [];
    }

    $_SESSION['glpicronuserrunning'] = 'budget_count_bench';
    $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
    $_SESSION['glpiactive_entity'] = $entity_id;
    $_SESSION['glpiactiveentities'] = [$entity_id];
    $_SESSION['glpiactiveentities_string'] = sprintf("'%d'", $entity_id);
    $_SESSION['glpishowallentities'] = 1;

    $CFG_GLPI['root_doc'] = '/glpi';
}

/**
 * @return int
 */
function getNextId(string $table): int
{
    global $DB;

    $row = $DB->request([
        'SELECT' => [
            new QueryExpression('COALESCE(MAX(' . $DB::quoteName('id') . '), 0)', 'max_id'),
        ],
        'FROM'   => $table,
    ])->current();

    return (int) ($row['max_id'] ?? 0) + 1;
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function insertRows(string $table, array $rows): void
{
    global $DB;

    if ($rows === []) {
        return;
    }

    $columns = array_keys(reset($rows));
    $quoted_columns = array_map([DBmysql::class, 'quoteName'], $columns);
    $values = [];

    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            $line[] = $value instanceof QueryExpression
                ? $value->getValue()
                : DBmysql::quoteValue($value);
        }
        $values[] = '(' . implode(', ', $line) . ')';
    }

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES %s',
        DBmysql::quoteName($table),
        implode(', ', $quoted_columns),
        implode(', ', $values)
    );

    $DB->doQuery($sql);
}

function createBenchmarkBudget(int $entity_id, string $name): Budget
{
    $budget = new Budget();
    $budgets_id = $budget->add([
        'name'        => $name,
        'entities_id' => $entity_id,
        'value'       => 0,
    ]);

    if (!$budgets_id || !$budget->getFromDB($budgets_id)) {
        throw new RuntimeException('Failed to create benchmark budget.');
    }

    return $budget;
}

function populateInfocomDataset(Budget $budget, int $items, int $entity_id, int $batch_size): void
{
    $computer_id = getNextId(Computer::getTable());
    $infocom_id = getNextId(Infocom::getTable());

    for ($offset = 0; $offset < $items; $offset += $batch_size) {
        $batch_count = min($batch_size, $items - $offset);
        $computers = [];
        $infocoms = [];

        for ($i = 0; $i < $batch_count; $i++) {
            $current_computer_id = $computer_id++;
            $current_infocom_id = $infocom_id++;
            $suffix = $offset + $i + 1;

            $computers[] = [
                'id'          => $current_computer_id,
                'entities_id' => $entity_id,
                'name'        => sprintf('Budget bench computer %d', $suffix),
                'is_template' => 0,
                'is_deleted'  => 0,
                'is_recursive'=> 0,
            ];

            $infocoms[] = [
                'id'          => $current_infocom_id,
                'items_id'    => $current_computer_id,
                'itemtype'    => Computer::class,
                'entities_id' => $entity_id,
                'is_recursive'=> 0,
                'value'       => 100,
                'budgets_id'  => $budget->fields['id'],
            ];
        }

        insertRows(Computer::getTable(), $computers);
        insertRows(Infocom::getTable(), $infocoms);
    }
}

function populateContractDataset(Budget $budget, int $items, int $costs_per_item, int $entity_id, int $batch_size): void
{
    $contract_id = getNextId(Contract::getTable());
    $contract_cost_id = getNextId(ContractCost::getTable());

    for ($offset = 0; $offset < $items; $offset += $batch_size) {
        $batch_count = min($batch_size, $items - $offset);
        $contracts = [];
        $costs = [];

        for ($i = 0; $i < $batch_count; $i++) {
            $current_contract_id = $contract_id++;
            $suffix = $offset + $i + 1;

            $contracts[] = [
                'id'          => $current_contract_id,
                'entities_id' => $entity_id,
                'is_recursive'=> 0,
                'name'        => sprintf('Budget bench contract %d', $suffix),
                'is_template' => 0,
                'is_deleted'  => 0,
            ];

            for ($cost_index = 0; $cost_index < $costs_per_item; $cost_index++) {
                $costs[] = [
                    'id'            => $contract_cost_id++,
                    'contracts_id'  => $current_contract_id,
                    'name'          => sprintf('Budget bench contract cost %d.%d', $suffix, $cost_index + 1),
                    'cost'          => 100,
                    'budgets_id'    => $budget->fields['id'],
                    'entities_id'   => $entity_id,
                    'is_recursive'  => 0,
                ];
            }
        }

        insertRows(Contract::getTable(), $contracts);
        insertRows(ContractCost::getTable(), $costs);
    }
}

/**
 * @param array<string, bool|int|string|null> $options
 */
function createSyntheticBudget(array $options): Budget
{
    $budget = createBenchmarkBudget(
        entity_id: (int) $options['entity-id'],
        name: sprintf(
            'Budget bench %s %s',
            (string) $options['source'],
            date('Y-m-d H:i:s')
        )
    );

    $setup_start = microtime(true);
    if ($options['source'] === 'infocom') {
        populateInfocomDataset(
            budget: $budget,
            items: (int) $options['items'],
            entity_id: (int) $options['entity-id'],
            batch_size: (int) $options['batch-size']
        );
    } else {
        populateContractDataset(
            budget: $budget,
            items: (int) $options['items'],
            costs_per_item: (int) $options['costs-per-item'],
            entity_id: (int) $options['entity-id'],
            batch_size: (int) $options['batch-size']
        );
    }

    printf("+ Dataset generated in %.3f s\n", microtime(true) - $setup_start);

    if (!$budget->getFromDB($budget->fields['id'])) {
        throw new RuntimeException('Failed to reload benchmark budget.');
    }

    return $budget;
}

function loadExistingBudget(int $budget_id): Budget
{
    $budget = new Budget();
    if (!$budget->getFromDB($budget_id)) {
        throw new RuntimeException(sprintf('Budget %d was not found.', $budget_id));
    }

    return $budget;
}

function legacyCountForBudget(Budget $budget): int
{
    global $DB;

    $get_item_list_criteria = Closure::bind(
        static fn(Budget $item): QueryUnion => $item->getItemListCriteria(),
        null,
        Budget::class
    );

    $result = $DB->request([
        'FROM'  => $get_item_list_criteria($budget),
        'COUNT' => 'cpt',
    ])->current();

    return (int) ($result['cpt'] ?? 0);
}

/**
 * @param callable(): int $callback
 * @return array{result: int, avg_ms: float, min_ms: float, max_ms: float, runs: list<float>}
 */
function benchmark(callable $callback, int $warmup, int $runs): array
{
    for ($i = 0; $i < $warmup; $i++) {
        $callback();
    }

    $samples = [];
    $result = 0;
    for ($i = 0; $i < $runs; $i++) {
        $start = hrtime(true);
        $result = $callback();
        $samples[] = (hrtime(true) - $start) / 1_000_000;
    }

    return [
        'result' => $result,
        'avg_ms' => array_sum($samples) / count($samples),
        'min_ms' => min($samples),
        'max_ms' => max($samples),
        'runs'   => $samples,
    ];
}

function printBenchmarkRow(string $label, array $stats): void
{
    printf(
        "  %-10s avg=%10.3f ms  min=%10.3f ms  max=%10.3f ms  result=%d\n",
        $label,
        $stats['avg_ms'],
        $stats['min_ms'],
        $stats['max_ms'],
        $stats['result']
    );
}

try {
    $options = parseOptions($_SERVER['argv']);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n\n");
    printUsage();
    exit(1);
}

if ($options['help']) {
    printUsage();
    exit(0);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel($options['env']);
$kernel->boot();
bootstrapCliSession((int) $options['entity-id']);

/** @var DBmysql $DB */
global $DB;

$budget = null;
$has_transaction = false;
$generated_dataset = false;

try {
    if ($options['budget-id'] !== null) {
        $budget = loadExistingBudget((int) $options['budget-id']);
    } else {
        $DB->beginTransaction();
        $has_transaction = true;
        $generated_dataset = true;
        $budget = createSyntheticBudget($options);
    }

    echo "+ Budget count benchmark\n";
    printf("+ Environment: %s\n", (string) ($options['env'] ?? 'default'));
    printf("+ Budget ID: %d\n", $budget->fields['id']);
    if ($generated_dataset) {
        printf("+ Synthetic dataset: source=%s, items=%d\n", (string) $options['source'], (int) $options['items']);
        if ($options['source'] === 'contract') {
            printf("+ Costs per contract: %d\n", (int) $options['costs-per-item']);
        }
        printf("+ Cleanup mode: %s\n", $options['keep-data'] ? 'commit' : 'rollback');
    } else {
        echo "+ Synthetic dataset: no (existing budget)\n";
    }

    $legacy = benchmark(
        callback: static fn(): int => legacyCountForBudget($budget),
        warmup: (int) $options['warmup'],
        runs: (int) $options['runs']
    );
    $optimized = benchmark(
        callback: static fn(): int => Budget::countForBudget($budget),
        warmup: (int) $options['warmup'],
        runs: (int) $options['runs']
    );

    echo "+ Results\n";
    printBenchmarkRow('legacy', $legacy);
    printBenchmarkRow('optimized', $optimized);

    if ($legacy['result'] !== $optimized['result']) {
        throw new RuntimeException(sprintf(
            'Count mismatch detected: legacy=%d optimized=%d',
            $legacy['result'],
            $optimized['result']
        ));
    }

    printf("+ Count match: yes (%d)\n", $optimized['result']);
    if ($optimized['avg_ms'] > 0.0) {
        printf("+ Speedup: %.2fx\n", $legacy['avg_ms'] / $optimized['avg_ms']);
    }

    if ($has_transaction) {
        if ($options['keep-data']) {
            $DB->commit();
            echo "+ Transaction committed\n";
        } else {
            $DB->rollBack();
            echo "+ Transaction rolled back\n";
        }
    }
} catch (Throwable $e) {
    if ($has_transaction) {
        $DB->rollBack();
    }

    fwrite(STDERR, 'Benchmark failed: ' . $e->getMessage() . "\n");
    exit(1);
}
