function get_properties(page, rm, address, min_p, max_p, business_model, tp, min_c_m2, max_c_m2, agent) {
    // Selecciona el preloader
    var preloader = document.getElementById('preloader');
    
    axios.get(getProperties + '?page=' + page + '&rm=' + rm + '&address=' + address + '&min_p=' + min_p + '&max_p=' + max_p + '&business_model=' + business_model + '&tp=' + tp + '&min_c_m2=' + min_c_m2 + '&max_c_m2=' + max_c_m2 + '&agent=' + agent)
        .then(function(response) {
            
            preloader.setAttribute("style", "display: none !important;");
            var cardsContainer = document.getElementById('cards-container');
            var data = response.data.data;

            if (data.length == 0) {
                document.getElementById("message-result-search").innerHTML = '<h1>No hay resultados para esta búsqueda.</h1>';
            } else {
                document.getElementById('message-result-search').innerHTML = '';
            }
            if (Array.isArray(data)) {
                document.getElementById("cards-container").innerHTML = '<div class="col-md-12"><div class="d-flex justify-content-between"><p>Página ' + response.data.page_actual + ' de ' + response.data.page_count + '</p><p>' + response.data.total_row + ' Propiedades</p></div></div>';

                data.forEach(function(item) {
                    var housingTypeName = item.housingtype_name.toUpperCase();
                    var address = item.address.toUpperCase();

                    var col = document.createElement('div');
                    col.classList.add('col-md-4');
                    cardsContainer.appendChild(col);

                    var box = document.createElement('div');
                    box.classList.add('box', 'w-100', 'mb-4');
                    col.appendChild(box);

                    var top = document.createElement('div');
                    top.classList.add('top');
                    box.appendChild(top);

                    var img = document.createElement('img');
                    img.src = '/properties/RM00' + item.id_properties + '/graphic/' + item.image;
                    img.alt = '';
                    top.appendChild(img);

                    var span = document.createElement('span');
                    top.appendChild(span);

                    

                    item.rrss.forEach(rrss => {
                        var shareBtn = document.createElement('p');
                        shareBtn.classList.add('text-label-card');
                        shareBtn.innerHTML = '<a href="'+ rrss['link'] +'" class="text-white" target="_blank">'+ rrss['name'] +'</a>';
                        span.appendChild(shareBtn);
                    });



                    var shareBtn = document.createElement('p');
                    shareBtn.classList.add('text-label-card');
                    shareBtn.innerText = 'RM00' + item.id_properties;
                    span.appendChild(shareBtn);

                    var bottom = document.createElement('div');
                    bottom.classList.add('bottom');
                    box.appendChild(bottom);

                    var municipalityName = item.municipality_name;
                    var stateName = item.state_name;

                    var title = document.createElement('h3');
                    var link = document.createElement('a');
                    link.href = '/app/properties/view/' + item.id_properties;
                    link.textContent = housingTypeName + ' | ' + address;
                    title.appendChild(link);
                    bottom.appendChild(title);

                    var description = document.createElement('p');
                    description.innerText = "Municipio " + municipalityName + " del " + (stateName === 'Distrito Capital' ? '' : 'Estado ') + stateName + ".";
                    bottom.appendChild(description);

                    var agent = document.createElement('p');
                    agent.innerText = 'Asesor inmobiliario: ' + item.name_agent;
                    bottom.appendChild(agent);

                    var advants = document.createElement('div');
                    advants.classList.add('advants');
                    bottom.appendChild(advants);

                    var bedrooms = document.createElement('div');
                    bedrooms.innerHTML = '<span>Hab</span><div><i class="fas fa-th-large"></i><span>' + item.bedrooms + '</span></div>';
                    advants.appendChild(bedrooms);

                    var bathrooms = document.createElement('div');
                    bathrooms.innerHTML = '<span>Ba</span><div><i class="fas fa-shower"></i><span>' + item.bathrooms + '</span></div>';
                    advants.appendChild(bathrooms);

                    var garages = document.createElement('div');
                    garages.innerHTML = '<span>Pe</span><div><i class="fa fa-car"></i><span>' + item.garages + '</span></div>';
                    advants.appendChild(garages);

                    var construction = document.createElement('div');
                    construction.innerHTML = '<span>Cons</span><div><i class="fas fa-vector-square"></i><span>' + item.meters_construction + '<span>m2</span></span></div>';
                    advants.appendChild(construction);

                    var land = document.createElement('div');
                    land.innerHTML = '<span>Terr</span><div><i class="fas fa-vector-square"></i><span>' + item.meters_land + '<span>m2</span></span></div>';
                    advants.appendChild(land);

                    var hr = document.createElement('hr');
                    bottom.appendChild(hr);

                    var price = document.createElement('div');
                    price.classList.add('price');
                    bottom.appendChild(price);

                    var status = document.createElement('span');
                    status.innerText = item.businessmodel_name.toUpperCase();
                    price.appendChild(status);


                    if (item.price_additional > 0 && item.price > 0) {
                        var priceValue = document.createElement('span');
                        priceValue.innerText = '$' + item.price + ' / $' + item.price_additional;
                        price.appendChild(priceValue);
                    } else {
                        if (item.price > 0) {
                            var priceValue = document.createElement('span');
                            priceValue.innerText = '$' + item.price;
                            price.appendChild(priceValue);
                        }

                        if (item.price_additional > 0) {
                            var priceValue = document.createElement('span');
                            priceValue.innerText = '$' + item.price_additional;
                            price.appendChild(priceValue);
                        }
                    }

                    var created_at = document.createElement('div');
                    created_at.innerHTML = '<hr> Publicado el: ' + item.created_at + '';
                    bottom.appendChild(created_at);
                });
            } else {
                console.error('Data is not an array:', data);
            }

            // Parse the JSON and extract the HTML string
            var htmlString = response.data.links;

            // Create a new DOM Parser
            var parser = new DOMParser();

            // Convert the HTML string into a document object
            var doc = parser.parseFromString(htmlString, 'text/html');

            // Select all 'a' elements
            var aElements = doc.querySelectorAll('a');

            // Iterate over each 'a' element
            aElements.forEach(function(aElement) {
                // Create a new radio input element
                var radioInput = document.createElement('input');
                radioInput.type = 'radio';
                radioInput.name = 'pagination';
                radioInput.class = 'pagination-list';

                // Extract the page number from the href attribute
                var pageMatch = aElement.href.match(/page=(\d+)/);
                var pageNumber = pageMatch ? pageMatch[1] : '';
                radioInput.id = 'pagination_option_' + pageNumber;

                // Use the page number as the value for the radio input
                radioInput.value = pageNumber;

                // Create a label element for the radio input
                var label = document.createElement('label');
                label.appendChild(document.createTextNode(aElement.textContent.trim()));

                // If there is a 'span' element, use its text for the label
                var span = aElement.querySelector('span');
                if (span) {
                    label.textContent = span.textContent.trim();
                }

                // Insert the radio input and label into the DOM
                aElement.parentNode.insertBefore(radioInput, aElement);
                aElement.parentNode.insertBefore(label, aElement.nextSibling);

                // Remove the original 'a' element
                aElement.parentNode.removeChild(aElement);
            });

            // Insert the modified HTML back into the desired container
            document.getElementById('pagination-container').innerHTML = doc.body.innerHTML;



            document.querySelectorAll('input[name="pagination"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    page_ready = this.value;
                    document.getElementById('cards-container').innerHTML = '';
                    document.getElementById('pagination-container').innerHTML = '';
                    
                    preloader.removeAttribute("style");
                    get_properties(page_ready, rm, address, min_p, max_p, business_model, tp, min_c_m2, max_c_m2, agent);
                });
            });

            // Obtén todos los elementos con el nombre 'pagination'
            var radios = document.getElementsByName('pagination');

            // Usa forEach para iterar sobre cada elemento
            radios.forEach(function(radio, indice) {
                // Aquí puedes trabajar con cada input radio
                if (radio.value == page) {
                    // Por ejemplo, puedes imprimir su valor
                    // Obtén el elemento por su ID
                    var radioInput = document.getElementById('pagination_option_' + page);

                    // Marca el input radio
                    radioInput.checked = true;
                }
            });

        })
        .catch(function(error) {
            console.error(error);
            preloader.setAttribute("style", "display: none !important;");
        });

}

function searchFilter() {
    preloader.removeAttribute("style");
    var rm = $('#rm').val();
    var address = $('#address').val();
    var min_p = $('#min_p').val();
    var max_p = $('#max_p').val();
    var tp = $('#tp').val();
    var min_c_m2 = $('#min_c_m2').val();
    var max_c_m2 = $('#max_c_m2').val();
    var business_model = $('#business_model').val();
    var agent = $('#agent').val();
    document.getElementById('cards-container').innerHTML = '';
    document.getElementById('pagination-container').innerHTML = '';
    get_properties(1, rm, address, min_p, max_p, business_model, tp, min_c_m2, max_c_m2, agent);
}

get_properties(1, 0, '', 0, 0, 0, 0, 0, 0, 0);


var min_p = document.getElementById("min_p");
var max_p = document.getElementById("max_p");

$("#business_model").change(function() {
    if ($(this).val() != 0) {
        min_p.removeAttribute("disabled");
        max_p.removeAttribute("disabled");
    } else {
        $("#min_p").val("");
        $("#max_p").val("");
        min_p.setAttribute("disabled", "");
        max_p.setAttribute("disabled", "");
    }
});

min_p.setAttribute("disabled", "");
max_p.setAttribute("disabled", "");