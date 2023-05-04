<?php
	requireLib('shjs');
	requireLib('mathjax');
	echoUOJPageHeader(UOJLocale::get('help')) 
?>
<article>
	<header>
		<h2 class="page-header">Często zadawane pytania</h2>
	</header>
	<section>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerOne" data-toggle="collapse" data-target="#collapseOne" style="cursor:pointer;">
				<h5 class="mb-0">Czym jest<?= UOJConfig::$data['profile']['oj-name-short'] ?></h5>
			</div>
			<div id="collapseOne" class="collapse">
				<div class="card-body">
					<p>Witaj na <?= UOJConfig::$data['profile']['oj-name'] ?>!</p>
					<p><img src="/images/utility/qpx_n/b37.gif" alt="niedźwiadki latają jak superman" /></p>
					<p>W zadaniach algorytmicznych chodzi o to by napisać program rozwiązujący jakiś problem. Program musi działać dosyć szybko i używać niezbyt dużo pamięci, by zmieścić się w limitach.</p>
					<p>Niby proste, jednak w niektórych momentach (np. liczby zmienno przecinkowe i ich dokładność) należy nie tylko porównać wejście programu z oczekiwanym, ale użyć tzw. special judge'a.</p>
					<p>Dlatego właśnie przekazujemy Ci do dyspozycji <?= UOJConfig::$data['profile']['oj-name-short'] ?>. Znajdziesz tu wiele ciekawych zadań i konkursów.</p>
					<p>Denerwujący jest często fakt, że niepoprawny program otrzymuje 100 puntków. Najczęściej spowodowane jest to tym, że testy są źle dobrane i nie wychwytują błędu.</p>
					<p>Dlatego wprowadziliśmy mechanizm hakowania. Każde zadanie ma dwie sekcje: testy i testy dodatkowe. Pula to 100 punktów. 97 przyznawanych jest za testy, natomiast 3 są przyznawane za testy dodatkowe.</p>
					<p>Na <?= UOJConfig::$data['profile']['oj-name-short'] ?> organizowane są też liczne konkursy.</p>
					<p>Życzymy dobrej zabawy z<?= UOJConfig::$data['profile']['oj-name-short'] ?>!</p>
					<p><img src="/images/utility/qpx_n/b54.gif" alt="ściskające się niedźwiedzie" /></p>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerTwo" data-toggle="collapse" data-target="#collapseTwo" style="cursor:pointer;">
				<h5 class="mb-0">Jak zmienić avatar?</h5>
			</div>
			<div id="collapseTwo" class="collapse">
				<div class="card-body">
					<p><?= UOJConfig::$data['profile']['oj-name-short'] ?> nie przechowuje avatarów. Musisz skorzystać z usługi <a href="https://gravatar.com">Gravatar</a>.</p>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerThree" data-toggle="collapse" data-target="#collapseThree" style="cursor:pointer;">
				<h5 class="mb-0">Jakiego środowiska używa <?= UOJConfig::$data['profile']['oj-name-short'] ?>?</h5>
			</div>
			<div id="collapseThree" class="collapse">
				<div class="card-body">
					<p>Środowisko ewaluacyjne to Ubuntu Linux 18.04 LTS x64.</p>
					<p>Kompilator C: gcc 7.4.0, polecenie:<code>gcc code.c -o code -lm -O2 -DONLINE_JUDGE</code>。</p>
					<p>Kompilator C++: g++ 7.4.0, polecenue：<code>g++ code.cpp -o code -lm -O2 -DONLINE_JUDGE</code>. Jeśli wolisz standard C++11 od C++17 możesz wyrazić chęć jego użycia przy wyborze języka (niezalecane). Wtedy do komendy zostanie dodana flaga <code>-std=c++11</code>.</p>
					<p>Wersja Java8 JDK: openjdk 1.8.0_222, komenda: <code>javac code.java</code>.</p>
					<p>Wersja Java11 JDK: openjdk 11.0.4, komenda: <code>javac code.java</code>.</p>
					<p>Wersja Pascal: fpc 3.0.4, komenda: <code>fpc code.pas -O2</code>.</p>
					<p>Python zostanie najpierw skompilowany do zoptymalizowanego kodu bajtowego <samp>.pyo</samp>. Obsługiwana wersja to Python 3.6 (dostępny jest także Python 2.7, którego używanie nie jest zalecane).</p>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerFour" data-toggle="collapse" data-target="#collapseFour" style="cursor:pointer;">
				<h5 class="mb-0">Co oznaczają różne stany oceny?</h5>
			</div>
			<div id="collapseFour" class="collapse">
				<div class="card-body">
					<ul>
						<li>Accepted: Zrobiłeś zadanie!</li>
						<li>Wrong Answer: Jakaś odpowiedź jest niepoprawna.</li>
						<li>Runtime Error: Błąd wykonania. Może dzielenie przez zero?, odwoływanie się poza tablicę?, problemy z wskaźnikami?</li>
						<li>Time Limit Exceeded: Program przekroczył maksymalny limit czasu wykonania.</li>
						<li>Memory Limit Exceeded: Przekroczenie limitu pamięci. Czy Twój program nie zużywa za dużo pamięci?</li>
						<li>Output Limit Exceeded: Twoje wyjście jest dwa razy dłuższe niż oczekiwane.</li>
						<li>Dangerous Syscalls: Pisałeś do plików? Użyłeś jakiejś ciekawej funkcji systemowej? Może po prostu pracujesz na Windowsie i dodałeś <code>system("pause")</code> na koniec kodu? ...albo też chcesz pozbawić nas platformy <?= UOJConfig::$data['profile']['oj-name-short'] ?>?!</li>
						<li>Judgement Failed: Raczej nie Twoja, lecz moja wina... Złgoś to jak najszybciej na podane dane kontaktowe!</li>
						<li>No Comment: To też złgoś...</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerFive" data-toggle="collapse" data-target="#collapseFive" style="cursor:pointer;">
				<h5 class="mb-0">Dlaczego 10<sup>7</sup> warst rekurencji nie zeksplodowało stosu?!</h5>
			</div>
			<div id="collapseFive" class="collapse">
				<div class="card-body">
					<p>Limit wielkości stosu, to limit pamięci na dane zadanie.</p>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerSix" data-toggle="collapse" data-target="#collapseSix" style="cursor:pointer;">
				<h5 class="mb-0">U mnie działa!</h5>
			</div>
			<div id="collapseSix" class="collapse">
				<div class="card-body">
					<p>Kilka częstych przyczyn:</p>
					<ul>
						<li>Masz złe rozwiązanie<li>
					</ul>
					<p>Jeżeli masz dobre to wtedy:</p>
					<ul>
						<li>Znak nowej lini na Linuxie to "\n", a na Windowsie "\r\n". Może ktoś generował testy na Windowsie?</li>
						<li>Używanie bibliotek/komend dostępnych tylko pod Windowsem?</li>
						<li>Lokalnie na Twoim komputerze być może będziesz mógł odwołać się poza tablicę. Na naszej sprawdzaczce <u>raczej</u> to nie przejdzie.</li>
						<li>Jest błąd w zadaniu... (trzecia rzecz, o której zgłaszanie prosimy)</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerSeven" data-toggle="collapse" data-target="#collapseSeven" style="cursor:pointer;">
				<h5 class="mb-0">Podręcznik blogerki/blogera</h5>
			</div>
			<div id="collapseSeven" class="collapse">
				<div class="card-body">
					<p>Blog korzysta z Markdowna.</p>
					<p>Możesz poczytać o Markdownie <a href="https://commonmark.org/help/">tutaj</a>.</p>
					<p>Obsługujemy także LaTeX'a. Spróbuj użyci znaku dolarka lub dwóch.</p>
				</div>
			</div>
		</div>
		<div class="card my-1">
			<div class="card-header collapsed" id="headerNine" data-toggle="collapse" data-target="#collapseNine" style="cursor:pointer;">
				<h5 class="mb-0">Informacjie kontaktowe</h5>
			</div>
			<div id="collapseNine" class="collapse">
				<div class="card-body">
					<p>Jeśli chcesz zadać pytanie, zorganizować konkurs, znalazłeś błąd lub masz jakieś sugestie zapraszamy do kontaktu:</p>
					<ul>
						<li>Kontakt prywatny: <?= UOJConfig::$data['profile']['administrator'] ?>.</li>
						<li>Kontakt mailowy: <?= UOJConfig::$data['profile']['admin-email'] ?>.</li>
					</ul>
				</div>
			</div>
		</div>
	</section>
</article>

<?php echoUOJPageFooter() ?>
