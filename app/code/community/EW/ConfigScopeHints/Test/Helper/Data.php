<?php

class EW_ConfigScopeHints_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test overridden levels detection
     *
     * @test
     * @loadFixture
     * @dataProvider dataProvider
     * @loadExpectation
     * @param $path
     * @param $contextScope
     * @param $contextScopeId
     * @param $expectationIndex
     */
    public function getOverridenLevels($path, $contextScope, $contextScopeId, $expectationIndex) {
        $expectation = self::expected()->getData($expectationIndex);
        $overridenExpected = (bool)$expectation['overriden'];
        $levelsExpected = $overridenExpected ? $expectation['levels'] : array();

        $helper = Mage::helper('ew_configscopehints');

        $overridenLevels = $helper->getOverridenLevels($path, $contextScope, $contextScopeId);

        $this->assertEquals($levelsExpected, $overridenLevels);
    }
}