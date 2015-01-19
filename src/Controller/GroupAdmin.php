<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 19/01/2015
 * Time: 08:43
 */

namespace App\Controller;


class GroupAdmin {
    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    /** @var  \RkaSc\Slim */
    protected $app;

    protected $data = [];

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

    public function __construct()
    {

    }

    public function index()
    {
        $this->data['title'] = 'Group List';

        /** render the template */
        $this->app->render('admin/groupindex.php', $this->data );
    }

    public function show()
    {
    }
    public function store()
    {
        $message = '';
        $success = false;

        $input = $this->app->request->post();

        try
        {
            // Create the group
            $group = Sentry::createGroup(array(
                'name'        => $input['name'],
                'permissions' => array(
                    'admin' => 1,
                    'users' => 1,
                ),
            ));
            $success = true;
            $message = 'User created successfully';
        }
        catch (\Cartalyst\Sentry\Groups\NameRequiredException $e)
        {
            $message = 'Name field is required';
        }
        catch (\Cartalyst\Sentry\Groups\GroupExistsException $e)
        {
            $message = 'Group already exists';
        }

        if($this->app->request->isAjax()){
            $this->app->response->headers()->set('Content-Type', 'application/json');
            $this->app->response->setBody(json_encode(
                array(
                    'success'   => $success,
                    'data'      => ($group) ? $group->toArray() : $group,
                    'message'   => $message,
                    'code'      => $success ? 200 : 500
                )
            ));
        }else{
            $this->app->response->redirect($this->siteUrl('admin/'));
        }
    }
    public function create()
    {
    }
    public function edit()
    {
    }
    public function update()
    {
    }
    public function destroy()
    {
    }
}