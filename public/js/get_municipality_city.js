$(document).ready(function() {
    $('#state').change(function() {
        var val_state = $(this).val();
        if (val_state) {
            $("#municipality").empty();
            $("#municipality").removeAttr("disabled");
            // Realizar la solicitud AJAX al servidor
            axios.get(getMunicipality + val_state)
                .then(function(response) {
                    var data = response.data;
                    $('<option>').val('').text(data['municipality_data'].length + ' Opciones').appendTo('#municipality');
                    $.each(data['municipality_data'], function(index, option) {
                        $('<option>').val(option.id).text(option.name).appendTo('#municipality');
                    });
                })
                .catch(function(error) {
                    console.log('Error al obtener los datos del servidor');
                });
        } else {
            $("#municipality").empty();
            $('<option>').val('').text('Selecciona un estado').appendTo('#municipality');
            $("#municipality").attr("disabled", "");
        }
    });
    $('#municipality').change(function() {
        var val_municipality = $(this).val();
        if (val_municipality) {
            $("#city").empty();
            $("#city").removeAttr("disabled");
            $("#state").attr("disabled", "");
            // Realizar la solicitud AJAX al servidor
            axios.get(getCity + val_municipality)
                .then(function(response) {
                    var data = response.data;
                    $('<option>').val('').text(data['city_data'].length + ' Opciones').appendTo('#city');
                    $.each(data['city_data'], function(index, option) {
                        $('<option>').val(option.id).text(option.name).appendTo('#city');
                    });
                })
                .catch(function(error) {
                    console.log('Error al obtener los datos del servidor');
                });
        } else {
            $("#city").empty();
            $('<option>').val('').text('Selecciona un municipio').appendTo('#city');
            $("#city").attr("disabled", "");
            $("#state").removeAttr("disabled");
        }
    });
    $('#city').change(function() {
        var val_city = $(this).val();
        if (val_city) {
            $("#municipality").attr("disabled", "");
        } else {
            $("#municipality").removeAttr("disabled");
        }
    });
});
