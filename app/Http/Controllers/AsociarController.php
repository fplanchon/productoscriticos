<?php

namespace App\Http\Controllers;
use App\Custom\Consultas\ConsultasProductoCritico;
use Illuminate\Http\Request;

class AsociarController extends Controller
{

    public function login(Request $request){
        $request->session()->forget(['id_usuario', 'id_fase']);
        return view('login');
    }//login

    public function formularioAsociar(Request $request){
        //$id_usuario = $request->input('id_usuario');
        //$id_fase = $request->input('id_fase');
        if(!$request->session()->has('id_usuario') OR !$request->session()->has('id_fase')){
            return redirect()->route('login');
        }

        $id_usuario = session('id_usuario');
        $id_fase    = session('id_fase');
        $id_hc     = session('id_hc');

        $ConPC = new ConsultasProductoCritico();
        $Unidades = $ConPC->unidadesEnMiFase($id_fase,1,'');
        //$Producto = $ConPC->productoPorCodProdProv('AT650CIP');
        //$Unidades = json_encode($Unidades,JSON_UNESCAPED_SLASHES);

        return view('asociarProductosCriticos',compact('id_usuario','Unidades','id_hc'));
    }//formularioAsociar

    public function asociarProductoCritico(Request $request){
        $ConPC = new ConsultasProductoCritico();

        $ID_HC = $request->input('ID_HC');
        $CODIGOPROVEEDOR = $request->input('CODIGOPROVEEDOR');
        $NROSERIE = $request->input('NROSERIE');
        $NROLOTE = $request->input('NROLOTE');
        $POS =intval($request->input('POS'));
        $ID_USUARIO = intval($request->input('ID_USUARIO'));

        $Res = $ConPC->asociarProdCriticoQr($ID_HC, $CODIGOPROVEEDOR, $NROSERIE, $NROLOTE, $POS, $ID_USUARIO);

        $respuesta = $Res;

        return response()->json($respuesta);
    }//asociarProductoCritico


    public function buscarFasesUsuario(Request $request){
        $ConPC = new ConsultasProductoCritico();
        $usuario = strtoupper($request->input('usuario'));
        $Fases = $ConPC->buscarFasesUsuario($usuario);
        $this->customUtf8Encode($Fases);

        return response()->json($Fases);
    }//buscarFasesUsuario

    public function peticionlogin(Request $request){
        $ConPC = new ConsultasProductoCritico();
        $usuario = strtoupper($request->input('usuario'));
        $password = $request->input('password');
        $id_fase = $request->input('id_fase');
        $ResLogin = $ConPC->verificarLogin($usuario,$password);
        if($ResLogin['login']==true){
            $id_usuario = $ConPC->obtenerIdUsuarioPorUsuario($usuario);
            session(['id_usuario' => $id_usuario]);
            session(['id_fase' => $id_fase]);
            return redirect()->route('formularioAsociar');
        }else{
            return redirect()->route('login');
        }

    }//peticionlogin

    public function loginauto($id_usuario, $id_fase, $id_hc = null, $accion=null){
        session(['id_usuario' => $id_usuario]);
        session(['id_fase' => $id_fase]);
        session(['id_hc' => $id_hc]);
        session(['accion' => $accion]);

        if($id_hc == 'CAPACHOS'){
            return redirect()->route('leerCapacho');
        }

        return redirect()->route('formularioAsociar');
    }//loginauto

    /****** */
    private function customUtf8Encode(&$Data){
        array_walk_recursive($Data, function(&$value, $key){
            foreach($value as $k => $val){
                $value->$k = utf8_encode($val);
            }
        });

    }//customUtf8Encode
}
