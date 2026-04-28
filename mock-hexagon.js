const http = require('http');
const url = require('url');

const server = http.createServer((req, res) => {
    const auth = req.headers['authorization'];
    const expectedAuth = 'Basic ' + Buffer.from('admin:secret').toString('base64');

    // Ambil query params (buat liat ID truk yang diminta)
    const queryObject = url.parse(req.url, true).query;
    const deviceId = queryObject.id || 'unknown';

    console.log(`[${new Date().toLocaleTimeString()}] Request for: ${deviceId}`);

    if (!auth || auth !== expectedAuth) {
        res.writeHead(401, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'Unauthorized' }));
    }

    if (req.url.includes('/Traveling')) {
        // LOGIC BIAR GAK TUMPUK:
        // Kalau ID-nya VEH-3515, kita geser koordinatnya dikit
        let lat = 3729811;
        let lon = 423569710;

        if (deviceId === 'HEX-3515-XYZ') {
            lat += 5000; // Geser agak jauh ke utara
            lon += 5000; // Geser agak jauh ke timur
        }

        res.writeHead(200, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify([{
            equipment: deviceId,
            latitude: lat,
            longitude: lon,
            velocity: deviceId === 'HEX-3515-XYZ' ? 25 : 45, // Speed beda dikit
            heading: 180,
            timestamp: new Date().toISOString()
        }]));
    }

    res.writeHead(404);
    res.end();
});

server.listen(3000, '127.0.0.1', () => {
    console.log('🚀 Mock Hexagon DYNAMIC jalan di http://127.0.0.1:3000');
});
