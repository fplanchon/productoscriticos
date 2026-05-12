<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NesteoController extends Controller
{
    public function marcarNesteoRelleno(Request $request, $accion = null)
    {
        if (!$request->session()->has('id_usuario') || !$request->session()->has('id_fase')) {
            return redirect()->route('login');
        }

        return view('marcarNesteoRelleno', compact('accion'));
    }

    public function obtenerEstadoNesteoRelleno(Request $request)
    {
        $idNesteoRelleno = intval($request->input('id_nesteo_relleno'));

        if ($idNesteoRelleno <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID_NESTEO_RELLENO invalido.'
            ]);
        }

        try {
            $filas = DB::select(
                '
                SELECT
                    ID_NESTEO_RELLENO,
                    NRO_PIEZA_RELLENO,
                    ESTADO
                FROM NESTEO_PIEZA_RELLENO
                WHERE ID_NESTEO_RELLENO = :id_nesteo_relleno
                ',
                ['id_nesteo_relleno' => $idNesteoRelleno]
            );

            if (empty($filas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe el ID_NESTEO_RELLENO indicado.'
                ]);
            }

            $this->customUtf8Encode($filas);

            $row = $filas[0];
            $estadoActual = intval($row->ESTADO ?? $row->estado ?? 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'ID_NESTEO_RELLENO' => $row->ID_NESTEO_RELLENO ?? $row->id_nesteo_relleno ?? null,
                    'NRO_PIEZA_RELLENO' => $row->NRO_PIEZA_RELLENO ?? $row->nro_pieza_relleno ?? null,
                    'ESTADO' => $estadoActual,
                    'ESTADO_TEXTO' => $this->estadoTexto($estadoActual),
                    'ACCION_HABILITADA' => $this->accionHabilitada($estadoActual),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }

    public function actualizarEstadoNesteoRelleno(Request $request)
    {
        if (!$request->session()->has('id_usuario') || !$request->session()->has('id_fase')) {
            return response()->json([
                'success' => false,
                'message' => 'Sesion invalida. Ingrese nuevamente.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'id_nesteo_relleno' => 'required|integer|min:1',
            'accion' => 'required|in:CHECK,STOP',
        ], [
            'id_nesteo_relleno.required' => 'ID_NESTEO_RELLENO es obligatorio.',
            'id_nesteo_relleno.integer' => 'ID_NESTEO_RELLENO debe ser entero.',
            'id_nesteo_relleno.min' => 'ID_NESTEO_RELLENO debe ser mayor a 0.',
            'accion.required' => 'Accion es obligatoria.',
            'accion.in' => 'Accion invalida.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $idNesteoRelleno = intval($request->input('id_nesteo_relleno'));
        $accion = strtoupper(trim((string) $request->input('accion')));
        $estadoDestino = ($accion === 'STOP') ? 0 : 10;

        try {
            $existencia = DB::select(
                '
                SELECT ID_NESTEO_RELLENO, ESTADO
                FROM NESTEO_PIEZA_RELLENO
                WHERE ID_NESTEO_RELLENO = :id_nesteo_relleno
                ',
                ['id_nesteo_relleno' => $idNesteoRelleno]
            );

            if (empty($existencia)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe el ID_NESTEO_RELLENO indicado.'
                ]);
            }

            $this->customUtf8Encode($existencia);
            $estadoActual = intval($existencia[0]->ESTADO ?? $existencia[0]->estado ?? 0);
            $accionPermitida = $this->accionHabilitada($estadoActual);

            if ($accion !== $accionPermitida) {
                return response()->json([
                    'success' => false,
                    'message' => 'La accion solicitada no coincide con el estado actual. Recargue el QR e intente nuevamente.'
                ]);
            }

            DB::update(
                '
                UPDATE NESTEO_PIEZA_RELLENO
                SET ESTADO = :estado,
                    FECHA_ULTIMA_ACTIV = CURRENT_TIMESTAMP
                WHERE ID_NESTEO_RELLENO = :id_nesteo_relleno
                ',
                [
                    'estado' => $estadoDestino,
                    'id_nesteo_relleno' => $idNesteoRelleno,
                ]
            );

            $filasActualizadas = DB::select(
                '
                SELECT
                    ID_NESTEO_RELLENO,
                    NRO_PIEZA_RELLENO,
                    ESTADO
                FROM NESTEO_PIEZA_RELLENO
                WHERE ID_NESTEO_RELLENO = :id_nesteo_relleno
                ',
                ['id_nesteo_relleno' => $idNesteoRelleno]
            );

            $this->customUtf8Encode($filasActualizadas);

            $row = $filasActualizadas[0];
            $estadoActual = intval($row->ESTADO ?? $row->estado ?? $estadoDestino);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
                'data' => [
                    'ID_NESTEO_RELLENO' => $row->ID_NESTEO_RELLENO ?? $row->id_nesteo_relleno ?? null,
                    'NRO_PIEZA_RELLENO' => $row->NRO_PIEZA_RELLENO ?? $row->nro_pieza_relleno ?? null,
                    'ESTADO' => $estadoActual,
                    'ESTADO_TEXTO' => $this->estadoTexto($estadoActual),
                    'ACCION_HABILITADA' => $this->accionHabilitada($estadoActual),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }

    private function estadoTexto($estado)
    {
        if (intval($estado) === 10) {
            return 'RELLENO HABILITADO';
        }

        if (intval($estado) === 0) {
            return 'SUSPENDIDO';
        }

        return 'ESTADO NO CONTEMPLADO';
    }

    private function accionHabilitada($estado)
    {
        if (intval($estado) === 10) {
            return 'STOP';
        }

        return 'CHECK';
    }

    private function customUtf8Encode(&$data)
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $row) {
            if (!is_object($row)) {
                continue;
            }

            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row->$key = utf8_encode($value);
                }
            }
        }
    }
}
