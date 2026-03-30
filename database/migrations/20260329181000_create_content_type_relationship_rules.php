<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContentTypeRelationshipRules extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_type_relationship_rules', ['id' => false, 'primary_key' => ['from_content_type_id', 'to_content_type_id', 'relation_type'], 'engine' => 'InnoDB'])
            ->addColumn('from_content_type_id', 'integer', ['signed' => false])
            ->addColumn('to_content_type_id', 'integer', ['signed' => false])
            ->addColumn('relation_type', 'string', ['limit' => 60])
            ->addIndex(['from_content_type_id', 'to_content_type_id', 'relation_type'], ['unique' => true])
            ->addForeignKey('from_content_type_id', 'content_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('to_content_type_id', 'content_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
