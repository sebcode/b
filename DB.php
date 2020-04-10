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
        /*
        $this->pdo->prepare("
            CREATE TEMPORARY TABLE tags (
                id INTEGER PRIMARY KEY,                
                desc TEXT NOT NULL DEFAULT '' UNIQUE,
                count INTEGER NOT NULL DEFAULT 1
            );
        ")->execute();
        */
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

        if ($filter) {
            $queryParts = explode(' ', $filter);
            $where = [];
            $args = [];
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

        if ($skip !== false && $count !== false) {
            $args['skip'] = $skip;
            $args['count'] = $count;
        }

        $st->execute($args);

        $ret = $st->fetchAll();

        return $ret;
    }
    
    public function getHashTagsArray()
    {
        
        $stAll = $this->pdo->prepare('
            SELECT desc FROM b WHERE desc LIKE :desc;
        ');
        
        $stAll->execute([ 'desc' => "%" ]);
                
        $allDescs = $stAll->fetchAll();
                
        $hashTags = array(array());
                
        foreach ( $allDescs as $desc ) {
                       
            $description = $desc['desc'];
           
            if (preg_match_all('@#[a-z0-9-_]+@i', $description, $m)) {
                $matches = $m[0];

                foreach ($matches as $tag) {
                    
                    $found = false;
                    foreach( $hashTags as $hashes ) {
                        if ($hashes['desc'] === $tag ) {
                            $hashes['count'] = $hashes['count'] + 1;
                            $found = true; 
                        }
                    }
                    if ( $found === false ) {
                        $hashTags[] = [ 'desc' => $tag , 'count' => 1 ];
                    }
                    
                }
            } 
        }
         
        asort($hashTags);
                
        return $hashTags;
    }
    
    /*
     *  orderby :=  "desc" | "count"
     */
    public function getHashTagsTable($orderBy)
    {
        
        //tmp
        /*$st = $this->pdo->prepare("
            DROP TABLE IF EXISTS tags; 
        ")->execute();
        */
        $st = $this->pdo->prepare("
            CREATE TEMPORARY TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY,                
                desc TEXT NOT NULL DEFAULT '' UNIQUE,
                count INTEGER NOT NULL DEFAULT 1
                );
        ")->execute();
        
        $st = $this->pdo->prepare("
            DELETE FROM tags
            ;
        ")->execute();
        
        $stAll = $this->pdo->prepare("
            SELECT desc FROM b WHERE desc LIKE :desc;
        ");
        
        $args['desc'] = "%";
        
        $stAll->execute($args);
        
        $allDescs = $stAll->fetchAll();            
        
        foreach ( $allDescs as $desc ) {
           
            //$description = htmlspecialchars($desc['desc']);
            $description = $desc['desc'];
           
            if (preg_match_all('@#[a-z0-9-_]+@i', $description, $m)) {
                $matches = $m[0];

                foreach ($matches as $tag) {
                    $this->incTagCounter($tag);
                }
            } 
        }
        
        $sql = "SELECT desc,count FROM tags ORDER BY desc ASC;";
        if ( "$orderBy" === "count"){
           $sql = "SELECT desc,count FROM tags ORDER BY count DESC;";
        }
        
        $stTags = $this->pdo->prepare($sql);
        
        $stTags->execute();
        
        $ret = $stTags->fetchAll();
        
        $st = $this->pdo->prepare("
            DROP TABLE tags; 
        ")->execute();
                
        return $ret;
    }
    
    private function incTagCounter($tag){
        
        $st = $this->pdo->prepare("
            INSERT OR REPLACE INTO tags(desc,count) 
                VALUES (
                    :tag ,
                    (SELECT (count + 1 ) FROM tags WHERE desc = :tag)
                );
                ")->execute([':tag' => $tag ]);
                
        $st = $this->pdo->prepare("        
            UPDATE tags SET count=1 WHERE desc = :tag and count = 0 ;
            ")->execute([':tag' => $tag ]);
        
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
