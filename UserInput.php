<?php

namespace Rebates\Core\UserInput;
use Rebates\Core\Mobile as Mobile;

/*
 * Interface for IUMagic actions $_GET, $_SET, etc
 * Revised: Magic methods removed, getFormInputs preserved. 
 */

interface IUIMagic {

    public function getFormInputs();
}

/*
 * Interface for Stringify actions and the method associated.
 * Stringify converts all user inputted datasources from arrays to string
 */

interface IUIStringify {

    public function stringify();
}

/*
 * Interface for encoding actions
 * Encoding deals with html encoding user inputted data and getting that datae
 */

interface IEncode {

    public function encode($encoded = []);

    public function getEncoded($var);

    public function getDecoded($var);
}

/*
 * Abstracted class that governs all user input whether it be cookie, post or get data
 */

abstract class AbstractUserInput {

    // Form Inputs contains the data type being parsed
    protected $formInputs;

    /*
     * Constructor sets of a chain by setting the form inputs. The result is 
     * meant to be an data array containing all public variables, which denotes 
     * the user input
     */

    function __construct($remoteInput = NULL) {
        $this->formInputs = $this->setFormInputs();
    }

    
    /*
     * @var array $data
     * Map is intended for variables submitted through remote API calls that
     * include all user input in a array derived from JSON data. There is no return 
     * input and the data is only saved into the class if the property is already
     * defined
     */
    protected function map($data) {
        if (is_object($data) && !empty($data)) {
            foreach (get_object_vars($data) as $property => $value)
                if (property_exists($this, $property))
                    $this->$property = $value;
        } elseif (is_array($data) && !empty($data)) {
            foreach ($data as $property => $value)
                if (property_exists($this, $property))
                    $this->$property = $value;
        }
    }

    /* 
     * Set Form input variables by using the reflection class to get a list of all
     * public variables. This is used primarily for outputting the variables as a string
     * 
     * @return array
     */

    protected function setFormInputs() {
        return $this->formInputs = $this->getValidProperties();
        /*
          return array_filter($this->getValidProperties(), function($key) {
          return substr($key, 0, 1) == '_';
          });
         */
    }

    /*
     * Get parsed form inputs
     * @return array
     */

    public function getFormInputs() {
        return $this->formInputs;
    }

    /*
     * Public variables in this class are assumbed to be part of the superglobal
     * being accessed. Using the reflection class, all public properties are grabbed 
     * and pushed to a return array.
     * @return array
     */

    protected function getValidProperties() {
        $reflectData = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($reflectData as $property) {
            $properties[] = $property->getName();
        }
        return $properties;
    }

    /*
     * The Loeb API has different variable names for each version, and thus
     * I had to de-couple the object property names with the loeb API property 
     * names. Translate correlates a specified variable to the static directory 
     * reference list $subs
     * 
     * @return string
     */

    protected function translate($var) {
        return isset(static::$subs[$var]) ? static::$subs[$var] : $var;
    }

    /*
     * Stringify accepts a false bool, data in array or a string. The parameter
     * denotes what data is to be selected for conversion to string format.
     * 
     * @var $var bool|array|string
     * @return string
     */

    public function stringify($choices = false) {
        $this->encode();
        if (is_array($choices) && !empty($choices))
            return $this->arrayToString($choices);
        elseif ($choices === false)
            return $this->arrayToString($this->getFormInputs());
        elseif (is_string($choices) && !empty($choices))
            return "{$this->translate($choices)}={$this->getEncoded($choices)}";
    }

    /*
     * Array to string is primarily called by the stringify function to
     * converted array data to a string
     */

    protected function arrayToString($arr) {
        foreach ($arr as $key) {
            $translatedKey = $this->translate($key);
            $stringArr[] = "{$translatedKey}={$this->getEncoded($key)}";
        }
        return implode('&', $stringArr);
    }

    /*
     * Translate array data from an array datasource and prepare for array
     * to json conversion. The returned object is populated by translated data
     * and corresponds to encoded values 
     *
     * @var $arr array
     * @return array
     */

    protected function translateArray($arr, $decode = false) {
        foreach ($arr as $key) {
            $transArr[$this->translate($key)] = $decode === false ? $this->getEncoded($key) : $this->getDecoded($key);
        }
        return $transArr;
    }

    /*
     * Prepare selected data whether it come in array, bool, or string format
     * for conversion to JSON. 
     * 
     * @var $choices bool|string|array
     * @return $array
     */

    public function getDataForJSON($choices = false) {
        $this->encode();
        if (is_array($choices) && !empty($choices))
            return $this->translateArray($choices, true);
        elseif ($choices === false)
            return $this->translateArray($this->getFormInputs(), true);
        elseif (is_string($choices) && !empty($choices))
            return [ $this->translate($choices) => $this->getDecoded($choices)];
    }

}

/*
 * Interface for fetching region data based off zipCode 
 */

interface IFetchRegion {

    public function getCity();

    public function getState();

    public function getCityAndState();

    public function getResponseState();
}

/*
 * Trait that fulfills json decoding returned request data from specified
 * source.
 */

trait TDecodeJSONRequest {
    /*
     * @var $url string
     * @return $string
     */

    private function getAndDecodeJSON($url) {
        return json_decode(file_get_contents($url));
    }

}

/*
 * Trait that fulfills encoding and decoding requirements that are outlined
 * by the IEncode action interface
 */

trait TEncode {
    /*
     * Urlencode supplied array. If array is empty, function retrieves all public
     * variables defined by parameters in getFormInputs()
     * 
     * @var $encoded array
     * @return null
     */

    public function encode($encoded = []) {
        empty($encoded) && $encoded = $this->getFormInputs();
        foreach ($this->getFormInputs() as $var) {
            $this->encoded[$var] = urlencode($this->$var);
        }
    }

    /*
     * Return specified encoded variable value if it exists, otherwise empty set
     * 
     * @var $var string
     * @return string|empty
     */

    public function getEncoded($var) {
        return isset($this->encoded[$var]) ? $this->encoded[$var] : '';
    }

    public function getDecoded($var) {
        return urldecode($this->$var);
    }

}

/*
 * AbstractZipCodeAPI base class that confirms to stringify, region fetching and encoding
 * actions defined by their respective interfaces. setRequest must be defined by
 * inherited classes
 * 
 */

abstract class AbstractZipCodeAPI implements IUIStringify, IFetchRegion, IEncode {

    protected $key; // API Key
    protected $state;
    protected $city;
    protected $zipCode;
    protected $respState = false;
    protected $response;
    protected $request;
    protected $string;

    const REGEXP = "^\d{5}([\-]?\d{4})?$";

    use TDecodeJSONRequest;

use TEncode {
        encode as traitEncode;
    }

    function __construct($zipCode, $key = '') {
        $this->zipCode = $zipCode;
        $this->key = $key;
        $this->setRequest();
        $this->setCityAndState($zipCode);
        if ($this->city && $this->state) {
            $this->respState = true;
            $this->stringify();
        }
    }

    public function encode($encoded = []) {
        $this->traitEncode(['city', 'state']);
    }

    protected function validateZipCode() {
        return preg_match("/" . self::REGEXP . "/", $this->zipCode);
    }

    protected function setCityAndState($zipCode) {
        #$key = '9LFli4nADC2bSHAuCo9j1vxBc45GIwoRi4dA8BbLddW5VxzxVIRXeoyrtD7baw3s'; 
        if ($this->validateZipCode($zipCode)) {
            $this->response = $resp = $this->getAndDecodeJSON($this->request);
            if (!empty($resp->city))
                $this->city = $resp->city;
            if (!empty($resp->state))
                $this->state = $resp->state;
        }
    }

    abstract protected function setRequest();

    public function getCity() {
        return $this->city;
    }

    public function getState() {
        return $this->state;
    }

    public function getCityAndState() {
        return ['city' => $this->city, 'state' => $this->state];
    }

    public function getResponseState() {
        return $this->respState;
    }

    public function stringify() {
        if ($this->respState)
            return $this->string = "city={$this->city}&state={$this->state}";
    }

}

/*
 * ZipCodeAPI service requires a API Key and a validated zipcode
 */

class ZipCodeAPI extends AbstractZipCodeAPI {

    protected function setRequest() {
        return $this->request = "https://www.zipcodeapi.com/rest/{$this->key}/info.json/{$this->zipCode}/degrees";
    }

}

/*
 * ZiptasticAPI service. Requires an API key and a validated zipCode
 */

class ZiptasticAPI extends AbstractZipCodeAPI {

    protected function setRequest() {
        return $this->request = "http://ZiptasticAPI.com/{$this->zipCode}";
    }

}

/* Region handler is a static class that determines which API is active and functional
 * and still works within the parameters of what's needed, then returns that API wrapper
 */

class RegionHandler {

    private static $APIs = [
        'ZipCodeAPI' => '9LFli4nADC2bSHAuCo9j1vxBc45GIwoRi4dA8BbLddW5VxzxVIRXeoyrtD7baw3s',
        'ZiptasticAPI' => ''
    ];

    public static function getRegion($zipCode) {
        foreach (self::$APIs as $api => $key) {
	    $FQN = __NAMESPACE__ . "\\" . $api;
            $APIObject = new $FQN($zipCode, $key); 
            if ($APIObject->getResponseState())
                return $APIObject;
        }
    }

}

/*
 * Reterieves, sanitizes and parses user input data through $_GET, and $_POST superglobals
 * for use by the data assemblers and ultimately in the request to the Loeb API.
 */

class ActiveUserInput extends AbstractUserInput implements IUIMagic, IUIStringify, IEncode {

    public $firstName;
    public $lastName;
    public $city;
    public $state;
    public $email;
    public $phoneNum;
    public $address;
    public $zipCode;
    private $encoded;
    private $region;
    private $newsOptIn = false;
    protected static $subs = [
        'phoneNum' => 'Phone',
        'firstName' => 'FirstName',
        'lastName' => 'LastName',
        'address' => 'Address1',
        'city' => 'City',
        'state' => 'State',
        'zipCode' => 'PostalCode',
        'email' => 'Email',
    ];

    use TEncode {
        encode as traitEncode;
    }

    function __construct($remoteInput = NULL) {
        parent::__construct($remoteInput);
        !empty($remoteInput) && $this->map($remoteInput);
    }

    public function hasOptedIntoNews() {
        $this->newsOptIn = true;
    }

    public function getNewsOptIn() {
        return $this->newsOptIn;
    }

    public function setRegionData(IFetchRegion $region) {
        if (!empty($this->city) || empty($this->state)) {
            $this->city = $region->getCity();
            $this->state = $region->getState();
        }
    }

    public function parseFullName($name) {
        $name = urldecode($name);
        if (!empty($name)) {
            $names = explode(' ', $name);
            $this->firstName = array_shift($names);
            if (count($names) > 0)
                $this->lastName = implode(' ', $names);
        }
    }

    public function getFullName() {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getParsedPhoneNum() {
        return preg_replace('@[^0-9]+@', '', $this->getDecoded('phoneNum'));
    }

    /* private function setCityAndState() {
      if(!empty($this->_zipCode) && empty($this->_state)) {
      $api = "https://www.zipcodeapi.com/rest/9LFli4nADC2bSHAuCo9j1vxBc45GIwoRi4dA8BbLddW5VxzxVIRXeoyrtD7baw3s/info.json/{$this->_zipCode}/degrees";
      $jsonLocData = $this->getAndDecodeJSON($api);
      if(empty($jsonLocData)) {
      $jsonLocData = $this->getAndDecodeJSON("http://ZiptasticAPI.com/{$this->_zipCode}");
      } elseif(!isset($this->jsonLocData->city)) {
      $jsonLocData = $this->getAndDecodeJSON("http://ZiptasticAPI.com/{$this->_zipCode}");
      }
      if($jsonLocData) {
      empty($this->city) && $this->city = $jsonLocData->city;
      empty($this->state) && $this->state = $jsonLocData->state;
      }
      }
      } */

    public function encode($encoded = []) {
        #if($this->zipCode) $this->setRegionData(RegionHandler::getRegion($this->zipCode));
        if ($this->zipCode) {
            $region = RegionHandler::getRegion($this->zipCode);
            if ($region)
                $this->setRegionData($region);
        }
        $this->traitEncode();
    }

}

/*
 * Retrieves, sanitizes and parses passive user data taken from the $_SERVER and $_GET
 * superglobals for use by the data assembler and ultimately used in requests to the
 * Loeb API.
 */

class PassiveUserInput extends AbstractUserInput implements IUIMagic, IEncode, IUIStringify {

    public $campaign;
    public $medium;
    public $source;
    public $content;
    public $terms;
    private $drug;
    private $userData;
    private $isPaid;
    public $userIP;
    public $os;
    public $browser;
    public $device;
    public $brand;

    protected static $subs = [
        'campaign' => 'UTMCampaign',
        'medium' => 'UTMMedium',
        'source' => 'UTMSource',
        'terms' => 'UTMTerm',
        'os' => 'ct_os',
        'browser' => 'ct_browser',
        'device' => 'ct_device',
        'userIP' => 'CT_ip',
    ];
    protected static $contactTrackingData = [
        'os' => 'os',
        'browser' => 'browser',
        'device' => 'device',
        'userIP' => 'ip',
        'content' => 'utm_content',
	'brand' => 'brand'
    ];
    protected static $contactValues = [
        'terms' => 'UTMTerm',
        'source' => 'UTMSource',
        'medium' => 'UTMMedium',
        'campaign' => 'UTMCampaign'
    ];

    use TEncode;

    function __construct(Mobile\DeviceData $detect, $remoteInput = '') {
        parent::__construct($remoteInput);
        empty($remoteInput) ? $this->setPassiveData($detect) : $this->map($remoteInput);
    }

    private function setPassiveData(Mobile\DeviceData $detect) {
        $this->content = filter_input(INPUT_COOKIE, 'utm_content', FILTER_SANITIZE_STRING);
        $this->medium = filter_input(INPUT_COOKIE, 'utm_medium', FILTER_SANITIZE_STRING);
        $this->terms = filter_input(INPUT_COOKIE, 'utm_term', FILTER_SANITIZE_STRING);
        $this->source = filter_input(INPUT_COOKIE, 'utm_source', FILTER_SANITIZE_STRING);
        $this->isPaid = filter_input(INPUT_COOKIE, 'isPaid', FILTER_SANITIZE_NUMBER_INT);
        $this->campaign = $this->isPaid ? filter_input(INPUT_COOKIE, 'utm_campaign', FILTER_SANITIZE_STRING) : '';
        $this->drug = filter_input(INPUT_COOKIE, 'drug', FILTER_SANITIZE_STRING);
        $this->userData = $this->getUserData();
        $this->userIP = $this->userData['userIP'];
        $this->device = $detect->getDevice();
        $this->os = $detect->getOS();
        $this->browser = $detect->getBrowser();
	$this->brand = inAdExperiment() ? 'UserSeesAds' : 'Original';
    }

    private function getUserData() {
        $userAgent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR')) {
            $userIP = current(explode(',', filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING)));
        } else {
            $userIP = filter_var($_SERVER['REMOTE_ADDR'], FILTER_SANITIZE_STRING);
        }
        $arrayIP = explode(',', $userIP);
        $userIP = $arrayIP[0];
        $signature = md5($userIP . $userAgent);
        return ['signature' => $signature, 'userIP' => $userIP, 'userAgent' => $userAgent];
    }

    public function getContactTrackingData() {
        foreach (self::$contactTrackingData as $name => $tracker)
            $this->$name && $contactTrackingData[$tracker] = $this->$name;
        return $contactTrackingData;
    }

    public function getContactValues() {
        foreach (self::$contactValues as $name => $values)
            $this->$name && $contactValues[$values] = $this->$name;
        return $contactValues;
    }        
}
