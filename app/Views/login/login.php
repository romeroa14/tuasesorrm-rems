<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="
        Asesores RM inmobiliaria con más de 9 años de experiencia, ofreciendo servicios exclusivos en compra, venta y alquiler de inmuebles.
        Asesores RM ¡De la mano contigo!">
        <meta name="author" content="Asesores RM">
        <link href="<?= base_url('img/logo/circle-logo.png') ?>" rel="icon">
        <title>Inicio de sesión | Asesores RM</title>
        <link href="<?= base_url('vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('css/ruang-admin.css') ?>" rel="stylesheet">
        <script src="<?= base_url('vendor/jquery/jquery.min.js') ?>"></script>
        <?php if (ENVIRONMENT !== 'development'): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>
    </head>
    <body class="bg-gradient-login">
        <!-- Login Content -->
        <div class="container-login">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-12 col-md-9">
                    <div class="card shadow-sm my-5">
                        <div class="card-body p-0">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="login-form">
                                        <div class="pb-5 d-flex justify-content-center w-100">
                                            <img src="<?= base_url('img/logo/logo-black.webp') ?>" class="w-75" alt="">
                                        </div>
                                        <?php if(!empty(session()->getFlashdata('failed'))): ?>
                                            <div class="alert alert-danger alert-dismissible fade show rounded-0 small" role="alert">
                                                <?= session()->getFlashdata('failed') ?>
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty(session()->getFlashdata('success'))): ?>
                                            <div class="alert alert-success alert-dismissible fade show rounded-0 small" role="alert">
                                                <?= session()->getFlashdata('success') ?>
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <div id="reCAPTCHA_warning" class="alert alert-warning alert-dismissible fade show d-none rounded-0 small" role="alert">
                                            <strong>¡Obligatorio!</strong> Si eres humano deberás marcar el reCAPTCHA.
                                            <button type="button" class="close" onclick="addDisplayNone()">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form class="user pt-3" action="<?= base_url('login') ?>" id="form_login" method="post">
                                            <?= csrf_field() ?>
                                            <div class="form-group">
                                                <input type="email" class="form-control" name="email" id="exampleInputEmail" aria-describedby="emailHelp"
                                                    placeholder="Correo electrónico">
                                            </div>
                                            <div class="form-group">
                                                <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Contraseña">
                                            </div>
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox small" style="line-height: 1.5rem;">
                                                    <input type="checkbox" class="custom-control-input" id="customCheck" onclick="togglePasswordVisibility()">
                                                    <label class="custom-control-label" for="customCheck">Mostrar contraseña</label>
                                                </div>
                                            </div>
                                            <?php if (ENVIRONMENT !== 'development'): ?>
                                            <div class="d-flex justify-content-center w-100 pb-4">
                                                <div class="g-recaptcha" data-sitekey="6LfNZYssAAAAAN2-bIc8NnYJbsgCqxNXjtbd25jG"></div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="form-group">
                                                <input type="submit" id="event_submit" value="Entrar" class="btn btn-block btn-dark">
                                            </div>
                                        </form>
                                        <hr>
                                        <div class="text-center">
                                            <p>
                                                Copyright &copy;<script>document.write(new Date().getFullYear());</script> 
                                                Todos los derechos reservados <a href="https://asesoresrm.com.ve/" class="text-info text-link">Asesores RM</a>
                                            </p>
                                            <p>
                                                Developer by <i class="fa fa-heart" aria-hidden="true"></i> Cristian Trejo
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Login Content -->
        <script src="<?= base_url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
        <script src="<?= base_url('vendor/jquery-easing/jquery.easing.min.js') ?>"></script>
        <script src="<?= base_url('js/ruang-admin.min.js') ?>"></script>
        <?php if (ENVIRONMENT !== 'development'): ?>
        <script>
            document.getElementById("event_submit").addEventListener("click", function(event) {
                
                event.preventDefault();
            
                if (grecaptcha.getResponse().length === 0) {
                    var reCAPTCHAWarning = document.getElementById("reCAPTCHA_warning");

                    reCAPTCHAWarning.classList.remove("d-none");
                }else{
                    document.getElementById("form_login").submit();
                }

            });

            function addDisplayNone() {
                var reCAPTCHAWarning = document.getElementById("reCAPTCHA_warning");
                reCAPTCHAWarning.classList.add("d-none");
            }
        </script>
        <?php else: ?>
        <script>
            // [DEV MODE] Submit directo sin validación de reCAPTCHA
            document.getElementById("event_submit").addEventListener("click", function(event) {
                event.preventDefault();
                document.getElementById("form_login").submit();
            });
        </script>
        <?php endif; ?>
        <script>
            function togglePasswordVisibility() {
                var passwordInput = document.getElementById("passwordInput");
                var showPasswordButton = document.getElementById("showPasswordButton");
                
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    showPasswordButton.textContent = "Ocultar contraseña";
                } else {
                    passwordInput.type = "password";
                    showPasswordButton.textContent = "Mostrar contraseña";
                }
            }
        </script>
    </body>
</html>