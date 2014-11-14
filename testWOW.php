<?php
// Test WOW API
// Phil Hansen, 10 February 2014
// Copyright (C) 2014 Phil Hansen

// These tests use the simpletest library
require_once('simpletest/autorun.php');

class WowTestCase
    extends UnitTestCase
    {
    function test_wow_call_function()
        {
        // a wow function that doesn't need parameters and should return data
        $result = wow_call_function('GetServiceDefs');
        $this->assertFalse(is_null($result));
        $this->assertTrue(count($result) > 1);
        }
    
    function test_wow_call_ticket_function()
        {
        // a ticket function that doesn't need parameters and should return data
        $result = wow_call_ticket_function('ticket_Get_Status_List');
        $this->assertFalse(is_null($result));
        $this->assertTrue(count($result) > 0);
        }
    
    function test_wow_build_xml_request()
        {
        $request = wow_build_xml_request('GetServiceDefs');
        // check some values
        $this->assertTrue(strpos($request, '<action>GetServiceDefs</action>') > 0);
        $this->assertTrue(strpos($request, '<username>') > 0);
        $this->assertTrue(strpos($request, '<password>') > 0);
        $this->assertTrue(strpos($request, '<logintype>staff</logintype>') > 0);
        
        // properties
        $properties = Array(
            'test1' => 'Test 1',
            'test2' => FALSE,
            'test3 type="integer"' => 123,
            );
        $request = wow_build_xml_request('Test', $properties);
        $expected = "<properties>\n".
                    "<test1>Test 1</test1>\n".
                    '<test2 type="boolean">false</test2>'."\n".
                    '<test3 type="integer">123</test3>'."\n".
                    "</properties>";
        $this->assertTrue(strpos($request, $expected) > 0);
        
        // parameters
        $parameters = Array(
            'test1' => 'Test 1',
            'test2' => FALSE,
            'test3 type="integer"' => 123,
            );
        $request = wow_build_xml_request('Test', NULL, $parameters);
        $expected = "<parameters>\n".
                    "<test1>Test 1</test1>\n".
                    '<test2 type="boolean">false</test2>'."\n".
                    '<test3 type="integer">123</test3>'."\n".
                    "</parameters>";
        $this->assertTrue(strpos($request, $expected) > 0);
        $this->assertFalse(strpos($request, '<properties>'));
        
        // with array
        $parameters = Array(
            'test1' => 'Test 1',
            'phonearray' => Array(
                'number' => '1234567890',
                'ph_comment' => 'notes',
                ),
            );
        $request = wow_build_xml_request('Test', NULL, $parameters);
        $expected = "<parameters>\n".
                    "<test1>Test 1</test1>\n".
                    '<phonearray type="array"><row>'."\n".
                    "<col_name>number</col_name>\n".
                    "<col_value>1234567890</col_value>\n".
                    "</row>\n<row>\n".
                    "<col_name>ph_comment</col_name>\n".
                    "<col_value>notes</col_value>\n".
                    "</row>\n".
                    "</phonearray>\n".
                    "</parameters>";
        $this->assertTrue(strpos($request, $expected) > 0);
        
        // with double indexed array (e.g. services)
        $parameters = Array(
            'test1' => 'Test 1',
            'column_array' => Array(
                Array(
                    'columnname' => 'name1',
                    'newvalue' => 'abc',
                    ),
                Array(
                    'columnname' => 'name2',
                    'newvalue' => 'def',
                    ),
                ),
            );
        $request = wow_build_xml_request('Test', NULL, $parameters);
        $expected = "<parameters>\n".
                    "<test1>Test 1</test1>\n".
                    '<column_array type="array"><row>'."\n".
                    "<columnname>name1</columnname>\n".
                    "<newvalue>abc</newvalue>\n".
                    "</row>\n<row>\n".
                    "<columnname>name2</columnname>\n".
                    "<newvalue>def</newvalue>\n".
                    "</row>\n".
                    "</column_array>\n".
                    "</parameters>";
        $this->assertTrue(strpos($request, $expected) > 0);
        }
    
    function test_remove_empty_arrays()
        {
        $test = Array();
        $this->assertEqual(remove_empty_arrays($test), Array());
        
        $test = Array(
            1 => 'test',
            2 => '',
            3 => Array(
                1 => 'test',
                2 => Array(),
                ),
            4 => Array(),
            );
        // empty arrays have been changed to empty strings
        $expected = Array(
            1 => 'test',
            2 => '',
            3 => Array(
                1 => 'test',
                2 => '',
                ),
            4 => '',
            );
        $this->assertEqual(remove_empty_arrays($test), $expected);
        }
    }
