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

        $this->render('login');
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
