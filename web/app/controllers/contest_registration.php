<?php
	requirePHPLib('form');
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if ($myUser == null) {
		redirectToLogin();
	} elseif (hasContestPermission($myUser, $contest) || hasRegistered($myUser, $contest) || $contest['cur_progress'] != CONTEST_NOT_STARTED) {
		redirectTo('/contests');
	}
	
	$register_form = new UOJForm('register');
	$register_form->handle = function() {
		global $myUser, $contest;
		DB::query("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$myUser['username']}', {$myUser['rating']}, {$contest['id']}, 0)");
		updateContestPlayerNum($contest);
	};
	$register_form->submit_button_config['class_str'] = 'btn btn-primary';
	$register_form->submit_button_config['text'] = 'Zarejestruj się';
	$register_form->succ_href = "/contests";
	
	$register_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - Zarejetsruj się') ?>
<h1 class="page-header">Zasady konkursu</h1>
<ul>
	<li>Możesz wysyłać wiele zgłoszeń. Wynik do zadania to <strong>ostatnie zgłoszenie nie zakończone błędem kompilacji</strong>.</li>
	<li>Po zawodach zgłoszenia zostaną sprawdzone ostatecznie. Rankingiem końcowym jest ranking po tym sprawdzeniu.</li>
	<li>Ranking tworzony jest na podstawie punktacji. Jeżeli punktacja jest taka sama, brany pod uwagę jest czas ukończenia konkursu (suma czasów zrobienia każdego zadania (pomijając zadania z ostatecznym wynikiem 0)).</li>
	<li>Nie można brać udziału w jednym konkursie używając wielu kont. Zabrania się komunikacji pomiędzy uczestnikami i kopiowania pomysłów/kodów innych uczestników.</li>
</ul>
<?php $register_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
