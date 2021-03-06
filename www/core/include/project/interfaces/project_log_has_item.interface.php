<?php
/**
 * @package project
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
 * Item Project Log Management Interface
 * @package project
 */ 	 
interface ProjectLogHasItemInterface
{
	/**
	 * @param integer $item_id
	 */
	function __construct($item_id);
	
	function __destruct();
	
	/**
     * @param integer $item_id
     * @return bool
     */
	public function link_item($item_id);
	
	/**
     * @param integer $log_id
     * @return array
     */   
	public static function get_items_by_log_id($log_id);
	
	/**
     * @param integer $log_id
     * @return bool
     */
	public static function delete_by_log_id($log_id);
}

?>
