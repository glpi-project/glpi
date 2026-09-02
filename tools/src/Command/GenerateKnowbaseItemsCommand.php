<?php

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

namespace Glpi\Tools\Command;

use Entity_KnowbaseItem;
use Glpi\Console\AbstractCommand;
use Glpi\DBAL\QueryParam;
use Glpi\UI\IllustrationManager;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Comment;
use KnowbaseItem_Favorite;
use KnowbaseItem_Item;
use KnowbaseItem_KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_Revision;
use KnowbaseItem_User;
use KnowbaseItemTranslation;
use mysqli_stmt;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use User;

/**
 * Fill the knowledge base with a large, deeply nested article tree, to see how
 * the knowledge base behaves with a lot of data.
 *
 * The generated articles all live under a single "container" article attached
 * to the knowledge base root, so the whole data set can be removed in one go
 * with the `--purge` option.
 *
 * Articles are inserted with plain `INSERT` statements instead of
 * `KnowbaseItem::add()`: history, notifications and revisions are not wanted
 * here, and going through the framework for tens of thousands of articles would
 * take ages.
 */
final class GenerateKnowbaseItemsCommand extends AbstractCommand
{
    /** Only the container article gets a visibility row, descendants inherit it. */
    private const VISIBILITY_INHERITED = 'inherited';

    /** Every generated article gets its own visibility row. */
    private const VISIBILITY_ALL = 'all';

    /** No visibility row at all: only knowledge base administrators see the data. */
    private const VISIBILITY_NONE = 'none';

    /**
     * Number of inserted rows between two commits. Inserting everything in a
     * single transaction would build a huge undo log for no benefit.
     */
    private const COMMIT_EVERY = 500;

    /** Maximum number of ids per `IN (...)` clause built while purging. */
    private const PURGE_CHUNK_SIZE = 1000;

    /** Number of days in the past the generated dates are spread over. */
    private const DATE_SPREAD_DAYS = 730;

    private const DOMAINS = [
        'Networking', 'Printers', 'Workstations', 'Servers', 'Software deployment',
        'Security', 'User accounts', 'Messaging', 'Remote access', 'Backups',
        'Telephony', 'Mobile devices', 'Databases', 'Monitoring', 'Licences',
        'Onboarding', 'Storage', 'Virtualization', 'Cloud services', 'Peripherals',
    ];

    private const ASPECTS = [
        'installation', 'configuration', 'troubleshooting', 'migration',
        'maintenance', 'best practices', 'known issues', 'procedures',
        'reference', 'reporting',
    ];

    private const ACTIONS = [
        'How to configure', 'How to install', 'How to reset', 'How to migrate',
        'How to monitor', 'How to secure', 'Troubleshooting', 'Understanding',
        'Setting up', 'Diagnosing', 'Automating', 'Auditing',
    ];

    private const SUBJECTS = [
        'the VPN client', 'a network printer', 'the LDAP directory',
        'a Windows workstation', 'the mail relay', 'an SSL certificate',
        'a shared mailbox', 'the backup agent', 'a virtual machine',
        'the antivirus policy', 'a docking station', 'the wireless network',
        'an inventory agent', 'the helpdesk workflow', 'a database replica',
        'the software repository',
    ];

    private const WORDS = [
        'agent', 'account', 'backup', 'certificate', 'client', 'cluster',
        'command', 'configuration', 'connection', 'console', 'credentials',
        'database', 'device', 'directory', 'domain', 'driver', 'endpoint',
        'entity', 'firewall', 'gateway', 'group', 'hardware', 'helpdesk',
        'hostname', 'image', 'instance', 'interface', 'inventory', 'licence',
        'network', 'node', 'package', 'password', 'permission', 'policy',
        'port', 'printer', 'profile', 'protocol', 'proxy', 'queue', 'replica',
        'repository', 'request', 'resource', 'role', 'schedule', 'server',
        'service', 'session', 'setting', 'snapshot', 'storage', 'switch',
        'task', 'template', 'ticket', 'token', 'update', 'user', 'version',
        'volume', 'workstation',
    ];

    private int $author_id = 0;

    /** Timestamp the generated creation/update dates are computed from. */
    private int $reference_time = 0;

    /** @var string[] Available illustration ids, empty when assets are not built. */
    private array $illustrations = [];

    private mysqli_stmt $article_stmt;
    private mysqli_stmt $link_stmt;
    private mysqli_stmt $visibility_stmt;

    private bool $in_transaction = false;
    private int $pending_rows = 0;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_knowbase_items');
        $this->setDescription('Generate a large knowledge base article tree, to test the KB with a lot of data.');

        $this->addOption(
            'count',
            null,
            InputOption::VALUE_REQUIRED,
            'Total number of articles to create, section articles included',
            '10000'
        );
        $this->addOption(
            'depth',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of nested section levels created below the container article',
            '4'
        );
        $this->addOption(
            'branching',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of sections created below each section',
            '5'
        );
        $this->addOption(
            'faq-ratio',
            null,
            InputOption::VALUE_REQUIRED,
            'Percentage of leaf articles flagged as FAQ',
            '20'
        );
        $this->addOption(
            'visibility',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Visibility rows to create (root entity, recursive): "%s" (container only, descendants inherit it),'
                . ' "%s" (one row per article) or "%s" (none, only KB administrators see the data)',
                self::VISIBILITY_INHERITED,
                self::VISIBILITY_ALL,
                self::VISIBILITY_NONE
            ),
            self::VISIBILITY_INHERITED
        );
        $this->addOption(
            'container-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of the article holding the generated tree',
            'KB load test data'
        );
        $this->addOption(
            'author',
            null,
            InputOption::VALUE_REQUIRED,
            'Login of the user set as author of the generated articles (defaults to the root article author)'
        );
        $this->addOption(
            'seed',
            null,
            InputOption::VALUE_REQUIRED,
            'Random seed, to get a reproducible data set',
            '1'
        );
        $this->addOption(
            'purge',
            null,
            InputOption::VALUE_NONE,
            'Delete the container article matching --container-name and all its descendants,'
            . ' instead of generating new articles'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container_name = trim((string) $input->getOption('container-name'));
        if ($container_name === '') {
            $output->writeln('<error>The --container-name option cannot be empty.</error>');
            return Command::FAILURE;
        }

        if (!KnowbaseItem::hasRoot()) {
            $output->writeln(
                '<error>The knowledge base has no root article,'
                . ' run the database installation/update first.</error>'
            );
            return Command::FAILURE;
        }

        return $input->getOption('purge')
            ? $this->purge($container_name)
            : $this->generate($container_name);
    }

    /**
     * Insert the container article, the section tree below it, then spread the
     * remaining articles over the sections.
     */
    private function generate(string $container_name): int
    {
        $count      = (int) $this->input->getOption('count');
        $depth      = (int) $this->input->getOption('depth');
        $branching  = (int) $this->input->getOption('branching');
        $faq_ratio  = (int) $this->input->getOption('faq-ratio');
        $seed       = (int) $this->input->getOption('seed');
        $visibility = (string) $this->input->getOption('visibility');

        if ($depth < 1 || $branching < 1) {
            $this->output->writeln('<error>The --depth and --branching options must be greater than 0.</error>');
            return Command::FAILURE;
        }
        if ($faq_ratio < 0 || $faq_ratio > 100) {
            $this->output->writeln('<error>The --faq-ratio option must be a percentage between 0 and 100.</error>');
            return Command::FAILURE;
        }
        $allowed_visibilities = [self::VISIBILITY_INHERITED, self::VISIBILITY_ALL, self::VISIBILITY_NONE];
        if (!in_array($visibility, $allowed_visibilities, true)) {
            $this->output->writeln(
                sprintf(
                    '<error>The --visibility option must be one of: %s.</error>',
                    implode(', ', $allowed_visibilities)
                )
            );
            return Command::FAILURE;
        }

        $sections_count = $this->countSections($depth, $branching, $count);
        if ($sections_count === null) {
            $this->output->writeln(
                sprintf(
                    '<error>A depth of %d with %d sections per level does not fit in %d articles.'
                    . ' Raise --count, or lower --depth/--branching.</error>',
                    $depth,
                    $branching,
                    $count
                )
            );
            return Command::FAILURE;
        }
        $leaves_count = $count - 1 - $sections_count;

        $author_id = $this->findAuthorId();
        if ($author_id === null) {
            return Command::FAILURE;
        }
        $this->author_id = $author_id;

        $this->output->writeln(
            sprintf(
                '<info>About to create %d articles under "%s": %d section articles'
                . ' (%d nested levels, %d sections per parent) and %d leaf articles.</info>',
                $count,
                $container_name,
                $sections_count,
                $depth,
                $branching,
                $leaves_count
            )
        );
        $this->warnAboutExecutionTime();
        $this->askForConfirmation();

        mt_srand($seed);
        $this->reference_time = time();
        $this->illustrations  = $this->findIllustrationIds();
        $this->prepareStatements();

        $progress_bar = new ProgressBar($this->output, $count);
        $progress_bar->start();

        $this->beginTransaction();
        try {
            $container_id = $this->insertArticle(
                $container_name,
                KnowbaseItem::getRootId(),
                is_section: true,
                is_faq: false
            );
            $progress_bar->advance();
            if ($visibility !== self::VISIBILITY_NONE) {
                $this->insertEntityVisibility($container_id);
            }

            // Section articles, level by level. They are the "categories" of the
            // generated data set: each one holds sub-sections and articles.
            $sections      = [];
            $current_level = [$container_id];
            $section_seq   = 0;
            for ($level = 1; $level <= $depth; $level++) {
                $next_level = [];
                foreach ($current_level as $parent_id) {
                    for ($i = 0; $i < $branching; $i++) {
                        $section_seq++;
                        $section_id = $this->insertArticle(
                            $this->buildSectionName($level, $section_seq),
                            $parent_id,
                            is_section: true,
                            is_faq: false
                        );
                        if ($visibility === self::VISIBILITY_ALL) {
                            $this->insertEntityVisibility($section_id);
                        }
                        $next_level[] = $section_id;
                        $sections[]   = $section_id;
                        $progress_bar->advance();
                    }
                }
                $current_level = $next_level;
            }

            // Leaf articles, spread evenly over every section of every level.
            for ($i = 0; $i < $leaves_count; $i++) {
                $leaf_id = $this->insertArticle(
                    $this->buildArticleName($i + 1),
                    $sections[$i % count($sections)],
                    is_section: false,
                    is_faq: mt_rand(1, 100) <= $faq_ratio
                );
                if ($visibility === self::VISIBILITY_ALL) {
                    $this->insertEntityVisibility($leaf_id);
                }
                $progress_bar->advance();
            }

            $this->commitTransaction();
        } catch (Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        $progress_bar->finish();
        $this->output->write(PHP_EOL);

        $this->output->writeln(
            sprintf('<info>%d articles created.</info>', $count),
            OutputInterface::VERBOSITY_QUIET
        );
        $this->output->writeln(
            sprintf(
                '<info>Container article: %s</info>',
                $this->getArticleUrl($container_id)
            ),
            OutputInterface::VERBOSITY_QUIET
        );

        return Command::SUCCESS;
    }

    /**
     * Delete the container articles matching the given name and everything
     * below them.
     */
    private function purge(string $container_name): int
    {
        $container_ids = $this->findContainerIds($container_name);
        if ($container_ids === []) {
            $this->output->writeln(
                sprintf('<info>No article named "%s" found below the knowledge base root.</info>', $container_name),
                OutputInterface::VERBOSITY_QUIET
            );
            return Command::SUCCESS;
        }

        $ids = $this->findDescendantIds($container_ids);

        $this->output->writeln(
            sprintf(
                '<comment>You are about to delete %d articles ("%s" and its descendants).</comment>',
                count($ids),
                $container_name
            )
        );
        if (!$this->input->getOption('no-interaction')) {
            // Deleting articles is not reversible, so an interactive run has to
            // confirm it. `--no-interaction` is an explicit "go ahead".
            $this->askForConfirmation(false);
        }

        $db = $this->getDb();
        foreach (array_chunk($ids, self::PURGE_CHUNK_SIZE) as $chunk) {
            foreach ($this->getRelatedTables() as $table) {
                $db->delete($table, ['knowbaseitems_id' => $chunk]);
            }
            $db->delete(KnowbaseItem_KnowbaseItem::getTable(), [
                'OR' => [
                    'knowbaseitems_id'        => $chunk,
                    'knowbaseitems_id_parent' => $chunk,
                ],
            ]);
            $db->delete('glpi_logs', [
                'itemtype' => KnowbaseItem::class,
                'items_id' => $chunk,
            ]);
            $db->delete(KnowbaseItem::getTable(), ['id' => $chunk]);
        }

        $this->output->writeln(
            sprintf('<info>%d articles deleted.</info>', count($ids)),
            OutputInterface::VERBOSITY_QUIET
        );

        return Command::SUCCESS;
    }

    /**
     * Total number of section articles for the given structure, or null when it
     * does not leave room for at least one leaf article in $count.
     */
    private function countSections(int $depth, int $branching, int $count): ?int
    {
        $total = 0;
        $nodes = 1;
        for ($level = 1; $level <= $depth; $level++) {
            $nodes *= $branching;
            $total += $nodes;
            if ($total + 1 >= $count) {
                // Already too big, no need to compute the remaining levels.
                return null;
            }
        }

        return $total;
    }

    /**
     * Id of the user set as author of the generated articles, or null when the
     * `--author` option does not match any user.
     */
    private function findAuthorId(): ?int
    {
        $login = $this->input->getOption('author');
        if ($login !== null) {
            $user = new User();
            if (!$user->getFromDBbyName((string) $login)) {
                $this->output->writeln(
                    sprintf('<error>No user found with the "%s" login.</error>', $login)
                );
                return null;
            }
            return $user->getID();
        }

        // Default to the root article author, which is the system user on a
        // freshly installed GLPI. Articles are then owned by nobody in
        // particular, so the "I am the author" visibility rule never applies to
        // them and visibility behaves the same for every tested user.
        $root = new KnowbaseItem();
        if (!$root->getFromDB(KnowbaseItem::getRootId())) {
            $this->output->writeln('<error>The knowledge base root article cannot be loaded.</error>');
            return null;
        }

        return (int) $root->fields['users_id'];
    }

    /**
     * @return string[] Native illustration ids, empty when the front-end assets
     *                  holding their definitions are not available.
     */
    private function findIllustrationIds(): array
    {
        try {
            return (new IllustrationManager())->getAllIconsIds();
        } catch (Throwable) {
            return [];
        }
    }

    private function prepareStatements(): void
    {
        $db = $this->getDb();

        $this->article_stmt = $db->prepare(
            $db->buildInsert(KnowbaseItem::getTable(), [
                'entities_id'   => new QueryParam(),
                'is_recursive'  => new QueryParam(),
                'name'          => new QueryParam(),
                'answer'        => new QueryParam(),
                'description'   => new QueryParam(),
                'illustration'  => new QueryParam(),
                'is_faq'        => new QueryParam(),
                'is_pinned'     => new QueryParam(),
                'users_id'      => new QueryParam(),
                'view'          => new QueryParam(),
                'date_creation' => new QueryParam(),
                'date_mod'      => new QueryParam(),
            ])
        );

        $this->link_stmt = $db->prepare(
            $db->buildInsert(KnowbaseItem_KnowbaseItem::getTable(), [
                'knowbaseitems_id'        => new QueryParam(),
                'knowbaseitems_id_parent' => new QueryParam(),
            ])
        );

        $this->visibility_stmt = $db->prepare(
            $db->buildInsert(Entity_KnowbaseItem::getTable(), [
                'knowbaseitems_id' => new QueryParam(),
                'entities_id'      => new QueryParam(),
                'is_recursive'     => new QueryParam(),
            ])
        );
    }

    /**
     * Insert an article and its link to the given parent, and return its id.
     */
    private function insertArticle(string $name, int $parent_id, bool $is_section, bool $is_faq): int
    {
        $db = $this->getDb();
        [$creation_date, $update_date] = $this->buildDates();

        $db->executeStatement($this->article_stmt, [
            0,                                                // entities_id
            1,                                                // is_recursive
            $name,
            $this->buildAnswer(),
            $this->buildSentence(mt_rand(8, 14)),              // description
            $is_section ? $this->pickIllustration() : '',
            (int) $is_faq,
            // A few pinned articles, to also exercise that part of the listing.
            (int) (!$is_section && mt_rand(1, 100) === 1),
            $this->author_id,
            mt_rand(0, 500),                                  // view
            $creation_date,
            $update_date,
        ]);
        $id = (int) $db->insertId();

        $db->executeStatement($this->link_stmt, [$id, $parent_id]);

        $this->onRowsInserted(2);

        return $id;
    }

    private function insertEntityVisibility(int $article_id): void
    {
        $this->getDb()->executeStatement($this->visibility_stmt, [$article_id, 0, 1]);
        $this->onRowsInserted(1);
    }

    private function pickIllustration(): string
    {
        if ($this->illustrations === []) {
            return '';
        }

        return $this->illustrations[mt_rand(0, count($this->illustrations) - 1)];
    }

    private function buildSectionName(int $level, int $sequence): string
    {
        $domain = self::DOMAINS[$sequence % count(self::DOMAINS)];
        if ($level === 1) {
            return sprintf('%s #%d', $domain, $sequence);
        }

        // Both indexes advance at a different pace, so sibling sections do not
        // all share the same aspect while combinations still vary over the tree.
        $aspect = self::ASPECTS[intdiv($sequence, 3) % count(self::ASPECTS)];

        return sprintf('%s - %s #%d', $domain, $aspect, $sequence);
    }

    private function buildArticleName(int $sequence): string
    {
        $action  = self::ACTIONS[$sequence % count(self::ACTIONS)];
        $subject = self::SUBJECTS[intdiv($sequence, count(self::ACTIONS)) % count(self::SUBJECTS)];

        return sprintf('%s %s #%d', $action, $subject, $sequence);
    }

    /**
     * Build a rich text answer, so the generated articles weight about as much
     * as real ones.
     */
    private function buildAnswer(): string
    {
        $blocks = ['<p>' . $this->buildParagraph(mt_rand(2, 4)) . '</p>'];

        $chapters = mt_rand(2, 4);
        for ($i = 0; $i < $chapters; $i++) {
            $blocks[] = '<h2>' . $this->buildSentence(mt_rand(2, 4), with_dot: false) . '</h2>';
            $blocks[] = '<p>' . $this->buildParagraph(mt_rand(3, 6)) . '</p>';
            if (mt_rand(0, 2) === 0) {
                $items = [];
                $items_count = mt_rand(3, 5);
                for ($j = 0; $j < $items_count; $j++) {
                    $items[] = '<li>' . $this->buildSentence(mt_rand(5, 12)) . '</li>';
                }
                $blocks[] = '<ul>' . implode('', $items) . '</ul>';
            }
        }

        return implode("\n", $blocks);
    }

    private function buildParagraph(int $sentences): string
    {
        $built = [];
        for ($i = 0; $i < $sentences; $i++) {
            $built[] = $this->buildSentence(mt_rand(8, 18));
        }

        return implode(' ', $built);
    }

    private function buildSentence(int $words, bool $with_dot = true): string
    {
        $picked = [];
        for ($i = 0; $i < $words; $i++) {
            $picked[] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
        }

        return ucfirst(implode(' ', $picked)) . ($with_dot ? '.' : '');
    }

    /**
     * @return array{string, string} Creation and update dates, spread over the
     *                               last `DATE_SPREAD_DAYS` days.
     */
    private function buildDates(): array
    {
        $created  = $this->reference_time - mt_rand(0, self::DATE_SPREAD_DAYS * DAY_TIMESTAMP);
        $modified = $created + mt_rand(0, $this->reference_time - $created);

        return [
            date('Y-m-d H:i:s', $created),
            date('Y-m-d H:i:s', $modified),
        ];
    }

    /**
     * Ids of the articles named $container_name that are attached to the
     * knowledge base root, i.e. the containers a previous run created.
     *
     * @return int[]
     */
    private function findContainerIds(string $container_name): array
    {
        $link_table = KnowbaseItem_KnowbaseItem::getTable();

        $ids = [];
        foreach ($this->getDb()->request([
            'SELECT'     => KnowbaseItem::getTableField('id'),
            'FROM'       => KnowbaseItem::getTable(),
            'INNER JOIN' => [
                $link_table => [
                    'ON' => [
                        $link_table              => 'knowbaseitems_id',
                        KnowbaseItem::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                KnowbaseItem::getTableField('name')                       => $container_name,
                KnowbaseItem_KnowbaseItem::getTableField('knowbaseitems_id_parent') => KnowbaseItem::getRootId(),
            ],
        ]) as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    /**
     * The given articles plus every article below them.
     *
     * @param int[] $container_ids
     *
     * @return int[]
     */
    private function findDescendantIds(array $container_ids): array
    {
        $root_id = KnowbaseItem::getRootId();
        $seen    = array_fill_keys($container_ids, true);
        $to_walk = $container_ids;

        while ($to_walk !== []) {
            $next = [];
            foreach (array_chunk($to_walk, self::PURGE_CHUNK_SIZE) as $chunk) {
                foreach ($this->getDb()->request([
                    'SELECT' => 'knowbaseitems_id',
                    'FROM'   => KnowbaseItem_KnowbaseItem::getTable(),
                    'WHERE'  => ['knowbaseitems_id_parent' => $chunk],
                ]) as $row) {
                    $child_id = (int) $row['knowbaseitems_id'];
                    // The tree is a DAG: an article may have several parents,
                    // and the root must never be deleted.
                    if ($child_id === $root_id || isset($seen[$child_id])) {
                        continue;
                    }
                    $seen[$child_id] = true;
                    $next[]          = $child_id;
                }
            }
            $to_walk = $next;
        }

        return array_keys($seen);
    }

    /**
     * Tables holding rows attached to an article through a `knowbaseitems_id`
     * column, cleaned up on purge.
     *
     * @return string[]
     */
    private function getRelatedTables(): array
    {
        return [
            Entity_KnowbaseItem::getTable(),
            Group_KnowbaseItem::getTable(),
            KnowbaseItem_Comment::getTable(),
            KnowbaseItem_Favorite::getTable(),
            KnowbaseItem_Item::getTable(),
            KnowbaseItem_Profile::getTable(),
            KnowbaseItem_Revision::getTable(),
            KnowbaseItem_User::getTable(),
            KnowbaseItemTranslation::getTable(),
        ];
    }

    private function getArticleUrl(int $id): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // `KnowbaseItem::getFormURL()` picks the URL from the current session
        // interface, which is not set in a CLI context and would fall back to
        // the helpdesk FAQ. The generated data is meant to be browsed from the
        // central interface, so its URL is built here.
        return sprintf(
            '%s%s/front/knowbaseitem.form.php?id=%d',
            $CFG_GLPI['url_base'],
            $CFG_GLPI['root_doc'],
            $id
        );
    }

    /**
     * Commit the current transaction and start a new one once enough rows have
     * been inserted.
     */
    private function onRowsInserted(int $rows): void
    {
        $this->pending_rows += $rows;
        if ($this->pending_rows < self::COMMIT_EVERY) {
            return;
        }

        $this->commitTransaction();
        $this->beginTransaction();
    }

    private function beginTransaction(): void
    {
        $this->getDb()->beginTransaction();
        $this->in_transaction = true;
        $this->pending_rows   = 0;
    }

    private function commitTransaction(): void
    {
        $this->getDb()->commit();
        $this->in_transaction = false;
        $this->pending_rows   = 0;
    }

    private function rollbackTransaction(): void
    {
        if (!$this->in_transaction) {
            return;
        }

        $this->getDb()->rollBack();
        $this->in_transaction = false;
        $this->pending_rows   = 0;
    }
}
