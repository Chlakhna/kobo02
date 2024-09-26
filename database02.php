<?php
// Database connection details
$servername = 'mysql-2b9c8eba-chlakhna702-5683.h.aivencloud.com';  
$username = 'avnadmin';         
$password = 'AVNS_EMM0l9B433aCexcaKkt';   
$dbname = 'dpa';            
$port = 10605;

$kobo_api_url = 'https://eu.kobotoolbox.org/api/v2/assets/ayR6wufB7edf9Ft8AFNVPi/data/?format=json&_last_updated__gt=2024-09-18+05%3A49%3A09';
$kobo_token = 'ea97948efb2a6f133463d617277b69caff728630';  

$conn = new mysqli($servername, $username, $password, $dbname, $port);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected to the database successfully!<br>";
}

$sql_last_update = "SELECT MAX(submission_time) as last_updated_time FROM kobo_data02";
$result = $conn->query($sql_last_update);
$last_updated_time = null;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_updated_time = $row['last_updated_time'];
}
if ($last_updated_time) {
    $kobo_api_url .= '&_last_updated__gt=' . urlencode($last_updated_time);
    echo "Fetching data submitted after: " . $last_updated_time . "<br>";
} else {
    echo "Fetching all data since no previous submissions found.<br>";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $kobo_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $kobo_token,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($http_code == 200 && isset($data['results'])) {
    echo "Successfully fetched data from KoboToolbox.<br>";
    
    // Print the API response for debugging
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    foreach ($data['results'] as $record) {
        // Retrieve fields from KoboToolbox JSON data
        $submission_id = mysqli_real_escape_string($conn, $record['_id']);
        $tstart = mysqli_real_escape_string($conn, $record['Tstart']);
        $tend = mysqli_real_escape_string($conn, $record['Tend']);
        $ttoday = mysqli_real_escape_string($conn, $record['Ttoday']);
        $username = mysqli_real_escape_string($conn, $record['username']);
        $phonenumber = mysqli_real_escape_string($conn, $record['phonenumber']);
        $deviceid = mysqli_real_escape_string($conn, $record['deviceid']);
        $name_collection = mysqli_real_escape_string($conn, $record['g_intro/name_collection']);
        $date_interview = mysqli_real_escape_string($conn, $record['g_intro/date_interview']);
        $name_interview = mysqli_real_escape_string($conn, $record['g_intro/name_interview']);
        $sex_interview = mysqli_real_escape_string($conn, $record['g_intro/sex_interview']);
        $name_respon = mysqli_real_escape_string($conn, $record['g_intro/name_respon']);
        $province = mysqli_real_escape_string($conn, $record['g_intro/province']);
        $district = mysqli_real_escape_string($conn, $record['g_intro/district']);
        $commune = mysqli_real_escape_string($conn, $record['g_intro/commune']);
        $village = mysqli_real_escape_string($conn, $record['g_intro/village']);
        $water_polution = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0201']);
        $water_polution_des = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0201txt']);
        $land_overlap = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0202']);
        $land_overlap_des = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0202txt']);
        $land_erosion = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0203']);
        $land_ero_des = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0203txt']);
        $land_by_waste = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0204']);
        $land_by_waste_des = mysqli_real_escape_string($conn, $record['g_envirog_natural/q_0204txt']);
        $com_consultant = mysqli_real_escape_string($conn, $record['g_violation/q_0301a']);
        $com_inform = mysqli_real_escape_string($conn, $record['g_violation/q_0301_b']);
        $com_consult_community = mysqli_real_escape_string($conn, $record['g_violation/q_0301c']);
        $allowance_from_community = mysqli_real_escape_string($conn, $record['g_violation/q0301c_yes']);
        $situation = mysqli_real_escape_string($conn, $record['g_violation/q0301txt']);
        $relocation_by_forces = mysqli_real_escape_string($conn, $record['q_0302']);
        $relocation_des = mysqli_real_escape_string($conn, $record['q_0302txt']);
        $illegal_activities = mysqli_real_escape_string($conn, $record['q_0303']);
        $illegal_act_des = mysqli_real_escape_string($conn, $record['q_0303txt']);
        $com_license = mysqli_real_escape_string($conn, $record['q0304']);
        $rapes_six_months = mysqli_real_escape_string($conn, $record['g_q0401/q_0401a']);
        $rapes_desciption = mysqli_real_escape_string($conn, $record['g_q0401/q_0401atxt']);
        $murder_six_months = mysqli_real_escape_string($conn, $record['g_q0401/q_0401b']);
        $murder_description = mysqli_real_escape_string($conn, $record['g_q0401/q_0401btxt']);
        $laterite_in_water = mysqli_real_escape_string($conn, $record['g_q0401/q_0402']);
        $laterite_in_water_des = mysqli_real_escape_string($conn, $record['g_q0401/q_0402txt']);
        $animal_lost_or_deaths = mysqli_real_escape_string($conn, $record['g_q_0501/q_0501']);
        $animal_lost_or_deaths_des = mysqli_real_escape_string($conn, $record['g_q_0501/q_0501txt']);
        $migration = mysqli_real_escape_string($conn, $record['g_q_0501/q0502']);
        $prostitution = mysqli_real_escape_string($conn, $record['g_q_0501/q0503']);
        $women_work = mysqli_real_escape_string($conn, $record['q06women']);
        $comments = mysqli_real_escape_string($conn, $record['comments']);
        $ifinish = mysqli_real_escape_string($conn, $record['i_finish']);
        $instance_id = mysqli_real_escape_string($conn, $record['meta/instanceID']);
        $submission_time = mysqli_real_escape_string($conn, $record['_submission_time']);


        // Check if the submission already exists in the database
        $sql_check = "SELECT * FROM kobo_data02 WHERE submission_id = '$submission_id'";
        $result_check = $conn->query($sql_check);

        if ($result_check->num_rows > 0) {
            echo "Record with submission ID $submission_id exists. Updating record...<br>";

            // Update the existing record
            $sql_update = "UPDATE kobo_data02 SET 
                submission_id = '$submission_id',
                tstart = '$tstart', 
                tend = '$tend', 
                ttoday = '$ttoday', 
                username = '$username', 
                phonenumber = '$phonenumber', 
                deviceid = '$deviceid', 
                name_collection = '$name_collection', 
                date_interview = '$date_interview', 
                name_interview = '$name_interview', 
                sex_interview = '$sex_interview', 
                name_respon = '$name_respon', 
                province = '$province', 
                district = '$district', 
                commune = '$commune', 
                village = '$village', 
                water_polution = '$water_polution', 
                water_polution_des = '$water_polution_des',
                land_overlap = '$land_overlap',
                land_overlap_des = '$land_overlap_des',
                land_erosion = '$land_erosion',
                land_ero_des = '$land_ero_des',
                land_by_waste = '$land_by_waste',
                land_by_waste_des = '$land_by_waste_des',
                com_consultant = '$com_consultant',
                com_inform = '$com_inform',
                com_consult_community = '$com_consult_community',
                allowance_from_community = '$allowance_from_community',
                situation = '$situation', 
                relocation_by_forces = '$relocation_by_forces',
                relocation_des = '$relocation_des',
                illegal_activities = '$illegal_activities', 
                illegal_act_des = '$illegal_act_des', 
                com_license = '$com_license',
                rapes_six_months = '$rapes_six_months',
                rapes_desciption = '$rapes_desciption',
                murder_six_months = '$murder_six_months',
                murder_description = '$murder_description',
                laterite_in_water = '$Laterite_in_water',
                laterite_in_water_des = '$Laterite_in_water_des',
                animal_lost_or_deaths = '$animal_lost_or_deaths',
                animal_lost_or_deaths_des = '$animal_lost_or_deaths_des',
                migration = '$migration', 
                prostitution = '$prostitution',
                women_work = '$women_work',
                comments = '$comments', 
                ifinish = '$ifinish', 
                instance_id = '$instance_id', 
                submission_time = '$submission_time'
                WHERE submission_id = '$submission_id'";

            if ($conn->query($sql_update) === TRUE) {
                echo "Record updated successfully for submission ID $submission_id<br>";
            } else {
                echo "Error updating record: " . $conn->error . "<br>";
            }
        } else {
            echo "No record found with submission ID $submission_id. Inserting new record...<br>";

            // Insert a new record
            $sql_insert = "INSERT INTO kobo_data02 (
                submission_id, tstart, tend, ttoday, username, phonenumber, deviceid, name_collection, 
                date_interview, name_interview, sex_interview, name_respon, province, district, commune, village,
                water_polution, water_polution_des, land_overlap, land_overlap_des, land_erosion, land_ero_des,
                land_by_waste, land_by_waste_des, com_consultant, com_inform, com_consult_community, allowance_from_community,
                situation, relocation_by_forces, relocation_des, illegal_activities, illegal_act_des, com_license,
                rapes_six_months, rapes_desciption, murder_six_months, murder_description, laterite_in_water,
                laterite_in_water_des, animal_lost_or_deaths, animal_lost_or_deaths_des, migration, prostitution, women_work,
                comments, ifinish, instance_id, submission_time) 
                VALUES (
                '$submission_id', '$tstart', '$tend', '$ttoday', '$username', '$phonenumber', '$deviceid', '$name_collection',
                '$date_interview', '$name_interview', '$sex_interview', '$name_respon', '$province', '$district', 
                '$commune', '$village', '$water_polution', '$water_polution_des', '$land_overlap', '$land_overlap_des',
                '$land_erosion', '$land_ero_des', '$land_by_waste', '$land_by_waste_des', '$com_consultant', '$com_inform',
                '$com_consult_community', '$allowance_from_community', '$situation', '$relocation_by_forces', 
                '$relocation_des', '$illegal_activities', '$illegal_act_des', '$com_license', '$rapes_six_months', 
                '$rapes_desciption', '$murder_six_months', '$murder_description', '$laterite_in_water', 
                '$laterite_in_water_des', '$animal_lost_or_deaths', '$animal_lost_or_deaths_des', '$migration', 
                '$prostitution', '$women_work', '$comments', '$ifinish', '$instance_id', '$submission_time')";
                
            if ($conn->query($sql_insert) === TRUE) {
                echo "New record created successfully for submission ID $submission_id<br>";
            } else {
                echo "Error inserting new record: " . $conn->error . "<br>";
            }
        }
    }
} else {
    echo "Failed to retrieve data from KoboToolbox. HTTP Code: " . $http_code . "<br>";
    file_put_contents('error_log.txt', "Kobo API response: " . $response . "\n", FILE_APPEND); 
}

// Display PHP errors for further debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn->close();
?>
<script type="text/javascript">
// Refresh the page every 600000 milliseconds (10 minutes)
setTimeout(function(){
   window.location.reload(1);
}, 600000);
</script>
