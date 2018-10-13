# PHPForker

## What is PHPForker?
A simple Multi-Process programming skeleton written in PHP and based on Workerman, which remove the part of Network Event Library, it aims at two aspects by programming in person: 
* help us study PHP Multi-Process programming 
* help us find out how Workerman core works 

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


## Related links and thanks

* [http://www.blogdaren.com](http://www.blogdaren.com)
* [https://www.workerman.net](https://www.workerman.net)

