<?php

declare(strict_types=1);

namespace Sections;

use RuntimeException;

/**
 * Builds the pages model from standalone section files.
 *
 * A section type used to be declared in four places inside
 * cockpit/models/pages.model.php: a condition variable, an option of the
 * « type » field, the shared condition of any common field it reuses, and its
 * own fields. Two sites adding a type touched the same lines, and every update
 * of the socle brought a conflict.
 *
 * A section now lives in one file. This class finds those files, checks them,
 * and folds them into the model Cockpit reads. It knows nothing about any
 * particular type.
 */
final class Registry
{
    /** A type is lowercase words joined by hyphens, like the template that renders it. */
    private const TYPE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Reads every section file of the given directories, in order.
     *
     * @param list<string> $directories socle first, site second
     * @return array<string, array{type: string, label: string, champs: list<array<string, mixed>>, communs: list<string>, fichier: string}>
     */
    public static function discover(array $directories): array
    {
        $sections = [];

        foreach ($directories as $rang => $directory) {
            $files = glob(rtrim($directory, '/\\').'/*.section.php') ?: [];
            sort($files);

            foreach ($files as $file) {
                $section = self::read($file);
                // The first directory is the socle's. A site cannot edit what
                // it holds, so a clash with the model is settled differently.
                $section['socle'] = $rang === 0;
                $type = $section['type'];

                if (isset($sections[$type])) {
                    throw new RuntimeException(
                        "Le type « {$type} » est déclaré deux fois :\n"
                        ."    ".self::short($sections[$type]['fichier'])."\n"
                        ."    ".self::short($file)
                    );
                }

                $sections[$type] = $section;
            }
        }

        return $sections;
    }

    /**
     * @return array{type: string, label: string, champs: list<array<string, mixed>>, communs: list<string>, fichier: string}
     */
    private static function read(string $file): array
    {
        $section = include $file;
        $nom = self::short($file);

        if (!is_array($section)) {
            throw new RuntimeException("{$nom} ne retourne pas un tableau.");
        }

        foreach (['type', 'label'] as $cle) {
            if (!isset($section[$cle]) || !is_string($section[$cle]) || trim($section[$cle]) === '') {
                throw new RuntimeException("{$nom} : la clé « {$cle} » est absente ou vide.");
            }
        }

        if (preg_match(self::TYPE, $section['type']) !== 1) {
            throw new RuntimeException(
                "{$nom} : « {$section['type']} » n'est pas un type valide. "
                .'Minuscules et tirets, comme le nom du gabarit.'
            );
        }

        if (basename($file, '.section.php') !== $section['type']) {
            throw new RuntimeException(
                "{$nom} déclare le type « {$section['type']} » : le fichier doit s'appeler "
                ."{$section['type']}.section.php."
            );
        }

        $champs = $section['champs'] ?? [];

        if (!is_array($champs)) {
            throw new RuntimeException("{$nom} : « champs » doit être un tableau.");
        }

        foreach ($champs as $index => $champ) {
            if (!is_array($champ) || !isset($champ['name'], $champ['type'])) {
                throw new RuntimeException(
                    "{$nom} : le champ n° ".($index + 1)." n'a pas de « name » ou de « type »."
                );
            }

            if (isset($champ['condition'])) {
                throw new RuntimeException(
                    "{$nom} : le champ « {$champ['name']} » ne doit pas porter de « condition », "
                    .'elle est écrite à l\'assemblage.'
                );
            }
        }

        $communs = $section['communs'] ?? [];

        if (!is_array($communs)) {
            throw new RuntimeException("{$nom} : « communs » doit être un tableau de noms de champs.");
        }

        return [
            'type' => $section['type'],
            'label' => $section['label'],
            'champs' => array_values($champs),
            'communs' => array_values($communs),
            'fichier' => $file,
        ];
    }

    /**
     * Folds the sections into the pages model.
     *
     * @param array<string, mixed> $model the model as pages.model.php returns it
     * @param array<string, array<string, mixed>> $sections
     * @param list<string> $templateDirectories where a section template may live
     * @return array<string, mixed>
     */
    public static function assemble(
        array $model,
        array $sections,
        array $templateDirectories,
        ?array &$ignores = null,
    ): array {
        $ignores = [];

        if ($sections === []) {
            return $model;
        }

        $blocs = self::locate($model);
        $champs = &$model['fields'][$blocs['field']]['opts']['fields'];
        $options = &$champs[$blocs['type']]['opts']['options'];

        foreach ($sections as $type => $section) {
            self::needsTemplate($section, $templateDirectories);

            $dansLeModele = false;

            foreach ($options as $option) {
                if (($option['value'] ?? null) === $type) {
                    $dansLeModele = true;
                    break;
                }
            }

            if ($dansLeModele && !($section['socle'] ?? false)) {
                throw new RuntimeException(
                    "Le type « {$type} » est déclaré deux fois : dans "
                    .'cockpit/models/pages.model.php, et dans '.self::short($section['fichier'])
                    .".\n    Garder un seul des deux."
                );
            }

            // The site declared this type before the socle shipped it. Its own
            // fields win and the socle's file is left aside: an update never
            // takes away what a site has already put in front of its client.
            if ($dansLeModele) {
                $ignores[] = $type;
                continue;
            }

            $options[] = ['value' => $type, 'label' => $section['label']];

            foreach ($section['communs'] as $commun) {
                $index = self::indexOf($champs, $commun);

                if ($index === null) {
                    throw new RuntimeException(
                        self::short($section['fichier'])." demande le champ commun « {$commun} », "
                        .'qui n\'existe pas dans pages.model.php.'
                    );
                }

                $champs[$index]['condition'] = self::widen($champs[$index]['condition'] ?? '', $type);
            }

            foreach ($section['champs'] as $champ) {
                $champ['condition'] = "data.type === '{$type}'";
                $champs[] = $champ;
            }
        }

        return $model;
    }

    /**
     * Adds a type to a condition, whatever shape it has.
     *
     * Conditions come in two shapes, « data.type === 'hero' » and
     * « ['hero', 'texte-image'].includes(data.type) ». Both are rewritten as a
     * list, so a field shared by three types reads the same as one shared by
     * two.
     */
    private static function widen(string $condition, string $type): string
    {
        preg_match_all("/'([a-z0-9-]+)'/", $condition, $trouves);

        $types = array_values(array_unique([...$trouves[1], $type]));
        sort($types);

        return "['".implode("', '", $types)."'].includes(data.type)";
    }

    /** @param list<array<string, mixed>> $champs */
    private static function indexOf(array $champs, string $name): ?int
    {
        foreach ($champs as $index => $champ) {
            if (($champ['name'] ?? null) === $name) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Finds the repeatable « blocs » field and its « type » select.
     *
     * @param array<string, mixed> $model
     * @return array{field: int, type: int}
     */
    private static function locate(array $model): array
    {
        foreach ($model['fields'] ?? [] as $index => $champ) {
            if (($champ['name'] ?? null) !== 'blocs') {
                continue;
            }

            $champs = $champ['opts']['fields'] ?? [];
            $type = self::indexOf($champs, 'type');

            if ($type === null) {
                throw new RuntimeException('Le champ « blocs » de pages.model.php n\'a pas de champ « type ».');
            }

            return ['field' => $index, 'type' => $type];
        }

        throw new RuntimeException('pages.model.php n\'a pas de champ « blocs ».');
    }

    /**
     * @param array<string, mixed> $section
     * @param list<string> $directories
     */
    private static function needsTemplate(array $section, array $directories): void
    {
        foreach ($directories as $directory) {
            if (is_file(rtrim($directory, '/\\').'/'.$section['type'].'.html.twig')) {
                return;
            }
        }

        throw new RuntimeException(
            self::short($section['fichier'])." déclare le type « {$section['type']} » "
            ."sans gabarit : écrire blocs/{$section['type']}.html.twig."
        );
    }

    /** The project-relative path, so a message names a file someone can open. */
    private static function short(string $file): string
    {
        $racine = str_replace('\\', '/', dirname(__DIR__, 2)).'/';

        return str_replace([$racine, '\\'], ['', '/'], str_replace('\\', '/', $file));
    }

    /**
     * The pages model as Cockpit should receive it.
     *
     * @return array<string, mixed>
     */
    public static function pagesModel(string $root, ?array &$ignores = null): array
    {
        $model = include "{$root}/cockpit/models/pages.model.php";

        return self::assemble(
            $model,
            self::discover(["{$root}/cockpit/sections", "{$root}/cockpit/sections-client"]),
            ["{$root}/templates-client/blocs", "{$root}/templates/blocs"],
            $ignores,
        );
    }
}
