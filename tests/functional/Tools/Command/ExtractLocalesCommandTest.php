<?php

namespace tests\functional\Tools\Command;

use Glpi\Tests\GLPITestCase;
use Glpi\Tools\Command\ExtractLocalesCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ExtractLocalesCommandTest extends GLPITestCase
{
    private string $test_dir;
    private ?string $plugin_dir = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->test_dir = sys_get_temp_dir() . '/glpi_test_locales_' . uniqid();
        mkdir($this->test_dir);
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->test_dir);
        if ($this->plugin_dir !== null && is_dir($this->plugin_dir)) {
            $this->removeDirectory($this->plugin_dir);
            $this->plugin_dir = null;
        }
        parent::tearDown();
    }

    public function testPluginExtraction(): void
    {
        // Mimic plugin structure
        $plugin_name = 'testlocales_' . uniqid();
        $this->plugin_dir = GLPI_ROOT . '/plugins/' . $plugin_name;

        if (!mkdir($this->plugin_dir)) {
            $this->markTestSkipped('Could not create temp plugin directory in ' . GLPI_ROOT . '/plugins');
        }
        mkdir($this->plugin_dir . '/locales');

        file_put_contents($this->plugin_dir . '/setup.php', "<?php define('PLUGIN_" . strtoupper($plugin_name) . "_VERSION', '1.0.0');");
        file_put_contents($this->plugin_dir . '/test.php', "<?php __('My String', '$plugin_name');");

        $command = new ExtractLocalesCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--plugin' => $plugin_name,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $dest_pot = $this->plugin_dir . '/locales/' . $plugin_name . '.pot';
        $this->assertFileExists($dest_pot);
        $content = file_get_contents($dest_pot);
        $this->assertStringContainsString('msgid "My String"', $content);
    }
}
