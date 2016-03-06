<?php

class EW_ConfigScopeHints_Helper_Data extends Mage_Core_Helper_Abstract
{
    const PROFILER_KEY = 'EW_ConfigScopeHints';

    /**
     * Get default store ID.
     * Abstracted so it can be improved if default store
     * ID not always 0.
     *
     * @return int
     */
    protected function _getDefaultStoreId() {
        return 0;
    }

    /**
     * Get scopes tree in following form:
     *
     * array('websites' => array (
     *          website id => array('stores' => array of store ids),
     *          ...
     *     )
     * )
     *
     * @return array
     */
    public function getScopeTree() {
        $tree = array('websites' => array());

        $websites = Mage::app()->getWebsites();

        /* @var $website Mage_Core_Model_Website */
        foreach($websites as $website) {
            $tree['websites'][$website->getId()] = array('stores' => array());

            /* @var $store Mage_Core_Model_Store */
            foreach($website->getStores() as $store) {
                $tree['websites'][$website->getId()]['stores'][] = $store->getId();
            }
        }

        return $tree;
    }

    /**
     * Get config node value after processing with backend model
     *
     * @param Mage_Core_Model_Config_Element $node
     * @param $path
     * @return string
     */
    protected function _getProcessedValue(Mage_Core_Model_Config_Element $node, $path) {
        $value = (string)$node;
        if (!empty($node['backend_model']) && !empty($value)) {
            $backend = Mage::getModel((string)$node['backend_model']);
            $backend->setPath($path)->setValue($value)->afterLoad();
            $value = $backend->getValue();
        }

        return $value;
    }

    /**
     * Get current value by scope and scope ID,
     * or null if none could be found.
     *
     * @param $path
     * @param $contextScope
     * @param $contextScopeId
     * @return mixed|null|string
     * @throws Mage_Core_Exception
     */
    protected function _getConfigValue($path, $contextScope, $contextScopeId) {
        $currentValue = null;
        switch($contextScope) {
            case 'websites':
                $code = Mage::app()->getWebsite($contextScopeId)->getCode();
                $node = Mage::getConfig()->getNode('websites/'.$code.'/'.$path);
                $currentValue = !$node ? '' : $this->_getProcessedValue($node, $path);
                break;
            case 'default':
                $node = Mage::getConfig()->getNode('default/' . $path);
                $currentValue = !$node ? '' : $this->_getProcessedValue($node, $path);
                break;
            case 'stores':
                $code = Mage::app()->getStore($contextScopeId)->getCode();
                $node = Mage::getConfig()->getNode('stores/' . $code . '/' . $path);
                $currentValue = !$node ? '' : $this->_getProcessedValue($node, $path);
                break;
        }

        return $currentValue;
    }

    /**
     * Get scopes where value of config at path is overridden.
     * Returned in form of
     * array( array('scope' => overridden scope, 'scope_id' => overridden scope id), ...)
     *
     * @param $path
     * @param $contextScope
     * @param $contextScopeId
     * @return array
     */
    public function getOverridenLevels($path, $contextScope, $contextScopeId) {
        $contextScopeId = $contextScopeId ?: $this->_getDefaultStoreId();

        $currentValue = $this->_getConfigValue($path, $contextScope, $contextScopeId);

        if(is_null($currentValue)) {
            return array(); //something is off, let's bail gracefully.
        }

        $tree = $this->getScopeTree();

        $overridden = array();

        switch($contextScope) {
            case 'websites':
                $stores = array_values($tree['websites'][$contextScopeId]['stores']);
                foreach($stores as $storeId) {
                    $value = $this->_getConfigValue($path, 'stores', $storeId);
                    if($value != $currentValue) {
                        $overridden[] = array(
                            'scope'     => 'store',
                            'scope_id'  => $storeId
                        );
                    }
                }
                break;
            case 'default':
                foreach($tree['websites'] as $websiteId => $website) {
                    $websiteValue = $this->_getConfigValue($path, 'websites', $websiteId);
                    if($websiteValue != $currentValue) {
                        $overridden[] = array(
                            'scope'     => 'website',
                            'scope_id'  => $websiteId
                        );
                    }

                    foreach($website['stores'] as $storeId) {
                        $value = $this->_getConfigValue($path, 'stores', $storeId);
                        if($value != $currentValue && $value != $websiteValue) {
                            $overridden[] = array(
                                'scope'     => 'store',
                                'scope_id'  => $storeId
                            );
                        }
                    }
                }
                break;
        }

        return $overridden;
    }

    /**
     * Format overridden scopes for output
     *
     * @param array $overridden
     * @return string
     */
    public function formatOverriddenScopes(array $overridden) {
        $title = $this->__('This setting is overridden at a more specific scope. Click for details.');

        $formatted = '<a class="overridden-hint-list-toggle" title="'. $title .'" href="#">'. $title .'</a>'.
                     '<ul class="overridden-hint-list">';

        foreach($overridden as $overriddenScope) {
            $scope = $overriddenScope['scope'];
            $scopeId = $overriddenScope['scope_id'];
            $scopeLabel = $scopeId;

            $url = '#';
            $section = Mage::app()->getRequest()->getParam('section'); //grrr.
            switch($scope) {
                case 'website':
                    $url = Mage::getModel('adminhtml/url')->getUrl(
                        '*/*/*',
                        array(
                            'section'=>$section,
                            'website'=>Mage::app()->getWebsite($scopeId)->getCode()
                        )
                    );
                    $scopeLabel = sprintf(
                        'website <a href="%s">%s</a>',
                        $url,
                        Mage::app()->getWebsite($scopeId)->getName()
                    );

                    break;
                case 'store':
                    $store = Mage::app()->getStore($scopeId);
                    $website = $store->getWebsite();
                    $url = Mage::getModel('adminhtml/url')->getUrl(
                        '*/*/*',
                        array(
                            'section'   => $section,
                            'website'   => $website->getCode(),
                            'store'     => $store->getCode()
                        )
                    );
                    $scopeLabel = sprintf(
                        'store view <a href="%s">%s</a>',
                        $url,
                        $website->getName() . ' / ' . $store->getName()
                    );
                    break;
            }

            $formatted .= "<li class='$scope'>Overridden on $scopeLabel</li>";
        }

        $formatted .= '</ul>';

        return $formatted;
    }
}