<?php

class EW_ConfigScopeHints_Model_Observer
{
    /**
     * @var EW_ConfigScopeHints_Helper_Data
     */
    protected $_helper = null;

    /**
     * Get helper singleton
     *
     * @return EW_ConfigScopeHints_Helper_Data
     */
    protected function _getHelper() {
        if(is_null($this->_helper)) {
            $this->_helper = Mage::helper('ew_configscopehints');
        }

        return $this->_helper;
    }

    /**
     * Add scope hint to system config elements
     * Observes: system_config_form_field_config_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function addScopeHint(Varien_Event_Observer $observer) {
        Varien_Profiler::start(EW_ConfigScopeHints_Helper_Data::PROFILER_KEY);

        /* @var $config Varien_Object */
        $config = $observer->getConfig();
        /* @var $element Mage_Core_Model_Config_Element */
        $element = $observer->getElement();
        /* @var $group Mage_Core_Model_Config_Element */
        $group = $observer->getGroup();
        /* @var $section Mage_Core_Model_Config_Element */
        $section = $observer->getSection();

        $scope = $observer->getScope();
        $scopeId = $observer->getScopeId();


        $path = $section->getName() . '/' . $group->getName() . '/' . $element->getName();

        $overriden = $this->_getHelper()->getOverridenLevels($path, $scope, $scopeId);

        if(empty($overriden)) {
            return;
        }

        $scopeLabel = $config->getScopeLabel();
        $scopeLabel .= $this->_getHelper()->formatOverriddenScopes($overriden);
        $config->setScopeLabel($scopeLabel);

        Varien_Profiler::stop(EW_ConfigScopeHints_Helper_Data::PROFILER_KEY);
    }
}