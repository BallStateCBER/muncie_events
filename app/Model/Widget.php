<?php
App::uses('AppModel', 'Model');
class Widget extends AppModel {
	public $name = 'Widget';
	public $useTable = false;
	public $widget_type = null;
	public $custom_styles = array();
	
	public function getDefaults() {
		if (! $this->widget_type) {
			throw new InternalErrorException('Widget type is null.');
		}
		$defaults = array(
			'styles' => array(
				'textColorDefault' => '#000000',
				'textColorLight' => '#666666',
				'textColorLink' => '#0b54a6',
				'borderColorLight' => '#aaaaaa',
				'borderColorDark' => '#000000',
				'backgroundColorDefault' => '#ffffff',
				'backgroundColorAlt' => '#f0f0f0',
				'showIcons' => 1,
				'hideGeneralEventsIcon' => 0
			),
			'iframe_options' => array(
				'outerBorder' => 1
			),
			'event_options' => array(
				'category' => '',
				'location' => '',
				'tags_included' => '',
				'tags_excluded' => ''
			)
		);
		switch ($this->widget_type) {
			case 'feed':
				$defaults['iframe_options']['height'] = 300;
				$defaults['iframe_options']['width'] = 300;
				break;
			case 'month':
				$defaults['styles']['fontSize'] = '11px';
				$defaults['styles']['showIcons'] = true;
				$defaults['iframe_options']['height'] = 400;
				$defaults['iframe_options']['width'] = 600;
				$defaults['event_options']['events_displayed_per_day'] = 2;
				break;
		}
		return $defaults;
	}
	
	public function setType($widget_type) {
		switch ($widget_type) {
			case 'feed':
			case 'month':
				$this->widget_type = $widget_type;
				break;
			default:
				throw new InternalErrorException('Unknown widget type: '.$widget_type);
		}
	}
	
	public function getOptions() {
		if (empty($_SERVER['QUERY_STRING'])) {
			return array();
		}
		$options = array();
		$parameters = explode('&', urldecode($_SERVER['QUERY_STRING']));
		foreach ($parameters as $option) {
			$option_split = explode('=', $option);
			if (count($option_split) != 2) {
				continue;	
			}
			list($key, $val) = $option_split;
			
			// Clean up option and skip blanks
			$val = trim($val);
			if ($val == '') {
				continue;
			}
			$key = str_replace('amp;', '', $key);
			
			// Retain only valid options that differ from their default values 
			if ($this->isValidNondefaultOption($key, $val)) {
				$options[$key] = $val;
			}
		}
		return $options;
	}
	
	public function getIframeQueryString() {
		if (empty($_SERVER['QUERY_STRING'])) {
			return '';
		}
		$defaults = $this->getDefaults();
		$iframe_params = array();
		$parameters = explode('&', urldecode($_SERVER['QUERY_STRING']));
		foreach ($parameters as $option) {
			$option_split = explode('=', $option);
			if (count($option_split) != 2) {
				continue;	
			}
			list($key, $val) = $option_split;
			
			// Clean up option and skip blanks
			$val = trim($val);
			if ($val == '') {
				continue;
			}
			$key = str_replace('amp;', '', $key);
			
			// Retain only valid params that differ from their default values 
			if ($this->isValidNondefaultOption($key, $val)) {
				// Iframe options (applying to the iframe element, but not 
				// its contents) aren't included in the query string
				if (! isset($defaults['iframe_options'][$key])) {
					$iframe_params[$key] = $val;
				}
			}
		}
		return http_build_query($iframe_params, '', '&amp;'); 
	}
	
	/**
	 * Returns TRUE if $key is found in default_styles, default_iframe_options, or default_event_options and $val is not the default value
	 * @param string $key
	 * @param string $val
	 * @return boolean
	 */
	public function isValidNondefaultOption($key, $val) {
		$defaults = $this->getDefaults();
		if (isset($defaults['styles'][$key])) {
			if ($defaults['styles'][$key] != $val) {
				return true;
			}
		} elseif (isset($defaults['event_options'][$key])) {
			if ($defaults['event_options'][$key] != $val) {
				return true;
			}
		} elseif(isset($defaults['iframe_options'][$key])) {
			if ($defaults['iframe_options'][$key] != $val) {
				return true;
			}
		}
		return false;
	}
	
	public function getIframeStyles($options) {
		$iframe_styles = '';
		$defaults = $this->getDefaults();
		
		// Dimensions
		foreach (array('width', 'height') as $dimension) {
			if (isset($options[$dimension])) {
				$unit = substr($options[$dimension], -1) == '%' ? '%' : 'px';
				$value = preg_replace("/[^0-9]/", "", $options[$dimension]);
			} else {
				$unit = 'px';
				$value = $defaults['iframe_options'][$dimension];
			}
			$iframe_styles .= "$dimension:{$value}$unit;";
		}
		
		// Border
		if (isset($options['outerBorder']) && $options['outerBorder'] == 0) {
			$iframe_styles .= "border:0;";
		} else {
			if (isset($options['borderColorDark'])) {
				$outer_border_color = $options['borderColorDark'];
			} else {
				$outer_border_color = $defaults['styles']['borderColorDark'];
			}
			$iframe_styles .= "border:1px solid $outer_border_color;";
		}
		
		return $iframe_styles;
	}
	
	public function addCustomStyle($elements, $rules) {
		if (! is_array($elements)) {
			$elements = array($elements);
		}
		if (! is_array($rules)) {
			$rules = array($rules);
		}
		foreach ($elements as $element) {
			foreach ($rules as $rule) {
				$this->custom_styles[$element][] = $rule;
			}
		}
	}
	
	public function processCustomStyles($options) {
		if (empty($options)) {
			return;	
		}
		$defaults = $this->getDefaults();
		foreach ($options as $var => $val) {
			if (stripos($var, 'amp;') !== false) {
				$var = str_replace('amp;', '', $var);
			}
			$val = trim($val);
			$var = trim($var);
			if ($val == '') {
				continue;
			} elseif (isset($defaults['styles'][$var])) {
				if ($defaults['styles'][$var] == $val) {
					continue;
				}
			} else {
				continue;	
			}
			if (method_exists($this, "style_$var")) {
				$method = "style_$var";
				$this->$method($val);
			}
		}
	}
	
	private function style_textColorDefault($val) {
		$this->addCustomStyle(
			'body',
			"color:$val;"
		);
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				'#event_list li a',
				"color:$val;"
			);
		} elseif ($this->widget_type == 'month') {
			$this->addCustomStyle(
				array('table.calendar thead', '#event_lists .time'),
				"color:$val;"
			);
		}
	}

	private function style_textColorLight($val) {
		$this->addCustomStyle(
			array(
				'div.header',
				'div.header a',
				'.event table.details th',
				'.event .footer',
				'#widget_filters',
				'#event_list li .icon:before'
			),
			"color:$val;"
		);
		$this->addCustomStyle(
			'ul.header li',
			"border-right:1px solid $val;"
		);
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				array(
					'#event_list h2.day', 
					'#event_list p.no_events', 
					'#load_more_events_wrapper.loading a',
				),
				"color:$val;"
			);
		}
	}

	private function style_textColorLink($val) {
		$this->addCustomStyle(
			'a', 
			"color:$val;"
		);
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				'#event_list li a.event_link .title',
				"color:$val;"
			);
		}
	}
	
	private function style_borderColorLight($val) {
		$this->addCustomStyle(
			'a.back:first-child',
			"border-bottom:1px solid $val;"
		);
		$this->addCustomStyle(
			'a.back:last-child',
			"border-top:1px solid $val;"
		);
		$this->addCustomStyle(
			'.event .description',
			"border-top:1px solid $val;"
		);
		$this->addCustomStyle(
			'#widget_filters',
			"border:1px solid $val;"
		);
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				'#event_list li',
				"border-bottom-color:$val;"
			);
			$this->addCustomStyle(
				'#event_list li:first-child',
				"border-color:$val;"
			);
		} elseif ($this->widget_type == 'month') {
			$this->addCustomStyle(
				'#event_lists .close',
				"border-color:$val;"
			);	
		}
	}
	
	private function style_borderColorDark($val) {
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				'#event_list li:hover',
				"border-color:$val;"
			);
		} elseif ($this->widget_type == 'month') {
			$this->addCustomStyle(
				array(
					'table.calendar td',
					'table.calendar thead'
				),
				"border-color:$val;"
			);
		}
	}
	
	private function style_backgroundColorDefault($val) {
		$this->addCustomStyle(
			array(
				'html, body', 
				'#loading div:nth-child(1)'
			),
			"background-color:$val;"
		);
		if ($this->widget_type == 'month') {
			$this->addCustomStyle(
				'#event_lists > div > div', 
				"background-color:$val;"
			);
		}
	}
	
	private function style_backgroundColorAlt($val) {
		$this->addCustomStyle(
			'#widget_filters',
			"background-color:$val;"
		);
		if ($this->widget_type == 'feed') {
			$this->addCustomStyle(
				'#event_list li',
				"background-color:$val;"
			);
		} elseif ($this->widget_type == 'month') {
			$this->addCustomStyle(
				array(
					'table.calendar tbody li:nth-child(2n)',
					'#event_lists a.event:nth-child(even)',
					'#event_lists .close'
				),
				"background-color:$val;"
			);
		}
	}
	
	private function style_fontSize($val) {
		if ($this->widget_type == 'month') {
			$this->addCustomStyle(
				array(
					'table.calendar tbody li',
					'table.calendar .no_events'
				),
				"font-size:$val;"
			);
		}
	}
	
	private function style_showIcons($val) {
		if ($val) {
			return;	
		}
		if ($this->widget_type == 'month') {
			$this->addCustomStyle(
				'table.calendar .icon:before',
				"display:none;"
			);
		}
	}
	
	private function style_hideGeneralEventsIcon($val) {
		if (! $val) {
			return;	
		}
		if ($this->widget_type == 'month') {
			$this->addCustomStyle(
				'table.calendar .icon-general-events:before',
				"display:none;"
			);
		}
	}
}