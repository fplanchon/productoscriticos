<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Productos Criticos</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <script src="{{ asset('/qrscanner/node_modules/html5-qrcode/html5-qrcode.min.js') }}"></script>


    </head>
    <body class="antialiased">
        <style>
            main {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            #reader {
                width: 100%;
            }
            #result {
                text-align: center;
                font-size: 1.5rem;
            }
        </style>


        <!--<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">-->
            <main>
                <div id="reader"></div>
                <div id="result"></div>
            </main>
        <!--</div>-->

        <script>
            const scanner = new Html5QrcodeScanner('reader', {
                    // Scanner will be initialized in DOM inside element with id of 'reader'
                    qrbox: {
                        width: 250,
                        height: 250,
                    },  // Sets dimensions of scanning box (set relative to reader element width)
                    fps: 20, // Frames per second to attempt a scan
                });


                scanner.render(success, error);
                // Starts scanner

                function success(result) {

                    document.getElementById('result').innerHTML = `
                    <h2>Success!</h2>
                    <p><a href="${result}">${result}</a></p>
                    `;
                    // Prints result as a link inside result element

                    scanner.clear();
                    // Clears scanning instance

                    //document.getElementById('reader').remove();
                    // Removes reader element from DOM since no longer needed

                }

                function error(err) {
                    console.error(err);
                    // Prints any errors to the console
                }
        </script>

    </body>
</html>
