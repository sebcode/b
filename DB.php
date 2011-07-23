<?php

namespace B;

class DB
{
	protected $pdo;

	public function __construct($file = '')
	{
		$new = ! file_exists($file);

		$this->pdo = new \PDO('sqlite:' . $file);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($new) {
			$this->createTables();
		}
	}

	private function createTables()
	{
		$this->pdo->prepare("
			CREATE TABLE b (
				id INTEGER PRIMARY KEY
				,date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				,desc TEXT NOT NULL DEFAULT ''
				,link TEXT NOT NULL DEFAULT '' UNIQUE
			);
		")->execute();
	}

	public function add($desc, $link)
	{
		$this->pdo->prepare('
			INSERT INTO b (desc, link) VALUES (:desc, :link)
		')->execute(array(
			':desc' => $desc
			,':link' => $link
		));

		return true;
	}

	public function getEntries($filter = false)
	{
		if (!$filter) {
			$filter = '%';
		}

		$st = $this->pdo->prepare('
			SELECT id, desc, link FROM b
			WHERE desc LIKE :filter
			ORDER BY date DESC
		');
		
		$st->execute(array(':filter' => "%$filter%"));
		
		$ret = $st->fetchAll(\PDO::FETCH_ASSOC);

		return $ret;
	}

	public function deleteEntry($id)
	{
		$this->pdo->prepare("
			DELETE FROM b WHERE id = :id
		")->execute(array(
			':id' => $id
		));

		return true;
	}

	public function setTitle($id, $title)
	{
		$this->pdo->prepare("
			UPDATE b SET desc = :desc WHERE id = :id
		")->execute(array(
			':id' => $id
			,':desc' => $title
		));

		return true;
	}
	
	public function setLink($id, $link)
	{
		$this->pdo->prepare("
			UPDATE b SET link = :link WHERE id = :id
		")->execute(array(
			':id' => $id
			,':link' => $link
		));

		return true;
	}

}

