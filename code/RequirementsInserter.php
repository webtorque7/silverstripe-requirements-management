<?php
/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 4/04/14
 * Time: 12:04 PM
 */

class RM_RequirementsInserter extends Extension {
	public function onAfterInit() {
		foreach (FileSet::get()->filter('Active', 1) as $fileSet) {
			$fileSet->includeFiles();
		}
	}
} 