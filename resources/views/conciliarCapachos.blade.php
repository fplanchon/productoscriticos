<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Conciliar Capacho</title>

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
            <input type="hidden" name="id_estado" id="id_estado" >

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
                        <th>Estado Seleccionado</th>
                        <td><span class="spanDatoCapacho" id="estado_mostrado"></span></td>
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

            <table class="table table-striped" id="tablaEstados" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Paso B: Seleccionar Estado</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <table class="table table-striped" id="tablaPosiciones" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Paso C: Seleccionar Posición para VACIO</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script>
        let qrObj;
        let capachoData = null;
        let identificadorActual = null;
        let estadoSeleccionado = null;

        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
        });

        const resetVista = () => {
            $('#textError').html('').hide();
            $('#textSuccess').html('').hide();
            $('.spanDatoCapacho').html('');

            $('#id_capacho').val('');
            $('#id_identificador').val('');
            $('#id_estado').val('');

            $('#tablaIdentificadores').hide();
            $('#tablaEstados').hide();
            $('#tablaPosiciones').hide();

            capachoData = null;
            identificadorActual = null;
            estadoSeleccionado = null;
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
                    procesarFlujoNuevo(qrObj);
                } else {
                    procesarFlujoLegacy(qrObj);
                }
            } catch (error) {
                if (error.name === 'Error') {
                    $('#textError').html(error.message).show();
                } else {
                    $('#textError').html('Formato incorrecto en el QR').show();
                }
            }
        };

        const procesarFlujoNuevo = (qrObj) => {
            if (!qrObj.identificador.hasOwnProperty('ID_IDENTIFICADOR')) {
                $('#textError').html('El QR nuevo no contiene ID_IDENTIFICADOR válido').show();
                return;
            }

            if (parseInt(qrObj.identificador.ID_CAPACHO) !== parseInt(qrObj.id_capacho)) {
                $('#textError').html('El identificador no pertenece al capacho leído').show();
                return;
            }

            identificadorActual = {
                ID_IDENTIFICADOR: qrObj.identificador.ID_IDENTIFICADOR,
                NUMERO: qrObj.identificador.NUMERO || qrObj.identificador.numero || qrObj.identificador.ID_IDENTIFICADOR
            };

            $('#id_identificador').val(identificadorActual.ID_IDENTIFICADOR);
            $('#identificador_mostrado').html('#' + identificadorActual.NUMERO);

            cargarEstadosConciliacion($('#id_capacho').val(), identificadorActual.ID_IDENTIFICADOR);
        };

        const procesarFlujoLegacy = (qrObj) => {
            buscarCapacho(qrObj.id_capacho);
        };

        const buscarCapacho = (id_capacho) => {
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

                html += `<tr>
                            <td>
                                <button type="button" class="btn ${colorBtn}"
                                    onclick="seleccionarIdentificador(${ident.ID_IDENTIFICADOR}, ${ident.NUMERO})">
                                    Identificador #${ident.NUMERO} - ${ident.ESTADO_CAPACHO}
                                </button>
                            </td>
                        </tr>`;
            });

            $('#tablaIdentificadores tbody').html(html);
            $('#tablaIdentificadores').show();
        };

        const seleccionarIdentificador = (id_identificador, numero) => {
            identificadorActual = {
                ID_IDENTIFICADOR: id_identificador,
                NUMERO: numero
            };

            $('#id_identificador').val(id_identificador);
            $('#identificador_mostrado').html('#' + numero);
            $('#tablaIdentificadores').hide();

            cargarEstadosConciliacion($('#id_capacho').val(), id_identificador);
        };

        const cargarEstadosConciliacion = (id_capacho, id_identificador) => {
            const formData = new FormData();
            formData.append('id_capacho', id_capacho);
            formData.append('id_identificador', id_identificador);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerEstadosConciliacion') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success){
                        mostrarEstados(response.data.ESTADOS || []);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al obtener estados de conciliación: ' + error).show();
                }
            });
        };

        const mostrarEstados = (estados) => {
            let html = '';

            if (!estados.length) {
                $('#textError').html('No hay estados disponibles para conciliar este capacho').show();
                return;
            }

            $.each(estados, function(index, estado) {
                const esActual = parseInt(estado.ES_ESTADO_ACTUAL) === 1;
                const clase = esActual ? 'btn-primary' : 'btn-secondary';
                const etiquetaActual = esActual ? ' (ACTUAL)' : '';

                html += `<tr>
                            <td>
                                <button type="button" class="btn ${clase}"
                                    onclick="seleccionarEstado(${estado.ID_ESTADO}, '${estado.ESTADO_CAPACHO}')">
                                    ${estado.ESTADO_CAPACHO}${etiquetaActual}
                                </button>
                            </td>
                        </tr>`;
            });

            $('#tablaEstados tbody').html(html);
            $('#tablaEstados').show();
        };

        const seleccionarEstado = (id_estado, estado_capacho) => {
            estadoSeleccionado = {
                ID_ESTADO: id_estado,
                ESTADO_CAPACHO: estado_capacho
            };

            $('#id_estado').val(id_estado);
            $('#estado_mostrado').html(estado_capacho);
            $('#tablaPosiciones').hide();

            if (parseInt(id_estado) === 10) {
                mostrarPosiciones($('#id_capacho').val());
                return;
            }

            confirmarYEjecutarConciliacion(0, 'SIN_POSICION');
        };

        const mostrarPosiciones = (id_capacho) => {
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
                    if(response.success && response.data.Capacho.POSICIONES){
                        let html = '';
                        const posiciones = response.data.Capacho.POSICIONES;

                        if (!posiciones.length) {
                            $('#textError').html('No hay posiciones disponibles para este capacho').show();
                            return;
                        }

                        $.each(posiciones, function(index, pos) {
                            html += `<tr>
                                        <td>
                                            <button type="button" class="btn btn-success"
                                                onclick="confirmarYEjecutarConciliacion(${pos.ID_POSICION}, '${pos.POSICION}')">
                                                ${pos.POSICION} - ${pos.FASE_DESTINO}
                                            </button>
                                        </td>
                                    </tr>`;
                        });

                        $('#tablaPosiciones tbody').html(html);
                        $('#tablaPosiciones').show();

                        const topPasoC = $('#tablaPosiciones').offset().top - 20;
                        $('html, body').animate({
                            scrollTop: topPasoC
                        }, 500);
                    }else{
                        $('#textError').html('No se pudieron cargar posiciones').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al obtener posiciones: ' + error).show();
                }
            });
        };

        const confirmarYEjecutarConciliacion = (id_posicion_vacio, posicionLabel) => {
            const numero = identificadorActual ? identificadorActual.NUMERO : $('#id_identificador').val();
            const estado = estadoSeleccionado ? estadoSeleccionado.ESTADO_CAPACHO : $('#id_estado').val();

            let mensaje = `¿Confirma conciliar el Identificador #${numero} al estado ${estado}?`;
            if (parseInt($('#id_estado').val()) === 10) {
                mensaje = `¿Confirma conciliar el Identificador #${numero} al estado ${estado} en la posición ${posicionLabel}?`;
            }

            if (!confirm(mensaje)) {
                return;
            }

            ejecutarConciliacion(id_posicion_vacio);
        };

        const ejecutarConciliacion = (id_posicion_vacio) => {
            const formData = new FormData();
            formData.append('id_identificador', $('#id_identificador').val());
            formData.append('id_estado', $('#id_estado').val());
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
                        $('#textSuccess').html('¡Conciliación realizada exitosamente!').show();
                        alert('EXITO');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al ejecutar conciliación: ' + error).show();
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
