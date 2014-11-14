<?php


class Security {
	
	
	public static function getXssSafeParams($params){
		if (is_array($params)){
			foreach ($params as &$param){
				$param = self::getXssSafeParam($param);
			}
		}else {
			$params = self::getXssSafeParam($params);
		}
		 
		return $params;
	}
	
	public static function getXssSafeParam($param){
		$param = self::RemoveXSS($param);
		return $param;
	}
	
	 
	/**
	 * http://typo3.org/fileadmin/typo3api-4.2.6/d3/dbb/RemoveXSS_8php-source.html
	 * @param $val
	 */
	private static function RemoveXSS($val)  {
     // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
     // this prevents some character re-spacing such as <java\0script>
     // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
     $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
 
     // straight replacements, the user should never need these since they're normal characters
     // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
     $search = 'abcdefghijklmnopqrstuvwxyz';
     $search.= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
     $search.= '1234567890!@#$%^&*()';
     $search.= '~`";:?+/={}[]-_|\'\\';
 
     for ($i = 0; $i < strlen($search); $i++) {
       // ;? matches the ;, which is optional
       // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
 
       // &#x0040 @ search for the hex values
       $val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
       // &# @ 0{0,7} matches '0' zero to seven times
       $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
     }
 
     // now the only remaining whitespace attacks are \t, \n, and \r
     $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base', 'svg');
     $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
     $ra = array_merge($ra1, $ra2);
 
     $found = true; // keep replacing as long as the previous round replaced something
     while ($found == true) {
       $val_before = $val;
       for ($i = 0; $i < sizeof($ra); $i++) {
         $pattern = '/';
         for ($j = 0; $j < strlen($ra[$i]); $j++) {
           if ($j > 0) {
             $pattern .= '(';
             $pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
             $pattern .= '|(&#0{0,8}([9][10][13]);?)?';
             $pattern .= ')?';
           }
           $pattern .= $ra[$i][$j];
         }
         $pattern .= '/i';
         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
         if ($val_before == $val) {
           // no replacements were made, so exit the loop
           $found = false;
         }
       }
     }
 
     return $val;
   }

   public static function sqlInjectionDefender(&$get = array(), &$post = array(), &$cookie = array()) {
       $getfilter="'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;
       $postfilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;
       $cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;

       if(!$get){
           $get = &$_GET;
       }
       if(!$post){
           $post = &$_POST;
       }
       if(!$cookie){
           $cookie = &$_COOKIE;
       }
       foreach($get as $key=>$value){
           self::stopSqlInjection($key,$value,$getfilter);
       }
       foreach($post as $key=>$value){
           self::stopSqlInjection($key,$value,$postfilter);
       }
       foreach($cookie as $key=>$value){
           self::stopSqlInjection($key,$value,$cookiefilter);
       }
   }
   
   private static function stopSqlInjection($StrFiltKey,$StrFiltValue,$ArrFiltReq) {
       if(is_array($StrFiltValue)) {
           $StrFiltValue=implode($StrFiltValue);
       }
       if (preg_match("/".$ArrFiltReq."/is",$StrFiltValue)==1){
           self::injectionLog("操作IP: ".$_SERVER["REMOTE_ADDR"]."\n操作时间: ".date('H:i:s')."\n操作页面:".$_SERVER["REQUEST_URI"]."\n提交方式: ".$_SERVER["REQUEST_METHOD"]."\n提交参数: ".$StrFiltKey."\n提交数据: ".$StrFiltValue."\n数据包:".print_r($_POST, true));
           print "Illegal operation!" ;
           exit();
       }
   }
   
   private static function injectionLog($logs) {
       
       $fileName = '/opt/tuniu/mnt/logs/shinan/injection_log_'.date('Y-m-d');
       //$fileName = './injection_log_'.date('Y-m-d');
       file_put_contents($fileName, $logs."\n\n", FILE_APPEND);
   }
   
   public static function headerCLeaner(){
       
       self::cleanXss($_SERVER['SCRIPT_URL']);
       self::cleanXss($_SERVER['SCRIPT_URI']);
       self::cleanXss($_SERVER['HTTP_HOST']);
	   if(
          !(isset($_REQUEST['m']) && $_REQUEST['m']=='content' &&
          $_REQUEST['c']=='content' &&
          $_REQUEST['a']=='init' &&
          $_REQUEST['menuid']>0 &&
          $_REQUEST['catid']>0)

       ){
		self::cleanXss($_SERVER['QUERY_STRING']);
		self::cleanXss($_SERVER['REQUEST_URI']);
	   }
	   
       self::cleanXss($_SERVER['SCRIPT_NAME']);
       self::cleanXss($_SERVER['PHP_SELF']);
       //self::cleanXss($_SERVER['HTTP_REFERER']);
       if(!isset($_SERVER['HTTP_REFERER'])){
           $_SERVER['HTTP_REFERER'] = '';
       }
	   $_SERVER['HTTP_REFERER'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
       $_SERVER['HTTP_REFERER'] = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL) ? $_SERVER['HTTP_REFERER'] : '';
   }
   
   private static function cleanXss(&$param){
       
        $param = htmlspecialchars($param);
        $param = urldecode($param);
   }

    /**
     * 防止任意文件读取漏洞，暂时只是过滤了../../这种情况
     */
    public static function fileDefender(&$get, &$post, &$cookie){
        $filter="../../";
        foreach($get as $key=>$value){
            self::stopFileRead($key,$value,$filter);
        }
        foreach($post as $key=>$value){
            self::stopFileRead($key,$value,$filter);
        }
        foreach($cookie as $key=>$value){
            self::stopFileRead($key,$value,$filter);
        }

    }

    private static function stopFileRead($key,$value,$filter){
        if(is_array($value)) {
            $value=implode($value);
        }
        $valueDecode = urldecode($value);
        if (false !== strpos($value, $filter) || false !== strpos($valueDecode, $filter)){
            self::fileReadLog("操作IP: ".$_SERVER["REMOTE_ADDR"]."\n操作时间: ".date('H:i:s')."\n操作页面:".$_SERVER["REQUEST_URI"]."\n提交方式: ".$_SERVER["REQUEST_METHOD"]."\n提交参数: ".$key."\n提交数据: ".$value."\n数据包:".print_r($_POST, true));
            print "Illegal operation!" ;
            exit();
        }
    }

    private static function fileReadLog($logs) {
//        $fileName = 'E:/www/logs/file_read'.date('Y-m-d');
        $fileName = '/opt/tuniu/logs/php/file_read'.date('Y-m-d');
        file_put_contents($fileName, $logs."\n\n", FILE_APPEND);
    }

    /**
     * 参数过滤
     * @author huangweirong
     * @date ${DATE}
     * @param array $get
     * @param array $post
     * @param array $cookie
     */
    public static function paramsFilter(&$get = array(), &$post = array(), &$cookie = array()){
        if(!$get){
            $get = &$_GET;
        }
        if(!$post){
            $post = &$_POST;
        }
        if(!$cookie){
            $cookie = &$_COOKIE;
        }
        self::sqlInjectionDefender($get, $post, $cookie);
        self::fileDefender($get, $post, $cookie);
    }
}
?>