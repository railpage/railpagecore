<?php

/**
 * Private Messages message
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\PrivateMessages;

use Exception;
use DateTime;
use Railpage\AppCore;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\Notifications\Notifications;
use Railpage\Notifications\Notification;

use Swift_Message;
use Swift_Mailer;
use Swift_SmtpTransport;

/**
 * Load an individual message
 * @since Version 3.3
 * @version 3.3
 * @author Michael Greenhill
 */

class Message extends PrivateMessages {
    
    /** 
     * Message ID
     * @since Version 3.3
     * @var int $id
     */
    
    public $id; 
    
    /** 
     * Message subject
     * @since Version 3.3
     * @var string $subject
     */
    
    public $subject; 
    
    /** 
     * Message body
     * @since Version 3.3
     * @var string $body
     */
    
    public $body; 
    
    /** 
     * Message date
     * @since Version 3.3
     * @var int $date
     */
    
    public $date; 
    
    /** 
     * BBCode UID
     * @since Version 3.3
     * @var string $bbcode_uid
     */
    
    public $bbcode_uid; 
    
    /** 
     * Message type
     * @since Version 3.3
     * @var int $type
     */
    
    public $type; 
    
    /** 
     * Enable BBCode
     * @since Version 3.3
     * @var int $enable_bbcode
     */
    
    public $enable_bbcode; 
    
    /** 
     * Enable smilies
     * @since Version 3.3
     * @var int $enable_html
     */
    
    public $enable_html; 
    
    /** 
     * Enable smilies
     * @since Version 3.3
     * @var int $enable_smilies
     */
    
    public $enable_smilies; 
    
    /** 
     * Enable signature
     * @since Version 3.3
     * @var int $enable_signature
     */
    
    public $enable_signature; 
    
    /**
     * Message author User object
     * @since Version 3.8.7
     * @var \Railpage\Users\User $Author
     */
    
    public $Author;
    
    /**
     * From user ID
     * @since Version 3.3
     * @var int $from_user_id
     */
    
    public $from_user_id; 
    
    /**
     * From username
     * @since Version 3.3
     * @var string $from_username
     */
    
    public $from_username; 
    
    /**
     * From user avatar
     * @since Version 3.3
     * @var string $from_user_avatar
     */
    
    public $from_user_avatar; 
    
    /**
     * Can we show if this user is online?
     * @since Version 3.3
     * @var boolean $from_user_viewonline
     */
    
    public $from_user_viewonline;
    
    /**
     * Recipient user object
     * @since Version 3.8.7
     * @var \Railpage\Users\User $Recipient
     */
    
    public $Recipient;
    
    /**
     * To user ID
     * @since Version 3.3
     * @var int $to_user_id
     */
    
    public $to_user_id; 
    
    /**
     * To username
     * @since Version 3.3
     * @var string $to_username
     */
    
    public $to_username; 
    
    /**
     * To user avatar
     * @since Version 3.3
     * @var string $to_user_avatar
     */
    
    public $to_user_avatar; 
    
    /**
     * Can we show if this user is online?
     * @since Version 3.3
     * @var boolean $to_user_viewonline
     */
    
    public $to_user_viewonline;
    
    /**
     * Object ID
     * 
     * In the case of multi-user PMs, or when a PM is directly related to another event (eg new download notification), this object ID forms the link between that event and the PM.
     * @since Version 3.2
     * @var string $object_id
     */
    
    public $object_id;
    
    /**
     * Hide from sender
     * @since Version 3.2
     * @var boolean $hide_from
     */
    
    public $hide_from;
    
    /**
     * Hide from recipient
     * @since Version 3.2
     * @var boolean $hide_to
     */
    
    public $hide_to;
    
    /**
     * Constructor
     * @since Version 3.3
     * @version 3.3
     * @param object $db
     * @param int $id
     */
    
    public function __construct($id = false) {
        parent::__construct(); 
        
        foreach (func_get_args() as $arg) {
            if (filter_var($arg, FILTER_VALIDATE_INT)) {
                $this->id = $id; 
                $this->fetch(); 
            }
        }
    }
    
    /** 
     * Fetch the message
     * @since Version 3.3
     * @version 3.3
     * @return boolean
     */
    
    public function fetch() {
        if (empty($this->id)) {
            throw new Exception("Cannot fetch PM - no message ID provided");
            return false;
        }
        
        $this->mckey = "railpage:messsages.message_id=" . $this->id;
        
        if (!$row = $this->Memcached->fetch($this->mckey)) {
            $query = "SELECT pm.*, pmt.*, ufrom.user_id AS user_id_from, ufrom.username AS username_from, ufrom.user_avatar AS from_user_avatar, ufrom.user_allow_viewonline AS from_user_viewonline, uto.user_id AS user_id_to, uto.username AS username_to, uto.user_allow_viewonline AS to_user_viewonline
                        FROM nuke_bbprivmsgs AS pm
                        LEFT JOIN nuke_bbprivmsgs_text AS pmt ON pm.privmsgs_id = pmt.privmsgs_text_id
                        LEFT JOIN nuke_users AS ufrom ON pm.privmsgs_from_userid = ufrom.user_id
                        LEFT JOIN nuke_users AS uto ON pm.privmsgs_to_userid = uto.user_id
                        WHERE pm.privmsgs_id = ?";
            
            $row = $this->db->fetchRow($query, $this->id); 
                
            $this->Memcached->save($this->mckey, $row);
        }
        
        if (isset($row) && count($row)) {
            // Nasty way of doing it, but it should work for now.
            // Remember to extend BOTH open AND close when adding another bbcode to match
            $bbcode_preg_open   = "@\[(img|url|list|b|i|u)\:([a-zA-Z0-9]+)\]@";
            $bbcode_preg_close  = "@\[/(img|url|list|b|i|u)\:([a-zA-Z0-9]+)\]@";
            
            $this->id           = $row['privmsgs_id'];
            $this->date         = $row['privmsgs_date']; 
            $this->subject      = $row['privmsgs_subject'];
            $this->body         = trim($row['privmsgs_text']);
            $this->bbcode_uid   = $row['privmsgs_bbcode_uid'];
            $this->type         = $row['privmsgs_type'];
            
            $this->enable_bbcode    = $row['privmsgs_enable_bbcode']; 
            $this->enable_html      = $row['privmsgs_enable_html']; 
            $this->enable_smilies   = $row['privmsgs_enable_smilies']; 
            $this->enable_signature = $row['privmsgs_attach_sig']; 
            
            $this->object_id        = $row['object_id'];
            
            $this->hide_from        = $row['hide_from'];
            $this->hide_to          = $row['hide_to'];
            
            $this->setRecipient(UserFactory::CreateUser($row['privmsgs_to_userid']));
            $this->setAuthor(UserFactory::CreateUser($row['privmsgs_from_userid']));
            
            #$this->Author = UserFactory::CreateUser($row['privmsgs_from_userid']);
            #$this->from_user_id        = $row['privmsgs_from_userid']; 
            #$this->from_username   = $row['username_from']; 
            #$this->from_user_avatar    = $row['from_user_avatar']; 
            #$this->from_user_viewonline    = $row['from_user_viewonline']; 
            #$this->Recipient = UserFactory::CreateUser($row['privmsgs_to_userid']);
            #$this->to_user_id      = $row['privmsgs_to_userid']; 
            #$this->to_username     = $row['username_to']; 
            #$this->to_user_viewonline  = $row['to_user_viewonline']; 
        }
    }
    
    /**
     * Validate a message before committing / sending
     * @since Version 3.3
     * @version 3.3
     * @return boolean
     */
    
    public function validate() {
        if (is_null($this->object_id)) {
            $this->object_id = "";
        }
        
        if (empty($this->subject)) {
            throw new Exception("PM subject cannot be empty"); 
            return false;
        }
        
        if (!filter_var($this->id, FILTER_VALIDATE_INT) && empty($this->body)) {
            throw new Exception("PM body cannot be empty"); 
            return false;
        }
        
        if (empty($this->to_user_id)) {
            throw new Exception("Cannot send PM - recipient user ID is empty"); 
            return false;
        }
        
        if (empty($this->from_user_id)) {
            throw new Exception("Cannot send PM - sender user ID is empty"); 
            return false;
        }
        
        if (empty($this->enable_bbcode)) {
            $this->enable_bbcode = true;
        }
        
        if (empty($this->enable_html)) {
            $this->enable_html = true;
        } 
        
        if (empty($this->enable_smilies)) {
            $this->enable_smilies = true;
        } 
        
        if (empty($this->enable_signature)) {
            $this->enable_signature = false;
        }
        
        if (empty($this->privmsgs_bbcode_uid)) {
            $this->privmsgs_bbcode_uid = "sausages";
        }
        
        if (empty($this->bbcode_uid)) {
            $this->bbcode_uid = "sausages";
        }
        
        return true;
    }
    
    /** 
     * Send this message
     * @since Version 3.3
     * @version 3.3
     * @return boolean
     */
    
    public function send() {
        $this->validate();
        
        $data = array(
            "privmsgs_type" => PRIVMSGS_UNREAD_MAIL,
            "privmsgs_subject" => $this->subject,
            "privmsgs_from_userid" => $this->from_user_id,
            "privmsgs_to_userid" => $this->to_user_id,
            "privmsgs_date" => time(),
            "privmsgs_ip" => encode_ip($_SERVER['REMOTE_ADDR']),
            "privmsgs_enable_bbcode" => $this->enable_bbcode,
            "privmsgs_enable_html" => $this->enable_html,
            "privmsgs_enable_smilies" => $this->enable_smilies,
            "privmsgs_attach_sig" => $this->enable_signature,
            "object_id" => $this->object_id
        );
        
        $this->db->insert("nuke_bbprivmsgs", $data);
        $pm_id = $this->db->lastInsertId();
        $this->id = $pm_id;
        
        $data = array(
            "privmsgs_text_id" => $pm_id,
            "privmsgs_bbcode_uid" => $this->bbcode_uid,
            "privmsgs_text" => function_exists("prepare_submit") ? prepare_submit($this->body) : $this->body
        );
        
        $rs = $this->db->insert("nuke_bbprivmsgs_text", $data); 
        
        /**
         * Send an email to the recipient if their settings say so
         */
        
        if ($this->Recipient->notify_privmsg == 1) {
            
            /**
             * Send a push notification
             */
            
            $Push = new Notification;
            $Push->transport = Notifications::TRANSPORT_PUSH;
            $Push->subject = sprintf("[Private Messages] New message from %s", $this->Author->username);
            $Push->body = sprintf("%s has sent you a new private message in the conversation titled \"%s\"", $this->Author->username, $this->subject); 
            $Push->setActionUrl(sprintf("/messages/conversation/%d", $this->id))->addRecipient($this->Recipient->id, $this->Recipient->username, $this->Recipient->username);
            $Push->commit()->dispatch(); 
            
            /**
             * Template settings
             */
            
            $Smarty = AppCore::getSmarty(); 
            
            $Smarty->assign("server_addr", "www.railpage.com.au");
            $Smarty->assign("message_id", $this->id);
            $Smarty->assign("pm_from_username", $this->Author->username);
            $Smarty->assign("userdata_username", $this->Recipient->username);
            
            /*
            if (defined("RP_SITE_ROOT")) {
                $path = sprintf("%s%scontent%semail_pm.tpl", RP_SITE_ROOT, DS, DS);
            } else {
                $path = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DS ."content" . DS . "email_pm.tpl";
            }
            
            $html = $Smarty->fetch($path);
            */
            
            /**
             * Create a user notification
             */
            
            $Notification = new Notification;
            $Notification->transport = Notifications::TRANSPORT_EMAIL;
            $Notification->status = Notifications::STATUS_QUEUED;
            $Notification->subject = sprintf("[Private Messages] New message from %s", $this->Author->username);
            #$Notification->body = $html;
            $Notification->addRecipient($this->Recipient->id, $this->Recipient->username, $this->Recipient->contact_email);
            
            $tpl = $Smarty->ResolveTemplate("template.generic");
            
            $email = array(
                "subject" => sprintf("New message from %s", $this->Author->username),
                "subtitle" => "Private Messages",
                "body" => nl2br(sprintf("Hi %s,\n\n<a href='http://www.railpage.com.au%s?utm_medium&email&utm_source=private-messages&utm_campain=user-%d'>%s</a> has sent you a new <a href='http://www.railpage.com.au/messages/conversation/%d?utm_medium=email&utm_source=private-messages&utm_campaign=user-%d#%d'>private message</a> in the conversation titled <em>%s</em>.",
                            $this->Recipient->username, $this->Author->url->url, $this->Author->id, $this->Author->username, $this->id, $this->Author->id, $this->id, $this->subject))
                                
            );
            
            $Smarty->Assign("email", $email);
            
            $Notification->body = $Smarty->fetch($tpl);
            
            /*
            $message = Swift_Message::newInstance()
                ->setSubject("New private message on Railpage")
                ->setFrom(array("rp2@railpage.com.au" => "Railpage"))
                ->setTo(array($this->Recipient->contact_email => $this->Recipient->username))
                ->setBody($html, 'text/html');
            
            // Mail transport
            $transport = Swift_SmtpTransport::newInstance($this->Config->SMTP->host, $this->Config->SMTP->port, $this->Config->SMTP->TLS = true ? "tls" : NULL)
                ->setUsername($this->Config->SMTP->username)
                ->setPassword($this->Config->SMTP->password);
            
            $mailer = Swift_Mailer::newInstance($transport);
            
            $result = $mailer->send($message);
            */
            
            $Notification->commit(); 
            
        }
    }
    
    /**
     * Update a PM
     * @since Version 3.3
     * @version 3.3
     * @return boolean
     */
    
    public function commit() {
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot commit changes to PM - PM does not exist!"); 
        } 
        
        $this->validate();
        
        // Theoretically nothing but the type should change. I'll leave the rest in for now...
        
        $dataArray = array(); 
        
        $dataArray['privmsgs_type']         = $this->type;
        $dataArray['privmsgs_subject']      = $this->subject;
        $dataArray['privmsgs_from_userid']  = $this->from_user_id; 
        $dataArray['privmsgs_to_userid']    = $this->to_user_id;
        $dataArray['privmsgs_ip']           = encode_ip($_SERVER['REMOTE_ADDR']); 
        $dataArray['privmsgs_enable_bbcode']    = $this->enable_bbcode; 
        $dataArray['privmsgs_enable_html']      = $this->enable_html; 
        $dataArray['privmsgs_enable_smilies']   = $this->enable_smilies; 
        $dataArray['privmsgs_attach_sig']       = $this->enable_signature;
        
        $dataArray['hide_from']     = $this->hide_from; 
        $dataArray['hide_to']       = $this->hide_to;
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            // Update
            
            $where = array(
                "privmsgs_id = ?" => $this->id
            );
            
            $this->db->update("nuke_bbprivmsgs", $dataArray, $where);
            
            $data = array(
                'privmsgs_bbcode_uid' => $this->bbcode_uid,
                'privmsgs_text' => $this->body
            );
            
            $where = array(
                "privmsgs_text_id = ?" => $this->id
            );
            
            $this->db->update("nuke_bbprivmsgs_text", $data, $where);
            
            $this->Memcached->delete($this->mckey);
            
            return true;
        } else {
            // Insert
            
            $this->db->insert("nuke_bbprivmsgs", $dataArray); 
            $this->id = $this->db->lastInsertId(); 
            
            $data = array(
                'privmsgs_bbcode_uid' => $this->bbcode_uid,
                'privmsgs_text' => $this->body,
                'privmsgs_text_id' => $this->id
            );
            
            $this->db->insert("nuke_bbprivmsgs_text", $data);
            
            return true;
        }
    }
    
    /**
     * Delete a message (hide it from the user)
     * @since Version 3.4
     * @param int $user_id
     * @return boolean
     */
    
    public function delete($user_id = false) {
        if (!$user_id) {
            throw new Exception("Cannot delete message - no user ID given"); 
        }
        
        $data = array(
            "privmsgs_id" => $this->id,
            "user_id" => $user_id
        );
        
        return $this->db->insert("privmsgs_hidelist", $data); 
    }
    
    /**
     * Set the recipient of this message
     * @since Version 3.8.7
     * @param \Railpage\Users\User $User
     * @return $this
     */
    
    public function setRecipient(User $User = NULL) {
        if ($User instanceof User) {
            $this->Recipient = $User;
            
            $this->to_user_id = $this->Recipient->id;
            $this->to_username = $this->Recipient->username;
            $this->to_user_viewonline = $this->Recipient->hide;
            $this->to_user_avatar = $this->Recipient->avatar;
        }
        
        return $this;
    }
    
    /**
     * Set the author of this message
     * @since Version 3.8.7
     * @param \Railpage\Users\User $User
     * @return $this
     */
    
    public function setAuthor(User $User = NULL) {
        if ($User instanceof User) {
            $this->Author = $User;
            
            $this->from_user_id = $this->Author->id;
            $this->from_username = $this->Author->username;
            $this->from_user_viewonline = $this->Author->hide;
            $this->from_user_avatar = $this->Author->avatar;
        }
        
        return $this;
    }
    
    /**
     * Mark this message as read
     * @since Version 3.8.7
     * @return $this
     */
    
    public function markAsRead() {
        $this->type = PRIVMSGS_READ_MAIL;
        $this->commit(); 
        
        return $this;
    }
}
