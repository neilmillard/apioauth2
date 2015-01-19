<?php

namespace App\Controller;

// TODO update with normal rather than Facade references.
use App\Exception;
use \Cartalyst\Sentry\Facades\Native\Sentry;
use App\Model;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\ResourceServer;

class UserAdmin {

    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    /** @var  \RkaSc\Slim */
    protected $app;

    protected $data = array();

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
        /** global javascript var */
        $this->data['global'] = array();
        $this->data['global']['baseUrl']  = $this->baseUrl();

        $this->data['title'] = 'Admin';

        $this->data['groups'] = array();
    }

    /**
     * generate base URL
     */
    protected function baseUrl()
    {
        $path       = dirname($_SERVER['SCRIPT_NAME']);
        $path       = trim($path, '/');
        $baseUrl    = \Slim\Slim::getInstance()->request()->getUrl();
        $baseUrl    = trim($baseUrl, '/').':8800';
        return $baseUrl.'/'.$path.( $path ? '/' : '' );
    }

    /**
     * display list of resource
     */
    public function index($page = 1)
    {
        $user = Sentry::instance()->getUser();
        $this->data['title'] = 'Users List';
        $this->data['users'] = (new Model\User) //->where('id', '<>', $user->id)
            ->get()
            ->toArray();

        /** load the user.js app */
        $js= array('app/user.js');

        /** render the template */
        $this->app->render('admin/index.php', $this->data );
//        View::display('@usergroup/user/index.twig', $this->data);
    }

    /**
     * display resource with specific id
     */
    public function show($id)
    {
        if($this->request->isAjax()){
            $user = null;
            $message = '';

            try{
                $user = Sentry::instance()->findUserById($id);
            }catch(\Exception $e){
                $message = $e->getMessage();
            }


            $this->response->headers()->set('Content-Type', 'application/json');
            $this->response->setBody(json_encode(
                array(
                    'success'   => !is_null($user),
                    'data'      => !is_null($user) ? $user->toArray() : $user,
                    'message'   => $message,
                    'code'      => is_null($user) ? 404 : 200
                )
            ));
        }else{

        }
    }

    /**
     * show edit from resource with specific id
     */
    public function edit($id)
    {
        try{
            $user = Sentry::instance()->findUserById($id);
            //display edit form in non-ajax request
            //
            $this->data['title'] = 'Edit User';
            $this->data['user'] = $user->toArray();

            View::display('@usergroup/user/edit.twig', $this->data);
        }catch(UserNotFoundException $e){
            $this->app->notFound();
        }catch(Exception $e){
            $this->app->response->setBody($e->getMessage());
            $this->app->response->finalize();
        }
    }

    /**
     * update resource with specific id
     */
    public function update($id)
    {
        $success = false;
        $message = '';
        $user    = null;
        $code    = 0;

        try{
            $input = $this->app->request->put();
            /** in case request come from post http form */
            $input = is_null($input) ? $this->app->request->post() : $input;

            if($input['password'] != $input['confirm_password']){
                throw new Exception("Password and confirmation password not match", 1);
            }

            $user = Sentry::instance()->findUserById($id);

            $user->email        = $input['email'];
            $user->first_name   = $input['first_name'];
            $user->last_name    = $input['last_name'];

            if($input['password']){
                $user->password = $input['password'];
            }

            $success = $user->save();
            $code    = 200;
            $message = 'User updated successfully';
            //TODO: authorized here

        }catch(UserNotFoundException $e){
            $message = $e->getMessage();
            $code    = 404;
        }catch (Exception $e){
            $message = $e->getMessage();
            $code    = 500;
        }

        if($this->app->request->isAjax()){
            $this->app->response->headers()->set('Content-Type', 'application/json');
            $this->app->response->setBody(json_encode(
                array(
                    'success'   => $success,
                    'data'      => ($user) ? $user->toArray() : $user,
                    'message'   => $message,
                    'code'      => $code
                )
            ));
        }else{
            $this->app->response->redirect($this->siteUrl('admin/user/'.$id.'/edit'));
        }
    }

    /**
     * create new resource
     */
    public function store()
    {
        $user    = null;
        $message = '';
        $success = false;

        try{
            $input = $this->app->request->post();

            if($input['password'] != $input['confirm_password']){
                throw new Exception("Password and confirmation password not match", 1);
            }

            try{
                // TODO: this should be register so we can verify email
                //  $user = Sentry::instance()->register(array(
                $user = Sentry::instance()->createUser(array(
                    'email'       => $input['email'],
                    'password'    => $input['password'],
                    'first_name'  => $input['first_name'],
                    'last_name'   => $input['last_name'],
                    'activated'   => 1
                ));

                // Let's get the activation code
                $activationCode = $user->getActivationCode();
                // TODO: email user with activation code so they can verify.

                /*
                try
                {
                    // Find the user using the user id
                    $user = Sentry::findUserById(1);

                    // Attempt to activate the user
                    if ($user->attemptActivation('8f1Z7wA4uVt7VemBpGSfaoI9mcjdEwtK8elCnQOb'))
                    {
                        // User activation passed
                    }
                    else
                    {
                        // User activation failed
                    }
                }
                catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
                {
                    echo 'User was not found.';
                }
                catch (Cartalyst\Sentry\Users\UserAlreadyActivatedException $e)
                {
                    echo 'User is already activated.';
                }
                 */

            }
            catch (\Cartalyst\Sentry\Users\LoginRequiredException $e)
            {
                throw new Exception('Login field is required.', 1);
            }
            catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e)
            {
                throw new Exception('Password field is required.', 1);
            }
            catch (\Cartalyst\Sentry\Users\UserExistsException $e)
            {
                throw new Exception('User with this login already exists.', 1);
            }

            $success = true;
            $message = 'User created successfully';
        }catch (Exception $e){
            $message = $e->getMessage();
        }

        if($this->app->request->isAjax()){
            $this->app->response->headers()->set('Content-Type', 'application/json');
            $this->app->response->setBody(json_encode(
                array(
                    'success'   => $success,
                    'data'      => ($user) ? $user->toArray() : $user,
                    'message'   => $message,
                    'code'      => $success ? 200 : 500
                )
            ));
        }else{
            $this->app->response->redirect('/admin');
        }
    }

    /**
     * destroy resource with specific id
     */
    public function destroy($id)
    {
        $id      = (int) $id;
        $deleted = false;
        $message = '';
        $code    = 0;

        try{
            $user    = Sentry::instance()->findUserById($id);
            $username= $user->getLogin();
            $deleted = $user->delete();
            /** @var ResourceServer $server */
            $server = $this->app->oauthServer;
            $sessiondeleted = $server->getSessionStorage()->deleteSession($username);
            $code    = 200;
        }catch(\Cartalyst\Sentry\Users\UserNotFoundException $e){
            $message = $e->getMessage();
            $code    = 404;
        }catch(Exception $e){
            $message = $e->getMessage();
            $code    = 500;
        }

        if($this->app->request->isAjax()){
            $this->app->response->headers()->set('Content-Type', 'application/json');
            $this->app->response->setBody(json_encode(
                array(
                    'success'   => $deleted,
                    'data'      => array( 'id' => $id ),
                    'message'   => $message,
                    'code'      => $code
                )
            ));
        }else{
            $this->app->response->redirect($this->siteUrl('admin/user'));
        }
    }
}