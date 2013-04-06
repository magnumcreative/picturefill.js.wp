<?php
/*
Plugin Name: Picturefill.WP
Plugin URI: http://github.com/kylereicks/picturefill.js.wp
Description: A wordpress plugin to load images via picturefill.js.
Author: Kyle Reicks
Version: 0.1
Author URI: http://kylereicks.me
*/

if(!class_exists('Picturefill_WP')){
  class Picturefill_WP{

    function __construct(){
      require_once(ABSPATH . 'wp-admin/includes/image.php');
      add_action('wp_enqueue_scripts', array($this, 'picturefill_scripts'));
      add_action('init', array($this, 'add_image_sizes'));
      add_filter('the_content', array($this, 'replace_images'), 11);
    }

    function picturefill_scripts(){
      wp_register_script('picturefill', plugins_url('js/libs/picturefill.js', __FILE__), array(), false, true);
    }

    function replace_images($html){
      global $_wp_additional_image_sizes;
      $content = new DOMDocument();
      $content->loadHTML($html);
      $images = $content->getElementsByTagName('img');
      if($images->length > 0){
        wp_enqueue_script('picturefill');
        foreach($images as $image){
          $original_image = $content->saveHTML($image);
          $original_image = substr($original_image, 0, strlen($original_image) - 1) . ' />';
          $src = $image->getAttribute('src');
          $alt = $image->getAttribute('alt');
          $title = $image->getAttribute('title');
          $class = $image->getAttribute('class');
          $id = $image->getAttribute('id');
          $width = $image->getAttribute('width');
          $height = $image->getAttribute('height');

          preg_match('/(?:size-)(\w+)/', $class, $size);
          preg_match('/(?:wp-image-)(\w+)/', $class, $attachment_id);

          $picture = '<span data-picture';
          $picture .= !empty($id) ? ' data-id="' . $id . '"' : '';
          $picture .= !empty($class) ? ' data-class="' . $class . '"' : '';
          $picture .= !empty($alt) ? ' data-alt="' . $alt . '"' : '';
          $picture .= !empty($title) ? ' data-title="' . $title . '"' : '';
          $picture .= !empty($width) ? ' data-width="' . $width . '"' : '';
          $picture .= !empty($height) ? ' data-height="' . $height . '"' : '';
          $picture .= '>';

          if(!empty($size) && !empty($attachment_id)){
            $image_attachment_data = $this->image_attachment_data($attachment_id[1]);

            if($size[1] === 'full' || $size[1] === 'large' || $size[1] === 'medium' || $size[1] === 'thumbnail'){
              $picture .= '<span data-src="' . $image_attachment_data['thumbnail'][0] . '" data-width="' . $image_attachment_data['thumbnail'][1] . '" data-height="' . $image_attachment_data['thumbnail'][2] . '"></span>';
              $picture .= '<span data-src="' . $image_attachment_data['thumbnail@2x'][0] . '" data-width="' . $image_attachment_data['thumbnail'][1] . '" data-height="' . $image_attachment_data['thumbnail'][2] . '" data-media="(-webkit-min-device-pixel-ratio: 1.5),(min-resolution: 144dpi),(min-resolution: 1.5dppx)"></span>';
            }
            if($size[1] === 'full' || $size[1] === 'large' || $size[1] === 'medium'){
              $breakpoint = $image_attachment_data['medium'][1] + 20;
              $picture .= '<span data-src="' . $image_attachment_data['medium'][0] . '" data-width="' . $image_attachment_data['medium'][1] . '" data-height="' . $image_attachment_data['medium'][2] . '" data-media="(min-width: ' . $breakpoint . 'px)"></span>';
              $picture .= '<span data-src="' . $image_attachment_data['medium@2x'][0] . '" data-width="' . $image_attachment_data['medium'][1] . '" data-height="' . $image_attachment_data['medium'][2] . '" data-media="(min-width: ' . $breakpoint . 'px) and (-webkit-min-device-pixel-ratio: 1.5),(min-resolution: 144dpi),(min-resolution: 1.5dppx)"></span>';
            }
            if($size[1] === 'full' || $size[1] === 'large'){
              $breakpoint = $image_attachment_data['large'][1] + 20;
              $picture .= '<span data-src="' . $image_attachment_data['large'][0] . '" data-width="' . $image_attachment_data['large'][1] . '" data-height="' . $image_attachment_data['large'][2] . '" data-media="(min-width: ' . $breakpoint . 'px)"></span>';
              $picture .= '<span data-src="' . $image_attachment_data['large@2x'][0] . '" data-width="' . $image_attachment_data['large'][1] . '" data-height="' . $image_attachment_data['large'][2] . '" data-media="(min-width: ' . $breakpoint . 'px) and (-webkit-min-device-pixel-ratio: 1.5),(min-resolution: 144dpi),(min-resolution: 1.5dppx)"></span>';
            }
            if($size[1] === 'full'){
              $picture .= '<span data-src="' . $src . '" data-width="' . $image_attachment_data['full'][1] . '" data-height="' . $image_attachment_data['full'][2] . '" data-media="(min-width: ' . $width . 'px)"></span>';
            }
          }

          if(empty($size)){
            $picture .= '<span data-src="' . $src . '"></span>';
          }

          $picture .= '<!--[if (lt IE 9) & (!IEMobile)]><span data-src="' . $src . '"></span><![endif]-->';

          $picture .= '<noscript>' . $original_image . '</noscript>';
          $picture .= '</span>';

          $html = str_replace($original_image, $picture, $html);
        }
      }
      return $html;
    }

    private function image_attachment_data($attachment_id){
      $image_attachment_data = array(
        'full' => wp_get_attachment_image_src($attachment_id, 'full'),
        'thumbnail' => wp_get_attachment_image_src($attachment_id, 'thumbnail'),
        'thumbnail2x' => wp_get_attachment_image_src($attachment_id, 'thumbnail@2x'),
        'medium' => wp_get_attachment_image_src($attachment_id, 'medium'),
        'medium@2x' => wp_get_attachment_image_src($attachment_id, 'medium@2x'),
        'large' => wp_get_attachment_image_src($attachment_id, 'large'),
        'large@2x' => wp_get_attachment_image_src($attachment_id, 'large@2x')
      );

      foreach($image_attachment_data as $attachment_size => $attachment_data){
        if($image_attachment_data['full'][0] === $attachment_data[0] && $image_attachment_data['full'][1] > $attachment_data[1] && $image_attachment_data['full'][2] > $image_attachment_data[2]){
          $new_meta_data = wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id));
          wp_update_attachment_metadata($attachment_id, $new_meta_data);
          $image_attachment_data[$attachment_size] = wp_get_attachment_image_src($attachment_id, $attachment_size);
        }
      }

      return $image_attachment_data;
    }

    function add_image_sizes(){
      add_image_size('thumbnailx2', get_option('thumbnail_size_w') * 2, get_option('thumbnail_size_h') * 2, get_option('thumbnail_crop'));
      add_image_size('mediumx2', get_option('medium_size_w') * 2, get_option('medium_size_h') * 2, get_option('medium_crop'));
      add_image_size('largex2', get_option('large_size_w') * 2, get_option('large_size_h') * 2, get_option('large_crop'));
    }
  }
  $picturefill_wp = new Picturefill_WP();
}
