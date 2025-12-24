<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Scanear Capacho</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link href="{{asset('bootstrap5/css/bootstrap.min.css')}}" rel="stylesheet" >
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
            <input type="hidden" name="id_identificador_seleccionado" id="id_identificador_seleccionado" >

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
                </tbody>
            </table>

            <table class="table table-striped" id="tablaIdentificadores" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Seleccionar Identificador</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <table class="table table-striped" id="tablaPosiciones" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Elegir Posición</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <table class="table table-striped" id="tablaOpcionesAvance" style="text-align: center; display: none;">
                <thead>
                    <tr>
                        <th>Elegir Opción de Avance</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

        </div>

        <script>
        let qrObj;
        let capachoData = null;
        let identificadorActual = null;
        let proximoEstado = null;
        const tienePermisoDirectoLleno = {{ $tienePermisoDirectoLleno ? 'true' : 'false' }};

        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
        });

        const procesarResultadoQr = (resultadoqr) => {
            try {
                $('#textError').html('').hide();
                $('#textSuccess').html('').hide();
                $('.spanDatoCapacho').html('');
                $('#id_capacho').val('');
                $('#tablaIdentificadores').hide();
                $('#tablaPosiciones').hide();
                $('#tablaOpcionesAvance').hide();

                qrObj = JSON.parse(resultadoqr);

                // Validar campos básicos del QR
                if (!qrObj.hasOwnProperty('id_capacho') ||
                    !qrObj.hasOwnProperty('nro_capacho') ||
                    !qrObj.hasOwnProperty('desc_producto') ||
                    !qrObj.hasOwnProperty('cantidad') ||
                    !qrObj.hasOwnProperty('id_tipo') ||
                    !qrObj.hasOwnProperty('id_unidad_medida')) {
                    throw new Error('Datos incompletos en el QR')
                }

                // Determinar si es flujo nuevo (con identificador) o legacy (sin identificador)
                if (qrObj.hasOwnProperty('identificador') && qrObj.identificador) {
                    // FLUJO NUEVO: Ya tenemos el identificador en el QR
                    procesarFlujoNuevo(qrObj);
                } else {
                    // FLUJO LEGACY: Necesitamos listar identificadores
                    procesarFlujoLegacy(qrObj);
                }

            } catch (error) {
                console.log(error);
                if (error.name == 'Error') {
                    $('#textError').html(error.message).show();
                } else {
                    $('#textError').html('Formato incorrecto en el QR').show();
                }
            }

            console.log(qrObj);
        } //procesarResultadoQr

        const procesarFlujoNuevo = (qrObj) => {
            // Validar que el identificador tenga ID_IDENTIFICADOR
            if (!qrObj.identificador.hasOwnProperty('ID_IDENTIFICADOR')) {
                $('#textError').html('El identificador en el QR no tiene ID_IDENTIFICADOR').show();
                return;
            }

            // Validar que id_capacho del identificador coincida con el del capacho
            if (parseInt(qrObj.identificador.ID_CAPACHO) !== parseInt(qrObj.id_capacho)) {
                $('#textError').html('El ID_CAPACHO del identificador no coincide con el del capacho').show();
                return;
            }

            $('#id_capacho').val(qrObj.id_capacho);
            $('#nro_capacho').html(qrObj.nro_capacho);
            $('#desc_producto').html(qrObj.desc_producto);
            $('#cantidad').html((parseFloat(qrObj.cantidad) > 0) ? qrObj.cantidad : 'LLENO');

            identificadorActual = qrObj.identificador;
            // Asegurar que tenga NUMERO para los mensajes
            if (!identificadorActual.NUMERO) {
                identificadorActual.NUMERO = identificadorActual.numero || identificadorActual.ID_NUMERO || identificadorActual.ID_IDENTIFICADOR;
            }
            $('#id_identificador_seleccionado').val(identificadorActual.ID_IDENTIFICADOR);

            // Obtener próximo estado con acción 99 - FLUJO NUEVO
            obtenerProximoEstadoFlujoNuevo(qrObj.id_capacho, identificadorActual.ID_IDENTIFICADOR);
        } //procesarFlujoNuevo

        const procesarFlujoLegacy = (qrObj) => {
            $('#id_capacho').val(qrObj.id_capacho);
            buscarCapacho(qrObj.id_capacho);
        } //procesarFlujoLegacy

        const buscarCapacho = (id_capacho) => {
            const formData = new FormData();
            formData.append("id_capacho", id_capacho);
            formData.append("accion", 99);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerCapachoQr') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response obtenerCapachoQr', response);
                    if(response.success){
                        capachoData = response.data.Capacho;
                        $('#nro_capacho').html(capachoData.NRO_CAPACHO);
                        $('#desc_producto').html(capachoData.PROD_DESC);
                        $('#cantidad').html((capachoData.CANTIDAD > 0) ? capachoData.CANTIDAD : 'LLENO');

                        // Mostrar lista de identificadores para que el usuario elija
                        mostrarIdentificadores(capachoData.IDENTIFICADORES);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html(error).show();
                    console.error('Error en ajax obtenerCapachoQr:', error);
                }
            });
        } //buscarCapacho

        const mostrarIdentificadores = (identificadores) => {
            let html = '';

            if (!identificadores || identificadores.length === 0) {
                $('#textError').html('Este capacho no tiene identificadores disponibles').show();
                return;
            }

            $.each(identificadores, function(index, ident) {
                let colorBtn = 'btn-warning'; // Por defecto amarillo
                if (parseInt(ident.ID_ESTADO_ACTUAL) === 10) {
                    colorBtn = 'btn-danger'; // Rojo si VACIO
                } else if (parseInt(ident.ID_ESTADO_ACTUAL) === 20) {
                    colorBtn = 'btn-success'; // Verde si LLENO
                }

                // Verificar si debe mostrar botón de avanzar directo a lleno
                let botonDirectoLleno = '';
                const noEstaLleno = parseInt(ident.ID_ESTADO_ACTUAL) !== 20;

                if (tienePermisoDirectoLleno && noEstaLleno) {
                    botonDirectoLleno = `
                        <button type="button" class="btn btn-primary ms-2"
                            onclick="avanzarDirectoHastaLleno(${ident.ID_IDENTIFICADOR}, ${ident.NUMERO})"
                            title="Avanzar directo a LLENO">
                            <i class="fas fa-box"></i>
                        </button>
                    `;
                }

                html += `<tr>
                            <td>
                                <button type="button" class="btn ${colorBtn}"
                                    onclick="seleccionarIdentificador(${ident.ID_IDENTIFICADOR}, ${ident.NUMERO}, '${ident.ESTADO_CAPACHO}')">
                                    Identificador #${ident.NUMERO} - ${ident.ESTADO_CAPACHO}
                                </button>
                                ${botonDirectoLleno}
                            </td>
                        </tr>`;
            });

            $('#tablaIdentificadores tbody').html(html);
            $('#tablaIdentificadores').show();
        } //mostrarIdentificadores

        const seleccionarIdentificador = (id_identificador, numero, estado) => {
            identificadorActual = {
                ID_IDENTIFICADOR: id_identificador,
                NUMERO: numero,
                ESTADO_CAPACHO: estado
            };
            $('#id_identificador_seleccionado').val(id_identificador);

            // Ocultar tabla de identificadores
            $('#tablaIdentificadores').hide();

            // Obtener próximo estado con acción 99 - FLUJO LEGACY
            obtenerProximoEstadoLegacy($('#id_capacho').val(), id_identificador);
        } //seleccionarIdentificador

        const obtenerProximoEstadoLegacy = (id_capacho, id_identificador) => {
            const formData = new FormData();
            formData.append("accion", 99);
            formData.append("id_capacho", id_capacho);
            formData.append("id_posicion", 0);
            formData.append("id_identificador", id_identificador);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('ejecutarActividad') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response obtenerProximoEstadoLegacy', response);
                    if(response.success){
                        proximoEstado = response.data.PROXIMO_ESTADO || 'DESCONOCIDO';

                        if (proximoEstado.toUpperCase() === 'VACIO') {
                            // FLUJO LEGACY: Mostrar posiciones SOLAMENTE (ya eligió si quería lleno o no)
                            mostrarPosiciones(id_capacho);
                        } else {
                            // FLUJO LEGACY: Confirmar y ejecutar directamente
                            if (confirm(`¿Desea avanzar el Número ${identificadorActual.NUMERO} al estado ${proximoEstado}?`)) {
                                ejecutarAvance(id_capacho, 0, id_identificador);
                            }
                        }
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al obtener próximo estado: ' + error).show();
                    console.error('Error en ajax obtenerProximoEstadoLegacy:', error);
                }
            });
        } //obtenerProximoEstadoLegacy

        const obtenerProximoEstadoFlujoNuevo = (id_capacho, id_identificador) => {
            const formData = new FormData();
            formData.append("accion", 99);
            formData.append("id_capacho", id_capacho);
            formData.append("id_posicion", 0);
            formData.append("id_identificador", id_identificador);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('ejecutarActividad') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response obtenerProximoEstadoFlujoNuevo', response);
                    if(response.success){
                        proximoEstado = response.data.PROXIMO_ESTADO || 'DESCONOCIDO';

                        if (proximoEstado.toUpperCase() === 'VACIO') {
                            // FLUJO NUEVO: Mostrar posiciones para que elija
                            mostrarPosiciones(id_capacho);
                        } else {
                            // FLUJO NUEVO: Verificar si tiene permiso para mostrar opciones
                            const noEstaLleno = proximoEstado.toUpperCase() !== 'LLENO';

                            if (tienePermisoDirectoLleno && noEstaLleno) {
                                // Mostrar opciones: proceso común y lleno directo
                                mostrarOpcionesAvance(id_capacho, id_identificador);
                            } else {
                                // Confirmar y ejecutar directamente
                                if (confirm(`¿Desea avanzar el Número ${identificadorActual.NUMERO} al estado ${proximoEstado}?`)) {
                                    ejecutarAvance(id_capacho, 0, id_identificador);
                                }
                            }
                        }
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al obtener próximo estado: ' + error).show();
                    console.error('Error en ajax obtenerProximoEstadoFlujoNuevo:', error);
                }
            });
        } //obtenerProximoEstadoFlujoNuevoFlujoNuevo

        const mostrarOpcionesAvance = (id_capacho, id_identificador) => {
            const numeroIdent = identificadorActual.NUMERO || id_identificador;

            let html = `<tr>
                            <td>
                                <button type="button" class="btn btn-success"
                                    onclick="confirmarProcesoComun(${id_capacho}, ${id_identificador})"
                                    title="Avanzar al siguiente estado: ${proximoEstado}">
                                    <i class="fas fa-arrow-right"></i> Proceso Común (${proximoEstado})
                                </button>
                                <button type="button" class="btn btn-primary ms-2"
                                    onclick="avanzarDirectoHastaLleno(${id_identificador}, ${numeroIdent})"
                                    title="Avanzar directo a LLENO">
                                    <i class="fas fa-box"></i> Lleno Directo
                                </button>
                            </td>
                        </tr>`;

            $('#tablaOpcionesAvance tbody').html(html);
            $('#tablaOpcionesAvance').show();
        } //mostrarOpcionesAvance

        const confirmarProcesoComun = (id_capacho, id_identificador) => {
            if (confirm(`¿Desea avanzar el Número ${identificadorActual.NUMERO} al estado ${proximoEstado}?`)) {
                ejecutarAvance(id_capacho, 0, id_identificador);
            }
        } //confirmarProcesoComun

        const mostrarPosiciones = (id_capacho) => {
            const formData = new FormData();
            formData.append("id_capacho", id_capacho);
            formData.append("accion", 99);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerCapachoQr') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response mostrarPosiciones', response);
                    if(response.success && response.data.Capacho.POSICIONES){
                        let html = '';
                        const posiciones = response.data.Capacho.POSICIONES;

                        if (posiciones.length === 0) {
                            $('#textError').html('Este capacho no tiene posiciones disponibles para la fase actual').show();
                            return;
                        }

                        $.each(posiciones, function(index, pos) {
                            html += `<tr>
                                        <td>
                                            <button type="button" class="btn btn-success"
                                                onclick="confirmarYEjecutarConPosicion(${pos.ID_POSICION}, '${pos.POSICION}')">
                                                ${pos.POSICION} - ${pos.FASE_DESTINO}
                                            </button>
                                        </td>
                                    </tr>`;
                        });

                        $('#tablaPosiciones tbody').html(html);
                        $('#tablaPosiciones').show();
                    }else{
                        $('#textError').html('Error al obtener posiciones del capacho').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al cargar posiciones: ' + error).show();
                    console.error('Error en ajax mostrarPosiciones:', error);
                }
            });
        } //mostrarPosiciones

        const confirmarYEjecutarConPosicion = (id_posicion, posicion) => {
            if (confirm(`¿Confirma avanzar Número ${identificadorActual.NUMERO} al estado ${proximoEstado} desde la posicion ${posicion}?`)) {
                ejecutarAvance($('#id_capacho').val(), id_posicion, $('#id_identificador_seleccionado').val());
            }
        } //confirmarYEjecutarConPosicion

        const ejecutarAvance = (id_capacho, id_posicion, id_identificador) => {
            const formData = new FormData();
            formData.append("accion", 2);
            formData.append("id_capacho", id_capacho);
            formData.append("id_posicion", id_posicion);
            formData.append("id_identificador", id_identificador);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('ejecutarActividad') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response ejecutarAvance', response);
                    if(response.success){
                        $('#textSuccess').html('¡Actividad ejecutada exitosamente!').show();
                        alert('EXITO');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al ejecutar actividad: ' + error).show();
                    console.error('Error en ajax ejecutarAvance:', error);
                }
            });
        } //ejecutarAvance

        const avanzarDirectoHastaLleno = (id_identificador, numero) => {
            if (!confirm(`¿Desea avanzar el Identificador #${numero} directamente hasta el estado LLENO?`)) {
                return;
            }

            const formData = new FormData();
            formData.append("id_identificador", id_identificador);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('avanzarCapachoHastaLleno') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response avanzarDirectoHastaLleno', response);
                    if(response.success){
                        $('#textSuccess').html('¡Capacho avanzado hasta LLENO exitosamente!').show();
                        alert('EXITO: Capacho avanzado hasta LLENO');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error al avanzar capacho: ' + error).show();
                    console.error('Error en ajax avanzarDirectoHastaLleno:', error);
                }
            });
        } //avanzarDirectoHastaLleno

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
                // console.error(err);
            }

            $('html, body').animate({
                scrollTop: $("#reader__dashboard_section_csr").offset().top
            }, 1500);
        } //iniciarScanner
        </script>

    </body>
</html>
