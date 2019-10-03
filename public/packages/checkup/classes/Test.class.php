<?php
namespace checkup;


class Test extends \qinoa\orm\Model {
    public static function getColumns() {
        return array (
		  'description' 	=> 	 array ( 'type' => 'text' ),
		  'name' 			=> 	 array ( 'type' => 'string' ),
          'type' 			=> 	 array ( 'type' => 'string', 'selection' => ['bool', 'integer', 'float', 'string'] ),
		  'category_id'		=> 	 array ( 'type' => 'many2one', 'foreign_object' => 'checkup\TestCategory'),
		);
	}
}