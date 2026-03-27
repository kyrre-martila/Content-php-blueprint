<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInstallCompletedSetting extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('settings')) {
            $this->table('settings', ['engine' => 'InnoDB'])
                ->addColumn('install_completed', 'boolean', ['default' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        } elseif (!$this->table('settings')->hasColumn('install_completed')) {
            $this->table('settings')
                ->addColumn('install_completed', 'boolean', ['default' => false])
                ->update();
        }

        $this->execute(
            <<<'SQL'
INSERT INTO settings (id, install_completed)
SELECT 1, 0
WHERE NOT EXISTS (SELECT 1 FROM settings)
SQL
        );
    }
}
