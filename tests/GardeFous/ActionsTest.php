<?php

declare(strict_types=1);

namespace Tests\GardeFous;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Les actions appelées par les travaux d'intégration.
 *
 * Elles s'exécutent avec un jeton sur le dépôt du socle, celui qui pose les
 * étiquettes et publie les versions distribuées à tout le parc. Une étiquette
 * comme « v2 » se redéplace chez son auteur : ce qui tourne ici changerait
 * sans qu'aucune proposition soit ouverte.
 */
final class ActionsTest extends TestCase
{
    /** @return list<array{string}> */
    public static function travaux(): array
    {
        $fichiers = glob(dirname(__DIR__, 2).'/.github/workflows/*.yml') ?: [];

        return array_map(static fn (string $f): array => [$f], $fichiers);
    }

    #[Test]
    #[DataProvider('travaux')]
    public function chaque_action_est_epinglee_a_une_empreinte(string $fichier): void
    {
        $lignes = preg_split('/\R/', (string) file_get_contents($fichier)) ?: [];

        foreach ($lignes as $numero => $ligne) {
            if (preg_match('/^\s*-?\s*uses:\s*(\S+)/', $ligne, $trouve) !== 1) {
                continue;
            }

            [$action, $reference] = array_pad(explode('@', $trouve[1], 2), 2, '');

            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{40}$/',
                $reference,
                sprintf(
                    '%s ligne %d : « %s » est appelée par « %s ». Une référence qui '
                    .'n\'est pas une empreinte de commit se redéplace chez son auteur.',
                    basename($fichier),
                    $numero + 1,
                    $action,
                    $reference,
                ),
            );
        }
    }

    #[Test]
    #[DataProvider('travaux')]
    public function chaque_empreinte_dit_quelle_version_elle_fige(string $fichier): void
    {
        $lignes = preg_split('/\R/', (string) file_get_contents($fichier)) ?: [];

        foreach ($lignes as $numero => $ligne) {
            if (preg_match('/^\s*-?\s*uses:\s*\S+@[0-9a-f]{40}/', $ligne) !== 1) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/#\s*v?\d+\.\d+/',
                $ligne,
                sprintf(
                    '%s ligne %d : une empreinte seule ne dit pas ce qu\'elle fige. '
                    .'Ajouter la version en commentaire de fin de ligne.',
                    basename($fichier),
                    $numero + 1,
                ),
            );
        }
    }
}
