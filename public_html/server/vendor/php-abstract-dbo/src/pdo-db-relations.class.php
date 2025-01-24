<?php
namespace axelhahn;

class pdo_db_relations extends pdo_db_base{

    /**
     * hash for a table
     * create database column, draw edit form
     * @var array 
     */
    protected array $_aProperties = [
        // 'label'       => ['create' => 'TEXT',     'label' => 'Label',                 'descr' => '', 'type' => 'text',           'edit' => true, 'required' => true],
        'from_table'       => ['create' => 'VARCHAR(32)',   ],
        'from_id'          => ['create' => 'INTEGER',],
        'from_column'      => ['create' => 'VARCHAR(32)',],
        'to_table'         => ['create' => 'VARCHAR(32)',   ],
        'to_id'            => ['create' => 'INTEGER',],
        'to_column'        => ['create' => 'VARCHAR(32)',],
        'uuid'             => ['create' => 'TEXT NOT NULL UNIQUE',],
        'remark'           => ['create' => 'TEXT',   ],
        # ^                            ^                      ^                                                 ^
        # db column                    sql create             label in editor                                   input type in editor

    ];

    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }
}