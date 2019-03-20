<?php  
namespace CodePi\Base\Libraries\Avalara;


class AvalarataxLibrary {
	
	public function __construct() {
		 require(APPPATH.'/third_party/AvaTax4PHP/AvaTax.php');     // location of the AvaTax.PHP Classes - Required

/*$this->client = new TaxServiceRest(
	"https://avatax.avalara.net", // TODO: Enter service URL
	"AvataxDev@ivieinc.com", //TODO: Enter Username or Account Number AvataxDev@ivienc.com
	"haps25_fAwns"); //TODO: Enter Password or License Key*/
$this->client = new TaxServiceRest(
	"https://development.avalara.net", // TODO: Enter service URL
	"AvataxDev@ivienc.com", //TODO: Enter Username or Account Number AvataxDev@ivienc.com
	"Ivie_avalara001"); //TODO: Enter Password or License Key
    }
	
function recon_tax_avatax_get_tax($order,$cust_code, $invoice_no,$type=0){
	

  // Include in all Avalara Scripts.
 
	
$request = new GetTaxRequest();

//Document Level Setup  
//     R: indicates Required Element
//     O: Indicates Optional Element
//
    $dateTime = new DateTime();                                  // R: Sets dateTime format 
	 date_format($dateTime, "Y-m-d");
	 $company_code = in_array($cust_code->corp_co,array('IVDIG','IVIEINC','GRNL'))?$cust_code->corp_co:'IVIEINC';
 $request->setCompanyCode($company_code);
    //$request->setCompanyCode("IVIEINC");                    // R: Company Code from the accounts Admin Console
    
   
	
    $request->setDocDate(date_format($dateTime, "Y-m-d"));  // R: Date the document is processed and Taxed - See TaxDate
    $request->setCustomerCode($cust_code->xid);             // R: String - Customer Tracking number or Exemption Customer Code
    $request->setDocCode($invoice_no);                         // R: Invoice or document tracking number - Must be unique
//  //$request->setDocType('SalesOrder'); // $request->setDocType(DocumentType::$SalesOrder);  // R: Typically SalesOrder,SalesInvoice, ReturnInvoice
    if ($type == 0) {
             $request->setDocType('SalesOrder');
      }
      else if ($type == 1){
            $request->setDocType('SalesInvoice');
      }
      else {
            $request->setDocType('ReturnInvoice');
      }
//  
// 
    
    
    $request->setDiscount(0);                   // O: Decimal - amount of total document discount
    $request->setPurchaseOrderNo($invoice_no);    // O: String 
    $request->setExemptionNo("");           // O: String   if not using ECMS which keys on customer code
    $request->setDetailLevel(DetailLevel::$Tax);     // R: Chose $Summary, $Document, $Line or $Tax - varying levels of results detail 
	if ($type == 1){
    	$request->setCommit(FALSE);                    // O: Default is FALSE - Set to TRUE to commit the Document
	}else{
		$request->setCommit(FALSE);                    // O: Default is FALSE - Set to TRUE to commit the Document
	}

    $origin = new Address();                    // R: New instance of an address, we will use this for the origin
	$destination = new Address();                 // R: New instance of an address, we will use this as the destination
	$addresses = array();
//Add Origin Address OR Populate the From address.
    $origin->setLine1("601 Silveron Blvd, Suite 200");    // O: It is not required to pass a valid street address, but it will provide for a better tax calculation.
    $origin->setCity("Flower Mound");                // R: City
    $origin->setRegion("Texas");              		// R: State or Province
    $origin->setPostalCode("75028");      		// R: String (Expects to be NNNNN or NNNNN-NNNN or LLN-LLN)
    $origin->setAddressCode("01");            	// R: Allows us to use the address on our Lines
	$addresses[] = $origin;						// 		Adds the address to our array of addresses on the request.
//End Origin Address
 

  // Add Populate the Destination address.
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
	$addresses[] = ${'destination' . $j};
	//print_r($addr['services']);
	foreach ($addr['services'] as $items) {
		//print_r($items);
    ${'line' . $j} = new Line();
    ${'line' . $j}->setLineNo($j);                             // R: string - line Number of invoice - must be unique.
    ${'line' . $j}->setItemCode(substr($items['s_name'],0,50));                   	// R: string - SKU or short name of Item
    ${'line' . $j}->setDescription($items['s_name']);              // O: string - Longer description of Item - R: for SST
    ${'line' . $j}->setTaxCode(strtoupper(trim($items['tax_code'])));               		// O: string - Tax Code associated with Item
    ${'line' . $j}->setQty($items['qa']);                          		// R: decimal - The number of items 
    ${'line' . $j}->setAmount(trim($items['price']));                   			// R: decimal - the "NET" amount of items  (extended amount)
    ${'line' . $j}->setDiscounted(false);                		// O: boolean - Set to true if line item is to discounted - see Discount
    ${'line' . $j}->setOriginCode("01");						// R: AddressCode set on the desired origin address above
    ${'line' . $j}->setDestinationCode($i);					// R: AddressCode set on the desired destination address above
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
	 // End Populate the Destination address.
	//print_r($lines);exit;
	$request->setAddresses($addresses);
  
  $request->setLines($lines);
//print_r($request);exit;
  // Try AvaTax.
  try {
        $getTaxResult = $this->client->getTax($request);
		//print_r($getTaxResult);
       // echo 'GetTax is: ' . $getTaxResult->getResultCode() . "\n";

// Error Trapping
//echo "<pre>";
//print_r($getTaxResult);exit;
        if ($getTaxResult->getResultCode() == SeverityLevel::$Success) {

// Success - Display GetTaxResults to console
            
            //Document Level Results

           // echo "DocCode: " . $request->getDocCode() . "\n";
           /* echo "TotalAmount: " . $getTaxResult->getTotalAmount() . "<br />";
            echo "TotalTax: " . $getTaxResult->getTotalTax() . "<br />";
			if($getTaxResult->getTotalTax()!=0 && $getTaxResult->getTotalAmount()!=0)
			  echo "Tax Percent: " . ($getTaxResult->getTotalTax()*100)/$getTaxResult->getTotalAmount() . "%<br />";
			  else
			  echo "Tax Percent: 0%<br />";
            
          exit;*/
            
            foreach ($getTaxResult->getTaxLines() as $ctl) {
				$getLineNo = $ctl->getLineNo();
				$tot_line[$getLineNo]['a_tax']=$ctl->getTax();
				$tot_line[$getLineNo]['a_tax_code']=$ctl->getTaxCode();
                                $tot_line[$getLineNo]['line_no']=$getLineNo;
                                $tot_line[$getLineNo]['DocCode']=$getTaxResult->getDocCode();
              /* echo "     Line: " . $ctl->getLineNo() . " Tax: " . $ctl->getTax() . " TaxCode: " . $ctl->getTaxCode() . "<br />";
			    echo " Tax: " . $ctl->getTax() . " TaxCode: " . $ctl->getTaxCode() . "<br />";
 */
            //Line Level Results (from a TaxDetails array class)
            //Displayed in a readable format
               foreach ($ctl->getTaxDetails() as $ctd) {
                   // echo "          Juris Type: " . $ctd->getJurisType() . "; Juris Name: " . $ctd->getJurisName() . "; Rate: " . $ctd->getRate() . "; Amt: " . $ctd->getTax() . "<br />";
                
                   $tot_line[$getLineNo]['state']=trim($ctd->getRegion() );
               }
                
            }
          /* echo "<pre>"; 
		   print_r($tot_line);exit;*/
		    $data['Success']=1;
			$data['msg']=$tot_line;
			return $data;
		  //return $tot_line;
// If NOT success - display error messages to console
// it is important to itterate through the entire message class        
                      
            } else {
				$msg_mess="";
				
            foreach ($getTaxResult->getMessages() as $msg) {
                $msg_mess.= $msg->getSummary() . "\n";
            }
			$data['Success']=0;
			$data['msg']=$msg_mess;
			return $data;
        }
    } 
	catch(Exception $exception)
	{
		$data['msg']="Exception: " . $exception->getMessage()."\n";
		//echo $msg = "Exception: " . $exception->getMessage()."\n";
		$data['Success']=0;
		return $data;
	}
	
	}
	
	
	function validateAddress($data,$job_id){
		$this->client = new TaxServiceRest(
	"https://avatax.avalara.net", // TODO: Enter service URL
	"AvataxDev@ivieinc.com", //TODO: Enter Username or Account Number AvataxDev@ivienc.com
	"haps25_fAwns"); //TODO: Enter Password or License Key
		try
			{
				$address = new Address();
				$address->setLine1($data['addr1']);		//R: An address line is required for validation.
				$address->setCity($data['city']);		//R: Two of the three: city, region, postal code are required.
				$address->setRegion($data['state']);
				$address->setPostalCode($data['zip']);
			
			/*echo "<pre>";
								print_r($address);exit;*/
			// Build Address object into an array
					
				$request = new ValidateRequest($address);
				/*echo "<pre>";
								print_r($request);exit;*/
				$result = $client->Validate($request);
				//echo "\n".'Validate ResultCode is: '. $result->getResultCode()."\n";exit;
			/*echo "<pre>";
								print_r($result);exit;*/
			// Output to console the result (Success or Not Success)
			// If not Success return Error Message results
			// If Success - retune Normalized address
			// If corrdinates = 1 return latitude and longitude
				//echo "\n".'Validate ResultCode is: '. $result->getResultCode()."\n";
				if($result->getResultCode() != SeverityLevel::$Success)
				{
					$mess='';
					foreach($result->getMessages() as $msg)
					{
						$mess.=$msg->getSeverity().": ".$msg->getSummary()."<br>";
					}
					$data['error']=1;
					$data['id']=$data['id'];
					$data['message']=$mess;
					$data['job_id']=$job_id;
					$data['from']='error';
					return $data;
				}
				else
				{
					$data['error']=0;
					$data['message']='Success';
					$data['id']=$data['id'];
					$data['job_id']=$job_id;
					$data['from']='success';
					/*echo "Normalized Address:\n";
					$valid = $result->getvalidAddress();
			
					echo "Line 1: ".$valid->getline1()."\n";
					echo "Line 2: ".$valid->getline2()."\n";
					echo "Line 3: ".$valid->getline3()."\n";
					echo "City: ".$valid->getcity()."\n";
					echo "Region: ".$valid->getregion()."\n";
					echo "Postal Code: ".$valid->getpostalCode()."\n";
					echo "Country: ".$valid->getcountry()."\n";
					echo "County: ".$valid->getcounty()."\n";
					echo "FIPS Code: ".$valid->getfipsCode()."\n";
					echo "PostNet: ".$valid->getpostNet()."\n";
					echo "Carrier Route: ".$valid->getcarrierRoute()."\n";
					echo "Address Type: ".$valid->getaddressType()."\n";*/
					return $data;
			
				}
			   
			}


		catch(Exception $exception)
		{
			//echo
					$msg = "Exception: " . $exception->getMessage()."\n";
					$data['error']=1;
					$data['message']=$msg;
					$data['id']=$data['id'];
					$data['job_id']=$job_id;
					$data['from']='exception';
					return $data;
		}
		
	}
	function validateDistributionAddress2($data,$job_id){
		
		/*$client = new TaxServiceRest(
	"https://avatax.avalara.net", // TODO: Enter service URL
	"AvataxDev@ivieinc.com", //TODO: Enter Username or Account Number AvataxDev@ivienc.com
	"haps25_fAwns"); //TODO: Enter Password or License Key
                 
                 */
            $client = new TaxServiceRest(
	"https://development.avalara.net", // TODO: Enter service URL
	"AvataxDev@ivienc.com", //TODO: Enter Username or Account Number AvataxDev@ivienc.com
	"Ivie_avalara001"); //TODO: Enter Password or License Key
    
	
$request = new GetTaxRequest();

//Document Level Setup  
//     R: indicates Required Element
//     O: Indicates Optional Element
//
    $dateTime = new DateTime();                                  // R: Sets dateTime format 
   $request->setCompanyCode("IVIEINC");                    // R: Company Code from the accounts Admin Console
    $request->setDocType('SalesOrder');        // $request->setDocType(DocumentType::$SalesOrder);                      // R: Typically SalesOrder,SalesInvoice, ReturnInvoice
    $request->setDocCode("IVIEINC");                          // R: Invoice or document tracking number - Must be unique
    $request->setDocDate(date_format($dateTime, "Y-m-d"));  // R: Date the document is processed and Taxed - See TaxDate
    $request->setCustomerCode("ALBC");             // R: String - Customer Tracking number or Exemption Customer Code
    $request->setDiscount(0);                   // O: Decimal - amount of total document discount
    $request->setExemptionNo("");           // O: String   if not using ECMS which keys on customer code
    $request->setDetailLevel(DetailLevel::$Tax);     // R: Chose $Summary, $Document, $Line or $Tax - varying levels of results detail 
    $request->setCommit(FALSE);                    // O: Default is FALSE - Set to TRUE to commit the Document

	$addresses = array();
//Add Origin Address
    $origin = new Address();                    // R: New instance of an address, we will use this for the origin
    $origin->setLine1("601 Silveron Blvd, Suite 200");            // O: It is not required to pass a valid street address, but it will provide for a better tax calculation.
    $origin->setCity("Flower Mound");                // R: City
    $origin->setRegion("Texas");              		// R: State or Province
    $origin->setPostalCode("75028");      		// R: String (Expects to be NNNNN or NNNNN-NNNN or LLN-LLN)
    $origin->setAddressCode("01");            	// R: Allows us to use the address on our Lines
	$addresses[] = $origin;						// 		Adds the address to our array of addresses on the request.

// Add Destination Address

	  $destination = new Address();                 // R: New instance of an address, we will use this as the destination
    $destination->setLine1($data['addr1']);  
	$destination->setCity($data['city']);                // R: City
    $destination->setRegion($data['state']);
	$destination->setCountry($data['country']); 
    $destination->setPostalCode($data['zip']);
    $destination->setAddressCode("02");       
	$addresses[] = $destination;
	$request->setAddresses($addresses);
//

    $line1 = new Line();                                // New instance of a line  
    $line1->setLineNo("01");                            // R: string - line Number of invoice - must be unique.
    $line1->setItemCode("SKU123");                   	// R: string - SKU or short name of Item
    $line1->setDescription("Blue widget");              // O: string - Longer description of Item - R: for SST
    $line1->setTaxCode('');               		// O: string - Tax Code associated with Item
    $line1->setQty(1);                          		// R: decimal - The number of items 
    $line1->setAmount(1);                   			// R: decimal - the "NET" amount of items  (extended amount)
    $line1->setDiscounted(false);                		// O: boolean - Set to true if line item is to discounted - see Discount
	$line1->setOriginCode("01");						// R: AddressCode set on the desired origin address above
	$line1->setDestinationCode("02");					// R: AddressCode set on the desired destination address above

    $request->setLines(array($line1));             // sets line items to $lineX array    
	
try {
        $getTaxResult = $client->getTax($request);
     
// Error Trapping

        if ($getTaxResult->getResultCode() == SeverityLevel::$Success) {
					$return['error']=0;
					$return['id']=$data['id'];
					$return['message']=$mess;
					$return['job_id']=$job_id;
					$return['from']='error';
					return $return;
          
                      
            } else {
           			$return['error']=1;
					$return['id']=$data['id'];
					$return['addr1']=$data['addr1'];
					$return['city']=$data['city'];
					$return['state']=$data['state'];
					$return['zip']=$data['zip'];
					$return['addr_id']=$data['id'];
					$return['job_id']=$job_id;
					$return['from']='error';
					return $return;
        }
    } 
	catch(Exception $exception)
	{
					$return['error']=1;
					$return['id']=$data['id'];
					$return['addr1']=$data['addr1'];
					$return['city']=$data['city'];
					$return['state']=$data['state'];
					$return['zip']=$data['zip'];
					$return['addr_id']=$data['id'];
					$return['job_id']=$job_id;
					$return['from']='exptionm';
					return $return;
	}
		
	}
        
        
   function validateDistributionAddress($data,$job_id){
	$serviceURL="https://development.avalara.net";
        $accountNumber="AvataxDev@ivienc.com";
        $licenseKey="Ivie_avalara001";
        $addressSvc = new AddressServiceRest($serviceURL, $accountNumber, $licenseKey);
 

	$address = new Address();                 // R: New instance of an address, we will use this as the destination
        $address->setLine1($data['addr1']);  
	$address->setCity($data['city']);                // R: City
        $address->setRegion($data['state']);
	$address->setCountry($data['country']); 
        $address->setPostalCode($data['zip']);
        
        $validateRequest = new ValidateRequest();
	$validateRequest->setAddress($address);
        $validateResult = $addressSvc->Validate($validateRequest);
        return $validateResult->getResultCode();
		
	}
}


