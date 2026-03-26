<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedInitialAdminUser extends AbstractMigration
{
    public function up(): void
    {
        $this->seedRoles();
        $this->seedInitialAdminUser();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM users WHERE email = 'admin@example.com'");
        $this->execute("DELETE FROM roles WHERE slug IN ('superadmin', 'admin', 'editor')");
    }

    private function seedRoles(): void
    {
        $this->execute(
            <<<'SQL'
INSERT INTO roles (name, slug, description)
VALUES
    ('Super Admin', 'superadmin', 'Full system control'),
    ('Admin', 'admin', 'Administrative access for site management'),
    ('Editor', 'editor', 'Content editing access')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description)
SQL
        );
    }

    private function seedInitialAdminUser(): void
    {
        $password = getenv('ADMIN_INITIAL_PASSWORD');
        $plainPassword = is_string($password) && trim($password) !== '' ? $password : 'ChangeMe123!';
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Failed to hash initial admin password.');
        }

        $this->execute(
            sprintf(
                <<<'SQL'
INSERT INTO users (role_id, email, password_hash, display_name, is_active)
SELECT r.id, 'admin@example.com', '%s', 'Initial Admin', 1
FROM roles r
WHERE r.slug = 'superadmin'
ON DUPLICATE KEY UPDATE
    role_id = VALUES(role_id),
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    is_active = VALUES(is_active)
SQL,
                addslashes($passwordHash)
            )
        );
    }
}
