<?php
/*
    This file is part of the qinoa framework <http://www.github.com/cedricfrancoys/qinoa>
    Some Rights Reserved, Cedric Francoys, 2018, Yegen
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qinoa\orm;


class Domain {
    /*
    $domain = [ // domain
        [       // clause 
            [   // condition
                '{operand}', '{operator}', '{value}'
            ],         
            ['{operand}', '{operator}', '{value}'] 	// another contition (AND)   
        ],
        [		// another clause (OR)
            [	// condition
				'{operand}', '{operator}', '{value}'
			],
            ['{operand}', '{operator}', '{value}'] 	// another contition (AND)   			
        ]
    ];
*/  
    
    
    /*
    * domain checks and operations
    * a domain should always be composed of a serie of clauses against which a OR test is made
    * a clause should always be composed of a serie of conditions agaisnt which a AND test is made
    * a condition should always be composed of a property operand, an operator, and a value
    */
    
    /** 
     * Checks condition validity (format and consistency against schema)
     * operand is checked based on value/type compatibility                       
     *
     */
    private static function conditionCheck($condition, $schema=[]) {
        // condition must be an array
        if(!is_array($condition)) {
            return false;
        }
        // condition must be composed of 3 elements (field, operator, operand)
        if(count($condition) != 3) {
            return false;        
        }
        // we need to have access to class definition to fully check conditions
        if(!empty($schema)) {
            // first operand (field) must be a valid field
            if(!in_array($condition[0], array_keys($schema))) {
                return false;
            }
            $target_type = $schema[$condition[0]]['type'];
            if($target_type == 'function') {
                $target_type = $schema[$condition[0]]['result_type'];
            }
            // operator must be amongst valid operators for specified field
            if(!in_array($condition[1], ObjectManager::$valid_operators[$target_type])) {
                return false;
            }
        }
        return true;
    }

    private static function clauseCheck($clause, $schema=[]) {
        if(!is_array($clause)) return false;
        foreach($clause as $condition) {
            if(!self::conditionCheck($condition, $schema)) {
                return false;
            }
        }
        return true;
    }

    private static function domainCheck($domain, $schema=[]) {
        if(!is_array($domain)) return false;
        foreach($domain as $clause) {
            if(!self::clauseCheck($clause, $schema)) {
                return false;
            }
        }
        return true;
    }

    public static function normalize($domain) {
        if(!is_array($domain)) return [];
        if(!empty($domain)) {
            if(!is_array($domain[0])) {
                // single condition
                $domain = [[$domain]];
            }
            else {
                if(empty($domain[0])) return [];
                if(!is_array($domain[0][0])) {
                    // single clause
                    $domain = [$domain];
                }
            }
        }
        return $domain;
    }
        
    public static function validate($domain, $schema=[]) {
        $domain = self::normalize($domain);
        return self::domainCheck($domain, $schema);
    }
    
    public static function toString($domain) {
        $domain = self::normalize($domain);
        foreach($domain as $i => $clause) {
            foreach($clause as $j => $condition) {
                $clause[$j] = "['".implode("','", $condition)."']";
            }
            $domain[$i] = '['.implode(',', $clause).']';
        }
        return '['.implode(',', $domain).']';
    }

	/**
	 * Adds a condition to a clause
	 */
    public static function clauseConditionAdd($clause, $condition) {
        if(!self::conditionCheck($condition)) return $clause;
        $clause[] = $condition;
        return $clause;
    }
    
	/** 
	 * Adds a condition to the domain
	 *
	 * @return	array	resulting domain 
	 */
    public static function conditionAdd($domain, $condition) {
        if(!self::conditionCheck($condition)) return $domain;
        
        if(empty($domain)) {
            $domain[] = self::clauseConditionAdd([], $condition);
        }
        else {
			// add contion to all clauses
            for($i = 0, $j = count($domain); $i < $j; ++$i) {
                $domain[$i] = self::clauseConditionAdd($domain[$i], $condition);
            }
        }
        return $domain;
    }

	/** 
	 * Adds a clause to the domain
	 */
    public static function clauseAdd($domain, $clause) {
        if(!self::clauseCheck($clause)) return $domain;
        $domain[] = $clause;
        return $domain;
    }

}