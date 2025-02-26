
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Desafio</title>
        <style>
            body {
                background: black;
                color: white;
            }
        </style>
    </head>
    <body>
        @foreach ($files as $file)
            {{$file}} <br>
        @endforeach

    </body>
</html>
