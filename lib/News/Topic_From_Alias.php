<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
		
	/**
	 * News topics - find by name instead of ID
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	class Topic_From_Alias extends Topic {
		
		/** 
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.8.7
		 * @param string $topic_alias
		 */
		
		public function __construct($topic_alias = false) {
			parent::__construct(); 
			
			if (!$topic_alias) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM nuke_topics WHERE topicname = '".$this->db->real_escape_string($topic_alias)."'";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
						$this->id 		= $row['topicid']; 
						$this->alias	= $topic_alias; 
						$this->title 	= $row['topictext'];
						$this->image	= $row['topicimage']; 
					}
				} else {
					trigger_error(__CLASS__.": Could not retrieve topic alias ".$topic_alias); 
					trigger_error($this->db->error); 
					trigger_error($query); 
					
					return false;
				}
			} else {
				$mckey = "railpage:news.topic_slug=" . $topic_alias; 
				
				if ($row = getMemcacheObject($mckey)) {
					$this->id = $row['topicid'];
					$this->alias	= $topic_alias; 
					$this->title 	= $row['topictext'];
					$this->image	= $row['topicimage']; 
				} else {
					$query = "SELECT * FROM nuke_topics WHERE topicname = ?";
					
					if ($row = $this->db_readonly->fetchRow($query, $topic_alias)) {
						$this->id 		= $row['topicid']; 
						$this->alias	= $topic_alias; 
						$this->title 	= $row['topictext'];
						$this->image	= $row['topicimage'];
						
						setMemcacheObject($mckey, $row, strtotime("+6 months")); 
					}
				}
			}
		}
	}	
?>