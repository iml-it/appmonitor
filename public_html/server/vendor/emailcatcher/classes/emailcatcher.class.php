<?php
/**
 * =======================================================================
 * 
 * PHP EMAIL CATCHER
 * Read emails sent by mail() and browse them
 * 
 * 👤 Author: Axel Hahn, Institute for Medical Education, University of Bern
 * 📄 Source: <https://git-repo.iml.unibe.ch/iml-open-source/php-emailcatcher>
 * 📗 Docs: <https://os-docs.iml.unibe.ch/php-emailcatcher/>
 * 📜 License: GNU GPL 3.0
 * 
 * ----------------------------------------------------------------------
 * 2024-10-08  v0.1  initial version
 * 2024-10-16  v0.2  detect parse error when reading email data
 * =======================================================================
 */

class emailcatcher
{

    /**
     * Filename for email data
     * @var string
     */
    protected string $sMailData='emaildata.txt';

    /**
     * Id of selected email
     * @var string
     */
    protected string $sId='';

    /**
     * Email data of the selected email
     * @var array
     */
    protected array $aSelectedEmail=[];

    // ------------------------------------------------------------------

    public function __construct()
    {
        $this->sMailData=dirname(__DIR__) . '/data/' . $this->sMailData;
    }

    // ------------------------------------------------------------------
    // STORE NEW EMAIL
    // ------------------------------------------------------------------

    /**
     * Fetch email of a single email from stdin and store it.
     * It returns the return value of file_put_contents().
     * used in php-sendmail.php
     * 
     * @return bool|int
     */
    public function catchEmail(){
        $fp = fopen('php://stdin', 'rb');

        $sMaildata='';
        while (!feof($fp)) {
            $sMaildata.=fgets($fp, 4096);
        }
        fclose($fp);

        return $this->storeEmail($sMaildata);
    }

    /**
     * Store a new email. 
     * It returns the return value of file_put_contents().
     * 
     * @param string $sMaildata  maildata with header and body
     * @return bool|int
     */
    public function storeEmail($sMaildata): bool|int
    {
        $sLogentry=json_encode(["date"=>date("Y-m-d H:i:s"), "mail"=>$sMaildata])."\n";
        return file_put_contents($this->sMailData, $sLogentry, FILE_APPEND | LOCK_EX);
    }

    // ------------------------------------------------------------------
    // READ STORED DATA
    // ------------------------------------------------------------------


    /**
     * Read all stored emails and return them as an array
     * @param array $aFilter  optional: filter items; valid keys are 
     *                         - "id"     {string}  of email to show
     *                         - "search" {string}  text to find
     * @return array
     */
    protected function _readEmails(array $aFilter = []): array
    {
        $sEmail2Show=$aFilter['id'] ?? '';
        $sSearch=$aFilter['search'] ?? '';

        if(!file_exists($this->sMailData)){
            return [];
        }
        foreach(file($this->sMailData) as $line) {

            if (empty(trim($line))) {
                continue;
            }
            $aLinedata=json_decode($line, true);
            if(!is_array($aLinedata)){
                // echo "ERROR: unable to parse line as single json object: <code>$line</code>";
                continue;
            }
            [$sHead, $sBody] = explode("\r\n\r\n", $aLinedata['mail']);
            
            $sHead="\n$sHead";
            preg_match('/\nfrom: (.*)/i', $sHead, $aFrom);
            preg_match('/\nto: (.*)/i', $sHead, $aTo);
            preg_match('/\ncc: (.*)/i', $sHead, $aCc);
            preg_match('/\nbcc: (.*)/i', $sHead, $aBcc);
            preg_match('/\nsubject: (.*)/i', $sHead, $aSubject);
            preg_match('/\Content-Type: (.*)/i', $sHead, $aContentType);

            $aEntry=[
                "date" => $aLinedata['date'],
                "from" => $aFrom[1] ?? false,
                "to" => $aTo[1] ?? false,
                "cc" => $aCc[1] ?? false,
                "bcc" => $aBcc[1] ?? false,
                "subject" => $aSubject[1] ?? false,
                "contentType" => $aContentType[1] ?? false,
            ];
            $sId=md5($aEntry['date'].' - to '.$aEntry['to'].': '.$aEntry['subject']);
            $aEntry['id']=$sId;

            if($sId==$sEmail2Show){
                $aEntry['head']=$sHead;
                $aEntry['body']=$sBody;
                return $aEntry;                    
            }
            $aEmails[$aLinedata['date']]=$aEntry;
        }
        krsort($aEmails);
        return $aEmails;
    }

    /**
     * Get a list of emails to render an inbox like selection.
     * It doesn't contain header and body - just metadata
     * 
     * @return array
     */
    public function readEmails(): array
    {
        return $this->_readEmails();
    }

    // ------------------------------------------------------------------
    // SEARCH
    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // SINGLE EMAIL
    // ------------------------------------------------------------------

    /**
     * Set a single email by id.
     * It returns a bool for success: false = failed
     * 
     * @param string $sId
     * @return bool
     */
    public function setId(string $sId): bool{
        $this->sId=$sId;
        $this->aSelectedEmail=$this->_readEmails(['id' => $sId]);

        if(! isset($this->aSelectedEmail['id'])){
            $this->sId='';
            $this->aSelectedEmail=[];
            return false;
        }
        return true;
    }

    /**
     * Get hash for a single email with all metadata and body
     * @param string  $sEmail2Show  optional: email id to show
     * @return array
     */
    public function getEmail($sEmail2Show=''): array
    {
        if($sEmail2Show){
            $this->setId($sEmail2Show);
        }
        return $this->aSelectedEmail;
    }

    /**
     * Get a Meta value of the selected email
     * @param string $sField
     * @return mixed
     */
    public function getField(string $sField): mixed
    {
        return $this->aSelectedEmail[$sField] ?? null;
    }

    /**
     * Get message body of the selected email
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->getField('body');
    }

    /**
     * get message header of the selected email
     * @return mixed
     */
    public function getHeader(): mixed
    {
        return $this->getField('head');
    }
}