<?php
namespace checkup;


class TestCategory extends \qinoa\orm\Model {
    public static function getColumns() {
        return array (
			'name' 				=> array ( 'type' => 'string'	  ),
			'description' 		=> array ( 'type' => 'text'	  ),
			'tests_ids'			=> array ( 'type' => 'one2many', 'foreign_object' => 'checkup\Test', 'foreign_field' => 'category_id')		  
		);
	}
}
