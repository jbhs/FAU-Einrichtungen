<?php
/*
* Gallery functions for theme, overwriting WordPress default gallery
* 
* @package WordPress
* @subpackage FAU
* @since FAU 1.10.54
*/


/*-----------------------------------------------------------------------------------*/
/* Change output for gallery
/*-----------------------------------------------------------------------------------*/

add_filter('post_gallery', 'fau_post_gallery', 10, 2);
if ( ! function_exists( 'fau_post_gallery' ) ) {
    function fau_post_gallery($output, $attr) {
	global $post;
	global $usejslibs;
	global $defaultoptions;


	if (isset($attr['orderby'])) {
	    $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
	    if (!$attr['orderby'])
		unset($attr['orderby']);
	}

	extract(shortcode_atts(array(
	    'order'	=> 'ASC',
	    'orderby'	=> 'menu_order ID',
	    'id'		=> $post->ID,
	    'columns'	=> 0,
	    'include'	=> '',
	    'exclude'	=> '',
	    'type'	=> 'default',
	    'captions'	=> 0,
	    'link'	=> 'file',
		// aus Wizard:
		// file = direkt zur mediendatei
		// none = nirgendwohin
		// NULL = anhang seite
	    'nodots'		=> 0,

	), $attr));

	$id = intval($id);
	if ('RAND' == $order) $orderby = 'none';

	if (!empty($include)) {
	    $include = preg_replace('/[^0-9,]+/', '', $include);
	    $_attachments = get_posts(array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));

	    $attachments = array();
	    foreach ($_attachments as $key => $val) {
		$attachments[$val->ID] = $_attachments[$key];
	    }
	}

	if (empty($attachments)) return '';

	$gridtype = 'default';

	$output = '<div class="image-gallery">';

	$attr['captions'] = filter_var( $attr['captions'], FILTER_VALIDATE_BOOLEAN );
	
	
	if (!isset($attr['type'])) {
	    $attr['type'] = 'default';
	}

	if ($attr['type'] != 'default') {

	    $gridclass = 'flexgrid';
	    $gridtype = 'grid';
	    if ($attr['columns']==2) {
		$gridclass = 'two';
	    } elseif ($attr['columns']==3) {
		$gridclass = 'three';
	    } elseif ($attr['columns']==4) {
		$gridclass = 'four';
	    } elseif ($attr['columns']==5) {
		$gridclass = 'five';
	
	    }
	    // Overwrite by type
	    if ($attr['type'] == '2cols') {
		$gridclass = 'two';
	    }
	    if ($attr['type'] == '4cols') {
		$gridclass = 'four';
	    }

	}


	if ($gridtype == 'grid') {
	    // Images in grid view

	    $rand = rand();
	    $usesameheight = '';

	    $output .= '<div class="grid">'."\n";

	    if ($gridclass=='flexgrid') {
		$output .= '<div class="flexgrid">'."\n";
	    } else {
		$output .= '<div class="'.$gridclass.'">'."\n";
		$output .= '<div class="row">'."\n";
	    }


	    $i = 0;


	    foreach ($attachments as $id => $attachment) {
		
		//    $img = wp_get_attachment_image_src($id,  'post-thumb');
		// use with small picture. This will make problems if images with
		// sizes like 2:5 are used.
		// so we make it in full and use css to size it.
		// srcset will reduce the data transfer
		
		    $imgsrcset =  wp_get_attachment_image_srcset($id, 'gallery-full');
		    $imgsrcsizes = wp_get_attachment_image_sizes($id, 'gallery-full');
		    $img = wp_get_attachment_image_src($id, 'gallery-full');
		    $imgmeta = fau_get_image_attributs($id);
		    $img_full = wp_get_attachment_image_src($id, 'full');
		    $lightboxattr = '';
		    $lightboxtitle = sanitize_text_field($meta->post_excerpt);
		    if (strlen(trim($lightboxtitle))>1) {
			$lightboxattr = ' title="'.$lightboxtitle.'"';
		    }

		    if(isset( $attr['captions']) && ($attr['captions']==1) &&(!fau_empty($imgmeta['excerpt']))) {
			$output .= '<figure class="with-caption">';
		    } else {
			$output .= '<figure>';
		    }
		    if ('none' !== $attr['link'] ) {
			$output .= '<a href="'.fau_esc_url($img_full[0]).'" class="lightbox" rel="lightbox-'.$rand.'"'.$lightboxattr.'>';		    
		    }

		    $output .= '<img src="'.fau_esc_url($img[0]).'" width="'.$img[1].'" height="'.$img[2].'" alt="'.$imgmeta['alt'].'"';
		    if ($imgsrcset) {
		    $output .= ' srcset="'.$imgsrcset.'"';
			if ($imgsrcsizes) {
			     $output .= ' sizes="'.$imgsrcsizes.'"';
			}
		    }
		    $output .= '>';
		     
		    if ('none' !== $attr['link'] ) {
			$output .= '</a>';
		    }

		    if(isset( $attr['captions']) && ($attr['captions']==1) && (!fau_empty($imgmeta['excerpt']))) {
			$output .= '<figcaption>'.$imgmeta['excerpt'].'</figcaption>';
		    }
		    $output .= '</figure>'."\n";
		    $i++;

		    if (($gridclass == 'two') && ($i % 2 == 0)) {
			$output .= '</div><div class="row">'."\n";  
		    } elseif (($gridclass == 'three') && ($i % 3 == 0)) {
			$output .= '</div><div class="row">'."\n";
		    } elseif (($gridclass == 'four') && ($i % 4 == 0)) {
			$output .= '</div><div class="row">'."\n";
		    }
	    }

	    $output .= '</div>'."\n";
	    if ($gridclass !='flexgrid') {
		$output .= '</div>'."\n";
	    }
	    $output .= '</div>'."\n";


	} else {
	    if ((!$attr['captions']) && (get_theme_mod('galery_force_caption_onslick'))) {
		$attr['captions'] = 1;
	    }
	    wp_enqueue_script('fau-js-heroslider');

	    $rand = rand();	    
	    $output .= "<div id=\"slider-$rand\" class=\"gallery-slick\" aria-hidden=\"true\" role=\"presentation\">\n";	
	    $output .= "<div class=\"slider-for-$rand\">\n";

	    foreach ($attachments as $id => $attachment) {
		$img = wp_get_attachment_image_src($id, 'gallery-full');
		$img_full = wp_get_attachment_image_src($id, 'full');
		$imgmeta = fau_get_image_attributs($id);
		$imgsrcset =  wp_get_attachment_image_srcset($id, 'gallery-full');
		$imgsrcsizes = wp_get_attachment_image_sizes($id, 'gallery-full');


		$output .= '<div class="item">';
		$output .= '<img src="'.fau_esc_url($img[0]).'" width="'.$img[1].'" height="'.$img[2].'" alt=""';

		if ($imgsrcset) {
		    $output .= ' srcset="'.$imgsrcset.'"';
		    if ($imgsrcsizes) {
			 $output .= ' sizes="'.$imgsrcsizes.'"';
		    }
		}
		$output .= ' role="presentation">';


		$link_origin = get_theme_mod('galery_link_original');
		
		if (($link_origin) || ($attr['captions'])) {
		    $output .= '<figcaption class="gallery-image-caption">';
			$lightboxattr = '';
			if (($attr['captions']) && (!fau_empty($imgmeta['excerpt']))) {
			    $output .= $imgmeta['excerpt']; 
			    $lightboxtitle = sanitize_text_field($imgmeta['excerpt']);
			    if (strlen(trim($lightboxtitle))>1) {
				$lightboxattr = ' title="'.$lightboxtitle.'"';
			    }
			}
			if (($link_origin) && ($attr['link'] != 'none')) {
			    if (!fau_empty($imgmeta['excerpt'])) { 
				$output .= '<br>'; 			
			    }
			    $output .= '<span class="linkorigin">(<a href="'.fau_esc_url($img_full[0]).'" '.$lightboxattr.' class="lightbox" rel="lightbox-'.$rand.'">'.__('Vergrößern','fau').'</a>)</span>';
			}
		    $output .='</figcaption>';
		}

		$output .='</div>';

	    }

	    $output .= "</div>\n";
	    $output .= "<div class=\"slider-nav-width slider-nav-$rand\">\n";


	    foreach ($attachments as $id => $attachment) {
		
		
		$output .= '<div>';
		$imgsrcset =  wp_get_attachment_image_srcset($id, $defaultoptions['default_rwdimage_typname']);
		$imgsrcsizes = wp_get_attachment_image_sizes($id, $defaultoptions['default_rwdimage_typname']);
		$img = wp_get_attachment_image_src($id, $defaultoptions['default_rwdimage_typname']);
		$imgmeta = fau_get_image_attributs($id);
		$alttext = sanitize_text_field($imgmeta['excerpt']);
		$output .= '<img src="'.fau_esc_url($img[0]).'" width="'.$img[1].'" height="'.$img[2].'" alt="'.$alttext.'"';
		if ($imgsrcset) {
		    $output .= ' srcset="'.$imgsrcset.'"';
		    if ($imgsrcsizes) {
			 $output .= ' sizes="'.$imgsrcsizes.'"';
		    }
		}
		$output .= '>';    	
		$output .= '</div>';
	    }

	    $output .= "</div>\n";	
	    $output .= "<script type=\"text/javascript\"> jQuery(document).ready(function($) {";	
	    $output .= "$('.slider-for-$rand').slick({slidesToShow: 1,slidesToScroll: 1, arrows: false, fade: true,adaptiveHeight: true,asNavFor: '.slider-nav-$rand' });";

	    $output .= "$('.slider-nav-$rand').slick({ slidesToShow: 4,  slidesToScroll: 1,   asNavFor: '.slider-for-$rand', centerMode: true,focusOnSelect: true, centerPadding: 5";
	    if ((isset($attr['nodots']) && $attr['nodots']==true)) {
		$output .= ", dots: false";
	    } else {
		$output .= ", dots: true";
	    }

	    $output .= ", responsive: [ 
	    {
		breakpoint: 920,
		settings: {
		  arrows: true,
		  slidesToShow: 3
		}
	      },
	    {
		breakpoint: 768,
		settings: {
		  arrows: true,
		  slidesToShow: 2
		}
	      },
	      {
		breakpoint: 480,
		settings: {
		  arrows: false,
		  slidesToShow: 1
		}
	      }
	    ]";
	    $output .= "});";
	    $output .= "});</script>";
	    $output .= "</div>";	

	}


	$output .= "</div>\n";
	return $output;
    }
}
/*-----------------------------------------------------------------------------------*/
/* EOF
/*-----------------------------------------------------------------------------------*/