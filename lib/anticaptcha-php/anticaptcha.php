<?php

interface AntiCaptchaTaskProtocol {
    
    public function getPostData();
    public function setTaskInfo($taskInfo);
    public function getTaskSolution();
    
}

class Anticaptcha {

    private $host = "api.anti-captcha.com";
    private $scheme = "https";
    private $clientKey;
    private $verboseMode = false;
    private $errorMessage;
    private $taskId;
    public $taskInfo;
    
    
    
    /**
     * Submit new task and receive tracking ID
     */
    public function createTask() {
        
        $postData = array(
            "clientKey" =>  $this->clientKey,
            "task"      =>  $this->getPostData()
        );
        $submitResult = $this->jsonPostRequest("createTask", $postData);
        
        if ($submitResult == false) {
            $this->debout("API error", "red");
            return false;
        }
        
        if ($submitResult->errorId == 0) {
            $this->taskId = $submitResult->taskId;
            $this->debout("created task with ID {$this->taskId}", "yellow");
            return true;
        } else {
            $this->debout("API error {$submitResult->errorCode} : {$submitResult->errorDescription}", "red");
            $this->setErrorMessage($submitResult->errorDescription);
            return false;
        }
        
    }
    
    public function waitForResult($maxSeconds = 60, $currentSecond = 0) {
        $postData = array(
            "clientKey" =>  $this->clientKey,
            "taskId"    =>  $this->taskId
        );
        if ($currentSecond == 0) {
            $this->debout("waiting 5 seconds..");
            sleep(3);
        } else {
            sleep(1);
        }
        $this->debout("requesting task status");
        $postResult = $this->jsonPostRequest("getTaskResult", $postData);
        
        if ($postResult == false) {
            $this->debout("API error", "red");
            return false;
        }
        
        $this->taskInfo = $postResult;
        
        
        if ($this->taskInfo->errorId == 0) {
            if ($this->taskInfo->status == "processing") {
                
                $this->debout("task is still processing");
                //repeating attempt
                return $this->waitForResult($maxSeconds, $currentSecond+1);
                
            }
            if ($this->taskInfo->status == "ready") {
                $this->debout("task is complete", "green");
                $this->getTaskSolution($this->taskInfo);
                return true;
            }
            $this->setErrorMessage("unknown API status, update your software", "red");
            return false;
            
        } else {
            $this->debout("API error {$this->taskInfo->errorCode} : {$this->taskInfo->errorDescription}", "red");
            $this->setErrorMessage($this->taskInfo->errorDescription);
            return false;
        }
    }
    
    public function getBalance() {
        $postData = array(
            "clientKey" =>  $this->clientKey
        );
        $result = $this->jsonPostRequest("getBalance", $postData);
        if ($result == false) {
            $this->debout("API error", "red");
            return false;
        }
        if ($result->errorId == 0) {
            return $result->balance;
        } else {
            return false;
        }
    }
    
    public function getTaskResult() {
        
        return $this->getTaskSolution();
        
    }
    
    public function getTaskInfo() {
        
        return $this->taskInfo;
        
    }
    
    public function jsonPostRequest($methodName, $postData) {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,"{$this->scheme}://{$this->host}/$methodName");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_ENCODING,"gzip,deflate");
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "POST");   
        $postDataEncoded = json_encode($postData);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postDataEncoded);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',     
            'Accept: application/json',     
            'Content-Length: ' . strlen($postDataEncoded) 
        ));
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
        $result =curl_exec($ch);
        $curlError = curl_error($ch);
        
        if ($curlError != "") {
            $this->debout("Network error: $curlError");
            return false;
        }
        curl_close($ch);
        return json_decode($result);
    }
    
    public function setVerboseMode($mode) {
        $this->verboseMode = $mode;
    }
    
    public function debout($message, $color = "white") {
        if (!$this->verboseMode) return false;
        if ($color != "white" and $color != "") {
            $CLIcolors = array(
                "cyan" => "0;36",
                "green" => "0;32",
                "blue"  => "0;34",
                "red"   => "0;31",
                "yellow" => "1;33"
            );
            
            $CLIMsg  = "\033[".$CLIcolors[$color]."m$message\033[0m";
            
        } else {
            $CLIMsg  = $message;
        }
        echo $CLIMsg."\n";
    }
    
    public function setErrorMessage($message) {
        $this->errorMessage = $message;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    public function getTaskId() {
        return $this->taskId;
    }
    
    public function setTaskId($taskId) {
        $this->taskId = $taskId;
    }
    
    public function setHost($host) {
        $this->host = $host;
    }
    
    public function setScheme($scheme) {
        $this->scheme = $scheme;
    }
    
    /**
     * Set client access key, must be 32 bytes long
     * @param string $key
     */
    public function setKey($key) {
        $this->clientKey = $key;
    }
    

}
