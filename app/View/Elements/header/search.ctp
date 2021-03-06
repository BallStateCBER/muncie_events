<div>
    <?= $this->Form->create('Event', [
        'id' => 'EventSearchForm',
        'url' => array_merge(['action' => 'search'], $this->params['pass'])
    ]) ?>
    <?= $this->Form->input('filter', [
        'div' => false,
        'label' => false,
        'class' => 'form-control',
        'before' => '<img src="/img/loading_small_dark.gif" id="search_autocomplete_loading" /> '
    ]) ?>
    <div class="input-group-btn">
        <div class="btn-group">
            <?= $this->Form->submit('Search', [
                'div' => false,
                'class' => 'btn btn-default btn-sm'
            ]) ?>
            <button id="search_options_toggler" class="dropdown-toggle btn btn-secondary btn-sm" type="button" data-toggle="collapse" aria-haspopup="true" aria-expanded="false" data-target="#search_options">
                <span class="caret"></span>
                <span class="sr-only">Search options</span>
            </button>
            <div id="search_options" class="collapse" aria-labelledby="search_options_toggler">
                <div>
                    <?= $this->Form->input('direction', [
                        'options' => [
                            'future' => 'Upcoming Events',
                            'past' => 'Past Events',
                            'all' => 'All Events'
                        ],
                        'default' => 'future',
                        'type' => 'radio',
                        'legend' => false,
                        'separator' => '<br />'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <?= $this->Form->end() ?>
    <?php $this->Js->buffer("setupSearch();"); ?>
</div>
