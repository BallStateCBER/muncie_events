<?php
App::uses('Sanitize', 'Utility');
App::uses('AppController', 'Controller');
class UsersController extends AppController
{
    public $name = 'Users';
    public $components = array();
    public $helpers = array();

    public function beforeFilter()
    {
        parent::beforeFilter();

        // Enable syncFacebookUser for specific actions
        $fb_sync_actions = array(
            'register_facebook',
            'login',
            'facebook_sync'
        );
        if (in_array($this->request->params['action'], $fb_sync_actions)) {
            $this->Connect->noAuth = false;
        }

        // Enable createUser for specific actions
        $fb_register_actions = array(
            'register_facebook'
        );
        if (in_array($this->request->params['action'], $fb_register_actions)) {
            $this->Connect->createUser = true;
        }

        $this->Auth->deny(
            'add', 'edit', 'delete', 'mine', 'approve',
            'moderate', 'account', 'edit_basic_info',
            'edit_basic_profile', 'change_pass', 'facebook_sync', 'facebook_unsync'
        );
    }

    public function beforeRender()
    {
        parent::beforeRender();
    }

    public function recent()
    {
        return parent::recent(
            array('User.name', 'User.id', 'User.created'),
            array('controller' => 'users', 'action' => 'view')
        );
    }

    /**
     * Run before a new user is created by Facebook
     */
    public function beforeFacebookSave()
    {
        if (! $this->Connect->user('name') || ! $this->Connect->user('email')) {
            $this->Flash->error('Could not register new account via Facebook (user data missing). Please try again.');
            return false;
        }

        // Set fields so that they're written to the database for a new user
        $email = $this->Connect->authUser['User']['email'] = strtolower($this->Connect->user('email'));
        $this->Connect->authUser['User']['name'] = $this->Connect->user('name');

        $existing_user = $this->User->findByEmail($email, array('User.id'));

        // Remember whether we're creating a new account or syncing to an exisitng account
        $this->Session->write('facebookRegistration.accountAlreadyExists', ! empty($existing_user));

        if ($existing_user) {
            // Set the user to be updated so that a new user isn't created
            $this->Connect->authUser['User']['id'] = $existing_user['User']['id'];

            // Prevent the existing user's name and password from being overwritten
            unset($this->Connect->authUser['User']['name']);
            unset($this->Connect->authUser['User']['password']);
        }

        return true;
    }

    public function beforeFacebookLogin()
    {
    }

    public function afterFacebookLogin()
    {
    }

    public function login()
    {
        // Remove the validation rule that should only apply to new registrants
        unset($this->User->validate['email']['emailUnclaimed']);

        // Redirect user if they're already registered and logged in
        if ($this->Auth->user('id')) {
            $this->redirect('/');
        }

        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                // Set 'remember me' cookie
                if ($this->request->data['User']['remember_me'] == 1) {
                    unset($this->request->data['User']['remember_me']);
                    $this->request->data['User']['password'] = $this->Auth->password($this->request->data['User']['password']);
                    $this->Cookie->write('remember_me_cookie', $this->request->data['User'], true, '10 years');
                }

                // Set user role
                //$this->User->id = $this->Auth->user('id');
                //$this->Session->write('Auth.User.role', $this->User->field('role'));

                // Redirect
                if ($this->request->is('ajax')) {
                    $this->set('url', $this->Auth->redirectUrl());
                    $this->render('/Pages/js_redirect');
                } else {
                    $this->redirect($this->Auth->redirectUrl());
                }
            } else {
                $this->set('password_error', 'Password incorrect.');
            }
        }

        // Removes "required field" styling
        $this->User->validate = array();

        // Prevents the user from being redirected to logout
        // (if they went directly from logout back to login)
        $redirect = $this->Auth->redirectUrl();
        if (stripos($redirect, 'logout') !== false) {
            $redirect = '/';
        }

        $this->set(array(
            'title_for_layout' => 'Log in',
            'redirect' => $redirect
        ));
    }

    /**
     * Intercepts failed Facebook logins
     */
    public function confirm_facebook_login()
    {
        /*
         * THIS IS APPARENTLY NOT DOING WHAT IT'S SUPPOSED TO.
         * $this->Auth->user('id') is coming up null even when the user
         * is successfully logged in.
         */

        // User was successfully logged in
        if (true || $this->Auth->user('id')) {
            if (isset($_GET['redirect'])) {
                $this->redirect($_GET['redirect']);
            } else {
                $this->redirect('/');
            }
        }

        // User was not logged in
        //$this->Flash->error('There was an error logging you in via Facebook. Make sure that you have registered an account with Facebook or synced an account with Facebook before trying to log in to it.');
        $this->redirect($this->referer());
    }

    public function logout()
    {
        $this->Flash->error('Thanks for stopping by!');
        $this->Cookie->delete('remember_me_cookie');
        $this->Session->destroy();
        $this->Auth->logout();

        /* Why there's no redirect here:
    	 * When a user is logged in via Facebook and the logout
    	 * action results in a redirect to '/' (or anywhere),
    	 * the user needs to click the logout button twice to
    	 * actually get logged out. This bug does not occur when
    	 * no redirect takes place after the logout. */
        return $this->renderMessage(array(
            'title' => 'Logged Out',
            'message' => 'Thanks for stopping by!',
            'class' => 'alert alert-success success'
        ));
    }

    /**
     * The action that the user is redirected to AFTER the Facebook
     * plugin runs beforeFacebookSave(), registers the user, and logs the user in.
     */
    public function register_facebook()
    {
        $facebook_user = $this->Connect->registrationData();
        if (! $facebook_user) {
            return;
        }
        $email = $facebook_user['registration']['email'];
        $join_mailing_list = isset($facebook_user['registration']['join_mailing_list']) && $facebook_user['registration']['join_mailing_list'];
        $existing_user = $this->Session->read('facebookRegistration.accountAlreadyExists');

        // Error encountered
        if (! $this->Connect->hasAccount) {
            if ($existing_user) {
                return $this->renderMessage(array(
                    'title' => 'Error Syncing to Facebook Account',
                    'message' => 'There was an error syncing your Muncie Events account to your Facebook account. Please try again or <a href="/contact">contact us</a> if you need assistance.',
                    'class' => 'error'
                ));
            } else {
                return $this->renderMessage(array(
                    'title' => 'Error Creating Account',
                    'message' => 'There was an error creating your account. Please try again or <a href="/contact">contact us</a> if you need assistance.',
                    'class' => 'error'
                ));
            }
        }

        if (empty($existing_user)) {
            $this->Flash->success('Your new account has been created.');
        } else {
            $this->Flash->success('Your existing user account ('.$email.') has been synced with your Facebook account. From now on, you can log in via Facebook.');
        }

        $this->Auth->login();

        if ($join_mailing_list) {
            $this->redirect(array('controller' => 'mailing_list', 'action' => 'join', $email));
        } else {
            $this->redirect('/');
        }
    }

    public function register()
    {
        // Redirect user if they're already registered and logged in
        if ($this->Auth->user('id')) {
            //$this->redirect('/');
        }

        if ($this->request->is('post')) {

            // Format data
            $this->request->data['User']['email'] = trim(strtolower($this->request->data['User']['email']));
            $this->request->data['User'] = Sanitize::clean($this->request->data['User']);
            $this->User->set($this->request->data);
            $hash = $this->Auth->password($this->request->data['User']['new_password']);
            $this->User->set('password', $hash);

            if ($this->Recaptcha->verify()) {
                if ($this->User->save($this->request->data)) {
                    $login_result = $this->Auth->login(array(
                        'id' => $this->User->id,
                        'role' => '',
                        'email' => $this->request->data['User']['email'],
                        'password' => $this->request->data['User']['new_password']
                    ));
                    if ($login_result) {
                        $this->Flash->success('You\'ve been registered and logged in.');
                        if ($this->request->data['User']['join_mailing_list']) {
                            $this->redirect(array('controller' => 'mailing_list', 'action' => 'join', $this->request->data['User']['email']));
                        } else {
                            $this->redirect('/');
                        }
                    } else {
                        $this->Flash->error('You\'ve been registered, but there was a problem logging you in. Try logging in manually.');
                        $this->redirect(array('controller' => 'users', 'action' => 'login'));
                    }
                }
            } else {
                $this->set(array('recaptcha_error' => $this->Recaptcha->error));
                $this->Flash->error('There was an error validating your CAPTCHA response. Please try again.');
            }
        }

        // So the password fields aren't filled out automatically when the user
        // is bounced back to the page by a validation error
        $this->request->data['User']['new_password'] = '';
        $this->request->data['User']['confirm_password'] = '';

        $this->set(array(
            'title_for_layout' => 'Register an Account'
        ));
    }

    public function account()
    {
        // Remove this validation rule, since the user's existing email address, unchanged, would violate it
        unset($this->User->validate['email']['emailUnclaimed']);

        $id = $this->Auth->user('id');
        $this->User->id = $id;
        if ($this->request->is('post')) {
            $this->request->data['User']['email'] = strtolower(trim($this->request->data['User']['email']));
            $this->User->set($this->request->data);
            $email_result = $this->User->find('first', array(
                'conditions' => array('email' => $this->request->data['User']['email']),
                'fields' => array('id'),
                'contains' => false
            ));
            if (! empty($email_result) && $email_result['User']['id'] != $id) {
                $this->User->validationErrors['email'] = 'Sorry, someone else already has that email address.';
            } elseif ($this->User->validates()) {
                if ($this->User->save()) {
                    $this->Flash->success('Information updated.');
                } else {
                    $this->Flash->error('Sorry, there was an error updating your information. Please try again.');
                }
            }
        } else {
            $this->request->data = $this->User->find('first', array(
                'fields' => array('name', 'email'),
                'conditions' => array('id' => $id),
                'contain' => false
            ));
        }

        // Collect the access hash for this user's mailing list account
        App::uses('MailingList', 'Model');
        $MailingList = new MailingList();
        $ml_result = $MailingList->find('first', array(
            'conditions' => array('email' => $this->data['User']['email']),
            'contain' => false,
            'fields' => array('id')
        ));
        $mailing_list_id = $ml_result ? $ml_result['MailingList']['id'] : null;
        $mailing_list_hash = $ml_result ? $MailingList->getHash($mailing_list_id) : null;

        // Collect Facebook connectivity information
        $facebook_id = $this->User->field('facebook_id');

        if ($facebook_id) {
            App::uses('FB', 'Facebook.Lib');
            $this->FB = new FB();
            $facebook_data = $this->FB->api('/'.$facebook_id);
            $facebook = array(
                'id' => $facebook_id,
                'name' => $facebook_data['name'],
                'url' => $facebook_data['link']
            );
        } else {
            $facebook = null;
        }

        $this->set(array(
            'title_for_layout' => 'My Account',
            'user_id' => $id,
            'facebook' => $facebook
        ));
        $this->set(compact('mailing_list_id', 'mailing_list_hash'));
    }

    public function view($id = null)
    {
        if (! $id) {
            return $this->renderMessage(array(
                'title' => 'User Not Found',
                'message' => 'You may have followed a broken link.',
                'class' => 'error'
            ));
        }
        $this->User->id = $id;
        if (! $this->User->read()) {
            return $this->renderMessage(array(
                'title' => 'User Not Found',
                'message' => 'Sorry, we couldn\'t find that user. You may have followed a link to a user profile that has been removed.',
                'class' => 'error'
            ));
        }
        $event_count = $this->User->Event->find('count', array(
            'conditions' => array('Event.user_id' => $id)
        ));
        if ($event_count) {
            $this->paginate = array(
                'conditions' => array('Event.user_id' => $id),
                'contain' => array(
                    'EventsImage' => array('Image'),
                    'Tag',
                    'Category',
                    'EventSeries',
                    'User'
                ),
                'order' => 'date DESC',
                'limit' => 20
            );
            $events = $this->paginate($this->User->Event);
            $events = $this->User->Event->arrangeByDate($events);
        } else {
            $events = array();
        }
        $this->set(array(
            'title_for_layout' => $this->User->data['User']['name'],
            'user' => $this->User->data,
            'event_count' => $event_count,
            'events' => $events
        ));
    }

    public function change_pass()
    {
        if ($this->request->is('post')) {
            $id = $this->Auth->user('id');
            $this->User->id = $id;
            $this->User->set($this->request->data);
            if ($this->User->validates()) {
                $hash = $this->Auth->password($this->request->data['User']['new_password']);
                $result = $this->User->saveField('password', $hash);
                $result = true;
                if ($result) {
                    $this->Flash->success('Password changed.');
                    $this->redirect(array('controller' => 'users', 'action' => 'account'));
                } else {
                    $this->Flash->error('Error changing password.');
                }
            }
        }

        $this->set(['title_for_layout' => 'Change Password']);
    }

    public function forgot_password()
    {
        $this->Components->load('Email')->startup($this);
        if ($this->request->is('post')) {
            $admin_email = Configure::read('admin_email');
            $email = strtolower(trim($this->request->data['User']['email']));
            if (empty($email)) {
                $this->Flash->error('Please enter the email address you registered with to have your password reset. Email <a href="mailto:'.$admin_email.'">'.$admin_email.'</a> for assistance.');
            } else {
                $user_id = $this->User->getIdFromEmail($email);
                if ($user_id) {
                    if ($this->User->sendPasswordResetEmail($user_id, $email)) {
                        $this->Flash->success('Message sent. You should be shortly receiving an email with a link to reset your password.');
                    } else {
                        $this->Flash->error('Whoops. There was an error sending your password-resetting email out. Please try again, and if it continues to not work, email <a href="mailto:'.$admin_email.'">'.$admin_email.'</a> for assistance.');
                    }
                } else {
                    $this->Flash->error('We couldn\'t find an account registered with the email address <b>'.$email.'</b>. Make sure you spelled it correctly. Email <a href="mailto:'.$admin_email.'">'.$admin_email.'</a> for assistance.');
                }
            }
        }
        $this->set(array(
            'title_for_layout' => 'Forgot Password'
        ));
    }

    public function reset_password($user_id, $reset_password_hash)
    {
        $this->User->id = $user_id;
        $email = $this->User->field('email');
        $expected_hash = $this->User->getResetPasswordHash($user_id, $email);
        if ($reset_password_hash != $expected_hash) {
            $this->Flash->error('Invalid password-resetting code. Make sure that you entered the correct address and that the link emailed to you hasn\'t expired.');
            $this->redirect('/');
        }
        if ($this->request->is('post')) {
            $this->User->set($this->request->data);
            if ($this->User->validates()) {
                $hash = $this->Auth->password($this->request->data['User']['new_password']);
                $this->User->set('password', $hash);
                if ($this->User->save()) {
                    $this->Flash->success('Password changed. You may now log in.');
                    $this->redirect(array('controller' => 'users', 'action' => 'login'));
                } else {
                    $this->Flash->error('There was an error changing your password.');
                }
            }
            unset($this->request->data['User']['new_password']);
            unset($this->request->data['User']['confirm_password']);
        }
        $this->set(array(
            'title_for_layout' => 'Reset Password',
            'user_id' => $user_id,
            'reset_password_hash' => $reset_password_hash
        ));
    }

    public function index()
    {
        $this->paginate = array(
            'order' => 'name ASC',
            'limit' => 50,
            'contain' => false,
            'fields' => array('User.id', 'User.name')
        );
        $this->set(array(
            'title_for_layout' => 'Users',
            'users' => $this->paginate()
        ));
    }

    public function facebook_sync()
    {
        $success = isset($this->viewVars['facebook_user']['id']) && $this->viewVars['facebook_user']['id'];
        $this->set(array(
            'title_for_layout' => ($success ? 'Synced With Facebook' : 'Error Syncing With Facebook'),
            'success' => $success
        ));
    }

    public function facebook_unsync()
    {
        $this->User->id = $this->Auth->user('id');
        $success = $this->User->saveField('facebook_id', 0);
        if ($success) {
            // Delete session data associated with Facebook account
            // to prevent Facebook plugin from reestablishing sync
            $this->Connect->FB->__call('destroySession', array());
            $this->Session->delete('FB');
            $fb_app_id = Configure::read('Facebook.appId');
            $this->Cookie->delete("fbm_$fb_app_id");
            $this->Cookie->delete("fbsr_$app_id");

            // Usual logout process
            $this->Session->destroy();
            $this->Cookie->delete('remember_me_cookie');
            $this->Auth->logout();

            $this->Flash->success('Removed sync. Please log in again to continue.');
            $this->redirect(array('action' => 'login'));
        }
        $this->set(array(
            'title_for_layout' => ($success ? 'Removed Sync With Facebook' : 'Error Unsyncing Facebook Account'),
            'success' => $success
        ));
    }
}
