<?php

namespace Wpae\App\Service\Addons;


class AddonService
{
    public function isUserAddonActive() {
        return defined('PMUE_EDITION');
    }

    public function isWooCommerceAddonActive() {
        return defined('PMWE_VERSION');
    }

    public function isAcfAddonActive() {
        return defined('PMAE_VERSION');
    }

    public function isWooCommerceProductAddonActive() {
        return defined('PMWPE_EDITION');
    }

    public function isWooCommerceOrderAddonActive() {
        return defined('PMWOE_EDITION');
    }

    public function isWoocommerceAddonActiveAndIsWooCommerceExport()
    {
        return $this->isWooCommerceAddonActive() && \XmlExportWooCommerce::$is_active;
    }


    public function isUserAddonActiveAndIsUserExport()
    {
        return $this->isUserAddonActive() && \XmlExportUser::$is_active;
    }


    public function userExportsExistAndAddonNotInstalled()
    {

	    $exports = new \PMXE_Export_List();
	    $exports->getBy('parent_id', 0)->convertRecords();

	    foreach ($exports as $item) {

		    if(!isset($item['options']['cpt'])) {
			    continue;
		    }

		    if(!is_array($item['options']['cpt'])) {
			    $item['options']['cpt'] = array($item['options']['cpt']);
		    }

		    if (
			    ((in_array('users', $item['options']['cpt']) || in_array('shop_customer', $item['options']['cpt'])) && !$this->isUserAddonActive()) ||
			    ($item['options']['export_type'] == 'advanced' && $item['options']['wp_query_selector'] == 'wp_user_query' && !$this->isUserAddonActive())
		    ) {
			    return true;
		    }

	    }

	    return false;
	}

    public function hasExportAtOlderVersionThan($version)
    {
        $exports = new \PMXE_Export_List();
        $exports->getBy('parent_id', 0)->convertRecords();

        foreach ($exports as $item) {

            if (!isset($item['options']['created_at_version'])) {
                continue;
            }

            if(version_compare($item['options']['created_at_version'], $version) < 0) {
                return true;
            }
        }

        return false;
    }

    public function wooCommerceExportsExistAndAddonNotInstalled()
    {
        $exports = new \PMXE_Export_List();
        $exports->getBy('parent_id', 0)->convertRecords();

        foreach ($exports as $item) {

            if(!isset($item['options']['cpt'])) {
                continue;
            }

            if(!is_array($item['options']['cpt'])) {
                $item['options']['cpt'] = array($item['options']['cpt']);
            }

            if (
                (
                    (
	                    (in_array('product', $item['options']['cpt']) && !$this->isWooCommerceProductAddonActive() && \class_exists('WooCommerce')) ||
                        in_array('product_variation', $item['options']['cpt']) ||
	                    (in_array('shop_order', $item['options']['cpt']) && !$this->isWooCommerceOrderAddonActive()) ||
                        in_array('shop_review', $item['options']['cpt']) ||
                        in_array('shop_coupon', $item['options']['cpt'])
                    )
                    && !$this->isWooCommerceAddonActive())
            ) {
                return true;
            }

        }

        return false;
    }



    public function acfExportsExistAndNotInstalled()
    {
        if($this->isAcfAddonActive()) {
            return false;
        }

        $exports = new \PMXE_Export_List();
        $exports->getBy('parent_id', 0)->convertRecords();

        foreach ($exports as $item) {

            if(is_array($item->options['cc_type']) && in_array('acf', $item->options['cc_type'])) {
                return true;
            }


        }

        return false;
    }

}