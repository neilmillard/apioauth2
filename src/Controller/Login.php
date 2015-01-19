<?php
/**
 * Created by PhpStorm.
 * User: Neil
 * Date: 11/01/2015
 * Time: 19:26
 */

namespace App\Controller;


class Login {

    /** @var  \Slim\Http\Request */
    protected $request;
    /** @var  \Slim\Http\Response */
    protected $response;
    /** @var  \RkaSc\Slim */
    protected $app;

    public function getLogin()
    {
        $flash = $this->app->view()->getData('flash');
        $error = '';
        if (isset($flash['error'])) {
            $error = $flash['error'];
        }
        $urlRedirect = '/';
        if ($this->app->request()->get('r') && base64_decode($this->app->request()->get('r')) != '/logout' && base64_decode($this->app->request()->get('r')) != '/login') {
            $_SESSION['urlRedirect'] = base64_decode($this->app->request()->get('r'));
        }
        if (isset($_SESSION['urlRedirect'])) {
            $urlRedirect = $_SESSION['urlRedirect'];
        }
        $email_value = $email_error = $password_error = '';
        if (isset($flash['email'])) {
            $email_value = $flash['email'];
        }
        if (isset($flash['errors']['email'])) {
            $email_error = $flash['errors']['email'];
        }
        if (isset($flash['errors']['password'])) {
            $password_error = $flash['errors']['password'];
        }
        $this->app->render('login.php', array('error' => $error, 'email_value' => $email_value, 'email_error' => $email_error, 'password_error' => $password_error, 'urlRedirect' => $urlRedirect));

    }

    public function postLogin()
    {
        $email = $this->app->request()->post('email');
        $password = $this->app->request()->post('password');
        $errors = array();
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
            $this->app->flash('email', $email);
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
            $this->app->flash('errors', $errors);
            $this->app->redirect('/login');
        }
        // overwriting Sentry?
        //$_SESSION['user'] = $email;
        if (isset($_SESSION['urlRedirect'])) {
            $tmp = $_SESSION['urlRedirect'];
            unset($_SESSION['urlRedirect']);
            $this->app->redirect($tmp);
        }
        $this->app->redirect('/');
    }

    public function getLogout()
    {
        unset($_SESSION['user']);
        \Cartalyst\Sentry\Facades\Native\Sentry::instance()->logout();
        $this->app->render('logout.php');
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