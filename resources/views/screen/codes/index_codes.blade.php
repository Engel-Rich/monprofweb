@extends('nav')


@section('content')
        <div class="row display-flex align-items-center">
            <div class="col-md-4 d-flex align-content-center">
                <h1 class="display-6">Tous les codes Codes </h1>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control", placeholder="Recherche">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary" type="submit">Rechercher</button>
            </div>
        </div>
  

    <table class="table">
        <thead>
            <tr class="display-flex align-items-center ">
                <th scope="col" class="display-7 fw-bold border border-solid">Codes</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Date de paiement</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Date d'activation</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Elèves.user</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Status du code</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($codes as $code)
                <tr>
                    <th scope="row">{{$code->code }}</th>
                    <td>{{ $code->created_at->format('D d M Y H:m')}}</td>      
                    <td>
                        {{ $code->active_date !=null? DateTime::createFromFormat('Y-m-d H:i:s',$code->active_date)->format('D d M Y H:m') : '/'}}              
                    </td>
                    <td>{{ $code->eleve?->user==null? '/': $code->eleve?->user?->name.' '.($code->eleve?->user?->last_name?:'') }}</td>                                      
                    <td>{{ $code->actif==0?'En attente':"Activé"}}</td>                    
                    <td><a href="#">Détails</a></td>
                </tr>
            @endforeach
        </tbody>        
    </table>
    {{$codes->links()}}
@endsection
