<?php

$dsn = "mysql:host=localhost;port=3306;dbname=superpack_database";
$dbusername = "root";
$dbpassword = "password";


try {
    $pdo = new PDO ($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
    echo "Connection Failed: " . $e->getMessage(); 
}

//Q: driver could not be found even after uncommenting the extension=php_pdo_mysql.dll in php.ini
//A: The extension=php_pdo_mysql.dll should be uncommented in the php.ini file in the PHP folder, not in the php.ini file in the Apache folder.