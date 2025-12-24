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

    public function avanzaCapacho()
    {
        $tituloAccion = 'Scanear Capacho';
        $colorAccion = 'primary';
        $id_fase = session('id_fase');
        $descripcionFase = self::obtenerDescripcionFase($id_fase);

        $id_usuario = session('id_usuario');
        $permisosController = new PermisosController();
        $tienePermisoDirectoLleno = $permisosController->tienePermiso($id_usuario, 'SIGWEB_PRODUCCION_CAPACHOS_DIRECTO_LLENO');

        return view('avanzaCapachos', compact('tituloAccion', 'colorAccion', 'descripcionFase', 'tienePermisoDirectoLleno'));
    }//avanzaCapacho

    public function verTrazabilidad()
    {
        $tituloAccion = 'Trazabilidad de Capacho';
        $colorAccion = 'info';

        return view('verTrazabilidad', compact('tituloAccion', 'colorAccion'));
    }//verTrazabilidad


    public function obtenerCapachoQr(Request $request){
        $id_capacho = $request->input('id_capacho');
        $accion = $request->input('accion');

        $Capacho = self::obtenerCapachoPorId($id_capacho);
        $this->customUtf8Encode($Capacho);

        $Capacho = $Capacho[0];
        $id_fase_usuario = session('id_fase');
        $Capacho->POSICIONES = self::buscarPosicionesCapacho($Capacho->ID_CAPACHO, $id_fase_usuario);
        $Capacho->IDENTIFICADORES = self::obtenerIdentificadoresCapachoConEstado($Capacho->ID_CAPACHO);
        $this->customUtf8Encode($Capacho->IDENTIFICADORES);

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
            'id_posicion'=> 'integer',
            'id_identificador'=> 'required|integer',
        ],[
            'accion.required' => 'Accion a ejecutar es obligatoria.',
            'accion.integer' => 'Accion debe ser entero.',

            'id_capacho.required' => 'ID Capacho es obligatorio.',
            'id_capacho.integer' => 'ID Capacho debe ser entero.',

            //'id_posicion.required' => 'Posicion es obligatorio.',
            'id_posicion.integer' => 'Posicion debe ser entero.',
            'id_identificador.required' => 'Identificador es obligatorio.',
            'id_identificador.integer' => 'Identificador debe ser entero.',
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
        $id_identificador = $request->input('id_identificador');

        //dd($accion, $id_capacho,$id_usuario, $id_posicion , $id_identificador);
        $res = self::ejecutarProcActividad($accion, $id_capacho, $id_usuario, $id_posicion, $id_identificador);
        self::customUtf8Encode($res);
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
            'data' => ['ID_ACTIVIDAD' => $res[0]->ID_ACTIVIDAD_SALIDA, 'PROXIMO_ESTADO' => $res[0]->PROXIMO_ESTADO],
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

                                fases.DESC_FASES
                            FROM CAPACHOS c
                                    INNER JOIN PERMISOS usr_a ON c.ID_USUARIO_ALTA = usr_a.IDUSUARIO
                                    INNER JOIN CAPACHOS_TIPO ct ON c.id_tipo = ct.id_tipo_capacho
                                    INNER JOIN fases_de_produc fases on c.id_fase = fases.id_fase
                                    LEFT JOIN PRODUCTO prod on c.id_pieza_prod = prod.id_producto
                                    LEFT JOIN PIEZA_PLANO psup on c.id_pieza_prod = psup.id_pieza
                                    LEFT JOIN pieza_plano_inferior pinf on c.id_pieza_prod = pinf.id_pieza_inferior

                                        WHERE 1=1  and c.activo = 1
                                            and  c.id_capacho = :id_capacho
                                        ",
            ['id_capacho' => $id_capacho]
        );
    }//obtenerCapachoPorId

    public function ejecutarProcActividad($accion, $id_capacho, $id_usuario, $id_posicion , $id_identificador)
    {
        return DB::connection()->select(
            'execute procedure CAPACHOS_NUEVA_ACTIVIDAD(:ACCION, :ID_CAPACHO, :ID_USUARIO, :ID_POSICION, :ID_IDENTIFICADOR)',
            [
                'ACCION' => $accion,
                'ID_CAPACHO' => $id_capacho,
                'ID_USUARIO' => $id_usuario,
                'ID_POSICION' => $id_posicion,
                'ID_IDENTIFICADOR' => $id_identificador
            ]
        );
    }//ejecutarActividad


    public function buscarPosicionesCapachoLegacy($id_capacho){
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
    }//buscarPosicionesCapachoLegacy

 public function buscarPosicionesCapacho($id_capacho, $id_fase_usuario){
        return DB::connection()->select(
            "select cp.ID_POSICION, cp.ID_CAPACHO, cp.ID_FASE, cp.POSICION, cp.FECHA_ALTA, cp.ID_USUARIO_ALTA, cp.ACTIVO,
                    f.desc_fases as FASE_DESTINO
                from CAPACHOS_POSICIONES  cp
                    inner join fases_de_produc f on cp.id_fase = f.id_fase
                WHERE cp.ACTIVO = 1
                    and cp.ID_CAPACHO = :id_capacho
                    and cp.ID_FASE = :id_fase_usuario

                order by cp.fecha_alta desc ",
            ['id_capacho'=> $id_capacho, 'id_fase_usuario' => $id_fase_usuario]
        );
    }//buscarPosicionesCapacho

    public function obtenerIdentificadoresCapachoConEstado($id_capacho){
        return DB::connection()->select(
            "SELECT CI.ID_IDENTIFICADOR, CI.ID_CAPACHO, CI.NUMERO, CI.ID_USUARIO_ALTA, CI.FECHA_ALTA, CI.ACTIVO,
                acti.fecha_hora_alta AS FECHA_ACTIVIDAD,
                coalesce( acti.ESTADO_CAPACHO, 'INICIAL VACIO') as ESTADO_CAPACHO,
                coalesce(acti.ID_ESTADO_ACTUAL, 10) as ID_ESTADO_ACTUAL
             FROM CAPACHOS_IDENTIFICADOR CI

                LEFT JOIN (
                           SELECT a.ID_CAPACHO, a.ID_POSICION, a.ID_ESTADO_ACTUAL, a.FECHA_HORA_ALTA, e.ESTADO_CAPACHO, a.ID_IDENTIFICADOR, po.POSICION
                              FROM CAPACHOS_ACTIVIDAD a
                                INNER JOIN CAPACHOS_ESTADOS e ON a.ID_ESTADO_ACTUAL = e.id_estado_capacho
                                LEFT JOIN CAPACHOS_POSICIONES po ON a.ID_POSICION = po.ID_POSICION
                             WHERE a.id_actividad = (
                                SELECT max(a2.id_actividad)
                                  FROM CAPACHOS_ACTIVIDAD a2
                                 WHERE a2.ID_IDENTIFICADOR = a.ID_IDENTIFICADOR
                                )
                        ) acti ON CI.ID_IDENTIFICADOR = acti.ID_IDENTIFICADOR
                WHERE CI.ID_CAPACHO = :id_capacho
                ORDER BY NUMERO ASC",
            ['id_capacho' => $id_capacho]
        );
    }

    public function obtenerDescripcionFase($id_fase)
    {
        $resultado = DB::connection()->select(
            "SELECT DESC_FASES FROM fases_de_produc WHERE id_fase = :id_fase",
            ['id_fase' => $id_fase]
        );

        return !empty($resultado) ? $resultado[0]->DESC_FASES : 'Fase No Encontrada';
    }//obtenerDescripcionFase

    public function avanzarCapachoHastaLleno(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_identificador' => 'required|integer',
        ],[
            'id_identificador.required' => 'Identificador es obligatorio.',
            'id_identificador.integer' => 'Identificador debe ser entero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => null,
            ]);
        }

        $id_identificador = $request->input('id_identificador');
        $id_usuario = session('id_usuario');

        try {
            $res = $this->avanzarCapachoHastaLlenoProc($id_identificador, $id_usuario);
            $this->customUtf8Encode($res);

            if(isset($res[0]->ERROR_STR) && $res[0]->ERROR_STR !== ''){
                return response()->json([
                    'success' => false,
                    'message' => $res[0]->ERROR_STR,
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Capacho avanzado hasta LLENO exitosamente',
                'data' => $res,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al avanzar capacho: ' . $e->getMessage(),
                'data' => null,
            ]);
        }
    }//avanzarCapachoHastaLleno

    private function avanzarCapachoHastaLlenoProc($id_identificador, $id_usuario)
    {
        return DB::connection()->select(
            'execute procedure CAPACHOS_AVANZAR_HASTA_LLENO(:ID_IDENTIFICADOR, :ID_USUARIO)',
            [
                'ID_IDENTIFICADOR' => $id_identificador,
                'ID_USUARIO' => $id_usuario
            ]
        );
    }//avanzarCapachoHastaLlenoProc

    /**
     * Obtiene la trazabilidad de un capacho
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerTrazabilidadCapacho(Request $request){
        $id_capacho = $request->input('id_capacho');
        $id_identificador = $request->input('id_identificador');

        if(empty($id_capacho)){
            return response()->json([
                'success' => false,
                'message' => 'Debe indicar ID del Capacho',
                'data' => null,
            ], 422);
        }

        if(empty($id_identificador)){
            return response()->json([
                'success' => false,
                'message' => 'Debe indicar ID del Identificador',
                'data' => null,
            ], 422);
        }

        try {
            // Obtener el último ciclo del identificador
            $ultimoCiclo = self::obtenerUltimoCiclo($id_identificador);
            
            if(!$ultimoCiclo){
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ciclo activo para este identificador',
                    'data' => [],
                ]);
            }

            // Obtener la trazabilidad del último ciclo
            $trazabilidad = self::obtenerTrazabilidadUltimoCiclo(
                $id_capacho,
                $ultimoCiclo->ID_CICLO,
                $ultimoCiclo->NUMERO_ID
            );

            $this->customUtf8Encode($trazabilidad);

            return response()->json([
                'success' => true,
                'message' => 'Trazabilidad obtenida correctamente',
                'data' => $trazabilidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener trazabilidad: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }//obtenerTrazabilidadCapacho

    /**
     * Obtiene el último ciclo (mayor ID_CICLO) de un identificador
     */
    private function obtenerUltimoCiclo($id_identificador)
    {
        $result = DB::connection()->select(
            "SELECT FIRST 1 ca.ID_CICLO, ci.NUMERO AS NUMERO_ID
             FROM CAPACHOS_ACTIVIDAD ca
             INNER JOIN CAPACHOS_IDENTIFICADOR ci ON ca.ID_IDENTIFICADOR = ci.ID_IDENTIFICADOR
             WHERE ca.ACTIVO = 1 
               AND ca.ID_IDENTIFICADOR = :ID_IDENTIFICADOR
             ORDER BY ca.ID_CICLO DESC",
            [
                'ID_IDENTIFICADOR' => $id_identificador
            ]
        );

        return !empty($result) ? $result[0] : null;
    }//obtenerUltimoCiclo

    /**
     * Obtiene todos los estados del recorrido con sus actividades para un ciclo específico
     */
    private function obtenerTrazabilidadUltimoCiclo($id_capacho, $id_ciclo, $numero_id)
    {
        // Obtener todos los estados del recorrido con sus actividades (si existen)
        return DB::connection()->select(
            "SELECT 
                act.ID_ACTIVIDAD,
                act.ID_CAPACHO,
                cp.NRO_CAPACHO,
                crd.ID_ESTADO AS ID_ESTADO_ACTUAL,
                act.FECHA_HORA_ALTA,
                e.ESTADO_CAPACHO,
                usr.USUARIO,
                po.POSICION,
                COALESCE(act.ID_CICLO, ?) AS ID_CICLO,
                COALESCE(ci.NUMERO, ?) AS NUMERO_ID
            FROM CAPACHOS cp
            INNER JOIN CAPACHOS_RECORRIDOS_DETALLE crd ON crd.ID_RECORRIDO = cp.ID_RECORRIDO
            INNER JOIN CAPACHOS_ESTADOS e ON crd.ID_ESTADO = e.ID_ESTADO_CAPACHO
            LEFT JOIN CAPACHOS_ACTIVIDAD act ON act.ID_CICLO = ? 
                                             AND act.ID_ESTADO_ACTUAL = crd.ID_ESTADO
            LEFT JOIN CAPACHOS_IDENTIFICADOR ci ON act.ID_IDENTIFICADOR = ci.ID_IDENTIFICADOR
            LEFT JOIN PERMISOS usr ON act.ID_USUARIO = usr.IDUSUARIO
            LEFT JOIN CAPACHOS_POSICIONES po ON act.ID_POSICION = po.ID_POSICION
            WHERE cp.ID_CAPACHO = ?
              AND crd.ACTIVO = 1
            ORDER BY crd.ORDEN ASC",
            [
                $id_ciclo,      // Para COALESCE ID_CICLO
                $numero_id,     // Para COALESCE NUMERO_ID
                $id_ciclo,      // Para LEFT JOIN
                $id_capacho     // Para WHERE
            ]
        );
    }//obtenerTrazabilidadUltimoCiclo
}
