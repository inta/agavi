<?php

/**
 * Data file for Asia/Bahrain timezone, compiled from the olson data.
 *
 * Auto-generated by the phing olson task on 02/21/2007 22:42:07
 *
 * @package    agavi
 * @subpackage translation
 *
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */

return array (
  'types' => 
  array (
    0 => 
    array (
      'rawOffset' => 14400,
      'dstOffset' => 0,
      'name' => 'GST',
    ),
    1 => 
    array (
      'rawOffset' => 10800,
      'dstOffset' => 0,
      'name' => 'AST',
    ),
  ),
  'rules' => 
  array (
    0 => 
    array (
      'time' => -1577935340,
      'type' => 0,
    ),
    1 => 
    array (
      'time' => 76190400,
      'type' => 1,
    ),
  ),
  'finalRule' => 
  array (
    'type' => 'static',
    'name' => 'AST',
    'offset' => 10800,
    'startYear' => 1973,
  ),
  'name' => 'Asia/Bahrain',
);

?>