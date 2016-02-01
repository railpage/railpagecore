<?php

/**
 * Base user class
 * @since   Version 3.0.1
 * @version 3.9
 * @author  Michael Greenhill
 * @package Railpage
 */

namespace Railpage\Users;

use stdClass;
use Exception;
use DateTime;
use DateTimeZone;

use Railpage\AppCore;
use Railpage\BanControl\BanControl;
use Railpage\Module;
use Railpage\Url;
use Railpage\Forums\Thread;
use Railpage\Forums\Forum;
use Railpage\Forums\Forums;
use Railpage\Forums\Index;
use Railpage\Debug;
use Railpage\Registry;
use Railpage\Session;

/**
 * User class
 * @since  Version 3.0
 * @author Michael Greenhill
 * @todo   Declare all the user vars, populate them. Make this more object-orientated. Does not cater for the
 *         creation of new users
 */
class User extends Base {

    /**
     * Registry cache key
     * @since Version 3.9.1
     * @const string REGISTRY_KEY
     */

    const REGISTRY_KEY = "railpage.users.user=%d";

    /**
     * Status: active
     * @since Version 3.9.1
     * @const int STATUS_ACTIVE
     */

    const STATUS_ACTIVE = 100;

    /**
     * Status: unactivated
     * @since Version 3.9.1
     * @const int STATUS_UNACTIVATED
     */

    const STATUS_UNACTIVATED = 200;

    /**
     * Status: banned
     * @since Version 3.9.1
     * @const int STATUS_BANNED
     */

    const STATUS_BANNED = 300;

    /**
     * Set the default theme
     * @since Version 3.9
     * @const string DEFAULT_THEME
     */

    const DEFAULT_THEME = "jiffy_simple";

    /**
     * System user ID
     * @since Version 3.9
     * @const int SYSTEM_USER_ID
     */

    const SYSTEM_USER_ID = 72587;

    /**
     * Human validation TTL
     * @since Version 3.10.0
     * @const int HUMAN_VALIDATION_TTL
     */

    const HUMAN_VALIDATION_TTL = 1800;

    /**
     * Array of group IDs this user is a member of
     * @since Version 3.7
     * @var array $groups
     */

    public $groups = array();

    /**
     * SSL option
     * @since Version 3.2
     * @var boolean $ssl
     */

    public $ssl = false;

    /**
     * User ID
     * @var int $id
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $id;

    /**
     * Authentication provider
     * Column: provider
     * @since Version 3.8.7
     * @var string $provider
     */

    public $provider;

    /**
     * Guest indicator
     * @var boolean $guest
     * @since   Version 3.0
     * @version 3.0
     */

    public $guest = true;

    /**
     * Username
     * @var string $username
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $username;

    /**
     * User active flag
     * @var int active
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $active = 0;

    /**
     * Activation key
     * @var string $act_key
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $act_key = "";

    /**
     * New password ( needs to be confirmed )
     * @var string $password_new
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $password_new;

    /**
     * User password
     * @var string $password
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $password;

    /**
     * BCrypt password
     * @var string $password_bcrypt
     * @since Version 3.2
     */

    public $password_bcrypt;

    /**
     * Session time
     * @var int $session_time
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_time = 0;

    /**
     * Session page
     * @var string $session_page
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_page = "";

    /**
     * Current session time
     * Column: user_current_visit
     * @var string $session_current
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_current = 0;

    /**
     * Last session time
     * Column: user_last_visit
     * @var string $session_last
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_last = 0;

    /**
     * Session IP
     * Column: last_session_ip
     * @var string $session_ip
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_ip = "";

    /**
     * Session CSLH
     * Column: last_session_cslh
     * @var string $session_cslh
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_cslh = "";

    /**
     * Session ignore multi user checks
     * Column: last_session_ignore
     * @var string $session_mu_ignore
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $session_mu_ignore = 0;

    /**
     * Website
     * @var string $website
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $website = "";

    /**
     * Last visit
     * @var int $lastvisit
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $lastvisit = 0;

    /**
     * Registration date
     * @var mixed $regdate
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $regdate;

    /**
     * Registration date as an instanceof \DateTime
     * @since Version 3.9.1
     * @var \DateTime $RegistrationDate
     */

    public $RegistrationDate;

    /**
     * Authentication level
     * @var int $level
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $level = 0;

    /**
     * Forum posts
     * @var int $posts
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $posts = 0;

    /**
     * Style (? NFI what this is)
     * @var int $style
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $style = 4;

    /**
     * Language
     * @var string $lang
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $lang = "english";

    /**
     * Date format
     * @var string $date_format
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $date_format = "D M d, Y g:i a";

    /**
     * New PMs
     * @var int $privmsg_new
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $privmsg_new = 0;

    /**
     * Unread PMs
     * @var int $privmsg_unread
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $privmsg_unread = 0;

    /**
     * Last PM ID
     * @var int $privmsg_last_id
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $privmsg_last_id = 0;

    /**
     * Show email address to all users
     * @var int $email_show
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $email_show = 1;

    /**
     * Attach user's signature to post
     * @var int $signature_attach
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $signature_attach = 0;

    /**
     * Show all users signatures
     * @var int $signature_showall
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $signature_showall = 0;

    /**
     * Signature
     * @var string $signature
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $signature = "";

    /**
     * Signature BBCode UID
     * @var string $signature_bbcode_uid
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $signature_bbcode_uid = "sausages";

    /**
     * Timezone
     * Column: timezone
     * @var string $timezone
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $timezone = "Australia/Melbourne";

    /**
     * Enable glossary
     * Column: user_enableglossary
     * @var string $enable_glossary
     * @since   Version 3.3
     * @version 3.3
     */

    public $enable_glossary = 0;

    /**
     * Enable RTE
     * Column: user_enablerte
     * @var string $enable_rte
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_rte = 1;

    /**
     * Enable HTML posts
     * @var int $enable_html
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_html = 1;

    /**
     * Enable BBCode
     * @var int $enable_bbcode
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_bbcode = 1;

    /**
     * Enable smilies (smiles/emoticons/etc)
     * @var int $enable_emoticons
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_emoticons = 1;

    /**
     * Enable this user's avatar
     * @var int $enable_avatar
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_avatar = 1;

    /**
     * Enable this user's PMs
     * @var int $enable_privmsg
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_privmsg = 1;

    /**
     * Enable popups for new private messages
     * @var int $enable_privmsg_popup
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $enable_privmsg_popup = 1;

    /**
     * Enable auto login
     * @var int $enable_autologin
     * @since Version 3.2
     */

    public $enable_autologin = 1;

    /**
     * Hide this users online status
     * Column: user_allow_viewonline
     * @var int $hide
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $hide = 0;

    /**
     * Notify user of events
     * @var int $notify
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $notify = 1;

    /**
     * Notify user of new PMs
     * @var int $notify_privmsg
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $notify_privmsg = 1;

    /**
     * User rank ID
     * @var int $rank_id
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $rank_id = 0;

    /**
     * User rank text
     * @var string $rank_text
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $rank_text;

    /**
     * Avatar image URL
     * @var string $avatar
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $avatar = "http://static.railpage.com.au/modules/Forums/images/avatars/765-default-avatar.png";

    /**
     * Avatar width
     * @var int $avatar_width
     * @since   Version 3.1
     * @version 3.1
     */

    public $avatar_width = 100;

    /**
     * Avatar height
     * @var int $avatar_height
     * @since   Version 3.1
     * @version 3.1
     */

    public $avatar_height = 100;

    /**
     * Avatar filename
     * @var string $avatar_filename
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $avatar_filename = "";

    /**
     * Avatar type
     * @var int $avatar_type
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $avatar_type = 0;

    /**
     * Use Gravatar if this user doesn't have an avatar
     * @var int $avatar_gravatar
     * @version 3.2
     */

    public $avatar_gravatar = 0;

    /**
     * Email address
     * @var string $contact_email
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_email = "";

    /**
     * ICQ username
     * @var string $contact_icq
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_icq = "";

    /**
     * AIM username
     * @var string $contact_aim
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_aim = "";

    /**
     * YIM username
     * @var string $contact_yim
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_yim = "";

    /**
     * MSN username
     * @var string $contact_msn
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_msn = "";

    /**
     * User location - where they're from
     * @var string $location
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $location = "";

    /**
     * Occupation
     * @var string $occupation
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $occupation = "";

    /**
     * Interests
     * @var string $interests
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $interests = "";

    /**
     * Real name
     * @var string $real_name
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $real_name = "";

    /**
     * Publicly viewable email address
     * @var string $contact_email_public
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $contact_email_public = "";

    /**
     * News - stories submitted by this user
     * @var int $news_submissions
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $news_submissions = 0;

    /**
     * Theme
     * @var string $theme
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $theme = "jiffy_simple";

    /**
     * Warning level
     * Column: user_warnlevel
     * @var string $warning_level
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $warning_level = 0;

    /**
     * Warning level bar colour
     * @var string $warning_level_colour
     * @since   Version 3.1
     * @version 3.1
     */

    public $warning_level_colour;

    /**
     * Exempt this user from warnings
     * Column: disallow_mod_warn
     * @var string $warning_exempt
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $warning_exempt = 0;

    /**
     * Group CP
     * Column: user_group_cp
     * @var int $group_cp
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $group_cp = 0;

    /**
     * List group CP
     * Column: user_group_list_cp
     * @var int $group_list_cp
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $group_list_cp = 0;

    /**
     * Active CP
     * Column: user_active_cp
     * @var int $active_cp
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $active_cp = 0;

    /**
     * Opt out of report notifications
     * Column: user_report_optout
     * @var int $report_optout
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $report_optout = 0;

    /**
     * User wheat
     * Column: uWheat
     * @var int $wheat
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $wheat = 0;

    /**
     * User chaff
     * Column: uChaff
     * @var int $chaff
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $chaff = 0;

    /**
     * Items per page
     * Column: user_forum_postsperpage
     * @var int $items_per_page
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $items_per_page = 25;

    /**
     * API key
     * Column: api_key
     * @var string $api_key
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $api_key = "";

    /**
     * API Secret
     * Column: api_secret
     * @var string $api_secret
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $api_secret = "";

    /**
     * Flickr oauth token
     * Column: flickr_oauth_token
     * @var string $flickr_oauth_token
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $flickr_oauth_token = "";

    /**
     * Flickr oauth secret
     * Column: flickr_oauth_token_secret
     * @var string $flickr_oauth_token_secret
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $flickr_oauth_token_secret = "";

    /**
     * Flickr NSID
     * Column: flickr_nsid
     * @var string $flickr_nsid
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $flickr_nsid = "";

    /**
     * Flickr username
     * Column: flickr_username
     * @var string $flickr_username
     * @since   Version 3.0.1
     * @version 3.0.1
     */

    public $flickr_username = "";

    /**
     * OAuth consumer key
     * Column: oauth_consumer.consumer_key
     * @var string $oauth_key
     * @since   Version 3.2
     * @version 3.2
     */

    public $oauth_key = "";

    /**
     * OAuth consumer secret
     * Column: oauth_consumer.consumer_secret
     * @var string $oauth_secret
     * @since   Version 3.2
     * @version 3.2
     */

    public $oauth_secret = "";

    /**
     * OAuth consumer ID
     * Column: oauth_consumer_id
     * @var string $oauth_id
     * @since Version 3.3
     */

    public $oauth_id = "";

    /**
     * Homepage sidebar type
     * Column: sidebar_type
     * @var int $sidebar_type
     * @since Version 3.2
     */

    public $sidebar_type = 1;

    /**
     * Facebook user ID
     * @column: facebook_user_id
     * @var int $facebook_user_id
     * @since Version 3.2
     */

    public $facebook_user_id = "";

    /**
     * Reported to StopForumSpam.com
     * @since Version 3.5
     * @column: reported_to_sfs
     * @var boolean $reported_to_sfs
     */

    public $reported_to_sfs = 0;

    /**
     * User notes
     * @since Version 3.6
     * @var array $notes
     */

    public $notes = array();

    /**
     * User options
     * @since  Version 3.7.5
     * @column user_opts
     * @var object $preferences
     */

    public $preferences = "";

    /**
     * Profile URL
     * @since Version 3.8.7
     * @var string $url
     */

    public $url;

    /**
     * Meta data for this user
     * @since Version 3.8.7
     * @var array $meta
     */

    public $meta;

    /**
     * Constructor
     * @since   Version 3.0
     * @version 3.10.0
     */

    public function __construct() {

        $timer = Debug::getTimer();

        Debug::RecordInstance();

        parent::__construct();

        $this->guest();

        $this->Module = new Module("users");

        foreach (func_get_args() as $arg) {
            if (filter_var($arg, FILTER_VALIDATE_INT)) {
                $this->id = $arg;
                $this->load();
            } elseif (is_string($arg) && strlen($arg) > 1) {
                $query = "SELECT user_id FROM nuke_users WHERE username = ?";
                $this->id = $this->db->fetchOne($query, $arg);

                if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                    $this->load();
                }
            }
        }

        Debug::LogEvent(__METHOD__ . "(" . $this->id . ")", $timer);
    }

    /**
     * Populate the user object
     * @since   Version 3.0.1
     * @version 3.0.1
     * @return boolean
     *
     * @param int $userId
     */

    public function load($userId = false) {

        if (filter_var($userId, FILTER_VALIDATE_INT)) {
            $this->id = $userId;
        }

        // Get out early
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        $timer = Debug::getTimer();

        $this->createUrls();

        Debug::logEvent(__METHOD__ . " - createUrls() completed", $timer);

        $this->mckey = sprintf("railpage:user_id=%d", $this->id);
        $cached = false;

        $timer = Debug::getTimer();

        /**
         * Load the User data from Redis first
         */

        if ($data = $this->Redis->fetch($this->mckey)) {
            $cached = true;

            Debug::logEvent(__METHOD__ . "(" . $this->id . ") loaded via Redis", $timer);
        }

        /**
         * I fucked up before, so validate the redis data before we continue...
         */

        if (!is_array($data) || empty( $data ) || count($data) === 1) {

            $timer = Debug::getTimer();
            $data = Utility\UserUtility::fetchFromDatabase($this);

            Debug::logEvent(__METHOD__ . "(" . $this->id . ") loaded via ZendDB", $timer);
        }

        /**
         * Process some of the returned values
         */

        $data = Utility\UserUtility::normaliseAvatarPath($data);
        $data = Utility\UserUtility::setDefaults($data, $this); 

        /**
         * Start setting the class vars
         */

        $this->getGroups();

        $this->provider = $data['provider'];
        $this->preferences = json_decode($data['user_opts']);
        $this->guest = false;
        $this->theme = $data['theme'];
        $this->rank_id = $data['user_rank'];
        $this->rank_text = $data['rank_title'];
        $this->timezone = $data['timezone'];
        $this->website = $data['user_website'];
        $this->hide = $data['user_allow_viewonline'];
        $this->meta = json_decode($data['meta'], true);

        if (json_last_error() != JSON_ERROR_NONE || !is_array($this->meta)) {
            $this->meta = array();
        }

        if (isset( $data['password_new'] )) {
            $this->password_new = $data['password_new'];
        }

        $this->session_last_nice = date($this->date_format, $this->lastvisit);
        $this->contact_email_public = (bool)$this->contact_email_public ? $this->contact_email : $data['femail'];
        $this->reputation = '100% (+' . $this->wheat . '/' . $this->chaff . '-)';
        
        if (intval($this->wheat) > 0) {
            $this->reputation = number_format(( ( ( $this->chaff / $this->wheat ) / 2 ) * 100 ), 1) . '% (+' . $this->wheat . '/' . $this->chaff . '-)';
        }

        /**
         * Map database fields to class vars
         */

        $fields = Utility\UserUtility::getColumnMapping();

        foreach ($fields as $key => $var) {
            $this->$var = $data[$key];
        }

        /**
         * Update the user registration date if required
         */
        
        Utility\UserMaintenance::checkUserRegdate($data); 

        $this->RegistrationDate = new DateTime($data['user_regdate_nice']);

        /**
         * Fetch the last IP address from the login logs
         */

        $this->getLogins(1);

        $this->warning_level_colour = Utility\UserUtility::getWarningBarColour($this->warning_level);

        if (isset( $data['oauth_key'] ) && isset( $data['oauth_secret'] )) {
            $this->oauth_key = $data['oauth_key'];
            $this->oauth_secret = $data['oauth_secret'];
        }

        $this->oauth_id = $data['oauth_consumer_id'];

        // Bugfix for REALLY old accounts with a NULL user_level
        if (!filter_var($this->level, FILTER_VALIDATE_INT) && $this->active == 1) {
            $this->level = 1;
        }

        $this->verifyAPI();

        /**
         * Set some default values for $this->preferences
         */

        $this->preferences = json_decode(json_encode($this->getPreferences()));

        if (!$cached) {
            $this->Redis->save($this->mckey, $data, strtotime("+6 hours"));
        }

        return true;
    }

    /**
     * Verify the API settings
     * @since Version 3.9.1
     * @return void
     */

    private function verifyAPI() {

        // Generate a new API key and secret
        if (empty( $this->api_key ) || empty( $this->api_secret )) {
            $this->api_secret = password_hash($this->username . $this->regdate . $this->id, PASSWORD_BCRYPT, array( "cost" => 4 ));
            $this->api_key = crypt($this->username . $this->id, "rl");

            try {
                $this->commit(true);
            } catch (Exception $e) {
                // Throw it away
            }
        }
    }

    /**
     * Validate this user object
     * @since   Version 3.2
     * @version 3.9
     *
     * @param boolean $ignore Flag to toggle if some value checks (eg password) should be ignored
     *
     * @throws \Exception if $this->username is empty
     * @throws \Exception if $this->contact_email is empty
     * @throws \Exception if the account provider is "railpage" and the password is empty
     * @throws \Exception if $this->contact_email is not a valid email address as per filter_var()
     * @throws \Exception if the username is not available
     *
     * @return boolean
     */

    public function validate($ignore = false) {

        if (empty( $this->username )) {
            throw new Exception("Username cannot be empty");
        }

        if (empty( $this->contact_email )) {
            throw new Exception("User must have an email address");
        }

        if (empty( $this->regdate )) {
            $this->regdate = date("M j, Y");
        }

        if (!filter_var($this->id, FILTER_VALIDATE_INT) && !$this->RegistrationDate instanceof DateTime) {
            $this->RegistrationDate = new DateTime;
        }

        if (!$ignore) {
            if ($this->provider == "railpage" && ( empty( $this->password ) )) {
                throw new Exception("Password is empty");
            }

            if (empty( $this->password )) {
                $this->password = "";
            }

            if (empty( $this->password_bcrypt )) {
                $this->password_bcrypt = "";
            }
        }

        if (!filter_var($this->level, FILTER_VALIDATE_INT)) {
            $this->level = 1;
        }

        if (!filter_var($this->contact_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(sprintf("%s is not a valid email address", $this->contact_email));
        }

        if (empty( $this->provider )) {
            $this->provider = "railpage";
        }

        if (empty( $this->default_theme )) {
            $this->default_theme = self::DEFAULT_THEME;
        }

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            if (!$this->isUsernameAvailable()) {
                throw new Exception(sprintf("The desired username %s is already in use", $this->username));
            }
        }

        return true;
    }

    /**
     * Commit changes to existing user or create new user
     * @since   Version 3.1
     * @version 3.9
     *
     * @param boolean $force Force an update of this user even if certain values (eg a password) are empty
     *
     * @return boolean
     */

    public function commit($force = false) {

        $this->validate($force);

        Utility\UserUtility::clearCache($this);

        $data = array();

        foreach (Utility\UserUtility::getColumnMapping() as $key => $var) {
            $data[$key] = $this->$var;
        }

        if (is_string($data['meta']) && strpos($data['meta'], "\\\\\\\\") !== false) {
            $data['meta'] = NULL;
        }

        $json = [ "meta", "user_opts" ];
        foreach ($json as $key) {
            $data[$key] = json_encode($data[$key]);
        }

        #printArray($data);die;

        /*
        $dataArray = array();

        $dataArray['provider'] = $this->provider;
        $dataArray['meta'] = json_encode($this->meta);
        $dataArray['user_opts'] = json_encode($this->preferences);
        $dataArray['username'] = $this->username;
        $dataArray['user_active'] = $this->active;
        $dataArray['user_regdate'] = $this->regdate;
        $dataArray['user_level'] = $this->level;
        $dataArray['user_posts'] = $this->posts;
        $dataArray['user_style'] = $this->style;
        $dataArray['theme'] = $this->theme;
        $dataArray['user_lang'] = $this->lang;
        $dataArray['user_dateformat'] = $this->date_format;
        $dataArray['user_rank'] = $this->rank_id;
        $dataArray['user_from'] = $this->location;
        $dataArray['user_occ'] = $this->occupation;
        $dataArray['user_interests'] = $this->interests;
        $dataArray['name'] = $this->real_name;
        $dataArray['timezone'] = $this->timezone;
        $dataArray['user_website'] = $this->website;
        $dataArray['user_allow_viewonline'] = $this->hide;

        $dataArray['uWheat'] = $this->wheat;
        $dataArray['uChaff'] = $this->chaff;

        $dataArray['api_key'] = $this->api_key;
        $dataArray['api_secret'] = $this->api_secret;

        $dataArray['user_report_optout'] = $this->report_optout;

        $dataArray['user_warnlevel'] = $this->warning_level;
        $dataArray['disallow_mod_warn'] = $this->warning_exempt;

        $dataArray['user_group_cp'] = $this->group_cp;
        $dataArray['user_group_list_cp'] = $this->group_list_cp;
        $dataArray['user_active_cp'] = $this->active_cp;

        $dataArray['user_forum_postsperpage'] = $this->items_per_page;

        $dataArray['user_avatar'] = $this->avatar;
        $dataArray['user_avatar_gravatar'] = $this->avatar_gravatar;
        $dataArray['user_avatar_type'] = $this->avatar_type;
        $dataArray['user_avatar_width'] = $this->avatar_width;
        $dataArray['user_avatar_height'] = $this->avatar_height;

        $dataArray['user_new_privmsg'] = $this->privmsg_new;
        $dataArray['user_unread_privmsg'] = $this->privmsg_unread;
        $dataArray['user_last_privmsg'] = $this->privmsg_last_id;

        $dataArray['user_viewemail'] = $this->email_show;
        $dataArray['storynum'] = $this->news_submissions;

        $dataArray['user_notify'] = $this->notify;
        $dataArray['user_notify_pm'] = $this->notify_privmsg;

        $dataArray['user_email'] = $this->contact_email;
        $dataArray['femail'] = $this->contact_email_public;
        $dataArray['user_icq'] = $this->contact_icq;
        $dataArray['user_aim'] = $this->contact_aim;
        $dataArray['user_yim'] = $this->contact_yim;
        $dataArray['user_msnm'] = $this->contact_msn;

        $dataArray['user_sig'] = $this->signature;
        $dataArray['user_attachsig'] = $this->signature_attach;
        $dataArray['user_showsigs'] = $this->signature_showall;
        $dataArray['user_sig_bbcode_uid'] = $this->signature_bbcode_uid;

        $dataArray['user_password'] = $this->password;
        $dataArray['user_password_bcrypt'] = $this->password_bcrypt;

        $dataArray['user_lastvisit'] = $this->lastvisit;
        $dataArray['user_session_time'] = $this->session_time;
        $dataArray['user_session_page'] = $this->session_page;
        $dataArray['user_current_visit'] = $this->session_current;
        $dataArray['user_last_visit'] = $this->session_last;
        $dataArray['last_session_ip'] = $this->session_ip;
        $dataArray['last_session_cslh'] = $this->session_cslh;
        $dataArray['last_session_ignore'] = $this->session_mu_ignore;

        $dataArray['user_enablerte'] = $this->enable_rte;
        $dataArray['user_enableglossary'] = $this->enable_glossary;
        $dataArray['user_allowhtml'] = $this->enable_html;
        $dataArray['user_allowbbcode'] = $this->enable_bbcode;
        $dataArray['user_allowsmile'] = $this->enable_emoticons;
        $dataArray['user_allowavatar'] = $this->enable_avatar;
        $dataArray['user_allow_pm'] = $this->enable_privmsg;
        $dataArray['user_popup_pm'] = $this->enable_privmsg_popup;

        $dataArray['flickr_oauth_token'] = $this->flickr_oauth_token;
        $dataArray['flickr_oauth_token_secret'] = $this->flickr_oauth_secret;
        $dataArray['flickr_nsid'] = $this->flickr_nsid;
        $dataArray['flickr_username'] = $this->flickr_username;

        $dataArray['user_actkey'] = $this->act_key;

        $dataArray['oauth_consumer_id'] = $this->oauth_id;
        $dataArray['sidebar_type'] = $this->sidebar_type;
        $dataArray['user_enableautologin'] = $this->enable_autologin;

        $dataArray['user_enablessl'] = $this->ssl;

        $dataArray['facebook_user_id'] = $this->facebook_user_id;
        $dataArray['reported_to_sfs'] = $this->reported_to_sfs;

        printArray(count($data));
        printArray(count($dataArray));

        foreach ($data as $key => $val)  {
            if (!in_array($key, array_keys($dataArray))) {
                printArray($key);
            }
        }
        die;
        */

        #ob_end_flush(); echo "adfadf"; var_dump($data);die;
        #ini_set('memory_limit','256M');
        #file_put_contents("/srv/railpage.com.au/www/public_html/content/userdata.txt", var_export($data));die;

        if ($this->RegistrationDate instanceof DateTime) {
            $data['user_regdate_nice'] = $this->RegistrationDate->format("Y-m-d H:i:s");
        }

        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->db->update("nuke_users", $data, array( "user_id = ?" => $this->id ));
        } else {
            $this->db->insert("nuke_users", $data);
            $this->id = $this->db->lastInsertId();
            $this->guest = false;

            $this->createUrls();
        }

        // Update the registry
        $Registry = Registry::getInstance();
        $regkey = sprintf(self::REGISTRY_KEY, $this->id);
        $Registry->remove($regkey); #->set($regkey, $this);

        return true;
    }

    /**
     * Populate this object with guest data
     * @since   Version 3.0.1
     * @version 3.0.1
     * @return boolean
     * @todo    Complete this function
     */

    public function guest() {

        $this->lang = "english";
        $this->date_format = "d M Y H:i";
        $this->avatar_filename = "blank.png";
        $this->email_show = 1;
        $this->signature_attach = 0;
        $this->signature_showall = 0;
        $this->enable_rte = 1;
        $this->enable_html = 1;
        $this->enable_bbcode = 1;
        $this->enable_emoticons = 1;
        $this->enable_avatar = 1;
        $this->hide = 0;
        $this->notify = 0;
        $this->notify_privmsg = 0;
        $this->theme = isset( $this->default_theme ) && !empty( $this->default_theme ) ? $this->default_theme : self::DEFAULT_THEME;
        $this->items_per_page = 25;
        $this->enable_glossary = 0;
        $this->sidebar_type = 2;
        $this->timezone = "Australia/Melbourne";

        /**
         * Set some default values for $this->opts
         */

        if (empty( $this->preferences )) {
            $this->preferences = new stdClass;

            $this->preferences->home = "Home";
            $this->preferences->showads = true;
            $this->preferences->forums = new stdClass;
            $this->preferences->forums->hideinternational = false;
        }

        return true;
    }

    /**
     * Check for group membership
     * @since   Version 3.0.1
     * @version 3.9.1
     *
     * @param int|bool $groupId
     *
     * @return boolean
     */

    public function inGroup($groupId = false) {

        if (!defined("RP_GROUP_ADMINS")) {
            define("RP_GROUP_ADMINS", "michaelisawesome");
        }

        if ($groupId == RP_GROUP_ADMINS && $this->level == 2) {
            return true;
        }

        $Group = new Group($groupId);

        $return = $Group->userInGroup($this);

        return $return;
    }

    /**
     * Generate user data array for phpBB2.x backwards compatibility
     * @since Version 3.7.5
     * @return array
     */

    public function generateUserData() {

        $return = array();
        $return['session_id'] = isset( $_SESSION['session_id'] ) ? filter_var($_SESSION['session_id'], FILTER_SANITIZE_STRING) : NULL;
        $return['user_id'] = $this->id;
        $return['username'] = $this->username;
        $return['theme'] = $this->theme;

        $return['session_logged_in'] = $this->id > 0 ? true : false;
        $return['user_lastvisit'] = $this->lastvisit;
        $return['user_showsigs'] = $this->signature_showall;
        $return['user_level'] = $this->level;
        $return['user_attachsig'] = $this->signature_attach;
        $return['user_notify'] = $this->notify;
        $return['user_allow_pm'] = $this->enable_privmsg;
        $return['user_allowhtml'] = $this->enable_html;
        $return['user_allowbbcode'] = $this->enable_bbcode;
        $return['user_allowsmile'] = $this->enable_emoticons;
        $return['user_sig'] = $this->signature;
        $return['user_report_optout'] = $this->report_optout;
        $return['user_style'] = $this->theme;

        $return['user_forum_postsperpage'] = $this->items_per_page;


        /**
         * Crap values that I don't care about anymore
         */

        $return['user_new_privmsg'] = false;
        $return['user_unread_privmsg'] = false;

        return $return;
    }

    /**
     * Set last visit time (user login)
     * @since   Version 3.0
     * @version 3.10.0
     */

    public function updateVisit() {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return $this;
        }

        if ($this->session_time >= ( time() - Session::DEFAULT_SESSION_LENGTH )) {
            return $this;
        }

        $this->lastvisit = $this->session_time - Session::DEFAULT_SESSION_LENGTH;
        $this->commit();

        return $this;

    }

    /**
     * Set last session activity
     * @since   Version 3.0
     * @version 3.10.0
     * @return boolean
     *
     * @param string $remoteAddr
     */

    public function updateSessionTime($remoteAddr = null) {

        $timer = Debug::GetTimer();

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return $this;
        }

        if ($this->session_time >= ( time() - 300 )) {
            return $this;
        }

        $this->session_time = time();

        if (is_null($remoteAddr)) {
            $this->session_ip = $remoteAddr;
        }

        $this->commit();

        Debug::logEvent("Zend_DB: Update user session time for user ID " . $this->id, $timer);

        return $this;

    }

    /**
     * Load warning history
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     */

    public function loadWarnings() {

        $query = "SELECT w.warn_id AS warning_id, w.user_id, u.username, w.warned_by AS staff_user_id, s.username AS staff_username, w.warn_reason, w.mod_comments AS staff_comments, w.actiontaken AS warn_action, w.warn_date FROM phpbb_warnings AS w LEFT JOIN nuke_users AS u ON u.user_id = w.user_ID LEFT JOIN nuke_users AS s ON s.user_id = w.warned_by WHERE w.user_id = ? ORDER BY w.warn_date";

        if ($result = $this->db->fetchAll($query, $this->id)) {
            foreach ($result as $row) {
                $this->warnings[] = $row;
            }
        }

        return true;
    }

    /**
     * Load user notes
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     */

    public function loadNotes() {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        $query = "SELECT un.nid AS note_id, un.datetime AS note_timestamp, un.data AS note_text, un.aid AS admin_user_id, u.username AS admin_username FROM nuke_users_notes AS un LEFT JOIN nuke_users AS u ON un.aid = u.user_id WHERE un.uid = ?";

        if ($result = $this->db->fetchAll($query, $this->id)) {
            foreach ($result as $row) {
                if ($row['admin_user_id'] == "0") {
                    $row['admin_username'] = "System";
                }

                $this->notes[] = $row;
            }
        }

        return true;
    }

    /**
     * Add a note
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     *
     * @param string $text
     * @param int    $adminUserId
     */

    public function addNote($text = false, $adminUserId = 0) {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        if ($text == false || is_null(filter_var($text, FILTER_SANITIZE_STRING))) {
            return false;
        }

        $data = array(
            "uid"      => !filter_var($this->id, FILTER_VALIDATE_INT) ? "0" : $this->id,
            "aid"      => $adminUserId,
            "datetime" => time(),
            "data"     => $text
        );

        return $this->db->insert("nuke_users_notes", $data);
    }

    /**
     * Set auto-login token
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     *
     * @param int $cookieExpire
     */

    public function setAutoLogin($cookieExpire) {

        if (empty( $cookieExpire )) {
            $cookieExpire = RP_AUTOLOGIN_EXPIRE;
        }

        if (!is_null(filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING))) {#!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientAddr = filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING); #$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientAddr = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL); #$_SERVER['REMOTE_ADDR'];
        }

        $data = array(
            "user_id"            => $this->id,
            "autologin_token"    => get_random_string("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", 16),
            "autologin_expire"   => $cookieExpire,
            "autologin_ip"       => $clientAddr,
            "autologin_hostname" => filter_input(INPUT_SERVER, "REMOTE_HOST", FILTER_SANITIZE_STRING),
            "autologin_last"     => time(),
            "autologin_time"     => time()
        );

        if (is_null($data['autologin_hostname'])) {
            $data['autologin_hostname'] = $clientAddr;
        }

        $autologin = array(
            "user_id" => $this->id,
            "token"   => $data['autologin_token']
        );

        if ($this->db->insert("nuke_users_autologin", $data)) {
            setcookie("rp_autologin", base64_encode(implode(":", $autologin)), $cookieExpire, RP_AUTOLOGIN_PATH, RP_AUTOLOGIN_DOMAIN, RP_SSL_ENABLED, true);

            $this->addNote("Autologin token set");

            return true;
        }

        return false;
    }

    /**
     * Attempt auto-login
     * @since   Version 3.2
     * @version 3.2
     * @return mixed
     */

    public function tryAutoLogin() {

        if (is_null(filter_input(INPUT_COOKIE, "rp_autologin"))) { #empty($_COOKIE['rp_autologin'])) {
            $this->addNote("Autologin attempted but no autologin cookie was found");

            return false;
        } else {
            $cookie = explode(":", base64_decode(filter_input(INPUT_COOKIE, "rp_autologin")));

            #printArray($cookie);die;

            if (count($cookie) < 2) {
                return false;
            }

            $query = "SELECT autologin_id FROM nuke_users_autologin WHERE user_id = ? AND autologin_token = ?";

            if ($autologin_id = $this->db->fetchOne($query, array( $cookie[0], $cookie[1] ))) {

                if (!is_null(filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING))) {#!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $client_addr = filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING); #$_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $client_addr = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL); #$_SERVER['REMOTE_ADDR'];
                }

                $data = array(
                    "autologin_last"     => time(),
                    "autologin_ip"       => $client_addr,
                    "autologin_hostname" => filter_input(INPUT_SERVER, "REMOTE_HOST", FILTER_SANITIZE_STRING),
                );

                $this->db->update("nuke_users_autologin", $data, array( "autologin_id = ?" => $autologin_id ));

                // Record the login event
                try {
                    $this->id = $cookie[0];
                    $this->recordLogin();
                } catch (Exception $e) {
                    global $Error;
                    $Error->save($e);
                }

                return $cookie[0];

            }

            $this->addNote("Autologin attempted but an invalid autologin cookie was found");

            return false;
        }
    }

    /**
     * Get auto logins for this user
     * @since   Version 3.2
     * @version 3.2
     * @return mixed
     */

    public function getAutoLogin() {

        $query = "SELECT autologin_id AS id, autologin_token AS token, autologin_time AS date_set, autologin_expire AS date_expire, autologin_last AS date_last, autologin_ip AS ip, autologin_hostname AS hostname FROM nuke_users_autologin WHERE user_id = ? ORDER BY autologin_last DESC";

        $autologins = array();

        if ($result = $this->db->fetchAll($query, $this->id)) {
            foreach ($result as $row) {
                $autologins[$row['id']] = $row;
            }
        }

        return $autologins;
    }

    /**
     * Delete autologin token
     * @since   Version 3.2
     * @version 3.2
     *
     * @param int $tokenId
     *
     * @return boolean
     */

    public function deleteAutoLogin($tokenId) {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        $clause = array(
            "user_id" => $this->id
        );

        if (filter_var($tokenId, FILTER_VALIDATE_INT)) {
            $clause['autologin_id'] = $tokenId;
        }

        return $this->db->delete("nuke_users_autologin", $clause);
    }

    /**
     * Record login event
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     *
     * @param string $clientAddr
     */

    public function recordLogin($clientAddr = false) {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        if ($clientAddr === false && !is_null(filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING))) {#!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientAddr = filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING); #$_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ($clientAddr === false) {
            $clientAddr = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL); #$_SERVER['REMOTE_ADDR'];
        }

        $data = array(
            "user_id"        => $this->id,
            "login_time"     => time(),
            "login_ip"       => $clientAddr,
            "login_hostname" => filter_input(INPUT_SERVER, "HTTP_HOST", FILTER_SANITIZE_STRING),
            "server"         => filter_input(INPUT_SERVER, "HTTP_HOST", FILTER_SANITIZE_STRING)
        );

        foreach ($data as $key => $val) {
            if (is_null($val)) {
                $data[$key] = "";
            }
        }

        if ($data['login_ip'] == $data['login_hostname']) {
            $data['login_hostname'] = gethostbyaddr($data['login_ip']);
        }

        return $this->db->insert("log_logins", $data);
    }

    /**
     * Get login history for this user
     * @since   Version 3.2
     * @version 3.2
     * @return mixed
     *
     * @param int $itemsPerPage
     * @param int $page
     */

    public function getLogins($itemsPerPage = 25, $page = 1) {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        $logins = array();

        $key = sprintf("railpage:user=%d;logins;perpage=%d;page=%d", $this->id, $itemsPerPage, $page);

        $query = "SELECT * FROM log_logins WHERE user_id = ? ORDER BY login_time DESC LIMIT ?,?"; # Dropped USE_INDEX - negatively impacted query performance when zero results were found

        $args = array(
            $this->id,
            ( $page - 1 ) * $itemsPerPage,
            $itemsPerPage
        );

        if ($result = $this->db->fetchAll($query, $args)) {
            foreach ($result as $row) {
                $logins[$row['login_id']] = $row;
            }
        }

        if (count($logins)) {
            $this->session_ip = $logins[key($logins)]['login_ip'];

            if ($this->lastvisit == 0) {
                $this->lastvisit = $logins[key($logins)]['login_time'];
            }
        }

        return $logins;
    }

    /**
     * Update this user's PC ID hash
     * @since   Version 3.2
     * @version 3.2
     * @return boolean
     */

    public function updateHash() {

        $cookie = is_null(filter_input(INPUT_COOKIE, "rp_userhash")) ? "" : filter_input(INPUT_COOKIE, "rp_userhash"); # isset($_COOKIE['rp_userhash']) ? $_COOKIE['rp_userhash'] : "";
        $hash = array();
        $update = false;

        if (is_null($cookie) || empty( $cookie ) || empty( $this->id )) {
            // No hash
            return false;
        }

        $query = "SELECT hash FROM nuke_users_hash WHERE user_id = ?";

        if ($result = $this->db->fetchAll($query, $this->id)) {
            foreach ($result as $row) {
                $hash[] = $row['hash'];
            }

            if (in_array($cookie, $hash)) {
                $update = true;
            }

            $data = array(
                "user_id" => $this->id,
                "hash"    => $cookie,
                "date"    => time()
            );

            if (!is_null(filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING))) {
                $data['ip'] = filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_SANITIZE_STRING);
            } else {
                $data['ip'] = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL);
            }

            if ($update) {
                $this->db->update("nuke_users_hash", $data, array( "user_id = ?" => $this->id ));
            } else {
                $this->db->insert("nuke_users_hash", $data);
            }
        }
    }

    /**
     * Generate a new API key for this user
     * @since Version 3.4
     * @return boolean
     */

    public function newAPIKey() {

        $len = 32;

        if (@is_readable('/dev/urandom')) {
            $file = fopen('/dev/urandom', 'r');
            $urandom = fread($file, $len);
            fclose($file);
        }

        $return = '';

        for ($i = 0; $i < $len; ++$i) {
            if (!isset( $urandom )) {
                if ($i % 2 == 0)
                    mt_srand(time() % 2147 * 1000000 + (double)microtime() * 1000000);
                $rand = 48 + mt_rand() % 64;
            } else {
                $rand = 48 + ord($urandom[$i]) % 64;
            }

            if ($rand > 57) {
                $rand += 7;
            }

            if ($rand > 90) {
                $rand += 6;
            }

            if ($rand == 123) {
                $rand = 45;
            }

            if ($rand == 124) {
                $rand = 46;
            }

            $return .= chr($rand);
        }

        $this->api_key = $return;
    }

    /**
     * Increase user's chaff rating by $amount
     * @since Version 3.4
     *
     * @param int $amount
     *
     * @return boolean
     */

    public function addChaff($amount = 1) {

        return $this->chaff($amount);
    }

    /**
     * Fetch the users' timeline
     * @since Version 3.5
     *
     * @param object $dateStart
     * @param object $dateEnd
     *
     * @return array
     */

    public function timeline($dateStart, $dateEnd) {

        $timer = Debug::GetTimer();

        $timezzz = (new Timeline)->setUser($this)->generateTimeline($dateStart, $dateEnd);

        Debug::LogEvent(__METHOD__, $timer);

        return $timezzz;

        #return Timeline::GenerateTimeline($this, $date_start, $date_end);

    }

    /**
     * Get all groups this user is a member of
     * @since Version 3.7
     * @return array
     *
     * @param boolean $force
     */

    public function getGroups($force = false) {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            return false;
        }

        $mckey = sprintf("railpage:usergroups.user_id=%d", $this->id);

        $this->groups = array();

        if ($force === false && $this->groups = $this->Redis->fetch($mckey)) {
            return $this->groups;
        }

        $query = "SELECT group_id FROM nuke_bbuser_group WHERE user_id = ? AND user_pending = 0";

        $this->groups = array();

        if ($result = $this->db->fetchAll($query, $this->id)) {

            foreach ($result as $row) {
                #if (!in_array($row['group_id'], $this->groups)) {
                $this->groups[] = $row['group_id'];
                #}
            }
        }

        $this->Redis->save($mckey, $this->groups, strtotime("+24 hours"));

        return $this->groups;
    }

    /**
     * Get a list of watched threads
     * @since Version 3.8
     * @return array
     *
     * @param int $page
     * @param int $limit
     */

    public function getWatchedThreads($page = 1, $limit = false) {

        // Assume Zend_Db

        if (!$limit) {
            $limit = $this->items_per_page;
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS '1' AS unread, t.topic_id, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_replies, t.topic_first_post_id, t.topic_last_post_id,
                    f.forum_id, f.forum_name,
                    ufirst.username AS topic_first_post_username, ufirst.user_id AS topic_first_post_user_id,
                    ulast.username AS topic_last_post_username, ulast.user_id AS topic_last_post_user_id,
                    pfirst.post_time AS topic_first_post_date,
                    plast.post_time AS topic_last_post_date
                    FROM nuke_bbtopics_watch AS tw
                    LEFT JOIN nuke_bbtopics AS t ON t.topic_id = tw.topic_id
                    LEFT JOIN nuke_bbforums AS f ON t.forum_id = f.forum_id
                    LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_last_post_id
                    LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
                    LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = t.topic_poster
                    LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
                    WHERE tw.user_id = ? 
                    ORDER BY plast.post_time DESC
                    LIMIT ?, ?";

        if (!$result = $this->db->fetchAll($query, array( $this->id, ( $page - 1 ) * $limit, $limit ))) {
            return false;
        }

        $return = array();
        $topic_ids = array();
        $return['page'] = $page;
        $return['items_per_page'] = $limit;
        $return['total'] = $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total");
        $return['topics'] = array();

        foreach ($result as $row) {
            if (filter_var($row['topic_id'], FILTER_VALIDATE_INT)) {
                $return['topics'][$row['topic_id']] = $row;
                $topic_ids[] = $row['topic_id'];
            }
        }

        $query = "SELECT UNIX_TIMESTAMP(viewed) AS viewed, topic_id FROM nuke_bbtopics_view WHERE user_id = ? AND topic_id IN (" . implode(",", $topic_ids) . ")";

        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $return['topics'][$row['topic_id']]['unread'] = intval($return['topics'][$row['topic_id']]['topic_last_post_date'] > $row['viewed']);
        }

        return $return;
    }

    /**
     * Unlink a Flickr account
     * @return boolean
     */

    public function unlinkFlickr() {

        $this->flickr_nsid = NULL;
        $this->flickr_username = NULL;
        $this->flickr_oauth_token = NULL;
        $this->flickr_oauth_secret = NULL;

        return $this->commit();
    }

    /**
     * Get this user's ACL role
     * @since Version 3.8.7
     *
     * @param int    $groupId A forums group ID
     * @param string $role     The default ACL role to return if a group ID is provided
     *
     * @return string
     */

    public function aclRole($groupId = NULL, $role = "maintainer") {

        if (defined("RP_GROUP_ADMINS") && $this->inGroup(RP_GROUP_ADMINS)) {
            return "administrator";
        }

        if (defined("RP_GROUP_MODERATORS") && $this->inGroup(RP_GROUP_MODERATORS)) {
            return "moderator";
        }

        if (filter_var($groupId, FILTER_VALIDATE_INT) && $this->inGroup($groupId)) {
            return $role;
        }

        if (!$this->guest) {
            return "user";
        }

        return "guest";
    }

    /**
     * Record a good event for this user
     * @return \Railpage\Users\User
     *
     * @param int $amt The amount to increase their reputation by
     *
     * @since Version 3.8.7
     */

    public function wheat($amt = 1) {

        if (!filter_var($amt, FILTER_VALIDATE_INT)) {
            $amt = 1;
        }

        $this->wheat = $this->wheat + $amt;
        $this->commit();

        return $this;

    }

    /**
     * Record a negative event for this user. Counts towards their reputation accross the site
     *
     * @param int $amt The amount to decrease their reputation by
     *
     * @since Version 3.8.7
     * @return \Railpage\Users\User
     */

    public function chaff($amt = 1) {

        if (!filter_var($amt, FILTER_VALIDATE_INT)) {
            $amt = 1;
        }

        $this->chaff = $this->chaff + $amt;
        $this->commit();

        return $this;

    }

    /**
     * Create URLs
     * @since Version 3.8.7
     * @return \Railpage\Users\User
     */

    private function createUrls() {

        $this->url = Utility\UrlUtility::MakeURLs($this);

    }

    /**
     * Set the password for this user
     *
     * Updated to use PHP 5.5's password_hash(), password_verify() and password_needs_rehash() functions
     * @since Version 3.8.7
     *
     * @param string $password
     *
     * @return \Railpage\Users\User
     */

    public function setPassword($password = false) {

        if (!$password || empty( $password )) {
            throw new Exception("Cannot set password - no password was provided");
        }

        try {
            $this->Redis->delete($this->mckey);
        } catch (Exception $e) {
            // Throw it away, don't care
        }

        /**
         * Check to make sure it's not a shitty password
         */

        if (!$this->safePassword($password)) {
            // MGH - 6/01/2015 commented out, people are dumb.
            //throw new Exception("Your desired password is unsafe. Please choose a different password.");
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->password_bcrypt = false; // Deliberately deprecate the bcrypt password option

        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->commit();
            $this->addNote("Password changed or hash updated using password_hash()");
        }
    }

    /**
     * Validate a password for this account
     *
     * Updated to use PHP 5.5's password_hash(), password_verify() and password_needs_rehash() functions
     * @since Version 3.8.7
     *
     * @param string $password
     *
     * @return boolean
     */

    public function validatePassword($password = false, $username = false) {
        
        Utility\PasswordUtility::validateParameters($password, $username, $this); 

        /**
         * Create a temporary instance of the requested user for logging purposes
         */

        try {
            $TmpUser = Factory::CreateUserFromUsername($username);
        } catch (Exception $e) {

            if ($e->getMessage() == "Could not find user ID from given username") {
                $TmpUser = new User($this->id);
            }

        }

        /**
         * Get the stored password for this username
         */

        if ($username && !empty( $username ) && empty( $this->username )) {

            $query = "SELECT user_id, user_password, user_password_bcrypt FROM nuke_users WHERE username = ?";
            $row = $this->db->fetchRow($query, $username);

            $stored_user_id = $row['user_id'];
            $stored_pass = $row['user_password'];
            $stored_pass_bcrypt = $row['user_password_bcrypt'];

        } elseif (!empty( $this->password )) {

            $stored_user_id = $this->id;
            $stored_pass = $this->password;
            $stored_pass_bcrypt = $this->password_bcrypt;

        }

        /**
         * Check if the invalid auth timeout is in effect
         */

        if (isset( $TmpUser->meta['InvalidAuthTimeout'] )) {
            if ($TmpUser->meta['InvalidAuthTimeout'] <= time()) {
                unset( $TmpUser->meta['InvalidAuthTimeout'] );
                unset( $TmpUser->meta['InvalidAuthCounter'] );
                $TmpUser->commit();
                $this->refresh();
            } else {
                $TmpUser->addNote("Login attempt while InvalidAuthTimeout is in effect");
                throw new Exception("You've attempted to log in with the wrong password too many times. We've temporarily disabled your account to protect it against hackers. Please try again soon. <a href='/account/resetpassword'>Can't remember your password?</a>");
            }
        }

        /**
         * Verify the password
         */

        if (Utility\PasswordUtility::validatePassword($password, $stored_pass, $stored_pass_bcrypt)) {
            $this->load($stored_user_id);

            /**
             * Check if the password needs rehashing
             */

            if (password_needs_rehash($stored_pass, PASSWORD_DEFAULT) || password_needs_rehash($stored_pass_bcrypt, PASSWORD_DEFAULT)) {
                $this->setPassword($password);
            }

            /**
             * Reset the InvalidAuthCounter
             */

            if (isset( $this->meta['InvalidAuthCounter'] )) {
                unset( $this->meta['InvalidAuthCounter'] );
            }

            if (isset( $this->meta['InvalidAuthTimeout'] )) {
                unset( $this->meta['InvalidAuthTimeout'] );
            }

            $this->commit();

            return true;
        }

        /**
         * Unsuccessful login attempt - bump up the invalid auth counter
         */

        $TmpUser->meta['InvalidAuthCounter'] = !isset( $TmpUser->meta['InvalidAuthCounter'] ) ? 1 : $TmpUser->meta['InvalidAuthCounter']++;

        $TmpUser->addNote(sprintf("Invalid login attempt %d", $TmpUser->meta['InvalidAuthCounter']));
        $TmpUser->commit();
        $this->refresh();

        if ($TmpUser->meta['InvalidAuthCounter'] === 3) {
            $TmpUser->meta['InvalidAuthTimeout'] = strtotime("+10 minutes");
            $TmpUser->addNote("Too many invalid login attempts - account disabled for ten minutes");
            $TmpUser->commit();
            $this->refresh();

            throw new Exception("You've attempted to log in with the wrong password too many times. As a result, we're disabling this account for the next ten minutes. <a href='/account/resetpassword'>Can't remember your password?</a>");
        }

        $this->reset();

        return false;
    }

    /**
     * Check if this account is active
     * @since Version 3.8.7
     * @return boolean
     */

    public function isActive() {

        if ($this->getUserAccountStatus() == self::STATUS_ACTIVE) {
            return true;
        }

        return false;
        
    }

    /**
     * Check if this user is pending activation
     * @since Version 3.9.1
     * @return boolean
     */

    public function getUserAccountStatus() {

        if ((boolean)$this->active === true) {
            return self::STATUS_ACTIVE;
        }

        $BanControl = new BanControl;
        $BanControl->loadUsers(true);

        if (!empty( $BanControl->lookupUser($this->id) )) {
            return self::STATUS_BANNED;
        }

        if ((boolean)$this->active === false) {
            return self::STATUS_UNACTIVATED;
        }

        throw new Exception("Cannot determine the status of this user account");
        
    }

    /**
     * Set this user's account to match a given status flag
     * @since Version 3.9.1
     *
     * @param int $status
     *
     * @return \Railpage\Users\User
     */

    public function setUserAccountStatus($status = false) {

        if (!$status) {
            return $this;
        }

        switch ($status) {
            case self::STATUS_ACTIVE :
                $this->active = true;
                break;

            case self::STATUS_UNACTIVATED :
                $this->active = false;
                break;

        }

        $this->commit();

        return $this;
    }

    /**
     * Refresh user data from the database
     * @since Version 3.8.7
     * @return \Railpage\Users\User
     */

    public function refresh() {

        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->load();
        }

        return $this;
    }

    /**
     * Reset this user object, for security purposes
     * @since Version 3.8.7
     * @return \Railpage\Users\User
     */

    public function reset() {

        foreach ($this as $key => $value) {
            unset( $this->$key );
        }

        $this->guest();

        return $this;
    }

    /**
     * Check if the supplied password is considered safe
     * @since Version 3.8.7
     *
     * @param string $password
     *
     * @return boolean
     */

    public function safePassword($password = false) {

        if (empty( $password ) || is_null(filter_var($password, FILTER_SANITIZE_STRING))) {
            throw new Exception("You gotta supply a password...");
        }

        if (strlen($password) < 7) {
            return false;
        }

        if (strtolower($password) === strtolower($this->username)) {
            return false;
        }

        /**
         * Bad passwords
         */

        $bad = [ "password", "pass", "012345", "0123456", "01234567", "012345678", "0123456789",
            "123456", "1234567", "12345678", "123456789", "1234567890", "letmein", "changeme",
            "qwerty", "111111", "iloveyou", "railpage", "password1", "azerty", "000000",
            "trains", "railway" ];

        if (in_array($password, $bad)) {
            return false;
        }

        /**
         * Looks good
         */

        return true;
    }

    /**
     * Validate an email address
     * @since Version 3.8.7
     *
     * @param string $email
     *
     * @return \Railpage\Users\User
     */

    public function validateEmail($email = false) {

        if (!$email || empty( $email )) {
            throw new Exception("No email address was supplied.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(sprintf("%s is not a valid email address", $email));
        }

        $query = "SELECT user_id FROM nuke_users WHERE user_email = ? AND user_id != ?";

        $result = $this->db->fetchAll($query, array( $email, $this->id ));

        if (count($result)) {
            throw new Exception(sprintf("The requested email address %s is already in use by a different user.", $email));
        }

        return $this;
    }

    /**
     * Get IP address this user has posted/logged in from
     * @since Version 3.9
     * @return array
     *
     * @param \DateTime $time Find IP addresses since the provided DateTime object
     */

    public function getIPs($time = false) {

        $ips = array();

        /**
         * Get posts
         */

        $query = "SELECT DISTINCT poster_ip FROM nuke_bbposts WHERE poster_id = ?";

        if ($time instanceof DateTime) {
            $query .= " AND post_time >= " . $time->getTimestamp();
        }

        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $ips[] = decode_ip($row['poster_ip']);
        }

        /**
         * Get logins
         */

        $query = "SELECT DISTINCT login_ip FROM log_logins WHERE user_id = ? AND login_ip NOT IN ('" . implode("','", $ips) . "')";

        if ($time instanceof DateTime) {
            $query .= " AND login_time >= " . $time->getTimestamp();
        }

        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $ips[] = $row['login_ip'];
        }

        natsort($ips);
        $ips = array_values($ips);

        return $ips;
    }

    /**
     * Check if the desired username is available
     * @since Version 3.9
     * @var string $username
     * @return boolean
     */

    public function isUsernameAvailable($username = false) {

        if (!$username && !empty( $this->username )) {
            $username = $this->username;
        }

        if (!$username) {
            throw new Exception("Cannot check if username is available because no username was provided");
        }

        return (new Base)->username_available($username);
    }

    /**
     * Check if the desired email address is available
     * @since Version 3.9
     * @var string $username
     * @return boolean
     */

    public function isEmailAvailable($email = false) {

        if (!$email && !empty( $this->contact_email )) {
            $email = $this->contact_email;
        }

        if (!$email) {
            throw new Exception("Cannot check if email address is available because no email address was provided");
        }

        return (new Base)->email_available($email);
    }

    /**
     * Log user activity
     * @since Version 3.9.1
     *
     * @param int    $moduleId
     * @param string $url
     * @param string $pagetitle
     * @param string $ip
     *
     * @return \Railpage\Users\User
     */

    public function logUserActivity($moduleId, $url = null, $pagetitle = null, $ipaddr = null) {

        // Temporarily commented out for performance
        return $this;

        if (!filter_var($moduleId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot log user activity because no module ID was provided");
        }

        if (is_null($url)) {
            throw new Exception("Cannot log user activity because no URL was provided");
        }

        if (is_null($pagetitle)) {
            throw new Exception("Cannot log user activity because no pagetitle was provided");
        }

        if (is_null($ipaddr)) {
            $ipaddr = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_URL);
        }

        if (is_null($ipaddr)) {
            throw new Exception("Cannot log user activity because no remote IP was provided");
        }

        $data = array(
            "user_id"   => $this->id,
            "ip"        => $ipaddr,
            "module_id" => $moduleId,
            "url"       => $url,
            "pagetitle" => $pagetitle
        );

        $this->db->insert("log_useractivity", $data);

        return $this;
    }

    /**
     * Find a list of duplicate usernames
     * @since Version 3.9.1
     * @return array
     */

    public function findDuplicates() {

        $query = "SELECT
u.user_id, u.username, u.user_active, u.user_regdate, u.user_regdate_nice, u.user_email, u.user_lastvisit, 
(SELECT COUNT(p.post_id) AS num_posts FROM nuke_bbposts AS p WHERE p.poster_id = u.user_id) AS num_posts,
(SELECT MAX(pt.post_time) AS post_time FROM nuke_bbposts AS pt WHERE pt.poster_id = u.user_id) AS last_post_time
FROM nuke_users AS u 
WHERE u.username = ? OR u.user_email = ?";

        $params = array(
            $this->username,
            $this->contact_email
        );

        return $this->db->fetchAll($query, $params);
    }

    /**
     * Validate user avatar
     * @since Version 3.9.1
     * @return \Railpage\Users\User
     *
     * @param boolean $force
     */

    public function validateAvatar($force = false) {

        if (!empty( $this->avatar )) {
            if ($force || ( empty( $this->avatar_width ) || empty( $this->avatar_height ) || $this->avatar_width == 0 || $this->avatar_height == 0 )) {
                if ($size = @getimagesize($this->avatar)) {

                    $Config = AppCore::getConfig();

                    if ($size[0] >= $Config->AvatarMaxWidth || $size[1] >= $Config->AvatarMaxHeight) {
                        $this->avatar = sprintf("https://static.railpage.com.au/image_resize.php?w=%d&h=%d&image=%s", $Config->AvatarMaxWidth, $Config->AvatarMaxHeight, urlencode($this->avatar));
                        $this->avatar_filename = $this->avatar;
                        $this->avatar_width = $size[0];
                        $this->avatar_height = $size[1];
                    } else {
                        $this->avatar_width = $size[0];
                        $this->avatar_height = $size[1];
                        $this->avatar_filename = $this->avatar;
                    }

                    $this->commit(true);

                    return $this;
                }
            }
        }

        $this->avatar = function_exists("format_avatar") ? format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120) : "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
        $this->avatar_filename = function_exists("format_avatar") ? format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120) : "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
        $this->avatar_width = 120;
        $this->avatar_height = 120;

        return $this;
    }

    /**
     * Get alerts for this user
     * @since Version 3.9.1
     *
     * @param \Zend_Acl $acl
     *
     * @return array
     */

    public function getAlerts(\Zend_Acl $acl) {

        $query = array();
        $params = array();

        if (!$this->guest) {

            // Replies to my watched threads
            //$query['forums'] = "SELECT 'Forum replies' AS module, COUNT(t.topic_id) AS num, '/forums/replies' AS url, GROUP_CONCAT(CONCAT(t.forum_id, ':', t.topic_id, ':', p.post_time) SEPARATOR ';') AS extra FROM nuke_bbtopics AS t LEFT JOIN nuke_bbposts AS p ON t.topic_last_post_id = p.post_id WHERE t.topic_id IN (SELECT topic_id FROM nuke_bbtopics_watch WHERE user_id = ?)";
            //$params[] = $this->id;

            // Private messages
            $query['pms'] = "SELECT 'Private Messages' AS module, 'Private Message' AS subtitle, '<i class=\"fa fa-inbox\"></i>' AS icon, COUNT(*) AS num, '/messages' AS url, NULL AS extra FROM nuke_bbprivmsgs WHERE privmsgs_to_userid = ? AND privmsgs_type = 5";
            $params[] = $this->id;

            $query['forums'] = "SELECT
                'Forums' AS module, 'Subscribed thread' AS subtitle, '<i class=\"fa fa-comment-o\"></i>' AS icon, COUNT(*) AS num, '/account/watchedthreads' AS url, NULL AS extra 
                FROM (
                    SELECT t.topic_title
                    FROM nuke_bbtopics AS t
                    LEFT JOIN nuke_bbposts AS p ON t.topic_last_post_id = p.post_id
                    LEFT JOIN nuke_bbtopics_view AS v ON t.topic_id = v.topic_id
                    WHERE t.topic_id IN (
                        SELECT topic_id FROM nuke_bbtopics_watch WHERE user_id = ?
                    )
                    AND p.post_time > UNIX_TIMESTAMP(v.viewed)
                    AND v.user_id = ?
                    GROUP BY t.topic_id
                    ORDER BY p.post_time DESC
                ) AS topics";
            $params[] = $this->id;
            $params[] = $this->id;

        }

        /**
         * Staff-level stuff
         */

        $acl_role = $this->aclRole(RP_GROUP_MODERATORS);

        if ($acl->isAllowed($acl_role, "railpage.downloads", "manage")) {
            $query['feedback'] = "SELECT 'Feedback' AS module, 'Feedback item' AS subtitle, '<i class=\"fa fa-bullhorn\"></i>' AS icon, COUNT(*) AS num, '/feedback/manage' AS url, NULL AS extra FROM feedback WHERE status = 1";
            $query['events'] = "SELECT 'Events' AS module, 'New event' AS subtitle, '<i class=\"fa fa-calendar-o\"></i>' AS icon, COUNT(*) AS num, '/events?mode=pending' AS url, NULL AS extra FROM event WHERE status = 0";
            $query['eventdates'] = "SELECT 'Event Dates' AS module, 'Event date' AS subtitle, '<i class=\"fa fa-calendar\"></i>' AS icon, COUNT(*) AS num, '/events?mode=pending' AS url, NULL AS extra FROM event_dates WHERE status = 0";
            $query['locations'] = "SELECT 'Locations' AS module, 'New location' AS subtitle, '<i class=\"fa fa-map-marker\"></i>' AS icon, COUNT(*) AS num, '/locations/pending' AS url, NULL AS extra FROM location WHERE active = 0";
            $query['reports'] = "SELECT 'Reported posts' AS module, 'Reported forum post' AS subtitle, '<i class=\"fa fa-exclamation-circle\"></i>' AS icon, COUNT(*) AS num, '/f-report-cp.htm' AS url, NULL AS extra FROM phpbb_reports_posts WHERE report_status = 1 AND report_action_time > 0";
            $query['news'] = "SELECT 'News' AS module, 'News article' AS subtitle, '<i class=\"fa fa-newspaper-o\"></i>' AS icon, COUNT(*) AS num, '/news/pending' AS url, NULL AS extra FROM nuke_stories WHERE approved = 0";
            $query['glossary'] = "SELECT 'Glossary' AS module, 'Glossary addition' AS subtitle, '<i class=\"fa fa-inbox\"></i>' AS icon, COUNT(*) AS num, '/glossary?mode=manage.pending' AS url, NULL AS extra FROM glossary WHERE status = 0";
            $query['downloads'] = "SELECT 'Downloads' AS module, 'New download' AS subtitle, '<i class=\"fa fa-download\"></i>' AS icon, COUNT(*) AS num, '/downloads/manage' AS url, NULL AS extra FROM download_items WHERE active = 1 AND approved = 0";
        }

        if ($acl->isAllowed($acl_role, "railpage.gallery.competition", "manage") && $this->id != 2) {
            $query['photocomp'] = "SELECT 'Photo comp' AS module, 'Photo comp submission' AS subtitle, '<i class=\"fa fa-camera\"></i>' AS icon, COUNT(*) AS num, '/gallery/comp' AS url, NULL AS extra FROM image_competition_submissions WHERE status = 0";
        }

        /**
         * Maintainer-level stuff
         */

        $acl_role = $this->aclRole(RP_GROUP_LOCOS);

        if ($acl->isAllowed($acl_role, "railpage.locos", "edit") && $this->id != 2) {
            $query['locos'] = "SELECT 'Locos' AS module, 'Locos correction' AS subtitle, '<i class=\"fa fa-train\"></i>' AS icon, COUNT(*) AS num, '/locos/corrections' AS url, NULL AS extra FROM loco_unit_corrections WHERE status = 0";
        }

        if (empty( $query )) {
            return false;
        }

        // Assemble the query
        $query = implode(" UNION ", $query);
        $query .= " ORDER BY module";

        /**
         * Get the result from the database
         */

        $result = $this->db->fetchAll($query, $params);

        return $result;
    }

    /**
     * Get preferences by section
     * @since Version 3.9.1
     *
     * @param string $section
     *
     * @return array
     */

    public function getPreferences($section = false) {

        $prefs = is_object($this->preferences) ? json_decode(json_encode($this->preferences), true) : $this->preferences;

        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true);
        }

        /**
         * Default preferences
         */

        $defaults = [
            "platform"       => "prod",
            "home"           => "home",
            "HTTPStaticHost" => "static.railpage.com.au",
            "homepage"       => "smooth",
            "showads"        => true,
            "notifications"  => [
                "dailynews"   => true,
                "curatednews" => true,
            ]
        ];

        /**
         * Merge default preferences and sort alphabetically
         */

        if (is_array($prefs)) {
            $prefs = array_merge($defaults, $prefs);
        } else {
            $prefs = $defaults;
        }

        uksort($prefs, "strnatcasecmp");

        /**
         * Return a section of the preferences if asked
         */

        if ($section) {
            if (!isset( $prefs[$section] )) {
                throw new Exception(sprintf("The requested preferences section \"%s\" does not exist", $section));
            }

            return $prefs[$section];
        }

        return $prefs;
    }

    /**
     * Save preferences
     * @since Version 3.9.1
     * @return \Railpage\Users\User
     */

    public function savePreferences() {

        $args = func_get_args();

        /**
         * Single parameter, assume we've been given *all* preferences
         */

        if (count($args) === 1) {
            $this->preferences = $args[0];
            $this->commit();

            return $this;
        }

        /**
         * Two parameters - assume we've been given IN THIS ORDER a section and associated preferences
         */

        if (count($args) === 2) {

            $prefs = $this->getPreferences();
            $prefs[$args[0]] = $args[1];

            $this->preferences = $prefs;
            $this->commit();

            return $this;
        }

        /**
         * No parameters passed
         */

        return $this;
    }

    /**
     * Get an array of this users' data
     * @since Version 3.9.1
     * @return array
     */

    public function getArray() {

        return array(
            "id"            => $this->id,
            "username"      => $this->username,
            "realname"      => $this->real_name,
            "contact_email" => $this->contact_email,
            "avatar"        => $this->avatar,
            "avatar_sizes"  => Utility\AvatarUtility::getAvatarSizes($this->avatar),
            "url"           => $this->url->getURLs()
        );
    }

    /**
     * Check if we have a valid human identifier tag
     * @since Version 3.9.1
     * @return boolean
     */

    public function validateHuman() {

        if (!is_array($this->meta)) {
            $this->meta = json_decode($this->meta, true);
        
            if (!is_array($this->meta)) {
                $this->meta = array();
            }
        }

        if (!isset( $this->meta['captchaTimestamp'] ) || empty( $this->meta['captchaTimestamp'] ) || $this->meta['captchaTimestamp'] - self::HUMAN_VALIDATION_TTL <= time()) {
            return false;
        }

        return true;
    }

    /**
     * Set our valid human tag
     * @since Version 3.9.1
     * @return \Railpage\Users\User
     */

    public function setValidatedHuman() {

        if (!is_array($this->meta)) {
            $this->meta = json_decode($this->meta, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->meta = array();
            }
        }

        $this->meta['captchaTimestamp'] = strtotime("+24 hours");
        $this->commit();

        return $this;
    }

}