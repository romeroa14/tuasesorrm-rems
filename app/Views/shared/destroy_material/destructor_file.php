<form action="<?= base_url($url_post_destroy); ?>" method="post">
    <?= csrf_field(); ?>
    <input hidden="" name="destroy_file" value="<?= $directory_file_destroy ?>">
    <button class="btn btn-danger btn-sm w-100 rounded-0">
        <i class="fa fa-trash" aria-hidden="true"></i>
    </button>
</form>