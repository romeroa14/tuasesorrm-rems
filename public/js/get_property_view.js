function get_property_view() {

    var url = $(location).attr('href');

    var id = url.split("/").pop();



    axios.get(getPropertiesView + id)

        .then(function(response) {



            if (response.data.property == 'No existe la propiedad') {

                window.location.href = '/app/properties/all';

            }

            var property = response.data.property;  

            var environments = response.data.environments;  

            var business_conditions = response.data.business_conditions;  

            var amenities = response.data.amenities;  

            var exteriors = response.data.exterior;  

            var adjacencies = response.data.adjacencies;  

            var images = response.data.images;   

            var rrss = response.data.rrss;    

            var wasi = response.data.wasi;  

            var wa_shared_sample = response.data.wa_shared_sample; 

            var wa_shared_complete = response.data.wa_shared_complete; 

            var BodyMemory = document.getElementById('BodyMemory');

            var HeaderMemory = document.getElementById('HeaderMemory');

            var CarouselImages = document.getElementById('CarouselImages');

            var CarouselImagesIndicators = document.getElementById('CarouselImagesIndicators');

            var AgentCard = document.getElementById('AgentCard');

            var TemplatePropertyCard = document.getElementById('TemplatePropertyCard');

            var MapCard = document.getElementById('MapCard');

            var RRSSCard = document.getElementById('RRSSCard');

            var WasiCard = document.getElementById('WasiCard');

            var WhatsappCard = document.getElementById('WhatsappCard');



            // To set HTML content

            var card_header_memory = document.createElement('div');

            card_header_memory.classList.add('shadow-sm', 'mb-3', 'bg-white', 'rounded', 'px-4', 'pt-3', 'pb-3');

            HeaderMemory.appendChild(card_header_memory);

            

            var title_header_memory = document.createElement('h5');

            title_header_memory.innerHTML = '<h5>Asesores RM ofrece '+ property.housingtype_name +' en '+ property.businessmodel_name +

            ', ubicado en '+ property.address.toLowerCase() +', Municipio '+ property.municipality_name.toLowerCase() +

            ' del '+ (property.state_name == "Distrito Capital" ? '' : 'Estado') + ' ' + property.state_name.toLowerCase()+'.</h5>';

            card_header_memory.appendChild(title_header_memory);



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Cuenta con:';

            BodyMemory.appendChild(title);

            

            var business = document.createElement('ul');

            business.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            BodyMemory.appendChild(business);



            var bedrooms = document.createElement('li');

            bedrooms.classList.add('list-group-item');

            bedrooms.innerText = 'Habitaciones: ' + property.bedrooms;

            business.appendChild(bedrooms);



            var bathrooms = document.createElement('li');

            bathrooms.classList.add('list-group-item');

            bathrooms.innerText = 'Baños: ' + property.bathrooms;

            business.appendChild(bathrooms);



            var garages = document.createElement('li');

            garages.classList.add('list-group-item');

            garages.innerText = 'Puestos de estacionamiento: ' + property.garages;

            business.appendChild(garages);



            var meters_construction = document.createElement('li');

            meters_construction.classList.add('list-group-item');

            meters_construction.innerText = 'M² construcción: ' + property.meters_construction;

            business.appendChild(meters_construction);



            var meters_land = document.createElement('li');

            meters_land.classList.add('list-group-item');

            meters_land.innerText = 'M² terreno: ' + property.meters_land;

            business.appendChild(meters_land);



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'En ambientes tenemos:';

            BodyMemory.appendChild(title);

            

            var list_environments = document.createElement('ul');

            list_environments.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            BodyMemory.appendChild(list_environments);



            environments.forEach(function(item) {

                var environment = document.createElement('li');

                environment.classList.add('list-group-item');

                environment.innerText = item;

                list_environments.appendChild(environment);

            });



            if (amenities.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'En comodidades tenemos:';

                BodyMemory.appendChild(title);

                

                var list_amenities = document.createElement('ul');

                list_amenities.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                BodyMemory.appendChild(list_amenities);



                amenities.forEach(function(item) {

                    var amenitie = document.createElement('li');

                    amenitie.classList.add('list-group-item');

                    amenitie.innerText = item;

                    list_amenities.appendChild(amenitie);

                });

            }

            

            if (exteriors.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'En exteriores tenemos:';

                BodyMemory.appendChild(title);

                

                var list_exteriors = document.createElement('ul');

                list_exteriors.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                BodyMemory.appendChild(list_exteriors);

    

                exteriors.forEach(function(item) {

                    var exterior = document.createElement('li');

                    exterior.classList.add('list-group-item');

                    exterior.innerText = item;

                    list_exteriors.appendChild(exterior);

                });

            }



            if (adjacencies.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'En adyacencias tenemos:';

                BodyMemory.appendChild(title);

                

                var list_adjacencies = document.createElement('ul');

                list_adjacencies.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                BodyMemory.appendChild(list_adjacencies);

    

                adjacencies.forEach(function(item) {

                    var adjacencie = document.createElement('li');

                    adjacencie.classList.add('list-group-item');

                    adjacencie.innerText = item;

                    list_adjacencies.appendChild(adjacencie);

                });

            }

            

            if (business_conditions.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'Las condiciones de negocio son:';

                BodyMemory.appendChild(title);

    

                var list_business_conditions = document.createElement('ul');

                list_business_conditions.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                BodyMemory.appendChild(list_business_conditions);

    

                business_conditions.forEach(function(item) {

                    var business_condition = document.createElement('li');

                    business_condition.classList.add('list-group-item');

                    business_condition.innerText = item;

                    list_business_conditions.appendChild(business_condition);

                });   

            }



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Negocio:';

            BodyMemory.appendChild(title);

            

            var business = document.createElement('ul');

            business.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            BodyMemory.appendChild(business);



            var price = document.createElement('li');

            price.classList.add('list-group-item');

            price.innerText = 'Precio para venta: $' + property.price;

            business.appendChild(price);



            var price_additional = document.createElement('li');

            price_additional.classList.add('list-group-item');

            price_additional.innerText = 'Precio para alquiler: $' + property.price_additional;

            business.appendChild(price_additional);



            $("#image-principal").attr("src", images[0]);



            var liCarousel = document.createElement('li');

            liCarousel.classList.add('active');

            $(liCarousel).attr('data-toggle', 'modal');

            $(liCarousel).attr('data-slide-to', 0);

            CarouselImagesIndicators.appendChild(liCarousel);



            images.forEach(function(item) {

                if (item != images[0]) {

                    var divCarousel = document.createElement('div');

                    divCarousel.classList.add('carousel-item');

                    $(divCarousel).attr('data-toggle', 'modal');

                    $(divCarousel).attr('data-target', '#imageModal');

                    CarouselImages.appendChild(divCarousel);

                    

                    var imagesCarousel = document.createElement('img');

                    imagesCarousel.classList.add('d-block', 'w-100', 'image-carousel-view');

                    $(imagesCarousel).attr('src', item);

                    divCarousel.appendChild(imagesCarousel);



                    var liCarousel = document.createElement('li');

                    liCarousel.classList.add('d-block', 'w-100', 'image-carousel-view');

                    $(liCarousel).attr('data-toggle', 'modal');

                    $(liCarousel).attr('data-slide-to', +1);

                    CarouselImagesIndicators.appendChild(liCarousel);

                }

            });



            var cardAgent = document.createElement('div');

            AgentCard.appendChild(cardAgent);



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Captador/a: '+ property.name_agent.charAt(0).toUpperCase() + property.name_agent.slice(1);

            cardAgent.appendChild(title);



            var imgAgent = document.createElement('img');

            imgAgent.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            $(imgAgent).attr('src', '/users/'+ property.email_agent + '/' + property.profile_photo_agent);

            $(imgAgent).attr('style', 'height: 20rem; width: 100%; object-position: top; object-fit: cover;');

            cardAgent.appendChild(imgAgent);



            var cardMap = document.createElement('div');

            MapCard.appendChild(cardMap);



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Google maps:';

            cardMap.appendChild(title);



            var iframeMap = document.createElement('iframe');

            iframeMap.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            $(iframeMap).attr('src', 'https://www.google.com/maps?q='+property.map_coordinates+'&output=embed');

            $(iframeMap).attr('style', 'height: 300px; width: 100%;');

            cardMap.appendChild(iframeMap);



            if (rrss.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'Redes sociales:';

                RRSSCard.appendChild(title);

                

                var list_rrss = document.createElement('ul');

                list_rrss.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                RRSSCard.appendChild(list_rrss);

    

                rrss.forEach(function(item) {

                    var exterior = document.createElement('li');

                    exterior.classList.add('list-group-item');

                    exterior.innerHTML = '<a href="'+item.link+'" target="_blank">'+item.name+'</a>';

                    list_rrss.appendChild(exterior);

                });

            }



            if (wasi.length != 0) {

                var title = document.createElement('h5');

                title.classList.add('pt-2');

                title.innerText = 'Wasi:';

                WasiCard.appendChild(title);

                

                var list_wasi = document.createElement('ul');

                list_wasi.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

                WasiCard.appendChild(list_wasi);

                

                var w = document.createElement('li');

                w.classList.add('list-group-item');

                w.innerHTML = '<a href="'+wasi+'" target="_blank">'+wasi+'</a>';

                list_wasi.appendChild(w);

            }



            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Links whatsapp:';

            WhatsappCard.appendChild(title);

            

            var list_whatsapp = document.createElement('ul');

            list_whatsapp.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-4');

            WhatsappCard.appendChild(list_whatsapp);

            

            var whatsapp_complete = document.createElement('li');

            whatsapp_complete.classList.add('list-group-item');

            whatsapp_complete.innerHTML = '<a href="'+wa_shared_sample+'" target="_blank">Mensaje completo</a>';

            list_whatsapp.appendChild(whatsapp_complete);



            

            var title = document.createElement('h5');

            title.classList.add('pt-2');

            title.innerText = 'Diseño para compartir: ';

            TemplatePropertyCard.appendChild(title);



            var canvaTemplate = document.createElement('canvas');

            canvaTemplate.classList.add('rounded', 'shadow-sm', 'list-group', 'mb-2', 'w-100');

            $(canvaTemplate).attr('id', 'template_property_canvas');

            $(canvaTemplate).attr('height', '1080px');

            $(canvaTemplate).attr('width', '1080px');

            TemplatePropertyCard.appendChild(canvaTemplate);



            var download_canvas = document.createElement('button');

            download_canvas.classList.add('btn', 'btn-success', 'text-white', 'w-100');

            $(download_canvas).attr('id', 'downloadCanvas');

            download_canvas.innerHTML = 'Descargar';

            TemplatePropertyCard.appendChild(download_canvas);

            

            // Selecciona el canvas y el contexto

            var canvas = document.getElementById('template_property_canvas');

            var ctx = canvas.getContext('2d');



            // Carga la imagen de fondo JPG

            var imgFondo = new Image();

            imgFondo.src =  images[0]; // Reemplaza con la ruta de tu imagen JPG





            imgFondo.onload = function() {

                // Dibuja la imagen de fondo en el canvas

                ctx.drawImage(imgFondo, 0, 0, 1080, 1080);

            

                var imgSuperpuesta = new Image();



                if (property.housingtype_name == 'Terreno') {

                    imgSuperpuesta.src = '/property_template/terreno.png'; // Asegúrate de que la ruta sea correcta

                }else{

                    imgSuperpuesta.src = '/property_template/'+property.area_type_name+'.png'; // Asegúrate de que la ruta sea correcta

                }

                

                // Asegúrate de que la imagen superpuesta se cargue antes de dibujarla

                imgSuperpuesta.onload = function() {

                    // Dibuja la imagen superpuesta en el canvas

                    ctx.drawImage(imgSuperpuesta, 0, 0, 1080, 1080);



                    ctx.font = '40px Gadugi black';

                    ctx.textAlign = 'center';

                    ctx.fillText(property.businessmodel_name.toUpperCase(), 880, 80);

                    



                    

                    if (property.housingtype_name == 'Terreno') {

                        ctx.font = '24px Gadugi';

                        ctx.fillStyle = 'white';

                        ctx.textAlign = 'left';

                        ctx.fillText(property.meters_land+' M²', 750, 130);

                    }else{

                        ctx.font = '24px Gadugi';

                        ctx.fillStyle = 'white';

                        ctx.textAlign = 'left';

                        ctx.fillText(property.meters_construction, 750, 130);

                        

                        ctx.font = '24px Gadugi';

                        ctx.fillStyle = 'white';

                        ctx.textAlign = 'left';

                        ctx.fillText(property.bedrooms, 865, 130);

                        

                        ctx.font = '24px Gadugi';

                        ctx.fillStyle = 'white';

                        ctx.textAlign = 'left';

                        ctx.fillText(property.bathrooms, 955, 130);

                        

                        ctx.font = '24px Gadugi';

                        ctx.fillStyle = 'white';

                        ctx.textAlign = 'left';

                        ctx.fillText(property.garages, 1040, 130);

                    }

                    

                    ctx.font = '38px Gadugi black';

                    ctx.fillStyle = 'black';

                    ctx.textAlign = 'left';

                    ctx.fillText(property.housingtype_name.toUpperCase(), 20, 870);

                    

                    ctx.font = '35px Gadugi';

                    ctx.fillStyle = 'black';

                    ctx.textAlign = 'left';

                    ctx.fillText(property.address.toUpperCase(), 20, 920);

                    

                    ctx.font = '30px Gadugi black'; 

                    ctx.fillStyle = 'black';

                    ctx.textAlign = 'left';

                    

                    var stateText = property.state_name.charAt(0).toUpperCase() + property.state_name.slice(1);

                    var stateTextWidth = ctx.measureText(stateText).width;

                    

                    ctx.fillText(stateText, 20, 970);

                    

                    ctx.font = '30px Gadugi';

                    

                    var municipalityX = 20 + stateTextWidth + 10;



                    ctx.fillText('| Municipio ' + property.municipality_name.toLowerCase(), municipalityX, 970);

                    

                    ctx.font = '40px Gadugi';

                    ctx.fillStyle = 'white';

                    ctx.textAlign = 'left';

                    ctx.fillText('RM00'+property.id_properties, 800, 1000);







                    var downloadCanvas = document.getElementById('downloadCanvas');



                    downloadCanvas.addEventListener('click', function (e) {

                        var dataURL = canvas.toDataURL('image/jpeg', 1.0); // Convierte el canvas a formato de imagen JPEG

                        var link = document.createElement('a');

                        link.download = 'RM00'+property.id_properties; // Define el nombre del archivo a descargar

                        link.href = dataURL;

                        link.click(); // Simula un click para iniciar la descarga

                    });

                };

            };

            

        })

        .catch(function(error) {

            console.error(error);

            preloader.setAttribute("style", "display: none !important;");

        });



}



get_property_view();