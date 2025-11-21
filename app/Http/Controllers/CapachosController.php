<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CapachosController extends Controller
{

    public function leerCapacho()
    {
        $accion = session('accion');

        if(empty(intval($accion))){
            $accion = 1;
        }

        $Titulos = ['Leer', 'Llenar', 'Denunciar Vacío'];
        $Colores = ['info', 'success', 'danger'];

        $tituloAccion = $Titulos[$accion-1];
        $colorAccion = $Colores[$accion-1];
        return view('leerCapachos', compact('accion','tituloAccion', 'colorAccion'));
    }//capachosLeer


    public function obtenerCapachoQr(Request $request){
        $id_capacho = $request->input('id_capacho');
        $accion = $request->input('accion');

        $Capacho = self::obtenerCapachoPorId($id_capacho);
        $this->customUtf8Encode($Capacho);

        $Capacho = $Capacho[0];

        $Capacho->POSICIONES = self::buscarPosicionesCapacho($Capacho->ID_CAPACHO);

        /*if($accion == 2 AND intval($Capacho->ID_ESTADO_ACTUAL) !== 10){
            return response()->json([
                'success' => false,
                'message' => 'El Capacho no está "VACIO", se encuentra '.$Capacho->ESTADO_CAPACHO,
                'data' => null,
            ]);
           //return Helpers::apiResponse(null,, 422);
        }

        if($accion === 3 AND intval($Capacho->ID_ESTADO_ACTUAL) !== 20){
            return response()->json([
                'success' => false,
                'message' => 'El Capacho no está "LLENO", se encuentra '.$Capacho->ESTADO_CAPACHO,
                'data' => null,
            ]);
        }*/

        return response()->json([
            'success' => true,
            'message' =>null,
            'data' => ['Capacho' => $Capacho],
        ]);
        //return Helpers::apiResponse(['Capacho' => $Capacho]);
    }//obtenerCapachoQr

    private function customUtf8Encode(&$Data){
        if(!empty($Data)){
            array_walk_recursive($Data, function(&$value, $key){
                foreach($value as $k => $val){
                    $value->$k = utf8_encode($val);
                }
            });
        }

    }//customUtf8Encode


    public function ejecutarActividad(Request $request){
        $validator = Validator::make($request->all(), [
            'accion' => 'required|integer',
            'id_capacho'=> 'required|integer',
            'id_posicion'=> 'required|integer',
        ],[
            'accion.required' => 'Accion a ejecutar es obligatoria.',
            'accion.integer' => 'Accion debe ser entero.',

            'id_capacho.required' => 'ID Capacho es obligatorio.',
            'id_capacho.integer' => 'ID Capacho debe ser entero.',

            'id_posicion.required' => 'Posicion es obligatorio.',
            'id_posicion.integer' => 'Posicion debe ser entero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => null,
            ]);

        }

        $id_capacho = $request->input('id_capacho');
        $id_posicion = $request->input('id_posicion');
        $accion = $request->input('accion');
        $id_usuario = session('id_usuario');


        $res = self::ejecutarProcActividad($accion, $id_capacho, $id_usuario, $id_posicion);

        if($res[0]->ERROR_STR !== ''){
            return response()->json([
                'success' => false,
                'message' => $res[0]->ERROR_STR,
                'data' => null,
            ]);

        }

        if(empty($res[0]->ID_ACTIVIDAD_SALIDA)){
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar la actividad, contacte administrador',
                'data' => null,
            ]);

        }

        return response()->json([
            'success' => true,
            'message' =>null,
            'data' => ['ID_ACTIVIDAD' => $res[0]->ID_ACTIVIDAD_SALIDA],
        ]);
    }//ejecutarActividad

    public function obtenerCapachoPorId($id_capacho)
    {
        return DB::connection()->select(
             "SELECT
                                c.ID_CAPACHO,
                                c.ID_TIPO,
                                c.ID_PIEZA_PROD,
                                c.ID_UNIDAD_MEDIDA,
                                c.CANTIDAD,
                                c.NRO_CAPACHO,
                                c.ID_USUARIO_ALTA,
                                c.FECHA_ALTA,
                                c.ID_ESTADO_CAPACHO,
                                c.ID_FASE,
                                CASE
                                    WHEN c.id_tipo = 7 THEN prod.desripcion
                                    WHEN c.id_tipo = 1 THEN psup.descripcion
                                    WHEN c.id_tipo = 3 THEN pinf.descripcion
                                    ELSE 'SIN DESCRIPCION'
                                END AS PROD_DESC,
                                ct.TIPO_CAPACHO_DESC,
                                usr_a.USUARIO,
                                acti.fecha_hora_alta AS FECHA_ACTIVIDAD,
                                acti.ESTADO_CAPACHO,
                                acti.ID_ESTADO_ACTUAL,
                                fases.DESC_FASES
                            FROM CAPACHOS c
                                    INNER JOIN PERMISOS usr_a ON c.ID_USUARIO_ALTA = usr_a.IDUSUARIO
                                    INNER JOIN CAPACHOS_TIPO ct ON c.id_tipo = ct.id_tipo_capacho
                                    INNER JOIN fases_de_produc fases on c.id_fase = fases.id_fase
                                    LEFT JOIN PRODUCTO prod on c.id_pieza_prod = prod.id_producto
                                    LEFT JOIN PIEZA_PLANO psup on c.id_pieza_prod = psup.id_pieza
                                    LEFT JOIN pieza_plano_inferior pinf on c.id_pieza_prod = pinf.id_pieza_inferior
                                    LEFT JOIN (
                                        SELECT a.ID_CAPACHO, a.ID_ESTADO_ACTUAL, a.FECHA_HORA_ALTA, e.ESTADO_CAPACHO
                                        FROM CAPACHOS_ACTIVIDAD a
                                        INNER JOIN CAPACHOS_ESTADOS e ON a.ID_ESTADO_ACTUAL = e.id_estado_capacho
                                WHERE a.FECHA_HORA_ALTA = (
                                    SELECT MAX(a2.FECHA_HORA_ALTA)
                                    FROM CAPACHOS_ACTIVIDAD a2
                                    WHERE a2.ID_CAPACHO = a.ID_CAPACHO
                                )
                            ) acti ON c.ID_CAPACHO = acti.ID_CAPACHO
                                        WHERE 1=1  and c.activo = 1
                                            and  c.id_capacho = :id_capacho
                                        ",
            ['id_capacho' => $id_capacho]
        );
    }//obtenerCapachoPorId

    public function ejecutarProcActividad($accion, $id_capacho, $id_usuario, $id_posicion)
    {
        return DB::connection()->select(
            'execute procedure CAPACHOS_NUEVA_ACTIVIDAD(:ACCION, :ID_CAPACHO, :ID_USUARIO, :ID_POSICION)',
            [
                'ACCION' => $accion,
                'ID_CAPACHO' => $id_capacho,
                'ID_USUARIO' => $id_usuario,
                'ID_POSICION' => $id_posicion
            ]
        );
    }//ejecutarActividad


    public function buscarPosicionesCapacho($id_capacho){
        return DB::connection()->select(
            "select cp.ID_POSICION, cp.ID_CAPACHO, cp.ID_FASE, cp.POSICION, cp.FECHA_ALTA, cp.ID_USUARIO_ALTA, cp.ACTIVO,
                    f.desc_fases as FASE_DESTINO,
                    acti.fecha_hora_alta AS FECHA_ACTIVIDAD,
                    coalesce( acti.ESTADO_CAPACHO, 'INICIAL VACIO') as ESTADO_CAPACHO,
                    coalesce(acti.ID_ESTADO_ACTUAL, 10) as ID_ESTADO_ACTUAL,
                    RIGHT('00' || EXTRACT(DAY FROM acti.FECHA_HORA_ALTA), 2) || '/' ||
                      RIGHT('00' || EXTRACT(MONTH FROM acti.FECHA_HORA_ALTA), 2) || '/' ||
                      EXTRACT(YEAR FROM acti.FECHA_HORA_ALTA) || ' ' ||
                      RIGHT('00' || EXTRACT(HOUR FROM acti.FECHA_HORA_ALTA), 2) || ':' ||
                      RIGHT('00' || EXTRACT(MINUTE FROM acti.FECHA_HORA_ALTA), 2) 
                     AS FECHA_HORA_ESTADO_CASTEADA
                from CAPACHOS_POSICIONES  cp
                    inner join fases_de_produc f on cp.id_fase = f.id_fase
                    LEFT JOIN (
                            SELECT a.ID_CAPACHO, a.ID_POSICION, a.ID_ESTADO_ACTUAL, a.FECHA_HORA_ALTA, e.ESTADO_CAPACHO
                            FROM CAPACHOS_ACTIVIDAD a
                            INNER JOIN CAPACHOS_ESTADOS e ON a.ID_ESTADO_ACTUAL = e.id_estado_capacho
                        WHERE a.FECHA_HORA_ALTA = (
                                SELECT MAX(a2.FECHA_HORA_ALTA)
                                FROM CAPACHOS_ACTIVIDAD a2
                                WHERE a2.ID_POSICION = a.ID_POSICION
                            )
                    ) acti ON cp.ID_POSICION = acti.ID_POSICION
                WHERE cp.ACTIVO = 1 and cp.ID_CAPACHO = :id_capacho order by cp.fecha_alta desc ",
            ['id_capacho'=> $id_capacho]
        );
    }//buscarPosicionesCapacho
}
