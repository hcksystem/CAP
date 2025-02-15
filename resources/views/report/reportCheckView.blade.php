@extends("theme.$theme.layout")
@section('styles1')
    <link rel="stylesheet" href="{{asset("assets/css/chosen.min.css")}}">
@endsection
@section('title')
Reporte revision
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        @include('includes.form-error')
        @include('includes.mensaje')
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Reporte revisión día checado</h3>
                <div class="box-tools pull-right">
                </div>
            </div>
            <form action="{{ route('generar_chequeo') }}">
                <div class="box-body" id="reportApp">
                    <div class="row">
                        <div class="col-md-5 col-md-offset-1">
                            <label for="start_date">Fecha inicial:</label>
                            <input type="date" name="start_date" id="start_date" :value="startDate" required>
                        </div>
                        <div class="col-md-5">
                            <label for="end_date">Fecha final:</label>
                            <input type="date" name="end_date" id="end_date" :value="endDate" required>
                        </div>
                    </div>
                    <br>
                
                <div class="box-footer">
                    <div class="col-lg-3"></div>
                    <div class="col-lg-6">
                        <button class="btn btn-warning" id="generar" name="generar" type="submit">Generar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 
@endsection

@section("scripts")
    
    <script src="{{ asset("assets/js/chosen.jquery.min.js") }}" type="text/javascript"></script>
    

    <script>
        $(".chosen-select").chosen();
    </script>
@endsection