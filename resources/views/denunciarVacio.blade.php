<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Denunciar Vacio</title>

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
        </style>

        <div class="container" style="max-width:600px">
            <div class="row">
                <div class="col-12 ">
                    <div class="alert alert-{{$colorAccion}}" role="alert">
                        <h5 class="text-center">{{$tituloAccion}} <i class="bi bi-qr-code me-2"></i></h5>
                        <h5 class="text-center">{{ $descripcionFase }}</h5>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12" id="readerContainer"></div>
            </div>

            <div class="row mb-3">
                <div class="col-12" >
                    <p class="text-danger" id="textError"></p>
                    <p class="text-success" id="textSuccess"></p>
                </div>
            </div>

            <input type="hidden" name="id_capacho" id="id_capacho" >
            <input type="hidden" name="id_identificador" id="id_identificador" >
            <input type="hidden" name="id_estado_actual" id="id_estado_actual" >

            <table class="table table-striped">
                <tbody>
                    <tr>
                        <th>Nro Capacho</th>
                        <td><span class="spanDatoCapacho" id="nro_capacho"></span></td>
                    </tr>
                    <tr>
                        <th>Producto/Pieza</th>
                        <td><span class="spanDatoCapacho" id="desc_producto"></span></td>
                    </tr>
                    <tr>
                        <th>Cantidad</th>
                        <td><span class="spanDatoCapacho" id="cantidad"></span></td>
                    </tr>
                    <tr>
                        <th>Identificador</th>
                        <td><span class="spanDatoCapacho" id="identificador_mostrado"></span></td>
                    </tr>
                    <tr>
                        <th>Estado Actual</th>
                        <td><span class="spanDatoCapacho" id="estado_actual_mostrado"></span></td>
                    </tr>
                </tbody>
            </table>

            <table class="table table-striped" id="tablaIdentificadores" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Paso A: Seleccionar Identificador</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <table class="table table-striped" id="tablaPosiciones" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Paso B: Seleccionar Posicion para Denunciar Vacio</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script>
        let qrObj;
        let capachoData = null;
        let identificadorActual = null;

        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
        });

        const resetVista = () => {
            $('#textError').html('').hide();
            $('#textSuccess').html('').hide();
            $('.spanDatoCapacho').html('');

            $('#id_capacho').val('');
            $('#id_identificador').val('');
            $('#id_estado_actual').val('');

            $('#tablaIdentificadores').hide();
            $('#tablaPosiciones').hide();

            capachoData = null;
            identificadorActual = null;
        };

        const procesarResultadoQr = (resultadoqr) => {
            try {
                resetVista();
                qrObj = JSON.parse(resultadoqr);

                if (!qrObj.hasOwnProperty('id_capacho') ||
                    !qrObj.hasOwnProperty('nro_capacho') ||
                    !qrObj.hasOwnProperty('desc_producto') ||
                    !qrObj.hasOwnProperty('cantidad') ||
                    !qrObj.hasOwnProperty('id_tipo') ||
                    !qrObj.hasOwnProperty('id_unidad_medida')) {
                    throw new Error('Datos incompletos en el QR');
                }

                $('#id_capacho').val(qrObj.id_capacho);
                $('#nro_capacho').html(qrObj.nro_capacho);
                $('#desc_producto').html(qrObj.desc_producto);
                $('#cantidad').html((parseFloat(qrObj.cantidad) > 0) ? qrObj.cantidad : 'LLENO');

                if (qrObj.hasOwnProperty('identificador') && qrObj.identificador) {
                    if (!qrObj.identificador.hasOwnProperty('ID_IDENTIFICADOR')) {
                        $('#textError').html('El QR nuevo no contiene ID_IDENTIFICADOR valido').show();
                        return;
                    }

                    if (parseInt(qrObj.identificador.ID_CAPACHO) !== parseInt(qrObj.id_capacho)) {
                        $('#textError').html('El identificador no pertenece al capacho leido').show();
                        return;
                    }

                    buscarCapacho(qrObj.id_capacho, parseInt(qrObj.identificador.ID_IDENTIFICADOR));
                    return;
                }

                buscarCapacho(qrObj.id_capacho, null);
            } catch (error) {
                if (error.name === 'Error') {
                    $('#textError').html(error.message).show();
                } else {
                    $('#textError').html('Formato incorrecto en el QR').show();
                }
            }
        };

        const buscarCapacho = (id_capacho, id_identificador_qr_nuevo) => {
            const formData = new FormData();
            formData.append('id_capacho', id_capacho);
            formData.append('accion', 99);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerCapachoQr') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success){
                        capachoData = response.data.Capacho;
                        $('#nro_capacho').html(capachoData.NRO_CAPACHO);
                        $('#desc_producto').html(capachoData.PROD_DESC);
                        $('#cantidad').html((capachoData.CANTIDAD > 0) ? capachoData.CANTIDAD : 'LLENO');

                        if (id_identificador_qr_nuevo) {
                            const identificador = obtenerIdentificadorPorId(id_identificador_qr_nuevo);
                            if (!identificador) {
                                $('#textError').html('No se encontro el identificador del QR en el capacho').show();
                                return;
                            }

                            seleccionarIdentificador(identificador.ID_IDENTIFICADOR, identificador.NUMERO, identificador.ID_ESTADO_ACTUAL, identificador.ESTADO_CAPACHO);
                            return;
                        }

                        mostrarIdentificadores(capachoData.IDENTIFICADORES || []);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al obtener capacho: ' + error).show();
                }
            });
        };

        const obtenerIdentificadorPorId = (id_identificador) => {
            if (!capachoData || !capachoData.IDENTIFICADORES) {
                return null;
            }

            for (let i = 0; i < capachoData.IDENTIFICADORES.length; i++) {
                if (parseInt(capachoData.IDENTIFICADORES[i].ID_IDENTIFICADOR) === parseInt(id_identificador)) {
                    return capachoData.IDENTIFICADORES[i];
                }
            }

            return null;
        };

        const mostrarIdentificadores = (identificadores) => {
            let html = '';

            if (!identificadores.length) {
                $('#textError').html('Este capacho no tiene identificadores disponibles').show();
                return;
            }

            $.each(identificadores, function(index, ident) {
                let colorBtn = 'btn-warning';
                if (parseInt(ident.ID_ESTADO_ACTUAL) === 10) {
                    colorBtn = 'btn-danger';
                } else if (parseInt(ident.ID_ESTADO_ACTUAL) === 20) {
                    colorBtn = 'btn-success';
                }

                const estadoEscapado = JSON.stringify((ident.ESTADO_CAPACHO || '').toString());

                html += `<tr>
                            <td>
                                <button type="button" class="btn ${colorBtn}"
                                    onclick='seleccionarIdentificador(${ident.ID_IDENTIFICADOR}, ${ident.NUMERO}, ${ident.ID_ESTADO_ACTUAL}, ${estadoEscapado})'>
                                    Identificador #${ident.NUMERO} - ${ident.ESTADO_CAPACHO}
                                </button>
                            </td>
                        </tr>`;
            });

            $('#tablaIdentificadores tbody').html(html);
            $('#tablaIdentificadores').show();
        };

        const seleccionarIdentificador = (id_identificador, numero, id_estado_actual, estado_actual) => {
            identificadorActual = {
                ID_IDENTIFICADOR: id_identificador,
                NUMERO: numero,
                ID_ESTADO_ACTUAL: id_estado_actual,
                ESTADO_CAPACHO: estado_actual
            };

            $('#id_identificador').val(id_identificador);
            $('#id_estado_actual').val(id_estado_actual || '');
            $('#identificador_mostrado').html('#' + numero);
            $('#estado_actual_mostrado').html(estado_actual || ('ID ' + id_estado_actual));
            $('#tablaIdentificadores').hide();

            mostrarPosiciones();
        };

        const mostrarPosiciones = () => {
            if (!capachoData || !capachoData.POSICIONES) {
                $('#textError').html('No hay posiciones disponibles para este capacho').show();
                return;
            }

            const posiciones = capachoData.POSICIONES;
            let html = '';

            if (!posiciones.length) {
                $('#textError').html('No hay posiciones disponibles para este capacho').show();
                return;
            }

            $.each(posiciones, function(index, pos) {
                const posicionEscapada = JSON.stringify((pos.POSICION || '').toString());
                html += `<tr>
                            <td>
                                <button type="button" class="btn btn-success"
                                    onclick='confirmarDenunciarVacio(${pos.ID_POSICION}, ${posicionEscapada})'>
                                    ${pos.POSICION}
                                </button>
                            </td>
                        </tr>`;
            });

            $('#tablaPosiciones tbody').html(html);
            $('#tablaPosiciones').show();

            const topPasoB = $('#tablaPosiciones').offset().top - 20;
            $('html, body').animate({
                scrollTop: topPasoB
            }, 500);
        };

        const confirmarDenunciarVacio = (id_posicion_vacio, posicionLabel) => {
            const numero = identificadorActual ? identificadorActual.NUMERO : $('#id_identificador').val();
            const estadoActual = identificadorActual ? identificadorActual.ESTADO_CAPACHO : $('#estado_actual_mostrado').html();

            const mensaje = `CONFIRMAR DENUNCIAR VACIO del Identificador #${numero} (estado actual: ${estadoActual}) en la posicion ${posicionLabel}.`;

            if (!confirm(mensaje)) {
                return;
            }

            ejecutarDenunciaVacio(id_posicion_vacio);
        };

        const ejecutarDenunciaVacio = (id_posicion_vacio) => {
            const formData = new FormData();
            formData.append('id_identificador', $('#id_identificador').val());
            formData.append('id_estado', 10);
            formData.append('id_posicion_vacio', id_posicion_vacio);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('ejecutarConciliacionCapacho') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success){
                        $('#textSuccess').html('Denuncia de vacio realizada exitosamente').show();
                        alert('EXITO');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al ejecutar denuncia de vacio: ' + error).show();
                }
            });
        };

        const iniciarScanner = () => {
            $('#readerContainer').html('<div id="reader"></div>');

            const scanner = new Html5QrcodeScanner('reader', {
                qrbox: {
                    width: 250,
                    height: 250,
                },
                fps: 20,
                showTorchButtonIfSupported: true
            }, false);

            scanner.render(success, error);

            function success(result) {
                procesarResultadoQr(result);
                $('#html5-qrcode-button-camera-stop').click();
            }

            function error(err) {
                // silent scanner errors
            }

            $('html, body').animate({
                scrollTop: $("#reader__dashboard_section_csr").offset().top
            }, 1500);
        };
        </script>
    </body>
</html>
