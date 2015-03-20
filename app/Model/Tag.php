<?php
App::uses('AppModel', 'Model');
class Tag extends AppModel {
	public $name = 'Tag';
	public $actsAs = array('Tree');
	public $displayField = 'name';
	public $hasAndBelongsToMany = array(
		'Event' => array(
			'className' => 'Event',
			'fields' => array('Event.id', 'Event.title'),
			'order' => 'Event.date DESC',
			'joinTable' => 'events_tags',
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'event_id'
		)
	);
	public $hasMany = array(
		'EventsTag' => array(
			'className' => 'EventsTag',
			'foreignKey' => 'tag_id'
		)
	);

	/**
	 * Returns an array of IDs of tags associated with events;
	 * If $direction is 'past' or 'future',
	 * then only tags associated with events in the past/future.
	 * @param string $direction
	 * @return array
	 */
	public function getUsedTagIds($direction = null) {
		$find_options = array(
			'fields' => array('DISTINCT EventsTag.tag_id'),
			'order' => 'EventsTag.tag_id ASC',
			'contain' => false
		);
		if ($direction == 'future' || $direction == 'past') {
			$db = $this->getDataSource();
			$subQuery = $db->buildStatement(
			    array(
			        'fields'     => array('Event.id'),
			        'table'      => $db->fullTableName($this->Event),
			        'alias'      => 'Event',
			        'limit'      => null,
			        'offset'     => null,
			        'joins'      => array(),
			        'conditions' => ($direction == 'future')
						? array('Event.date >=' => date('Y-m-d'))
						: array('Event.date <' => date('Y-m-d')),
			        'order'      => null,
			        'group'      => null
			    ),
			    $this->Event
			);
			$subQuery = ' EventsTag.event_id IN (' . $subQuery . ') ';
			$subQueryExpression = $db->expression($subQuery);

			$find_options['conditions'] = array(
				//'EventsTag.event_id' => $event_ids
				$subQueryExpression
			);
		}
		$results = $this->EventsTag->find('all', $find_options);
		$retval = array();
		foreach ($results as $result) {
			$retval[] = $result['EventsTag']['tag_id'];
		}
		return $retval;
	}

	public function getList() {
		$results = $this->find('threaded', array(
			'conditions' => array('Tag.listed' => 1),
			'recursive' => 0,
			'fields' => array('Tag.name', 'Tag.id', 'Tag.parent_id', 'Tag.selectable'),
			'order' => array('Tag.name ASC')
		));
		$results = $this->sortThreaded($results);
		return $results;
	}

	/**
	 * Takes the result of find('threaded') and sorts so that branches (with children) come before leaves; Assumes that everything is already alphabetized
	 * @param array $threaded
	 * @return array
	 */
	function sortThreaded($threaded) {
		$branches = $leaves = array();
		foreach ($threaded as $item) {
			if (empty($item['children'])) {
				$leaves[] = $item;
			} else {
				$item['children'] = $this->sortThreaded($item['children']);
				$branches[] = $item;
			}
		}
		return array_merge($branches, $leaves);
	}

	// Gets all of the tags that are ancestors of a specified parent
	public function getListBranch($parent_id) {
		$tree = $this->find('threaded', array(
			'conditions' => array(
				'listed' => 1,
				'Tag.parent_id' => $parent_id
			),
			'recursive' => 0,
			'fields' => array('Tag.name', 'Tag.id', 'Tag.parent_id', 'Tag.selectable'),
			'order' => array('Tag.name ASC')
		));
		if (! empty($tree)) {
			foreach ($tree as &$child) {
				$parent_id = $child['Tag']['id'];
				$child['children'] = $this->getListBranch($parent_id);
			}
		}
		return $tree;
	}

	public function randomSampling($tags, $max_length = 199) {
		if (empty($tags)) {
			return '';
		}
		$string_length = 0;
		$continued = false;
		shuffle($tags);
		$i = 0;
		foreach ($tags as $tag) {
			$to_add = strlen($tag['name']) + 2;
			if ($to_add + $string_length > $max_length) {
				$continued = true;
				break;
			} else {
				$string_length += $to_add;
			}
			$i++;
		}
		$truncated_tag_array = array_slice($tags, 0, $i + 1);
		$links_array = array();
		foreach ($truncated_tag_array as $tag) {
			$url = Router::url(array(
				'controller' => 'tags',
				'action' => 'view',
				'id' => $tag['id']
			));
			$links_array[] = '<a href="'.$url.'">'.$tag['name'].'</a>';
		}
		$output = implode(', ', $links_array);
		if ($continued) {
			$output .= '...';
		}
		return $output;
	}

	// Takes a list of tags and returns only those that are ancesors of the 'art genres' tag
	public function extractArtGenreTags($tags) {
		$art_genres = array();
		foreach ($tags as $tag) {
			$path = $this->getpath($tag['id']);
			foreach ($path as $ancestor) {
				if (strtolower($ancestor['Tag']['name']) == 'art genres') {
					$art_genres[] = $tag;
					break;
				}
			}
		}
		return $art_genres;
	}

	public function getIdFromName($name) {
		$result = $this->find('list', array(
			'conditions' => array('name' => trim(strtolower($name))),
			'limit' => 1
		));
		return empty($result) ? false : reset(array_keys($result));
	}

	/**
	 * Returns ID from a slug with format "ID_slugname"
	 * @param string $slug
	 * @return int
	 */
	public function getIdFromSlug($slug) {
		$split_slug = explode('_', $slug);
		return (int) $split_slug[0];
	}

	/**
	 * Returns a list of tags;
	 * If $with_events is true, only tags associated with events are returned;
	 * If $direction is 'past' or 'future', then only tags associated with past/future events are returned.
	 * @param string $containing
	 * @param boolean $with_events
	 * @param string $direction
	 * @return array
	 */
	public function search($containing, $with_events = false, $direction = null) {
		$tag_ids_used = $this->getUsedTagIds($direction);
		$tags = $this->Event->Tag->find('all', array(
			'conditions' => array(
				'Tag.name LIKE' => '%'.$containing.'%',
				'Tag.id' => $tag_ids_used
			),
			'fields' => array('Tag.id', 'Tag.name', 'LENGTH(Tag.name) as length'),
			'order' => array(
				'length ASC',
				'Tag.name ASC'
			),
			'contain' => false
		));

		return $tags;
	}

	/**
	 * Returns the ID of the 'unlisted' tag group that new custom tags automatically go into.
	 * @return int
	 */
	public function getUnlistedGroupId() {
		return 1012;
	}

	/**
	 * Returns the ID of the 'delete' tag group for tags to be deleted.
	 * @return int
	 */
	public function getDeleteGroupId() {
		return 1011;
	}

	public function isUnderUnlistedGroup($id = null) {
		if (! $id) {
			if (! $this->id) {
				throw new InternalErrorException('Required tag ID not supplied to Tag::isUnderUnlistedGroup().');
			}
			$id = $this->id;
		}
		$unlisted_group_id = $this->getUnlistedGroupId();

		// Assume that after 100 levels, a circular path must have been found and exit
		for ($n = 0; $n <= 100; $n++) {
			$parent_id = $this->field('parent_id', array('id' => $id));

			// Child of root
			if (empty($parent_id)) {
				return false;
			}

			// Child of 'unlisted'
			if ($parent_id == $unlisted_group_id) {
				return true;
			}

			// Go up a level
			$id = $parent_id;
		}

		return false;
	}

	/**
	 * Used by the tag adder (in the tag manager) to determine how indented a line is
	 * @param string $name
	 * @return number
	 */
	public function getIndentLevel($name) {
		$level = 0;
		for ($i = 0; $i < strlen($name); $i++) {
			if ($name[$i] == "\t" || $name[$i] == '-') {
				$level++;
			} else {
				break;
			}
		}
		return $level;
    }

    /**
     * Returns the tags associated with events on or after today
     * @param array $filter
     * @return array
     */
    public function getUpcoming($filter = array(), $sort = 'alpha') {
    	$filter['direction'] = 'future';
    	return $this->getWithCounts($filter);
    }

	/**
     * Returns the tags associated with events that occurred before today
     * @param array $filter
     * @return array
     */
    public function getPast($filter = array(), $sort = 'alpha') {
    	$filter['direction'] = 'past';
    	return $this->getWithCounts($filter);
    }

    /**
     * Returns an array of the IDs of Tags associated with Events
     * @param string $direction Optional, either 'future' or 'past'
     * @return array
     */
    public function getIDsWithEvents($direction = null) {
		$conditions = array();
		if ($direction == 'future') {
			$conditions['EventsTag.event_id'] = $this->Event->getFutureEventIDs();
		} elseif ($direction == 'past') {
			$conditions['EventsTag.event_id'] = $this->Event->getPastEventIDs();
		}
    	$results = $this->EventsTag->find('all', array(
			'fields' => array('EventsTag.tag_id'),
			'conditions' => $conditions
		));
		$retval = array();
		foreach ($results as $result) {
			$retval[] = $result['EventsTag']['tag_id'];
		}
		return $retval;
    }

    /**
     * Returns tags and includes their counts (total of times they're used)
     * @param array $filter
     * @param string $sort Either 'alpha' for alphabetic or 'count' to sort by count, descending
     * @return array
     */
    public function getWithCounts($filter = array(), $sort = 'alpha') {
    	// Apply filters and find tags
    	$conditions = array('published' => 1);
    	if ($filter['direction'] == 'future') {
    		$conditions['date >='] = date('Y-m-d');
    	} elseif ($filter['direction'] == 'past') {
    		$conditions['date <'] = date('Y-m-d');
    	}
		if (isset($filter['categories'])) {
			$conditions['category_id'] = $filter['categories'];
		}
		$results = $this->Event->find('all', array(
			'conditions' => $conditions,
			'fields' => array('id'),
			'contain' => array('Tag' => array('fields' => array('Tag.id', 'Tag.name')))
		));

		// Create an alphabetically-sorted array of
		// tags with count information included
		$tags = array();
		foreach ($results as $result) {
			foreach ($result['Tag'] as $tag) {
				if (isset($tags[$tag['name']])) {
					$tags[$tag['name']]['count']++;
				} else {
					$tags[$tag['name']] = array(
						'id' => $tag['id'],
						'name' => $tag['name'],
						'count' => 1
					);
				}
			}
		}
		ksort($tags);
		if ($sort == 'alpha') {
			return $tags;
		}

		// Sort by count if $sort is not 'alpha'
		$sorted_tags = array();
		foreach ($tags as $tag_name => $tag) {
			$sorted_tags[$tag['count']][$tag['name']] = $tag;
		}
		krsort($sorted_tags);
		$final_tags = array();
		foreach ($sorted_tags as $count => $tags) {
			foreach ($tags as $name => $tag) {
				$final_tags[$tag['name']] = $tag;
			}
		}
		return $final_tags;
    }

    public function getCategoriesWithTags($direction = 'future') {
    	if ($direction == 'future') {
    		$event_ids = $this->Event->getFutureEventIDs();
    	} elseif ($direction == 'past') {
    		$event_ids = $this->Event->getPastEventIDs();
    	}
    	$tagged_event_ids = $this->EventsTag->find('list', array(
    		'conditions' => array('event_id' => $event_ids),
    		'fields' => array('event_id')
    	));
		$results = $this->Event->find('all', array(
    		'conditions' => array('Event.id' => $tagged_event_ids),
    		'fields' => array('DISTINCT Event.category_id as category_id'),
			'contain' => false
    	));
    	$retval = array();
    	foreach ($results as $result) {
			$retval[] = $result['Event']['category_id'];
    	}
    	return $retval;
    }
}