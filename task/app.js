// Wait for page to fully load
$(document).ready(function() {
    // Remove preloader once page is loaded
    if ($('#preloader').length) {
        $('#preloader').delay(1000).fadeOut('slow', function() {
            $(this).remove();
        });
    }
    

    setupEventListeners();
    
    // Initialize results area
    $('#resultsContent').html('<p>Select an API and submit a query to see results here.</p>');
});

// Setup for event listeners
function setupEventListeners() {
    
    //  Nearby  Name API button
    $('#findNearbyBtn').on('click', function() {
        const lat = $('#lat1').val().trim();
        const lng = $('#lng1').val().trim();
        
        if (!lat || !lng) {
            showError('Please enter both latitude and longitude');
            return;
        }
        
        callApi('findNearbyPlaceName', {
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
        
        callApi('countryInfo', {
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
        
        callApi('search', {
            q: query,
            maxRows: maxRows
        });
    });
}

// API handler
function callApi(apiType, params) {

    $('#resultsContent').html('<p>Loading...</p>');
    
    // Add API type to params
    params.apiType = apiType;
    
    console.log('Calling API:', apiType, params);
    
    //  AJAX request
    $.ajax({
        url: 'api.php',
        type: 'GET',
        dataType: 'json',
        data: params,
        success: function(response) {
            console.log('API Response:', response);
            displayResults(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error:', textStatus, errorThrown);
            showError('Error: ' + textStatus + ' - ' + errorThrown);
        }
    });
}

//display API results
function displayResults(response) {
    
    // Get the results element
    const resultsContent = $('#resultsContent');
    
    // Clear previous content
    resultsContent.empty();
    
    // Format JSON for display
    const formattedJson = JSON.stringify(response, null, 4);
    
    // Create and append the pre element
    const preElement = $('<pre></pre>').text(formattedJson);
    resultsContent.append(preElement);
    
    // Scroll to results
    $('html, body').animate({
        scrollTop: $('#results').offset().top
    }, 500);
}

// Function to display error messages
function showError(message) {
    $('#resultsContent').html('<div style="color: red; padding: 10px;">' + message + '</div>');
}