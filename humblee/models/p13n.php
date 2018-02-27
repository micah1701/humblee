<?php defined('include_only') or die('No direct script access.');

class Core_Model_P13n {

    /**
     * returns an arry of p13n database tabel
     * also includes
     * $reverseOrder (optional) BOOL if set to true, it reverses the order
     *
     */
    public function getAll($reverseOrder=false,$id_only=false){

        $default_row = (object)array("name"=>"Default","description"=>"No personalization","criteria_role"=>false,"criteria"=>false);

		if($reverseOrder)
		{
    		$p13n_versions = ($id_only) ? array(0) : array(0 => $default_row);
    		$p13n = ORM::for_table( _table_content_p13n)->order_by_desc('priority')->find_many();
		}
		else
		{
	        $p13n = ORM::for_table( _table_content_p13n)->order_by_asc('priority')->find_many();
	        $p13n_versions = array();
		}

		//test each on and add to array of usable version
		foreach($p13n as $version)
		{
			switch($version->criteria_type) {
				case 'has_role' :
					if(Core::auth($version->criteria))
					{
						$p13n_versions[] = ($id_only) ? $version->id : $version;
					}
				break;

				case 'i18n' :
					$url_parts = Core::getURIparts();
					if($url_parts[0] == $version->criteria)
					{
						$p13n_versions[] = ($id_only) ? $version->id : $version;
					}
				break;

			}

		}

		if(!$reverseOrder)
		{
    		$p13n_versions[] = ($id_only) ? 0 : $default_row;
		}

		return $p13n_versions;
    }


}