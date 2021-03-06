<?php

/**
 * Store and fetch image data from our local database
 *
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\Users\Utility\UserUtility;
use Railpage\Images\Utility\ImageUtility;
use Railpage\API;
use Railpage\AppCore;
use Railpage\Place;
use Railpage\PlaceUtility;
use Railpage\Locos\Factory as LocosFactory;
use Railpage\Locos\Locomotive;
use Railpage\Locos\LocoClass;
use Railpage\Locos\Liveries\Livery;
use Railpage\Module;
use Railpage\Url;
use Railpage\Debug;
use Exception;
use DateTime;
use DateTimeZone;
use DateInterval;
use stdClass;
use DomDocument;
use GuzzleHttp\Client;
use Zend_Db_Expr;
use Railpage\ContentUtility;

/**
 * Store and fetch data of Flickr, Weston Langford, etc images in our local database
 *
 * @since Version 3.8.7
 */
class Image extends AppCore {

    /**
     * Max age of data before it requires refreshing
     */

    const MAXAGE = 14;

    /**
     * Image ID in sparta
     *
     * @since Version 3.8.7
     * @var int $id
     */

    public $id;

    /**
     * Image title
     *
     * @since Version 3.8.7
     * @var string $title
     */

    public $title;

    /**
     * Image description
     *
     * @since Version 3.8.7
     * @var string $description
     */

    public $description;

    /**
     * Image provider
     *
     * @since Version 3.8.7
     * @var string $provider
     */

    public $provider;

    /**
     * Photo ID from the image provider
     *
     * @since Version 3.8.7
     * @var int $photo_id
     */

    public $photo_id;

    /**
     * Geographic place where this photo was taken
     *
     * @since Version 3.8.7
     * @var \Railpage\Place $Place
     */

    public $Place;

    /**
     * Nearest geoplace to this photo
     *
     * @since Version 3.9.1
     * @var \Railpage\Place $GeoPlace
     */

    public $GeoPlace;

    /**
     * Array of image sizes and their source URLs
     *
     * @since Version 3.8.7
     * @var array $sizes
     */

    public $sizes;

    /**
     * Object of image page URLs
     *
     * @since Version 3.8.7
     * @var \stdClass $links
     */

    public $links;

    /**
     * Object representing the image author
     *
     * @since Version 3.8.7
     * @var \stdClass $author
     */

    public $author;

    /**
     * URL to this image on Railpage
     *
     * @since Version 3.8.7
     * @var string $url
     */

    public $url;

    /**
     * Source URL to this image
     *
     * @since Version 3.8.7
     * @var string $source
     */

    public $source;

    /**
     * Image meta data
     *
     * @since Version 3.8.7
     * @var array $meta
     */

    public $meta;

    /**
     * Date modified
     *
     * @since Version 3.8.7
     * @var \DateTime $Date
     */

    public $Date;

    /**
     * Date the photo was taken
     *
     * @since Version 3.9.1
     * @var \DateTime $DateCaptured
     */

    public $DateCaptured;

    /**
     * Memcached identifier key
     *
     * @since Version 3.8.7
     * @var string $mckey
     */

    public $mckey;

    /**
     * JSON data string of this image
     *
     * @since Version 3.8.7
     * @var string $json
     */

    public $json;

    /**
     * Image provider options
     *
     * @since Version 3.9.1
     * @var array $providerOptions
     */

    private $providerOptions = array();

    /**
     * Image provider
     *
     * @since Version 3.9.1
     * @var object $ImageProvider
     */

    private $ImageProvider;

    /**
     * Latitude
     *
     * @since Version 3.9.1
     * @var float $lat
     */

    private $lat;

    /**
     * Longitude
     *
     * @since Version 3.9.1
     * @var float $lon
     */

    private $lon;

    /**
     * Constructor
     *
     * @since Version 3.8.7
     *
     * @param int $id
     * @param int $option
     */

    public function __construct($id = null, $option = null) {

        parent::__construct();

        $timer = Debug::getTimer();

        $this->GuzzleClient = new Client;

        $this->Module = new Module("images");

        if ($this->id = filter_var($id, FILTER_VALIDATE_INT)) {
            $this->load($option);
        }

        Debug::logEvent(__METHOD__, $timer);

    }

    /**
     * Populate this object from an array
     *
     * @since Version 3.9.1
     * @return void
     */

    public function populateFromArray($row) {

        $this->provider = $row['provider'];
        $this->photo_id = $row['photo_id'];
        $this->Date = new DateTime($row['modified']);
        $this->DateCaptured = !isset( $row['captured'] ) || is_null($row['captured']) ? null : new DateTime($row['captured']);

        $this->title = !empty( $row['meta']['title'] ) ? ContentUtility::FormatTitle($row['meta']['title']) : "Untitled";
        $this->description = $row['meta']['description'];
        $this->sizes = $row['meta']['sizes'];
        $this->links = $row['meta']['links'];
        $this->meta = $row['meta']['data'];
        $this->lat = $row['lat'];
        $this->lon = $row['lon'];

        #printArray($row['meta']);die;

        if (!$this->DateCaptured instanceof DateTime) {
            if (isset( $row['meta']['data']['dates']['taken'] )) {
                $this->DateCaptured = new DateTime($row['meta']['data']['dates']['taken']);
            }
        }

        /**
         * Normalize some sizes
         */

        if (count($this->sizes)) {
            $this->sizes = Images::normaliseSizes($this->sizes);
        }

        $this->url = Utility\Url::CreateFromImageID($this->id);

        if (empty( $this->mckey )) {
            $this->mckey = sprintf("railpage:image=%d", $this->id);
        }

    }

    /**
     * Populate this image object
     *
     * @since Version 3.9.1
     * @return void
     *
     * @param int $option
     */

    private function load($option = null) {

        Debug::RecordInstance();

        $this->mckey = sprintf("railpage:image=%d", $this->id);

        if (( defined("NOREDIS") && NOREDIS == true ) || !$row = $this->Redis->fetch($this->mckey)) {

            Debug::LogCLI("Fetching data for " . $this->id . " from database");

            $query = "SELECT i.title, i.description, i.id, i.provider, i.photo_id, i.modified, i.meta, i.lat, i.lon, i.user_id, i.geoplace, i.captured FROM image AS i WHERE i.id = ?";

            $row = $this->db->fetchRow($query, $this->id);
            $row['meta'] = json_decode($row['meta'], true);

            $this->Redis->save($this->mckey, $row, strtotime("+24 hours"));
        }

        $this->populateFromArray($row);

        if ($this->provider == "rpoldgallery") {
            $GalleryImage = new \Railpage\Gallery\Image($this->photo_id);
            $this->url->source = $GalleryImage->url->url;

            if (empty( $this->meta['source'] )) {
                $this->meta['source'] = $this->url->source;
            }
        }

        if (!isset( $row['user_id'] )) {
            $row['user_id'] = 0;
        }

        /**
         * Update the database row
         */

        if (( ( !isset( $row['title'] ) || empty( $row['title'] ) || is_null($row['title']) ) && !empty( $this->title ) ) ||
            ( ( !isset( $row['description'] ) || empty( $row['description'] ) || is_null($row['description']) ) && !empty( $this->description ) )
        ) {
            $row['title'] = $this->title;
            $row['description'] = $this->description;

            $this->Redis->save($this->mckey, $row, strtotime("+24 hours"));

            $this->commit();
        }

        /**
         * Load the author. If we don't know who it is, attempt to re-populate the data
         */

        if (isset( $row['meta']['author'] )) {
            $this->author = json_decode(json_encode($row['meta']['author']));

            if (isset( $this->author->railpage_id ) && $row['user_id'] === 0) {
                $row['user_id'] == $this->author->railpage_id;
            }

            if (filter_var($row['user_id'], FILTER_VALIDATE_INT)) {
                $this->author->User = UserFactory::CreateUser($row['user_id']);
            }
        } else {
            Debug::LogCLI("No author found in local cache - refreshing from " . $this->provider);
            Debug::LogEvent("No author found in local cache - refreshing from " . $this->provider);
            $this->populate(true, $option);
        }

        /**
         * Unless otherwise instructed load the places object if lat/lng are present
         */

        if ($option != Images::OPT_NOPLACE && round($row['lat'], 3) != "0.000" && round($row['lon'],
                3) != "0.000"
        ) {
            try {
                $this->Place = Place::Factory($row['lat'], $row['lon']);
            } catch (Exception $e) {
                // Throw it away. Don't care.
            }
        }

        /**
         * Set the source URL
         */

        if (isset( $this->meta['source'] )) {
            $this->source = $this->meta['source'];
        } else {
            switch ($this->provider) {
                case "flickr" :
                    if (function_exists("base58_encode")) {
                        $this->source = "https://flic.kr/p/" . base58_encode($this->photo_id);
                    }
            }
        }

        /**
         * Create an array/JSON object
         */

        $this->getJSON();

    }

    /**
     * Validate changes to this image
     *
     * @since Version 3.8.7
     * @return boolean
     * @throws \Exception if $this->provider is empty
     * @throws \Exception if $this->photo_id is empty
     */

    public function validate() {

        if (empty( $this->provider ) && strpos($this->author->id, "@") !== false) {
            $this->provider = "flickr";
        }

        if (empty( $this->provider )) {
            throw new Exception("Image provider cannot be empty");
        }

        if (!filter_var($this->photo_id)) {
            throw new Exception("Photo ID from the image provider cannot be empty");
        }

        return true;
    }

    /**
     * Commit changes to this image
     *
     * @since Version 3.8.7
     * @return boolean
     */

    public function commit() {

        $this->validate();

        $user_id = isset( $this->author->User ) && $this->author->User instanceof User ? $this->author->User->id : 0;

        $author = $this->author;
        unset( $author->User );

        $data = array(
            "title"       => $this->title,
            "description" => $this->description,
            "captured"    => $this->DateCaptured instanceof DateTime ? $this->DateCaptured->format("Y-m-d H:i:s") : null,
            "provider"    => $this->provider,
            "photo_id"    => $this->photo_id,
            "user_id"     => $user_id,
            "meta"        => json_encode(array(
                "title"       => $this->title,
                "description" => $this->description,
                "sizes"       => $this->sizes,
                "links"       => $this->links,
                "data"        => $this->meta,
                "author"      => $author
            ))
        );

        if ($this->Place instanceof Place) {
            $data['lat'] = $this->Place->lat;
            $data['lon'] = $this->Place->lon;
        }

        // Update
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->Memcached->delete($this->mckey);
            $this->Redis->delete($this->mckey);

            $where = array(
                "id = ?" => $this->id
            );

            $Date = new DateTime();
            $data['modified'] = $Date->format("Y-m-d g:i:s");

            $this->db->update("image", $data, $where);

            $this->getJSON();
            
            return $this;
        }
        
        // Insert
        $this->db->insert("image", $data);
        $this->id = $this->db->lastInsertId();
        $this->url = Utility\Url::CreateFromImageID($this->id);

        $this->getJSON();

        return $this;
    }

    /**
     * Check if this image has become stale
     *
     * @since Version 3.8.7
     * @return boolean
     */

    public function isStale() {

        if (!$this->Date instanceof DateTime) {
            return true;
        }

        $Now = new DateTime;
        $Diff = $this->Date->diff($Now);

        if ($Diff->d >= self::MAXAGE) {
            $this->Memcached->delete($this->mckey);

            Debug::LogCLI("Image ID " . $this->id . " (photo ID " . $this->photo_id . ") is stale");

            return true;
        }

        return false;
    }

    /**
     * Get an instance of the image provider
     *
     * @since Version 3.9.1
     * @return object
     */

    public function getProvider() {

        if (!is_null($this->ImageProvider)) {
            return $this->ImageProvider;
        }

        return ImageUtility::CreateImageProvider($this->provider, $this->providerOptions);

    }

    /**
     * Set the image provider's options
     *
     * @since Version 3.9.1
     *
     * @param array $options
     *
     * @return \Railpage\Images\Image
     */

    public function setProviderOptions($options) {

        $this->providerOptions = $options;

        if (!is_null($this->ImageProvider)) {
            $this->ImageProvider->setOptions($this->providerOptions);
        }

        return $this;

    }

    /**
     * Populate this image with fresh data
     *
     * @since Version 3.8.7
     * @return $this
     *
     * @param boolean $force
     * @param int     $option
     *
     * @throws \Exception if the photo cannot be found on the image provider
     * @todo  Split this into utility functions
     */

    public function populate($force = false, $option = null) {

        if ($force === false && !$this->isStale()) {
            return $this;
        }

        Debug::LogCLI("Fetching data from " . $this->provider . " for image ID " . $this->id . " (photo ID " . $this->photo_id . ")");

        /**
         * Start the debug timer
         */

        if (RP_DEBUG) {
            global $site_debug;
            $debug_timer_start = microtime(true);
        }

        /**
         * New and improved populator using image providers
         */

        $Provider = $this->getProvider();

        $data = false;

        try {
            $data = $Provider->getImage($this->photo_id, $force);
        } catch (Exception $e) {
            $expected = array(
                sprintf(
                    "Unable to fetch data from Flickr: Photo \"%s\" not found (invalid ID) (1)",
                    $this->photo_id
                ),
                "Unable to fetch data from Flickr: Photo not found (1)"
            );

            if (in_array($e->getMessage(), $expected)) {

                $where = ["image_id = ?" => $this->id];
                $this->db->delete("image_link", $where);

                $where = ["id = ?" => $this->id];
                $this->db->delete("image", $where);

                throw new Exception("Photo no longer available from " . $this->provider);
            }
        }

        if ($data) {
            $this->sizes = $data['sizes'];
            $this->title = empty( $data['title'] ) ? "Untitled" : $data['title'];
            $this->description = $data['description'];
            $this->meta = array(
                "dates" => array(
                    "posted" => $data['dates']['uploaded'] instanceof DateTime ? $data['dates']['uploaded']->format("Y-m-d H:i:s") : $data['dates']['uploaded']['date'],
                    "taken"  => $data['dates']['taken'] instanceof DateTime ? $data['dates']['taken']->format("Y-m-d H:i:s") : $data['dates']['taken']['date'],
                )
            );

            $this->author = new stdClass;
            $this->author->username = $data['author']['username'];
            $this->author->realname = !empty( $data['author']['realname'] ) ? $data['author']['realname'] : $data['author']['username'];
            $this->author->id = $data['author']['id'];
            $this->author->url = "https://www.flickr.com/photos/" . $this->author->id;

            if ($user_id = UserUtility::findFromFlickrNSID($this->author->id)) {
                $data['author']['railpage_id'] = $user_id;
            }

            if (isset( $data['author']['railpage_id'] ) && filter_var($data['author']['railpage_id'],
                    FILTER_VALIDATE_INT)
            ) {
                $this->author->User = UserFactory::CreateUser($data['author']['railpage_id']);
            }

            /**
             * Load the tags
             */

            if (isset( $data['tags'] ) && is_array($data['tags']) && count($data['tags'])) {
                foreach ($data['tags'] as $row) {
                    $this->meta['tags'][] = $row['raw'];
                }
            }

            /**
             * Load the Place object
             */

            if ($option != Images::OPT_NOPLACE && isset( $data['location'] ) && !empty( $data['location'] )) {
                try {
                    $this->Place = Place::Factory($data['location']['latitude'], $data['location']['longitude']);
                } catch (Exception $e) {
                    // Throw it away. Don't care.
                }
            }

            $this->links = new stdClass;
            $this->links->provider = isset( $data['urls']['url'][0]['_content'] ) ? $data['urls']['url'][0]['_content'] : $data['urls'][key($data['urls'])];

            $this->commit();
            $this->cacheGeoData();

            return true;
        }

        /**
         * Fetch data in various ways for different photo providers
         */

        switch ($this->provider) {

            /**
             * Picasa
             */

            case "picasaweb" :

                if (
                    empty( $this->meta ) && 
                    !is_null(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL)) && 
                    strpos(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL), "picasaweb.google.com")
                ) {
                    $album = preg_replace(
                        "@(http|https)://picasaweb.google.com/([a-zA-Z\-\.]+)/(.+)@", "$2",
                        filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL)
                    );

                    if (is_string($album)) {
                        $update_url = sprintf(
                            "https://picasaweb.google.com/data/feed/api/user/%s/photoid/%s?alt=json",
                            $album, 
                            $this->photo_id
                        );
                    }
                }

                if (isset( $update_url )) {
                    $data = file_get_contents($update_url);
                    $json = json_decode($data, true);

                    $this->meta = array(
                        "title"       => $json['feed']['subtitle']['$t'],
                        "description" => $json['feed']['title']['$t'],
                        "dates"       => array(
                            "posted" => date("Y-m-d H:i:s", $json['feed']['gphoto$timestamp']['$t']),
                        ),
                        "sizes"       => array(
                            "original" => array(
                                "width"  => $json['feed']['gphoto$width']['$t'],
                                "height" => $json['feed']['gphoto$height']['$t'],
                                "source" => str_replace(
                                    sprintf("/s%d/", $json['feed']['media$group']['media$thumbnail'][0]['width']),
                                    sprintf("/s%d/", $json['feed']['gphoto$width']['$t']),
                                    $json['feed']['media$group']['media$thumbnail'][0]['url']
                                ),
                            ),
                            "largest"  => array(
                                "width"  => $json['feed']['gphoto$width']['$t'],
                                "height" => $json['feed']['gphoto$height']['$t'],
                                "source" => str_replace(
                                    sprintf("/s%d/", $json['feed']['media$group']['media$thumbnail'][0]['width']),
                                    sprintf("/s%d/", $json['feed']['gphoto$width']['$t']),
                                    $json['feed']['media$group']['media$thumbnail'][0]['url']
                                ),
                            ),
                        ),
                        "photo_id"    => $json['feed']['gphoto$id']['$t'],
                        "album_id"    => $json['feed']['gphoto$albumid']['$t'],
                        "updateurl"   => sprintf("%s?alt=json", $json['feed']['id']['$t'])
                    );

                    foreach ($json['feed']['media$group']['media$thumbnail'] as $size) {
                        if ($size['width'] <= 500 && $size['width'] > 200) {
                            $this->meta['sizes']['small'] = array(
                                "width"  => $size['width'],
                                "height" => $size['height'],
                                "source" => $size['url']
                            );
                        }

                        if ($size['width'] <= 200) {
                            $this->meta['sizes']['small'] = array(
                                "width"  => $size['width'],
                                "height" => $size['height'],
                                "source" => $size['url']
                            );
                        }

                        if ($size['width'] <= 1024 && $size['width'] > 500) {
                            $this->meta['sizes']['large'] = array(
                                "width"  => $size['width'],
                                "height" => $size['height'],
                                "source" => $size['url']
                            );
                        }
                    }

                    foreach ($json['feed']['link'] as $link) {
                        if ($link['rel'] == "alternate" && $link['type'] == "text/html") {
                            $this->meta['source'] = $link['href'];
                        }
                    }

                    if ($option != Images::OPT_NOPLACE && isset( $json['feed']['georss$where']['gml$Point'] ) && is_array($json['feed']['georss$where']['gml$Point'])) {
                        $pos = explode(" ", $json['feed']['georss$where']['gml$Point']['gml$pos']['$t']);
                        $this->Place = Place::Factory($pos[0], $pos[1]);
                    }

                    $this->title = $this->meta['title'];
                    $this->description = $this->meta['description'];

                    $this->author = new stdClass;
                    $this->author->username = $album;
                    $this->author->id = $album;
                    $this->author->url = sprintf("%s/%s", $json['feed']['generator']['uri'], $album);
                }

                $this->sizes = $this->meta['sizes'];

                $this->commit();
                break;

            /**
             * Vicsig
             */

            case "vicsig" :

                if (strpos(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL), "vicsig.net/photo")) {
                    $this->meta['source'] = filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_STRING);

                    $response = $this->GuzzleClient->get($this->meta['source']);

                    if ($response->getStatusCode() != 200) {
                        throw new Exception(
                            sprintf(
                                "Failed to fetch image data from %s: HTTP error %s",
                                $this->provider, 
                                $response->getStatusCode()
                            )
                        );
                    }

                    /**
                     * Start fetching it
                     */

                    $data = $response->getBody();

                    $doc = new DomDocument();
                    $doc->loadHTML($data);

                    $images = $doc->getElementsByTagName("img");

                    foreach ($images as $element) {

                        if (!empty( $element->getAttribute("src") ) && !empty( $element->getAttribute("alt") )) {
                            #$image_title = $element->getAttribute("alt");

                            $this->sizes['original'] = array(
                                "source" => $element->getAttribute("src"),
                                "width"  => $element->getAttribute("width"),
                                "height" => $element->getAttribute("height"),
                            );

                            if (substr($this->sizes['original']['source'], 0, 1) == "/") {
                                $this->sizes['original']['source'] = "http://www.vicsig.net" . $this->sizes['original']['source'];
                            }

                            break;
                        }
                    }

                    $desc = $doc->getElementsByTagName("i");

                    foreach ($desc as $element) {
                        if (!isset( $image_desc )) {
                            $text = trim($element->nodeValue);
                            $text = str_replace("\r\n", "\n", $text);
                            $text = explode("\n", $text);

                            /**
                             * Loop through the exploded text and remove the obvious date/author/etc
                             */

                            foreach ($text as $k => $line) {

                                // Get the author
                                if (preg_match("@Photo: @i", $line)) {
                                    $this->author = new stdClass;
                                    $this->author->realname = str_replace("Photo: ", "", $line);
                                    $this->author->url = filter_input(
                                        INPUT_SERVER, 
                                        "HTTP_REFERER",
                                        FILTER_SANITIZE_STRING
                                    );
                                    
                                    unset( $text[$k] );
                                }

                                // Get the date
                                try {
                                    $this->meta['dates']['posted'] = (new DateTime($line))->format("Y-m-d H:i:s");
                                    unset( $text[$k] );
                                } catch (Exception $e) {
                                    // Throw it away
                                }
                            }

                            /**
                             * Whatever's left must be the photo title and description
                             */

                            foreach ($text as $k => $line) {
                                if (empty( $this->title )) {
                                    $this->title = $line;
                                    continue;
                                }

                                $this->description .= $line;
                            }

                            $this->links = new stdClass;
                            $this->links->provider = filter_input(
                                INPUT_SERVER, 
                                "HTTP_REFERER",
                                FILTER_SANITIZE_STRING
                            );

                            $this->commit();
                        }
                    }

                }

                break;

        }

        /**
         * End the debug timer
         */

        if (RP_DEBUG) {
            $site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
        }

        return $this;
    }

    /**
     * Link this image to a loco, location, etc
     *
     * @param string     $namespace
     * @param int|string $namespaceKey
     *
     * @throws \Exception if $namespace is null
     * @throws \Exception if $namespaceKey is null
     * @return \Railpage\Images\Image
     */

    public function addLink($namespace = null, $namespaceKey = null) {

        if (is_null($namespace)) {
            throw new Exception("Parameter 1 (namespace) cannot be empty");
        }

        if (!filter_var($namespaceKey, FILTER_VALIDATE_INT)) {
            throw new Exception("Parameter 2 (namespace_key) cannot be empty");
        }

        $id = $this->db->fetchOne(
            "SELECT id FROM image_link WHERE namespace = ? AND namespace_key = ? AND image_id = ?",
            array($namespace, $namespaceKey, $this->id))
        ;

        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            $data = array(
                "image_id"      => $this->id,
                "namespace"     => $namespace,
                "namespace_key" => $namespaceKey,
                "ignored"       => 0
            );

            $this->db->insert("image_link", $data);
        }

        $this->getJSON();

        return $this;
    }

    /**
     * Generate the JSON data string
     *
     * @return $this
     */

    public function getJSON() {

        if (isset( $this->author )) {
            $author = clone $this->author;

            if (isset( $author->User ) && $author->User instanceof User) {
                $author->User = $author->User->getArray();
            }
        }

        $data = array(
            "id"          => $this->id,
            "title"       => $this->title,
            "description" => $this->description,
            "score"       => $this->getScore(),
            "provider"    => array(
                "name"     => $this->provider,
                "photo_id" => $this->photo_id
            ),
            "sizes"       => Images::NormaliseSizes($this->sizes),
            "srcset"      => implode(", ", Utility\ImageUtility::generateSrcSet($this)),
            "author"      => isset( $author ) ? $author : false,
            "url"         => $this->url instanceof Url ? $this->url->getURLs() : array(),
            "dates"       => array()
        );

        $times = ["posted", "taken"];


        #printArray($this->meta['dates']);die;

        foreach ($times as $time) {
            if (isset( $this->meta['dates'][$time] )) {
                $Date = filter_var($this->meta['dates'][$time],
                    FILTER_VALIDATE_INT) ? new DateTime("@" . $this->meta['dates'][$time]) : new DateTime($this->meta['dates'][$time]);

                $data['dates'][$time] = array(
                    "absolute" => $Date->format("Y-m-d H:i:s"),
                    "nice"     => $Date->Format("d H:i:s") == "01 00:00:00" ? $Date->Format("F Y") : $Date->format("F j, Y, g:i a"),
                    "relative" => ContentUtility::RelativeTime($Date)
                );
            }
        }

        if ($this->Place instanceof Place) {
            $data['place'] = array(
                "url"     => $this->Place->url,
                "lat"     => $this->Place->lat,
                "lon"     => $this->Place->lon,
                "name"    => $this->Place->name,
                "country" => array(
                    "code" => $this->Place->Country->code,
                    "name" => $this->Place->Country->name,
                    "url"  => $this->Place->Country->url
                )
            );
        }

        $this->json = json_encode($data);

        return $this;
    }

    /**
     * Get locos in this image
     *
     * @since Version 3.8.7
     * @return array
     */

    public function getLocos() {

        $query = "SELECT namespace_key AS loco_id FROM image_link WHERE image_id = ? AND namespace = ? AND ignored = 0";

        $return = array();

        foreach ($this->db->fetchAll($query, array($this->id, "railpage.locos.loco")) as $row) {
            $return[] = $row['loco_id'];
        }

        return $return;
    }

    /**
     * Find Railpage objects (loco, class, livery) in this image
     *
     * @since Version 3.8.7
     *
     * @param string  $namespace
     * @param boolean $force
     *
     * @return \Railpage\Images\Image;
     * @throws \Exception if $namespace is null or empty
     */

    public function findObjects($namespace = null, $force = false) {

        if (is_null($namespace)) {
            throw new Exception("Parameter 1 (namespace) cannot be empty");
        }

        $key = sprintf("railpage:images.image=%d;objects.namespace=%s;lastupdate", $this->id, $namespace);

        $lastupdate = $this->Memcached->fetch($key);

        if (!$force && $lastupdate && $lastupdate > strtotime("1 day ago")) {
            return $this;
        }

        /**
         * Start the debug timer
         */

        $timer = Debug::GetTimer();

        switch ($namespace) {

            case "railpage.locos.loco" :
                if (isset( $this->meta['tags'] )) {

                    foreach ($this->meta['tags'] as $tag) {
                        if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
                            Debug::LogEvent(__METHOD__ . " :: #1 Instantating new LocoClass object with ID " . $matches[1] . "  ");
                            $LocoClass = LocosFactory::CreateLocoClass($matches[1]);
                        }
                    }

                    foreach ($this->meta['tags'] as $tag) {
                        if (isset( $LocoClass ) && $LocoClass instanceof LocoClass && preg_match("@railpage:loco=([a-zA-Z0-9]+)@", $tag, $matches) ) {
                            Debug::LogEvent(__METHOD__ . " :: #2 Instantating new LocoClass object with class ID " . $LocoClass->id . " and loco number " . $matches[1] . "  ");
                            $Loco = LocosFactory::CreateLocomotive(false, $LocoClass->id, $matches[1]);

                            if (filter_var($Loco->id, FILTER_VALIDATE_INT)) {
                                $this->addLink($Loco->namespace, $Loco->id);
                            }
                        }
                    }

                    foreach ($this->db->fetchAll("SELECT id AS class_id, flickr_tag AS class_tag FROM loco_class") as $row) {
                        foreach ($this->meta['tags'] as $tag) {
                            if (stristr($tag, $row['class_tag']) && strlen(str_replace($row['class_tag'] . "-", "", $tag) > 0) ) {
                                $loco_num = str_replace($row['class_tag'] . "-", "", $tag);
                                Debug::LogEvent(__METHOD__ . " :: #3 Instantating new LocoClass object with class ID " . $row['class_id'] . " and loco number " . $loco_num . "  ");
                                $Loco = LocosFactory::CreateLocomotive(false, $row['class_id'], $loco_num);

                                if (filter_var($Loco->id, FILTER_VALIDATE_INT)) {
                                    $this->addLink($Loco->namespace, $Loco->id);

                                    if (!$Loco->hasCoverImage()) {
                                        $Loco->setCoverImage($this);
                                    }

                                    if (!$Loco->Class->hasCoverImage()) {
                                        $Loco->Class->setCoverImage($this);
                                    }
                                }
                            }
                        }
                    }
                }

                break;

            case "railpage.locos.class" :
                if (isset( $this->meta['tags'] )) {
                    foreach ($this->db->fetchAll("SELECT id AS class_id, flickr_tag AS class_tag FROM loco_class") as $row) {
                        foreach ($this->meta['tags'] as $tag) {
                            if ($tag == $row['class_tag']) {
                                $LocoClass = LocosFactory::CreateLocoClass($row['class_id']);

                                if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
                                    $this->addLink($LocoClass->namespace, $LocoClass->id);
                                }
                            }
                        }
                    }

                    foreach ($this->meta['tags'] as $tag) {
                        if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
                            $LocoClass = LocosFactory::CreateLocoClass($matches[1]);

                            if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
                                $this->addLink($LocoClass->namespace, $LocoClass->id);

                                if (!$LocoClass->hasCoverImage()) {
                                    $LocoClass->setCoverImage($this);
                                }
                            }
                        }
                    }
                }

                break;

            case "railpage.locos.liveries.livery" :
                if (isset( $this->meta['tags'] )) {
                    foreach ($this->meta['tags'] as $tag) {
                        if (preg_match("@railpage:livery=([0-9]+)@", $tag, $matches)) {
                            $Livery = new Livery($matches[1]);

                            if (filter_var($Livery->id, FILTER_VALIDATE_INT)) {
                                $this->addLink($Livery->namespace, $Livery->id);
                            }
                        }
                    }
                }

                break;
        }

        Debug::LogEvent(__METHOD__ . "(\"" . $namespace . "\")", $timer);

        $this->Memcached->save($key, time());

        return $this;
    }

    /**
     * Get objects linked to this image
     *
     * @since Version 3.8.7
     * @return array
     *
     * @param string $namespace
     */

    public function getObjects($namespace = null) {

        $params = array(
            $this->id
        );
        
        $where_namespace = "";

        if (!is_null($namespace)) {
            $params[] = $namespace;
            $where_namespace = "AND namespace = ?";
        }

        $rs = $this->db->fetchAll(
            "SELECT * FROM image_link WHERE image_id = ? " . $where_namespace . " AND ignored = 0",
            $params
        );

        return $rs;
    }

    /**
     * Mark an image as ignored
     *
     * @since Version 3.8.7
     *
     * @param boolean $ignored
     *
     * @return boolean
     */

    public function ignored($ignored = 1, $linkId = 0) {

        $data = array(
            "ignored" => intval($ignored)
        );

        $where = array(
            "image_id = ?" => $this->id
        );

        if (filter_var($linkId, FILTER_VALIDATE_INT) && $linkId > 0) {
            $where['id = ?'] = $linkId;
        }

        $this->db->update("image_link", $data, $where);

        return true;
    }

    /**
     * Get an associative array representing this object
     *
     * @since Version 3.9.1
     * @return array
     */

    public function getArray() {

        $this->getJSON();

        return json_decode($this->json, true);
    }

    /**
     * Suggest locos to tag
     *
     * @since Version 3.9.1
     * @return array
     *
     * @param boolean $skipTagged Remove locos already tagged in this photo from the list of suggested locos
     */

    public function suggestLocos($skipTagged = null) {
        
        return Utility\Tagger::suggestLocos($this, $skipTagged); 
        
    }

    /**
     * Suggest liveries to tag based on other locos in this class
     *
     * @since Version 3.9.1
     * @return array
     */

    public function suggestLiveries() {

        $query = '
            SELECT livery.livery_id AS id, livery.livery AS name, livery.photo_id
            FROM loco_livery AS livery
                LEFT JOIN image_link AS link ON link.namespace_key = livery.livery_id
            WHERE link.namespace = "railpage.locos.liveries.livery"
                AND image_id IN (
                    SELECT image_id 
                    FROM image_link 
                    WHERE namespace = ? 
                        AND namespace_key IN (
                            SELECT namespace_key AS class_id 
                            FROM image_link 
                            WHERE namespace = ? 
                                AND image_id = ?
                                AND ignored = 0
                        )
                )
                AND livery_id NOT IN (
                    SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.liveries.livery" AND image_id = ?
                )
            GROUP BY livery.livery_id
            ORDER BY link.id DESC';

        $params = [
            "railpage.locos.loco",
            "railpage.locos.loco",
            $this->id,
            $this->id
        ];

        $liveries = $this->db->fetchAll($query, $params);

        if (count($liveries) === 0) {
            $params = [
                "railpage.locos.class",
                "railpage.locos.class",
                $this->id,
                $this->id
            ];

            $liveries = $this->db->fetchAll($query, $params);
        }

        return $liveries;
    }

    /**
     * Insert this photo into the geocache
     *
     * @since Version 3.9.1
     * @return \Railpage\Images\Image
     */

    private function cacheGeoData() {

        if (!$this->Place instanceof Place) {
            return $this;
        }

        $data = [
            "photo_id"   => $this->photo_id,
            "lat"        => $this->Place->lat,
            "lon"        => $this->Place->lon,
            "owner"      => $this->author->url,
            "ownername"  => $this->author->username,
            "title"      => $this->title,
            "tags"       => isset( $this->meta['tags'] ) ? $this->meta['tags'] : array(),
            "dateadded"  => "",
            "dateupload" => "",
            "datetaken"  => ""
        ];

        $sizes = [75, 100, 240, 500, 640, 1024, 320, 800];

        foreach ($this->sizes as $k => $row) {
            $i = array_search($row['width'], $sizes);
            if ($i !== false) {
                $size = sprintf("size%d", $i);
                $width = sprintf("%s_w", $size);
                $height = sprintf("%s_h", $size);

                $data[$size] = $row['source'];
                $data[$width] = $row['width'];
                $data[$height] = $row['height'];

                continue;
            }

            if ($k == "original" || $k == "largest" || $row['width'] >= 1024) {
                $size = sprintf("size%d", 8);
                $width = sprintf("%s_w", $size);
                $height = sprintf("%s_h", $size);

                $data[$size] = $row['source'];
                $data[$width] = $row['width'];
                $data[$height] = $row['height'];

                continue;
            }
        }

        if (is_array($data['tags'])) {
            $data['tags'] = implode(" ", $data['tags']);
        }

        $query = "INSERT IGNORE INTO flickr_geodata SET ";

        $terms = count($data);
        foreach ($data as $key => $val) {
            $terms--;

            $query .= $key . ' = ' . $this->db->quote($val);

            if ($terms) {
                $query .= ', ';
            }
        }

        $cn = $this->db->getConnection();

        $cn->query($query);

        return $this;

    }

    /**
     * Bump the hit counter
     *
     * @since Version 3.9.1
     * @return \Railpage\Images\Image
     */

    public function hit() {

        $data = [
            "hits_today"   => new Zend_Db_Expr('hits_today + 1'),
            "hits_weekly"  => new Zend_Db_Expr('hits_weekly + 1'),
            "hits_overall" => new Zend_Db_Expr('hits_overall + 1')
        ];

        $where = [
            "id = ?" => $this->id
        ];

        $this->db->update("image", $data, $where);

        return $this;

    }

    /**
     * Update the geoplace reference for this image
     *
     * @since Version 3.9.1
     * @return void
     */

    public function updateGeoPlace() {

        if (!filter_var($this->lat, FILTER_VALIDATE_FLOAT) || !filter_var($this->lat, FILTER_VALIDATE_FLOAT)) {
            return;
        }

        $timer = microtime(true);

        $GeoPlaceID = PlaceUtility::findGeoPlaceID($this->lat, $this->lon);

        #var_dump($GeoPlaceID);die;

        $data = ["geoplace" => $GeoPlaceID];

        $where = ["id = ?" => $this->id];
        $this->db->update("image", $data, $where);

        $this->Memcached->delete($this->mckey);
        $this->Redis->delete($this->mckey);

        Debug::logEvent(__METHOD__, $timer);
        Debug::LogCLI(__METHOD__, $timer);

        return;

    }

    /**
     * Hide this photo from the pool and searches
     *
     * @since Version 3.9.1
     * @return \Railpage\Images\Image
     */

    public function hide() {

        $data = ["hidden" => 1];
        $where = ["id = ?" => $this->id];

        $this->db->update("image", $data, $where);

        return $this;

    }

    /**
     * Get the score out of 100 of this image, based on titles, tags, description, etc.
     *
     * @since Version 3.10.0
     * @return int
     */

    public function getScore() {

        $score = 0;

        if ($this->title != "Untitled") {
            $score += 20;
        }

        if (strlen($this->title) > 30) {
            $score -= strlen($this->title) - 30;
        }

        $score += min(( strlen($this->description) / 20 ) * 3, 70);

        if (!preg_match("/([a-z])/", $this->description)) {
            $score -= 34;
        }

        if (filter_var($this->lat, FILTER_VALIDATE_FLOAT) && filter_var($this->lon, FILTER_VALIDATE_FLOAT)) {
            $score += 14;
        }

        return min($score, 100);

    }
}
