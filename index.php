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
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width; initial-scale=1.0;">
  <title>b</title>

  <link href='http://fonts.googleapis.com/css?family=Electrolize' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="vendor/normalize.css"/>
  <link rel="stylesheet" href="style.css"/>
<head>

<body>

<div class="content">

<div class="header">
  <form>
    <input autofocus type="text" name="query" value="" placeholder=""/>
  </form>
</div>

<?php foreach ($entries as $entry): ?>

<div class="entry" id="entry_<?php echo $entry['id']; ?>" data-id="<?php echo $entry['id']; ?>" data-title="<?php echo htmlspecialchars($entry['desc']); ?>">
  <div class="title"><?php echo BookmarkManager::formatDesc($entry['desc'], false); ?></div>
  <a class="link" target="_blank" href="<?php echo htmlspecialchars($entry['link']); ?>"><?php echo htmlspecialchars($entry['link']);  ?></a>
  <div class="tags"><?php echo BookmarkManager::formatTags($entry['desc']); ?></div>
</div>

<?php endforeach; ?>

</div>

<script src="vendor/jquery-2.0.3.min.js"></script>
<script src="bookmarkManager.js"></script>

</body>
</head>

