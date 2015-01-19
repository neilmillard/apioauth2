<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Users extends Eloquent
{
    protected $hidden = array('id','created_at','updated_at', 'password');

    public function get($email = null)
    {
        $query = Capsule::table('users')->select(['email', 'password', 'first_name', 'last_name']);

        if ($email !== null) {
            $query->where('email', '=', $email);
        }

        $result = $query->get();

        if (count($result) > 0) {
            return $result;
        }

        return;
    }
}
