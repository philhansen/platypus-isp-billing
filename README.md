platypus-isp-billing
====================

PHP code for working with the Platypus ISP Billing API

## wow.inc.php

Functions for using the Platypus WOW API as described in the API documentation. This is the implementation I am currently using on a project.  
You will need to make some adjustments for your own implementation - particularly setting the appropriate username and password, setting
the WOW API server details, and adjusting the logging functionality.

Calling an API function is as simple as: `wow_call_function('GetServiceDefs');`

The data will be returned as an associative array or NULL on error.  The last error message can be retrieved by calling
`wow_get_last_error()`

Retrieving a customer record: `$customer = wow_call_function('GetCustomer', Array('custid'=>$customer_id));`

Example of an API method requiring both a property and a parameter:
`$invoice_details = wow_call_function('GetInvoiceDetails', Array('custid'=>$cust_id), Array('invoice'=>$id));`

## testWOW.php

Unit tests for the functions in wow.inc.php.  The unit tests use the SimpleTest PHP unit testing library: 
http://www.simpletest.org/

## Contact / Discussing Platypus

If you are working with the Platypus API and would like to have someone to dialog with, feel free to contact me!