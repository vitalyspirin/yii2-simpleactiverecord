<?php
ini_set('display_errors', 1);
error_reporting(-1);

defined('YII_DEBUG') or define('YII_DEBUG', true);

require(__DIR__ . '/../../basic/vendor/yiisoft/yii2/Yii.php');

$yiiConfig = require(__DIR__ . '/../../basic/config/web.php');
new yii\web\Application($yiiConfig); // Do NOT call run() here


ini_set('display_errors', 1);
error_reporting(-1);
