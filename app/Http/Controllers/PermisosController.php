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
}
