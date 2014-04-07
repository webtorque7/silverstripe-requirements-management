<?php
/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 4/04/14
 * Time: 12:04 PM
 */

class RM_RequirementsInserter extends Extension {
	public function onAfterInit() {
		$fileSets = FileSet::get()->filter('Active', 1);

		//filter for subsites
		if (class_exists('Subsite') && Subsite::get()->count() > 0) {
			$subsite = Subsite::currentSubsiteID();
			//main subsite
			if (!$subsite) {
				$fileSets = $fileSets->filter('IncludeOnMainSite', 1);
			}
			else {
				$fileSets = $fileSets->innerJoin('FileSet_Subsites', sprintf('"SubsiteID" = %d', $subsite));
			}
		}

		foreach ($fileSets as $fileSet) {
			$fileSet->includeFiles();
		}
	}
} 