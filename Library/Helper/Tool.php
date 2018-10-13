<?php
/**
 * @script   tool.php
 * @brief    PHP Toolbox
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.blogdaren.com
 * @version  1.0.0
 * @modify   2018-09-26
 */

namespace PHPForker\Library\Helper;

class Tool
{
	/**
	 * 检测电子邮件
	 *
	 * @param  string  $email
	 *
	 * @return boolean
	 */
	static public function checkEmail($email)
	{
		if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email))
		{
			list($username, $domain)= explode('@', $email);

			if(!self::checkdnsrr($domain,'MX'))
			{
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * 检测手机号码
	 *
	 * @param  string  $mobile
	 *
	 * @return boolean
	 */
	static public function checkMobile($mobile)
	{
		if(!preg_match("/^(1[3-9])\d{9}$/", $mobile))
		{
			return false;
		}

		return true;
	}

	/**
	 * 检测固话
	 *
	 * @param  string  $mobile
	 *
	 * @return boolean
	 */
	static public function checkTelphone($mobile)
	{
		if(!preg_match("/^(0[0-9]{2,3}-)?([2-9][0-9]{6,7})+(-[0-9]{1,4})?$/", $mobile))
		{
			return false;
		}

		return true;
	}

	/**
	 * 创建多级目录
	 *
	 * @param  string  $dir	层级目录： a/b/c
	 * @param  string  $mode 权限
	 *
	 * @return boolean
	 */
	static public function createMultiDirectory($dir, $mode = 0777)
	{
		if(is_dir($dir))
		{
			return true;
		}

		if(!self::createMultiDirectory(dirname($dir), $mode))
		{
			return false;
		}

		return mkdir($dir, $mode);
	}

    /**
     * 检验一个数据项是否为正整数
     *
     * @param  string  $input
     *
     * @return boolean
     */
    static public function checkIsInt($input)
    {
        return preg_match('/^[1-9][0-9]*$/is', $input, $matches);
    }

    /**
     * 检验一个数据项是否为正整数或0
     *
     * @param  string  $input
     *
     * @return boolean
     */
    static public function checkIsIntOrZero($input)
    {
        return preg_match('/^([1-9][0-9]*|0)$/is', $input, $matches);
    }

    /**
     * 检验一个数据项是否为浮点数
     *
     * @param  string  $input
     *
     * @return boolean
     */
    static public function checkIsFloat($input)
    {
        return preg_match('/^\d+.\d+$/', $input, $matches);
    }

    /**
     * 检验一个数据项是否为整形或浮点数
     *
     * @param  string  $input
     *
     * @return boolean
     */
    static public function checkIsIntFloat($input)
    {
        return preg_match('/^\d+(\.[0-9]+|\d*)$/', $input, $matches);
    }

    /**
     * 检验一个数据项是否为整形或浮点数
     *
     * @param  string  $input
     *
     * @return boolean
     */
    static public function checkIsIntFloatZero($input)
    {
        return preg_match('/^\d+(\.[0]+)?$/', $input, $matches);
    }

    /**
     * 获取毫秒数时间
     *
     * @return int
     */
    static public function getMicrosecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 获取人性化的通用日志记录时间
     *
     * @param  int  $time
     *
     * @return string
     */
    static public function getHumanLogTime($time = 0)
    {
        //如果$time <= 0 则路由到getNowTime()
        if($time <= 0 || !is_numeric($time)) 
        {
            return self::getNowTime(true);
        }

        //这行是兼容之前有值的$time
        return date("Y-m-d H:i:s", $time);
    }

    /**
     * 获取当前系统时间
     *
     * @param  int  $human      是否返回人性化时间
     * @param  int  $given_time 手动指定时间
     *
     * @return int|string
     */
    static public function getNowTime($human = false, $given_time = null)
    {
        $his = date("H:i:s", time());
        $ymd_his = date("Y-m-d H:i:s", time());

        //读取手动指定时间
        $is_given_time_valid = self::checkDateTime($given_time);
        if(!empty($given_time) && $is_given_time_valid)
        {
            $int_given_time = strtotime($given_time);
            $hand_time = $human ? $given_time : $int_given_time;
            return $hand_time;
        }

        //读取配置时间
        $config_debug_time = '';
        $is_time_valid = self::checkDateTime($config_debug_time);
        if(empty($config_debug_time) || !$is_time_valid) 
        {
            $now_time = $human ? $ymd_his : time();
            return $now_time;
        }

        $tmp_time = strtotime($config_debug_time);
        $config_debug_time = date("Y-m-d H:i:s", $tmp_time); 
        $now_time = $human ? $config_debug_time : strtotime($config_debug_time);

        return $now_time;
    }

    /**
     * @brief    arrayRecursive     
     *
     * @param    string  $array
     * @param    string  $function
     * @param    string  $apply_to_keys_also
     *
     * @return   boolean
     */
    static function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000)
        {
            die('possible deep recursion attack');
        }

        foreach ($array as $key => $value)
        {
            if (is_array($value)) {
                self::arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }

        $recursive_counter--;
    }

    /**
     *  将数组转换为JSON字符串（兼容中文）
     *
     *  注： PHP-5.4以上版本可直接使用内置函数: json_encode("中文", JSON_UNESCAPED_UNICODE);
     *
     *  @param  $array   待转换的数组
     *
     *  @return string|bool
     */
    static public function toJson($array)
    {
        if(!is_array($array)) $array = array($array);

        self::arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);

        return urldecode($json);
    }

    /**
     * 校验日期格式：支持多种有效的日期格式
     *
     * @param  int  $datetime       外部日期参数
     *
     * @return bool
     */
	static function checkDateTime($datetime)
    {
		if("0000-00-00 00:00:00" == $datetime) return true;
		
		//合法格式
		$dateformat = array('Y-m-d H:i:s','Y-m-d','Y-m-d H:i');
		foreach($dateformat as $val)
		{
 			if($datetime == date($val,strtotime($datetime))){
  				return true;
 			}
		}
		return false;	
    }	
    
    /**
     * 校验日期格式：支持多种有效的日期格式
     *
     * @param  int  $date       外部日期参数
     *
     * @return bool
     */
    static function checkDateFormat($date)
    {
        if("0000-00-00" == $date) return true;

        if(preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches))
        {
            if(checkdate($matches[2], $matches[3], $matches[1]))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * 仅用于日志调试
     *
     * @param  string|array $content    日志内容
     * @param  bool         $json       是否采用JSON格式存储
     * @param  bool         $append     是否采用追加模式记录日志
     * @param  string       $filename   文件名(考虑到权限问题,统统压入/tmp目录)
     *
     * @return int
     */
    static public function debug($content, $json = true, $append = true, $filename = "debug", $base_dir = "/tmp/")
    {
        if(empty($filename) || empty($content)) return 0;

        $filename = str_replace("/", "", $filename);
        $filename = str_replace("\\", "", $filename);

        if("linux" == strtolower(PHP_OS))
        {
            $dir = !empty($base_dir) ? $base_dir : (defined("CACHE_DIR") ? CACHE_DIR . "/logs/" : "/tmp/");
        }
        else
        {
            $dir = defined("CACHE_DIR") ? CACHE_DIR : 'C:\\';
        }

        $rs = true;
        if(!is_dir($dir))
        {
            $rs = @mkdir($dir, 0700, true);
        }

        if(empty($rs)) $dir = "/tmp/";
        $filename = $dir . DIRECTORY_SEPARATOR . $filename . ".log";

        if(!empty($json))
        {
            $content = self::toJson($content);
        }
        else
        {
            $content = var_export($content, true);
        }

        $client_ip = self::getClientIp();
        $log_time = self::getHumanLogTime();
        $content = "【" . $log_time . " | {$client_ip}】" . $content .  "\r\n";

        if(empty($append))
        {
            $rs = file_put_contents($filename, $content);
        }
        else
        {
            $rs = file_put_contents($filename, $content, FILE_APPEND);
        }

        return $rs;
    }

    /**
     * ajax-B/S数据交互表示
     *
     * @param  string       $error_code 错误码
     * @param  string       $error_msg  错误信息
     * @param  array|string $extra_msg  额外数据
     *
     * @usage
     *  $error_code = '-1';
     *  $error_msg = '验证码错误';
     *  $extra_msg = array('k1' => "v1", 'k2' => 'v2');
     *  $data = Tool::stop($error_code, $error_msg, $extra_msg);
     * @usage
     *
     * @return exit
     */
    static public function stop($error_code = '', $error_msg = '', $extra_msg = array())
    {
        if($error_code == '0')
        {
            $error_code = '0';
            empty($error_msg) && $error_msg = '操作成功';
        }

        !is_array($extra_msg) && $extra_msg = array($extra_msg);

        $data = array(
            'error_msg' =>  urlencode($error_msg),
            'error_code'=>  $error_code,
            'extra_msg' =>  $extra_msg,
        );

        $data = array_merge($data, $extra_msg);

        exit(urldecode(json_encode($data)));
    }

    /**
     * 非ajax-B/S数据交互表示
     *
     * @param  string       $error_code 错误码
     * @param  string       $error_msg  错误信息
     * @param  array|string $extra_msg  额外数据
     *
     * @usage
     * $error_code = '-1';
     * $error_msg = '验证码错误';
     * $extra_msg = array('k1' => "v1", 'k2' => 'v2');
     * $data = Tool::throwback($error_code, $error_msg, $extra_msg);
     * @usage
     *
     * @return exit
     */
    static public function throwback($error_code = '', $error_msg = '', $extra_msg = array())
    {
        if($error_code == '0' || $error_code === '0000')
        {
            $error_code == '0' && $error_code = '0';
            $error_code === '0000' && $error_code = '0000';
            empty($error_msg) && $error_msg = '操作成功';
        }

        !is_array($extra_msg) && $extra_msg = array($extra_msg);

        $data = array(
            'error_code'=>  $error_code,
            'error_msg' =>  $error_msg,
            'extra_msg' =>  $extra_msg,
        );

        $data = array_merge($data, $extra_msg);

        return $data;
    }
    
    /**
     * 获取浮点级别的秒数
     *
     * @return float 
     */
    static public function getFloatMicrotime()
    {
        return microtime(true);

        list($t1, $t2) = explode(" ", microtime());

        return ((float)$t1 + (float)$t2);

        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);  
    }

    /**
     * 根据某个字段从一个2维数组中格式化出一个1维数组: 仅针对2维数组
     *
     * @param  array   $input_array
     * @param  string  $field
     *
     * @return array
     */
    static function rebuildArrayByOneField($input_array, $field)
    {
        $output = array();
        $depth = self::getArrayDepth($input_array);

        if(!is_array($input_array) || $depth <> 2 || !is_string($field))
        {
            return $output;
        }

        foreach($input_array as $key => $value)
        {
            if(array_key_exists($field, $value))            
            {
                $output[] = $value[$field];
            }
        }
    
        return $output; 
    }

    /**
     * 计算数组的维数即深度 
     *
     * @param  int  $array
     *
     * @return int 
     */
    static function getArrayDepth($array) 
    {
        $max_depth = 1;

        if(!is_array($array)) return $max_depth;

        foreach($array as $value) 
        {
            if(is_array($value)) 
            {
                $depth = self::getArrayDepth($value) + 1;

                if($depth > $max_depth) 
                {
                    $max_depth = $depth;
                }   
            }   
        }   

        return $max_depth;
    }

    /**
     * 获取格式化后的浮点数：注意本函数是非四舍五入的!!!
     *
     * @param  int  $f      待格式化的浮点数
     * @param  int  $len    小数点后保留位数
     *
     * @return float 
     */
    static function getNoRoundFloatValue($number, $len = 2)
    {
        $len <= 0 && $len = 2;
        if($number <= 0) return 0;

        $data = explode(".", $number);
        $left = $data[0];

        if(empty($data[1]))
        {
            $right = str_repeat("0", $len);
        }
        else
        {
            $right = strlen($data[1]) < $len ? str_pad($data[1], $len, "0") : substr($data[1], 0, $len);
        }

        $float_number = $left . "." . $right;

        return $float_number;
    }

    /**
     * 生成全局唯一的UUID
     *
     * @return string 
     */
    static public function createUuid()
    {
        if (function_exists('com_create_guid'))
        {
            return com_create_guid();
        }
        else
        {
            mt_srand((double)microtime()*10000);
            //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"

            return $uuid;
        }
    }
    
    /**
     * 判断IP是否为私有IP或系统保留IP
     *
     * @param  string  $ip
     *
     * @return boolean 
     */
    static public function isPrivateIP($ip) 
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * 校验IP是否有效
     *
     * @param  string  $ip
     *
     * @return boolean 
     */
    static public function isValidIP($ip) 
    {
        if(empty($ip)) return false;

        return filter_var($ip, FILTER_VALIDATE_IP) ? true : false;
    }
    
    /**
     * @brief    time2second    
     *
     * @param    string  $time
     * @param    string  $is_log
     *
     * @return   mixed
     */
    public static function time2second($time, $is_log = true)
    {
        if(is_numeric($time)) 
        {
            $value = array(
                "years" => 0, 
                "days" => 0, 
                "hours" => 0,
                "minutes" => 0, 
                "seconds" => 0,
            );

            if($time >= 31556926)
            {
                $value["years"] = floor($time/31556926);
                $time = ($time%31556926);
            }

            if($time >= 86400)
            {
                $value["days"] = floor($time/86400);
                $time = ($time%86400);
            }

            if($time >= 3600)
            {
                $value["hours"] = floor($time/3600);
                $time = ($time%3600);
            }

            if($time >= 60)
            {
                $value["minutes"] = floor($time/60);
                $time = ($time%60);
            }

            $value["seconds"] = floor($time);

            if ($is_log) 
            {
                $t = $value["days"] ."d ". $value["hours"] ."h ". $value["minutes"] ."m ".$value["seconds"]."s";
            }
            else 
            {
                $t = $value["days"] ." days ". $value["hours"] ." hours ". $value["minutes"] ." minutes";
            }

            return $t;
        }

        return false;
    }
}




