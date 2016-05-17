<?php namespace Rebates\Core\Response;
use Rebates\Core\Codes as Codes;

/*
 * The response class cluster deals specifically with interpretting the data returned 
 * from any request to the SR API. Both the ContactService API and the original
 * GET API have their own parsers that adheres to the Parser contract
 * 
 */

/*
 * Contract that deals with the returning of data from the SR Response as well
 * as post filter validation and normalizing the response so that there is a standard
 * response format for both SR APIs.
 * 
 */
interface IResponseParser {

    public function getResponse();

    public function hasWarnings();

    public function hasErrors();

    public function getWarnings();

    public function getErrors();

    public function setValidChannels($channels);

    public function getValidChannels();

    public function getNormalizedResponse($cID = '');
}

/*
 * Response base class deals heavily with parsing the SR Response and fulfilling 
 * the various get methods in the IResponseParser contract for returning that
 * data 
 */
abstract class AbstractResponseParser {

    protected $response;
    protected $errors;
    protected $warnings;
    protected $state = false;
    protected $contacts;

    public function getResponse() {
        return $this->response;
    }

    public function hasWarnings() {
        return !empty($this->warnings);
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function getWarnings() {
        return $this->warnings;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getState() {
        return $this->state;
    }

    public function getContactData() {
        return $this->contacts;
    }

    public function setValidChannels($channels) {
        $this->validChannel = $channels;
        return $this;
    }

    public function getValidChannels() {
        return $this->validChannel;
    }

    abstract protected function getBIN($contactID = 0);

    abstract protected function getPCN($contactID = 0);

    abstract protected function getGRP($contactID = 0);

    abstract protected function getUID($contactID = 0);

    abstract protected function parse();
}

/*
 * Class expects a JSON object passed to it, where it is then parsed and it's data 
 * used to fulfill the get methods.
 */
class LoebContactResponseParser extends AbstractResponseParser implements IResponseParser, Codes\ICodes {

    private $sentEmail;
    private $sentSMS;
    private $userIPLocation;
    private $prospectID;
    private $contactDataByID;

    function __construct($response) {
        $this->response = $response;
        $this->parse();
    }

    protected function parse() {
        if ($this->response) {
            $this->warnings = $this->response->Warnings;
            $this->errors = $this->response->Errors;
            $this->state = $this->response->Success;
            $this->contacts = $this->response->Contacts;
            $this->sentEmail = $this->response->SentEmail;
            $this->sentSMS = $this->response->SentSMS;
            $this->userIPLocation = $this->response->IPLocation;
            $this->prospectID = $this->response->ProspectId;
            $this->splitContact();
        }
    }

    public function getUserLocation() {
        return $this->userIPLocation;
    }

    public function getSentSMS() {
        return $this->sentSMS;
    }

    public function getSentEmail() {
        return $this->sentEmail;
    }

    public function getProspectID() {
        return $this->prospectID;
    }

    private function splitContact() {
        if (!empty($this->contacts) && is_array($this->contacts)) {
            foreach ($this->contacts as $contact)
                $data[$contact->ContactTypeId] = $contact;
            $this->contactDataByID = $data;
        }
    }

    private function getContactDetail($cID, $detail) {
        if (isset($this->contactDataByID[$cID]->$detail)) {
            return $this->contactDataByID[$cID]->$detail;
        } else
            return false;
    }

    public function getPCN($cID = 0) {
        return $this->getContactDetail($cID, 'PCN');
    }

    public function getGRP($cID = 0) {
        return $this->getContactDetail($cID, 'GroupNumber');
    }

    public function getBIN($cID = 0) {
        return $this->getContactDetail($cID, 'BIN');
    }

    public function getUID($cID = 0) {
        return $this->getContactDetail($cID, 'MemberNumber');
    }

    public function getAllContactDataFor($detail) {
        if (is_array($this->contacts) && !empty($this->contacts)) {
            foreach ($this->contacts as $contact)
                $data[$contact->ContactTypeId] = $contact->$detail;
            return $data;
        }
    }

    public function getContactByID($cID) {
        return !empty($this->contactDataByID[$cID]) && $this->contactDataByID[$cID];
    }

    public function getNormalizedResponse($cID = '') {
        if ($cID === '' && !empty($this->contactDataByID))
            $cID = key($this->contactDataByID);
        if ($this->response) {
            $response = new \stdClass();
            $response->BIN = $this->getBIN($cID);
            $response->GroupNumber = $this->getGRP($cID);
            $response->PCN = $this->getPCN($cID);
            $response->MemberNumber = $this->getUID($cID);
            $response->ErrorDetails = $this->getErrors();
            $response->WarningDetails = $this->getWarnings();
            $response->Success = $this->getState();
            $response->validData = $this->getValidChannels();
            return $response;
        }
    }

}
