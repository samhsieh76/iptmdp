<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mdb-vue-ui-kit/css/mdb.min.css" />
    <style>
        body {
            background: aliceblue;
        }
        .row {
            height: 100vh;
        }
    </style>
</head>
<body class="bg-gray" oncontextmenu ="return false">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-sm-12 my-auto">
                <div class="card card-block bg-light">
                    <div class="card-body">
                        <h1>{{ trans('emails.request_location_title') }}</h1>
                        <p>場域授權失敗</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>