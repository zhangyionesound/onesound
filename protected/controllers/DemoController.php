<?php

class DemoController extends Controller {

    public $layout = '/';

    public function actionIndex() {
        
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /*
     * 登录
     */

    public function actionLogin() {
//        $a = array(22);
//        $this->test($a);
        $this->render('login');
    }

    /*
     * PHP 是弱类型的，要养成检查类型的好习惯
     * PHP5的方法类型提示
     * 强制规定参数不能是基本数据类型，可以是对象或者数组。
     * 并且可以规定参数的默认值
     */

    public function test(array $a=array()) {
        var_dump($a);
    }

    /*
     * 登陆验证
     */

    public function actionLogincheck() {
        //表单里面的input必须加上 name才能 post过来！
        //var_dump($_REQUEST,$_POST);
    }

    /*
     * 上传文件
     */

    public function actionUploadfile() {
        //文件里面有中文会，文件格式会自动变成utf8？不然是ANSI?
        $this->render('uploadfile');
    }

    public function actionUploader() {
        var_dump($_POST);
    }

    /*
     * PHP excel
     */

    public function actionExcel() {
        
    }

    /*
     * 动态翻页
     */

    public function actionAjaxPager() {
        
    }

}
