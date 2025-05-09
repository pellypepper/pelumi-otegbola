
$(document).ready(function() {
    if ($('#preloader').length) {
        $('#preloader').delay(1000).fadeOut('slow', function() {
            $(this).remove();
        });
    }
    
    setupEventListeners();
    
    // Initialize results area
    $('#resultsContent').html('<p>Select an API and submit a query to see results here.</p>');
    
    // display client-side
    $('#clientModeInfo').show();
});

// Event listeners setup
function setupEventListeners() {
    
    // Nearby Name API button
    $('#findNearbyBtn').on('click', function() {
        const lat = $('#lat1').val().trim();
        const lng = $('#lng1').val().trim();
        
        if (!lat || !lng) {
            showError('Please enter both latitude and longitude');
            return;
        }
        
        callGeoNamesDirectly('findNearbyPlaceName', {
            lat: lat,
            lng: lng
        });
    });
    
    // Country Info API button
    $('#countryInfoBtn').on('click', function() {
        const country = $('#country').val().trim();
        const lang = $('#lang').val().trim() || 'en';
        
        if (!country) {
            showError('Please enter a country code');
            return;
        }
        
        callGeoNamesDirectly('countryInfo', {
            country: country,
            lang: lang
        });
    });
    
    // Search API button
    $('#searchBtn').on('click', function() {
        const query = $('#q').val().trim();
        const maxRows = $('#maxRows').val() || 10;
        
        if (!query) {
            showError('Please enter a search query');
            return;
        }
        
        callGeoNamesDirectly('search', {
            q: query,
            maxRows: maxRows
        });
    });
}

// API Calls
function callGeoNamesDirectly(apiType, params) {
    $('#resultsContent').html('<p>Loading...</p>');
    
    const username = 'ppeliance';
    let url = '';
    
    switch (apiType) {
        case 'findNearbyPlaceName':
            url = `https://secure.geonames.org/findNearbyPlaceNameJSON?formatted=true&lat=${params.lat}&lng=${params.lng}&username=${username}&style=full`;
            break;
        
        case 'countryInfo':
            url = `https://secure.geonames.org/countryInfoJSON?formatted=true&lang=${params.lang}&country=${params.country}&username=${username}&style=full`;
            break;
        
        case 'search':
            url = `https://secure.geonames.org/searchJSON?formatted=true&q=${encodeURIComponent(params.q)}&maxRows=${params.maxRows}&username=${username}&style=full`;
            break;
    }
    
    // JSONP callback
    url += '&callback=?';
    
    //bypass CORS restrictions
    $.ajax({
        url: url,
        dataType: 'jsonp',
        success: function(response) {
          
            
            // Format response for display
            const formattedResponse = {
                status: {
                    code: 200,
                    name: "ok",
                    description: "success",
                    returnedIn: "client-side"
                },
                data: response.geonames || response
            };
            
            displayResults(formattedResponse);
        },
        error: function(jqXHR, textStatus, errorThrown) {
         
            showError('Error: ' + textStatus + (errorThrown ? ' - ' + errorThrown : '') + 
                      '<br>Please check your internet connection and try again.');
        }
    });
}

// display results
function displayResults(response) {

    // Get results 
    const resultsContent = $('#resultsContent');
    
    // Clear previous content
    resultsContent.empty();
    
    // Format JSON 
    const formattedJson = JSON.stringify(response, null, 4);
    
    // Create and append
    const preElement = $('<pre></pre>').text(formattedJson);
    resultsContent.append(preElement);
    
    // Scroll to results
    $('html, body').animate({
        scrollTop: $('#results').offset().top
    }, 500);
}

// display error messages
function showError(message) {
    $('#resultsContent').html('<div style="color: red; padding: 10px;">' + message + '</div>');
}