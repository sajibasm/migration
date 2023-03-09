<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=synchronization',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',

    // TableInfo cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];
