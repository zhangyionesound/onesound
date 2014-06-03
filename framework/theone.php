<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<title>日志查询</title>
</head>
<form onsubmit="return checkInput()" action="" method="post">
    <div class=" state_form" >
    		<select name="type">
    			<option value="1">订单号</option>
    			<option value="2">按手机号</option>
    			<option value="3">按身份证号</option>
    			<option value="4">短信记录</option>
    			<option value="5">URL</option>
    			<option value="6">.failed</option>
    			<option value="7">.request</option>
    			<option value="8">.response</option>
    		</select>
    		内容:<input style="width:200px;" type="text" name="id" id="id" value="" >
    		时间:<input style="width:200px;" type="text" name="date" id="date" value="<?php echo date('Y-m-d');?>" >
    		<input name="" id="numberSubmit"  class="get" type="submit" value="查询" />	
    </div>
</form>
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

require(dirname(__FILE__).'/yii.php');

if($_POST){
    $mongo = getMongo();
    if($mongo){
        $statistics = $mongo->statistics;
        $cursor;
        $sDay = date('Y-m-d');  if($_POST['date'] && strtotime($_POST['date'] ))  $sDay = $_POST['date'];
        if($_POST['type']=='1') {
            $collection = $statistics->theone;
            //$cursor = $theoneCollection->find(array('msg' => '{"Status":"SUCCESS","ErrorMessage":"","OrderNumber":"1000110"}'));
/*             $query = array('$or' => array(
            		array("msg"=>new MongoRegex("/.*{$_POST['id']}.*$/i")),
            		array("msg"=>array('OrderNumber'=>$_POST['id'])
            ))); */
   		    if (is_numeric($_POST['id'])) {
   		    	 $query=array("msg"=>new MongoRegex("/.*{$_POST['id']}.*/i"));
   		    }
        } else if($_POST['type']=='5') {
            $collection = $statistics->web;
            $query=array(
                    'URL'=>new MongoRegex("/http://{$_SERVER['HTTP_HOST']}{$_POST['id']}.*/i"),
                    'REQUEST_TIME'=>array('$gt' =>strtotime($sDay), '$lt' => strtotime('+1 days', strtotime($sDay)))
            );
        }
        else if($_POST['type']=='2') {
            $collection = $statistics->theone;
            if (is_numeric($_POST['id'])) {
            	$query=array("msg"=>array('mobile'=>$_POST['id']));
            }
        }else if($_POST['type']=='4') {
            $collection = $statistics->theone;
            $query=array(
                    'cat'=> '.SendSMS',
                    'datetime'=>new MongoRegex("/$sDay.*/i"),
            );
            //if (is_numeric($_POST['id'])) {
            	$query=array(
            		'cat'=> '.SendSMS',
            		'msg'=>array('mobile'=>new MongoRegex('/.*13815441058.*/i'))
            	);
           // }
        }else if($_POST['type']=='6') {
            $collection = $statistics->theone;
            $query=array(
                    'cat'=>new MongoRegex("/.*.failed$/i"),
                    'datetime'=>new MongoRegex("/$sDay.*/i"),
             );
        }else if($_POST['type']=='7') {
            $collection = $statistics->theone;
            $query=array(
                    'cat'=>new MongoRegex("/.*.request$/i"),
                    'datetime'=>new MongoRegex("/$sDay.*/i"),
             );
        }else if($_POST['type']=='8') {
            $collection = $statistics->theone;
            $query=array(
                    'cat'=>new MongoRegex("/.*.response$/i"),
                    'datetime'=>new MongoRegex("/$sDay.*/i"),
            );
        }
        $collection = $statistics->theone;
        $query=array(
        		'cat'=> '.SendSMS',
        		//'msg'=> array('$all'=>array('mobile'=>'13815441058','result', )
        );
        PTrack($query);
        $cursor = $collection->find($query);
        $cursor->sort(array('_id'=>-1)); //(-1倒序，1正序)
        echo 'count:' .$cursor->count().'<br>'; #全部
        if($cursor->count()>50)
        {
            $cursor->sort(array('_id'=>-1))->limit(50);
        }
        foreach ($cursor as $array) {    //遍历集合中的文档
            PTrack($array)."<br/>";
        }
    }
}
?>