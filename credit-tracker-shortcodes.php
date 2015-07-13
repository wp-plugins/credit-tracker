<?php
/**
 * Plugin shortcodes.
 *
 * @package   Credit_Tracker
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2013 Labs64
 */


// add shortcodes
add_shortcode('credit_tracker_table', 'credit_tracker_table_shortcode');
add_filter('img_caption_shortcode', 'credit_tracker_caption_shortcode_filter', 10, 3);

function credit_tracker_table_shortcode($atts)
{
    $columns_set_i18n = array(
        'ident_nr' => __('Ident-Nr.', CREDITTRACKER_SLUG),
        'author' => __('Author', CREDITTRACKER_SLUG),
        'publisher' => __('Publisher', CREDITTRACKER_SLUG),
        'copyright' => __('Copyright', CREDITTRACKER_SLUG),
        'license' => __('License', CREDITTRACKER_SLUG)
    );
    $columns_set = implode(",", array_keys($columns_set_i18n));

    extract(shortcode_atts(
            array(
                'id' => '',
                'size' => 'thumbnail',
                'style' => 'default',
                'include_columns' => $columns_set
            ), $atts)
    );
    if (empty($include_columns)) {
        $include_columns = $columns_set;
    }
    $columns = explode(",", $include_columns);
    foreach ($columns as $key => $value) {
        $columns[$key] = trim($columns[$key]);
    }

    if (is_numeric($size)) {
        $size = array($size, $size);
    } else if (stripos($size, 'x') !== false) {
        $size = explode('x', $size);
    }

    $request = array(
        'size' => $size,
        'include' => $id
    );
    $images = credittracker_get_images($request);

    $ret = '<table id="credit-tracker-table" class="credit-tracker-' . $style . '"><thead>';
    $ret .= '<th>' . '&nbsp;' . '</th>';

    foreach ($columns as $column) {
        if (!empty($column)) {
            $column_name = __($columns_set_i18n[$column], CREDITTRACKER_SLUG);
            if (empty($column_name)) {
                $column_name = $column;
            }
            $ret .= '<th>' . $column_name . '</th>';
        }
    }
    $ret .= '</thead><tbody>';

    if (empty($images)) {
        $ret .= '<tr class="credit-tracker-row"><td colspan="6" class="credit-tracker-column-empty">' . __('No images found', CREDITTRACKER_SLUG) . '</td></tr>';
    }

    foreach ($images as $image) {
        if (!empty($image['author'])) {
            $ct_copyright_format = credittracker_get_source_copyright($image['source']);
            if (empty($ct_copyright_format)) {
                $ct_copyright_format = credittracker_get_single_option('ct_copyright_format');
            }
            $ret .= '<tr>';
            $ret .= '<td>' . '<img width="' . $image['width'] . '" height="' . $image['height'] . '" src="' . $image['url'] . '" class="attachment-thumbnail" alt="' . $image['alt'] . '">' . '</td>';
            foreach ($columns as $column) {
                if (!empty($column)) {
                    if ($column != 'copyright') {
                        $ret .= '<td>' . $image[(string)$column] . '</td>';
                    } else {
                        $ret .= '<td>' . credittracker_process_item_copyright($image, $ct_copyright_format) . '</td>';
                    }
                }
            }
            $ret .= '</tr>';
        }
    }

    $ret .= '</tbody></table>';
    return $ret;

}

function credit_tracker_caption_shortcode_filter($val, $attr, $content = null)
{
    extract(shortcode_atts(
            array(
                'id' => '',
                'align' => 'aligncenter',
                'width' => '',
                'caption' => '',
                'text' => '',
                'type' => 'caption'
            ), $attr)
    );

    $ct_override_caption_shortcode = credittracker_get_single_option('ct_override_caption_shortcode');
    if ((bool)$ct_override_caption_shortcode) {

        $id_orig = $id;
        if ($id) {
            $id = esc_attr($id);
        }

        // extract attachment id
        preg_match("/\d+/", $id, $matches);
        if (!empty($matches)) {
            $id = $matches[0];
        }

        // find attachment
        $request = array(
            'size' => 'thumbnail',
            'include' => $id
        );
        $images = credittracker_get_images($request);
        if (empty($images)) {
            return $val;
        }
        $image = reset($images);

        $ct_copyright_format = credittracker_get_source_copyright($image['source']);
        if (empty($ct_copyright_format)) {
            $ct_copyright_format = credittracker_get_single_option('ct_copyright_format');
        }
        // override image caption via 'text' attribute
        if (!empty($text)) {
            $image['caption'] = $text;
        }
        $ct_copyright = htmlspecialchars_decode(credittracker_process_item_copyright($image, $ct_copyright_format));

        $content = str_replace('<img', '<img itemprop="contentUrl"', $content);

        $style = '';
        if ((int)$width > 0) {
            $style = 'style="width: ' . (int)$width . 'px"';
        }

        $ret = '<div id="' . $id_orig . '" class="wp-caption credit-tracker-caption ' . esc_attr($align) . '" itemscope itemtype="http://schema.org/ImageObject" ' . $style . '>';
        $ret .= do_shortcode($content);
        $ret .= '<p class="wp-caption-text" itemprop="copyrightHolder">' . $ct_copyright . '</p>';
        $ret .= '<meta itemprop="name" content="' . $image['title'] . '">';
        $ret .= '<meta itemprop="caption" content="' . $image['caption'] . '">';
        $ret .= '<meta itemprop="author" content="' . $image['author'] . '">';
        $ret .= '<meta itemprop="publisher" content="' . $image['publisher'] . '">';
        $ret .= '</div>';

        return $ret;
    } else {
        return $val;
    }
}

?>
