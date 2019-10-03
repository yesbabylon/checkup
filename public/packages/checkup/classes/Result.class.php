<?php
namespace checkup;


class Result extends \qinoa\orm\Model {
    public static function getColumns() {
        return array (
		  'value' => 
              array (
                'type' => 'string'
              ),
		  'pass' => 
              array (
                'type' => 'boolean',
              ),
		  'report_id' => 
              array (
                'type'              => 'many2one',
                'foreign_object'    => 'checkup\Report',
                'ondelete'          => 'cascade'
              ),
		  'test_id' => 
              array (
                'type'              => 'many2one',
                'foreign_object'    => 'checkup\Test',
              ),
		  'category' => 
              array (
                'type'              => 'function',
                'result_type'       => 'string',
                'store'             => true,
                'function'          => 'checkup\Result::getCategory'
              ),              
		);
	}
    

    public static function getCategory($om, $oids, $lang) {
        $res = [];
        $results = $om->read(__CLASS__, $oids, ['test_id']);
        $tests_ids = array_map(function($a){return $a['test_id'];}, $results);
        $tests = $om->read('checkup\Test', $tests_ids, ['category_id']);
        $categories_ids = array_map(function($a){return $a['category_id'];}, $tests);
        $categories = $om->read('checkup\TestCategory', $categories_ids, ['name']);
        foreach($results as $oid => $odata) {            
            $res[$oid] = $categories[$tests[$odata['test_id']]['category_id']]['name'];
        }
        return $res;        
    }    
}
