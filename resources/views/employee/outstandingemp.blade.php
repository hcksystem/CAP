@extends("theme.$theme.layout")
@section('title')
Empleados
@endsection

@section("scripts")
<script src="{{asset("assets/pages/scripts/admin/datatable/indexFaltantes.js")}}" type="text/javascript"></script>
<script>
    $(document).ready( function () {
        $('#myTable').DataTable({
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            },
            "colReorder": true,
            "dom": 'Bfrtip',
            "lengthMenu": [
                [ 10, 25, 50, 100, -1 ],
                [ 'Mostrar 10', 'Mostrar 25', 'Mostrar 50', 'Mostrar 100', 'Mostrar todo' ]
            ],
            "buttons": [
                    'copy', 'csv', 'excel', 'print'
                ]
        });
    });
</script>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        @include('includes.mensaje')
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Empleados pendientes de departamento</h3>
                @include('layouts.usermanual', ['link' => "http://192.168.1.233:8080/dokuwiki/doku.php?id=wiki:asignacionpstoydeptopen"])
                <div class="box-tools pull-right">
                </div>
            </div>
            <div class="box-body">
                <table class="table table-striped table-bordered table-hover" id="myTable">
                    <thead>
                        <tr>
                            <th>Número empleado</th>
                            <th>Nombre empleado</th>
                            <th>Departamento CAP</th>
                            <th>Puesto</th>
                            <th class="width70"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)
                        <tr>
                            <td>{{$data->num_employee}}</td>
                            <td>{{$data->name}}</td>
                            <td>{{$data->department_id == null ? "" : $data->department->name}}</td>
                            <td>{{$data->job_id == null ? "" : $data->job->name}}</td>
                            <td>
                                @if(isset($foraneos))

                                @else
                                <form action="{{route('terminar_configurar', ['id' => $data->id])}}" class="d-inline form-configurar" method="POST">
                                    @csrf @method("delete")
                                    <button type="submit" class="btn-accion-tabla tooltipsC" title="Confirmar departamento">
                                        <i class="fa fa-fw fa-check text-danger"></i>
                                    </button>
                                </form>
                                @endif
                                <a href="{{route('editar_empleado_faltante', ['id' => $data->id])}}" class="btn-accion-tabla tooltipsC" title="Editar este registro">
                                    <i class="fa fa-fw fa-pencil"></i>
                                </a>
                                @if(isset($foraneos))

                                @else
                                <form action="{{route('enviar_empleado_foraneo', ['id' => $data->id])}}" class="d-inline form-eliminar" method="POST">
                                    @csrf @method("delete")
                                    <button type="submit" class="btn-accion-tabla eliminar tooltipsC" title="Enviar a foraneo">
                                        <i class="fa fa-fw fa-truck  text-danger"></i>
                                    </button>
                                </form>
                                @endif
                                
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection