@extends('nav')


@section('content')
    <div class="row display-flex align-items-center">
        <div class="col-md d-flex align-content-center">
            <h1 class="display-5 text-primary">Activation du code de paiement </h1>
        </div>
    </div>

    @error('error')
    <div class="m-3 text-danger display-6" >
        {{ $message }}
    </div>
@enderror
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold border border-solid text-primary">Institullé </th>
                <th scope="col" class="display-7 fw-bold border border-solid text-primary">Valeur</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Nom du client</td>
                <th scope="col">{{ $paie->user->last_name . ' ' . $paie->user->name }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Nombre de codes</td>
                <th scope="col">{{ $paie->nombre_de_code }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Montant du paiement</td>
                <th scope="col">{{ $paie->montant }} XAF</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Type de code</td>
                <th scope="col">{{ $paie->categorie->libelle }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Numéro à débiter</td>
                <th scope="col">{{ $paie->numero_payeur }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Numéro à Notifier</td>
                <th scope="col">{{ $paie->numero_client }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Status actuel du code</td>
                <th scope="col">{{ $paie->status == 0 ? 'En attente' : 'Validé' }}</th>
            </tr>
            <tr>
                <td scope="col" class="display-7 text-secondary border border-solid">Date de paiments</td>
                <th scope="col">{{ $paie->created_at->format('D d M Y - H:m') }}</th>
            </tr>
            {{-- @if ($paie->status == 1) --}}
                <tr>
                    <td scope="col" class="display-7 text-secondary border border-solid">Date de Validation</td>
                    <th scope="col">{{  DateTime::createFromFormat('Y-m-d H:i:s',$paie->paiement_date)->format('D d M Y - H:m')}}</th>
                </tr>
            {{-- @endif --}}
        </tbody>

    </table>
    {{-- @if ($paie->status == 0) --}}
        <div class="row display-flex align-items-center">
            <form action="{{ route('paiement.valide') }}" method="POST">
                @csrf
                <input type="hidden" name="paiement" value="{{ $paie->id }}">
                <div class="col-md d-flex align-content-center">
                    <button type="submit" class="btn btn-outline-primary px-5 fw-bold text-lg"> Activer le code</button>
                </div>
            </form>
        </div>
    {{-- @endif --}}
@endsection
