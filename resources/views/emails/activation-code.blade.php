<x-mail::message>
# Bonjour{{ $name ? ' '.$name : '' }},

L’équipe MonProf vous offre un accès{{ $category ? ' à la catégorie **'.$category.'**' : '' }}.

Voici votre code d’activation :

<x-mail::panel>
<div style="font-size: 22px; font-weight: 700; letter-spacing: 3px; text-align: center;">{{ $activationCode }}</div>
</x-mail::panel>

Pour l’utiliser, ouvrez l’application MonProf, rendez-vous dans la section des codes d’activation et saisissez ce code pour débloquer vos cours.

Ce code est personnel : ne le partagez pas.

Cordialement,<br>
L’équipe MonProf
</x-mail::message>
