<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class AdminLoginForm extends CFormModel {

    public $name;
    public $password;
    public $rememberMe;
    private $_identity;

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules() {
        return array(
            array('password', 'authenticate'),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'name' => '用户名',
            'password' => '密码',
            'rememberMe' => '记住我',
        );
    }

    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     */
    public function authenticate($attribute, $params) {
        if (!$this->hasErrors()) {
            $this->_identity = new AdminIdentity($this->name, $this->password);
            if (!$this->_identity->authenticate())
                $this->addError('password', '错误的用户名或密码!');
        }
    }

    /**
     * Logs in the user using the given username and password in the model.
     * @return boolean whether login is successful
     */
    public function login() {
        if ($this->_identity === null) {
            $this->_identity = new AdminIdentity($this->name, $this->password);
            $this->_identity->authenticate();
        }
        if ($this->_identity->errorCode === AdminIdentity::ERROR_NONE) {
            //$duration= 3600*24*10; // 30 days
            Yii::app()->user->login($this->_identity, 0);
            return true;
        }
        else
            return false;
    }

}
