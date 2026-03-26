<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPatternBlocksToContentItems extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_items')
            ->addColumn('pattern_blocks', 'text', ['null' => true, 'after' => 'body'])
            ->update();
    }
}
