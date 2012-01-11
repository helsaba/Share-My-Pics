<?php

/*******************************************************************************
    ShareMyPics, a free twitpic clone
    Copyright (C) 2012 Jimmy Rudolf

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

define("FEEDBACK_ERROR", 0);
define("FEEDBACK_INFO", 1);
define("FEEDBACK_WIDGET", 2);

/**
 * Provides functionnalities to interact with SMP
 */
class API {
    private $Events = Array();
    private $Feedbacks = Array();
    
    /**
     * Finds all extensions registered for a specified event
     */
    private function getRegisteredExtensions($event) {
        if(array_key_exists($event, $this->Events)) {
            return $this->Events[$event];
        }
        
        return null;
    }
    
    /**
     * Finds for registered extensions for the specified event, and executes
     * the module's registered function with specified parameters
     */ 
    public function execute($event, $params = Array()) {
        $extensions = $this->getRegisteredExtensions($event);
        
        if(is_array($extensions)) {
            foreach($extensions as $details) {
                call_user_func_array(Array($details["name"], $details["function"]), $params);
            }
        } 
    }
    
    /**
     * Allow an extension to register itself for the specified event
     */
    public function registerExtension($event, $details) {
        $this->Events[$event][] = $details;
    }
    
    /**
     * Allow an extension to notify SMP
     */
    public function sendFeedback($event, $feedback) {
        $this->Feedbacks[$event][] = $feedback;
    }
    
    /**
     * Determines if a feedback exists for a specified event
     */
    public function feedbacksExist($event) {
        $exists = false;
        
        if(array_key_exists($event, $this->Feedbacks) && is_array($this->Feedbacks[$event]) && count($this->Feedbacks[$event]) > 0) {
            $exists = true;
        }
        
        return $exists;
    }
    
    /**
     * Retrieves the feedbacks recorded for a specific event
     */
    public function getFeedbacks($event) {
        if(is_string($event) && $this->feedbacksExist($event)) {
            return $this->Feedbacks[$event];
        } else if(is_array($event)) {
            $list = Array();
            
            foreach(array_keys($this->Feedbacks) as $key) {
                if(in_array($key, $event)) {
                    foreach($this->Feedbacks[$key] as $feedback) {
                        $list[] = $feedback;
                    }
                }
            }
            
            return $list;
        }
        
        return null;
    }
}

/**
 * Used by extensions to inform SMP about their status
 */
class Feedback {
    public $type = FEEDBACK_INFO;
    public $cancelAction = false;   // Can potentially cancel current action
    public $message = "";
    public $extensionName = "";
    public $functionName = "";
    
    public function __construct($extensionName, $functionName, $type = FEEDBACK_INFO, $cancelAction = false, $message = "") {
        $this->type = $type;
        $this->cancelAction = $cancelAction;
        $this->message = $message;
        $this->extensionName = $extensionName;
        $this->functionName = $functionName;
    }
}

?>