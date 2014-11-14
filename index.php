<?php

// change the following paths if necessary
$yii=dirname(__FILE__).'/framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

//防止xss攻击
require_once dirname(__FILE__).'/protected/components/Security.php';
//Security::sqlInjectionDefender();
Security::headerCLeaner();
//防止xss攻击
$_POST = Security::getXssSafeParams($_POST);
$_GET = Security::getXssSafeParams($_GET);
$_REQUEST = Security::getXssSafeParams($_REQUEST);
$_COOKIE = Security::getXssSafeParams($_COOKIE);

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();