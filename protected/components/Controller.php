<?php

/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController {

    // seo des
    private $_pageTitle;
    public $keyword = '';
    public $des = '';
    
    /**
     * @var string the default layout for the controller view. Defaults to '//layouts/column1',
     * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
     */
    public $layout = '//layouts/mainLayout';

    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu = array();

    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs = array();

    function oneSoundCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/bootstrap.css");
        Yii::app()->getClientScript()->registerCssFile("/css/starter-template.css");
        Yii::app()->getClientScript()->registerCssFile("/css/onesound/onesoundindex.css");
    }

    public function init() {
        Util::checkInput();
    }

    // 客服和管理员
    public function isAdmin() {
        $uid = Yii::app()->user->id;
        return $uid > 0 && $uid < 100;
    }

    //管理员
    public function isTopAdmin() {
        return Yii::app()->user->id == 1;
        /* 		 $uid = Yii::app()->user->id;
          $type=Admin::model()->find('id='.$uid)->type;
          return $type==0; */
    }

    public function getPageTitle() {
        if ($this->_pageTitle !== null)
            return $this->_pageTitle;
        else {
            $name = ucfirst(basename($this->getId()));
            if ($this->getAction() !== null && strcasecmp($this->getAction()->getId(), $this->defaultAction))
                return $this->_pageTitle = ucfirst($this->getAction()->getId()) . ' ' . $name . ' - ' . Yii::app()->name;
            else
                return $this->_pageTitle = $name . '-' . Yii::app()->name;
        }
    }

    public function setPageTitle($value, $index = false) {
        if ($index == false)
            $this->_pageTitle = $value . '-' . Yii::app()->name;
        else
            $this->_pageTitle = Yii::app()->name . '-' . $value;
    }

    //客户端js提示
    public function clientAlert($notify, $redirect = FALSE, $parentFlag = 0) {
        @header("Content-type:text/html;charset=utf-8");
        if ($redirect == FALSE)
            echo "<script>alert('{$notify}');</script>";
        else {
            //是否父窗口跳转
            if ($parentFlag == 1) {
                echo "<script>alert('{$notify}');window.parent.location.href='{$redirect}'</script>";
                Yii::app()->end();
            } else {
                echo "<script>alert('{$notify}');window.location.href='{$redirect}'</script>";
                Yii::app()->end();
            }
        }
    }

    // 客服端父端口提示
    public function clientParentAlert($notify, $redirect = FALSE) {
        @header("Content-type:text/html;charset=utf-8");
        if ($redirect == FALSE)
            echo "<script>alert('{$notify}');</script>";
        else {
            echo "<script>alert('{$notify}');window.parent.location='{$redirect}'</script>";
            Yii::app()->end();
        }
    }

    /*
     * 检查二维数组的每个元素为空情况，有一个为空则返回false
     * @author zhangyi 2013-08-21
     */

    public function checkTwoDimArray($array) {
        if (empty($array)) {
            return false;
        }
        foreach ($array as $key => $value) {
            if ($key != 'mobile') {
                if (empty($value)) {
                    return false;
                }
                foreach ($value as $k => $v) {
                    if ($k != 'mobile') {
                        if (empty($v)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    public function validateCsrfToken() {
        $request = Yii::app()->request;
        if ($request->getIsPostRequest()) {
            // only validate POST requests
            $cookies = $request->getCookies();
            if ($cookies->contains($request->csrfTokenName) && isset($_POST[$request->csrfTokenName])) {
                $tokenFromCookie = $cookies->itemAt($request->csrfTokenName)->value;
                $tokenFromPost = $_POST[$request->csrfTokenName];
                $valid = $tokenFromCookie === $tokenFromPost;
            }
            else
                $valid = false;
            if (!$valid) {
                if (Yii::app()->request->isAjaxRequest) {
                    echo Controller::TOKENERROR;
                    Yii::app()->end();
                }
                else
                    throw new CHttpException('403', '令牌错误, 非法请求! -' . json_encode($_POST));
            }
        }
    }

    public function translate($d) {
        $ret = array();
        foreach ($d as $k => $v) {
            $ret[] = "'$k' , $v";
        }
        $ret = '[[' . join('], [', $ret) . ']]';
        return $ret;
    }

    // http 200 error no log
    public function http200error($msg, $logCat = '') {
        $this->render('/site/error', array(
            'message' => $msg,
        ));
        if ($logCat)
            Yii::log($msg, CLogger::LEVEL_ERROR, $logCat);
        Yii::app()->end();
    }

    /*
     * 获取自己网站的锁票金额
     */

    public function getRealPriceByOrderNumber($orderNumber) {
        $money = 0;
        if (empty($orderNumber)) {
            return FALSE;
        }
        //优先从ticket detail 中取 金额
        $details = TicketDetails::model()->findByPk($orderNumber);
        if (!empty($details->total_price)) {
            return floatval(strval($details->total_price));
        }
        //从log service中读取 锁票信息
        $sql = "SELECT msg FROM v1_log_service WHERE OrderNumber=:orderNumber AND action='锁票成功' ORDER BY id DESC LIMIT 1";
        $command = Yii::app()->db->createCommand($sql);
        $command->bindParam(':orderNumber', $orderNumber, PDO::PARAM_INT);
        $result = $command->queryScalar();
        $msg = is_array($result) ? $result : json_decode($result, true);

        if (empty($msg['description'])) {
            return FALSE;
        }
        //组装处理，并计算价格
        $description = $msg['description'];
        $seatArray = explode('总票款：', $description);
        if (empty($seatArray[1])) {
            $seatArray = explode('金额：', $description);
            return FALSE;
        }
        $money = str_replace(array('元', '|', ' '), '', $seatArray[1]);
        if (is_numeric($money)) {
            $money = floatval($money);
            return floatval(strval($money));
        } else {
            return FALSE;
        }
    }

    /*
     * 统计订单更换客户端的次数，目前先记在缓存里
     */

    public function checkChangeTime($orderNumber, $clientType, $showTimeFalg = 0) {
        $cacheKey = 'checkChangeTime' . $orderNumber;
        $changeTimeArray = Yii::app()->cache->get($cacheKey);
        if (empty($changeTimeArray[$clientType])) {
            $changeTimeArray[$clientType] = 0;
        }
        //加一个参数，返回更换客户端的次数
        if ($showTimeFalg) {
            return $changeTimeArray[$clientType];
        }
        $changeTimeArray[$clientType] = $changeTimeArray[$clientType] + 1;
        Yii::app()->cache->set($cacheKey, $changeTimeArray, 3600 * 24);
        return $changeTimeArray[$clientType];
    }

    /*
     * 重置支付客户端的方法
     */

    public function resetPayClient($orderNumber) {
        $statusSql = "SELECT status FROM v1_order_status WHERE OrderNumber={$orderNumber}";
        $status = Yii::app()->db->createCommand($statusSql)->queryScalar();
        //只有订单状态是72 和 85 的才被修改，防止订单状态被乱改
        if ($status == 72 || $status == 85) {
            $sql = "UPDATE v1_order_status SET clientId='',status=72 WHERE OrderNumber={$orderNumber}";
            Yii::app()->db->createCommand($sql)->execute();
            $sqlPayClient = "DELETE FROM v1_order_pay_client WHERE OrderNumber={$orderNumber}";
            Yii::app()->db->createCommand($sqlPayClient)->execute();
        }
        return TRUE;
    }

    public function useCsrfToken() {
        $request = Yii::app()->request;
        echo CHtml::hiddenField($request->csrfTokenName, $request->getCsrfToken(), array('id' => false));
    }

    function useTrainJs() {
        Yii::app()->clientScript->registerScriptFile('/js/cityTrain.js?031806', CClientScript::POS_END);
    }

    function useCommonJs() {
        Yii::app()->clientScript->registerScriptFile('/js/common.js?0118', CClientScript::POS_END);
    }

    function useZhanZhanJs() {
        Yii::app()->clientScript->registerScriptFile('/js/zhanzhan.js?0117', CClientScript::POS_END);
    }

    function useCalendarJs() {
        Yii::app()->clientScript->registerScriptFile('/js/newhomeCalendar.js?0117', CClientScript::POS_END);
    }

    function useBuyJs() {
        Yii::app()->clientScript->registerScriptFile('/js/newbuy.js?140401', CClientScript::POS_END);
    }

    function useFlightJs() {
        Yii::app()->clientScript->registerScriptFile('/js/flight.js?0117', CClientScript::POS_END);
    }

    function useNewCheciCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/newcheci.css?0117");
    }

    function useTrainCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/trainNew.css?1128");
    }

    function useOldTrainCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/train.css?1128");
    }

    function useUserCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/user.css?0117");
    }

    function useHelpCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/webhelp.css?1128");
    }

    function useGlobalCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/global.css?0117");
    }

    function useStyleCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/styles.css?140317");
    }

    function useDengLuCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/denglu.css?1128");
    }

    function useChooseTrainCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/chooseTrain.css?1128");
    }

    function usePayCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/pay.css?140317");
    }

    //add backstage css
    function useAdminHomeCss() {
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/bootstrap/css/bootstrap.min.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/bootstrap/css/bootstrap-responsive.min.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/font-awesome/css/font-awesome.min.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/css/style-metro.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/css/style.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/css/style-responsive.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/css/themes/default.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/uniform/css/uniform.default.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/gritter/css/jquery.gritter.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/bootstrap-daterangepicker/daterangepicker.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/fullcalendar/fullcalendar/fullcalendar.css");
        // Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/jqvmap/jqvmap/jqvmap.css");
        Yii::app()->getClientScript()->registerCssFile("/css/boss/plugins/jquery-easy-pie-chart/jquery.easy-pie-chart.css");
    }

    //add backstage Js
    function useAdminHomeJs() {
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-1.10.1.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-migrate-1.2.1.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-ui/jquery-ui-1.10.1.custom.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap/js/bootstrap.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-slimscroll/jquery.slimscroll.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.blockui.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.cookie.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/uniform/jquery.uniform.min.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/jquery.vmap.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.russia.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.world.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.europe.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.germany.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.usa.js', CClientScript::POS_END);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/data/jquery.vmap.sampledata.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/flot/jquery.flot.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/flot/jquery.flot.resize.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.pulsate.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap-daterangepicker/date.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap-daterangepicker/daterangepicker.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/gritter/js/jquery.gritter.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/fullcalendar/fullcalendar/fullcalendar.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-easy-pie-chart/jquery.easy-pie-chart.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.sparkline.min.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/app.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/index.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/init.js', CClientScript::POS_END);
    }

    //add backstage Js at begin
    function useAdminHomeJsBegin() {
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-1.10.1.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-migrate-1.2.1.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-ui/jquery-ui-1.10.1.custom.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap/js/bootstrap.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-slimscroll/jquery.slimscroll.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.blockui.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.cookie.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/uniform/jquery.uniform.min.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/jquery.vmap.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.russia.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.world.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.europe.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.germany.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/maps/jquery.vmap.usa.js', CClientScript::POS_BEGIN);
        // Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jqvmap/jqvmap/data/jquery.vmap.sampledata.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/flot/jquery.flot.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/flot/jquery.flot.resize.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.pulsate.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap-daterangepicker/date.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/bootstrap-daterangepicker/daterangepicker.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/gritter/js/jquery.gritter.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/fullcalendar/fullcalendar/fullcalendar.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery-easy-pie-chart/jquery.easy-pie-chart.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/plugins/jquery.sparkline.min.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/app.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/index.js', CClientScript::POS_BEGIN);
        Yii::app()->clientScript->registerScriptFile('/css/boss/scripts/init.js', CClientScript::POS_BEGIN);
    }

    function dataformat($time) {
        if ($time >= 3600 * 24) {
            $day = floor($time / (3600 * 24));
            $hour = floor(($time - ($day * 3600 * 24)) / 3600);
            $minute = floor(($time - ((3600 * $hour) + ($day * 3600 * 24))) / 60);
            return $day . '天' . $hour . '小时' . $minute . '分';
        } else if ($time >= 60) {
            $hour = floor($time / 3600);
            $minute = floor(($time - 3600 * $hour) / 60);
            return $hour . '小时' . $minute . '分';
        }
        else
            return $time . '分';
    }

}