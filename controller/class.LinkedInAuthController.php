<?php
/**
 * Twitter Auth Controller
 * Save the OAuth tokens for LinkedIn account authorization.
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class LinkedInAuthController extends ThinkUpAuthController {
    /**
     *
     * @var boolean
     */
    var $is_missing_param = false;

    public function __construct($session_started=false) {
        parent::__construct($session_started);
        $config = Config::getInstance();
        Utils::defineConstants();
        $this->setViewTemplate(THINKUP_WEBAPP_PATH.'plugins/linkedin/view/auth.tpl');
        $this->setPageTitle('Authorizing Your LinkedIn Account');
        if (!isset($_GET['oauth_token']) || $_GET['oauth_token'] == '' ) {
            $this->addInfoMessage('No OAuth token specified.');
            $this->is_missing_param = true;
        }
        if (!isset($_SESSION['oauth_request_token_secret']) || $_SESSION['oauth_request_token_secret'] == '' ) {
            $this->addInfoMessage('Secret token not set.');
            $this->is_missing_param = true;
        }
    }

    public function authControl() {
        $msg = "";
        if (!$this->is_missing_param) {
            $request_token = $_GET['oauth_token'];
            $request_token_secret = $_SESSION['oauth_request_token_secret'];

            // get oauth values
            $plugin_option_dao = DAOFactory::GetDAO('PluginOptionDAO');
            $options = $plugin_option_dao->getOptionsHash('linkedin', true); //get cached

            $to = new linkedin($options['oauth_consumer_key'], $options['oauth_consumer_secret']);
            $to->set_token_request($request_token);
            $to->set_token_request$request_token_secret);

            $tok = $to->getAccessToken();

            if (isset($tok['oauth_token']) && isset($tok['oauth_token_secret'])) {
                $api = new TwitterAPIAccessorOAuth($tok['oauth_token'], $tok['oauth_token_secret'],
                $options['oauth_consumer_key']->option_value, $options['oauth_consumer_secret']->option_value);

                $u = $api->verifyCredentials();

                //echo "User ID: ". $u['user_id'];
                //echo "User name: ". $u['user_name'];
                $twitter_id = $u['user_id'];
                $tu = $u['user_name'];

                $od = DAOFactory::getDAO('OwnerDAO');
                $owner = $od->getByEmail($this->getLoggedInUser());

                if ($twitter_id > 0) {
                    $msg = "<h2 class=\"subhead\">Twitter authentication successful!</h2>";

                    $id = DAOFactory::getDAO('InstanceDAO');
                    $i = $id->getByUsername($tu);
                    $oid = DAOFactory::getDAO('OwnerInstanceDAO');

                    if (isset($i)) {
                        $msg .= "Instance already exists.<br />";

                        $oi = $oid->get($owner->id, $i->id);
                        if ($oi != null) {
                            $msg .= "Owner already has this instance, no insert  required.<br />";
                            if ($oid->updateTokens($owner->id, $i->id, $tok['oauth_token'],
                            $tok['oauth_token_secret'])) {
                                $msg .= "OAuth Tokens updated.";
                            } else {
                                $msg .= "OAuth Tokens NOT updated.";
                            }
                        } else {
                            if ($oid->insert($owner->id, $i->id, $tok['oauth_token'], $tok['oauth_token_secret'])) {
                                $msg .= "Added owner instance.<br />";
                            } else {
                                $msg .= "PROBLEM Did not add owner instance.<br />";
                            }
                        }

                    } else {
                        $msg .= "Instance does not exist.<br />";

                        $id->insert($twitter_id, $tu);
                        $msg .= "Created instance.<br />";

                        $i = $id->getByUsername($tu);
                        if ($oid->insert(
                        $owner->id,
                        $i->id,
                        $tok['oauth_token'],
                        $tok['oauth_token_secret'])) {
                            $msg .= "Created an owner instance.<br />";
                        } else {
                            $msg .= "Did NOT create an owner instance.<br />";
                        }
                    }
                }
            }
            $config = Config::getInstance();
            $msg .= '<a href="'.$config->getValue('site_root_path').
        'account/index.php?p=twitter" class="tt-button ui-state-default tt-button-icon-left ui-corner-all"><span 
        class="ui-icon ui-icon-circle-arrow-e"></span>Back to your account</a>';
            $this->addInfoMessage($msg);
        }
        return $this->generateView();
    }

}