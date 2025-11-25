<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Test - PhOD</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div class="container" style="padding: 20px;">
        <h1>GPS Test</h1>
        <button onclick="testGPS()" style="padding: 15px 30px; font-size: 18px;">
            📍 Get GPS Location
        </button>
        
        <div id="result" style="margin-top: 20px; padding: 20px; background: white; border-radius: 10px;"></div>
    </div>
    
    <script src="/js/gps.js"></script>
    <script>
        function testGPS() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p style="color: blue;">Getting GPS location...</p>';
            
            getCurrentGPS(
                (data) => {
                    resultDiv.innerHTML = `
                        <h2 style="color: green;">GPS Success!</h2>
                        <p><strong>Latitude:</strong> ${data.latitude.toFixed(6)}</p>
                        <p><strong>Longitude:</strong> ${data.longitude.toFixed(6)}</p>
                        <p><strong>Accuracy:</strong> ±${Math.round(data.accuracy)} meters</p>
                        <p><strong>Timestamp:</strong> ${new Date(data.timestamp).toLocaleString()}</p>
                    `;
                },
                (error) => {
                    resultDiv.innerHTML = `
                        <h2 style="color: red;">GPS Failed</h2>
                        <p>${error}</p>
                        <p><small>Make sure location permissions are enabled for this site.</small></p>
                    `;
                }
            );
        }
    </script>
</body>
</html>
