<?php
function rotatePassword(string $password) : string {
	$valid = false;
	$new_password = $password;
	
	while(!$valid) {
		// Must not contain these letters. Skip past all invalid combos.
		foreach(['i','o','l'] as $letter) {
			if(($pos = strpos($new_password, $letter)))
				$new_password = str_pad(
					substr($new_password, 0, $pos) . ++$letter,
					strlen($new_password),
					'a'
				);
		}
		
		// Increase our letters by converting to ordinal and carrying left (PHP does this already)
		$new_password++;
		
		// Must contain two different non-overlapping pairs
		if(!preg_match_all('/([a-z])\1/', $new_password, $matches) || count(array_unique($matches[0])) < 2)
			continue;
		
		// Must include a straight of 3 letters
		if(!preg_match('/(?:(?=ab|bc|cd|de|ef|fg|gh|hi|ij|jk|kl|lm|mn|no|op|pq|qr|rs|st|tu|uv|vw|wx|xy|yz).){2,}./', $new_password))
			continue;
		
		$valid = true;
	}
	
	return $new_password;
}

$old_password = 'cqjxjnds';
$new_password = rotatePassword($old_password);

printf("Part 1: %s\n", $new_password); // cqjxxyzz
printf("Part 2: %s\n", rotatePassword($new_password)); // cqkaabcc