<?php
/**
* @Copyright Ready Bytes Software Labs Pvt. Ltd. (C) 2010- author-Team Joomlaxi
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once( JPATH_ROOT .'/components/com_community/libraries/core.php');

// include joomla plugin framework
jimport( 'joomla.plugin.plugin' );
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

if(!class_exists('plgCommunityProfile_visitor'))
{

class plgCommunityProfile_visitor extends CApplications
{	
	var $name		= "Profile Visitor";
	
	function plgCommunityProfile_visitor(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->_db		= JFactory::getDBO();
		$this->session  = JFactory::getSession();
		$this->loadLanguage();
	}
	
	function onProfileDisplay()
	{
		
		$this->_createTable();
			
		$ownerId 	=  JFactory::getApplication()->input->get('userid', 0, 'GET');
		$accessorId = JFactory::getUser()->id;
		
		if(!$ownerId || !$accessorId){
			return true;
		}
		
		if($ownerId != $accessorId){
			$this->_updateRecord($ownerId, $accessorId);
		}
			
		$html = '';
		if($ownerId == $accessorId){
			$users = $this->_getViewer($ownerId);
	        $html  = $this->_getHtml($users);
		}
		return $html;
	}
	
	private function _createTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS `#__profile_visitor` (
  				`id` int(11) NOT NULL AUTO_INCREMENT,
  				`owner_id` int(11) NOT NULL,
  				`accessor_id` int(11) NOT NULL,
  				`last_visited` datetime NOT NULL DEFAULT '00:00:00',
  				`count` int(11) NOT NULL DEFAULT 0,
  				PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
				
		$this->_db->setQuery($sql);
		return $this->_db->query();
	}
	
	private function _updateRecord($ownerId, $accessorId)
	{		
		$sql = "SELECT * 
				FROM `#__profile_visitor`
				WHERE `owner_id` = $ownerId
				AND `accessor_id` = $accessorId";
		$this->_db->setQuery($sql);
		$result = $this->_db->loadObject();
	
		if($result)
		{					
			$sql = " UPDATE `#__profile_visitor` 
					 SET `last_visited` = NOW()";
			
			// do not update count if its from same session
			if($this->session->get('wvmp-view-'. $ownerId, false) == false )
				$sql .= ", `count` = {$result->count} + 1";

			$this->session->set('wvmp-view-'. $ownerId, true);
			$sql .=" WHERE `owner_id` = $ownerId
					 AND `accessor_id` = $accessorId";
		}   
		else
			$sql = " INSERT INTO `#__profile_visitor` (`owner_id`, `accessor_id`, `last_visited`, `count`)
					 VALUES ($ownerId, $accessorId, NOW(), 1)";
		
		$this->_db->setQuery($sql);
		
		if(!$this->_db->query()){
			JError::raiseError(500, $this->_db->getErrorMsg());
			return false;
		}
				
		return true;		
	}
	
	private function _getViewer($userId)
	{
		$sql = "SELECT * 
				FROM `#__profile_visitor`
				WHERE `owner_id` = $userId
				ORDER BY `last_visited` DESC
				LIMIT {$this->params->get('userLimit', 5)}";
		$this->_db->setQuery($sql);
		return $this->_db->loadObjectList('accessor_id');
	}
	
	function _getHtml($users)
	 {
	 	ob_start();
		?>
		<div class="cModule app-box">
		<div class="app-box-content">
		 <?php
		   if(empty($users)):
		    echo JText::_("Nobody visited your profile!!");
		    endif;	
		   foreach($users as $user):
		   		$link  = CRoute::_('index.php?option=com_community&view=profile&userid='.$user->accessor_id);
				$cUser = CFactory::getUser($user->accessor_id);
		  ?>
			 <a href=<?php echo $link;?>> |<?php echo $cUser->getDisplayName();?>|</a>
		  <?php endforeach;?>				
			</div>
		</div> 
		<?php 
		$content	= ob_get_contents();
		ob_end_clean(); 
		return $content;
	}
	
}
}
