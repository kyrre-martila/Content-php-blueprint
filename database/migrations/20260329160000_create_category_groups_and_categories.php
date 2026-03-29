<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCategoryGroupsAndCategories extends AbstractMigration
{
    public function change(): void
    {
        $this->createCategoryGroupsTable();
        $this->createCategoriesTable();
        $this->createContentItemCategoriesTable();
    }

    private function createCategoryGroupsTable(): void
    {
        $this->table('category_groups', ['engine' => 'InnoDB'])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('slug', 'string', ['limit' => 150])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    private function createCategoriesTable(): void
    {
        $this->table('categories', ['engine' => 'InnoDB'])
            ->addColumn('group_id', 'integer', ['signed' => false])
            ->addColumn('parent_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('slug', 'string', ['limit' => 150])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['group_id'])
            ->addIndex(['parent_id'])
            ->addIndex(['group_id', 'slug'], ['unique' => true])
            ->addForeignKey('group_id', 'category_groups', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('parent_id', 'categories', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createContentItemCategoriesTable(): void
    {
        $this->table('content_item_categories', [
            'id' => false,
            'primary_key' => ['content_item_id', 'category_id'],
            'engine' => 'InnoDB',
        ])
            ->addColumn('content_item_id', 'integer', ['signed' => false])
            ->addColumn('category_id', 'integer', ['signed' => false])
            ->addIndex(['category_id'])
            ->addForeignKey('content_item_id', 'content_items', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
