# PHPForker

## What is it
A simple Multi-Process programming skeleton written in PHP and based on [Workerman](https://www.workerman.net), which remove the part of Network Event Library, it aims at two aspects by programming personally：
* `Help us study PHP Multi-Process programming` 
* `Help us find out how Workerman core works` 

## Requires
* \>= PHP 5.3
* A POSIX compatible operating system (Linux, OSX, BSD)  
* POSIX extensions for PHP  
* PCNTL extensions for PHP  

## Usage

```php
<?php
require_once dirname(__DIR__). '/Autoloader.php';

use PHPForker\Container;

//imitate...
$totalContainer = 2;
for($i = 1; $i <= $totalContainer; $i++)
{
    $name = "demo-" . $i;
    $socket_name = "tcp://0.0.0.0:2" . str_pad($i, 3, '0', STR_PAD_LEFT);
    $box = new Container($socket_name);
    $box->setPublicProps([
        'name' => $name,
        'count' => 2,
        'user' => 'root',
    ]);
}
```

## Demostrate
![demo1](https://github.com/blogdaren/PHPForker/blob/master/Image/demo1.png)
----
![demo2](https://github.com/blogdaren/PHPForker/blob/master/Image/demo2.png)
----
![demo3](https://github.com/blogdaren/PHPForker/blob/master/Image/demo3.png)
----
![demo4](https://github.com/blogdaren/PHPForker/blob/master/Image/demo4.png)
----
![demo5](https://github.com/blogdaren/PHPForker/blob/master/Image/demo5.png)


## Related links and thanks

* [http://www.blogdaren.com](http://www.blogdaren.com)
* [https://www.workerman.net](https://www.workerman.net)

