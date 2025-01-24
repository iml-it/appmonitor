<?php
namespace axelhahn;

require_once 'pdo-db-base.class.php';

class pdo_db_attachments extends pdo_db_base{

    protected string $_sUploadDir='';
    protected string $_sUrlBase='';

    /**
     * hash for a table
     * create database column, draw edit form
     * @var array 
     */
    protected array $_aProperties = [
        'filename'       => ['create' => 'VARCHAR(255)','overview'=>1,],
        'mime'           => ['create' => 'VARCHAR(32)',],
        'description'    => ['create' => 'VARCHAR(2048)',],
        'size'           => ['create' => 'INTEGER',],
        'width'          => ['create' => 'INTEGER',],
        'height'         => ['create' => 'INTEGER',],
    ];

    public function __construct(object $oDB)
    {
        parent::__construct(__CLASS__, $oDB);
    }

    // ----------------------------------------------------------------------
    //
    // CUSTOM METHODS
    //
    // ----------------------------------------------------------------------


    // ----------------------------------------------------------------------
    // SETTER
    // ----------------------------------------------------------------------

    /**
     * Sets the upload directory for the file attachments.
     *
     * @param string $sDir The directory path to set as the upload directory.
     * @return bool Returns true if the directory is set successfully, false otherwise.
     */
    public function setUploadDir(string $sDir) :bool{
        if(!is_dir($sDir)){
            $this->_log(PB_LOGLEVEL_ERROR, __METHOD__ . '('.$sDir.')', 'Given directory not found.');
            return false;
        }
        $this->_sUploadDir = $sDir;
        return true;
    }

    /**
     * Sets the base URL for the file attachments.
     *
     * @param string $sUrl The base URL to set.
     * @return bool
     */
    public function setUrlBase(string $sUrl) :bool {
        $this->_sUrlBase = $sUrl;
        return true;
    }

}