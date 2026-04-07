<div class="row">
    <div class="col-md-12 pb-3"> 
        <div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
            <ol class="carousel-indicators" id="CarouselImagesIndicators"></ol>
            <div class="carousel-inner" id="CarouselImages">
                <div class="carousel-item active" data-toggle="modal" data-target="#imageModal">
                    <img class="d-block w-100 image-carousel-view" id="image-principal">
                </div>
            </div>
            <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Anterior</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Siguiente</span>
            </a>
        </div>
    </div>
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body">
                    <img src="" class="img-fluid w-100" id="imagepreview">
                    <button type="button" class="btn btn-primary btn-sm mt-3 w-100" data-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="pt-2" id="HeaderMemory"></div>
        <div class="pt-2" id="BodyMemory"></div>
    </div>
    <div class="col-md-4">
        <div class="pt-2" id="AgentCard"></div>
        <div class="pt-2" id="MapCard"></div>
        <div class="pt-2" id="RRSSCard"></div>
        <div class="pt-2" id="WasiCard"></div>
        <div class="pt-2" id="WhatsappCard"></div>
        <div class="pt-2" id="TemplatePropertyCard"></div>
    </div>
</div>