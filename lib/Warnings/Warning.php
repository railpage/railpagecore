<?php

/**
 * User warning adjustment
 *
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Warnings;

use Railpage\PrivateMessages\Message;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\AppCore;
use Exception;
use DateTime;

/**
 * User warning adjustment object
 */
class Warning extends AppCore {

    /**
     * Warning ID
     *
     * @since Version 3.8.7
     * @var int $id
     */

    public $id;

    /**
     * Warning level
     *
     * @since Version 3.8.7
     * @var int $level
     */

    public $level;

    /**
     * Warning level adjustment
     *
     * @since Version 3.8.7
     * @var int $adjustment
     */

    public $adjustment;

    /**
     * Reason for the warning adjustment
     *
     * @since Version 3.8.7
     * @var string $reason
     */

    public $reason;

    /**
     * Action taken
     *
     * @since Version 3.8.7
     * @var string $action
     */

    public $action;

    /**
     * Staff comments
     *
     * @since Version 3.8.7
     * @var string $comments
     */

    public $comments;

    /**
     * Date of issue
     *
     * @since Version 3.8.7
     * @var \DateTime $Date
     */

    public $Date;

    /**
     * Warning recipient
     *
     * @since Version 3.8.7
     * @var \Railpage\Users\User $Recipient
     */

    public $Recipient;

    /**
     * Warning issuer
     *
     * @since Version 3.8.7
     * @var \Railpage\Users\User $Issuer ;
     */

    public $Issuer;

    /**
     * Constructor
     *
     * @since Version 3.8.7
     *
     * @param int|bool $id
     *
     * @returns \Railpage\Warnings\Warning
     */

    public function __construct($id = null) {

        parent::__construct();

        if (!$id = filter_var($id, FILTER_VALIDATE_INT)) {
            return $this;
        }
        
        $this->id = $id;

        $query = "SELECT * FROM phpbb_warnings WHERE warn_id = ?";

        if ($row = $this->db->fetchRow($query, $this->id)) {
            $this->level = $row['new_warning_level'];
            $this->adjustment = $row['new_warning_level'] - $row['old_warning_level'];
            $this->reason = $row['warn_reason'];
            $this->action = $row['actiontaken'];
            $this->comments = $row['mod_comments'];

            $this->Date = new DateTime("@" . $row['warn_date']);
            $this->Recipient = UserFactory::CreateUser($row['user_id']);
            $this->Issuer = UserFactory::CreateUser($row['warned_by']);
        }

        return $this;

    }

    /**
     * Commit changes to this warning
     *
     * @since Version 3.8.7
     * @return void
     */

    public function commit() {

        $this->validate();

        $data = array(
            "new_warning_level" => trim($this->level),
            "old_warning_level" => $this->level - trim($this->adjustment),
            "warn_reason"       => trim($this->reason),
            "actiontaken"       => trim($this->action),
            "mod_comments"      => trim($this->comments),
            "user_id"           => $this->Recipient->id,
            "warned_by"         => $this->Issuer->id,
            "warn_date"         => $this->Date->getTimestamp()
        );

        $this->db->insert("phpbb_warnings", $data);
        $this->id = $this->db->lastInsertId();

        $this->Recipient->warning_level = $this->level;
        $this->Recipient->commit();

        $Message = new Message;
        $Message->setRecipient($this->Recipient);
        $Message->setAuthor($this->Issuer);
        $Message->subject = "You have received an official warning from Railpage";
        $Message->body = sprintf(
            "%s,\n\nYou have been issued a warning for breaching our Terms of Use or Rules for Posting.\n\n[b]Reason[/b]\n%s\n\n[b]Action taken[/b]\n%s\n\nRegards,\n%s\n\nRailpage Moderator Team.",
            $this->Recipient->username, 
            $this->reason, 
            $this->action, 
            $this->Issuer->username
        );
        $Message->send();
        
    }

    /**
     * Validate this warning level adjustment
     *
     * @since Version 3.8.7
     * @return boolean
     * @throws \Exception if $this->Recipient is not an instance of \Railpage\Users\User
     * @throws \Exception if $this->Issuer is not an instance of \Railpage\Users\User
     * @throws \Exception if $this->level is not a valid integer
     * @throws \Exception if $this->reason is empty
     * @throws \Exception if $this->Recipient is excluded from warnings
     */

    private function validate() {

        if (!$this->Recipient instanceof User) {
            throw new Exception("Cannot validate warning level adjustment - no or invalid recipient provided");
        }

        if (!$this->Issuer instanceof User) {
            throw new Exception("Cannot validate warning level adjustment - no or invalid issuer provided");
        }

        if (!filter_var($this->level, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot validate warning level adjustment - no new warning level provided");
        }

        if (empty( $this->reason )) {
            throw new Exception("Cannot validate warning level adjustment - reason cannot be empty");
        }

        if ($this->Recipient->warning_exempt === 1) {
            throw new Exception(
                sprintf(
                    "Cannot add warning to this user (ID %d, Username %s). Disallowed by system policy.",
                    $this->Recipient->id, 
                    $this->Recipient->username
                )
            );
        }

        if (!$this->Date instanceof DateTime) {
            $this->Date = new DateTime;
        }

        if (!filter_var($this->adjustment, FILTER_VALIDATE_INT)) {
            $this->adjustment = $this->level - $this->Recipient->warning_level;
        }

        return true;
        
    }

    /**
     * Set the recipient of this warning
     *
     * @since Version 3.8.7
     *
     * @param \Railpage\Users\User $userObject
     *
     * @return $this
     */

    public function setRecipient(User $userObject) {

        $this->Recipient = $userObject;

        return $this;
        
    }

    /**
     * Set the issuer of this warning
     *
     * @since Version 3.8.7
     *
     * @param \Railpage\Users\User $userObject
     *
     * @return $this
     */

    public function setIssuer(User $userObject) {

        $this->Issuer = $userObject;

        return $this;
        
    }
}