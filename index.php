<?php
/*
 * Plugin Name:		Redirect By cookie
 * Description:		Redirect By cookie value
 * Text Domain:		redirect-by-cookie
 * Domain Path:		/languages
 * Version:		1.11
 * WordPress URI:	https://wordpress.org/plugins/htaccess-cookie-redirect/
 * Plugin URI:		https://puvox.software/software/wordpress-plugins/?plugin=redirect-by-cookie
 * Contributors: 	puvoxsoftware,ttodua
 * Author:		Puvox.software
 * Author URI:		https://puvox.software/
 * Donate Link:		https://paypal.me/Puvox
 * License:		GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.html
 
 * @copyright:		Puvox.software
*/


namespace RedirectByCookie
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library.php" );
  require_once( __DIR__."/library_wp.php" );
  
  class PluginClass extends \Puvox\wp_plugin
  {

	public function declare_settings()
	{
		$this->initial_static_options	= 
		[
			'has_pro_version'        => 0, 
            'show_opts'              => true, 
            'show_rating_message'    => true, 
            'show_donation_footer'   => true, 
            'show_donation_popup'    => true, 
            'menu_pages'             => [
                'first' =>[
                    'title'           => 'Redirect By cookie', 
                    'default_managed' => 'network',            // network | singlesite
                    'required_role'   => 'install_plugins',
                    'level'           => 'submenu', 
                    'page_title'      => 'Redirect By cookie',
                    'tabs'            => [],
                ],
            ]
		];

		$this->initial_user_options		= 
		[ 
			//'&raquo;';
			'enabled'			=> true,
			'cookies_all'		=> []
		];
	}


	public function __construct_my()
	{
		add_action('admin_init',	[$this, 'first_time_setups'],	22);
		$this->wp_config_addon_php 	= __DIR__.'/_wp_config_addon.php';
		$this->json_array_file	 	= __DIR__.'/redirects.json';
		$this->phrase_start	= '///////// REDIRECT_BY_COOKIE___PLUGIN___START (If you need to remove this, at first deactivate plugin, and then fully remove this block!)';	
		$this->phrase_end	= '///////// REDIRECT_BY_COOKIE___PLUGIN___END';
	}

	// ======================================================== //
	// ======================================================== //



	public function deactivation_funcs(){  
		$this->opts['enabled']=false;
		$this->update_opts();
		$this->update_config();
	}

	public function first_time_setups()
	{
		// ========= insert code in wp-config  =========// 
		$wp_config=ABSPATH.'wp-config.php';
		
		//if not yet included, let's include
		if(!defined("ewdfad_included"))
		{
			$wp_config_content=file_get_contents($wp_config);
			if( strpos($wp_config_content, $this->phrase_start) ===false )
			{
				$path = str_replace( $this->helpers->replace_slashes(ABSPATH), '', $this->helpers->replace_slashes( $this->wp_config_addon_php ));
				$inserting_code_block= 
				"\r\n".
				"\r\n".$this->phrase_start.
				"\r\n".'if(file_exists($a=__DIR__."/'. $path.'")){ include_once($a); }'.
				"\r\n".$this->phrase_end.
				"\r\n".
				"\r\n".
				"\r\n";

				$pattern= function($which){ return '/\bdefine\b(|\W+)\([\W]+'.$which.'(.*?)\;/i'; };
				
				$new_content= $wp_config_content;
				$new_content= preg_replace('/\/\* That\'s all, stop editing/i', $inserting_code_block.'$0', $new_content);
				copy($wp_config, ABSPATH .'wp-config_backup_by_RedirectByCookie_safe_to_delete_'.date('Y-m-d H-i-s').rand(1,99999).rand(1,99999).'.php');
				file_put_contents($wp_config, $new_content);
			}
		}
		// ===============================================//
	}
	
	public function update_config()
	{
		$home=  home_url();
		$pre_url = $this->helpers->normalize_with_slashes( $this->helpers->remove_https_www($home) );

		$array=[     'enabled'=>$this->opts['enabled'], 'cookies_all'=>$this->opts['cookies_all']     ]; 
		for ($i=0; $i<count($this->opts['cookies_all']['cookie_name']); $i++) 
		{ 
			$array['cookies_all']['redirect_from'][$i] =  $this->helpers->normalize_with_slashes( $pre_url . $array['cookies_all']['redirect_from'][$i] );
		} 
		file_put_contents($this->json_array_file, json_encode($array));
	}


	// =================================== Options page ================================ //
	public function opts_page_output()
	{
		$this->settings_page_part("start", 'first');
		?> 

		<style>
		p.submit { text-align:center; }
		.myplugin {padding:10px;}
		</style>
		
		<?php if ($this->active_tab=="Options") 
		{
			//if form updated
			if( $this->checkSubmission() ) 
			{
				$this->opts['enabled']			= !empty($_POST[ $this->plugin_slug ]['enabled']);
				//
				$this->opts['cookies_all']	= $this->array_map_deep('sanitize_text_field', $_POST[ $this->plugin_slug ]['cookies_all']);
				$this->update_config();
				$this->update_opts(); 
			}
			?> 

			<form class="mainForm" method="post" action="">

			<h2><?php _e('Plugin redirects visitor who visit the homepage of site to specific destination.', 'redirect-by-cookie');?></h2>
			
			<div class="description"><?php echo sprintf(__('Note, we created this plugin just for our specific needs and is not as multifunctional as other plugins ( like %s ), so you want more capabilities, switch to them. (Just we don\'t know the performance differences)', 'redirect-by-cookie'), '<a href="https://wordpress.org/plugins/redirection/" target="_blank">Redirection</a>') ;?></div>
			<table class="form-table">
			  <tbody id="cookie_settings">
				<tr class="def">
					<th scope="row">
						<label for="enabled">
							<?php _e('Enable/Disable functionality of plugin', 'redirect-by-cookie');?>
						</label>
					</th>
					<td>
						<input id="enabled" name="<?php echo $this->plugin_slug;?>[enabled]" type="checkbox" value="1" <?php checked($this->opts['enabled']); ?>  />  
					</td>
					<td>
						
					</td>
				</tr>
				<tr class="def">
					<th scope="row">
						<label for="">
							<?php _e('Using method:', 'redirect-by-cookie');?>
						</label>
					</th>
					<td>
						<input disabled type="radio" value="wp_config" checked  /><?php _e('config', 'redirect-by-cookie');?> <input disabled type="radio" value="htaccess" /> <?php _e('.htaccess (not available in this plugin)', 'redirect-by-cookie');?> 
					</td>
					<td>
						
					</td>
					<td>
						
					</td>
				</tr> 
				<?php for ($i=0; $i< $count = ((isset($this->opts['cookies_all']['cookie_name']))  ? count( $this->opts['cookies_all']['cookie_name']) : 0)+1; $i++) { 
					if ($i != $count-1 && empty($this->opts['cookies_all']['cookie_name'][$i]) ) continue; ?>
				<tr class="def_cookie">
					<th scope="row">
						<label for="cookie_name">
							<?php _e('Cookie name', 'redirect-by-cookie');?>
						</label>
						<br/><input id="cookie_name" name="<?php echo $this->plugin_slug;?>[cookies_all][cookie_name][]" class="small-text" type="text" value="<?php echo htmlentities( ( isset($this->opts['cookies_all']['cookie_name'][$i]) ? $this->opts['cookies_all']['cookie_name'][$i] : '') ); ?>"  placeholder="" />
					</th>
					<td>
						<label for="redirect_from">
							<?php _e('Redirect when visiting path:', 'redirect-by-cookie');?>
						</label>
						<br/><input id="redirect_from" name="<?php echo $this->plugin_slug;?>[cookies_all][redirect_from][]" class="text" type="text" value="<?php echo htmlentities( ( isset($this->opts['cookies_all']['redirect_from'][$i]) ? $this->opts['cookies_all']['redirect_from'][$i] : '') ); ?>"  placeholder="" />
						<p class="description"><?php _e('When user visits what path (relative from homepage)?  default is blank, meaning it\'s homepage.', 'redirect-by-cookie');?></p>
					</td>
					<td>
						<label for="redirect_to">
							<?php _e('Redirect to', 'redirect-by-cookie');?>
						</label>
						<br/><input id="redirect_to" name="<?php echo $this->plugin_slug;?>[cookies_all][redirect_to][]" class="text" type="text" value="<?php echo htmlentities( ( isset($this->opts['cookies_all']['redirect_to'][$i]) ? $this->opts['cookies_all']['redirect_to'][$i] : '') ); ?>"  placeholder="" />
						<p class="description"><?php _e('If that cookie is set, then where the visitor will be redirected? If you will leave empty, the redirection target will be same as cookie value (mostly useful for "lang" cookies (to redirect to i.e. "<code>eng</code>" or etc...)', 'redirect-by-cookie');?></p>
					</td>
					<td>
						<label for="autoset_empty_to">
							<?php _e('Auto-set if that cookie is not set', 'redirect-by-cookie');?>
						</label>
						<br/><input id="autoset_empty_to" name="<?php echo $this->plugin_slug;?>[cookies_all][autoset_empty_to][]" class="text" type="text" value="<?php echo htmlentities( ( isset($this->opts['cookies_all']['autoset_empty_to'][$i]) ? $this->opts['cookies_all']['autoset_empty_to'][$i] : '') ); ?>"  placeholder="" />
						<p class="description"><?php _e('If that cookie was not set, do you want that to autoset to specific value?', 'redirect-by-cookie');?></p>
					</td>
				</tr>
				<?php } ?>
			  </tbody>
			</table>
			<button onclick="event.preventDefault(); copyRow();">Add Row</button>
			<script>
			function copyRow(){
				var row = document.getElementsByClassName("def_cookie")[0].cloneNode(true);
				document.getElementById("cookie_settings").appendChild(row);
				return false;
			}
			</script>
			<?php $this->nonceSubmit(); ?>

			</form>

		<?php 
		} 
		
		
		$this->settings_page_part("end", '');
	} 



  } // End Of Class

  $GLOBALS[__NAMESPACE__] = new PluginClass();

} // End Of NameSpace

?>