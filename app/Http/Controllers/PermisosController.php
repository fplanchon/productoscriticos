<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisosController extends Controller
{
    public function obtenerPermisosUsuario($id_usuario)
    {
        return DB::connection()->select("SELECT t.id_tareas_menu, t.des_tarea_menu, t.desc_tarea
                                           FROM FAC_USUARIO_TAREAS_MENU ftm
                                                inner join fac_tareas_menu t on ftm.id_tarea = t.id_tareas_menu
                                          WHERE ftm.id_usuario = :id_usuario", ['id_usuario' => $id_usuario]);
    }//obtenerPermisosUsuario

    public function tienePermiso($id_usuario, $nombre_permiso)
    {
        $resultado = DB::connection()->select("SELECT t.id_tareas_menu, t.des_tarea_menu, t.desc_tarea
                                                 FROM FAC_USUARIO_TAREAS_MENU ftm
                                                      inner join fac_tareas_menu t on ftm.id_tarea = t.id_tareas_menu
                                                WHERE ftm.id_usuario = :id_usuario
                                                  AND t.des_tarea_menu = :nombre_permiso",
            [
                'id_usuario' => $id_usuario,
                'nombre_permiso' => $nombre_permiso
            ]);

        return !empty($resultado);
    }//tienePermiso

    /*VERIFICA QUE TENGA PERMISOS EXTRAS AL DE SUPERVISOR*/
    public function tienePermisoCapachoEspecial($id_usuario)
    {
        $resultado = DB::connection()->select("SELECT t.id_tareas_menu
                                                 FROM FAC_USUARIO_TAREAS_MENU ftm
                                                      inner join fac_tareas_menu t on ftm.id_tarea = t.id_tareas_menu
                                                WHERE ftm.id_usuario = :id_usuario
                                                  AND t.des_tarea_menu IN ('SIGWEB_PRODUCCION_CAPACHOS_PINTURA',
                                                   'SIGWEB_PRODUCCION_CAPACHOS_NESTEADO',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_MECANIZADO',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_LOGISTICA',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_CORTE',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_PLEGADO',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_ACCESORIOS',
                                                    'SIGWEB_PRODUCCION_CAPACHOS_DIRECTO_LLENO')",
            [
                'id_usuario' => $id_usuario
            ]);

        return !empty($resultado);
    }//tienePermiso
}
