<?php

class Mage_CoinCorner_Model_invoiceCurrencies
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'eur', 'label' => 'Euros (€)'),
            array('value' => 'usd', 'label' => 'US Dollars ($)'),
            array('value' => 'gbp', 'label' => 'Pounds (£)'),
        );
    }
}
