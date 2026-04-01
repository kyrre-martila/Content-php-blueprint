<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFieldValuesJsonToContentItems extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_items')
            ->addColumn('field_values_json', 'text', ['null' => true, 'after' => 'pattern_blocks'])
            ->update();
    }
}
