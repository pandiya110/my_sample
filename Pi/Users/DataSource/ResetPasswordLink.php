<?php

namespace CodePi\Users\DataSource;

use CodePi\Users\DataTranslators\UserTranslators;
use CodePi\Base\DataSource\DataSource;
use Crypt;
use Hash;

class ResetPasswordLink extends DataSource {
	function model() {
		// $return = 'CodePi\Users\Eloquant\Users';
		return 'CodePi\Users\Eloquant\Users';
	}
	/**
	 * Use input params data is saving into database table users:
	 *
	 * @params array $params
	 * $return array $data
	 */
	function saveUserPassword($params) {
		$encid = $params ['id'];
		if (! empty ( $encid )) {
			$id = Crypt::decrypt ( $encid );
			$password = Hash::make ( $params ['password'] );
			$userPasswordSave = $this->model->where ( 'id', $id )->update ( [ 
					'password' => $password,
					'password_set' => 1 
			          ] );
			$data = array (
					'status' => true,
					'msg' => 'Your Password has been changed Successfully' 
			);
		} else {
			$data = array (
					'status' => false,
					'msg' => 'In Access Code..Already Changed Some one..' 
			);
			//return;
		}
		return $data;
	}
}
