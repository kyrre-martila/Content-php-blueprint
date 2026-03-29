<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHierarchyToContentItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('content_items');

        $table
            ->addColumn('parent_id', 'biginteger', ['signed' => false, 'null' => true, 'after' => 'content_type_id'])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'after' => 'parent_id'])
            ->addIndex(['parent_id'])
            ->addIndex(['parent_id', 'sort_order'])
            ->addForeignKey('parent_id', 'content_items', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->update();
    }
}
