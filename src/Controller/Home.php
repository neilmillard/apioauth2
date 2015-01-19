<?php

namespace App\Controller;

class Home
{
    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    protected $app;

    public function index()
    {
        $sentry = \Cartalyst\Sentry\Facades\Native\Sentry::instance();
        $user=NULL;
        if($sentry->check()){
            $user = $sentry->getUser()['first_name'];
        }
        $this->app->render('index.php',array('user'=>$user));
    }

    public function about()
    {
        $this->app->render('about.php');
    }

    public function hello($name)
    {
        echo "Hello, $name";
    }

    public function post()
    {
        $sentry = \Cartalyst\Sentry\Facades\Native\Sentry::instance();
        $user=NULL;
        if($sentry->check()){
            $user = $sentry->getUser()['first_name'];
        }
        $this->response->setBody(json_encode(array('user'=> $user,
            'post' => $_POST)));
    }

    public function setApp($app)
    {
        $this->app = $app;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }
}