<?php

class RoleSeeder
{
    public function run(PDO $db): void
    {
        $roles = [
            ['customer', 'Customer'],
            ['admin', 'Administrator'],
            ['staff', 'Staff'],
        ];

        $exists = $db->prepare('SELECT id FROM roles WHERE name = ?');
        $insert = $db->prepare('INSERT INTO roles (name, display_name) VALUES (?, ?)');

        foreach ($roles as [$name, $display]) {
            $exists->execute([$name]);

            if ($exists->fetchColumn() === false) {
                $insert->execute([$name, $display]);
            }
        }

        echo "Roles seeded successfully." . PHP_EOL;
    }
}
