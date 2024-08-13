<?php

namespace App\Db;

class User extends \Bs\Db\User
{

    public function __construct()
    {
        parent::__construct();
        vd('creating App\Db\User');
    }


    public static function testCall()
    {
        vd('App\Db\User::testCall');
    }


}