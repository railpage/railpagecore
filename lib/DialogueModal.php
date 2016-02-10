<?php

/**
 * A modal dialogue which appears over the page contents at page 
 * load, having been set at the previous page generation
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage;

class DialogueModal {
    
    /**
     * Modal header
     * @since Version 3.10.0
     * @var string $header
     */
    
    private $header;
    
    /**
     * Modal body
     * @since Version 3.10.0
     * @var string $body
     */
    
    private $body;
    
    /**
     * Actions/buttons which appear in the modal footer
     * @since Version 3.10.0
     * @var array $actions
     */
    
    private $actions;
    
    /**
     * An optional form destination
     * @since Version 3.10.0
     * @var string $formaction
     */
    
    private $formaction;
    
    /**
     * Constructor
     * @since Version 3.10.0
     */
    
    public function __construct($header, $body, $actions) {
        
        if (!is_null($header)) {
            $this->header = $header;
        }
        
        if (!is_null($body)) {
            $this->body = $body;
        }
        
        if (!is_null($actions)) {
            $this->actions = $actions;
        }
        
    }
    
    /**
     * Set the header
     * @since Version 3.10.0
     * @param string $header
     * @return \Railpage\SessionModal
     */
    
    public function setHeader($header) {
        
        $this->header = $header;
        
        return $this;
        
    }
    
    /**
     * Set the body
     * @since Version 3.10.0
     * @param string $body
     * @return \Railpage\SessionModal
     */
    
    public function setBody($body) {
        
        $this->body = $body;
        
        return $this;
        
    }
    
    /**
     * Add a link action
     * @since Version 3.10.0
     * @param string $label
     * @param string $href
     * @return \Railpage\SessionModal
     */
    
    public function addLinkAction($label, $href, $class) {
        
        $this->actions[] = [
            "element" => "a",
            "label" => $label,
            "attrs" => [ 
                "href" => $href,
                "class" => $class
            ]
        ];
        
        return $this;
        
    }
    
    /**
     * Add a button action
     * @since Version 3.10.0
     * @param string $label
     * @param string $href
     * @return \Railpage\SessionModal
     */
    
    public function addButtonAction($label, $class, $type, $attrs) {
        
        if (!is_array($attrs)) {
            $attrs = [];
        }
        
        $this->actions[] = [
            "element" => "a",
            "label" => $label,
            "attrs" => array_merge([ 
                "type" => "submit",
                "class" => $class
            ], $attrs)
        ];
        
        return $this;
        
    }
    
    /**
     * Get this modal dialogue as a HTML string
     * @since Version 3.10.0
     * @return string
     */
    
    public function __toString() {
        
        $Smarty = AppCore::GetSmarty(); 
        $tpl = $Smarty->ResolveTemplate("template.modal"); 
        
        // Add a default "ok" action to the dialogue
        if (empty($this->actions)) {
            $this->addButtonAction("Close", "btn btn-primary", "button", [ "data-dismiss" => "modal" ]);
        }
        
        $modal = [
            "id" => "globalModal",
            "class" => "modal hide",
            "hide" => true,
            
            "header" => $this->header,
            
            "body" => function_exists("wpautop") ? wpautop($this->body) : $this->body,
            
            "formaction" => $this->formaction,
            
            "actions" => $this->actions
        ];
        
        $Smarty->Assign("modal", $modal); 
        return $Smarty->Fetch($tpl); 
        
    }
    
}