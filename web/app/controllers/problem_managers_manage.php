<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "Nie istnieje użytkownik o nazwie {$username}";
			}
			return '';
		},
		function($type, $username) {
			global $problem;
			if ($type == '+') {
				DB::query("insert into problems_permissions (problem_id, username) values (${problem['id']}, '$username')");
			} elseif ($type == '-') {
				DB::query("delete from problems_permissions where problem_id = ${problem['id']} and username = '$username'");
			}
		}
	);
	
	$managers_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - Menedżer - Zarządzaj zadaniami') ?>
<h1 class="page-header" align="center">Zarządzaj #<?=$problem['id']?> : <?=$problem['title']?></h1>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">Edytuj</a></li>
	<li class="nav-item"><a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">Menedżer</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">Dane</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?=$problem['id']?>" role="tab">Powrót</a></li>
</ul>

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
	$result = DB::query("select username from problems_permissions where problem_id = ${problem['id']}");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
	</tbody>
</table>
<p class="text-center">Format polecenia: jedno polecenie na linię, +mike oznacza dodanie mike, -mike usunięcie</p>
<?php $managers_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
