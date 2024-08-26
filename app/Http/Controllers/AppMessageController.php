<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotifiCationJob;
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
            $messageList = AppMessage::paginate(page: $request->page ?: 1);
            return response()->json([
                'status' => true,
                'data' => $messageList,
            ], 200);
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
                'data' => $unreadMessageList,
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
            SendNotifiCationJob::dispatch($message->id)->delay(now());          
            // dd($message);
            return redirect()->route('messages.index');

        } catch (\Throwable $th) {
            dd($th);
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
        $userId = auth()->id();

        try {
            $request->validate([
                'message_id' => 'integer|exists:notification_reades,id'
            ]);
            $messageId = $request->message_id;
            $notificationReaderInstance = NotificationReade::where('user_id', $userId)->where('app_message_id', $messageId)->exists();
            if (!$notificationReaderInstance) {
                NotificationReade::create([
                    'user_id' => $userId,
                    'app_message_id' => $messageId,
                ]);
                return response()->json([
                    'status' => true,
                    'data' => 'notification has been read succesfully',
                ], 200);
            }
        } catch (\Throwable $th) {
           
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
