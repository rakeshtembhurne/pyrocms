<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * PyroCMS file Admin Controller
 *
 * Provides an admin for the file module.
 *
 * @author		Jerel Unruh - PyroCMS Dev Team
 * @package		PyroCMS\Core\Modules\Files\Controllers
 */
class Admin extends Admin_Controller {

	private $_folders	= array();
	private $_type 		= null;
	private $_ext 		= null;
	private $_filename	= null;

	// ------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$this->config->load('files');
		$this->lang->load('files');
		$this->load->library('files/files');

		$allowed_extensions = '';

		foreach (config_item('files:allowed_file_ext') as $type) 
		{
			$allowed_extensions .= implode('|', $type).'|';
		}

		$this->template->append_metadata(
			"<script>
				pyro.lang.fetching = '".lang('files:fetching')."';
				pyro.lang.fetch_completed = '".lang('files:fetch_completed')."';
				pyro.lang.start = '".lang('files:start')."';
				pyro.lang.width = '".lang('files:width')."';
				pyro.lang.height = '".lang('files:height')."';
				pyro.lang.ratio = '".lang('files:ratio')."';
				pyro.lang.full_size = '".lang('files:full_size')."';
				pyro.lang.cancel = '".lang('buttons.cancel')."';
				pyro.lang.synchronization_started = '".lang('files:synchronization_started')."';
				pyro.lang.untitled_folder = '".lang('files:untitled_folder')."';
				pyro.lang.exceeds_server_setting = '".lang('files:exceeds_server_setting')."';
				pyro.lang.exceeds_allowed = '".lang('files:exceeds_allowed')."';
				pyro.files = { permissions : ".json_encode(Files::allowed_actions())." };
				pyro.files.max_size_possible = '".Files::$max_size_possible."';
				pyro.files.max_size_allowed = '".Files::$max_size_allowed."';
				pyro.files.valid_extensions = '/".trim($allowed_extensions, '|')."$/i';
				pyro.lang.file_type_not_allowed = '".lang('files:file_type_not_allowed')."';
				pyro.lang.new_folder_name = '".lang('files:new_folder_name')."';
			</script>");
	}

	/**
	 * Folder Listing
	 */
	public function index()
	{
		$parts = explode(',', Settings::get('files_enabled_providers'));

		$this->template

			// The title
			->title($this->module_details['name'])

			// The CSS files
			->append_css('module::jquery.fileupload-ui.css')
			->append_css('module::files.css')

			// The Javascript files
			->append_js('module::jquery.fileupload.js')
			->append_js('module::jquery.fileupload-ui.js')
			->append_js('module::functions.js')

			// should we show the "no data" message to them?
			->set('folders', $this->file_folders_m->count_by('parent_id', 0))
			->set('locations', array_combine($parts, $parts))
			->set('folder_tree', Files::folder_tree())
			->set('admin', &$this);

		$files_path = Files::$path;
		if (!is_really_writable($files_path))
		{
			$this->template->set('messages', array('error' => sprintf(lang('files:unwritable'), $files_path)));
		}

		$this->template->build('admin/index');
	}

	/**
	 * Folder Sidebar
	 *
	 * @param array $folder The array of the folder structure.
	 * @param null|bool $is_root Due to the root folder not having the 'children' element.
	 *
	 * @return string
	 */
	public function sidebar($folder, $is_root = null)
	{

		if ($is_root || (isset($folder['children']) && is_array($folder['children'])))
		{
			$items = ($is_root) ? $folder : $folder['children'];
			$list_items = '';

			foreach ($items as $item)
			{
<<<<<<< HEAD
				$file = $this->upload->data();
				$file_name = pathinfo($this->input->post('name'));
				$data = array(
					'folder_id'		=> (int) $this->input->post('folder_id'),
					'user_id'		=> (int) $this->current_user->id,
					'type'			=> $this->_type,
					'name'			=> array_key_exists('extension', $file_name)
                                            ? basename($file_name['basename'],'.'.$file_name['extension'])
                                            : $file_name,
					'description'	=> $this->input->post('description') ? $this->input->post('description') : '',
					'filename'		=> $file['file_name'],
					'extension'		=> $file['file_ext'],
					'mimetype'		=> $file['file_type'],
					'filesize'		=> $file['file_size'],
					'width'			=> (int) $file['image_width'],
					'height'		=> (int) $file['image_height'],
					'date_added'	=> now()
				);

				// Insert success
				if ($id = $this->file_m->insert($data))
				{
					$status		= 'success';
					$message	= lang('files.create_success');
				}
				// Insert error
				else
				{
					$status		= 'error';
					$message	= lang('files.create_error');
				}
=======
				$list_items .= '<li class="folder" data-id="'.$item['id'].'" data-name="'.$item['name'].'">
					<div></div>
					<a href="#">'.$item['name'].'</a>';
>>>>>>> upstream/2.1/develop

				$children_items = $this->sidebar($item);
				if ( ! empty($children_items))
				{
					$list_items .= '<ul style="display:none" >'.$children_items.'</ul>';
				}

				$list_items .= '</li>';
			}

			return $list_items;
		}

		return '';
	}

	/**
	 * Create a new folder
	 *
	 * Grabs the parent id and the name of the folder from POST data.
	 */
	public function new_folder()
	{
		// This is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('create_folder', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		$parent_id = $this->input->post('parent');
		$name = $this->input->post('name');

		echo json_encode(Files::create_folder($parent_id, $name));
	}

	/**
	 * Get all files and folders within a folder
	 *
	 * Grabs the parent id from the POST data.
	 */
	public function folder_contents()
	{
		$parent = $this->input->post('parent');

		echo json_encode(Files::folder_contents($parent));
	}

	/**
	 * See if a container exists with that name.
	 *
	 * This is different than folder_exists() as this checks for unindexed containers.
	 * Grabs the name of the container and the location from the POST data.
	 */
	public function check_container()
	{
		$name = $this->input->post('name');
		$location = $this->input->post('location');

		echo json_encode(Files::check_container($name, $location));
	}

	/**
	 * Set the order of files and folders
	 */
	public function order()
	{

		if ($collection = $this->input->post('order'))
		{
			foreach ($collection as $type => $item)
			{
<<<<<<< HEAD
				// Setup upload config
				$this->load->library('upload', array(
					'upload_path'	=> $this->_path,
					'allowed_types'	=> $this->_ext
				));

				// File upload error
				if ( ! $this->upload->do_upload('userfile'))
				{
					$status		= 'error';
					$message	= $this->upload->display_errors();

					if ($this->input->is_ajax_request())
					{
						$data = array();
						$data['messages'][$status] = $message;
						$message = $this->load->view('admin/partials/notices', $data, TRUE);

						return $this->template->build_json(array(
							'status'	=> $status,
							'message'	=> $message
						));
					}

					$this->data->messages[$status] = $message;
				}
				// File upload success
				else
				{
					// Remove the original file
					$this->file_m->delete_file($id);

					$file = $this->upload->data();
					$file_name = pathinfo($this->input->post('name'));
					
					$data = array(
						'folder_id'		=> (int) $this->input->post('folder_id'),
						'user_id'		=> (int) $this->current_user->id,
						'type'			=> $this->_type,
						'name'			=> array_key_exists('extension', $file_name)
                                            ? basename($file_name['basename'],'.'.$file_name['extension'])
                                            : $file_name,
						'description'	=> $this->input->post('description'),
						'filename'		=> $file['file_name'],
						'extension'		=> $file['file_ext'],
						'mimetype'		=> $file['file_type'],
						'filesize'		=> $file['file_size'],
						'width'			=> (int) $file['image_width'],
						'height'		=> (int) $file['image_height'],
					);

					if ($this->file_m->update($id, $data))
					{
						$status		= 'success';
						$message	= lang('files.edit_success');
					}
					else
					{
						$status		= 'error';
						$message	= lang('files.edit_error');
					};

					if ($this->input->is_ajax_request())
					{
						$data = array();
						$data['messages'][$status] = $message;
						$message = $this->load->view('admin/partials/notices', $data, TRUE);

						return $this->template->build_json(array(
							'status'	=> $status,
							'message'	=> $message,
							'title'		=> $status === 'success' ? sprintf(lang('files.edit_title'), $this->input->post('name')) : $file->name
						));
					}

					if ($status === 'success')
					{
						$this->session->set_flashdata($status, $message);
						redirect ('admin/files');
					}
				}
			}

			// Upload data
			else
			{
				$file_name = pathinfo($this->input->post('name'));
				
				$data = array(
					'folder_id'		=> $this->input->post('folder_id'),
					'user_id'		=> $this->current_user->id,
					'name'			=> array_key_exists('extension', $file_name)
                                            ? basename($file_name['basename'],'.'.$file_name['extension'])
                                            : $file_name,
					'description'	=> $this->input->post('description')
				);

				if ($this->file_m->update($id, $data))
=======
				$i = 0;

				foreach ($item as $id) 
>>>>>>> upstream/2.1/develop
				{
					$model = ($type == 'folder') ? 'file_folders_m' : 'file_m';

					$this->{$model}->update_by('id', $id, array('sort' => $i));
					$i++;
				}
			}

			// let the files library format the return array like all the others
			echo json_encode(Files::result(TRUE, lang('files:sort_saved')));
		}
		else 
		{
			echo json_encode(Files::result(FALSE, lang('files:save_failed')));
		}
	}

	/**
	 * Rename a folder
	 */
	public function rename_folder()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('edit_folder', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id') AND $name = $this->input->post('name'))
		{
			echo json_encode(Files::rename_folder($id, $name));
		}
	}

	/**
	 * Delete an empty folder
	 */
	public function delete_folder()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('delete_folder', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id'))
		{
			echo json_encode(Files::delete_folder($id));
		}
	}

	/**
	 * Upload files
	 */
	public function upload()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('upload', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		$input = $this->input->post();

		if ($input['folder_id'] AND $input['name'])
		{
			echo json_encode(Files::upload($input['folder_id'], $input['name'], 'file', $input['width'], $input['height'], $input['ratio']));
		}
	}

	/**
	 * Rename a file
	 */
	public function rename_file()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('edit_file', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('file_id') AND $name = $this->input->post('name'))
		{
			echo json_encode(Files::move($id, $name));
		}
	}

	/**
	 * Edit description of a file
	 */
	public function save_description()
	{
		if ($id = $this->input->post('file_id') AND $description = $this->input->post('description'))
		{
			$this->file_m->update($id, array('description' => $description));

			echo json_encode(Files::result(TRUE, lang('files:description_saved')));
		}
	}

	/**
	 * Edit location of a folder (S3/Cloud Files/Local)
	 */
	public function save_location()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('set_location', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id') AND $location = $this->input->post('location') AND $container = $this->input->post('container'))
		{
			$this->file_folders_m->update($id, array('location' => $location));

			echo json_encode(Files::create_container($container, $location, $id));
		}
	}

	/**
	 * Pull new files down from the cloud location
	 */
	public function synchronize()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('synchronize', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id'))
		{
			echo json_encode(Files::synchronize($id));
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete a file
	 *
	 * @access	public
	 * @return	void
	 */
	public function delete_file()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('delete_file', Files::allowed_actions()))
		{
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('file_id'))
		{
			echo json_encode(Files::delete_file($id));
		}
	}

	/**
	 * Search for files and folders
	 */
	public function search()
	{
		echo json_encode(Files::search($this->input->post('search')));
	}

}
