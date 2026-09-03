<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verrouillage par appareil
    |--------------------------------------------------------------------------
    |
    | Un compte élève est rattaché à l'identifiant du téléphone (« phone-emei »)
    | avec lequel il s'est inscrit, pour empêcher le partage d'un abonnement
    | entre plusieurs élèves. Les comptes listés ci-dessous échappent au
    | contrôle : ce sont les comptes de test et de recette.
    |
    */

    'bypass_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DEVICE_CHECK_BYPASS_EMAILS', 'engel@rich.com'))
    ))),

    /*
    | Rôle soumis au verrouillage. Les parents/bienfaiteurs (rule_id 3) peuvent
    | se connecter depuis n'importe quel téléphone.
    */
    'locked_rule_id' => (int) env('DEVICE_CHECK_RULE_ID', 2),

];
