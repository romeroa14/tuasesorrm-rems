$(document).ready(function() {
    $('#carouselExampleControls').on('click', '.carousel-item', function() {
        var imgSource = $(this).find('img').attr('src');
        $('#imagepreview').attr('src', imgSource);
    });
});