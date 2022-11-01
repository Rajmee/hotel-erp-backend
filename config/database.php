<?php

$database_config = [
 'driver'=>'mysql',
    // 'host'=>'162.0.213.75',
    'host'=>'194.233.78.193',
    // 'host'=>'36.255.68.114',
    'database'=>'managebeds',
    'username'=>'root',
    'password'=>'Dth@2022',
    'charset'=>'utf8',
    'collation'=>'utf8_unicode_ci',
    'prefix'=>''

];

$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($database_config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;
