<?php
/*
 Plugin Name: LinkedIn
 Plugin URI: http://github.com/mbmccormick/thinkup-linkedin
 Description: Crawler plugin fetches data from LinkedIn.com for the authorized user.
 Icon: assets/img/linkedin_icon.png
 Version: 0.01
 Author: Matt McCormick
 */
$config = Config::getInstance();
//@TODO: Figure out a better way to do this
if (!class_exists('linkedin')) {
    Utils::defineConstants();
    require_once THINKUP_WEBAPP_PATH.'plugins/linkedin/lib/linkedin.class.php';
}

$webapp = Webapp::getInstance();
$webapp->registerPlugin('linkedin', 'LinkedInPlugin');

$crawler = Crawler::getInstance();
$crawler->registerCrawlerPlugin('LinkedInPlugin');
