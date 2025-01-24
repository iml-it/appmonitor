<?php

require 'inc_require.php';

class objnotifications extends axelhahn\pdo_db_base
{

    /**
     * Database description for Notifications 
     * @var array 
     */
    protected array $_aProperties = [
        'timestamp'   => ['create' => 'int',],
        'appid'       => ['create' => 'varchar(32)',],
        'changetype'  => ['create' => 'int',],
        'status'      => ['create' => 'int',],
        'message'     => ['create' => 'text',],
        'result'      => ['create' => 'text',], // appresult array ... not used yet - maybe we remove it.
    ];


    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }
}