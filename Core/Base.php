<?php
/**
 * @script   Base.php
 * @brief    This file is part of PHPForker
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.blogdaren.com
 * @version  1.0.0
 * @modify   2018-10-11
 */

namespace PHPForker\Core;
use PHPForker\Library\Helper\Tool;
use PHPForker\Library\CustomTerminalColor\Color;

class Base
{
    /**
     * container version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * when force to kill process:
     *
     * we send `reboot` command to the child process,
     * after FORCE_KILL_CONTAINER_TIMEOUT seconds,
     * if the process is still alive then forced to kill.
     *
     * @var int 
     */
    const FORCE_KILL_CONTAINER_TIMEOUT = 2;

    /**
     * ui safe length necessary for keeping beauty
     *
     * @var int
     */
    const UI_SAFE_LENGTH    = 4;

    /**
     * status code for starting 
     *
     * @var int
     */
    const STATUS_STARTING   = 1;

    /**
     * status code for running
     *
     * @var int
     */
    const STATUS_RUNNING    = 2;

    /**
     * status code for shutdown
     *
     * @var int
     */
    const STATUS_SHUTDOWN   = 4;

    /**
     * status code for reloading
     *
     * @var int
     */
    const STATUS_RELOADING  = 8;

    /**
     * log level code for debuging mode
     *
     * @var int
     */
    const LOG_LEVEL_DEBUG   = 1;

    /**
     * log level code for info mode
     *
     * @var int
     */
    const LOG_LEVEL_INFO    = 2;

    /**
     * log level code for warning mode
     *
     * @var int
     */
    const LOG_LEVEL_WARN    = 3;

    /**
     * log level code for error mode
     *
     * @var int
     */
    const LOG_LEVEL_ERROR   = 4;

    /**
     * log level code for crazy mode
     *
     * @var int
     */
    const LOG_LEVEL_CRAZY   = 5;

    /**
     * start script
     *
     * @var string
     */
    static  protected   $_startFile = '';

    /**
     * is daemonize or not
     *
     * @var boolean
     */
    static  public $daemonize = false;

    /**
     * log file
     *
     * @var string
     */
    static  public  $logFile = '';

    /**
     * stdout file
     *
     * @var string
     */
    static  public  $stdoutFile = '/dev/null';

    /**
     * master process pid file
     *
     * @var string
     */
    static  public  $masterPidFile = '';

    /**
     * current run state
     *
     * @var int
     */
    static  protected   $_status = self::STATUS_STARTING;

    /**
     * global configuration
     *
     * @var array
     */
    static  protected   $_config            = array();

    /**
     * standard output stream
     *
     * @var resource
     */
    static  protected	$_outputStream	    = null;

    /**
     * $outputStream is decorated or not
     *
     * @var boolean
     */
    static  protected	$_outputDecorated   = false;

    /**
     * all container instances
     *
     * @var array
     */
    static  protected   $_containers =      array();

    /**
     * container start time
     *
     * @var int
     */
    static  protected   $_start_time        = 0;

    /**
     * is silent mode or not 
     *
     * @var boolean
     */
    static  protected   $_isSilentMode      = false;

    /**
     * show text for start mode
     *
     * @var string
     */
    static  protected   $_start_mode_text   = '';

    /**
     * master process pid
     *
     * @var string
     */
    static  protected   $_masterPid = '';

    /**
     * PHP built-in protocols
     *
     * @var array
     */
    static  protected   $_builtinTransports = array(
        'tcp'   => 'tcp',
        'udp'   => 'udp',
        'unix'  => 'unix',
        'ssl'   => 'tcp'
    );

    /**
     * @brief    __construct    
     *
     * @return   void
     */
    public function __construct()
    {
        self::$_config = require(dirname(__DIR__) . '/Config/Main.php');
        self::$_start_time = Tool::getNowTime(false); 
    }

    /**
     * @brief    check PHP_SAPI execute environment necessary
     *
     * @return   true | exit
     */
    static protected function checkSapiExecuteEnvironment()
    {
        PHP_OS != 'Linux' && self::showHelpByeBye('only run in Linux platform');
        PHP_SAPI != 'cli' && self::showHelpByeBye("only run in command line mode");
        !extension_loaded('posix') && self::showHelpByeBye('please ensure POSIX extension are installed');
        !extension_loaded('pcntl') && self::showHelpByeBye('please ensure PCNTL extension are installed');

        return true;
    }

    /**
     * @brief    daemonize  
     *
     * @return   void
     */
    static protected function daemonize()
    {
        if(!self::$daemonize) return;

        //set max privilege to file 0666 and dir 0777
        umask(0);

        //fork for the first time
        $pid = pcntl_fork();
        $pid === -1 && self::showHelpByeBye('Daemonize: fork faild');
        $pid > 0 && exit(0);

        //setsid to be a leader
        posix_setsid() === -1 && self::showHelpByeBye('Daemonize: setsid failded');

        //fork again avoid SVR4 system regain the control of terminal
        $pid = pcntl_fork();
        $pid === -1 && self::showHelpByeBye('Daemonize: fork faild');
        $pid > 0 && exit(0);
    }

    /**
     * @brief    displayGui
     *
     * @return   void | boolean
     */
    static public function displayGui()
    {
        global $argv;

        //slient mode
        if(self::$_isSilentMode) return false;

        //show banner
        self::showBanner();

        //show usage
        self::showUsage();

        //show synopsis
        self::showVersion();

        //show containers
        self::showContainers();

        //show log
        $msg = "Container [" . basename(self::$_startFile) . "] start success. Press Ctrl + C to stop.";
        self::$daemonize && $msg = "Container [" . basename(self::$_startFile) . "] start success. Type php {$argv[0]} stop to stop";
        self::log($msg, self::LOG_LEVEL_DEBUG);
        self::log(self::$_start_mode_text, self::LOG_LEVEL_DEBUG);
    }

    /**
     * @brief    showSplitLine
     *
     * @param    string  $msg
     *
     * @return   void
     */
    static public function showSplitLine($msg = '')
    {
        $total_length = self::getSingleLineTotalLength();
        $split_line = '<n>' . str_pad("<t>  $msg  </t>", $total_length + strlen('<t></t>'), '-', STR_PAD_BOTH) . '</n>'. PHP_EOL;
        self::safeEcho($split_line);
    }

    /**
     * @brief    showVersion
     *
     * @return   void
     */
    static public function showVersion()
    {
        self::showSplitLine('PHPForker');
        $total_length = self::getSingleLineTotalLength();
        $php_config = self::getPHPConfiguration();
        //attention: $run_time not work expected !! support in future !!
        $run_time = Tool::time2second(time() - self::$_start_time, false);
        $line_version = 'PHPForker  Version:   ' . self::VERSION . str_pad('PHP Version: ', 31, ' ', STR_PAD_LEFT) . PHP_VERSION . PHP_EOL;
        $line_version .= 'System     Platform:  ' . PHP_OS . str_pad('PHP Configuration: ', 37, ' ', STR_PAD_LEFT) . $php_config . PHP_EOL;
        $line_version .= 'PHPForker  StartTime: ' . Tool::getHumanLogTime(self::$_start_time);
        //$line_version .= str_pad('PHP StartFile: ', 19, ' ', STR_PAD_LEFT) . self::$_startFile . PHP_EOL;
        $line_version .= str_pad('Run ', 8, ' ', STR_PAD_LEFT) . $run_time . ' (hey, not support yet)' . PHP_EOL;
        !defined('LINE_VERSIOIN_LENGTH') && define('LINE_VERSIOIN_LENGTH', strlen($line_version));
        self::safeEcho($line_version);
    }

    /**
     * @brief    showContainers
     *
     * @return   void
     */
    static public function showContainers()
    {
        self::showSplitLine('Containers');
        $total_length = self::getSingleLineTotalLength();
        $title = '';
        foreach(self::getUiColumnsName() as $column_name => $prop)
        {
            $key = 'max' . ucfirst(strtolower($column_name)) . 'NameLength';
            $maxKeyLength = self::$_config['ui']['length'][$key];
            $column_name = ucfirst($column_name);
            $title.= "<s>{$column_name}</s>"  .  str_pad('', $maxKeyLength + self::UI_SAFE_LENGTH - strlen($column_name));
        }
        $title && self::safeEcho($title . PHP_EOL);

        $index = 1;
        foreach (self::$_containers as $container) 
        {
            $content = '';
            foreach(self::getUiColumnsName() as $column_name => $prop)
            {
                $key = 'max' . ucfirst(strtolower($column_name)) . 'NameLength';
                $maxKeyLength = self::$_config['ui']['length'][$key];
                preg_match_all("/(<n>|<\/n>|<w>|<\/w>|<g>|<\/g>|<y>|<\/y>|<s>|<\/s>)/is", $container->{$prop}, $matches);
                $place_holder_length = !empty($matches) ? strlen(implode('', $matches[0])) : 0;
                $container->index = '(' . str_pad($index, 2, '0', STR_PAD_LEFT) . ')';
                $content .= str_pad($container->{$prop}, $maxKeyLength + self::UI_SAFE_LENGTH + $place_holder_length);
            }
            $content && self::safeEcho("<g>" . $content . "</g>" . PHP_EOL);
            $index++;
        }

        //show last line
        $line_last = str_pad('', self::getSingleLineTotalLength(), '-') . PHP_EOL;
        $content && self::safeEcho($line_last);
    }

    /**
     * @brief    showBanner
     *
     * @return   void
     */
    static public function showBanner()
    {
        self::showSplitLine('Welcome');
        print <<<EOT
    ____   __  __ ____   ______              __                   
   / __ \ / / / // __ \ / ____/____   _____ / /__ ___   ____      A simple Multi-Process Skeleton
  / /_/ // /_/ // /_/ // /_   / __ \ / ___// //_// _ \ / ___/     
 / ____// __  // ____// __/  / /_/ // /   / ,<  /  __// /         http://github.com/blogdaren
/_/    /_/ /_//_/    /_/     \____//_/   /_/|_| \___//_/          
                                                                  http://www.blogdaren.com   

EOT;
    }

    /**
     * @brief    showUsage  
     *
     * @return   void
     */
    static public function showUsage()
    {
        self::showSplitLine("Synopsis");
        print <<<EOT
Usage: /path/to/php  /path/to/xxx.php  <command>  [option]  
<command>:                             [option]:
  start          启动所有容器实例         -d            化身为守护进程并在后台运行
  stop           停止所有容器实例         -g            优雅的stop或reboot容器实例
  reboot         重启所有容器实例         -h            在控制台打印本脚本使用说明
  reload         热启所有容器实例         -v            分层次打印对应级别日志数据

EOT;
    }

    /**
     * @brief    getUiColumnsName   
     *
     * @return   array
     */
    static public function getUiColumnsName()
    {
        return  self::$_config['ui']['column'];
    }

    /**
     * @brief    getUiColumnsLength     
     *
     * @return   int
     */
    static public function getUiColumnsLength()
    {
        return  self::$_config['ui']['length'];
    }

    /**
     * @brief    getSingleLineTotalLength   
     *
     * @return   int
     */
    static public function getSingleLineTotalLength()
    {
        $total_length = 0;

        foreach(self::getUiColumnsName() as $column_name => $prop){
            $key = 'max' . ucfirst(strtolower($column_name)) . 'NameLength';
            $maxKeyLength = self::$_config['ui']['length'][$key];
            $total_length += $maxKeyLength + self::UI_SAFE_LENGTH;
        }

        //keep beauty when show less colums
        !defined('LINE_VERSIOIN_LENGTH') && define('LINE_VERSIOIN_LENGTH', 0);
        $total_length <= LINE_VERSIOIN_LENGTH && $total_length = LINE_VERSIOIN_LENGTH;

        return $total_length;
    }

    /**
     * @brief    getPHPConfiguration    
     *
     * @return   string
     */
    static public function getPHPConfiguration()
    {
        exec('php --ini', $buffer, $status);

        if(empty($buffer) || $status <> 0) 
        {
            ob_start(); 
            @phpinfo(); 
            $buffer = ob_get_contents(); 
            ob_end_clean();
            $buffer = explode(PHP_EOL, $buffer);
        }

        $match_line = '';
        foreach($buffer as $k => $v)
        {
            preg_match_all("/Loaded Configuration File/is", $v, $matches);
            if(!empty($matches[0])) {
                $match_line = $v;
                unset($buffer);
                break;
            }
        }

        $result = explode(" ", $match_line);
        $config = !empty($result) ?  array_pop($result) : 'none';

        return $config;
    }

    /**
     * @brief    showHelpByeBye
     *
     * @param    string  $msg
     *
     * @return   exit
     */
    static protected function showHelpByeBye($msg = "")
    {
        if(!empty($msg))
        {
            !is_string($msg) && $msg = json_encode($msg);
            self::showSplitLine('Error');
            $error_msg = PHP_EOL . "Error: " . wordwrap($msg, 72, "\n  ") . PHP_EOL . PHP_EOL;
            Color::showError($error_msg);
        }

        $usage = self::showUsage() . PHP_EOL;
        exit($usage);
    }

    /*static public function getUsage()
    {
        $usage = ":::::::::::::::: PHPForker 守护进程脚本 ::::::::::::::::" . PHP_EOL . PHP_EOL;
        $usage .= "Usage: /path/to/php /path/to/xxx.php <command> [option] " . PHP_EOL . PHP_EOL;
        $usage .= "<command>:" . PHP_EOL;
        $usage .= "  start          启动容器实例" . PHP_EOL;
        $usage .= "  stop           停止容器实例" . PHP_EOL;
        $usage .= "  restart        重启容器实例" . PHP_EOL;
        $usage .= "  reload         热启动容器实例" . PHP_EOL . PHP_EOL;
        $usage .= "[option]:" . PHP_EOL;
        $usage .= "  -d             化身为守护进程并在后台运行" . PHP_EOL;
        $usage .= "  -g             优雅的stop或reload容器实例" . PHP_EOL;
        $usage .= "  -v             增一个显示日志级别" . PHP_EOL;
        $usage .= "  -h             显示帮助信息" . PHP_EOL;

        return $usage;
    }*/


    /**
     * @brief    safeEcho
     *
     * @param    string  $msg
     * @param    string  $show_colorful
     *
     * @return   boolean
     */
    static public function safeEcho($msg, $show_colorful = true)
    {
        $stream = self::setOutputStream();
        if(!$stream) return false;

        if($show_colorful) 
        {
            $line = $white = $yellow = $red = $green = $blue = $skyblue = $end = '';
            if(self::$_outputDecorated) 
            {
                $line    =  "\033[1A\n\033[K";
                $white   =  "\033[47;30m";
                $yellow  =  "\033[1m\033[33m";
                $red     =  "\033[1m\033[31m";
                $green   =  "\033[1m\033[32m";
                $blue    =  "\033[1m\033[34m";
                $skyblue =  "\033[1m\033[36m";
                $ry      =  "\033[1m\033[41;33m";
                $ul      =  "\033[1m\033[4m\033[36m";
                $end     =  "\033[0m";
            }

            $color = array($line, $white, $green, $yellow, $skyblue, $red, $blue, $ry, $ul);
            $msg = str_replace(array('<n>', '<w>', '<g>', '<y>', '<s>', '<r>', '<b>', '<t>', '<u>'), $color, $msg);
            $msg = str_replace(array('</n>', '</w>', '</g>', '</y>', '</s>', '</r>', '</b>', '</t>', '</u>'), $end, $msg);
        } 
        elseif(!self::$_outputDecorated) 
        {
            return false;
        }

        fwrite($stream, $msg);
        fflush($stream);

        return true;
    }

    /**
     * @brief    setOutputStream
     *
     * @param    string  $stream
     *
     * @return   boolean
     */
    static private function setOutputStream($stream = null)
    {
        if(!$stream) 
        {
            $stream = self::$_outputStream ? self::$_outputStream : STDOUT;
        }

        if(!$stream || !is_resource($stream) || 'stream' !== get_resource_type($stream)) 
        {
            return false;
        }

        $stat = fstat($stream);

        if(($stat['mode'] & 0170000) === 0100000) {
            self::$_outputDecorated = false;
        } else {
            self::$_outputDecorated = function_exists('posix_isatty') && posix_isatty($stream);
        }

        return self::$_outputStream = $stream;
    }

    /**
     * @brief    installSignal  
     *
     * @return   void
     */
    static protected function installSignal()
    {
        //stop -> graceful stop -> reload ->  graceful reload -> status -> connection status
        $signals = array(SIGINT, SIGTERM, SIGUSR1, SIGQUIT, SIGUSR2);
        foreach($signals as $signal)
        {
            pcntl_signal($signal,  array('\PHPForker\Container', 'signalHandler'), false);
        }

        //ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    /**
     * @brief    removeSignal   
     *
     * @return   void
     */
    static protected function removeSignal()
    {
        $signals = array(SIGINT, SIGTERM, SIGUSR1, SIGQUIT, SIGUSR2);
        foreach($signals as $signal)
        {
            pcntl_signal($signal,  SIG_IGN, false);
        }
    }

    /**
     * @brief    reinstallSignal    
     *
     * @return   void
     */
    static protected function reinstallSignal()
    {
        self::removeSignal();
        self::installSignal();
        //self::log("child__pid: " . posix_getpid() . " 子进程安装信号成功");
    }

    /**
     * @brief    getSignalText  
     *
     * @param    int    $signal
     *
     * @return   string
     */
    static protected function getSignalName($signal)
    {
        if(empty($signal)) return '';

        $signal_name = '';
        $signal == SIGINT  && $signal_name = "SIGINT";
        $signal == SIGTERM && $signal_name = "SIGTERM";
        $signal == SIGUSR1 && $signal_name = "SIGUSR1";
        $signal == SIGQUIT && $signal_name = "SIGQUIT";

        return $signal_name;
    }

    /**
     * @brief    showSignalLog  
     *
     * @param    int    $signal
     *
     * @return   
     */
    static protected function showSignalLog($signal)
    {
        $pid = posix_getpid();
        $prefix1 = $pid == self::$_masterPid ? 'master_pid' : 'child__pid';
        $prefix2 = $pid == self::$_masterPid ? '主进程' : '子进程';
        $signal_name = self::getSignalName($signal);

        if(!empty($signal_name))
        {
            $log = "{$prefix1}: {$pid} {$prefix2}捕捉到信号 {$signal_name}";
            self::log($log, self::LOG_LEVEL_DEBUG);
        }
    }

    /**
     * @brief    setProcessTitle    
     *
     * @param    string  $title
     *
     * @return   void
     */
    static protected function setProcessTitle($title)
    {
        set_error_handler(function(){});
        if(function_exists('cli_set_process_title')) 
        {
            cli_set_process_title($title);
        } 
        elseif(extension_loaded('proctitle') && function_exists('setproctitle')) 
        {
            setproctitle($title);
        }
        restore_error_handler();
    }

    /**
     * @brief    logger    
     *
     * @param    string  $msg       
     * @param    string  $level     DEBUG|INFO|WARN|ERROR|CRAZY
     *
     * @return   void
     */
    static public function log($msg, $level = self::LOG_LEVEL_INFO)
    {
        if(empty($msg)) return;

        $log_level = self::getLogLevel($level);
        $log_level = str_pad($log_level, 5, ' ', STR_PAD_RIGHT);
        list($ts, $ms) = explode(".", sprintf("%f", microtime(true)));
        $time = date("Y-m-d H:i:s") . "." . str_pad($ms, 6, 0);
        $prefix = "$time | $log_level | ";
        $msg = $prefix . $msg . PHP_EOL;
        file_put_contents((string)self::$logFile, $msg, FILE_APPEND | LOCK_EX);

        //show colorful text by level 
        $level == self::LOG_LEVEL_DEBUG && $msg = Color::getColorfulText($msg, 'purple');
        $level == self::LOG_LEVEL_WARN  && $msg = Color::getColorfulText($msg, 'brown');
        $level == self::LOG_LEVEL_ERROR && $msg = Color::getColorfulText($msg, 'red');
        $level == self::LOG_LEVEL_CRAZY && $msg = Color::getColorfulText($msg, 'light_red');

        //only show in DEBUG mode
        !self::$daemonize && self::safeEcho($msg);
    }

    /**
     * @brief    getLogLevel    
     *
     * @param    string  $level
     *
     * @return   string
     */
    static public function getLogLevel($level)
    {
        switch ($level) {
            case self::LOG_LEVEL_DEBUG:
                $log_level = 'DEBUG';
                break;
            case self::LOG_LEVEL_INFO:
                $log_level = 'INFO';
                break;
            case self::LOG_LEVEL_WARN:
                $log_level = 'WARN';
                break;
            case self::LOG_LEVEL_ERROR:
                $log_level = 'ERROR';
                break;
            case self::LOG_LEVEL_CRAZY:
                $log_level = 'CRAZY';
                break;
            default:
                $log_level = 'INFO';
                break;
        }

        return $log_level;
    }

    /**
     * @brief    get master pid
     *
     * @return   int
     */
    static public function getMasterPid()
    {
        $master_pid = is_file(self::$masterPidFile) && file_exists(self::$masterPidFile) 
                    ? file_get_contents(self::$masterPidFile) : 0;

        return $master_pid;
    }

    /**
     * @brief    check if master process is alive
     *
     * @return   boolean
     */
    static public function checkIfMasterIsAlive()
    {
        $master_pid = self::getMasterPid();
        $master_is_alive = $master_pid && posix_kill($master_pid, 0) && posix_getpid() != $master_pid;

        return $master_is_alive;
    }
}

