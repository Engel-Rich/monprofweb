@extends('nav')

@section('content')
<form action="{{route('statistiques')}}" class="form">
    <div class="row">

        <div class="col-md-3">
            <h1 class="display-5">Statistiques </h1>
        </div>
        <div class="col-md-3">
            <!-- add classe dropdown -->
            <select name="classe" id="classe" , class="form-control">
                <option value="">Toutes les classes</option>
                @foreach ($classes as $classe)
                <option value="{{ $classe->id }}">{{ $classe->libelle }}</option>
                @endforeach
            </select>
        </div>
        <!-- <div class="col-md-3"> -->
        <!-- add date plage selection -->
        <!-- </div> -->
        <div class="col-md-3">
            <button class="btn btn-outline-primary" type="submit">Filtrer</button>
        </div>

    </div>
</form>


<div id="article-list">
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Categories </th>
                <th scope="col" class="display-7 fw-bold">Nbr.Aabonnements actif</th>
                <th scope="col" class="display-7 fw-bold">Montant</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $categorie)
            <tr>
                <th scope="row">{{ $categorie->libelle }}</th>
                <td>{{ $categorie->total_paiements }}</td>
                <td>{{$categorie->total_montant }}</td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection