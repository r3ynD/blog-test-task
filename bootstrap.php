<?php

declare(strict_types=1);

use Smarty\Smarty;

require __DIR__ . '/vendor/autoload.php';

$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/templates');
$smarty->setCompileDir(__DIR__ . '/var/templates_c');
$smarty->setEscapeHtml(true);

return $smarty;
