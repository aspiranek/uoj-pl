<?php
	requirePHPLib('form');
	
	if (!UOJContext::hasBlogPermission()) {
		become403Page();
	}
	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHis($blog)) {
		become404Page();
	}
	
	$delete_form = new UOJForm('delete');
	$delete_form->handle = function() {
		global $blog;
		deleteBlog($blog['id']);
	};
	$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
	$delete_form->submit_button_config['text'] = 'Tak, jestem pewien, że chcę usunąć';
	$delete_form->succ_href = "/archive";
	
	$delete_form->runAtServer();
?>
<?php echoUOJPageHeader('Usuń bloga - ' . HTML::stripTags($blog['title'])) ?>
<h3>Czy na pewno chcesz usunąć blog <?= $blog['title'] ?>? Ta operacja jest nieodwracalna!</h3>
<?php $delete_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
