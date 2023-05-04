<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$oj_name = UOJConfig::$data['profile']['oj-name'];
	$problem_extra_config = getProblemExtraConfig($problem);

	$data_dir = "/var/uoj_data/${problem['id']}";

	function echoFileNotFound($file_name) {
		echo '<h4>', htmlspecialchars($file_name), '<sub class="text-danger"> ', 'Nie znaleziono pliku', '</sub></h4>';
	}
	function echoFilePre($file_name) {
		global $data_dir;
		$file_full_name = $data_dir . '/' . $file_name;

		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $file_full_name);
		if ($mimetype === false) {
			echoFileNotFound($file_name);
			return;
		}
		finfo_close($finfo);

		echo '<h4>', htmlspecialchars($file_name), '<sub> ', $mimetype, '</sub></h4>';
		echo "<pre>\n";

		$output_limit = 1000;
		if (strStartWith($mimetype, 'text/')) {
			echo htmlspecialchars(uojFilePreview($file_full_name, $output_limit));
		} else {
			echo htmlspecialchars(uojFilePreview($file_full_name, $output_limit, 'binary'));
		}
		echo "\n</pre>";
	}


	//上传数据
	if ($_POST['problem_data_file_submit']=='submit') {
		if ($_FILES["problem_data_file"]["error"] > 0) {
			$errmsg = "Error: ".$_FILES["problem_data_file"]["error"];
			becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
		} else {
			$zip_mime_types = array('application/zip', 'application/x-zip', 'application/x-zip-compressed');
			if (in_array($_FILES["problem_data_file"]["type"], $zip_mime_types)) {
				$up_filename="/tmp/".rand(0,100000000)."data.zip";
				move_uploaded_file($_FILES["problem_data_file"]["tmp_name"], $up_filename);
				$zip = new ZipArchive;
				if ($zip->open($up_filename) === TRUE) {
					$zip->extractTo("/var/uoj_data/upload/{$problem['id']}");
					$zip->close();
					exec("cd /var/uoj_data/upload/{$problem['id']}; if [ `find . -maxdepth 1 -type f`File = File ]; then for sub_dir in `find -maxdepth 1 -type d ! -name .`; do mv -f \$sub_dir/* . && rm -rf \$sub_dir; done; fi");
					echo "<script>alert('Przesłano pomyślnie!')</script>";
				} else {
					$errmsg = "Dekompresja nie powiodła się.";
					becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
				}
				unlink($up_filename);
			} else {
				$errmsg = "Proszę przesłać w formacie ZIP.";
				becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
			}
		}
	}

	//添加配置文件
	if ($_POST['problem_settings_file_submit']=='submit') {
		if ($_POST['use_builtin_checker'] and $_POST['n_tests'] and $_POST['input_pre'] and $_POST['input_suf'] and $_POST['output_pre'] and $_POST['output_suf'] and $_POST['time_limit'] and $_POST['memory_limit']) {
			$set_filename="/var/uoj_data/upload/{$problem['id']}/problem.conf";
			$has_legacy=false;
			if (file_exists($set_filename)) {
				$has_legacy=true;
				unlink($set_filename);
			}
			$setfile = fopen($set_filename, "w");
			fwrite($setfile, "use_builtin_judger on\n");
			if ($_POST['use_builtin_checker'] != 'ownchk') {
				fwrite($setfile, "use_builtin_checker ".$_POST['use_builtin_checker']."\n");
			}
			fwrite($setfile, "n_tests ".$_POST['n_tests']."\n");
			if ($_POST['n_ex_tests']) {
				fwrite($setfile, "n_ex_tests ".$_POST['n_ex_tests']."\n");
			} else {
				fwrite($setfile, "n_ex_tests 0\n");
			}
			if ($_POST['n_sample_tests']) {
				fwrite($setfile, "n_sample_tests ".$_POST['n_sample_tests']."\n");
			} else {
				fwrite($setfile, "n_sample_tests 0\n");
			}
			fwrite($setfile, "input_pre ".$_POST['input_pre']."\n");
			fwrite($setfile, "input_suf ".$_POST['input_suf']."\n");
			fwrite($setfile, "output_pre ".$_POST['output_pre']."\n");
			fwrite($setfile, "output_suf ".$_POST['output_suf']."\n");
			fwrite($setfile, "time_limit ".$_POST['time_limit']."\n");
			fwrite($setfile, "memory_limit ".$_POST['memory_limit']."\n");
			fclose($setfile);
			if (!$has_legacy) {
				echo "<script>alert('Dodano pomyślnie!')</script>";
			} else {
				echo "<script>alert('Zastąpiono pomyślnie!')</script>";
			}
		} else {
			$errmsg = "Nie udało się dodać pliku konfiguracyjnego.";
			becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
		}
	}


	$info_form = new UOJForm('info');
	$http_host = HTML::escape(UOJContext::httpHost());
	$download_url = HTML::url("/download.php?type=problem&id={$problem['id']}");
	$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<!--<label class="col-sm-3 control-label">Przesyłanie danych ZIP</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<row>
			<button type="button" style="width:30%" class="btn btn-primary" data-toggle="modal" data-target="#UploadDataModal">Prześlij dane</button>
			<button type="submit" style="width:30%" id="button-submit-data" name="submit-data" value="data" class="btn btn-danger">Sprawdź konfigurację i zsynchronizuj dane</button>
			</row>
		</div>
	</div>-->
</div>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">problem_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="$download_url">$download_url</a>
		</div>
	</div>
</div>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">testlib.h</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="/download.php?type=testlib.h">Pobierz</a>
		</div>
	</div>
</div>
EOD
	);

	$esc_submission_requirement = HTML::escape(json_encode(json_decode($problem['submission_requirement']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">Prześlij plik konfiguracyjny</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_submission_requirement
</pre>
		</div>
	</div>
</div>
EOD
	);
	$esc_extra_config = HTML::escape(json_encode(json_decode($problem['extra_config']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">Dodatkowa konfiguracja</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_extra_config
</pre>
		</div>
	</div>
</div>
EOD
	);
	if (isSuperUser($myUser)) {
		$info_form->addVInput('submission_requirement', 'text', 'Prześlij plik konfiguracyjny', $problem['submission_requirement'],
			function ($submission_requirement, &$vdata) {
				$submission_requirement = json_decode($submission_requirement, true);
				if ($submission_requirement === null) {
					return 'Zły format JSON';
				}
				$vdata['submission_requirement'] = json_encode($submission_requirement);
			},
			null);
		$info_form->addVInput('extra_config', 'text', 'Dodatkowa konfiguracja', $problem['extra_config'],
			function ($extra_config, &$vdata) {
				$extra_config = json_decode($extra_config, true);
				if ($extra_config === null) {
					return 'Zły format JSON';
				}
				$vdata['extra_config'] = json_encode($extra_config);
			},
			null);
		$info_form->handle = function(&$vdata) {
			global $problem;
			$esc_submission_requirement = DB::escape($vdata['submission_requirement']);
			$esc_extra_config = DB::escape($vdata['extra_config']);
			DB::update("update problems set submission_requirement = '$esc_submission_requirement', extra_config = '$esc_extra_config' where id = {$problem['id']}");
		};
	} else {
		$info_form->no_submit = true;
	}

	class DataDisplayer {
		public $problem_conf = array();
		public $data_files = array();
		public $displayers = array();

		public function __construct($problem_conf = null, $data_files = null) {
			global $data_dir;

			if (isset($problem_conf)) {
				foreach ($problem_conf as $key => $val) {
					$this->problem_conf[$key] = array('val' => $val);
				}
			}

			if (!isset($data_files)) {
				$this->data_files = array_filter(scandir($data_dir), function($x) {
					return $x !== '.' && $x !== '..' && $x !== 'problem.conf';
				});
				natsort($this->data_files);
				array_unshift($this->data_files, 'problem.conf');
			} else {
				$this->data_files = $data_files;
			}

			$this->setDisplayer('problem.conf', function($self) {
				global $info_form;
				$info_form->printHTML();
				echo '<div class="top-buffer-md"></div>';

				echo '<table class="table table-bordered table-hover table-striped table-text-center">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>key</th>';
				echo '<th>value</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ($self->problem_conf as $key => $info) {
					if (!isset($info['status'])) {
						echo '<tr>';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), '</td>';
						echo '</tr>';
					} elseif ($info['status'] == 'danger') {
						echo '<tr class="text-danger">';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), ' <span class="glyphicon glyphicon-remove"></span>', '</td>';
						echo '</tr>';
					}
				}
				echo '</tbody>';
				echo '</table>';

				echoFilePre('problem.conf');
			});
		}

		public function setProblemConfRowStatus($key, $status) {
			$this->problem_conf[$key]['status'] = $status;
			return $this;
		}

		public function setDisplayer($file_name, $fun) {
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function addDisplayer($file_name, $fun) {
			$this->data_files[] = $file_name;
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function echoDataFilesList($active_file) {
			foreach ($this->data_files as $file_name) {
				echo '<li class="nav-item">';
				if ($file_name != $active_file) {
					echo '<a class="nav-link" href="#">';
				} else {
					echo '<a class="nav-link active" href="#">';
				}
				echo htmlspecialchars($file_name), '</a>', '</li>';
			}
		}
		public function displayFile($file_name) {
			global $data_dir;

			if (isset($this->displayers[$file_name])) {
				$fun = $this->displayers[$file_name];
				$fun($this);
			} elseif (in_array($file_name, $this->data_files)) {
				echoFilePre($file_name);
			} else {
				echoFileNotFound($file_name);
			}
		}
	}

	function getDataDisplayer() {
		global $data_dir;
		global $problem;

		$allow_files = array_flip(array_filter(scandir($data_dir), function($x) {
			return $x !== '.' && $x !== '..';
		}));

		$getDisplaySrcFunc = function($name) use ($allow_files) {
			return function() use ($name, $allow_files) {
				$src_name = $name . '.cpp';
				if (isset($allow_files[$src_name])) {
					echoFilePre($src_name);
				} else {
					echoFileNotFound($src_name);
				}
				if (isset($allow_files[$name])) {
					echoFilePre($name);
				} else {
					echoFileNotFound($name);
				}
			};
		};

		$problem_conf = getUOJConf("$data_dir/problem.conf");
		if ($problem_conf === -1) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echoFileNotFound('problem.conf');
			});
		}
		if ($problem_conf === -2) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echo '<h4 class="text-danger">problem.conf jest źle sformatowany</h4>';
				echoFilePre('problem.conf');
			});
		}

		$judger_name = getUOJConfVal($problem_conf, 'use_builtin_judger', null);
		if (!isset($problem_conf['use_builtin_judger'])) {
			return new DataDisplayer($problem_conf);
		}
		if ($problem_conf['use_builtin_judger'] == 'on') {
			$n_tests = getUOJConfVal($problem_conf, 'n_tests', 10);
			if (!validateUInt($n_tests)) {
				return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_tests', 'danger');
			}

			$has_extra_tests = !(isset($problem_conf['submit_answer']) && $problem_conf['submit_answer'] == 'on');

			$data_disp = new DataDisplayer($problem_conf, array('problem.conf'));
			$data_disp->addDisplayer('tests',
				function($self) use ($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
					for ($num = 1; $num <= $n_tests; $num++) {
						$input_file_name = getUOJProblemInputFileName($problem_conf, $num);
						$output_file_name = getUOJProblemOutputFileName($problem_conf, $num);
						echo '<div class="row">';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$input_file_name])) {
							echoFilePre($input_file_name);
						} else {
							echoFileNotFound($input_file_name);
						}
						echo '</div>';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$output_file_name])) {
							echoFilePre($output_file_name);
						} else {
							echoFileNotFound($output_file_name);
						}
						echo '</div>';
						echo '</div>';
					}
				}
			);
			if ($has_extra_tests) {
				$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', 0);
				if (!validateUInt($n_ex_tests)) {
					return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_ex_tests', 'danger');
				}

				$data_disp->addDisplayer('extra tests',
					function($self) use ($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
						for ($num = 1; $num <= $n_ex_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($problem_conf, $num);
							echo '<div class="row">';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$input_file_name])) {
								echoFilePre($input_file_name);
							} else {
								echoFileNotFound($input_file_name);
							}
							echo '</div>';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$output_file_name])) {
								echoFilePre($output_file_name);
							} else {
								echoFileNotFound($output_file_name);
							}
							echo '</div>';
							echo '</div>';
						}
					}
				);
			}
			
			if (!isset($problem_conf['interaction_mode'])) {
				if (isset($problem_conf['use_builtin_checker'])) {
					$data_disp->addDisplayer('checker', function($self) {
						echo '<h4>use builtin checker : ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
					});
				} else {
					$data_disp->addDisplayer('checker', $getDisplaySrcFunc('chk'));
				}
			}
			if ($problem['hackable']) {
				$data_disp->addDisplayer('standard', $getDisplaySrcFunc('std'));
				$data_disp->addDisplayer('validator', $getDisplaySrcFunc('val'));
			}
			if (isset($problem_conf['interaction_mode'])) {
				$data_disp->addDisplayer('interactor', $getDisplaySrcFunc('interactor'));
			}
			return $data_disp;
		} else {
			return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('use_builtin_judger', 'danger');
		}
	}

	$data_disp = getDataDisplayer();

	if (isset($_GET['display_file'])) {
		if (!isset($_GET['file_name'])) {
			echoFileNotFound('');
		} else {
			$data_disp->displayFile($_GET['file_name']);
		}
		die();
	}

	$hackable_form = new UOJForm('hackable');
	$hackable_form->handle = function() {
		global $problem;
		$problem['hackable'] = !$problem['hackable'];
		//$problem['hackable'] = 0;
		$ret = dataSyncProblemData($problem);
		if ($ret) {
			becomeMsgPage('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
		}
		
		$hackable = $problem['hackable'] ? 1 : 0;
		DB::query("update problems set hackable = $hackable where id = ${problem['id']}");
	};
	$hackable_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
	$hackable_form->submit_button_config['text'] = $problem['hackable'] ? 'Hakowanie włączone' : 'Hakowanie wyłączone';
	$hackable_form->submit_button_config['smart_confirm'] = '';

	$data_form = new UOJForm('data');
	$data_form->handle = function() {
		global $problem, $myUser;
		set_time_limit(60 * 5);
		$ret = dataSyncProblemData($problem, $myUser);
		if ($ret) {
			becomeMsgPage('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">Powrót</a>');
		}
	};
	$data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$data_form->submit_button_config['text'] = 'Sprawdź konfigurację i zsynchronizuj dane';
	$data_form->submit_button_config['smart_confirm'] = '';
	
	$clear_data_form = new UOJForm('clear_data');
	$clear_data_form->handle = function() {
		global $problem;
		dataClearProblemData($problem);
	};
	$clear_data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$clear_data_form->submit_button_config['text'] = 'Wyczyść dane zadania';
	$clear_data_form->submit_button_config['smart_confirm'] = '';

	$rejudge_form = new UOJForm('rejudge');
	$rejudge_form->handle = function() {
		global $problem;
		rejudgeProblem($problem);
	};
	$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$rejudge_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$rejudge_form->submit_button_config['text'] = 'Przetestuj ponownie';
	$rejudge_form->submit_button_config['smart_confirm'] = '';
	
	$rejudgege97_form = new UOJForm('rejudgege97');
	$rejudgege97_form->handle = function() {
		global $problem;
		rejudgeProblemGe97($problem);
	};
	$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$rejudgege97_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$rejudgege97_form->submit_button_config['text'] = 'Przetestuj ponownie programy z wynikiem >=97';
	$rejudgege97_form->submit_button_config['smart_confirm'] = '';
	
	$view_type_form = new UOJForm('view_type');
	$view_type_form->addVSelect('view_content_type',
		array('NONE' => 'nikt',
				'SELF' => 'tylko ja',
				'ALL_AFTER_AC' => 'wszyscy po AC',
				'ALL' => 'wszyscy'
		),
		'Zobacz zgłoszenia:',
		$problem_extra_config['view_content_type']
	);
	$view_type_form->addVSelect('view_all_details_type',
		array('NONE' => 'nikt',
				'SELF' => 'tylko ja',
				'ALL_AFTER_AC' => 'wszyscy po AC',
				'ALL' => 'wszyscy'
		),
		'Zobacz szczegółowy opis:',
		$problem_extra_config['view_all_details_type']
	);
	$view_type_form->addVSelect('view_details_type',
		array('NONE' => 'nikt',
				'SELF' => 'ja',
				'ALL_AFTER_AC' => 'wszyscy po AC',
				'ALL' => 'wszyscy'
		),
		'Zobacz szczegóły testów',
		$problem_extra_config['view_details_type']
	);
	$view_type_form->handle = function() {
		global $problem, $problem_extra_config;
		$config = $problem_extra_config;
		$config['view_content_type'] = $_POST['view_content_type'];
		$config['view_all_details_type'] = $_POST['view_all_details_type'];
		$config['view_details_type'] = $_POST['view_details_type'];
		$esc_config = DB::escape(json_encode($config));
		DB::query("update problems set extra_config = '$esc_config' where id = '{$problem['id']}'");
	};
	$view_type_form->submit_button_config['class_str'] = 'btn btn-warning btn-block top-buffer-sm';
	
	if ($problem['hackable']) {
		$test_std_form = new UOJForm('test_std');
		$test_std_form->handle = function() {
			global $myUser, $problem;
			
			$user_std = queryUser('std');
			if (!$user_std) {
				becomeMsgPage('Utwórz konto "std".');
			}
			
			$requirement = json_decode($problem['submission_requirement'], true);
			
			$zip_file_name = uojRandAvaiableSubmissionFileName();
			$zip_file = new ZipArchive();
			if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
				becomeMsgPage('Przesyłanie nie powiodło się.');
			}
		
			$content = array();
			$content['file_name'] = $zip_file_name;
			$content['config'] = array();
			foreach ($requirement as $req) {
				if ($req['type'] == "source code") {
					$content['config'][] = array("{$req['name']}_language", "C++");
				}
			}
		
			$tot_size = 0;
			foreach ($requirement as $req) {
				$zip_file->addFile("/var/uoj_data/{$problem['id']}/std.cpp", $req['file_name']);
				$tot_size += $zip_file->statName($req['file_name'])['size'];
			}
		
			$zip_file->close();
		
			$content['config'][] = array('validate_input_before_test', 'on');
			$content['config'][] = array('problem_id', $problem['id']);
			$esc_content = DB::escape(json_encode($content));
			$esc_language = DB::escape('C++');
		 	
			$result = array();
			$result['status'] = "Waiting";
			$result_json = json_encode($result);
			$is_hidden = $problem['is_hidden'] ? 1 : 0;
			
			DB::insert("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values ({$problem['id']}, now(), '{$user_std['username']}', '$esc_content', '$esc_language', $tot_size, '{$result['status']}', '$result_json', $is_hidden)");
		};
		$test_std_form->succ_href = "/submissions?problem_id={$problem['id']}";
		$test_std_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$test_std_form->submit_button_config['text'] = 'Sprawdź poprawność danych.';
		$test_std_form->runAtServer();
	}
	
	$hackable_form->runAtServer();
	$view_type_form->runAtServer();
	$data_form->runAtServer();
	$clear_data_form->runAtServer();
	$rejudge_form->runAtServer();
	$rejudgege97_form->runAtServer();
	$info_form->runAtServer();
?>
<?php
	$REQUIRE_LIB['dialog'] = '';
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - Dane - Zarządzaj zadaniami') ?>
<h1 class="page-header" align="center">Zarządzaj #<?=$problem['id']?> : <?=$problem['title']?></h1>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">Edytuj</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">Menedżer</a></li>
	<li class="nav-item"><a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">Dane</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?=$problem['id']?>" role="tab">Powrót</a></li>
</ul>

<div class="row">
	<div class="col-md-10 top-buffer-sm">
		<div class="row">
			<div class="col-md-3 top-buffer-sm" id="div-file_list">
				<ul class="nav nav-pills flex-column">
					<?php $data_disp->echoDataFilesList('problem.conf'); ?>
				</ul>
			</div>
			<div class="col-md-9 top-buffer-sm" id="div-file_content">
				<?php $data_disp->displayFile('problem.conf'); ?>
			</div>
			<script type="text/javascript">
				curFileName = '';
				$('#div-file_list a').click(function(e) {
					$('#div-file_content').html('<h3>Loading...</h3>');
					$(this).tab('show');

					var fileName = $(this).text();
					curFileName = fileName;
					$.get('/problem/<?= $problem['id'] ?>/manage/data', {
							display_file: '',
							file_name: fileName
						},
						function(data) {
							if (curFileName != fileName) {
								return;
							}
							$('#div-file_content').html(data);
						},
						'html'
					);
					return false;
				});
			</script>
		</div>
	</div>
	<div class="col-md-2 top-buffer-sm">
		<div class="top-buffer-md">
			<?php if ($problem['hackable']): ?>
				<span class="glyphicon glyphicon-ok"></span> Hakowanie włączone
			<?php else: ?>
				<span class="glyphicon glyphicon-remove"></span> Hakowanie wyłączone
			<?php endif ?>
			<?php $hackable_form->printHTML() ?>
		</div>
		<div class="top-buffer-md">
		<?php if ($problem['hackable']): ?>
			<?php $test_std_form->printHTML() ?>
		<?php endif ?>
		</div>
		<div class="top-buffer-md">
			<button id="button-display_view_type" type="button" class="btn btn-primary btn-block" onclick="$('#div-view_type').toggle('fast');">Zmień uprawnienia</button>
			<div class="top-buffer-sm" id="div-view_type" style="display:none; padding-left:5px; padding-right:5px;">
				<?php $view_type_form->printHTML(); ?>
			</div>
		</div>
		<div class="top-buffer-md">
			<?php $data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $clear_data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudge_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudgege97_form->printHTML(); ?>
		</div>

		<div class="top-buffer-md">
			<button type="button" class="btn btn-block btn-primary" data-toggle="modal" data-target="#UploadDataModal">Prześlij dane</button>
		</div>
		<div class="top-buffer-md">
			<button type="button" class="btn btn-block btn-primary" data-toggle="modal" data-target="#ProblemSettingsFileModal">Konfiguracja zadania</button>
		</div>
	</div>

	<div class="modal fade" id="UploadDataModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  		<div class="modal-dialog">
    			<div class="modal-content">
      				<div class="modal-header">
						<h4 class="modal-title" id="myModalLabel">Prześlij dane</h4>
        				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      				</div>
      				<div class="modal-body">
        				<form action="" method="post" enctype="multipart/form-data" role="form">
							<div class="form-group">
									<label for="exampleInputFile">Prześlij plik ZIP</label>
									<input type="file" name="problem_data_file" id="problem_data_file">
									<p class="help-block">Uwaga: Wszystkie dane należy umieścić w katalogu głównym skompresowanej paczki. Jeśli w skompresowanym pakiecie znajdują się tylko foldery, ale nie ma plików, zawartość tych podfolderów pierwszego poziomu zostanie przeniesiona do katalogu głównego, a następnie te podfoldery pierwszego poziomu zostaną usunięte; jeśli w tych folderach znajdują się pliki o tej samej nazwie, następuje losowa zamiana i przechowywana jest tylko jedna kopia.</p>
							</div>
							<input type="hidden" name="problem_data_file_submit" value="submit">
      				</div>
      				<div class="modal-footer">
						<button type="submit" class="btn btn-success">Prześlij</button>
						</form>
        				<button type="button" class="btn btn-secondary" data-dismiss="modal">Zamknij</button>
      				</div>
    			</div>
  		</div>
	</div>

	<div class="modal fade" id="ProblemSettingsFileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  		<div class="modal-dialog">
    			<div class="modal-content">
      				<div class="modal-header">
						<h4 class="modal-title" id="myModalLabel">Konfiguracja zadania</h4>
        				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      				</div>
      				<div class="modal-body">
        				<form class="form-horizontal" action="" method="post" role="form">
        					<div class="form-group row">
    							<label for="use_builtin_checker" class="col-sm-5 control-label">Sprawdzaczka</label>
    							<div class="col-sm-7">
								<select class="form-control" id="use_builtin_checker" name="use_builtin_checker">
  									<option value="ncmp">Jednowierszowy ciąg liczb całkowitych</option>
  									<option value="wcmp">Jednowierszowy ciąg napisów</option>
  									<option value="fcmp">Dane wielowierszowe (nie ignoruj ​​spacji na końcu wiersza, ale ignoruj ​​znak powrotu karetki na końcu tekstu)</option>
									<option value="ownchk">Niestandardowy walidator</option>
								</select>
      								<!--<input type="hidden" class="form-control" id="use_builtin_checker" name="use_builtin_checker" placeholder="walidator">-->
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="n_tests" class="col-sm-5 control-label">n_tests</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="n_tests" name="n_tests" placeholder="liczba testów">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="n_ex_tests" class="col-sm-5 control-label">n_ex_tests</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="n_ex_tests" name="n_ex_tests" placeholder="liczba dodatkowych testów">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="n_sample_tests" class="col-sm-5 control-label">n_sample_tests</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="n_sample_tests" name="n_sample_tests" placeholder="liczba testów przykładowych">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="input_pre" class="col-sm-5 control-label">input_pre</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="input_pre" name="input_pre" placeholder="prefiks plików wejściowych">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="input_suf" class="col-sm-5 control-label">input_suf</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="input_suf" name="input_suf" placeholder="sufiks plików wejściowych">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="output_pre" class="col-sm-5 control-label">output_pre</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="output_pre" name="output_pre" placeholder="prefiks plików wyjściowych">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="output_suf" class="col-sm-5 control-label">output_suf</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="output_suf" name="output_suf" placeholder="sufiks plików wyjściowych">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="time_limit" class="col-sm-5 control-label">time_limit</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="time_limit" name="time_limit" placeholder="Limit czasu (bez miejsc dziesiętnych)">
    							</div>
  							</div>
  							<div class="form-group row">
    							<label for="memory_limit" class="col-sm-5 control-label">memory_limit</label>
    							<div class="col-sm-7">
      								<input type="text" class="form-control" id="memory_limit" name="memory_limit" placeholder="Limit pamięci">
    							</div>
  							</div>
							<input type="hidden" name="problem_settings_file_submit" value="submit">
      				</div>
      				<div class="modal-footer">
						<button type="submit" class="btn btn-success">Zatwierdź</button>
						</form>
        				<button type="button" class="btn btn-secondary" data-dismiss="modal">Zamknij</button>
      				</div>
    			</div>
  		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
