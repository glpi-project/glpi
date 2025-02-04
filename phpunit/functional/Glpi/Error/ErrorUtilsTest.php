<?php

namespace Glpi\Error;

use PHPUnit\Framework\Attributes\DataProvider;

class ErrorUtilsTest extends \DbTestCase
{
    public function testcleanPathsOnSafeContent()
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);
        $safeMessage = 'a string without GLPI_ROOT';

        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertEquals($safeMessage, ErrorUtils::cleanPaths($safeMessage));
    }

    public function testcleanPathsOnUnsafeContentsKeepOtherContents()
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);

        $data = 'a string with ' . \GLPI_ROOT . ' and other content';
        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertStringContainsString('other content', ErrorUtils::cleanPaths($data));
    }

    #[DataProvider('unsafeContentsProvider')]
    public function testcleanPathsOnUnsafeContentsRemovesGLPI_ROOT($data)
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);

        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertStringNotContainsString(\GLPI_ROOT, ErrorUtils::cleanPaths($data));
    }

    public static function unsafeContentsProvider(): array
    {
        return [
            [\GLPI_ROOT . 'bla bla'],
            ['bla bla' . \GLPI_ROOT],
            [\GLPI_ROOT],
            ['/path/' . \GLPI_ROOT . '/path'],
        ];
    }
}
