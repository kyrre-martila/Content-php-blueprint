<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContentTypeFields extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_type_fields', ['engine' => 'InnoDB'])
            ->addColumn('content_type_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('label', 'string', ['limit' => 255])
            ->addColumn('field_type', 'string', ['limit' => 40])
            ->addColumn('is_required', 'boolean', ['default' => false])
            ->addColumn('default_value', 'text', ['null' => true])
            ->addColumn('settings_json', 'text', ['null' => true])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['content_type_id', 'name'], ['unique' => true])
            ->addIndex(['content_type_id', 'sort_order'])
            ->addForeignKey('content_type_id', 'content_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
