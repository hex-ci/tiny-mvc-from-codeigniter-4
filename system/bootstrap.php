<?php

// Path to root
define('ROOTPATH', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

// Load config file
require ROOTPATH . 'application/Config/Config.php';
$config1 = new App\Config\Config();

define('BASEPATH', realpath(ROOTPATH.$config1->systemDirectory).DIRECTORY_SEPARATOR);
define('APPPATH', realpath(ROOTPATH.$config1->applicationDirectory).DIRECTORY_SEPARATOR);

require BASEPATH . 'Autoloader.php';

$loader = new \System\Autoloader();
$loader->register();

$app = new \System\Core($config1);

return $app;

/* End of file bootstrap.php */
/* Location: ./system/bootstrap.php */
