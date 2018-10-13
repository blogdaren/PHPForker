<?php
require_once dirname(__DIR__). '/Autoloader.php';

use PHPForker\Container;

//imitate...
$totalContainer = 2;
for($i = 1; $i <= $totalContainer; $i++)
{
    $name = "demo-" . $i;
	$socket_name = "tcp://0.0.0.0:2" . str_pad($i, 3, '0', STR_PAD_LEFT);
    //$socket_name = "";
	$box = new Container($socket_name);
    $box->setPublicProps([
        'name' => $name,
        'count' => 2,
        'user' => 'root',
    ]);

}

Container::start();
