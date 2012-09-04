<?PHP

  function getYear($s) {
	  return substr($s, 0, 4);
	}
	
	function getMonth($s) {
	  return substr($s, 5, 2);
	}
	
	function getDay($s) {
	  return substr($s, 8, 2);
	}

?>
