<?php

class Mage_CoinCorner_Model_settlementCurrencies
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'eur', 'label' => 'Euros (€)'),
            array('value' => 'gbp', 'label' => 'Pounds (£)'),
        );
    }
}
?>