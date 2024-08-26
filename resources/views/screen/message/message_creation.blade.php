@extends('nav')

@section('content')
    <h1 class="display-5">Nouveau message</h1>

    <div class="container py-5 px-5">
        <div class="row">
            <div class="col-lg col-md">
                <form method="POST"
                    action="{{route('messages.store') }}">
                    @csrf
                   
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Titre du message</label>
                        <input type="text" class="form-control" id="title" aria-describedby="classe_name"
                            name="title" value="{{ old('title') }}" required>

                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="body">Corp du message</label>
                        <textarea rows="4" type="text" class="form-control" id="body" name="body"
                            value="{{ old('body') }}" required>  </textarea>                      
                    </div>

                    <button type="submit" class="btn btn-outline-primary px-5">Envoyer</button>
                </form>
            </div>
            <div class="col-lg col-md">

            </div>
        </div>
    </div>
@endsection
