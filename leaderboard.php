<?php

include("config.php");

$json = json_decode($input);
$PlayFabId = $json->PlayFabId;
$Limit = $json->Limit;
$Country = $json->Country;

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport", $myuser, $mypass
);

$sql1 = "select * from mytower.leaderboard where PlayFabId = :PlayFabId1
union
(select * from mytower.leaderboard_cache 
where TowerLevel < (select TowerLevel from mytower.leaderboard where PlayFabId = :PlayFabId2) order by TowerLevel desc limit $Limit)
union
(select * from mytower.leaderboard_cache 
where TowerLevel >= (select TowerLevel from mytower.leaderboard where PlayFabId = :PlayFabId3)
and PlayFabId <> :PlayFabId4 order by TowerLevel asc limit $Limit)
order by TowerLevel
";

$sql2 = "select * from mytower.leaderboard where PlayFabId = :PlayFabId1
union
(select * from mytower.leaderboard_cache 
where TowerLevel < (select TowerLevel from mytower.leaderboard where PlayFabId = :PlayFabId2) 
and Country = :Country1
order by TowerLevel desc limit $Limit)
union
(select * from mytower.leaderboard_cache 
where TowerLevel >= (select TowerLevel from mytower.leaderboard where PlayFabId = :PlayFabId3)
and Country = :Country2
and PlayFabId <> :PlayFabId4 order by TowerLevel asc limit $Limit)
order by TowerLevel
";


$statement1 = $connection->prepare($sql1);
$statement1->execute(
        array(':PlayFabId1' => $PlayFabId, ':PlayFabId2' => $PlayFabId, 
            ':PlayFabId3' => $PlayFabId, ':PlayFabId4' => $PlayFabId)
        );
$rows1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

$statement2 = $connection->prepare($sql2);
$statement2->execute(
        array(':PlayFabId1' => $PlayFabId, ':PlayFabId2' => $PlayFabId, 
            ':PlayFabId3' => $PlayFabId, ':PlayFabId4' => $PlayFabId,
            ':Country1' => $Country, ':Country2' => $Country)
        );
$rows2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

function limit_around_user($rows) {
    global $PlayFabId, $Limit;
    $pos = 0;
    foreach ($rows as $k=>$v) {
        if ($v['PlayFabId'] == $PlayFabId) {
            $pos = $k;
        } 
    }

    $rows1 = [];
    if (isset($rows[$pos])) $rows1[] = $rows[$pos];
    
    $end = count($rows) - 1;
    $a = $pos - 1;
    $b = $pos + 1;
    while (count($rows1) < $Limit) {
        if ($a >= 0) {
            array_unshift($rows1, $rows[$a]);
            $a--;
        }
        if ($b <= $end) {
            array_push($rows1, $rows[$b]);
            $b++;
        }
        if ($a < 0 && $b > $end) {
            break;
        }
    }
    return $rows1;
}


$data["PlayFabId"] = $PlayFabId;
$data["Country"] = $Country;
$data["Limit"] = $Limit;
$data["global"] = limit_around_user($rows1);
$data["region"] = limit_around_user($rows2);

return $data;
