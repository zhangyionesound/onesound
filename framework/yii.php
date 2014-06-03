<?php
/**
 * Yii bootstrap file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @version $Id$
 * @package system
 * @since 1.0
 */

require(dirname(__FILE__).'/YiiBase.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It encapsulates {@link YiiBase} which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of YiiBase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system
 * @since 1.0
 */
class Yii extends YiiBase
{
}

//theone.com
function YiiDomain(){
	$domain = explode('.', $_SERVER['HTTP_HOST']);
	if(isset($domain[1])&&isset($domain[2])) return $domain[1].'.'.$domain[2];
	else return false;
}

//Mongo记录系统日志  theone 表
function YiiBaseLog($message='', $cat="web"){
	//if(!is_array($message)) $message = (array) $message;
	if(is_object($message)) $message = (array) $message;
	$mongo = getMongo();
	if($mongo){
		$statistics = $mongo->statistics;
		$theoneCollection = $statistics->theone;
		$theoneCollection->insert(
				array('msg' => $message,
						'cat' => $cat,
						'datetime' => date('Y-m-d H:i:s')
				));
	}
}

/**
 * IP地址 查询
 2. 响应信息：
 （json格式的）国家 、省（自治区或直辖市）、市（县）、运营商
 3. 返回数据格式：
 {"code":0,"data":{"ip":"210.75.225.254","country":"\u4e2d\u56fd","area":"\u534e\u5317",
 "region":"\u5317\u4eac\u5e02","city":"\u5317\u4eac\u5e02","county":"","isp":"\u7535\u4fe1",
 "country_id":"86","area_id":"100000","region_id":"110000","city_id":"110000",
 "county_id":"-1","isp_id":"100017"}}
 */
function YiiBaseAreaIP($ip =NULL){
	$ip = $ip?$ip:Util::getIP();
	//$ip = '112.80.236.22'; //南京 联通
	$cacheKey = 'OpenArea'.$ip;
	$cacheAreaIp = Yii::app()->cache->get($cacheKey);
	if(!$cacheAreaIp)
	{
		//@todo 从自己的Ip库里面取出已经存在的记录, 考虑 memcache 一旦故障, 一时请求太多
		$url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
		$str = Util::curl($url);
		if(!empty($str))
		{
			$cacheAreaIp = json_decode($str,true);
			if($cacheAreaIp['code']==0) YiiBaseLogAreaIP($cacheAreaIp['data']); // 0：成功，1：失败。
			else $cacheAreaIp = false;
			Yii::app()->cache->set($cacheKey, $cacheAreaIp);
		} else Yii::app()->cache->set('OpenArea'.$ip, false);
	}
	return $cacheAreaIp;
}

function YiiBaseLogAreaIP($areaIP){
		$mongo = getMongo();
		if($mongo){
			$statistics = $mongo->statistics;
			$ipCollection = $statistics->ip;
			$result = $ipCollection->count(array("ip" =>$areaIP["ip"]));
			if($result==0) $ipCollection->insert($areaIP);
		}
}

//Mongo记录用户浏览web日志 userAgent, web 表
function YiiBaseLogWeb()
{
	/*
	const AJAXSUCCESS = 1; // AJAX请求成功
	const SUCCESS = 2; //执行成功
	const FAILED = 3; // 执行失败
	const ILLEGALITY = 4; // 非法请求
	const DBERROR = 5; // 数据库错误
	const NOTEXISTUSER = 6; // 用户不存在
	const PLEASELOGIN = 8; // 请登录
	const INACITVE = 7; // 未激活
	const NOPERMISSION = 9; // 无权限
	const TOKENERROR = 10; // 令牌错误
	const DBERRORLINK = 11; // 数据库链接错误
	*/
	//if (stristr($_SERVER['HTTP_HOST'], 'qumaiya.com')===FALSE) die();
	if(php_uname('n')=='10-4-2-65') die();
	//if(isAjaxRequest()){
			$mongo = getMongo();
			if($mongo){
				$statistics = $mongo->statistics;
				$browser = $userAgent= array();
				//用户信息
				$userAgent["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
				$userAgent["HTTP_ACCEPT_LANGUAGE"] = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
				$userAgent["REMOTE_ADDR"] = Util::getIP();
				$userAgent["SCREEN"] =  isset($_GET["screen"])?$_GET["screen"]:'';
				//$browser["DAYUNIQUE"] = md5($browser["HTTP_USER_AGENT"].$browser["HTTP_ACCEPT_LANGUAGE"].$browser["SCREEN"].$browser["REMOTE_ADDR"].date('Y-m-d'));
				$userAgent["UNIQUE"] = md5($userAgent["HTTP_USER_AGENT"].$userAgent["HTTP_ACCEPT_LANGUAGE"].$userAgent["SCREEN"].$userAgent["REMOTE_ADDR"]);
				
				$result = 0 ;
				$resultInert = FALSE;
				$cacheKey = 'userAgentUNIQUE'.$userAgent["UNIQUE"];
				$cacheUNIQUE = Yii::app()->cache->get($cacheKey);
				if(!$cacheUNIQUE)
				{
				    $userAgentCollection = $statistics->userAgent;
				    $result = $userAgentCollection->count(array("UNIQUE" =>$userAgent["UNIQUE"]));
				    if($result==0)
				    {
				        $resultInert = $userAgentCollection->insert($userAgent);
				        if($resultInert) Yii::app()->cache->set($cacheKey, $userAgent["UNIQUE"]);
				    } else {
				        $resultInert = TRUE;
				        Yii::app()->cache->set($cacheKey, $userAgent["UNIQUE"]);
				    }
				}
				
				//web浏览信息
				if($cacheUNIQUE||$resultInert){
					$browser["URL"] = isset($_GET["url"])?$_GET["url"]:'';
					Yii::log($browser["URL"], CLogger::LEVEL_ERROR,$category='browserURL');
					if(!isUTF8($browser["URL"])) $browser["URL"] = iconv("UTF-8","GB2312//IGNORE", $browser["URL"]);
					$browser["HTTP_HOST"] = $_SERVER["HTTP_HOST"];
					$browser["REQUEST_TIME"] = $_SERVER["REQUEST_TIME"];
					$browser["COOKIE"] = $_COOKIE;
					$browser["POST"] = $_POST;
					$browser["UNIQUE"] = $userAgent["UNIQUE"];
					$webCollection = $statistics->web;
					if($webCollection->insert($browser)) {
						echo 1;
						YiiBaseAreaIP($userAgent["REMOTE_ADDR"]);
					} else echo 3;
				} else echo 3;
		} else echo 5;
	//} else echo '!getIsAjaxRequest';
	die();
}

function isUTF8($str)
{
    $output = iconv('UTF-8', 'UTF-8', $str);
    return $output == $str;
}

/* function isAjaxRequest()
{
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
} */

function UtilMail($subject="No Subject", $msg='None', $to='benben.cool@qq.com'){
	//$to = "somebody@example.com, somebodyelse@example.com";
	$message = "
	<html>
	<head>
	<title>HTML email</title>
	</head>
	<body>
	<p>{$msg}  From {$_SERVER['REMOTE_ADDR']}</p>
	</body>
	</html>
	";
	// 当发送 HTML 电子邮件时，请始终设置 content-type
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=utf-8" . "\r\n";
	$headers .= 'From: <theone@qumaiya.com>' . "\r\n";

	@mail($to, $subject, $message, $headers);
}

function PTrack(){
	$args = func_get_args();
	$backtrace = debug_backtrace();
	$file = $backtrace[0]['file'];
	$line = $backtrace[0]['line'];
	echo "<pre>";
	echo "File: $file : $line<br/>";
	foreach ($args as $arg)
	{
		// print_r($arg);
		var_dump($arg);
		// $debug=var_export($arg,true);
	}
	echo "</pre>";
}

function getMongo(){
	$mongo = NULL;
	if (extension_loaded('mongo')){
		try {
			if(stripos($_SERVER['HTTP_HOST'],"qumaiya.com")!==false) $mongo = new MongoClient('mongodb://192.168.1.51:27017/');
			else $mongo = new MongoClient('mongodb://127.0.0.1:27017/');
		} catch (MongoConnectionException $e) {
			UtilMail('MongoClient Connection Failed', $e->getMessage());
		}
		return $mongo;
	}
}