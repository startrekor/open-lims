<?php
/**
 * @package sample
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
 * Sample Template Category Admin IO Class
 * @package sample
 */
class AdminSampleTemplateCatIO
{
	public static function home()
	{
		$list = new List_IO("SampleAdminTemplateCat", "ajax.php?nav=sample", "admin_sample_template_categorie_list_categories", "admin_sample_template_categorie_count_categories", "0", "SampleAdminTemplateCat");
				
		$list->add_column(Language::get_message("SampleGeneralListColumnName", "general"), "name", true, null);
		$list->add_column(Language::get_message("SampleGeneralListColumnEdit", "general"), "edit", false, "10%");
		$list->add_column(Language::get_message("SampleGeneralListColumnDelete", "general"), "delete", false, "10%");
		
		$template = new HTMLTemplate("sample/admin/sample_template_cat/list.html");	
	
		$paramquery = $_GET;
		$paramquery['action'] = "add";
		unset($paramquery['nextpage']);
		$params = http_build_query($paramquery,'','&#38;');
		
		$template->set_var("add_params", $params);
	
		$template->set_var("list", $list->get_list());		
		
		$template->output();		
	}

	public static function create()
	{
		if ($_GET['nextpage'] == 1)
		{
			$page_1_passed = true;
				
			if ($_POST['name'])
			{
				if (SampleTemplateCat::exist_name($_POST['name']) == true)
				{
					$page_1_passed = false;
					$error = "This name already exists";
				}
			}
			else
			{
				$page_1_passed = false;
				$error = "You must enter a name";
			}
		}
		else
		{
			$page_1_passed = false;
			$error = "";
		}

		if ($page_1_passed == false)
		{
			$template = new HTMLTemplate("sample/admin/sample_template_cat/add.html");
			
			$paramquery = $_GET;
			$paramquery['nextpage'] = "1";
			$params = http_build_query($paramquery,'','&#38;');
			
			$template->set_var("params",$params);
			
			if ($error)
			{
				$template->set_var("error", $error);
			}
			else
			{
				$template->set_var("error", "");	
			}

			if ($_POST['name'])
			{
				$template->set_var("name", $_POST['name']);
			}
			else
			{
				$template->set_var("name", "");	
			}
			
			$template->output();
		}
		else
		{				
			$sample_template_cat = new SampleTemplateCat(null);
								
			$paramquery = $_GET;
			unset($paramquery['action']);
			unset($paramquery['nextpage']);
			$params = http_build_query($paramquery,'','&#38;');
			
			if ($sample_template_cat->create($_POST['name']))
			{
				Common_IO::step_proceed($params, "Add Sample Template Categories", "Operation Successful", null);
			}
			else
			{
				Common_IO::step_proceed($params, "Add Sample Template Categories", "Operation Failed" ,null);	
			}
		}
	}
	
	/**
	 * @throws SampleTemplateCategoryIDMissingException
	 */
	public static function delete()
	{
		if ($_GET['id'])
		{
			if ($_GET['sure'] != "true")
			{
				$template = new HTMLTemplate("sample/admin/sample_template_cat/delete.html");
				
				$paramquery = $_GET;
				$paramquery['sure'] = "true";
				$params = http_build_query($paramquery);
				
				$template->set_var("yes_params", $params);
						
				$paramquery = $_GET;
				unset($paramquery['sure']);
				unset($paramquery['action']);
				unset($paramquery['id']);
				$params = http_build_query($paramquery,'','&#38;');
				
				$template->set_var("no_params", $params);
				
				$template->output();
			}
			else
			{
				$paramquery = $_GET;
				unset($paramquery['sure']);
				unset($paramquery['action']);
				unset($paramquery['id']);
				$params = http_build_query($paramquery,'','&#38;');
				
				$sample_template_cat = new SampleTemplateCat($_GET['id']);
				
				if ($sample_template_cat->delete())
				{							
					Common_IO::step_proceed($params, "Delete Sample Template Category", "Operation Successful" ,null);
				}
				else
				{							
					Common_IO::step_proceed($params, "Delete Sample Template Category", "Operation Failed" ,null);
				}	
			}
		}
		else
		{
			throw new SampleTemplateCategoryIDMissingException();
		}
	}
	
	/**
	 * @throws SampleTemplateCategoryIDMissingException
	 */
	public static function edit()
	{
		if ($_GET['id'])
		{
			$sample_template_cat = new SampleTemplateCat($_GET['id']);
		
			if ($_GET['nextpage'] == 1)
			{
				$page_1_passed = true;
				
				if ($_POST['name'])
				{
					if (SampleTemplateCat::exist_name($_POST['name']) == true and $sample_template_cat->get_name() != $_POST['name'])
					{
						$page_1_passed = false;
						$error = "This name already exists";
					}
				}
				else
				{
					$page_1_passed = false;
					$error = "You must enter a name";
				}
			}
			else
			{
				$page_1_passed = false;
				$error = "";
			}
	
			if ($page_1_passed == false)
			{
				$template = new HTMLTemplate("sample/admin/sample_template_cat/edit.html");
				
				$paramquery = $_GET;
				$paramquery['nextpage'] = "1";
				$params = http_build_query($paramquery,'','&#38;');
				
				$template->set_var("params",$params);
				
				if ($error)
				{
					$template->set_var("error", $error);
				}
				else
				{
					$template->set_var("error", "");	
				}
													 
				if ($_POST['name'])
				{
					$template->set_var("name", $_POST['name']);
				}
				else
				{
					$template->set_var("name", $sample_template_cat->get_name());
				}
							
				$template->output();
			}
			else
			{
				$paramquery = $_GET;
				unset($paramquery['nextpage']);
				unset($paramquery['action']);
				$params = http_build_query($paramquery);
				
				if ($sample_template_cat->set_name($_POST['name']))
				{
					Common_IO::step_proceed($params, "Edit Sample Template Category", "Operation Successful", null);
				}
				else
				{
					Common_IO::step_proceed($params, "Edit Sample Tempalte Category", "Operation Failed" ,null);	
				}
			}
		}
		else
		{
			throw new SampleTemplateCategoryIDMissingException();
		}
	}
	
	public static function handler()
	{			
		switch($_GET['action']):
			case "add":
				self::create();
			break;

			case "delete":
				self::delete();
			break;

			case "edit":
				self::edit();
			break;	
				
			default:
				self::home();
			break;
		endswitch;
	}
	
}

?>