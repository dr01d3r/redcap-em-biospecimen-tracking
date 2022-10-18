<?php
/** @var \ORCA\BiospecimenTracking\BiospecimenTracking $this */
namespace ORCA\BiospecimenTracking;

use Exception;

trait PlateUtils {

    private $_allowed_box_name_parts = [
        "box_type" => true,
        "sample_type" => true,
        "aliquot_number" => true,
        "visit" => true,
        "box_number" => true,
        "fake_field" => true
    ];

    /**
     * @param $name
     * @return int|string|null
     * @throws Exception
     */
    function searchPlatesByName($name) {
        if ($this->_plate_project === null) {
            throw new Exception("Plate project context has not yet been established");
        }
        if (empty($name)) {
            return null;
        }
        $result = \REDCap::getData([
              "project_id" => $this->_plate_project->project_id,
              "fields" => [ "record_id" ],
              "filterLogic" => "[box_name] = '$name'"
        ]);
        if (empty($result) || count($result) == 0) {
            return null;
        } else if (count($result) > 1) {
            throw new Exception("More than one plate matched the searched value: <strong>$name</strong>");
        } else {
            return key($result);
        }
    }

    /**
     * @param $plate_id
     * @param string $box_name_regex
     * @return array
     * @throws Exception
     */
    function getPlate($plate_id, string $box_name_regex) {
        if (!is_numeric($plate_id)) return [];
        $project = $this->getPlateProject();

        // get all plate info by record
        // get all plate info by record
        $records = \REDCap::getData([
            "project_id" => $project->project_id,
            "records" => [ $plate_id ]
        ]);
        if (count($records) === 1 && isset($records[$plate_id])) {
            $tmp = $records[$plate_id][$project->firstEventId];
            $tmp["box_name_parsed"] = $this->parsePlateName($tmp["box_name"], $box_name_regex);
            return $tmp;
        }
        return [];
    }

    /**
     * @deprecated users no longer have the option of selecting plates
     * @param null $selected_type
     * @return array
     * @throws Exception
     */
    function getPlateSelectionInfo($selected_type = null) {
        $plate_project = $this->getPlateProject();
        $plates = [];
        $records = \REDCap::getData([
            "project_id" => $plate_project->project_id,
            "fields" => [
                "box_name",
                "box_type"
            ],
        ]);
        foreach ($records as $record_id => $record) {
            $plate_info = $record[$plate_project->firstEventId];
            if ($selected_type !== null && $plate_info["box_type"] !== $selected_type) {
                continue;
            }
            $plates[$record_id] = $plate_info["box_name"];
        }
        return array_reverse($plates, true);
    }

    function parsePlateName($name, $regex) : array {
        $result = [];
        if (stripos($regex, '/') === false) $regex = "/$regex/";
        if (preg_match($regex, $name, $matches, PREG_UNMATCHED_AS_NULL)) {
            $result = array_intersect_key($matches, $this->_allowed_box_name_parts);
        }
        return $result;
    }

    /* REQUEST HANDLERS */

    function handleInitializeBoxDashboard($system_config) {
        $response = [
            "config" => [],
            "messages" => [],
            "warnings" => [],
            "errors" => []
        ];

        try {
            // prep some helper info for validation \ plate visuals
            $plate_size = $this->_config["plate_size_map"][$system_config["plate_size"]];
            // participant width & height for this box config
            $pw = floor($plate_size["col"] / $system_config["num_visits"]);
            $ph = floor($plate_size["row"] / $system_config["num_specimens"]);
            // TODO some config entries should be omitted or given default value based on box_type
            $response["config"] = [
                "fields" => array_column($this->getPlateProject()->metadata, "element_label", "field_name"),
                "box_type" => $this->getDictionaryValuesFor($this->getPlateProject()->project_id, "box_type"),
                "alphabet" => range('A', 'Z'),
                "plate_size" => $plate_size,
                "num_visits" => $system_config["num_visits"],
                "num_specimens" => $system_config["num_specimens"],
                "max_participants" => $pw * $ph,
                "box_name_regex" => $system_config["box_name_regex"],
                "specimen_name_regex" => $system_config["specimen_name_regex"],
                "default_volume" => $system_config["default_volume"],
                "datetime_format" => $system_config["datetime_format"],
                "shipment_dashboard_base_url" => $this->getUrl("views/shipment.php"),
                "sample_type_units" => $this->_config["sample_type_units"]
            ];

            // prep new box url
            $new_box_id = \DataEntry::getAutoId($this->getPlateProject()->project_id);
            $new_plate_url = APP_PATH_WEBROOT . "DataEntry/index.php?" . http_build_query([
                    "pid" => $this->getPlateProject()->project_id,
                    "id" => $new_box_id,
                    "event_id" => $this->getPlateProject()->firstEventId,
                    "page" => $this->getPlateProject()->firstForm,
                    "auto" => "1"
                ]);
            $response["config"]["new_plate_url"] = $new_plate_url;

            // get box context if specified
            if (!empty($_GET["id"]) && is_numeric($_GET["id"])) {
                // get the plate data
                $plate = $this->getPlate($_GET["id"], $system_config["box_name_regex"]);
                if (!empty($plate)) {
                    $response["plate"] = $plate;
                    // get the specimen data
                    $response["specimens"] = $this->getSpecimens($_GET["id"], $system_config["specimen_name_regex"]);
                }
                // get record_home url
                $box_record_home_url = APP_PATH_WEBROOT . "DataEntry/record_home.php?" . http_build_query([
                        "pid" => $this->getPlateProject()->project_id,
                        "id" => $_GET["id"]
                    ]);
                $response["config"]["box_record_home_url"] = $box_record_home_url;
            }
            $this->addTime("initialization finished");
        } catch (Exception $ex) {
            $response["errors"][] = $ex->getMessage();
        }
        // send it back!
        $this->sendResponse($response);
    }

    function handleSearchPlate(string $search_value, array $system_config, bool $include_specimens = true) {
        if ($search_value === null || empty($search_value)) {
            $this->sendError("No search value provided.");
        }
        try {
            $plate_id = $this->searchPlatesByName($search_value);
            if (is_numeric($plate_id)) {
                $plate = $this->getPlate($plate_id, $system_config["box_name_regex"]);
                $response["plate"] = $plate;
                if ($include_specimens === true) {
                    // get the specimen data
                    $response["specimens"] = $this->getSpecimens($plate_id, $system_config["specimen_name_regex"]);
                }
                $this->sendResponse($response);
            }
            $this->sendError("No box found by that name");
        } catch (Exception $ex) {
            $this->sendError($ex->getMessage());
        }
    }
}