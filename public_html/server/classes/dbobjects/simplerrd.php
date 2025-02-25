<?php

require 'inc_require.php';

class objsimplerrd extends axelhahn\pdo_db_base
{

    /**
     * Database description for Notifications 
     * @var array 
     */
    protected array $_aProperties = [
        'appid'          => ['create' => 'varchar(32)', 'index' => true,],
        'countername'    => ['create' => 'varchar(32)', 'index' => true,],
        'data'           => ['create' => 'text',],
    ];


    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }
}