<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DatabaseConfigTest extends TestCase
{
    private function getConfigContent(): string
    {
        $configPath = dirname(__DIR__, 2) . '/config/database.php';
        $this->assertFileExists($configPath);

        return file_get_contents($configPath);
    }

    /**
     * Test that database config does not use deprecated PDO::MYSQL_ATTR_INIT_COMMAND.
     *
     * In PHP 8.5+, PDO::MYSQL_ATTR_INIT_COMMAND is deprecated in favor of
     * Pdo\Mysql::ATTR_INIT_COMMAND. This test ensures the config file uses
     * the new constant to avoid deprecation warnings.
     */
    public function test_database_config_does_not_use_deprecated_pdo_constant(): void
    {
        $content = $this->getConfigContent();

        // The deprecated constant should NOT be present
        $this->assertStringNotContainsString(
            '\PDO::MYSQL_ATTR_INIT_COMMAND',
            $content,
            'config/database.php should not use deprecated \PDO::MYSQL_ATTR_INIT_COMMAND. Use \Pdo\Mysql::ATTR_INIT_COMMAND instead.'
        );
    }

    /**
     * Test that database config uses the new Pdo\Mysql namespace constants.
     */
    public function test_database_config_uses_new_pdo_mysql_constants(): void
    {
        $content = $this->getConfigContent();

        // Should import the Pdo\Mysql namespace
        $this->assertStringContainsString(
            'use Pdo\Mysql;',
            $content,
            'config/database.php should import Pdo\Mysql namespace'
        );
    }

    /**
     * Test that mysql connection options use Mysql:: prefixed constants.
     */
    public function test_mysql_connection_uses_mysql_class_constants(): void
    {
        $content = $this->getConfigContent();

        // Should use Mysql::ATTR_SSL_CA (already correct)
        $this->assertStringContainsString(
            'Mysql::ATTR_SSL_CA',
            $content,
            'config/database.php should use Mysql::ATTR_SSL_CA'
        );

        // Count occurrences of Mysql:: to ensure all options use the new style
        $mysqlConstantCount = substr_count($content, 'Mysql::');
        $this->assertGreaterThanOrEqual(
            4, // At least 2 SSL_CA + 2 INIT_COMMAND replacements
            $mysqlConstantCount,
            'config/database.php should use Mysql:: constants for all MySQL options'
        );
    }
}
