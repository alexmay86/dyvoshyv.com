<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

abstract class Table extends \WP_List_Table
{
    public function display()
    {
        $singular = $this->_args['singular'];

        $this->screen->render_screen_reader_content('heading_list');
        ?>
        <form hidden method="post"></form>
        <table class="wp-list-table <?php echo implode(' ', $this->get_table_classes()); ?>" style="margin-top: 10px">
            <thead>
            <tr><?php $this->print_column_headers(); ?></tr>
            </thead>
            <tbody id="the-list"<?php echo $singular ? " data-wp-lists='list:$singular'" : ''?>>
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
            <tfoot>
            <tr></tr>
            </tfoot>
        </table>
        <?php
        $this->display_tablenav('bottom');
    }

    protected function getUser($data): string
    {
        $data = json_decode($data, true);
        $str = '<strong>'.$data['last_name'].' '.$data['first_name'].(!empty($data['middle_name']) ? ' '.$data['middle_name'] : '').'</strong><br>';
        $str .= '<strong>'.$data['phone'].'</strong><br>';
        $str .= '<small>'.$data['country']['text'].', '
            .(!empty($data['postcode']) ? $data['postcode'].', ' : '')
            .(!empty($data['region']['text']) ? $data['region']['text'].', ' : '')
            .(!empty($data['city']['text']) ? $data['city']['text'].', ' : '');
        if (isset($data['branch'])) {
            $str .= __('branch') .' '. $data['branch']['text'];
        } else {
            $str .= $data['street']['text'].' '.$data['building']
                .(!empty($data['flat']) ? '/'.$data['flat'] : '');
        }

        return $str.'<small>';
    }

    public function search_box($text, $inputId, $placeholder = null) {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $inputId = $inputId . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '"/>';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '"/>';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($inputId); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr($inputId); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php echo esc_attr($placeholder) ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }
}
