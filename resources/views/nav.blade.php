@extends('base')

@section('nav')
<div class="container-fluid">
    <div class="row flex-nowrap">
        <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">
            <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100">
                <a href="{{route('index')}}" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                    <span class="fs-5 d-none d-sm-inline">Mon Prof</span>
                </a>
                <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
                    <li class="nav-item">
                        <a href="{{route('classe.index')}}" class="nav-link align-middle px-0">
                            <i class="fs-4 bi-house"></i> <span class="ms-1 d-none d-sm-inline">Classes</span>
                        </a>
                    </li>                   
                    <li>
                        <a href="{{route('matiere.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Matières</span></a>
                    </li>
                    <li>
                        <a href="{{route('eleve.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Elèves</span></a>
                    </li>                                        
                    <li>
                        <a href="{{route('professeur.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Enseignants</span></a>
                    </li>
                    <li>
                        <a href="{{route('professeur.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Cours</span></a>
                    </li>
                    <li>
                        <a href="{{route('professeur.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Questions</span></a>                    </li>
                    <li>                        
                        <a href="{{route('professeur.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Catégories</span></a>
                    </li>
                    {{-- Paiments --}}
                    <li>
                        <a href="#submenu3" data-bs-toggle="collapse" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-grid"></i> <span class="ms-1 d-none d-sm-inline">Paiments</span> </a>
                            <ul class="collapse nav flex-column ms-1" id="submenu3" data-bs-parent="#menu">
                            <li class="w-100">
                                <a href="{{route('matiere.index')}}" class="nav-link px-0"> <span class="d-none d-sm-inline">En attente</span> 1</a>
                            </li>
                            <li>
                                <a href="{{route('matiere.index')}}" class="nav-link px-0"> <span class="d-none d-sm-inline">Terminés</span> 2</a>
                            </li>                           
                        </ul>
                    </li>

                    {{-- Codes des élèves --}}

                    <li>
                        <a href="#submenu3" data-bs-toggle="collapse" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-grid"></i> <span class="ms-1 d-none d-sm-inline">Codes</span> </a>
                            <ul class="collapse nav flex-column ms-1" id="submenu3" data-bs-parent="#menu">
                            <li class="w-100">
                                <a href="#" class="nav-link px-0"> <span class="d-none d-sm-inline">Tous les Codes</span></a>
                            </li>
                            <li class="w-100">
                                <a href="#" class="nav-link px-0"> <span class="d-none d-sm-inline">Utilisés</span> </a>
                            </li>
                            <li>
                                <a href="#" class="nav-link px-0"> <span class="d-none d-sm-inline">Non utilisé</span></a>
                            </li>                           
                        </ul>
                    </li>
                    {{-- <li>
                        <a href="" data-bs-toggle="collapse" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-speedometer2"></i> <span class="ms-1 d-none d-sm-inline">Paiments</span> </a>
                        <ul class="collapse show nav flex-column ms-1" id="submenu1" data-bs-parent="#menu">
                            <li class="w-100">
                                <a href="{{route('matiere.index')}}" class="nav-link px-0"> <span class="d-none d-sm-inline">En attente</span> 1 </a>
                            </li>
                            <li>
                                <a href="{{route('classe.index')}}" class="nav-link px-0"> <span class="d-none d-sm-inline">Terminé</span> 2 </a>
                            </li>
                        </ul>
                    </li> --}}
                    {{--  --}}
                    <li>
                        <a href="{{route('professeur.index')}}" class="nav-link px-0 align-middle">
                            <i class="fs-4 bi-table"></i> <span class="ms-1 d-none d-sm-inline">Enseignants</span></a>
                    </li>
                </ul>
                <hr>
                <div class="dropdown pb-4">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://www.monprof.com/images/france/logo.png" alt="hugenerd" width="30" height="30" class="rounded-circle">
                        <span class="d-none d-sm-inline mx-1">Autres</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow">                      
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{route('auth.logout')}}">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col py-3">
            @yield('content')
        </div>
    </div>
</div>
@endsection
