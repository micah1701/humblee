<?php defined('include_only') or die('No direct script access.');

class Core_Model_P13n {

    /**
     * returns an array of p13n database table rows
     *
     * $test_results (optional) BOOL if TRUE, test the criteria against the current user and only return matching p13n rows
     * $id_only (optional)      BOOL if TRUE, returned array is just list of p13n row IDs
     *
     */
    public function getAll($test_results=false,$id_only=false){

        $p13n_versions = array();

		if($test_results)
		{
    	    //if testing results, reverse lookup so highest priority (lowest number) is last
    		$p13n = ORM::for_table( _table_content_p13n)->order_by_desc('priority')->find_many();
		}
		else
		{
	        $p13n = ORM::for_table( _table_content_p13n)->order_by_asc('priority')->find_many();
		}

		if(!$p13n)
		{
		    return $p13n_versions;
		}

		foreach($p13n as $criteria)
		{
		    if($test_results)
		    {
		        if($criteria->active == 0)
		        {
		            continue; // if persona is turned off, no need to test
		        }

		        if($this->testCriteria($criteria->criteria))
		        {
    		        $p13n_versions[] = ($id_only) ? $criteria->id : $criteria;
		        }
		    }
		    else
		    {
		        $p13n_versions[] = ($id_only) ? $criteria->id : $criteria;
		    }
		}

		return $p13n_versions;
    }

    /**
     * Test a given criteria against the current user session
     *
     * criteria structure:
     *
     * first level are "OR" operators:  $p13n[0] OR $p13n[1] can be true to pass
     * second level are "AND" operators within the given "OR":  ($p13n[0][0] AND $p13n[0][1]) both have to be true to pass, OR $p13n[1][0]
     *
     * example:
        $p13n = array(
                    0 => array(
                            array('type'=>'required_role', 'operator'=>'==', 'value'=>'login'),
                            array('type'=>'i18n','operator'=>'==','value'=>'es')
                        ),
                    1 => array(
                            array('type'=>'required_role', 'operator'=>'==', 'value'=>'develop')
                        )
                );
     */
    public function testCriteria($criteria)
    {
        $criteria = (is_array($criteria)) ? $criteria : json_decode($criteria);

        if(!is_array($criteria))
        {
            return false;
        }

        foreach($criteria as $criterium_OR => $criterium_AND)
        {
            $pass_AND = 0;
            foreach($criterium_AND as $criterium)
            {
                switch ($criterium->type) {

                    case 'required_role' :
                        if($criterium->operator == "=" && Core::auth($criterium->value))
                        {
                            $pass_AND++;
                        }
                        elseif ($criterium->operator == "!=" && !Core::auth($criterium->value))
                        {
                            $pass_AND++;
                        }
                    break;

                    case 'i18n' :
                        $uri_parts = Core::getURIparts(true);
                        if($criterium->operator == "=" && strtolower($uri_parts[0]) == strtolower($criterium->value) )
                        {
                            $pass_AND++;
                        }
                        elseif ($criterium->operator == "!=" && strtolower($uri_parts[0]) != strtolower($criterium->value))
                        {
                            $pass_AND++;
                        }
                    break;

                    case 'time_of_day' :
                        if($criterium->operator == "<" && strtotime(date("H:i")) < strtotime($criterium->value))
                        {
                            $pass_AND++;
                        }
                        elseif($criterium->operator == ">" && strtotime(date("H:i")) > strtotime($criterium->value))
                        {
                            $pass_AND++;
                        }
                    break;

                }

            }

            if($pass_AND == count($criterium_AND))
            {
                return true;
            }
        }

        return false;

    }

}