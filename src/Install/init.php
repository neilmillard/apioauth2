<?php

namespace App\Install;

use Illuminate\Database\Capsule\Manager as Capsule;

include __DIR__.'/../../vendor/autoload.php';

// Setup config
if(empty(getenv('APIKEY'))){
    $dotenv = new \Dotenv\Dotenv;
    $dotenv->load(__DIR__ . '/../../');
}
$configPath = __DIR__ . '/../config';
$filesystem = new \Illuminate\Filesystem\Filesystem;
$loader = new \Illuminate\Config\FileLoader($filesystem, $configPath);
$config = new \Illuminate\Config\Repository($loader, 'production');

//setup database
$capsule = new Capsule();
$capsule->addConnection( $config->get('database') );
$capsule->setAsGlobal();
//$capsule->bootEloquent();

/******************************************************************************/
print 'Creating candles table'.PHP_EOL;
if (!Capsule::schema()->hasTable('users')){
    Capsule::schema()->create('candles', function($table) {
        /** @var \Illuminate\Database\Schema\Blueprint $table */
        $table->increments('id');
        $table->date('date');
        $table->string('candletime');
        $table->string('instrument');
        $table->double('open');
        $table->double('high');
        $table->double('low');
        $table->double('close');
        $table->tinyInteger('complete');
        $table->datetime('created_at');
        $table->datetime('updated_at');
        $table->index('instrument');
    });
}

print 'Creating users table'.PHP_EOL;

if (!Capsule::schema()->hasTable('users')){
    Capsule::schema()->create('users', function($table)
    {
        $table->increments('id');
        $table->string('email');
        $table->string('password');
        $table->text('permissions')->nullable();
        $table->boolean('activated')->default(0);
        $table->string('activation_code')->nullable();
        $table->timestamp('activated_at')->nullable();
        $table->timestamp('last_login')->nullable();
        $table->string('persist_code')->nullable();
        $table->string('reset_password_code')->nullable();
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->timestamps();
        // We'll need to ensure that MySQL uses the InnoDB engine to
        // support the indexes, other engines aren't affected.
        $table->engine = 'InnoDB';
        $table->unique('email');
        $table->index('activation_code');
        $table->index('reset_password_code');
    });
}
/**
 * create table for sentry group
 */
if (!Capsule::schema()->hasTable('groups')){
    Capsule::schema()->create('groups', function($table)
    {
        $table->increments('id');
        $table->string('name');
        $table->text('permissions')->nullable();
        $table->timestamps();
        // We'll need to ensure that MySQL uses the InnoDB engine to
        // support the indexes, other engines aren't affected.
        $table->engine = 'InnoDB';
        $table->unique('name');
    });
}
/**
 * create user-group relation
 */
if (!Capsule::schema()->hasTable('users_groups')){
    Capsule::schema()->create('users_groups', function($table)
    {
        $table->integer('user_id')->unsigned();
        $table->integer('group_id')->unsigned();
        // We'll need to ensure that MySQL uses the InnoDB engine to
        // support the indexes, other engines aren't affected.
        $table->engine = 'InnoDB';
        $table->primary(array('user_id', 'group_id'));
    });
}
/**
 * create throttle table
 */
if (!Capsule::schema()->hasTable('throttle')){
    Capsule::schema()->create('throttle', function($table)
    {
        $table->increments('id');
        $table->integer('user_id')->unsigned();
        $table->string('ip_address')->nullable();
        $table->integer('attempts')->default(0);
        $table->boolean('suspended')->default(0);
        $table->boolean('banned')->default(0);
        $table->timestamp('last_attempt_at')->nullable();
        $table->timestamp('suspended_at')->nullable();
        $table->timestamp('banned_at')->nullable();
        // We'll need to ensure that MySQL uses the InnoDB engine to
        // support the indexes, other engines aren't affected.
        $table->engine = 'InnoDB';
        $table->index('user_id');
    });
}

//Capsule::schema()->create('users', function ($table) {
//    $table->increments('id');
//    $table->string('username');
//    $table->string('password');
//    $table->string('name');
//    $table->string('email');
//    $table->string('photo');
//    $table->datetime('created_at');
//    $table->datetime('updated_at');
//});

Capsule::table('users')->insert([
    'email'         =>  'admin@api.neilmillard.com',
    'password'      =>  password_hash('cider', PASSWORD_DEFAULT),
    'first_name'    =>  'Website',
    'last_name'     => 'Administrator',
    'activated'     =>  '1',
    'permissions'   => '{ "admin" : 1 }',
]);

Capsule::table('users')->insert([
    'email'         =>  'neil@neilmillard.com',
    'password'      =>  password_hash('custard', PASSWORD_DEFAULT),
    'first_name'    =>  'Neil',
    'last_name'     => 'Millard',
    'activated'     => '1',
    'permissions'   => '{ "admin" : 1 }',
]);

/******************************************************************************/

print 'Creating clients table'.PHP_EOL;

Capsule::schema()->create('oauth_clients', function ($table) {
    $table->string('id');
    $table->string('secret');
    $table->string('name');
    $table->primary('id');
});

Capsule::table('oauth_clients')->insert([
    'id'        =>  'fxmasterclient',
    'secret'    =>  'topSecret5555',
    'name'      =>  'FXMaster Client',
]);

/******************************************************************************/

print 'Creating client redirect uris table'.PHP_EOL;

Capsule::schema()->create('oauth_client_redirect_uris', function ($table) {
    $table->increments('id');
    $table->string('client_id');
    $table->string('redirect_uri');
});

Capsule::table('oauth_client_redirect_uris')->insert([
    'client_id'     =>  'fxmasterclient',
    'redirect_uri'  =>  'http://www.fxmaster.dev/redirect',
]);

/******************************************************************************/

print 'Creating scopes table'.PHP_EOL;

Capsule::schema()->create('oauth_scopes', function ($table) {
    $table->string('id');
    $table->string('description');
    $table->primary('id');
});

Capsule::table('oauth_scopes')->insert([
    'id'            =>  'basic',
    'description'   =>  'Basic details about your account',
]);

Capsule::table('oauth_scopes')->insert([
    'id'            =>  'email',
    'description'   =>  'Your email address',
]);

Capsule::table('oauth_scopes')->insert([
    'id'            =>  'photo',
    'description'   =>  'Your photo',
]);

Capsule::table('oauth_scopes')->insert([
    'id'            =>  'useradmin',
    'description'   =>  'User Admin rights. Create users etc',
]);

/******************************************************************************/

print 'Creating sessions table'.PHP_EOL;

Capsule::schema()->create('oauth_sessions', function ($table) {
    $table->increments('id');
    $table->string('owner_type');
    $table->string('owner_id');
    $table->string('client_id');
    $table->string('client_redirect_uri')->nullable();

    $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
});

Capsule::table('oauth_sessions')->insert([
    'owner_type'    =>  'client',
    'owner_id'      =>  'fxmasterclient',
    'client_id'     =>  'fxmasterclient',
]);

Capsule::table('oauth_sessions')->insert([
    'owner_type'    =>  'user',
    'owner_id'      =>  '1',
    'client_id'     =>  'fxmasterclient',
]);

Capsule::table('oauth_sessions')->insert([
    'owner_type'    =>  'user',
    'owner_id'      =>  '2',
    'client_id'     =>  'fxmasterclient',
]);

/******************************************************************************/

print 'Creating access tokens table'.PHP_EOL;

Capsule::schema()->create('oauth_access_tokens', function ($table) {
    $table->string('access_token')->primary();
    $table->unsignedinteger('session_id');
    $table->integer('expire_time');

    $table->foreign('session_id')->references('id')->on('oauth_sessions')->onDelete('cascade');
});

Capsule::table('oauth_access_tokens')->insert([
    'access_token'  =>  'iamgod',
    'session_id'    =>  '1',
    'expire_time'   =>  time() + 86400,
]);

Capsule::table('oauth_access_tokens')->insert([
    'access_token'  =>  'iamneil',
    'session_id'    =>  '2',
    'expire_time'   =>  time() + 86400,
]);

/******************************************************************************/

print 'Creating refresh tokens table'.PHP_EOL;

Capsule::schema()->create('oauth_refresh_tokens', function ($table) {
    $table->string('refresh_token')->primary();
    $table->integer('expire_time');
    $table->string('access_token');

    $table->foreign('access_token')->references('access_token')->on('oauth_access_tokens')->onDelete('cascade');
});

/******************************************************************************/

print 'Creating auth codes table'.PHP_EOL;

Capsule::schema()->create('oauth_auth_codes', function ($table) {
    $table->string('auth_code')->primary();
    $table->unsignedinteger('session_id');
    $table->integer('expire_time');
    $table->string('client_redirect_uri');

    $table->foreign('session_id')->references('id')->on('oauth_sessions')->onDelete('cascade');
});

/******************************************************************************/

print 'Creating oauth access token scopes table'.PHP_EOL;

Capsule::schema()->create('oauth_access_token_scopes', function ($table) {
    $table->increments('id');
    $table->string('access_token');
    $table->string('scope');

    $table->foreign('access_token')->references('access_token')->on('oauth_access_tokens')->onDelete('cascade');
    $table->foreign('scope')->references('id')->on('oauth_scopes')->onDelete('cascade');
});

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamgod',
    'scope'         =>  'basic',
]);

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamgod',
    'scope'         =>  'email',
]);

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamgod',
    'scope'         =>  'photo',
]);

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamgod',
    'scope'         =>  'useradmin',
]);

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamneil',
    'scope'         =>  'email',
]);

Capsule::table('oauth_access_token_scopes')->insert([
    'access_token'  =>  'iamneil',
    'scope'         =>  'photo',
]);

/******************************************************************************/

print 'Creating oauth auth code scopes table'.PHP_EOL;

Capsule::schema()->create('oauth_auth_code_scopes', function ($table) {
    $table->increments('id');
    $table->string('auth_code');
    $table->string('scope');

    $table->foreign('auth_code')->references('auth_code')->on('oauth_auth_codes')->onDelete('cascade');
    $table->foreign('scope')->references('id')->on('oauth_scopes')->onDelete('cascade');
});

/******************************************************************************/

print 'Creating oauth session scopes table'.PHP_EOL;

Capsule::schema()->create('oauth_session_scopes', function ($table) {
    $table->increments('id');
    $table->unsignedinteger('session_id');
    $table->string('scope');

    $table->foreign('session_id')->references('id')->on('oauth_sessions')->onDelete('cascade');
    $table->foreign('scope')->references('id')->on('oauth_scopes')->onDelete('cascade');
});
