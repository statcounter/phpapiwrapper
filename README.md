statcounter-api-php
====================

PHP wrapper for the StatCounter.com API

*Usage of the StatCounter API requires [a paid StatCounter account](http://statcounter.com/pricing/)*

##Instructions

This wrapper provides a PHP interface to the StatCounter API.  It provides a number of methods that enable you to perform various operations such as create a new StatCounter project, retrieve project details, and, most importantly, retrieve stats.  For most methods, data is returned back as a typical 2-dimensional associative array.

To start using, include StatCounter.php in your script using a statement like this:

```php
<?php include("StatCounterAPI.php"); ?>
```

Then, create a new StatCounter instance using your StatCounter username and password:

```php
$sc = new StatCounterAPI("myusername", "mypassword");
```

Now, assuming you've entered a correct username and password you can use any of the methods available.

##Examples
*Note:* it is wise to wrap method calls in a try/catch to ensure smooth exception handling:
```php
try {

    $sc->get_summary_stats_date("929313", "05/25/2014");
    
} catch (Exception $e) {

    // Do something graceful
} 
```

####Create a new project
Input your website's URL and title and timezone setting as parameters and create a variable to store the result like so:

```php
$result = $sc->create_statcounter_project("http://mywebsite.com", "My Website's Title", "America/Chicago");
```

If successful, an array will be returned containing the new project ID and security code.  You can access them like this:
```php
$new_project_id = $result['project_id'];
$new_security_code = $result['security_code'];
```

####Get User Project Details
Gets all of the projects associated with your account.

```php
$projects = $sc->get_user_project_details();
```

You can then manipulate the results like so:

```php
foreach ($projects as $project) {
  
  echo $project['project_id'];
  echo $project['project_name'];
  echo $project['project_url'];
}
```

####Get Summary Stats for a Date
Returns the summary stats for any given project ID and date

***Note: Date input must be of the format MM/DD/YYYY***

```php
$stats = $sc->get_summary_stats_date("928193", "05/25/2014");

echo $stats['date'];
echo $stats['page_views'];
echo $stats['unique_visits'];
echo $stats['returning_visits'];
echo $stats['first_time_visits'];
```

####Get Summary Stats for a Date Range
Returns the summary stats for any given project ID and date range

***Note: Date input must be of the format MM/DD/YYYY***

```php
$stats = $sc->get_summary_stats_date_range("928193", "05/18/2014", "05/25/2014");

foreach ($stats as $date) {

  echo $date['date'];
  echo $date['page_views'];
  echo $date['unique_visits'];
  echo $date['returning_visits'];
  echo $date['first_time_visits'];
}
```

####Recent Visitors
Returns 20 recent visitors for the project ID "928193" and the date range December 1-2, 2015

```php
$recent_visitors = $sc->get_recent_visitors_date_range("928193", "12/01/2015", "12/02/2015", 20);

foreach ($recent_visitors as $visitor) {

  echo $visitor['log_visits'];
  echo $visitor['entries_in_visit'];
  echo $visitor['entry_time'];
  echo $visitor['entry_url'];
  echo $visitor['entry_title'];
  echo $visitor['se_keywords'];
  echo $visitor['link'];
  echo $visitor['country_name'];
  echo $visitor['state'];
  echo $visitor['resolution'];
  echo $visitor['exit_time'];
  echo $visitor['exit_url'];
  echo $visitor['exit_page_title'];
  echo $visitor['returning_count'];
  echo $visitor['browser_name'];
  echo $visitor['browser_version'];
  echo $visitor['os'];
  echo $visitor['resolution_width'];
  echo $visitor['resolution_'];
  echo $visitor['javascript'];
  echo $visitor['country'];
  echo $visitor['city'];
  echo $visitor['isp'];
  echo $visitor['ip_address'];
  echo $visitor['latitude'];
  echo $visitor['longitude'];
  echo $visitor['num_entry'];
  echo $visitor['visit_length'];
}
```

#### Recent Keyword Activity
Returns last 20 keyword activity data for the project ID "928193" and the date range December 1-2, 2015

```php
$recent_keywords = $sc->get_recent_keyword_activity("928193", "12/01/2015", "12/02/2015", 20);
```

Then you can iterate through the fetched keywords:

```php
foreach ($recent_keywords as $keyword) {
  
  echo $keyword;
}
```

**More examples coming soon**
