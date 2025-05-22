<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Leer QR Capachos</title>

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

                <div class="row">
                    <div class="col-12 ">

                        <div class="alert alert-{{$colorAccion}}" role="alert">
                            <h5 class="text-center">Leer QR de Capacho para {{$tituloAccion}} <i class="bi bi-qr-code me-2"></i></h5>
                        </div>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12" id="readerContainer"></div>
                </div>

                    <div class="row mb-3">
                        <div class="col-12" >
                            <p class="text-danger" id="textError"></p>
                        </div>
                    </div>
                <input type="hidden" name="id_capacho" id="id_capacho" >
                <table class="table table-striped">
                    <tbody>
                        <tr >
                            <th >
                                Nro Capacho
                            </th>
                            <td >
                                <span class="spanDatoCapacho" id="nro_capacho"></span>
                            </td>
                        </tr>
                        <tr >
                            <th >
                                Producto/Pieza
                            </th>
                            <td >
                                <span class="spanDatoCapacho" id="desc_producto"></span>
                            </td>
                        </tr>
                        <tr >
                            <th >
                                Cantidad
                            </th>
                            <td >
                                <span class="spanDatoCapacho" id="cantidad"></span>
                            </td>
                        </tr>
                        <!--<tr >
                            <th >
                                Fase
                            </th>
                            <td >
                                <span class="spanDatoCapacho" id="fase_destino"></span>
                            </td>
                        </tr>-->
                    </tbody>
                </table>
                <table class="table table-striped"  id="tablaPosiciones" style="text-align: center">
                    <thead >
                        <tr>
                            <th >
                                Elegir Posicion
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                <!--<form id="formActividadCapacho" onsubmit="ejecutarActividad(this); return false">
                    <input type="hidden" name="id_capacho" id="id_capacho" >
                    <div class="flex justify-center mt-4">
                        @switch($accion)
                            @case(2)
                            <button type="submit" class="btn btn-success w-100"  id="btnAccion" class="btn btn-success hidden">Llenar este capacho</button>
                            @break

                            @case(3)
                            <button type="submit" class="btn btn-success w-100"  id="btnAccion"  text="" class="btn btn-danger hidden">Reclamar este capacho vacío</button>
                            @break

                            @default
                        @endswitch

                    </div>
                </form>-->
            </div>
        <!--</div>-->

        <script>
        let qrObj;
        let Accion = {{ $accion }};
        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
            $('#btnAccion').hide();
        });

        const procesarResultadoQr = (resultadoqr) => {
            try {
                $('#textError').html('').hide();
                $('.spanDatoCapacho').html('');
                $('#id_capacho').val('');
                $('#btnAccion').hide();

                qrObj = JSON.parse(resultadoqr);

                if (qrObj.hasOwnProperty('id_capacho') &&
                    qrObj.hasOwnProperty('nro_capacho') &&
                    qrObj.hasOwnProperty('desc_producto') &&
                    qrObj.hasOwnProperty('cantidad') &&
                    qrObj.hasOwnProperty('id_tipo') &&
                    qrObj.hasOwnProperty('id_unidad_medida')) {
                    //alert('SI! '+qrObj.nro_capacho+' - '+ qrObj.desc_producto+' - '+qrObj.cantidad);
                } else {
                    throw new Error('Datos incompletos')
                }


                //$('#nro_capacho').html(qrObj.nro_capacho);
                $('#desc_producto').html('-');
                $('#cantidad').html('-');
                //$('#fase_destino').html('-');
                $('#btnAccion').hide();

                buscarCapacho(qrObj.id_capacho);


            } catch (error) {
                console.log(error);
                if (error.name == 'Error') {
                    $('#textError').html(error.message).show();
                } else {
                    $('#textError').html('Formato incorrecto en el QR').show();
                }

            } //fin try...catch


            console.log(qrObj);
        } //procesarResultadoQr

        const buscarCapacho =  (id_capacho) => {

            const formData = new FormData();

            formData.append("id_capacho", id_capacho);
            formData.append("accion", Accion);

            formData.append("_token",'{{ csrf_token() }}');

            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerCapachoQr') }}",
                data: formData,
                processData: false,  // tell jQuery not to process the data
                contentType: false,
                beforeSend: function() {

                    //$('#cartelAsociando').show();
                },
                success: function(response) {
                    console.log('response', response);
                    if(response.success){
                        $('#id_capacho').val(response.data.Capacho.ID_CAPACHO);
                        $('#nro_capacho').html(response.data.Capacho.NRO_CAPACHO);
                        $('#desc_producto').html(response.data.Capacho.PROD_DESC);
                        $('#cantidad').html((response.data.Capacho.CANTIDAD > 0) ? response.data.Capacho.CANTIDAD : 'LLENO');
                        //$('#fase_destino').html(response.data.Capacho.DESC_FASES);
                        completarTablaPosiciones(response.data.Capacho.POSICIONES);
                        $('#btnAccion').show();
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html(error);
                    console.error('Error en ajax obtenerCapachoQr:', error);
                },
                complete: function(data) {
                    //$('#cartelAsociando').hide();
                }

            });

        } //buscarCapachos

        const ejecutarActividad = async (id_posicion, txtAccion, posicion) => {
            if (confirm("¿Deseas ejecutar la acción "+ txtAccion +" en posición "+posicion+"?")) {

            } else {
                return;
            }
            const formData = new FormData();

            formData.append("id_capacho", $('#id_capacho').val());
            formData.append("id_posicion", id_posicion);
            formData.append("accion", Accion);

            formData.append("_token",'{{ csrf_token() }}');

            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            $.ajax({
                type: 'POST',
                url: "{{ route('ejecutarActividad') }}",
                data: formData,
                processData: false,  // tell jQuery not to process the data
                contentType: false,
                beforeSend: function() {
                    //$('#cartelAsociando').show();
                },
                success: function(response) {
                    console.log('response', response);
                    if(response.success){
                        alert('EXITO');
                        location.reload();
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html(error).show();
                    console.error('Error en ajax ejecutarActividad:', error);
                },
                complete: function(data) {
                    //$('#cartelAsociando').hide();
                }

            });

        } //buscarCapachos

        const completarTablaPosiciones = (Posiciones) => {
            let html =  '';

            $.each(Posiciones, function(index, pos) {
                if((Accion == 2) && (pos.ID_ESTADO_ACTUAL == 15)){
                    html += `<tr>
                                <td> <button type="button" style="background-color:lightgreen ; border-radius:5px"  onclick="ejecutarActividad(${pos.ID_POSICION},'LLENAR','${pos.POSICION}')">LLENAR ${pos.POSICION}</button></td>
                            </tr>`;
                }else if((Accion == 3) && (pos.ID_ESTADO_ACTUAL == 20)){
                    html += `<tr>
                                <td> <button type="button" style="background-color:lightgreen; border-radius:5px" onclick="ejecutarActividad(${pos.ID_POSICION},'DENUNCIAR','${pos.POSICION}')">DENUNCIAR ${pos.POSICION}</button></td>
                            </tr>`;
                }else{
                    html += `<tr>
                                <td><strong>${pos.POSICION}</strong></td>
                            </tr>`;
                }
            });

             $('#tablaPosiciones tbody').html(html);
        }//completarTablaPosiciones

        const iniciarScanner = () => {
            $('#readerContainer').html('<div  id="reader"></div>');

            const scanner = new Html5QrcodeScanner('reader', {
                // Scanner will be initialized in DOM inside element with id of 'reader'
                qrbox: {
                    width: 250,
                    height: 250,
                }, // Sets dimensions of scanning box (set relative to reader element width)
                fps: 20, // Frames per second to attempt a scan
                showTorchButtonIfSupported: true
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

            $('html, body').animate({
                scrollTop: $("#reader__dashboard_section_csr").offset().top
            }, 1500);

        } //iniciarScanner
        </script>

    </body>
</html>
