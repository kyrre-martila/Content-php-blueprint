<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddViewTypeToContentTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_types')
            ->addColumn('view_type', 'string', ['limit' => 20, 'default' => 'single', 'after' => 'description'])
            ->update();
    }
}
