<?php
namespace checkup;


class Mailing extends \qinoa\orm\Model {
    public static function getColumns() {
      return [
      'date'      => [ 'type' => 'datetime' ],        
      'email'     => [ 'type' => 'string' ],
		  'report_id' => [
                      'type'              => 'many2one',
                      'foreign_object'    => 'checkup\Report'
                     ]
		  ];
	  }
}
