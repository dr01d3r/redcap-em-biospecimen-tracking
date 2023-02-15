<?php
/** @var \ORCA\BiospecimenTracking\BiospecimenTracking $module */

$module->addTime();
?>
    <div id="ORCA_BIOSPECIMEN_TRACKING"></div>
    <script>
        const OrcaBiospecimenTracking = function() {
            return {
                url: '<?= $module->getBaseUrl() ?>',
                redcap_csrf_token: <?= json_encode($module->getCSRFToken()) ?>,
                userid: <?= (isset($userid) ? "'$userid'" : 'null') ?>
            }
        };
    </script>
    <script src="<?= $module->getUrl('dist/pages/dashboard/app.js') ?>"></script>
    <link rel="stylesheet" href="<?= $module->getUrl('dist/pages/dashboard/style.css') ?>">
<?php
$module->outputModuleVersionJS();