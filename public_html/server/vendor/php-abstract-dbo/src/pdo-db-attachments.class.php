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
        'label'          => ['create' => 'VARCHAR(255)','overview'=>1,],
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


    /**
     * Get array of all hook actions
     * Keys:
     *   - backend_preview   {string}  method name for preview in AxelOM
     *   - <any>             {string}  method name four your custom action
     * 
     * @return array
     */
    public function hookActions(): array
    {
        return [
            'backend_preview' => 'attachmentPreview',
            'backend_upload' => 'attachmentPreview',
            'delete' => 'hookDelete',
        ];
    }


    // ----------------------------------------------------------------------
    // FULE UPLOAD
    // ----------------------------------------------------------------------

    /**
     * Upload files given by $_FILES from a form and optionally create a
     * relation to a given target object
     * 
     * see pages/object.php
     * 
     * @param array $aFile  single array element of $_FILES which means
     *                      - name      {string} => filename, eg. my-image.png
     *                      - full_path {string} => ignored 
     *                      - type      {string} => MIME type eg. image/png 
     *                      - tmp_name  {string} => location of uploaded file eg. /tmp/php2hi7k4315in34bgFjGz/tmp/php2hi7k4315in34bgFjGz 
     *                      - error     {int}    => error code, eg 0 for OK
     *                      - size      {int}    => filesize in byte eg. 312039
     * @return void
     */
    public function uploadFile(array $aFile = []): bool|int{
        // print_r($aFile);
        if(!($aFile['tmp_name']??false)){
            return false;
        }
        
        $sTargetFolder=date("Y/m/d");
        $sTargetFilemae=md5($aFile["name"].microtime(true)).'.'.pathinfo($aFile["name"], PATHINFO_EXTENSION);;
        $target_file=$this->_sUploadDir.'/'.$sTargetFolder. '/'.$sTargetFilemae;
        if($aFile['error']==0){
            if(!is_dir($this->_sUploadDir.'/'.$sTargetFolder)){
                if(!mkdir($this->_sUploadDir.'/'.$sTargetFolder, 0750, true)){
                    $this->_log(
                        PB_LOGLEVEL_ERROR, 
                        __METHOD__, 
                        "Unable to create target folder for upload file [$sTargetFolder]."
                    );
                }
            }
            if (move_uploaded_file($aFile["tmp_name"], $target_file)) {
                $this->new();
                $this->setItem([
                    'label'=>basename($aFile["name"]),
                    'filename'=>$sTargetFolder. '/'.$sTargetFilemae,
                    'mime'=>$aFile["type"],
                    'description'=>'',
                    'size'=>$aFile["size"],
                    'width'=>NULL,
                    'height'=>NULL,
                ]);
                if($this->save()){
                    return $this->id();
                } else {
                    $this->_log(
                        PB_LOGLEVEL_ERROR, 
                        __METHOD__, 
                        "Unable to save uploaded file object in [$this->_table]."
                    );
                    unlink($target_file);
                }
            } else {
                $this->_log(
                    PB_LOGLEVEL_ERROR, 
                    __METHOD__, 
                    "Unable to move uploaded file in temp dir to [$target_file]."
                );
            }
        } else {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__, 
                "Upload failed. Error in given file array. ".print_r($aFile, true)
            );
        }
        return false;
    }

    /**
     * Hook for delete() method
     * Delete local file before deleting database item
     * It returns false if the file does not exist or the deletion failed.
     * 
     * @return bool
     */
    protected function hookDelete(): bool
    {
        $sFile=$this->get('filename');
        $target_file="$this->_sUploadDir/$sFile";
        if(file_exists($target_file)){
            if(unlink($target_file)){
                return true;
            };
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__, 
                "Deletion of existing file failed: [$target_file]"
            );
        } else {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__, 
                "Cannot delete non existing file: [$target_file] - maybe you need to set \$o->setUploadDir([BaseDir]); first."
            );
            return true;
        }
        return false;
    }

    /**
     * Get html code for attachment preview
     * 
     * @param array  $aOptions  array of options; known keys:
     *                          - baseurl {string}  set base url for attachments that triggers -> setUrlBase(<baseurl>)
     * 
     * @return string
     */
    public function attachmentPreview(array $aOptions = []): string
    {
        $sReturn = '';
        $aFile = $this->getItem();
        if (!$aFile) {
            $this->_log(PB_LOGLEVEL_ERROR, __METHOD__, 'Item not found. Use read(<ID>) first.');
            return $sReturn;
        }
        if((int)$aFile['id']<1){
            // there is no object for preview
            return false;
        }
        if($aOptions['baseurl']??false){
            $this->setUrlBase($aOptions['baseurl']);
        }

        $sAttachmentUrl=$this->_sUrlBase . '/' . $aFile['filename'];

        switch($aFile['mime']){
            // ---- audio
            case 'audio/mpeg':
            case 'audio/ogg':
            case 'audio/wav':
                $sReturn .= "<audio controls><source src=\"$sAttachmentUrl\" type=\"$aFile[mime]\"></audio>";
                break;

            // ---- images
            case 'image/gif':
            case 'image/jpeg':
            case 'image/png':
                $sReturn .= "<img src=\"$sAttachmentUrl\" alt=\"$aFile[filename]\">";
                break;

            // ---- video
            case 'video/mp4':
            case 'video/ogg':
                $sReturn .= "<video controls><source src=\"$sAttachmentUrl\" type=\"$aFile[mime]\"></video>";
                break;
        }

        $sReturn.=''
            . ($sReturn ? '<br><br>' : '')
            . '<a href="' . $sAttachmentUrl . '" target="_blank">' . basename($aFile['filename']) . '</a><br>'
            .$aFile['mime'].'<br>'
            ;

        return $sReturn ? "<div class=\"preview\">$sReturn</div>" : '';
    }

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
     * Sets the base URL for the file attachments
     *
     * @param string $sUrl The base URL to set.
     * @return bool
     */
    public function setUrlBase(string $sUrl) :bool {
        $this->_sUrlBase = $sUrl;
        return true;
    }

}