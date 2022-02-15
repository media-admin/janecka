<?php
require_once __DIR__ .'/../libraries/XmlExportACF.php';

function pmae_pmxe_init_addons() {

    if(!\XmlExportEngine::$acf_export) {
        \XmlExportEngine::$acf_export = new XmlExportACF();
    }
}