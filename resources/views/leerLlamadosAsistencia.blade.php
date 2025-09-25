<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Leer QR Llamados de Asistencia</title>

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

        <div class="container" style="max-width:600px">

            <div class="row">
                <div class="col-12 ">

                    <div class="alert alert-info" role="alert">
                        <h5 class="text-center">Leer QR de Llamados de Asistencia <i class="bi bi-qr-code me-2"></i></h5>
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
            <input type="hidden" name="id_llamado" id="id_llamado" >

            <div class="row mb-3">
                <div class="col-12">
                    <p><strong>Nro Solicitud:</strong> <span id="nro_solicitud_display"></span></p>
                    <p style="display: none"><strong>ID Llamado:</strong> <span id="id_llamado_display"></span></p>
                    <p><strong>Observaciones:</strong> <span id="obs_llamados_display"></span></p>
                    <p><strong>Fase Destino:</strong> <span id="fase_destino_display"></span></p>
                    <p style="display: none"><strong>Usuario Crea:</strong> <span id="usuario_crea_display"></span></p>
                    <p style="display: none"><strong>Fecha Solicitud:</strong> <span id="fecha_solicitud_display"></span></p>

                </div>
            </div>

        </div>

        <script>
        let qrObj;
        document.addEventListener('DOMContentLoaded', function() {
            iniciarScanner();
        });

        const procesarResultadoQr = (resultadoqr) => {
            try {
                $('#textError').html('').hide();
                $('#textSuccess').html('').hide();
                $('#id_llamado').val('');
                $('#id_llamado_display').html('');
                $('#obs_llamados_display').html('');
                $('#fase_destino_display').html('');
                $('#usuario_crea_display').html('');
                $('#fecha_solicitud_display').html('');
                $('#nro_solicitud_display').html('');

                qrObj = JSON.parse(resultadoqr);

                let id_llamado_parsed = parseInt(qrObj.id_llamado);
                if (qrObj.hasOwnProperty('id_llamado') && !isNaN(id_llamado_parsed) && Number.isInteger(id_llamado_parsed) && id_llamado_parsed > 0) {
                    // Validar que id_llamado sea un entero positivo
                } else {
                    throw new Error('El QR no contiene un identificador de llamado válido (debe ser un número entero)')
                }

                $('#id_llamado').val(id_llamado_parsed);
                $('#id_llamado_display').html(id_llamado_parsed);

                // Obtener info del llamado
                obtenerInfoLlamado(id_llamado_parsed);

            } catch (error) {
                console.log(error);
                if (error.name == 'SyntaxError') {
                    $('#textError').html('Formato incorrecto en el QR. Debe ser un JSON válido.').show();
                } else {
                    $('#textError').html(error.message).show();
                }
            }

            console.log(qrObj);
        } //procesarResultadoQr

        const obtenerInfoLlamado = (id_llamado) => {
            const formData = new FormData();

            formData.append("id_llamado", id_llamado);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('obtenerInfoLlamado') }}",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    // Mostrar loading si es necesario
                },
                success: function(response) {
                    console.log('response', response);
                    if(response.success){
                        const data = response.data;
                        $('#obs_llamados_display').html(data.OBS_LLAMADOS);
                        $('#fase_destino_display').html(data.FASE_DESTINO_DESC);
                        $('#usuario_crea_display').html(data.USUARIO_CREA_NOMBRE);
                        $('#fecha_solicitud_display').html(data.FECHA_SOLICITUD);
                        $('#nro_solicitud_display').html(data.NRO_SOLICITUD);

                        // Confirmar
                        if (confirm("¿Desea enviar " + data.OBS_LLAMADOS + " a " + data.FASE_DESTINO_DESC + "?")) {
                            realizarLlamadoAsistencia(id_llamado);
                        }
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error en la solicitud: ' + error).show();
                    console.error('Error en ajax obtenerInfoLlamado:', error);
                },
                complete: function(data) {
                    // Ocultar loading si es necesario
                }
            });
        } //obtenerInfoLlamado

        const realizarLlamadoAsistencia = (id_llamado) => {
            const formData = new FormData();

            formData.append("id_llamado", id_llamado);
            formData.append("_token",'{{ csrf_token() }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('realizarLlamadoAsistencia') }}",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    // Mostrar loading si es necesario
                },
                success: function(response) {
                    console.log('response', response);
                    if(response.success){
                        $('#textSuccess').html('Llamado de asistencia realizado exitosamente.').show();
                        alert('Llamado de asistencia realizado exitosamente.');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }else{
                        $('#textError').html(response.message).show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#textError').html('Error en la solicitud: ' + error).show();
                    console.error('Error en ajax realizarLlamadoAsistencia:', error);
                },
                complete: function(data) {
                    // Ocultar loading si es necesario
                }
            });
        } //realizarLlamadoAsistencia

        const iniciarScanner = () => {
            $('#readerContainer').html('<div  id="reader"></div>');

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
