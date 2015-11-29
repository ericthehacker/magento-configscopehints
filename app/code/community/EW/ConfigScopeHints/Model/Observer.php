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
     * Add not visible at this scope hint
     *
     * @param Varien_Object $config
     * @param $fieldNotVisible
     */
    protected function _addVisibilityHint(Varien_Object $config, Mage_Core_Model_Config_Element $element, $fieldNotVisible) {
        if(!$fieldNotVisible) {
            return;
        }

        $newClass = trim($config->getClass() . ' not-visible');
        $newComment = '<em>' . $this->_getHelper()->__('This config field cannot be set at this scope.') . '</em>';

        $ifModuleEnabled = trim((string)$element->if_module_enabled);
        if ($ifModuleEnabled && !Mage::helper('Core')->isModuleEnabled($ifModuleEnabled)) {
            // JK! field not visible because required module disabled
            $newComment = '<em>' . $this->_getHelper()->__('This field belongs to %s, which is disabled.', $ifModuleEnabled) . '</em>';
        }

        $config->setComment($newComment);
        $config->setClass($newClass);
        $config->setDisabled(true);
    }

    /**
     * Add overridden at more specific scope hint
     *
     * @param Varien_Object $config
     * @param array $overriden
     */
    protected function _addOverriddenHint(Varien_Object $config, array $overriden) {
        if(empty($overriden)) {
            return;
        }

        $scopeLabel = $config->getScopeLabel();
        $scopeLabel .= $this->_getHelper()->formatOverriddenScopes($overriden);
        $config->setScopeLabel($scopeLabel);

        Varien_Profiler::stop(EW_ConfigScopeHints_Helper_Data::PROFILER_KEY);
    }

    /**
     * Add config hints to system config elements
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
        $fieldNotVisible = !(bool)$observer->getCanShow();

        $this->_addVisibilityHint($config, $element, $fieldNotVisible);
        $this->_addOverriddenHint($config, $overriden);
    }
}