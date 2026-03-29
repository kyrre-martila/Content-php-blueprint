<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginAttemptsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('login_attempts')
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('attempt_count', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('window_start', 'datetime', ['null' => false])
            ->addColumn('last_attempt_at', 'datetime', ['null' => false])
            ->addIndex(['ip_address'], ['unique' => true])
            ->create();
    }
}
