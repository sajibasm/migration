<?php


use yii\mutex\MysqlMutex;
use yii\queue\db\Queue;
use yii\queue\ExecEvent;


$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],

    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => null, // disable non-namespaced migrations if app\migrations is listed below
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
                'app\migrations', // Common migrations for the whole application
                //'module\migrations', // Migrations for the specific project's module
                //'some\extension\migrations', // Migrations for the specific extension
            ],
        ],
    ],

    'components' => [


        'queue' => [
            'class' => Queue::class,
            'db' => $db, // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'ttr' => 2 * 60, // Max time for anything job handling
            'attempts' => 1,
            'channel' => 'default', // Queue channel key
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
            'on afterError' => function (ExecEvent $event) {
               echo $event->error;
            },
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'panels' => [
            'queue' => \yii\queue\debug\Panel::class,
        ],
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
