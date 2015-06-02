<?php
	/**
	 * Notification email transport
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Notifications\Transport;
	
	use Railpage\AppCore;
	use Railpage\Notifications\TransportInterface;
	use Exception;
	use DateTime;
	
	use Swift_Message;
	use Swift_Mailer;
	use Swift_SmtpTransport;
	use Swift_MailTransport;
	use Swift_Encoding;
	use Swift_Plugins_AntiFloodPlugin;
	use Swift_Plugins_DecoratorPlugin;
	
	/**
	 * Email
	 */
	
	class Email extends AppCore implements TransportInterface {
		
		/**
		 * Email message data
		 * @since Version 3.9.1
		 * @var array $data
		 */
		
		private $data;
		
		/**
		 * Set the message data
		 * @param array $data
		 * @since Version 3.9.1
		 * @return \Railpage\Notifications\Transport\Email
		 * @throws \Exception if $data is not an array
		 */
		
		public function setData($data) {
			if (!is_array($data)) {
				throw new Exception("No or invalid message data was sent");
			}
			
			$this->data = $data;
		}
		
		/**
		 * Send the email
		 * @since Version 3.9.1
		 * @return \Railpage\Notifications\Transport\Email
		 */
		
		public function send() {
			
			$this->validate();
			
			$from_email = $this->data['author']['username'] == "Railpage System User" ? $this->Config->SMTP->username : $this->data['author']['email'];
			$from_name = $this->data['author']['username'] == "Railpage System User" ? $this->Config->SiteName : $this->data['author']['username'];
			
			/**
			 * Create a new instance of SwiftMail
			 */
			
			$message = Swift_Message::newInstance()
				->setEncoder(Swift_Encoding::get8BitEncoding())
				->setSubject($this->data['subject'])
				->setFrom(array($from_email => $from_name))
				->setBody($this->data['body'], 'text/html')
				->setCharset("UTF-8");
			
			/*
			foreach ($this->data['recipients'] as $recipient) {
				$message->setBcc(array($recipient['destination'] => $recipient['username']));
			}
			*/
			
			/**
			 * Create an SMTP transport object
			 */
			
			if (isset($this->Config->SMTP->password) && !empty($this->Config->SMTP->password) && $this->Config->SMTP->password != "xxxxx") {
				$transport = Swift_SmtpTransport::newInstance($this->Config->SMTP->host, $this->Config->SMTP->port, $this->Config->SMTP->TLS = true ? "tls" : NULL)
					->setUsername($this->Config->SMTP->username)
					->setPassword($this->Config->SMTP->password);
			} else {
				$transport = Swift_MailTransport::newInstance(); 
			}
			
			/**
			 * Set the mailer 
			 */
			
			$mailer = Swift_Mailer::newInstance($transport);
			
			/**
			 * Use AntiFlood to re-connect after 100 emails and pause 30s between batches
			 */
			
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));
			
			/**
			 * Decorate the email
			 */
			
			if (isset($this->data['decoration'])) {
				$decorator = new Swift_Plugins_DecoratorPlugin($this->data['decoration']);
				$mailer->registerPlugin($decorator);
			}
			
			/** 
			 * Dispatch the email and store the result
			 */
			
			$failures = array();
			$result = array(); 
			
			foreach ($this->data['recipients'] as $recipient) {
				$message->setTo(array($recipient['destination'] => $recipient['username']));
				$stat = $mailer->send($message, $fail);
				
				$result[$recipient['destination']] = $stat; 
				
				$failures = array_merge($failures, $fail); 
				
			}
			
			$return = array(
				"stat" => $result,
				"failures" => $failures
			);
			
			return $return;
		}
		
		/**
		 * Validate the email
		 * @since Version 3.9.1
		 * @return boolean
		 * @throws \Exception if self::$data is empty
		 */
		
		public function validate() {
			if (!is_array($this->data)) {
				throw new Exception("Email data is not set");
			}
			
			return true;
		}
	}