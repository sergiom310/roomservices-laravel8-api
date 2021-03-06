<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoHabitacion;
use App\Models\Bitacora;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TipoHabitacionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:usuario.index', ['only' => ['index', 'show']]);
        $this->middleware('permission:usuario.create', ['only' => ['store']]);
        $this->middleware('permission:usuario.update', ['only' => ['update', 'desActivar']]);
        $this->middleware('permission:usuario.destroy', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ($page = \Request::get('page')) {
            $limit = \Request::get('limit') ? \Request::get('limit') : 20;
            $TipoHabitacion = TipoHabitacion::select(
                'tipo_habitacion.id',
                'des_tipo_habitacion',
                'tipo_habitacion.created_at',
                'tipo_habitacion.estatus',
                'nom_estado')
            ->join('estados', 'tipo_habitacion.estatus', '=', 'estados.id')
            ->whereNotIn('tipo_habitacion.estatus', [1, 5])
            ->paginate($limit);

            $TipoHabitacion = $TipoHabitacion->toArray();
            $TipoHabitacion = $TipoHabitacion['data'];
        } else {
            $TipoHabitacion = TipoHabitacion::select(
                'tipo_habitacion.id',
                'des_tipo_habitacion',
                'tipo_habitacion.created_at',
                'tipo_habitacion.estatus',
                'nom_estado')
            ->join('estados', 'tipo_habitacion.estatus', '=', 'estados.id')
            ->whereNotIn('tipo_habitacion.estatus', [1, 5])
            ->get();
        }

        return response()->json([
            "data" => $TipoHabitacion
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => 'La descripción del tipo de habitación es requerida.',
            'max'      => 'La descripción del tipo de habitación debe ser máximo 50 caracteres.'
        ];
        $validator = \Validator::make($request->all(), [
            'des_tipo_habitacion' => 'required|max:50'
        ], $messages);

        if ($validator->passes()) {
            $TipoHabitacion = TipoHabitacion::create($request->all());

            return response()->json([
                "data" => $TipoHabitacion
            ], 201);
        }

        return response()->json(['error' => $validator->errors()->all()], 422);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $TipoHabitacion = TipoHabitacion::whereId($id)->get();

        return response()->json([
            "data" => $TipoHabitacion
        ], 200);
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
        $TipoHabitacion = TipoHabitacion::findOrFail($id);

        $messages = [
            'required' => 'La descripción del tipo de habitación es requerida.',
            'max'      => 'La descripción del tipo de habitación debe ser máximo 50 caracteres.'
        ];
        $validator = \Validator::make($request->all(), [
            'des_tipo_habitacion' => 'required|max:50'
        ], $messages);

        if ($validator->passes()) {
            $obsBitacora = $TipoHabitacion->toJson();

            $Bitacora = Bitacora::create([
                'tabla_id' => $id,
                'user_id' => \Auth::user()->id,
                'nom_tabla' => 'tipo_habitacion',
                'estado_id' => $TipoHabitacion->estatus,
                'estatus' => 4,
                'created_at' => Carbon::now(),
                'obs_bitacora' => $obsBitacora
            ]);

            $TipoHabitacion->update($request->all());
            return response()->json(['success' => 'Registro actualizado exitosamente'], 201);
        }

        return response()->json(['error' => 'Error actualizando BD!'], 422);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reversetipohab(Request $request, $id)
    {
        $TipoHabitacion = TipoHabitacion::findOrFail($id);

        $TipoHabitacion->update(['estatus' => 2]);

        return response()->json(['success' => 'Registro restaurado exitosamente'], 201);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function selectTipoHabitacion(Request $request)
    {
        $TipoHabitacion = TipoHabitacion::select(
            'tipo_habitacion.id AS value',
            'des_tipo_habitacion AS label'
        )
        ->join('estados', 'tipo_habitacion.estatus', '=', 'estados.id')
        ->whereNotIn('tipo_habitacion.estatus', [1, 5])
        ->get()
        ;

        \Log::debug($TipoHabitacion);

        return response()->json([
            "data" => $TipoHabitacion
        ], 200);
    }

    /**
     * mark the specified resource as deleted.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $TipoHabitacion = TipoHabitacion::findOrFail($id);

        $obsBitacora = $TipoHabitacion->toJson();

        $Bitacora = Bitacora::create([
            'tabla_id' => $id,
            'user_id' => \Auth::user()->id,
            'nom_tabla' => 'tipo_habitacion',
            'estado_id' => $TipoHabitacion->estatus,
            'estatus' => 2,
            'created_at' => Carbon::now(),
            'obs_bitacora' => $obsBitacora
        ]);

        $TipoHabitacion->update(['estatus' => 5]);

        return response()->json(['success' => 'Registro eliminado'], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $TipoHabitacion = TipoHabitacion::findOrFail($id);

        $obsBitacora = $TipoHabitacion->toJson();

        $Bitacora = Bitacora::create([
            'tabla_id' => $id,
            'user_id' => \Auth::user()->id,
            'nom_tabla' => 'tipo_habitacion',
            'estado_id' => 13,
            'estatus' => 2,
            'created_at' => Carbon::now(),
            'obs_bitacora' => $obsBitacora
        ]);

        $TipoHabitacion->delete();

        return response()->json(['success' => 'Registro eliminado'], 201);
    }
}
