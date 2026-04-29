const http = require('http');

const server = http.createServer((req, res) => {
    // Veridapt pake Token Header
    const auth = req.headers['authorization'];
    console.log(`[${new Date().toLocaleTimeString()}] 📡 Request masuk ke Mock Veridapt`);

    if (!auth || !auth.startsWith('Token token=')) {
        res.writeHead(401, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ errors: [{ message: "Unauthorized" }] }));
    }

    // GraphQL selalu pake POST
    if (req.method === 'POST') {
        let body = '';
        req.on('data', chunk => { body += chunk.toString(); });
        req.on('end', () => {
            const payload = JSON.parse(body);
            const query = payload.query || '';
            res.writeHead(200, { 'Content-Type': 'application/json' });

            // 1. Balasan untuk query GetFuelStations (Tangki Statis)
            if (query.includes('GetFuelStations')) {
                console.log('✅ Balesan: Data Tangki (GetFuelStations)');
                return res.end(JSON.stringify({
                    data: {
                        site: {
                            tanks: {
                                edges: [{
                                    node: {
                                        id: "TANK-001", code: "T01", name: "Main Fuel Station",
                                        gpsCoordinates: "-1.234567, 116.876543", // Format string "lat,lng"
                                        enabled: true, location: { code: "PIT-A" }
                                    }
                                }]
                            }
                        }
                    }
                }));
            }

            // 2. Balasan untuk query GetFuelTruckPositions (Truk Bensin)
            if (query.includes('GetFuelTruckPositions')) {
                console.log('✅ Balesan: Data Truk Bensin (GetFuelTruckPositions)');
                return res.end(JSON.stringify({
                    data: {
                        site: {
                            serviceTrucks: {
                                edges: [{
                                    node: {
                                        id: "TRK-001", equipmentId: "FT-9911", description: "Fuel Truck 1",
                                        gpsCoordinates: "-1.235000, 116.877000",
                                        status: "ACTIVE"
                                    }
                                }]
                            }
                        }
                    }
                }));
            }

            // Default balesan kosong kalau query gak match
            res.end(JSON.stringify({ data: null }));
        });
    } else {
        res.writeHead(404);
        res.end();
    }
});

// Kita set di port 4000 biar gak tabrakan sama Hexagon (3000)
server.listen(4000, '127.0.0.1', () => {
    console.log('⛽ Mock Veridapt API jalan di http://127.0.0.1:4000');
});
