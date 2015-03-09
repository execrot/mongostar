<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once "vendor/autoload.php";

Mongostar_Model::setConfig(array(
    'default' => array(
        'connection' => array(
            'server' => "localhost:27017",
            'db' => 'hitman'
        ),
    )
));

/**
 * @property MongoId $id
 * @property string  $name
 * @property int     $age
 *
 * @method static User[] fetchAll(array $cond = null, array $sort = null, $count = null, $offset = null, $hint = NULL)
 * @method static User|null fetchOne(array $cond = null, array $sort = null)
 * @method static User fetchObject(array $cond = null, array $sort = null)
 */
class User extends Mongostar_Model {}

$newUser = User::fetchObject(array(
    'id' => null
));

echo $newUser->name . "<br>";

die("lsjdlksj");

if (rand(0,1)) {
    // User::remove();
}

$user = User::fetchOne(array(
    'id' => '54de8e1e9cd81ebf330041bc'
));

echo $user->name . "<br>";

$user->name = "User_name - " . rand(1000, 9999);
$user->save();

$user = User::fetchOne(array(
    'id' => '54de8e1e9cd81ebf330041bc'
));

echo $user->name . "<br>";

die();

$user->name = "Name: ".rand(1000, 9999);
$user->save();

echo "last name: " .$user->name . "<br/>";

$user->name = "Name-edited";
$user->save();

foreach(User::fetchAll() as $user)
{
    echo $user->id . ' - ' . $user->name . "<br>";
}

echo "count: " . User::getCount() . "<br>";