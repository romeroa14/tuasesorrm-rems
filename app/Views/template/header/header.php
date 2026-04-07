<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link href="<?= base_url('img/logo/circle-logo.png') ?>" rel="icon">
        <title><?= $title.$slogan ?></title>
        <link href="<?= base_url('vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('css/ruang-admin.css') ?>" rel="stylesheet">
        <link href="<?= base_url('css/jquery.dataTables.min.css') ?>" rel="stylesheet">
        <!-- Select2 -->
        <link href="<?= base_url('vendor/select2/dist/css/select2.min.css') ?>" rel="stylesheet" type="text/css">
        <!-- Bootstrap DatePicker -->  
        <link href="<?= base_url('vendor/bootstrap-datepicker/css/bootstrap-datepicker.min.css') ?>" rel="stylesheet" >
        <!-- Bootstrap Touchspin -->
        <link href="<?= base_url('vendor/bootstrap-touchspin/css/jquery.bootstrap-touchspin.css') ?>" rel="stylesheet" >
    </head>
    <body id="page-top"
    <?php if (isset($datatable['prefix'])): ?>
        onload="initDataTable('<?= $datatable['prefix'] ?>table')"
    <?php endif ?>
    >
        <div id="wrapper">