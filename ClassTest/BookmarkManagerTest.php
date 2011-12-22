<?php

require_once(realpath(dirname(__FILE__) . '/../') . '/BookmarkManager.php');

class BookmarkManagerTest extends PHPUnit_Framework_TestCase
{

	public function test_extractTitle()
	{
		$nbsp = html_entity_decode('&nbsp;', 0, 'UTF-8');

		$tests = array(
			'extractTitle.html' => 'bla\'bla möö'.$nbsp.'p&hop',
			'extractTitle.latin1.html' => 'mööp',
		);

		foreach ($tests as $file => $exp) {
			$title = file_get_contents(dirname(__FILE__) . '/' . $file);
			$title = B\BookmarkManager::extractTitle($title);
			$this->assertEquals($exp, $title);
			$this->assertEquals('UTF-8', mb_detect_encoding($title, 'UTF-8', true));
		}
	}

}

