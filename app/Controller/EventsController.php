<?php
App::uses('AppController', 'Controller');
class EventsController extends AppController
{
    public $name = 'Events';
    public $helpers = array('Tag');
    public $components = array(
        'Calendar',
        'Search.Prg',
        'RequestHandler'
    );
    public $uses = array('Event');
    public $paginate = array(
        'order' => array(
            'Event.date' => 'asc',
            'Event.time_start' => 'asc'
        ),
        'limit' => 15,
        'contain' => array(
            'User' => array(
                'fields' => array('id', 'name')
            ),
            'Category' => array(
                'fields' => array('id', 'name', 'slug')
            ),
            'EventSeries' => array(
                'fields' => array('id', 'title')
            ),
            'EventsImage' => array(
                'fields' => array('id', 'caption'),
                'Image' => array(
                    'fields' => array('id', 'filename')
                )
            ),
            'Tag' => array(
                'fields' => array('id', 'name')
            )
        )
    );
    public $auto_publish = false; // false puts new additions into moderation queue
    public $event_filter = array();
    public $admin_actions = array('publish', 'approve', 'moderate');

    /**
     * Used by the Search plugin
     * @var boolean
     */
    public $presetVars = true;

    public function beforeFilter()
    {
        parent::beforeFilter();

        // This must be set because the Search.Prg component somehow
        // changes it to User
        $this->modelClass = 'Event';

        $this->Auth->deny('edit', 'delete', 'mine', 'publish', 'approve', 'moderate');
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

        // Otherwise, only authors can modify authored content
        $author_only = array('edit', 'delete');
        if (in_array($this->action, $author_only)) {
            return $this->__isAdminOrAuthor($this->request->params['named']['id']);
        }

        // Logged-in users can access everything else
        return true;
    }

    private function __processCustomTags()
    {
        if (! isset($this->request->data['Event']['custom_tags'])) {
            return;
        }
        $custom_tags = trim($this->request->data['Event']['custom_tags']);
        if (empty($custom_tags)) {
            return;
        }
        $custom_tags = explode(',', $custom_tags);

        // Force lowercase and remove leading/trailing whitespace
        foreach ($custom_tags as &$ct) {
            $ct = strtolower(trim($ct));
        }
        unset($ct);

        // Remove duplicates
        $custom_tags = array_unique($custom_tags);

        $this->Event->Tag = $this->Event->Tag;
        foreach ($custom_tags as $ct) {
            // Skip over blank tags
            if ($ct == '') {
                continue;
            }

            // Get ID of existing tag, if it exists
            $tag_id = $this->Event->Tag->field('id', array('name' => $ct));

            // Include this tag if it exists and is selectable
            if ($tag_id) {
                $selectable = $this->Event->Tag->field('selectable', array('id' => $tag_id));
                if ($selectable) {
                    $this->request->data['Tag'][] = $tag_id;
                } else {
                    continue;
                }

            // Create the custom tag if it does not already exist
            } else {
                $this->Event->Tag->create();
                $this->Event->Tag->set(array(
                    'name' => $ct,
                    'user_id' => $this->Auth->user('id'),
                    'parent_id' => $this->Event->Tag->getUnlistedGroupId(), // 'Unlisted' group
                    'listed' => 0,
                    'selectable' => 1
                ));
                $this->Event->Tag->save();
                $this->request->data['Tag'][] = $this->Event->Tag->id;
            }
        }
        $this->request->data['Tag'] = array_unique($this->request->data['Tag']);
        $this->request->data['Event']['custom_tags'] = '';
    }

    private function __processImageData()
    {
        if (! isset($this->request->data['Image'])) {
            $this->request->data['Image'] = array();
        }
        if (empty($this->request->data['Image'])) {
            return;
        }
        $weight = 1;
        $this->request->data['EventsImage'] = array();
        foreach ($this->request->data['Image'] as $image_id => $caption) {
            $this->request->data['EventsImage'][] = array(
                'image_id' => $image_id,
                'weight' => $weight,
                'caption' => $caption
            );
            $weight++;
        }
        unset($this->request->data['Image']);
    }

    /**
     * Returns true if the user is logged in as an admin or if
     * this request's "id" named parameter corresponds to an event
     * that the logged-in user created
     * @return boolean
     */
    private function __isAdminOrAuthor($event_id)
    {
        if ($this->Auth->user('role') == 'admin') {
            return true;
        }
        $user_id = $this->Auth->user('id');
        if ($user_id) {
            $this->Event->id = $event_id;
            $author_id = $this->Event->field('user_id');
            if ($author_id) {
                return $user_id == $author_id;
            }
        }
        return false;
    }

    /**
     * Sets open graph meta tags for the layout
     * @param array $event
     */
    private function __setOgMetaTags($event)
    {
        $title = $event['Event']['title'];
        $title .= ' ('.date('M j, Y', strtotime($event['Event']['date'])).')';
        $og_meta_tags = array(
            'og:title' => $title,
            'og:description' => $event['Event']['description'],
            'og:type' => 'article',
            'og:url' => Router::url(array(
                'controller' => 'events',
                'action' => 'view',
                'id' => $event['Event']['id']
            ), true)
        );
        /* Adds this event's images to the Open Graph meta tags.
         * Since Facebook's seems to pick the largest image as the default
         * (http://stackoverflow.com/questions/13424780/facebook-multiple-ogimage-tags-which-is-default)
         * this event's main image (the first one) is provided in 'full'
         * size and the rest are provided in 'small' size. */
        if (isset($event['EventsImage'])) {
            foreach ($event['EventsImage'] as $n => $eventsImage) {
                $filename = $eventsImage['Image']['filename'];
                $size = ($n == 0) ? 'full' : 'small';
                $og_meta_tags['og:image'][] = FULL_BASE_URL.'/'.IMAGES_URL.'events/'.$size.'/'.$filename;
            }
        }
        $this->set(compact('og_meta_tags'));
    }

    private function __rejectSpam()
    {
        $spammy_words = array(
            'viagra',
            'cialis'
        );
        foreach ($spammy_words as $spammy_word) {
            if (stripos($this->request->data['Event']['title'], $spammy_word) !== false) {
                $this->set(array(
                    'title_for_layout' => 'Uh oh. Something smells spammy.',
                    'spammy_word' => $spammy_word
                ));
                $this->response->statusCode(403);
                $this->render('spam_rejection');
                return true;
            }
        }
        return false;
    }

    public function index()
    {
        $filters = $this->request->params['named'];
        $events = $this->Event->getPage(null, $filters);
        $this->set(array(
            'title_for_layout' => ucwords($this->Event->getFilterName($filters)),
            'events' => $events,
            'next_start_date' => $this->Event->getNextStartDate($events)
        ));
    }

    public function ics()
    {
        $this->response->type('text/calendar');
        $this->response->download('foo.bar');
        $this->layout = 'ics/default';
        return $this->render('/Events/ics/view');
    }

    public function view($id = null)
    {
        $event = $this->Event->find('first', array(
            'conditions' => array('Event.id' => $id),
            'contain' => array(
                'User' => array(
                    'fields' => array('User.id', 'User.name', 'User.email')
                ),
                'Category' => array(
                    'fields' => array('Category.id', 'Category.name', 'Category.slug')
                ),
                'EventSeries' => array(
                    'fields' => array('EventSeries.id', 'EventSeries.title')
                ),
                'Tag' => array(
                    'fields' => array('Tag.id', 'Tag.name')
                ),
                'EventsImage' => array(
                    'fields' => array('EventsImage.id', 'EventsImage.caption'),
                    'Image' => array(
                        'fields' => array('Image.id', 'Image.filename')
                    )
                )
            )
        ));

        if (empty($event)) {
            throw new NotFoundException("Event #$id not found");
        }

        $this->__setOgMetaTags($event);
        $this->set(array(
            'title_for_layout' => $event['Event']['title'].' ('.date('M j, Y', strtotime($event['Event']['date'])).')',
            'event' => $event
        ));
    }

    public function delete()
    {
        if ($this->request->is('post')) {
            foreach ($this->request->pass as $event_id) {
                $this->Event->id = $event_id;
                $exists = $this->Event->exists();
                $result = $exists && $this->Event->delete();
                if (! $result) {
                    break;
                }
            }
            $this->__redirectAfterDelete($event_id, $exists, $result);
        } else {
            $referer = $this->referer();
            return $this->renderMessage(array(
                'title' => 'Invalid Attempt to Delete Event',
                'message' => 'Event can not be deleted that way. A proper link must be followed.',
                'class' => 'error',
                'back' => ($referer == '/') ? false : $referer
            ));
        }
    }

    private function __redirectAfterDelete($id, $exists, $delete_successful)
    {
        $referer = $this->referer();
        $view_path = Router::url(array('controller' => 'events', 'action' => 'view', 'id' => $id));
        if (stripos($referer, $view_path) !== false) {
            if (! $exists) {
                return $this->renderMessage(array(
                    'title' => 'Event Not Found',
                    'message' => 'The event you were trying to remove may have already been removed.',
                    'class' => 'error'
                ));
            } elseif ($delete_successful) {
                return $this->renderMessage(array(
                    'title' => 'Removed',
                    'message' => 'That event has been successfully removed.',
                    'class' => 'success'
                ));
            } else {
                return $this->renderMessage(array(
                    'title' => 'Error Removing Event',
                    'message' => 'There was a problem removing that event. Please try again.',
                    'class' => 'error'
                ));
            }
        } else {
            if (! $exists) {
                $this->Flash->error('Event not found. It may have already been removed.');
            } elseif ($delete_successful) {
                $this->Flash->success('Event removed.');
            } else {
                $this->Flash->error('Error removing event.');
            }
            $this->redirect($referer);
        }
    }

    public function moderate()
    {
        // Collect all unapproved events
        $unapproved = $this->Event->find('all', array(
            'conditions' => array(
                'Event.approved_by' => null
            ),
            'order' => array(
                'Event.created ASC',
                'Event.date ASC'
            ),
            'contain' => array(
                'User' => array('fields' => array(
                    'User.id', 'User.name'
                )),
                'Tag',
                'Category',
                'EventsImage' => array(
                    'fields' => array('EventsImage.id', 'EventsImage.caption'),
                    'Image' => array(
                        'fields' => array('Image.id', 'Image.filename')
                    )
                ),
                'EventSeries' => array(
                    'fields' => array('EventSeries.id', 'EventSeries.title')
                )
            )
        ));

        // Find sets of identical events (belonging to the same series
        // and with the same modified date) and remove all but the first
        $identical_series_members = array();
        foreach ($unapproved as $k => $event) {
            if (empty($event['EventSeries'])) {
                continue;
            }
            $event_id = $event['Event']['id'];
            $series_id = $event['EventSeries']['id'];
            $modified = $event['Event']['modified'];
            if (isset($identical_series_members[$series_id][$modified])) {
                unset($unapproved[$k]);
            }
            $identical_series_members[$series_id][$modified][] = $event_id;
        }

        //identicals -> series_id -> modified_date -> array(id, id, id)

        // Prevent unpublished events associated with a series from being excluded
        /*
        $this->Event->EventSeries->hasMany['Event']['conditions'] = null;
        $unapproved['EventSeries'] = $this->Event->EventSeries->find('all', array(
            'conditions' => array('published' => 0),
            'order' => 'created ASC',
            'contain' => array(
                'User' => array('fields' => array('User.id', 'User.name')),
                'Event' => $event_contain
            )
        ));
        */


        $this->set(array(
            'title_for_layout' => 'Review Unapproved Content',
            'unapproved' => $unapproved,
            'identical_series_members' => $identical_series_members
        ));
    }

    private function __formatFormData()
    {
        if (! $this->request->data['Event']['time_start']) {
            // Fixes bug where midnight is saved as null
            $this->request->data['Event']['time_start'] = '00:00:00';
        }
        if ($this->request->data['Event']['has_end_time']) {
            if (! $this->request->data['Event']['time_end']) {
                // Fixes bug where midnight is saved as null
                $this->request->data['Event']['time_end'] = '00:00:00';
            }
        } else {
            $this->request->data['Event']['time_end'] = null;
        }
        /*
        if (! isset($this->request->data['Event']['series_id'])) {
            $this->request->data['Event']['series_id'] = null;
        }
        */
        $this->request->data['Event']['description'] = strip_tags(
            $this->request->data['Event']['description'],
            $this->Event->allowed_tags
        );

        // Fixes bug that prevents CakePHP from deleting all tags
        if (! isset($this->request->data['Tag'])) {
            $this->request->data['Tag'] = array();
        }
    }

    private function __prepareEventForm()
    {
        $user_id = $this->Auth->user('id');
        $this->set(array(
            'categories' => $this->Event->Category->getList(),
            'available_tags' => $this->Event->Tag->getList(),
            'previous_locations' => $this->Event->getPreviousLocations($user_id),
            'user_id' => $user_id
        ));
        if (! isset($this->request->data['Event']['update_series'])) {
            $this->request->data['Event']['update_series'] = 0;
        }

        // Determine what optional attributes this Event has
        $event = $this->request->data['Event'];
        if ($this->action == 'add' || $this->action == 'edit_series') {
            $has_series = count(explode(',', $event['date'])) > 1;
            $has_end_time = isset($event['has_end_time']) ? $event['has_end_time'] : false;
        } elseif ($this->action == 'edit') {
            $has_series = isset($event['series_id']) ? (bool) $event['series_id'] : false;
            $has_end_time = isset($event['time_end']) && $event['time_end'];
        }
        $this->set(array(
            'has' => array(
                'series' => $has_series,
                'end_time' => $has_end_time,
                'address' => isset($event['address']) && $event['address'],
                'cost' => isset($event['cost']) && $event['cost'],
                'ages' => isset($event['age_restriction']) && $event['age_restriction'],
                'source' => isset($event['source']) && $event['source']
            )
        ));

        // Prepare date picker
        if ($this->action == 'add' || $this->action == 'edit_series') {
            $date_field_values = array();
            if (empty($this->request->data['Event']['date'])) {
                $default_date = 0; // Today
                $datepicker_preselected_dates = '[]';
            } else {
                //strpos($this->request->data['Event']['date'], '/');
                $dates = explode(',', $this->request->data['Event']['date']);
                foreach ($dates as $date) {
                    list($year, $month, $day) = explode('-', $date);
                    if (! isset($default_date)) {
                        $default_date = "$month/$day/$year";
                    }
                    $date_field_values[] = "$month/$day/$year";
                }
                $dates_for_js = array();
                foreach ($date_field_values as $date) {
                    $dates_for_js[] = "'".$date."'";
                }
                $dates_for_js = implode(',', $dates_for_js);
                $datepicker_preselected_dates = "[$dates_for_js]";
            }
            $this->set(compact('default_date', 'datepicker_preselected_dates'));
            $this->request->data['Event']['date'] = implode(',', $date_field_values);
        } elseif ($this->action == 'edit') {
            list($year, $month, $day) = explode('-', $this->request->data['Event']['date']);
            $this->request->data['Event']['date'] = "$month/$day/$year";
        }

        // Collect more image data:
        // - Populate $this->request->data['Image'] with data about selected images
        // - Provide $images to the view with a list of all of this User's images
        $this->loadModel('Image');
        $images = $this->Event->User->getImagesList($user_id);
        if (! empty($this->request->data['EventsImage'])) {
            foreach ($this->request->data['EventsImage'] as $association) {
                $image_id = $association['image_id'];
                if (isset($images[$image_id])) {
                    $this->request->data['Image'][$image_id] = array(
                        'id' => $image_id,
                        'filename' => $images[$image_id]
                    );
                } else {
                    /* If an image is in $this->request->data['EventsImage']
                     * but not in the current user's images, then the user is
                     * probably an admin editing someone else's event. */
                    $this->Image->id = $image_id;
                    $filename = $this->Image->field('filename');
                    if ($filename) {
                        $images[$image_id] = $filename;
                        $this->request->data['Image'][$image_id] = array(
                            'id' => $image_id,
                            'filename' => $images[$image_id]
                        );
                    }
                }
            }
        }
        $this->set('images', $images);
    }

    public function add()
    {
        $user_id = $this->Auth->user('id');
        $logged_in = (boolean) $user_id;
        $autopublish = $this->User->canAutopublish($user_id);
        if (! $logged_in) {
            $this->prepareRecaptcha();
        }
        if ($this->request->is('post')) {
            if ($this->__rejectSpam()) {
                return;
            }
            $dates = explode(',', $this->request->data['Event']['date']);
            $is_series = count($dates) > 1;
            $user_id = $this->Auth->user('id');
            $error_flag = false;

            // Process data
            $this->__formatFormData();
            $this->__processCustomTags();
            $this->__processImageData();

            // Correct date format
            foreach ($dates as &$date) {
                $date = trim($date);
                $timestamp = strtotime($date);
                $date = date('Y-m-d', $timestamp);
            }
            unset($date);
            if ($this->Auth->user('role') == 'admin') {
                $this->request->data['Event']['approved_by'] = $this->Auth->user('id');
                $autopublish = 1;
            }
            $this->request->data['Event']['user_id'] = $user_id;
            $this->request->data['Event']['published'] = $autopublish;

            $this->Event->set($this->request->data);

            // Validation is disabled in calls to Event::save()
            // because ReCAPTCHA validation fails if called twice
            if ($this->Event->validates()) {

                // Process submission of an event series
                if ($is_series) {
                    if (trim($this->request->data['EventSeries']['title']) == '') {
                        $this->request->data['EventSeries']['title'] = $this->request->data['Event']['title'];
                    }
                    $this->Event->EventSeries->create();
                    $this->Event->EventSeries->set('title', $this->request->data['EventSeries']['title']);
                    $this->Event->EventSeries->set('user_id', $user_id);
                    $this->Event->EventSeries->set('published', $autopublish);
                    if ($this->Event->EventSeries->save()) {
                        $this->request->data['Event']['series_id'] = $this->Event->EventSeries->id;

                        // Prevents saveAssociated() from creating a new EventSeries for every Event
                        unset($this->request->data['EventSeries']);

                        $dates = array_unique($dates);
                        sort($dates);
                        $redirect_to_event_id = null;
                        foreach ($dates as $date) {
                            $this->request->data['Event']['date'] = $date;
                            $this->Event->create();
                            if (! $this->Event->saveAssociated($this->request->data, array('validate' => false))) {
                                $error_flag = true;
                            } elseif (! $redirect_to_event_id) {
                                $redirect_to_event_id = $this->Event->id;
                            }
                        }
                    } else {
                        $error_flag = true;
                        $this->Flash->error('There was a problem creating this event series. Please try again, or <a href="/contact">contact us</a> if you need assistance.');
                    }

                // Process submission of a single event
                } else {
                    $this->request->data['Event']['date'] = date('Y-m-d', strtotime(trim($this->request->data['Event']['date'])));
                    unset($this->request->data['EventSeries']);
                    if ($this->Event->saveAssociated($this->request->data, array('validate' => false))) {
                        $redirect_to_event_id = $this->Event->id;
                    } else {
                        $error_flag = true;
                    }
                }

                if (! $error_flag) {
                    $noun_verb1 = $is_series ? 'events have' : 'event has';
                    $this->request->data = null;

                    // If event is auto-published
                    if ($autopublish) {
                        $this->Flash->success("Your $noun_verb1 been added to the calendar.");
                        $this->redirect(array(
                            'controller' => 'events',
                            'action' => 'view',
                            'id' => $redirect_to_event_id
                        ));

                    // If event is now in moderation queue
                    } else {
                        $noun_verb2 = $is_series ? 'they are' : 'it is';
                        $noun = $is_series ? 'they' : 'it';
                        $add_url = Router::url(array('controller' => 'events', 'action' => 'add'));
                        return $this->renderMessage(array(
                            'title' => ($is_series ? 'Events' : 'Event').' Submitted',
                            'message' => "Your $noun_verb1 been submitted for review. Once $noun_verb2 approved by an administrator, $noun will appear on the calendar. <a href=\"$add_url\">Add another event</a>",
                            'class' => 'success'
                        ));
                    }
                }
            }
        } else {
            $this->request->data['Event']['date'] = '';
            foreach (array('time_start', 'time_end') as $field) {
                if (isset($this->request->data['Event'][$field])) {
                    // Fixes bug where midnight is represented as noon
                    if ($this->request->data['Event'][$field] == '00:00:00') {
                        $this->request->data['Event'][$field] = '24:00:00';
                    }
                } else {
                    // For some reason, noon has to be specified as 0pm intead of 12pm
                    $this->request->data['Event'][$field] = array(
                        'hour' => ($field == 'time_start') ? '0' : '1',
                        'min' => '00',
                        'meridian' => 'pm'
                    );
                }
            }
        }

        $this->__prepareEventForm();
        $this->set(array(
            'title_for_layout' => 'Submit an Event',
            'autopublish' => $autopublish
        ));
        $this->render('form');
    }

    public function edit($id)
    {
        if (! $id) {
            return $this->renderMessage(array(
                'title' => 'Error',
                'message' => "No ID specified. Which event do you want to edit?",
                'class' => 'error'
            ));
        }
        $this->Event->id = $id;
        if (! $this->Event->exists()) {
            return $this->renderMessage(array(
                'title' => 'Event Not Found',
                'message' => 'Sorry, it looks like you were trying to edit an event that doesn\'t exist anymore.',
                'class' => 'error'
            ));
        }
        if ($this->request->is('put') || $this->request->is('post')) {
            // Process data
            $this->__formatFormData();
            $this->__processCustomTags();
            $this->__processImageData();

            // Correct date format
            $timestamp = strtotime($this->request->data['Event']['date']);
            $this->request->data['Event']['date'] = date('Y-m-d', $timestamp);

            $this->Event->set($this->request->data);
            if ($this->Event->validates()) {
                $this->Event->removeImageAssociations($id);
                $this->Event->removeTagAssociations($id);
                if ($this->Event->saveAssociated($this->request->data)) {
                    if ($this->request->data['Event']['update_series']) {
                        $this->Event->updateSeries($this->request->data);
                    }
                    $is_admin = $this->Auth->user('role') == 'admin';
                    $action = ($is_admin && $this->Event->approve()) ? 'updated and approved' : 'updated';
                    $this->Flash->success("Event $action.");
                    $this->redirect(array(
                        'controller' => 'events',
                        'action' => 'view',
                        'id' => $id
                    ));
                } else {
                    $this->Flash->error('There was a problem editing that event.');
                }
            }
        } else {
            $this->request->data = $this->Event->read();
        }

        $this->__prepareEventForm();
        $this->set(array(
            'title_for_layout' => 'Edit Event: '.$this->request->data['Event']['title'],
            'event_id' => $id
        ));
        $this->render('form');
    }

    public function edit_series($series_id)
    {
        // Get information about series
        $this->Event->EventSeries->id = $series_id;
        if (! $this->Event->EventSeries->exists()) {
            return $this->renderMessage(array(
                'title' => 'Event Series Not Found',
                'message' => 'Sorry, it looks like you were trying to edit an event series that doesn\'t exist anymore.',
                'class' => 'error'
            ));
        }
        $saveDisplayField = $this->Event->displayField;
        $this->Event->displayField = 'date';
        $events = $this->Event->find('list', array(
            'conditions' => array('series_id' => $series_id),
            'contain' => false
        ));
        $this->Event->displayField = $saveDisplayField;
        $dates = array_values($events);

        // Pick an arbitrary event in the series
        $event_ids = array_keys($events);
        $this->Event->id = $event_ids[0];
        $this->Event->set('date', implode(',', $dates));

        if ($this->request->is('put') || $this->request->is('post')) {
            $dates = explode(',', $this->request->data['Event']['date']);

            // Process data
            $this->__formatFormData();
            $this->__processCustomTags();
            foreach ($dates as &$date) {
                $date = date('Y-m-d', strtotime(trim($date)));
            }
            unset($date);
            // Prevent anonymously-submitted events from being saved with user id 0 instead of null
            if (! $this->request->data['Event']['user_id']) {
                $this->request->data['Event']['user_id'] = null;
            }

            $this->Event->set($this->request->data);
            if ($this->Event->validates()) {
                // Update series title
                if (trim($this->request->data['EventSeries']['title']) == '') {
                    $this->request->data['EventSeries']['title'] = $this->request->data['Event']['title'];
                }
                $this->Event->EventSeries->saveField('title', $this->request->data['EventSeries']['title']);

                // Update/add event for each submitted date
                $error_flag = false;
                foreach ($dates as $date) {
                    $event_id = array_search($date, $events);
                    if ($event_id === false) {
                        $this->Event->create($this->request->data);
                    } else {
                        $this->Event->id = $event_id;
                        $this->Event->set($this->request->data);
                    }
                    $this->Event->set('date', $date);
                    if (! $this->Event->save(null, false)) {
                        $error_flag = true;
                    }
                }

                // Remove events
                foreach ($events as $event_id => $date) {
                    if (! in_array($date, $dates)) {
                        $this->Event->delete($event_id);
                    }
                }

                if ($error_flag) {
                    $this->Flash->error('There was an error updating the events in this series.');
                } else {
                    $this->Flash->success('Series updated.');
                    if ($this->Event->EventSeries->field('published')) {
                        // If event is published, go to series view page
                        $this->redirect(array('controller' => 'event_series', 'action' => 'view', 'id' => $series_id));
                    } else {
                        // Otherwise, it's assumed an admin needs to be redirected back to the moderation page
                        $this->redirect(array('controller' => 'events', 'action' => 'moderate'));
                    }
                }
            }
        } else {
            $this->request->data = $this->Event->read();
            $this->Flash->set('All events in this series will be overwritten.');
        }

        $this->request->data['Event']['date'] = implode(',', $dates);
        $this->__prepareEventForm();
        $this->set(array(
            'title_for_layout' => 'Edit Event Series: '.$this->Event->EventSeries->field('title')
        ));
        $this->render('form');
    }

    public function category($slug)
    {
        $category = $this->Event->Category->find('first', array(
            'conditions' => array('slug' => $slug),
            'fields' => array('id', 'name', 'slug'),
            'contain' => false
        ));
        if (empty($category)) {
            return $this->renderMessage(array(
                'title' => 'Category Not Found',
                'message' => "Sorry, but we couldn't find the category \"$slug\".",
                'class' => 'error'
            ));
        }
        $filters = array('category' => $category['Category']['id']);
        $events = $this->Event->getPage(null, $filters);
        $this->set(array(
            'title_for_layout' => $category['Category']['name'],
            'category' => $category,
            'events' => $events,
            'next_start_date' => $this->Event->getNextStartDate($events)
        ));
    }

    public function tag($slug = '')
    {
        $direction = isset($this->request->named['direction']) ? $this->request->named['direction'] : 'future';

        // Get tag
        $tag_id = $this->Event->Tag->getIdFromSlug($slug);
        $tag = $this->Event->Tag->find('first', array(
            'conditions' => array('id' => $tag_id),
            'fields' => array('id', 'name'),
            'contain' => false
        ));
        if (empty($tag)) {
            return $this->renderMessage(array(
                'title' => 'Tag Not Found',
                'message' => "Sorry, but we couldn't find that tag ($slug)",
                'class' => 'error'
            ));
        }


        // Set up paginate to find past/upcoming events with the correct tag
        $this->paginate[0] = ($direction == 'future') ? 'upcomingWithTag' : 'pastWithTag';
        $this->paginate['conditions'] = array('Tag.id' => $tag_id);

        // Get a count of the events found in the other direction (past/future)
        $count_other_direction = $direction == 'future'
            ? $this->Event->getCountPastWithTag($tag_id)
            : $this->Event->getCountUpcomingWithTag($tag_id);

        $this->set(array(
            'title_for_layout' => 'Tag: '.ucwords($tag['Tag']['name']),
            'tag' => $tag,
            'events' => $this->Event->arrangeByDate($this->paginate()),
            'slug' => $slug,
            'direction' => $direction,
            'count_other_direction' => $count_other_direction
        ));
    }

    public function accordion($start_date = null)
    {
        $filter = array();
        if (isset($this->params['named']['category'])) {
            $filter['category'] = $this->params['named']['category'];
        } elseif (isset($this->params['named']['tag'])) {
            $filter['tags_included'] = $this->params['named']['tag'];
        }
        if (! ($start_date)) {
            $start_date = date('Y-m-d');
        }
        $events = $this->Event->getPage($start_date, $filter);
        $next_start_date = $this->Event->getNextStartDate($events);

        $this->set(compact('events', 'next_start_date'));
        $this->layout = 'ajax';
    }

    public function search()
    {
        $this->Prg->commonProcess();
        $this->set(array(
            'title_for_layout' => 'Search Results',
        ));
        if (! isset($this->passedArgs['filter'])) {
            return;
        } else {
            $term = $this->passedArgs['filter'] = trim(urldecode($this->passedArgs['filter']));
            if (empty($term)) {
                return;
            }
            $this->request->data['Event']['filter'] = $term;
            $this->passedArgs['tags'] = $term;
        }

        // Determine the direction (past or future)
        $direction = isset($this->passedArgs['direction']) ? $this->passedArgs['direction'] : 'future';

        // Get search results
        $this->paginate['conditions'] = $this->Event->parseCriteria($this->passedArgs);
        $events = $this->Event->arrangeByDate($this->paginate());

        if ($direction == 'all') {
            $current_date = date('Y-m-d');
            $counts = array('upcoming' => 0, 'past' => 0);
            foreach ($events as $date => $date_events) {
                if ($date >= $current_date) {
                    $counts['upcoming']++;
                } else {
                    $counts['past']++;
                }
            }
            $this->set(compact('counts'));
        } else {
            // Determine if there are events in the opposite direction
            $this->passedArgs['direction'] = ($direction == 'future') ? 'past' : 'future';
            $opposite_direction_count = $this->Event->find('count', array(
                'conditions' => $this->Event->parseCriteria($this->passedArgs)
            ));
            $this->set('events_found_in_other_direction', $opposite_direction_count);
        }

        $this->set(array(
            'direction' => $direction,
            'events' => $events,
            'tags' => $this->Event->Tag->search($term, true, $direction),
            'term' => $term,
            'direction_adjective' => ($direction == 'future') ? 'upcoming' : $direction
        ));
    }

    public function validate()
    {
        if (! $this->request->is('put') && ! $this->request->is('post')) {
            return;
        }
        $this->__formatFormData();
        $this->Event->set($this->request->data);
        $validates = $this->Event->validates();
    }

    public function search_autocomplete($direction = 'future')
    {
        App::uses('Sanitize', 'Utility');
        $string_to_complete = Sanitize::clean($_GET['term']);
        $limit = 10;
        $qualifying_tag_ids = $this->Event->Tag->getIDsWithEvents($direction);

        // Tag.name will be compared via LIKE to each of these,
        // in order, until $limit tags are found.
        $like_conditions = array(
            $string_to_complete,
            $string_to_complete.' %',
            $string_to_complete.'%',
            '% '.$string_to_complete.'%',
            '%'.$string_to_complete.'%'
        );

        // Collect tags up to $limit
        $tags = array();
        foreach ($like_conditions as $like) {
            if (count($tags) == $limit) {
                break;
            }
            $conditions = array(
                'Tag.name LIKE' => $like,
                'Tag.listed' => 1,
                'Tag.selectable' => 1,
                'Tag.id' => $qualifying_tag_ids
            );
            $results = $this->Event->Tag->find('all', array(
                'fields' => array('Tag.id', 'Tag.name'),
                'conditions' => $conditions,
                'contain' => false,
                'limit' => $limit - count($tags)
            ));

            if (! empty($results)) {
                foreach ($results as $result) {
                    if (! in_array($result['Tag']['name'], $tags)) {
                        $tag_id = $result['Tag']['id'];
                        $tags[$tag_id] = $result['Tag']['name'];
                    }
                }
            }
        }
        $this->set(compact('tags'));
        $this->layout = 'ajax';
    }

    public function day($month = null, $day = null, $year = null)
    {
        if (! $year || ! $month || ! $day) {
            $this->redirect('/');
        }

        // Zero-pad day and month numbers
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $events = $this->Event->getFilteredEventsOnDates("$year-$month-$day");
        $events = array_pop($events);
        if (isset($this->params['requested'])) {
            return $events;
        }
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        $date_string = date('F j, Y', $timestamp);
        $this->set(compact('events', 'month', 'year', 'day'));
        $this->set(array(
            'title_for_layout' => 'Events on '.$date_string,
            'displayed_date' => date('l F j, Y', $timestamp)
        ));
    }

    /**
     * Produces a view with JS used by the datepicker in the header
     */
    public function datepicker_populated_dates()
    {
        $results = $this->Event->getPopulatedDates();
        $dates = array();
        foreach ($results as $result) {
            list($year, $month, $day) = explode('-', $result['Event']['date']);
            $dates["$month-$year"][] = $day;
        }
        $this->set(compact('dates'));
        $this->layout = 'blank';
    }

    public function past_locations()
    {
        $locations = $this->Event->getLocations('past');
        $this->set(array(
            'title_for_layout' => 'Locations of Past Events',
            'past_locations' => $locations,
            'list_past_locations' => true
        ));
    }

    public function location($location = null, $direction = 'future')
    {
        $this->paginate['conditions'] = array(
            'Event.published' => 1,
            'Event.location' => $location
        );
        $this->paginate['order'] = 'Event.date ASC';
        if ($direction == 'future') {
            $this->paginate['conditions']['Event.date >='] = date('Y-m-d');
        } elseif ($direction == 'past') {
            $this->paginate['conditions']['Event.date <'] = date('Y-m-d');
        }

        if ($direction == 'future' || $direction == 'past') {
            $conditions = array(
                'Event.published' => 1,
                'Event.location' => $location
            );
            if ($direction == 'future') {
                $conditions['Event.date <'] = date('Y-m-d');
            } elseif ($direction == 'past') {
                $conditions['Event.date >='] = date('Y-m-d');
            }
            $count_other_direction = $this->Event->find('count', compact('conditions'));
        } else {
            $count_other_direction = false;
        }

        $this->set(array(
            'title_for_layout' => $location,
            'location' => $location,
            'events' => $this->Event->arrangeByDate($this->paginate()),
            'direction' => $direction,
            'count_other_direction' => $count_other_direction
        ));
    }

    public function today()
    {
        $today_stamp = time();
        $this->redirect(array(
            'controller' => 'events',
            'action' => 'day',
            date('m', $today_stamp),
            date('d', $today_stamp),
            date('Y', $today_stamp)
        ));
    }

    public function tomorrow()
    {
        $tomorrow_stamp = strtotime('+1 day');
        $this->redirect(array(
            'controller' => 'events',
            'action' => 'day',
            date('m', $tomorrow_stamp),
            date('d', $tomorrow_stamp),
            date('Y', $tomorrow_stamp)
        ));
    }

    public function getAddress($location = '')
    {
        $this->layout = 'blank';
        $this->set('address', $this->Event->getAddress($location));
    }

    public function publish($id = null)
    {
        $this->Event->id = $id;
        if (! $this->Event->exists()) {
            $this->Flash->error('Cannot publish. Event with ID# '.$id.' not found.');
            $this->redirect($this->referer());
        }
        $this->Event->saveField('published', 1);
        $url = Router::url(array('controller' => 'events', 'action' => 'view', 'id' => $id));
        $this->Flash->success("Event published. <a href=\"$url\">Go to event page</a>");
        $this->redirect($this->referer());
    }

    public function approve($id = null)
    {
        $ids = $this->request->pass;
        $admin_id = $this->Auth->user('id');
        if (empty($ids)) {
            $this->Flash->error('No events approved because no IDs were specified');
        } else {
            $series_to_approve = array();
            foreach ($ids as $id) {
                $this->Event->id = $id;
                if (! $this->Event->exists()) {
                    $this->Flash->error('Cannot approve. Event with ID# '.$id.' not found.');
                }
                $this->Event->saveField('approved_by', $admin_id);
                $this->Event->saveField('published', 1);
                if ($series_id = $this->Event->field('series_id')) {
                    $series_to_approve[$series_id] = true;
                }
                $url = Router::url(array(
                    'controller' => 'events',
                    'action' => 'view',
                    'id' => $id
                ));
                $this->Flash->success("Event #$id approved. <a href=\"$url\">Go to event page</a>");
            }
            foreach ($series_to_approve as $series_id => $flag) {
                $this->Event->EventSeries->id = $series_id;
                $this->Event->EventSeries->saveField('published', true);
            }
        }
        $this->redirect($this->referer());
    }









    public function teasers()
    {
        /* This is only ever requested, but there were still
         * inexplicable 'Error:  The view for EventsController::teasers() was not found.'
         * errors cropping up after what seemed like any login action (logging in or restarting session). */
        if (! isset($this->params['requested'])) {
            $this->redirect('/');
        }
        $year = isset($this->params['y']) ? $this->params['y']: date('Y');
        $month = isset($this->params['m']) ? str_pad($this->params['m'], 2, '0', STR_PAD_LEFT) : date('m');
        return $this->Event->getTeasers($year, $month, 10);
    }

    public function mine()
    {
        $this->set(array(
            'events' => $this->Event->getUsersNonSeriesEvents($this->Auth->user('id')),
            'series' => $this->Event->EventSeries->getUsersEventSeries($this->Auth->user('id')),
            'title_for_layout' => 'My Events'
        ));
    }

    public function getBigCalendarEvents($month = null, $year = null)
    {
        return $this->Event->getBigCalendarEvents($month, $year);
    }

    public function getDaysWithEvents($month, $year)
    {
        return $this->Event->getDaysWithEvents($month, $year);
    }

    public function small_calendar()
    {
        $this->set(array(
            'sc_month' => $this->params['named']['month'],
            'sc_year' => $this->params['named']['year']
        ));
        $this->layout = 'ajax';
    }

    public function with_tag($tag_id, $fields = array())
    {
        $this->paginate = array(
            'limit' => 50,
            'contain' => false,
            'fields' => array('Event.id', 'Event.title', 'Event.date'),
            'recursive' => 0,
            'conditions' => array('Event.published' => 1),
            'order' => array('Event.date DESC'),
            'joins' => array(
                array(
                    'table' => 'events_tags',
                    'alias' => 'EventsTag',
                    'type' => 'inner',
                    'foreginKey' => false,
                    'conditions' => array(
                        'EventsTag.event_id = Event.id',
                        'EventsTag.tag_id' => $tag_id
                    )
                )
            )
        );

        $events = $this->paginate();
        $this->set(array(
            'events' => $events,
            'tag_id' => $tag_id
        ));
    }

    // Requested via AJAX to update the big calendar
    public function big_calendar()
    {
        $month = $this->params['named']['month'];
        $year = $this->params['named']['year'];
        $this->set(array(
            'month' => $month,
            'year' => $year,
            'events' => $this->Event->getBigCalendarEvents($month, $year)
        ));
    }

    public function upcoming_tags()
    {
        $this->Calendar->setEventFilter();
        $tags = $this->Event->Tag->getUpcoming($this->event_filter);


        if (isset($this->params['requested'])) {
            return $tags;
        }
        $this->set(array(
            'upcoming_event_tags' => $tags,
            'title_for_layout' => 'Upcoming Event Tags',
            'filter' => $this->event_filter
        ));
    }

    public function upcoming($tag_id)
    {
        $this->Event->Tag->id = $tag_id;
        $tag_name = $this->Event->Tag->field('name');
        if (! $tag_name) {
            $this->Flash->error("Sorry, we couldn't find that tag. Here are some other tags for upcoming events.");
            $this->redirect(Router::url(array('controller' => 'events', 'action' => 'upcoming_tags')));
        }

        $results = $this->Event->Tag->find('all', array(
            'conditions' => array('id' => $tag_id),
            'fields' => array('id'),
            'contain' => array(
                'Event' => array(
                    'id', 'title', 'location', 'date',
                    'conditions' => array(
                        'published' => 1,
                        'date >=' => date('Y-m-d')
                    ),
                    'Category' => array('name', 'icon', 'slug')
                )
            )
        ));
        $events = array();
        foreach ($results as $result) {
            foreach ($result['Event'] as $event) {
                $events[$event['date']][] = array(
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'location' => $event['location'],
                    'date' => $event['date'],
                    'Category' => $event['Category']
                );
            }
        }
        ksort($events);
        $this->set(array(
            'events' => $events,
            'title_for_layout' => 'Upcoming '.ucwords($tag_name).' Events'
        ));
    }



    /* Used to be in the Calendar component: */



    public function setEventFilter()
    {
        // Collect active filters
        $filter = array();
        if (isset($this->params['named']['tag'])) {
            $tag_id = reset(explode('_', $this->params['named']['tag']));
            $this->Event->Tag->id = $tag_id;
            $filter['tag'] = array(
                'name' => $this->Event->Tag->field('name'),
                'id' => $tag_id
            );
        }
        $filter['categories'] = $this->Cookie->read('EventFilter.categories');
        if (empty($filter['categories'])) {
            $categories = $this->Event->Category->getList();
            $filter['categories'] = array_keys($categories);
            $this->Cookie->write('EventFilter.categories', $filter['categories'], false);
        }

        $this->event_filter = $filter;
        $this->set(compact('filter'));
    }

    // Sets the variables needed for the "event accordion"
    public function prepareEventAccordion()
    {
        // Determine the date boundaries of this page
        if (isset($this->params['named']['start_date'])) {
            $boundary_date = $this->params['named']['start_date'];
            $boundary_side = 'start';
        } elseif (isset($this->params['named']['end_date'])) {
            $boundary_date = $this->params['named']['end_date'];
            $boundary_side = 'end';
        } else {
            $boundary_date = date('Y-m-d');
            $boundary_side = 'start';
        }
        $boundary = array($boundary_side, $boundary_date);
        $events = $this->Event->getPage($boundary_side, $boundary_date, $this->event_filter);
        $starting_date = reset(array_keys($events));
        $ending_date = end(array_keys($events));
        $this->set(compact(
            'events', 'starting_date', 'ending_date', 'filter', 'boundary'
        ));
    }

    public function prepareEventAccordionWrapper()
    {
        $this->set(array(
            'categories' => $this->Event->Category->getAll(),
            'upcoming_event_tags' => $this->Event->Tag->getUpcoming($this->event_filter)
        ));
    }
}
