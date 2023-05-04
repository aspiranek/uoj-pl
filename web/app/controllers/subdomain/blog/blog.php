<?php
	requirePHPLib('form');
	
	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHis($blog)) {
		become404Page();
	}
	if ($blog['is_hidden'] && !UOJContext::hasBlogPermission()) {
		become403Page();
	}
	
	$comment_form = new UOJForm('comment');
	$comment_form->addVTextArea('comment', 'Treść', '',
		function($comment) {
			global $myUser;
			if ($myUser == null) {
				return 'Proszę się zalogować';
			}
			if (!$comment) {
				return 'Komenatrz nie może być pusty';
			}
			if (strlen($comment) > 1000) {
				return 'Długość komentarza nie może być większa niż 1000';
			}
			return '';
		},
		null
	);
	$comment_form->handle = function() {
		global $myUser, $blog, $comment_form;
		$comment = HTML::escape($_POST['comment']);
		
		list($comment, $referrers) = uojHandleAtSign($comment, "/post/{$blog['id']}");
		
		$esc_comment = DB::escape($comment);
		DB::insert("insert into blogs_comments (poster, blog_id, content, reply_id, post_time, zan) values ('{$myUser['username']}', '{$blog['id']}', '$esc_comment', 0, now(), 0)");
		$comment_id = DB::insert_id();
		
		$rank = DB::selectCount("select count(*) from blogs_comments where blog_id = {$blog['id']} and reply_id = 0 and id < {$comment_id}");
		$page = floor($rank / 20) + 1;
		
		$uri = getLongTablePageUri($page) . '#' . "comment-{$comment_id}";
		
		foreach ($referrers as $referrer) {
			$content = 'Ktoś wspomniał o Tobie w komentarzu na blogu ' . $blog['title'] . '. <a href="' . $uri . '">Sprawdź!</a>';
			sendSystemMsg($referrer, 'Ktoś o Tobie wspomniał', $content);
		}
		
		if ($blog['poster'] !== $myUser['username']) {
			$content = 'Ktoś odpowiedział na Twój blog ' . $blog['title'] . '. <a href="' . $uri . '">Sprawdź!</a>';
			sendSystemMsg($blog['poster'], 'Nowa odpowiedź na Twoim blogu', $content);
		}
		
		$comment_form->succ_href = getLongTablePageRawUri($page);
	};
	$comment_form->ctrl_enter_submit = true;
	
	$comment_form->runAtServer();
	
	$reply_form = new UOJForm('reply');
	$reply_form->addHidden('reply_id', '0',
		function($reply_id, &$vdata) {
			global $blog;
			if (!validateUInt($reply_id) || $reply_id == 0) {
				return 'Obiekt, na który odpowiadasz, nie istnieje';
			}
			$comment = queryBlogComment($reply_id);
			if (!$comment || $comment['blog_id'] != $blog['id']) {
				return 'Obiekt, na który odpowiadasz, nie istnieje';
			}
			$vdata['parent'] = $comment;
			return '';
		},
		null
	);
	$reply_form->addVTextArea('reply_comment', 'Treść', '',
		function($comment) {
			global $myUser;
			if ($myUser == null) {
				return 'Proszę się zalogować';
			}
			if (!$comment) {
				return 'Komenatrz nie może być pusty';
			}
			if (strlen($comment) > 140) {
				return 'Długość komentarza nie może przekraczać 140';
			}
			return '';
		},
		null
	);
	$reply_form->handle = function(&$vdata) {
		global $myUser, $blog, $reply_form;
		$comment = HTML::escape($_POST['reply_comment']);
		
		list($comment, $referrers) = uojHandleAtSign($comment, "/post/{$blog['id']}");
		
		$reply_id = $_POST['reply_id'];
		
		$esc_comment = DB::escape($comment);
		DB::insert("insert into blogs_comments (poster, blog_id, content, reply_id, post_time, zan) values ('{$myUser['username']}', '{$blog['id']}', '$esc_comment', $reply_id, now(), 0)");
		$comment_id = DB::insert_id();
		
		$rank = DB::selectCount("select count(*) from blogs_comments where blog_id = {$blog['id']} and reply_id = 0 and id < {$reply_id}");
		$page = floor($rank / 20) + 1;
		
		$uri = getLongTablePageUri($page) . '#' . "comment-{$reply_id}";
		
		foreach ($referrers as $referrer) {
			$content = 'Ktoś wspomniał o Tobie w komentarzu na blogu ' . $blog['title'] . '. <a href="' . $uri . '">Sprawdź!</a>';
			sendSystemMsg($referrer, 'Ktoś o Tobie wspomniał!', $content);
		}
		
		$parent = $vdata['parent'];
		$notified = array();
		if ($parent['poster'] !== $myUser['username']) {
			$notified[] = $parent['poster'];
			$content = 'Ktoś odpowiedział na Twój komentarz na blogu ' . $blog['title'] . '. <a href="' . $uri . '">Sprawdź!</a>';
			sendSystemMsg($parent['poster'], 'Ktoś odpowiedział na Twój komentarz!', $content);
		}
		if ($blog['poster'] !== $myUser['username'] && !in_array($blog['poster'], $notified)) {
			$notified[] = $blog['poster'];
			$content = 'Ktoś odpowiedział na Twój blog ' . $blog['title'] . '. <a href="' . $uri . '">Sprawdź!</a>';
			sendSystemMsg($blog['poster'], 'Nowa odpowiedź na Twoim blogu', $content);
		}
		
		$reply_form->succ_href = getLongTablePageRawUri($page);
	};
	$reply_form->ctrl_enter_submit = true;
	
	$reply_form->runAtServer();
	
	$comments_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs_comments',
		'cond' => 'blog_id = ' . $blog['id'] . ' and reply_id = 0',
		'tail' => 'order by id asc',
		'page_len' => 20
	));
?>
<?php
	$REQUIRE_LIB['mathjax'] = '';
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(HTML::stripTags($blog['title']) . ' - Blog') ?>
<?php echoBlog($blog, array('show_title_only' => isset($_GET['page']) && $_GET['page'] != 1)) ?>
<h2>Komentarz <span class="glyphicon glyphicon-comment"></span></h2>
<div class="list-group">
<?php if ($comments_pag->isEmpty()): ?>
	<div class="list-group-item text-muted">Brak komentarzy</div>
<?php else: ?>
	<?php foreach ($comments_pag->get() as $comment):
		$poster = queryUser($comment['poster']);
		$esc_email = HTML::escape($poster['email']);
		$asrc = HTML::avatar_addr($poster, 80);
		
		$replies = DB::selectAll("select id, poster, content, post_time from blogs_comments where reply_id = {$comment['id']} order by id");
		foreach ($replies as $idx => $reply) {
			$replies[$idx]['poster_rating'] = queryUser($reply['poster'])['rating'];
		}
		$replies_json = json_encode($replies);
	?>
	<div id="comment-<?= $comment['id'] ?>" class="list-group-item">
		<div class="media">
			<div class="media-left comtposterbox mr-3">
				<a href="<?= HTML::url('/user/profile/'.$poster['username']) ?>" class="d-none d-sm-block">
					<img class="media-object img-rounded" src="<?= $asrc ?>" alt="avatar" />
				</a>
			</div>
			<div id="comment-body-<?= $comment['id'] ?>" class="media-body comtbox">
				<div class="row">
					<div class="col-sm-6"><?= getUserLink($poster['username']) ?></div>
					<div class="col-sm-6 text-right"><?= getClickZanBlock('BC', $comment['id'], $comment['zan']) ?></div>
				</div>
				<div class="comtbox1"><?= $comment['content'] ?></div>
				<ul class="text-right list-inline bot-buffer-no"><li><small class="text-muted"><?= $comment['post_time'] ?></small></li><li><a id="reply-to-<?= $comment['id'] ?>" href="#">Odpowiedź</a></li></ul>
				<?php if ($replies): ?>
				<div id="replies-<?= $comment['id'] ?>" class="comtbox5"></div>
				<?php endif ?>
				<script type="text/javascript">showCommentReplies('<?= $comment['id'] ?>', <?= $replies_json ?>);</script>
			</div>
		</div>
	</div>
	<?php endforeach ?>
<?php endif ?>
</div>
<?= $comments_pag->pagination() ?>

<h3 class="mt-4">Komentarz</h3>
<p>Użyj @mike by wspomnieć o użytkowniku mike. Jeśli chcesz uzyć znaku "@" wpisz "@@".</p>
<?php $comment_form->printHTML() ?>

<div id="div-form-reply" style="display:none">
	<?php $reply_form->printHTML() ?>
</div>

<?php echoUOJPageFooter() ?>
