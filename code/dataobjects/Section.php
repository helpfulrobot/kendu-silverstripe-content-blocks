<?php
class Section extends DataObject {
    
	static $db = array(
		'Sort' => 'Int',
        'Name' => 'Varchar',
		'SectionHeader' => "Enum('None, h1, h2, h3, h4, h5, h6')",
		'SectionContent' => 'HTMLText',
        'Link' => 'Varchar',
		'Template' => 'Varchar',
		'Active' => 'Boolean(1)'
    );
    
	static $has_one = array(
        'Page' => 'Page'
    );

	static $many_many = array(
		'Images' => 'Image',
    );
	
	public static $default_sort='Sort';
	
	public static $defaults = array(
		'Template' => 'Default',
		'Active' => 1
	);

	public static $summary_fields = array( 
		'ID' => 'ID',
		'Thumbnail' => 'Thumbnail',
		'Name' => 'Name',
		'Template' => 'Template',
		'ClassName' => 'Type',
		'getIsActive' => 'Active'
	);
	
	public function getIsActive(){
		return $this->Active ? 'Yes' : 'No';
	}
	
	public function getCMSFields() {	
		$fields = parent::getCMSFields();
		$fields->removeByName('Sort');
		$fields->removeByName('PageID');
		$fields->removeByName('Active');
		$fields->removeByName('SectionHeader');
		
		$thumbField = new UploadField('Images', 'Images');
		$thumbField->allowedExtensions = array('jpg', 'gif', 'png');
	
		$fields->addFieldsToTab("Root.Main", new TextField('Name', 'Name'));
		$fields->addFieldsToTab("Root.Main", new DropdownField('SectionHeader', 'Choose a header', $this->dbObject('SectionHeader')->enumValues()), 'SectionContent');
		$fields->addFieldsToTab("Root.Main", new HTMLEditorField('SectionContent', 'Content'));

		// Image tab
		$fields->addFieldsToTab("Root.Images", $thumbField);
		
		
		// Template tab
		$optionset = array();
		$theme = SSViewer::current_theme();
		$src    = "../themes/".$theme."/templates/SectionTemplates/";
		
		if(file_exists($src)) {
			foreach (glob($src . "*.ss") as $filename) {	
				$name = $this->file_ext_strip(basename($filename));
				$html = '<span class="page-icon class-Page"></span><strong class="title">'. $name .'</strong><span class="description">'. $filename .'</span>';
				$optionset[$name] = $html;
			}
			
			$tplField = new OptionsetField(
				"Template", 
				"Vælg template", 
				$optionset, 
				$this->Template
			);
			$fields->addFieldsToTab("Root.Template", $tplField);
		} else {
			$fields->addFieldsToTab("Root.Template", new LiteralField ($name = "literalfield", $content = '<p class="message warning"><strong>Warning:</strong> The folder '.$src.' was not found.</div>'));
		}

		// Settings tab
		$fields->addFieldsToTab("Root.Settings", new CheckboxField('Active', 'Active'));
		$fields->addFieldsToTab("Root.Settings", new TextField('Link', 'Link'));
		$fields->addFieldsToTab("Root.Settings", new TextField('Sort', 'Sort order'));
		$fields->addFieldsToTab("Root.Settings", new ReadonlyField('PageID', 'Relation ID'));//TreeDropdownField("PageID", "This section belongs to:", "SiteTree"));		
		
		return $fields;
	}	

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		// Run on dev buld		
		
		// If templates does not exist on current theme, copy from module
		$theme = SSViewer::current_theme();
		$copyto    = "../themes/".$theme."/templates/SectionTemplates/";
		
		if(!file_exists($copyto)) {
			$copyfrom = BASE_PATH . "/sectionmodule/templates/SectionTemplates/";
			if(file_exists($copyfrom)) {
				$this->recurse_copy($copyfrom, $copyto);
				echo '<li style="color: green">SectionTemplates copied to: '.$copyto.'</li>';
			} else {
				echo "The default template archive was not found: " . $copyfrom;
			}
		}
		
		// Perhaps crate a sample page with the module in action?
/*		if(!DataObject::get_one('PageType')) {
		$pageType = new PageType();
		$pageType->write();
		} 
*/
	
	
	}	

	function recurse_copy($src,$dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					recurse_copy($src . '/' . $file,$dst . '/' . $file);
				}
				else {
					copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}
	
	public function getThumbnail() { 
		if ($this->Images()->Count() >= 1) {
			return $this->Images()->First()->croppedImage(50,40);
		}
	}	
	
	function forTemplate() {
		return $this->renderWith($this->Template);
	}

	// Returns only the file extension (without the period).
	function file_ext($filename) {
		if( !preg_match('/\./', $filename) ) return '';
		return preg_replace('/^.*\./', '', $filename);
	}
	
	// Returns the file name, less the extension.
	function file_ext_strip($filename){
		return preg_replace('/\.[^.]*$/', '', $filename);
	}	
	
}