<!doctype html>
<html lang="en">

<head>
    <title>Title</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h2 class="mt-4 mb-4">ESMS Login Test Page</h2>

        <form id="urlForm">
            {{-- <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Current Host:</label>
                        <div class="form-control" readonly>{{ $host }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Current ip:</label>
                        <div class="form-control" readonly>{{ $ip }}</div>
                    </div>
                </div>
            </div> --}}
            <div class="form-group">
                <label>Current Host:</label>
                <div class="form-control" readonly>{{ $host }}</div>
            </div>
            <div class="form-group">
                <label for="authLevel">Auth Level:</label>
                <select class="form-control" id="authLevel" onchange="handleAuthLevelChange()">
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
            </div>

            <div class="form-group">
                <label for="regionCode">Region Code:</label>
                <select class="form-control" id="regionCode" onchange="generateURL()">
                    <option value="" disabled selected>Select Region Code</option>
                    <!-- Add options dynamically using JavaScript based on your data -->
                </select>
            </div>

            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" placeholder="Enter Name"
                    oninput="generateURL()">
            </div>

            <div class="form-group">
                <label for="generatedURL">Generated URL:</label>
                <input type="text" class="form-control" id="generatedURL" readonly>
            </div>

            <button type="button" class="btn btn-primary" onclick="redirectToURL()">Go to URL</button>
        </form>
    </div>

    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <script>
        // Sample region codes data, you may fetch it dynamically from your backend
        const regionCodes = {!! json_encode(
            \App\Models\County::select('id', 'name', 'code')->get()->all(),
        ) !!}

        function handleAuthLevelChange() {
            const authLevel = document.getElementById('authLevel').value;
            populateRegionCodeDropdown(authLevel);
            generateURL();
        }
        // Populate region code dropdown based on auth level
        function populateRegionCodeDropdown(authLevel) {
            const regionCodeDropdown = document.getElementById('regionCode');
            regionCodeDropdown.innerHTML = '<option value="" disabled selected>Select Region Code</option>';
            if (authLevel === '2') {
                for (const region of regionCodes) {
                    const option = document.createElement('option');
                    option.value = region.code;
                    option.text = `${region.code} - ${region.name}`;
                    regionCodeDropdown.appendChild(option);
                }
            }
        }

        function generateURL() {
            const authLevel = encodeURIComponent(document.getElementById('authLevel').value);
            const regionCode = encodeURIComponent(document.getElementById('regionCode').value);
            const name = encodeURIComponent(document.getElementById('name').value);
            const generatedURL = "https://epa.dynapelbs.tw/esms/login" + `?auth_level=${authLevel}&region_code=${regionCode}&name=${name}`;
            document.getElementById('generatedURL').value = generatedURL;
        }

        function redirectToURL() {
            const url = document.getElementById('generatedURL').value;
            window.location.href = url;
        }

        // Initial population of region code dropdown based on default auth level
        populateRegionCodeDropdown(document.getElementById('authLevel').value);
    </script>
</body>

</html>
