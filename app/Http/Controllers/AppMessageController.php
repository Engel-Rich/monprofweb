<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotifiCationJob;
// use App\Services\PushNotifictaionService;
// use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\AppMessage;
use App\Models\NotificationReade;
use Illuminate\Http\Request;

class AppMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->rule_id == 1) {
            $messageList = AppMessage::paginate();
            return view('screen.message.message_index', ['messages' => $messageList]);
        } else {
            try {
                $messageList = AppMessage::orderBy('created_at','desc')-> paginate(page: $request->page ?: 1);
            $result = $messageList->getCollection()->transform( function ($value) use ($user){
                $value->status = NotificationReade::where('app_message_id','=', $value->id)->where('user_id','=', $user->id)->exists();
                return $value;
            });
            $messageList->setCollection($result);
            return response()->json([
                'status' => true,
                'data' => $messageList,
            ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                ], $th->getCode());
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getNotificationsOnRead()
    {
        try {
            $userId = auth()->id();
            $unreadMessageList = AppMessage::whereNotIn('id', function ($queryBuilder) use ($userId) {
                $queryBuilder->select('app_message_id')
                    ->from('notification_reades')
                    ->where('user_id', $userId);
            })->get();
            return response()->json([
                'status' => true,
                'data' => count($unreadMessageList),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => $th->getMessage(),
            ], $th->getCode());
        }
    }
    public function create()
    {
        return view('screen.message.message_creation');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validationData = ['title' => 'string|required', 'body' => 'string|required'];
            $request->validate($validationData);
            $dataToSave = [
                'title' => $request->title,
                'body' => $request->body,
            ];
            $message = AppMessage::create($dataToSave);
            SendNotifiCationJob::dispatch($message->body,$message->title)->delay(now()->addSeconds(2));
            return redirect()->route('messages.index');

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return to_route('messages.create')->withErrors([
                'error' => $th->getMessage(),
            ])->onlyInput('title', 'body');
        }
    }

    /**
     * Display the specified resource.
     */
    public function readNotification(Request $request)
    {
        

        try {
            $userId = auth()->id();
            $request->validate([
                'message_id' => 'integer|exists:app_messages,id'
            ]);
            $id = $request->message_id;
            $notificationReaderInstance = NotificationReade::where('user_id', $userId)->where('app_message_id', $id)->exists();
            if (!$notificationReaderInstance) {
                NotificationReade::create([
                    'user_id' => $userId,
                    'app_message_id' => $id,
                ]);
                return response()->json([
                    'status' => true,
                    'data' => 'notification has been read succesfully',
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'data' => 'Notification not found',
                ], 400);
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'status' => false,
                'data' => $th->getMessage(),
            ], $th->getCode());
        }
    }

    public function show(AppMessage $appMessage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppMessage $appMessage)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AppMessage $appMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppMessage $appMessage)
    {
        //
    }
}
