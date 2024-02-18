@extends('nav')

@section('content')
{{-- {{dd($question->reponse)}} --}}
<div class="col-md-3 d-flex align-content-center">
    <h1 class="display-5">Question {{$question->tire}} </h1>
</div>
<div class="row">
    <div class="col">
        <h5 class="">Classe  :  {{$question->classe->libelle}} </h5><br>
        <h5 class="">Matière : {{$question->classe->libelle}} </h5><br>
        <h5 class="">Description : </h5>
        <p>
            {{$question->description}}
        </p>
    </div>
    <div class="col"> 
            <h2 class="display-5">Reponse</h2>
            <form enctype="multipart/form-data" action="{{$question->reponse==null ? route('reponse.store') : route('reponse.update',$question->reponse?->id)}}" method="post">
                @if ($question->reponse !=null)
                    @method('PUT')
                @endif
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold" for="titre">Titre</label>
                    <input  type="text" class="form-control" id="titre" name="titre" value="{{old('titre',$question->reponse?->titre)}}"/>
                </div>
                <input type="hidden" id="questions_id" name="questions_id" value="{{$question->id}}"/>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="description">Description</label>
                    <textarea  rows="5" type="text" class="form-control" id="description" name="description" required>
                        {{old('description',$question->reponse?->description)}}
                    </textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="video">Chargez une image</label>
                    <input type="file" class="form-control" id="image" name="image"
                        accept="image/jpg,image/png,image/jpeg,image/*">
                </div>
                <button type="submit" class="btn btn-outline-primary px-5 mb-3">Submit</button>
            </form>
       
         
    </div>
</div>
@endsection