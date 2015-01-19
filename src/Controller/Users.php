<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 31/12/2014
 * Time: 14:53
 */

namespace App\Controller;
use App\Model\Users as ModelUsers;
use App\Exception\ValidationException;
use App\Model;
use App\Validation;

class Users {
    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    /** @var  \RkaSc\Slim */
    protected $app;

    public function index()
    {
        /** @var \League\OAuth2\Server\ResourceServer $resourceServer */
        $resourceServer = $this->app->oauthServer;

        $results = (new Model\Users())->get();

        $users = [];

        foreach ($results as $result) {
            $user = [
                'username'  =>  $result['username'],
                'name'      =>  $result['name'],
            ];

            if ($resourceServer->getAccessToken()->hasScope('email')) {
                $user['email'] = $result['email'];
            }

            $users[] = $user;
        }

        $this->response->setBody(json_encode($users));
        return;
    }

    public function user($email)
    {
        /** @var \League\OAuth2\Server\ResourceServer $resourceServer */
        $resourceServer = $this->app->oauthServer;

        $result = (new Model\Users())->get($email);

        if (count($result) === 0) {
            throw new NotFoundException();
        }

        $user = [
            'username'  =>  $result[0]['username'],
            'name'      =>  $result[0]['name'],
        ];

        if ($resourceServer->getAccessToken()->hasScope('email')) {
            $user['email'] = $result[0]['email'];
        }

        if ($resourceServer->getAccessToken()->hasScope('photo')) {
            $user['photo'] = $result[0]['photo'];
        }
        $this->response->setBody(json_encode($user));
        return;
    }

    public function create()
    {
        /** @var \League\OAuth2\Server\ResourceServer $resourceServer */
        $resourceServer = $this->app->oauthServer;
        // get the json parse array ( Courtesy of Slim_Middleware_ContentTypes())
        $body = $this->request->getBody();

        if (!$resourceServer->getAccessToken()->hasScope('useradmin')) {
            // Check some fields username, name and email are required
            $rules = array(
                'password'  => 'required|min:5',
                'name'      => 'required',
                'email'     => 'required|email|unique:users',
            );

            /** @var \Illuminate\Validation\Factory $validation */
            $validation = $this->app->validator->getValidator();

            $validator = $validation->make($body, $rules);

            if ($validator->fails())
            {
                $messages = $validator->messages();
                // TODO throw exception
                throw new ValidationException(
                    "Invalid data",
                    0,
                    $messages->toArray()
                );

            }

            // and create our object
            $newuser = ModelUsers::create(array());
            $newuser->username  = $body['email'];
            $newuser->password  = password_hash($body['password'], PASSWORD_DEFAULT);
            $newuser->name      = $body['name'];
            $newuser->email     = $body['email'];
            $newuser->photo     = 'https://s.gravatar.com/avatar/'.md5( strtolower( trim( $body['email'] ) ) );

            $newuser->save();

            // TODO: return some useful JSON
            $this->response->setBody(json_encode(array(
                'message'=>'User Created',
                'user'=> $newuser->toArray(),
                )));
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