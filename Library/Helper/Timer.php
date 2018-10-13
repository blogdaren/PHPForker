<?php
/**
 * @script   Timer.php
 * @brief    This file is part of PHPForker
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.blogdaren.com
 * @version  1.0.0
 * @modify   2018-10-13
 */

namespace PHPForker\Library\Helper;

use PHPForker\container;
use Exception;

/**
 * Timer
 *
 * Example:
 * Timer::add($time_interval, callback, array($arg1, $arg2..));
 */
class Timer
{
    /**
     * Tasks that based on ALARM signal
     * [
     *   run_time => [[$func, $args, $persistent, time_interval], [$func, $args, $persistent, time_interval], ...]],
     *   run_time => [[$func, $args, $persistent, time_interval], [$func, $args, $persistent, time_interval], ...]],
     *   ...
     * ]
     *
     * @var array
     */
    protected static $_tasks = array();

    /**
     * Init
     *
     * @return void
     */
    public static function init()
    {
        self::delAll();

        if(function_exists('pcntl_signal')) 
        {
            pcntl_signal(SIGALRM, array(__CLASS__, 'signalHandle'), false);
        }
    }

    /**
     * ALARM signal handler
     *
     * @return void
     */
    public static function signalHandle($signo)
    {
        pcntl_alarm(1);
        self::tick();
    }

    /**
     * Add a timer
     *
     * @param float    $time_interval
     * @param callable $func
     * @param mixed    $args
     * @param bool     $persistent
     * @return int/false
     */
    public static function add($time_interval, $func, $args = array(), $persistent = true)
    {
        if($time_interval <= 0) 
        {
            Container::safeEcho(new Exception("bad time_interval"));
            return false;
        }

        if(!is_callable($func)) 
        {
            Container::safeEcho(new Exception("not callable"));
            return false;
        }

        if(empty(self::$_tasks)) 
        {
            pcntl_alarm(1);
        }

        $time_now = time();
        $run_time = $time_now + $time_interval;

        if(!isset(self::$_tasks[$run_time])) 
        {
            self::$_tasks[$run_time] = array();
        }

        self::$_tasks[$run_time][] = array($func, (array)$args, $persistent, $time_interval);

        return 1;
    }


    /**
     * Tick
     *
     * @return void
     */
    public static function tick()
    {
        if(empty(self::$_tasks)) 
        {
            pcntl_alarm(0);
            return;
        }

        $time_now = time();
        foreach (self::$_tasks as $run_time => $task_data) 
        {
            if($time_now >= $run_time) 
            {
                foreach ($task_data as $index => $one_task) 
                {
                    $task_func     = $one_task[0];
                    $task_args     = $one_task[1];
                    $persistent    = $one_task[2];
                    $time_interval = $one_task[3];

                    try {
                        call_user_func_array($task_func, $task_args);
                    } catch (\Exception $e) {
                        Container::safeEcho($e);
                    }

                    $persistent && self::add($time_interval, $task_func, $task_args);
                }

                unset(self::$_tasks[$run_time]);
            }
        }
    }

    /**
     * remove a timer
     *
     * @param mixed $timer_id
     * @return bool
     */
    public static function del($timer_id)
    {
        return false;
    }

    /**
     * Remove all timers.
     *
     * @return void
     */
    public static function delAll()
    {
        self::$_tasks = array();
        pcntl_alarm(0);
    }
}
