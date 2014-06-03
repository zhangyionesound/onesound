<?php
class Admin extends BaseModel {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return '{{admin}}';
    }

    public function rules() {
        return array(
            array('name', 'unique'),
            array('name, realname', 'required', 'message' => '{attribute}不能为空!'),
            array('password', 'length', 'allowEmpty' => false, 'min' => '6', 'max' => '12', 'tooShort' => '6-12位字母或数字组成', 'tooLong' => '6-12位字母或数字组成'),
        );
    }

    public function loadInit($params = array()) {
        $user = Admin::model()->findByPk($this->getCacheId());
        if ($user) {
            $this->_attributes = $user->attributes;
            return true;
        }
        return false;
    }

    //type: 管理员是0 ; 客服是1
    public function isService() {
        return $this->type == 1;
    }

    public function isAdmin() {
        return $this->type == 0;
    }

    //是否是客服主管
    public function isKefuManager() {
        $userId = $this->id;
        if (strstr(AdminRole::model()->find("adminID=$userId")->roleIDs, '5')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    //根据角色判断是否是客服
    public function isCustomerService() {
        $userId = $this->id;
        if (strstr(AdminRole::model()->find("adminID=$userId")->roleIDs, '16')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    //根据角色判断是否是管理员
    public function isAdministrators() {
        $userId = $this->id;
        if (strstr(AdminRole::model()->find("adminID=$userId")->roleIDs, '4')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function beforeSave() {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->password = $this->hashPassword($this->password);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the given password is correct.
     * @param string the password to be validated
     * @return boolean whether the password is valid
     */
    public function validatePassword($password) {
        return $this->hashPassword($password) === $this->password;
    }

    /**
     * Generates the password hash.
     * @param string password
     * @param string salt
     * @return string hash
     */
    public function hashPassword($password) {
        return md5($this->generateSalt() . $password);
    }

    /**
     * Generates a salt that can be used to generate a password hash.
     * @return string the salt
     */
    public function generateSalt() {
        return sha1('2012-12-18');
        //return uniqid('',true);
    }

    public function attributeLabels() {
        return array(
            'name' => '用户名',
            'realname' => '真实姓名',
            'password' => '密码',
            'mobile' => '手机号码',
            'role' => '角色',
            'jobID' => '职务',
            'depID' => '部门',
            'callingno' => '客服坐席号',
            'callingpwd' => '客服坐席号密码',
            'callingphone' => '客服绑定电话',
            'type' => '客服类型',
        );
    }

    //获取在线客服的公共方法
    public function getOndutyId() {
        $onDutyId = '0';
        //return $onDutyId; //暂时关闭
        $sql = 'SELECT id
                FROM v1_admin
                WHERE isDuty=1';
        $allOnDuty = Yii::app()->db->createCommand($sql)->queryAll();
        if (!empty($allOnDuty)) {
            $onDutyKey = array_rand($allOnDuty);
            if (!empty($allOnDuty[$onDutyKey]['id'])) {
                $onDutyId = $allOnDuty[$onDutyKey]['id'];
            }
        }
        return $onDutyId;
    }

    //当订单客服在值是分任务给客服
    public function getOndutyIdByOrderNumber($orderId) {
        $onDutyId = '0';
        //return $onDutyId; //暂时关闭
        $sql = "SELECT a.id 
                FROM v1_admin a
                LEFT JOIN v1_order_status b
                ON a.id = b.kefu
                WHERE a.isDuty=1
                AND b.OrderNumber=$orderId";
        $result = Yii::app()->db->createCommand($sql)->queryScalar();
        if (!empty($result)) {
            $onDutyId = $result;
        }
        return $onDutyId;
    }

    //退出时自动设置不在岗
    public function offDuty($duty = 0, $userId = 0) {
        if (empty($userId)) {
            $userId = Yii::app()->user->id;
        }
        $result = '';
        if (is_numeric($duty) && !empty($userId)) {
            $sql = "UPDATE v1_admin
                    SET isDuty=$duty
                    WHERE id=$userId";
            $result = Yii::app()->db->createCommand($sql)->execute();
        }
        //清除分单缓存
        Yii::app()->fileCache->set('keFuArray', '', 300);
        return $result;
    }

}