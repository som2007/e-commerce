<?php

namespace Rk\Threadwell\Core;

use PDO;

/**
 * PDO wrapper used ONLY when DB_DRIVER=sqlite (handy for quick local testing
 * without MySQL). It translates the MySQL flavoured DDL used in migrations
 * into SQLite compatible SQL. Production should use MySQL.
 */
class CompatPdo extends PDO
{
    public function exec(string $statement): int|false
    {
        return parent::exec($this->translate($statement));
    }

    private function translate(string $sql): string
    {
        if (!preg_match('/^\s*CREATE\s+TABLE/i', $sql)) {
            return $sql;
        }

        $sql = preg_replace(
            '/BIGINT\s+UNSIGNED\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i',
            'INTEGER PRIMARY KEY AUTOINCREMENT',
            $sql
        );
        $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);
        $sql = preg_replace('/ENUM\s*\([^)]*\)/i', 'TEXT', $sql);
        $sql = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/,\s*(?:INDEX|KEY)\s+\w+\s*\([^)]*\)/i', '', $sql);
        $sql = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/COLLATE\s*=\s*\w+/i', '', $sql);

        return $sql;
    }
}
