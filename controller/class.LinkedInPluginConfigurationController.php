<?php
/**
 * Twitter Plugin Configuration Controller
 *
 * Handles plugin configuration requests.
 *
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class LinkedInPluginConfigurationController extends PluginConfigurationController {
    /**
     *
     * @var Owner
     */
    var $owner;

    public function authControl() {
        $config = Config::getInstance();
        Utils::defineConstants();
        $this->setViewTemplate(THINKUP_WEBAPP_PATH.'plugins/linkedin/view/linkedin.account.index.tpl');

        $id = DAOFactory::getDAO('InstanceDAO');
        $od = DAOFactory::getDAO('OwnerDAO');

        // get plugin option values if defined...
        $plugin_options = $this->getPluginOptions();
        $linkedin_consumer_key = $this->getPluginOption('linkedin_consumer_key');
        $linkedin_consumer_secret = $this->getPluginOption('linkedin_consumer_secret');
        
        if (isset($linkedin_consumer_key) && isset($linkedin_consumer_secret)) {
            $to = new linkedin($linkedin_consumer_key, $linkedin_consumer_secret);
            /* Request tokens from twitter */
            $tok = $to->token_request();
            if (isset($tok['oauth_token'])) {
                $token = $tok['oauth_token'];
                $_SESSION['oauth_request_token_secret'] = $tok['oauth_token_secret'];

                /* Build the authorization URL */
                $oauthorize_link = $to->getAuthorizeURL($token);
            } else {
                //set error message here
                $this->addErrorMessage(
                "Unable to obtain OAuth token. Check your LinkedIn API Key and Secret Key configuration.");
                $oauthorize_link = '';
            }
        } else {
            $this->addErrorMessage(
                "Missing required settings! Please configure the LinkedIn plugin below.");
            $oauthorize_link = '';
        }
        $owner_instances = $id->getByOwnerAndNetwork($this->owner, 'linkedin');

        $this->addToView('owner_instances', $owner_instances);
        $this->addToView('oauthorize_link', $oauthorize_link);

        // add plugin options from
        $this->addOptionForm();

        return $this->generateView();
    }

    /**
     * Set plugin option fields for admin/plugin form
     */
    private function addOptionForm() {

        $linkedin_consumer_key = array('name' => 'linkedin_consumer_key', 'label' => 'API Key');
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $linkedin_consumer_key);

        $linkedin_consumer_secret = array('name' => 'linkedin_consumer_secret', 'label' => 'Secret Key');
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $linkedin_consumer_secret);

    }
}
