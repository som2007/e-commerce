<?php

namespace Rk\Threadwell\Models;

use PDO;

class User
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare("
            SELECT
                id,
                role_id,
                first_name,
                last_name,
                email,
                phone,
                password,
                status,
                email_verified_at,
                last_login_at,
                created_at
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $statement->execute([$email]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
{
    $statement = $this->db->prepare("
        SELECT
            id,
            role_id,
            first_name,
            last_name,
            email,
            phone,
            status,
            email_verified_at,
            last_login_at,
            created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $statement->execute([$id]);

    $user = $statement->fetch();

    return $user ?: null;
}


public function markEmailVerified(int $userId): void
{
    $statement = $this->db->prepare("
        UPDATE users
        SET email_verified_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $statement->execute([$userId]);
}

public function updatePassword(
    int $userId,
    string $passwordHash
): void {
    $statement = $this->db->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
    ");

    $statement->execute([
        $passwordHash,
        $userId
    ]);
}


public function updateLastLogin(int $userId): void
{
    $statement = $this->db->prepare("
        UPDATE users
        SET last_login_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $statement->execute([$userId]);
}

    public function create(array $data): int
    {
        $statement = $this->db->prepare("
            INSERT INTO users (
                role_id,
                first_name,
                last_name,
                email,
                phone,
                password,
                status
            )
            VALUES (
                :role_id,
                :first_name,
                :last_name,
                :email,
                :phone,
                :password,
                :status
            )
        ");

        $statement->execute([
            'role_id' => $data['role_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => $data['status'] ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }


    public function updateUnverifiedUser(
    int $userId,
    array $data
): void {
    $statement = $this->db->prepare("
        UPDATE users
        SET
            first_name = :first_name,
            last_name = :last_name,
            phone = :phone,
            password = :password,
            status = 'active',
            email_verified_at = NULL
        WHERE id = :id
          AND email_verified_at IS NULL
    ");

    $statement->execute([
        'id' => $userId,
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'] ?? null,
        'phone' => $data['phone'] ?? null,
        'password' => $data['password'],
    ]);
}
}