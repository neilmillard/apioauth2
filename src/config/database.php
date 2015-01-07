<?php
return array (
    'driver'    => 'mysql',
    'host'      => $_ENV['DBHOST'],
    'database'  => $_ENV['DBNAME'],
    'username'  => $_ENV['DBUSER'],
    'password'  => $_ENV['DBPASS'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
);