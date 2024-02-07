@extends('nav')


@section('content')
        <div class="row display-flex align-items-center">
            <div class="col-md-3 d-flex align-content-center">
                <h1 class="display-5">Paiements </h1>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control", placeholder="Recherche">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary" type="submit">Rechercher</button>
            </div>
        </div>
  

    <table class="table">
        <thead>
            <tr class="display-flex align-items-center ">
                <th scope="col" class="display-7 fw-bold border border-solid">Nom du client</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Qte</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Montant</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Categorie</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Num.débiter</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Num.notifier</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Status</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Date de paiments</th>                
                <th scope="col" class="display-7 fw-bold border border-solid">Date de validation</th>                
                <th scope="col" class="display-7 fw-bold border border-solid">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($paiements as $paie)
                <tr>
                    <th scope="row">{{$paie->user->last_name.' '. $paie->user->name }}</th>
                    <td>{{ $paie->nombre_de_code}}</td>
                    <td><strong>{{ $paie->montant }} XAF</strong></td>
                    <td>{{ $paie->categorie->libelle }}</td>
                    <td>{{ $paie->numero_payeur }}</td>
                    <td>{{ $paie->numero_client }}</td>                    
                    <td>{{ $paie->status==0?'En attente':"Validé"}}</td>
                    <td>{{ $paie->created_at->format('D d M Y H:m') }}</td>
                    <td>{{ $paie->paiement_date !=null? DateTime::createFromFormat('Y-m-d H:i:s',$paie->paiement_date)->format('D d M Y H:m') : '/'}}</td>
                    <td><a href="{{route('paiement.active',$paie->id)}}">Valider</a></td>
                    {{-- <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
