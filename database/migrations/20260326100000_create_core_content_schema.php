<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCoreContentSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->createRolesTable();
        $this->createPermissionsTable();
        $this->createRolePermissionsTable();
        $this->createUsersTable();
        $this->createContentTypesTable();
        $this->createContentFieldsTable();
        $this->createContentItemsTable();
        $this->createContentFieldValuesTable();
        $this->createMediaTable();
        $this->createSlugRedirectsTable();
    }

    private function createRolesTable(): void
    {
        $this->table('roles', ['engine' => 'InnoDB'])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('slug', 'string', ['limit' => 120])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    private function createPermissionsTable(): void
    {
        $this->table('permissions', ['engine' => 'InnoDB'])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('slug', 'string', ['limit' => 160])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    private function createRolePermissionsTable(): void
    {
        $this->table('role_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id'], 'engine' => 'InnoDB'])
            ->addColumn('role_id', 'integer', ['signed' => false])
            ->addColumn('permission_id', 'integer', ['signed' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createUsersTable(): void
    {
        $this->table('users', ['engine' => 'InnoDB'])
            ->addColumn('role_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 190])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('display_name', 'string', ['limit' => 150])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['role_id'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createContentTypesTable(): void
    {
        $this->table('content_types', ['engine' => 'InnoDB'])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('slug', 'string', ['limit' => 120])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    private function createContentFieldsTable(): void
    {
        $this->table('content_fields', ['engine' => 'InnoDB'])
            ->addColumn('content_type_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('field_key', 'string', ['limit' => 120])
            ->addColumn('field_type', 'string', ['limit' => 50])
            ->addColumn('is_required', 'boolean', ['default' => false])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('settings_json', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['content_type_id'])
            ->addIndex(['content_type_id', 'field_key'], ['unique' => true])
            ->addForeignKey('content_type_id', 'content_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createContentItemsTable(): void
    {
        $this->table('content_items', ['engine' => 'InnoDB'])
            ->addColumn('content_type_id', 'integer', ['signed' => false])
            ->addColumn('author_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('slug', 'string', ['limit' => 191])
            ->addColumn('status', 'string', ['limit' => 40, 'default' => 'draft'])
            ->addColumn('body', 'text', ['null' => true])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['content_type_id'])
            ->addIndex(['status'])
            ->addIndex(['slug'], ['unique' => true])
            ->addForeignKey('content_type_id', 'content_types', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('author_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createContentFieldValuesTable(): void
    {
        $this->table('content_field_values', ['engine' => 'InnoDB'])
            ->addColumn('content_item_id', 'integer', ['signed' => false])
            ->addColumn('content_field_id', 'integer', ['signed' => false])
            ->addColumn('value_text', 'text', ['null' => true])
            ->addColumn('value_json', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['content_item_id'])
            ->addIndex(['content_field_id'])
            ->addIndex(['content_item_id', 'content_field_id'], ['unique' => true])
            ->addForeignKey('content_item_id', 'content_items', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('content_field_id', 'content_fields', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createMediaTable(): void
    {
        $this->table('media', ['engine' => 'InnoDB'])
            ->addColumn('uploaded_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('content_item_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('storage_disk', 'string', ['limit' => 60, 'default' => 'local'])
            ->addColumn('path', 'string', ['limit' => 255])
            ->addColumn('filename', 'string', ['limit' => 255])
            ->addColumn('mime_type', 'string', ['limit' => 120])
            ->addColumn('size_bytes', 'biginteger', ['signed' => false])
            ->addColumn('alt_text', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['content_item_id'])
            ->addIndex(['uploaded_by_user_id'])
            ->addIndex(['path'], ['unique' => true])
            ->addForeignKey('uploaded_by_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('content_item_id', 'content_items', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createSlugRedirectsTable(): void
    {
        $this->table('slug_redirects', ['engine' => 'InnoDB'])
            ->addColumn('content_item_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('old_slug', 'string', ['limit' => 191])
            ->addColumn('new_slug', 'string', ['limit' => 191])
            ->addColumn('http_status_code', 'integer', ['default' => 301])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['old_slug'], ['unique' => true])
            ->addIndex(['new_slug'])
            ->addForeignKey('content_item_id', 'content_items', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
