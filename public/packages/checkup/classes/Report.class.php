<?php
namespace checkup;


class Report extends \qinoa\orm\Model {
    public static function getColumns() {
        return [
		  'date'    => [ 'type' => 'datetime' ],
		  'domain'  => [ 'type' => 'string' ],
          'url'     => [ 'type' => 'string' ],
          'content' => [ 'type' => 'text' ],
		  'email'   => [ 'type' => 'string' ],          
		  'results_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'checkup\Result',
                'foreign_field'     => 'report_id'
		  ]
		];
	}
}
