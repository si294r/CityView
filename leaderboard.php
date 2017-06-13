<?php

include("config.php");

$json = json_decode($input);
$PlayFabId = isset($json->PlayFabId) ? $json->PlayFabId : "";
$Limit = isset($json->Limit) ? $json->Limit : 20;
$Country = isset($json->Country) ? $json->Country : "";
$RenewCache = isset($json->RenewCache) ? $json->RenewCache : "";

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport", $myuser, $mypass
);

function get_file_cache($param) {
    global $IS_DEVELOPMENT;
    return $IS_DEVELOPMENT ? "cache/".$param.".tmpdev" : "cache/".$param.".tmp";
}


if ($RenewCache == "1") {
    $CountryCodes = ["AD", "AE", "AF", "AR", "AS", "AU", "AW", "AX", "AZ", "BB", "BD", "BE", "BH", "BN", "BR", "BS", "BY", "CA", "CH", "CL", "CN", "GB", "global", "ID", "US"];
    foreach ($CountryCodes as $v) {
        if ($v == "global") {
            $sql = "
SELECT PlayFabId, TowerLevel, 'global' Country, SortRank, LastUpdate 
FROM
(
    SELECT 
	*,
    ROW_NUMBER() OVER (PARTITION BY TowerLevel) as row_number
    FROM leaderboard
) t
WHERE row_number <= 2 
ORDER BY TowerLevel;
            ";            
        } else {
            $sql = "
SELECT PlayFabId, TowerLevel, Country, SortRank, LastUpdate 
FROM
(
    SELECT 
	*,
    ROW_NUMBER() OVER (PARTITION BY TowerLevel) as row_number
    FROM leaderboard WHERE Country = '$v'
) t
WHERE row_number <= 2 
ORDER BY TowerLevel;
            ";            
        }
        
        $statement = $connection->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        file_put_contents(get_file_cache($v), json_encode($rows));
    }
}

/*
$sql1 = "select * from leaderboard where PlayFabId = :PlayFabId1
union
(select * from leaderboard_cache 
where TowerLevel < (select TowerLevel from leaderboard where PlayFabId = :PlayFabId2) 
and Country = 'global'
order by TowerLevel desc limit $Limit)
union
(select * from.leaderboard_cache 
where TowerLevel >= (select TowerLevel from leaderboard where PlayFabId = :PlayFabId3)
and Country = 'global'
and PlayFabId <> :PlayFabId4 order by TowerLevel asc limit $Limit)
order by TowerLevel
";

$sql2 = "select * from leaderboard where PlayFabId = :PlayFabId1
union
(select * from leaderboard_cache 
where TowerLevel < (select TowerLevel from leaderboard where PlayFabId = :PlayFabId2) 
and Country = :Country1
order by TowerLevel desc limit $Limit)
union
(select * from leaderboard_cache 
where TowerLevel >= (select TowerLevel from leaderboard where PlayFabId = :PlayFabId3)
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
*/

$sql1 = "select * from leaderboard where PlayFabId = :PlayFabId1";
$statement1 = $connection->prepare($sql1);
$statement1->execute(
        array(':PlayFabId1' => $PlayFabId)
        );
$rows1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

$row_global = json_decode(file_get_contents(get_file_cache("global")), true);
$row_region = json_decode(file_get_contents(get_file_cache($Country)), true);

if (array_search($PlayFabId, array_column($row_region, "PlayFabId")) === FALSE) {
    $row_region[] = $rows1[0];
}

if (array_search($PlayFabId, array_column($row_global, "PlayFabId")) === FALSE) {
    $rows1[0]['Country'] = "global";
    $row_global[] = $rows1[0];
}

function cmp_row($a, $b) {
    if (intval($a['TowerLevel']) == intval($b['TowerLevel'])) {
        return 0;
    }
    return (intval($a['TowerLevel']) < intval($b['TowerLevel'])) ? -1 : 1;
}

usort($row_global, 'cmp_row');
usort($row_region, 'cmp_row');

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
$data["global"] = limit_around_user($row_global);
$data["region"] = limit_around_user($row_region);

return $data;
