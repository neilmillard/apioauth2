<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 11/01/2015
 * Time: 16:46
 */

namespace App;


class AuthServer {

    /** @var \League\OAuth2\Server\AuthorizationServer  */
    protected $server;

    public function __construct()
    {
        // Set up the OAuth 2.0 authorization server
        $this->server = new \League\OAuth2\Server\AuthorizationServer();
        $this->server->setSessionStorage(new Storage\SessionStorage());
        $this->server->setAccessTokenStorage(new Storage\AccessTokenStorage());
        $this->server->setRefreshTokenStorage(new Storage\RefreshTokenStorage());
        $this->server->setClientStorage(new Storage\ClientStorage());
        $this->server->setScopeStorage(new Storage\ScopeStorage());
        $this->server->setAuthCodeStorage(new Storage\AuthCodeStorage());

        $clientCredentials = new \League\OAuth2\Server\Grant\ClientCredentialsGrant();
        $this->server->addGrantType($clientCredentials);

        $passwordGrant = new \League\OAuth2\Server\Grant\PasswordGrant();
        $passwordGrant->setVerifyCredentialsCallback(function ($email, $password) {
            $credentials = array(
                'email' => $email,
                'password' => $password,
            );
            try
            {
                $user = \Cartalyst\Sentry\Facades\Native\Sentry::instance()->authenticate($credentials, false);
            }
            catch (\Cartalyst\Sentry\Users\WrongPasswordException $e)
            {
                return false;
            }
            catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
            {
                return false;
            }
            catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e)
            {
                return false;
            }
                // The following is only required if the throttling is enabled
            catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e)
            {
                echo 'User is suspended.';
            }
            catch (\Cartalyst\Sentry\Throttling\UserBannedException $e)
            {
                echo 'User is banned.';
            }
            return $email; // this may need to be $user->id
        });
        $this->server->addGrantType($passwordGrant);

        $refreshTokenGrant = new \League\OAuth2\Server\Grant\RefreshTokenGrant();
        $this->server->addGrantType($refreshTokenGrant);

        $authCodeGrant = new \League\OAuth2\Server\Grant\AuthCodeGrant();
        $this->server->addGrantType($authCodeGrant);
    }

    public function getServer()
    {
        return $this->server;
    }
}