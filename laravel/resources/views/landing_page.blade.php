<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>MyCities</title>

    <!-- Custom fonts for this template-->
    <link href="{{ url('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="{{ url('/css/main.css')  }}" rel="stylesheet">
    <!-- Custom styles for this template-->
</head>

<body>
<div class="main-body">
    <div class="bg-image">
        <div class="intro-txt">
            <h3>Welcome to the MyCities App where you can manage your meters and much more free of cost!</h3>
			<div class="admin-btn">
                <a class="admin-button" href="{{ url('/web-app') }}">Go to Web App</a>
            </div>
            <div class="admin-btn">
                <a class="admin-button" href="{{ url('/admin') }}">Go to Admin Panel</a>
            </div>

        </div>
    </div>
</div>
</body>

</html>
