<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 30/12/2014
 * Time: 12:18
 */

namespace App\Controller;


class Tokeninfo {

    protected $request;
    protected $response;
    protected $app;

    public function index()
    {
        /** @var \League\OAuth2\Server\ResourceServer $resourceServer */
        $resourceServer = $this->app->oauthServer;
        $accessToken = $resourceServer->getAccessToken();
        /** @var  $session */
        $session = $resourceServer->getSessionStorage()->getByAccessToken($accessToken);
        $token = [
            'owner_id' => $session->getOwnerId(),
            'owner_type' => $session->getOwnerType(),
            'access_token' => $accessToken,
            'client_id' => $session->getClient()->getId(),
            'scopes' => $accessToken->getScopes(),
        ];

        return $this->response->setBody(json_encode($token));
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