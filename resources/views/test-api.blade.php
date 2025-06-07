<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Testing - VAMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">API Testing Interface</h1>
            
            <!-- API Key Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">API Key</h2>
                <div class="flex items-center space-x-4">
                    <input 
                        type="text" 
                        id="apiKey" 
                        placeholder="Enter your API key here"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button 
                        onclick="testConnection()"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                    >
                        Test Connection
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    Get your API key from the <a href="{{ route('admin.users.index') }}" class="text-blue-500 hover:underline">Users Management</a> page.
                </p>
            </div>

            <!-- Test Endpoints -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Test Endpoints</h2>
                
                <div class="space-y-4">
                    <!-- Get Albums -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium mb-2">GET /api/public/albums/by-title/{title}</h3>
                        <div class="flex items-center space-x-4">
                            <input 
                                type="text" 
                                id="albumTitle" 
                                placeholder="Album title"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md"
                            />
                            <button 
                                onclick="testGetAlbum()"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                            >
                                Test
                            </button>
                        </div>
                    </div>

                    <!-- Get Mosaics -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium mb-2">GET /api/public/mosaics/by-title/{title}</h3>
                        <div class="flex items-center space-x-4">
                            <input 
                                type="text" 
                                id="mosaicTitle" 
                                placeholder="Mosaic title"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md"
                            />
                            <button 
                                onclick="testGetMosaic()"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                            >
                                Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Response</h2>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-auto max-h-96">
                    <pre id="response">No requests made yet...</pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '{{ url("/") }}';
        
        function getApiKey() {
            return document.getElementById('apiKey').value.trim();
        }
        
        function updateResponse(data) {
            document.getElementById('response').textContent = JSON.stringify(data, null, 2);
        }
        
        function showError(message) {
            updateResponse({ error: message });
        }
        
        async function makeApiRequest(endpoint, method = 'GET') {
            const apiKey = getApiKey();
            if (!apiKey) {
                showError('Please enter an API key');
                return;
            }
            
            try {
                const response = await fetch(`${baseUrl}/api${endpoint}`, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    updateResponse({
                        status: response.status,
                        statusText: response.statusText,
                        error: data
                    });
                } else {
                    updateResponse({
                        status: response.status,
                        data: data
                    });
                }
            } catch (error) {
                showError(`Network error: ${error.message}`);
            }
        }
        
        async function testConnection() {
            await makeApiRequest('/user');
        }
        
        async function testGetAlbum() {
            const title = document.getElementById('albumTitle').value.trim();
            if (!title) {
                showError('Please enter an album title');
                return;
            }
            await makeApiRequest(`/public/albums/by-title/${encodeURIComponent(title)}`);
        }
        
        async function testGetMosaic() {
            const title = document.getElementById('mosaicTitle').value.trim();
            if (!title) {
                showError('Please enter a mosaic title');
                return;
            }
            await makeApiRequest(`/public/mosaics/by-title/${encodeURIComponent(title)}`);
        }
    </script>
</body>
</html> 