LinkedIn ThinkUp Plugin
========================

The LinkedIn ThinkUp plugin retrieves comments and replies and adds them to the ThinkUp database.

Installation
------------

Log into LinkedIn and [register your ThinkUp instance](https://www.linkedin.com/secure/developer?newapp=). 

Set the callback URL to:
    http://yourserver.com/path-to-thinkup-webapp/plugins/twitter/auth.php

Set Company to your name.

Set Application Name to anything.

Set Description to anything.

Set Application Name to anything.

Set Application Type to "Web application".

Set Application Use to "Social Aggregation".

Set Live Status to "Live".

Enter your Contact Information.

Set OAuth Redirect URL to:
    "http://yourserver.com/path-to-thinkup-webapp/plugins/linkedin/auth.php".

Copy down the LinkedIn-provided API Key and Secret Key.

In ThinkUp's configuration area, authorize the LinkedIn account ThinkUp should crawl.

Crawler Plugin
--------------

During the crawl process, the LinkedIn plugin retrieves comments and replies and inserts them into the ThinkUp database.