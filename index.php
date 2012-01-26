<?php

namespace B;

if (isset($_GET['configtest'])) {
	$requiredModules = array(
		'sqlite3',
		'curl',
	);

	$failed = false;

	header('Content-type: text/plain');

	foreach ($requiredModules as $module) {
		if (!extension_loaded($module)) {
			echo "PHP module missing: $module \n";
			$failed = true;
		}
	}

	if ($failed) {
		exit(1);
	}

	echo "OK.";
	exit(0);
}

require_once('BookmarkManager.php');

try {
	$b = new BookmarkManager($_SERVER['REQUEST_URI']);
	$b->handleAjaxRequest($_POST);

	if (!empty($_GET['filter'])) {
		$filter = $_GET['filter'];
	} else {
		$filter = false;
	}

	$entries = $b->getDB()->getEntries($filter);
} catch (\Exception $e) {
	header('HTTP/1.1 500 err');
	echo $e->getMessage();
	exit();
}

?>
<!doctype html>
<title>b</title>

<link rel="stylesheet" href="style.css"/>

<div class="content">

<header>
	<form>
		<input autofocus type="text" name="query" value="" placeholder="http://... <- enter new url here and press return | or enter filter query"/>
	</form>
</header>

<?php foreach ($entries as $entry): ?>

<div class="entry" id="entry_<?php echo $entry['id']; ?>" data-id="<?php echo $entry['id']; ?>" data-title="<?php echo htmlspecialchars($entry['desc']); ?>">
	<div class="title"><?php echo BookmarkManager::formatDesc($entry['desc']); ?></div>
	<a class="link" target="_blank" href="<?php echo htmlspecialchars($entry['link']); ?>"><?php echo htmlspecialchars($entry['link']);  ?></a>
</div>

<?php endforeach; ?>

</div>

<script src="jquery-1.7.1.min.js"></script>
<script src="bookmarkManager.js"></script>

