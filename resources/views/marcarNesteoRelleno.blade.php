<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Marcar Nesteo Relleno</title>

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

            .btn-accion {
                width: 100%;
            }
        </style>

        <div class="container" style="max-width: 700px;">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <h5 class="text-center mb-0">Leer QR Nesteo Relleno</h5>
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

            <input type="hidden" id="id_nesteo_relleno" name="id_nesteo_relleno">
            <input type="hidden" id="accion_habilitada" name="accion_habilitada">

            <div class="row mb-3" id="resultadoNesteo" style="display: none;">
                <div class="col-12">
                    <p class="mb-1"><strong>ID NESTEO RELLENO:</strong> <span id="id_nesteo_relleno_display"></span></p>
                    <p class="mb-1"><strong>NRO PIEZA:</strong> <span id="nro_pieza_relleno_display"></span></p>
                    <p class="mb-1"><strong>ESTADO ACTUAL:</strong> <span id="estado_actual_display"></span></p>
                </div>
            </div>

            <div class="row mb-4" id="bloqueAcciones" style="display: none;">
                <div class="col-12 d-flex justify-content-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-success btn-accion" id="btnCheck" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.08.022l3.992-4.99a.75.75 0 0 0-1.17-.942L7.447 9.412 5.383 7.348a.75.75 0 1 0-1.06 1.06l2.647 2.622z"/>
                        </svg>
                        CHECK
                    </button>
                    <button type="button" class="btn btn-warning btn-accion" id="btnStop" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3-8.5A1.5 1.5 0 0 0 9.5 5h-3A1.5 1.5 0 0 0 5 6.5v3A1.5 1.5 0 0 0 6.5 11h3A1.5 1.5 0 0 0 11 9.5v-3z"/>
                        </svg>
                        STOP
                    </button>
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
                $('#id_nesteo_relleno').val('');
                $('#accion_habilitada').val('');
                $('#id_nesteo_relleno_display').html('');
                $('#nro_pieza_relleno_display').html('');
                $('#estado_actual_display').html('');
                $('#resultadoNesteo').hide();
                $('#bloqueAcciones').hide();
                $('#btnCheck').hide();
                $('#btnStop').hide();
            };

            const reiniciarVistaConAlerta = (mensaje) => {
                alert(mensaje);
                location.reload();
            };

            const mostrarResultado = (data) => {
                const estado = parseInt(data.ESTADO ?? 0, 10);
                const accionHabilitada = data.ACCION_HABILITADA ?? 'CHECK';

                $('#id_nesteo_relleno').val(data.ID_NESTEO_RELLENO ?? '');
                $('#accion_habilitada').val(accionHabilitada);
                $('#id_nesteo_relleno_display').html(data.ID_NESTEO_RELLENO ?? '');
                $('#nro_pieza_relleno_display').html(data.NRO_PIEZA_RELLENO ?? '');

                if (estado === 10) {
                    $('#estado_actual_display').html('RELLENO HABILITADO');
                } else if (estado === 0) {
                    $('#estado_actual_display').html('SUSPENDIDO');
                } else {
                    $('#estado_actual_display').html(data.ESTADO_TEXTO ?? 'ESTADO NO CONTEMPLADO');
                }

                $('#resultadoNesteo').show();
                $('#bloqueAcciones').show();

                if (accionHabilitada === 'STOP') {
                    $('#btnStop').show();
                    $('#btnCheck').hide();
                } else {
                    $('#btnCheck').show();
                    $('#btnStop').hide();
                }
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

                const idNesteoParsed = parseInt(qrObj.ID_NESTEO_RELLENO, 10);
                const esValido = qrObj.hasOwnProperty('ID_NESTEO_RELLENO')
                    && !isNaN(idNesteoParsed)
                    && Number.isInteger(idNesteoParsed)
                    && idNesteoParsed > 0;

                if (!esValido) {
                    reiniciarVistaConAlerta('El QR no contiene un ID_NESTEO_RELLENO valido (entero positivo).');
                    return;
                }

                consultarEstado(idNesteoParsed);
            };

            const consultarEstado = (idNesteoRelleno) => {
                const formData = new FormData();
                formData.append('id_nesteo_relleno', idNesteoRelleno);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('obtenerEstadoNesteoRelleno') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (!response.success) {
                            reiniciarVistaConAlerta(response.message || 'No se pudo consultar el estado.');
                            return;
                        }

                        mostrarResultado(response.data || {});
                    },
                    error: function(xhr, status, error) {
                        reiniciarVistaConAlerta('Error en la solicitud: ' + error);
                    }
                });
            };

            const ejecutarAccion = (accion) => {
                const idNesteoRelleno = parseInt($('#id_nesteo_relleno').val(), 10);
                const textoAccion = accion === 'STOP' ? 'SUSPENDER' : 'HABILITAR RELLENO';

                if (isNaN(idNesteoRelleno) || idNesteoRelleno <= 0) {
                    reiniciarVistaConAlerta('Debe leer un QR valido antes de ejecutar una accion.');
                    return;
                }

                const confirmado = confirm('Confirma ejecutar la accion: ' + textoAccion + '?');
                if (!confirmado) {
                    return;
                }

                const formData = new FormData();
                formData.append('id_nesteo_relleno', idNesteoRelleno);
                formData.append('accion', accion);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('actualizarEstadoNesteoRelleno') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (!response.success) {
                            reiniciarVistaConAlerta(response.message || 'No se pudo actualizar el estado.');
                            return;
                        }

                        mostrarResultado(response.data || {});
                        $('#textSuccess').html(response.message || 'Estado actualizado correctamente.').show();
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

            $(document).on('click', '#btnCheck', function() {
                ejecutarAccion('CHECK');
            });

            $(document).on('click', '#btnStop', function() {
                ejecutarAccion('STOP');
            });
        </script>
    </body>
</html>
