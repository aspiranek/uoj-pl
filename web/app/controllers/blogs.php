<?php
	requirePHPLib('form');
	
	function echoBlogCell($blog) {
		echo '<tr>';
		echo '<td>' . getBlogLink($blog['id']) . '</td>';
		echo '<td>' . getUserLink($blog['poster']) . '</td>';
		echo '<td>' . $blog['post_time'] . '</td>';
		echo '</tr>';
	}
	$header = <<<EOD
	<tr>
		<th width="60%">Tytuł</th>
		<th width="20%">Autor</th>
		<th width="20%">Data dodania</th>
	</tr>
EOD;
	$config = array();
	$config['table_classes'] = array('table', 'table-hover');
?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>
<?php if (Auth::check()): ?>
<div class="float-right">
	<div class="btn-group">
		<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-secondary btn-sm">Strona główna</a>
		<a href="<?= HTML::blog_url(Auth::id(), '/post/new/write')?>" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-edit"></span> Nowy blog</a>
	</div>
</div>
<?php endif ?>
<h3>Przeglądaj</h3>
<?php echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan'), 'blogs', 'is_hidden = 0', 'order by post_time desc', $header, 'echoBlogCell', $config); ?>
<?php echoUOJPageFooter() ?>
