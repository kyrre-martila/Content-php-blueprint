<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFilesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('files', ['engine' => 'InnoDB'])
            ->addColumn('original_name', 'string', ['limit' => 255])
            ->addColumn('stored_name', 'string', ['limit' => 255])
            ->addColumn('slug', 'string', ['limit' => 191])
            ->addColumn('mime_type', 'string', ['limit' => 120])
            ->addColumn('extension', 'string', ['limit' => 20])
            ->addColumn('size_bytes', 'biginteger', ['signed' => false])
            ->addColumn('visibility', 'string', ['limit' => 20, 'default' => 'private'])
            ->addColumn('storage_disk', 'string', ['limit' => 60, 'default' => 'local'])
            ->addColumn('storage_path', 'string', ['limit' => 255])
            ->addColumn('checksum_sha256', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('uploaded_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'])
            ->addIndex(['visibility'])
            ->addIndex(['storage_disk', 'storage_path'], ['unique' => true])
            ->addIndex(['uploaded_by_user_id'])
            ->addForeignKey('uploaded_by_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
