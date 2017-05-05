<ul class="navbar-nav">
	<li class="<?php echo (!empty($this->params['action']) && ($this->params['action']=='home'))?'active ' :'' ?>nav-item"><?php
        echo $this->Html->link('Home', array(
            'plugin' => false,
            'controller' =>
            'pages',
            'action' => 'home'),
            array(
                'class' => 'nav-link',
            ));
    ?></li>
	<li class="nav-item">
		<a class="nav-link" id="date_picker_toggler" data-toggle="collapse" href="#header_nav_datepicker" aria-expanded="false" aria-controls="header_nav_datepicker">Go to Date...</a>
		<?php
            if (! isset($default)) {
                $default = date('m/d/Y');
            }
        ?>
		<div id="header_nav_datepicker" class="collapse" aria-labelledby="date_picker_toggler">
			<div>
				<?php
                    $day_links = array();
                    for ($n = 0; $n < 30; $n++) {
                        // Skip if date has no events
                        $timestamp = strtotime("+$n days");
                        $month_year = date('m-Y', $timestamp);
                        if (! isset($header_vars['populated_dates'][$month_year])) {
                            continue;
                        }
                        $day = date('d', $timestamp);
                        $pop_dates_in_month = $header_vars['populated_dates'][$month_year];
                        if (! in_array($day, $pop_dates_in_month)) {
                            continue;
                        }

                        // Today
                        if ($n == 0) {
                            $day_links[] = $this->Html->link('Today', array(
                                'controller' => 'events',
                                'action' => 'today'
                            ));
                        // Tomorrow
                        } elseif ($n == 1) {
                            $day_links[] = $this->Html->link('Tomorrow', array(
                                'controller' => 'events',
                                'action' => 'tomorrow'
                            ));
                        // Monday, Tuesday, etc.
                        } elseif ($n < 7) {
                            $day_links[] = $this->Html->link(date('l', $timestamp), array(
                                'controller' => 'events',
                                'action' => 'day',
                                date('m', $timestamp),
                                date('d', $timestamp),
                                date('Y', $timestamp)
                            ));
                        // A week or more in the future
                        } else {
                            $day_links[] = $this->Html->link(date('D, M j', $timestamp), array(
                                'controller' => 'events',
                                'action' => 'day',
                                date('m', $timestamp),
                                date('d', $timestamp),
                                date('Y', $timestamp)
                            ));
                        }
                        if (count($day_links) == 7) {
                            break;
                        }
                    }
                ?>
				<?php if (! empty($day_links)): ?>
					<ul>
						<?php foreach ($day_links as $day_link): ?>
							<li>
								<?php echo $day_link; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div id="header_datepicker"></div>
			</div>
		</div>
	</li>
	<li class="<?php echo (!empty($this->params['action']) && ($this->params['action']=='add'))?'active ' :'' ?>nav-item">
		<?php echo $this->Html->link('Add Event', array('plugin' => false, 'controller' => 'events', 'action' => 'add'), array('class'=>'nav-link')); ?>
	</li>
	<li class="<?php echo (!empty($this->params['action']) && ($this->params['controller']=='widgets'))?'active ' :'' ?>nav-item">
		<?php echo $this->Html->link('Widgets', array('plugin' => false, 'controller' => 'widgets', 'action' => 'index'), array('class'=>'nav-link')); ?>
	</li>
</ul>
<?php
    if (isset($header_vars['populated_dates'])) {
        foreach ($header_vars['populated_dates'] as $month => $days) {
            $quoted_days = array();
            foreach ($days as $day) {
                $quoted_days[] = "'$day'";
            }
            $this->Js->buffer("muncieEvents.populatedDates['$month'] = [" . implode(',', $quoted_days) . "];");
        }
    }
    $this->Js->buffer("setupHeaderNav();");
?>
