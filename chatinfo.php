<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include 'mp.php';
$user = MP::getUser();
if(!$user) {
	header('Location: login.php?logout=1');
	die();
}

$theme = MP::getSettingInt('theme', 0);

$id = null;
if(isset($_POST['c'])) {
	$id = $_POST['c'];
} else if(isset($_GET['c'])) {
	$id = $_GET['c'];
} else {
	die();
}

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: private, no-cache, no-store");

function exceptions_error_handler($severity, $message, $filename, $lineno) {
	throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
set_error_handler('exceptions_error_handler');

try {
	$MP = MP::getMadelineAPI($user);

	$lng = MP::initLocale();

	header('Content-Type: text/html; charset=utf-8');
	header('Cache-Control: private, no-cache, no-store');

	include 'themes.php';
	Themes::setTheme($theme);

	$chat = $MP->getPwrChat($id);
	$name = $chat['title'] ?? (isset($chat['first_name']) ? $chat['first_name'] . (isset($chat['last_name']) ? ' '.$chat['last_name'] : '') : null) ?? 'Deleted Account';
	$type = $chat['type'];
	
	$desc = null;

	if($type != 'user') {
		$desc = $MP->getFullInfo($id)['full']['about'] ?? null;
		
		$members = $chat['participants'] ?? null;
		$onlines = 0;

		if($members) {
			foreach($members as $i => $m) {
				if(isset($m['kicked_by'])) {
					unset($members[$i]);
				} else if(isset($m['user']) && isset($m['user']['status']) && $m['user']['status']['_'] == 'userStatusOnline') {
					$onlines ++;
				}
			}
		}
	}

	echo '<head><title>'.MP::dehtml($name).'</title>';
	echo Themes::head();
	echo '</head>';
	echo Themes::bodyStart();
	echo '<div>';
	echo '<div class="chr"><small><a href="chat.php?c='.$id.'">'.MP::x($lng['back']).'</a></small></div>';
	echo '<div class="cava"><img class="ri" src="ava.php?c='.$id.'&p=r48"></div>';
	echo '<div>';
	echo MP::dehtml($name);
	echo '</div>';
	echo '<div>';
	if($type != 'user') {
		echo MP::x(Locale::number($type == 'chat' ? 'members' : 'subscribers', count($members)));
		if($onlines > 0) {
			echo ', ' . strval($onlines) . ' ' . MP::x($lng['online']);
		}
	}
	echo '</div>';
	echo '</div>';
	if($desc) {
		echo '<p>'.MP::x($lng['chat_about']).':<br>'.MP::dehtml($desc).'</p>';
	}
	if($members) {
		$avas = MP::getSettingInt('avas', 0);
		echo MP::x($lng['chat_members']).':';
		echo '<div>';
		$i = 0;
		foreach($members as $m) {
			$i ++;
			if($i > 100) {
				echo '<div>...</div>';
				break;
			}
			echo '<div class="c0">';
			$u = $m['user'] ?? $m['chat'] ?? $m['channel'];
			$un = $u['title'] ?? (isset($u['first_name']) ? $u['first_name'] . (isset($u['last_name']) ? ' '.$u['last_name'] : '') : null) ?? 'Deleted Account';
			$status = null;
			if(isset($u['status'])) {
				$status = $u['status']['_'] == 'userStatusOnline';
				$last = $u['status']['was_online'] ?? 0;
			}
			$rank = null;
			if(isset($m['rank'])) {
				$rank = $m['rank'];
			} else if(isset($m['role'])) {
				$role = $m['role'];
				if($role == 'creator') {
					$rank = MP::x($lng['owner']);
				} else if($role == 'admin') {
					$rank = MP::x($lng['admin']);
				}
			}
			if($rank) {
				echo '<div class="chr ml">'.MP::dehtml($rank).'</div>';
			}
			if($avas) {
				echo '<div class="cava"><img class="ri" src="ava.php?c='.$u['id'].'&p=r36"></div>';
			}
			echo '<div>'.MP::dehtml($un).'</div>';
			echo '<div class="ml">'. ($status !== null ? ($status ? MP::x($lng['online']) : '') : '&nbsp;').'</div>';
			echo '</div>';
		}
		echo '</div>';
	}

	echo Themes::bodyEnd();
} catch (Exception $e) {
	echo '<xmp>';
	echo $e;
	echo '</xmp>';
}
?>