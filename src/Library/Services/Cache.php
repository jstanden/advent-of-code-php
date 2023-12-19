<?php // Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace jstanden\AoC\Library\Services;

class Cache {
	static ?Cache $_instance = null;
	public array $_cache = [];
	
	static function getInstance() : Cache {
		if(is_null(self::$_instance)) {
			self::$_instance = new Cache();
		}
		
		return self::$_instance;
	}
	
	function get($key) : mixed {
		return $this->_cache[$key] ?? null;
	}
	
	function set($key, $value) : void {
		$this->_cache[$key] = $value;
	}
	
	function clear() : void {
		$this->_cache = [];
	}
}