<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Leer qr de Equipo/Inventario</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link href="{{ asset('bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
        <script src="{{ asset('bootstrap5/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('jquery/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('html5-qrcode/html5-qrcode.min.js') }}"></script>
    </head>
    <body class="antialiased">
        <style>
            #reader {
                width: 100%;
            }

            .sigweb-links {
                margin-top: 10px;
            }

            .sigweb-links .btn {
                width: 100%;
            }

            .sigweb-links .btn + .btn {
                margin-top: 10px;
            }
        </style>

        <div class="container" style="max-width: 700px;">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning" role="alert">
                        <h5 class="text-center mb-0">Leer qr de Equipo/Inventario</h5>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12" id="readerContainer"></div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <p class="text-danger mb-0" id="textError" style="display: none;"></p>
                </div>
            </div>

            <input type="hidden" id="id_inventario" name="id_inventario">

            <div class="row" id="bloqueLinks" style="display: none;">
                <div class="col-12">
                    <div class="sigweb-links">
                        <a href="#" id="btn_link_pendientes" class="btn btn-primary">Ver Pendientes</a>
                        <a href="#" id="btn_link_historial" class="btn btn-secondary">Ver Historial</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let scannerInstance;
            const sigwebUrl = @json($sigwebUrl);

            document.addEventListener('DOMContentLoaded', function() {
                iniciarScanner();
            });

            const limpiarResultado = () => {
                $('#id_inventario').val('');
                $('#textError').html('').hide();
                $('#bloqueLinks').hide();
                $('#btn_link_pendientes').attr('href', '#');
                $('#btn_link_historial').attr('href', '#');
            };

            const mostrarError = (mensaje) => {
                $('#textError').html(mensaje).show();
                $('#bloqueLinks').hide();
            };

            const construirLinks = (idInventario) => {
                const pendientesUrl = sigwebUrl + '/mantenimiento/mis-solicitudes?id_inv=' + encodeURIComponent(idInventario);
                const historialUrl = sigwebUrl + '/mantenimiento/menuTareasMantenimiento?id_inv=' + encodeURIComponent(idInventario);

                $('#btn_link_pendientes').attr('href', pendientesUrl);
                $('#btn_link_historial').attr('href', historialUrl);
                $('#bloqueLinks').show();
            };

            const procesarResultadoQr = (resultadoQr) => {
                limpiarResultado();

                let qrObj;
                try {
                    qrObj = JSON.parse(resultadoQr);
                } catch (error) {
                    mostrarError('Formato de qr incorrecto');
                    return;
                }

                const idInventario = parseInt(qrObj.id_inventario, 10);
                const esValido = Object.prototype.hasOwnProperty.call(qrObj, 'id_inventario')
                    && Number.isInteger(idInventario)
                    && idInventario > 0;

                if (!esValido) {
                    mostrarError('Formato de qr incorrecto');
                    return;
                }

                $('#id_inventario').val(idInventario);
                construirLinks(idInventario);
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

            $(document).on('click', '#btn_link_pendientes, #btn_link_historial', function(event) {
                event.preventDefault();

                const url = $(this).attr('href');
                if (!url || url === '#') {
                    return;
                }

                const confirmado = confirm('Confirma abrir este enlace?');
                if (!confirmado) {
                    return;
                }

                window.location.href = url;
            });
        </script>
    </body>
</html>
