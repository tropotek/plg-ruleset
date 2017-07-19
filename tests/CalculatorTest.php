<?php
namespace tests;

use App\Db\Placement;
use Rs\Calculator;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class CalculatorTest extends \PHPUnit_Framework_TestCase
{


    public function __construct()
    {
        parent::__construct('Calculator Test');
    }

    public function setUp()
    {
        $config = \App\Factory::getConfig();

    }

    public function tearDown()
    {

    }



    public function testCreate()
    {
        /** @var Placement $placement */
        $placement = \App\Db\PlacementMap::create()->find(21308);

        $calc = \Rs\Calculator::create($placement->getCourse(), $placement->getUser());
        $this->assertTrue($calc instanceof Calculator);
    }



}

