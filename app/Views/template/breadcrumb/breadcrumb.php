<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url('/app/dashboard') ?>">Panel</a></li>
        <?php if(isset($breadcrumb)): ?>
            <li class="breadcrumb-item"><a href="<?= base_url($breadcrumb['previous_page_url'])  ?>"><?= $breadcrumb['previous_page_name'] ?></a></li>
        <?php endif; ?>
        <?php if('Panel' != $title): ?>
            <li class="breadcrumb-item"><a href="<?= $cleanUrl = str_replace("/index.php", "", current_url());  ?>"><?= $title ?></a></li>
        <?php endif; ?>
    </ol>
</div>