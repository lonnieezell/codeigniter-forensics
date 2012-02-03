<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Vd Class
 *
 * Beautifier var_dump library for CodeIgniter
 *
 * @category	Libraries
 * @author		Spir
 * @link		
 * @license		MIT
 * @version		1.0
 */
class Vd
{
	function dump ($var, $name = '', $return=FALSE)
	{
        $style = "/*background-color: whitesmoke;*/ padding: 8px 8px 8px 8px; /*border: 1px solid black;*/ text-align: left; font-family: monospace; font-size: 100%;";
        if (!$return)
        {
        	echo "<pre style='$style'>" .
        		($name != '' ? "$name : " : '') .
        		vd::_get_info_var ($var, $name) .
        		"</pre>";
        }
        else
        {
        	return "<pre style='$style'>" .
        		($name != '' ? "$name : " : '') .
        		vd::_get_info_var ($var, $name) .
        		"</pre>"; 
        }
    }
    
    function get ($var, $name = '')
    {
        return ($name != '' ? "$name : " : '') . vd::_get_info_var ($var, $name);
    }
    
	function _get_info_var ($var, $name = '', $indent = 0)
	{
		static $methods = array ();
		$indent > 0 or $methods = array ();
		
		$indent_chars = '    ';
		$spc = $indent > 0 ? str_repeat ($indent_chars, $indent ) : '';
		
		$out = '';
		if (is_array ($var))
		{
			$out .= "<span style='color:#cc0000;'><b>array</b></span>(" . count ($var) . ") (\n";
			foreach (array_keys ($var) as $key)
			{
				$out .= "$spc    [<span style='color:#cc0000;'>$key</span>] <font color='#888a85'>=&gt;</font> ";
				if (($indent == 0) && ($name != '') && (! is_int ($key)) && ($name == $key))
				{
					$out .= "LOOP\n";
				}
				else
				{
					$out .= vd::_get_info_var ($var[$key], '', $indent + 1);
				}
			}
			$out .= "$spc)";
		}
		else if (is_object ($var))
		{
			$class = "<span style='color:#00aaaa;'>" . get_class ($var) . "</span>";
			$out .= "<span style='color:purple;'><b>object</b></span> $class";
			$parent = get_parent_class ($var);
			$out .= $parent != '' ? " <span style='color:purple;'>extends</span> <span style='color:#006688;'>" . $parent  . "</span>" : '';
			$out .= " (\n";
			$arr = get_object_vars ($var);
			while (list($prop, $val) = each($arr))
			{
				$out .= "$spc  " . "<font color='#888a85'>-&gt;</font><span style='color:purple;'>$prop</span> = ";
				$out .= vd::_get_info_var ($val, $name != '' ? $prop : '', $indent + 1);
			}
			$arr = get_class_methods ($var);
			$out .= "$spc  " . "$class methods: " . count ($arr) . " ";
			if (in_array ($class, $methods))
			{
				$out .= "<font color='#a8aaa5'><i>[already listed]</i></font>\n";
			}
			else
			{
				$out .= "(\n";
				$methods[] = $class;
				while (list($prop, $val) = each($arr))
				{
					if ($val != $class)
					{
						$out .= $indent_chars . "$spc  " . "<font color='#888a85'>-&gt;</font><span style='color:blue;'>$val();</span>\n";
					}
					else
					{
						$out .= $indent_chars . "$spc  " . "<font color='#888a85'>-&gt;</font><span style='color:blue;'>$val();</span> <font color='#a8aaa5'><i>[<b>constructor</b>]</i></font>\n";
					}
				}
				$out .= "$spc  " . ")\n";
			}
			$out .= "$spc)";
		}
		else if (is_resource ($var))
			$out .= "<small>resource <font color='#a8aaa5'><i>(" . get_resource_type($var) . ")</i></font></small> <span style='color:steelblue;'>" . $var . "</span>";
		else if (is_int ($var))
			$out .= "<small>int</small> <span style='color:blue;'>" . $var . "</span>";
		else if (is_float ($var))
			$out .= "<small>float</small> <span style='color:blue;'>" . $var . "</span>";
		else if (is_numeric ($var))
			$out .= "<small>numstring (" . strlen($var) . ")</font></small> '<span style='color:green;'>" . $var . "</span>'";
		else if (is_string ($var))
			$out .= "<small>string (" . strlen($var) . ")</small> '<span style='color:green;'>" . nl2br(htmlentities($var)) . "</span>'";
		else if (is_bool ($var))
			$out .= "<small>bool</small> <span style='color:darkorange;'>" . ($var ? 'True' : 'False') . "</span>";
		else if (! isset ($var))
			$out .= "<small>null</small>";
		else
			$out .= "<small>other</small> " . $var . "";
		
		return $out . "\n";
	}
}

/* End of file Vd.php */
/* Location: ./application/libraries/Vd.php */