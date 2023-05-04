<?php
	if (!isset($_GET['p'])) {
		become404Page();
	}
	function resetPassword() {
		list($username, $check_code) = explode('.', base64url_decode($_GET['p']));
		if (!isset($_POST['newPW']) || !validatePassword($_POST['newPW'])) {
			return 'Operacja nie powiodła się, nieprawidłowe hasło';
		}
		if (!isset($username) || !validateUsername($username)) {
			return 'Nieznany błąd...';
		}
		if (!isset($check_code)) {
			return 'Nieznany błąd...';
		}
		
		$newPW = $_POST['newPW'];
		$user = queryUser($username);
		if ($user == null) {
			return 'Nieznany błąd...';
		}
		if ($check_code !== md5($user['username'] . '+' . $user['password'])) {
			return 'Nieznany błąd...';
		}
		$newPW = getPasswordToStore($newPW, $user['username']);
		DB::update("update user_info set password = '$newPW' where username = '{$user['username']}'");
		return 'ok';
	}
	if (isset($_POST['reset'])) {
		die(resetPassword());
	}
?>
<?php
	$REQUIRE_LIB['dialog'] = '';
	$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader('Zmień hasło') ?>
<h2 class="page-header">Zmień hasło</h2>
<form id="form-reset" class="form-horizontal">
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label">Nowe hasło</label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="Wpisz nowe hasło" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="Wpisz nowe hasło ponownie" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
  </div>
  <div class="form-group">
	<div class="col-sm-offset-2 col-sm-3">
	  <button type="submit" id="button-submit" class="btn btn-secondary">Zatwierdź</button>
	</div>
  </div>
</form>

<script type="text/javascript">
function validateResetPwPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	return ok;
}
$(document).ready(function() {
	$('#form-reset').submit(function(e) {
		if (!validateResetPwPost()) {
			return false;
		}
		$.post(<?= json_encode($_SERVER['REQUEST_URI']) ?>, {
			reset : '',
			newPW : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
		}, function(res) {
			if (res == 'ok') {
				BootstrapDialog.show({
					title   : 'Sukces!',
					message : 'Hasło zostało pomyślnie zmienione!',
					type    : BootstrapDialog.TYPE_SUCCESS,
					buttons: [{
						label: 'OK',
						action: function(dialog) {
							dialog.close();
						}
					}],
					onhidden : function(dialog) {
						window.location.href = '/login';
					}
				});
			} else {
				BootstrapDialog.show({
					title   : 'Błąd',
					message : res,
					type	: BootstrapDialog.TYPE_DANGER,
					buttons: [{
							label: 'OK',
							action: function(dialog) {
								dialog.close();
							}
					}]
				});
			}
		});
		return false;
	});
});
</script>
<?php echoUOJPageFooter() ?>
