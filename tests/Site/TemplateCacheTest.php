<?php

declare(strict_types=1);

namespace Tests\Site;

use App\Cache\TemplateCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Twig nests its compiled classes in folders named after a hash. A purge that
 * only looked at the first level would leave every template behind, and the
 * host would go on serving the previous build.
 */
final class TemplateCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/twig-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            (new TemplateCache($this->directory))->clear();
            @rmdir($this->directory);
        }
    }

    private function compiled(string $path): void
    {
        $full = $this->directory.'/'.$path;
        @mkdir(dirname($full), 0o755, true);
        file_put_contents($full, '<?php class __TwigTemplate_x {}');
    }

    #[Test]
    public function les_gabarits_compiles_des_sous_dossiers_sont_retires(): void
    {
        $this->compiled('a1/b2c3d4.php');
        $this->compiled('a1/e5f6a7.php');
        $this->compiled('b2/c8d9e0.php');

        $this->assertSame(3, (new TemplateCache($this->directory))->clear());
        $this->assertSame([], glob($this->directory.'/*') ?: []);
    }

    #[Test]
    public function un_dossier_absent_ne_fait_pas_echouer_la_purge(): void
    {
        $this->assertSame(0, (new TemplateCache($this->directory.'/jamais-cree'))->clear());
    }

    #[Test]
    public function un_dossier_deja_vide_ne_retire_rien(): void
    {
        @mkdir($this->directory, 0o755, true);

        $this->assertSame(0, (new TemplateCache($this->directory))->clear());
    }
}
