<?php
/**
 * @var \View $this
 * @var array $events
 */

$leave_open = (isset($open_only_event) && $open_only_event && count($events) == 1);
?>

<ul class="event_accordion">
    <?php foreach ($events as $event): ?>
        <li <?php if (! empty($event['EventsImage'])): ?>class="with_images"<?php endif; ?>>
            <?php
                $url = Router::url(array(
                    'controller' => 'events',
                    'action' => 'view',
                    'id' => $event['Event']['id']
                ), true);
                $eventId = $event['Event']['id'];
                $address = $event['Event']['address'];
                $location = $event['Event']['location'];
                $locationDetails = $event['Event']['location_details'];
                $cost = $event['Event']['cost'];
                $ageRestriction = $event['Event']['age_restriction'];
                $isVirtual = $location == 'Virtual Event';
                $hasDetails = $cost || $ageRestriction || $isVirtual;
            ?>
            <?php if (! empty($event['EventsImage'])): ?>
                <span class="tiny_thumbnails">
                    <?php foreach ($event['EventsImage'] as $image): ?>
                        <?= $this->Calendar->thumbnail('tiny', array(
                            'filename' => $image['Image']['filename'],
                            'caption' => $image['caption'],
                            'group' => 'event'.$event['Event']['id'].'_tiny_tn'
                        )) ?>
                    <?php endforeach; ?>
                </span>
            <?php endif; ?>
            <a data-toggle="collapse" data-target="#more_info_<?= $eventId ?>" href="<?php echo $url; ?>" title="Click for more info" class="more_info_handle" id="more_info_handle_<?= $eventId ?>">
                <?php echo $this->Icon->category($event['Category']['name']); ?>
                <span class="title">
                    <?php echo $event['Event']['title']; ?>
                </span>
                <span class="when">
                    <?php echo $this->Calendar->eventTime($event); ?>
                    @
                </span>
                <span class="where">
                    <?= $location ? $location : '&nbsp;' ?>
                    <?php if ($location != 'Virtual Event'): ?>
                        <?php if ($locationDetails): ?>
                            <span class="location_details" id="location_details_<?= $eventId ?>">
                                <?= $locationDetails ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($address): ?>
                            <span class="address" id="address_<?= $eventId ?>">
                                <?= $address ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </a>
            <div class="collapse" id="more_info_<?php echo $event['Event']['id']; ?>" <?php if (! $leave_open): ?>style="height: 0;"<?php endif; ?>>
                <div class="card">
                    <div class="card-header">
                        <?php echo $this->element('events/actions', compact('event')); ?>
                        <?php if ($hasDetails): ?>
                            <div class="details">
                                <table>
                                    <?php if ($location == 'Virtual Event'): ?>
                                        <tr class="cost">
                                            <th>URL:</th>
                                            <td>
                                                <?= $address ? $this->Text->autoLinkUrls($address) : 'URL not provided' ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($cost): ?>
                                        <tr class="cost">
                                            <th>Cost:</th>
                                            <td>
                                                <?= $cost ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($ageRestriction): ?>
                                        <tr class="age_restriction detail" id="age_restriction_<?= $eventId ?>">
                                            <th>Ages:</th>
                                            <td>
                                                <?= $ageRestriction ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="description">
                        <?php if (! empty($event['EventsImage'])): ?>
                            <div class="images">
                                <?php foreach ($event['EventsImage'] as $image): ?>
                                    <?php echo $this->Calendar->thumbnail('small', array(
                                        'filename' => $image['Image']['filename'],
                                        'caption' => $image['caption'],
                                        'group' => 'event'.$event['Event']['id']
                                    )); ?>
                                    <?php if ($image['caption']): ?>
                                        <span class="caption">
                                            <?php echo $image['caption']; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($event['Event']['description']): ?>
                            <?php echo $this->Text->autolink($event['Event']['description'], array('escape' => false)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <table class="details">
                            <?php if (! empty($event['Tag'])): ?>
                                <tr class="tags">
                                    <th>Tags:</th>
                                    <td>
                                        <?php echo $this->Calendar->eventTags($event); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (! empty($event['Event']['series_id']) && ! empty($event['EventSeries']['title'])): ?>
                                <tr class="tags">
                                    <th>Series:</th>
                                    <td>
                                        <?php echo $this->Html->link($event['EventSeries']['title'], array(
                                            'controller' => 'event_series',
                                            'action' => 'view',
                                            'id' => $event['EventSeries']['id']
                                        )); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($event['Event']['source']): ?>
                                <tr class="source">
                                    <th>Source:</th>
                                    <td>
                                        <?php echo $this->Text->autoLink($event['Event']['source']); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr class="link">
                                <th>Link:</th>
                                <td>
                                    <?php echo $this->Html->link($url, $url); ?>
                                </td>
                            </tr>
                            <?php if (isset($event['User']['name']) && $event['User']['name']): ?>
                                <tr class="author">
                                    <th>
                                        Author:
                                    </th>
                                    <td>
                                        <?php echo $this->Html->link(
                                            $event['User']['name'],
                                            array(
                                                'controller' => 'users',
                                                'action' => 'view',
                                                'id' => $event['User']['id']
                                            )
                                         ); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
<?php
	if ($leave_open) {
		$this->Js->buffer("$('.event_accordion a.tn_tiny').hide();");
	}
