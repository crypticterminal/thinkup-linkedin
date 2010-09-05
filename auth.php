<?php
chdir("..");
chdir("..");
require_once 'init.php';
require_once 'plugins/linkedin/controller/class.LinkedInAuthController.php';

$controller = new LinkedInAuthController();
echo $controller->go();
