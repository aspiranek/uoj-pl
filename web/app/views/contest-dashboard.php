<div class="table-responsive">
	<table class="table table-bordered table-hover table-striped table-text-center">
		<thead>
			<tr>
				<th style="width:5em">#</th>
				<th><?= UOJLocale::get('problems::problem') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ($i = 0; $i < count($contest_problems); $i++): ?>
				<tr>
					<?php
						echo $contest_problems[$i]['submission_id'] ? '<td class="success">' : '<td>';
						echo chr(ord('A') + $i);
						echo '</td>';
					?>
					<td><?= getContestProblemLink($contest_problems[$i]['problem'], $contest['id']) ?></td>
				</tr>
			<?php endfor ?>
		</tbody>
	</table>
</div>

<h3><?= UOJLocale::get('contests::contest notice') ?></h3>
<div class="table-responsive">
	<table class="table table-bordered table-hover table-vertical-middle table-text-center">
		<thead>
			<tr>
				<th style="width:10em"><?= UOJLocale::get('title') ?></th>
				<th><?= UOJLocale::get('content') ?></th>
				<th style="width:12em"><?= UOJLocale::get('time') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($contest_notice)): ?>
				<tr><td colspan="233"><?= UOJLocale::get('none') ?></td></tr>
			<?php else: foreach ($contest_notice as $notice): ?>
				<tr>
					<td><?= HTML::escape($notice['title']) ?></td>
					<td style="white-space:pre-wrap; text-align: left"><?= $notice['content'] ?></td>
					<td><?= $notice['time'] ?></td>
				</tr>
			<?php endforeach; endif ?>
		</tbody>
	</table>
</div>


<?php if ($post_notice): ?>
	<div class="text-center">
		<button id="button-display-post-notice" type="button" class="btn btn-danger btn-xs">Zamieść ogłoszenie o konkursie</button>
	</div>
	<div id="div-form-post-notice" style="display:none" class="bot-buffer-md">
		<?php $post_notice->printHTML() ?>
	</div>
	<script type="text/javascript">
	$(document).ready(function() {
		$('#button-display-post-notice').click(function() {
			$('#div-form-post-notice').toggle('fast');
		});
	});
	</script>
<?php endif ?>

<h3>Zadaj pytanie</h3>
<?php if ($my_questions_pag != null): ?>
	<div>
		<?php if ($post_question): ?>
			<div class="float-right">
				<button id="button-display-post-question" type="button" class="btn btn-primary btn-xs">Zadaj pytanie</button>
			</div>
		<?php endif ?>
		<h4>Moje pytania</h4>
		<?php if ($post_question): ?>
			<div id="div-form-post-question" style="display:none" class="bot-buffer-md">
				<?php $post_question->printHTML() ?>
			</div>
			<script type="text/javascript">
			$(document).ready(function() {
				$('#button-display-post-question').click(function() {
					$('#div-form-post-question').toggle('fast');
				});
			});
			</script>
		<?php endif ?>
		<?php uojIncludeView('contest-question-table', ['pag' => $my_questions_pag]) ?>
	</div>
<?php endif ?>

<div>
	<?php if ($my_questions_pag != null): ?>
		<h4>Pytania innych</h4>
	<?php else: ?>
		<h4>Wszystkie pytania</h4>
	<?php endif ?>
	<?php uojIncludeView('contest-question-table', ['pag' => $others_questions_pag]) ?>
</div>
