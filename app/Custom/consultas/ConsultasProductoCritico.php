<?php
namespace App\Custom\Consultas;

use Illuminate\Support\Facades\DB;
use PDO;

class ConsultasProductoCritico {

    public function verificarLogin($user,$pass){
        $host = "192.168.0.229";
        $bbdd = "c:\hermann\servidor\GUALEGUAYCHUYSIG.GDB";
        $charset = "ISO8859_1";
        ///$user = "EGALARZA";
        //$pass = "larenga";


        $str_conn="firebird:dbname=//192.168.0.229/".$bbdd.";charset=".$charset;

        try{
            $connfr = new PDO($str_conn, $user, $pass);
            $connfr->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            unset($connfr);
            return array('login'=>true);
        }catch(\Exception $e){
                //dd($e);
                return array('login'=>false, 'error' =>"Error de Login: " . $e->getMessage());
        }
    }

    public function unidadesEnMiFase($id_fase_actual,$t_vin, $vin){
        return DB::connection()->select(
            "select pf.id_produccion_fase,PF.id_fase  ,  AYS.nro_vin , FASES.desc_fases , PROX_FASE.desc_fases AS DESC_PROX_FASE,hc.id_hoja_caracteristicas
        ,IIF(np.tipo_nota = 20,Extract(year from ays.ano_as)||'/'||LPAD(AYS.nro_as, 6, '0') ,(LPAD(ays.NRO_SECRETO, 6, '0')||'/'||LPAD(ays.nro_as, 6, '0')) ) as nro_compuesto
        from produccion_de_fase pf
             inner join nota_de_pedido np on pf.id_nota_ped = np.id_nota_pedido
             inner join acoplado_y_semi ays on pf.id_semi_carro = ays.id_acop_semi
             inner join hoja_de_caracteristicas hc on ays.id_acop_semi = hc.id_aco_semi_carro
             left join fases_de_produc fases on pf.id_fase = fases.id_fase
             left join fases_de_produc prox_fase on pf.id_prox_fase = prox_fase.id_fase
             left join modelo_as mod on ays.id_modelo = mod.id_modelo_as
        where
               pf.id_fase= {$id_fase_actual} AND pf.id_prox_fase is null and pf.finalizado = 0
               and (({$t_vin} = 1)or (ays.nro_vin = '{$vin}') )
           order by  pf.fecha_hora_fase_inicio  asc"
        );


    }//unidadesEnMiFase


    public function productoPorCodProdProv($cod_prov){
        return DB::connection()->select(
            "select cp.cod_prod_prove, cp.id_producto, p.desripcion as producto_desc, p.id_tipo_critico, p.cod_interno, pa.nro_subcta, pa.nombre
            from cotiza_producto cp
                inner join producto p on cp.id_producto = p.id_producto
                inner join PADRON pa on (pa.nro_subcta = cp.id_proveedor and pa.cli_prov = 2)
             where cp.cod_prod_prove = '{$cod_prov}'"
        );
    }//productoPorCodProdProv


    public function prodCriticoExisteParaQr($id_proveedor, $id_producto, $nro_serie, $nro_lote){
        return DB::connection()->select(
            "SELECT pc.*
            FROM PRODUCTO_CRITICO pc
                where pc.id_proveedor = {$id_proveedor}
                    and pc.id_producto = {$id_producto}
                    and pc.nro_serie = '{$nro_serie}'
                    and pc.nro_lote = '{$nro_lote}'"
        );
    }//prodCriticoExisteParaQr

    public function asociarProdCriticoQr($ID_HC, $CODIGOPROVEEDOR, $NROSERIE, $NROLOTE, $POS, $ID_USUARIO){



        return DB::connection()->select(
            "execute procedure  ASOCIAR_PROD_CRITICO_QR({$ID_HC}, '{$CODIGOPROVEEDOR}', '{$NROSERIE}', '{$NROLOTE}', {$POS}, {$ID_USUARIO})"
        );

    }//asociarProdCriticoQr

    public function fasesProduccion(){
        return DB::connection()->select("SELECT * FROM fases_de_produc  fp
        where fp.activo =1");
    }//fasesProduccion


    public function buscarFasesUsuario($usuario){
        return DB::connection()->select("
        select f.id_fase ,f.DESC_FASES
          FROM USUARIO_FASES USUARIO_FASES
            INNER JOIN FASES_DE_PRODUC f  ON USUARIO_FASES.ID_FASE = f.ID_FASE
            inner join permisos p on usuario_fases.id_usuario = p.idusuario
         WHERE p.usuario = '{$usuario}' AND f.activo = 1 and f.tipo_fase = 1
         ORDER BY f.NIVEL_DE_FASE ASC "
        );
    }//buscarFasesUsuario

    public function obtenerIdUsuarioPorUsuario($usuario){
        $ResUsuario = DB::connection()->select("SELECT  FIRST 1 IDUSUARIO FROM PERMISOS WHERE USUARIO = '{$usuario}'  ");

        if(!empty($ResUsuario)){
            return $ResUsuario[0]->IDUSUARIO;
        }else{
            return false;
        }
    }//obtenerIdUsuarioPorUsuario
}//ConsultasProductoCritico
