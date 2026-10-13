<?php

declare(strict_types=1);

namespace Tests\GardeFous;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sections\Registry;

/**
 * L'assemblage du modèle des pages.
 *
 * Un type de section se déclare soit dans pages.model.php, comme avant, soit
 * dans son propre fichier. Les deux écritures doivent cohabiter sans que le
 * client voie la différence : c'est ce qui permet à un site de monter de
 * version sans toucher à ses types existants.
 */
final class RegistreDeSectionsTest extends TestCase
{
    private string $racine;

    protected function setUp(): void
    {
        $this->racine = sys_get_temp_dir().'/sections-'.bin2hex(random_bytes(6));

        foreach (['sections', 'sections-client', 'blocs'] as $dossier) {
            mkdir("{$this->racine}/{$dossier}", 0o755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->racine}/*/*") ?: [] as $fichier) {
            @unlink($fichier);
        }

        foreach (glob("{$this->racine}/*") ?: [] as $dossier) {
            @rmdir($dossier);
        }

        @rmdir($this->racine);
    }

    /** Le modèle réduit à ce que l'assemblage regarde. */
    private function modele(): array
    {
        return ['fields' => [[
            'name' => 'blocs',
            'opts' => ['fields' => [
                ['name' => 'type', 'type' => 'select', 'opts' => ['options' => [
                    ['value' => 'hero', 'label' => 'Bandeau'],
                    ['value' => 'texte-image', 'label' => 'Texte et image'],
                ]]],
                ['name' => 'texte', 'type' => 'wysiwyg', 'condition' => "data.type === 'texte-image'"],
            ]],
        ]]];
    }

    private function section(string $dossier, string $type, string $corps): void
    {
        file_put_contents("{$this->racine}/{$dossier}/{$type}.section.php", "<?php\n\nreturn {$corps};\n");
    }

    private function gabarit(string $type): void
    {
        file_put_contents("{$this->racine}/blocs/{$type}.html.twig", '<section></section>');
    }

    private function assembler(): array
    {
        return Registry::assemble(
            $this->modele(),
            Registry::discover(["{$this->racine}/sections", "{$this->racine}/sections-client"]),
            ["{$this->racine}/blocs"],
        );
    }

    /** @return list<string> */
    private function typesProposes(array $assemble): array
    {
        return array_column($assemble['fields'][0]['opts']['fields'][0]['opts']['options'], 'value');
    }

    private function champ(array $assemble, string $nom): ?array
    {
        foreach ($assemble['fields'][0]['opts']['fields'] as $champ) {
            if (($champ['name'] ?? null) === $nom) {
                return $champ;
            }
        }

        return null;
    }

    #[Test]
    public function sans_aucun_fichier_de_section_le_modele_est_rendu_intact(): void
    {
        $this->assertSame($this->modele(), $this->assembler());
    }

    #[Test]
    public function les_types_declares_dans_le_modele_restent_en_place(): void
    {
        $this->gabarit('faq');
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions']");

        $this->assertSame(['hero', 'texte-image', 'faq'], $this->typesProposes($this->assembler()));
    }

    #[Test]
    public function les_champs_d_un_type_recoivent_leur_condition(): void
    {
        $this->gabarit('faq');
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions', "
            ."'champs' => [['name' => 'questions', 'type' => 'set']]]");

        $this->assertSame(
            "data.type === 'faq'",
            $this->champ($this->assembler(), 'questions')['condition'] ?? null,
        );
    }

    #[Test]
    public function un_champ_commun_est_etendu_au_nouveau_type(): void
    {
        $this->gabarit('faq');
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions', "
            ."'communs' => ['texte']]");

        $this->assertSame(
            "['faq', 'texte-image'].includes(data.type)",
            $this->champ($this->assembler(), 'texte')['condition'] ?? null,
        );
    }

    #[Test]
    public function les_types_du_socle_passent_avant_ceux_du_site(): void
    {
        $this->gabarit('tarifs');
        $this->gabarit('faq');
        $this->section('sections', 'tarifs', "['type' => 'tarifs', 'label' => 'Tarifs']");
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions']");

        $this->assertSame(
            ['hero', 'texte-image', 'tarifs', 'faq'],
            $this->typesProposes($this->assembler()),
            "l'ordre doit être le même d'une machine à l'autre",
        );
    }

    #[Test]
    public function un_type_du_site_declare_aussi_dans_le_modele_est_refuse(): void
    {
        $this->gabarit('hero');
        $this->section('sections-client', 'hero', "['type' => 'hero', 'label' => 'Bandeau']");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/déclaré deux fois.+pages\.model\.php.+Garder un seul/s');

        $this->assembler();
    }

    #[Test]
    public function un_type_du_socle_deja_dans_le_modele_laisse_la_place_au_site(): void
    {
        $this->gabarit('hero');
        $this->section('sections', 'hero', "['type' => 'hero', 'label' => 'Bandeau du socle', "
            ."'champs' => [['name' => 'venu_du_socle', 'type' => 'text']]]");

        $assemble = Registry::assemble(
            $this->modele(),
            Registry::discover(["{$this->racine}/sections", "{$this->racine}/sections-client"]),
            ["{$this->racine}/blocs"],
            $ignores,
        );

        $this->assertSame(['hero'], $ignores, 'le type laissé de côté doit être signalé');
        $this->assertSame(['hero', 'texte-image'], $this->typesProposes($assemble));
        $this->assertNull(
            $this->champ($assemble, 'venu_du_socle'),
            'les champs du socle ne doivent pas s\'ajouter à ceux que le site a déjà',
        );
    }

    #[Test]
    public function sans_collision_aucun_type_n_est_laisse_de_cote(): void
    {
        $this->gabarit('faq');
        $this->section('sections', 'faq', "['type' => 'faq', 'label' => 'Questions']");

        Registry::assemble(
            $this->modele(),
            Registry::discover(["{$this->racine}/sections", "{$this->racine}/sections-client"]),
            ["{$this->racine}/blocs"],
            $ignores,
        );

        $this->assertSame([], $ignores);
    }

    #[Test]
    public function un_type_declare_dans_deux_fichiers_est_refuse_en_les_nommant(): void
    {
        $this->gabarit('faq');
        $this->section('sections', 'faq', "['type' => 'faq', 'label' => 'Questions']");
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions']");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/déclaré deux fois/');

        $this->assembler();
    }

    #[Test]
    public function un_type_sans_gabarit_est_refuse(): void
    {
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions']");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/sans gabarit/');

        $this->assembler();
    }

    #[Test]
    public function un_fichier_sans_type_est_refuse_en_le_nommant(): void
    {
        $this->section('sections-client', 'faq', "['label' => 'Questions']");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/faq\.section\.php.+« type ».+absente/s');

        $this->assembler();
    }

    #[Test]
    public function un_fichier_mal_nomme_est_refuse(): void
    {
        $this->gabarit('questions');
        $this->section('sections-client', 'faq', "['type' => 'questions', 'label' => 'Questions']");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/doit s\'appeler questions\.section\.php/');

        $this->assembler();
    }

    #[Test]
    public function une_condition_ecrite_a_la_main_est_refusee(): void
    {
        $this->gabarit('faq');
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions', "
            ."'champs' => [['name' => 'q', 'type' => 'text', 'condition' => 'data.type']]]");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ne doit pas porter de « condition »/');

        $this->assembler();
    }

    #[Test]
    public function un_champ_commun_inconnu_est_refuse(): void
    {
        $this->gabarit('faq');
        $this->section('sections-client', 'faq', "['type' => 'faq', 'label' => 'Questions', "
            ."'communs' => ['inexistant']]");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/« inexistant ».+n\'existe pas/s');

        $this->assembler();
    }
}
