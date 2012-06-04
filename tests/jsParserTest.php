<?php
require_once "../jsParser.php";

class jsParserTest extends PHPUnit_Framework_TestCase {
    function testParser() {
        $inputStr = '"string"';
        $result = jsParser::parse($inputStr);
        $this->assertEquals("string", $result);
        $this->assertEquals('', $inputStr);

        // arrays
        $result = jsParser::doParse("[]");
        $this->assertEquals(array(), $result);

        $result = jsParser::doParse("[5]");
        $this->assertEquals(array(5), $result);

        $result = jsParser::doParse("[ 5 , 6 ]");
        $this->assertEquals(array(5,6), $result);

        $result = jsParser::doParse("[' 5']");
        $this->assertEquals(array(' 5'), $result);

        $result = jsParser::doParse("['5', \"6\"]");
        $this->assertEquals(array('5','6'), $result);

        $result = jsParser::doParse(" ['5',\"6\" ] ");
        $this->assertEquals(array('5','6'), $result);

        // objects
        $result = jsParser::doParse("{}");
        $this->assertEquals(array(), $result);

        $result = jsParser::doParse("{test:5}");
        $this->assertEquals(array('test' => 5), $result);

        $result = jsParser::doParse('{"test":5}');
        $this->assertEquals(array('test' => 5), $result);

        $result = jsParser::doParse("{test:'string'}");
        $this->assertEquals(array('test' => 'string'), $result);

        $result = jsParser::doParse("{test:[]}");
        $this->assertEquals(array('test' => array()), $result);

        $result = jsParser::doParse("{test:[1,2,3]}");
        $this->assertEquals(array('test' => array(1,2,3)), $result);

        $result = jsParser::doParse('{"test":5, "b":false}');
        $this->assertEquals(array('test' => 5, 'b' => false), $result);

        $result = jsParser::doParse('{test:[{test:1}]}');
        $this->assertEquals(array('test'=>array(array('test'=>1))), $result);

        $result = jsParser::doParse('true');
        $this->assertEquals(true, $result);

        $result = jsParser::doParse('null');
        $this->assertEquals(null, $result);
    }
}
