<?php
// Functions for using the Platypus WOW API
// Phil Hansen, 17 December 2013
// Copyright (C) 2013 Phil Hansen

// If a function returns an error the message will be filled in here
$wow_error_message = NULL;

/// Calls a WOW API function
///    method - the WOW API method to call
///    properties - an array of properities name=>value
///    parameters - an array of parameters name=>value
///    object - specify 'addusr' for plat functions, 'wombat' for ticket functions
///    request - use a pre-built XML request rather than building it (useful for specific customization)
/// Returns the returned data as an associative array or NULL on error
function wow_call_function($method, $properties=NULL, $parameters=NULL, $object = 'addusr', $request=NULL)
    {
    global $wow_error_message;
    // clear any previous error
    $wow_error_message = NULL;
    if (is_null($request))
        $request = wow_build_xml_request($method, $properties, $parameters, $object);
    
    $response = wow_send_request($request);
    if (is_null($response))
        return NULL;
    
    // convert the returned XML to an associative array
    $xml = @simplexml_load_string($response);
    $response = json_decode(json_encode($xml), TRUE);
    // change empty array values to empty strings
    $response = remove_empty_arrays($response);
    
    // check response code
    // codes and meanings taken from the WOW API documentation
    if ($response['body']['data_block']['response_code'] != 'SUCCESS')
        {
        $response_code = $response['body']['data_block']['response_code'];
        if ($response_code == 'PARAM_ERROR')
            $msg = 'An invalid parameter type or an invalid number of parameters was passed';
        else if ($response_code == 'KEY_ERROR')
            $msg = "The Platypus key in your database is expired or corrupt or you haven't purchased a license that gives you access to the web object";
        else if ($response_code == 'ODBC_ERROR')
            $msg = "An error occurred attempting to connect to the database";
        else if ($response_code == 'DATA_ERROR')
            $msg = ''; // generic error
        else if ($response_code == 'XML_PARSE_ERROR')
            $msg = 'The XML request or response is not valid XML';
        else if ($response_code == 'XML_ERROR')
            $msg = 'An XML parse error occurred';
        else if ($response_code == 'LOGIN_ERROR')
            $msg = 'The login information supplied is invalid';
        else if ($response_code == 'PROPERTY_ERROR')
            $msg = 'A required property is not set or is an invalid type';
        else if ($response_code == 'PERMIT_ERROR')
            $msg = 'The logged in staff/customer member does not have security to run this method';
        else if ($response_code == 'TRANSID_ERROR')
            $msg = 'Unable to generate a new transaction id';
        else
            $msg = "unrecognized error code: $response_code";
        
        // include the response message if available
        if (@$response['body']['data_block']['response_text'] != '')
            {
            if ($msg != '')
                $msg .= ': ';
            $msg .= $response['body']['data_block']['response_text'];
            }
        $wow_error_message = $msg;
        wow_log(LogLevel::DEBUGGING, "Error calling $method: $msg");
        return NULL;
        }
    
    // prepare the returned data
    // each row is included as an array
    $r = Array();
    if (isset($response['body']['data_block']['attributes']) && is_array($response['body']['data_block']['attributes']))
        {
        foreach ($response['body']['data_block']['attributes'] as $row)
            $r[] = $row;
        }
    if (count($r) == 1)
        $r = $r[0];
    return $r;
    }

/// Provides a simpler interface for calling ticket functions
///   i.e. the object needs to be set to 'wombat' for each call
function wow_call_ticket_function($method, $properties=NULL, $parameters=NULL)
    {
    return wow_call_function($method, $properties, $parameters, 'wombat');
    }

/// Builds the formatted XML request for sending to the WOW API
///    method - the WOW API method to call
///    properties - an array of properities name=>value
///    parameters - an array of parameters name=>value
///    object - specify 'addusr' for plat functions, 'wombat' for ticket functions
function wow_build_xml_request($method, $properties=NULL, $parameters=NULL, $object='addusr')
    {
    global $USER;
    $request = '<?xml version="1.0"?>
    <PLATXML>
        <header></header>
        <body>
            <data_block>
                <protocol>Plat</protocol>
                <object>'.$object.'</object>
                <action>'.$method.'</action>
                <username>'.$USER->name.'</username>
                <password>'.$USER->password.'</password>
                <logintype>staff</logintype>';
    // add parameters
    if (!is_null($parameters) && count($parameters) > 0)
        {
        $request .= "<parameters>\n";
        foreach ($parameters as $name=>$value)
            {
            $type = NULL;
            // some names have types included also, parse it out
            if (strpos($name, ' ') !== FALSE)
                list($name, $type) = explode(' ', $name);
            $request .= "<$name";
            if (@$type != '')
                $request .= ' ' . $type;
            // special case, sending a boolean false (usually for an optional parameter)
            else if ($value === FALSE)
                $request .= ' type="boolean"';
            // special case, array of values
            else if (is_array($value))
                $request .= ' type="array"';
            $request .= '>';
            
            if ($value === FALSE)
                $request .= 'false';
            // special case, array of values
            else if (is_array($value))
                {
                foreach ($value as $sub_name=>$sub_value)
                    {
                    $request .= "<row>\n";
                    // special case, another array of values (e.g. services)
                    if (is_array($sub_value))
                        {
                        foreach ($sub_value as $sub_sub_name=>$sub_sub_value)
                            $request .= "<$sub_sub_name>$sub_sub_value</$sub_sub_name>\n";
                        }
                    // standard way to process an array
                    else
                        {
                        $request .= "<col_name>$sub_name</col_name>\n";
                        $request .= "<col_value>$sub_value</col_value>\n";
                        }
                    $request .= "</row>\n";
                    }
                }
            else
                $request .= $value;
            $request .= "</$name>\n";
            }
        $request .= "</parameters>\n";
        }
    
    // add properties
    if (!is_null($properties) && count($properties) > 0)
        {
        $request .= "<properties>\n";
        foreach ($properties as $name=>$value)
            {
            $type = NULL;
            // some names have types included also, parse it out
            if (strpos($name, ' ') !== FALSE)
                list($name, $type) = explode(' ', $name);
            $request .= "<$name";
            if (@$type != '')
                $request .= ' ' . $type;
            // special case, sending a boolean false (usually for an optional parameter)
            else if ($value === FALSE)
                $request .= ' type="boolean"';
            $request .= '>';
            
            if ($value === FALSE)
                $request .= 'false';
            else
                $request .= $value;
            $request .= "</$name>\n";
            }
        $request .= "</properties>\n";
        }
    
    $request .= '</data_block>
        </body>
    </PLATXML>';
    return $request;
    }

/// Takes a formatted WOW XML request and sends it to the WOW API server
/// Reads the response and returns it, returns NULL on error
function wow_send_request($request)
    {
    global $CONF;
    $errorNumber = NULL;
    $errorString = NULL;
    $package = "content-length:".strlen($request)."\r\n\r\n".$request;
    $socket = fsockopen($CONF['wow_server'], $CONF['wow_port'], $errorNumber, $errorString);
    if (!$socket)
        {
        $msg = "WOW API request error: $errorString ($errorNumber)\n";
        wow_log(LogLevel::ERROR, $msg);
        return NULL;
        }
    
    // Send request
    if (fwrite($socket, $package, strlen($package)) === FALSE)
        {
        $msg = "WOW API request error: cannot write to socket\n";
        wow_log(LogLevel::ERROR, $msg);
        wow_close_connection($socket);
        return NULL;
        }
    
    // set timeout to 4 min
    stream_set_timeout($socket, 240);
    // get first line
    $contentHeader = fgets($socket);
    if ($contentHeader === FALSE)
        {
        $msg = "WOW API request error: cannot read from socket\n";
        wow_log(LogLevel::ERROR, $msg);
        wow_close_connection($socket);
        return NULL;
        }
    else if (stripos($contentHeader, 'content-length:') === FALSE)
        {
        $msg = "WOW API request error: unexpected content-length header: $contentHeader\n";
        wow_log(LogLevel::ERROR, $msg);
        wow_close_connection($socket);
        return NULL;
        }
    $contentHeader = str_ireplace('content-length:', '', $contentHeader);
    $expected_size = intval(trim($contentHeader));
    
    // clear the blank line
    fgets($socket);
    
    // get the xml response package
    $buffer = fread($socket, $expected_size);
    if (!$buffer)
        {
        $msg = "WOW API request error: error reading response package\n";
        wow_log(LogLevel::ERROR, $msg);
        wow_close_connection($socket);
        return NULL;
        }
    
    $read_size = strlen($buffer);
    $package = $buffer;
    // keep going if we expect more
    while($read_size < $expected_size)
        {
        $buffer = fread($socket, ($expected_size - $read_size));
        if (!$buffer)
            {
            $msg = "WOW API request error: error reading response package\n";
            wow_log(LogLevel::ERROR, $msg);
            wow_close_connection($socket);
            return NULL;
            }
        
        $read_size += strlen($buffer);
        $package .= $buffer;
        }

    wow_close_connection($socket);
    return $package;
    }

/// Close the connection on the given socket
function wow_close_connection($socket)
    {
    // from the WOW docs:
    // the QUIT command is used exclusively for indicating to the server that the transfer is complete
    $msg = "QUIT\r\n";
    fwrite($socket, $msg, strlen($msg));
    fclose($socket);
    }

/// Returns the last error message
function wow_get_last_error()
    {
    global $wow_error_message;
    return $wow_error_message;
    }

// Write a log message
function wow_log($level, $message)
    {
    // Use your own logging implementation here
    print("WOW API: $level: $message\n");
    }

/// Checks each value in an array and converts values containing
/// an empty array to an empty string instead
/// e.g. Array('test'=>1, 'test2' => Array() ) will be returned as
///      Array('test'=>1, 'test2' => '')
function remove_empty_arrays($data)
    {
    foreach ($data as $key=>&$value)
        {
        if (is_array($value))
            {
            if (count($value) == 0)
                $value = '';
            else
                $value = remove_empty_arrays($value);
            }
        }
    return $data;
    }