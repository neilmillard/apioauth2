<?php
namespace App;

define('ROOT_PATH'  , __DIR__.'/../');
define('VENDOR_PATH', ROOT_PATH.'vendor/');
define('APP_PATH'   , ROOT_PATH.'src/');

include VENDOR_PATH . 'autoload.php';
use League\OAuth2\Server\ResourceServer;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Model;
use App\Storage;

/*
* @property \Slim\Http\Response $slimResponse
* @property \Slim\Http\Request  $slimRequest
*/
Class Bootstrap
{
    /** @var \RkaSc\Slim */
    private $slimApp;
    public $version = "v1";

    function __construct()
    {
        // Setup environment with production as default
        if(empty(getenv('APIKEY'))){
            $dotenv = new \Dotenv\Dotenv;
            $dotenv->load(ROOT_PATH);
        }
        define('ENVIRONMENT', (getenv('SLIM_ENV') ? getenv('SLIM_ENV') : 'production'));

        // Setup Slim
        $this->slimApp = new \RkaSc\Slim(
            [
                'mode' => ENVIRONMENT,
                'templates.path'    => APP_PATH . 'Views',
                'debug' => false        //this isn't needed, exceptions check the environment.
            ]
        );

        $this->slimApp->add(new \Slim\Middleware\SessionCookie(array('secret' => 'myappsecret')));

        // enable json parsing middleware
        $this->slimApp->add(new \Slim\Middleware\ContentTypes());

        //setup config
        $this->setupConfig();

        // Create monolog logger and store logger in container as singleton
        // (Singleton resources retrieve the same log resource definition each time)
        $this->slimApp->container->singleton('log', function () {
            $log = new \Monolog\Logger('fxmaster-data');
            $log->pushHandler(new \Monolog\Handler\StreamHandler(APP_PATH.'logs/'.ENVIRONMENT.'_'.date('Y-m-d').'.log', \Monolog\Logger::DEBUG));
            return $log;
        });

        /* setup json errors if needed */
        $app = $this->slimApp;
        // JSON friendly errors
        // NOTE: debug must be false
        // or default error template will be printed
        $app->error(function (\Exception $e) use ($app) {
            $mediaType = $app->request->getMediaType();
            $isAPI = (bool) preg_match('|^/api/v.*$|', $app->request->getPath());
            // Standard exception data
            $error = array(
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            );
            // Graceful error data for production mode
            if (!in_array(
                    get_class($e),
                    array('App\\Exception', 'App\\Exception\ValidationException')
                )
                && 'production' === $app->config('mode')) {
                $error['message'] = 'There was an internal error';
                unset($error['file'], $error['line']);
            }
            // Custom error data (e.g. Validations)
            if (method_exists($e, 'getData')) {
                $errors = $e->getData();
            }
            if (!empty($errors)) {
                $error['errors'] = $errors;
            }
            $app->log->error($e->getMessage());
            if ('application/json' === $mediaType || true === $isAPI) {
                $app->response->headers->set(
                    'Content-Type',
                    'application/json'
                );
                echo json_encode($error, JSON_PRETTY_PRINT);
            } else {
                echo '<html>
        <head><title>Error</title></head>
        <body><h1>Error: ' . $error['code'] . '</h1><p>'
                    . $error['message']
                    .'</p></body></html>';
            }
        });

        $app->notFound(function () use ($app) {
            $mediaType = $app->request->getMediaType();

            $isAPI = (bool) preg_match('|^/api/v.*$|', $app->request->getPath());

            if ('application/json' === $mediaType || true === $isAPI) {
                $app->response->headers->set(
                    'Content-Type',
                    'application/json'
                );
                echo json_encode(
                    array(
                        'code' => 404,
                        'message' => 'Not found'
                    )
                );
            } else {
                echo '<html>
        <head><title>404 Page Not Found</title></head>
        <body><h1>404 Page Not Found</h1><p>The page you are
        looking for could not be found.</p></body></html>';
            }
        });

        $this->initialiseDatabase();
        $this->initialiseValidator();

        // Create the Sentry alias
        class_alias('Cartalyst\Sentry\Facades\Native\Sentry', 'Sentry');

        $this->initialiseOauth2();
        $this->initialiseRouting();
        //$this->run();
        return $this;
    }

    function initialiseOauth2()
    {
        // Set up the OAuth 2.0 resource server
        $this->slimApp->container->singleton('sessionStorage', function () {
            return new Storage\SessionStorage();
        });
        $this->slimApp->container->singleton('accessTokenStorage', function () {
            return new Storage\AccessTokenStorage();
        });
        $this->slimApp->container->singleton('clientStorage', function () {
            return new Storage\ClientStorage();
        });
        $this->slimApp->container->singleton('scopeStorage', function () {
            return new Storage\ScopeStorage();
        });
        $this->slimApp->container->singleton('oauthServer', function ($c) {
            return new ResourceServer(
                $c['sessionStorage'],
                $c['accessTokenStorage'],
                $c['clientStorage'],
                $c['scopeStorage']
            );
        });

        $this->slimApp->container->singleton('authorizationServer', function () {
            $server = new AuthServer();
            return $server->getServer();
        });
    }

    function setupConfig()
    {
        $this->slimApp->container->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem();
        });

        $this->slimApp->container->singleton('loader', function ($c) {
            $configPath = APP_PATH . 'config';
            return new \Illuminate\Config\FileLoader($c['files'], $configPath);
        });

        $this->slimApp->container->singleton('config', function ($c) {
            return new \Illuminate\Config\Repository($c['loader'], ENVIRONMENT);
        });

        $this->slimApp->config('debug', ENVIRONMENT!='production');
    }

    function initialiseDatabase()
    {
        $capsule = new Capsule();

        $capsule->addConnection( $this->slimApp->config->get('database') );

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function initialiseValidator()
    {
        $this->slimApp->container->singleton('validator', function () {
            $validation = new \App\Validation\Manager('en', APP_PATH.'/lang');
            $validation->setConnection($this->slimApp->config->get('database') );
            return $validation;
        });


    }

    function initialiseRouting()
    {
        $slimObj = $this->slimApp;

        $checkToken = function () {

            return function()
            {
                $app = \Slim\Slim::getInstance();
                /** @var ResourceServer $server */
                $server = $app->oauthServer;
                // Test for token existence and validity
                try {
                    $server->isValidRequest(false); //ENVIRONMENT=='production');
                }

                    // The access token is missing or invalid...
                catch (\League\OAuth2\Server\Exception\OAuthException $e)
                {
                    $res = $app->response();
                    $res['Content-Type'] = 'application/json';
                    $res->status(403);

                    $res->body(json_encode(array(
                        'error' =>  $e->getMessage(),
                    )));
                    $app->stop();
                }
            };

        };

        $authenticate = function () {
            return function () {
                $app = \Slim\Slim::getInstance();
                if (!isset($_SESSION['user'])) {
                    $_SESSION['urlRedirect'] = $app->request()->getPathInfo();
                    $app->flash('error', 'Login required');
                    $app->redirect('/login');
                }
            };
        };

        /**
         * @param string|null $permission
         * @return callable
         */
        $check = function ( $permission = null) {
            return /**
             * @param string|null $permission
             * @throws \Slim\Exception\Stop
             */
            function () use ($permission) {
                $app = \Slim\Slim::getInstance();
                $sentry = \Cartalyst\Sentry\Facades\Native\Sentry::instance();
                $accessdeny = true;

                if(!$sentry->check()){
                    $accessdeny=true;
                } else {
                    //user is logged in
                    if(!is_null($permission)){

                        $user = $sentry->getUser();
                        if ($user->hasAccess($permission))
                        {
                            // access granted
                            $accessdeny=false;
                        } else {
                            $accessdeny=true;
                        }
                    } else {
                        $accessdeny=false;
                    }
                }

                if($accessdeny){
                    if($app->request->isAjax()){
                        $app->response->headers()->set('Content-Type', 'application/json');
                        $app->response->setBody(json_encode(
                            array(
                                'success'   => false,
                                'message'   => 'Session expired or unauthorized access.',
                                'code'      => 401
                            )
                        ));
                        $app->stop();
                    }else{
                        $redirect = $app->request->getResourceUri();
                        $app->response->redirect('/login'.'?r='.base64_encode($redirect));
                    }
                }
            };
        };
//        $slimObj->hook('slim.before.dispatch', function() use ($slimObj) {
//            $user = null;
//            if (isset($_SESSION['user'])) {
//                $user = $_SESSION['user'];
//            }
//            $slimObj->view()->setData('user', $user);
//        });

//        $checkSession = function () {
//            return function()
//            {
//                $app = \Slim\Slim::getInstance();
//                $user = null;
//                if (isset($_SESSION['user'])) {
//                    $user = $_SESSION['user'];
//                }
//                $app->view()->setData('user', $user);
//            };
//        };

        // Set up routes
        // Optionally register a controller with the container
        $slimObj->container->singleton('App\Home', function ($container) {
            // Retrieve any required dependencies from the container and
            // inject into the constructor of the controller
            return new \App\Controller\Home();
        });

        // API group
        $slimObj->group('/api', function () use ($slimObj,$checkToken){
            // Version group
            $slimObj->group('/v1', function () use ($slimObj,$checkToken) {
                $slimObj->get('/echo', 'App\Controller\EchoController:index');
                $slimObj->get('/tokeninfo', $checkToken(), 'App\Controller\Tokeninfo:index');
                $slimObj->get('/authorize','App\Controller\Authorize:index');
                $slimObj->post('/accesstoken', 'App\Controller\Accesstoken:index');
                $slimObj->get('/users', $checkToken(), 'App\Controller\Users:index');
                $slimObj->get('/users/:username', $checkToken(), 'App\Controller\Users:user');
                $slimObj->post('/users', $checkToken(), 'App\Controller\Users:create');
            });
        });

        //admin group
        $slimObj->group('/admin', function () use ($slimObj, $check){
            $slimObj->get('/', $check('admin'), 'App\Controller\UserAdmin:index');
            //$slimObj->get('/user/:id', $check(), 'App\Controller\UserAdmin:show');
            $this->resource('/user', 'App\Controller\UserAdmin');
            $this->resource('/group', 'App\Controller\GroupAdmin');
        });

        $slimObj->get('/login', 'App\Controller\Login:getLogin');
        $slimObj->post('/login', 'App\Controller\Login:postLogin');
        $slimObj->get('/logout', 'App\Controller\Login:getLogout');

        $slimObj->get('/', 'App\Home:index');
        $slimObj->post('/', 'App\Home:post');
        $slimObj->get('/about', 'App\Home:about');

        $slimObj->get('/hello/:name', 'App\Controller\Home:hello');

    }

    /**
     * Route resource to single controller
     */
    public static function resource(){
        $arguments  = func_get_args();
        $path       = $arguments[0];
        $controller = end($arguments);
        $resourceRoutes = array(
            'get'           => array(
                'pattern'       => "$path",
                'method'        => 'get',
                'handler'       => "$controller:index"
            ),
            'get_paginate'  => array(
                'pattern'       => "$path/page/:page",
                'method'        => 'get',
                'handler'       => "$controller:index"
            ),
            'get_create'    => array(
                'pattern'       => "$path/create",
                'method'        => 'get',
                'handler'       => "$controller:create"
            ),
            'get_edit'      => array(
                'pattern'       => "$path/:id/edit",
                'method'        => 'get',
                'handler'       => "$controller:edit"
            ),
            'get_show'      => array(
                'pattern'       => "$path/:id",
                'method'        => 'get',
                'handler'       => "$controller:show"
            ),
            'post'          => array(
                'pattern'       => "$path",
                'method'        => 'post',
                'handler'       => "$controller:store"
            ),
            'put'           => array(
                'pattern'       => "$path/:id",
                'method'        => 'put',
                'handler'       => "$controller:update"
            ),
            'delete'        => array(
                'pattern'       => "$path/:id",
                'method'        => 'delete',
                'handler'       => "$controller:destroy"
            )
        );
        foreach ($resourceRoutes as $route) {
            $callable   = $arguments;
            //put edited pattern to the top stack
            array_shift($callable);
            array_unshift($callable, $route['pattern']);
            //put edited controller to the bottom stack
            array_pop($callable);
            array_push($callable, $route['handler']);
            call_user_func_array(array(\Slim\Slim::getInstance(), $route['method']), $callable);
        }
    }

    public function run()
    {
        //output
        $this->slimApp->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->slimApp->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->slimApp->response->headers->set('Access-Control-Allow-Headers','Origin, Content-Type, Accept, Authorization, X-Request-With');
        $this->slimApp->response->headers->set('Access-Control-Allow-Credentials', 'true');

        $this->slimApp->run();
    }
}