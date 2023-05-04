
<ul class="nav nav-pills float-right" role="tablist">
	<li class="nav-item"><a class="nav-link active" href="#tab-question" role="tab" data-toggle="tab">Zadaj pytanie</a></li>
	<?php if ($post_notice): ?>
		<li class="nav-item"><a class="nav-link" href="#tab-notice" role="tab" data-toggle="tab">Ogłoszenia</a></li>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<li class="nav-item"><a class="nav-link" href="#tab-standings" role="tab" data-toggle="tab">Ostateczny ranking</a></li>
	<?php endif ?>
</ul>
<div class="tab-content">
	<div class="tab-pane active" id="tab-question">
		<h3>Zadaj pytanie</h3>
		<?php uojIncludeView('contest-question-table', ['pag' => $questions_pag, 'can_reply' => true, 'reply_question' => $reply_question]) ?>
	</div>
	<?php if ($post_notice): ?>
		<div class="tab-pane" id="tab-notice">
			<h3>Zamieść ogłoszenie o konkursie</h3>
			<?php $post_notice->printHTML() ?>
		</div>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<div class="tab-pane" id="tab-standings">
			<h3>Ostateczny ranking</h3>
			<?php uojIncludeView('contest-standings', $standings_data) ?>
		</div>
	<?php endif ?>
</div>
