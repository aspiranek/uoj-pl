<?php
	requirePHPLib('form');
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}
	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', 'nazwa', 'New Contest',
		function($str) {
			return '';
		},
		null
	);
	$time_form->addInput(
		'start_time', 'text', 'początek', date("Y-m-d H:i:s"),
		function($str, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($str);
			} catch (Exception $e) {
				return 'nieprawidłowy format czasu';
			}
			return '';
		},
		null
	);
	$time_form->addInput(
		'last_min', 'text', 'czas trwania (w minutach) ', 180,
		function($str) {
			return !validateUInt($str) ? 'Musi być liczbą całkowitą' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
				
		$purifier = HTML::pruifier();
		
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		$esc_name = DB::escape($esc_name);
		
		DB::query("insert into contests (name, start_time, last_min, status) values ('$esc_name', '$start_time_str', ${_POST['last_min']}, 'unfinished')");
	};
	$time_form->succ_href="/contests";
	$time_form->runAtServer();
?>
<?php echoUOJPageHeader('Nowy Kontest') ?>
<h1 class="page-header">Nowy Kontest</h1>
<div class="tab-pane active" id="tab-time">
<?php
	$time_form->printHTML();
?>
</div>
<?php echoUOJPageFooter() ?>
