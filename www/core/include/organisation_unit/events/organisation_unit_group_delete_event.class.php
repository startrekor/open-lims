<?php
/**
 * @package organisation_unit
 * @version 0.4.0.0
 * @author Roman Konertz <konertz@open-lims.org>
 * @copyright (c) 2008-2014 by Roman Konertz
 * @license GPLv3
 * 
 * This file is part of Open-LIMS
 * Available at http://www.open-lims.org
 * 
 * This program is free software;
 * you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation;
 * version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Organisation Unit Group Delete Event
 * @package organisation_unit
 */
class OrganisationUnitGroupDeleteEvent extends Event
{    
	private $organisation_unit_id;
	private $group_id;
	
	function __construct($organisation_unit_id, $group_id)
    {
    	if (is_numeric($organisation_unit_id) and is_numeric($group_id))
    	{
    		parent::__construct();
    		$this->organisation_unit_id = $organisation_unit_id;
    		$this->group_id = $group_id;
    	}
    	else
    	{
    		$this->organisation_unit_id = null;
    		$this->group_id = null;
    	}
    }
    
    public function get_organisation_unit_id()
    {
    	if ($this->organisation_unit_id)
    	{
    		return $this->organisation_unit_id;
    	}
    	else
    	{
    		return null;
    	}
    }
    
  	public function get_group_id()
    {
    	if ($this->group_id)
    	{
    		return $this->group_id;
    	}
    	else
    	{
    		return null;
    	}
    }
}

?>