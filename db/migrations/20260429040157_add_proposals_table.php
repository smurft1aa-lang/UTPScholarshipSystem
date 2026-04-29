<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProposalsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $proposals = $this->table('proposals');
        $proposals->addColumn('scholarship_id', 'integer')
                  ->addColumn('title', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('content', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
                  ->addColumn('generated_by', 'integer')
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addForeignKey('scholarship_id', 'scholarships', 'id', ['delete' => 'CASCADE'])
                  ->addForeignKey('generated_by', 'users', 'id', ['delete' => 'CASCADE'])
                  ->create();
    }
}
