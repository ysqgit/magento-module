<?php
$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');

$installer->startSetup();

$reader = Mage::getSingleton('core/resource')->getConnection('core_read');

$result = $reader->query('select attribute_code from '.$prefix.'eav_attribute WHERE attribute_code = "mundipagg_recurrent"')->fetchAll();

if (!empty($result)) {
    $installer->removeAttribute('catalog_product', 'mundipagg_recurrent');
}

$result = $reader->query('select attribute_code from '.$prefix.'eav_attribute WHERE attribute_code = "mundipagg_frequency_enum"')->fetchAll();

if (!empty($result)) {
    $installer->removeAttribute('catalog_product', 'mundipagg_frequency_enum');
}

$result = $reader->query('select attribute_code from '.$prefix.'eav_attribute WHERE attribute_code = "mundipagg_recurrences"')->fetchAll();

if (!empty($result)) {
    $installer->removeAttribute('catalog_product', 'mundipagg_recurrences');
}

$result = $reader->query('select attribute_code from '.$prefix.'eav_attribute WHERE attribute_code = "mundipagg_recurrence_mix"')->fetchAll();
if (!empty($result)) {
    $installer->removeAttribute('catalog_product', 'mundipagg_recurrence_mix');
}

$result = $reader->query('select attribute_code from '.$prefix.'eav_attribute WHERE attribute_code = "mundipagg_recurrence_discount"')->fetchAll();
if (!empty($result)) {
    $installer->removeAttribute('catalog_product', 'mundipagg_recurrence_discount');
}

$installer->cleanCache();
$installer->endSetup();
