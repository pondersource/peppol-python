<?php

  namespace OCA\PeppolNext\Migration;
  
  use OCA\PeppolNext\Db\NextUserMapper;
  use OCA\PeppolNext\Db\PeppolIdentityMapper;
  use OCA\PeppolNext\Db\MessageMapper;

  use Closure;
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

  class Version010000Date20221224000000 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable(NextUserMapper::DB_NAME)) {
            $table = $schema->createTable(NextUserMapper::DB_NAME);
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('address', 'text', [
                'notnull' => false,
                'default' => ''
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'user_id_index');
        }

        if (!$schema->hasTable(PeppolIdentityMapper::DB_NAME)) {
            $table = $schema->createTable(PeppolIdentityMapper::DB_NAME);
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('scheme', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('peppol_id', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('certificate', 'text', [
                'notnull' => false,
                'default' => ''
            ]);
            $table->addColumn('service_name', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('data', 'string', [
                'notnull' => false,
                'length' => 200
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'user_id_index');
            $table->addIndex(['peppol_id'], 'peppol_id_index');
        }

        if (!$schema->hasTable(MessageMapper::DB_NAME)) {
            $table = $schema->createTable(MessageMapper::DB_NAME);
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('contact_id', 'string', [
                'notnull' => false,
                'length' => 200
            ]);
            $table->addColumn('contact_name', 'text', [
                'notnull' => true,
                'default' => ''
            ]);
            $table->addColumn('title', 'text', [
                'notnull' => false,
                'default' => ''
            ]);
            $table->addColumn('message_type', 'integer', [
                'notnull' => true
            ]);
            $table->addColumn('category', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('createdAt', 'datetime', [
                'notnull' => true,
                'default' => 'CURRENT_TIMESTAMP'
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'user_id_index');
            $table->addIndex(['contact_id'], 'contact_id_index');
            $table->addIndex(['message_type'], 'message_type_index');
            $table->addIndex(['category'], 'category_index');
        }
        
        return $schema;
    }

}