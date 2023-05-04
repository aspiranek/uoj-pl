<?php
	function handleRegisterPost() {
		if (!crsf_check()) {
			return 'Strona wygasła';
		}
		if (!isset($_POST['username'])) {
			return "Niepoprawnie wypełniony formularz";
		}
		if (!isset($_POST['password'])) {
			return "Niepoprawnie wypełniony formularz";
		}
		if (!isset($_POST['email'])) {
			return "Niepoprawnie wypełniony formularz";
		}

		$username = $_POST['username'];
		$password = $_POST['password'];
		$email = $_POST['email'];
		if (!validateUsername($username)) {
			return "Błąd: Nieprawidłowa nazwa użytkownika.";
		}
		if (queryUser($username)) {
			return "Błąd: Ten użytkownik już istnieje.";
		}
		if (!validatePassword($password)) {
			return "Błąd: Nieprawidłowe hasło.";
		}
		if (!validateEmail($email)) {
			return "Błąd: Nieprawidłowy email.";
		}
		
		$password = getPasswordToStore($password, $username);
		
		$esc_email = DB::escape($email);
		
		$svn_pw = uojRandString(10);
		if (!DB::selectCount("SELECT COUNT(*) FROM user_info")) {
			DB::query("insert into user_info (username, email, password, svn_password, register_time, usergroup) values ('$username', '$esc_email', '$password', '$svn_pw', now(), 'S')");
		} else {
			DB::query("insert into user_info (username, email, password, svn_password, register_time) values ('$username', '$esc_email', '$password', '$svn_pw', now())");
		}
		
		return "Witaj! " . $username . ", zarejestrowałeś się pomyślnie!";
	}
	
	if (isset($_POST['register'])) {
		echo handleRegisterPost();
		die();
	} elseif (isset($_POST['check_username'])) {
		$username = $_POST['username'];
		if (validateUsername($username) && !queryUser($username)) {
			echo '{"ok" : true}';
		} else {
			echo '{"ok" : false}';
		}
		die();
	}
?>
<?php
	$REQUIRE_LIB['md5'] = '';
	$REQUIRE_LIB['dialog'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('register')) ?>
<h2 class="page-header"><?= UOJLocale::get('register') ?></h2>
<form id="form-register" class="form-horizontal">
	<div id="div-email" class="form-group">
		<label for="input-email" class="col-sm-2 control-label"><?= UOJLocale::get('email') ?></label>
		<div class="col-sm-3">
			<input type="email" class="form-control" id="input-email" name="email" placeholder="<?= UOJLocale::get('enter your email') ?>" maxlength="50" />
			<span class="help-block" id="help-email"></span>
		</div>
	</div>
	<div id="div-username" class="form-group">
		<label for="input-username" class="col-sm-2 control-label"><?= UOJLocale::get('username') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" id="input-username" name="username" placeholder="<?= UOJLocale::get('enter your username') ?>" maxlength="20" />
			<span class="help-block" id="help-username"></span>
		</div>
	</div>
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label"><?= UOJLocale::get('password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-secondary"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
function checkUsernameNotInUse() {
	var ok = false;
	$.ajax({
		url : '/register',
		type : 'POST',
		dataType : 'json',
		async : false,
		
		data : {
			check_username : '',
			username : $('#input-username').val()
		},
		success : function(data) {
			ok = data.ok;
		},
		error :	function(XMLHttpRequest, textStatus, errorThrown) {
			alert(XMLHttpRequest.responseText);
			ok = false;
		}
	});
	return ok;
}
function validateRegisterPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('email', validateEmail);
	ok &= getFormErrorAndShowHelp('username', function(str) {
		var err = validateUsername(str);
		if (err)
			return err;
		if (!checkUsernameNotInUse())
			return 'Ta nazwa użytkownika jest zajęta.';
		return '';
	})
	ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	return ok;
}

function submitRegisterPost() {
	if (!validateRegisterPost()) {
		return;
	}
	
	$.post('/register', {
		_token : "<?= crsf_token() ?>",
		register : '',
		username : $('#input-username').val(),
		email		: $('#input-email').val(),
		password : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
	}, function(msg) {
		if (/^Witaj!/.test(msg)) {
			BootstrapDialog.show({
				title	 : 'Rejestracja zakończona',
				message : msg,
				type		: BootstrapDialog.TYPE_SUCCESS,
				buttons: [{
					label: 'OK',
					action: function(dialog) {
						dialog.close();
					}
				}],
				onhidden : function(dialog) {
					var prevUrl = document.referrer;
					if (!prevUrl) {
						prevUrl = '/';
					};
					window.location.href = prevUrl;
				}
			});
		} else {
			BootstrapDialog.show({
				title	 : 'Rejestracja nie powiodła się',
				message : msg,
				type		: BootstrapDialog.TYPE_DANGER,
				buttons: [{
					label: 'OK',
					action: function(dialog) {
						dialog.close();
					}
				}],
			});
		}
	});
}
$(document).ready(function() {
	$('#form-register').submit(function(e) {
		submitRegisterPost();
		return false;
	});
});
</script>
<?php echoUOJPageFooter() ?>
