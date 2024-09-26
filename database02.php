<?php
// Bind to a port for Render Web Service
$port = getenv('PORT'); // Render will provide the PORT environment variable

// Start a simple HTTP server
$socket = stream_socket_server("tcp://0.0.0.0:$port", $errno, $errstr);

if (!$socket) {
    echo "Error: Unable to create socket: $errstr ($errno)\n";
    exit(1);
}

echo "Server running on port $port\n";

// Simple HTTP response for incoming requests
function handleRequest($client) {
    if (is_resource($client)) {
        fwrite($client, "HTTP/1.1 200 OK\r\n");
        fwrite($client, "Content-Type: text/plain\r\n");
        fwrite($client, "Connection: close\r\n");
        fwrite($client, "\r\n");
        fwrite($client, "Background process running as a web service!\n");
        fclose($client);
    }
}

// Keep accepting incoming connections
while ($client = @stream_socket_accept($socket, -1)) {
    handleRequest($client);
}

ignore_user_abort(true);

// --- Your existing background process code starts here ---

// Get environment variables for database connection
$servername = getenv('DB_SERVERNAME');  
$username = getenv('DB_USERNAME');         
$password = getenv('DB_PASSWORD');   
$dbname = getenv('DB_NAME');            
$port = getenv('DB_PORT');

// KoboToolbox API details
$kobo_api_url = 'https://eu.kobotoolbox.org/api/v2/assets/ayR6wufB7edf9Ft8AFNVPi/data/?format=json&_last_updated__gt=2024-09-18+05%3A49%3A09';
$kobo_token = 'ea97948efb2a6f133463d617277b69caff728630';  

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the latest submission time from the database
$sql_last_update = "SELECT MAX(submission_time) as last_updated_time FROM kobo_data02_1";
$result = $conn->query($sql_last_update);
$last_updated_time = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_updated_time = $row['last_updated_time'];
}

// Log the last updated time
file_put_contents('debug_log.txt', "Last Updated Time: $last_updated_time\n", FILE_APPEND);

// Append the last updated time to the KoboToolbox API URL
if ($last_updated_time) {
    $kobo_api_url .= '&_last_updated__gt=' . urlencode($last_updated_time);
}

// Log the final API URL
file_put_contents('debug_log.txt', "API URL: $kobo_api_url\n", FILE_APPEND);

// Set up the cURL request to fetch data from KoboToolbox
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $kobo_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $kobo_token,
]);

// Execute the cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Log the response from KoboToolbox
file_put_contents('kobo_response_log.txt', print_r($data, true), FILE_APPEND);

// Check if data is received
if ($http_code == 200 && isset($data['results'])) {
    $counter = 0;

    // Prepare the statement for inserting or updating the data
    $stmt_insert = $conn->prepare("INSERT INTO kobo_data02_1 (
        submission_id, tstart, tend, ttoday, username, phonenumber, deviceid, name_collection, 
        date_interview, name_interview, sex_interview, name_respon, province, district, commune, village,
        water_polution, water_polution_des, land_overlap, land_overlap_des, land_erosion, land_ero_des,
        land_by_waste, land_by_waste_des, com_consultant, com_inform, com_consult_community, allowance_from_community,
        situation, relocation_by_forces, relocation_des, illegal_activities, illegal_act_des, com_license,
        rapes_six_months, rapes_desciption, murder_six_months, murder_description, laterite_in_water,
        laterite_in_water_des, animal_lost_or_deaths, animal_lost_or_deaths_des, migration, prostitution, women_work,
        comments, ifinish, instance_id, submission_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        tstart=VALUES(tstart), tend=VALUES(tend), ttoday=VALUES(ttoday), username=VALUES(username), 
        phonenumber=VALUES(phonenumber), deviceid=VALUES(deviceid), name_collection=VALUES(name_collection),
        date_interview=VALUES(date_interview), name_interview=VALUES(name_interview), sex_interview=VALUES(sex_interview), 
        name_respon=VALUES(name_respon), province=VALUES(province), district=VALUES(district), 
        commune=VALUES(commune), village=VALUES(village), water_polution=VALUES(water_polution), 
        water_polution_des=VALUES(water_polution_des), land_overlap=VALUES(land_overlap), 
        land_overlap_des=VALUES(land_overlap_des), land_erosion=VALUES(land_erosion), land_ero_des=VALUES(land_ero_des), 
        land_by_waste=VALUES(land_by_waste), land_by_waste_des=VALUES(land_by_waste_des), com_consultant=VALUES(com_consultant), 
        com_inform=VALUES(com_inform), com_consult_community=VALUES(com_consult_community), 
        allowance_from_community=VALUES(allowance_from_community), situation=VALUES(situation), 
        relocation_by_forces=VALUES(relocation_by_forces), relocation_des=VALUES(relocation_des), 
        illegal_activities=VALUES(illegal_activities), illegal_act_des=VALUES(illegal_act_des), 
        com_license=VALUES(com_license), rapes_six_months=VALUES(rapes_six_months), rapes_desciption=VALUES(rapes_desciption), 
        murder_six_months=VALUES(murder_six_months), murder_description=VALUES(murder_description), 
        laterite_in_water=VALUES(laterite_in_water), laterite_in_water_des=VALUES(laterite_in_water_des), 
        animal_lost_or_deaths=VALUES(animal_lost_or_deaths), animal_lost_or_deaths_des=VALUES(animal_lost_or_deaths_des), 
        migration=VALUES(migration), prostitution=VALUES(prostitution), women_work=VALUES(women_work), 
        comments=VALUES(comments), ifinish=VALUES(ifinish), instance_id=VALUES(instance_id), 
        submission_time=VALUES(submission_time)");

    foreach ($data['results'] as $record) {
        if ($counter >= 100) break; // Optional limit to process only a certain number of records
        $counter++;

        // Retrieve fields from KoboToolbox JSON data
        $submission_id = $record['_id'];
        $tstart = $record['Tstart'];
        $tend = $record['Tend'];
        $ttoday = $record['Ttoday'];
        $username = $record['username'];
        $phonenumber = $record['phonenumber'];
        $deviceid = $record['deviceid'];
        $name_collection = $record['g_intro/name_collection'];
        $date_interview = $record['g_intro/date_interview'];
        $name_interview = $record['g_intro/name_interview'];
        $sex_interview = $record['g_intro/sex_interview'];
        $name_respon = $record['g_intro/name_respon'];
        $province = $record['g_intro/province'];
        $district = $record['g_intro/district'];
        $commune = $record['g_intro/commune'];
        $village = $record['g_intro/village'];
        $water_polution = $record['g_envirog_natural/q_0201'];
        $water_polution_des = $record['g_envirog_natural/q_0201txt'];
        $land_overlap = $record['g_envirog_natural/q_0202'];
        $land_overlap_des = $record['g_envirog_natural/q_0202txt'];
        $land_erosion = $record['g_envirog_natural/q_0203'];
        $land_ero_des = $record['g_envirog_natural/q_0203txt'];
        $land_by_waste = $record['g_envirog_natural/q_0204'];
        $land_by_waste_des = $record['g_envirog_natural/q_0204txt'];
        $com_consultant = $record['g_violation/q_0301a'];
        $com_inform = $record['g_violation/q_0301_b'];
        $com_consult_community = $record['g_violation/q_0301c'];
        $allowance_from_community = $record['g_violation/q0301c_yes'];
        $situation = $record['g_violation/q0301txt'];
        $relocation_by_forces = $record['q_0302'];
        $relocation_des = $record['q_0302txt'];
        $illegal_activities = $record['q_0303'];
        $illegal_act_des = $record['q_0303txt'];
        $com_license = $record['q0304'];
        $rapes_six_months = $record['g_q0401/q_0401a'];
        $rapes_desciption = $record['g_q0401/q_0401atxt'];
        $murder_six_months = $record['g_q0401/q_0401b'];
        $murder_description = $record['g_q0401/q_0401btxt'];
        $laterite_in_water = $record['g_q0401/q_0402'];
        $laterite_in_water_des = $record['g_q0401/q_0402txt'];
        $animal_lost_or_deaths = $record['g_q_0501/q_0501'];
        $animal_lost_or_deaths_des = $record['g_q_0501/q_0501txt'];
        $migration = $record['g_q_0501/q0502'];
        $prostitution = $record['g_q_0501/q0503'];
        $women_work = $record['q06women'];
        $comments = $record['comments'];
        $ifinish = $record['i_finish'];
        $instance_id = $record['meta/instanceID'];
        $submission_time = $record['_submission_time'];

        // Bind parameters and execute the insert/update query
        $stmt_insert->bind_param("isssssssssssssssss", $submission_id, $tstart, $tend, $ttoday, $username, $phonenumber, $deviceid, $name_collection,
            $date_interview, $name_interview, $sex_interview, $name_respon, $province, $district, $commune, $village,
            $water_polution, $water_polution_des, $land_overlap, $land_overlap_des, $land_erosion, $land_ero_des, $land_by_waste,
            $land_by_waste_des, $com_consultant, $com_inform, $com_consult_community, $allowance_from_community, $situation,
            $relocation_by_forces, $relocation_des, $illegal_activities, $illegal_act_des, $com_license, $rapes_six_months,
            $rapes_desciption, $murder_six_months, $murder_description, $laterite_in_water, $laterite_in_water_des, $animal_lost_or_deaths,
            $animal_lost_or_deaths_des, $migration, $prostitution, $women_work, $comments, $ifinish, $instance_id, $submission_time);
        $stmt_insert->execute();
    }
    $stmt_insert->close();
} else {
    echo "Failed to retrieve data from KoboToolbox. HTTP Code: " . $http_code;
    file_put_contents('error_log.txt', "Kobo API response: " . $response . "\n", FILE_APPEND); 
}

// Close the MySQL connection
$conn->close();
?>
