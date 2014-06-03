<?php

/**
 * 
 * @author xianxian
 *
 */
abstract class BaseModel extends CActiveRecord {

    private $_cacheId;      //cache 唯一标识
    private static $_names = array();

    abstract public function loadInit($params);

    /**
     * @throws CHttpException
     * @return BaseModel
     */
    public static function load($cacheId, $params = array(), $break = FALSE) {
        if (empty($cacheId))
            throw new CHttpException('200', '禁止非法Id!');
        $call_class = get_called_class();
        $cache_key = $call_class . '_' . $cacheId;
        $item = Yii::app()->cache->get($cache_key);
        //$item = FALSE; //测试中用, 不进行缓存
        if ($item === FALSE) {
            $item = new $call_class;
            $item->cacheId = $cacheId;
            if ($item->loadInit($params)) {
                Yii::app()->cache->set($cache_key, $item);
            } else {
                //Yii::app()->cache->set($cache_key, NULL); // 设置NULL 避免再次查询
                $item = NULL;
                if ($break)
                    throw new CHttpException('404', self::LOADERROR);
            }
        }
        return $item;
    }

    public static function delCache($cacheId) {
        $call_class = get_called_class();
        $cache_key = $call_class . '_' . $cacheId;
        Yii::app()->cache->delete($cache_key);
    }

    public static function setCache($cacheId, $value) {
        $call_class = get_called_class();
        $cache_key = $call_class . '_' . $cacheId;
        Yii::app()->cache->set($cache_key, $value);
    }

    public static function model($className = __CLASS__) {
        $c = get_called_class();
        return parent::model($c);
    }

    public function attributeNames() {
        $className = get_class($this);
        if (!isset(self::$_names[$className])) {
            $class = new ReflectionClass(get_class($this));
            $names = array();
            foreach ($class->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isPublic() && !$property->isStatic())
                    $names[] = $name;
            }
            return self::$_names[$className] = $names;
        }
        else
            return self::$_names[$className];
    }

    public function setCacheId($value) {
        $this->_cacheId = $value;
    }

    public function getCacheId() {
        return $this->_cacheId;
    }

    /**
     * 返回formValidator需求的error参数形式
     */
    public function getValidateError() {
        $res = array();
        if ($this->hasErrors()) {
            $index = 0;
            foreach ($this->getErrors() as $key => $v) {
                $res[$index]['id'] = CHtml::activeId($this, $key);
                $res[$index]['msg'] = $v[0]; //获取第一个错误
                $index++;
            }
        }
        return $res;
    }

}