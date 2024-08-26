@extends('nav')


@section('content')     
    <div class="row">
        <div class="col">
            <h1 class="display-5"> Messages </h1>
        </div>
        <div class="col">
            <a href="{{ route('messages.create') }}">
                <button class="btn btn-outline-primary ">Envoyer un message</button>
            </a>
        </div>
    </div>    

    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Titre</th>
                <th scope="col" class="display-7 fw-bold">Description</th>                
                <th scope="col" class="display-7 fw-bold">Date d'envoie</th>
                {{-- <th scope="col" class="display-7 fw-bold">Action</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($messages as $msg)
            <tr>
                <th scope="row">{{$msg->title  }}</th>
                {{-- <td>{{$matiere->libelle  }}</td>                 --}}
                <td>{{ $msg->body }}</td>
                <td>{{DateTime::createFromFormat('Y-m-d H:i:s',$msg->created_at)->format('D d M Y H:m')}}</td>
                {{-- <td><a href="#">Voi</a></td> --}}
            </tr>
            @endforeach                            
        </tbody>
    </table>

@endsection