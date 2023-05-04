<?php
	requirePHPLib('form');
	
	$blogs_cond = "poster = '".UOJContext::userid()."' and is_draft = false";
	if (!UOJContext::hasBlogPermission()) {
		$blogs_cond .= " and is_hidden = false";
	}
	
	$display_blogs_cond = $blogs_cond;
	
	if (isset($_GET['tag'])) {
		$blog_tag_required = $_GET['tag'];
		$display_blogs_cond .= " and '".DB::escape($blog_tag_required)."' in (select tag from blogs_tags where blogs_tags.blog_id = blogs.id)";
	} else {
		$blog_tag_required = null;
	}
	
	$blogs_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs',
		'cond' => $display_blogs_cond,
		'tail' => 'order by post_time desc',
		'page_len' => 10
	));
	
	$all_tags = DB::selectAll("select distinct tag from blogs_tags where blog_id in (select id from blogs where $blogs_cond)");
	
	requireLib('mathjax');
	requireLib('shjs');
?>
<?php echoUOJPageHeader('Blog') ?>

<div class="row">
	<div class="col-md-3">
		<?php if (UOJContext::hasBlogPermission()): ?>
		<div class="btn-group d-flex">
			<a href="<?=HTML::blog_url(UOJContext::userid(), '/post/new/write')?>" class="btn btn-primary"><span class="glyphicon glyphicon-edit"></span> Stwórz nowy post</a>
			<a href="<?=HTML::blog_url(UOJContext::userid(), '/slide/new/write')?>" class="btn btn-primary"><span class="glyphicon glyphicon-edit"></span> Stwórz nowy slajd</a>
		</div>
		<?php endif ?>
		<div class="card border-info top-buffer-sm">
			<div class="card-header bg-info">Tag</div>
			<div class="card-body">
			<?php if ($all_tags): ?>
			<?php foreach ($all_tags as $tag): ?>
				<?php echoBlogTag($tag['tag']) ?>
			<?php endforeach ?>
			<?php else: ?>
				<div class="text-muted">jeszcze nie</div>
			<?php endif ?>
			</div>
		</div>
	</div>
	<div class="col-md-9">
		<?php if (!$blog_tag_required): ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="text-muted">Ta osoba jest leniwa i nie ma bloga (albo nie ma czasu by go stworzyć bo robi zadanka)</div>
			<?php else: ?>
			<?php foreach ($blogs_pag->get() as $blog): ?>
				<?php echoBlog($blog, array('is_preview' => true)) ?>
			<?php endforeach ?>
			<div class="text-right text-muted">Razem <?= $blogs_pag->n_rows ?> postów</div>
			<?php endif ?>
		<?php else: ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="alert alert-danger">
				Nie ma blogów z tagiem "<?= HTML::escape($blog_tag_required) ?>":
			</div>
			<?php else: ?>
			<div class="alert alert-success">
				Znaleziono <?= $blogs_pag->n_rows ?> blog(ów) zawierających tag "<?= HTML::escape($blog_tag_required) ?>":
			</div>
			<?php foreach ($blogs_pag->get() as $blog): ?>
				<?php echoBlog($blog, array('is_preview' => true)) ?>
			<?php endforeach ?>
			<?php endif ?>
		<?php endif ?>
		
		<?= $blogs_pag->pagination() ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
