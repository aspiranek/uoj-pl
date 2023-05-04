<?php
	requirePHPLib('form');
	
	$forgot_form = new UOJForm('forgot');
	$forgot_form->addInput('username', 'text', 'Nazwa użytkownika', '',
		function($username, &$vdata) {
			if (!validateUsername($username)) {
				return 'Nazwa użytkownika nie jest poprawna!';
			}
			$vdata['user'] = queryUser($username);
			if (!$vdata['user']) {
				return 'Użytkownik nie istnieje!';
			}
			return '';
		},
		null
	);
	$forgot_form->handle = function(&$vdata) {
		$user = $vdata['user'];
		$password = $user["password"];
		
		$oj_name = UOJConfig::$data['profile']['oj-name'];
		$oj_name_short = UOJConfig::$data['profile']['oj-name-short'];
		$sufs = base64url_encode($user['username'] . "." . md5($user['username'] . "+" . $password));
		$url = HTML::url("/reset-password", array('params' => array('p' => $sufs)));
		$html = <<<EOD
<base target="_blank" />

<p>Witaj {$user['username']},</p>
<p>Właśnie planujesz odzyskać swoje hasło. Wejdź na poniższy link by zamienić plany w czyn:</p>
<p><a href="$url">$url</a></p>
<p>{$oj_name}</p>

<style type="text/css">
body{font-size:14px;font-family:arial,verdana,sans-serif;line-height:1.666;padding:0;margin:0;overflow:auto;white-space:normal;word-wrap:break-word;min-height:100px}
pre {white-space:pre-wrap;white-space:-moz-pre-wrap;white-space:-pre-wrap;white-space:-o-pre-wrap;word-wrap:break-word}
</style>
EOD;
		
		$mailer = UOJMail::noreply();
		$mailer->addAddress($user['email'], $user['username']);
		$mailer->Subject = $oj_name_short."Odzyskaj hasło";
		$mailer->msgHTML($html);
		if (!$mailer->send()) {
			error_log($mailer->ErrorInfo);
			becomeMsgPage('<div class="text-center"><h2>Nie udało się wysłać wiadomości email.<span class="glyphicon glyphicon-remove"></span></h2></div>');
		} else {
			becomeMsgPage('<div class="text-center"><h2>Pomyślnie wysłano wiadomość email.<span class="glyphicon glyphicon-ok"></span></h2></div>');
		}
	};
	$forgot_form->submit_button_config['align'] = 'offset';
	
	$forgot_form->runAtServer();
?>
<?php echoUOJPageHeader('Odzyskiwanie hasła') ?>
<h2 class="page-header">Odzyskaj hasło</h2>
<h4>Wprowadź swoją nazwę użytkownika:</h4>
<?php $forgot_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
