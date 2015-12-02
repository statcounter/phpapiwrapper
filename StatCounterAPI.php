<?php

/**
 * StatCounterAPI PHP API Wrapper
 * Version 1.1
 * http://api.statcounter.com
 */
class StatCounterAPI {

    private $sc_base_url = "https://api.statcounter.com";
    private $sc_version_num = "3";
    private $sc_username;
    private $sc_password;
    private $sc_query_string;

    /**
     * StatCounterAPI constructor.
     *
     * @param $sc_username the StatCounter username
     * @param $sc_password the StatCounter password
     */
    public function __construct($sc_username, $sc_password) {

        date_default_timezone_set("Europe/Dublin");
        $this->sc_username = $sc_username;
        $this->sc_password = $sc_password;
        $this->sc_query_string = "";
    }

    /**
     * Returns true if the username and password of the instance are valid
     *
     * @return bool Returns true if the username and password of the instance are valid
     */
    public function valid_login() {

        $url = $this->build_url("user_projects");

        $xml = simplexml_load_file($url);

        return ($xml->attributes()->status == "ok");
    }

    /**
     * Gets the user's details
     *
     * @return array array with the user's details
     * @throws Exception invalid credentials exception
     */
    public function get_user_details() {

        $url = $this->build_url("user_details");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array("name" => $xml->sc_data->name[0],
                "email" => $xml->sc_data->email[0],
                "log_quota" => $xml->sc_data->log_quota[0]
            );

            return $result;
        }
        throw new Exception("XML error: Check your username and password.");
    }

    /**
     * Gets all of the user projects for the user with username and password as specified in initial construction
     *
     * @return array an array of the user's project details
     * @throws Exception invalid credentials exception
     */
    public function get_user_project_details() {

        $url = $this->build_url("user_projects");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $sc_project) {

                $result[] = array("project_id" => $sc_project->project_id,
                    "project_name" => $sc_project->project_name,
                    "project_url" => $sc_project->url
                );
            }

            return $result;
        }
        throw new Exception("XML error: Check your username and password.");
    }

    /**
     * Creates a StatCounter project with the given website URL and website title and timezone setting
     * If successful, returns an associative array with the newly created Project ID and Security Code
     *
     * @param string $website_url the URL of the website to create a project for
     * @param string $website_title the title of the website to create a project for
     * @param string $timezone the timezone to create a project for
     * @return array an array with the new project ID and security code
     * @throws Exception invalid credentials exception
     */
    public function create_statcounter_project($website_url, $website_title, $timezone) {

        if (!$this->valid_timezone($timezone)) {

            throw new Exception("Invalid timezone entered");
        }

        date_default_timezone_set($timezone);

        $this->sc_query_string =
            "&wt=" . urlencode($website_title) .
            "&wu=" . urlencode($website_url) .
            "&tz=" . urlencode($timezone) .
            "&ps=0";

        $url = $this->build_url("add_project");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $sc_project) {

                $result = array("project_id" => $xml->sc_data->project_id,
                    "security_code" => $xml->sc_data->security_code
                );
            }

            return $result;
        }

        throw new Exception("Unable to create project. Check your login details.");
    }

    /**
     * Returns the recent X number of keywords to visit the site associated with the given project ID
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @param bool|false $exclude_encrypted_kws
     * @return array
     * @throws Exception
     */
    public function get_recent_keyword_activity_date_range($project_id, $start_date, $end_date, $num_of_results=20, $offset=0, $exclude_encrypted_kws=false) {

        $this->sc_query_string =
            "&s=keyword-activity" .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            for ($i = 0; $i < count($xml->sc_data); $i++) {

                $keyword = $xml->sc_data[$i]->keyword;

                if ($exclude_encrypted_kws) {

                    if (strpos($keyword, "Encrypted Search")) { continue; }
                }

                $result[] = $keyword;
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the popular pages for the given project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @param string $count_type the type of page visit
     * @return array
     * @throws Exception
     */
    public function get_popular_pages_date_range($project_id, $start_date, $end_date, $num_of_results=20, $offset=0, $count_type="page_view") {

        $this->sc_query_string =
            "&s=popular" .
            "&pi=" . $project_id .
            "&ct=" . $count_type .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "page_title" => $page->page_title,
                    "page_url" => $page->url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");

    }

    /**
     * Gets entry pages for the given project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_entry_pages_date_range($project_id, $start_date, $end_date, $num_of_results=20, $offset=0) {

        $this->sc_query_string =
            "&s=entry" .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "page_title" => $page->page_title,
                    "page_url" => $page->page_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the exit pages for the given project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_exit_pages_date_range($project_id, $start_date, $end_date, $num_of_results=20, $offset=0) {

        $this->sc_query_string =
            "&s=exit" .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $exit_page) {

                $result[] = array("page_views" => $exit_page->page_views,
                    "page_title" => $exit_page->title,
                    "page_url" => $exit_page->page_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets came from for the given project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $external include external URLs or not
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_came_from_date_range($project_id, $start_date, $end_date, $external=1, $num_of_results=20, $offset=0) {

        $this->sc_query_string =
            "&s=camefrom" .
            "&pi=" . $project_id .
            "&e=" . $external .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "referring_url" => $page->referring_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the percentages of browsers that have visited the site with the given project ID.
     * Default is all devices, but you may also specify:
     * 1. desktop
     * 2. mobile
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param string $device the device type
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_browsers_date_range($project_id, $start_date, $end_date, $device="all", $num_of_results=20, $offset=0) {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=browsers" .
            "&de=" . $device .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $browser) {

                $result[] = array("browser_page_views" => $browser->page_views,
                    "browser_name" => $browser->browser_name,
                    "browser_version" => $browser->browser_version,
                    "browser_percentage" => $browser->percentage
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the operating system ratios for the given project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param string $device the device type
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_operating_systems_date_range($project_id, $start_date, $end_date, $device="all", $num_of_results=20, $offset=0) {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string = "&s=os" .
            "&de=" . $device .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $os) {

                $result[] = array("os_page_views" => $os->page_views,
                    "os_name" => $os->os_name,
                    "os_percentage" => $os->percentage
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the recent pageload activity for the given project
     *
     * @param string $project_id the project ID
     * @param string $device the device type
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_recent_pageload_activity_date_range($project_id, $device="all", $start_date, $end_date, $num_of_results=20, $offset=0) {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=pageload" .
            "&de=" . $device .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $pageload) {

                $result[] = array("page_url" => $pageload->page_url,
                    "time" => $pageload->time,
                    "referring_url" => $pageload->referring_url,
                    "page_title" => $pageload->page_title,
                    "browser_name" => $pageload->browser_name,
                    "browser_version" => $pageload->browser_version,
                    "os_name" => $pageload->os_name,
                    "device_vendor" => $pageload->device_vendor,
                    "device_model" => $pageload->device_model,
                    "se_keywords" => $pageload->se_keywords,
                    "resolution_width" => $pageload->resolution_width,
                    "resolution_height" => $pageload->resolution_height,
                    "isp" => $pageload->isp,
                    "city" => $pageload->city,
                    "state" => $pageload->state,
                    "country" => $pageload->country,
                    "ip_address" => $pageload->ip_address
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the exit link activity for the given project
     *
     * @param string $project_id the project ID
     * @param string $device the device type
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_exit_link_activity_date_range($project_id, $device="all", $start_date, $end_date, $num_of_results=20, $offset=0) {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=exit-link-activity" .
            "&de=" . $device .
            "&pi=" . $project_id .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&n=" . $num_of_results .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $exit_link) {

                $result[] = array("link" => $exit_link->link,
                    "time" => $exit_link->time,
                    "page_url" => $exit_link->page_url,
                    "page_title" => $exit_link->page_title,
                    "ip_address" => $exit_link->ip_number
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the download link activity for the given project
     *
     * @param string $project_id the project ID
     * @param string $device the device type
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception
     */
    public function get_download_link_activity_date_range($project_id, $device="all", $start_date, $end_date, $num_of_results=20, $offset=0) {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=download-link-activity" .
            "&de=" . $device .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $download_link) {

                $result[] = array("link" => $download_link->link,
                    "time" => $download_link->time,
                    "page_url" => $download_link->page_url,
                    "page_title" => $download_link->page_title,
                    "ip_address" => $download_link->ip_number,
                    "extension" => $download_link->extension
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");

    }

    /**
     * Gets the summary stats for a given project ID on a given date
     *
     * @param string $project_id project ID
     * @param string $date date in MM/DD/YYYY format
     * @return array
     * @throws Exception invalid credentials exception
     */
    public function get_summary_stats_date($project_id, $date) {

        return $this->get_summary_stats_date_range($project_id, $date, $date)[0];
    }

    /**
     * Retrieves an array of daily summary stats for each day of the given date range
     *
     * @param string $project_id project ID
     * @param string $start_date start date in MM/DD/YYYY format
     * @param string $end_date end date in MM/DD/YYYY format
     * @return array
     * @throws Exception invalid credentials exception
     */
    public function get_summary_stats_date_range($project_id, $start_date, $end_date) {

        $this->sc_query_string =
            "&s=summary" .
            "&g=daily" .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach($xml->sc_data as $site) {

                $result[] = array("date" => $site->date,
                    "page_views" => $site->page_views,
                    "unique_visits" => $site->unique_visits,
                    "returning_visits" => $site->returning_visits,
                    "first_time_visits" => $site->first_time_visits
                );
            }

            return $result;
        }

        throw new Exception("XML error: Check your login information and project ID.");
    }

    /**
     * Fetches the given number of recent visitors results for a project
     *
     * @param string $project_id the project ID
     * @param string $start_date the start date in MM/DD/YYYY format
     * @param string $end_date the end date in MM/DD/YYYY format
     * @param int $num_of_results the number of results to return
     * @param int $offset the numerical offset (used for pagination to fetch next page)
     * @return array
     * @throws Exception invalid credentials exception
     */
    public function get_recent_visitors_date_range($project_id, $start_date, $end_date, $num_of_results=20, $offset=0) {

        $this->sc_query_string =
            "&s=visitor" .
            "&g=daily" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            $this->build_start_end_date_url_string($start_date, $end_date) .
            "&o=" . $offset;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach($xml->sc_data as $visitor) {

                $result[] = array(
                    "log_visits" => $visitor->log_visits,
                    "entries_in_visit" => $visitor->entries_in_visit,
                    "entry_time" => $visitor->entry_t,
                    "entry_url" => $visitor->entry_url,
                    "entry_title" => $visitor->entry_title,
                    "se_keywords" => $visitor->se_keywords,
                    "link" => $visitor->link,
                    "country_name" => $visitor->country_name,
                    "state" => $visitor->state,
                    "resolution" => $visitor->res,
                    "exit_time"=> $visitor->exit_t,
                    "exit_url" => $visitor->exit_url,
                    "exit_page_title" => $visitor->exit_title,
                    "returning_count" => $visitor->returning_count,
                    "browser_name" => $visitor->browser_name,
                    "browser_version" => $visitor->browser_version,
                    "os" => $visitor->os,
                    "resolution_width" => $visitor->width,
                    "resolution_" => $visitor->height,
                    "javascript" => $visitor->javascript,
                    "country" => $visitor->country,
                    "city" => $visitor->city,
                    "isp" => $visitor->isp,
                    "ip_address" => $visitor->ip_address,
                    "latitude" => $visitor->latitude,
                    "longitude" => $visitor->longitude,
                    "num_entry" => $visitor->num_entry,
                    "visit_length" => $visitor->visit_length
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your login credentials and project ID.");
    }

    /**
     * Constructs the URL with SHA1
     *
     * @param string $function the StatCounter API function used
     * @return string the constructed URL
     */
    private function build_url($function) {

        $base = $this->sc_base_url . "/" . $function . "/";

        $this->sc_query_string = "?vn=" . $this->sc_version_num .
            "&t=" . time() .
            "&u=" . $this->sc_username .
            $this->sc_query_string .
            "&f=xml";

        $sha1 = sha1($this->sc_query_string . $this->sc_password);

        $url = $base . $this->sc_query_string . "&sha1=" . $sha1;

        return $url;
    }

    /**
     * Splits an inputted date and returns the month/day/year as an array
     * Expects date format to be MM/DD/YYYY
     *
     * @param string $date date in MM/DD/YYYY format
     * @return string date as an array
     * @throws Exception
     */
    private function date_to_array($date) {

        if (!$this->valid_date($date)) {

            throw new Exception("Invalid start date entered.");
        }

        $date_arr = explode("/", $date);

        return $date_arr;
    }

    /**
     * Validates the inputted device
     *
     * @param string $device the device type
     * @return bool true if valid according to StatCounter API rules
     */
    private function valid_device($device) {

        return ($device == "all" || $device == "desktop" || $device == "mobile");
    }

    /**
     * Returns true if the given date is of valid format mm/dd/yyyy, else returns false
     *
     * @param string $date the date to validate in mm/dd/yyyy format
     * @return bool true if the given date is in valid format
     */
    private function valid_date($date) {

        $date = explode("/", $date);

        if (count($date) != 3) { return false; }

        $month = $date[0];
        $day = $date[1];
        $year = $date[2];

        if ($month <= 0 || $month > 12) { return false; }
        if ($day < 1 || $day > cal_days_in_month(CAL_GREGORIAN, $month, $year)) { return false; }
        if ($year > date("Y")) { return false; }

        return true;
    }

    /**
     * Returns true if the given timezone string is valid e.g. America/Chicago
     *
     * @param string $timezone the timezone to check
     * @return bool true if the timezone is valid
     */
    private function valid_timezone($timezone) {

        return in_array($timezone, timezone_identifiers_list());
    }

    private function build_start_end_date_url_string($start_date, $end_date) {

        if (!$this->valid_date($start_date) || !$this->valid_date($end_date)) {

            throw new Exception("Invalid date(s) entered.");
        }

        $string = "";

        $string = $string . "&sm=" . $this->date_to_array($start_date)[0] .
            "&sd=" . $this->date_to_array($start_date)[1] .
            "&sy=" . $this->date_to_array($start_date)[2] .
            "&em=" . $this->date_to_array($end_date)[0] .
            "&ed=" . $this->date_to_array($end_date)[1] .
            "&ey=" . $this->date_to_array($end_date)[2];

        return $string;
    }

    //
    // Deprecated Methods
    //

    /**
     * Returns the recent X number of keywords to visit the site associated with the given project ID
     */
    public function get_recent_keyword_activity($project_id, $num_of_results, $exclude_encrypted_kws=false) {

        $this->sc_query_string =
            "&s=keyword-activity" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            for ($i = 0; $i < count($xml->sc_data); $i++) {

                $keyword = $xml->sc_data[$i]->keyword;

                if ($exclude_encrypted_kws) {

                    if (strpos($keyword, "Encrypted Search")) { continue; }
                }

                $result[] = $keyword;
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the X most popular pages for the given project id
     */
    public function get_popular_pages($project_id, $num_of_results, $count_type="page_view") {

        $this->sc_query_string =
            "&s=popular" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            "&ct=" . $count_type;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "page_title" => $page->page_title,
                    "page_url" => $page->url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");

    }

    /**
     * Gets the X number of entry pages for the given project id
     */
    public function get_entry_pages($project_id, $num_of_results) {

        $this->sc_query_string =
            "&s=entry" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "page_title" => $page->page_title,
                    "page_url" => $page->page_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the X number of exit pages for the given project id
     */
    public function get_exit_pages($project_id, $num_of_results) {

        $this->sc_query_string =
            "&s=exit" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $exit_page) {

                $result[] = array("page_views" => $exit_page->page_views,
                    "page_title" => $exit_page->title,
                    "page_url" => $exit_page->page_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the X number of referring URLs for the given project id
     */
    public function get_came_from($project_id, $num_of_results, $external=1) {

        $this->sc_query_string =
            "&s=camefrom" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            "&e=" . $external;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $page) {

                $result[] = array("page_views" => $page->page_views,
                    "referring_url" => $page->referring_url
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }


    /**
     * Gets the percentages of browsers that have visited the site with the given project ID.
     * Default is all devices, but you may also specify:
     * 1. desktop
     * 2. mobile
     */
    public function get_browsers($project_id, $device="all") {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=browsers" .
            "&de=" . $device .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $browser) {

                $result[] = array("browser_page_views" => $browser->page_views,
                    "browser_name" => $browser->browser_name,
                    "browser_version" => $browser->browser_version,
                    "browser_percentage" => $browser->percentage
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the operating system ratios for the given project id
     */
    public function get_operating_systems($project_id, $device="all") {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string = "&s=os" .
            "&de=" . $device .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $os) {

                $result[] = array("os_page_views" => $os->page_views,
                    "os_name" => $os->os_name,
                    "os_percentage" => $os->percentage
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the recent pageload activity for the given project id
     */
    public function get_recent_pageload_activity($project_id, $device="all") {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=pageload" .
            "&de=" . $device .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $pageload) {

                $result[] = array("page_url" => $pageload->page_url,
                    "time" => $pageload->time,
                    "referring_url" => $pageload->referring_url,
                    "page_title" => $pageload->page_title,
                    "browser_name" => $pageload->browser_name,
                    "browser_version" => $pageload->browser_version,
                    "os_name" => $pageload->os_name,
                    "device_vendor" => $pageload->device_vendor,
                    "device_model" => $pageload->device_model,
                    "se_keywords" => $pageload->se_keywords,
                    "resolution_width" => $pageload->resolution_width,
                    "resolution_height" => $pageload->resolution_height,
                    "isp" => $pageload->isp,
                    "city" => $pageload->city,
                    "state" => $pageload->state,
                    "country" => $pageload->country,
                    "ip_address" => $pageload->ip_address
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the exit link activity for the given project id
     */
    public function get_exit_link_activity($project_id, $device="all") {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=exit-link-activity" .
            "&de=" . $device .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $exit_link) {

                $result[] = array("link" => $exit_link->link,
                    "time" => $exit_link->time,
                    "page_url" => $exit_link->page_url,
                    "page_title" => $exit_link->page_title,
                    "ip_address" => $exit_link->ip_number
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");
    }

    /**
     * Gets the download link activity for the given project id
     */
    public function get_download_link_activity($project_id, $device="all") {

        if (!$this->valid_device($device)) {

            throw new Exception("Invalid device entered.");
        }

        $this->sc_query_string =
            "&s=download-link-activity" .
            "&de=" . $device .
            "&pi=" . $project_id;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach ($xml->sc_data as $download_link) {

                $result[] = array("link" => $download_link->link,
                    "time" => $download_link->time,
                    "page_url" => $download_link->page_url,
                    "page_title" => $download_link->page_title,
                    "ip_address" => $download_link->ip_number,
                    "extension" => $download_link->extension
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your project ID and login credentials.");

    }

    /**
     * Gets the most recent X number of recent visitors details for the given project ID
     */
    public function get_recent_visitors($project_id, $num_of_results) {

        $this->sc_query_string =
            "&s=visitor" .
            "&g=daily" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results;

        $url = $this->build_url("stats");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array();

            foreach($xml->sc_data as $visitor) {

                $result[] = array(
                    "log_visits" => $visitor->log_visits,
                    "entries_in_visit" => $visitor->entries_in_visit,
                    "entry_time" => $visitor->entry_t,
                    "entry_url" => $visitor->entry_url,
                    "entry_title" => $visitor->entry_title,
                    "se_keywords" => $visitor->se_keywords,
                    "link" => $visitor->link,
                    "country_name" => $visitor->country_name,
                    "state" => $visitor->state,
                    "resolution" => $visitor->res,
                    "exit_time"=> $visitor->exit_t,
                    "exit_url" => $visitor->exit_url,
                    "exit_page_title" => $visitor->exit_title,
                    "returning_count" => $visitor->returning_count,
                    "browser_name" => $visitor->browser_name,
                    "browser_version" => $visitor->browser_version,
                    "os" => $visitor->os,
                    "resolution_width" => $visitor->width,
                    "resolution_" => $visitor->height,
                    "javascript" => $visitor->javascript,
                    "country" => $visitor->country,
                    "city" => $visitor->city,
                    "isp" => $visitor->isp,
                    "ip_address" => $visitor->ip_address,
                    "latitude" => $visitor->latitude,
                    "longitude" => $visitor->longitude,
                    "num_entry" => $visitor->num_entry,
                    "visit_length" => $visitor->visit_length
                );
            }

            return $result;
        }

        throw new Exception("XML Error: Check your login credentials and project ID.");
    }
}

?>
