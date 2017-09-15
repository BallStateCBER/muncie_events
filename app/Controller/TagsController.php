<?php
App::uses('AppController', 'Controller');
class TagsController extends AppController {
	public $name = 'Tags';
	public $helpers = array('Tag');
	public $uses = array('Tag', 'Event', 'User');

	public $admin_actions = array('get_name', 'getnodes', 'group_unlisted', 'manage', 'recover', 'remove', 'reorder', 'reparent', 'trace', 'edit', 'merge');

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny($this->admin_actions);
	}

	public function isAuthorized() {
		// Admins can access everything
		if ($this->Auth->user('role') == 'admin') {
			return true;

		// Some actions are admin-only
		} elseif (in_array($this->action, $this->admin_actions)) {
			return false;
		}

		// Otherwise, only authors can modify authored content
		$author_only = array();
		if (in_array($this->action, $author_only)) {
			return $this->__isAdminOrAuthor($this->request->params['named']['id']);
		}

		// Logged-in users can access everything else
		return true;
	}

	public function manage() {
		$this->set(array(
			'title_for_layout' => 'Manage Tags'
		));
	}

	public function view() {

	}

	public function index($direction = 'future', $category = 'all') {
		if ($direction != 'future' && $direction != 'past') {
			$direction = 'future';
		}
		$filters = compact('direction');
		if ($category != 'all') {
			$filters['categories'] = $category;
		}
		$tags = $this->Tag->getWithCounts($filters, 'alpha');
		$tags_by_first_letter = array();
		foreach ($tags as $tag_name => $tag) {
			$first_letter = ctype_alpha($tag['name'][0]) ? $tag['name'][0] : '#';
			$tags_by_first_letter[$first_letter][$tag['name']] = $tag;
		}
		$direction_adjective = ($direction == 'future' ? 'upcoming' : 'past');
		$title_for_layout = 'Tags (';
		$title_for_layout .= ucfirst($direction_adjective);
		$this->loadModel('Category');
		if ($category != 'all' && $category_name = $this->Category->getName($category)) {
			$title_for_layout .= ' '.str_replace(' Events', '', ucwords($category_name));
		}
		$title_for_layout .= ' Events)';
		$this->set(compact(
			'title_for_layout',
			'tags',
			'tags_by_first_letter',
			'direction',
			'direction_adjective',
			'category'
		));
		$this->loadModel('Category');
		$this->set(array(
			'categories' => $this->Category->getAll(),
			'categories_with_tags' => $this->Tag->getCategoriesWithTags($direction)
		));
	}

	public function getEvents($tag_id) {
		$this->paginate['Event']['conditions'] = array('Tag.id' => $tag_id);
		$this->Tag->id = $tag_id;
		return $results;
	}

	public function auto_complete($only_listed = true, $only_selectable = true) {
		$string_to_complete = htmlspecialchars_decode($_GET['term']);
		$limit = 10;

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
			$conditions = array('Tag.name LIKE' => $like);
			if ($only_listed) {
				$conditions['Tag.listed'] = 1;
			}
			if ($only_selectable) {
				$conditions['Tag.selectable'] = 1;
			}
			if (! empty($tags)) {
				$conditions['Tag.id NOT'] = array_keys($tags);
			}
			$results = $this->Tag->find('all', array(
				'fields' => array('Tag.id', 'Tag.name'),
				'conditions' => $conditions,
				'contain' => false,
				'limit' => $limit - count($tags)
			));
			foreach ($results as $result) {
				if (! array_key_exists($result['Tag']['id'], $tags)) {
					$tags[$result['Tag']['id']] = array(
						'label' => $result['Tag']['name'],
						'value' => $result['Tag']['id']
					);
				}
			}
		}

		$this->set(compact('tags'));
		$this->layout = 'blank';
	}

	public function recover() {
		list($start_usec, $start_sec) = explode(" ", microtime());
		set_time_limit(3600);
		$this->Tag->recover();
		list($end_usec, $end_sec) = explode(" ", microtime());
		$start_time = $start_usec + $start_sec;
		$end_time = $end_usec + $end_sec;
		$loading_time = $end_time - $start_time;
		$minutes = round($loading_time / 60, 2);
		return $this->renderMessage(array(
			'message' => "Done recovering tag tree (took $minutes minutes).",
			'class' => 'success',
			'layout' => 'ajax'
		));
    }

    /**
     * Places any root-level unlisted tags in the 'unlisted' tag group
     */
    public function group_unlisted() {
    	list($start_usec, $start_sec) = explode(" ", microtime());
		set_time_limit(3600);

	    // Take all unlisted tags without parents and place them under the 'unlisted' group
		$unlisted_group_id = $this->Tag->getUnlistedGroupId();
		$delete_group_id = $this->Tag->getDeleteGroupId();
		$results = $this->Tag->find('all', array(
			'conditions' => array(
				'OR' => array(
					'Tag.parent_id' => 0,
					'Tag.parent_id' => null
				),
				'Tag.id NOT' => array(
					$unlisted_group_id,
					$delete_group_id
				),
				'Tag.listed' => 0
			),
			'fields' => array('Tag.id'),
			'contain' => false,
			'limit' => 20
		));
		foreach ($results as $result) {
			$this->Tag->id = $result['Tag']['id'];
			$this->Tag->saveField('parent_id', $unlisted_group_id);
			$this->Tag->moveUp($result['Tag']['id'], true);
		}

		list($end_usec, $end_sec) = explode(" ", microtime());
		$start_time = $start_usec + $start_sec;
		$end_time = $end_usec + $end_sec;
		$loading_time = $end_time - $start_time;
		$minutes = round($loading_time / 60, 2);

		$message = 'Regrouped '.count($results)." unlisted tags (took $minutes minutes).";
		$more = $this->Tag->find('count', array(
			'conditions' => array(
				'OR' => array(
					'Tag.parent_id' => 0,
					'Tag.parent_id' => null
				),
				'Tag.id NOT' => array(
					$unlisted_group_id,
					$delete_group_id
				),
				'Tag.listed' => 0
			)
		));
		if ($more) {
			$message .= '<br />There\'s '.$more.' more unlisted tag'.($more == 1 ? '' : 's').' left to move. Please run this function again.';
		}
		return $this->renderMessage(array(
			'message' => $message,
			'class' => 'success',
			'layout' => 'ajax'
		));
    }

	public function getnodes() {
	    // retrieve the node id that Ext JS posts via ajax
	    $parent = isset($_POST['node']) ? intval($_POST['node']) : 0;

	    // find all the nodes underneath the parent node defined above
	    // the second parameter (true) means we only want direct children
	    $nodes = $this->Tag->children($parent, true);

	    $rearranged_nodes = array('branches' => array(), 'leaves' => array());
	    foreach ($nodes as $key => &$node) {
	    	$tag_id = $node['Tag']['id'];

	    	// Check for events associated with this tag
	    	if ($node['Tag']['selectable']) {
	    		$count = $this->Tag->EventsTag->find('count', array(
	    			'conditions' => array('tag_id' => $tag_id)
	    		));
				$node['Tag']['no_events'] = $count == 0;
	    	}

	    	// Check for children
	    	$has_children = $this->Tag->childCount($tag_id, true);
	    	if ($has_children) {
	    		$tag_name = $node['Tag']['name'];
	    		$rearranged_nodes['branches'][$tag_name] = $node;
	    	} else {
				$rearranged_nodes['leaves'][$tag_id] = $node;
	    	}
	    }

	    // Sort nodes by alphabetical branches, then alphabetical leaves
    	ksort($rearranged_nodes['branches']);
    	ksort($rearranged_nodes['leaves']);
		$nodes = array_merge(
			array_values($rearranged_nodes['branches']),
			array_values($rearranged_nodes['leaves'])
		);

	    // Visually note categories with no data
	    $showNoEvents = true;

	    // send the nodes to our view
	    $this->set(compact('nodes', 'showNoEvents'));

	    $this->layout = 'blank';
	}

	public function reorder() {

		// retrieve the node instructions from javascript
		// delta is the difference in position (1 = next node, -1 = previous node)

		$node = intval($_POST['node']);
		$delta = intval($_POST['delta']);

		if ($delta > 0) {
			$this->Tag->moveDown($node, abs($delta));
		} elseif ($delta < 0) {
			$this->Tag->moveUp($node, abs($delta));
		}

		// send success response
		exit('1');

	}

	public function reparent() {
		$node = intval($_POST['node']);
		$parent = ($_POST['parent'] == 'root') ? 0 : intval($_POST['parent']);
		$in_unlisted_before = $this->Tag->isUnderUnlistedGroup($node);
		$in_unlisted_after = ($_POST['parent'] == 'root') ? false : $this->Tag->isUnderUnlistedGroup($parent);
		$this->Tag->id = $node;

		// Moving out of the 'Unlisted' group
		if ($in_unlisted_before && ! $in_unlisted_after) {
			//echo 'Making listed.';
			$this->Tag->saveField('listed', 1);
		}

		// Moving into the 'Unlisted' group
		if (! $in_unlisted_before && $in_unlisted_after) {
			//echo 'Making unlisted.';
			$this->Tag->saveField('listed', 0);
		}

		// Move tag
		$this->Tag->saveField('parent_id', $parent);

		// If position == 0, then we move it straight to the top
		// otherwise we calculate the distance to move ($delta).
		// We have to check if $delta > 0 before moving due to a bug
		// in the tree behaviour (https://trac.cakephp.org/ticket/4037)
		$position = intval($_POST['position']);
		if ($position == 0) {
			$this->Tag->moveUp($node, true);
		} else {
			$count = $this->Tag->childCount($parent, true);
			$delta = $count-$position-1;
			if ($delta > 0) {
				$this->Tag->moveUp($node, $delta);
			}
		}

		// send success response
		exit('1');
	}

	/**
	 * Returns a path from the root of the Tag tree to the tag with the provided name
	 * @param string $tag_name
	 */
	public function trace($tag_name = '') {
		$path = array();
		$target_tag = $this->Tag->find('first', array(
			'conditions' => array('Tag.name' => $tag_name),
			'fields' => array('Tag.id', 'Tag.name', 'Tag.parent_id'),
			'contain' => false
		));
		if ($target_tag) {
			$target_tag_id = $target_tag['Tag']['id'];
			$parent_id = $target_tag['Tag']['parent_id'];
			$path[] = "{$target_tag['Tag']['name']} ({$target_tag_id})";
			if ($parent_id) {
				$root_found = false;
				while (! $root_found) {
					$parent = $this->Tag->find('first', array(
						'conditions' => array('Tag.id' => $parent_id),
						'fields' => array('Tag.id', 'Tag.name', 'Tag.parent_id'),
						'contain' => false
					));
					if ($parent) {
						$path[] = "{$parent['Tag']['name']} ({$parent['Tag']['id']})";
						if (! $parent_id = $parent['Tag']['parent_id']) {
							$root_found = true;
						}
					} else {
						$path[] = "(Parent data tag with id $parent_id not found)";
						break;
					}
				}
			}
		} else {
			$path[] = "(Tag named '$tag_name' not found)";
		}
		$this->layout = 'ajax';
		$path = array_reverse($path);
		$this->set(compact('path'));
	}

	/**
	 * Returns the name of the Tag with id $id, used by the tag manager
	 * @param int $id
	 */
	public function get_name($id) {
		$this->Tag->id = $id;
		if ($this->Tag->exists()) {
			$name = $this->Tag->field('name');
		} else {
			$name = "Error: Tag does not exist";
		}
		$this->set(compact('name'));
		$this->layout = 'ajax';
	}

	public function remove($name) {
		$tag_id = $this->Tag->getIdFromName($name);
		if (! $tag_id) {
			$message = "The tag \"$name\" does not exist (you may have already deleted it).";
			$class = 'error';
		} elseif ($this->Tag->delete($tag_id)) {
			$message = "Tag \"$name\" deleted.";
			$class = 'success';
		} else {
			$message = "There was an unexpected error deleting the \"$name\" tag.";
			$class = 'error';
		}
		return $this->renderMessage(array(
			'message' => $message,
			'class' => $class,
			'layout' => 'ajax'
		));
	}

	/**
	 * Removes all unlisted, unused, root-level tags with no children
	 */
	public function remove_unlisted_unused() {
		$tags = $this->Tag->find('list', array(
			'conditions' => array(
				'Tag.parent_id' => null,
				'Tag.listed' => 0,
				'Tag.id NOT' => array_merge(
					$this->Tag->getUsedTagIds(),
					array(
						$this->Tag->getUnlistedGroupId(),
						$this->Tag->getDeleteGroupId()
					)
				)
			)
		));
		$skipped_tags = $deleted_tags = array();
		foreach ($tags as $tag_id => $tag_name) {
			$this->Tag->id = $tag_id;
			if ($this->Tag->childCount()) {
				$skipped_tags[] = $tag_name;
			} else {
				$this->Tag->delete();
				$deleted_tags[] = $tag_name;
			}
		}
		if (empty($deleted_tags)) {
			$message = 'No tags found that were both unlisted and unused.';
		} else {
			$message = 'Deleted the following tags: <br />- ';
			$message .= implode('<br />- ', $deleted_tags);
		}
		if (! empty($skipped_tags)) {
			$message .= '<br />&nbsp;<br />Did not delete the following tags, since they have child-tags: <br />- ';
			$message .= implode('<br />- ', $skipped_tags);
		}

		return $this->renderMessage(array(
			'message' => $message,
			'class' => 'success',
			'layout' => 'ajax'
		));
	}

	/**
	 * Finds duplicate tags and merges the tags with higher IDs into those with the lowest ID
	 */
	public function duplicates() {
		// List all tag names and corresponding id(s)
		$tags = $this->Tag->find('list');
		$tags_arranged = array();
		foreach ($tags as $tag_id => $tag_name) {
			if (isset($tags_arranged[$tag_name])) {
				$tags_arranged[$tag_name][] = $tag_id;
			} else {
				$tags_arranged[$tag_name] = array($tag_id);
			}
		}

		// Find duplicate tags
		$message = '';
		$recover_tree = false;
		foreach ($tags_arranged as $tag_name => $tag_ids) {
			if (count($tag_ids) < 2) {
				continue;
			}

			// Aha! Duplicates!
			$message .= "Tag \"$tag_name\" has IDs: ".implode(', ', $tag_ids).'<br />';
			/* Commenting this block until better safeguards can be put in place
			 * to protect child tags.
			sort($tag_ids);
			$merge_into_id = reset($tag_ids);
			$duplicate_tag_ids = array_slice($tag_ids, 1);
			$message .= "$tag_name ($merge_into_id), absorbing duplicate tags ".implode(', ', $duplicate_tag_ids).'<br />';
			foreach ($duplicate_tag_ids as $tag_id) {
				// Correct events associated with the duplicate tag
				$this->Tag->query("
					UPDATE events_tags
					SET tag_id = $merge_into_id
					WHERE tag_id = $tag_id
				");

				// If the duplicate tag has children, reparent them
				$count = $this->Tag->find('count', array(
					'conditions' => array('Tag.parent_id' => $tag_id)
				));
				if ($count) {
					$this->Tag->query("
						UPDATE tags
						SET parent_id = $merge_into_id
						WHERE parent_id = $tag_id
					");
					$recover_tree = true;
				}

				// Delete duplicate tag
				$this->Tag->id = $tag_id;
				$this->Tag->delete();
			}
			*/
		}
		$message .= 'No action taken.';

		if ($message == '') {
			$message = 'No duplicate tags found.';
		}

		// If tags have been reparented, recover tag tree
		if ($recover_tree) {
			$this->Tag->recover();
		}

		return $this->renderMessage(array(
			'message' => $message,
			'class' => 'success',
			'layout' => 'ajax'
		));
	}

	/**
	 * Turns all associations with Tag $tag_id into associations with Tag $merge_into_id
	 * and deletes Tag $tag_id, and moves any child tags under Tag $merge_into_id.
	 * @param int $tag_id
	 * @param int $merge_into_id
	 */
	public function merge($removed_tag_name = '', $retained_tag_name = '') {
		$this->layout = 'ajax';
		$removed_tag_name = trim($removed_tag_name);
		$retained_tag_name = trim($retained_tag_name);

		// Verify input
		if ($removed_tag_name == '') {
			return $this->renderMessage(array(
				'message' => 'No name provided for the tag to be removed.',
				'class' => 'error'
			));
		} else {
			$removed_tag_id = $this->Tag->getIdFromName($removed_tag_name);
			if (! $removed_tag_id) {
				return $this->renderMessage(array(
					'message' => "The tag \"$removed_tag_name\" could not be found.",
					'class' => 'error'
				));
			}
		}
		if ($retained_tag_name == '') {
			return $this->renderMessage(array(
				'message' => 'No name provided for the tag to be retained.',
				'class' => 'error'
			));
		} else {
			$retained_tag_id = $this->Tag->getIdFromName($retained_tag_name);
			if (! $retained_tag_id) {
				return $this->renderMessage(array(
					'message' => "The tag \"$retained_tag_name\" could not be found.",
					'class' => 'error'
				));
			}
		}
		if ($removed_tag_id == $retained_tag_id) {
			return $this->renderMessage(array(
				'message' => "Cannot merge \"$retained_tag_name\" into itself.",
				'class' => 'error'
			));
		}

		$message = '';
		$class = 'success';

		// Switch event associations
		$associated_count = $this->Tag->EventsTag->find('count', array(
			'conditions' => array('tag_id' => $removed_tag_id)
		));
		if ($associated_count) {
			$result = $this->Tag->query("
				UPDATE events_tags
				SET tag_id = $retained_tag_id
				WHERE tag_id = $removed_tag_id
			");
			$message .= "Changed association with \"$removed_tag_name\" into \"$retained_tag_name\" in $associated_count event".($associated_count == 1 ? '' : 's').'.<br />';
		} else {
			$message .= 'No associated events to edit.<br />';
		}

		// Move child tags
		$children = $this->Tag->find('list', array(
			'conditions' => array('parent_id' => $removed_tag_id)
		));
		if (empty($children)) {
			$message .= 'No child-tags to move.<br />';
		} else {
			foreach ($children as $child_id => $child_name) {
				$this->Tag->id = $child_id;
				if ($this->Tag->saveField('parent_id', $retained_tag_id)) {
					$message .= "Moved \"$child_name\" from under \"$removed_tag_name\" to under \"$retained_tag_name\".<br />";
				} else {
					$class = 'error';
					$message .= "Error moving \"$child_name\" from under \"$removed_tag_name\" to under \"$retained_tag_name\".<br />";
				}
			}
			// $message .= "Moved ".count($children)." child tag".(count($children) == 1 ? '' : 's')." of \"$removed_tag_name\" under tag \"$retained_tag_name\".<br />";
		}

		// Delete tag
		if ($class == 'success') {
			if ($this->Tag->delete($removed_tag_id)) {
				$message .= "Removed \"$removed_tag_name\".";
			} else {
				$message .= "Error trying to delete \"$removed_tag_name\" from the database.";
				$class = 'error';
			}
		} else {
			$message .= "\"$removed_tag_name\" not removed.";
		}

		return $this->renderMessage(array(
			'message' => $message,
			'class' => $class
		));
	}

	/**
	 * Removes entries from the events_tags join table where either the tag or event no longer exists
	 */
	public function remove_broken_associations() {
		set_time_limit(120);
		$this->layout = 'ajax';

		$associations = $this->Tag->EventsTag->find('all', array('contain' => false));
		$tags = $this->Tag->find('list');
		$events = $this->Tag->Event->find('list');
		foreach ($associations as $a) {
			// Note missing tags/events for output message
			$t = $a['EventsTag']['tag_id'];
			if (! isset($tags[$t])) {
				$missing_tags[$t] = true;
			}
			$e = $a['EventsTag']['event_id'];
			if (! isset($events[$e])) {
				$missing_events[$e] = true;
			}

			// Remove broken association
			if (! isset($tags[$t]) || ! isset($events[$e])) {
				$this->Tag->EventsTag->delete($a['EventsTag']['id']);
			}
		}
		$message = '';
		if (! empty($missing_tags)) {
			$message .= 'Removed associations with nonexistent tags: '.implode(', ', array_keys($missing_tags)).'<br />';
		}
		if (! empty($missing_events)) {
			$message .= 'Removed associations with nonexistent events: '.implode(', ', array_keys($missing_events)).'<br />';
		}
		if ($message == '') {
			$message = 'No broken associations to remove.';
		}
		return $this->renderMessage(array(
			'message' => $message,
			'class' => 'success',
			'layout' => 'ajax'
		));
	}

	/**
	 * Removes all tags in the 'delete' group
	 */
	public function empty_delete_group() {
		$delete_group_id = $this->Tag->getDeleteGroupId();
		$children = $this->Tag->children($delete_group_id, true, array('id'));
		foreach ($children as $child) {
			$this->Tag->delete($child['Tag']['id']);
		}
		return $this->renderMessage(array(
			'message' => 'Delete Group Emptied',
			'class' => 'success',
			'layout' => 'ajax'
		));
	}

	public function edit($tag_name = null) {
		if ($this->request->is('ajax')) {
			$this->layout = 'ajax';
		}
		if ($this->request->is('put') || $this->request->is('post')) {
			$this->request->data['Tag']['name'] = strtolower(trim($this->request->data['Tag']['name']));
			$this->request->data['Tag']['parent_id'] = trim($this->request->data['Tag']['parent_id']);
			if (empty($this->request->data['Tag']['parent_id'])) {
				$this->request->data['Tag']['parent_id'] = null;
			}
			$duplicates = $this->Tag->find('list', array(
				'conditions' => array(
					'Tag.name' => $this->request->data['Tag']['name'],
					'Tag.id NOT' => $this->request->data['Tag']['id']
				)
			));
			if (! empty($duplicates)) {
				$message = 'That tag\'s name cannot be changed to "';
				$message .= $this->request->data['Tag']['name'];
				$message .= '" because another tag (';
				$message .= implode(', ', array_keys($duplicates));
				$message .= ') already has that name. You can, however, merge this tag into that tag.';
				return $this->renderMessage(array(
					'message' => $message,
					'class' => 'error'
				));
			}

			// Set flag to recover tag tree if necessary
			$this->Tag->id = $this->request->data['Tag']['id'];
			$previous_parent_id = $this->Tag->field('parent_id');
			$new_parent_id = $this->request->data['Tag']['parent_id'];
			$recover_tag_tree = ($previous_parent_id != $new_parent_id);

			if ($this->Tag->save($this->request->data)) {
				if ($recover_tag_tree) {
					$this->Tag->recover();
				}
				$message = 'Tag successfully edited.';
				if ($this->request->data['Tag']['listed'] && $this->Tag->isUnderUnlistedGroup()) {
					$message .= '<br /><strong>This tag is now listed, but is still in the "Unlisted" group. It is recommended that it now be moved out of that group.</strong>';
				}
				return $this->renderMessage(array(
					'message' => $message,
					'class' => 'success'
				));
			}
			return $this->renderMessage(array(
				'message' => 'There was an error editing that tag.',
				'class' => 'error'
			));
		} else {
			if (! $tag_name) {
				return $this->renderMessage(array(
					'title' => 'Tag Name Not Provided',
					'message' => 'Please try again. But with a tag name provided this time.',
					'class' => 'error'
				));
			}
			$result = $this->Tag->find('all', array(
				'conditions' => array('Tag.name' => $tag_name),
				'contain' => false
			));
			if (empty($result)) {
				return $this->renderMessage(array(
					'title' => 'Tag Not Found',
					'message' => "Could not find a tag with the exact tag name \"$tag_name\".",
					'class' => 'error'
				));
			}
			if (count($result) > 1) {
				$tag_ids = array();
				foreach ($result as $tag) {
					$tag_ids[] = $tag['Tag']['id'];
				}
				return $this->renderMessage(array(
					'title' => 'Duplicate Tags Found',
					'message' => "Tags with the following IDs are named \"$tag_name\": ".implode(', ', $tag_ids).'<br />You will need to merge them before editing.',
					'class' => 'error'
				));
			}
			$this->request->data = $result[0];
		}
	}

	public function add() {
		$this->layout = 'ajax';
		if (! $this->request->is('post')) {
			return;
		}
		if (trim($this->request->data['Tag']['name']) == '') {
			return $this->renderMessage(array(
				'title' => 'Error',
				'message' => "Tag name is blank",
				'class' => 'error'
			));
		}

		// Determine parent_id
		$parent_name = $this->request->data['Tag']['parent_name'];
		if ($parent_name == '') {
			$root_parent_id = null;
		} else {
			$root_parent_id = $this->Tag->getIdFromName($parent_name);
			if (! $root_parent_id) {
				return $this->renderMessage(array(
					'title' => 'Error',
					'message' => "Parent tag \"$parent_name\" not found",
					'class' => 'error'
				));
			}
		}

		$class = 'success';
		$message = '';
		$inputted_names = explode("\n", trim(strtolower($this->request->data['Tag']['name'])));
		$level = 0;
		$parents = array($root_parent_id);
		foreach ($inputted_names as $line_num => $name) {
			$level = $this->Tag->getIndentLevel($name);

			// Discard any now-irrelevant data
			$parents = array_slice($parents, 0, $level + 1);

			// Determine this tag's parent_id
			if ($level == 0) {
				$parent_id = $root_parent_id;
			} elseif (isset($parents[$level])) {
				$parent_id = $parents[$level];
			} else {
				$class = 'error';
				$message .= "Error with nested tag structure. Looks like there's an extra indent in line $line_num: \"$name\".<br />";
				continue;
			}

			// Strip leading/trailing whitespace and hyphens used for indenting
			$name = trim(ltrim($name, '-'));

			// Confirm that the tag name is non-blank and non-redundant
			if (! $name) {
				continue;
			}
			$exists = $this->Tag->find('count', array(
				'conditions' => array('Tag.name' => $name)
			));
			if ($exists) {
				$class = 'error';
				$message .= "Cannot create the tag \"$name\" because a tag with that name already exists.<br />";
				continue;
			}

			// Add tag to database
			$this->Tag->create();
			$save_result = $this->Tag->save(array('Tag' => array(
				'name' => $name,
				'parent_id' => $parent_id,
				'listed' => 1,
				'selectable' => 1
			)));
			if ($save_result) {
				$message .= "Created tag #{$this->Tag->id}: $name<br />";
				$parents[$level + 1] = $this->Tag->id;
			} else {
				$class = 'error';
				$message .= "Error creating the tag \"$name\"<br />";
			}
		}

		return $this->renderMessage(array(
			'title' => 'Results:',
			'message' => $message,
			'class' => $class
		));
	}
}
