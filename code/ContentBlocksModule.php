<?php
class ContentBlocksModule extends DataExtension {

	private static $create_block_tab = true;
	private static $contentarea_rows = 12;

	private static $db = array();

	private static $has_one = array();
	
	private static $many_many = array(
		'Blocks' => 'Block'
	);
	
	private static $many_many_extraFields=array(
        'Blocks'=>array('Sort'=>'Int')
    );
	
	public function updateCMSFields(FieldList $fields) {

		// Relation handler for Blocks		
		$SConfig = GridFieldConfig_RelationEditor::create(25);
		$SConfig->addComponent(new GridFieldOrderableRows());
		$SConfig->addComponent(new GridFieldDeleteAction());
		
		// If the copy button module is installed, add copy as option
		if (class_exists('GridFieldCopyButton')) {
	
			$SConfig->removeComponentsByType('GridFieldDetailForm');
	
			$SConfig->addComponent(new GridFieldDetailFormCustom());
			$SConfig->addComponent(new GridFieldCopyButton(), 'GridFieldDeleteAction');
		}

		$gridField = new GridField("Blocks", "Content blocks", $this->owner->Blocks(), $SConfig);
		
		$classes = array_values(ClassInfo::subclassesFor($gridField->getModelClass()));
		
		if (count($classes) > 1 && class_exists('GridFieldAddNewMultiClass')) {
			$gridFieldConfig->removeComponentsByType('GridFieldAddNewButton');
			$gridFieldConfig->addComponent(new GridFieldAddNewMultiClass());
		}
		
		if (self::$create_section_tab) {
			$fields->addFieldToTab("Root.Blocks", $gridField);
		} else {
			// Downsize the content field
			$fields->removeByName('Content');
			$fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content')->setRows(self::$contentarea_rows), 'Metadata');
			
			$fields->addFieldToTab("Root.Main", $gridField, 'Metadata');
		}
		
		return $fields;
	}

	public function ActiveBlocks() {
		return $this->owner->Blocks()->filter(array('Active' => '1'))->sort('Sort');
	}
	
	// Run on dev buld
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		// If css file does not exist on current theme, copy from module
		$copyfrom = BASE_PATH . "/".CONTENTBLOCKS_MODULE_DIR."/css/section.css";
		$theme = SSViewer::current_theme();
		$copyto    = "../themes/".$theme."/css/section.css";
		
		if(!file_exists($copyto)) {
			if(file_exists($copyfrom)) {
				copy($copyfrom,$copyto);
				echo '<li style="green: green">block.css copied to: ' . $copyto . '</li>';
			} else {
				echo '<li style="red">The default css file was not found: ' . $copyfrom . '</li>';
			}
		}
	}	
	
	public function contentcontrollerInit($controller) {
		Requirements::themedCSS('block');
	}
}