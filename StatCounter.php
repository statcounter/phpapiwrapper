<?php

/**
 * PHP wrapper for the StatCounter API
 *
 * http://api.statcounter.com
 */
class StatCounter {

    private $sc_base_url = "http://api.statcounter.com";
    private $sc_version_num = "3";
    private $sc_username;
    private $sc_password;
    private $sc_query_string;

    /**
     * Constructs a new StatCounter object with the given username and password
     */
    public function __construct($sc_username, $sc_password) {

        $this->sc_username = $sc_username;
        $this->sc_password = $sc_password;
        $this->sc_query_string = "";
    }

    /**
     * Gets all of the user projects for the user with username and password as specified in initial construction
     */
    public function get_user_project_details() {

        $this->sc_query_string = "&f=xml";

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
     **/
    public function create_statcounter_project($website_url, $website_title, $timezone) {
    
        if (!$this->valid_timezone($sc_timezone)) {

            throw new Exception("Invalid timezone entered");
        }

        date_default_timezone_set($sc_timezone);

        $this->sc_query_string = 
            "&wt=" . urlencode($website_title) .
            "&wu=" . urlencode($website_url) .
            "&tz=" . urlencode($timezone) .
            "&ps=0&f=xml";

        $url = $this->build_url("add_project");

        $xml = simplexml_load_file($url);

        if ($xml->attributes()->status == "ok") {

            $result = array("project_id" => $xml->project_id,
                            "security_code" => $xml->security_code
                            );

            return $result;
        }

        throw new Exception("Unable to create project.  Check your login details.");
    }

    /**
     * Returns the recent X number of keywords to visit the site associated with the given project ID
     */
    public function get_recent_keyword_activity($project_id, $num_of_results, $exclude_encrypted_kws=false) {

        $this->sc_query_string = 
            "&s=keyword-activity" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            "&f=xml";

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
            "&ct=" . $count_type .
            "&f=xml";

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
            "&n=" . $num_of_results .
            "&f=xml";

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
            "&n=" . $num_of_results .
            "&f=xml";

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
            "&e=" . $external .
            "&f=xml";

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
            "&pi=" . $project_id .
            "&f=xml";

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
            "&pi=" . $project_id .
            
            "&f=xml";

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
            "&pi=" . $project_id .
            
            "&f=xml";

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
            "&pi=" . $project_id .
            
            "&f=xml";

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
            "&pi=" . $project_id .
            
            "&f=xml";

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
     * Date must be in format MM/DD/YYYY
     */
    public function get_summary_stats_date($project_id, $date) {

        return $this->get_summary_stats_date_range($project_id, $date, $date)[0];
    }

    /**
     * Retrieves an array of daily summary stats for each day of the given date range
     * Date must be in format MM/DD/YYYY
     */
    public function get_summary_stats_date_range($project_id, $start_date, $end_date) {

        $start_date_arr = explode("/", $start_date);
        $end_date_arr = explode("/", $end_date);

        if (!$this->valid_date($start_date)) {

            throw new Exception("Invalid start date entered.");
        }

        if (!$this->valid_date($end_date)) {

            throw new Exception("Invalid end date entered.");
        }

        $start_month = $start_date_arr[0];
        $start_day = $start_date_arr[1];
        $start_year = $start_date_arr[2];

        $end_month = $end_date_arr[0];
        $end_day = $end_date_arr[1];
        $end_year = $end_date_arr[2];

        $this->sc_query_string = 
            "&s=summary" .
            "&g=daily" .
            "&sd=" . $start_day .
            "&sm=" . $start_month .
            "&sy=" . $start_year .
            "&ed=" . $end_day .
            "&em=" . $end_month .
            "&ey=" . $end_year .
            "&pi=" . $project_id .
            "&f=xml";

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
     * Gets the most recent X number of recent visitors details for the given project ID
     */
    public function get_recent_visitors($project_id, $num_of_results) {

        $this->sc_query_string = 
            "&s=visitor" .
            "&g=daily" .
            "&pi=" . $project_id .
            "&n=" . $num_of_results .
            "&f=xml";

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
     */
    private function build_url($function) {

        $base = $this->sc_base_url . "/" . $function . "/";
        
        $this->sc_query_string =  "?vn=" . $this->sc_version_num .
                        "&t=" . time() .
                        "&u=" . $this->sc_username .
						 $this->sc_query_string;
			
        $sha1 = sha1($this->sc_query_string . $this->sc_password);

        $url = $base . $this->sc_query_string . "&sha1=" . $sha1;

        return $url;
    }

	/**
	 * Returns true if the given device is valid
	 */
    private function valid_device($device) {

        return ($device == "all" || $device == "desktop" || $device == "mobile");
    }

    /**
     * Returns true if the given date is of valid format mm/dd/yyyy, else returns false
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
     * Else returns false
     */
    private function valid_timezone($timezone) {
        return in_array($timezone, timezone_identifiers_list());
    }
}
?>
