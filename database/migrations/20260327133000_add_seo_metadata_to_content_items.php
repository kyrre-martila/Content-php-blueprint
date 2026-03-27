<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSeoMetadataToContentItems extends AbstractMigration
{
    public function change(): void
    {
        $this->table('content_items')
            ->addColumn('meta_title', 'string', ['limit' => 255, 'null' => true, 'after' => 'title'])
            ->addColumn('meta_description', 'string', ['limit' => 320, 'null' => true, 'after' => 'meta_title'])
            ->addColumn('og_image', 'string', ['limit' => 255, 'null' => true, 'after' => 'meta_description'])
            ->addColumn('canonical_url', 'string', ['limit' => 2048, 'null' => true, 'after' => 'og_image'])
            ->addColumn('noindex', 'boolean', ['default' => false, 'after' => 'canonical_url'])
            ->update();
    }
}
