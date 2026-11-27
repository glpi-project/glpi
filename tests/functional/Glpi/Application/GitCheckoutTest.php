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

namespace tests\units\Glpi\Application;

use Glpi\Application\GitCheckout;
use Glpi\Tests\GLPITestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;

class GitCheckoutTest extends GLPITestCase
{
    private const SHA1 = '0123456789abcdef0123456789abcdef01234567';
    private const OTHER_SHA1 = 'fedcba9876543210fedcba9876543210fedcba98';
    private const SHA256 = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    /**
     * @return iterable<string, array{structure: array<string, mixed>, is_git: bool, expected: ?string}>
     */
    public static function revisionProvider(): iterable
    {
        yield 'no git directory' => [
            'structure' => ['glpi' => []],
            'is_git'    => false,
            'expected'  => null,
        ];

        yield 'branch with a loose ref' => [
            'structure' => [
                'glpi' => [
                    '.git' => [
                        'HEAD' => "ref: refs/heads/main\n",
                        'refs' => ['heads' => ['main' => self::SHA1 . "\n"]],
                    ],
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'branch with a packed ref' => [
            'structure' => [
                'glpi' => [
                    '.git' => [
                        'HEAD'        => "ref: refs/heads/main\n",
                        'packed-refs' => "# pack-refs with: peeled fully-peeled sorted\n"
                            . self::OTHER_SHA1 . " refs/heads/other\n"
                            . self::SHA1 . " refs/heads/main\n",
                    ],
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'loose ref takes precedence over packed ref' => [
            'structure' => [
                'glpi' => [
                    '.git' => [
                        'HEAD'        => "ref: refs/heads/main\n",
                        'refs'        => ['heads' => ['main' => self::SHA1 . "\n"]],
                        'packed-refs' => self::OTHER_SHA1 . " refs/heads/main\n",
                    ],
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'detached HEAD' => [
            'structure' => [
                'glpi' => [
                    '.git' => ['HEAD' => self::SHA1 . "\n"],
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'SHA-256 repository' => [
            'structure' => [
                'glpi' => [
                    '.git' => ['HEAD' => self::SHA256 . "\n"],
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA256,
        ];

        yield 'unborn branch' => [
            'structure' => [
                'glpi' => [
                    '.git' => ['HEAD' => "ref: refs/heads/main\n"],
                ],
            ],
            'is_git'    => true,
            'expected'  => null,
        ];

        yield 'ref outside of refs directory' => [
            'structure' => [
                'glpi' => [
                    '.git' => [
                        'HEAD'   => "ref: refs/../config\n",
                        'config' => self::SHA1,
                    ],
                ],
            ],
            'is_git'    => true,
            'expected'  => null,
        ];

        yield 'invalid HEAD contents' => [
            'structure' => [
                'glpi' => [
                    '.git' => ['HEAD' => "not a ref\n"],
                ],
            ],
            'is_git'    => true,
            'expected'  => null,
        ];

        yield 'linked worktree' => [
            'structure' => [
                'repo' => [
                    '.git' => [
                        'HEAD'      => "ref: refs/heads/main\n",
                        'refs'      => ['heads' => ['main' => self::OTHER_SHA1 . "\n", 'feature' => self::SHA1 . "\n"]],
                        'worktrees' => [
                            'glpi' => [
                                'HEAD'      => "ref: refs/heads/feature\n",
                                'commondir' => "../..\n",
                            ],
                        ],
                    ],
                ],
                'glpi' => [
                    '.git' => "gitdir: ../repo/.git/worktrees/glpi\n",
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'linked worktree with a packed ref' => [
            'structure' => [
                'repo' => [
                    '.git' => [
                        'HEAD'        => "ref: refs/heads/main\n",
                        'packed-refs' => self::SHA1 . " refs/heads/feature\n",
                        'worktrees'   => [
                            'glpi' => [
                                'HEAD'      => "ref: refs/heads/feature\n",
                                'commondir' => "../..\n",
                            ],
                        ],
                    ],
                ],
                'glpi' => [
                    '.git' => "gitdir: ../repo/.git/worktrees/glpi\n",
                ],
            ],
            'is_git'    => true,
            'expected'  => self::SHA1,
        ];

        yield 'git file pointing to a missing directory' => [
            'structure' => [
                'glpi' => [
                    '.git' => "gitdir: ../missing/.git\n",
                ],
            ],
            'is_git'    => true,
            'expected'  => null,
        ];
    }

    /**
     * @param array<string, mixed> $structure
     */
    #[DataProvider('revisionProvider')]
    public function testGetRevision(array $structure, bool $is_git, ?string $expected): void
    {
        vfsStream::setup('root', null, $structure);

        $root_dir = vfsStream::url('root/glpi');

        $this->assertSame($is_git, GitCheckout::isGitCheckout($root_dir));
        $this->assertSame($expected, GitCheckout::getRevision($root_dir));
    }
}
