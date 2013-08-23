<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//--------------------------------------------------------------------
// Profiler Sections
//--------------------------------------------------------------------
// Choose which sections you want to show up in your profiler bar.
//

$config['benchmarks']           = TRUE;
$config['config']               = TRUE;
$config['controller_info']      = TRUE;
$config['get']                  = TRUE;
$config['http_headers']         = TRUE;
$config['memory_usage']         = TRUE;
$config['post']                 = TRUE;
$config['queries']              = TRUE;
$config['uri_string']           = TRUE;
$config['view_data']            = TRUE;
$config['query_toggle_count']   = 50;