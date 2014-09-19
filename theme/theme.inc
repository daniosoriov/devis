<?php

function devis_theme() {
    return array(
        'user_profile_form' => array(
            'render element' => 'form',
            //'path' => drupal_get_path('theme', 'devis') . '/templates', 
            'template' => 'templates/user-profile-edit',
        ),
    );
}