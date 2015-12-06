<?php
/**
*
* @package phpBB Extension - Wiki
 * @copyright (c) 2015 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace tas2580\usermap\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.page_header'						=> 'page_header',
			'core.permissions'						=> 'permissions',
			'core.memberlist_view_profile'				=> 'memberlist_view_profile',
		);
	}

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	/** @var string */
	protected $phpbb_extension_manager;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\controller\helper		$helper		Controller helper object
	* @param \phpbb\template			$template		Template object
	* @param \phpbb\user				$user		User object
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\path_helper $path_helper, $phpbb_extension_manager, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
		$this->template = $template;
		$this->user = $user;
	}

	public function permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions += array(
			'u_usermap_view'	=> array(
				'lang'		=> 'ACL_U_USERMAP_VIEW',
				'cat'		=> 'profile'
			),
			'u_usermap_add'	=> array(
				'lang'		=> 'ACL_U_USERMAP_ADD',
				'cat'		=> 'profile'
			),
		);
		$event['permissions'] = $permissions;
	}

	public function page_header($event)
	{
		if ($this->auth->acl_get('u_usermap_view'))
		{
			$this->user->add_lang_ext('tas2580/usermap', 'link');
			$this->template->assign_vars(array(
				'U_USERMAP'	=> $this->helper->route('tas2580_usermap_index', array()),
			));
		}
	}

	public function memberlist_view_profile($event)
	{
		$data = $event['member'];
		if (!empty($this->user->data['user_usermap_lon']) && !empty($this->user->data['user_usermap_lat']))
		{
			$x1 = $this->user->data['user_usermap_lon'];
			$y1 = $this->user->data['user_usermap_lat'];
			$x2 = $data['user_usermap_lon'];
			$y2 = $data['user_usermap_lat'];
			// e = ARCCOS[ SIN(Breite1)*SIN(Breite2) + COS(Breite1)*COS(Breite2)*COS(Länge2-Länge1) ]
			$distance = acos(sin($x1=deg2rad($x1))*sin($x2=deg2rad($x2))+cos($x1)*cos($x2)*cos(deg2rad($y2) - deg2rad($y1)))*(6378.137);
		}


		// Center the map to user
		$this->template->assign_vars(array(
			'S_IN_USERMAP'		=> true,
			'USERMAP_CONTROLS'	=> 'false',
			'USERMAP_LON'		=> $data['user_usermap_lon'],
			'USERMAP_LAT'			=> $data['user_usermap_lat'],
			'USERMAP_ZOOM'		=> (int) 10,
			'DISTANCE'			=> ($distance <> 0) ? round($distance, 2) : '',
			'MARKER_PATH'		=> $this->path_helper->update_web_root_path($this->phpbb_extension_manager->get_extension_path('tas2580/usermap', true) . 'marker'),
			'MAP_TYPE'			=> $this->config['tas2580_usermap_map_type'],
			'GOOGLE_API_KEY'		=> $this->config['tas2580_usermap_google_api_key'],
		));

		// Set marker for user
		$this->template->assign_block_vars('user_list', array(
			'USER_ID'			=> $data['user_id'],
			'USERNAME'		=> get_username_string('full', $data['user_id'], $data['username'], $data['user_colour']),
			'LON'				=> $data['user_usermap_lon'],
			'LAT'				=> $data['user_usermap_lat'],
			'GROUP_ID'		=> $data['group_id'],
		));

		$sql = 'SELECT group_id, group_usermap_marker
			FROM ' . GROUPS_TABLE . '
			WHERE group_id = ' . (int) $data['group_id'];
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('group_list', array(
				'GROUP_ID'	=> $row['group_id'],
				'MARKER'		=> $row['group_usermap_marker'],
			));
		}
	}
}
