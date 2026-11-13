<?php

use Rk\Threadwell\Core\Migration;

/**
 * MySQL/MariaDB silently turned the first "TIMESTAMP NOT NULL" column into
 * "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP". Every update to an OTP row
 * (for example counting a wrong attempt) therefore reset expires_at to "now", and the
 * next - correct - code was reported as expired. Convert the columns to plain DATETIME.
 */
return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
                return;
            }

            $this->db->exec('ALTER TABLE email_verifications MODIFY expires_at DATETIME NOT NULL');
            $this->db->exec('ALTER TABLE password_resets MODIFY expires_at DATETIME NOT NULL');
            // clear codes that were already broken by the bug
            $this->db->exec('DELETE FROM email_verifications WHERE verified_at IS NULL');
            $this->db->exec('DELETE FROM password_resets WHERE verified_at IS NULL');
        }

        public function down(): void
        {
        }
    };
};
