<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 31/12/2014
 * Time: 12:46
 */

namespace App\Controller;

use App\Model;
use App\Storage;
use League\OAuth2\Server\Exception\OAuthException;

class Accesstoken {
    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    protected $app;

    /**
     * is POST only
     *
     *
     */
    public function index()
    {
        /** @var \League\OAuth2\Server\AuthorizationServer $server */
        $server = $this->app->authorizationServer;

        try {
            $response = $server->issueAccessToken();

            /*
             * $this->assertTrue(array_key_exists('access_token', $response));
             * $this->assertTrue(array_key_exists('token_type', $response));
             * $this->assertTrue(array_key_exists('expires_in', $response));
             */
            $this->response->setBody(json_encode($response));
            return;
        } catch (OAuthException $e) {
            $this->response->setBody(
                json_encode([
                    'error'     =>  $e->errorType,
                    'message'   =>  $e->getMessage(),
                ]));
            $this->response->setStatus( $e->httpStatusCode );
            return;
        }
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