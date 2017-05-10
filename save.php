<?php

include("config.php");

$json = json_decode($input);

$data['PlayFabId'] = isset($json->PlayFabId) ? $json->PlayFabId : "";
$data['TowerLevel'] = isset($json->TowerLevel) ? $json->TowerLevel : 0;
$data['Country'] = isset($json->Country) ? $json->Country : "";

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport",
    $myuser, $mypass
);
    
// create record if not exists
$sql1 = "INSERT INTO $table_name (PlayFabId, TowerLevel, Country)
    VALUES (:PlayFabId, :TowerLevel, :Country)
    ON DUPLICATE KEY UPDATE
    TowerLevel = :TowerLevel2, Country = :Country2
";
$statement1 = $connection->prepare($sql1);
$statement1->bindParam(":PlayFabId", $data['PlayFabId']);
$statement1->bindParam(":TowerLevel", $data['TowerLevel']);
$statement1->bindParam(":Country", $data['Country']);
$statement1->bindParam(":TowerLevel2", $data['TowerLevel']);
$statement1->bindParam(":Country2", $data['Country']);
$statement1->execute();

$data['affected_row'] = $statement1->rowCount();
$data['error'] = 0;
$data['message'] = 'Success';

return $data;
