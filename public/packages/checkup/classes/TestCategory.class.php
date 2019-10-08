<?php
namespace checkup;


class TestCategory extends \qinoa\orm\Model {
    public static function getColumns() {
        return array (
			'name' 				=> ['type' => 'string', 'description' => 'unique mnemonic code for the category'],
			'title' 			=> ['type' => 'string', 'decription' => 'human readable title'],
			'description' 		=> ['type' => 'text'],
			'tests_ids'			=> ['type' => 'one2many', 'foreign_object' => 'checkup\Test', 'foreign_field' => 'category_id']
		);
	}
}
