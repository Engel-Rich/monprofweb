@extends('nav')


@section('content')     
    <div class="row">
        <div class="col">
            <h1 class="display-5"> Suggestion </h1>
        </div>
        
    </div>    

    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Suggestion</th>
                {{-- <th scope="col" class="display-7 fw-bold">Description</th>                 --}}
                <th scope="col" class="display-7 fw-bold">Envoyé par</th>
                <th scope="col" class="display-7 fw-bold">Date d'envoie</th>
                
            </tr>
        </thead>
        <tbody>
            @foreach ($suggestion as $msg)
            <tr>
                <td scope="row">{{$msg->body  }}</td>               
                <td> {{ $msg->user != null? $msg->user->email :  '/' }}</td>
                <td>{{DateTime::createFromFormat('Y-m-d H:i:s',$msg->created_at)->format('D d M Y H:m')}}</td>
                
            </tr>
            @endforeach                            
        </tbody>
    </table>
    {{$suggestion->links()}}
@endsection