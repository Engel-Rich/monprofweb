<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Stockage des médias avec MinIO

Les photos de profil, images de questions/réponses et vidéos passent toutes par
`FileManager`. Le service actif est choisi au démarrage par `FileServiceProvider`.

Variables à déclarer dans Dokploy pour utiliser MinIO :

```dotenv
FILE_STORAGE_DRIVER=minio

# URL privée entre le conteneur MonProf et le service MinIO.
MINIO_ENDPOINT=http://minio:9000
MINIO_ACCESS_KEY=monprof
MINIO_SECRET_KEY=change-me
MINIO_BUCKET=monprof
MINIO_REGION=us-east-1
MINIO_USE_PATH_STYLE_ENDPOINT=true

# Domaine HTTPS accessible depuis les navigateurs et applications mobiles.
# Ne pas ajouter le nom du bucket à cette URL.
MINIO_PUBLIC_URL=https://files.example.com
```

Le compte MinIO doit pouvoir créer le bucket et appliquer une bucket policy.
L’application applique automatiquement une policy `s3:GetObject` publique ; les
uploads et suppressions restent authentifiés. Pour revenir temporairement à
Firebase, utiliser `FILE_STORAGE_DRIVER=firebase` et conserver les variables
Firebase existantes.

Pour migrer les fichiers déjà présents, conserver d’abord
`FILE_STORAGE_DRIVER=firebase`, commencer par une simulation, puis lancer la
copie vers MinIO :

```bash
php artisan files:migrate-to-minio --dry-run
php artisan files:migrate-to-minio
```

La commande conserve les mêmes noms et arborescences : elle ne modifie pas la base
de données et peut être relancée sans créer de doublons. Une fois la copie validée,
passer à `FILE_STORAGE_DRIVER=minio` puis redéployer l’application. Les fichiers
Firebase restent disponibles comme sauvegarde pendant cette transition.

## Paiements MundiPay

L’identification d’une transaction est séparée en trois valeurs :

- `transactions.id` : identifiant local MonProf ;
- `transactions.reference` : référence métier locale (`MPP-…`) ;
- `transactions.provider_reference` : identifiant renvoyé par le fournisseur.

Le `payment_token` MundiPay est stocké séparément et sert à appeler l’endpoint de
vérification. Après déploiement, exécuter `php artisan migrate --force` pour
renommer l’ancienne colonne sans perdre les références déjà enregistrées.

Variables à déclarer dans Dokploy :

```dotenv
MUNDIPAY_API_URL=https://gateway.mundipay.pro/api
MUNDIPAY_TRANSACTION_PATH=v1/transaction
MUNDIPAY_API_KEY=
MUNDIPAY_API_SECRET=
MUNDIPAY_TIMEOUT=30
```

Les anciennes variables `MUNDY_PAY_*` restent acceptées pendant la transition.
Pour une nouvelle version de l’application mobile, envoyer l’ID local dans
`payment_service_id`. Le champ historique `subscription_id` reste accepté afin
de ne pas casser les clients déjà publiés.
