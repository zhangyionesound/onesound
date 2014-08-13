<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class TestController extends Controller {

    /*
     * 测试redis的代码
     */
    public function actionTestRedis($param) {
        $key = md5($keyWords . $catKeyWords . "Yii::model::SiteClassification::getHotGuides/");
        //http://jira.tuniu.org/browse/WEBS-2467 Memcache set value 超过1M改用redis
        $guides = Yii::app()->redis->get($key);
        var_dump($guides);
        $guides = array(111);
        Yii::app()->redis->set($key, $guides, 3600);
        die();
    }

}
