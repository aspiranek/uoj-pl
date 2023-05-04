<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	
	if ($myUser == null || !isSuperUser($myUser)) {
		become403Page();
	}
	
	$user_form = new UOJForm('user');
	$user_form->addInput('username', 'text', 'Nazwa użytkownika', '',
		function ($username) {
			if (!validateUsername($username)) {
				return 'Nazwa użytkownika jest niepoprawna';
			}
			if (!queryUser($username)) {
				return 'Użytkownik nie istnieje';
			}
			return '';
		},
		null
	);
	$options = array(
		'banneduser' => 'ustaw jako zbanowany użytkownika',
		'normaluser' => 'ustaw jako zwykły użytkownik',
		'superuser' => 'ustaw jako superuser'
	);
	$user_form->addSelect('op-type', $options, 'Rodzaj operacji', '');
	$user_form->handle = function() {
		global $user_form;
		
		$username = $_POST['username'];
		switch ($_POST['op-type']) {
			case 'banneduser':
				DB::update("update user_info set usergroup = 'B' where username = '{$username}'");
				break;
			case 'normaluser':
				DB::update("update user_info set usergroup = 'U' where username = '{$username}'");
				break;
			case 'superuser':
				DB::update("update user_info set usergroup = 'S' where username = '{$username}'");
				break;
		}
	};
	$user_form->runAtServer();
	
	$blog_link_contests = new UOJForm('blog_link_contests');
	$blog_link_contests->addInput('blog_id', 'text', 'ID bloga', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawne ID';
			}
			if (!queryBlog($x)) {
				return 'Taki blog nie istnieje';
			}
			return '';
		},
		null
	);
	$blog_link_contests->addInput('contest_id', 'text', 'ID konkursu', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawne ID';
			}
			if (!queryContest($x)) {
				return 'Konkurs nie istnieje';
			}
			return '';
		},
		null
	);
	$blog_link_contests->addInput('title', 'text', 'Tytuł', '',
		function ($x) {
			return '';
		},
		null
	);
	$options = array(
		'add' => 'Dodaj',
		'del' => 'Usuń'
	);
	$blog_link_contests->addSelect('op-type', $options, 'Rodzaj operacji', '');
	$blog_link_contests->handle = function() {
		$blog_id = $_POST['blog_id'];
		$contest_id = $_POST['contest_id'];
		$str = DB::selectFirst(("select * from contests where id='${contest_id}'"));
		$all_config = json_decode($str['extra_config'], true);
		$config = $all_config['links'];

		$n = count($config);
		
		if ($_POST['op-type'] == 'add') {
			$row = array();
			$row[0] = $_POST['title'];
			$row[1] = $blog_id;
			$config[$n] = $row;
		}
		if ($_POST['op-type'] == 'del') {
			for ($i = 0; $i < $n; $i++) {
				if ($config[$i][1] == $blog_id) {
					$config[$i] = $config[$n - 1];
					unset($config[$n - 1]);
					break;
				}
			}
		}

		$all_config['links'] = $config;
		$str = json_encode($all_config);
		$str = DB::escape($str);
		DB::query("update contests set extra_config='${str}' where id='${contest_id}'");
	};
	$blog_link_contests->runAtServer();
	
	$blog_link_index = new UOJForm('blog_link_index');
	$blog_link_index->addInput('blog_id2', 'text', 'ID bloga', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawne ID';
			}
			if (!queryBlog($x)) {
				return 'Taki blog nie istnieje';
			}
			return '';
		},
		null
	);
	$blog_link_index->addInput('blog_level', 'text', 'Najwyższy poziom (usuń nie wypełniaj)', '0',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawna liczba';
			}
			if ($x > 3) {
				return 'Ten poziom nie istnieje';
			}
			return '';
		},
		null
	);
	$options = array(
		'add' => 'Dodaj',
		'del' => 'Usuń'
	);
	$blog_link_index->addSelect('op-type2', $options, 'Rodzaj operacji', '');
	$blog_link_index->handle = function() {
		$blog_id = $_POST['blog_id2'];
		$blog_level = $_POST['blog_level'];
		if ($_POST['op-type2'] == 'add') {
			if (DB::selectFirst("select * from important_blogs where blog_id = {$blog_id}")) {
				DB::update("update important_blogs set level = {$blog_level} where blog_id = {$blog_id}");
			} else {
				DB::insert("insert into important_blogs (blog_id, level) values ({$blog_id}, {$blog_level})");
			}
		}
		if ($_POST['op-type2'] == 'del') {
			DB::delete("delete from important_blogs where blog_id = {$blog_id}");
		}
	};
	$blog_link_index->runAtServer();
	
	$blog_deleter = new UOJForm('blog_deleter');
	$blog_deleter->addInput('blog_del_id', 'text', 'ID bloga', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawne ID';
			}
			if (!queryBlog($x)) {
				return 'Blog nie istnieje';
			}
			return '';
		},
		null
	);
	$blog_deleter->handle = function() {
		deleteBlog($_POST['blog_del_id']);
	};
	$blog_deleter->runAtServer();

	$contest_submissions_deleter = new UOJForm('contest_submissions');
	$contest_submissions_deleter->addInput('contest_id', 'text', 'ID konkursu', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'Niepoprawne ID';
			}
			if (!queryContest($x)) {
				return 'Blog nie istnieje';
			}
			return '';
		},
		null
	);
	$contest_submissions_deleter->handle = function() {
		$contest = queryContest($_POST['contest_id']);
		genMoreContestInfo($contest);
		
		$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']}");
		foreach ($contest_problems as $problem) {
			$submissions = DB::selectAll("select * from submissions where problem_id = {$problem['problem_id']} and submit_time < '{$contest['start_time_str']}'");
			foreach ($submissions as $submission) {
				$content = json_decode($submission['content'], true);
				unlink(UOJContext::storagePath().$content['file_name']);
				DB::delete("delete from submissions where id = {$submission['id']}");
				updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
			}
		}
	};
	$contest_submissions_deleter->runAtServer();

	$custom_test_deleter = new UOJForm('custom_test_deleter');
	$custom_test_deleter->addInput('last', 'text', 'Usuń ostatnie zgłoszenie', '5',
		function ($x, &$vdata) {
			if (!validateUInt($x)) {
				return 'Nielegalny';
			}
			$vdata['last'] = $x;
			return '';
		},
		null
	);
	$custom_test_deleter->handle = function(&$vdata) {
		$all = DB::selectAll("select * from custom_test_submissions order by id asc limit {$vdata['last']}");
		foreach ($all as $submission) {
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath().$content['file_name']);
		}
		DB::delete("delete from custom_test_submissions order by id asc limit {$vdata['last']}");
	};
	$custom_test_deleter->runAtServer();

	$judger_adder = new UOJForm('judger_adder');
	$judger_adder->addInput('judger_adder_name', 'text', 'Nazwa maszyny oceniającej', '',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return 'Nielegalny';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'")!=0) {
				return 'Nielegalny';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_adder->handle = function(&$vdata) {
		$password=uojRandString(32);
		DB::insert("insert into judger_info (judger_name,password) values('{$vdata['name']}','{$password}')");
	};
	$judger_adder->runAtServer();
	
	$judger_deleter = new UOJForm('judger_deleter');
	$judger_deleter->addInput('judger_deleter_name', 'text', 'Nazwa maszyny oceniającej', '',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return 'Nielegalny';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'")!=1) {
				return 'Nielegalny';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_deleter->handle = function(&$vdata) {
		DB::delete("delete from judger_info where judger_name='{$vdata['name']}'");
	};
	$judger_deleter->runAtServer();
	
	$judgerlist_cols = array('judger_name', 'password');
	$judgerlist_config = array();
	$judgerlist_header_row = <<<EOD
	<tr>
		<th>Nazwa maszyny oceniającej</th>
		<th>Hasło</th>
	</tr>
EOD;
	$judgerlist_print_row = function($row) {
		echo <<<EOD
			<tr>
				<td>{$row['judger_name']}</td>
				<td>{$row['password']}</td>
			</tr>
EOD;
	};
	
	$banlist_cols = array('username', 'usergroup');
	$banlist_config = array();
	$banlist_header_row = <<<EOD
	<tr>
		<th>Nazwa użytkownika</th>
	</tr>
EOD;
	$banlist_print_row = function($row) {
		$hislink = getUserLink($row['username']);
		echo <<<EOD
			<tr>
				<td>${hislink}</td>
			</tr>
EOD;
	};
	
	$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
	
	$tabs_info = array(
		'users' => array(
			'name' => 'zarządzaj użytkownikami',
			'url' => "/super-manage/users"
		),
		'blogs' => array(
			'name' => 'zarządzaj blogiem',
			'url' => "/super-manage/blogs"
		),
		'submissions' => array(
			'name' => 'zarządzaj zgłoszeniami',
			'url' => "/super-manage/submissions"
		),
		'custom-test' => array(
			'name' => 'niestandardowy test',
			'url' => '/super-manage/custom-test'
		),
		'click-zan' => array(
			'name' => 'zan',
			'url' => '/super-manage/click-zan'
		),
		'search' => array(
			'name' => 'zarządzaj wyszukiwaniem',
			'url' => '/super-manage/search'
		),
		'judger' => array(
			'name' => 'zarządzaj maszyną oceniającą',
			'url' => '/super-manage/judger'
		)
	);
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
?>
<?php
	requireLib('shjs');
	requireLib('morris');
?>
<?php echoUOJPageHeader('Zarządzanie systemem') ?>
<div class="row">
	<div class="col-sm-3">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills flex-column') ?>
	</div>
	
	<div class="col-sm-9">
		<?php if ($cur_tab === 'users'): ?>
			<?php $user_form->printHTML(); ?>
			<h3>Lista banów</h3>
			<?php echoLongTable($banlist_cols, 'user_info', "usergroup='B'", '', $banlist_header_row, $banlist_print_row, $banlist_config) ?>
		<?php elseif ($cur_tab === 'blogs'): ?>
			<div>
				<h4>Dodaj link do konkursu</h4>
				<?php $blog_link_contests->printHTML(); ?>
			</div>

			<div>
				<h4>Dodaj do biuletynu</h4>
				<?php $blog_link_index->printHTML(); ?>
			</div>
		
			<div>
				<h4>Usuń bloga</h4>
				<?php $blog_deleter->printHTML(); ?>
			</div>
		<?php elseif ($cur_tab === 'submissions'): ?>
			<div>
				<h4>Usuń zgłoszenia przedkonkursowe</h4>
				<?php $contest_submissions_deleter->printHTML(); ?>
			</div>
			<div>
				<h4>Oceny zakończone niepowodzeniem</h4>
				<?php echoSubmissionsList("result_error = 'Judgement Failed'", 'order by id desc', array('result_hidden' => ''), $myUser); ?>
			</div>
		<?php elseif ($cur_tab === 'custom-test'): ?>
		<?php $custom_test_deleter->printHTML() ?>
		<?php
			$submissions_pag = new Paginator(array(
				'col_names' => array('*'),
				'table_name' => 'custom_test_submissions',
				'cond' => '1',
				'tail' => 'order by id asc',
				'page_len' => 5
			));
			foreach ($submissions_pag->get() as $submission) {
				$problem = queryProblemBrief($submission['problem_id']);
				$submission_result = json_decode($submission['result'], true);
				echo '<dl class="dl-horizontal">';
				echo '<dt>id</dt>';
				echo '<dd>', "#{$submission['id']}", '</dd>';
				echo '<dt>problem_id</dt>';
				echo '<dd>', "#{$submission['problem_id']}", '</dd>';
				echo '<dt>submit time</dt>';
				echo '<dd>', $submission['submit_time'], '</dd>';
				echo '<dt>submitter</dt>';
				echo '<dd>', $submission['submitter'], '</dd>';
				echo '<dt>judge_time</dt>';
				echo '<dd>', $submission['judge_time'], '</dd>';
				echo '</dl>';
				echoSubmissionContent($submission, getProblemCustomTestRequirement($problem));
				echoCustomTestSubmissionDetails($submission_result['details'], "submission-{$submission['id']}-details");
			}
		?>
		<?= $submissions_pag->pagination() ?>
		<?php elseif ($cur_tab === 'click-zan'): ?>
		Złe QAQ
		<?php elseif ($cur_tab === 'search'): ?>
		<h2 class="text-center">Tygodniowe wyszukiwanie</h2>
		<div id="search-distribution-chart-week" style="height: 250px;"></div>
		<script type="text/javascript">
			new Morris.Line({
				element: 'search-distribution-chart-week',
				data: <?= json_encode(DB::selectAll("select DATE_FORMAT(created_at, '%Y-%m-%d %h:00'), count(*) from search_requests  where created_at > now() - interval 1 week group by DATE_FORMAT(created_at, '%Y-%m-%d %h:00')")) ?>,
				xkey: "DATE_FORMAT(created_at, '%Y-%m-%d %h:00')",
				ykeys: ["count(*)"],
				labels: ['number'],
				resize: true
			});
		</script>
		
		<h2 class="text-center">Wyszukiwanie styczniowe</h2>
		<div id="search-distribution-chart-month" style="height: 250px;"></div>
		<script type="text/javascript">
			new Morris.Line({
				element: 'search-distribution-chart-month',
				data: <?= json_encode(DB::selectAll("select DATE_FORMAT(created_at, '%Y-%m-%d'), count(*) from search_requests  where created_at > now() - interval 1 week group by DATE_FORMAT(created_at, '%Y-%m-%d')")) ?>,
				xkey: "DATE_FORMAT(created_at, '%Y-%m-%d')",
				ykeys: ["count(*)"],
				labels: ['number'],
				resize: true
			});
		</script>
		
		<?php echoLongTable(array('*'), 'search_requests', "1", 'order by id desc',
			'<tr><th>id</th><th>created_at</th><th>remote_addr</th><th>type</th><th>q</th><tr>',
			function($row) {
				echo '<tr>';
				echo '<td>', $row['id'], '</td>';
				echo '<td>', $row['created_at'], '</td>';
				echo '<td>', $row['remote_addr'], '</td>';
				echo '<td>', $row['type'], '</td>';
				echo '<td>', HTML::escape($row['q']), '</td>';
				echo '</tr>';
			}, array(
				'page_len' => 1000
			))
		?>
		<?php elseif ($cur_tab === 'judger'): ?>
			<div>
				<h4>Dodaj maszynę oceniającą</h4>
				<?php $judger_adder->printHTML(); ?>
			</div>
			<div>
				<h4>Usuń maszynę oceniającą</h4>
				<?php $judger_deleter->printHTML(); ?>
			</div>
			<h3>Lista maszyn oceniających</h3>
			<?php echoLongTable($judgerlist_cols, 'judger_info', "1=1", '', $judgerlist_header_row, $judgerlist_print_row, $judgerlist_config) ?>
		<?php endif ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
