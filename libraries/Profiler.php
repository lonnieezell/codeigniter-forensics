<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Profiler Class
 *
 * This class enables you to display benchmark, query, and other data
 * in order to help with debugging and optimization.
 *
 * Note: At some point it would be good to move all the HTML in this class
 * into a set of template files in order to allow customization.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/profiling.html
 */
class CI_Profiler extends CI_Loader {

	protected $CI;

	protected $_available_sections = array(
										'benchmarks',
										'get',
										'memory_usage',
										'post',
										'uri_string',
										'controller_info',
										'queries',
										'eloquent',
										'http_headers',
										'config',
										'files',
										'console',
										'userdata',
										'view_data'
										);

	protected $_sections = array();		// Stores _compile_x() results

	protected $_query_toggle_count 	= 25;

	// --------------------------------------------------------------------

	public function __construct($config = array())
	{
		$this->CI =& get_instance();
		$this->CI->load->language('profiler');

		// If the config file has a query_toggle_count,
		// use it, but remove it from the config array.
		if ( isset($config['query_toggle_count']) )
		{
			$this->_query_toggle_count = (int) $config['query_toggle_count'];
			unset($config['query_toggle_count']);
		}

		// default all sections to display
		foreach ($this->_available_sections as $section)
		{
			if ( ! isset($config[$section]))
			{
				$this->_compile_{$section} = TRUE;
			}
		}

		// Make sure the Console is loaded.
		if (!class_exists('Console'))
		{
			$this->CI->load->library('Console');
		}

		$this->set_sections($config);

		// Strange hack to get access to the current
		// vars in the CI_Loader class.
		$this->_ci_cached_vars = $this->CI->load->_ci_cached_vars;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * Sets the private _compile_* properties to enable/disable Profiler sections
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function set_sections($config)
	{
		foreach ($config as $method => $enable)
		{
			if (in_array($method, $this->_available_sections))
			{
				$this->_compile_{$method} = ($enable !== FALSE) ? TRUE : FALSE;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Auto Profiler
	 *
	 * This function cycles through the entire array of mark points and
	 * matches any two points that are named identically (ending in "_start"
	 * and "_end" respectively).  It then compiles the execution times for
	 * all points and returns it as an array
	 *
	 * @return	array
	 */
	protected function _compile_benchmarks()
	{
		$profile = array();
		$output = array();

		foreach ($this->CI->benchmark->marker as $key => $val)
		{
			// We match the "end" marker so that the list ends
			// up in the order that it was defined
			if (preg_match("/(.+?)_end/i", $key, $match))
			{
				if (isset($this->CI->benchmark->marker[$match[1].'_end']) AND isset($this->CI->benchmark->marker[$match[1].'_start']))
				{
					$profile[$match[1]] = $this->CI->benchmark->elapsed_time($match[1].'_start', $key);
				}
			}
		}

		// Build a table containing the profile data.
		// Note: At some point we might want to make this data available to be logged.
		foreach ($profile as $key => $val)
		{
			$key = ucwords(str_replace(array('_', '-'), ' ', $key));
			$output[$key] = $val;
		}

		unset($profile);

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile Queries
	 *
	 * @return	string
	 */
	protected function _compile_queries()
	{
		$dbs = array();
		$output = array();

		// Let's determine which databases are currently connected to
		foreach (get_object_vars($this->CI) as $CI_object)
		{
			if (is_object($CI_object) && is_subclass_of(get_class($CI_object), 'CI_DB') )
			{
				$dbs[] = $CI_object;
			}
		}

		if (count($dbs) == 0)
		{
			return $this->CI->lang->line('profiler_no_db');
		}

		// Load the text helper so we can highlight the SQL
		$this->CI->load->helper('text');

		// Key words we want bolded
		$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');


		$total = 0; // total query time
		foreach ($dbs as $db)
		{

			foreach ($db->queries as $key => $val)
			{
				$time = number_format($db->query_times[$key], 4);
				$total += $db->query_times[$key];

				foreach ($highlight as $bold)
				{
					$val = str_replace($bold, '<b>'. $bold .'</b>', $val);
				}

				$output[][$time] = $val;
			}

		}

		if(count($output) == 0)
		{
			$output = $this->CI->lang->line('profiler_no_queries');
		}
		else
		{
			$total = number_format($total, 4);
			$output[][$total] = 'Total Query Execution Time';
		}

		return $output;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Compile Eloquent Queries
	 *
	 * @return	string
	 */
	protected function _compile_eloquent()
	{
		$output = array();
		
		// hack to make eloquent not throw error for now
		$this->CI->load->model('eloquent/assets/action');
		
		if ( ! class_exists('Illuminate\Database\Capsule\Manager')) {
			$output = 'Illuminate\Database has not been loaded.';
		} else {
			// Load the text helper so we can highlight the SQL
			$this->CI->load->helper('text');

			// Key words we want bolded
			$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');
		
		
			$total = 0; // total query time
			$queries = Illuminate\Database\Capsule\Manager::getQueryLog();
			foreach ($queries as $q)
			{
				$time = number_format($q['time']/1000, 4);
				$total += $q['time']/1000;
			
				$query = interpolateQuery($q['query'], $q['bindings']);
				foreach ($highlight as $bold)
					$query = str_ireplace($bold, '<b>'.$bold.'</b>', $query);
			
				$output[][$time] = $query;
			}

			if(count($output) == 0)
			{
				$output = $this->CI->lang->line('profiler_no_queries');
			}
			else
			{
				$total = number_format($total, 4);
				$output[][$total] = 'Total Query Execution Time';
			}
		}

		return $output;
	}


	// --------------------------------------------------------------------

	/**
	 * Compile $_GET Data
	 *
	 * @return	string
	 */
	protected function _compile_get()
	{
		$output = array();

		$get = $this->CI->input->get();

		if (count($get) == 0 || $get === false)
		{
			$output = $this->CI->lang->line('profiler_no_get');
		}
		else
		{
			foreach ($get as $key => $val)
			{
				if (is_array($val))
				{
					$output[$key] = "<pre>" . htmlspecialchars(stripslashes(print_r($val, true))) . "</pre>";
				}
				else
				{
					$output[$key] = htmlspecialchars(stripslashes($val));
				}
			}
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile $_POST Data
	 *
	 * @return	string
	 */
	protected function _compile_post()
	{
		$output = array();

		if (count($_POST) == 0)
		{
			$output = $this->CI->lang->line('profiler_no_post');
		}
		else
		{
			foreach ($_POST as $key => $val)
			{
				if ( ! is_numeric($key))
				{
					$key = "'".$key."'";
				}

				if (is_array($val))
				{
					$output['&#36;_POST['. $key .']'] = '<pre>'. htmlspecialchars(stripslashes(print_r($val, TRUE))) . '</pre>';
				}
				else
				{
					$output['&#36;_POST['. $key .']'] = htmlspecialchars(stripslashes($val));
				}
			}
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Show query string
	 *
	 * @return	string
	 */
	protected function _compile_uri_string()
	{
		if ($this->CI->uri->uri_string == '')
		{
			$output = $this->CI->lang->line('profiler_no_uri');
		}
		else
		{
			$output = $this->CI->uri->uri_string;
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Show the controller and function that were called
	 *
	 * @return	string
	 */
	protected function _compile_controller_info()
	{
		$output = $this->CI->router->fetch_class()."/".$this->CI->router->fetch_method();

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile memory usage
	 *
	 * Display total used memory
	 *
	 * @return	string
	 */
	protected function _compile_memory_usage()
	{
		if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '')
		{
			$output = number_format($usage) .' bytes';
		}
		else
		{
			$output = $this->CI->lang->line('profiler_no_memory_usage');
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile header information
	 *
	 * Lists HTTP headers
	 *
	 * @return	string
	 */
	protected function _compile_http_headers()
	{
		$output = array();

		foreach (array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD',' HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR') as $header)
		{
			$val = (isset($_SERVER[$header])) ? $_SERVER[$header] : '';
			$output[$header] =  $val;
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Compile config information
	 *
	 * Lists developer config variables
	 *
	 * @return	string
	 */
	protected function _compile_config()
	{
		$output = array();

		foreach ($this->CI->config->config as $config=>$val)
		{
			if (is_array($val))
			{
				$val = print_r($val, TRUE);
			}

			$output[$config] = htmlspecialchars($val);
		}

		return $output;
	}

	// --------------------------------------------------------------------

	public function _compile_files()
	{
		$files = get_included_files();

		sort($files);

		return $files;
	}

	//--------------------------------------------------------------------

	public function _compile_console()
	{
		$logs = Console::get_logs();

		if ($logs['console'])
		{
			foreach ($logs['console'] as $key => $log)
			{
				if ($log['type'] == 'log')
				{
					$logs['console'][$key]['data'] = print_r($log['data'], true);
				}
				elseif ($log['type'] == 'memory')
				{
					$logs['console'][$key]['data'] = $this->get_file_size($log['data']);
				}
			}
		}

		return $logs;
	}

	//--------------------------------------------------------------------

	function _compile_userdata()
	{
		$output = array();

		if (FALSE !== $this->CI->load->is_loaded('session'))
		{

			$compiled_userdata = $this->CI->session->all_userdata();

			if (count($compiled_userdata))
			{
				foreach ($compiled_userdata as $key => $val)
				{
					if (is_numeric($key))
					{
						$output[$key] = "'$val'";
					}

					if (is_array($val) || is_object($val))
					{
						$output[$key] = htmlspecialchars(stripslashes(print_r($val, true)));
					}
					else
					{
						$output[$key] = htmlspecialchars(stripslashes($val));
					}
				}
			}
		}

		return $output;
	}

	//--------------------------------------------------------------------

	/**
	 * Compile View Data
	 *
	 * Allows any data passed to views to be available in the profiler bar.
	 *
	 * @return array
	 */
	public function _compile_view_data()
	{
		$output = '';

		foreach ($this->_ci_cached_vars as $key => $val)
		{
			if (is_numeric($key))
			{
				$output[$key] = "'$val'";
			}

			if (is_array($val) || is_object($val))
			{
				$output[$key] = '<pre>' . htmlspecialchars(stripslashes(print_r($val, true))) . '</pre>';
			}
			else
			{
				$output[$key] = htmlspecialchars(stripslashes($val));
			}
		}

		return $output;
	}

	//--------------------------------------------------------------------


	public static function get_file_size($size, $retstring = null) {
        // adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
	    $sizes = array('bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

	    if ($retstring === null) { $retstring = '%01.2f %s'; }

		$lastsizestring = end($sizes);

		foreach ($sizes as $sizestring) {
	       	if ($size < 1024) { break; }
	           if ($sizestring != $lastsizestring) { $size /= 1024; }
		}

		if ($sizestring == $sizes[0]) { $retstring = '%01d %s'; } // Bytes aren't normally fractional
		return sprintf($retstring, $size, $sizestring);
	}

	//--------------------------------------------------------------------

	/**
	 * Run the Profiler
	 *
	 * @return	string
	 */
	public function run()
	{
		$this->CI->load->helper('language');

		$fields_displayed = 0;

		foreach ($this->_available_sections as $section)
		{
			if ($this->_compile_{$section} !== FALSE)
			{
				$func = "_compile_{$section}";
				if ($section == 'http_headers') $section = 'headers';
				$this->_sections[$section] = $this->{$func}();
				$fields_displayed++;
			}
		}

		return $this->CI->load->view('profiler_template', array('sections' => $this->_sections), true);
	}

}

// END CI_Profiler class

//--------------------------------------------------------------------

/* End of file Profiler.php */
/* Location: ./system/libraries/Profiler.php */
