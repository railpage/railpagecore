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
	use Swift_Encoding;
	
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
			
			/**
			 * Create a new instance of SwiftMail
			 */
			
			$message = Swift_Message::newInstance()
				->setEncoder(Swift_Encoding::get8BitEncoding())
				->setSubject($this->data['subject'])
				->setFrom(array($this->data['author']['email'] => $this->data['author']['username']))
				->setBody($this->data['body'], 'text/html')
				->setCharset("UTF-8");
			
			foreach ($this->data['recipients'] as $recipient) {
				$message->setBcc(array($recipient['destination'] => $recipient['username']));
			}
			
			/**
			 * Create an SMTP transport object
			 */
			
			$transport = Swift_SmtpTransport::newInstance($this->Config->SMTP->host, $this->Config->SMTP->port, $this->Config->SMTP->TLS = true ? "tls" : NULL)
				->setUsername($this->Config->SMTP->username)
				->setPassword($this->Config->SMTP->password);
			
			/**
			 * Set the mailer 
			 */
			
			$mailer = Swift_Mailer::newInstance($transport);
			
			/** 
			 * Dispatch the email and store the result
			 */
			
			$result = $mailer->send($message, $failures);
			
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