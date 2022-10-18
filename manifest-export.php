<?php
/** @var \ORCA\BiospecimenTracking\BiospecimenTracking $module */

try {
    // get system configuration
    $system_config = $module->getConfiguration($module->getProjectId());
    // if any errors
    if (!empty($system_config["errors"])) {
        // end the request and send back the error(s)
        $module->sendError($system_config["errors"]);
    } else {
        // establish context
        $module->setConfigProjectContext($system_config);
    }

    // ensure a shipment record_id was provided
    if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
        $module->sendError("Cannot export manifest - shipment_id is invalid or missing.");
    }

    // TODO possibly allow export to include/exclude extra headers
    $include_extra_headers = true;

    $csv_data = [];

    // get the data
    [ $shipment, $data ] = $module->getShipmentManifestData($_GET["id"]);

    // prep some values
    $sample_type = $module->getFieldDisplayValue($module->getShipmentProject(), "sample_type", $shipment["sample_type"])["value"];
    $sample_unit = $module->_config["sample_type_units"][$shipment["sample_type"]];

    // extra headers to csv
    if ($include_extra_headers === true) {
        $csv_data[] = "Study Name: " . $system_config["study_name"];
        $csv_data[] = "Shipped To: " . $module->getFieldDisplayValue($module->getShipmentProject(), "shipment_to", $shipment["shipment_to"])["value"];
        $csv_data[] = "Shipped Date: " . $module->getFieldDisplayValue($module->getShipmentProject(), "shipment_date", $shipment["shipment_date"])["value"];
        $csv_data[] = "Sample Type: " . $sample_type;
        $csv_data[] = "Shipment Details: ";
        $csv_data[] = "";
    }

    // dump data to csv
    $temp_path = $module->generateTempFileName(5);
    // headers
    $csv_data[] = implode(',', array_map(function($k) use ($sample_type, $sample_unit) {
        $tmp = $k;
        switch ($k) {
            case "volume":
                $tmp = "$sample_type ($sample_unit)";
                break;
        }
        return $tmp;
    }, array_keys(reset($data))));
    // rows
    foreach ($data as $i => $row) {
        $csv_data[] = implode(',', $row);
    }
    file_put_contents($temp_path, implode("\r\n", $csv_data));

    // output file
    $module->downloadFile($temp_path, "manifest.csv");
} catch (Exception $ex) {
    $module->sendError($ex->getMessage());
}