<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Users extends Eloquent
{
    protected $hidden = array('id','created_at','updated_at', 'password');

    public function get($username = null)
    {
        $query = Capsule::table('users')->select(['username', 'password', 'name', 'email', 'photo']);

        if ($username !== null) {
            $query->where('username', '=', $username);
        }

        $result = $query->get();

        if (count($result) > 0) {
            return $result;
        }

        return;
    }
}
