<?php

namespace Rk\Threadwell\Models;

use PDO;

class EmailVerification
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        int $userId,
        string $email,
        string $otpHash,
        string $expiresAt
    ): int {
        $statement = $this->db->prepare("
            INSERT INTO email_verifications (
                user_id,
                email,
                otp_hash,
                expires_at
            )
            VALUES (
                :user_id,
                :email,
                :otp_hash,
                :expires_at
            )
        ");

        $statement->execute([
            'user_id' => $userId,
            'email' => $email,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findLatestByUserId(
        int $userId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT
                id,
                user_id,
                email,
                otp_hash,
                expires_at,
                verified_at,
                attempts,
                created_at
            FROM email_verifications
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $statement->execute([$userId]);

        $verification = $statement->fetch();

        return $verification ?: null;
    }


    public function getLatestCreatedAt(
    int $userId
): ?string {
    $statement = $this->db->prepare("
        SELECT created_at
        FROM email_verifications
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $statement->execute([$userId]);

    $createdAt = $statement->fetchColumn();

    return $createdAt ?: null;
}

    public function incrementAttempts(
        int $id
    ): void {
        $statement = $this->db->prepare("
            UPDATE email_verifications
            SET attempts = attempts + 1
            WHERE id = ?
        ");

        $statement->execute([$id]);
    }

    public function markVerified(
        int $id
    ): void {
        $statement = $this->db->prepare("
            UPDATE email_verifications
            SET verified_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $statement->execute([$id]);
    }

    /** Newest unverified rows (an older emailed code stays usable until it expires). */
    public function findRecentActive(int $userId, int $limit = 5): array
    {
        $statement = $this->db->prepare(
            "SELECT id, otp_hash, expires_at, attempts FROM email_verifications
             WHERE user_id = ? AND verified_at IS NULL ORDER BY id DESC LIMIT " . (int) $limit
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }
}
