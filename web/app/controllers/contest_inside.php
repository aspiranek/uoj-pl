<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);

	if (!hasContestPermission(Auth::user(), $contest)) {
		if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
			header("Location: /contest/{$contest['id']}/register");
			die();
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			if ($myUser == null || !hasRegistered(Auth::user(), $contest)) {
				becomeMsgPage("<h1>Konkurs w toku</h1><p>Niestety nie jesteś jeszcze zarejestrowany.</p>");
			}
		}
	}
	
	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'dashboard';
	}
	
	$tabs_info = array(
		'dashboard' => array(
			'name' => UOJLocale::get('contests::contest dashboard'),
			'url' => "/contest/{$contest['id']}"
		),
		'submissions' => array(
			'name' => UOJLocale::get('contests::contest submissions'),
			'url' => "/contest/{$contest['id']}/submissions"
		),
		'standings' => array(
			'name' => UOJLocale::get('contests::contest standings'),
			'url' => "/contest/{$contest['id']}/standings"
		)
	);
	
	if (hasContestPermission(Auth::user(), $contest)) {
		$tabs_info['backstage'] = array(
			'name' => UOJLocale::get('contests::contest backstage'),
			'url' => "/contest/{$contest['id']}/backstage"
		);
	}
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
	
	if (isset($_POST['check_notice'])) {
		$result = DB::query("select * from contests_notice where contest_id = '${contest['id']}' order by time desc limit 10");
		$ch = array();
		$flag = false;
		try {
			while ($row = DB::fetch($result)) {
				if (new DateTime($row['time']) > new DateTime($_POST['last_time'])) {
					$ch[] = $row['title'].': '.$row['content'];
				}
			}
		} catch (Exception $e) {
		}
		global $myUser;
		$result=DB::query("select * from contests_asks where contest_id='${contest['id']}' and username='${myUser['username']}' order by reply_time desc limit 10");
		try {
			while ($row = DB::fetch($result)) {
				if (new DateTime($row['reply_time']) > new DateTime($_POST['last_time'])) {
					$ch[] = $row['question'].': '.$row['answer'];
				}
			}
		} catch (Exception $e) {
		}
		if ($ch) {
			die(json_encode(array('msg' => $ch, 'time' => UOJTime::$time_now_str)));
		} else {
			die(json_encode(array('time' => UOJTime::$time_now_str)));
		}
	}
	
	if (isSuperUser($myUser)) {
		if (CONTEST_PENDING_FINAL_TEST <= $contest['cur_progress'] && $contest['cur_progress'] <= CONTEST_TESTING) {
			$start_test_form = new UOJForm('start_test');
			$start_test_form->handle = function() {
				global $contest;
				$result = DB::query("select id, problem_id, content from submissions where contest_id = {$contest['id']}");
				while ($submission = DB::fetch($result, MYSQLI_ASSOC)) {
					if (!isset($contest['extra_config']["problem_{$submission['problem_id']}"])) {
						$content = json_decode($submission['content'], true);
						if (isset($content['final_test_config'])) {
							$content['config'] = $content['final_test_config'];
							unset($content['final_test_config']);
						}
						if (isset($content['first_test_config'])) {
							unset($content['first_test_config']);
						}
						$esc_content = DB::escape(json_encode($content));
						DB::update("update submissions set judge_time = NULL, result = '', score = NULL, status = 'Waiting Rejudge', content = '$esc_content' where id = {$submission['id']}");
					}
				}
				DB::query("update contests set status = 'testing' where id = {$contest['id']}");
			};
			$start_test_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$start_test_form->submit_button_config['smart_confirm'] = '';
			if ($contest['cur_progress'] < CONTEST_TESTING) {
				$start_test_form->submit_button_config['text'] = 'Rozpocznij testy końcowe';
			} else {
				$start_test_form->submit_button_config['text'] = 'Ponownie uruchom testy końcowe';
			}

			$start_test_form->runAtServer();
		}
		if ($contest['cur_progress'] >= CONTEST_TESTING) {
			$publish_result_form = new UOJForm('publish_result');
			$publish_result_form->handle = function() {
				// time config
				set_time_limit(0);
				ignore_user_abort(true);

				global $contest;
				$contest_data = queryContestData($contest);
				calcStandings($contest, $contest_data, $score, $standings, true);
				if (!isset($contest['extra_config']['unrated'])) {
					$rating_k = isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400;
					$ratings = calcRating($standings, $rating_k);
				} else {
					$ratings = array();
					for ($i = 0; $i < count($standings); $i++) {
						$ratings[$i] = $standings[$i][2][1];
					}
				}

				for ($i = 0; $i < count($standings); $i++) {
					$user = queryUser($standings[$i][2][0]);
					$change = $ratings[$i] - $user['rating'];
					$user_link = getUserLink($user['username']);

					if ($change != 0) {
						$tail = '<strong style="color:red">' . ($change > 0 ? '+' : '') . $change . '</strong>';
						$content = <<<EOD
<p>${user_link} witaj: </p>
<p class="indent2">Jesteś <a href="/contest/{$contest['id']}">{$contest['name']}</a> Ranking po konkursie zmienił się na ${tail}，Obecna pozycja to <strong style="color:red">{$ratings[$i]}</strong>.</p>
EOD;
					} else {
						$content = <<<EOD
<p>${user_link} witaj：</p>
<p class="indent2">Jesteś <a href="/contest/{$contest['id']}">{$contest['name']}</a> Ranking po konkursie nie zmienił się. Obecna pozycja to  <strong style="color:red">{$ratings[$i]}</strong>.</p>
EOD;
					}
					sendSystemMsg($user['username'], 'Zmiana oceny', $content);
					DB::query("update user_info set rating = {$ratings[$i]} where username = '{$standings[$i][2][0]}'");
					DB::query("update contests_registrants set rank = {$standings[$i][3]} where contest_id = {$contest['id']} and username = '{$standings[$i][2][0]}'");
				}
				DB::query("update contests set status = 'finished' where id = {$contest['id']}");
			};
			$publish_result_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$publish_result_form->submit_button_config['smart_confirm'] = '';
			$publish_result_form->submit_button_config['text'] = 'Publikacja wyników';
			
			$publish_result_form->runAtServer();
		}
	}
	
	if ($cur_tab == 'dashboard') {
		if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
			$post_question = new UOJForm('post_question');
			$post_question->addVTextArea('qcontent', 'Pytanie', '', 
				function($content) {
					if (!Auth::check()) {
						return 'Nie zalogowałeś się';
					}
					if (!$content || strlen($content) == 0) {
						return 'Pytanie nie może być puste!';
					}
					if (strlen($content) > 140 * 4) {
						return 'Pytanie jest zbyt długie!';
					}
					return '';
				},
				null
			);
			$post_question->handle = function() {
				global $contest;
				$content = DB::escape($_POST['qcontent']);
				$username = Auth::id();
				DB::query("insert into contests_asks (contest_id, question, username, post_time, is_hidden) values ('{$contest['id']}', '$content', '$username', now(), 1)");
			};
			$post_question->runAtServer();
		} else {
			$post_question = null;
		}
	} elseif ($cur_tab == 'backstage') {
		if (isSuperUser(Auth::user())) {
			$post_notice = new UOJForm('post_notice');
			$post_notice->addInput('title', 'text', 'Tytuł', '',
				function($title) {
					if (!$title) {
						return 'Tytuł nie może być pusty!';
					}
					return '';
				},
				null
			);
			$post_notice->addTextArea('content', 'Zawartość', '', 
				function($content) {
					if (!$content) {
						return 'Ogłoszenie nie może być puste!';
					}
					return '';
				},
				null
			);
			$post_notice->handle = function() {
				global $contest;
				$title = DB::escape($_POST['title']);
				$content = DB::escape($_POST['content']);
				DB::insert("insert into contests_notice (contest_id, title, content, time) values ('{$contest['id']}', '$title', '$content', now())");
			};
			$post_notice->runAtServer();
		} else {
			$post_notice = null;
		}
		
		if (hasContestPermission(Auth::user(), $contest)) {
			$reply_question = new UOJForm('reply_question');
			$reply_question->addHidden('rid', '0',
				function($id) {
					global $contest;
				    
					if (!validateUInt($id)) {
						return 'nieważne ID';
					}
					$q = DB::selectFirst("select * from contests_asks where id = $id");
					if ($q['contest_id'] != $contest['id']) {
						return 'nieważne ID';
					}
					return '';
				},
				null
			);
			$reply_question->addVSelect('rtype', [
				'public' => 'publiczny',
				'private' => 'niepubliczny',
				'statement' => 'Przeczytaj tytuł (niepubliczny)',
				'no_comment' => 'Bez odpowiedzi (niepubliczny)',
				'no_play' => 'Proszę zachowywać poważnie (niepubliczny)',
			], 'Typ odpowiedzi', 'private');
			$reply_question->addVTextArea('rcontent', 'odpowiedź', '', 
				function($content) {
					if (!Auth::check()) {
						return 'Nie zalogowałeś się';
					}
					switch ($_POST['rtype']) {
				    	case 'public':
				    	case 'private':
				    		if (strlen($content) == 0) {
				    			return 'Odpowiedź nie może być pusta!';
				    		}
							break;
				    }
					return '';
				},
				null
			);
			$reply_question->handle = function() {
				global $contest;
				$content = DB::escape($_POST['rcontent']);
				$is_hidden = 1;
				switch ($_POST['rtype']) {
					case 'statement':
						$content = 'Przeczytaj uważnie tytuł!';
						break;
					case 'no_comment':
						$content = 'Pytanie pozostawione bez odpowiedzi. ╮(╯▽╰)╭ ';
						break;
					case 'no_play':
						$content = 'Proszę zachowywać się poważnie (￣口￣)!!';
						break;
					case 'public':
						$is_hidden = 0;
						break;
					default:
						break;
				}
				DB::update("update contests_asks set answer = '$content', reply_time = now(), is_hidden = {$is_hidden} where id = {$_POST['rid']}");
			};
			$reply_question->runAtServer();
		} else {
			$reply_question = null;
		}
	}
	
	function echoDashboard() {
		global $contest, $post_notice, $post_question, $reply_question;
		
		$myname = Auth::id();
		$contest_problems = DB::selectAll("select contests_problems.problem_id, best_ac_submissions.submission_id from contests_problems left join best_ac_submissions on contests_problems.problem_id = best_ac_submissions.problem_id and submitter = '{$myname}' where contest_id = {$contest['id']} order by contests_problems.problem_id asc");
		
		for ($i = 0; $i < count($contest_problems); $i++) {
			$contest_problems[$i]['problem'] = queryProblemBrief($contest_problems[$i]['problem_id']);
		}
		
		$contest_notice = DB::selectAll("select * from contests_notice where contest_id = {$contest['id']} order by time desc");
		
		if (Auth::check()) {
			$my_questions = DB::selectAll("select * from contests_asks where contest_id = {$contest['id']} and username = '{$myname}' order by post_time desc");
			$my_questions_pag = new Paginator([
				'data' => $my_questions
			]);
		} else {
			$my_questions_pag = null;
		}
		
		$others_questions_pag = new Paginator([
			'col_names' => array('*'),
			'table_name' => 'contests_asks',
			'cond' => "contest_id = {$contest['id']} and username != '{$myname}' and is_hidden = 0",
			'tail' => 'order by reply_time desc',
			'page_len' => 10
		]);
		
		uojIncludeView('contest-dashboard', [
			'contest' => $contest,
			'contest_notice' => $contest_notice,
			'contest_problems' => $contest_problems,
			'post_question' => $post_question,
			'my_questions_pag' => $my_questions_pag,
			'others_questions_pag' => $others_questions_pag
		]);
	}
	
	function echoBackstage() {
		global $contest, $post_notice, $reply_question;
		
		$questions_pag = new Paginator([
			'col_names' => array('*'),
			'table_name' => 'contests_asks',
			'cond' => "contest_id = {$contest['id']}",
			'tail' => 'order by post_time desc',
			'page_len' => 50
		]);
		
		if ($contest['cur_progress'] < CONTEST_TESTING) {
			$contest_data = queryContestData($contest, ['pre_final' => true]);
			calcStandings($contest, $contest_data, $score, $standings);
			
			$standings_data = [
				'contest' => $contest,
				'standings' => $standings,
				'score' => $score,
				'contest_data' => $contest_data
			];
		} else {
			$standings_data = null;
		}
		
		uojIncludeView('contest-backstage', [
			'contest' => $contest,
			'post_notice' => $post_notice,
			'reply_question' => $reply_question,
			'questions_pag' => $questions_pag,
			'standings_data' => $standings_data
		]);
	}
	
	function echoMySubmissions() {
		global $contest, $myUser;

		$show_all_submissions_status = Cookie::get('show_all_submissions') !== null ? 'checked="checked" ' : '';
		$show_all_submissions = UOJLocale::get('contests::show all submissions');
		echo <<<EOD
			<div class="checkbox text-right">
				<label for="input-show_all_submissions"><input type="checkbox" id="input-show_all_submissions" $show_all_submissions_status/> $show_all_submissions</label>
			</div>
			<script type="text/javascript">
				$('#input-show_all_submissions').click(function() {
					if (this.checked) {
						$.cookie('show_all_submissions', '');
					} else {
						$.removeCookie('show_all_submissions');
					}
					location.reload();
				});
			</script>
EOD;
		if (Cookie::get('show_all_submissions') !== null) {
			echoSubmissionsList("contest_id = {$contest['id']}", 'order by id desc', array('judge_time_hidden' => ''), $myUser);
		} else {
			echoSubmissionsList("submitter = '{$myUser['username']}' and contest_id = {$contest['id']}", 'order by id desc', array('judge_time_hidden' => ''), $myUser);
		}
	}
	
	function echoStandings() {
		global $contest;
		
		$contest_data = queryContestData($contest);
		calcStandings($contest, $contest_data, $score, $standings);
		
		uojIncludeView('contest-standings', [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data
		]);
	}
	
	function echoContestCountdown() {
		global $contest;
		$rest_second = $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
		$time_str = UOJTime::$time_now_str;
		$contest_ends_in = UOJLocale::get('contests::contest ends in');
		echo <<<EOD
 		<div class="card border-info">
 			<div class="card-header bg-info">
 				<h3 class="card-title">$contest_ends_in</h3>
 			</div>
 			<div class="card-body text-center countdown" data-rest="$rest_second"></div>
 		</div>
		<script type="text/javascript">
			checkContestNotice({$contest['id']}, '$time_str');
		</script>
EOD;
	}
	
	function echoContestJudgeProgress() {
		global $contest;
		if ($contest['cur_progress'] < CONTEST_TESTING) {
			$rop = 0;
			$title = UOJLocale::get('contests::contest pending final test');
		} else {
			$total = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']}");
			$n_judged = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']} and status = 'Judged'");
			$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
			$title = UOJLocale::get('contests::contest final testing');
		}
		echo <<<EOD
 		<div class="card border-info">
 			<div class="card-header bg-info">
 				<h3 class="card-title">$title</h3>
 			</div>
 			<div class="card-body">
				<div class="progress bot-buffer-no">
					<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$rop" aria-valuemin="0" aria-valuemax="100" style="width: {$rop}%; min-width: 20px;">{$rop}%</div>
				</div>
			</div>
 		</div>
EOD;
	}
	
	function echoContestFinished() {
		$title = UOJLocale::get('contests::contest ended');
		echo <<<EOD
 		<div class="card border-info">
 			<div class="card-header bg-info">
 				<h3 class="card-title">$title</h3>
 			</div>
 		</div>
EOD;
	}
	
	$page_header = HTML::stripTags($contest['name']) . ' - ';
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . $tabs_info[$cur_tab]['name'] . ' - ' . UOJLocale::get('contests::contest')) ?>
<div class="text-center">
	<h1><?= $contest['name'] ?></h1>
	<?= getClickZanBlock('C', $contest['id'], $contest['zan']) ?>
</div>
<div class="row">
	<?php if ($cur_tab == 'standings'): ?>
	<div class="col-sm-12">
	<?php else: ?>
	<div class="col-sm-9">
	<?php endif ?>
		<?= HTML::tablist($tabs_info, $cur_tab) ?>
		<div class="top-buffer-md">
		<?php
			if ($cur_tab == 'dashboard') {
				echoDashboard();
			} elseif ($cur_tab == 'submissions') {
				echoMySubmissions();
			} elseif ($cur_tab == 'standings') {
				echoStandings();
			} elseif ($cur_tab == 'backstage') {
				echoBackstage();
			}
		?>
		</div>
	</div>
	
	<?php if ($cur_tab == 'standings'): ?>
	<div class="col-sm-12">
		<hr />
	</div>
	<?php endif ?>

	<div class="col-sm-3">
		<?php
			if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
				echoContestCountdown();
			} elseif ($contest['cur_progress'] <= CONTEST_TESTING) {
				echoContestJudgeProgress();
			} else {
				echoContestFinished();
			}
		?>
		<?php if ($cur_tab == 'standings'): ?>
	</div>
	<div class="col-sm-3">
	<?php endif ?>
	<?php if (!isset($contest['extra_config']['contest_type']) || $contest['extra_config']['contest_type']=='OI'):?>
	<p>Ten konkurs używa systemu zawodów OI.</p>
	<p><strong>Uwaga: Podczas trwania konkursu wyświetlane są jedynie wyniki testów próbnych!</strong></p>
	<?php elseif ($contest['extra_config']['contest_type']=='IOI'):?>
	<p>Ten konkurs używa systemu zawodów IOI.</p>
	<p><strong>Uwaga: Podczas trwania konkursu wyświetlany jest ostateczny wynik zgłoszenia, ale nie można zobaczyć szczegółowego raportu!</strong></p>
	<?php endif?>
	
		<a href="/contest/<?=$contest['id']?>/registrants" class="btn btn-info btn-block"><?= UOJLocale::get('contests::contest registrants') ?></a>
		<?php if (isSuperUser($myUser)): ?>
		<a href="/contest/<?=$contest['id']?>/manage" class="btn btn-primary btn-block">Zarządzaj</a>
		<?php if (isset($start_test_form)): ?>
		<div class="top-buffer-sm">
			<?php $start_test_form->printHTML(); ?>
		</div>
		<?php endif ?>
		<?php if (isset($publish_result_form)): ?>
		<div class="top-buffer-sm">
			<?php $publish_result_form->printHTML(); ?>
		</div>
		<?php endif ?>
		<?php endif ?>
	
		<?php if ($contest['extra_config']['links']) { ?>
			<?php if ($cur_tab == 'standings'): ?>
	</div>
	<div class="col-sm-3">
		<div class="card border-info">
		<?php else: ?>
		<div class="card border-info top-buffer-lg">
		<?php endif ?>
			<div class="card-header bg-info">
				<h3 class="card-title">Informacje o konkursie</h3>
			</div>
			<div class="list-group">
			<?php foreach ($contest['extra_config']['links'] as $link) { ?>
				<a href="/blogs/<?=$link[1]?>" class="list-group-item"><?=$link[0]?></a>
			<?php } ?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
