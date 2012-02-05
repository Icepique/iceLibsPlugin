<?php

define('XHPROF_FLAGS_NO_BUILTINS', 1);
define('XHPROF_FLAGS_CPU', 2);
define('XHPROF_FLAGS_MEMORY', 4);

/**
 * @param  integer  $flags
 * @param  array    $options
 *
 * @return null
 */
function xhprof_enable($flags = 0, array $options) { return null; }

/**
 * @return array  An array of xhprof data, from the run.
 */
function xhprof_disable() { return array(); }
