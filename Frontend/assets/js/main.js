// Check if map element exists
if (document.getElementById('map')) {
    // Load map-related scripts dynamically
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js';
    document.body.appendChild(script);
    
    // Load our map.js after Leaflet is loaded
    script.onload = () => {
        const mapScript = document.createElement('script');
        mapScript.src = 'js/map.js';
        document.body.appendChild(mapScript);
    };
}

// Always load these
const notificationsScript = document.createElement('script');
notificationsScript.src = 'js/notifications.js';
document.body.appendChild(notificationsScript);

const formValidatorScript = document.createElement('script');
formValidatorScript.src = 'js/form-validator.js';
document.body.appendChild(formValidatorScript);