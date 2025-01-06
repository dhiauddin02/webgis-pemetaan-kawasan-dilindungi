<!DOCTYPE html>
<html>
<head>
    <title>Protected Areas Map with Draw and Edit Features</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #map {
            width: 100%;
            height: 90vh;
        }

        .action-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .delete-btn {
            background-color: #ff4444;
        }

        .cancel-btn {
            background-color: #6c757d;
        }

        .save-btn {
            background-color: #28a745;
        }

        #debug-info {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            max-width: 500px;
            max-height: 200px;
            overflow: auto;
            z-index: 1000;
        }

        .popup-content input {
            width: 100%;
            margin-top: 5px;
            padding: 5px;
        }

        #form-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }

        #form-container input {
            width: 100%;
            margin: 5px 0;
            padding: 5px;
        }

        .control-btn {
            position: fixed;
            top: 20px;
            left: 60px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <button id="add-polygon-btn" class="control-btn action-btn">Add New Area</button>
    <div id="map"></div>
    <div id="debug-info"></div>
    
    <div id="form-container">
        <h3>Add New Protected Area</h3>
        <input type="text" id="new-area-name" placeholder="Area Name">
        <input type="number" id="new-area-gis" placeholder="Area Size">
        <button class="action-btn save-btn" onclick="saveNewArea()">Save Area</button>
        <button class="action-btn cancel-btn" onclick="cancelNewArea()">Cancel</button>
    </div>

    <script>
        let drawnItems = new L.FeatureGroup();
        let drawControl;
        let currentDrawing = null;

        const map = L.map('map', {
            center: [0.582402, 100.858131],
            zoom: 6
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        map.addLayer(drawnItems);

        function showDebugInfo(message) {
            const debugDiv = document.getElementById('debug-info');
            debugDiv.innerHTML = message;
        }

        function initializeDrawControl() {
            drawControl = new L.Control.Draw({
                draw: {
                    polygon: true,
                    polyline: false,
                    circle: false,
                    rectangle: false,
                    marker: false,
                    circlemarker: false
                },
                edit: {
                    featureGroup: drawnItems
                }
            });
        }

        function loadWfsData() {
            const wfsDilindungi = 'http://localhost:8080/geoserver/ows?' +
                'service=WFS&version=1.0.0&request=GetFeature&' +
                'typeName=lindungsumatra:kawasan_dilindungi&' +
                'outputFormat=application/json&srsName=epsg:4326&' +
                `_=${Date.now()}`;

            showDebugInfo('Loading WFS data...');

            $.getJSON(wfsDilindungi)
                .then(function (res) {
                    showDebugInfo('WFS data loaded. Features count: ' + res.features.length);

                    map.eachLayer((layer) => {
                        if (layer instanceof L.GeoJSON) {
                            map.removeLayer(layer);
                        }
                    });

                    L.geoJson(res, {
                        onEachFeature: function (feature, layer) {
                            const fullFeatureId = feature.id.includes('.') ? feature.id : `kawasan_dilindungi.${feature.id}`;
                            layer.bindPopup(createPopupContent(feature, fullFeatureId));
                        }
                    }).addTo(map);
                })
                .catch(function (error) {
                    showDebugInfo('Error loading WFS data: ' + error.message);
                    console.error('Error loading WFS data:', error);
                });
        }

        function createPopupContent(feature, featureId) {
            const popupDiv = document.createElement('div');
            popupDiv.classList.add('popup-content');

            popupDiv.innerHTML = `
                <div>
                    <h3><input type="text" id="name-${featureId}" value="${feature.properties.name}" disabled></h3>
                    <p><strong>Feature ID:</strong> ${featureId}</p>
                    <p><strong>Area:</strong> <input type="text" id="area-${featureId}" value="${feature.properties.gis_area}" disabled></p>
                    <button class="action-btn edit-btn" onclick="toggleEdit('${featureId}')">Edit</button>
                    <button class="action-btn save-btn" onclick="saveChanges('${featureId}')" style="display: none;">Save</button>
                    <button class="action-btn delete-btn" onclick="deleteFeature('${featureId}')">Delete</button>
                </div>
            `;

            popupDiv.dataset.originalName = feature.properties.name;
            popupDiv.dataset.originalArea = feature.properties.gis_area;

            return popupDiv;
        }

        // Initialize drawing mode
        document.getElementById('add-polygon-btn').addEventListener('click', function() {
            if (!drawControl) {
                initializeDrawControl();
                map.addControl(drawControl);
            }
            // Clear any existing drawings
            drawnItems.clearLayers();
            document.getElementById('form-container').style.display = 'none';
        });

        // Handle drawn polygon
        map.on('draw:created', function(e) {
            currentDrawing = e.layer;
            drawnItems.addLayer(currentDrawing);
            document.getElementById('form-container').style.display = 'block';
        });

        function saveNewArea() {
            if (!currentDrawing) {
                alert('Please draw a polygon first');
                return;
            }

            const name = document.getElementById('new-area-name').value;
            const gisArea = document.getElementById('new-area-gis').value;

            if (!name || !gisArea) {
                alert('Please fill in all fields');
                return;
            }

            const coordinates = currentDrawing.getLatLngs()[0].map(latlng => [latlng.lng, latlng.lat]);
            // Close the polygon by adding the first point again
            coordinates.push(coordinates[0]);

            const workspace = 'lindungsumatra';
            const layerName = 'kawasan_dilindungi';

            const insertXml = `
                <wfs:Transaction
                    service="WFS"
                    version="1.0.0"
                    xmlns:wfs="http://www.opengis.net/wfs"
                    xmlns:gml="http://www.opengis.net/gml"
                    xmlns:${workspace}="http://www.openplans.org/${workspace}">
                    <wfs:Insert>
                        <${workspace}:${layerName}>
                            <${workspace}:name>${name}</${workspace}:name>
                            <${workspace}:gis_area>${gisArea}</${workspace}:gis_area>
                            <${workspace}:geom>
                                <gml:MultiPolygon srsName="EPSG:4326">
                                    <gml:polygonMember>
                                        <gml:Polygon>
                                            <gml:outerBoundaryIs>
                                                <gml:LinearRing>
                                                    <gml:coordinates>
                                                        ${coordinates.map(coord => coord.join(',')).join(' ')}
                                                    </gml:coordinates>
                                                </gml:LinearRing>
                                            </gml:outerBoundaryIs>
                                        </gml:Polygon>
                                    </gml:polygonMember>
                                </gml:MultiPolygon>
                            </${workspace}:geom>
                        </${workspace}:${layerName}>
                    </wfs:Insert>
                </wfs:Transaction>`;

            fetch('http://localhost:8080/geoserver/wfs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'text/xml',
                    'Accept': 'text/xml'
                },
                body: insertXml
            })
                .then(response => response.text())
                .then(data => {
                    console.log('WFS Insert Response:', data);
                    if (data.includes('SUCCESS')) {
                        alert('New area saved successfully');
                        cancelNewArea();
                        loadWfsData();
                    } else {
                        alert('Failed to save new area. Check console for details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the new area.');
                });
        }

        function cancelNewArea() {
            drawnItems.clearLayers();
            currentDrawing = null;
            document.getElementById('form-container').style.display = 'none';
            document.getElementById('new-area-name').value = '';
            document.getElementById('new-area-gis').value = '';
            if (drawControl) {
                map.removeControl(drawControl);
                drawControl = null;
            }
        }

        // Existing functions remain the same
        function toggleEdit(featureId) {
            const nameInput = document.getElementById(`name-${featureId}`);
            const areaInput = document.getElementById(`area-${featureId}`);
            const editButton = document.querySelector(`.edit-btn[onclick="toggleEdit('${featureId}')"]`);
            const saveButton = document.querySelector(`.save-btn[onclick="saveChanges('${featureId}')"]`);
            const deleteButton = document.querySelector(`.delete-btn[onclick="deleteFeature('${featureId}')"]`);

            nameInput.disabled = false;
            areaInput.disabled = false;
            editButton.style.display = 'none';
            saveButton.style.display = 'inline-block';
            
            deleteButton.className = 'action-btn cancel-btn';
            deleteButton.textContent = 'Cancel';
            deleteButton.setAttribute('onclick', `cancelEdit('${featureId}')`);
        }

        function cancelEdit(featureId) {
            const nameInput = document.getElementById(`name-${featureId}`);
            const areaInput = document.getElementById(`area-${featureId}`);
            const editButton = document.querySelector(`.edit-btn[onclick="toggleEdit('${featureId}')"]`);
            const saveButton = document.querySelector(`.save-btn[onclick="saveChanges('${featureId}')"]`);
            const cancelButton = document.querySelector(`.cancel-btn[onclick="cancelEdit('${featureId}')"]`);
            const popupContent = nameInput.closest('.popup-content');

            nameInput.value = popupContent.dataset.originalName;
            areaInput.value = popupContent.dataset.originalArea;

            nameInput.disabled = true;
            areaInput.disabled = true;
            editButton.style.display = 'inline-block';
            saveButton.style.display = 'none';

            cancelButton.className = 'action-btn delete-btn';
            cancelButton.textContent = 'Delete';
            cancelButton.setAttribute('onclick', `deleteFeature('${featureId}')`);
        }

        function saveChanges(featureId) {
            // Existing saveChanges function remains the same
        }

        function deleteFeature(featureId) {
            // Existing deleteFeature function remains the same
        }

        loadWfsData();
    </script>
</body>
</html>