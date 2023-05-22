<?php

use B\BookmarkManager;

require_once __DIR__.'/../vendor/autoload.php';

try {
    $b = new BookmarkManager($_SERVER['REQUEST_URI']);

    if ($b->subPage === 'bookmarklet') {
        include __DIR__.'/bookmarklet.php';
        exit();
    } elseif ($b->subPage) {
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
    if ($count !== null) {
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
            <a class="link" target="_blank" href="<?php echo htmlspecialchars($entry['link']); ?>"><?php echo htmlspecialchars($entry['link']); ?></a>
            <div class="tags"><?php echo BookmarkManager::formatTags($entry['desc']); ?></div>
        </div>
        <?php
    }
}

$pageConfig = [
    'filter' => $filter ? $filter : '',
    'infiniteScrolling' => $b->getConfig('infiniteScrolling'),
    'add' => $_GET['add'] ?? null,
];

$h = 'htmlspecialchars';

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="referrer" content="no-referrer">
  <meta name="google" content="notranslate">
  <title>b</title>

  <link rel="stylesheet" href="normalize.css"/>
  <link rel="stylesheet" href="style.css"/>
</head>

<body>

<div id="content">
    <div class="header">
        <form id="filterform">
            <input id="query"
            autofocus
            type="text"
            name="query"
            placeholder=""
            value="<?php echo $h($filter); ?>"
            />
        </form>
    </div>

    <?php dumpEntries($entries); ?>
</div>

<div data-b="<?php echo $h(json_encode($pageConfig)) ?>"></div>

<script src="bookmarkManager.js"></script>
</body>
</html>
