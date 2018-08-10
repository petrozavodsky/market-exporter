<?php

class Market_Exporter_Exclude_Meta_Box
{

    public static $field_exclude_from_price_list = '_market_exporter_exclude';

    public static $post_types;

    /**
     * Initial payload
     *
     * @author Vladimir Rambo Petrozavodsky
     */
    public static function run()
    {
        self::$post_types = apply_filters('market_exporter_helper_post_types', ['product']);
        self::filter_helper();
        self::ui_helper();

        add_filter('market_exporter_exclude_post', [__CLASS__, 'exclude_filter'], 10, 2);
    }

    /**
     * Exclude posts method initial
     */
    public static function filter_helper()
    {
        // TODO see market-exporter/admin/class-market-exporter-wc.php
        add_filter('market_exporter_exclude_post', [__CLASS__, 'exclude_filter'], 10, 2);
    }

    /**
     * Filter call back method.
     *
     * @param WP_Post $post The post object.
     * @return bool
     */
    public static function exclude_filter($value, $post)
    {
        if (in_array($post->post_type, self::$post_types)) {
            $meta_switcher = get_post_meta($post->ID, self::$field_exclude_from_price_list, true);

            if ($meta_switcher) {
                //TODO true == 'exclude product'
                return true;
            }
        }
        return $value;
    }

    /**
     * Initial exclude post
     */
    public static function ui_helper()
    {
        add_action('add_meta_boxes', [__CLASS__, 'fields'], 1);
        add_action('save_post', [__CLASS__, 'save_fields'], 0);
    }

    /**
     * Save fields in product meta data
     *
     * @param $post_id
     * @return integer
     *
     * @author Vladimir Rambo Petrozavodsky
     */
    public static function save_fields($post_id)
    {
        if (!isset($_POST['additional_fields_nonce']) || !wp_verify_nonce($_POST['additional_fields_nonce'],
                __FILE__)) {
            return false;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        if (!current_user_can('edit_post', $post_id) || !isset($_POST['additional'])) {
            return false;
        }
        $_POST['additional'] = array_map('trim', $_POST['additional']);

        foreach ($_POST['additional'] as $key => $value) {
            if (empty($value)) {
                delete_post_meta($post_id, $key);
                continue;
            }
            update_post_meta(
                $post_id,
                $key,
                self::save_fields_prepare($value, $key)
            );
        }

        return $post_id;
    }

    /**
     * Sanitise data method
     *
     * @param mixed $value
     * @param string $key
     * @return integer|bool
     *
     * @author Vladimir Rambo Petrozavodsky
     */
    public static function save_fields_prepare($value, $key = '')
    {
        // TODO For future
        return (int)$value;
    }


    /**
     * Register meat box
     *
     * @author Vladimir Rambo Petrozavodsky
     */
    public static function fields()
    {
        add_meta_box(
            'market-exporter-helper-exclude-product',
            __('Yandex market settings', 'market-exporter'),
            [__CLASS__, 'fields_html'],
            [
                self::$post_types
            ],
            'side',
            'low'
        );
    }

    /**
     * Meta box template
     *
     * @param WP_Post $post The post object.
     * @return void
     *
     * @author Vladimir Rambo Petrozavodsky
     */
    public static function fields_html($post)
    {

        $data = get_post_meta($post->ID, self::$field_exclude_from_price_list, true);

        if (empty($data)) {
            $data = '0';
        }

        ?>
        <p class="market-exporter-helper__uploader-field">
            <input type="checkbox"
                   id="<?php echo self::$field_exclude_from_price_list; ?>"
                   name="additional[<?php echo self::$field_exclude_from_price_list; ?>]"
                   value="1"
                <?php checked($data, '1'); ?>
            />
            <label for="<?php echo self::$field_exclude_from_price_list; ?>">
                <?php _e('Exclude from price YML file', 'market-exporter'); ?>
            </label>
        </p>


        <input type="hidden" name="additional_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>"/>
        <?php
    }

}