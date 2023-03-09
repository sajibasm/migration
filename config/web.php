<?php


use yii\mutex\MysqlMutex;
use yii\queue\db\Queue;
use yii\queue\ExecEvent;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'queue' => [
            'class' => Queue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'ttr' => 2 * 60, // Max time for anything job handling
            'attempts' => 1,
            'channel' => 'default',
            'mutex' => MysqlMutex::class,
            'on afterError' => function (ExecEvent $event) {
                dd($event);
            },
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'meo1DJ1o0hBmfECIODRBGDvUJyDAGnD8',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info', 'warning', 'trace'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
//            'rules' => [
//            ],
        ],

    ],
    'modules' => [
        'gridview' => [
            'class' => '\kartik\grid\Module'
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'panels' => [
            'queue' => \yii\queue\debug\Panel::class,
        ],
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'job' => [
                'class' => \yii\queue\gii\Generator::class,
            ],
        ]
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

function dd(...$args)
{
    $numargs = count($args);
    echo '<pre>';
    if ($numargs > 0) {
        $isVarDump = false;
        if ($args[$numargs - 1] === true) {
            $isVarDump = true;
        }
        foreach ($args as $key => $arg) {
            if ($isVarDump) {
                var_dump($arg);
            } else {
                print_r($arg);
            }
            echo PHP_EOL;
        }
    }
    echo '</pre>';
}

return $config;
