<?php
/**
 * @script   Container.php
 * @brief    This file is part of PHPForker
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.blogdaren.com
 * @version  1.0.0
 * @modify   2018-09-25
 */

namespace PHPForker;
use PHPForker\Library\Helper\Tool;
use PHPForker\Library\Helper\Timer;
use PHPForker\Library\CustomTerminalColor\Color;
use PHPForker\Core\Base;
require_once __DIR__ . '/Library/Common/Functions.php';

class Container extends Base
{
    /**
     * pids waiting for restarting or reloading
     *
     * @var array
     */
	static  protected	$_pidsToRestart = array();

    /**
     * container id mapping to pid
     *
     * @var array
     */
    static  protected   $_idMap = array();

    /**
     * pid mapping to pid
     *
     * @var array
     */
    static  protected   $_pidMap = array();

    /**
     * is gracefully stop or not 
     *
     * @var boolean
     */
    static  protected   $_gracefulStop = false;

    /**
     * root path for autoload 
     *
     * @var string
     */
    protected $_autoloadRootPath = '';

    /**
     * socket name
     *
     * @var string
     */
    protected $_socketName = '';

    /**
     * main socket
     *
     * @var resouce
     */
    protected $_mainSocket = null;

    /**
     * context for socket
     *
     * @var resouce
     */
    protected $_context = null;

    /**
     * unix user of processes, needs appropriate privileges (usually root)
     *
     * @var string
     */
    public $user = 'root';

    /**
     * unix group of processes, needs appropriate privileges (usually root)
     *
     * @var string
     */
    public $group = 'root';

    /**
     * container name
     *
     * @var string
     */
    public $name = 'none';

    /**
     * how many child processes to start or fork
     *
     * @var int
     */
    public $count = 1;

    /**
     * transport layer protocol
     *
     * @var string
     */
    public $transport = 'tcp';

    /**
     * is container stopping or not
     *
     * @var boolean
     */
    public $stopping = false;

    /**
     * is container reloadable or not
     *
     * @var boolean
     */
    public $reloadable = true;

    /**
     * listening socket name
     *
     * @var boolean
     */
    public $listening = '';

    /**
     * @brief    __construct    
     *
     * @param    string  $socket_name
     *
     * @return   void
     */
    public function __construct($socket_name = '')
	{
        parent::__construct();

		$this->hashId = spl_object_hash($this);
		self::$_containers[$this->hashId] = $this;
        self::$_pidMap[$this->hashId]  = array();

        if(!empty($socket_name)) 
        {
            if(!isset($context_option['socket']['backlog'])) 
            {
                $context_option['socket']['backlog'] = self::$_config['socket']['backlog'];
            }

            $this->_context = stream_context_create($context_option);
            $this->_socketName = $socket_name;
        }
	}

    /**
     * @brief    start entry
     *
     * @return   void
     */
    static public function start()
    {
        self::checkSapiExecuteEnvironment();
        self::init();
        self::parseTerminalCommand();
        self::daemonize();
		self::startContainers();
		self::displayGui();
        self::saveMasterPid();
        self::installSignal();
        self::forkContainers();
        self::monitorContainers();
	}

    /**
     * @brief    getSocketName  
     *
     * @return   string
     */
    public function getSocketName()
    {
        return $this->_socketName ? lcfirst($this->_socketName) : 'none';
    }

    /**
     * @brief    init something 
     *
     * @return   void 
     */
    static protected function init()
    {
        //locate start file
        $backtrace        = debug_backtrace();
        self::$_startFile = $backtrace[count($backtrace) - 1]['file'];

        //init log dir
        Tool::createMultiDirectory(LOG_DIR);

        //init master pid file
        $unique_prefix = str_replace('/', '_', self::$_startFile);
        empty(self::$masterPidFile) && self::$masterPidFile = LOG_DIR . "/{$unique_prefix}.pid";

        //init log file
        empty(self::$logFile) && self::$logFile = LOG_DIR . '/container.log';
        $log_file = (string)self::$logFile;
        if(!is_file($log_file)) 
        {
            touch($log_file);
            chmod($log_file, 0622);
        }

        //init container state
        self::$_status = self::STATUS_STARTING;

        //init process title
        self::setProcessTitle('PHPForker: master process  start_file=' . self::$_startFile);

        //init container id
        self::initContainerId();

        //init timer
        Timer::init();
    }

    /**
     * @brief    parse command from terminal
     *
     * @return   void
     */
    static protected function parseTerminalCommand()
    {
        global $argv;
        $start_file = $argv[0];
        $available_commands = array('start', 'stop', 'reboot', 'reload');

        //check if command is valid
        if(empty($argv[1]) || !in_array($argv[1], $available_commands)) 
        {
            $msg = empty($argv[1]) ? "" : "unknown command: {$argv[1]}";
            self::showHelpByeBye($msg);
        }

        //command
        $command1 = trim($argv[1]);
        $command2 = isset($argv[2]) ? trim($argv[2]) : '';

        //command2 == -h | -q | -d 
        $command2 == '-h' && self::showHelpByeBye();
        $command2 == '-d' && self::$daemonize = true;
        $command2 == '-g' && self::$_gracefulStop = true;
        $command2 == '-q' && self::$_isSilentMode = true;
        $command2 == '-v' && self::showHelpByeBye('`-v -vv ...` not work currently, it will support in future');

        //command1
        $mode = '';
        in_array($command1, array('start', 'reboot')) && $mode = self::$daemonize ? 'in DAEMON mode' : 'in DEBUG mode';
        self::$_start_mode_text = "Container [$start_file] $command1 $mode";

        //get master process pid and check if master process is alive
        $master_pid = self::getMasterPid();
        if(self::checkIfMasterIsAlive()) 
        {
            $command1 === 'start' && self::showHelpByeBye("Container [$start_file] is already running");
        } 
        elseif($command1 !== 'start' && $command1 !== 'reboot') 
        {
            self::showHelpByeBye("Container [$start_file] not run");
        }

        //execute command1
        switch($command1) 
        {
            case 'start':
                break;
            case 'stop':
            case 'reboot':
                //reboot log 
                $command1 == 'reboot' && self::log("Container [$start_file] reboot ...");

                if(self::$_gracefulStop) 
                {
                    $signal = SIGTERM;
                    self::log("Container [$start_file] is stopping gracefully ...");
                } 
                else 
                {
                    $signal = SIGINT;
                    self::log("Container [$start_file] is stopping ...");
                }

                //send stop signal to master process
                $master_pid && posix_kill($master_pid, $signal);

                //check if master process is still alive
                $start_time = time();
                while(1) 
                {
                    if(self::checkIfMasterIsAlive()) 
                    {
                        if(!self::$_gracefulStop && time() - $start_time >= self::$_config['timeout']['process']) 
                        {
                            self::showHelpByeBye("Container [$start_file] stop failed");
                        }
                        usleep(10000);
                        continue;
                    }

                    //stop success
                    self::log("Container [$start_file] stop success");
                    $command1 === 'stop' && exit(0);
                    break;
                }
                break;
            case 'reload':
                $signal = self::$_gracefulStop ? SIGQUIT : SIGUSR1;
                posix_kill($master_pid, $signal);
                exit;
            default:
                self::showHelpByeBye();
        }
    }

    /**
     * @brief    init container id
     *
     * @return   array
     */
    static protected function initContainerId()
    {
        foreach(self::$_containers as $hash_id => $container) 
        {
            $new_id_map = array();
            $container->count = $container->count <= 0 ? 1 : $container->count;
            for($id = 1; $id <= $container->count; $id++) 
            {
                $new_id_map[$id] = isset(self::$_idMap[$hash_id][$id]) ? self::$_idMap[$hash_id][$id] : 0;
            }
            self::$_idMap[$hash_id] = $new_id_map;
        }
    }

    /**
     * @brief    startContainers    
     *
     * @return   void
     */
    static protected function startContainers()
    {
        foreach(self::$_containers as $container) 
		{
            //set container name
            empty($container->name) && $container->name = 'none';

            //set unix user of the container process
            empty($container->user) && $container->user = self::getCurrentUser();

            //set listening socket name 
			$container->listening = $container->getSocketName();

            //set status name
			$container->status = '<g> [OK] </g>';

			//get clolumn mapping for GUI
            foreach(self::getUiColumnsName() as $column_name => $prop)
            {
				!isset($container->{$prop}) && $container->{$prop}= 'NNNN';
				$prop_length = strlen($container->{$prop});
				$key = 'max' . ucfirst(strtolower($column_name)) . 'NameLength';
				self::$_config['ui']['length'][$key] = max(self::$_config['ui']['length'][$key], $prop_length);
			}

            //start listen
            $container->listen();
        }
    }

    /**
     * @brief    save master pid
     *
     * @return   void
     */
    static protected function saveMasterPid()
    {
        self::$_masterPid = posix_getpid();

        if(false === file_put_contents(self::$masterPidFile, self::$_masterPid)) 
        {
            self::showHelpByeBye('failed to save pid to ' . self::$masterPidFile);
        }

        self::log("master_pid: " . self::$_masterPid . " 主进程写入文件成功");
        self::log("master_pid: " . self::$_masterPid . " 主进程安装信号成功");
    }

    /**
     * @brief    signalHandler  
     *
     * @param    int    $signal
     *
     * @return   void
     */
    static public function signalHandler($signal)
    {
        //show signal log when caught signal
        self::showSignalLog($signal);

        switch($signal) 
        {
            case SIGINT:    //stop
            case SIGTERM:   //graceful stop
                self::$_gracefulStop = $signal === SIGTERM ? true : false;
                self::stopAll();
                break;
            case SIGUSR1:   //reload
            case SIGQUIT:   //graceful reload
                self::$_gracefulStop = $signal === SIGQUIT ? true : false;
                self::$_pidsToRestart = self::getAllContainerPids();
                self::reload();
                break;
            default:
                break;
        }
    }

    /**
     * @brief    forkContainers     
     *
     * @return   void
     */
    static protected function forkContainers()
    {
        foreach(self::$_containers as $container) 
        {
            while(count(self::$_pidMap[$container->hashId]) < $container->count) 
            {
                self::forkOneChildContainer($container);
            }
        }
    }

    /**
     * @brief    forkOneChildContainer  
     *
     * @param    string  $container
     *
     * @return   void
     */
    static protected function forkOneChildContainer($container)
    {
        //get one available container id
        $id = self::getOneContainerId($container->hashId, 0);
        if($id === false)  return;

        $pid = pcntl_fork();
        $pid < 0 && self::showHelpByeBye("forkOneChildContainer fail");

        if($pid > 0) 
        {
            self::$_pidMap[$container->hashId][$pid] = $pid;
            self::$_idMap[$container->hashId][$id]   = $pid;
        } 
        elseif(0 === $pid) 
        {
            //clear all timer
            Timer::delAll();

            self::log("child__pid: " . posix_getpid() . " 子进程创建成功($container->name)");
            self::$_status === self::STATUS_STARTING && self::resetStd();
            self::$_pidMap = self::$_idMap = array();

            //remove other listener
            self::removeOtherListener($container);

            $title = 'PHPForker: child process ' . $container->name . ' ' . $container->getSocketName();
            self::setProcessTitle($title);
            $container->setUserAndGroup();
            $container->id = $id;
            $container->run();
            $err = new Exception('child process exit unexpected...');
            self::log($err);
            exit(250);
        } 
    }

    /**
     * set unix user and group for current process
     *
     * @return void
     */
    public function setUserAndGroup()
    {
        //get uid and gid
        $user_info = posix_getpwnam($this->user);
        if(!$user_info) 
        {
            self::log("User__Info: {$this->user} 用户不存在", self::LOG_LEVEL_WARN);
            return;
        }
        $uid = $user_info['uid'];
        $gid = $user_info['gid'];

        if($this->group) 
        {
            $group_info = posix_getgrnam($this->group);
            if(!$group_info) 
            {
                self::log("Group__Info: {$this->group} 组不存在", self::LOG_LEVEL_WARN);
                return;
            }
            $gid = $group_info['gid'];
        }

        //set uid and gid
        if($uid != posix_getuid() || $gid != posix_getgid()) 
        {
            if(!posix_setgid($gid) || !posix_initgroups($user_info['name'], $gid) || !posix_setuid($uid)) 
            {
                self::log("Change gid or uid failed", self::LOG_LEVEL_WARN);
            }
        }
    }

    /**
     * @brief    run    
     *
     * @return   void
     */
    public function run()
    {
        //update process state
        self::$_status = self::STATUS_RUNNING;

        //register shutdown function for checking errors
        register_shutdown_function(array("\\PHPForker\\Container", 'checkErrors'));

        //set autoload root path
        Autoloader::setRootPath($this->_autoloadRootPath);

        //reinstall signal
        self::reinstallSignal();

        restore_error_handler();
        
        //main loop ready for accept connection from client
        self::readyForAcceptClientConnection();
    }

    /**
     * @brief    checkErrors    
     *
     * @return   void
     */
    static public function checkErrors()
    {
        if(self::STATUS_SHUTDOWN != self::$_status) 
        {
            $type = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_ERROR, E_RECOVERABLE_ERROR);
            $error_msg = 'Container ['. posix_getpid() .'] process terminated';
            $errors    = error_get_last();

            if($errors && in_array($errors['type'], $type)) 
            {
                $error_msg .= ' with ERROR: ' . self::getErrorType($errors['type']);
                $error_msg .= " \"{$errors['message']} in {$errors['file']} on line {$errors['line']}\"";
            }

            self::log($error_msg);
        }
    }

    /**
     * @brief    getErrorType   
     *
     * @param    string  $type
     *
     * @return   void
     */
    static protected function getErrorType($type)
    {
        switch ($type) {
            case E_ERROR:                       // 1 
                return 'E_ERROR';
            case E_WARNING:                     // 2 
                return 'E_WARNING';
            case E_PARSE:                       // 4
                return 'E_PARSE';
            case E_NOTICE:                      // 8 
                return 'E_NOTICE';
            case E_CORE_ERROR:                  // 16 
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:                // 32
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:               // 64
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:             // 128 
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:                  // 256
                return 'E_USER_ERROR';
            case E_USER_WARNING:                // 512
                return 'E_USER_WARNING';
            case E_USER_NOTICE:                 // 1024
                return 'E_USER_NOTICE';
            case E_STRICT:                      // 2048
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:           // 4096 
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:                  // 8192
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:             // 16384
                return 'E_USER_DEPRECATED';
        }

        return "";
    }

    /**
     * @brief    resetStd   
     *
     * @return   void
     */
    static public function resetStd()
    {
        if(!self::$daemonize) return;

        global $STDOUT, $STDERR;
        $handle = fopen(self::$stdoutFile, "a");

        //change output stream
        if($handle) 
        {
            unset($handle);
            set_error_handler(function(){});
            fclose($STDOUT);
            fclose($STDERR);
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen(self::$stdoutFile, "a");
            $STDERR = fopen(self::$stdoutFile, "a");
            self::setOutputStream($STDOUT);
            restore_error_handler();
        } 
        else 
        {
            self::showHelpByeBye('can not open stdoutFile ' . self::$stdoutFile);
        }
    }

    /**
     * @brief    monitorContainers  
     *
     * @return   void
     */
    static protected function monitorContainers()
    {
        self::$_status = self::STATUS_RUNNING;
        while (1) 
        {
            pcntl_signal_dispatch();
            $status = 0;
            $child_pid = pcntl_wait($status, WUNTRACED);
            pcntl_signal_dispatch();

            //if a child has already exited
            if($child_pid > 0) 
            {
                //find out which container process has exited
                foreach(self::$_pidMap as $hash_id => $container_pid_array) 
                {
                    if(isset($container_pid_array[$child_pid])) 
                    {
                        $container = self::$_containers[$hash_id];
                        self::log("child__pid: {$child_pid} exit with status $status");

                        //clear process data
                        unset(self::$_pidMap[$hash_id][$child_pid]);

                        //mark id available
                        $id = self::getOneContainerId($hash_id, $child_pid);
                        self::$_idMap[$hash_id][$id] = 0;

                        break;
                    }
                }

                //is still running state then fork a new container process
                if(self::$_status !== self::STATUS_SHUTDOWN) 
                {
                    //continue forking
                    self::forkContainers();

                    //continue reloading
                    if(isset(self::$_pidsToRestart[$child_pid])) 
                    {
                        unset(self::$_pidsToRestart[$child_pid]);
                        self::reload();
                    }
                }
            }

            //if shutdown state and all child processes exited then master process exit
            if(self::$_status === self::STATUS_SHUTDOWN && !self::getAllContainerPids()) 
            {
                self::exitMasterAndClearAll();
            }
        }
    }

    /**
     * @brief    exitMasterAndClearAll  
     *
     * @return   void
     */
    static protected function exitMasterAndClearAll()
    {
        foreach(self::$_containers as $container) 
        {
            $socket_name = $container->getSocketName();
            if($container->transport === 'unix' && $socket_name) 
            {
                list(, $address) = explode(':', $socket_name, 2);
                @unlink($address);
            }
        }

        @unlink(self::$masterPidFile);
        self::log("Container [" . basename(self::$_startFile) . "] has been stopped...", self::LOG_LEVEL_DEBUG);
        exit(0);
    }

    /**
     * @brief    getAllContainerPids    
     *
     * @return   array
     */
    static protected function getAllContainerPids()
    {
        $pid_array = array();
        foreach(self::$_pidMap as $container_pid_array) 
        {
            foreach($container_pid_array as $pid) 
            {
                $pid_array[$pid] = $pid;
            }
        }

        return $pid_array;
    }

    /**
     * @brief    getOneContainerId  
     *
     * @param    string  $hash_id
     * @param    string  $pid
     *
     * @return   int
     */
    static protected function getOneContainerId($hash_id, $pid)
    {
        return array_search($pid, self::$_idMap[$hash_id]);
    }

    /**
     * @brief    stopAll    
     *
     * @return   void
     */
    static public function stopAll()
    {
        self::$_status = self::STATUS_SHUTDOWN;

        if(self::$_masterPid === posix_getpid()) 
        {
            self::log("Container [" . basename(self::$_startFile) . "] is stopping ...", self::LOG_LEVEL_DEBUG);
            $container_pid_array = self::getAllContainerPids();

            //send stop signal to all child processes
            $signal = self::$_gracefulStop ? SIGTERM : SIGINT;
            foreach($container_pid_array as $container_pid) 
            {
                posix_kill($container_pid, $signal);
                if(!self::$_gracefulStop)
                {
                    Timer::add(self::FORCE_KILL_CONTAINER_TIMEOUT, 'posix_kill', array($container_pid, SIGKILL), false);
                }
            }

            Timer::add(1, "\\PHPForker\\Container::checkIfChildIsRunning");
        } 
        else 
        {
            foreach(self::$_containers as $container) 
            {
                if(!$container->stopping)
                {
                    $container->stop();
                    $container->stopping = true;
                }
            }

            self::$_containers = array();
            exit(0);
        }
    }

    /**
     * @brief    stop   
     *
     * @return   void
     */
    public function stop()
    {
        $this->unlisten();
    }

    /**
     * @brief    reload     
     *
     * @return   void
     */
    static protected function reload()
    {
        if(self::$_masterPid === posix_getpid()) 
        {
            //set reloading state
            if(self::$_status !== self::STATUS_RELOADING && self::$_status !== self::STATUS_SHUTDOWN) 
            {
                self::log("Container [" . basename(self::$_startFile) . "] is reloading...", self::LOG_LEVEL_DEBUG);
                self::$_status = self::STATUS_RELOADING;
            }

            //send reload signal to all child processes
            $signal = self::$_gracefulStop ? SIGQUIT : SIGUSR1;
            $reloadable_pid_array = array();
            foreach(self::$_pidMap as $hash_id => $container_pid_array) 
            {
                $container = self::$_containers[$hash_id];
                if($container->reloadable) 
                {
                    foreach($container_pid_array as $pid) 
                    {
                        $reloadable_pid_array[$pid] = $pid;
                    }
                } 
                else 
                {
                    foreach($container_pid_array as $pid) 
                    {
                        posix_kill($pid, $signal);
                    }
                }
            }

            //get all pids that are waiting reload
            self::$_pidsToRestart = array_intersect(self::$_pidsToRestart, $reloadable_pid_array);

            //reload complete
            if(empty(self::$_pidsToRestart)) 
            {
                self::$_status !== self::STATUS_SHUTDOWN && self::$_status = self::STATUS_RUNNING;
                return;
            }

            //continue reload
            $one_container_pid = current(self::$_pidsToRestart);

            //send reload signal to a container process
            posix_kill($one_container_pid, $signal);

            //if the process does not exit after self::FORCE_KILL_CONTAINER_TIMEOUT seconds, the force to kill it
            if(!self::$_gracefulStop)
            {
                Timer::add(self::FORCE_KILL_CONTAINER_TIMEOUT, 'posix_kill', array($one_container_pid, SIGKILL), false);
            }
        } 
        else 
        {
            reset(self::$_containers);
            $container = current(self::$_containers);
            $container->reloadable && self::stopAll();
        }
    }

    /**
     * @brief    listen     
     *
     * @return   void
     */
    public function listen()
    {
        if(!$this->_socketName) return;

        Autoloader::setRootPath($this->_autoloadRootPath);

        if(!$this->_mainSocket) 
        {
            list($scheme, $address) = explode(':', $this->_socketName, 2);

            //only support tcp & udp currently
            if(!in_array(strtolower($scheme), array('tcp', 'udp'))) 
            {
                self::showHelpByeBye('only support TCP & UDP protocol currently ...');
            }

            $this->transport = $scheme;
            $local_socket = self::$_builtinTransports[$this->transport] . ":" . $address;

            //flag
            $flags = $this->transport === 'udp' ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
            $errno = 0;
            $errmsg = '';

            //create an Internet or Unix domain server socket
            $this->_mainSocket = stream_socket_server($local_socket, $errno, $errmsg, $flags, $this->_context);
            if(!$this->_mainSocket) throw new \Exception($errmsg);

            if($this->transport === 'ssl') 
            {
                stream_socket_enable_crypto($this->_mainSocket, false);
            } 
            elseif($this->transport === 'unix') 
            {
                $socket_file = substr($address, 2);
                $this->user  && chown($socket_file, $this->user);
                $this->group && chgrp($socket_file, $this->group);
            }

            //try to open keepalive for tcp and disable Nagle algorithm
            if(function_exists('socket_import_stream') && self::$_builtinTransports[$this->transport] === 'tcp') 
            {
                set_error_handler(function(){});
                $socket = socket_import_stream($this->_mainSocket);
                socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
                socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
                restore_error_handler();
            }

            //non blocking
            stream_set_blocking($this->_mainSocket, 0);
        }
    }

    /**
     * @brief    unlisten   
     *
     * @return   void
     */
    public function unlisten() 
    {
        if($this->_mainSocket) 
        {
            set_error_handler(function(){});
            fclose($this->_mainSocket);
            $this->_mainSocket = null;
            //self::log("成功关闭 socket 套接字...");
            restore_error_handler();
        }
    }

    /**
     * @brief    setPublicProps     
     *
     * @param    string  $props
     *
     * @return   $this
     */
    public function setPublicProps($props = array())
    {
        if(empty($props)) return $this;

        foreach($props as $prop => $value)
        {
            if(!property_exists(__CLASS__, $prop)) continue;
            $this->{$prop} = $value;
        }

        return $this;
    }

    /**
     * @brief    removeOtherListener    
     *
     * @param    string  $container
     *
     * @return   void
     */
    static public function removeOtherListener($container)
    {
        foreach(self::$_containers as $hash_id => $one_container) 
        {
            if($one_container->hashId !== $container->hashId) 
            {
                $one_container->unlisten();
                unset(self::$_containers[$hash_id]);
            }
        }
    }

    /**  
     * check if child processes is really running
     */
    public static function checkIfChildIsRunning()
    {    
        foreach(self::$_pidMap as $hash_id => $container_pid_array) 
        {
            foreach($container_pid_array as $pid => $container_pid) 
            {
                //if process is dead...
                if(!posix_kill($pid, 0)) 
                {
                    unset(self::$_pidMap[$hash_id][$pid]);
                }    
            }    
        }    
    }

    /**
     * @brief    just implement a simple `tcp sever` with SELECT Multiplex for demostrating !!!
     *
     * @return   void
     */
    public function readyForAcceptClientConnection()
    {
        //要监听的三个sockets数组
        $read_socks = $write_socks =  array();
        $except_socks = NULL;  
        $read_socks[] = $this->_mainSocket;

        while(1)
        {
            pcntl_signal_dispatch();
            usleep(500000);

            //这两个数组会因stream_select()参数"传址"而被改变,所以用两个临时变量
            $tmp_reads = $read_socks;
            $tmp_writes = $write_socks;

            //stream_select(array &$read , array &$write , array &$except , int $tv_sec [, int $tv_usec = 0 ])
            //timeout 传 NULL 会一直阻塞直到有结果返回
            $select_result = @stream_select($tmp_reads, $tmp_writes, $except_socks, NULL);  
            if(false === $select_result) continue;

            foreach($tmp_reads as $read)
            {
                if($read == $this->_mainSocket)
                {
                    //监测到新的客户端连接请求
                    $new_socket = @stream_socket_accept($this->_mainSocket, 0, $remote_address);
                    if(!$new_socket) continue;

                    $local_address = $this->transport . '://' . stream_socket_get_name($new_socket, false);
                    self::log("local sever now in service: {$local_address}", self::LOG_LEVEL_DEBUG);
                    self::log("receive connect from client: {$remote_address}");

                    //把新的连接sokcet加入监听
                    $read_socks[] = $new_socket;
                    $write_socks[] = $new_socket;
                }
                else
                {
                    //从客户端读取数据, 此时一定会读到数组而不会产生阻塞
                    $msg = fread($read, 65535);  

                    if($msg === '')
                    {
                        //移除对该 socket 监听
                        foreach($read_socks as $k => $v)
                        {
                            if($v == $read) unset($read_socks[$k]);
                        }

                        foreach($write_socks as $k => $v)
                        {
                            if($v == $read) unset($write_socks[$k]);
                        }

                        fclose($read);
                    }
                    else
                    {
                        $msg = trim($msg);
                        if(empty($msg)) continue;
                        self::log("receive data from client: {$msg}");

                        //如果客户端可写,把数据回写给客户端
                        if(in_array($read, $tmp_writes))
                        {
                            $response = 'welcome to PHPForker: hi,' . $msg;
                            self::log("respone data to client: {$response}");
                            fwrite($read, $response . PHP_EOL);
                        }
                    }
                }
            }
        }
    }

}



