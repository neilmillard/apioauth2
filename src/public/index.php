<?php
include __DIR__ . '/../../vendor/autoload.php';
use League\OAuth2\Server\ResourceServer;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Model;
use App\Storage;

/*
* @property \Slim\Http\Response $slimResponse
* @property \Slim\Http\Request  $slimRequest
*/
Class indexLoader
{
    /** @var \RkaSc\Slim */
    private $slimApp;
    public $version = "v1";

    function __construct()
    {
        // Setup environment with production as default
        if(empty(getenv('DBHOST'))){
            $dotenv = new \Dotenv\Dotenv;
            $dotenv->load(__DIR__ . '/../../');
        }
        define('ENVIRONMENT', (getenv('SLIM_ENV') ? getenv('SLIM_ENV') : 'production'));

        // Setup Slim
        $this->slimApp = new \RkaSc\Slim(
            [
                'mode' => ENVIRONMENT,
                'debug' => false        //this isn't needed, exceptions check the environment.
            ]
        );

        // enable json parsing middleware
        $this->slimApp->add(new Slim\Middleware\ContentTypes());

        //setup config
        $this->setupConfig();

        // Create monolog logger and store logger in container as singleton
        // (Singleton resources retrieve the same log resource definition each time)
        $this->slimApp->container->singleton('log', function () {
            $log = new \Monolog\Logger('fxmaster-data');
            $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/'.ENVIRONMENT.'_'.date('Y-m-d').'.log', \Monolog\Logger::DEBUG));
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
        $this->initialiseOauth2();
        $this->initialiseRouting();
        $this->run();
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
    }

    function setupConfig()
    {
        $this->slimApp->container->singleton('files', function () {
            return new Illuminate\Filesystem\Filesystem();
        });

        $this->slimApp->container->singleton('loader', function ($c) {
            $configPath = __DIR__ . '/../config';
            return new Illuminate\Config\FileLoader($c['files'], $configPath);
        });

        $this->slimApp->container->singleton('config', function ($c) {
            return new Illuminate\Config\Repository($c['loader'], ENVIRONMENT);
        });
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
            $validation = new App\Validation\Manager('en', __DIR__.'/../lang');
            $validation->setConnection($this->slimApp->config->get('database') );
            return $validation;
        });


    }

    function initialiseRouting()
    {
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
                        'error' =>  $e->getMessage()
                    )));
                    $app->stop();
                }
            };

        };

        $slimObj = $this->slimApp;
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
                $slimObj->get('/accesstoken', 'App\Controller\Accesstoken:index');
                $slimObj->get('/authorize', 'App\Controller\Authorize:index');
                $slimObj->get('/users', $checkToken(), 'App\Controller\Users:index');
                $slimObj->get('/users/:username', $checkToken(), 'App\Controller\Users:user');
                $slimObj->post('/users', $checkToken(), 'App\Controller\Users:create');
            });
        });

        $slimObj->get('/', 'App\Home:index');
        $slimObj->get('/hello/:name', 'App\Controller\Home:hello');

    }

    function run()
    {

        $this->slimApp->config('debug', ENVIRONMENT!='production');
        //output
        $this->slimApp->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->slimApp->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->slimApp->response->headers->set('Access-Control-Allow-Headers','Origin, Content-Type, Accept, Authorization, X-Request-With');
        $this->slimApp->response->headers->set('Access-Control-Allow-Credentials', 'true');

        $this->slimApp->run();
    }
}

$loadIt = new indexLoader();