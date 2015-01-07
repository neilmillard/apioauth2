<?php

namespace App\Controller;

class EchoController
{
    protected $request;
    protected $response;

    public function index()
    {
        echo "Echo";
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