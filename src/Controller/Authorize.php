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

    /**
     * index from path /api/v1/authorize/
     * expects $_GET array to contain
     * 'grant_type'    => 'authorization_code',
     * 'client_id'     =>  'testapp', // the client_id
     * 'client_secret' =>  'foobar', // the client_secret. not checked in checkAuthorizeParams. checked in CompleteFlow
     * 'redirect_uri'  =>  'http://foo/bar', // the client redirect, this is checked too
     * 'response_typ'          =>  'code', // default is code
     * 'username'  => 'user1@example.com', // the existing user you want to authorize
     * 'password' => 'Pa55word', // the username password.
     * @throws \League\OAuth2\Server\Exception\InvalidGrantException
     * returns code= to redirect url
     */
    public function index()
    {
        /** @var \League\OAuth2\Server\AuthorizationServer $server */
        $server = $this->app->authorizationServer;
        $errors = [];
        try {
            /* \League\OAuth2\Server\Grant\AuthCodeGrant->checkAuthorizeParams(): */
            $authParams = $server->getGrantType('authorization_code')->checkAuthorizeParams();
            $email = $this->request->get('username');
            if (is_null($email)) {
                throw new \League\OAuth2\Server\Exception\InvalidRequestException('username');
            }
            $password = $this->request->get('password');
            if (is_null($email)) {
                throw new \League\OAuth2\Server\Exception\InvalidRequestException('password');
            }

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
        // TODO: check if user already has authorization
        // Normally at this point you would show the user a sign-in screen and ask them to authorize the requested scopes

        $credentials = array(
            'email' => $email,
            'password' => $password,
        );
        // TODO: function candidate to authorise user
        try
        {
            $user = \Cartalyst\Sentry\Facades\Native\Sentry::instance()->authenticate($credentials, false);
        }
        catch (\Cartalyst\Sentry\Users\WrongPasswordException $e)
        {
            $errors['password'] = "Password does not match.";
        }
        catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
        {
            $errors['email'] = "Email is not found.";
        }
        catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e)
        {
            $errors['email'] = "User Not Activated";
        }
            // The following is only required if the throttling is enabled
        catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e)
        {
            $errors['email'] = 'User is suspended.';
        }
        catch (\Cartalyst\Sentry\Throttling\UserBannedException $e)
        {
            $errors['email'] = 'User is banned.';
        }

        if (count($errors) > 0) {
            $response = [
                'error'     =>  'error',
                'message'   =>  $errors,
            ];
        } else {
            // Create a new authorize request which will respond with a redirect URI that the user will be redirected to

            try {
                $redirectUri = $server->getGrantType('authorization_code')->newAuthorizeRequest('user', $user->getId(), $authParams);

                $response = [
                    'Location'  =>  $redirectUri
                ];
            } catch (\Illuminate\Database\QueryException $e){
                $response = [
                    'error'     =>  'PDO error',
                    'message'   =>  $e->getMessage()
                ];
            }

        }

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