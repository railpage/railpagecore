<?php

/**
 * RSS feed scraper for PageUp
 *
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Jobs;

use Exception;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;
use Railpage\AppCore;
use Railpage\Module;
use Railpage\Url;
use Railpage\Organisations\Organisation;
use Zend\Http\Client;
use Railpage\RSS\Consume;
use Railpage\Debug;

/**
 * Scraper
 */
class Scraper extends AppCore {

    /**
     * RSS feed URL
     *
     * @since Version 3.8.7
     * @var string $feed
     */

    private $feed;

    /**
     * RSS feed provider
     *
     * @since Version 3.8.7
     * @var string $provider
     */

    private $provider;

    /**
     * Company offering these jobs
     *
     * @since Version 3.8.7
     * @var \Railpage\Organisations\Organisation $Organisation
     */

    private $Organisation;

    /**
     * Constructor
     *
     * @since Version 3.8.7
     *
     * @param string|bool                          $url
     * @param string                               $provider
     * @param \Railpage\Organisations\Organisation $Organisation
     */

    public function __construct($url = false, $provider = "pageuppeople", Organisation $Organisation) {

        parent::__construct();

        $this->Module = new Module("jobs");

        if (is_string($url)) {
            $this->feed = $url;
        }

        if (is_string($provider)) {
            $this->provider = $provider;
        }

        if ($Organisation instanceof Organisation) {
            $this->Organisation = $Organisation;
        }
    }

    /**
     * Scrape the RSS feed
     *
     * @since Version 3.8.7
     * @return \Railpage\Jobs\Scraper
     * @throws \Exception if $this->feed has not been set
     */

    public function fetch() {

        if (!is_string($this->feed)) {
            throw new Exception("Cannot fetch jobs from RSS feed because no RSS feed was provided");
        }

        $jobs = array();

        $Consume = new Consume;
        $Consume->addFeed($this->feed)->scrape()->parse();

        foreach ($Consume->getFeeds() as $feed) {
            foreach ($feed['items'] as $item) {

                /**
                 * Format the location
                 */

                $location = explode("|", $item['extra']['location']);

                if (!is_array($location) || count($location) === 1) {
                    $location = array(
                        0 => "Australia",
                        1 => is_array($location) ? $location[0] : $location
                    );
                }

                /**
                 * Get geolocation data using Yahoo's WhereOnEarth (woe) API, if our function exists.
                 */

                $timezone = "Australia/Melbourne";

                if (!function_exists("getWoeData")) {
                    $location = array(
                        "name" => sprintf("%, %s", $location[1], $location[0])
                    );
                } else {
                    $woe = getWoeData(sprintf("%s, %s", $location[1], $location[0]));

                    if (count($woe)) {
                        $location = array(
                            "name" => $woe['places']['place'][0]['name'],
                            "lat"  => $woe['places']['place'][0]['centroid']['latitude'],
                            "lon"  => $woe['places']['place'][0]['centroid']['longitude']
                        );
                    }
                }

                /**
                 * Assemble this job into an associative array
                 */

                $row = array(
                    "title"       => $item['title'],
                    "id"          => $item['extra']['refNo'],
                    "date"        => array(
                        "open"  => new DateTime($item['date']),
                        "close" => new DateTime(empty( $item['extra']['closingDate'] ) ? sprintf("@%d",
                            strtotime("+1 month")) : $item['extra']['closingDate'])
                    ),
                    "category"    => $item['category'],
                    "url"         => array(
                        "view"  => $item['link'],
                        "apply" => $item['extra']['applyLink']
                    ),
                    "location"    => $location,
                    "salary"      => 0,
                    "type"        => explode(",", $item['extra']['workType']),
                    "description" => $item['description']
                );

                $row['date']['open']->setTimeZone(new DateTimeZone($timezone));
                $row['date']['close']->setTimeZone(new DateTimeZone($timezone));

                /**
                 * Add this job to the list of jobs found in this scrape
                 */

                $jobs[] = $row;
            }
        }

        $this->jobs = $jobs;

        return $this;
    }

    /**
     * Scrape the RSS feed
     *
     * @since Version 3.8.7
     * @return $this
     * @throws \Exception if $this->feed has not been set
     */

    public function fetchOld() {

        if (!is_string($this->feed)) {
            throw new Exception("Cannot fetch jobs from RSS feed because no RSS feed was provided");
        }

        $jobs = array();

        /**
         * Zend HTTP config
         */

        $config = array(
            'adapter'     => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
        );

        $client = new Client($this->feed, $config);

        /**
         * Fetch the RSS feed
         */

        $response = $client->send();
        $content = $response->getContent();

        /**
         * Load the SimpleXML object
         */

        $xml = new SimpleXMLElement($content);

        /**
         * Load the namespaces
         */

        $ns = $xml->getNamespaces(true);

        /**
         * Loop through each RSS item and build an associative array of the data we need
         */

        foreach ($xml->channel->item as $item) {
            $job = $item->children($ns['job']);

            $location = explode("|", $job->location);

            if (!is_array($location) || count($location) === 1) {
                $location = array(
                    0 => "Australia",
                    1 => is_array($location) ? $location[0] : $location
                );
            }

            /**
             * Get geolocation data using Yahoo's WhereOnEarth (woe) API, if our function exists.
             */

            $timezone = "Australia/Melbourne";

            if (!function_exists("getWoeData")) {
                $location = array(
                    "name" => sprintf("%, %s", $location[1], $location[0])
                );
            } else {
                $woe = getWoeData(sprintf("%s, %s", $location[1], $location[0]));

                if (count($woe)) {
                    $location = array(
                        "name" => $woe['places']['place'][0]['name'],
                        "lat"  => $woe['places']['place'][0]['centroid']['latitude'],
                        "lon"  => $woe['places']['place'][0]['centroid']['longitude']
                    );
                }
            }

            /**
             * Assemble this job into an associative array
             */

            $row = array(
                "title"       => $item->title,
                "id"          => $job->refNo,
                "date"        => array(
                    "open"  => new DateTime($item->pubDate),
                    "close" => new DateTime($job->closingDate)
                ),
                "category"    => $job->category,
                "url"         => array(
                    "view"  => $item->link,
                    "apply" => $job->applyLink
                ),
                "location"    => $location,
                "salary"      => 0,
                "type"        => explode(",", $job->workType),
                "description" => $job->description
            );

            $row['date']['open']->setTimeZone(new DateTimeZone($timezone));
            $row['date']['close']->setTimeZone(new DateTimeZone($timezone));

            /**
             * Add this job to the list of jobs found in this scrape
             */

            $jobs[] = $row;
        }

        $this->jobs = $jobs;

        return $this;
    }

    /**
     * Store jobs in the database
     *
     * @since Version 3.8.7
     * @return $this
     */

    public function store() {

        foreach ($this->jobs as $job) {

            if (strtolower(trim($job['title'])) != "test job") {
                $Job = new Job;

                $Job->title = $job['title'];
                $Job->Organisation = $this->Organisation;
                $Job->desc = $job['description'];
                $Job->Open = $job['date']['open'];
                $Job->expiry = $job['date']['close'];
                $Job->salary = $job['salary'];
                $Job->duration = implode(", ", $job['type']);
                $Job->Location = new Location($job['location']['name']);
                $Job->Classification = new Classification(strval($job['category']));
                $Job->reference_id = $job['id'];
                $Job->url = new Url;
                $Job->url->apply = strval($job['url']['apply']);

                try {
                    $Job->commit();
                } catch (Exception $e) {
                    Debug::printArray($e->getMessage());
                    die;
                }
            }
        }
    }
}
