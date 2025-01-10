@extends('nav')

@section('content')
<form action="" class="form">
    <div class="row">

        <div class="col-md-3">
            <h1 class="display-5">Partenaires </h1>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" , placeholder="Email" id="searchTextfield">
        </div>
        <!-- <div class="col-md-3">
            <button class="btn btn-outline-primary" type="submit">Rechercher</button>
        </div> -->

        <div class="col-md-3">
            <a href="{{route('partner.add')}}" class="btn btn-outline-primary"> Ajouter </a>
        </div>

    </div>
</form>


<div id="article-list">
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Nom </th>
                <th scope="col" class="display-7 fw-bold">Prénom</th>
                <th scope="col" class="display-7 fw-bold">Téléphone</th>
                <th scope="col" class="display-7 fw-bold">Email</th>
                <th scope="col" class="display-7 fw-bold">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($partners as $user)
            <tr>
                <th scope="row">{{ $user->name }}</th>
                <td>{{ $user->last_name }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->email }}</td>
                <td><a href="{{ route('matiere.create') }}">voire</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection