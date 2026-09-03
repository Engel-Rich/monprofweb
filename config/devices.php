<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verrouillage par appareil
    |--------------------------------------------------------------------------
    |
    | Un compte élève est rattaché à l'identifiant du téléphone (« phone-emei »)
    | avec lequel il s'est inscrit, pour empêcher le partage d'un abonnement
    | entre plusieurs élèves.
    |
    */

    /*
    | Adresses email exemptées du contrôle : comptes de test, de recette et de
    | démonstration, qui doivent pouvoir se connecter depuis n'importe quel
    | téléphone.
    |
    | Deux façons de les déclarer, cumulatives :
    |   1. cette liste, pour les comptes permanents versionnés avec le code ;
    |   2. la variable DEVICE_CHECK_BYPASS_EMAILS, pour ajouter un compte en
    |      production sans redéployer (séparateurs : virgule, point-virgule ou
    |      espace).
    |
    | La casse et les espaces sont sans importance, les doublons et les valeurs
    | qui ne sont pas des adresses valides sont ignorés.
    */
    'bypass_emails' => array_merge(
        [
            'engel@rich.com',
        ],
        preg_split(
            '/[\s,;]+/',
            (string) env('DEVICE_CHECK_BYPASS_EMAILS', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: []
    ),

    /*
    | Rôle soumis au verrouillage. Les parents/bienfaiteurs (rule_id 3) peuvent
    | se connecter depuis n'importe quel téléphone.
    */
    'locked_rule_id' => (int) env('DEVICE_CHECK_RULE_ID', 2),

];
