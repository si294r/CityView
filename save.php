<?php

include("config.php");

$json = json_decode($input);

$data['PlayFabId'] = isset($json->PlayFabId) ? $json->PlayFabId : "";
$data['TowerLevel'] = isset($json->TowerLevel) ? $json->TowerLevel : 0;
$data['Country'] = isset($json->Country) ? $json->Country : "";
$data['SortRank'] = isset($json->SortRank) ? $json->SortRank : 0;

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport",
    $myuser, $mypass
);
    
// create record if not exists
$sql1 = "INSERT INTO $table_name (PlayFabId, TowerLevel, Country, SortRank)
    VALUES (:PlayFabId, :TowerLevel, :Country, :SortRank)
    ON DUPLICATE KEY UPDATE
    TowerLevel = :TowerLevel2, Country = :Country2, SortRank = :SortRank2
";
$statement1 = $connection->prepare($sql1);
$statement1->bindParam(":PlayFabId", $data['PlayFabId']);
$statement1->bindParam(":TowerLevel", $data['TowerLevel']);
$statement1->bindParam(":Country", $data['Country']);
$statement1->bindParam(":SortRank", $data['SortRank']);
$statement1->bindParam(":TowerLevel2", $data['TowerLevel']);
$statement1->bindParam(":Country2", $data['Country']);
$statement1->bindParam(":SortRank2", $data['SortRank']);
$statement1->execute();

$data['affected_row'] = $statement1->rowCount();
$data['error'] = 0;
$data['message'] = 'Success';

return $data;
