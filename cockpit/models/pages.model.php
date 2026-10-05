<?php

/**
 * Site pages.
 *
 * Fixed structure: the customer fills it in, never edits it. Installed to
 * public/admin/storage/content/ by bin/install-cockpit.php.
 *
 * A page is a list of blocks. One block type = one entry in the `type` list,
 * a few fields shown by a `condition`, and a Twig partial of the same name in
 * templates/blocs/. Adding a type is documented in the README.
 *
 * Publication is not a field: Cockpit tracks it natively on `_state`
 * (1 published, 0 unpublished, -1 archived) with its own button in the admin.
 * The public site only ever serves items with `_state` = 1.
 */

// Headings only, plus links and lists: the page title is the only level-one
// heading, and the toolbar must not offer anything that would break that.
$toolbar = 'format | link | listBullet listOrdered';

// Conditions are evaluated in the admin against the block being edited.
$isHero = "data.type === 'hero'";
$isTexteImage = "data.type === 'texte-image'";
$isContact = "data.type === 'contact'";
$isFormulaire = "data.type === 'formulaire'";
$isTemoignages = "data.type === 'temoignages'";
$isHebergements = "data.type === 'hebergements'";
$isTarifs = "data.type === 'tarifs'";
$hasImage = "['hero', 'texte-image'].includes(data.type)";
$hasTexte = "['texte-image', 'contact', 'formulaire'].includes(data.type)";

return [
    'name' => 'pages',
    'label' => 'Pages',
    'info' => 'Les pages du site.',
    'type' => 'collection',
    'group' => null,
    'preview' => [],
    'meta' => [
        'unique' => ['slug'],
    ],
    '_created' => 1754179200,
    '_modified' => 1754352000,

    'fields' => [
        [
            'name' => 'titre',
            'type' => 'text',
            'label' => 'Titre de la page',
            'info' => 'Apparaît en titre principal en haut de la page et dans le menu.',
            'required' => true,
            'localize' => false,
            'multiple' => false,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => [],
        ],
        [
            'name' => 'slug',
            'type' => 'text',
            'label' => 'Adresse de la page',
            'info' => "Termine l'adresse de la page : « services » donne /services. Lettres minuscules et tirets.",
            'required' => true,
            'localize' => false,
            'multiple' => false,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => ['placeholder' => 'services'],
        ],

        // ── Blocs ─────────────────────────────────────────────────────────
        [
            'name' => 'blocs',
            'type' => 'set',
            'label' => 'Contenu de la page',
            'info' => 'Les sections affichées sous le titre, dans cet ordre.',
            'required' => false,
            'localize' => false,
            'multiple' => true,
            'group' => 'Contenu',
            'width' => '1-1',
            'opts' => [
                'display' => '${data.titre || data.type || \'Section\'}',
                'fields' => [
                    [
                        'name' => 'type',
                        'type' => 'select',
                        'label' => 'Type de section',
                        'required' => true,
                        'width' => '1-1',
                        'opts' => [
                            'options' => [
                                ['value' => 'hero', 'label' => 'Bandeau d’ouverture'],
                                ['value' => 'texte-image', 'label' => 'Texte et image'],
                                ['value' => 'contact', 'label' => 'Coordonnées'],
                                ['value' => 'hebergements', 'label' => 'Hébergements (3 cartes)'],
                                ['value' => 'tarifs', 'label' => 'Tableau de tarifs & options'],
                                ['value' => 'formulaire', 'label' => 'Formulaire de contact'],
                                ['value' => 'temoignages', 'label' => 'Témoignages'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'titre',
                        'type' => 'text',
                        'label' => 'Titre de la section',
                        'info' => 'Apparaît en tête de la section.',
                        'width' => '1-1',
                        'opts' => [],
                    ],
                    [
                        'name' => 'accroche',
                        'type' => 'text',
                        'label' => 'Accroche',
                        'info' => 'La phrase affichée sous le titre du bandeau.',
                        'width' => '1-1',
                        'condition' => $isHero,
                        'opts' => ['multiline' => true, 'maxlength' => 200],
                    ],
                    [
                        'name' => 'texte',
                        'type' => 'wysiwyg',
                        'label' => 'Texte',
                        'info' => 'Le corps de la section.',
                        'width' => '1-1',
                        'condition' => $hasTexte,
                        'opts' => ['toolbar' => $toolbar],
                    ],
                    [
                        'name' => 'image',
                        'type' => 'asset',
                        'label' => 'Image',
                        'info' => 'Apparaît dans la section. Format paysage conseillé.',
                        'width' => '1-2',
                        'condition' => $hasImage,
                        'opts' => ['filter' => ['type' => 'image']],
                    ],
                    [
                        'name' => 'alt',
                        'type' => 'text',
                        'label' => 'Description de l’image',
                        'info' => 'Lue à voix haute par les lecteurs d’écran, et affichée si l’image ne charge pas. Décrire ce que l’on voit.',
                        'width' => '1-2',
                        'condition' => $hasImage,
                        'opts' => ['maxlength' => 150],
                    ],
                    [
                        'name' => 'positionImage',
                        'type' => 'select',
                        'label' => 'Position de l’image',
                        'width' => '1-2',
                        'condition' => $isTexteImage,
                        'opts' => [
                            'options' => [
                                ['value' => 'droite', 'label' => 'À droite du texte'],
                                ['value' => 'gauche', 'label' => 'À gauche du texte'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'boutonTexte',
                        'type' => 'text',
                        'label' => 'Texte du bouton',
                        'info' => 'Laisser vide pour ne pas afficher de bouton.',
                        'width' => '1-2',
                        'condition' => $isHero,
                        'opts' => ['maxlength' => 40],
                    ],
                    [
                        'name' => 'boutonLien',
                        'type' => 'text',
                        'label' => 'Adresse du bouton',
                        'info' => 'Adresse d’une page du site, par exemple /services.',
                        'width' => '1-2',
                        'condition' => $isHero,
                        'opts' => ['placeholder' => '/services'],
                    ],
                    // ── Témoignages ─────────────────────────────────────
                    [
                        'name' => 'introduction',
                        'type' => 'text',
                        'label' => 'Phrase d’introduction',
                        'info' => 'Apparaît sous le titre.',
                        'width' => '1-1',
                        'condition' => "$isTemoignages || $isHebergements || $isTarifs",
                        'opts' => ['multiline' => true, 'maxlength' => 200],
                    ],
                    [
                        'name' => 'temoignages',
                        'type' => 'set',
                        'label' => 'Témoignages',
                        'info' => 'Chaque entrée devient un témoignage affiché dans la section.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isTemoignages,
                        'opts' => [
                            'display' => '${data.auteur || \'Témoignage\'}',
                            'fields' => [
                                [
                                    'name' => 'citation',
                                    'type' => 'text',
                                    'label' => 'Ce que dit la personne',
                                    'required' => true,
                                    'width' => '1-1',
                                    'opts' => ['multiline' => true, 'maxlength' => 400],
                                ],
                                [
                                    'name' => 'auteur',
                                    'type' => 'text',
                                    'label' => 'Nom',
                                    'width' => '1-2',
                                    'opts' => [],
                                ],
                                [
                                    'name' => 'fonction',
                                    'type' => 'text',
                                    'label' => 'Fonction ou ville',
                                    'info' => 'Affichée sous le nom, en plus discret.',
                                    'width' => '1-2',
                                    'opts' => [],
                                ],
                                [
                                    'name' => 'portrait',
                                    'type' => 'asset',
                                    'label' => 'Portrait',
                                    'info' => 'Facultatif. Image carrée conseillée.',
                                    'width' => '1-2',
                                    'opts' => ['filter' => ['type' => 'image']],
                                ],
                                [
                                    'name' => 'alt',
                                    'type' => 'text',
                                    'label' => 'Description du portrait',
                                    'info' => 'Obligatoire dès qu’un portrait est choisi.',
                                    'width' => '1-2',
                                    'opts' => ['maxlength' => 150],
                                ],
                            ],
                        ],
                    ],

                    // --- DEBUT CHAMPS BLOC HEBERGEMENTS ---
                    [
                        'name' => 'cartes',
                        'type' => 'set',
                        'label' => 'Cartes hébergement',
                        'info' => 'Ajoutez vos cartes (Image, titre, puces).',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isHebergements,
                        'opts' => [
                            'display' => '${data.titre || \'Nouvelle carte\'}',
                            'fields' => [
                                ['name' => 'image', 'type' => 'asset', 'label' => 'Image de l\'hébergement'],
                                ['name' => 'titre', 'type' => 'text', 'label' => 'Titre (ex: Mobil-home 4 pers.)'],
                                ['name' => 'caracteristiques', 'type' => 'wysiwyg', 'label' => 'Caractéristiques (utilisez les puces)', 'opts' => ['toolbar' => 'listBullet']],
                                ['name' => 'lien_texte', 'type' => 'text', 'label' => 'Texte du bouton', 'width' => '1-2'],
                                ['name' => 'lien_url', 'type' => 'text', 'label' => 'Lien du bouton', 'width' => '1-2'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'bouton_texte',
                        'type' => 'text',
                        'label' => 'Texte du bouton global',
                        'info' => 'Bouton situé tout en bas de la section.',
                        'width' => '1-2',
                        'condition' => $isHebergements,
                    ],
                    [
                        'name' => 'bouton_url',
                        'type' => 'text',
                        'label' => 'Lien du bouton global',
                        'width' => '1-2',
                        'condition' => $isHebergements,
                    ],
                    // --- FIN CHAMPS BLOC HEBERGEMENTS ---

                    // --- DEBUT CHAMPS BLOC TARIFS ---
                    [
                        'name' => 'lignes',
                        'type' => 'set',
                        'label' => 'Lignes du tableau des tarifs',
                        'info' => 'Chaque ligne correspond à un hébergement et ses prix par saison.',
                        'multiple' => true,
                        'width' => '1-1',
                        'condition' => $isTarifs,
                        'opts' => [
                            'display' => '${data.libelle || \'Ligne tarifaire\'}',
                            'fields' => [
                                ['name' => 'libelle', 'type' => 'text', 'label' => 'Hébergement / Prestation'],
                                ['name' => 'basse', 'type' => 'text', 'label' => 'Prix Basse saison'],
                                ['name' => 'moyenne', 'type' => 'text', 'label' => 'Prix Moyenne saison'],
                                ['name' => 'haute', 'type' => 'text', 'label' => 'Prix Haute saison'],
                                ['name' => 'tres_haute', 'type' => 'text', 'label' => 'Prix Très haute saison'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'supplements',
                        'type' => 'wysiwyg',
                        'label' => 'Bloc Suppléments et Options',
                        'info' => 'Texte explicatif pour la taxe de séjour, les animaux, etc.',
                        'width' => '1-1',
                        'condition' => $isTarifs,
                        'opts' => ['toolbar' => $toolbar],
                    ],
                    [
                        'name' => 'periodes',
                        'type' => 'wysiwyg',
                        'label' => 'Bloc Repères des Périodes',
                        'info' => 'Détails des mois de basse, moyenne et haute saison.',
                        'width' => '1-1',
                        'condition' => $isTarifs,
                        'opts' => ['toolbar' => $toolbar],
                    ],
                    // --- FIN CHAMPS BLOC TARIFS ---

                    [
                        'name' => 'afficherHoraires',
                        'type' => 'boolean',
                        'label' => 'Afficher les horaires',
                        'info' => 'Les horaires saisis dans « Identité du site » apparaissent sous les coordonnées.',
                        'width' => '1-1',
                        'condition' => $isContact,
                        'opts' => ['default' => true],
                    ],
                ],
            ],
        ],

        // ── Référencement ─────────────────────────────────────────────────
        [
            'name' => 'seoTitre',
            'type' => 'text',
            'label' => 'Titre dans les résultats de recherche',
            'info' => "Apparaît en bleu dans Google et dans l'onglet du navigateur. Vide, le titre de la page est repris.",
            'required' => false,
            'localize' => false,
            'multiple' => false,
            'group' => 'Référencement',
            'width' => '1-1',
            'opts' => ['maxlength' => 60, 'showCount' => true],
        ],
        [
            'name' => 'seoDescription',
            'type' => 'text',
            'label' => 'Résumé dans les résultats de recherche',
            'info' => 'Apparaît sous le titre dans Google. Environ 155 caractères.',
            'required' => false,
            'localize' => false,
            'multiple' => false,
            'group' => 'Référencement',
            'width' => '1-1',
            'opts' => ['multiline' => true, 'maxlength' => 160, 'showCount' => true],
        ],
    ],
];
