<?php  
namespace CodePi\Base\Libraries\Avalara;

use AvaTax\ATConfig;
use AvaTax\AddressServiceSoap;
use AvaTax\ValidateRequest;
use AvaTax\Address;
use AvaTax\SeverityLevel;
use AvaTax\TextCase;

class AvalarataxSoapLibrary {
	
    public function __construct() {
       // require(APPPATH.'/third_party/AvaTax/AvaTax.php');     // location of the AvaTax.PHP Classes - Required

        new ATConfig('Development', array(
           'url'       => 'https://development.avalara.net',
           'account'   => 'AvataxDev@ivienc.com',
           'license'   => 'Ivie_avalara001',
           'trace'     => true, // change to false for production
           'client' => 'AvaTaxSample',
           'name' => '14.4')
       );

       /* Production Account
        * TODO: Modify the account and license key 
        *       values below with your own.
        */
        new ATConfig('Production', array(
           'url'       => 'https://avatax.avalara.net',
           'account'   => 'AvataxDev@ivieinc.com',
           'license'   => 'haps25_fAwns',
           'trace'     => false, // change to false for development
               'client' => 'AvaTaxSample',
               'name' => '14.4')
       );
    }
    
  
	
function isAccountValidate(){

$client = new AccountServiceSoap('Development');
        try
        {
                $result = $client->isAuthorized("GetTax");
                
                echo 'IsAuthorized ResultCode is: '. $result->getResultCode()."\n";
                if($result->getResultCode() != SeverityLevel::$Success)	// call failed
                {
                        echo "isAuthorized(\"Validate\") failed\n";
                        foreach($result->Messages() as $idx => $msg)
                        {
                                echo $msg->getName().": ".$msg->getSummary()."\n";
                        }
                } 
                else 
                {
                        echo "isAuthorized succeeded\n";
                        echo 'Expiration: '. $result->getexpires()."\n\n";
                }
        }
        catch(SoapFault $exception)
        {
            print "ssss";
                $msg = "Exception: ";
                if($exception)
                        $msg .= $exception->faultstring;

                echo $msg."\n";
                echo $client->__getLastRequest()."\n";
                echo $client->__getLastResponse()."\n";
        }
}
function recon_tax_avatax_get_tax($order,$cust_code, $invoice_no,$type=0){
        $client = new TaxServiceSoap('Development');
	$request= new GetTaxRequest();
					
	//Add Origin Address
	$origin = new Address();
	$origin->setLine1("601 Silveron Blvd, Suite 200");
	//$origin->setLine2("Suite 200");
	$origin->setCity("Texas");
	$origin->setRegion("WA");
	$origin->setPostalCode("75028");
	$request->setOriginAddress($origin);	      //Address
	
	//$company_code = in_array($cust_code->corp_co,array('IVDIG','IVIEINC','GRNL','BUZZ','RDF'))?$cust_code->corp_co:'IVIEINC';
        $company_code='IVIEINC';
	$request->setCompanyCode($company_code);         // Your Company Code From the Dashboard
        //$request->setDocType(DocumentType::$SalesInvoice);   	// Only supported types are SalesInvoice or SalesOrder
        if ($type == 0) {
             $request->setDocType('SalesOrder');
        }
        else if ($type == 1){
              $request->setDocType('SalesInvoice');
        }

	$dateTime=new DateTime();
    $request->setDocCode($invoice_no);             //    invoice number
    $request->setDocDate(date_format($dateTime,"Y-m-d"));           //date
    $request->setSalespersonCode("");             // string Optional
    $request->setCustomerCode($cust_code->xid);        //string Required
    $request->setCustomerUsageType("");   //string   Entity Usage
    $request->setDiscount(0.00);            //decimal
    $request->setPurchaseOrderNo($invoice_no);     //string Optional
    $request->setExemptionNo("");         //string   if not using ECMS which keys on customer code
    $request->setDetailLevel(DetailLevel::$Tax);         //Summary or Document or Line or Tax or Diagnostic
    
    $request->setLocationCode("");        //string Optional - aka outlet id for tax forms

    
   //Add Destination address
       $i=2;
    $j=1;
	$tot_line = array();
	//echo "<pre>";print_r($order);
  foreach($order as $addr){
	 // if($j>0 && $j<600){
  	
	//$addresses[] = ${'destination' . $j};
        
	//print_r($addr['services']);
	foreach ($addr['services'] as $items) {
        //$request->setDestinationAddress(${'destination' . $j}); 
	${'destination' . $j} = new Address();                 // R: New instance of an address, we will use this as the destination
        ${'destination' . $j}->setLine1(trim($addr['addr1']));  
	${'destination' . $j}->setCity(trim($addr['city']));                // R: City
        ${'destination' . $j}->setRegion(trim($addr['state']));         
        ${'destination' . $j}->setPostalCode(trim($addr['zip']));
	${'destination' . $j}->setCountry(trim($addr['country'])); 
        ${'destination' . $j}->setAddressCode($i);       
           $request->setDestinationAddress(${'destination' . $j}); 
        
        ${'line' . $j} = new Line();
            ${'line' . $j}->setNo($j);    
            ${'line' . $j}->setDestinationAddress($origin);  
            ${'line' . $j}->setDestinationAddress(${'destination' . $j});            
            ${'line' . $j}->setItemCode(substr($items['s_name'],0,50));                   	
            ${'line' . $j}->setDescription($items['s_name']);              
            ${'line' . $j}->setTaxCode(strtoupper(trim($items['tax_code'])));               		
            ${'line' . $j}->setQty($items['qa']);                          		
            ${'line' . $j}->setAmount(trim($items['price']));                   			
            ${'line' . $j}->setDiscounted(false);                		
           
          
            $lines[] = ${'line' . $j};
            
            $tot_line[$j]['jeso_id']=$items['jeso_id'];
            $tot_line[$j]['jne_id']=$items['jne_id'];
            $tot_line[$j]['qty']=$items['qa'];
            $tot_line[$j]['amount']=trim($items['price']);
            $tot_line[$j]['jdd_id']=$items['jdd_id'];
            $tot_line[$j]['jdc_id']=$items['jdc_id'];
            $tot_line[$j]['addr_type']=$items['addr_type'];
            $tot_line[$j]['distribution_type_id']=$items['distribution_type_id'];
            $tot_line[$j]['r_tax_code']=strtoupper(trim($items['tax_code']));
            $tot_line[$j]['version_name']=$items['version_name'];

            $tot_line[$j]['addr1']=trim($addr['addr1']);
            $tot_line[$j]['city']=trim($addr['city']);
            //$tot_line[$j]['state']=trim($addr['state']);
            $tot_line[$j]['zip']=trim($addr['zip']);
            $tot_line[$j]['country']=trim($addr['country']);
   
	 // }
	   $j++;
  }
  //exit;
	$i++;
  } 
  
   $lineObject = new stdClass();			//object
   $lineObject->Line = $lines;
   $request->setLines($lineObject);
     // echo "<pre>";print_r($request);
	try
	{
		$getTaxResult = $client->getTax($request);
		//echo 'GetTax is: '. $getTaxResult->getResultCode()."\n";
	//print_r($getTaxResult);
		if ($getTaxResult->getResultCode() == SeverityLevel::$Success)
	        {
			//echo "DocCode: ".$request->getDocCode()."\n";			
	        //echo "TotalAmount: ".$getTaxResult->getTotalAmount()."\n";
	       // echo "TotalTax: ".$getTaxResult->getTotalTax()."\n";
			foreach($getTaxResult->getTaxLines() as $ctl)
			{
                                $getLineNo = $ctl->getNo();
				$tot_line[$getLineNo]['a_tax']=$ctl->getTax();
				$tot_line[$getLineNo]['a_tax_code']=$ctl->getTaxCode();
                                $tot_line[$getLineNo]['line_no']=$getLineNo;
                                $tot_line[$getLineNo]['DocCode']=$getTaxResult->getDocCode();
				//echo "     Line: ".$ctl->getNo()." Tax: ".$ctl->getTax()." TaxCode: ".$ctl->getTaxCode()."\n";
	
				foreach($ctl->getTaxDetails() as $ctd)
				{
                                    $tot_line[$getLineNo]['state']=trim($ctd->getRegion() );
					//echo "          Juris Type: ".$ctd->getJurisType()."; Juris Name: ".$ctd->getJurisName()."; Rate: ".$ctd->getRate()."; Amt: ".$ctd->getTax()."\n";
				}
				
			}//exit;
                        $data['Success']=1;
                       // print "%%%%%%%%%%5";
                       // print_r($tot_line);exit;
			$data['msg']=$tot_line;
			return $data;
		}
	        else
	        {        $msg_mess="";
			foreach($getTaxResult->getMessages() as $msg)
			{
				 $msg_mess= $msg->getName().": ".$msg->getSummary()."\n";
			}
                        $data['Success']=0;
			$data['msg']=$msg_mess;
			return $data;
		}
	
	}
	catch(SoapFault $exception)
	{
		$msg = "Exception: ";
		if($exception)
			$msg .= $exception->faultstring;
	
		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}

function validateAddress($data){
    ini_set('soap.wsdl_cache_enabled',0);
    ini_set('soap.wsdl_cache_ttl',0);   
    $addressSvc = new AddressServiceSoap('Development');
    $mess=array();
    try{
        $address = new Address();
        $address->setLine1($data['addr1']);
        $address->setLine2("");
        $address->setLine3("");
        $address->setCity($data['city']);
        $address->setRegion($data['state']);
        $address->setPostalCode($data['zip']);
        $textCase = TextCase::$Mixed;
        $coordinates = 1;
    //Request    
        $validateRequest = new ValidateRequest($address, ($textCase ? $textCase : TextCase::$Default), $coordinates);
	$validateResult = $addressSvc->Validate($validateRequest);
    //Results  
//        echo "\n" . 'Validate ResultCode is: ' . $validateResult->getResultCode() . "\n";
        if ($validateResult->getResultCode() != SeverityLevel::$Success){        
            foreach($validateResult->getMessages() as $msg){
                    $mess['verified'] = false;
                    $mess['message'] =  $msg->getName().": ".$msg->getSummary();
//                    $mess[$data['addr1']][$data['city']][$data['state']][$data['zip']]=$msg->getName().": ".$msg->getSummary();
            }
//            print_r($mess);exit;
        }else{
            foreach($validateResult->getValidAddresses() as $res){
                $mess['avalara']['address'] = $res->getLine1();
                $mess['avalara']['city'] = $res->getCity();
                $mess['avalara']['state'] = $res->getRegion();
                $mess['avalara']['zip'] = $res->getPostalCode();
                $mess['avalara']['country'] = $res->getCountry();
            }   
            $mess['verified'] = true;
            $mess['message'] = "Address verified Successfully";
        }
        return $mess;
    } catch (SoapFault $exception) {
        $message = "Exception: ";
        if ($exception){
            $message .= $exception->faultstring;
        }
        $mess['verified'] = false;
        $mess['message'] =  $message . " ,Last Request :".$addressSvc->__getLastRequest()." ,Last Response :".$addressSvc->__getLastResponse();
        return $mess;
    }
}
/*
        $i=2;
    $j=1;
	$tot_line = array();
	//print_r($order);exit;
  foreach($order as $addr){
	 // if($j>0 && $j<600){
  	${'destination' . $j} = new Address();                 // R: New instance of an address, we will use this as the destination
    ${'destination' . $j}->setLine1(trim($addr['addr1']));  
	${'destination' . $j}->setCity(trim($addr['city']));                // R: City
    ${'destination' . $j}->setRegion(trim($addr['state']));         
    ${'destination' . $j}->setPostalCode(trim($addr['zip']));
	${'destination' . $j}->setCountry(trim($addr['country'])); 
    ${'destination' . $j}->setAddressCode($i);       
	//$addresses[] = ${'destination' . $j};
         $request->setDestinationAddress(${'destination' . $j}); 
	//print_r($addr['services']);
	foreach ($addr['services'] as $items) {
	
            ${'line' . $j} = new Line();
            ${'line' . $j}->setNo($j);                             // R: string - line Number of invoice - must be unique.
            ${'line' . $j}->setItemCode(substr($items['s_name'],0,50));                   	// R: string - SKU or short name of Item
            ${'line' . $j}->setDescription($items['s_name']);              // O: string - Longer description of Item - R: for SST
            ${'line' . $j}->setTaxCode(strtoupper(trim($items['tax_code'])));               		// O: string - Tax Code associated with Item
            ${'line' . $j}->setQty($items['qa']);                          		// R: decimal - The number of items 
            ${'line' . $j}->setAmount(trim($items['price']));                   			// R: decimal - the "NET" amount of items  (extended amount)
            ${'line' . $j}->setDiscounted(false);                		// O: boolean - Set to true if line item is to discounted - see Discount
           
          
            $lines[] = ${'line' . $j};
            
            $tot_line[$j]['jeso_id']=$items['jeso_id'];
            $tot_line[$j]['jne_id']=$items['jne_id'];
            $tot_line[$j]['qty']=$items['qa'];
            $tot_line[$j]['amount']=trim($items['price']);
            $tot_line[$j]['jdd_id']=$items['jdd_id'];
            $tot_line[$j]['jdc_id']=$items['jdc_id'];
            $tot_line[$j]['addr_type']=$items['addr_type'];
            $tot_line[$j]['distribution_type_id']=$items['distribution_type_id'];
            $tot_line[$j]['r_tax_code']=strtoupper(trim($items['tax_code']));
            $tot_line[$j]['version_name']=$items['version_name'];

            $tot_line[$j]['addr1']=trim($addr['addr1']);
            $tot_line[$j]['city']=trim($addr['city']);
            //$tot_line[$j]['state']=trim($addr['state']);
            $tot_line[$j]['zip']=trim($addr['zip']);
            $tot_line[$j]['country']=trim($addr['country']);
   
	 // }
	   $j++;
  }
  //exit;
	$i++;
  } 
  
   $lineObject = new stdClass();			//object
   $lineObject->Line = $lines;
   $request->setLines($lineObject);
 * 
 */


/*
 $destination=  new Address();
	$destination->setLine1("900 Winslow Way");
	
	$destination->setCity("Bainbridge Island");
	$destination->setRegion("WA");
	$destination->setPostalCode("98110");
        $request->setDestinationAddress($destination);
        
        $destination1=  new Address();
	$destination1->setLine1("10 King Street");
	
	$destination1->setCity("Waldorf");
	$destination1->setRegion("MD");
	$destination1->setPostalCode("20602");
	$request->setDestinationAddress($destination1);
	//$request->setDestinationAddress	($address);     //Address
	//Add line
        $line1 = new Line();
        $line1->setNo ("2");                  //string  // line Number of invoice
        $line1->setItemCode("SKU123");            //string
        $line1->setDestinationAddress($destination);   
        $line1->setDescription("Invoice Calculated From PHP SDK");         //string
        $line1->setTaxCode("");             //string
        $line1->setQty(1.0);                 //decimal
        $line1->setAmount(1000.00);              //decimal // TotalAmmount
        $line1->setDiscounted(false);          //boolean
        $lines[] = ${'line1'};
        $line2 = new Line();
        $line2->setNo ("1");                  //string  // line Number of invoice
        $line2->setDestinationAddress($destination1);   
        $line2->setItemCode("SKU122");            //string
        $line2->setDescription("Invoice Calculated From PHP SDK");         //string
        $line2->setTaxCode("");             //string
        $line2->setQty(1.0);                 //decimal
        $line2->setAmount(1000.00);              //decimal // TotalAmmount
        $line2->setDiscounted(false);   
	 $lines[] = ${'line2'};
	$lineObject = new stdClass();			//object
	$lineObject->Line = $lines;
	$request->setLines($lineObject);
  */
}

