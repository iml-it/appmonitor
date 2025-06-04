<?php
namespace axelhahn;

class pdo_db_relations extends pdo_db_base{

    /**
     * hash for a table
     * create database column, draw edit form
     * @var array 
     */
    protected array $_aProperties = [
        'from_table'       => ['create' => 'VARCHAR(32)',   'index'=>1,],
        'from_id'          => ['create' => 'INTEGER',       'index'=>1,],
        'from_column'      => ['create' => 'VARCHAR(32)',   'index'=>1,],
        'to_table'         => ['create' => 'VARCHAR(32)',   'index'=>1,],
        'to_id'            => ['create' => 'INTEGER',       'index'=>1,],
        'to_column'        => ['create' => 'VARCHAR(32)',   'index'=>1,],
        'uuid'             => ['create' => 'TEXT NOT NULL UNIQUE',     ],
        'remark'           => ['create' => 'TEXT',                     ],

    ];

    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }
}