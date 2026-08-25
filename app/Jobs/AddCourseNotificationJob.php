<?php

namespace App\Jobs;

use App\Models\Cours;
use App\Models\Eleve;
use App\Services\PushNotifictaionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddCourseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $courseId;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $status = $this->course->open;
        $courseFromDB = Cours::with('categorie', 'classe', 'matiere')->find($this->courseId);
        $message = 'Un nouveau cours de '.$courseFromDB->matiere->libelle.' a été ajouté dans la catégorie '.$courseFromDB->categorie->libelle.' de la classe '.$courseFromDB->classe->libelle;
        $title = $courseFromDB->libelle;
        $status = $courseFromDB->open;

        if ($status) {
            $eleves = Eleve::with('user')->where('classe_id', $courseFromDB->classe_id)->get();
            $tokens = $eleves->pluck('user.fcm_token')->toArray();
        } else {
            // Récupérer les élèves avec codes actifs dans la catégorie du cours
            $eleves = Eleve::whereHas('codes', function ($query) use ($courseFromDB) {
                $query->where('actif', true)
                    ->whereNull('revoked_at')
                    ->whereHas('paiement', function ($q) use ($courseFromDB) {
                        $q->where('categorie_id', $courseFromDB->categorie_id)
                            ->where('status', true)
                            ->whereNull('revoked_at');
                    });
            })
                ->with('user') // Chargement anticipé de la relation user
                ->get();
            // Récupération des tokens FCM valides
            $tokens = $eleves->pluck('user.fcm_token')
                ->filter() // Enlève les valeurs null
                ->values() // Réindexe le tableau
                ->unique() // Évite les doublons
                ->toArray();
        }
        Log::info('Tokens trouvés pour ce cours: ', ['course_id' => $this->courseId, 'tokens' => $tokens, 'Count' => count($tokens)]);
        if (count($tokens) > 0) {
            $notification = new PushNotifictaionService($message, $title);
            $notification->sendMultiCastFCM($tokens, 'NEW_COURSE');
        }
    }
}
