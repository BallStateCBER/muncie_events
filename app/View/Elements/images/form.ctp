<?php
	$upload_max = ini_get('upload_max_filesize');
	$post_max = ini_get('post_max_size');
    $server_filesize_limit = min($upload_max, $post_max);
    $manual_filesize_limit = min('10M', $server_filesize_limit);
	$this->Html->script('image_manager.js', array('inline' => false));
?>

<div id="image_form">
	<ul class="actions">
		<li>
			<a href="#" id="image_upload_toggler">Upload new image</a>
		</li>
		<li>
			<a href="#" id="image_select_toggler">Select a previously uploaded image</a>
		</li>
		<li>
			<a href="#" id="image_help_toggler" class="help_toggler">Help / Rules</a>
		</li>
	</ul>

	<div id="image_upload_container" style="display: none;">
		<a href="#" id="image_upload_button">Select image</a>
	</div>

	<div id="image_select_container" style="display: none;"></div>

	<div id="image_help" class="help" style="display: none;">
		<h3>Uploading</h3>
		<ul class="footnote">
			<li>Images must be .jpg, .jpeg, .gif, or .png.</li>
			<li>Each file cannot exceed <?php echo $manual_filesize_limit; ?>B</li>
			<li>You can upload an image once and re-use it in multiple events.</li>
			<li>By uploading an image, you affirm that you are not violating any copyrights.</li>
            <li>Images must not include offensive language, nudity, or graphic violence</li>
		</ul>

		<h3>After selecting images</h3>
		<ul class="footnote">
			<li>
				The first image will be displayed as the event's main image.
			</li>
			<li>
				Click on the <img src="/img/icons/arrow-move.png" alt="Move" title="Move" /> icon to drag images up or down and resort them.
			</li>
			<li>
				Click on the <img src="/img/icons/cross.png" class="remove" alt="Remove" title="Remove" /> icon to unselect an image.
			</li>
		</ul>
	</div>

	<ul id="selected_images">
		<?php if (! empty($this->request->data['EventsImage'])): ?>
			<?php foreach ($this->request->data['EventsImage'] as $selected_image): ?>
				<?php
					$id = $selected_image['image_id'];
					$filename = $this->request->data['Image'][$id]['filename'];
				?>
				<li id="selectedimage_<?php echo $id; ?>" data-image-id="<?php echo $id; ?>">
					<img src="/img/icons/arrow-move.png" class="handle" alt="Move" title="Move" />
					<a href="#" class="remove"><img src="/img/icons/cross.png" class="remove" alt="Remove" title="Remove" /></a>
					<?php echo $this->Calendar->thumbnail('tiny', array(
						'filename' => $filename,
						'class' => 'selected_image'
					)); ?>
					<?php echo $this->Form->input("Image.$id", array(
						'label' => 'Caption:',
						'div' => false,
						'type' => 'text',
						'value' => $selected_image['caption'],
						'placeholder' => "Enter a caption for this image",
						'class' => 'caption'
					)); ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</div>

<?php
	echo $this->Html->script('/uploadifive/jquery.uploadifive.min.js', array('inline' => false));
	echo $this->Html->css('/uploadifive/uploadifive.css', null, array('inline' => false));
	$this->Js->buffer("
		ImageManager.setupUpload({
			token: '".md5(Configure::read('upload_verify_token').time())."',
			user_id: '".$this->Session->read('Auth.User.id')."',
			event_id: ".(isset($event_id) ? $event_id : 'null').",
			filesize_limit: '{$manual_filesize_limit}B',
			timestamp: ".time()."
		});
		ImageManager.user_id = $user_id;
		ImageManager.setupManager();
	");
?>