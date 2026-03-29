<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContentTypeCategoryGroups extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_type_category_groups', [
            'id' => false,
            'primary_key' => ['content_type_id', 'category_group_id'],
            'engine' => 'InnoDB',
        ])
            ->addColumn('content_type_id', 'integer', ['signed' => false])
            ->addColumn('category_group_id', 'integer', ['signed' => false])
            ->addIndex(['category_group_id'])
            ->addIndex(['content_type_id', 'category_group_id'], ['unique' => true])
            ->addForeignKey('content_type_id', 'content_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('category_group_id', 'category_groups', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
