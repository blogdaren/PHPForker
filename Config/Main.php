<?php
/**
 * @script   Main.php
 * @brief    主配置文件
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.blogdaren.com
 * @version  1.0.0
 * @modify   2018-09-26
 */

/****************************基础设置************************************************/
date_default_timezone_set("Asia/Shanghai");


/****************************常量配置区域, 请根据情况自行配置************************/

//运行模式有 devel、deploy 和 test 三种
!defined('RUN_MODE') && define("RUN_MODE", 'devel');

//容器基准根目录
!defined('ROOT_DIR') && define("ROOT_DIR", dirname(dirname(__FILE__)));

//应用ID
!defined('APP_ID') && define("APP_ID", 'phpforker');

//文件缓存目录: 请自行配置
!defined('CACHE_DIR') && define("CACHE_DIR", "/tmp/" . APP_ID);

//日志目录: 请自行配置
!defined('LOG_DIR') && define("LOG_DIR", CACHE_DIR . "/logs");


/***************************非常量配置区域, 请根据情况自行配置**********************/

/**
 * 应用程序配置信息
 */
return array(
    'socket' => array(
        'backlog' => 5000,
    ),

    'ui' => array(
        'column' => array(
            'index'		                =>	'index', 
            'protocol'		            =>	'transport', 
            'user'		                =>	'user', 
            'container'	                =>	'name', 
            'listening'	                =>	'listening',
            'processes'                 =>	'count',
            'status'	                =>	'status',
        ),
        'length' => array(
            'maxIndexNameLength'	    =>  4, 
            'maxProtocolNameLength'	    =>	7,
            'maxUserNameLength'	        =>	12,
            'maxContainerNameLength'	=>  12, 
            'maxListeningNameLength'	=>  12, 
            'maxProcessesNameLength'	=>	9, 
            'maxStatusNameLength'	    =>	1, 
        )
    ),

    'timeout' => array(
        'process' => 5,
    ),
);



