<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Productos Criticos Blade</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link href="{{asset('bootstrap5/css/bootstrap.min.css')}}" rel="stylesheet" >
        <script src="{{asset('bootstrap5/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('jquery/jquery-3.6.0.min.js')}}"></script>
        <script src="{{asset('html5-qrcode/html5-qrcode.min.js')}}"></script>

    </head>
    <body class="antialiased">
        <style>

            #reader {
                width: 100%;

            }
            #result {
                text-align: center;
                font-size: 1.5rem;
            }
        </style>


        <!--<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">-->
            <div class="container" style="max-width:600px">
                <input type="hidden" id="id_usuario" value="{{$id_usuario}}" >
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-center">Asociar Productos Críticos</h5>
                    </div>
                </div>
                <div class="row formularioAsociar">
                    <div class="col-12">
                        
                        <label for="id_hc" class="form-label">Seleccione una unidad:</label>
                        <select class="form-select" id="id_hc">
                            @forelse ($Unidades as $Unidad)
                                <option value="{{$Unidad->ID_HOJA_CARACTERISTICAS}}" >{{ $Unidad->NRO_VIN }}</option>
                            @empty
                                <option value="0">Sin unidades en tu fase</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-12">
                        <br>
                        <label for="posicion" class="form-label">Posición:</label>
                        <small>- Solo si se requiere</small>
                        <input type="number" class="form-control" id="posicion" value="">
                        <br>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12" id="readerContainer"></div>
                </div>
                <form>
                    <div class="row mb-3">
                        <label for="codigoProducto" class="col-3 col-form-label">Código producto:</label>
                        <div class="col-9">
                            <input type="text" class="form-control" disabled="disabled" id="codigoProducto">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="nroSerie" class="col-3 col-form-label">Nro Serie:</label>
                        <div class="col-9">
                            <input type="text" class="form-control" disabled="disabled" id="nroSerie">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="nroLote" class="col-3 col-form-label">Nro Lote:</label>
                        <div class="col-9">
                            <input type="text" class="form-control" disabled="disabled" id="nroLote">
                        </div>
                    </div>
                    <div class="row mb-3">                    
                        <div class="col-12" >
                            <p class="text-danger" id="textError"></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <!--<h6>{{$id_usuario}}</h6>-->
                    </div>
                </div>
                <div class="row " id="cartelAsociando" style="display: none">
                    <div class="col-12">
                        <p class="text-success text-center">...Asociando...</p>
                    </div>
                </div>

                <div class="fixed-bottom">
                    <button type="button" class="btn btn-success w-100" onclick="asociarProductoCritico()">Asociar</button>
                </div>
            </div>
        <!--</div>-->

        <script>
            let qrObj;

            const asociarProductoCritico = () => {
                try{
                    if(!qrObj.hasOwnProperty('datos')){
                        throw new Error('No se escaneado un QR valido');
                    }

                    const formData = new FormData();

                    formData.append('ID_HC',$('#id_hc').val());
                    formData.append("CODIGOPROVEEDOR", qrObj.datos.CODIGOPROVEEDOR);
                    formData.append("NROSERIE", qrObj.datos.NROSERIE);
                    formData.append("NROLOTE", qrObj.datos.NROLOTE);
                    formData.append("POS", $('#posicion').val());
                    formData.append("ID_USUARIO",$('#id_usuario').val() );

                    formData.append("_token",'{{ csrf_token() }}');
                    console.log(formData);
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('asociarproductocritico') }}",
                        data: formData,
                        processData: false,  // tell jQuery not to process the data
                        contentType: false,
                        beforeSend: function() {
                            $('#cartelAsociando').show();
                        },
                        success: function(response) {
                            console.log('response', response);
                            if(response[0]['T_SALIDA'] == 1){
                                alert(response[0]['EXITO_S']);
                                location.reload();
                            }else{
                                $('#textError').html(response[0]['ERR_S']);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#textError').html(error);
                            console.error('Error en ajax asociarProductoCritico:', error);
                        },
                        complete: function(data) {
                            $('#cartelAsociando').hide();
                        }

                     });
                } catch (error) {
                    console.log(error);
                    //$('#textError').html(error.message);
                    if(error.name == 'Error'){
                        $('#textError').html(error.message);
                    }else{
                        $('#textError').html('Error al asociar, verifique datos');
                    }
                }


            }

            const procesarResultadoQr = (resultadoqr) => {

                try {
                    $('#textError').html('');
                    qrObj = JSON.parse(resultadoqr);
                    if(qrObj.hasOwnProperty('datos')){
                        if(qrObj.datos.hasOwnProperty('CODIGOPROVEEDOR') && qrObj.datos.hasOwnProperty('NROSERIE') && qrObj.datos.hasOwnProperty('NROLOTE')){
                            //alert('SI! '+qrObj.datos.CODIGOPROVEEDOR+' - '+ qrObj.datos.NROSERIE+' - '+qrObj.datos.NROLOTE);
                            if(qrObj.datos.CODIGOPROVEEDOR == ''){
                                throw new Error('No se indico Codigo del producto')
                            }

                            if(qrObj.datos.NROSERIE == ''){
                                throw new Error('No se indico NRO SERIENo se indico NRO SERIE')
                            }

                            if(qrObj.datos.NROLOTE == ''){
                                //throw new Error('No se indico NRO LOTE')
                            }


                        }else{
                            throw new Error('Datos incompletos')
                        }

                        $('#codigoProducto').val(qrObj.datos.CODIGOPROVEEDOR);
                        $('#nroSerie').val(qrObj.datos.NROSERIE);
                        $('#nroLote').val(qrObj.datos.NROLOTE);
                        //alert('SI! '+qrObj.datos.CODIGOPROVEEDOR+' - '+ qrObj.datos.NROSERIE+' - '+qrObj.datos.NROLOTE);
                    }
                } catch (error) {
                    if(error.name == 'Error'){
                        $('#textError').html(error.message);
                    }else{
                        $('#textError').html('Formato incorrecto en el QR');
                    }

                }//fin try...catch


                console.log(qrObj);
            }//procesarResultadoQr


            const iniciarScanner = ()=>{
                //$('.formularioAsociar').fadeOut();
                $('#readerContainer').html('<div  id="reader"></div>')
                const scanner = new Html5QrcodeScanner('reader', {
                        // Scanner will be initialized in DOM inside element with id of 'reader'
                        qrbox: {
                            width: 250,
                            height: 250,
                        },  // Sets dimensions of scanning box (set relative to reader element width)
                        fps: 20, // Frames per second to attempt a scan
                    }, false);


                    scanner.render(success, error);
                    // Starts scanner

                    function success(result) {
                        procesarResultadoQr(result);
                        $('#html5-qrcode-button-camera-stop').click();
                        //$('#result').val(result);
                        /*document.getElementById('result').innerHTML = `
                        <h2>Success!</h2>
                        <p><a href="${result}">${result}</a></p>
                        `;*/
                        // Prints result as a link inside result element

                        //scanner.clear();
                        // Clears scanning instance

                        //document.getElementById('reader').remove();
                        // Removes reader element from DOM since no longer needed

                    }

                    function error(err) {
                       // console.error(err);
                        // Prints any errors to the console
                    }
                }//iniciarScanner


                iniciarScanner();
        </script>

    </body>
</html>
