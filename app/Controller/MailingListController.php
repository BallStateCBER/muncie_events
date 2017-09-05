<?php
App::uses('AppController', 'Controller');
class MailingListController extends AppController
{
    public $name = 'MailingList';
    public $components = array();
    public $helpers = array();
    public $uses = array('MailingList', 'MailingListLog', 'Event', 'Category');
    public $admin_actions = array('reset_processed_time', 'set_categories');

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->deny('reset_processed_time', 'set_categories');
    }

    public function beforeRender()
    {
        parent::beforeRender();
    }

    public function isAuthorized()
    {
        // Admins can access everything
        if ($this->Auth->user('role') == 'admin') {
            return true;

        // Some actions are admin-only
        } elseif (in_array($this->action, $this->admin_actions)) {
            return false;
        }

        // Logged-in users can access everything else
        return true;
    }

    /**
     * For all users, sets the 'mailing_list_id' field according to matching
     * email addresses. Intended to be run once, manually.
     */
    public function set_ids()
    {
        // Currently disabled to prevent being run accidentally
        return;

        $list_members = $this->MailingList->find('list');
        $this->MailingList->User->displayField = 'email';
        $users = $this->MailingList->User->find('all', array(
            'fields' => array('id', 'name', 'email', 'mailing_list_id'),
            'contain' => false
        ));
        $users_modified = array();
        foreach ($users as $user) {
            $list_member_id = array_search($user['User']['email'], $list_members);
            if ($list_member_id !== false) {
                $this->User->id = $user['User']['id'];
                if ($user['User']['mailing_list_id'] != $list_member_id) {
                    $this->User->saveField('mailing_list_id', $list_member_id);
                    $users_modified[] = $user['User']['name'];
                }
            }
        }
        if (empty($users_modified)) {
            $message = 'All users are appropriately linked to their mailing list memberships.';
        } else {
            $message = 'The following users have had their accounts linked to mailing list memberships: ';
            $message .= implode(', ', $users_modified);
        }
        return $this->renderMessage(array(
            'title' => 'Done',
            'message' => $message,
            'class' => 'success'
        ));
    }

    /**
     * For all list members, sets all_categories or the appropriate category associations;
     * Intended to be run once , manually.
     */
    /* Has been run, will probably not be necessary in the future
    public function set_categories() {
        // Determine what value for the old (TheMuncieScene.com) MailingList.category field would represent 'all categories'
        $all_categories_value = '8,9,10,11,12,13';

        $members = $this->MailingList->find('all', array(
            'fields' => array('id', 'all_categories', 'categories'),
            'contain' => false
        ));
        foreach ($members as $member) {
            $this->MailingList->id = $member['MailingList']['id'];
            $is_all_categories = $member['MailingList']['categories'] == $all_categories_value;

            // Set MailingList.all_categories
            $this->MailingList->saveField('all_categories', $is_all_categories);

            // Set the associated categories even if this user has signed up for all of them
            $this->MailingList->save(array(
                'Category' => explode(',', $member['MailingList']['categories'])
            ));
        }

        return $this->renderMessage(array(
            'title' => 'Done',
            'class' => 'success'
        ));
    }
    */

    private function __sendDailyEmail($events, $recipient, $testing = false)
    {
        list($result, $message) = $this->MailingList->sendDaily($recipient, $events, $testing);
        if ($result) {
            $this->Flash->success($message);
        } else {
            $this->Flash->error($message);
        }
        return $result;
    }

    private function __sendWeeklyEmail($events, $recipient, $testing = false)
    {
        list($result, $message) = $this->MailingList->sendWeekly($recipient, $events, $testing);
        if ($result) {
            $this->Flash->success($message);
        } else {
            $this->Flash->error($message);
        }
        return $result;
    }

    public function preview_daily($y = null, $m = null, $d = null)
    {
        if (! $y && ! $m && ! $d) {
            list($y, $m, $d) = $this->MailingList->getTodayYMD();
        }
        $events = $this->Event->getEventsOnDay($y, $m, $d, true);
        $recipients = $this->MailingList->getDailyRecipients();
        foreach ($recipients as $recipient) {
            $this->__sendDailyEmail($events, $recipient, true);
        }

        // Pull the flash messages set by __sendDailyEmail()
        $recipient_results = $this->Session->read('FlashMessage');
        $this->Session->delete('FlashMessage');

        $this->set(array(
            'title_for_layout' => 'Preview of Email',
            'recipient_email' => 'fake@email.addy',
            'recipient_id' => 0,
            'hash' => $this->MailingList->getHash("fakehash"),
            'date' => date("l, F jS, Y", mktime(0, 0, 0, $m, $d, $y)),
            'welcome_message' => $this->MailingList->getWelcomeMessage(),
            'settings_display' => array('event_types' => '(event types)', 'frequency' => '(frequency)')
        ));
        $this->set(compact('events', 'recipients', 'recipient_results'));
    }

    public function preview_weekly($y = null, $m = null, $d = null)
    {
        if (! $y && ! $m && ! $d) {
            list($y, $m, $d) = $this->MailingList->getTodayYMD();
        }
        $events = $this->Event->getEventsUpcomingWeek($y, $m, $d, true);
        $recipients = $this->MailingList->getWeeklyRecipients();
        foreach ($recipients as $recipient) {
            $this->__sendWeeklyEmail($events, $recipient, true);
        }

        // Pull the flash messages set by __sendWeeklyEmail()
        $recipient_results = $this->Session->read('FlashMessage');
        $this->Session->delete('FlashMessage');

        $this->set(array(
            'title_for_layout' => 'Preview of Weekly Events Email',
            'recipient_email' => 'fake@email.addy',
            'recipient_id' => 0,
            'hash' => $this->MailingList->getHash("fakehash"),
            'date' => date("l, F jS, Y", mktime(0, 0, 0, $m, $d, $y)),
            'welcome_message' => $this->MailingList->getWelcomeMessage(),
            'settings_display' => array('event_types' => '(event types)', 'frequency' => '(frequency)'),
            'is_delivery_day' => $this->MailingList->isWeeklyDeliveryDay()
        ));
        $this->set(compact('events', 'recipients', 'recipient_results'));
    }

    public function send_daily()
    {
        // Make sure there are recipients
        $recipients = $this->MailingList->getDailyRecipients();
        if (empty($recipients)) {
            return $this->renderMessage(array(
                'title' => 'Daily Emails Not Sent',
                'message' => 'No recipients found for today',
                'class' => 'notification'
            ));
        }

        // Make sure there are events to report
        list($y, $m, $d) = $this->MailingList->getTodayYMD();
        $events = $this->Event->getEventsOnDay($y, $m, $d, true);
        if (empty($events)) {
            $this->MailingList->markAllDailyAsProcessed($recipients, 'd');
            return $this->renderMessage(array(
                'title' => 'Daily Emails Not Sent',
                'message' => 'No events to inform anyone about today',
                'class' => 'notification'
            ));
        }

        // Send emails
        $email_addresses = array();
        foreach ($recipients as $recipient) {
            $this->__sendDailyEmail($events, $recipient);
            $email_addresses[] = $recipient['MailingList']['email'];
        }
        return $this->renderMessage(array(
            'title' => 'Daily Emails Sent',
            'message' => count($events).' total events, sent to '.count($recipients).' recipients: '.implode(', ', $email_addresses),
            'class' => 'success'
        ));
    }

    public function send_weekly()
    {
        // Make sure that today is the correct day
        if (! $this->MailingList->testing_mode && ! $this->MailingList->isWeeklyDeliveryDay()) {
            return $this->renderMessage(array(
                'title' => 'Weekly Emails Not Sent',
                'message' => 'Today is not the day of the week designated for delivering weekly emails.',
                'class' => 'notification'
            ));
        }

        // Make sure there are recipients
        $recipients = $this->MailingList->getWeeklyRecipients();
        if (empty($recipients)) {
            return $this->renderMessage(array(
                'title' => 'Weekly Emails Not Sent',
                'message' => 'No recipients found for this week',
                'class' => 'notification'
            ));
        }

        // Make sure there are events to report
        list($y, $m, $d) = $this->MailingList->getTodayYMD();
        $events = $this->Event->getEventsUpcomingWeek($y, $m, $d, true);
        if (empty($events)) {
            $this->MailingList->markAllWeeklyAsProcessed($recipients);
            return $this->renderMessage(array(
                'title' => 'Weekly Emails Not Sent',
                'message' => 'No events to inform anyone about this week',
                'class' => 'notification'
            ));
        }

        // Send emails
        $success_count = 0;
        foreach ($recipients as $recipient) {
            if ($this->__sendWeeklyEmail($events, $recipient)) {
                $success_count++;
            }
        }
        $events_count = 0;
        foreach ($events as $day => $d_events) {
            $events_count += count($d_events);
        }
        return $this->renderMessage(array(
            'title' => 'Weekly Emails Sent',
            'message' => $events_count.' total events, sent to '.$success_count.' recipients.',
            'class' => 'success'
        ));
    }

    public function join($email = null)
    {
        $categories = $this->Category->getAll();
        $days = $this->MailingList->getDays();

        if ($this->request->is('post')) {
            $error = false;
            $this->MailingList->create();
            $this->__readFormData();
            if ($this->__validateForm()) {
                if ($this->MailingList->save()) {
                    $this->MailingList->resetMemberCount();
                    return $this->renderMessage(array(
                        'title' => 'Subscribed',
                        'message' => 'You have been added to the mailing list.',
                        'class' => 'success'
                    ));
                }
                $join_url = Router::url();
                return $this->renderMessage(array(
                    'title' => 'Error Subscribing',
                    'message' => 'There was an error adding you to the mailing list.
						Please <a href="'.$join_url.'">try again</a> or
						<a href="/contact">contact support</a> if you need assistance.',
                    'class' => 'error'
                ));
            }
        } else {
            $this->__setDefaultValues();
            if ($email) {
                $this->request->data['MailingList']['email'] = trim(strtolower($email));
            }
        }

        $this->set(array(
            'title_for_layout' => 'Join Muncie Events Mailing List'
        ));
        $this->set(compact('categories', 'days'));
    }

    public function settings($recipient_id = null, $hash = null)
    {
        if ($this->request->is('ajax')) {
            $this->layout = 'ajax';
        }

        // Make sure link is valid
        if (! $recipient_id || $hash != $this->MailingList->getHash($recipient_id)) {
            return $this->renderMessage(array(
                'title' => 'Invalid Link',
                'message' => 'It appears that you clicked on a broken link. If you copied and
					pasted a URL to get here, you may not have copied the whole address.
					Please <a href="/contact">contact support</a> if you need assistance.',
                'class' => 'error'
            ));
        }

        // Make sure subscriber exists
        $this->MailingList->id = $recipient_id;
        if (! $this->MailingList->exists()) {
            return $this->renderMessage(array(
                'title' => 'Invalid Link',
                'message' => 'It looks like you\'re trying to change settings for a user who is no longer
					on our mailing list. Please <a href="/contact">contact support</a> if you need assistance.',
                'class' => 'error'
            ));
        }

        $recipient = $this->MailingList->read();

        if ($this->request->is('post')) {
            // Unsubscribe
            if ($this->request->data['MailingList']['unsubscribe']) {
                return $this->__unsubscribe($recipient_id);
            }

            $this->__readFormData();

            /*
            // If there's an associated user, update its email too
            $user_id = $this->MailingList->getAssociatedUserId();
            if ($user_id) {
                $this->User->id = $user_id;
                $this->User->saveField('email', $this->request->data['MailingList']['email']);
            }
            */

            // Update settings
            if ($this->__validateForm($recipient_id)) {
                if ($this->MailingList->save()) {
                    return $this->renderMessage(array(
                        'title' => 'Settings Updated',
                        'message' => 'Your mailing list settings have been updated.',
                        'class' => 'success'
                    ));
                }
                return $this->renderMessage(array(
                    'title' => 'Error Updating Settings',
                    'message' => 'Please try again, or <a href="/contact">contact support</a> for assistance.',
                    'class' => 'error'
                ));
            }
        } else {
            $this->__setDefaultValues($recipient);
        }
        $this->set(array(
            'title_for_layout' => 'Update Mailing List Settings',
            'days' => $this->MailingList->getDays(),
            'categories' => $this->Category->getAll()
        ));
        $this->set(compact('recipient', 'recipient_id', 'hash'));
    }

    /**
     * Sets $this->MailingList->data with submitted form data
     */
    private function __readFormData()
    {
        $this->request->data['MailingList']['email'] = strtolower(trim($this->request->data['MailingList']['email']));
        $this->MailingList->set('email', $this->request->data['MailingList']['email']);

        // If joining for the first time with default settings
        if (isset($this->request->data['MailingList']['settings'])) {
            if ($this->request->data['MailingList']['settings'] == 'default') {
                $this->MailingList->set('weekly', 1);
                $this->MailingList->set('all_categories', 1);
                $this->MailingList->set(array(
                    'Category' => array_keys($this->Category->getList())
                ));
                return;
            }
        }

        // All event types
        // If the user did not select 'all events', but has each category individually selected, set 'all_categories' to true
        $all_categories_selected = ($this->request->data['MailingList']['event_categories'] == 'all');
        if (! $all_categories_selected) {
            $selected_category_count = count($this->request->data['MailingList']['selected_categories']);
            $all_categories_count = $this->MailingList->Category->find('count');
            if ($selected_category_count == $all_categories_count) {
                $all_categories_selected = true;
            }
        }
        $this->MailingList->set('all_categories', $all_categories_selected);

        // Custom event types
        if (isset($this->request->data['MailingList']['selected_categories'])) {
            $this->MailingList->set(array(
                'Category' => array_keys($this->request->data['MailingList']['selected_categories'])
            ));
        }

        // Weekly frequency
        $weekly = $this->request->data['MailingList']['weekly'] || $this->request->data['MailingList']['frequency'] == 'weekly';
        $this->MailingList->set('weekly', $weekly);

        // Daily frequency
        $days = $this->MailingList->getDays();
        $daily = $this->request->data['MailingList']['frequency'] == 'daily';
        foreach ($days as $code => $day) {
            $value = $daily || $this->request->data['MailingList']["daily_$code"];
            $this->MailingList->set("daily_$code", $value);
        }
    }

    /**
     * Run special validation in addition to MailingList->validates(), returns TRUE if data is valid
     * @return boolean
     */
    private function __validateForm($recipient_id = null)
    {
        $error_found = false;

        // If updating an existing subscription
        if ($recipient_id) {
            $email_in_use = $this->MailingList->find('count', array(
                'conditions' => array(
                    'MailingList.email' => $this->request->data['MailingList']['email'],
                    'MailingList.id NOT' => $recipient_id
                )
            ));
            if ($email_in_use) {
                $error_found = true;
                $this->MailingList->validationErrors['email'] = 'Cannot change to that email address because another subscriber is currently signed up with it.';
            }

        // If creating a new subscription
        } else {
            $email_in_use = $this->MailingList->find('count', array(
                'conditions' => array(
                    'MailingList.email' => $this->request->data['MailingList']['email']
                )
            ));
            if ($email_in_use) {
                $error_found = true;
                $this->MailingList->validationErrors['email'] = 'That address is already subscribed to the mailing list.';
            }
        }
        $all_categories_selected = ($this->request->data['MailingList']['event_categories'] == 'all');
        $no_categories_selected = empty($this->request->data['MailingList']['selected_categories']);
        if (! $all_categories_selected && $no_categories_selected) {
            $error_found = true;
            $this->set('categories_error', 'At least one category must be selected.');
        }
        $frequency = $this->request->data['MailingList']['frequency'];
        $weekly = $this->request->data['MailingList']['weekly'];
        if ($frequency == 'custom' && ! $weekly) {
            $selected_days_count = 0;
            $days = $this->MailingList->getDays();
            foreach ($days as $code => $day) {
                $selected_days_count += $this->request->data['MailingList']["daily_$code"];
            }
            if (! $selected_days_count) {
                $error_found = true;
                $this->set('frequency_error', 'You\'ll need to pick either the weekly email or at least one daily email to receive.');
            }
        }
        return ($this->MailingList->validates() && ! $error_found);
    }

    private function __setDefaultValues($recipient = null)
    {
        $this->request->data = $this->MailingList->getDefaultFormValues($recipient);
    }

    private function __unsubscribe($recipient_id)
    {
        if ($this->MailingList->delete($recipient_id)) {

            // Un-associate associated User
            $user_id = $this->User->field('id', array('mailing_list_id' => $recipient_id));
            if ($user_id) {
                $this->User->id = $user_id;
                $this->User->saveField('mailing_list_id', null);
            }

            return $this->renderMessage(array(
                'title' => 'Unsubscribed',
                'message' => 'You have been removed from the mailing list.',
                'class' => 'success'
            ));
        }
        return $this->renderMessage(array(
            'title' => 'Error Unsubscribing',
            'message' => 'There was an error removing you from the mailing list. Please <a href="/contact">contact support</a> for assistance.',
            'class' => 'error'
        ));
    }

    public function reset_processed_time()
    {
        $recipients = $this->MailingList->find('list');
        foreach ($recipients as $r_id => $r_email) {
            $this->MailingList->id = $r_id;
            $this->MailingList->saveField('processed_daily', null);
            $this->MailingList->saveField('processed_weekly', null);
        }
        $this->Flash->success(count($recipients).' mailing list members\' "last processed" times reset.');
    }

    public function bulk_add()
    {
        if (! empty($this->request->data)) {
            $addresses = explode("\n", $this->request->data['email_addresses']);
            $retained_addresses = array();
            foreach ($addresses as $address) {
                $address = trim(strtolower($address));
                if (! $address) {
                    continue;
                }

                // Set
                $this->MailingList->create();
                $this->MailingList->set(array(
                    'email' => $address,
                    'weekly' => 1,
                    'all_categories' => 1
                ));

                if ($this->MailingList->validates()) {
                    if ($this->MailingList->save()) {
                        $this->Flash->success("$address added.");
                    } else {
                        $retained_addresses[] = $address;
                        $this->Flash->error("Error adding $address.");
                    }
                } else {
                    $retained_addresses[] = $address;
                    $this->Flash->error("$address: ".$this->MailingList->validationErrors['email']);
                }
            }
            $this->request->data['email_addresses'] = implode("\n", $retained_addresses);
        }

        $this->set(array(
            'title_for_layout' => 'Bulk Add - Mailing List'
        ));
    }
}
