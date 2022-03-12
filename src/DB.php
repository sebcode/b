<?php

namespace B;

class DB
{
    protected \PDO $pdo;

    public function __construct(string $file = '')
    {
        $new = !file_exists($file);

        $this->pdo = new \PDO('sqlite:'.$file);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        if ($new) {
            $this->createTables();
        }
    }

    private function createTables(): void
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

    public function add(string $desc, string $link): bool
    {
        $this->pdo->prepare('
            INSERT INTO b (desc, link) VALUES (:desc, :link)
        ')->execute([
            ':desc' => $desc,
            ':link' => $link,
        ]);

        return true;
    }

    public function exists(string $link): bool
    {
        $st = $this->pdo->prepare('
            SELECT id FROM b
            WHERE link = :link
        ');

        $st->execute([ ':link' => $link ]);

        return (bool) $st->fetch();
    }

    public function getEntries(?string $filter = null, ?int $skip = null, ?int $count = null): array
    {
        if ($skip !== null && $count !== null) {
            $limit = 'LIMIT :skip, :count';
        } else {
            $limit = '';
        }

        $where = [];
        $args = [];

        if ($filter !== null) {
            $queryParts = explode(' ', $filter);
            foreach ($queryParts as $i => $part) {
                $where[] = "desc LIKE :filter$i";
                $args[":filter$i"] = '%'.$part.'%';
            }
        } else {
            $where[] = "desc LIKE :filter";
            $args[":filter"] = '%';
        }

        $st = $this->pdo->prepare("
            SELECT id, desc, link FROM b
            WHERE ". join(' AND ', $where) ."
            ORDER BY date DESC
            $limit
        ");

        if ($skip !== null && $count !== null) {
            $args['skip'] = $skip;
            $args['count'] = $count;
        }

        $st->execute($args);

        $ret = $st->fetchAll();

        return $ret;
    }

    public function deleteEntry(int $id): bool
    {
        $this->pdo->prepare('
            DELETE FROM b WHERE id = :id
        ')->execute([
            ':id' => $id,
        ]);

        return true;
    }

    public function setTitle(int $id, string $title): bool
    {
        $this->pdo->prepare('
            UPDATE b SET desc = :desc WHERE id = :id
        ')->execute([
            ':id' => $id,
            ':desc' => $title,
        ]);

        return true;
    }

    public function setLink(int $id, string $link): bool
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
