<?php
/*
 * Any changes to this file should be copied over to /src/Template/Element/Pages/about.ctp
 * in the Muncie Events API codebase
 */
?>
<h1 class="page_title">
	About Muncie Events
</h1>

<p>
	Muncie Events is a free, comprehensive event promotion service provided to the city of
    Muncie, Indiana with the support of Ball State University's <a href="https://bsu.edu/cber">Center for Business and
    Economic Research</a> and the <a href="https://munciearts.org">Muncie Arts and Culture Council</a>. It serves as a
    central repository for event information that gets distributed to every website with
    <?= $this->Html->link(
        'a Muncie Events calendar',
        [
            'controller' => 'widgets',
            'action' => 'index',
            'plugin' => false
        ]
    ); ?>, to apps using <a href="https://api.muncieevents.com">the Muncie Events API</a>, and to
    <?php echo $this->Html->link(
        'a customizable mailing list',
        [
            'controller' => 'mailing_list',
            'action' => 'join',
            'plugin' => false
        ]
    ); ?>.
</p>

<p>
    Muncie Events began in 2003 as a component of the community website TheMuncieScene.com. Its event database currently
    holds information for <?= number_format($eventCount) ?> local events going back over <?= $yearsCount ?> years.
</p>

<?php
    $credits = array(
        'People' => array(
            '<a href="mailto:graham@phantomwatson.com">Graham Watson</a>' => 'Web Developer, Administrator',
            'Erica Dee Fox' => 'Web Developer',
            'Gunner Bills' => 'Mobile App Developer',
            'Michael Bratton' => 'Mobile App Developer',
            'Ronan Furlong' => 'Mobile App Developer',
            'Timothy Hartke' => 'Mobile App Developer',
            'Sydnee Kuebler' => 'Icon Designer',
            'Benjamin Easley' => 'Graphic Designer',
            'Nicholas Boyum' => 'Artist (map of Muncie background image)',
        ),
        'Organizations' => array(
            '<a href="https://munciearts.org">Muncie Arts and Culture Council</a>' => '',
            '<a href="http://bsu.edu/cber">Center for Business and Economic Research</a>' => ''
        ),
        'Software' => array(
            '<a href="http://cakephp.org">CakePHP</a>' => 'Back-end framework',
            '<a href="http://jquery.com/">jQuery</a> &amp; <a href="http://jqueryui.com/">jQuery UI</a>' => 'Front-end framework',
            '<a href="https://facebook.github.io/react-native/">React Native</a>' => 'Mobile app framework',
            '<a href="http://dimsemenov.com/plugins/magnific-popup/">Magnific Popup</a>' => 'Elegant media popups',
            '<a href="http://www.digitalmagicpro.com/jPicker/">jPicker</a>' => 'Color picker',
            '<a href="http://recaptcha.net/">reCAPTCHA</a>' => 'Spam defense',
        )
    );
?>

<ul id="credits">
	<?php foreach ($credits as $category => $members): ?>
		<li class="category"><?php echo $category ?><br class="break" /></li>
		<?php foreach ($members as $name => $position): ?>
			<li class="row"><div class="name"><?php echo $name ?></div><div class="position"><?php echo $position ?></div><br class="break" /></li>
		<?php endforeach; ?>
		<li class="row" style="border: none;">&nbsp;</li>
	<?php endforeach; ?>
</ul>
