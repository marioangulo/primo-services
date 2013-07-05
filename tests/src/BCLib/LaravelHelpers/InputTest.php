<?php
namespace BCLib\LaravelHelpers;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2013-07-05 at 01:42:06.
 */
class InputTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Input
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Input;
    }

    protected function tearDown()
    {
    }

    /**
     * @covers BCLib\LaravelHelpers\Input::get
     */
    public function testGetSingleParamsCorrectly()
    {
        $_SERVER['QUERY_STRING'] = 'foo=1&bar=2&foo=3';
        $this->assertEquals(array('2'), Input::get('bar'));
    }

    /**
     * @covers BCLib\LaravelHelpers\Input::get
     */
    public function testGetMultipleParamsCorrectly()
    {
        $_SERVER['QUERY_STRING'] = 'foo=1&bar=2&foo=3';
        $this->assertEquals(array('1', '3'), Input::get('foo'));
    }

    public function testHasFindsName()
    {
        $_SERVER['QUERY_STRING'] = 'foo=1&bar=2&foo=3';
        $this->assertTrue(Input::has('foo'));
    }

    public function testHasReturnsFalseWhenNotPresent()
    {
        $_SERVER['QUERY_STRING'] = 'foo=1&bar=2&foo=3';
        $this->assertFalse(Input::has('baz'));
    }
}
