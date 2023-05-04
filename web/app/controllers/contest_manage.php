<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}
	
	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', 'Nazwa', $contest['name'],
		function($str) {
			return '';
		},
		null
	);
	$time_form->addInput(
		'start_time', 'text', 'Czas rozpoczęcia', $contest['start_time_str'],
		function($str, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($str);
			} catch (Exception $e) {
				return 'Nieprawidłowy format czasu';
			}
			return '';
		},
		null
	);
	$time_form->addInput(
		'last_min', 'text', 'Czas trwania (w minutach)', $contest['last_min'],
		function($str) {
			return !validateUInt($str) ? 'Musi być liczbą całkowitą' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		global $contest;
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		
		$purifier = HTML::pruifier();
		
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		$esc_name = DB::escape($esc_name);
		
		DB::update("update contests set start_time = '$start_time_str', last_min = {$_POST['last_min']}, name = '$esc_name' where id = {$contest['id']}");
	};
	
	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "Nie istnieje użytkownik o nazwie {$username}";
			}
			return '';
		},
		function($type, $username) {
			global $contest;
			if ($type == '+') {
				DB::query("insert into contests_permissions (contest_id, username) values (${contest['id']}, '$username')");
			} elseif ($type == '-') {
				DB::query("delete from contests_permissions where contest_id = ${contest['id']} and username = '$username'");
			}
		}
	);
	
	$problems_form = newAddDelCmdForm('problems',
		function($cmd) {
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "Nieprawidłowy numer zadania";
			}
			$problem_id = $matches[1];
			if (!validateUInt($problem_id) || !($problem = queryProblemBrief($problem_id))) {
				return "Nie ma zadania o ID {$problem_id}";
			}
			if (!hasProblemPermission(Auth::user(), $problem)) {
				return "Nie masz uprawnień do dodania problemu o ID {$problem_id}";
			}
			return '';
		},
		function($type, $cmd) {
			global $contest;
			
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "Nieprawidłowe ID zadania";
			}
			
			$problem_id = $matches[1];
			
			if ($type == '+') {
				DB::insert("insert into contests_problems (contest_id, problem_id) values ({$contest['id']}, '$problem_id')");
			} elseif ($type == '-') {
				DB::delete("delete from contests_problems where contest_id = {$contest['id']} and problem_id = '$problem_id'");
			}
			
			if (isset($matches[2])) {
				switch ($matches[2]) {
					case '[sample]':
						unset($contest['extra_config']["problem_$problem_id"]);
						break;
					case '[full]':
						$contest['extra_config']["problem_$problem_id"] = 'full';
						break;
					case '[no-details]':
						$contest['extra_config']["problem_$problem_id"] = 'no-details';
						break;
				}
				$esc_extra_config = json_encode($contest['extra_config']);
				$esc_extra_config = DB::escape($esc_extra_config);
				DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
			}
		}
	);
	
	if (isSuperUser($myUser)) {
		$rating_k_form = new UOJForm('rating_k');
		$rating_k_form->addInput('rating_k', 'text', 'Górna granica zmian klasyfikacji', isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400,
			function ($x) {
				if (!validateUInt($x) || $x < 1 || $x > 1000) {
					return 'Nielegalna granica';
				}
				return '';
			},
			null
		);
		$rating_k_form->handle = function() {
			global $contest;
			$contest['extra_config']['rating_k'] = $_POST['rating_k'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$rating_k_form->runAtServer();
		
		$rated_form = new UOJForm('rated');
		$rated_form->handle = function() {
			global $contest;
			if (isset($contest['extra_config']['unrated'])) {
				unset($contest['extra_config']['unrated']);
			} else {
				$contest['extra_config']['unrated'] = '';
			}
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$rated_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
		$rated_form->submit_button_config['text'] = isset($contest['extra_config']['unrated']) ? 'liczony do rankingu' : 'nieliczony do rankingu';
		$rated_form->submit_button_config['smart_confirm'] = '';
	
		$rated_form->runAtServer();
		
		$version_form = new UOJForm('version');
		$version_form->addInput('standings_version', 'text', 'wersja rankingu', $contest['extra_config']['standings_version'],
			function ($x) {
				if (!validateUInt($x) || $x < 1 || $x > 2) {
					return 'nieprawidłowy numer wersji';
				}
				return '';
			},
			null
		);
		$version_form->handle = function() {
			global $contest;
			$contest['extra_config']['standings_version'] = $_POST['standings_version'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$version_form->runAtServer();

		$contest_type_form = new UOJForm('contest_type');
		$contest_type_form->addInput('contest_type', 'text', 'system konkursowy', $contest['extra_config']['contest_type'],
			function ($x) {
				if ($x != 'OI' && $x != 'ACM' && $x != 'IOI') {
					return 'niepoprawna nazwa formatu';
				}
				return '';
			},
			null
		);
		$contest_type_form->handle = function() {
			global $contest;
			$contest['extra_config']['contest_type'] = $_POST['contest_type'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$contest_type_form->runAtServer();
	}
	
	$time_form->runAtServer();
	$managers_form->runAtServer();
	$problems_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - zarządzanie konkursem') ?>
<h1 class="page-header" align="center"><?=$contest['name']?>Zarządzaj</h1>
<ul class="nav nav-tabs mb-3" role="tablist">
	<li class="nav-item"><a class="nav-link active" href="#tab-time" role="tab" data-toggle="tab">Czas rywalizacji</a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-managers" role="tab" data-toggle="tab">Menedżer</a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-problems" role="tab" data-toggle="tab">Zadania</a></li>
	<?php if (isSuperUser($myUser)): ?>
	<li class="nav-item"><a class="nav-link" href="#tab-others" role="tab" data-toggle="tab">Inne</a></li>
	<?php endif ?>
	<li class="nav-item"><a class="nav-link" href="/contest/<?=$contest['id']?>" role="tab">Wróć</a></li>
</ul>
<div class="tab-content top-buffer-sm">
	<div class="tab-pane active" id="tab-time">
		<?php $time_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-managers">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>Nazwa użytkownika</th>
				</tr>
			</thead>
			<tbody>
<?php
	$row_id = 0;
	$result = DB::query("select username from contests_permissions where contest_id = {$contest['id']}");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">Format polecenia: jedno polecenie na linię, +mike oznacza dodanie mike'a, -mike usunięcie </p>
		<?php $managers_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-problems">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>Nazwa zadania</th>
				</tr>
			</thead>
			<tbody>
<?php
	$result = DB::query("select problem_id from contests_problems where contest_id = ${contest['id']} order by problem_id asc");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$problem = queryProblemBrief($row['problem_id']);
		$problem_config_str = isset($contest['extra_config']["problem_{$problem['id']}"]) ? $contest['extra_config']["problem_{$problem['id']}"] : 'sample';
		echo '<tr>', '<td>', $problem['id'], '</td>', '<td>', getProblemLink($problem), ' ', "[$problem_config_str]", '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">Format polecenia: jedno polecenie na linię, +233 oznacza dodanie zadania o ID 233, -233 usunięcie</p>
		<?php $problems_form->printHTML(); ?>
	</div>
	<?php if (isSuperUser($myUser)): ?>
	<div class="tab-pane" id="tab-others">
		<div class="row">
			<div class="col-sm-12">
				<h3>Ranking</h3>
				<div class="row">
					<div class="col-sm-3">
						<?php $rated_form->printHTML(); ?>
					</div>
				</div>
				<div class="top-buffer-sm"></div>
				<?php $rating_k_form->printHTML(); ?>
			</div>
			<div class="col-sm-12 top-buffer-sm">
				<h3>Wersja</h3>
				<?php $version_form->printHTML(); ?>
			</div>
			<div class="col-sm-12 top-buffer-sm">
				<h3>System konkursowy</h3>
				<?php $contest_type_form->printHTML(); ?>
			</div>
		</div>
	</div>
	<?php endif ?>
</div>
<?php echoUOJPageFooter() ?>
