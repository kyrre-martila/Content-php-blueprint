<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ReduceRelationshipRelationTypeLength extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_item_relationships')
            ->changeColumn('relation_type', 'string', ['limit' => 60])
            ->update();

        $this->table('content_type_relationship_rules')
            ->changeColumn('relation_type', 'string', ['limit' => 60])
            ->update();
    }
}
