<?php namespace Rebates\Core\Optimizely;
use Rebates\Core\Reference as Reference;

interface IOptimizelyExperiment {
    public function isFullForm();
    public function fire();
    public function setContactID($id);
    public function setBuckets($buckets);
}


abstract class AbstractOptimizelyExperiment {
    protected $projectID;
    protected $experimentID;
    protected $variations;
    protected $variation; 
    protected $isFullForm = false;
    protected $goalID;
    protected $goalName;
    protected $buckets;
    
    function __construct($projectID, $experimentID, Reference\OptimizelyEventReference $ref, $variations = []) {
        $this->projectID = $projectID;
        $this->experimentID = $experimentID;
        $this->variations = $variations;
        $this->variation = reset($variations);
        $this->eventReference = $ref;
    }
    
    public function isFullForm() {
        $this->isFullForm = true;
        return $this;
    }
    
    public function setContactID($id) {
        $this->id = $id;
        return $this;
    }
    
    public function fire() {
        $variations = $this->isInExperiment() ? [ $this->variation ] : $this->variations;
        if($this->isArmed() && !empty($this->id) && !empty($this->goalName) && $this->isInExperiment())
            return $this->make($variations); 
    }

    public function setBuckets($buckets) {
        $this->buckets = $buckets;
        return $this;
    }

    protected function getBuckets() {
	$buckets = [];
        if(empty($this->buckets)) {
            if(filter_has_var(INPUT_COOKIE, 'optimizelyBuckets'))
                $buckets = json_decode(urldecode(stripslashes($_COOKIE['optimizelyBuckets'])), true);
        } else $buckets = $this->buckets;
	return $buckets;
    }

    protected function isInExperiment() {
	$buckets = $this->getBuckets();
        if(is_array($buckets) && !empty($this->variation)) { 
            return isset($buckets[$this->experimentID]) ? $this->experimentID != '0' : false;
        } else return false;
    }
        
   protected function make(array $variation) { 
        $optimizely = (new OptimizelyEvents($this->projectID))
                ->setExperiments($this->experimentID, $variation)
                ->setEvent($this->goalID, $this->goalName);
        #var_dump($optimizely->makeRequest());
        return $optimizely->makeRequest();                    
    }

    abstract protected function isArmed();
}

class OptimizelyExperiment extends AbstractOptimizelyExperiment implements IOptimizelyExperiment {
    public function setGoalDetails() { 
        if($this->isFullForm) {
            $goal = $this->eventReference->getFullForm();
            $this->goalID = reset($goal);
            $this->goalName = end($goal);
        } elseif(!empty($this->id)) {  
            $goal = $this->eventReference->getCode($this->id); 
            $this->goalID = reset($goal);
            $this->goalName = end($goal);
        }       
        return $this;   
    }
    
    protected function isArmed() {
        return ($this->id != '3' || $this->id != '5') ? true : false;
    }
}


class OptimizelyLocalExperiment extends OptimizelyExperiment implements IOptimizelyExperiment {    
    protected function isInExperiment() {
	$buckets = $this->getBuckets();
	if(isset($buckets[$this->experimentID])) {
	     if($buckets[$this->experimentID] != '0') {
		return $this->variation = $buckets[$this->experimentID];
	     } else return false;
	} else return false;
    }

    public function fire() { 
            if($this->isInExperiment()) $this->make([$this->experiment]);
    }
    
    protected function isArmed() {
        return true;
    }
}


class OptimizelyExperimentForAPI extends AbstractOptimizelyExperiment implements IOptimizelyExperiment {
    public function setGoalDetails() { 
        $args = func_get_args();
        if(func_num_args() >= 2) {
            $this->goalID = $args[0];
            $this->goalName = $args[1];
        }
    }
    
    protected function isArmed() {
        return true;
    }
}
  
class OptimizelyLocalExperimentForAPI extends OptimizelyExperimentForAPI implements IOptimizelyExperiment {
    public function fire() {
        if($this->isInExperiment()) 
            return $this->make([$this->buckets[$this->experimentID]] );
    }    
}

class OptimizelyEvents {
    public $response;
    public $request;

    private $projectID;
    private $experimentsID;
    private $variationID;
    private $eventName;
    private $goalID;
    private $contactType;
    private $uniUserID;
    private $optimizelyEndUserId;
    
    const PRINT_COUPON = .9;
    const EMAIL = .3;
    const ADDRESS = .3;
    const SMS = 1.2;
    const NEWSLETTER = .25;
    const FULLFORM = 2.95;
    
    function __construct($projectID) {
        $contactType = "";
        $this->setProject($projectID, $contactType);
	$this->optimizelyEndUserId = filter_input(INPUT_COOKIE, 'optimizelyEndUserId', FILTER_SANITIZE_STRING);
    }
    
    private function getURL() {
        $req = "https://{$this->projectID}.log.optimizely.com/event";
        $req .= "?a={$this->projectID}";
        $req .= "&" . $this->formatExperimentIDs();
        if(!empty($this->eventName)) $req .= "&n={$this->eventName}";
	$req .= "&g=" . $this->goalID;
	$req .= "&u=" . $this->optimizelyEndUserId; 
        $contactType = strtoupper($this->contactType);
        if(isset(self::$contactType)) {
            $value = self::$contactType * 100;
            $req .= "&v={$value}";
        } 
	#var_dump($req);
        return $this->request = $req;
    }
    
    public function makeRequest($eventName = '') {
        if(!empty($eventName)) $this->eventName = $eventName;
        $this->response = file_get_contents($this->getURL());
	return $this->response;
    }
    
    public function setEvent($goalID, $eventName = '') {
	$this->goalID = $goalID;
        !empty($eventName) && $this->eventName = $eventName;
        return $this;
    }
    
    public function setProject($projectID, $contactType) {
        $this->projectID = $projectID; 
        $this->contactType = $contactType;
        return $this;
    }
    
    public function setExperiments($id, $variations) {
        $this->experimentsID = $id;
        $this->variations = $variations;
        return $this;
    }
    
    private function formatExperimentIDs () {
        $variations = implode('-', $this->variations);
        return "x{$this->experimentsID}={$variations}";
    }
}
