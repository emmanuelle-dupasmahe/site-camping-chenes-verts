<?php

declare(strict_types=1);

/**
 * Frequently asked questions.
 *
 * The condition that shows these fields only for this type is written when the
 * model is assembled: the file names its type once, at the top.
 */

return [
    'type' => 'faq',
    'label' => 'Questions fréquentes',

    // Common fields of the trunk this type also uses.
    'communs' => ['introduction'],

    'champs' => [
        [
            'name' => 'questions',
            'type' => 'set',
            'label' => 'Questions',
            'info' => 'Affichées dans l’ordre où elles sont rangées ici. La première est dépliée.',
            'multiple' => true,
            'width' => '1-1',
            'opts' => [
                'display' => '${data.question || \'Question\'}',
                'fields' => [
                    [
                        'name' => 'question',
                        'type' => 'text',
                        'label' => 'La question',
                        'required' => true,
                        'width' => '1-1',
                        'opts' => ['maxlength' => 120],
                    ],
                    [
                        'name' => 'reponse',
                        'type' => 'text',
                        'label' => 'La réponse',
                        'width' => '1-1',
                        'opts' => ['multiline' => true, 'maxlength' => 600],
                    ],
                ],
            ],
        ],
    ],
];
