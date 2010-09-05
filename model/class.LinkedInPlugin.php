<?php

class LinkedInPlugin implements CrawlerPlugin, WebappPlugin {
    public function crawl() {
        $logger = Logger::getInstance();
        $config = Config::getInstance();
        $id = DAOFactory::getDAO('InstanceDAO');
        $oid = DAOFactory::getDAO('OwnerInstanceDAO');
        $od = DAOFactory::getDAO('OwnerDAO');

        $plugin_option_dao = DAOFactory::GetDAO('PluginOptionDAO');
        $options = $plugin_option_dao->getOptionsHash('linkedin', true); //get cached

        $current_owner = $od->getByEmail($_SESSION['user']);

        //crawl LinkedIn user profiles
        $instances = $id->getAllActiveInstancesStalestFirstByNetwork('linkedin');
        foreach ($instances as $instance) {
            if (!$oid->doesOwnerHaveAccess($current_owner, $instance->network_username)) {
                // Owner doesn't have access to this instance; let's not crawl it.
                continue;
            }
            $logger->setUsername($instance->network_username);
            $tokens = $oid->getOAuthTokens($instance->id);
            $session_key = $tokens['oauth_access_token'];

            $linkedin = new LinkedIn($options['linkedin_consumer_key']->option_value, 
            $options['linkedin_consumer_secret']->option_value);

            $id->updateLastRun($instance->id);
            $crawler = new LinkedInCrawler($instance, $linkedin);
            try {
                $crawler->fetchInstanceUserInfo($instance->network_user_id, $session_key);
                $crawler->fetchUserPostsAndReplies($instance->network_user_id, $session_key);
            } catch (Exception $e) {
                $logger->logStatus('PROFILE EXCEPTION: '.$e->getMessage(), get_class($this));
            }

            $id->save($crawler->instance, $crawler->owner_object->post_count, $logger);
        }

        //crawl LinkedIn pages
        $instances = $id->getAllActiveInstancesStalestFirstByNetwork('linkedin page');
        foreach ($instances as $instance) {
            $logger->setUsername($instance->network_username);
            $tokens = $oid->getOAuthTokens($instance->id);
            $session_key = $tokens['oauth_access_token'];

            $linkedin = new LinkedIn($options['linkedin_consumer_key']->option_value, 
            $options['linkedin_consumer_secret']->option_value);

            $id->updateLastRun($instance->id);
            $crawler = new LinkedInCrawler($instance, $linkedin);

            try {
                $crawler->fetchPagePostsAndReplies($instance->network_user_id, $instance->network_viewer_id, $session_key);
            } catch (Exception $e) {
                $logger->logStatus('PAGE EXCEPTION: '.$e->getMessage(), get_class($this));
            }
            $id->save($crawler->instance, 0, $logger);

        }
        $logger->close(); # Close logging

    }

    public function renderConfiguration($owner) {
        $controller = new LinkedInPluginConfigurationController($owner, 'linkedin');
        return $controller->go();
    }

    public function getChildTabsUnderPosts($instance) {
        $linkedin_data_tpl = Utils::getPluginViewDirectory('linkedin').'linkedin.inline.view.tpl';

        $child_tabs = array();

        //All tab
        $alltab = new WebappTab("all_linkedin_posts", "All", '', $linkedin_data_tpl);
        $alltabds = new WebappTabDataset("all_linkedin_posts", 'PostDAO', "getAllPosts",
        array($instance->network_user_id, 'linkedin', 15, false));
        $alltab->addDataset($alltabds);
        array_push($child_tabs, $alltab);
        return $child_tabs;
    }

    public function getChildTabsUnderReplies($instance) {
        $linkedin_data_tpl = Utils::getPluginViewDirectory('linkedin').'linkedin.inline.view.tpl';
        $child_tabs = array();

        //All Replies
        $artab = new WebappTab("all_linkedin_replies", "Replies", "Replies to your LinkedIn posts", $linkedin_data_tpl);
        $artabds = new WebappTabDataset("all_linkedin_replies", 'PostDAO', "getAllReplies",
        array($instance->network_user_id, 'linkedin', 15));
        $artab->addDataset($artabds);
        array_push($child_tabs, $artab);
        return $child_tabs;
    }

    public function getChildTabsUnderFriends($instance) {
        $linkedin_data_tpl = Utils::getPluginViewDirectory('linkedin').'linkedin.inline.view.tpl';
        $child_tabs = array();

        //Popular friends
        $poptab = new WebappTab("friends_mostactive", 'Popular', '', $linkedin_data_tpl);
        $poptabds = new WebappTabDataset("linkedin_users", 'FollowDAO', "getMostFollowedFollowees",
        array($instance->network_user_id, 15));
        $poptab->addDataset($poptabds);
        array_push($child_tabs, $poptab);

        return $child_tabs;
    }

    public function getChildTabsUnderFollowers($instance) {
        $linkedin_data_tpl = Utils::getPluginViewDirectory('linkedin').'linkedin.inline.view.tpl';
        $child_tabs = array();

        //Most followed
        $mftab = new WebappTab("followers_mostfollowed", 'Most-followed', 'Followers with most followers',
        $linkedin_data_tpl);
        $mftabds = new WebappTabDataset("linkedin_users", 'FollowDAO', "getMostFollowedFollowers",
        array($instance->network_user_id, 15));
        $mftab->addDataset($mftabds);
        array_push($child_tabs, $mftab);

        return $child_tabs;
    }

    public function getChildTabsUnderLinks($instance) {
        $linkedin_data_tpl = Utils::getPluginViewDirectory('linkedin').'linkedin.inline.view.tpl';
        $child_tabs = array();

        //Links from friends
        $fltab = new WebappTab("links_from_friends", 'Links', 'Links posted on your wall', $linkedin_data_tpl);
        $fltabds = new WebappTabDataset("links_from_friends", 'LinkDAO', "getLinksByFriends",
        array($instance->network_user_id));
        $fltab->addDataset($fltabds);
        array_push($child_tabs, $fltab);

        return $child_tabs;
    }
}
