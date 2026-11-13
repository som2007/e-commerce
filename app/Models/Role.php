<?php

namespace Rk\Threadwell\Models;

use PDO;

class Role
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->db->prepare("
            SELECT
                id,
                name,
                display_name
            FROM roles
            WHERE name = ?
            LIMIT 1
        ");

        $statement->execute([$name]);

        $role = $statement->fetch();

        return $role ?: null;
    }
}