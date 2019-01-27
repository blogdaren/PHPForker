<?php
/**
 * @script   Connection.php
 * @brief    This file is part of PHPForker
 * @author   blogdaren<blogdaren@163.com>
 * @version  1.0.1
 * @modify   2019-01-25
 */

namespace PHPForker\Core;
use PHPForker\Container;

class Connection 
{
    /**
     * connection id
     *
     * @var array
     */
    public $id = 0;

    /**
     * id recorder
     *
     * @var int
     */
    static protected $_idRecorder = 1;

    /**
     * keep how many client connections for one process
     *
     * @var int
     */
    static public $connectionCount = 0;

    /**
     *  all connection instances
     *
     * @var array
     */
    static public $connections = array(); 


    /**
     * construct
     *
     * @param resource $socket
     * @param string   $remote_address
     */
    public function __construct()
    {
        self::$connectionCount++;
        $this->id = self::$_idRecorder++;
        self::$_idRecorder === PHP_INT_MAX && self::$_idRecorder = 0;
        self::$connections[$this->id] = $this;
    }


    /**
     * destruct
     *
     * @return void
     */
    public function __destruct()
    {
        self::$connectionCount--;
        $pid = posix_getpid();
        Container::log("child__pid: {$pid} still have " . self::$connectionCount . " connecions alived", Container::LOG_LEVEL_WARN);

        if(Container::isGracefulStop() && self::$connectionCount <= 1) 
        {
            Container::stopAll();
        }
    }

    /**
     * @brief    getTotalConnections    
     *
     * @return   int 
     */
    static public function getTotalConnections()
    {
        return self::$connectionCount;
    }
}

