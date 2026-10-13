<?php

declare(strict_types=1);

namespace Tests\GardeFous;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Trois caches peuvent servir un contenu périmé sans jamais le signaler : les
 * pages rendues, les gabarits compilés par Twig, et les modèles de contenu
 * analysés par Cockpit. Les deux derniers ont déjà laissé un travail juste
 * sans effet sur un hébergement.
 */
final class CachesTest extends TestCase
{
    private function source(string $chemin): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/'.$chemin);
    }

    #[Test]
    public function twig_verifie_la_date_des_gabarits_en_production(): void
    {
        $this->assertMatchesRegularExpression(
            "/'auto_reload'\s*=>\s*true/",
            $this->source('public/index.php'),
            "sans auto_reload, Twig suit 'debug' et ne relit jamais un gabarit en production",
        );
    }

    #[Test]
    public function la_purge_vide_le_cache_des_gabarits(): void
    {
        $source = $this->source('bin/purge-cache.php');

        $this->assertStringContainsString('TemplateCache', $source);
        $this->assertStringContainsString('var/cache/twig', $source);
    }

    #[Test]
    public function l_installation_retire_le_cache_des_modeles_de_cockpit(): void
    {
        $this->assertStringContainsString(
            'var/data/app.memory.sqlite',
            $this->source('bin/install-cockpit.php'),
            "un modèle recopié sans cette purge n'apparaît pas dans l'administration",
        );
    }

    #[Test]
    public function la_mise_en_ligne_sans_ligne_de_commande_dit_quoi_purger(): void
    {
        $procedure = $this->source('docs/mise-a-jour-socle.md');

        $this->assertStringContainsString(
            'var/data/app.memory.sqlite',
            $procedure,
            'sans ligne de commande, rien ne purge ce cache : le modèle monté resterait invisible',
        );

        $this->assertStringContainsString(
            'var/cache/twig',
            $procedure,
            'un transfert qui conserve les dates laisse les gabarits compilés en place',
        );
    }

    #[Test]
    public function l_installation_retire_le_cache_des_modules_de_cockpit(): void
    {
        $this->assertStringContainsString(
            'modules.cache.php',
            $this->source('bin/install-cockpit.php'),
            'une extension ajoutée serait copiée sans être chargée',
        );
    }
}
