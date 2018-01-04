<?php


include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/jeeb.php');

$jeeb = new jeeb();

Tools::redirect(Context::getContext()->link->getModuleLink('jeeb', 'payment'));

?>
