<?php

declare(strict_types=1);

/**
 * Empties the page cache by hand.
 *
 * The admin does it on its own whenever content is saved. This is for the
 * other cases: after deploying new templates or a new stylesheet, whose
 * addresses are already written into the stored pages.
 *
 * Usage: php bin/purge-cache.php
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script s'exécute en ligne de commande.\n");
}

require_once __DIR__.'/../src/Cache/PageCache.php';
require_once __DIR__.'/../src/Cache/TemplateCache.php';
require_once __DIR__.'/../src/View/Colours.php';

$cache = new App\Cache\PageCache(dirname(__DIR__).'/public/cache');
$removed = $cache->clear();

// The compiled templates go too: after new files are sent to a host, the
// pages and the templates they were rendered from are both out of date.
$templates = new App\Cache\TemplateCache(dirname(__DIR__).'/var/cache/twig');
$compiled = $templates->clear();

// The colours are written from the site identity, like the pages themselves.
(new App\View\Colours(dirname(__DIR__).'/public/assets/css/couleurs.css'))->forget();

echo match (true) {
    $removed === 0 => "\n  Le cache était déjà vide.\n",
    $removed === 1 => "\n  1 page retirée du cache.\n",
    default => "\n  {$removed} pages retirées du cache.\n",
};

echo match (true) {
    $compiled === 0 => "  Aucun gabarit compilé à retirer.\n\n",
    $compiled === 1 => "  1 gabarit compilé retiré.\n\n",
    default => "  {$compiled} gabarits compilés retirés.\n\n",
};
