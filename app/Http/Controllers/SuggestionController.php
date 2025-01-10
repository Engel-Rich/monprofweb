<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    
    public function index(){
        $sugestionList = Suggestion::paginate();
        $result = $sugestionList->getCollection()->transform(function ($value)  {
            if($value->user_id!=null){
                $value->user = User::find($value->user_id);                
            }
            return $value;
        });
        $sugestionList->setCollection($result);
        return view('screen.suggestions.index', ['suggestion'=> $sugestionList]);
    }

    public function store(Request $request)  {
        $userId = auth()?->id();
        try {
            $request->validate([
                'body'=> 'string|required',
                'title' => 'string|nullable'
            ]);
            Suggestion::create([
                'title'=>$request->title,
                'body'=> $request->body,
                'user_id'=>$userId,
            ]);
            return response()->json([
                'status'=>true,
                'data'=> "suggestion send succesFully"
            ], 200);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'status'=>false,
                'data'=> $th->getMessage()
            ], $th->getCode());            
        }

    }

}
