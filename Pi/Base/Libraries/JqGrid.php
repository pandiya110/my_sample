<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author enterpi
 */
class JqGrid {
	function gridJson($postvals, $sql, $array_fields, $msg = '', $array_fields_functions = array()) {
		$sql = trim ( $sql );
		$sql = substr_replace ( $sql, 'select SQL_CALC_FOUND_ROWS ', 0, 6 );
		// $sql= str_replace($sql,array('Select','select'))
		$session_name = str_replace ( '/', '_', $_SERVER ['PATH_INFO'] );
		// echo $session_name;
		// if ($this->db_session) {
		// / $this->db_session->set_userdata($session_name, $postvals);
		// }
		$page = isset ( $postvals ['page'] ) ? $postvals ['page'] : 1; // get the requested page
		$limit = isset ( $postvals ['rows'] ) ? $postvals ['rows'] : 10; // get how many rows we want to have into the grid
		$sidx = isset ( $postvals ['sidx'] ) ? $postvals ['sidx'] : 'id'; // get index row - i.e. user click to sort
		$sord = isset ( $postvals ['sord'] ) ? $postvals ['sord'] : 'desc'; // get the direction
		$query = $sql;
		
		$start = $limit * $page - $limit; // do not put $limit*($page - 1)
		if ($start < 0)
			$start = 0;
		$query .= " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		// echo $query;
		$result3 = DB::Select ( $query );
		$result = DB::Select ( "select FOUND_ROWS() as cnt" );
		
		if ($result)
			$count = $result [0]->cnt;
		else
			$count = 0;
		
		if ($count > 0) {
			$total_pages = ceil ( $count / $limit );
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) {
			$page = $total_pages;
			$start = $limit * $page - $limit;
			if ($start < 0)
				$start = 0;
			$query2 = $sql;
			$query2 .= " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			// echo $query;
			$result3 = DB::Select ( $query2 );
			// $result = $this->getDBResult("select FOUND_ROWS() as cnt");
		}
		// $start = $limit * $page - $limit; // do not put $limit*($page - 1)
		// if ($start < 0)
		// $start = 0;
		
		// echo $query;
		$responce = new stdClass ();
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		// $responce->total_records = $count;
		$i = 0;
		// $result3 = $result2->result();
		
		if ($result3) {
			foreach ( $result3 as $row ) {
				$row_info = array ();
				foreach ( $array_fields as $k => $v ) {
					
					if (array_key_exists ( $v, $row )) {
						if (! empty ( $array_fields_functions ) && array_key_exists ( $v, $array_fields_functions )) {
							$param = array ();
							foreach ( $array_fields_functions [$v] ['arg'] as $p => $q ) {
								if (array_key_exists ( $q, $row )) {
									$param [] = $row->{$q};
								} else {
									$param [] = $q;
								}
							}
							
							if (isset ( $array_fields_functions [$v] ['function'] ))
								$new = call_user_func_array ( $array_fields_functions [$v] ['function'], $param );
							if (isset ( $array_fields_functions [$v] ['class'] )) {
								$new = call_user_func_array ( $array_fields_functions [$v] ['class'], $param );
							}
							if (isset ( $array_fields_functions [$v] ['condition'] )) {
								$condition_to_be_changed = $array_fields_functions [$v] ['condition'];
								
								$abc = ( array ) $row;
								
								$new_param = implode ( $param );
								// echo $array_fields_functions[$v]["condition"];
								$system = create_function ( $new_param, $array_fields_functions [$v] ["condition"] );
								extract ( $abc );
								$aa = array ();
								foreach ( $param as $l => $m ) {
									$x = trim ( $m, '$' );
									if (array_key_exists ( $x, $row )) {
										$aa [] = $row->{$x};
									} else {
										$aa [] = $x;
									}
								}
								$new = call_user_func_array ( $system, $aa );
								
								// $new=$system($col3);
							}
						} else {
							$new = $row->{$v};
						}
						$row_info [] = $new;
					} else {
						$matches = array ();
						$arr = preg_match_all ( '/(?<={%)[^%}]+(?=%})/', $v, $matches );
						if (! empty ( $arr )) {
							foreach ( $matches [0] as $l => $m ) {
								if (array_key_exists ( $m, $row )) {
									
									if (! empty ( $array_fields_functions ) && array_key_exists ( $m, $array_fields_functions )) {
										$param = array ();
										foreach ( $array_fields_functions [$m] ['arg'] as $p => $q ) {
											if (array_key_exists ( $q, $row )) {
												$param [] = $row->{$q};
											} else {
												$param [] = $q;
											}
										}
										if (isset ( $array_fields_functions [$m] ['function'] ))
											$new = call_user_func_array ( $array_fields_functions [$m] ['function'], $param );
										if (isset ( $array_fields_functions [$m] ['class'] )) {
											$new = call_user_func_array ( $array_fields_functions [$m] ['class'], $param );
										}
										if (isset ( $array_fields_functions [$m] ['condition'] )) {
											$condition_to_be_changed = $array_fields_functions [$m] ['condition'];
											
											$abc = ( array ) $row;
											
											$new_param = implode ( $param );
											// echo $array_fields_functions[$v]["condition"];
											$system = create_function ( $new_param, $array_fields_functions [$m] ["condition"] );
											extract ( $abc );
											$aa = array ();
											foreach ( $param as $a => $b ) {
												$x = trim ( $b, '$' );
												if (array_key_exists ( $x, $row )) {
													$aa [] = $row->{$x};
												} else {
													$aa [] = $b;
												}
											}
											$new = call_user_func_array ( $system, $aa );
											
											// $new=$system($col3);
										}
									} else {
										$new = $row->{$m};
									}
									
									$v = str_replace ( '{%' . $m . '%}', $new, $v );
								}
							}
						}
						$row_info [] = $v;
					}
				}
				$responce->rows [$i] ['id'] = $row->id;
				$responce->rows [$i] ['cell'] = $row_info;
				$i ++;
			}
		} else {
			// $msg = 'No Records Found';
			
			if (empty ( $msg )) {
				$msg = "No Records Found";
			}
			$responce->rows [0] ['id'] = 0;
			$responce->rows [0] ['cell'] = array (
					$msg 
			); // array($msg, '', '', '', '');
		}
		return json_encode ( $responce );
	}
}
