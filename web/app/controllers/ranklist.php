<?php
	if (isset($_GET['type']) && $_GET['type'] == 'rating') {
		$config = array('page_len' => 100);
	} else {
		become404Page();
	}
?>
<?php echoUOJPageHeader('Ranking') ?>
<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
