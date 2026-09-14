<?php

namespace Tests\Feature;

use App\Contracts\FileStorageService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le filtrage des listes d'administration se faisait dans le navigateur, sur la
 * seule page affichée : chercher au-delà des 25 premières lignes ne renvoyait
 * rien. Ces tests vérifient que chaque critère est bien appliqué en base, sur
 * l'ensemble de la table.
 */
class AdminListFiltersTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->app->bind(FileStorageService::class, fn () => new FakeFileStorage);

        $this->createSchema();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'rule_id' => 1,
        ]);
    }

    // ─── Paiements ────────────────────────────────────────────────────────

    public function test_payments_are_searched_across_the_whole_table(): void
    {
        $categorie = $this->categorie('Terminale');
        $wanted = $this->payment('Awa', 'awa@example.test', '699000001', $categorie);
        $this->payment('Bob', 'bob@example.test', '699000002', $categorie);

        // Bien au-delà de la première page, là où le filtrage navigateur échouait.
        for ($index = 0; $index < 40; $index++) {
            $this->payment('Autre'.$index, "autre{$index}@example.test", '69900'.(1000 + $index), $categorie);
        }

        $this->assertPaymentResults(['search' => 'awa@example.test'], [$wanted]);
        $this->assertPaymentResults(['search' => '699000001'], [$wanted]);
        $this->assertPaymentResults(['email' => 'AWA@'], [$wanted]);
        $this->assertPaymentResults(['phone' => '000001'], [$wanted]);
    }

    public function test_payments_are_filtered_by_provider_service_category_and_code(): void
    {
        $terminale = $this->categorie('Terminale');
        $seconde = $this->categorie('Seconde');
        $campay = $this->provider('CAMPAY');
        $mundi = $this->provider('MUNDIPAY');
        $serviceA = $this->service($campay, 'MTN Mobile Money');
        $serviceB = $this->service($mundi, 'Orange Money');

        $first = $this->payment('Awa', 'awa@example.test', '699000001', $terminale, [
            'provider' => $campay, 'service' => $serviceA, 'reference' => 'REF-AAA',
        ]);
        $second = $this->payment('Bob', 'bob@example.test', '699000002', $seconde, [
            'provider' => $mundi, 'service' => $serviceB, 'reference' => 'REF-BBB',
        ]);

        $this->code($first, 'CODE-ALPHA');
        $this->code($second, 'CODE-BETA');

        $this->assertPaymentResults(['provider' => (string) $campay], [$first]);
        $this->assertPaymentResults(['service' => (string) $serviceB], [$second]);
        $this->assertPaymentResults(['categorie' => (string) $terminale], [$first]);
        $this->assertPaymentResults(['code' => 'alpha'], [$first]);
        $this->assertPaymentResults(['reference' => 'REF-BBB'], [$second]);
        $this->assertPaymentResults(['search' => 'CODE-BETA'], [$second]);
    }

    public function test_payments_are_filtered_by_status(): void
    {
        $categorie = $this->categorie('Terminale');

        $pending = $this->payment('Pending', 'pending@example.test', '699000001', $categorie);
        $validated = $this->payment('Validated', 'validated@example.test', '699000002', $categorie, [
            'status' => true, 'paiement_date' => now(), 'transactionStatus' => 'SUCCESS',
        ]);
        $failed = $this->payment('Failed', 'failed@example.test', '699000003', $categorie, [
            'transactionStatus' => 'FAILED',
        ]);
        $toFinalize = $this->payment('Finalize', 'finalize@example.test', '699000004', $categorie, [
            'transactionStatus' => 'SUCCESS',
        ]);
        $revoked = $this->payment('Revoked', 'revoked@example.test', '699000005', $categorie, [
            'status' => true, 'paiement_date' => now(), 'revoked_at' => now(),
        ]);

        $this->assertPaymentResults(['status' => 'pending'], [$pending]);
        $this->assertPaymentResults(['status' => 'validated'], [$validated]);
        $this->assertPaymentResults(['status' => 'failed'], [$failed]);
        $this->assertPaymentResults(['status' => 'to_finalize'], [$toFinalize]);
        $this->assertPaymentResults(['status' => 'revoked'], [$revoked]);
    }

    public function test_the_payments_page_size_is_configurable_and_bounded(): void
    {
        $categorie = $this->categorie('Terminale');
        for ($index = 0; $index < 30; $index++) {
            $this->payment('Client'.$index, "client{$index}@example.test", '69900'.(1000 + $index), $categorie);
        }

        $this->assertSame(25, $this->payments([])->perPage());
        $this->assertSame(15, $this->payments(['per_page' => 15])->perPage());
        $this->assertSame(50, $this->payments(['per_page' => 50])->perPage());

        // Une taille arbitraire dans l'URL ne doit pas permettre de réclamer
        // toute la table en une requête.
        $this->assertSame(25, $this->payments(['per_page' => 100000])->perPage());
        $this->assertSame(25, $this->payments(['per_page' => 'toutes'])->perPage());
    }

    public function test_pagination_links_keep_the_active_filters(): void
    {
        $categorie = $this->categorie('Terminale');
        for ($index = 0; $index < 40; $index++) {
            $this->payment('Client'.$index, "client{$index}@example.test", '699001'.(100 + $index), $categorie);
        }

        $nextUrl = $this->payments(['search' => 'client', 'per_page' => 15])->nextPageUrl();

        $this->assertStringContainsString('search=client', (string) $nextUrl);
        $this->assertStringContainsString('per_page=15', (string) $nextUrl);
    }

    // ─── Élèves ───────────────────────────────────────────────────────────

    public function test_students_are_filtered_on_every_text_criterion(): void
    {
        $sixieme = $this->classe('Sixième');
        $terminale = $this->classe('Terminale C');

        $awa = $this->student('Awa', 'Ndiaye', 'awa@example.test', '699000001', $sixieme, 'Lycée de Bonabéri');
        $bob = $this->student('Bob', 'Kamga', 'bob@example.test', '699000002', $terminale, 'Collège Vogt');

        $this->assertStudentResults(['email' => 'AWA@'], [$awa]);
        $this->assertStudentResults(['phone' => '000002'], [$bob]);
        $this->assertStudentResults(['classe' => 'sixi'], [$awa]);
        $this->assertStudentResults(['etablissement' => 'vogt'], [$bob]);
        $this->assertStudentResults(['name' => 'ndiaye'], [$awa]);
        $this->assertStudentResults(['name' => 'Bob Kamga'], [$bob]);
        $this->assertStudentResults(['search' => 'bonabéri'], [$awa]);
        $this->assertStudentResults(['sexe' => 'FEMME'], [$awa]);
    }

    public function test_the_students_page_size_is_configurable(): void
    {
        $classe = $this->classe('Sixième');
        for ($index = 0; $index < 30; $index++) {
            $this->student('Élève'.$index, 'Test', "eleve{$index}@example.test", '69900'.(1000 + $index), $classe, 'Lycée');
        }

        $this->assertSame(25, $this->students([])->perPage());
        $this->assertSame(100, $this->students(['per_page' => 100])->perPage());
    }

    // ─── Cours ────────────────────────────────────────────────────────────

    public function test_courses_are_filtered_by_content_and_classification(): void
    {
        $classe = $this->classe('Terminale C');
        $autreClasse = $this->classe('Sixième');
        $maths = $this->matiere('Mathématiques');
        $physique = $this->matiere('Physique');
        $categorie = $this->categorie('Lycée');
        $autreCategorie = $this->categorie('Collège');

        $limites = $this->course('Les limites', 'Étude des limites de fonctions', $maths, $classe, $categorie, open: false);
        $optique = $this->course('Optique géométrique', 'Réfraction et miroirs', $physique, $autreClasse, $autreCategorie, open: true);

        $this->assertCourseResults(['title' => 'limi'], [$limites]);
        $this->assertCourseResults(['description' => 'miroirs'], [$optique]);
        $this->assertCourseResults(['matiere' => (string) $physique], [$optique]);
        $this->assertCourseResults(['classe' => (string) $classe], [$limites]);
        $this->assertCourseResults(['categorie' => (string) $categorie], [$limites]);
        $this->assertCourseResults(['access' => 'free'], [$optique]);
        $this->assertCourseResults(['access' => 'subscription'], [$limites]);
        $this->assertCourseResults(['search' => 'réfraction'], [$optique]);
        $this->assertCourseResults(['author' => 'Admin'], [$limites, $optique]);
    }

    public function test_the_courses_page_size_is_configurable(): void
    {
        $classe = $this->classe('Terminale C');
        $matiere = $this->matiere('Mathématiques');
        $categorie = $this->categorie('Lycée');
        for ($index = 0; $index < 20; $index++) {
            $this->course('Cours '.$index, 'Description', $matiere, $classe, $categorie);
        }

        $this->assertSame(15, $this->courses([])->perPage());
        $this->assertSame(50, $this->courses(['per_page' => 50])->perPage());
    }

    /**
     * Un caractère joker saisi par l'utilisateur doit être cherché tel quel, et
     * non élargir la recherche à toute la table.
     */
    public function test_like_wildcards_typed_by_the_user_are_escaped(): void
    {
        $classe = $this->classe('Terminale C');
        $matiere = $this->matiere('Mathématiques');
        $categorie = $this->categorie('Lycée');

        $percent = $this->course('Les 100% de réussite', 'Description', $matiere, $classe, $categorie);
        $this->course('Un autre cours', 'Description', $matiere, $classe, $categorie);

        $this->assertCourseResults(['title' => '100%'], [$percent]);

        // « % » est cherché comme un caractère ordinaire : seul le titre qui en
        // contient un ressort, au lieu de la table entière.
        $this->assertCourseResults(['title' => '%'], [$percent]);
        $this->assertCourseResults(['title' => '_'], []);
    }

    // ─── Utilitaires ──────────────────────────────────────────────────────

    private function payments(array $query)
    {
        return $this->actingAs($this->admin)
            ->get(route('paiement.index', $query))
            ->assertOk()
            ->viewData('payments');
    }

    private function students(array $query)
    {
        return $this->actingAs($this->admin)
            ->get(route('eleve.index', $query))
            ->assertOk()
            ->viewData('eleves');
    }

    private function courses(array $query)
    {
        return $this->actingAs($this->admin)
            ->get(route('cours.index', $query))
            ->assertOk()
            ->viewData('cours');
    }

    private function assertPaymentResults(array $query, array $expectedIds): void
    {
        $this->assertSame(
            $expectedIds,
            $this->payments($query)->pluck('id')->sort()->values()->all(),
            'Filtre paiements : '.json_encode($query),
        );
    }

    private function assertStudentResults(array $query, array $expectedIds): void
    {
        $this->assertSame(
            $expectedIds,
            $this->students($query)->pluck('id')->sort()->values()->all(),
            'Filtre élèves : '.json_encode($query),
        );
    }

    private function assertCourseResults(array $query, array $expectedIds): void
    {
        $this->assertSame(
            $expectedIds,
            $this->courses($query)->pluck('id')->sort()->values()->all(),
            'Filtre cours : '.json_encode($query),
        );
    }

    private function categorie(string $libelle): int
    {
        return DB::table('categories')->insertGetId([
            'libelle' => $libelle, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function classe(string $libelle): int
    {
        return DB::table('classes')->insertGetId([
            'libelle' => $libelle, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function matiere(string $libelle): int
    {
        return DB::table('matieres')->insertGetId([
            'libelle' => $libelle, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function provider(string $code): int
    {
        return DB::table('payment_providers')->insertGetId([
            'name' => $code, 'code' => $code, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function service(int $providerId, string $title): int
    {
        return DB::table('payment_services')->insertGetId([
            'payment_provider_id' => $providerId, 'title' => $title, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payment(
        string $name,
        string $email,
        string $phone,
        int $categorieId,
        array $options = [],
    ): int {
        $userId = DB::table('users')->insertGetId([
            'name' => $name, 'last_name' => 'Client', 'email' => $email, 'phone' => $phone,
            'rule_id' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $transactionId = DB::table('transactions')->insertGetId([
            'payment_provider_id' => $options['provider'] ?? null,
            'service_id' => $options['service'] ?? null,
            'user_id' => $userId,
            'provider_reference' => $options['reference'] ?? null,
            'reference' => 'MPP-'.$phone,
            'amount' => 1000,
            'phone_number' => $phone,
            'status' => $options['transactionStatus'] ?? 'PENDING',
            'sens' => 'IN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('paiements')->insertGetId([
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'categorie_id' => $categorieId,
            'nombre_de_code' => 1,
            'montant' => 1000,
            'numero_payeur' => $phone,
            'numero_client' => $phone,
            'status' => $options['status'] ?? false,
            'paiement_date' => $options['paiement_date'] ?? null,
            'revoked_at' => $options['revoked_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function code(int $paymentId, string $code): void
    {
        DB::table('codes')->insert([
            'paiements_id' => $paymentId, 'code' => $code, 'actif' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function student(
        string $name,
        string $lastName,
        string $email,
        string $phone,
        int $classeId,
        string $school,
    ): int {
        $userId = DB::table('users')->insertGetId([
            'name' => $name, 'last_name' => $lastName, 'email' => $email, 'phone' => $phone,
            'rule_id' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('eleves')->insertGetId([
            'user_id' => $userId,
            'classe_id' => $classeId,
            'etablissement' => $school,
            'sexe' => $name === 'Awa' ? 'FEMME' : 'HOMME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function course(
        string $title,
        string $description,
        int $matiereId,
        int $classeId,
        int $categorieId,
        bool $open = false,
    ): int {
        return DB::table('cours')->insertGetId([
            'libelle' => $title,
            'description' => $description,
            'video_url' => 'https://cdn.test/video.mp4',
            'matieres_id' => $matiereId,
            'classe_id' => $classeId,
            'categorie_id' => $categorieId,
            'user_id' => $this->admin->id,
            'open' => $open,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rule_id')->default(2);
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('profile_image')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->integer('prix')->default(0);
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('cours', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('matieres_id')->nullable();
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('open')->default(false);
            $table->timestamps();
        });
        Schema::create('eleves', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('sexe')->nullable();
            $table->timestamps();
        });
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::create('payment_services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_provider_id')->nullable();
            $table->string('title');
            $table->string('img')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_provider_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('phone_number');
            $table->string('status');
            $table->string('sens');
            $table->text('metadatas')->nullable();
            $table->timestamps();
        });
        Schema::create('paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('categorie_id');
            $table->integer('nombre_de_code')->default(1);
            $table->decimal('montant', 12, 2);
            $table->string('numero_payeur');
            $table->string('numero_client')->nullable();
            $table->boolean('status')->default(false);
            $table->dateTime('paiement_date')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('paiements_id');
            $table->unsignedBigInteger('eleve_id')->nullable();
            $table->string('code')->unique();
            $table->dateTime('active_date')->nullable();
            $table->boolean('actif')->default(false);
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
        });
    }
}

class FakeFileStorage implements FileStorageService
{
    public function store(UploadedFile $file, string $folder): ?string
    {
        return $file->getClientOriginalName();
    }

    public function delete(string $filename, string $folder): bool
    {
        return true;
    }

    public function get(string $filename, string $folder): ?string
    {
        return $filename;
    }

    public function getDecrypted(string $filename, string $folder): ?string
    {
        return $filename;
    }
}
