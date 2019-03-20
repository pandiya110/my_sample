<?php

namespace CodePi\Base\Libraries;

/**
 * PasswordValidationRules class validates the given password
 * Returns the encrypted password
 * author: Naresh kumar
 */
class PasswordValidationRules {

    //constant variables
    const EMPTY_PASSWORD = "Password field should not be empty";
    const PASSWORD_LENGTH_CASE = "Password length value doesn't match";
    const CAPITAL_CASE = "Password should contains atleast one capital case letter";
    const LOWER_CASE = "Password should contains atleast one lower case letter";
    const NUMBER_CASE = "Password should contains atleast one Number";
    const SPECIAL_CASE = "Password should contains atleast one special character";

    //variable declation
    public $password;
    public $setPasswordExpiryDate;
    public $setPasswordRemainderDate;
    public $setCompareDate;

    //list of all possible special characters
    public $arrSpecialChar = array("'",' ','!','"','#','$','%','&','`','(',')','*','+',',','-','.','/',':',';','<','>','?','@','[',']','^','_','{','}','|','~','=');

    //default variables which can be override
    private $minPasswordLength = 8;
    private $maxPasswordLength = 20;
    private $passwordExpiryDays = 90;
    private $passwordRemainderDays = 5;       
    private $arrSpecialCharNotAllowed = array();
    private $arrSpecialCharAllowed = array();
    private $compareExpiryRemindDate;
    
    //flag variables for skipping any password validations
    // false = OFF , true = ON
    private $isSetPasswordLength = false;
    Private $isSetCapitalCase = false;
    Private $isSetLowerCase = false;
    Private $isSetNumberCase = false;
    Private $isSetSpecialCase = false;

    //Error displaying variable
    private $arrError = [
        101 => 'Password minimum length should be greater than or equal to 8',
        102 => 'Password maximum length should be greater than 7',
        103 => 'spaces are not allowed'
    ];

    /**
     * Setting the password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Getting the password
     * @return password
     */
    public function validatePassword()
    {
        if(!empty($this->password)) //checks password is empty or not
        {
            if($this->checkPasswordLength()) //checks length of the password
            {
                if($this->checkCapitalCase()) //checks Upper case letter
                {
                    if($this->checkLowerCase()) //checks Lower case letter
                    {
                        if($this->checkNumberCase()) //checks Number
                        {
                            if($this->checkSpecialCharacter()) //checks special character
                            {
                                return true;
                            }
                            else
                            {
                                //displaying error for special case validation
                                return PasswordValidationRules::SPECIAL_CASE;
                            }
                        }
                        else
                        {
                            //displaying error for number case validation
                            return PasswordValidationRules::NUMBER_CASE;
                        }
                    }
                    else
                    {
                        //displaying error for lower case validation
                        return PasswordValidationRules::LOWER_CASE;
                    }
                }
                else
                {
                    //displaying error for capital case validation
                    return PasswordValidationRules::CAPITAL_CASE;
                }
            }
            else
            {
                //displaying error for password length validation
                return PasswordValidationRules::PASSWORD_LENGTH_CASE;
            }
        }
        else
        {
            //displaying error for empty password passing
            return PasswordValidationRules::EMPTY_PASSWORD;
        }
    }

    ///////////////////// Starts - Assigning flag values for validation ////////////////////////

    /**
     * assigning flag values for validations
     * validating the Password Length
     * @return boolean
     */
    public function setisPasswordLength($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to password length boolean variable
            $this->isSetPasswordLength = true;
        }
    }

    /**
     * assigning flag values for validations
     * validating the Capital Case
     * @return boolean
     */
    public function setisCapitalCase($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to capital case boolean variable
            $this->isSetCapitalCase = true;
        }
    }

    /**
     * assigning flag values for validations
     * validating the Lower Case
     * @return boolean
     */
    public function setisLowerCase($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to lower case boolean variable
            $this->isSetLowerCase = true;
        }
    }

    /**
     * assigning flag values for validations
     * validating the Number Case
     * @return boolean
     */
    public function setisNumberCase($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to number case boolean variable
            $this->isSetNumberCase = true;
        }
    }

    /**
     * assigning flag values for validations
     * Validating the Special Case
     * @return boolean
     */
    public function setisSpecialCase($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to special case boolean variable
            $this->isSetSpecialCase = true;
        }
    }

    /**
     * assigning flag values for all validation validations
     * @return boolean
     */
    public function setAllValidationToTrue($flag = null)
    {
        if($flag == true)
        {
            //assigning true value to all validation variables
            $this->isSetPasswordLength = true;
            $this->isSetCapitalCase = true;
            $this->isSetLowerCase = true;
            $this->isSetNumberCase = true;
            $this->isSetSpecialCase = true;
        }
    }

    ///////////////////// Ends - Assigning flag values for validation ////////////////////////


    /**
     * sets the minimum length of the password
     * min:4 characters assigns if not set this variable
     * @return void
     */
    public function setMinPasswordLength($value)
    {
        if($value >= 8)
        {
            $this->minPasswordLength = $value;
        }
        else
        {
            return $this->arrError[101];
        }
    }
    
    /***
     * Sets the date with which expiry date and reminder date are compared
     * Default is Current date if not set this variable
     * @return void
     */
    
    public function setCompareExpiryRemindDate ($value) {
        if(!empty($value)) {
          $this->compareExpiryRemindDate = date('Y/m/d',strtotime($value));  
        } else{
           $this->compareExpiryRemindDate = date('Y/m/d');
        }
    }


    /**
     * sets the maximum length of the password
     * max:50 characters assigns if not set this variable
     * @return void
     */
    public function setMaxPasswordLength($value)
    {
        if($value > 7)
        {
            $this->maxPasswordLength = $value;
        }
        else
        {
            return $this->arrError[102];
        }
    }

    /**
     * gets the minimum length of the password
     * min:4 characters display if not set this variable
     * @return number
     */
    public function getMinPasswordLength()
    {
        return $this->minPasswordLength;
    }

    /**
     * gets the maximum length of the password
     * max:50 characters display if not set this variable
     * @return number
     */
    public function getMaxPasswordLength()
    {
        return $this->maxPasswordLength;
    }
    /***
     * Gets date with which to compare expiry date,reminder date
     * Default value is current date if not set this variable
     * @return date
     */
    public function getCompareExpiryRemindDate () 
    {      
        if(empty($this->compareExpiryRemindDate)){
            $this->compareExpiryRemindDate = date('Y/m/d');
        }
        return $this->compareExpiryRemindDate;  
    }

    /**
     * checks the length of the password
     * min:4 characters required
     * @return boolean
     */
    public function checkPasswordLength()
    {
        if(strlen($this->password) >= $this->minPasswordLength && strlen($this->password) <= $this->maxPasswordLength && $this->isSetPasswordLength == true)
        {
            return true;
        }
        else if ($this->isSetPasswordLength == false)
        {
            return true;
        }
    }

    /**
     * checks the capital case letter in password
     * @return boolean
     */
    public function checkCapitalCase()
    {
        if(preg_match( '/[A-Z]/', $this->password ) && $this->isSetCapitalCase == true)
        {
            return true;
        }
        else if ($this->isSetCapitalCase == false)
        {
            return true;
        }
    }

    /**
     * checks the lower case letter in password
     * @return boolean
     */
    public function checkLowerCase()
    {
        if(preg_match( '/[a-z]/', $this->password ) && $this->isSetLowerCase == true)
        {
            return true;
        }
        else if ($this->isSetLowerCase == false)
        {
            return true;
        }
    }

    /**
     * checks the number present in password
     * @return boolean
     */
    public function checkNumberCase()
    {
        if(preg_match( '/\d/', $this->password ) && $this->isSetNumberCase == true)
        {
            return true;
        }
        else if ($this->isSetNumberCase == false)
        {
            return true;
        }
    }

    /**
     * sets the special characters which are not allowed
     * @return void
     */
    public function setNotAllowdSpecialCharcters($arrSpecialCharNotAllowed)
    {
        $this->arrSpecialCharNotAllowed = $arrSpecialCharNotAllowed;
    }

    /**
     * sets the special characters which are allowed
     * @return void
     */
    public function setAllowdSpecialCharcters($arrSpecialCharAllowed)
    {
        $this->arrSpecialCharAllowed = $arrSpecialCharAllowed;
    }

    /**
     * checks the special character in password
     * @return boolean
     */
    public function checkSpecialCharacter()
    {
        if ($this->isSetSpecialCase == true && preg_match( '/[^a-zA-Z\d]/', $this->password )) {

            if(!empty($this->arrSpecialCharAllowed) && !empty($this->arrSpecialCharNotAllowed)) {
                return true;
            }

            if(!empty($this->arrSpecialCharNotAllowed) && empty($this->arrSpecialCharAllowed)) {
                for ($i = 0; $i < count($this->arrSpecialCharNotAllowed); $i++) {
                    $char = $this->arrSpecialCharNotAllowed[$i];
                    if(substr_count($this->password,$char) != 0)
                    {
                        if($char == ' ') {
                            return $this->arrError[103];
                            exit;
                        }
                        return $char." not allowed";
                        exit;
                    }
                }
            }

            if(!empty($this->arrSpecialCharAllowed) && empty($this->arrSpecialCharNotAllowed)) {
                $result = array_diff($this->arrSpecialChar, $this->arrSpecialCharAllowed);
                $result = array_values($result);
                for ($i = 0; $i < count($result); $i++) {
                    $char1 = $result[$i];
                    if(substr_count($this->password,$char1) != 0)
                    {
                        if($char1 == ' ') {
                            return $this->arrError[103];
                            exit;
                        }
                        return $char1." not allowed";
                        exit;
                    }
                }
            }

            return true;
        }
        else if ($this->isSetSpecialCase == false)
        {
            return true;
        }
    }

    /**
     * sets the password expiry days
     * min:90 days assigns if not set this variable
     * @return void
     */
    public function setPasswordExpiryDays($value)
    {
        $this->passwordExpiryDays = $value;
    }

    /**
     * gets the password expiry days
     * min:5 days display if not set this variable
     * @return number
     */
    public function getPasswordExpiryDays()
    {
        return $this->passwordExpiryDays;
    }

    /**
     * sets the password remainder days
     * min:5 days assigns if not set this variable
     * @return void
     */
    public function setPasswordRemainderDays($value)
    {
        $this->passwordRemainderDays = $value;
    }

    /**
     * gets the password remiander days
     * min:90 days display if not set this variable
     * @return number
     */
    public function getPasswordRemainderDays()
    {
        return $this->passwordRemainderDays;
    }

    /**
     * sets the password expiry date (+90 days to current date)
     * date format (yyyy/mm/dd)
     * @return date
     */
    public function getPasswordExpiryDate()
    {
        
        $this->setPasswordExpiryDate = date('Y/m/d',strtotime($this->getCompareExpiryRemindDate() . "+".$this->passwordExpiryDays." day"));
        //$this->setPasswordExpiryDate = Date('Y/m/d', strtotime("+".$this->passwordExpiryDays." days"));
        return $this->setPasswordExpiryDate;
    }

    /**
     * sets the password remainder date (-5 days from given date)
     * date format (yyyy/mm/dd)
     * @return date
     */
    public function getPasswordRemainderDate($expiry_date)
    {
        $this->setPasswordRemainderDate = date('Y/m/d',strtotime($this->getCompareExpiryRemindDate() . "+".$this->passwordRemainderDays." day"));
       // $this->setPasswordRemainderDate = date('Y/m/d',(strtotime ( "-".$this->passwordRemainderDays." day" , strtotime ( $expiry_date) ) ));
        return $this->setPasswordRemainderDate;
    }

    /**
     * Alert for password change
     * date format (yyyy/mm/dd)
     * parameters required PASSWORD REMAINDER DATE & EXPIRY DATE
     * @return string
     */
    public function getPasswordRemainder($remainder_date,$expiry_date)
    {
        $compareDate = $this->compareExpiryRemindDate;
        if($compareDate >= $remainder_date && $compareDate <= $expiry_date)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * set all validation parameters
     */
    public function setValidationVariable($arrSet)
    {
        if($arrSet['setMinPasswordLength'] != '')
        {
            if($arrSet['setMinPasswordLength'] >= 8)
            {
                $this->minPasswordLength = $value;
            }
            else
            {
                return $this->arrError[101];
            }
        }

        if($arrSet['setMaxPasswordLength'] != '')
        {
            if($arrSet['setMaxPasswordLength'] > 7)
            {
                $this->maxPasswordLength = $value;
            }
            else
            {
                return $this->arrError[102];
            }
        }

        if($arrSet['passwordExpiryDays'] != '')
        {
            $this->passwordExpiryDays = $arrSet['passwordExpiryDays'];
        }

        if($arrSet['passwordRemainderDays'] != '')
        {
            $this->passwordRemainderDays = $arrSet['passwordRemainderDays'];
        }
    }

} //class PasswordValidationRules ends