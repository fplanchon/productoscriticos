<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Solicitud de Mantenimiento</title>

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

        <div class="container" style="max-width: 700px;">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning" role="alert">
                        <h5 class="text-center mb-0">Leer QR Solicitud de Mantenimiento <i class="bi bi-qr-code me-2"></i></h5>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12" id="readerContainer"></div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <p class="text-danger mb-1" id="textError" style="display: none;"></p>
                    <p class="text-success mb-1" id="textSuccess" style="display: none;"></p>
                </div>
            </div>

            <input type="hidden" id="id_inventario" name="id_inventario">
            <input type="hidden" id="detalle_solicitud" name="detalle_solicitud">

            <div class="row mb-3" id="resultadoInventario" style="display: none;">
                <div class="col-12">
                    <p class="mb-1"><strong>ID Inventario:</strong> <span id="id_inventario_display"></span></p>
                    <p class="mb-1"><strong>Numero Inventario:</strong> <span id="nro_inventario_display"></span></p>
                    <p class="mb-1"><strong>Descripcion:</strong> <span id="descripcion_inventario_display"></span></p>
                </div>
            </div>

            <div class="row mb-3" id="bloquePendientes" style="display: none;">
                <div class="col-12">
                    <p class="mb-2"><strong>Solicitudes pendientes:</strong></p>
                    <div id="tablaPendientesContainer"></div>
                </div>
            </div>

            <div class="row mb-4" id="bloqueSolicitar" style="display: none;">
                <div class="col-12">
                    <button type="button" class="btn btn-primary w-100" id="btnSolicitarReparacion">Solicitar Reparacion</button>
                </div>
            </div>
        </div>

        <script>
            let scannerInstance;

            document.addEventListener('DOMContentLoaded', function() {
                iniciarScanner();
            });

            const limpiarPantalla = () => {
                $('#textError').html('').hide();
                $('#textSuccess').html('').hide();
                $('#id_inventario').val('');
                $('#detalle_solicitud').val('');
                $('#id_inventario_display').html('');
                $('#nro_inventario_display').html('');
                $('#descripcion_inventario_display').html('');
                $('#tablaPendientesContainer').html('');
                $('#resultadoInventario').hide();
                $('#bloquePendientes').hide();
                $('#bloqueSolicitar').hide();
            };

            const reiniciarVistaConAlerta = (mensaje) => {
                alert(mensaje);
                location.reload();
            };

            const procesarResultadoQr = (resultadoQr) => {
                limpiarPantalla();

                let qrObj;
                try {
                    qrObj = JSON.parse(resultadoQr);
                } catch (error) {
                    reiniciarVistaConAlerta('Formato incorrecto en el QR. Debe ser un JSON valido.');
                    return;
                }

                const idInventarioParsed = parseInt(qrObj.id_inventario, 10);
                const esValido = qrObj.hasOwnProperty('id_inventario')
                    && !isNaN(idInventarioParsed)
                    && Number.isInteger(idInventarioParsed)
                    && idInventarioParsed > 0;

                if (!esValido) {
                    reiniciarVistaConAlerta('El QR no contiene un id_inventario valido (entero positivo).');
                    return;
                }

                $('#id_inventario').val(idInventarioParsed);
                validarInventario(idInventarioParsed);
            };

            const validarInventario = (idInventario) => {
                const formData = new FormData();
                formData.append('id_inventario', idInventario);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('validarInventarioMantenimiento') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (!response.success) {
                            reiniciarVistaConAlerta(response.message || 'El inventario no existe.');
                            return;
                        }

                        const data = response.data;
                        $('#id_inventario_display').html(data.ID_INVENTARIO_PROD ?? data.id_inventario_prod ?? '');
                        $('#nro_inventario_display').html(data.NRO_INVENTARIO ?? data.nro_inventario ?? '');
                        $('#descripcion_inventario_display').html(data.DESCRIPCION ?? data.descripcion ?? '');
                        $('#resultadoInventario').show();
                        $('#bloqueSolicitar').show();

                        obtenerPendientes(idInventario);
                    },
                    error: function(xhr, status, error) {
                        reiniciarVistaConAlerta('Error en la solicitud: ' + error);
                    }
                });
            };

            const obtenerPendientes = (idInventario) => {
                const formData = new FormData();
                formData.append('id_inventario', idInventario);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('obtenerPendientesMantenimiento') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (!response.success) {
                            $('#tablaPendientesContainer').html('<p class="mb-0 text-danger">' + (response.message || 'No se pudieron consultar solicitudes pendientes.') + '</p>');
                            $('#bloquePendientes').show();
                            return;
                        }

                        const filas = response.data || [];
                        if (!filas.length) {
                            $('#tablaPendientesContainer').html('<p class="mb-0">Sin solicitudes.</p>');
                            $('#bloquePendientes').show();
                            return;
                        }

                        let htmlTabla = '';
                        htmlTabla += '<div class="table-responsive">';
                        htmlTabla += '<table class="table table-sm table-bordered">';
                        htmlTabla += '<thead><tr><th>Tarea</th><th>Alta</th><th>Estado</th></tr></thead>';
                        htmlTabla += '<tbody>';

                        filas.forEach(function(item) {
                            const idMant = item.ID_MANTENIMIENTO ?? '';
                            const fecha = item.FECHA_HORA_CREACION ?? '';
                            const estado = item.NOMBRE_ESTADO ?? '';
                            htmlTabla += '<tr>';
                            htmlTabla += '<td>' + idMant + '</td>';
                            htmlTabla += '<td>' + fecha + '</td>';
                            htmlTabla += '<td>' + estado + '</td>';
                            htmlTabla += '</tr>';
                        });

                        htmlTabla += '</tbody></table></div>';
                        $('#tablaPendientesContainer').html(htmlTabla);
                        $('#bloquePendientes').show();
                    },
                    error: function(xhr, status, error) {
                        $('#tablaPendientesContainer').html('<p class="mb-0 text-danger">Error en la solicitud: ' + error + '</p>');
                        $('#bloquePendientes').show();
                    }
                });
            };

            const solicitarReparacion = () => {
                const idInventario = parseInt($('#id_inventario').val(), 10);
                if (isNaN(idInventario) || idInventario <= 0) {
                    reiniciarVistaConAlerta('Debe leer un inventario valido antes de solicitar reparacion.');
                    return;
                }

                const detalle = prompt('Ingrese el texto de solicitud de reparacion:', '');
                if (detalle === null) {
                    return;
                }

                const detalleLimpio = detalle.trim();
                $('#detalle_solicitud').val(detalleLimpio);

                if (detalleLimpio === '') {
                    reiniciarVistaConAlerta('Debe ingresar un detalle para la solicitud.');
                    return;
                }

                const formData = new FormData();
                formData.append('id_inventario', idInventario);
                formData.append('detalle', detalleLimpio);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('solicitarReparacionMantenimiento') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const mensaje = response.message || (response.success ? 'Solicitud generada correctamente.' : 'Error al generar la solicitud.');
                        reiniciarVistaConAlerta(mensaje);
                    },
                    error: function(xhr, status, error) {
                        reiniciarVistaConAlerta('Error en la solicitud: ' + error);
                    }
                });
            };

            const iniciarScanner = () => {
                $('#readerContainer').html('<div id="reader"></div>');

                scannerInstance = new Html5QrcodeScanner('reader', {
                    qrbox: {
                        width: 250,
                        height: 250,
                    },
                    fps: 20,
                    showTorchButtonIfSupported: true
                }, false);

                scannerInstance.render(success, error);

                function success(result) {
                    procesarResultadoQr(result);
                    const stopBtn = document.getElementById('html5-qrcode-button-camera-stop');
                    if (stopBtn) {
                        stopBtn.click();
                    }
                }

                function error(err) {
                    // keep scanner running
                }

                if ($('#reader__dashboard_section_csr').length) {
                    $('html, body').animate({
                        scrollTop: $('#reader__dashboard_section_csr').offset().top
                    }, 1000);
                }
            };

            $(document).on('click', '#btnSolicitarReparacion', function() {
                solicitarReparacion();
            });
        </script>
    </body>
</html>
