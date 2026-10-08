<?php

declare(strict_types=1);

use Smarty\Smarty;

require __DIR__ . '/vendor/autoload.php';

$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/templates');
$smarty->setCompileDir(__DIR__ . '/var/templates_c');
$smarty->setEscapeHtml(true);
$smarty->assign('assetVersions', [
    'css' => filemtime(__DIR__ . '/public/assets/app.css'),
    'theme' => filemtime(__DIR__ . '/public/assets/theme.js'),
    'view' => filemtime(__DIR__ . '/public/assets/article-view.js'),
]);

return $smarty;
