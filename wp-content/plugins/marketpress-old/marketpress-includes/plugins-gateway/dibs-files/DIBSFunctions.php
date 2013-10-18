<?php

/*! \mainpage DIBS PHP Functions */

/*!-----------------------------------------------------------------------------------
This set of PHP functions constitutes a simplified integration of the DIBS API
It enables basic interaction between DIBS internet payment gateway and PHP.
Copyright (C) Lars Wichmann Hansen / Kaka Consult (post@kaka-consult.dk)
Version 1.8 (Oct 2008)

Changes from Version 1.7
     - the calculation of MD5 key in DIBSRefund has been corrected, as it previously added the variables in the wrong order resulting in a wrong key.
Changes from Version 1.6 
     - clean up of the documentation 
     - check on params not to contain any &'s
     - test parameter is set in the input rather than hardcoded
Changes from Version 1.5
     - cleaned up bits and pieces
Changes from Version 1.3
     - addition of postype on DIBSAuth, and MD5-keys in capture
Changes from Version 1.2
     - addition of the function DIBSCancel, which truely cancels an already authorised transaction.

These functions are free software; you can redistribute them and/or
modify them under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

These functions are distributed in the hope that they will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.


Please note the following:                                                                   
- a basic understanding of PHP and the DIBS API is compulsary to employ these functions       
- Your server/PHP MUST be compiled with (open-)ssl to enable server-to-server https requests
- DIBS internet payment gateway is NOT a free service and this set of functions is pretty useless   
  without a suitable DIBS account - please see www.dibs.dk for further information           
- read (and understand) the DIBS API documentation before asking any questions.   
- These functions employs the DIBS API in its (almost) simplest form
  several other parameters/options are available in the DIBS API.
  If you wish to send more parameters to the DIBS API through the server-to-server https requests.
  Simply add these onto the variable $postvars, as you would on a normal GET-request
   i.e. $postvars = $postvars."&VarX=17&VarY=12831"
  The corresponding response from DIBS is available in the response array
   i.e. $RES['VarX-response']

Acknowledgements:
     - Kjetil Stadskleiv for pointing out a bug in MD5 key calculation
     - Jens Agerberg and Frederick Bjork for pointing out a few bugs
     - Jon Helge Stensrud for commenting on a security issue.

-------------------------------------------------------------------------------------*/


/*!-----------------------------------------------------------------------------------
DESCRIPTION OF THE FUNCTIONS

FUNCTIONS
 DIBSAuth($Merchant,$Amount,$Currency,$CardNo,$ExpMon,$ExpYear,$CVC,$OrderID,$postype,$MD5,$test) 
  Authorisation of creditcard and payment

 DIBSCapt($Merchant,$Amount,$Transact,$OrderID,$test) 
  Capture already authorised payment

 DIBSTicketPreAuth($Merchant,$Currency,$CardNo,$ExpMon,$ExpYear,$CVC,$OrderID,$test) 
  Ticket pre-authorisation 

 DIBSTicketAuth($Merchant,$Ticket,$Amount,$Currency,$OrderID,$MD5,$test) 
  Ticket payment authorisation

 DIBSReAuth($Merchant,$Transact) 
  Reauhtorise a creditcard/payment

 DIBSCancel($Merchant,$Transact,$OrderID) 
  Truely cancels and authorised transaction.

 DIBSTransInfo($OrderID,$Merchant,$Currency,$Amount) 
  Returns the status for a particular order

 DIBSTransStat($OrderID,$Merchant,$Transact,$Currency,$Amount) 
  Returns the status for a particular transaction

 DIBSTransAmount($Transact,$OrderID,$Merchant,$Currency) 
  Returns the amount on an authorised transaction

 DIBSPayInfo($Transact,$Auth)
  Returns the amount, currency, orderid, status, transact, and approvalcode for a transaction

 DIBSChangeStatus($Merchant,$Amount,$Transact,$Action,$Auth) 
  Change the status of a transaction, e.g. canceled (see API dcoumentation for other options)

 DIBSCardType($Merchant,$Transact)
  Returns the cardtype for a given transaction (see API documentation for possible answers)

 DIBSTransStat2($Merchant,$Transact)
  Returns the status for a given transaction (see API transstatus.pml documentation for possible answers)

 DIBSRefund($Merchant,$Amount,$Currency,$Transact,$OrderID,$Auth,$MD5)
  Makes a refund
  NB! Requires a special arrangement/deal with the acquierer (see API documentation for possible answers)


INPUTS
  $MerchantID - a unique DIBS merchant identification number
  $Amount - the amount to be drawn on the creditcard 
    (always in smallest possible unit, e.g. øre when using DKK)
  $Currency - currency code senso ISO4217, e.g. 208 for DKK
  $CardNo - creditcard number
  $ExpMon - expiry month
  $ExpYear - expiry year
  $CVC - control numbers
  $OrderID - a unique order identification number
  $Transact - unique transaction identification as returned by DIBSAuth for successfull authorisations
  $Ticket - ticket identification as returned by DIBSTicketPreAuth for a successfull pre-authorisation 
  $Auth - basic authentication information (ie. DIBS login and password), 
    Where: $Auth['username'] = DIBSuser, $Auth['password'] = DIBSUserPassword
  $Action - which action to do when using DIBSChangeStatus
  $postype - point-of-sale - optional (default ssl), it determines the postype used (ssl, phone, mail)
  $test - set $test="yes" if the transaction is a test-transaction (default is "no").
  $MD5 (optional) - a set of unique keys for a generating md5 value-checks
    The variable $MD5 is optional, but if used must be an array with your
    specific shop's special MD5-keys inputted with K1 and K2 as indices
    Also, the MD5key control must be enabled in the DIBS user-interface a priori.


OUTPUT
  All functions return an associative array containing the response from DIBS
  If no response was available from DIBS or something went wrong the functions return false.
  What variables that are available in the reponse array is defined in the DIBS API documentation.
  For instance, a successful authorisation returns the variable 'transact',
-------------------------------------------------------------------------------------*/

/*!-----------------------------------------------------------------------------------
SIMPLIFIED EXAMPLE
  Authorisation and immediate capture of a creditcard payment

Define input variables (here simply static variables)
  $Merchant = "123456789";
  $OrderID = "AHJ798123AH-BH";
  $Currency = "208"; DKK
  $Amount = "30000"; In smallest possible unit 30000 Øre = DKK 300 
  $CardNo = "5019100000000000"; DIBS test Dankort values
  $ExpMon = "06"; DIBS test Dankort value
  $ExpYear = "05"; DIBS test Dankort value
  $CVC = "684"; DIBS test Dankort value
  $MD5['K1'] = "~.(S96%u|(UV,~ifxTt.DAKSNb&SKAHD"; K1 and K2 MUST be gathered through
  $MD5['K2'] = "qJuH6vjXHLSDB*%¤&/hbnkjlBHGhjJKJ"; ones DIBS admin-webinterface.

Call function DIBSAuth to authorise payment
  $RES = DIBSAuth($Merchant,$Amount,$Currency,$CardNo,$ExpMon,$ExpYear,$CVC,$OrderID,$MD5);

Check the response (the DIBS API returns the variable transact on success)
  if ( $RES['transact'] != "" ) {
    printf ("Authorisation successful! TransaktionID = %s",$RES['transact']);   

Call function DIBSCapt to capture payment
    $RES2 = DIBSCapt($Merchant,$Amount,$RES['transact'],$OrderID);
    if ( $RES2['status'] == "ACCEPTED" ) {
      printf ("Transaction completed");
    } else {
      printf ("Capture failed!");  
    }
  } else {
    printf ("Authorisation failed");
  }
-------------------------------------------------------------------------------------*/


/*-------------------------------------------------------------------------------------*/
//!A function for asking DIBS for authorisation of a creditcard payment 
function DIBSAuth($Merchant,$Amount,$Currency,$CardNo,$ExpMon,$ExpYear,$CVC,$OrderID,$postype="ssl",$MD5="",$test="no") {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&amount=".parmchk($Amount);

  //Check if MD5key check is used and add if it is
  if ( count($MD5) == 2 ) {
    $md5Key = md5($MD5['K2'].md5($MD5['K1'].$postvars));
    $postvars .= "&md5key=".$md5Key;
  }

  $postvars .= "&cardno=".parmchk($CardNo);
  $postvars .= "&expmon=".parmchk($ExpMon);
  $postvars .= "&expyear=".parmchk($ExpYear);
  $postvars .= "&cvc=".parmchk($CVC);
  $postvars .= "&textreply=yes";
  $postvars .= "&postype=".parmchk($postype);

  //Add testvar if "yes"
  if ( $test == "yes" ) {
    $postvars .= "&test=yes"; 
  }

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-ssl/auth.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $AuthInfo = false;
  } else { //Response is good (does not mean that authorisation was granted)
    $AuthInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $AuthInfo[$A[0]] = $A[1];
    }
  }
  
  //Check that the returned MD5 key is correct
  if ( $AuthInfo['authkey'] <> "" ) {
    $vars = "transact=".$AuthInfo['transact']."&amount=".$Amount."&currency=".$Currency;
    $Control = md5($MD5['K2'].md5($MD5['K1'].$vars));
    if ( $Control <> $AuthInfo['authkey'] ) {
      $AuthInfo = array();
      $AuthInfo['result'] = "MD5key authorisation failed";
    }
  }

  return array (
  'authinfo' => $AuthInfo,
  'postvars' => $postvars,
  );
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for capturing an already authorised creditcard payment
function DIBSCapt($Merchant,$Amount,$Transact,$OrderID,$MD5,$test="no") {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&amount=".parmchk($Amount);

  //Check if MD5key check is used and add if it is
  if ( count($MD5) == 2 ) {
    $md5Key = md5($MD5['K2'].md5($MD5['K1'].$postvars));
    $postvars .= "&md5key=".$md5Key;
  }
  
  $postvars .= "&textreply=yes";
  $postvars .= "&force=true";

  //Add testvar if "yes"
  if ( $test == "yes" ) {
    $postvars .= "&test=yes"; 
  }
  
  //Send post request
  $response = http_post('payment.architrade.com','/cgi-bin/capture.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N == 1 ) {
    $CaptInfo['result'] = $response[0];
  } else {
    if ( $N < 2 ) { //Response is an error
      $CaptInfo = false;
    } else { //Response is good (does not mean that the capture was successfull)
      $CaptInfo = array(); //Define output array
      while ( $N-- > 0 ) {
	$A = explode("=",$response[$N]);
	$CaptInfo[$A[0]] = $A[1];
      }
    }
  }
  
  return $CaptInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for pre-authorising of a payment-ticket
function DIBSTicketPreAuth($Merchant,$Currency,$CardNo,$ExpMon,$ExpYear,$CVC,$OrderID,$test="no") {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&preauth=foo";
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&cardno=".parmchk($CardNo);
  $postvars .= "&expmon=".parmchk($ExpMon);
  $postvars .= "&expyear=".parmchk($ExpYear);
  $postvars .= "&cvc=".parmchk($CVC);
  $postvars .= "&textreply=yes";

  //Add testvar if "yes"
  if ( $test == "yes" ) {
    $postvars .= "&test=yes"; 
  }

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-ssl/auth.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $AuthInfo = false;
  } else { //Response is good (does not mean that the authorisation was granted)
    $AuthInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $AuthInfo[$A[0]] = $A[1];
    }
  }

  return $AuthInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for authorising of a payment using an existing ticket
function DIBSTicketAuth($Merchant,$Ticket,$Amount,$Currency,$OrderID,$MD5="",$test="no") {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&ticket=".parmchk($Ticket);
  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&amount=".parmchk($Amount);

  //Check if MD5key check is used and add if it is
  if ( count($MD5) == 2 ) {
    $md5Key = md5($MD5['K2'].md5($MD5['K1'].$postvars));
    $postvars .= "&md5key=".$md5Key;
  }

  //Add testvar if "yes"
  if ( $test == "yes" ) {
    $postvars .= "&test=yes"; 
  }
  $postvars .= "&textreply=yes";
 
  //Send post request
  $response = http_post('payment.architrade.com','/cgi-ssl/ticket_auth.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $AuthInfo = false;
  } else { 
    $AuthInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $AuthInfo[$A[0]] = $A[1];
    }
  }

  return $AuthInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for reauthorising a creditcard payment
function DIBSReAuth($Merchant,$Transact) {

  //Set up post-variable string
  $postvars = "&merchant=".parmchk($Merchant);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&textreply=yes";

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-bin/reauth.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $CaptInfo = false;
  } else { 
    $CaptInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $CaptInfo[$A[0]] = $A[1];
    }
  }

  return $AuthInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for truely canceling an authorised transaction
function DIBSCancel($Merchant,$Transact,$OrderID,$Auth) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&textreply=yes";

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-adm/cancel.cgi', $postvars, $Auth );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $CaptInfo = false;
  } else { 
    $CaptInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $CaptInfo[$A[0]] = $A[1];
    }
  }

  return $CaptInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for transaction information.
function DIBSTransInfo($OrderID,$Merchant,$Currency,$Amount) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&amount=".parmchk($Amount);

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-bin/transinfo.cgi', $postvars );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $TransInfo = false;
  } else { //Response is good
    $TransInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $TransInfo[$A[0]] = $A[1];
    }
  }

  return $TransInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for transaction status
function DIBSTransStat($OrderID,$Merchant,$Transact,$Currency,$Amount) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&amount=".parmchk($Amount);

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-bin/transstat.cgi', $postvars );

  //Deal with response
  if ( $response == 0 ) { //If zero is returned its because its an error
    $Output = false;
  } else { //If not zero, then the amount is returned
    $Output['status'] = $response;
  }
  return $Output;

}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for transaction amount.
function DIBSTransAmount($Transact,$OrderID,$Merchant,$Currency) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&currency=".parmchk($Currency);

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-bin/confirmtransact.cgi', $postvars );
  if ( $response == 0 ) { //If zero is returned its because its an error
    $Output = false;
  } else { //If not zero, then the amount is returned
    $Output['amount'] = $response;
  }
  return $Output;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for payment information
function DIBSPayInfo($Transact,$Auth) {

  //Set up post-variable string
  $postvars = "&transact=".parmchk($Transact);

  //Send post request
  $response = http_post("payment.architrade.com","/cgi-adm/payinfo.cgi", $postvars, $Auth );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 2 ) { //Response is an error
    $TransInfo = false;
  } else { //Response is good
    $TransInfo = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $TransInfo[$A[0]] = $A[1];
    }
  }

  return $TransInfo;
}
/*-------------------------------------------------------------------------------------*/


//!A function for changing the status of a transaction
function DIBSChangeStatus($Merchant,$Amount,$Transact,$Action,$Auth) {

  //Set up post-variable string
  $postvars = "&merchant=".parmchk($Merchant);
  $postvars .= "&amount=".parmchk($Amount);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&action=".parmchk($Action);
  $postvars .= "&textreply=yes";

  //Send post request
  $response = http_post('payment.architrade.com','/cgi-adm/changestatus.cgi', $postvars, $Auth );

  //Deal with reponse
  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 1 ) { //Response is an error
    $Out = false;
  } else { //Response is good
    $Out = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $Out[$A[0]] = $A[1];
    }
  }

  return $Out;
}
/*-------------------------------------------------------------------------------------*/


//!A function for looking up cardtype
function DIBSCardType($Merchant,$Transact) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&transact=".parmchk($Transact);

  //Send post request
  $response = http_post('payment.architrade.com','/cardtype.pml', $postvars );

  //Deal with reponse
  if ( $response == "" ) { //Response is an error
    $Out = false;
  } else { //Response is good
    $Out['CardType'] = $response;
  }

  return $Out;
}
/*-------------------------------------------------------------------------------------*/


//!A function for looking up status of the transaction using transstatus.pml
function DIBSTransStat2($Merchant,$Transact) {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&transact=".parmchk($Transact);

  //Send post request
  $response = http_post('payment.architrade.com','/transstatus.pml', $postvars );

  //Deal with reponse
  if ( $response == "" ) { //Response is an error
    $Out = false;
  } else { //Response is good
    $Out['Status'] = $response;
  }

  return $Out;
}
/*-------------------------------------------------------------------------------------*/


//!A function for asking DIBS for refund
//!NB! Requires a special aggrement/setup with the acquirer (e.g. PBS)
function DIBSRefund($Merchant,$Amount,$Currency,$Transact,$OrderID,$Auth,$MD5="") {

  //Set up post-variable string
  $postvars = "merchant=".parmchk($Merchant);
  $postvars .= "&orderid=".parmchk($OrderID);
  $postvars .= "&transact=".parmchk($Transact);
  $postvars .= "&amount=".parmchk($Amount);

  //Check if MD5key check is used and add if it is
  if ( count($MD5) == 2 ) {
    $md5Key = md5($MD5['K2'].md5($MD5['K1'].$postvars));
    $postvars .= "&md5key=".$md5Key;
  }

  $postvars .= "&currency=".parmchk($Currency);
  $postvars .= "&textreply=yes";
  
  //Send post request
  $response = http_post("payment.architrade.com","/cgi-adm/refund.cgi", $postvars, $Auth );

  $response = explode("&",$response);
  $N = count($response);
  if ( $N < 1 ) { //Response is an error
    $Out = false;
  } else { //Response is good
    $Out = array(); //Define output array
    while ( $N-- > 0 ) {
      $A = explode("=",$response[$N]);
      $Out[$A[0]] = $A[1];
    }
  }

  return $Out;
}
/*-------------------------------------------------------------------------------------*/


//!Function for sending a 'within-php' post-request and receive the response-body as a string
function http_post($host, $path, $data, $auth="") {

  $sock = fsockopen("ssl://".$host, 443, $errno, $errstr, 30);
  if (!$sock) die("$errstr ($errno)\n");  
  
  fwrite($sock, "POST ".$path." HTTP/1.0\r\n");
  fwrite($sock, "Host: ".$host."\r\n");
  fwrite($sock, "Content-type: application/x-www-form-urlencoded\r\n");
  fwrite($sock, "Content-length: " . strlen($data) . "\r\n");
  fwrite($sock, "User-Agent: PHP-DIBS (Kaka Consult) v1.0 \r\n"); 

  //If basic authentication is required (e.g. payinfo.cgi, changestatus.cgi and refund.cgi)
  if ( is_array($auth) ) {
    fwrite($sock, "Authorization: Basic ".base64_encode($auth['username'].":".$auth['password'])."\r\n");
  }
  
  fwrite($sock, "Accept: */*\r\n");
  fwrite($sock, "\r\n");
  fwrite($sock, $data."\r\n");
  fwrite($sock, "\r\n");

  //Take out the header first  
  $headers = "";
  while ( $str = trim(fgets($sock, 4096)) ) {
    $headers .= "$str\n";
  }
      
  //Then collect the body and prepare for returning
  $body = "";
  while ( !feof($sock) ) {
    $body .= fgets($sock, 4096);
  }  
  
  fclose($sock);

  return $body;
}
/*-------------------------------------------------------------------------------------*/


//!Function for checking that the parameters do not contain any &, which may corrupt message to DIBS
function parmchk($in) {
  return str_replace("&","",$in);
}
/*-------------------------------------------------------------------------------------*/


?>