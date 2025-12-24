<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $tituloAccion }}</title>

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
            .timeline-item {
                position: relative;
                padding-left: 30px;
                padding-bottom: 20px;
            }
            .timeline-item::before {
                content: '';
                position: absolute;
                left: 8px;
                top: 25px;
                bottom: -5px;
                width: 2px;
                background-color: #dee2e6;
            }
            .timeline-item:last-child::before {
                display: none;
            }
            .timeline-dot {
                position: absolute;
                left: 0;
                top: 8px;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                border: 3px solid #fff;
                box-shadow: 0 0 0 2px;
            }
            .timeline-dot.bg-danger {
                box-shadow: 0 0 0 2px #dc3545;
            }
            .timeline-dot.bg-success {
                box-shadow: 0 0 0 2px #198754;
            }
            .timeline-dot.bg-warning {
                box-shadow: 0 0 0 2px #ffc107;
            }
            .timeline-dot.bg-secondary {
                box-shadow: 0 0 0 2px #6c757d;
            }
            .timeline-content {
                background-color: #f8f9fa;
                border-left: 3px solid;
                padding: 12px;
                border-radius: 0 8px 8px 0;
                margin-left: 10px;
            }
            .timeline-content.border-danger {
                border-left-color: #dc3545;
                background-color: #f8d7da;
            }
            .timeline-content.border-success {
                border-left-color: #198754;
                background-color: #d1e7dd;
            }
            .timeline-content.border-warning {
                border-left-color: #ffc107;
                background-color: #fff3cd;
            }
            .timeline-content.border-secondary {
                border-left-color: #6c757d;
                background-color: #e2e3e5;
            }
        </style>

        <div class="container" style="max-width:600px">

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-{{$colorAccion}}" role="alert">
                        <h5 class="text-center">{{$tituloAccion}} <i class="fas fa-route me-2"></i></h5>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12" id="readerContainer"></div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <p class="text-danger" id="textError"></p>
                    <p class="text-success" id="textSuccess"></p>
                </div>
            </div>

            <input type="hidden" name="id_capacho" id="id_capacho">
            <input type="hidden" name="id_identificador_seleccionado" id="id_identificador_seleccionado">

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

            <div id="contenedorBotonTrazabilidad" style="display: none;">
                <button type="button" class="btn btn-success btn-lg w-100" id="btnVerTrazabilidad">
                    <i class="fas fa-route me-2"></i>Ver Trazabilidad - Identificador #<span id="numeroIdentificador"></span>
                </button>
            </div>

        </div>

        <!-- Modal de Trazabilidad -->
        <div class="modal fade" id="modalTrazabilidad" tabindex="-1" aria-labelledby="modalTrazabilidadLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalTrazabilidadLabel">
                            <i class="fas fa-dolly me-2"></i>Trazabilidad de Capacho #<span id="modalNroCapacho">---</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Contenedor del Timeline -->
                        <div id="contenedorTimeline">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin fa-3x mb-2"></i>
                                <p>Cargando trazabilidad...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let qrObj;
        let capachoData = null;
        let identificadorActual = null;
        let modalTrazabilidad;

        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
            modalTrazabilidad = new bootstrap.Modal(document.getElementById('modalTrazabilidad'));
        });

        const procesarResultadoQr = (resultadoqr) => {
            try {
                $('#textError').html('').hide();
                $('#textSuccess').html('').hide();
                $('.spanDatoCapacho').html('');
                $('#id_capacho').val('');
                $('#tablaIdentificadores').hide();
                $('#contenedorBotonTrazabilidad').hide();

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

            // Mostrar botón de trazabilidad
            mostrarBotonTrazabilidad(identificadorActual.NUMERO);
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

                html += `<tr>
                            <td>
                                <button type="button" class="btn ${colorBtn}"
                                    onclick="seleccionarIdentificador(${ident.ID_IDENTIFICADOR}, ${ident.NUMERO}, '${ident.ESTADO_CAPACHO}')">
                                    Identificador #${ident.NUMERO} - ${ident.ESTADO_CAPACHO}
                                </button>
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

            // Mostrar botón de trazabilidad
            mostrarBotonTrazabilidad(numero);
        } //seleccionarIdentificador

        const mostrarBotonTrazabilidad = (numero) => {
            $('#numeroIdentificador').text(numero);
            $('#contenedorBotonTrazabilidad').show();
        } //mostrarBotonTrazabilidad

        // Event listener para abrir el modal de trazabilidad
        $(document).on('click', '#btnVerTrazabilidad', function() {
            const nroCapacho = $('#nro_capacho').text();
            $('#modalNroCapacho').text(nroCapacho);

            modalTrazabilidad.show();
            cargarTrazabilidad();
        });

        const cargarTrazabilidad = () => {
            const id_capacho = $('#id_capacho').val();
            const id_identificador = $('#id_identificador_seleccionado').val();

            $('#contenedorTimeline').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-3x mb-2"></i><p>Cargando trazabilidad...</p></div>');

            const formData = new FormData();
            formData.append("id_capacho", id_capacho);
            formData.append("id_identificador", id_identificador);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerTrazabilidadCapacho') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('response obtenerTrazabilidadCapacho', response);
                    if(response.success){
                        mostrarTrazabilidad(response.data);
                    }else{
                        $('#contenedorTimeline').html(`<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${response.message}</div>`);
                    }
                },
                error: function(xhr, status, error) {
                    $('#contenedorTimeline').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los datos</div>');
                    console.error('Error en ajax obtenerTrazabilidadCapacho:', error);
                }
            });
        } //cargarTrazabilidad

        const mostrarTrazabilidad = (datos) => {
            if (!datos || datos.length === 0) {
                $('#contenedorTimeline').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No se encontraron registros de trazabilidad</div>');
                return;
            }

            let html = '<div class="timeline">';

            datos.forEach((actividad, index) => {
                const color = obtenerColorEstado(actividad);
                const fechaFormateada = formatearFechaHora(actividad.FECHA_HORA_ALTA);

                html += `
                    <div class="timeline-item">
                        <div class="timeline-dot bg-${color}"></div>
                        <div class="timeline-content border-${color}">
                            <div class="fw-bold text-${color} mb-1">${actividad.ESTADO_CAPACHO}</div>
                            <div class="small">
                                <div class="mb-1">
                                    <i class="fas fa-clock text-${color} me-1"></i>
                                    <strong>${fechaFormateada}</strong>
                                </div>
                                <div class="text-muted">
                                    <i class="fas fa-user text-${color} me-1"></i>
                                    ${actividad.USUARIO || 'Pendiente'}
                                </div>
                                ${actividad.POSICION ? `<div class="text-muted mt-1">
                                    <i class="fas fa-map-marker-alt text-${color} me-1"></i>
                                    Posición: ${actividad.POSICION}
                                </div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            $('#contenedorTimeline').html(html);
        } //mostrarTrazabilidad

        const obtenerColorEstado = (actividad) => {
            // Si no tiene fecha ni usuario, es un estado pendiente -> gris
            if (!actividad.FECHA_HORA_ALTA && !actividad.USUARIO) {
                return 'secondary';
            }

            // Si ya pasó por el estado, determinar color según ID
            if (actividad.ID_ESTADO_ACTUAL === 10) return 'danger';
            if (actividad.ID_ESTADO_ACTUAL === 20) return 'success';

            // Cualquier otro estado con datos -> amarillo
            return 'warning';
        } //obtenerColorEstado

        const formatearFechaHora = (fechaHora) => {
            if (!fechaHora) return 'Pendiente';

            const fecha = new Date(fechaHora);
            const dia = String(fecha.getDate()).padStart(2, '0');
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const anio = fecha.getFullYear();
            const horas = String(fecha.getHours()).padStart(2, '0');
            const minutos = String(fecha.getMinutes()).padStart(2, '0');

            return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
        } //formatearFechaHora

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
