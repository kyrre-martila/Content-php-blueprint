<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInstalledVersionSetting extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('settings')) {
            $this->table('settings', ['engine' => 'InnoDB'])
                ->addColumn('install_completed', 'boolean', ['default' => false])
                ->addColumn('installed_version', 'string', ['limit' => 64, 'null' => true])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        } elseif (!$this->table('settings')->hasColumn('installed_version')) {
            $this->table('settings')
                ->addColumn('installed_version', 'string', ['limit' => 64, 'null' => true, 'after' => 'install_completed'])
                ->update();
        }

        $this->execute(
            <<<'SQL'
INSERT INTO settings (id, install_completed, installed_version)
SELECT 1, 0, NULL
WHERE NOT EXISTS (SELECT 1 FROM settings)
SQL
        );
    }
}
