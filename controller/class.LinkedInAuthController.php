<?php

/**
 * LinkedIn Auth Controller
 * Save the OAuth tokens for LinkedIn account authorization.
 * @author Matt McCormick <mbmccormick[at]gmail[dot]com>
 **/
 
class LinkedInAuthController extends ThinkUpAuthController
{    
    var $is_missing_param = false;

    public function __construct($session_started=false)
    {
        parent::__construct($session_started);
        $config = Config::getInstance();
        Utils::defineConstants();
        $this->setViewTemplate(THINKUP_WEBAPP_PATH.'plugins/linkedin/view/auth.tpl');
        $this->setPageTitle('Authorizing Your LinkedIn Account');       
        
        // if (!isset($_REQUEST['oauth_verifier']))
        // {
               // $this->addInfoMessage('Verifier not set.');
               // $this->is_missing_param = true;
        // }
    }

    public function authControl()
    {
        $msg = "";
        if (!$this->is_missing_param)
        {
            // get oauth values
            $plugin_option_dao = DAOFactory::GetDAO('PluginOptionDAO');
            $options = $plugin_option_dao->getOptionsHash('linkedin', true); //get cached

            $linkedin = new LinkedIn($options['linkedin_consumer_key']->option_value, $options['linkedin_consumer_secret']->option_value, "http://" . $_SERVER["HTTP_HOST"] . "/thinkup/" . "plugins/linkedin/auth.php");
            
            if (isset($_REQUEST['oauth_verifier']))
            {
                $_SESSION['oauth_verifier'] = $_REQUEST['oauth_verifier'];

                $linkedin->request_token = unserialize($_SESSION['requestToken']);
                $linkedin->oauth_verifier = $_SESSION['oauth_verifier'];
                $linkedin->getAccessToken($_REQUEST['oauth_verifier']);

                $_SESSION['oauth_access_token'] = serialize($linkedin->access_token);
                header("Location: " . "http://" . $_SERVER["HTTP_HOST"] . "/thinkup/" . "plugins/linkedin/auth.php");
                exit;
            }
            else
            {
                $linkedin->request_token = unserialize($_SESSION['requestToken']);
                $linkedin->oauth_verifier = $_SESSION['oauth_verifier'];
                $linkedin->access_token = unserialize($_SESSION['oauth_access_token']);
            }
            
            $xml_response = $linkedin->getProfile("~:(id,first-name,last-name)");
            $profile = split("\n", $xml_response);
            
            $xml_id = trim(str_replace("<id>", "", str_replace("</id>", "", $profile[2])));
            $xml_firstname = trim(str_replace("<first-name>", "", str_replace("</first-name>", "", $profile[3])));
            $xml_lastname = trim(str_replace("<last-name>", "", str_replace("</last-name>", "", $profile[4])));
            
            $linkedin_id = $xml_id;
            $tu = $xml_firstname . " " . $xml_lastname;

            $od = DAOFactory::getDAO('OwnerDAO');
            $owner = $od->getByEmail($this->getLoggedInUser());

            if ($linkedin_id != null)
            {
                $msg = "<h2 class=\"subhead\">LinkedIn authentication successful!</h2>";

                $id = DAOFactory::getDAO('InstanceDAO');
                $i = $id->getByUserIdOnNetwork($linkedin_id, 'linkedin');
                $oid = DAOFactory::getDAO('OwnerInstanceDAO');

                if (isset($i))
                {
                    $msg .= "Instance already exists.<br />";

                    $oi = $oid->get($owner->id, $i->id);
                    if ($oi != null)
                    {
                        $msg .= "Owner already has this instance, no insert required.<br />";
                        if ($oid->updateTokens($owner->id, $i->id, $linkedin->request_token->key, $linkedin->request_token->secret))
                        {
                            $msg .= "OAuth Tokens updated.";
                        }
                        else
                        {
                            $msg .= "OAuth Tokens NOT updated.";
                        }
                    }
                    else
                    {
                        if ($oid->insert($owner->id, $i->id, $linkedin->request_token->key, $linkedin->request_token->secret))
                        {
                            $msg .= "Added owner instance.<br />";
                        }
                        else
                        {
                            $msg .= "PROBLEM Did not add owner instance.<br />";
                        }
                    }
                }
                else
                {
                    $msg .= "Instance does not exist.<br />";

                    $id->insert($linkedin_id, $tu, "linkedin");
                    $msg .= "Created instance.<br />";

                    $i = $id->getByUserIdOnNetwork($linkedin_id, 'linkedin');
                    if ($oid->insert($owner->id, $i->id, $linkedin->request_token->key, $linkedin->request_token->secret))
                    {
                        $msg .= "Created an owner instance.<br />";
                    }
                    else
                    {
                        $msg .= "Did NOT create an owner instance.<br />";
                    }
                }
            }
            
            $config = Config::getInstance();
            $msg .= '<a href="' . $config->getValue('site_root_path') . 
                    'account/index.php?p=linkedin" class="tt-button ui-state-default tt-button-icon-left ui-corner-all"><span 
                    class="ui-icon ui-icon-circle-arrow-e"></span>Back to your account</a>';
            $this->addInfoMessage($msg);
        }
        
        return $this->generateView();
    }
}