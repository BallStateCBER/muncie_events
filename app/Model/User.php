<?php
App::uses('AppModel', 'Model');
class User extends AppModel
{
    public $name = 'User';
    public $displayField = 'name';
    public $actsAs = array(
        'Containable'
    );
    public $hasMany = array('Event', 'Image');
    public $belongsTo = array('MailingList');
    public $validate = array(
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notBlank',
                'message' => 'This is required.',
                'last' => true
            )
        ),
        'new_password' => array(
            'nonempty' => array(
                'rule' => 'notBlank',
                'message' => 'You\'ll definitely need a password.'
            )
        ),
        'confirm_password' => array(
            'identicalFieldValues' => array(
                'rule' => array('identicalFieldValues', 'new_password' ),
                'message' => 'Hey, those two passwords didn\'t match.'
            )
        ),
        'email' => array(
            'is_email' => array(
                'rule' => 'email',
                'message' => 'That doesn\'t look like a valid email address.'
            ),
            'emailUnclaimed' => array(
                'rule' => array('emailUnclaimed'),
                'message' => 'Sorry, someone else is already using that email address.'
            ),
            'notEmpty' => array(
                'rule' => 'notBlank',
                'message' => 'This is required.',
                'last' => true
            )
        )
    );

    public function identicalFieldValues($field = array(), $compare_field = null)
    {
        foreach ($field as $key => $value) {
            $v1 = $value;
            $v2 = $this->data[$this->name][$compare_field];
            if ($v1 !== $v2) {
                return false;
            }
        }
        return true;
    }

    public function emailUnclaimed($data)
    {
        $result = $this->find('first', array(
            'conditions' => array('User.email' => strtolower($data['email'])),
            'fields' => array('id'),
            'contains' => false
        ));
        return empty($result);
    }

    public function getIdFromEmail($email)
    {
        $email = strtolower(trim($email));
        $result = $this->find('first', array(
            'conditions' => array('User.email' => $email),
            'fields' => array('User.id'),
            'contain' => false
        ));
        if (isset($result['User']['id']) && $result['User']['id']) {
            return $result['User']['id'];
        }
        return false;
    }

    public function getImagesList($id)
    {
        return $this->Image->find('list', array(
            'conditions' => array('user_id' => $id),
            'order' => 'created DESC'
        ));
    }

    public function sendPasswordResetEmail($user_id, $email_address)
    {
        $reset_password_hash = $this->getResetPasswordHash($user_id, $email_address);
        App::uses('CakeEmail', 'Network/Email');
        $email = new CakeEmail('default');
        $title_for_layout = 'Muncie Events: Reset Password';
        $reset_url = Router::url(array(
            'controller' => 'users',
            'action' => 'reset_password',
            $user_id,
            $reset_password_hash
        ), true);
        $email->to($email_address)
            ->subject('Muncie Events: Reset Password')
            ->template('forgot_password')
            ->emailFormat('both')
            ->helpers(array('Html', 'Text'))
            ->viewVars(compact(
                'title_for_layout',
                'email_address',
                'reset_url'
            ));
        return $email->send();
    }

    public function getResetPasswordHash($user_id, $email = null)
    {
        $salt = Configure::read('password_reset_salt');
        $month = date('my');
        return md5($user_id.$email.$salt.$month);
    }

    /**
     * Returns true only if the user has previously submitted an event that has been published/approved
     * @param $user_id
     * @return boolean
     */
    public function canAutopublish($user_id)
    {
        if (! $user_id) {
            return false;
        }
        $count = $this->Event->find(
            'count',
            array(
                'conditions' => array(
                    'Event.user_id' => $user_id,
                    'Event.published' => true,
                    'Event.approved_by NOT' => null
                ),
                'limit' => 1
            )
        );
        return $count > 1;
    }
}
