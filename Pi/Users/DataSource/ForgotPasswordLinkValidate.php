<?php

namespace CodePi\Users\DataSource;

use CodePi\Users\DataTranslators\UserTranslators;
use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Mailer\UserForgotTokens;
use Crypt;

class ForgotPasswordLinkValidate extends DataSource {
	function model() {
		$new = 'CodePi\Users\Eloquant\Users'; 
		return $new;
	}

	/*
	* check if the forgot password link is validated or not
	*
	* @params array $data
	* @return array
	*/
	function CheckInListData($data) {

		$encid = $data ['id'];
		$token = $data ['token']; 
// 		echo $enc_id; die; 
		$objUserForgotTokens = new UserForgotTokens ();
		$status = false;

		if (! empty ( $encid )) {
           
			$enc_id = Crypt::decrypt ( $encid );
			if (is_numeric ( $enc_id )) {
                            
				 $result = $objUserForgotTokens->checkResetTokenId ( $enc_id, $token );
				 //print_r($result);
				
				if (! empty ( $result )) {
					$current_timestamp = time ();
					if ($result->valid_upto > $current_timestamp) {
						$status = true;
					}
				}
			}
		}
		
		return [ 
				'enc_id' => $enc_id,
				'token' => $token,
				'status' => $status 
		];
	}
}
