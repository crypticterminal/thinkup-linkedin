<?php
if (!class_exists('linkedinOAuth')) {
    Utils::defineConstants();
    require_once THINKUP_WEBAPP_PATH.'_lib/extlib/linkedinoauth/linkedinoauth.php';
}

class LinkedInOAuthThinkUp extends LinkedInOAuth {

    //Adding a no OAuth required call to this class, for calls to the Search API
    function noAuthRequest($url) {
        return $this->http($url, 'GET');
    }
}
