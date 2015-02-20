<?php

/*
  - wbAdvert for Joomla! -----------------------------------------------------------------

  Version:        2.5.0
  Release Date:   05/04/2007
  Last Modified:  03/28/2013
  Developer:      David Hunt
  Copyright:      2007-2010 Webuddha.com, The Holodyn Corporation
  License:        GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
  Source:         http://software.webuddha.com/

  - Description --------------------------------------------------------------------------

  This module will output Advertisements for the wbAdvert component.

  ----------------------------------------------------------------------------------------
*/

// Block Direct Access
defined( '_JEXEC' ) or die('Access Denied');

// Block Direct Access
defined( 'DS' ) or define('DS', DIRECTORY_SEPARATOR);

// Application
$app = JFactory::getApplication();

// Pull Parameters
$moduleclass_sfx  = htmlspecialchars($params->get( 'moduleclass_sfx' ));
$params->def('debug', 0);

// Get DBO
$db =& JFactory::getDBO();

// Include Classes
require_once(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_wbadvert'.DS.'load.php');
$wbAdvert = new wbAdvert_advert($db);

// Include Component Langyuage
$jLang = JFactory::getLanguage();
$jLang->load('com_wbadvert', JPATH_SITE, 'en-GB', true);

// Pull Shown Advertisements
$wbAdvert_config = wbAdvert_config::getInstance();
if( !count( $wbAdvert_config->shown ) )
  $wbAdvert_config->shown = Array();

// Configuration
$cat_strict   = $params->get( 'cat_strict',   $wbAdvert_config->get('cat_strict', 1)  );
$wrap_module  = $params->get( 'wrap_module',  $wbAdvert_config->get('wrap_module', 0) );
$wrap_advert  = $params->get( 'wrap_advert',  $wbAdvert_config->get('wrap_advert', 0) );
$show_alert   = $params->get( 'show_alert',   $wbAdvert_config->get('show_alert', 1)  );
$minSize      = explode(',', $params->get('min_size', 2));
$maxSize      = explode(',', $params->get('max_size', 2));

// Pull Active Groups
$db->setQuery(
  "SELECT g.* FROM #__wbadvert_group AS g"
  ." WHERE g.module_id = $module->id"
  ." AND g.published = 1"
  ." AND g.count > 0"
  ." GROUP BY g.id"
  ." ORDER BY g.ordering"
  );
$groups = $db->loadObjectList();
echo $db->getErrorMsg();

if( !count( $groups ) ){

  if( $show_alert )
    echo '<span class="alert">'. JText::_('MOD_WBADVERT_ERR_NOACTIVEGROUPS') .'</span>';

} else {

  // Pull Visitor Page
  $filter = new wbAdvert_filter();

  // DEBUG
  if( $params->get('debug') ){
    echo "Menu: $filter->menu_id <br/>";
    echo "Category: $filter->category_id <br/>";
    echo "Category Chain: ".implode(',', $filter->category_chain) ." <br/>";
    echo "Content: $filter->content_id <br/>";
  }

  // Wrap the Module
  if( $wrap_module )
    echo '<div class="wbAdvert m'.$module->id.'">';

  // Loop through the Groups
  $total_shown = 0;
  foreach($groups AS $group ){

    // DEBUG
    if( $params->get('debug') )
      echo "$group->name <br/>";

    // Group Ordering
    $ordering = array();
    switch( $group->order ){
      case 'ordering':
        $ordering[] = "`idx_group`.`ordering` ASC";
        break;
      case 'name':
        $ordering[] = "`advert`.`name` ASC";
        break;
      default:
        $ordering[] = "RAND()";
        break;
    }

    // Pull Advertisements
    $adverts = $wbAdvert->getAdvertList(array(
      'not_advert_id' => $wbAdvert_config->shown,
      'group_id'      => $group->id,
      'menu_id'       => $filter->menu_id,
      'category_id'   => ($cat_strict ? $filter->category_id : $filter->category_chain),
      'content_id'    => $filter->content_id,
      'min_width'     => (count($minSize)==2?(int)$minSize[0]:null),
      'max_width'     => (count($maxSize)==2?(int)$maxSize[0]:null),
      'min_height'    => (count($minSize)==2?(int)$minSize[1]:null),
      'max_height'    => (count($maxSize)==2?(int)$maxSize[1]:null),
      'ordering'      => $ordering,
      'limit'         => $group->count
      ));

    if( count($adverts) )
      foreach( $adverts AS $advert ){
        $total_shown++;
        if( $wrap_advert )
          echo '<div class="ad a'.$advert->id.'">';
        echo $wbAdvert->getAdvertCode( $advert->id );
        if( $wrap_advert )
          echo '</div>';
        echo "\n";
        $wbAdvert->impression( $advert->id );
        $wbAdvert_config->shown[] = $advert->id;
      } // foreach $adverts

  } // foreach $groups

  if( !$total_shown && $show_alert )
    echo '<span class="alert">'. JText::_('MOD_WBADVERT_ERR_NOADVERTS') .'</span>';

  if( $wrap_module )
    echo '</div>';

} // if count groups
