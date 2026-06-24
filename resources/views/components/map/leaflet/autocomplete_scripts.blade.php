<script src="{{ asset('backend/js/autocomplete.min.js') }}"></script>
<script>
    new Autocomplete("leaflet_search", {
        // default selects the first item in
        // the list of results
        selectFirst: true,

        // The number of characters entered should start searching
        howManyCharacters: 3,

        // onSearch
        onSearch: ({
            currentValue
        }) => {
            // You can also use static files
            // const api = '../static/search.json'
            const countryName = "<?php echo session()->get('selected_country'); ?>";
            if (countryName) {
                var api =
                `https://nominatim.openstreetmap.org/search?format=geojson&limit=5&city=${encodeURI(currentValue)}&country=${encodeURI(countryName)}`;
            }else{
                var api =
                `https://nominatim.openstreetmap.org/search?format=geojson&limit=5&city=${encodeURI(currentValue)}`;
            }

            return new Promise((resolve) => {
                fetch(api)
                    .then((response) => response.json())
                    .then((data) => {
                        resolve(data.features);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
        // nominatim GeoJSON format parse this part turns json into the list of
        // records that appears when you type.
        onResults: ({
            currentValue,
            matches,
            template
        }) => {

            const regex = new RegExp(currentValue, "gi");

            // if the result returns 0 we
            // show the no results element
            return matches === 0 ?
                template :
                matches
                .map((element) => {

                    let full_address = element.properties.display_name;

                    let split_string = full_address.split(', ');
                    let country = split_string.pop();

                    return `
               <li class="loupe">
                   <p>
                    ${element.properties.name} , ${country}
                   </p>
               </li> `;
                })
                .join("");
        },

        // we add an action to enter or click
        onSubmit: ({
            object
        }) => {
            // console.log(object)
            // remove all layers from the map
            leaflet_map.eachLayer(function(layer) {
                if (!!layer.toGeoJSON) {
                    leaflet_map.removeLayer(layer);
                }
            });

            const {
                display_name
            } = object.properties;
            const [lng, lat] = object.geometry.coordinates;

            //    const marker = L.marker([lat, lng], {
            //         title: display_name,
            //    });

            //    marker.addTo(leaflet_map).bindPopup(display_name);

            leaflet_map.setView([lat, lng], 8);
        },

        // get index and data from li element after
        // hovering over li with the mouse or using
        // arrow keys ↓ | ↑
        onSelectedItem: ({
            index,
            element,
            object
        }) => {

            //    console.log(object.properties)
            //    console.log(object.geometry.coordinates)
            let leaf_lon = object.geometry.coordinates[0]
            let leaf_lat = object.geometry.coordinates[1]

            let full_address = object.properties.display_name;
            let city = object.properties.name;

            $('.leaf_lon').val(leaf_lon);
            $('.leaf_lat').val(leaf_lat);

            let split_string = full_address.split(', ');
            let country = split_string.pop();

            $('.city').val(city);
            $('.country').val(country);

            // var form = new FormData();
            // form.append('lat', leaf_lat);
            // form.append('lng', leaf_lon);
            // form.append('country', country);
            // form.append('place', full_address);

            // axios.post('/set/session', form)
            // .then((res) => {
            //     // alert()
            //     // console.log(res.data);
            //     // toastr.success("Location Saved", 'Success!');
            // })
            // .catch((e) => {
            //     toastr.error("Something Wrong", 'Error!');
            // });
        },

        // the method presents no results element
        noResults: ({
                currentValue,
                template
            }) =>
            template(`<li>No results found: "${currentValue}"</li>`),
    });
</script>