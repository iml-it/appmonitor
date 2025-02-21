<?php

require 'inc_require.php';

class objwebapps extends axelhahn\pdo_db_base
{

    /**
     * Database description for Notifications 
     * @var array 
     */
    protected array $_aProperties = [
        'appid'       => ['create' => 'varchar(32)', 'index' => true],
        // 'expires'     => ['create' => 'datetime',],     // weg, wenn ich weiterhin ahcache benutze
        // 'result'      => ['create' => 'text',],         // auch weg
        'lastresult'  => ['create' => 'text',],
        'lastok'      => ['create' => 'text',],

        // store information to access on failure
        // 'checks'       => ['create' => 'text',],
        // 'notification' => ['create' => 'text',],
        'tags'         => ['create' => 'text',],
    ];


    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }
}