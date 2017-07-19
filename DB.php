<?php

namespace B;

class DB
{
    protected $pdo;

    public function __construct($file = '')
    {
        $new = !file_exists($file);

        $this->pdo = new \PDO('sqlite:'.$file);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        if ($new) {
            $this->createTables();
        }
    }

    private function createTables()
    {
        $this->pdo->prepare("
            CREATE TABLE b (
                id INTEGER PRIMARY KEY,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                desc TEXT NOT NULL DEFAULT '',
                link TEXT NOT NULL DEFAULT '' UNIQUE
            );
        ")->execute();
    }

    public function add($desc, $link)
    {
        $this->pdo->prepare('
            INSERT INTO b (desc, link) VALUES (:desc, :link)
        ')->execute([
            ':desc' => $desc,
            ':link' => $link,
        ]);

        return true;
    }

    public function exists($link)
    {
        $st = $this->pdo->prepare('
            SELECT id FROM b
            WHERE link = :link
        ');

        $st->execute([ ':link' => $link ]);

        return (bool) $st->fetch();
    }

    public function getEntries($filter = false, $skip = false, $count = false)
    {
        if (!$filter) {
            $filter = '%';
        }

        if ($skip !== false && $count !== false) {
            $limit = 'LIMIT :skip, :count';
        } else {
            $limit = '';
        }

        $st = $this->pdo->prepare("
            SELECT id, desc, link FROM b
            WHERE desc LIKE :filter
            ORDER BY date DESC
            $limit
        ");

        $args = [ ':filter' => "%$filter%" ];

        if ($skip !== false && $count !== false) {
            $args['skip'] = $skip;
            $args['count'] = $count;
        }

        $st->execute($args);

        $ret = $st->fetchAll();

        return $ret;
    }

    public function deleteEntry($id)
    {
        $this->pdo->prepare('
            DELETE FROM b WHERE id = :id
        ')->execute([
            ':id' => $id,
        ]);

        return true;
    }

    public function setTitle($id, $title)
    {
        $this->pdo->prepare('
            UPDATE b SET desc = :desc WHERE id = :id
        ')->execute([
            ':id' => $id,
            ':desc' => $title,
        ]);

        return true;
    }

    public function setLink($id, $link)
    {
        $this->pdo->prepare('
            UPDATE b SET link = :link WHERE id = :id
        ')->execute([
            ':id' => $id,
            ':link' => $link,
        ]);

        return true;
    }
}
