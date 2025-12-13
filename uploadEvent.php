<?php    // uploadEvent.php

/*
JSON:  key name & value's data type: 
    id          UUID: (e.g. 40723612-6F3A-475E-8DA8-ADE846734541)
    timestamp   Date: For now, outputs Z (e.g. 2025-01-06 04:11:44.123456)
    latitude    Double: decimal degrees (S are -values) 
    longitude   Double: decimal degrees (W are -values)
    eventType   String: One of standard BoatNotes types (e.g. Comment), or could be custom set by user
    desc        String: Main description field, no length restrictions set
    property    String: Mostly used to provide more context for eventType
    value1      String: Often a numerical value, but not necessarily, so transmitted as String
    value2      String: Not used often, same as value1
    boatName    String
    venueName   String  
    userName    String
*/

require_once "database.php";
header("Content-Type: application/json");

// ------------------------------------------------------
// 1. Read RAW POST BODY
// ------------------------------------------------------
$json = file_get_contents("php://input");

if (!$json) {
    http_response_code(400);
    echo json_encode(["error" => "No JSON received"]);
    exit;
}

$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}


// ------------------------------------------------------
// 2. Get DB connection
// ------------------------------------------------------
$db = new Database();
$pdo = $db->getConnection();


// ------------------------------------------------------
// 3. Convert Swift ISO8601 → MySQL DATETIME(6)
// ------------------------------------------------------
//
// Handles:
//
//   2025-02-20T16:13:35Z
//   2025-02-20T16:13:35.581Z
//   2025-02-20T16:13:35+00:00
//   2025-02-20T16:13:35.581+00:00
//
// Produces:
//
//   2025-02-20 16:13:35.581000
//
function convertSwiftTimestamp($value) {
    if (!$value) return null;

    // Remove Z or timezone offset (+00:00 etc)
    $v = preg_replace('/Z$/', '', $value);
    $v = preg_replace('/([+-]\d\d:\d\d)$/', '', $v);

    // Replace T with a space
    $v = str_replace("T", " ", $v);

    // Add or pad microseconds
    if (strpos($v, ".") !== false) {
        list($base, $frac) = explode(".", $v, 2);
        $frac = preg_replace('/\D/', '', $frac); // remove any garbage
        $frac = str_pad($frac, 6, "0");
        return $base . "." . $frac;
    } else {
        return $v . ".000000";
    }
}


// ------------------------------------------------------
// 4. UPSERT into MySQL
// ------------------------------------------------------
function upsert($pdo, $table, $cols, $row) {
    $columns = implode(",", $cols);
    $placeholders = implode(",", array_fill(0, count($cols), "?"));

    $updates = [];
    foreach ($cols as $c) {
        $updates[] = "$c = VALUES($c)";
    }
    $updateString = implode(",", $updates);

    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)
            ON DUPLICATE KEY UPDATE $updateString";

    $stmt = $pdo->prepare($sql);

    $values = [];
    foreach ($cols as $c) {
        $values[] = $row[$c] ?? null;
    }

    $stmt->execute($values);
}


// ------------------------------------------------------
// 5. EVENT FIELD MAP
// ------------------------------------------------------

// SQL column in your MySQL table => JSON key sent from BoatNotesPro

$eventMap = [
    "event_uuid"      => "id",
    "event_timestamp" => "timestamp",
    "event_lat"       => "latitude",
    "event_lon"       => "longitude",
    "event_type"      => "eventType",
    "event_desc"      => "desc",
    "event_property"  => "property",
    "event_value"     => "value1",
    "event_value_2"   => "value2",
    "event_boat"      => "boatName",
    "event_venue"     => "venueName",
    "event_user"      => "userName"
];


// ------------------------------------------------------
// 6. PROCESS EVENTS
// ------------------------------------------------------
if (!empty($data["exportEvents"])) {

    foreach ($data["exportEvents"] as $e) {

        // Build row using the mapping
        $row = [];
        foreach ($eventMap as $dbCol => $jsonKey) {
            $row[$dbCol] = $e[$jsonKey] ?? null;
        }

        // Convert Swift timestamp → MySQL DATETIME(6)
        if (!empty($row["event_timestamp"])) {
            $row["event_timestamp"] = convertSwiftTimestamp($row["event_timestamp"]);
        }

        // Debug logging (remove after testing)
        // error_log("RAW timestamp: " . $e["timestamp"]);
        // error_log("CONVERTED timestamp: " . $row["event_timestamp"]);

        // Insert/update
        upsert(
            $pdo,
            "events",
            array_keys($eventMap),
            $row
        );
    }
}


// ------------------------------------------------------
// 7. DONE
// ------------------------------------------------------
echo json_encode(["status" => "success"]);

?>
