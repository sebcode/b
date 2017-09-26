<?php

namespace B;

if (isset($_GET['configtest'])) {
    $requiredModules = [
        'sqlite3',
        'curl',
    ];

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

    echo 'OK.';
    exit();
}

require_once 'BookmarkManager.php';

try {
    $b = new BookmarkManager($_SERVER['REQUEST_URI']);

    if ($b->subPage === 'bookmarklet') {
        include __DIR__.'/bookmarklet.php';
        exit();
    } else if ($b->subPage) {
        throw new \Exception('Page not found', 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = json_decode(file_get_contents("php://input"), true);
        $b->handleAjaxRequest($postData);
    }

    if (!empty($_GET['filter'])) {
        $filter = $_GET['filter'];
    } else {
        $filter = false;
    }

    $skip = false;
    $count = $b->getConfig('infiniteScrolling');
    if ($count !== false) {
        $skip = 0;
    }

    if (isset($_GET['skip'])) {
        $skip = $_GET['skip'];
    }

    if (isset($_GET['count'])) {
        $count = $_GET['count'];
    }

    if (!empty($_GET['format'])) {
        $format = $_GET['format'];
    } else {
        $format = false;
    }

    $entries = $b->getDB()->getEntries($filter, $skip, $count);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($format === 'html') {
        dumpEntries($entries);
        exit();
    }
} catch (\Exception $e) {
    if (($code = $e->getCode()) && is_numeric($code)) {
        http_response_code($code);
    } else {
        http_response_code(500);
    }

    echo $e->getMessage();
    exit();
}

function dumpEntries($entries)
{
  foreach ($entries as $entry) {
  ?>
    <div class="entry" id="entry_<?php echo $entry['id']; ?>" data-id="<?php echo $entry['id']; ?>" data-title="<?php echo htmlspecialchars($entry['desc']); ?>">
      <div class="title"><?php echo BookmarkManager::formatDesc($entry['desc'], false); ?></div>
      <a class="link" target="_blank" href="<?php echo htmlspecialchars($entry['link']); ?>"><?php echo htmlspecialchars($entry['link']);  ?></a>
      <div class="tags"><?php echo BookmarkManager::formatTags($entry['desc']); ?></div>
    </div>

  <?php
  }
}

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="referrer" content="no-referrer">
  <title>b</title>

  <link rel="stylesheet" href="vendor/normalize.css"/>
  <link rel="stylesheet" href="style.css"/>
</head>

<body>

<div id="content">

<div class="header">
  <form id="filterform">
    <input id="query" autofocus type="text" name="query" value="" placeholder=""/>
  </form>
</div>

<?php dumpEntries($entries); ?>

</div>

<script>
window.filter = <?php echo $filter ? json_encode($filter) : "''"; ?>

<?php if ($step = $b->getConfig('infiniteScrolling')): ?>
window.infiniteScrolling = <?php echo $step; ?>
<?php endif; ?>
</script>
<script src="bookmarkManager.js"></script>

<?php if (!empty($_GET['add'])): ?>

<script>

(function () {
    $(document).ready(function() {
        var input = $(':input')
        input.val("<?php echo $_GET['add']; ?> ")
        input.focus()
        /* Remove query string from URL */
        history.replaceState({}, null, window.location.pathname)
    })
}())

</script>

<?php endif; ?>

</body>
</html>

