<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LlamadosAsistenciaController extends Controller
{
    public function leerLlamadosAsistencia(Request $request){
        if(!$request->session()->has('id_usuario') OR !$request->session()->has('id_fase')){
            return redirect()->route('login');
        }

        return view('leerLlamadosAsistencia');
    }//leerLlamadosAsistencia

    public function obtenerInfoLlamado(Request $request){
        $id_llamado = $request->input('id_llamado');

        try {
            $result = DB::select("
                SELECT
                    l.ID_LLAMADOS_ASISNT,
                    l.ID_FASE_ORIGEN,
                    l.ID_FASE_DESTINO,
                    l.OBS_LLAMADOS,
                    l.ID_USUARIO_CREA,
                    l.FECHA_SOLICITUD,
                    l.NRO_SOLICITUD,
                    l.ACTIVO,
                    fo.DESC_FASES as FASE_ORIGEN_DESC,
                    fd.DESC_FASES as FASE_DESTINO_DESC,
                    u.USUARIO as USUARIO_CREA_NOMBRE
                FROM LLAMADOS_DE_ASISTENCIAS l
                LEFT JOIN FASES_DE_PRODUC fo ON l.ID_FASE_ORIGEN = fo.ID_FASE
                LEFT JOIN FASES_DE_PRODUC fd ON l.ID_FASE_DESTINO = fd.ID_FASE
                LEFT JOIN PERMISOS u ON l.ID_USUARIO_CREA = u.IDUSUARIO
                WHERE l.ID_LLAMADOS_ASISNT = ? AND l.ACTIVO = 1
            ", [$id_llamado]);
            $this->customUtf8Encode($result);
            if (!empty($result)) {
                return response()->json(['success' => true, 'data' => $result[0]]);
            } else {
                return response()->json(['success' => false, 'message' => 'Llamado no encontrado o inactivo.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
    }//obtenerInfoLlamado

    public function realizarLlamadoAsistencia(Request $request){
        $id_llamado = $request->input('id_llamado');
        $id_usuario = session('id_usuario');

        try {
            $result = DB::select("execute procedure LLAMADOSNUEVAACTIVIDAD(:ID_USUARIO_ACTI, :ID_LLAMADO)", [
                'ID_USUARIO_ACTI' => $id_usuario,
                'ID_LLAMADO' => $id_llamado
            ]);

            if (!empty($result) && $result[0]->RESULTADO == 'OK') {
                return response()->json(['success' => true, 'message' => 'Llamado realizado exitosamente.']);
            } else {
                $errorMsg = !empty($result) ? $result[0]->RESULTADO : 'Error desconocido';
                return response()->json(['success' => false, 'message' => $errorMsg]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
    }//realizarLlamadoAsistencia

        private function customUtf8Encode(&$Data){
        if(!empty($Data)){
            array_walk_recursive($Data, function(&$value, $key){
                foreach($value as $k => $val){
                    $value->$k = utf8_encode($val);
                }
            });
        }

    }//customUtf8Encode
}
