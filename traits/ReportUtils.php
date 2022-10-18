<?php
/** @var \ORCA\BiospecimenTracking\BiospecimenTracking $this */
namespace ORCA\BiospecimenTracking;

use Exception;

trait ReportUtils {

    function handleInitializeReportDashboard($system_config) {
        $response = [
            "config" => [],
            "messages" => [],
            "warnings" => [],
            "errors" => []
        ];
        try {
            // prep some helper info for validation
            $response["config"] = [
                "specimen_fields" => array_keys($this->getSpecimenFields(true))
            ];
            $specimens = $this->getAllSpecimens($system_config);
            $response["specimens"] = $specimens;
        } catch (Exception $ex) {
            $response["errors"][] = $ex->getMessage();
        }
        // send it back!
        $this->sendResponse($response);
    }
}