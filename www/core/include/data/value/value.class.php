<?php
/**
 * @package data
 * @version 0.4.0.0
 * @author Roman Konertz
 * @copyright (c) 2008-2010 by Roman Konertz
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
 * 
 */
require_once("interfaces/value.interface.php");

if (constant("UNIT_TEST") == false or !defined("UNIT_TEST"))
{
	require_once("exceptions/value_not_found_exception.class.php");
	require_once("exceptions/value_version_not_found_exception.class.php");
	
	require_once("access/value.access.php");
	require_once("access/value_version.access.php");
}

/**
 * Value Management Class
 * @package data
 */
class Value extends DataEntity implements ValueInterface, EventListenerInterface
{
	private $value_id;
	
	private $value;
	private $value_version;
		
	private $content_array;
	
	private $autofield_array_string;

	/**
	 * @param integer $value_id
	 */
    function __construct($value_id)
    {
		if ($value_id == null)
		{
			parent::__construct(null);
			$this->value_id = null;
			$this->value = new Value_Access(null);
			$this->value_version = new ValueVersion_Access(null);
			parent::__construct(null);
		}
		else
		{
			$this->value_id = $value_id;
			$this->value = new Value_Access($value_id);
			
			$value_version_id = ValueVersion_Access::get_current_entry_by_toid($value_id);
			$this->value_version = new ValueVersion_Access($value_version_id);

			parent::__construct($this->value->get_data_entity_id());
		}
    }
    
    function __destruct()
    {
    	unset($this->value_id);
    	unset($this->value);
    	unset($this->value_version);
    	unset($this->content_array);
    }
	
	/**
 	 * @return bool
	 */
	public function can_set_automatic()
	{
		if ($this->value_id)
		{
			$parent_folder = Folder::get_instance($this->get_parent_folder_id());
			return $parent_folder->can_set_automatic();
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @return bool
	 */
	public function can_set_data_entity()
	{
		if ($this->value_id)
		{
			$parent_folder = Folder::get_instance($this->get_parent_folder_id());
			return $parent_folder->can_set_data_entity();
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @return bool
	 */
	public function can_set_control()
	{
		if ($this->value_id)
		{
			$parent_folder = Folder::get_instance($this->get_parent_folder_id());
			return $parent_folder->can_set_control();
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @return bool
	 */
	public function can_set_remain()
	{
		if ($this->value_id)
		{
			$parent_folder = Folder::get_instance($this->get_parent_folder_id());
			return $parent_folder->can_set_remain();
		}
		else
		{
			return false;
		}
	}
    
	/**
	 * Opens another version of the value with internal revision id
	 * @param integer
	 * @return bool
	 */
	public function open_internal_revision($internal_revision)
	{
		if (is_numeric($internal_revision) and $this->value_id)
		{
			$value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $internal_revision);
			$this->value_version = new ValueVersion_Access($value_version_id);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Opens another version of the value with value version id (primary key)
	 * @param integer $value_version_id
	 * @return bool
	 */
	private function open_value_version_id($value_version_id)
	{
		if (is_numeric($value_version_id) and $this->value_id)
		{
			$this->value_version = new ValueVersion_Access($value_version_id);
			return true;
		}
		else
		{
			return false;
		}
	}
    	
	/**
	 * Creates a new value
	 * @param integer $folder_id
	 * @param integer $owner_id
	 * @param integer $type_id
	 * @param array $value
	 * @param bool $premature
	 * @return integer
	 */
	public function create($folder_id, $owner_id, $type_id, $value)
	{
		global $user, $transaction;
		
		if ($folder_id and $type_id)
		{
			$transaction_id = $transaction->begin();
			
			if ($owner_id == null)
			{
				$owner_id = $user->get_user_id();
			}
			
			$checksum = md5(serialize($value));
			
			$folder = Folder::get_instance($folder_id);
					
			if (($data_entity_id = parent::create($owner_id, null)) != null)
			{
				if (parent::set_as_child_of($folder->get_data_entity_id()) == false)
				{
					if ($transaction_id != null)
					{
						$transaction->rollback($transaction_id);
					}
					return null;
				}
				
				$value_access = new Value_Access(null);
				
				if (($value_id = $value_access->create($data_entity_id, $type_id)) != null)
				{
					if ($type_id != 2 and is_array($value))
					{
						$full_text_index = false;
						$full_text_key_array = array();
						$full_text_content_string = "";
					
						foreach ($value as $fe_key => $fe_value)
						{
							if (strpos($fe_key, "-vartype") !== false)
							{
								if ($fe_value == "string")
								{
									$full_text_index = true;
									$tmp_key = str_replace("-vartype","",$fe_key);
									array_push($full_text_key_array, $tmp_key);
								}
							}
						}
						
						if (is_array($full_text_key_array) and count($full_text_key_array) >= 1)
						{
							foreach($full_text_key_array as $fe_key => $fe_value)
							{
								if ($full_text_content_string)
								{
									$full_text_content_string = $full_text_content_string." ".$value[$fe_value];
								}
								else
								{
									$full_text_content_string = $value[$fe_value];
								}
							}
						}
					}
					else
					{
						$full_text_index = true;
						$full_text_content_string = $value;
					}
					
					$value_version_access = new ValueVersion_access(null);
					$value_version_id = $value_version_access->create($value_id, 1, serialize($value), $checksum, null, 1, true, $owner_id);
					
					if ($full_text_index == true and $full_text_content_string)
					{
						$value_version_access->set_text_search_vector($full_text_content_string, "english");
					}
					
					if ($value_version_id != null)
					{
						if ($transaction_id != null)
						{
							$transaction->commit($transaction_id);
						}
						return $value_id;
					}
					else
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return null;
					}
				}
				else
				{
					if ($transaction_id != null)
					{
						$transaction->rollback($transaction_id);
					}
					return null;
				}
			}
			else
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return null;
			}
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Deletes a value
	 * @return bool
	 */
	public function delete()
	{
		global $transaction;
		
		if (($this->value_id != null) and $this->value and $this->value_version)
		{
			$transaction_id = $transaction->begin();

			$value_version_array = ValueVersion_Access::list_entries_by_toid($this->value_id);

			unset($this->value_version);
			
			if (is_array($value_version_array) and count($value_version_array) >= 1)
			{
				foreach($value_version_array as $key => $fe_value)
				{
					$value_version = new ValueVersion_Access($fe_value);
					if ($value_version->delete() == false)
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return false;
					}
				}
				
				if ($this->value->delete() == true)
				{
					if (parent::delete() == true)
					{
						if ($transaction_id != null)
						{
							$transaction->commit($transaction_id);
						}
						return true;
					}
					else
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return false;
					}
				}
				else
				{
					if ($transaction_id != null)
					{
						$transaction->rollback($transaction_id);
					}
					return false;
				}
			}
			else
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			}
		}
		else
		{
			return false;	
		}
	}

	/**
	 * Deletes a specific value version
	 * @param integer $internal_revision
	 * @return bool
	 */
	public function delete_version($internal_revision)
	{
		global $transaction;
		
		if ($this->value_id and $this->value and $this->value_version)
		{
			$transaction_id = $transaction->begin();
			
			if (is_numeric($internal_revision))
			{
				$this->open_internal_revision($internal_revision);
				
				$number_of_root_major_versions = ValueVersion_Access::get_number_of_root_major_versions_by_toid($this->value_id);
				
				if ($number_of_root_major_versions > 1)
				{
					$value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $internal_revision);			
					
					$minor_value_array = ValueVersion_Access::list_entries_by_previous_version_id($value_version_id);
					
					if (is_array($minor_value_array) and count($minor_value_array) >= 1)
					{
						foreach($minor_value_array as $fe_key => $fe_value)
						{
							$value = new Value($this->value_id);
							$value->open_value_version_id($fe_value);	
							if ($value->delete_version($value->get_internal_revision()) == false)
							{
								if ($transaction_id != null)
								{
									$transaction->rollback($transaction_id);
								}
								return 0;
							}								
						}	
					}
	
					if ($this->value_version->get_current() == true)
					{
						$next_current_value_version_id = ValueVersion_Access::get_last_uploaded_version_entry_by_toid($this->value_id, $internal_revision);
						$value_version_access = new ValueVersion_Access($next_current_value_version_id);
						$value_version_access->set_current(true);
					}
	
					if ($this->value_version->delete() == false)
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return 0;
					}
					else
					{
						if ($transaction_id != null)
						{
							$transaction->commit($transaction_id);
						}
						return 1;
					}
				}
				else
				{
					if ($this->delete() == true)
					{
						if ($transaction_id != null)
						{
							$transaction->commit($transaction_id);
						}
						return 2;
					}
					else
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return 0;
					}
				}	
			}
			else
			{
				return 0;
			}		
		}
		else
		{
			return 0;
		}
	}	
	
	/**
	 * Returns true, if a value version exists
	 * @param integer $internal_revision
	 * @return bool
	 */
	public function exist_value_version($internal_revision)
	{
		if ($this->value_id and $this->value_version)
		{
   			return $this->value_version->exist_value_version_by_internal_revision($internal_revision);
		}
		else
		{
   			return false;
   		}
	}	

	/**
	 * Updates the current value
	 * @param array $value_array
	 * @param integer $previous_version_id
	 * @param bool $major
	 * @param bool $current
	 * @param string $full_text_index
	 * @return bool
	 */
	public function update($value_array, $previous_version_id, $major, $current, $full_text_index)
	{
		global $transaction, $user;
		
		$transaction_id = $transaction->begin();
		
		if ($this->get_type_id() != 2)
		{
			$current_value = $this->value_version->get_value();
			
			if (strlen($current_value) > 0)
			{		
				$current_value_array = unserialize($current_value);
			
				if (is_array($current_value_array) and count($current_value_array) >= 1)
				{
					$new_current_value_array = array();
					
					foreach ($current_value_array as $fe_key => $fe_value)
					{
						if (strpos($fe_key, "af-") === false)
						{
							$new_current_value_array[$fe_key] = $fe_value;
						}
					}
				
					$current_value_array = $new_current_value_array;			
				}
		
				$intersection_array = array_intersect_key($value_array,$current_value_array);
				$new_diff_array = array_diff_key($value_array,$current_value_array);
				$old_diff_array = array_diff_key($current_value_array,$value_array);
				
				$value_array = $intersection_array + $new_diff_array + $old_diff_array;
			
				unset($intersection_array);
				unset($new_diff_array);
				unset($old_diff_array);
			}
			else
			{
				$current_value_array = array();
			}
		
			$full_text_index = false;
			$full_text_key_array = array();
			$full_text_content_string = "";
		
			foreach ($value_array as $fe_key => $fe_value)
			{
				if (strpos($fe_key, "-vartype") !== false)
				{
					if ($fe_value == "string")
					{
						$full_text_index = true;
						$tmp_key = str_replace("-vartype","",$fe_key);
						array_push($full_text_key_array, $tmp_key);
					}
				}
			}
			
			if (is_array($full_text_key_array) and count($full_text_key_array) >= 1)
			{
				foreach($full_text_key_array as $fe_key => $fe_value)
				{
					if ($full_text_content_string)
					{
						$full_text_content_string = $full_text_content_string." ".$value_array[$fe_value];
					}
					else
					{
						$full_text_content_string = $value_array[$fe_value];
					}
				}
			}
		
			$value_string = serialize($value_array);
			$value_checksum = md5($value_string);
			
			$current_value_version_id = ValueVersion_Access::get_current_entry_by_toid($this->value_id);
			$current_value_version = new ValueVersion_Access($current_value_version_id);
			
			$highest_revision_value_version_id = ValueVersion_Access::get_highest_internal_revision_entry_by_toid($this->value_id);
			$highest_revision_value_version = new ValueVersion_Access($highest_revision_value_version_id);
			
			$new_internal_revision = $current_value_version->get_internal_revision()+1;
											
			if ((array_diff_assoc($value_array, $current_value_array) != null) or 
				(array_diff_assoc($current_value_array, $value_array) != null))
			{	
				if ($major == true)
				{										
					if ($previous_version_id == null)
					{
						$new_version = $current_value_version->get_version()+1;
						$previous_version_pk_id = null;
					}
					else
					{
						$major_value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $previous_version_id);
						$major_value_version = new ValueVersion_Access($major_value_version_id);
						
						if ($major_value_version->get_previous_version_id() == $major_value_version->get_id())
						{
							$previous_version_pk_id = null;
						}
						else
						{
							$previous_version_pk_id = $major_value_version->get_previous_version_id();
						}
						
						$major_value_version_id = ValueVersion_Access::get_highest_major_version_entry_by_toid_and_previous_version_id($major_value_version->get_toid(), $previous_version_pk_id);
						$major_value_version = new ValueVersion_Access($major_value_version_id);
						
						$new_version = $major_value_version->get_version()+1;
					}
				}
				else
				{				
					$major_value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $previous_version_id);
					
					$current_minor_version_id = ValueVersion_Access::get_highest_minor_version_entry_by_id($major_value_version_id);
					
					if ($current_minor_version_id)
					{
						$current_minor_version = new ValueVersion_Access($current_minor_version_id);
						$new_version = $current_minor_version->get_version() + 1;
					}
					else
					{
						$new_version = 1;
					}								
					
					$previous_version_pk_id = $major_value_version_id;
				}

				if ($current == true)
				{
					$value_version_id = $this->value_version->create($this->value_id, $new_version, $value_string , $value_checksum, $previous_version_pk_id, $new_internal_revision, true, $user->get_user_id());			
					$current_value_version->set_current(false);
				}
				else
				{
					$value_version_id = $this->value_version->create($this->value_id, $new_version, $value_string , $value_checksum, $previous_version_pk_id, $new_internal_revision, true, $user->get_user_id());
				}
				
				if ($value_version_id != null)
				{
					if ($full_text_index == true and $full_text_content_string)
					{
						$this->value_version->set_text_search_vector($full_text_content_string, "english");
					}
					$this->value_version->__construct($value_version_id);
					if ($transaction_id != null)
					{
						$transaction->commit($transaction_id);
					}
					return true;
				}
				else
				{
					if ($transaction_id != null)
					{
						$transaction->rollback($transaction_id);
					}
					return false;
				}
			}
			else
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return true;
			}
		}
		else
		{
			$current_value = $this->value_version->get_value();
			
			if (strlen($current_value) > 0)
			{
				$current_value_string = trim(unserialize($current_value));
				$new_value_string = trim($_POST[desc]);
				
				if ($current_value_string != $new_value_string and $new_value_string)
				{
					$value_checksum = md5($new_value_string);
					
					$current_value_version_id = ValueVersion_Access::get_current_entry_by_toid($this->value_id);
					$current_value_version = new ValueVersion_Access($current_value_version_id);
					
					$highest_revision_value_version_id = ValueVersion_Access::get_highest_internal_revision_entry_by_toid($this->value_id);
					$highest_revision_value_version = new ValueVersion_Access($highest_revision_value_version_id);

					$new_internal_revision = $current_value_version->get_internal_revision()+1;
						
					if ($major == true)
					{										
						if ($previous_version_id == null)
						{
							$new_version = $current_value_version->get_version()+1;
							$previous_version_pk_id = null;
						}
						else
						{
							$major_value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $previous_version_id);
							$major_value_version = new ValueVersion_Access($major_value_version_id);
							
							if ($major_value_version->get_previous_version_id() == $major_value_version->get_id())
							{
								$previous_version_pk_id = null;
							}
							else
							{
								$previous_version_pk_id = $major_value_version->get_previous_version_id();
							}
							
							$major_value_version_id = ValueVersion_Access::get_highest_major_version_entry_by_toid_and_previous_version_id($major_value_version->get_toid(), $previous_version_pk_id);
							$major_value_version = new ValueVersion_Access($major_value_version_id);
							
							$new_version = $major_value_version->get_version()+1;
						}
					}
					else
					{				
						$major_value_version_id = ValueVersion_Access::get_entry_by_toid_and_internal_revision($this->value_id, $previous_version_id);
						
						$current_minor_version_id = ValueVersion_Access::get_highest_minor_version_entry_by_id($major_value_version_id);
						
						if ($current_minor_version_id)
						{
							$current_minor_version = new ValueVersion_Access($current_minor_version_id);
							$new_version = $current_minor_version->get_version() + 1;
						}
						else
						{
							$new_version = 1;
						}								
						
						$previous_version_pk_id = $major_value_version_id;
					}
					
					if ($current == true)
					{
						$value_version_id = $this->value_version->create($this->value_id, $new_version, serialize($new_value_string) , $value_checksum, $previous_version_pk_id, $new_internal_revision, true, $user->get_user_id());			
						$current_value_version->set_current(false);
					}
					else
					{
						$value_version_id = $this->value_version->create($this->value_id, $new_version, serialize($new_value_string) , $value_checksum, $previous_version_pk_id, $new_internal_revision, true, $user->get_user_id());
					}
					
					if ($value_version_id != null)
					{
						$this->value_version->set_text_search_vector($new_value_string, "english");
						$this->value_version->__construct($value_version_id);
						if ($transaction_id != null)
						{
							$transaction->commit($transaction_id);
						}
						return true;
					}
					else
					{
						if ($transaction_id != null)
						{
							$transaction->rollback($transaction_id);
						}
						return false;
					}
				}
			}
			else
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			}
		}
	}

	/**
	 * Moves value to another location
	 * @todo LATER: Move value is not supported in current version
	 * @param integer $folder_id
	 * @return bool
	 */
	public function move($folder_id)
	{
		
	}
	
	/**
	 * Copies value to another location
	 * @todo LATER: Copy value is not supported in current version
	 * @param integer $folder_id
	 * @return bool
	 */
	public function copy($folder_id)
	{
		
	}

	/**
	 * Checks if the current value is the current version
	 * @return bool
	 */
	public function is_current()
	{
		if ($this->value_id and $this->value_version)
		{
			if ($this->value_version->get_current() == true)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return integer
	 */
	public function get_value_internal_revisions()
	{
		if ($this->value_id and $this->value_version)
		{
			$value_version_array = ValueVersion_Access::list_entries_by_toid($this->value_id);
			$return_array = array();
	
			foreach($value_version_array as $key => $value)
			{
				$value_version = new ValueVersion_Access($value);
				array_push($return_array, $value_version->get_internal_revision());
			}
			
			if (count($return_array) > 0)
			{
				return $return_array;
			}
			else
			{
				return null;
			}			
		}
	}
	
	/**
	 * @return string
	 */	
	public function get_type_name()
	{
		if ($this->value)
		{
			$value_type = new ValueType($this->value->get_type_id());
			return $value_type->get_name();
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * @todo LATER: individual value naming
	 */
	public function get_name()
	{
		return $this->get_type_name();
	}
	
	/**
	 * @return integer
	 */
	public function get_type_id()
	{
		if ($this->value)
		{
			return $this->value->get_type_id();
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * @return string (X.Y.Z)
	 */
	public function get_version()
	{
		if ($this->value_version)
		{
			if ($this->value_version->get_id() == $this->value_version->get_previous_version_id())
			{
				return $this->value_version->get_version();
			}
			else
			{
				$tmp_value_version_id = $this->value_version->get_id();
				$tmp_internal_revision = $this->value_version->get_internal_revision();
				$tmp_previous_version_id = $this->value_version->get_previous_version_id();
				
				$return_string = $this->value_version->get_version();
				
				do
				{
					$tmp_value_version = new ValueVersion_Access($tmp_previous_version_id);
					$return_string = $tmp_value_version->get_version().".".$return_string;
					$tmp_previous_version_id = $tmp_value_version->get_previous_version_id();
				}while($tmp_value_version->get_id() != $tmp_value_version->get_previous_version_id());
			
				return $return_string;
			}
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * @return integer
	 */
	public function get_internal_revision()
	{
		if ($this->value_version)
		{
			return $this->value_version->get_internal_revision();
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * @return string
	 */
	public function get_value()
	{
		if ($this->value_version)
		{
			return $this->value_version->get_value();
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * @return string
	 */
	public function get_checksum()
	{
		if ($this->value_version)
		{
			return $this->value_version->get_checksum();
		}
		else
		{
			return null;
		}
	}
	    
    /**
     * Checks if the xml-array contains one or more each-statements
     * @param array $xml_array
     * @return bool
     */
    private function array_contains_each_statements($xml_array)
    {
    	if (is_array($xml_array) and count($xml_array) >= 1)
    	{
    		foreach($xml_array as $key => $value)
    		{
    			$value[1] = strtolower(trim($value[1]));
    			$value[2] = strtolower(trim($value[2]));
    			
    			if ($value[1] == "each")
    			{
    				return true;
    			}
    		}
    		return false;
    	}
    	else
		{
    		return false;
    	}
    }
    
    /**
     * Resolves the each statements of the xml_array
     * @param array $xml_array
     * @return array
     */
    private function resolve_each_statements($xml_array, $folder_id)
    {
    	$xml_return_array = array();
    	$xml_each_temp_array = array();
    	
    	$each_count = 0;
    	$value_var_content = "";
    	
    	if (is_array($xml_array) and count($xml_array) >= 1)
    	{
    		$value_var = new ValueVar($folder_id);
	
    		foreach($xml_array as $key => $value)
    		{
    			$value[1] = strtolower(trim($value[1]));
    			$value[2] = strtolower(trim($value[2]));
    			
    			if ($value[1] == "each" and $value[2] != "#")
    			{
    				$each_count++;
					$value_var_content = $value_var->get_content($value[3]['var']);				
    			}
    			else
    			{
	    			if ($value[1] == "each" and $value[2] == "#")
	    			{
	    				if ($each_count == 1)
	    				{
		    				if (is_array($value_var_content) and count($value_var_content) >= 1)
		    				{
		    					foreach($value_var_content as $value_var_key => $value_var_value)
		    					{
				    				if (is_array($xml_each_temp_array) and count($xml_each_temp_array) >= 1)
				    				{
					    				foreach ($xml_each_temp_array as $sub_key => $sub_value)
					    				{
					    					if ($sub_value[3]['var'])
					    					{
					    						$sub_value[3]['var'] = str_replace("each", $value_var_value, $sub_value[3]['var']);
					    					}
					    					if ($sub_value[3]['name'])
					    					{
					    						$sub_value[3]['name'] = $sub_value[3]['name']."-".$value_var_value;
					    					}
					    					array_push($xml_return_array, $sub_value);
					    				}
				    				}
		    					}
		    				}
	    				}
	    				$each_count--;	
	    				$xml_each_temp_array = array();
	    			}
	    			else
	    			{
	    				if ($each_count < 1)
	    				{
		    				array_push($xml_return_array, $value);
		    			}
		    			else
		    			{
		    				array_push($xml_each_temp_array, $value);
		    			}
	    			}
    			}
    		}
    		return $xml_return_array;
    	} 	
    }
    
    /**
     * Returns an HTML-String of the current value
     * @todo remove HTML
     * @param array $error_array
     * @param integer $type_id
     * @return string
     */
	public function get_html_form($error_array, $type_id, $folder_id)
	{	
		if ($type_id == null and $this->value_id)
		{
			$type_id = $this->value->get_type_id();
		}
		
		if ($folder_id == null and $this->value_id)
		{
			$folder_id = $this->get_parent_folder_id();
		}
		
		$value_type = new ValueType($type_id);
    	$olvdl = new Olvdl($value_type->get_template_id());
    	
    	$xml_array = $olvdl->get_xml_array();
    	
    	if (is_array($xml_array) and count($xml_array) >= 1)
    	{
			if ($this->value_version->get_value())
			{
				$content_array = unserialize($this->value_version->get_value());
			}
			else
			{
				$content_array = $this->content_array;
			}
			
			if ($this->array_contains_each_statements($xml_array))
			{
				$xml_array = $this->resolve_each_statements($xml_array, $folder_id);
			}
			
			if (is_array($content_array) and count($content_array) >= 1)
			{
				$autofield_array = array();
				$autofield_counter = 0;
				
				$value_array = array();
				
				foreach ($content_array as $fe_key => $fe_value)
				{
					if (strpos($fe_key, "af-") !== false)
					{
						if (strpos($fe_key, "-vartype") !== false)
						{
							$autofield_array[$autofield_counter][1] = $fe_value;
						}
						elseif(strpos($fe_key, "-name") !== false)
						{
							$autofield_array[$autofield_counter][0] = $fe_value;
							$autofield_counter++;
						}
						else
						{
							$autofield_array[$autofield_counter][2] = $fe_value;
						}
					}
					else
					{
						$value_array[$fe_key] = $fe_value;
					}
				}
			
				if (is_array($autofield_array) and count($autofield_array) >= 1)
				{
					$local_autofield_array_string = serialize($autofield_array);
				}
			
				$content_array = $value_array;			
			}
							
			$in_format = false;
			$in_line = false;
			
			foreach($xml_array as $key => $value)
			{
				$value[0] = trim(strtolower($value[0]));
				$value[1] = trim(strtolower($value[1]));
				$value[2] = trim(strtolower($value[2]));
				
				if ($value[1] == "format" and $value[2] != "#")
				{
					$return .= "<table class='formTable'>\n";	
					$in_format = true;
					
					if ($value[3][colspan])
					{
						$colspan_array = array();
						$colspan_array = explode(",",$value[3][colspan]);
						$colspan_array_count = 0;
					}
				}
				
				if ($value[1] == "format" and $value[2] == "#")
				{
					$return .= "</table>\n";
					$in_format = false;
					
					if ($colspan_array)
					{
						unset($colspan_array);
						unset($colspan_array_count);
					}
				}
				
				if ($value[1] == "line" and $value[2] != "#")
				{
					if ($in_format == true)
					{
						$return .= "<tr>\n";	
						$in_line = true;
						
						if ($value[3][colspan])
						{
							$colspan_array = array();
							$colspan_array = explode(",",$value[3][colspan]);
							$colspan_array_count = 0;
						}
					}
				}
				
				if ($value[1] == "line" and $value[2] == "#")
				{
					if ($in_format == true)
					{
						$return .= "</tr>\n";
						$in_line = false;
						
						if ($colspan_array)
						{
							unset($colspan_array);
							unset($colspan_array_count);
						}
					}
					else
					{
						$return .= "<br />\n";
					}
				}
				
				if ($value[1] == "print" and $value[2] != "#")
				{
					if ($in_line == true and $in_format == true)
					{
						if ($colspan_array)
						{
							$return .= "<td colspan='".$colspan_array[$colspan_array_count]."'>\n";
							$colspan_array_count++;
						}
						else
						{
							$return .= "<td>\n";
						}
					}

					if ($value[3][format])
					{
						$return .= "<span class='".$value[3][format]."'>\n";
					}
					else
					{
						$return .= "<span>";
					}
					
					if ($value[3][value] and !$value[3]['var'])
					{
						$print_value = $value[3][value];
						$print_value = str_replace("[high]","<sup>",$print_value);
						$print_value = str_replace("[low]","<sub>",$print_value);
						$print_value = str_replace("[/high]","</sup>",$print_value);
						$print_value = str_replace("[/low]","</sub>",$print_value);
						
						$return .= $print_value;
					}
					elseif (!$value[3][value] and $value[3]['var'])
					{
						$value_var = new ValueVar($folder_id);
						$value_var_content = $value_var->get_content($value[3]['var']);
								
						if (!is_array($value_var_content))
						{
							$return .= $value_var_content;
						}
						else
						{
							$return .= "<div class='italic'>Error: Array is given</div>";
							print_r($value_var_content);
						}
					}
					else
					{
						$return .= "";
					}
					
					
					if ($value[3][newline] == "true")
					{
						$return .= "<br />\n";
					}
					
					$return .= "</span>\n";
					
					if ($in_line == true and $in_format == true)
					{
						$return .= "</td>\n";
					}
				}
				
				if ($value[1] == "field" and $value[2] != "#")
				{
					if ($colspan_array)
					{
						$return .= "<td colspan='".$colspan_array[$colspan_array_count]."'>";
						$colspan_array_count++;
					}
					else
					{
						$return .= "<td>";
					}
					
					if ($value[3][name])
					{
						$field_name = $value[3][name];
					}
					
					if ($content_array and !$_POST[$value[3][name]])
					{
						$field_default = $content_array[$value[3][name]];
					}
					elseif($value[3]['default'])
					{
						$field_default = $value[3]['default'];
					}
					else
					{
						$field_default = $_POST[$value[3][name]];
					}
					
					if ($value[3][length])
					{
						$field_length = $value[3][length];
					}
					else
					{
						$field_length = 30;
					}
					
					if ($value[3][size])
					{
						$sizeArray = explode(",",$value[3][size]);
						$field_cols = $sizeArray[0];
						$field_rows = $sizeArray[1];
						unset($sizeArray);
					}
					else
					{
						$field_cols = 64;
						$field_rows = 15;
					}
					
					if ($value[3][type])
					{
						switch (trim(strtolower($value[3][type]))):
						
							case("textarea"):
								$type = 4;
							break;
													
							case("checkbox"):
								$type = 3;
								$typename = "checkbox";
							break;
							
							case("dropdown"):
								$type = 2;
							break;
							
							default:
								$type = 1;
								$typename = "textfield";
							break;
						
						endswitch;
					}
					else
					{
						$type = 1;
						$typename = "textfield";
					}
					
					if ($value[3]['vartype'])
					{
						$vartype = $value[3]['vartype'];
					}
					else
					{
						$vartype = "string";
					}
					
					switch ($type):
						
						case (1):
							$return .= "<input type='".$typename."' name='".$field_name."' value='".$field_default."' size='".$field_length."' />\n" .
									"<input type='hidden' name='".$field_name."-vartype' value='".$vartype."' />\n";
						break;
						
						case (2):
							$return .= "<select name='".$field_name."' />\n";
							
							if ($value[3][value] and !$value[3]['var'])
							{
		
								$value_array = explode(",",$value[3]['value']);
								$value_array_length = substr_count($value[3]['value'],",");
								
								for ($i=0;$i<=$value_array_length;$i++)
								{
									$return .= "<option>".$value_array[$i]."</option>\n";
								}
							}
							elseif (!$value[3][value] and $value[3]['var'])
							{
								$value_var = new ValueVar($folder_id);
								$value_var_content = $value_var->get_content($value[3]['var']);

								if (is_array($value_var_content) and count($value_var_content) >= 1) {
	
									foreach ($value_var_content as $value_var_key => $value_var_value)
									{
										if (!is_array($value_var_value))
										{
											if ($value_var_key == $field_default)
											{
												$return .= "<option value='".$value_var_key."' selected='selected'>".$value_var_value."</option>\n";
											}
											else
											{
												$return .= "<option value='".$value_var_key."'>".$value_var_value."</option>\n";
											}
										}
									}
								}
							}
							else
							{
								// Exception
							}

							echo "</select>\n";
						break;
						
						case (3):
							$return .= "<select type='".$typename."' name='".$field_name."' />\n";
						break;
						
						default:
							$return .= "<textarea name='".$field_name."' cols='".$field_cols."' rows='".$field_rows."' >".$field_default."</textarea>\n" .
										"<input type='hidden' name='".$field_name."-vartype' value='".$vartype."' />\n";
						break;
					
					endswitch;
					
					if ($value[3][set])
					{
						$return .= "<input type='hidden' name='".$field_name."-set' value='".$value[3][set]."' />\n";
					}
					
					$return .= "".$error_array[$field_name]."</td>\n";
					
					unset($field_name);
					unset($field_default);
				}
				
				if ($value[1] == "autofield" and $value[2] != "#")
				{
					$return .= "<div class='autofield'>" .
								"<div class='autofield_header'>Dynamic Values</div>" .
								"<div id='autofield_area'></div>" .
								"<div id='autofield_footer'></div>" .
								"<button type='button' id='autofield_edit' class='autofield_button'>edit</button>" .
								"</div>";
					
					$return .= "<script language='javascript'>" .
								"auto_field = new AutoField();";
					
					if ($this->autofield_array_string)
					{		
						$return .= "auto_field.setFieldArray('".$this->autofield_array_string."');";
					}
					elseif($local_autofield_array_string)
					{
							$return .= "auto_field.setFieldArray('".$local_autofield_array_string."');";
					}
						
					$return .= "auto_field.initialize();" .
								"</script>";
					
				}
			}
    	}
    	else
    	{
    		// Exeption
    	}
		return $return;
	}
		
	/**
	 * @param array $content_array
	 */
	public function set_content_array($content_array)
	{
		if ($content_array and is_array($content_array))
		{
			$this->content_array = $content_array;
		}
	}
	
	/**
	 * @param array $autofield_array_string
	 */
	public function set_autofield_array_string($autofield_array_string)
	{
		if ($autofield_array_string)
		{
			$this->autofield_array_string = $autofield_array_string;
		}
	}
	
	
	/**
	 * @return array
	 */
	public static function list_entries_by_type_id($type_id)
	{
		return Value_Access::list_entries_by_type_id($type_id);
	}
	
	/**
	 * Returns true, if a value ($value_id) exists
	 * @param integer $value_id
	 * @return bool
	 */
	public static function exist_value($value_id)
	{
		if (is_numeric($value_id))
		{
   			return Value_Access::exist_value_by_value_id($value_id);
   		}
   		else
   		{
   			return false;
   		}
	}
	
	/**
	 * @param integer $data_entity_id
	 * @return integer
	 */
	public static function get_value_id_by_data_entity_id($data_entity_id)
	{	
		return Value_Access::get_entry_by_data_entity_id($data_entity_id);
	}
	
	/**
	 * Sets the owner_id on null, where owner_id = $owner_id
	 * @param integer $owner_group_id
	 * @return bool
	 */
	public static function set_owner_group_id_on_null($owner_group_id)
	{
		return Value_Access::set_owner_group_id_on_null($owner_group_id);
	}
	
	/**
	 * Checks if an value-id is a type of type-id
	 * @param integer $value_id
	 * @param integer $type_id
	 * @return bool
	 */
	public static function is_entry_type_of($value_id, $type_id)
	{
		return Value_Access::is_entry_type_of($value_id, $type_id);
	}
	
	/**
     * @param object $event_object
     * @return bool
     */
    public static function listen_events($event_object)
    {
    	if ($event_object instanceof UserDeleteEvent)
    	{
    		if (ValueVersion_Access::set_owner_id_on_null($event_object->get_user_id()) == false)
    		{
    			return false;
    		}
    	}
    	
    	return true;
    }
    
}
?>