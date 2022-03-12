<?php

namespace B;

class BookmarkManager
{
    /** @var Array<?string> */
    protected array $config;
    protected DB $db;
    protected string $user;
    protected string $baseDir;
    public string $requestUri;
    public string $subPage = '';

    public function __construct(string $requestUri = '/')
    {
        $getEnv = function (string $key): ?string {
            $value = getenv($key);
            return $value !== false ? $value : null;
        };

        $this->config = [
            'baseDir' => $getEnv('BASE_DIR') ?? '/app/db/',
            'baseUri' => $getEnv('BASE_URI') ?? '/',
            'infiniteScrolling' => $getEnv('INFINITE_SCROLLING') ?? null,
        ];

        $this->baseDir = $this->config['baseDir'];

        if (!is_dir($this->baseDir)) {
            throw new \Exception('invalid baseDir: ' . $this->baseDir);
        }

        if (!is_writeable($this->baseDir)) {
            throw new \Exception('baseDir not writeable: ' . $this->baseDir);
        }

        $baseUri = $this->config['baseUri'];

        if (strpos($requestUri, $baseUri) !== 0) {
            throw new \Exception('invalid baseuri');
        }

        /* cut baseUri from request uri */
        $this->requestUri = substr($requestUri, strlen($baseUri));

        /* cut query string from request uri */
        if (($p = strpos($this->requestUri, '?')) !== false) {
            $this->requestUri = substr($this->requestUri, 0, $p);
        }

        $uriParts = explode('/', trim($this->requestUri, '/'));

        if (count($uriParts) === 2) {
            $this->subPage = $uriParts[1];
        }

        $user = $uriParts[0];

        if (!preg_match('@^[a-z0-9]+$@i', $user)) {
            throw new \Exception('Page not found', 404);
        }

        if (!is_dir($this->baseDir.$user)) {
            throw new \Exception('No such user: '.$user, 404);
        }

        if (!is_writeable($this->baseDir.$user)) {
            throw new \Exception('User dir not writeable');
        }

        $this->user = $user;

        $this->db = new DB($this->baseDir.$user.'/b.db');
    }

    public function getConfig(string $key): ?string
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return null;
    }

    public function getDB(): DB
    {
        return $this->db;
    }

    public function handleAjaxRequest(array $postData): void
    {
        if (empty($postData['action'])) {
            return;
        }

        $action = (string)$postData['action'];
        $error = true;
        $result = [];

        if (!empty($postData['id'])) {
            $id = (int)$postData['id'];
            $result['id'] = $id;
        } else {
            $id = 0;
        }

        try {
            switch ($action) {
            case 'add':
                $result['url'] = (string)$postData['url'];
                $result['force'] = $postData['force'] ? true : false;
                list($url, $desc) = array_pad(explode(' ', (string)$postData['url'], 2), 2, '');
                $this->addBookmark($url, $desc, !empty($postData['force']));
                $error = false;
                break;

            case 'delete':
                if ($id) {
                    $this->db->deleteEntry($id);
                    $error = false;
                }
                break;

            case 'settitle':
                if ($id && !empty($postData['title'])) {
                    $this->db->setTitle($id, (string)$postData['title']);
                    $error = false;
                    $result['title'] = self::formatDesc((string)$postData['title'], false);
                    $result['rawTitle'] = (string)$postData['title'];
                    $result['tags'] = self::formatTags((string)$postData['title']);
                }
                break;

            case 'setlink':
                if ($id && !empty($postData['link'])) {
                    $this->db->setLink($id, (string)$postData['link']);
                    $error = false;
                    $result['link'] = (string)$postData['link'];
                }
                break;

            default:
                return;
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        if ($error) {
            $result['result'] = false;
        } else {
            $result['result'] = true;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);

        exit();
    }

    public function addBookmark(string $url, string $appendDesc = '', bool $force = false): bool
    {
        if ($this->db->exists($url)) {
            throw new \Exception('Bookmark already exists.');
        }

        try {
            $body = $this->fetch($url);
        } catch (\Exception $e) {
            if (!$force) {
                throw $e;
            }
        }

        if (!empty($body)) {
            $desc = $this->extractTitle($body).' '.$appendDesc;
        } else {
            $desc = $url;
        }

        return $this->db->add($desc, $url);
    }

    private function fetch(string $url): string
    {
        if (($h = curl_init($url)) === false) {
            throw new \Exception('could not init curl');
        }

        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_FOLLOWLOCATION, true);

        if (($ret = curl_exec($h)) === false) {
            throw new \Exception('could not fetch');
        }

        return (string)$ret;
    }

    public static function extractTitle(string $body): string
    {
        if (!preg_match('@<title>([^<]+)@', $body, $m)) {
            return '(unknown title)';
        }

        $ret = $m[1];

        $enc = mb_detect_encoding($ret, 'UTF-8,ISO-8859-1', true);

        if ($enc !== 'UTF-8') {
            $ret = mb_convert_encoding($ret, 'UTF-8', $enc);
        }

        $ret = trim(html_entity_decode($ret, ENT_QUOTES, 'UTF-8'));

        return $ret;
    }

    public static function formatDesc(string $desc, bool $withTags = true): string
    {
        $desc = htmlspecialchars($desc);

        if (preg_match_all('@#[a-z0-9-_]+@i', $desc, $m)) {
            $matches = $m[0];

            foreach ($matches as $tag) {
                if ($withTags) {
                    $replaceWith = self::formatTagLink($tag);
                } else {
                    $replaceWith = '';
                }
                $desc = str_replace($tag, $replaceWith, $desc);
            }
        }

        return trim($desc);
    }

    public static function formatTags(string $desc): string
    {
        $tags = '';
        $desc = htmlspecialchars($desc);

        if (preg_match_all('@#[a-z0-9-_]+@i', $desc, $m)) {
            $matches = $m[0];

            foreach ($matches as $tag) {
                $tags .= self::formatTagLink($tag).' ';
            }
        }

        return $tags;
    }

    public static function formatTagLink(string $tag): string
    {
        return '<a class="hash" href="?filter='.rawurlencode($tag).'">'.$tag.'</a>';
    }
}
