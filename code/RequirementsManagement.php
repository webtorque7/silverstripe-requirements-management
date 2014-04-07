<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 3/11/13
 * Time: 6:25 PM
 */

class RequirementsManagement extends ModelAdmin
{

	public static $managed_models = array('CSSFileSet', 'JSFileSet');

	public static $url_segment = 'requirements-management';

	public static $menu_title = 'CSS/JS';
}

class FileSet extends DataObject
{
	private static $allowed_extensions = array('css');

	public static $db = array(
		'Title' => 'Varchar(200)',
		'Active' => 'Boolean',
		'CombineFiles' => 'Boolean',
		'IncludeOnMainSite' => 'Boolean'
	);

	public static $many_many = array(
		'Files' => 'File',
		'Subsites' => 'Subsite'
	);

        public static $many_many_extraFields = array(
                'Files' => array(
                        'SortOrder' => 'Int'
                )
        );

	public static $summary_fields = array(
		'Title' => 'Title',
		//'NumberOfFiles' => 'Number of Files'
	);

        public function Files() {
                return $this->getManyManyComponents('Files')->sort('SortOrder');
        }

        public function getCMSFields() {
                $fields = parent::getCMSFields();

	        $fields->removeByName('Files');
	        $fields->addFieldToTab('Root.Upload', UploadField::create('Files'));
		$fields->addFieldsToTab('Root.Sort', array(
			LiteralField::create('SortHelp', '<p>Order of files are important if there are dependencies between files</p>'),
			GridField::create('SortFiles', 'File Order', $this->Files(), GridFieldConfig_RelationEditor::create()->addComponent(new GridFieldOrderableRows('SortOrder')))
		));

	        $fields->removeByName('Subsites');
	        if (class_exists('Subsite') && Subsite::get()->count() > 0) {
		        $subsites = Subsite::get()->map('ID', 'Title');
		        $fields->addFieldsToTab('Root.Main', array(
			        CheckboxField::create('IncludeOnMainSite', 'Include on main site?'),
			        CheckboxSetField::create('Subsites', 'Subsites', $subsites
			)));
	        }
	        else {
		        $fields->removeByName('MainSite');
	        }

                return $fields;
        }

	public function NumberOfFiles() {
		return $this->Files()->count();
	}

	public function getCMSActions() {
		$actions = parent::getCMSActions();
	}

	public function getFileList() {
		$return = array();
		foreach ($this->Files() as $file) {
			$return[] = $file->RelativeLink();
		}
		return $return;
	}

	public function cacheFileList() {
		$cache = SS_Cache::factory('CachedFiles', 'Output', array('automatic_serialization' => true));

		$cache->save($this->ID, $this->getFileList());
	}

	public function getCombinedFileName() {
		return md5($this->ID . $this->Title);
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
		Requirements::delete_combined_files($this->getCombinedFileName());
		//$this->cacheFileList();
	}

	public function includeFiles() {
		user_error('This function needs implementing');
	}
}

class CSSFileSet extends FileSet
{
	public static $singular_name = 'CSS File Set';
	public static $plural_name = 'CSS File Sets';

        private static $allowed_extensions = array('css');

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		if ($files = $fields->dataFieldByName('Files')) {
			$files
				->setFolderName('css-files')
				->getValidator()->setAllowedExtensions(self::$allowed_extensions);
		}

		return $fields;
	}

	public function includeFiles() {
		if ($this->CombineFiles) {
			Requirements::combine_files($this->getCombinedFileName() . '.css', $this->getFileList());
		}
		else {
			foreach ($this->getFileList() as $file) {
				Requirements::css($file);
			}
		}
	}
}

class JSFileSet extends FileSet
{

	public static $singular_name = 'Javascript File Set';
	public static $plural_name = 'Javascript File Sets';

        private static $allowed_extensions = array('js');

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		if ($files = $fields->dataFieldByName('Files')) {
			$files
				->setFolderName('js-files')
				->getValidator()->setAllowedExtensions(self::$allowed_extensions);
		}

		return $fields;
	}

	public function includeFiles() {
		if ($this->CombineFiles) {
			Requirements::combine_files($this->getCombinedFileName() . '.js', $this->getFileList());
		}
		else {
			foreach ($this->getFileList() as $file) {
				Requirements::javascript($file);
			}
		}
	}
}