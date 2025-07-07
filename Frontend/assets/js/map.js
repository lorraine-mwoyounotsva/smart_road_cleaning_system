// Initialize the map with Windhoek coordinates
function initMap() {
    const map = L.map('map').setView([-22.5609, 17.0658], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    return map;
}

// Load and display routes on the map
function loadRoutes(map) {
    fetch('get_routes.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(route => {
                const coordinates = JSON.parse(route.coordinates);
                const polyline = L.polyline(coordinates, {
                    color: getStatusColor(route.status),
                    weight: 5,
                    opacity: 0.7
                }).addTo(map);
                
                polyline.bindPopup(`
                    <b>${route.name}</b><br>
                    Status: ${route.status}<br>
                    ${route.cleaner_name ? 'Cleaner: ' + route.cleaner_name : ''}
                `);
            });
            
            if (data.length > 0) {
                const bounds = data.flatMap(route => JSON.parse(route.coordinates));
                map.fitBounds(bounds);
            }
        });
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'green';
        case 'in-progress': return 'orange';
        case 'missed': return 'red';
        default: return 'gray';
    }
}

// Auto-refresh data every 30 seconds
function setupAutoRefresh(map) {
    setInterval(() => {
        map.eachLayer(layer => {
            if (layer instanceof L.Polyline) {
                map.removeLayer(layer);
            }
        });
        loadRoutes(map);
    }, 30000);
}

// Initialize map when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('map')) {
        const map = initMap();
        loadRoutes(map);
        setupAutoRefresh(map);
    }
});