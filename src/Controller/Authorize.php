<?php
namespace App\Controller;

use App\Model;
use App\Storage;
use League\OAuth2\Server\Exception\OAuthException;

class Authorize {
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

        $authCodeGrant = new \League\OAuth2\Server\Grant\AuthCodeGrant();
        $server->addGrantType($authCodeGrant);

        try {
            /* \League\OAuth2\Server\Grant\AuthCodeGrant->checkAuthorizeParams(): */
            $authParams = $server->getGrantType('authorization_code')->checkAuthorizeParams();

        } catch (OAuthException $e) {
            $this->response->setBody(
                json_encode([
                    'error'     =>  $e->errorType,
                    'message'   =>  $e->getMessage(),
                ]));
            $this->response->setStatus( $e->httpStatusCode );
            return;
        }
        
        // TODO: workflow for scope authorization
        // Normally at this point you would show the user a sign-in screen and ask them to authorize the requested scopes
        
        // ...
        
        // ...
        
        // ...
        
        // Create a new authorize request which will respond with a redirect URI that the user will be redirected to
        
        $redirectUri = $server->getGrantType('authorization_code')->newAuthorizeRequest('user', 1, $authParams);
        
        $response = [
        		'Location'  =>  $redirectUri
        ];
                
        $this->response->setBody(json_encode($response));
        return;
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