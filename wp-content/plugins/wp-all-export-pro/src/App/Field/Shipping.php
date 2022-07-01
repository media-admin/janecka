<?php

namespace Wpae\App\Field;


class Shipping extends Field
{
    const SECTION = 'shipping';

    public function getValue($snippetData)
    {
        $shippingData = $this->feed->getSectionFeedData(self::SECTION);

        if (isset($shippingData['includeAttributes']) && $shippingData['includeAttributes'] == 'include') {

            $shippingAttributes = [
                $this->replaceSnippetsInValue($shippingData['shippingCountry'], $snippetData),
                $this->replaceSnippetsInValue($shippingData['shippingDeliveryArea'], $snippetData),
                $this->replaceSnippetsInValue($shippingData['shippingService'], $snippetData),
                $this->shippingPrice($snippetData, $shippingData)
            ];

            return implode(':', $shippingAttributes);
        } else {
            return ':::' . $this->shippingPrice($snippetData, $shippingData) . '';
        }
    }

    public function getFieldName()
    {
        return 'shipping';
    }

    private function formatPrice($price)
    {
        $availabilityPriceData = $this->feed->getSectionFeedData('availabilityPrice');

        return number_format($price, 2) . '' . $availabilityPriceData['currency'];
    }

    /**
     * @param $snippetData
     * @param $shippingData
     * @return float|int|mixed|string
     */
    private function shippingPrice($snippetData, $shippingData)
    {
        $price = $this->replaceSnippetsInValue($shippingData['shippingPrice'], $snippetData);
        $adjustShippingPriceValue = $this->replaceSnippetsInValue($shippingData['adjustShippingPriceValue'], $snippetData);

        if ($shippingData['adjustShippingPrice'] && $shippingData['adjustPriceType'] == '%') {
            $price = $price * $adjustShippingPriceValue / 100;
        } else if ($shippingData['adjustShippingPrice'] && $shippingData['adjustPriceType'] == 'USD') {
            $price = $price + $adjustShippingPriceValue;
        }

        if (is_numeric($price)) {
            return $this->formatPrice($price);
        } else {
            return $price;
        }
    }
}