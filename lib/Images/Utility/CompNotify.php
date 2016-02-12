<?php

/**
 * Send notifications for all kinds of shit to do with photo comps
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Utility;

use Railpage\Config\Base as Config;
use Railpage\SiteMessages\SiteMessages;
use Railpage\SiteMessages\SiteMessage;
use Railpage\AppCore;
use Railpage\Module;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Exception;
use DateTime;
use DateInterval;
use DatePeriod;
use stdClass;
use Railpage\ContentUtility;

use Railpage\Notifications\Notifications;
use Railpage\Notifications\Notification;

use Railpage\Images\Competition;
use Railpage\Images\Images;
use Railpage\Images\Image;

/**
 * CompNotify
 */

class CompNotify {
    
    /**
     * Notify the winner
     * @since Version 3.9.1
     * @return \Railpage\Gallery\Competition
     * @param \Railpage\Images\Competition
     * @todo Check recipient preferences for email notifications
     */
    
    public static function notifyWinner(Competition $compObject) {
            
        if (isset($compObject->meta['winnernotified']) && $compObject->meta['winnernotified'] === true) {
            return $this;
        }
            
        if ($Photo = $compObject->getWinningPhoto()) {
            
            /**
             * Create a news article
             */
            
            Utility\CompetitionUtility::createNewsArticle_Winner($this); 
            
            /**
             * Create a site message
             */
            
            Utility\CompetitionUtility::createSiteNotificationForWinner($this);
            
            /**
             * Create an email
             */
            
            $Notification = new Notification;
            $Notification->AddRecipient($Photo->Author->id, $Photo->Author->username, $Photo->Author->contact_email);
            $Notification->subject = sprintf("Photo competition: %s", $compObject->title); 
            
            /**
             * Set our email body
             */
            
            $body = sprintf(
                "Hi %s,\n\nCongratulations! You won the <a href='%s'>%s</a> photo competition!\n\nAs the winner of this competition, you get to <a href='%s'>select a theme</a> for the next competition. You have seven days to do so, before we automatically select one.\n\nThanks\nThe Railpage team.",
                $Photo->Author->username, 
                $compObject->url->canonical, 
                $compObject->title, 
                "https://www.railpage.com.au" . $compObject->url->suggestsubject
            );
            
            if (function_exists("wpautop") && function_exists("format_post")) {
                $body = wpautop(format_post($body));
            }
            
            /**
             * Assemble some template vars for our email
             */
            
            foreach ($Photo->Image->sizes as $size) {
                $hero = $size['source'];
                if ($size['width'] >= 600) {
                    break;
                }
            }
            
            $Smarty = AppCore::getSmarty(); 
            
            $Smarty->Assign("email", array(
                "subject" => $Notification->subject,
                "hero" => array(
                    "image" => $hero,
                    "title" => sprintf("Winning photo: Yours! <em>%s</em>", $Photo->Image->title),
                    "link" => $compObject->url->url,
                    "author" => $Photo->Author->username
                ),
                "body" => $body
            ));
            
            $Notification->body = $Smarty->Fetch($Smarty->ResolveTemplate("template.generic"));
            
            $Notification->commit(); 
            
            /**
             * Update the winnernotified flag
             */
            
            $compObject->meta['winnernotified'] = true;
            $compObject->commit(); 
        }
        
        return $compObject;
    }
}