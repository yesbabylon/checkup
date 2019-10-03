<?php
use qinoa\orm\ObjectManager;
use qinoa\http\HttpRequest;
use core\User;
use core\Group;

$providers = inject(['context', 'orm', 'auth', 'access']);

$tests = [
    //0xxx : calls related to QN methods
    '0101' => array(
                'description'       =>  "Get auth provider",
                'return'            =>  array('boolean'),
                'expected'          =>  true,
                'test'              =>  function (){
                                            list($params, $providers) = announce([
                                                'providers' => ['qinoa\auth\AuthenticationManager']
                                            ]);
                                            $auth = $providers['qinoa\auth\AuthenticationManager'];
                                            return ( is_object($auth) && ($auth instanceof qinoa\auth\AuthenticationManager) );
                                        },

                ),
    '0102' => array(
                'description'       =>  "Get auth provider using custom registered name",
                'return'            =>  array('boolean'),
                'expected'          =>  true,
                'test'              =>  function (){
                                            list($params, $providers) = announce([
                                                'providers' => ['@@testAuth' => 'qinoa\auth\AuthenticationManager']
                                            ]);
                                            $auth = $providers['@@testAuth'];
                                            return ( is_object($auth) && ($auth instanceof qinoa\auth\AuthenticationManager));
                                        },

                ),

    //1xxx : calls related to the ObjectManger instance
    '1000' => array(
                'description'       => "Get instance of the object Manager",
                'return'            => array('boolean'),
                'expected'          => true,
                'test'              => function (){
                                            $om = &ObjectManager::getInstance();
                                            return (is_object($om) && ($om instanceof qinoa\orm\ObjectManager));
                                        },
                ),

    '1100' => array(
                'description'       =>  "Check uniqueness of ObjectManager instance",
                'return'            =>  array('boolean'),
                'expected'          =>  true,
                'test'              =>  function (){
                                            $om1 = &ObjectManager::getInstance();
                                            $om2 = &ObjectManager::getInstance();
                                            return ($om1 === $om2);
                                        },
                ),

    //21xx : calls related to the read method
    // @signature   function read($uid, $class, $ids, $fields=NULL, $lang=DEFAULT_LANG)
    // @return      mixed (int or array) error code OR resulting associative array

    '2100' => array(
                'description'       =>  "Requesting User object by passing an id array holding a unique id",
                'return'            =>  array('integer', 'array'),
                'expected'          =>  array(
                                        '1' => array(
                                                'language'  => 'en',
                                                'firstname' => 'root',
                                                'lastname'  => '@system'
                                              )
                                        ),
                'test'              =>  function (){
                                            $om = &ObjectManager::getInstance();
                                            return $om->read('core\User', [ROOT_USER_ID], array('language','firstname','lastname'));
                                        },
                ),

    '2101' => array(
                    'description'       =>  "Requesting User object by passing an integer as id",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(
                                            '1' => array(
                                                    'language'  => 'en',
                                                    'firstname' => 'root',
                                                    'lastname'  => '@system'
                                                  )
                                            ),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', ROOT_USER_ID, array('language','firstname','lastname'));
                                            },
                    ),
    '2102' => array(
                    'description'       =>  "Requesting User object by pasing a string as id",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(
                                            '1' => array(
                                                    'language'  => 'en',
                                                    'firstname' => 'root',
                                                    'lastname'  => '@system'
                                                  )
                                            ),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', (string) ROOT_USER_ID, array('language','firstname','lastname'));
                                            },
                    ),

    '2103' => array(
                    'description'       =>  "Requesting User object by giving a non-existing integer id",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', 0, array('language','firstname','lastname'));
                                             },
                    ),

    '2104' => array(
                    'description'       =>  "Requesting User object by passing an array containing an invalid id",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(
                                            '1' => [
                                                    'language'  => 'en',
                                                    'firstname' => 'root',
                                                    'lastname'  => '@system'
                                                   ]
                                            ),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', array(0, ROOT_USER_ID), array('language','firstname','lastname'));
                                            },
                    ),

    '2105' => array(
                    'description'       =>  "Call ObjectManager::read with empty value for \$ids parameter : empty array",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', array(), array('language','firstname','lastname'));
                                            },
                    ),

    '2110' => array(
                    'description'       =>  "Call ObjectManager::read with missing \$ids parameters",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  QN_ERROR_MISSING_PARAM,
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User');
                                            },
                    ),
    '2120' => array(
                    'description'       =>  "Call ObjectManager::read with wrong \$ids parameters",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', 0);
                                            },
                    ),
    '2130' => array(
                    'description'       =>  "Call ObjectManager::read some unexisting object from non-existing class",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  QN_ERROR_UNKNOWN_OBJECT,
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\Foo', array('1'), array('bar'));
                                            },
                    ),

    '2140' => array(
                    'description'       =>  "Call ObjectManager::read with a string as field",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(
                                            '1' => array(
                                                    'firstname' => 'root'
                                                  )
                                            ),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', array('1'), 'firstname');
                                            },
                    ),
    '2150' => array(
                    'description'       =>  "Call ObjectManager::read with wrong \$fields value : unexisting field name",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  [
                                                '1' => []
                                            ],
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', array('1'), array('foo'));
                                            },
                    ),
    '2151' => array(
                    'description'       =>  "Call ObjectManager::read with wrong \$fields value : unexisting field name",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array('1' => array('firstname' => 'root') ),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->read('core\User', array('1'), array('foo', 'firstname'));
                                            },
                    ),


    //22xx : calls related to the create method
    '2201' => array(
                    'description'       =>  "Create a user (no validation)",
                    'return'            =>  array('integer'),
                    'test'              =>  function () {
                                                global $dummy_user_id;
                                                $om = &ObjectManager::getInstance();
                                                $dummy_user_id = $om->create('core\User', [
                                                                                'login'     => 'dummy@example.com',
                                                                                'password'  => md5('test'),
                                                                                'firstname' => 'foo',
                                                                                'lastname'  => 'bar'
                                                                                ]);
                                                return $dummy_user_id;
                                            },
                    ),

    //23xx : calls related to the write method

    //24xx : calls related to the remove method
    '2401' => array(
                    'description'       =>  "Remove a user (no validation)",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(&$GLOBALS['dummy_user_id']),
                    'test'              =>  function () {
                                                global $dummy_user_id;
                                                $om = &ObjectManager::getInstance();
                                                return $om->remove('core\User', $dummy_user_id, true);
                                            },
                    ),

    //25xx : calls related to the search method
    // @signature : public function search($object_class, $domain=NULL, $order='id', $sort='asc', $start='0', $limit='0', $lang=DEFAULT_LANG) {
    // @return : mixed (integer or array)
    '2501' => array(
                    'description'       =>  "Search an object with valid clause 'ilike'",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array('1'),
                    'test'              =>  function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->search('core\Group', array(array(array('name', 'ilike', '%Default%'))));
                                            }
                    ),
    '2502' => array(
                    'description'       => "Search an object with invalid clause 'ilike' (non-existing field)",
                    'return'            => array('integer', 'array'),
                    'expected'          => QN_ERROR_INVALID_PARAM,
                    'test'              => function (){
                                                $om = &ObjectManager::getInstance();
                                                return $om->search('core\Group', array(array(array('badname', 'ilike', '%Default%'))));
                                            }
                    ),
    '2510' => array(
                    'description'       =>  "Search for some object : clause 'contains' on one2many field",
                    'return'            =>  array('boolean'),
                    'expected'          =>  true,
                    'test'              =>  function (){
// todo
                                                return true;
                                            },
                    ),
    '2520' => array(
                    'description'       =>  "Search for some object : clause 'contains' on one2many field (using a foreign key different from 'id')",
                    'return'            =>  array('boolean'),
                    'expected'          =>  true,
                    'test'              =>  function (){
// todo
                                                return true;
                                            }
                    ),
    '2530' => array(
                    'description'       =>  "Search for some object : clause 'contains' on many2one field",
                    'return'            =>  array('boolean'),
                    'expected'          =>  true,
                    'test'              =>  function (){
// todo
                                                return true;
                                            }
                    ),

    '2540' => array(
                    'description'       =>  "Search for some object : clause contain on many2many field",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(1 => ['login' => 'admin'], 2 => ['login' => 'cedricfrancoys@gmail.com']),
                    'test'              =>  function () use($providers) {
                                                try {
                                                    $providers['auth']->authenticate('cedricfrancoys@gmail.com', '02e5408967241673cd03126fe55dcd1a');
                                                    $providers['access']->grant(QN_R_READ);

                                                    $values = User::search(array(array('groups_ids', 'contains', array(1, 2, 3))))
                                                          ->read(['login'])
                                                          ->get();
                                                }
                                                catch(Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_NOT_ALLOWED
                                                    $values = $e->getCode();

                                                }
                                                return $values;
                                            }
                    ),

    // 3xxx methods : related to Collections calls
    '3001' => array(
                    'description'       =>  "Check uniqueness of services instances",
                    'return'            =>  array('boolean', 'array'),
                    'expected'          =>  true,
                    'test'              =>  function () use($providers) {

                                                $auth1 = $providers['context']->container->get('auth');
                                                $auth2 = $providers['auth'];

                                                $access1 = $providers['context']->container->get('access');
                                                $access2 = $providers['access'];


                                                return ( $auth1 == $auth2 && $access1 == $access2);
                                            }
                    ),

    '3101' => array(
                    'description'       =>  "Search for an existing user object using Collection (result as map)",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array('2' => ['login' => 'cedricfrancoys@gmail.com']),
                    'test'              =>  function () {
                                                try {

                                                    $values = User::search(['login', 'like', 'cedricfrancoys@gmail.com'])
                                                              ->read(['login'])
                                                              ->get();
                                                }
                                                catch(\Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_NOT_ALLOWED
                                                    $values = $e->getCode();
                                                }
                                                return $values;
                                            }
                    ),
    '3102' => array(
                    'description'       =>  "Search for an existing user object using Collection (result as array)",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(['login' => 'cedricfrancoys@gmail.com']),
                    'test'              =>  function () {
                                                try {
                                                    $values = User::search(['login', '=', 'cedricfrancoys@gmail.com'])
                                                              ->read(['login'])
                                                              ->get(true);
                                                }
                                                catch(\Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_NOT_ALLOWED
                                                    $values = $e->getCode();
                                                }
                                                return $values;
                                            }
                    ),

    '3103' => array(
                    'description'       =>  "Search for a new user object using Collection (result as array)",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  array(['login' => 'test@equal.run']),
                    'arrange'           =>  function() use($providers) {
                                                try {
                                                    $providers['access']->grant(QN_R_CREATE|QN_R_DELETE);
                                                    $values = User::create(['login' => 'test@equal.run', 'password' => md5('test'), 'firstname', 'lastname'])->ids();
                                                }
                                                catch(\Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_NOT_ALLOWED
                                                    $values = $e->getCode();
                                                }
                                                return $values;
                                            },
                    'rollback'          =>  function() use($providers) {
                                                $om = $providers['orm'];
                                                $ids = $om->search('core\User', [['login', '=', 'test@equal.run']]);
                                                $om->remove('core\User', $ids, true);
                                                $providers['access']->revoke(QN_R_CREATE|QN_R_DELETE);
                                            },
                    'test'              =>  function () {
                                                try {
                                                    $values = User::search(['login', '=', 'test@equal.run'])
                                                              ->read(['login'])
                                                              ->get(true);
                                                }
                                                catch(\Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_NOT_ALLOWED
                                                    $values = $e->getCode();
                                                }
                                                return $values;
                                            }
                    ),

    '4101' => array(
                    'description'       =>  "HTTP basic auth",
                    'return'            =>  array('integer', 'array'),
                    'expected'          =>  [
                                                "login" => "cedricfrancoys@gmail.com",
                                                "firstname"=> "Cédric",
                                                "lastname"=> "FRANÇOYS",
                                                "language"=> "fr"
                                            ],
                    'test'              =>  function () {
                                                try {
                                                    /*
                                                    Test HttpRequest
                                                    $oauthRequest = new HttpRequest('/plus/v1/people/me', ['Host' => 'www.googleapis.com:443']);
                                                    $res = $oauthRequest->send();
                                                    */
                                                    $request = new HttpRequest("http://localhost/me");                                              
                                                    $response = $request
                                                                ->header('Authorization', 'Basic '.base64_encode("cedricfrancoys@gmail.com:02e5408967241673cd03126fe55dcd1a"))
                                                                ->send();
                                                    return $response->body();
                                                }
                                                catch(\Exception $e) {
                                                    // possible raised Exception codes : QN_ERROR_INVALID_USER
                                                    $values = $e->getCode();
                                                }
                                                return $values;
                                            }
                    ),
                    
                    
];