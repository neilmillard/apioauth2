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

    public function index()
    {
        // Set up the OAuth 2.0 authorization server
        $server = new \League\OAuth2\Server\AuthorizationServer();
        $server->setSessionStorage(new Storage\SessionStorage());
        $server->setAccessTokenStorage(new Storage\AccessTokenStorage());
        $server->setRefreshTokenStorage(new Storage\RefreshTokenStorage());
        $server->setClientStorage(new Storage\ClientStorage());
        $server->setScopeStorage(new Storage\ScopeStorage());
        $server->setAuthCodeStorage(new Storage\AuthCodeStorage());

        $clientCredentials = new \League\OAuth2\Server\Grant\ClientCredentialsGrant();
        $server->addGrantType($clientCredentials);

        $passwordGrant = new \League\OAuth2\Server\Grant\PasswordGrant();
        $passwordGrant->setVerifyCredentialsCallback(function ($username, $password) {
            $result = (new Model\Users())->get($username);
            if (count($result) !== 1) {
                return false;
            }

            if (password_verify($password, $result[0]['password'])) {
                return $username;
            }

            return false;
        });
        $server->addGrantType($passwordGrant);

        $refreshTokenGrant = new \League\OAuth2\Server\Grant\RefreshTokenGrant();
        $server->addGrantType($refreshTokenGrant);


        try {
            $response = $server->issueAccessToken();

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