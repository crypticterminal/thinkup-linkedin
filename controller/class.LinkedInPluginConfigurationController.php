<?php
/**
 * LinkedIn Plugin Configuration Controller
 *
 * Handles plugin configuration requests.
 *
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class LinkedInPluginConfigurationController extends PluginConfigurationController
{
    var $owner;

    public function authControl()
    {
        $config = Config::getInstance();
        Utils::defineConstants();
        $this->setViewTemplate(THINKUP_WEBAPP_PATH.'plugins/linkedin/view/linkedin.account.index.tpl');

        $id = DAOFactory::getDAO('InstanceDAO');
        $od = DAOFactory::getDAO('OwnerDAO');

        $plugin_options = $this->getPluginOptions();
        $linkedin_consumer_key = $this->getPluginOption('linkedin_consumer_key');
        $linkedin_consumer_secret = $this->getPluginOption('linkedin_consumer_secret');
        
        if (isset($linkedin_consumer_key) && isset($linkedin_consumer_secret))
        {
            $linkedin = new LinkedIn($linkedin_consumer_key, $linkedin_consumer_secret, "http://" . $_SERVER["HTTP_HOST"] . "/thinkup/" . "plugins/linkedin/auth.php");
            
            $linkedin->getRequestToken();
            $_SESSION['requestToken'] = serialize($linkedin->request_token);

            if (isset($_SESSION['requestToken']))
            {
                $oauthorize_link = $linkedin->generateAuthorizeUrl();
            }
            else
            {
                $this->addErrorMessage("Unable to obtain OAuth token. Check your LinkedIn API Key and Secret Key configuration.");
                $oauthorize_link = '';
            }
        }
        else
        {
            $this->addErrorMessage("Missing required settings! Please configure the LinkedIn plugin below.");
            $oauthorize_link = '';
        }
        
        $owner_instances = $id->getByOwnerAndNetwork($this->owner, 'linkedin');

        $this->addToView('owner_instances', $owner_instances);
        $this->addToView('oauthorize_link', $oauthorize_link);

        $this->addOptionForm();

        return $this->generateView();
    }

    private function addOptionForm()
    {
        $linkedin_consumer_key = array('name' => 'linkedin_consumer_key', 'label' => 'API Key');
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $linkedin_consumer_key);

        $linkedin_consumer_secret = array('name' => 'linkedin_consumer_secret', 'label' => 'Secret Key');
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $linkedin_consumer_secret);
    }
    
    protected static function send_request($request, $url, $method, $data = NULL) {
	  // check for cURL
	  if(extension_loaded('curl')) {
      // start cURL, checking for a successful initiation
      if($handle = curl_init()) {
        // set cURL options, based on parameters passed
  	    curl_setopt($handle, CURLOPT_HEADER, 0);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_URL, $url);
        
        // check the method we are using to communicate with LinkedIn
        switch($method) {
          case 'DELETE':
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
            break;
          case 'POST':
          case 'PUT':
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
            break;
        }
        
        // check if we are sending data to LinkedIn 
        if(is_null($data)) {
          curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            $request->to_header()
          ));
        } else {
          curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
          curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            $request->to_header(), 
            'Content-Type: text/xml; charset=UTF-8'
          ));
        }
        
        // gather the response
        $return_data['linkedin']  = curl_exec($handle);
        $return_data['info']      = curl_getinfo($handle);
        
        // close cURL connection
        curl_close($handle);
        
        // no exceptions thrown, return the data
        return $return_data;
      } else {
        // cURL failed to start
        throw new LinkedInException('send_request: cURL did not initialize properly.');
      }
    } else {
      // cURL not present
      throw new LinkedInException('send_request: PHP cURL extension does not appear to be loaded/present.');
    }
	}
}
