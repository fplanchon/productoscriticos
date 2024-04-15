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

            <div class="container py-5">
                <div class="w-80 center border rounded px-3 py-3 mx-auto">
                    <h1>Asociar Productos Criticos</h1>
                    <h2 class="text-center">Login</h2>
                        <form action="" method="POST">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" >
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario</label>
                                <input type="text" value="" name="usuario" id="usuario" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="id_fase_usuario" class="form-label">Fase</label>
                                <select name="id_fase" id="id_fase_usuario" class="form-control">
                                </select>
                            </div>
                            <div class="mb-3 d-grid">
                                <button name="submit" type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
        <script>
            $('#usuario').change(function(){
                buscarFasesUsuario();
            });

            const buscarFasesUsuario = ()=> {
                const formData = new FormData();

                formData.append('usuario',$('#usuario').val());
                formData.append("_token",'{{ csrf_token() }}');
                $.ajax({
                    type: 'POST',
                    url: "{{ route('buscarfasesusuario') }}",
                    data: formData,
                    processData: false,  // tell jQuery not to process the data
                    contentType: false,
                    beforeSend: function() {
                        //$('#cartelAsociando').show();
                    },
                    success: function(response) {
                        console.log(response);
                        let html = response.map(fase => {
                            return `<option value="${fase.ID_FASE}">${fase.DESC_FASES}</option>`;
                        });

                        $('#id_fase_usuario').html(html);
                    },
                    error: function(xhr, status, error) {
                        $('#textError').html(error);
                        console.error('Error en ajax asociarProductoCritico:', error);
                    },
                    complete: function(data) {
                        $('#cartelAsociando').hide();
                    }
                });
            }
        </script>
    </body>
</html>
