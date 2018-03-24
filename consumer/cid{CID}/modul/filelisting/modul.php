<?php
/**
 * FOTOGALLERY
 *
 * The Fotoalbum displays all images of a choosen category.
 * The List displays thumbnails of the original images and opens a Javascript widget
 * on demand, showing the full size version.
 *
 * Copyright (C) Kevin Papst
 *
 * For further information go to:
 * http://wiki.bigace.de/bigace:extensions:modul:fotogallery
 *
 * @version $Id: modul.php,v 1.18 2009/05/26 23:04:03 kpapst Exp $
 * @author Kevin Papst 
 * @package bigace.modul
 */

// ----------------------------------------------------------
// Module code starts here
import('classes.util.LinkHelper');
import('classes.file.File');
import('classes.file.FileService');
import('classes.category.Category');
import('classes.category.CategoryService');
import('classes.item.ItemProjectService');
import('classes.util.IOHelper');

define('FL_PRJ_NUM_CATEGORY', 'filelisting_category');
define('FL_PRJ_NUM_DESCRIPTION', 'filelisting_show_description');
define('FL_PRJ_NUM_ORDER_NAME', 'filelisting_sort_name');
define('FL_PRJ_NUM_ORDER_POS', 'filelisting_sort_position');
define('FL_PRJ_NUM_ORDER_REVERSE', 'filelisting_sort_reverse');
define('FL_PRJ_NUM_CSS','filelisting_use_css');

loadLanguageFile("bigace");

$modul          = new Modul($MENU->getModulID());
$CAT_SERVICE    = new CategoryService();
$FILE_SERVICE   = new FileService();
$projectService = new ItemProjectService(_BIGACE_ITEM_MENU);

/* #########################################################################
 * ############################  Show Admin Link  ##########################
 * #########################################################################
 */
if ($modul->isModulAdmin())
{

    import('classes.util.links.ModulAdminLink');
    import('classes.util.LinkHelper');
    $mdl = new ModulAdminLink();
    $mdl->setItemID($MENU->getID());
    $mdl->setLanguageID($MENU->getLanguageID());

    ?>
    <script type="text/javascript">
    <!--
    function openAdmin()
    {
        fenster = open("<?php echo LinkHelper::getUrlFromCMSLink($mdl); ?>","ModulAdmin","menubar=no,toolbar=no,statusbar=no,directories=no,location=no,scrollbars=yes,resizable=no,height=350,width=400,screenX=0,screenY=0");
        bBreite=screen.width;
        bHoehe=screen.height;
        fenster.moveTo((bBreite-400)/2,(bHoehe-350)/2);
    }
    // -->
    </script>
    <?php

    echo '<div class="modulAdminLink" align="left"><a onClick="openAdmin(); return false;" href="'.LinkHelper::getUrlFromCMSLink($mdl).'">'.getTranslation('modul_admin').'</a></div>';
}


/* #########################################################################
 * ##################  Show List of all categorized Images  ################
 * #########################################################################
 */
if(!$projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_CATEGORY))
{
    echo '<br><b>'.getTranslation('gallery_unconfigured').'</b><br>';
}
else
{
    if($projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_CSS))
        $useCSS = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_CSS);
    else
        $useCSS = FALSE;
	
    // show description of images?
    if($projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_DESCRIPTION))
        $showDesc = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_DESCRIPTION);
    else
        $showDesc = FALSE;

    // order by name
    if($projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_NAME))
        $orderByName = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_NAME);
    else
        $orderByName = FALSE;

    // order by position
    if($projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_POS))
        $orderByPosition = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_POS);
    else
        $orderByPosition = FALSE;

    // order by position
    if($projectService->existsProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_POS))
        $orderReverse = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_ORDER_REVERSE);
    else
        $orderReverse = FALSE;
        
    // get image category
    $CUR_CAT = $projectService->getProjectNum($MENU->getID(), $MENU->getLanguageID(), FL_PRJ_NUM_CATEGORY);

    // ... and now fetch all linked images
    $search = $CAT_SERVICE->getItemsForCategory($FILE_SERVICE->getItemtype(), $CUR_CAT);
    
    if($useCSS) 
    {
	    import('classes.smarty.SmartyStylesheet');
	    $sss = new SmartyStylesheet("File-Listing");
	    echo '<link rel="stylesheet" href="'.$sss->getURL().'" type="text/css" media="screen" />'."\n";
    }
    
    // TODO add option to chosse whether to display content or not
    echo $MENU->getContent();

    
    if($search->count() > 0)
    {
    	$allImages = array();
		
    	// ---------------------------------
    	if($orderByName) 
    	{
        	while ($search->hasNext()) {
        		$temp = $search->next();
        		$temp = $FILE_SERVICE->getClass($temp['itemid']);
        		$allImages[$temp->getName()] = $temp;
        	}
        	if($orderReverse)
	        	krsort($allImages, SORT_STRING);
        	else
	        	ksort($allImages, SORT_STRING);
    	}
    	else if($orderByPosition)
    	{
        	while ($search->hasNext()) {
        		$temp = $search->next();
        		$temp= $FILE_SERVICE->getClass($temp['itemid']);
        		$allImages[$temp->getPosition()] = $temp;
        	}
        	if($orderReverse)
	        	krsort($allImages, SORT_NUMERIC);
        	else
        		ksort($allImages, SORT_NUMERIC);
    	}
    	else
    	{
        	while ($search->hasNext()) {
        		$temp = $search->next();
        		$temp= $FILE_SERVICE->getClass($temp['itemid']);
        		$allImages[$temp->getID()] = $temp;
        	}
        	if($orderReverse)
	        	krsort($allImages, SORT_NUMERIC);
        	else
        		ksort($allImages, SORT_NUMERIC);
    	}
    	// ---------------------------------
    	
        echo '<div id="fileListing">';
        foreach ($allImages AS $key => $temp)
        {
            $link = LinkHelper::getCMSLinkFromItem($temp);
            $fileURL = LinkHelper::getUrlFromCMSLink($link);
            $mimetype = " " . getFileExtension($temp->getOriginalName());
            ?>
            <div class="entry">
            	<a href="<?php echo $fileURL; ?>" class="download<?php echo $mimetype; ?>"><?php echo $temp->getName(); ?></a>
				<?php
	            if($showDesc) {
	            	echo '<span class="caption">'.$temp->getDescription().'</span>';
				} 
            	echo '<span class="stats">'.getTranslation('gallery_downloads').': '.$temp->getViews().' - '.getTranslation('gallery_date').': '.date('d.m.Y H:i',$temp->getLastDate()).'</span>';
				?>
            </div>
            <?php
        }
        echo '</div><div class="fileListingFooter"><a href="http://www.keleo.de/freebies/" title="Keleo Freebies" target="_blank">File-Listing</a> is powered by <a href="http://www.keleo.de" target="_blank" title="Webdesign Bonn">Keleo</a></div>';
    }
    else
    {
        echo '<b>'.getTranslation('gallery_empty').'</b>';
    }
}

?>