<?php
	// This element has $available_tags and (optionally) $selected_tags passed into it

	// Counters CakePHP's variable-renaming weirdness
	if (! isset($available_tags)) {
		$available_tags = isset($availableTags) ? $availableTags : array();
	}
	if (! isset($selected_tags)) {
		$selected_tags = isset($selectedTags) ? $selectedTags : array();
	}
	if (! isset($hide_label)) {
		$hide_label = false;
	}

	$this->Html->script('tag_manager.js', array('inline' => false));
?>

<div class="input" id="tag_editing">
	<?php /*
	<ul>
		<li>
			Suggested Tags
		</li>
		<li>
			All Tags
		</li>
	</ul>
	*/ ?>
	<div id="available_tags_container" class="form-control">
		<div id="available_tags"></div>
		<div id="popular_tags"></div>
	</div>
	<div class="text-muted">
		Click <img src="/img/icons/menu-collapsed.png" /> to expand groups.
		Click
		<a href="#" title="Selectable tags will appear in blue" id="example_selectable_tag">selectable tags</a>
		to select them.
		<?php
			$this->Js->buffer("
				$('#example_selectable_tag').tooltip().click(function(event) {
					event.preventDefault();
				});
			");
		?>
	</div>

	<div id="selected_tags_container" style="display: none;">
		<span class="label">
			Selected tags:
		</span>
		<span id="selected_tags"></span>
		<div class="text-muted">
			Click on a tag to unselect it.
		</div>
	</div>

	<?php if ($allow_custom): ?>
		<div id="custom_tag_input_wrapper">
			<label for="custom_tag_input">
				Additional Tags
				<span id="tag_autosuggest_loading" style="display: none;">
					<img src="/img/loading_small.gif" alt="Working..." title="Working..." style="vertical-align:top;" />
				</span>
			</label>
			<?php
				echo $this->Form->input('custom_tags', array(
					'label' => false,
					'class' => 'form-control',
					'after' => '<div class="text-muted">Write out tags, separated by commas. <a href="#new_tag_rules" data-toggle="collapse">Rules for creating new tags</a></div>',
					'id' => 'custom_tag_input'
				));
			?>
            <?php $this->Js->buffer("TagManager.setupAutosuggest('#custom_tag_input');"); ?>
            <?php
                /*
                 * Any changes to tag rules should be copied over to /src/Template/Element/Pages/rules_tags.ctp
                 * in the Muncie Events API codebase
                 */
            ?>
            <div id="new_tag_rules" class="alert alert-info collapse">
				<p>
					Before entering new tags, please search for existing tags that meet your needs.
					Once you start typing, please select any appropriate suggestions that appear below the input field.
					Doing this will make it more likely that your event will be linked to popular tags that are viewed
                    by more visitors.
				</p>

				<p>
					New tags must:
				</p>
				<ul>
					<li>
						be short, general descriptions that people might search for, describing what will take place
                        at the event
					</li>
					<li>
						be general enough to also apply to other events (including events outside of a series)
					</li>
				</ul>

				<p>
					And must not:
				</p>
				<ul>
					<li>
						include punctuation, such as dashes, commas, slashes, periods, etc.
					</li>
					<li>
						include profanity, email addresses, or website addresses
					</li>
					<li>
						be the name of the event specific location (having this as a tag would be redundant, since people can
                        already view events by location)
					</li>
				</ul>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php echo $this->Tag->setup($available_tags, 'available_tags', $selected_tags); ?>
