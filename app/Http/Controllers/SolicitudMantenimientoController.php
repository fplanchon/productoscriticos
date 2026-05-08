<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudMantenimientoController extends Controller
{
    public function solicitudMantenimiento(Request $request, $accion = null)
    {
        if (!$request->session()->has('id_usuario') || !$request->session()->has('id_fase')) {
            return redirect()->route('login');
        }

        return view('solicitudMantenimiento', compact('accion'));
    }

    public function validarInventarioMantenimiento(Request $request)
    {
        $idInventario = intval($request->input('id_inventario'));

        if ($idInventario <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID de inventario invalido.'
            ]);
        }

        try {
            $inventario = DB::select(
                "
                SELECT
                    inventario.ID_INVENTARIO_PROD,
                    inventario.NRO_INVENTARIO,
                    inventario.DESCRIPCION
                FROM FAC_INVENTARIO_PRODUCTOS inventario
                WHERE inventario.ID_INVENTARIO_PROD = :id_inventario
                ORDER BY inventario.NRO_INVENTARIO
                ",
                ['id_inventario' => $idInventario]
            );

            $this->customUtf8Encode($inventario);

            if (empty($inventario)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El inventario no existe.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $inventario[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }

    public function obtenerPendientesMantenimiento(Request $request)
    {
        $idInventario = intval($request->input('id_inventario'));

        if ($idInventario <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID de inventario invalido.'
            ]);
        }

        try {
            $pendientes = DB::select(
                "
                SELECT
                    FAC_HISTORIAL_MANTENIMIENTO.id_mantenimiento,
                    FAC_HISTORIAL_MANTENIMIENTO.fecha_hora_creacion,
                    sup.nombre as supervisor,
                    encargado.nombre as encargado,
                    alta.nombre as usuarioalta,
                    tipo.descripcion as tipo_mant,
                    est.nombre_estado,
                    i.id_usuario_inventario,
                    falla.desc_falla,
                    tf.descripcion as tipo_falla,
                    i.descripcion as inventario_desc
                from FAC_HISTORIAL_MANTENIMIENTO
                    left join permisos sup on FAC_HISTORIAL_MANTENIMIENTO.usuario_supervisor = sup.idusuario
                    left join permisos encargado on FAC_HISTORIAL_MANTENIMIENTO.usuario_encargado = encargado.idusuario
                    left join permisos alta on FAC_HISTORIAL_MANTENIMIENTO.usuario_alta = alta.idusuario
                    left join tipo_mantenimiento tipo on FAC_HISTORIAL_MANTENIMIENTO.ID_TIPO_MANTENIMIENTO = tipo.id_tipo_mantenimiento
                    left join estados_hist_mant est on FAC_HISTORIAL_MANTENIMIENTO.id_estado = est.id_estado
                    left join fac_inventario_productos i on FAC_HISTORIAL_MANTENIMIENTO.id_inventario_prod = i.id_inventario_prod
                    left join tipo_falla_mantenim falla on FAC_HISTORIAL_MANTENIMIENTO.id_falla = falla.id_falla_mant
                    left join tipo_falla_mant tf  on falla.id_tipo_falla = tf.id_tipo_falla
                where FAC_HISTORIAL_MANTENIMIENTO.id_inventario_prod = :id_inventario
                    and FAC_HISTORIAL_MANTENIMIENTO.id_estado in (10,20,30)
                    and FAC_HISTORIAL_MANTENIMIENTO.id_tipo_mantenimiento = 8
                order by FAC_HISTORIAL_MANTENIMIENTO.id_mantenimiento desc
                ",
                ['id_inventario' => $idInventario]
            );

            $this->customUtf8Encode($pendientes);

            $tablaPendientes = array_map(function ($row) {
                return [
                    'ID_MANTENIMIENTO' => $row->ID_MANTENIMIENTO ?? $row->id_mantenimiento ?? null,
                    'FECHA_HORA_CREACION' => $row->FECHA_HORA_CREACION ?? $row->fecha_hora_creacion ?? null,
                    'NOMBRE_ESTADO' => $row->NOMBRE_ESTADO ?? $row->nombre_estado ?? null,
                ];
            }, $pendientes);

            return response()->json([
                'success' => true,
                'data' => $tablaPendientes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }

    public function solicitarReparacionMantenimiento(Request $request)
    {
        $idInventario = intval($request->input('id_inventario'));
        $detalle = trim((string) $request->input('detalle'));
        $idUsuario = intval(session('id_usuario'));
        $idFase = intval(session('id_fase'));

        if ($idInventario <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID de inventario invalido.'
            ]);
        }

        if ($idUsuario <= 0 || $idFase <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Sesion invalida. Ingrese nuevamente.'
            ]);
        }

        if ($detalle === '') {
            return response()->json([
                'success' => false,
                'message' => 'Debe ingresar el detalle de la solicitud.'
            ]);
        }

        try {
            $result = DB::select(
                'execute procedure PROC_SOLICITA_MANTEN(:id_inventario,:id_usuario,:detalle,:id_fase)',
                [
                    'id_inventario' => $idInventario,
                    'id_usuario' => $idUsuario,
                    'detalle' => $detalle,
                    'id_fase' => $idFase,
                ]
            );

            $this->customUtf8Encode($result);

            $procedureResult = $this->extractProcedureText($result);
            $normalized = strtoupper(trim($procedureResult));

            $successTokens = ['OK', 'TRUE', '1', 'EXITO', 'SUCCESS'];
            if ($procedureResult === '' || in_array($normalized, $successTokens, true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud generada correctamente.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $procedureResult
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }

    private function extractProcedureText($result)
    {
        if (empty($result) || !isset($result[0])) {
            return '';
        }

        $row = (array) $result[0];
        foreach ($row as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return '';
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
