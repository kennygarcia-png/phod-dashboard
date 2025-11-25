/**
 * GPS Helper Functions for PhOD
 */

/**
 * Get current GPS position
 * @param {function} successCallback - Called with {latitude, longitude, accuracy, timestamp}
 * @param {function} errorCallback - Called with error message
 */
function getCurrentGPS(successCallback, errorCallback) {
    if (!navigator.geolocation) {
        errorCallback('GPS not supported by this device');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            successCallback({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                timestamp: new Date(position.timestamp).toISOString()
            });
        },
        (error) => {
            const errors = {
                1: 'GPS permission denied',
                2: 'GPS position unavailable',
                3: 'GPS request timeout'
            };
            errorCallback(errors[error.code] || 'GPS error');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

/**
 * Auto-fill GPS fields in a form
 * @param {string} latFieldId - ID of latitude input field
 * @param {string} lonFieldId - ID of longitude input field  
 * @param {string} statusElementId - ID of element to show status messages
 */
function autoFillGPS(latFieldId, lonFieldId, statusElementId) {
    const statusEl = document.getElementById(statusElementId);
    
    if (statusEl) {
        statusEl.textContent = 'Getting GPS location...';
        statusEl.style.color = 'blue';
    }
    
    getCurrentGPS(
        (data) => {
            document.getElementById(latFieldId).value = data.latitude.toFixed(6);
            document.getElementById(lonFieldId).value = data.longitude.toFixed(6);
            
            if (statusEl) {
                statusEl.textContent = `GPS acquired (±${Math.round(data.accuracy)}m)`;
                statusEl.style.color = 'green';
            }
        },
        (error) => {
            if (statusEl) {
                statusEl.textContent = error;
                statusEl.style.color = 'red';
            }
        }
    );
}
