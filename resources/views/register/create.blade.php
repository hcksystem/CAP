@extends("theme.$theme.layout")
@section('title')
    Checadas
@endsection

@section('content')
<div class="row" id="divRegistries">
    <div class="col-lg-12">
        @include('includes.form-error')
        @include('includes.mensaje')
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Crear checada</h3>
                <div class="box-tools pull-right">
                    <a href="{{route('checada')}}" class="btn btn-block btn-info btn-sm">
                        <i class="fa fa-fw fa-reply-all"></i> Regresar
                    </a>
                </div>
            </div>
            <form action="{{route('guardar_checada')}}" id="form-general" class="form-horizontal" method="POST" autocomplete="off">
                @csrf
                <div class="box-body">
                    @include('register.form')
                </div>
                <div class="box-footer">
                    <div class="col-lg-3"></div>
                    <div class="col-lg-6">
                        @include('includes.button-form-create')
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section("scripts")
    <script src="{{ asset("assets/js/vue.js") }}" type="text/javascript"></script>
    <script src="{{ asset("assets/js/vues/SVueRegistries.js") }}" type="text/javascript"></script>
@endsection