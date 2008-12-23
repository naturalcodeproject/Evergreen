<?php

class ValidationHandler {
	
	public function validateCreate(&$model)
	{
		return true;
	}
	
	public function validate_user_name($user_name, &$messages, $check_in_use=true) {
		$flag_valid = true;
		
		//echo "USER: $user_name<BR>";
		
		if (!preg_match("/(.){3}/", $user_name)) {
			$flag_valid = false; 
			$messages[] = "Username: Must Be Longer";
			//echo "LOL AT YOU<BR>";
			return $flag_valid;
		}
		
		if ($flag_valid && $check_in_use) {
			$user = System::load_model('user');
			$user->set_user_name($user_name);
			$num_users = $user->search();
			if ($num_users > 0) {
				$flag_valid = false;
				$messages[] = "Username: In Use";
				return $flag_valid;
			}
		}
			
		return $flag_valid;
	}
	
	public function validate_password($password, &$messages) {
		$flag_valid = true;
		
		if (!preg_match("/(.){3}/", $password)) {
			$flag_valid = false; 
			$messages[] = "Password: Must Be Longer";
			return $flag_valid;
		}
		
		return $flag_valid;
	}
	
	public function validate_phone($phone, &$messages) {
		$flag_valid = true;
		
		$phone = str_replace('-', '', $phone);
		$phone = str_replace('(', '', $phone);
		$phone = str_replace(')', '', $phone);
		
		if (strlen($phone) < 10) {
			$flag_valid = false;
			$messages[] = "Phone: Unknown Number";
			return $flag_valid;
		}
		
		return $flag_valid;
	}
	
	public function validate_email($email, &$messages, $check_in_use=true) {
		$flag_valid = true;
	
		// First, we check that there's one @ symbol, and that the lengths are right
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
 			$flag_valid=false;
			$messages[] = 'Email: Invalid Format';
			return $flag_valid;
  		}

		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				$flag_valid=false;
				$messages[] = 'Email: Invalid Format';
				return $flag_valid;
			}
		}

		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				$flag_valid=false; // Not enough parts to domain
				$messages[] = 'Email: Invalid Format';
				return $flag_valid;
			}

			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					$flag_valid=false;
					$messages[] = 'Email: Invalid Format';
					return $flag_valid;
				}
			}
		}
		
		if ($flag_valid && $check_in_use) {
			$user = System::load_model('user');
			$user->set_email_address($email);
			$num_users = $user->search();
			if ($num_users > 0) {
				$flag_valid=false;
				$messages[] = 'Email: In Use';
				return $flag_valid;
			}
		}
		
		return $flag_valid;
	}
	
	public function validate_expiration_date($expiration_date, $future_date, &$messages) {
		if (strtotime($expiration_date) < strtotime($future_date)) {
			$messages[] = "Card Expired";
			return false;
		}
		
		return true;
	}
	
	public function validate_card_number($card_number, &$messages) {
		if (!is_numeric($card_number)) {
			$messages[] = "Card Number can contain numbers only";
			return false;
		}
		
		$valid = false;
		
		if (strlen($card_numer) == 15 || strlen($card_number) == 16) { 
			$cardnumber = $card_number;
			$cardnumber=preg_replace("/\D|\s/", "", $cardnumber);  # strip any non-digits
    		$cardlength=strlen($cardnumber);
    		$parity=$cardlength % 2;
    		$sum=0;
    		
    		for ($i=0; $i<$cardlength; $i++) {
      			$digit=$cardnumber[$i];
      			if ($i%2==$parity) $digit=$digit*2;
      			if ($digit>9) $digit=$digit-9;
      			$sum=$sum+$digit;
    		}
    		
    		$valid=($sum%10==0);
		}
		
		if (!$valid) {
        	$messages[] = "Card Number Invalid";
		}
		
        return $valid;
	}
}
?>