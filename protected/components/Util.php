<?php
class Util 
{
	/**
	 * 
	 * tieyou return gb2312  
	 * @return string
	 */
	public static function g2uDecode ($str){
		$str = urldecode($str);
		return iconv('GB2312', 'UTF-8', $str); //将字符串的编码从GB2312转到UTF-8
	}
	
	public static function array2xml($array, $encoding = 'utf-8') {
		static $xml_header;
		$xml = '';
		if ($xml_header != 1) {
			$xml ='<?xml version="1.0" encoding="'.$encoding.'"?>';
			$xml_header = 1;
		}
		foreach($array as $key=>$val) {
			if(is_numeric($key)) continue;
			//is_numeric($key) && $key="item id=\"$key\"";
			$xml .= "<$key>";
			if( $encoding!='utf-8' && is_string($val)){
				//$val = iconv('UTF-8', $encoding.'//IGNORE', $val);
				$val = mb_convert_encoding($val,  $encoding, 'UTF-8');
			}
			$xml .= is_array($val) ? self::array2xml($val, $encoding) :$val;
			$xml .= "</$key>";
		}
		return $xml;
	}
	
	//防止sql注入
	public static function checkValues($v){
		$check = false;
		if(is_array($v))
			foreach($v as $key => $val){
			$check=self::checkValues($val);
			if($check==true)break;
		}
		else{
			//http://www.theone.com/pay/yeepay/pd_FrpId/CMBCHINA-NET-B2C/order/1000104
			$pattern='/<[^>]*?=[^>]*?&#[^>]*?>|[\s\n\r\t]+and|[\s\n\r\t]+update|[\s\n\r\t]+select|[\s\n\r\t]+insert|[\s\n\r\t]+delete|[\s\n\r\t]+or|[\s\n\r\t]+join|[\s\n\r\t]+union|[\s\n\r\t]+into|<\\s*script\\b|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>/i';
			if(preg_match($pattern,$v)) 
			{
				$check = true;
			}
			
			//$v=htmlspecialchars($v);
		}
		return $check;
	}
	
	//check Input : 防止sql注入检测 $_POST, $_GET
	public static function checkInput(){
		$sqlError=false;
		$msg = '';
		foreach ($_POST as $key => $value){
			if(self::checkValues($key)||self::checkValues($value)){
				$sqlError=true;
				$msg=$key.':'.$value;
				break;
			}
		}
		
		foreach ($_GET as $key => $value){
			if(self::checkValues($key)||self::checkValues($value)){
				$sqlError=true;
				$msg=$key.':'.$value;
				break;
			}
		}
		
		if ($sqlError) {
		    @Util::mail('非法字符',$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].json_encode($_POST));
			if (Yii::app()->request->isAjaxRequest) {
				echo Controller::ILLEGALITY;
				Yii::app()->end();
			} else throw new CHttpException('403', '存在非法字符! '. htmlentities($msg));
		}
	}
	
	//邮件发送
	public static function mail($subject="No Subject", $msg='None', $to='914820102@qq.com', $ip = NULL, $date = NULL, $break = false){
		$ip = $ip ? $ip : Util::getIP();
		//检测是否仿造ip
		if($ip!=$_SERVER["REMOTE_ADDR"]){
			if (isset($_SERVER["HTTP_CDN_SRC_IP"]))
				$ip.=' CDN: '. $_SERVER["HTTP_CDN_SRC_IP"];
			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
				$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				/* 取X-Forwarded-For中第x个非unknown的有效IP字符? */
				foreach ($arr as $ipInner)
				{
					$ipInner = trim($ipInner);
					if ($ipInner != 'unknown')
					{
						$ip.=' Forwarded: '. $ipInner;
						break;
					}
				}
			}
			if (isset($_SERVER["HTTP_CLIENT_IP"]))
				$ip.=' ClientIP: '. $_SERVER["HTTP_CLIENT_IP"];
		   $ip.=' Native: '. $_SERVER["REMOTE_ADDR"];
		}
		
		$date = $date ? $date : date('Y-m-d H:i:s');
		if(!$break) {
			if($_SERVER['HTTP_HOST']=='www.qumaiya.com'){
				$day = intval(date('Ymd'));
				$key = sprintf("%u", crc32($subject.$msg.$to.$ip));
				$value = Yii::app()->countCache->get($key);
				if(!empty($value)){
					$value = Yii::app()->countCache->incr($key);
					if($value>30){
						Util::mail('Mail Too Much '.$subject, $msg, $to, $ip, $date, true);
						Yii::log($subject.':'.$msg.':'.$to, CLogger::LEVEL_WARNING, 'Mail Too Much');
						return -254;
					}
				}else{
					$value = Yii::app()->countCache->set($key, 1, 2*24*3600);
				}
			}
		}
		//$to = "somebody@example.com, somebodyelse@example.com";
		$message = "
		<html>
		<head>
		<title>{$subject}</title>
		</head>
		<body>
		<p>{$msg}  From {$ip} @ {$date}</p>
		</body>
		</html>
		";
		// 当发送 HTML 电子邮件时，请始终设置 content-type
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=utf-8" . "\r\n";
		$headers .= 'From: <theone@qumaiya.com>' . "\r\n";
		
		return @mail($to, $subject, $message, $headers);
	}
	
	//获取客户端IP地址, 获取失败 返回Unknown
	public static  function getIP() {
		static $realip = NULL;
		if ($realip !== NULL) return $realip;
		
		if (isset($_SERVER["HTTP_CDN_SRC_IP"]))
			$realip = $_SERVER["HTTP_CDN_SRC_IP"];
		else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			/* 取X-Forwarded-For中第x个非unknown的有效IP字符? */
			foreach ($arr as $ip)
			{
				$ip = trim($ip);
				if ($ip != 'unknown')
				{
					$realip = $ip;
					break;
				}
			}
		}
		else if (isset($_SERVER["HTTP_CLIENT_IP"]))
			$realip = $_SERVER["HTTP_CLIENT_IP"];
		else if (isset($_SERVER["REMOTE_ADDR"]))
			$realip = $_SERVER["REMOTE_ADDR"];
		else if (getenv("HTTP_X_FORWARDED_FOR"))
			$realip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("HTTP_CLIENT_IP"))
			$realip = getenv("HTTP_CLIENT_IP");
		else if (getenv("REMOTE_ADDR"))
			$realip = getenv("REMOTE_ADDR");
		else
			$realip = "unknown";
		preg_match("/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/", $realip, $onlineip);
		$realip = ! empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
		return $realip;
	}
	
	//抓取url
	public static function curl($url)
	{
	    $ch = curl_init();
	    curl_setopt($ch,CURLOPT_URL,$url);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    $userAgent = array(
	    		'Mozilla/5.0 (Windows NT 6.1; rv:22.0) Gecko/20100101 Firefox/22.0', // FF 22
	    		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36', // Chrome 27
	    		'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)', // IE 9
	    		'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)', // IE 8
	    		'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)', // IE 7
	    		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.1 (KHTML, like Gecko) Maxthon/4.1.0.4000 Chrome/26.0.1410.43 Safari/537.1', // Maxthon 4
	    		'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)', // 2345 2
	    		'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; QQBrowser/7.3.11251.400)', // QQ 7
	    		'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0)', // Sougo 4
	    		'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0) LBBROWSER', //  liebao 4
	    );
	    $userAgentI = rand(0, count($userAgent)-1);
	    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent[$userAgentI]);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $data = @curl_exec($ch);
	    curl_close($ch);
	    return $data;
	}
	
	/*
	 * 订单日志记录
	 * $action 操作
	 * $msg = array(
	 * 						'description'=> //描述
	 * 						'status'=>//状态						
	 * 				)
	 */
	public static function orderLog($uid, $orderNumber, $action, $msg='', $clientId='') 
	{
		if ($uid && $orderNumber && $action) {
			$log = new LogService;
			$log->action = $action;
			if(!is_string($msg)) $msg = json_encode($msg);
			$log->msg = $msg;
			$log->uid = $uid;
			$log->OrderNumber=$orderNumber;
			$log->ctime = date('Y-m-d H:i:s');
            $log->clientId = $clientId;
			return $log->save();
		}
	}
	
	//@todo remove with tieyou out
	public static function logService($uid, $orderNumber, $action, $msg=''){
		Util::orderLog($uid, $orderNumber, $action, $msg);
	}
	
	public static function utf8_substr($str,$start) {
		$null = "";
		preg_match_all("/./u", $str, $ar);
		if(func_num_args() >= 3) {
			$end = func_get_arg(2);
			return join($null, array_slice($ar[0],$start,$end));
		} else {
			return join($null, array_slice($ar[0],$start));
		}
	}
    
    //二维数组中某个项排序                                
    public static function multi_array_sort($multi_array, $sort_key, $sort=SORT_ASC){
        if(is_array($multi_array)){
            foreach ($multi_array as $row_array){
                if(is_array($row_array)){
                    $key_array[] = $row_array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }

    /**
     * 判断客户机是否是手机浏览器
     *
     * @return flag : 是手机端返回true
     * @author 
     **/
    public static function is_mobile()
    {
    	// return false;	// 临时关闭
		$is_mobile = false;	// 默认值
    	if (!isset($_SERVER['HTTP_USER_AGENT'])) return $is_mobile;
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ;
		$mobile_agents = Array("240x320","acer","acoon","acs-","abacho","ahong","airness","alcatel","amoi","android","anywhereyougo.com","applewebkit/525","applewebkit/532","asus","audio","au-mic","avantogo","becker","benq","bilbo","bird","blackberry","blazer","bleu","cdm-","compal","coolpad","danger","dbtel","dopod","elaine","eric","etouch","fly ","fly_","fly-","go.web","goodaccess","gradiente","grundig","haier","hedy","hitachi","htc","huawei","hutchison","inno","ipad","ipaq","ipod","jbrowser","kddi","kgt","kwc","lenovo","lg ","lg2","lg3","lg4","lg5","lg7","lg8","lg9","lg-","lge-","lge9","longcos","maemo","mercator","meridian","micromax","midp","mini","mitsu","mmm","mmp","mobi","mot-","moto","nec-","netfront","newgen","nexian","nf-browser","nintendo","nitro","nokia","nook","novarra","obigo","palm","panasonic","pantech","philips","phone","pg-","playstation","pocket","pt-","qc-","qtek","rover","sagem","sama","samu","sanyo","samsung","sch-","scooter","sec-","sendo","sgh-","sharp","siemens","sie-","softbank","sony","spice","sprint","spv","symbian","tablet","talkabout","tcl-","teleca","telit","tianyu","tim-","toshiba","tsm","up.browser","utec","utstar","verykool","virgin","vk-","voda","voxtel","vx","wap","wellco","wig browser","wii","windows ce","wireless","xda","xde","zte");
		foreach ($mobile_agents as $device) {
			if (stristr($user_agent, $device)) {
			    if( $device != 'tablet' || ( $device == 'tablet' && !stristr($user_agent, 'Tablet PC') ) ){
			        $is_mobile = true;
			        break;
			    }
			}
		}
		return $is_mobile;
    }
    
    /**
     * 验证身份证
     * @return boolean
     */
    public static function checkIdCard($cardNo){
        $idcard = trim($cardNo);
        $City = array('11'=>"北京",12=>"天津",13=>"河北",14=>"山西",15=>"内蒙古",21=>"辽 宁",22=>"吉林",23=>"黑龙江",31=>"上海",32=>"江苏",33=>"浙江",34=>" 安徽",35=>"福建",36=>"江西",37=>"山东",41=>"河南",42=>"湖北",43=>" 湖南",44=>"广东",45=>"广西",46=>"海南",50=>"重庆",51=>"四川",52=>" 贵州",53=>"云南",54=>"西藏",61=>"陕西",62=>"甘肃",63=>"青海",64=>" 宁夏",65=>"新疆",71=>"台湾",81=>"香港",82=>"澳门",91=>"国外");
        $iSum = 0;
        $idCardLength = strlen($idcard);
        //长度验证
        if(!preg_match('/^\d{17}(\d|x)$/i',$idcard) && !preg_match('/^\d{15}$/i',$idcard)){
            return false;
        }
        //地区验证
        if(!array_key_exists(intval(substr($idcard,0,2)),$City)){
            return false;
        }
        // 15位身份证验证生日，转换为18位
        if ($idCardLength == 15)
        {
            $sBirthday = '19'.substr($idcard,6,2).'-'.substr($idcard,8,2).'-'.substr($idcard,10,2);
            $d = new DateTime($sBirthday);
            $dd = $d->format('Y-m-d');
            if($sBirthday != $dd){
                return false;
            }
            $idcard = substr($idcard,0,6)."19".substr($idcard,6,9);//15to18
            $Bit18 = self::getVerifyBit($idcard);//算出第18位校验码
            $idcard = $idcard.$Bit18;
        }
        // 判断是否大于2078年，小于1900年
        $year = substr($idcard,6,4);
        if ($year<1900 || $year>2078 ){
            return false;
        }
    
        //18位身份证处理
        $sBirthday = substr($idcard,6,4).'-'.substr($idcard,10,2).'-'.substr($idcard,12,2);
        $d = new DateTime($sBirthday);
        $dd = $d->format('Y-m-d');
        if($sBirthday != $dd){
            return false;
        }
        //身份证编码规范验证
        $idcard_base = substr($idcard,0,17);
        if(strtoupper(substr($idcard,17,1)) != self::getVerifyBit($idcard_base)){
            return false;
        }
        return true;
    }
    
    // 计算身份证校验码，根据国家标准GB 11643-1999
    private static function getVerifyBit($idcard_base){
        if(strlen($idcard_base) != 17){
            $this->addError('identify','证件号不正确！');
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4','3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++)
        {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }
}