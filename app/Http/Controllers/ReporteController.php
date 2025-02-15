<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\department;
use App\Models\employees;
use App\Models\area;
use App\Models\prepayrollAdjType;
use App\Models\prepayrollAdjust;
use App\Models\DepartmentRH;
use App\Models\typeincident;
use App\Models\departmentsGroup;
use App\SUtils\SDelayReportUtils;
use App\SUtils\SInfoWithPolicy;
use App\SUtils\SHolidayWork;
use App\SUtils\SGenUtils;
use App\SData\SDataProcess;
use DB;
use Carbon\Carbon;
use App\SUtils\SReg;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Recibe el tipo de reporte y en base a este retorna una colección con los valores posibles
     *
     * @param integer $reportType
     *                  1  Reporte por área
     *                  2  Reporte por grupo de departamentos
     *                  3  Reporte por departamentos
     *                  4  Reporte por empleados
     * 
     * @return view('report.reportES')
     */
    public function esReport($type = 0){
        $lAreas = null;
        $lDepsGroups = null;
        $lDepts = null;
        $lEmployees = null;

        switch ($type) {
            case 1:
                $lAreas = area::select('id','name')->where('is_delete', false)->get();
                break;
            case 2:
                $lDepsGroups = departmentsGroup::select('id','name')->where('is_delete', false)->get();
                break;
            case 3:
                $lDepts = department::select('id','name')->where('is_delete', false)->get();
                break;
            case 4:
                $lEmployees = employees::select('id', 'name', 'num_employee')->where('is_delete', false)->get();
                break;
            case 5:
                $numero = session()->get('name');
                $usuario = DB::table('users')
                    ->where('name',$numero)
                    ->get();
                $dgu = DB::table('group_dept_user')
                    ->where('user_id',$usuario[0]->id)
                    ->select('groupdept_id AS id')
                    ->get();
                $Adgu = [];
                for($i=0;count($dgu)>$i;$i++){
                $Adgu[$i]=$dgu[$i]->id;
                }
                //$lEmployees = employees::select('id', 'name', 'num_employee')->where('is_delete', false)->->get(); 
                $lEmployees = DB::table('employees')
                        ->join('jobs','jobs.id','=','employees.job_id')
                        ->join('departments','departments.id','=','employees.department_id')
                        ->join('department_group','department_group.id','=','departments.dept_group_id')
                        ->orderBy('employees.job_id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active','1')
                        ->whereIn('departments.dept_group_id',$Adgu)
                        ->orderBy('employees.name')
                        ->select('employees.name AS name','employees.num_employee AS num_employee','employees.id AS id')
                        ->get();
                break;
            default:
                # code...
                break;
        }

        return view('report.reportES')->with('lAreas', $lAreas)
                                        ->with('lDepsGroups', $lDepsGroups)
                                        ->with('lDepts', $lDepts)
                                        ->with('lEmployees', $lEmployees)
                                        ->with('reportType', $type);

    }

    public function reporteESView(Request $request){
        $reportType = $request->reportType;
        $values = $request->vals;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        DB::enableQueryLog();

        $register = DB::table('registers AS r')
                    ->join('employees AS e', 'e.id', '=', 'r.employee_id');

        switch ($reportType) {
            case 1:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->join('areas AS a', 'a.id', '=', 'd.area_id')
                                    ->whereIn('a.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id','a.name AS areaname', 'e.external_id')
                                    ->groupBy('e.name','date','type_id','e.num_employee','a.name')
                                    ->orderBy('e.name')
                                    ->orderBy('date')
                                    ->orderBy('time')
                                    ->orderBy('a.id');
                break;
            case 2:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->join('department_group AS dg', 'dg.id', '=', 'd.dept_group_id')
                                    ->whereIn('dg.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id','dg.name AS groupname', 'e.external_id')
                                    ->groupBy('e.name','date','type_id','e.num_employee')
                                    ->orderBy('e.name')
                                    ->orderBy('date')
                                    ->orderBy('time')
                                    ->orderBy('dg.id');
                break;
            case 3:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->whereIn('d.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id','d.name AS depname', 'e.external_id')
                                    ->groupBy('e.name','date','type_id','e.num_employee')
                                    ->orderBy('e.name')
                                    ->orderBy('date')
                                    ->orderBy('time')
                                    ->orderBy('d.id');;
                break;
            case 4:
                $register = $register->whereIn('e.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id', 'e.external_id')
                                    ->groupBy('e.name','date','type_id','e.num_employee')
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            case 5:
                $register = $register->whereIn('e.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id', 'e.external_id')
                                    ->groupBy('e.name','date','type_id','e.num_employee')
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            
            default:
                # code...
                break;
        }
        

        $register = $register->whereBetween('r.date', [$startDate, $endDate])
                             ->where('r.is_delete',0)
                             ->get();

        return view('report.reporteESView')
                        ->with('reportType', $reportType)
                        ->with('lRegistries', $register);
    

    }

    /**
     * Recibe el tipo de reporte y en base a este retorna una colección con los valores posibles
     *
     * @param integer $reportType
     *                  1  Reporte por área
     *                  2  Reporte por grupo de departamentos
     *                  3  Reporte por departamentos
     *                  4  Reporte por empleados
     * 
     * @return view('report.reportRegs')
     */
    public function registriesReport($reportType = 0)
    {
        $lAreas = null;
        $lDepsGroups = null;
        $lDepts = null;
        $lEmployees = null;

        switch ($reportType) {
            case 1:
                $lAreas = area::select('id','name')->where('is_delete', false)->get();
                break;
            case 2:
                $lDepsGroups = departmentsGroup::select('id','name')->where('is_delete', false)->get();
                break;
            case 3:
                $lDepts = department::select('id','name')->where('is_delete', false)->get();
                break;
            case 4:
                $lEmployees = employees::select('id', 'name', 'num_employee')
                                        ->where('is_delete', false)
                                        ->orderBy('name', 'ASC')
                                        ->get();
                break;
            case 5:
                $numero = session()->get('name');
                $usuario = DB::table('users')
                        ->where('name',$numero)
                        ->get();
                $dgu = DB::table('group_dept_user')
                        ->where('user_id',$usuario[0]->id)
                        ->select('groupdept_id AS id')
                        ->get();
                $Adgu = [];
                for($i=0;count($dgu)>$i;$i++){
                    $Adgu[$i]=$dgu[$i]->id;
                }
                //$lEmployees = employees::select('id', 'name', 'num_employee')->where('is_delete', false)->->get(); 
                $lEmployees = DB::table('employees')
                            ->join('jobs','jobs.id','=','employees.job_id')
                            ->join('departments','departments.id','=','employees.department_id')
                            ->join('department_group','department_group.id','=','departments.dept_group_id')
                            ->orderBy('employees.job_id')
                            ->where('employees.is_delete','0')
                            ->where('employees.is_active','1')
                            ->whereIn('departments.dept_group_id',$Adgu)
                            ->orderBy('employees.name')
                            ->select('employees.name AS name','employees.num_employee AS num_employee','employees.id AS id')
                            ->get();
                break;
            default:
                # code...
                break;
        }

        return view('report.reportRegs')->with('lAreas', $lAreas)
                                        ->with('lDepsGroups', $lDepsGroups)
                                        ->with('lDepts', $lDepts)
                                        ->with('lEmployees', $lEmployees)
                                        ->with('reportType', $reportType);
    }

    /**
     * Recibe el formulario con los datos a consultar y el tipo de reporte
     *
     * @param Request $request deberá contener el tipo de reporte, un arreglo con los valores a consultar, 
     *                  la fecha de inicio y la fecha final para consultar el rango de fechas de los registros (checadas)
     * @return view report.reportRegsView
     */
    public function reporteRegistrosView(Request $request)
    {
        $reportType = $request->reportType;
        $values = $request->vals;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $checadasbrutas = $request->checadasbrutas;
        if($request->checadasbrutas != null){
            $checadasbrutas = 1;
        }else{
            $checadasbrutas = 0;
        }

        DB::enableQueryLog();

        $register = DB::table('registers AS r')
                    ->join('employees AS e', 'e.id', '=', 'r.employee_id');

        switch ($reportType) {
            case 1:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->join('areas AS a', 'a.id', '=', 'd.area_id')
                                    ->whereIn('a.id', $values)
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            case 2:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->join('department_group AS dg', 'dg.id', '=', 'd.dept_group_id')
                                    ->whereIn('dg.id', $values)
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            case 3:
                $register = $register->join('jobs AS j', 'j.id', '=', 'e.job_id')
                                    ->join('departments AS d', 'd.id', '=', 'j.department_id')
                                    ->whereIn('d.id', $values)
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            case 4:
                $register = $register->whereIn('e.id', $values)
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            case 5:
                $register = $register->whereIn('e.id', $values)
                                    ->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id')
                                    ->orderBy('date')
                                    ->orderBy('e.name')
                                    ->orderBy('time');
                break;
            default:
                # code...
                break;
        }
        if($checadasbrutas == 1){
               
        }else{
            $register = $register->groupBy('date','type_id','e.name','e.num_employee'); 
        }

        $register = $register->select('e.num_employee', 'e.name', 'r.date', 'r.time', 'r.type_id', 'e.external_id')
                                ->whereBetween('r.date', [$startDate, $endDate])
                                ->where('r.is_delete',0)
                                ->get();

        return view('report.reportRegsView')
                        ->with('reportType', $reportType)
                        ->with('lRegistries', $register);
    }

    public function genDelayReport()
    {
        $config = \App\SUtils\SConfiguration::getConfigurations();

        $lEmployees = SGenUtils::toEmployeeIds(0, 0, []);
        
        return view('report.reportsGen')
                    ->with('tReport', \SCons::REP_DELAY)
                    ->with('sTitle', 'Reporte de Retardos')
                    ->with('sRoute', 'reporteRetardos')
                    ->with('lEmployees', $lEmployees)
                    ->with('startOfWeek', $config->startOfWeek);
    }

    public function genHrExReport()
    {
        $config = \App\SUtils\SConfiguration::getConfigurations();

        $lEmployees = SGenUtils::toEmployeeIds(0, 0, []);

        return view('report.reportsGen')
                    ->with('tReport', \SCons::REP_HR_EX)
                    ->with('sTitle', 'Reporte de tiempos extra')
                    ->with('sRoute', 'reportetiemposextra')
                    ->with('lEmployees', $lEmployees)
                    ->with('startOfWeek', $config->startOfWeek);
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function delaysReport(Request $request)
    {
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;
        $iEmployee = $request->emp_id;
        $nochecan = $request->nochecan;
        if($request->nochecan != null){
            $nochecan = 1;
        }else{
            $nochecan = 0;
        }
        $oStartDate = Carbon::parse($sStartDate);
        $oEndDate = Carbon::parse($sEndDate);

        if (! $oStartDate->lessThanOrEqualTo($oEndDate)) {
            return \Redirect::back()->withErrors(['Error', 'La fecha de inicio debe ser previa a la fecha final']);
        }

        if ($request->optradio == "employee") {
            if ($iEmployee > 0) {
                $lEmployees = SGenUtils::toEmployeeIds(0, 0, 0, [$iEmployee],$nochecan);
                $payWay = $lEmployees[0]->way_pay_id;
            }
            else {
                return \Redirect::back()->withErrors(['Error', 'Debe seleccionar empleado']);
            }
        }
        else {
            /**
             * 1: quincena
             * 2: semana
             * 3: todos
             */
            $payWay = $request->pay_way == null ? \SCons::PAY_W_S : $request->pay_way;

            $filterType = $request->i_filter;
            $ids = $request->elems;
            $lEmployees = SGenUtils::toEmployeeIds($payWay, $filterType, $ids, [] , $nochecan);
        }

        $lRows = SDataProcess::process($sStartDate, $sEndDate, $payWay, $lEmployees);

        $aEmployees = $lEmployees->pluck('num_employee', 'id');
        $lEmpWrkdDays = SDelayReportUtils::getTheoreticalDaysOffBasedOnDaysWorked($lRows, $aEmployees, $sStartDate, $sEndDate);

        $sPayWay = "";
        switch ($payWay) {
            case \SCons::PAY_W_Q :
                $sPayWay = "Quincena";
                break;
            case \SCons::PAY_W_S :
                $sPayWay = "Semana";
                break;
            default:
                $sPayWay = "Todos";
                break;
        }

        $adjTypes = prepayrollAdjType::get()->toArray();

        $lAdjusts = DB::table('prepayroll_adjusts AS pa')
                        ->join('prepayroll_adjusts_types AS pat', 'pa.adjust_type_id', '=', 'pat.id')
                        ->select('pa.employee_id',
                                    'pa.dt_date',
                                    'pa.dt_time',
                                    'pa.minutes',
                                    'pa.apply_to',
                                    'pa.adjust_type_id',
                                    'pat.type_code',
                                    'pat.type_name',
                                    'pa.id'
                                    )
                        ->whereBetween('dt_date', [$sStartDate, $sEndDate])
                        ->where('is_delete', false)
                        ->get();

        return view('report.reportRetardos')
                    ->with('tReport', \SCons::REP_HR_EX)
                    ->with('sStartDate', $sStartDate)
                    ->with('sEndDate', $sEndDate)
                    ->with('sPayWay', $sPayWay)
                    ->with('sTitle', 'Reporte de retardos')
                    ->with('adjTypes', $adjTypes)
                    ->with('lAdjusts', $lAdjusts)
                    ->with('lEmpWrkdDays', $lEmpWrkdDays)
                    ->with('lRows', $lRows);
    }

    /**
     * Muestra reporte de tiempos extra
     *
     * @param Request $request
     * @return void
     */
    public function hrExtReport(Request $request)
    {
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;
        $iEmployee = $request->emp_id;

        $oStartDate = Carbon::parse($sStartDate);
        $oEndDate = Carbon::parse($sEndDate);

        if (! $oStartDate->lessThanOrEqualTo($oEndDate)) {
            return \Redirect::back()->withErrors(['Error', 'La fecha de inicio debe ser previa a la fecha final']);
        }

        if ($request->optradio == "employee") {
            if ($iEmployee > 0) {
                $lEmployees = SGenUtils::toEmployeeIds(0, 0, 0, [$iEmployee]);
                $payWay = $lEmployees[0]->way_pay_id;
            }
            else {
                return \Redirect::back()->withErrors(['Error', 'Debe seleccionar empleado']);
            }
        }
        else {
            /**
             * 1: quincena
             * 2: semana
             * 3: todos
             */
            $payWay = $request->pay_way == null ? \SCons::PAY_W_S : $request->pay_way;

            $filterType = $request->i_filter;
            $ids = $request->elems;
            $lEmployees = SGenUtils::toEmployeeIds($payWay, $filterType, $ids);
        }

        $lRows = SDataProcess::process($sStartDate, $sEndDate, $payWay, $lEmployees);

        $aEmployees = $lEmployees->pluck('num_employee', 'id');
        $lEmpWrkdDays = SDelayReportUtils::getTheoreticalDaysOffBasedOnDaysWorked($lRows, $aEmployees, $sStartDate, $sEndDate);

        $sPayWay = "";
        switch ($payWay) {
            case \SCons::PAY_W_Q :
                $sPayWay = "Quincena";
                break;
            case \SCons::PAY_W_S :
                $sPayWay = "Semana";
                break;
            default:
                $sPayWay = "Todos";
                break;
        }

        $adjTypes = prepayrollAdjType::get()->toArray();

        $lAdjusts = DB::table('prepayroll_adjusts AS pa')
                        ->join('prepayroll_adjusts_types AS pat', 'pa.adjust_type_id', '=', 'pat.id')
                        ->select('pa.employee_id',
                                    'pa.dt_date',
                                    'pa.dt_time',
                                    'pa.minutes',
                                    'pa.apply_to',
                                    'pa.adjust_type_id',
                                    'pat.type_code',
                                    'pat.type_name',
                                    'pa.id'
                                    )
                        ->whereBetween('dt_date', [$sStartDate, $sEndDate])
                        ->where('is_delete', false)
                        ->get();

        return view('report.reportDelaysView')
                    ->with('tReport', \SCons::REP_HR_EX)
                    ->with('sStartDate', $sStartDate)
                    ->with('sEndDate', $sEndDate)
                    ->with('sPayWay', $sPayWay)
                    ->with('sTitle', 'Reporte de tiempos extra')
                    ->with('adjTypes', $adjTypes)
                    ->with('lAdjusts', $lAdjusts)
                    ->with('lEmpWrkdDays', $lEmpWrkdDays)
                    ->with('lRows', $lRows);
    }

    public function hrReport(Request $request)
    {
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;
        $year = Carbon::parse($sStartDate);
        $year = $year->format('Y');
        $lEmployees = [];
        /**
         * 1: quincena
         * 2: semana
         * 3: todos
         */
        $tipoDatos = $request->tipodato;
        $payWay = $request->way_pay;
        $id = 0;
        $id = $request->vals;
        
        switch($request->reportType){
            case 1:
                
                $employees = DB::table('employees')
                        ->join('jobs','jobs.id','=','employees.job_id')
                        ->join('departments','departments.id','=','jobs.department_id')
                        ->join('department_group','department_group.id','=','departments.dept_group_id')
                        ->join('areas','areas.id','=','departments.area_id')
                        ->orderBy('employees.job_id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active', true)
                        ->where('employees.way_pay_id',$payWay);
                for($i = 0 ; count($id) > $i ; $i++ ){
                   if($i != 0){
                        $employees = $employees->OrWhere('areas.id',$id[$i]);
                   }else{
                        $employees = $employees->where('areas.id',$id[$i]);
                   }
                }
                $employees = $employees->select('employees.id')->get();
                for($i = 0 ; count($employees) > $i ; $i++){
                    $lEmployees[$i] = $employees[$i]->id; 
                }
                if($i == 0){
                    return redirect('report/datosreportestps/1/2')->with('mensaje','La periodicidad de pago tiene un error');
                }
            break;
            case 2:
                $employees = DB::table('employees')
                        ->join('jobs','jobs.id','=','employees.job_id')
                        ->join('departments','departments.id','=','jobs.department_id')
                        ->join('department_group','department_group.id','=','departments.dept_group_id')
                        ->orderBy('employees.job_id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active', true)
                        ->where('employees.way_pay_id',$payWay);
                for($i = 0 ; count($id) > $i ; $i++ ){
                   if($i != 0){
                        $employees = $employees->OrWhere('departments.dept_group_id',$id[$i]);
                   }else{
                        $employees = $employees->where('departments.dept_group_id',$id[$i]);
                   }
                }
                $employees = $employees->select('employees.id')->get();
                for($i = 0 ; count($employees) > $i ; $i++){
                    $lEmployees[$i] = $employees[$i]->id; 
                }
                if($i == 0){
                    return redirect('report/datosreportestps/2/2')->with('mensaje','La periodicidad de pago tiene un error');
                }
            break;
            case 3:
                $employees = DB::table('employees')
                        ->join('dept_rh','dept_rh.id','=','employees.dept_rh_id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active', true)
                        ->where('employees.way_pay_id',$payWay);
                for($i = 0 ; count($id) > $i ; $i++ ){
                   if($i != 0){
                        $employees = $employees->OrWhere('dept_rh.id',$id[$i]);
                   }else{
                        $employees = $employees->where('dept_rh.id',$id[$i]);
                   }
                }
                $employees = $employees->select('employees.id AS id')->get();
                for($i = 0 ; count($employees) > $i ; $i++){
                    $lEmployees[$i] = $employees[$i]->id; 
                }
                if($i == 0){
                    return redirect('report/datosreportestps/3/2')->with('mensaje','La periodicidad de pago tiene un error');
                }
            break;
            case 4:
                $comprobacion = DB::table('employees')
                        ->orderBy('employees.job_id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active', true)
                        ->where('employees.way_pay_id',$payWay);
                for($i = 0 ; count($id) > $i ; $i++ ){
                    if($i != 0){
                        $comprobacion = $comprobacion->OrWhere('employees.id',$id[$i]);
                    }else{
                        $comprobacion = $comprobacion->where('employees.id',$id[$i]);
                    }
                }
                $comprobacion = $comprobacion->get();
                if(count($comprobacion) == 0){
                    return redirect('report/datosreportestps/4/2')->with('mensaje','La periodicidad de pago tiene un error');    
                }
                $lEmployees = $id; 
            break;
            case 5:
                $employees = DB::table('employees')
                    ->orderBy('employees.job_id')
                    ->where('employees.is_delete','0')
                    ->where('employees.is_active', true)
                    ->where('employees.way_pay_id',$payWay)
                    ->get();

                    for($i = 0 ; count($employees) > $i ; $i++){
                        $lEmployees[$i] = $employees[$i]->id; 
                    }
                break;
            
        }
        $prueba = SInfoWithPolicy::preProcessInfo($sStartDate,$year,$sEndDate,$payWay,0);
        SHolidayWork::holidayworked($sStartDate,$sEndDate);
        
        //$lEmployees[0] = 32; 
        $lRows = DB::table('processed_data')
                        ->join('employees','employees.id','=','processed_data.employee_id')
                        ->whereIn('employees.id',$lEmployees)
                        ->where(function($query) use ($sStartDate,$sEndDate) {
                            $query->whereBetween('inDate',[$sStartDate,$sEndDate])
                            ->OrwhereBetween('outDate',[$sStartDate,$sEndDate]);
                        })
                        ->orderBy('employees.num_employee')
                        ->orderBy('week')
                        ->orderBy('biweek')
                        ->orderBy('outDate')
                        ->orderBy('week')
                        ->orderBy('biweek')
                        ->select(['employees.num_employee','employees.name', 'employees.external_id','processed_data.week','processed_data.biweek','processed_data.*'])
                        ->get();
        //$lEmployees = $id;
        $incapacidades = DB::table('incidents')
                        ->join('employees','employees.id','=','incidents.employee_id')
                        ->join('incidents_day','incidents_day.incidents_id','=','incidents.id')
                        ->where('employees.is_active','1')
                        ->where('employees.is_delete','0')
                        ->where('incidents.cls_inc_id',2)
                        ->whereIn('employees.id',$lEmployees)
                        ->where(function ($query) use ($sStartDate,$sEndDate) {
                                return $query->whereBetween('start_date', [$sStartDate,$sEndDate])
                                ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                        })
                        ->select('employees.id AS idEmp','incidents_day.date as Date')
                        ->get();
        $vacaciones = DB::table('incidents')
                        ->join('employees','employees.id','=','incidents.employee_id')
                        ->join('incidents_day','incidents_day.incidents_id','=','incidents.id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active','1')
                        ->whereIn('employees.id',$lEmployees)
                        ->where('incidents.cls_inc_id',3)
                        ->where(function ($query) use ($sStartDate,$sEndDate) {
                                return $query->whereBetween('start_date', [$sStartDate,$sEndDate])
                                ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                        })
                        ->select('employees.id AS idEmp','incidents_day.date as Date')
                        ->get();
        $inasistencia = DB::table('incidents')
                        ->join('employees','employees.id','=','incidents.employee_id')
                        ->join('incidents_day','incidents_day.incidents_id','=','incidents.id')
                        ->where('employees.is_delete','0')
                        ->where('employees.is_active','1')
                        ->whereIn('employees.id',$lEmployees)
                        ->where('incidents.cls_inc_id',1)
                        ->where(function ($query) use ($sStartDate,$sEndDate) {
                                return $query->whereBetween('start_date', [$sStartDate,$sEndDate])
                                ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                        })
                        ->select('employees.id AS idEmp','incidents_day.date as Date','incidents.type_incidents_id as tipo')
                        ->get();
        return view('report.reportView')
                    ->with('sTitle', 'Reporte de checadas')
                    ->with('lRows', $lRows)
                    ->with('incapacidades',$incapacidades)
                    ->with('vacaciones',$vacaciones)
                    ->with('inasistencia',$inasistencia)
                    ->with('reporttype',$request->reportType)
                    ->with('tipo', $tipoDatos)
                    ->with('payWay',$payWay);
    } 

    public function prueba(){
        $start = '2020-05-01';
        $end = '2020-05-15';
        $way = 1;
        $year = '2020';    
        $employees[0] = 67;
        

        //$employees[0] = 24;
        $key[0] = 2;
        //$prueba = SInfoWithPolicy::standardization($start,$end,$way,2,$key,$employees);
        $prueba = SInfoWithPolicy::preProcessInfo($start,$year,$end,$way);
    }

    public function datosReporteSecretaria($reportType,$tipoDatos){
        $lAreas = null;
        $lDepsGroups = null;
        $lDepts = null;
        $lEmployees = null;

        switch ($reportType) {
            case 1:
                $lAreas = area::select('id','name')->where('is_delete', false)->get();
                break;
            case 2:
                $lDepsGroups = departmentsGroup::select('id','name')->where('is_delete', false)->get();
                break;
            case 3:
                $lDepts = DepartmentRH::select('id','name')->where('is_delete', false)->get();
                break;
            case 4:
                $lEmployees = employees::select('id', 'name', 'num_employee')
                                        ->where('is_delete', false)
                                        ->orderBy('name', 'ASC')
                                        ->get();
                break;
            
            default:
                # code...
                break;
        }
        //$lWayPay = way_pay::select('id','name')->where('is_delete',false)->get();
        return view('report.datosReportView')->with('lAreas', $lAreas)
                                            ->with('lDepsGroups', $lDepsGroups)
                                            ->with('lDepts', $lDepts)
                                            ->with('lEmployees', $lEmployees)
                                            //->with('lWay',$lWayPay)
                                            ->with('reportType', $reportType)
                                            ->with('tipoDatos', $tipoDatos);
    }

    public function generarReporteSecretaria(Request $request){

    }

    public function reporteRevisionView(){
        return view('report.reportRevisionView');
    }

    public function generarReporteRevision(Request $request){
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;

        $inicio = Carbon::parse($sStartDate);
        $fin = Carbon::parse($sEndDate);
        $diferencia = ($inicio->diffInDays($fin));

        $empleadosSemanal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',2)
                                ->select('employees.id AS id')
                                ->get();
        $empleadosQuincenal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',1)
                                ->select('employees.id AS id')
                                ->get();
        
        
        
        
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow = [];
        $lProg = [];
        for( $x = 0 ; count($empleadosSemanal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosSemanal[$x]->id)
                        ->get();
            $registrosEntrada = DB::table('registers')
                        ->join('employees','employees.id','=','registers.employee_id')
                        ->where('employee_id',$empleadosSemanal[$x]->id)
                        ->where('type_id',1)
                        ->where('registers.is_delete',0)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->groupBy('date')
                        ->select('date AS date','employee_id AS id','employees.name AS name')
                        ->get();
            $registrosSalida = DB::table('registers')
                        ->where('employee_id',$empleadosSemanal[$x]->id)
                        ->where('type_id',2)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->groupBy('date')
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosSemanal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosSemanal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosSemanal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();
                if($i < count($registrosEntrada)){
                    $auxComparacion = Carbon::parse($registrosEntrada[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $row->idEmployee = $idEmpleado;
                    $row->nameEmployee = $nameEmpleado;
                    $row->date = $auxIni->toDateString();
                    $row->entrada = false;
                    $auxIni->addDay();
                }
                $lRow [$j] = $row;
                $j++;
            }
            $auxIni = Carbon::parse($sStartDate);
            $i = 0;
            $j = $auxContador;
            while( $auxFin >= $auxIni ){
                if($i < count($registrosSalida)){
                    $auxComparacion = Carbon::parse($registrosSalida[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $lRow [$j]->salida = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        
                        $lRow [$j]->salida = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $lRow [$j]->salida = false;
                    $auxIni->addDay();
                }
                $j++;
            }
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados = count($empleadosSemanal);
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow1 = [];
        $lProg1 = [];
        for( $x = 0 ; count($empleadosQuincenal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosQuincenal[$x]->id)
                        ->get();
            $registrosEntrada = DB::table('registers')
                        ->join('employees','employees.id','=','registers.employee_id')
                        ->where('employee_id',$empleadosQuincenal[$x]->id)
                        ->where('type_id',1)
                        ->where('registers.is_delete',0)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->groupBy('date')
                        ->select('date AS date','employee_id AS id','employees.name AS name')
                        ->get();
            $registrosSalida = DB::table('registers')
                        ->where('employee_id',$empleadosQuincenal[$x]->id)
                        ->where('type_id',2)
                        ->where('registers.is_delete',0)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->groupBy('date')
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosQuincenal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosQuincenal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosQuincenal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg1[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();
                if($i < count($registrosEntrada)){
                    $auxComparacion = Carbon::parse($registrosEntrada[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $row->idEmployee = $idEmpleado;
                    $row->nameEmployee = $nameEmpleado;
                    $row->date = $auxIni->toDateString();
                    $row->entrada = false;
                    $auxIni->addDay();
                }
                $lRow1 [$j] = $row;
                $j++;
            }
            $auxIni = Carbon::parse($sStartDate);
            $i = 0;
            $j = $auxContador;
            while( $auxFin >= $auxIni ){
                if($i < count($registrosSalida)){
                    $auxComparacion = Carbon::parse($registrosSalida[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $lRow1 [$j]->salida = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        
                        $lRow1 [$j]->salida = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $lRow1 [$j]->salida = false;
                    $auxIni->addDay();
                }
                $j++;
            }
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados1 = count($empleadosQuincenal);
        //$lRowsSem = SDataProcess::process($sStartDate, $sEndDate, 2, $lEmpSem);
        //$lRowsQui = SDataProcess::process($sStartDate, $sEndDate, 1, $lEmpQui);
        $dateini = date_create($sStartDate);
        $datefin = date_create($sEndDate);
        $fechaAux1=date_format($dateini, 'd-m-Y');
        $fechaAux2=date_format($datefin, 'd-m-Y');
        return view('report.reportRevision')->with('lRows',$lRow)->with('lRows1',$lRow1)->with('inicio',$fechaAux1)->with('fin',$fechaAux2)->with('diff',$diferencia)->with('numEmpleados',$numEmpleados)->with('numEmpleados1',$numEmpleados1)->with('programado',$lProg)->with('programado1',$lProg1);
    }

    public function reporteCheckView(){
        return view('report.reportCheckView');
    }

    public function generarReporteCheck(Request $request){
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;

        $inicio = Carbon::parse($sStartDate);
        $fin = Carbon::parse($sEndDate);
        $diferencia = ($inicio->diffInDays($fin));

        $empleadosSemanal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',2)
                                ->select('employees.id AS id')
                                ->get();
        $empleadosQuincenal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',1)
                                ->select('employees.id AS id')
                                ->get();
        
        
        
        
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow = [];
        $lProg = [];
        for( $x = 0 ; count($empleadosSemanal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosSemanal[$x]->id)
                        ->get();
            $registros = DB::table('registers')
                        ->join('employees','employees.id','=','registers.employee_id')
                        ->where('employee_id',$empleadosSemanal[$x]->id)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->where('registers.is_delete',0)
                        ->groupBy('date')
                        ->select('date AS date','employee_id AS id','employees.name AS name')
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosSemanal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosSemanal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosSemanal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();
                if($i < count($registros)){
                    $auxComparacion = Carbon::parse($registros[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $row->idEmployee = $idEmpleado;
                    $row->nameEmployee = $nameEmpleado;
                    $row->date = $auxIni->toDateString();
                    $row->entrada = false;
                    $auxIni->addDay();
                }
                $lRow [$j] = $row;
                $j++;
            }
            
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados = count($empleadosSemanal);
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow1 = [];
        $lProg1 = [];
        for( $x = 0 ; count($empleadosQuincenal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosQuincenal[$x]->id)
                        ->get();
            $registros = DB::table('registers')
                        ->join('employees','employees.id','=','registers.employee_id')
                        ->where('employee_id',$empleadosQuincenal[$x]->id)
                        ->whereBetween('date',[$sStartDate,$sEndDate])
                        ->groupBy('date')
                        ->select('date AS date','employee_id AS id','employees.name AS name')
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosQuincenal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosQuincenal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosQuincenal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg1[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();
                if($i < count($registros)){
                    $auxComparacion = Carbon::parse($registros[$i]->date);    
                    
                    if( $auxIni == $auxComparacion ){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = true;
                        $i++;
                        $auxIni->addDay();
                    }else if($auxIni < $auxComparacion){
                        $row->idEmployee = $idEmpleado;
                        $row->nameEmployee = $nameEmpleado;
                        $row->date = $auxIni->toDateString();
                        $row->entrada = false;
                        $auxIni->addDay();
                    }
                    
                }else{
                    $row->idEmployee = $idEmpleado;
                    $row->nameEmployee = $nameEmpleado;
                    $row->date = $auxIni->toDateString();
                    $row->entrada = false;
                    $auxIni->addDay();
                }
                $lRow1 [$j] = $row;
                $j++;
            }
            
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados1 = count($empleadosQuincenal);
        //$lRowsSem = SDataProcess::process($sStartDate, $sEndDate, 2, $lEmpSem);
        //$lRowsQui = SDataProcess::process($sStartDate, $sEndDate, 1, $lEmpQui);
        $dateini = date_create($sStartDate);
        $datefin = date_create($sEndDate);
        $fechaAux1=date_format($dateini, 'd-m-Y');
        $fechaAux2=date_format($datefin, 'd-m-Y');
        return view('report.reportCheck')->with('lRows',$lRow)->with('lRows1',$lRow1)->with('inicio',$fechaAux1)->with('fin',$fechaAux2)->with('diff',$diferencia)->with('numEmpleados',$numEmpleados)->with('numEmpleados1',$numEmpleados1)->with('programado',$lProg)->with('programado1',$lProg1);
    }

    public function reporteNumRegisterView(){
        return view('report.reportNumRegisterView');
    }

    public function generarReporteNumRegister(Request $request){
        $sStartDate = $request->start_date;
        $sEndDate = $request->end_date;

        $inicio = Carbon::parse($sStartDate);
        $fin = Carbon::parse($sEndDate);
        $diferencia = ($inicio->diffInDays($fin));

        $empleadosSemanal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',2)
                                ->select('employees.id AS id')
                                ->get();
        $empleadosQuincenal = DB::table('employees')
                                ->join('jobs','jobs.id','=','employees.job_id')
                                ->join('departments','departments.id','=','jobs.department_id')
                                ->whereIn('dept_group_id',[1,4,6,7,8,9])
                                ->where('is_active','=',1)
                                ->where('way_pay_id','=',1)
                                ->select('employees.id AS id')
                                ->get();
        
        
        
        
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow = [];
        $lProg = [];
        for( $x = 0 ; count($empleadosSemanal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosSemanal[$x]->id)
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosSemanal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosSemanal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosSemanal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();   
                    
                $row->nameEmployee = $nameEmpleado;
                $row->date = $auxIni->toDateString();
                $fecha = $auxIni->toDateString();
                $numeroEntrada = DB::table('registers')
                                    ->where('date',$fecha)
                                    ->where('employee_id',$idEmpleado)
                                    ->where('registers.is_delete',0)
                                    ->where('type_id',1)
                                    ->select(DB::raw('COUNT(id) as numero'))
                                    ->get();
                $numeroSalida = DB::table('registers')
                                    ->where('date',$fecha)
                                    ->where('employee_id',$idEmpleado)
                                    ->where('registers.is_delete',0)
                                    ->where('type_id',2)
                                    ->select(DB::raw('COUNT(id) as numero'))
                                    ->get();
                if(isset($numeroEntrada[0])){
                    $row->num_entrada = $numeroEntrada[0]->numero;
                }else{
                    $row->num_entrada = 0;
                }
                if(isset($numeroSalida[0])){
                    $row->num_salida = $numeroSalida[0]->numero; 
                }else{
                    $row->num_salida = 0;
                }     
                $auxIni->addDay();
                
                $lRow [$j] = $row;
                $j++;
            }
            
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados = count($empleadosSemanal);
        $dateS = Carbon::parse($sStartDate);
        $dateE = Carbon::parse($sEndDate);
        $auxIni = Carbon::parse($sStartDate);
        $auxFin = Carbon::parse($sEndDate);
        
        $auxContador = 0;
        $j = 0;
        $i = 0;
        $lRow1 = [];
        $lProg1 = [];
        for( $x = 0 ; count($empleadosQuincenal) > $x ; $x++ ){
            $programado = false;
            $empleado = DB::table('employees')
                        ->where('id',$empleadosQuincenal[$x]->id)
                        ->get();
            $asignacion = DB::table('schedule_assign')
                    ->where('is_delete',0)
                    ->where('employee_id',$empleadosQuincenal[$x]->id)
                    ->where('start_date','<=',$sStartDate)
                    ->where(function ($query) use ($sStartDate,$sEndDate) {
                        return $query->where('start_date','<=',$sStartDate)
                        ->orwhereBetween('end_date', [$sStartDate,$sEndDate]);
                    })
                    ->get();
            
            $programacion = DB::table('week_department_day')
                    ->join('day_workshifts','week_department_day.id','=','day_workshifts.day_id')
                    ->join('day_workshifts_employee','day_workshifts.id','=','day_workshifts_employee.day_id')
                    ->join('workshifts','day_workshifts.workshift_id','=','workshifts.id')
                    ->join('type_day','day_workshifts_employee.type_day_id','=','type_day.id')
                    ->join('employees','employees.id','=','day_workshifts_employee.employee_id')
                    ->where('employees.id',$empleadosQuincenal[$x]->id)
                    ->where('week_department_day.date',$sStartDate)
                    ->get();
            if(count($asignacion) > 0 || count($programacion) > 0){
                $programado = true;
            }       
            $j = $auxContador;
            $i = 0;
            $idEmpleado = $empleadosQuincenal[$x]->id;
            $nameEmpleado = $empleado[0]->name;
            $lProg1[$x] = $programado;
            while( $auxFin >= $auxIni ){
                $row = new SReg();   
                    
                $row->nameEmployee = $nameEmpleado;
                $row->date = $auxIni->toDateString();
                $fecha = $auxIni->toDateString();
                $numeroEntrada = DB::table('registers')
                                    ->where('date',$fecha)
                                    ->where('employee_id',$idEmpleado)
                                    ->where('registers.is_delete',0)
                                    ->where('type_id',1)
                                    ->select(DB::raw('COUNT(id) as numero'))
                                    ->get();
                $numeroSalida = DB::table('registers')
                                    ->where('date',$fecha)
                                    ->where('employee_id',$idEmpleado)
                                    ->where('registers.is_delete',0)
                                    ->where('type_id',2)
                                    ->select(DB::raw('COUNT(id) as numero'))
                                    ->get();
                if(isset($numeroEntrada[0])){
                    $row->num_entrada = $numeroEntrada[0]->numero;
                }else{
                    $row->num_entrada = 0;
                }
                if(isset($numeroSalida[0])){
                    $row->num_salida = $numeroSalida[0]->numero; 
                }else{
                    $row->num_salida = 0;
                }     
                $auxIni->addDay();
                
                $lRow1 [$j] = $row;
                $j++;
            }
            
            $auxContador = $j;
            $auxIni = Carbon::parse($sStartDate);
        } 
        //$lEmpSem = SGenUtils::toEmployeeIds(0, 0, null, $lEmpSem);
        //$lEmpQui = SGenUtils::toEmployeeIds(0, 0, null, $lEmpQui);
        $numEmpleados1 = count($empleadosQuincenal);
        //$lRowsSem = SDataProcess::process($sStartDate, $sEndDate, 2, $lEmpSem);
        //$lRowsQui = SDataProcess::process($sStartDate, $sEndDate, 1, $lEmpQui);
        $dateini = date_create($sStartDate);
        $datefin = date_create($sEndDate);
        $fechaAux1=date_format($dateini, 'd-m-Y');
        $fechaAux2=date_format($datefin, 'd-m-Y');
        return view('report.reportNumRegister')->with('lRows',$lRow)->with('lRows1',$lRow1)->with('inicio',$fechaAux1)->with('fin',$fechaAux2)->with('diff',$diferencia)->with('numEmpleados',$numEmpleados)->with('numEmpleados1',$numEmpleados1)->with('programado',$lProg)->with('programado1',$lProg1);
    }
    
        // Reporte de incidencias
        public function incidentReportView(){
            $incidents = typeincident::select('id','name')->get();
            $deptos = DepartmentRH::select('id','name')->where('is_delete',0)->get();
            $employees = employees::select('id','name')->where('is_delete',0)->get();

            return view('report.incidentReport')->with('incidents',$incidents)->with('deptos',$deptos)->with('employees',$employees);
        }
   
        public function incidentReportGenerar(Request $request){
            $sStartDate = $request->start_date;
            $sEndDate = $request->end_date; 
            
            $employee = $request->employees;
            $department = $request->dept;
            $tipo = $request->tipo;
            $incident = $request->incidents;

            switch($tipo){
                //Caso de departamentos
                case 1:
                    $datas = DB::table('incidents')
                                    ->join('employees','incidents.employee_id','=','employees.id')
                                    ->join('dept_rh','dept_rh.id','=','employees.dept_rh_id')
                                    ->join('class_incident','class_incident.id','=','incidents.cls_inc_id')
                                    ->join('type_incidents','type_incidents.id', '=', 'incidents.type_incidents_id')
                                    ->whereBetween('incidents.start_date',[$sStartDate,$sEndDate])
                                    ->whereBetween('incidents.end_date',[$sStartDate,$sEndDate])
                                    ->where('dept_rh.id',$department) 
                                    ->orderBy('incidents.start_date')
                                    ->select('incidents.start_date AS fechaI','incidents.end_date AS fechaF','type_incidents.name AS tipo','dept_rh.name AS departamento');
                    break;
                //Caso de empleados
                case 2:
                    if($employee != 0){
                        $datas = DB::table('incidents')
                                    ->join('employees','incidents.employee_id','=','employees.id')
                                    ->join('dept_rh','dept_rh.id','=','employees.dept_rh_id')
                                    ->join('class_incident','class_incident.id','=','incidents.cls_inc_id')
                                    ->join('type_incidents','type_incidents.id', '=', 'incidents.type_incidents_id')
                                    ->whereBetween('incidents.start_date',[$sStartDate,$sEndDate])
                                    ->whereBetween('incidents.end_date',[$sStartDate,$sEndDate])
                                    ->where('employees.id',$employee) 
                                    ->orderBy('incidents.start_date')
                                    ->select('incidents.start_date AS fechaI','incidents.end_date AS fechaF','type_incidents.name AS tipo','employees.name AS empleado'); 
                    }else{
                        $datas = DB::table('incidents')
                                    ->join('employees','incidents.employee_id','=','employees.id')
                                    ->join('class_incident','class_incident.id','=','incidents.cls_inc_id')
                                    ->join('type_incidents','type_incidents.id', '=', 'incidents.type_incidents_id')
                                    ->whereBetween('incidents.start_date',[$sStartDate,$sEndDate])
                                    ->whereBetween('incidents.end_date',[$sStartDate,$sEndDate])
                                    ->orderBy('incidents.start_date')
                                    ->select('incidents.start_date AS fechaI','incidents.end_date AS fechaF','type_incidents.name AS tipo','employees.name AS empleado');              
                    }
                    break;
            }
            //Si selecciono una incidencia en particular
            if($incident != 0){
                $datas = $datas->where('incidents.type_incidents_id',$employee)->get();
            }else{
                $datas = $datas->get();
            }
        

            return view('report.incidentReportGenerar')->with('datas',$datas)->with('tipo',$tipo);
        }
}
