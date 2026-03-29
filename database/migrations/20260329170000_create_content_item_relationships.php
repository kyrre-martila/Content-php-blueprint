<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContentItemRelationships extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_item_relationships', ['engine' => 'InnoDB'])
            ->addColumn('from_content_item_id', 'integer', ['signed' => false])
            ->addColumn('to_content_item_id', 'integer', ['signed' => false])
            ->addColumn('relation_type', 'string', ['limit' => 120])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['from_content_item_id'])
            ->addIndex(['to_content_item_id'])
            ->addIndex(['from_content_item_id', 'relation_type'])
            ->addIndex(['to_content_item_id', 'relation_type'])
            ->addIndex(['from_content_item_id', 'to_content_item_id', 'relation_type'], ['unique' => true])
            ->addForeignKey('from_content_item_id', 'content_items', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('to_content_item_id', 'content_items', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
