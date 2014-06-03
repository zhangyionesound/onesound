<?php

/**
 * DbConnectionMan(Database Connection Manager) class is a manager of database connections.
 * for the purpose of database read/write splitting.
 * It override the createCommand method,
 * detect the sql statement to decide which connection will be used.
 * Default it use the master connection.
 * */
class DbConnectionMan extends CDbConnection {

    /**
     * @var array $slaves.Slave database connection(Read) config array.
     * The array value's format is the same as CDbConnection.
     * @example 
     * 'components'=>array(
     * 		'db'=>array(
     * 			'connectionString'=>'mysql://<master>',
     * 			'slaves'=>array(
     * 				array('connectionString'=>'mysql://<slave01>'),
     * 				array('connectionString'=>'mysql://<slave02>'),
     * 			)
     * 		)
     * )
     * */
    public $slaves = array();

    /**
     * Whether enable the slave database connection.
     * Defaut is true.Set this property to false for the purpose of only use the master database.
     * @var bool $enableSlave 
     * */
    public $enableSlave = false;


    /**
     * @override
     * @var bool $autoConnect Whether connect while init
     * */
    //public $autoConnect=false;

    /**
     * @var CDbConnection
     */
    private $_slave;

    /**
     * Creates a CDbCommand object for excuting sql statement.
     * It will detect the sql statement's behavior.
     * While the sql is a simple read operation.
     * It will use a slave database connection to contruct a CDbCommand object.
     * Default it use current connection(master database).
     * 
     * @override 
     * @param string $sql
     * @return CDbCommand
     * */
    public function createCommand($sql = null) {
        if ($this->enableSlave && !$this->getCurrentTransaction() && self::isReadOperation($sql)) {
            //Yii::log('slave-'.$sql,CLogger::LEVEL_ERROR);
            return $this->getSlave()->createCommand($sql);
        } else {
            //Yii::log('master-'.$sql,CLogger::LEVEL_ERROR);
            return parent::createCommand($sql);
        }
    }

    /*
     * 指定使用master
     */

    public function master($sql) {
        //Yii::log('master-Force-'.$sql,CLogger::LEVEL_ERROR);
        return parent::createCommand($sql);
    }

    /*
     * 指定使用slave
     */

    public function slave($sql) {
        //Yii::log('slave-Force-'.$sql,CLogger::LEVEL_ERROR);
        return $this->getSlave()->createCommand($sql);
    }

    /**
     * Construct a slave connection CDbConnection for read operation.
     * @return CDbConnection
     * */
    public function getSlave() {
        if (!isset($this->_slave)) {
            foreach ($this->slaves as $slaveConfig) {
                if (!isset($slaveConfig['class']))
                    $slaveConfig['class'] = 'CDbConnection';
                try {
                    if ($slave = Yii::createComponent($slaveConfig)) {
                        Yii::app()->setComponent('dbslave', $slave);
                        $this->_slave = $slave;
                        break;
                    }
                } catch (Exception $e) {
                    Yii::log('Create slave database connection failed!', CLogger::LEVEL_ERROR);
                    Util::mail('slave database down', $e->getMessage());
                    continue;
                }
            }
            if (!$this->_slave) {
                $this->_slave = clone $this;
                $this->_slave->enableSlave = false;
            }
        }
        return $this->_slave;
    }

    /**
     * Detect whether the sql statement is just a simple read operation.
     * Read Operation means this sql will not change any thing ang aspect of the database.
     * Such as SELECT,DECRIBE,SHOW etc.
     * On the other hand:UPDATE,INSERT,DELETE is write operation.
     * */
    public function isReadOperation($sql) {
        return preg_match('/^\s*(SELECT|SHOW|DESCRIBE|PRAGMA)/i', $sql);
    }

}
